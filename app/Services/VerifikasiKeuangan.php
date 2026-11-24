<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\Ump;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class VerifikasiKeuangan
{
    public const TYPES = ['permohonan', 'pencairan', 'ump'];

    public function documents(string $type): Builder
    {
        return match ($type) {
            'permohonan' => Permohonan::query(),
            'pencairan' => Pencairan::query(),
            'ump' => Ump::query(),
            default => abort(404),
        };
    }

    public function active(Builder $query, string $type): Builder
    {
        return match ($type) {
            'permohonan' => $query->where(fn ($request) => $request
                ->whereIn('status', ['disetujui_keuangan', 'dicairkan'])
                ->orWhere(fn ($waiting) => $waiting->where('status', 'diketahui_kadiv')
                    ->where(fn ($level) => $level->whereHas('levelingRequired', fn ($snapshot) => $snapshot->where('urutan', '>=', 2))
                        ->orWhere(fn ($legacy) => $legacy->whereDoesntHave('levelingRequired')
                            ->whereHas('user.leveling', fn ($submitter) => $submitter->where('urutan', '>=', 2)))))),
            'pencairan' => $query->where('status', 'dicairkan')
                ->whereHas('permohonan', fn ($parent) => $parent->whereNotIn('status', ['selesai', 'ditolak', 'dibatalkan']))
                ->whereDoesntHave('ump', fn ($report) => $report->whereIn('status', ['selesai', 'dibatalkan'])),
            'ump' => $query->whereIn('status', ['ttd_pj', 'ttd_manager_keuangan', 'ttd_kepala_keuangan'])
                ->whereHas('pencairan', fn ($payout) => $payout->where('status', 'dicairkan')),
            default => abort(404),
        };
    }

    public function isActive(string $type, int $id): bool
    {
        return $this->active($this->documents($type), $type)->whereKey($id)->exists();
    }

    public function pending(string $type): Builder
    {
        return $this->active($this->documents($type), $type)
            ->whereNull('verifikasi_keuangan_at')->whereNull('verifikasi_keuangan_catatan');
    }

    public function sourceUrl(string $type, int $id): string
    {
        return match ($type) {
            'permohonan' => route('permohonan.detail', $id),
            'pencairan' => route('pencairan.index', ['ppd' => $id]),
            'ump' => route('ump.detail', $id),
            default => abort(404),
        };
    }

    /** @return list<int> */
    private function responsibleIds(Permohonan|Pencairan|Ump $document): array
    {
        $ids = match (true) {
            $document instanceof Permohonan => [$document->user_id, $document->pengusul_id],
            $document instanceof Pencairan => [$document->dicairkan_oleh],
            $document instanceof Ump => [$document->pj_id, $document->pencairan?->dicairkan_oleh],
        };

        return array_values(array_filter($ids));
    }

    public function isResponsible(Permohonan|Pencairan|Ump $document, User $user): bool
    {
        return $user->is_active && in_array($user->id, $this->responsibleIds($document), true);
    }

    public function mayReview(Permohonan|Pencairan|Ump $document, User $user): bool
    {
        return $user->is_active && Gate::forUser($user)->allows('verifikasi-keuangan')
            && ! $this->isResponsible($document, $user)
            && (! $document instanceof Pencairan || $document->dicairkan_oleh !== null);
    }

    public function decide(string $type, int $id, User $user, string $decision, ?string $note = null): void
    {
        DB::transaction(function () use ($type, $id, $user, $decision, $note): void {
            $document = $this->documents($type)->lockForUpdate()->findOrFail($id);
            abort_unless($this->mayReview($document, $user), 403);
            $isVerified = $document->verifikasi_keuangan_at !== null;
            $isReturned = ! $isVerified && $document->verifikasi_keuangan_catatan !== null;
            if ($isReturned || $isVerified !== ($decision === 'unverified')) {
                throw ValidationException::withMessages(['catatan' => 'Status verifikasi sudah berubah. Muat ulang antrean dan coba lagi.']);
            }
            $this->ensureActive($type, $id, 'catatan');
            $note = trim($note ?? '');
            Validator::make(['catatan' => $note], [
                'catatan' => [$decision === 'returned' ? 'required' : 'nullable', 'string', 'max:1000'],
            ], ['catatan.required' => 'Alasan perbaikan wajib diisi.'])->validate();
            $note = $note === '' ? null : $note;

            $audit = $this->record($document, $decision, [
                'verifikasi_keuangan_at' => $decision === 'verified' ? now() : null,
                'verifikasi_keuangan_oleh' => $decision === 'unverified' ? null : $user->id,
                'verifikasi_keuangan_catatan' => $decision === 'unverified' ? null : $note,
            ]);

            if ($decision === 'returned') {
                foreach (User::whereKey($this->responsibleIds($document))->where('is_active', true)->get() as $recipient) {
                    NotificationService::kirim($recipient, 'dokumen_diputuskan', [
                        'judul' => 'Kelengkapan Dokumen Perlu Perbaikan',
                        'pesan' => $note,
                        'url' => $this->sourceUrl($type, $id),
                        'jenis' => $type,
                        'entity_id' => $id,
                    ], "verifikasi_keuangan_perbaikan_{$audit->id}");
                }
            }
        });
    }

    public function resubmit(string $type, int $id, User $user, ?UploadedFile $attachment): void
    {
        $path = null;

        try {
            DB::transaction(function () use ($type, $id, $user, $attachment, &$path): void {
                $document = $this->documents($type)->lockForUpdate()->findOrFail($id);
                abort_unless($this->isResponsible($document, $user), 403);
                if ($document->verifikasi_keuangan_at !== null || $document->verifikasi_keuangan_catatan === null) {
                    throw ValidationException::withMessages(['bukti_koreksi' => 'Dokumen ini tidak sedang menunggu perbaikan kelengkapan.']);
                }
                $this->ensureActive($type, $id, 'bukti_koreksi');

                if ($attachment !== null) {
                    $path = $attachment->store('lampiran/verifikasi-keuangan', 'local');
                    if ($path === false) {
                        throw new RuntimeException('Bukti perbaikan gagal disimpan.');
                    }

                    $document->lampiran()->create([
                        'jenis' => 'bukti',
                        'path' => $path,
                        'original_name' => $attachment->getClientOriginalName(),
                        'mime' => $attachment->getMimeType(),
                        'size' => $attachment->getSize(),
                        'sha256' => hash_file('sha256', $attachment->getRealPath()),
                        'uploaded_by' => $user->id,
                    ]);
                }

                $this->record($document, 'resubmitted', [
                    'verifikasi_keuangan_at' => null,
                    'verifikasi_keuangan_oleh' => null,
                    'verifikasi_keuangan_catatan' => null,
                ]);
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    private function ensureActive(string $type, int $id, string $errorField): void
    {
        if (! $this->isActive($type, $id)) {
            throw ValidationException::withMessages([$errorField => 'Dokumen tidak berada pada tahap verifikasi kelengkapan.']);
        }
    }

    /** @param array{verifikasi_keuangan_at: Carbon|null, verifikasi_keuangan_oleh: int|null, verifikasi_keuangan_catatan: string|null} $changes */
    private function record(Permohonan|Pencairan|Ump $document, string $action, array $changes): AuditLog
    {
        $keys = array_keys($changes);
        $before = $document->only($keys);
        $document->forceFill($changes)->save();

        return (new AuditLogger)->log('verifikasi_keuangan.'.$action, $document, $before, $document->only($keys));
    }
}
