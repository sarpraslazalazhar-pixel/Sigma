<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Ump;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VerifikasiKeuangan
{
    public function documents(): Builder
    {
        return Ump::query();
    }

    public function active(?Builder $query = null): Builder
    {
        return ($query ?? $this->documents())
            ->whereIn('status', ['ttd_manager_keuangan', 'ttd_kepala_keuangan'])
            ->whereHas('pencairan', fn (Builder $payout) => $payout->where('status', 'dicairkan'));
    }

    public function isActive(int|Ump $ump): bool
    {
        $id = $ump instanceof Ump ? $ump->id : $ump;

        return $this->active()->whereKey($id)->exists();
    }

    public function pending(?Builder $query = null): Builder
    {
        return $this->active($query)->whereNull('verifikasi_keuangan_at');
    }

    public function awaitingApproval(?Builder $query = null): Builder
    {
        return $this->active($query)->whereNotNull('verifikasi_keuangan_at');
    }

    public function returned(?Builder $query = null): Builder
    {
        return ($query ?? $this->documents())
            ->where('status', 'perlu_perbaikan')
            ->whereNull('verifikasi_keuangan_at')
            ->whereNotNull('verifikasi_keuangan_catatan');
    }

    public function sourceUrl(int|Ump $ump): string
    {
        $id = $ump instanceof Ump ? $ump->id : $ump;

        return route('ump.detail', $id);
    }

    public function isResponsible(Ump $ump, User $user): bool
    {
        return $user->is_active
            && $ump->pj_id !== null
            && (int) $ump->pj_id === (int) $user->id;
    }

    public function mayReview(Ump $ump, User $user): bool
    {
        return $user->is_active
            && Gate::forUser($user)->allows('verifikasi-keuangan')
            && ! $this->isResponsible($ump, $user);
    }

    public function decide(int $id, User $user, string $decision, ?string $note = null): void
    {
        if (! in_array($decision, ['verified', 'returned', 'unverified'], true)) {
            throw ValidationException::withMessages(['catatan' => 'Keputusan verifikasi tidak valid.']);
        }

        DB::transaction(function () use ($id, $user, $decision, $note): void {
            $ump = Ump::with(['pencairan.permohonan', 'pj'])->lockForUpdate()->findOrFail($id);
            abort_unless($this->mayReview($ump, $user), 403);

            if (! $this->isActive($ump->id)) {
                throw ValidationException::withMessages([
                    'catatan' => 'Laporan tidak berada pada tahap Verifikasi Keuangan.',
                ]);
            }

            $isVerified = $ump->verifikasi_keuangan_at !== null;
            if ($decision === 'unverified') {
                if (! $isVerified || $ump->status !== 'ttd_manager_keuangan') {
                    throw ValidationException::withMessages([
                        'catatan' => 'Verifikasi hanya dapat dibatalkan sebelum disetujui Manager Keuangan.',
                    ]);
                }
            } elseif ($isVerified) {
                throw ValidationException::withMessages([
                    'catatan' => 'Status verifikasi sudah berubah. Muat ulang antrean dan coba lagi.',
                ]);
            }

            $note = trim($note ?? '');
            Validator::make(['catatan' => $note], [
                'catatan' => $decision === 'returned'
                    ? ['required', 'string', 'min:5', 'max:1000']
                    : ['nullable', 'string', 'max:1000'],
            ], [
                'catatan.required' => 'Alasan perbaikan wajib diisi.',
                'catatan.min' => 'Alasan perbaikan minimal 5 karakter.',
                'catatan.max' => 'Catatan maksimal 1000 karakter.',
            ])->validate();
            $note = $note === '' ? null : $note;

            if ($decision === 'verified') {
                $audit = $this->record($ump, 'verified', [
                    'verifikasi_keuangan_at' => now(),
                    'verifikasi_keuangan_oleh' => $user->id,
                    'verifikasi_keuangan_catatan' => $note,
                ]);

                $isKepalaStage = $ump->status === 'ttd_kepala_keuangan';
                $approvers = User::where('is_active', true)
                    ->where('role', 'keuangan')
                    ->whereHas('leveling', fn (Builder $q) => $isKepalaStage ? $q->where('urutan', '>=', 3) : $q->where('urutan', 2))
                    ->get();

                if ($approvers->isEmpty()) {
                    $approvers = User::where('is_active', true)
                        ->where('role', 'keuangan')
                        ->get();
                }

                $targetRoleLabel = $isKepalaStage ? 'Kepala Divisi Keuangan' : 'Manager Keuangan';
                foreach ($approvers as $approver) {
                    NotificationService::kirim($approver, 'dokumen_menunggu_tindakan', [
                        'judul' => 'Laporan Lolos Verifikasi Keuangan',
                        'no_ppd' => $ump->pencairan?->no_ppd,
                        'jenis' => 'ump',
                        'entity_id' => $ump->id,
                        'url' => $this->sourceUrl($ump->id),
                        'pesan' => "Laporan No. PPD {$ump->pencairan?->no_ppd} telah lolos Verifikasi Keuangan dan menunggu persetujuan {$targetRoleLabel}.",
                    ], "tunggu_ump_{$ump->id}_{$ump->status}_verified_{$audit->id}");
                }

                return;
            }

            if ($decision === 'returned') {
                $siklus = max(1, (int) $ump->approvals()->max('siklus'));

                $audit = $this->record($ump, 'returned', [
                    'status' => 'perlu_perbaikan',
                    'verifikasi_keuangan_at' => null,
                    'verifikasi_keuangan_oleh' => $user->id,
                    'verifikasi_keuangan_catatan' => $note,
                ]);

                Approval::create([
                    'approvable_type' => Ump::class,
                    'approvable_id' => $ump->id,
                    'siklus' => $siklus,
                    'tahap' => 'verifikasi_keuangan',
                    'keputusan' => 'perlu_perbaikan',
                    'approver_id' => $user->id,
                    'signer_name' => $user->name,
                    'signer_jabatan' => $user->jabatan,
                    'signed_at' => now(),
                    'recorded_by' => Auth::id() ?? $user->id,
                    'catatan' => $note,
                ]);

                if ($ump->pj && $ump->pj->is_active) {
                    NotificationService::kirim($ump->pj, 'dokumen_diputuskan', [
                        'judul' => 'Kelengkapan Laporan Perlu Perbaikan',
                        'no_ppd' => $ump->pencairan?->no_ppd,
                        'pesan' => "Laporan No. PPD {$ump->pencairan?->no_ppd} dikembalikan Verifikasi Keuangan: {$note}",
                        'url' => $this->sourceUrl($ump->id),
                        'jenis' => 'ump',
                        'entity_id' => $ump->id,
                        'keputusan' => 'perlu_perbaikan',
                    ], "verifikasi_keuangan_perbaikan_{$audit->id}");
                }

                return;
            }

            $this->record($ump, 'unverified', [
                'verifikasi_keuangan_at' => null,
                'verifikasi_keuangan_oleh' => null,
                'verifikasi_keuangan_catatan' => null,
            ]);
        });
    }

    /**
     * @param  array{status?: string, verifikasi_keuangan_at: Carbon|null, verifikasi_keuangan_oleh: int|null, verifikasi_keuangan_catatan: string|null}  $changes
     */
    private function record(Ump $ump, string $action, array $changes): AuditLog
    {
        $keys = array_keys($changes);
        $before = $ump->only($keys);
        $ump->forceFill($changes)->save();

        return (new AuditLogger)->log('verifikasi_keuangan.'.$action, $ump, $before, $ump->only($keys));
    }
}
