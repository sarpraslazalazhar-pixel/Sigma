<?php

namespace App\Livewire\Keuangan;

use App\Models\Ump;
use App\Services\UmpWorkflow;
use App\Services\VerifikasiKeuangan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Verifikasi Keuangan: SIGMA LAZ Al-Azhar')]
class VerifikasiKeuanganIndex extends Component
{
    public string $search = '';

    public string $filterStatus = 'belum';

    public ?int $dokumenDipilih = null;

    public string $catatan = '';

    public ?int $approvalTargetId = null;

    public string $approvalAction = '';

    public string $approvalNotes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('verifikasi-keuangan'), 403);
    }

    public function setFilterStatus(string $status): void
    {
        Gate::authorize('verifikasi-keuangan');
        $allowed = ['belum', 'menunggu_persetujuan', 'perbaikan', 'terverifikasi', 'semua'];
        $this->filterStatus = in_array($status, $allowed, true) ? $status : 'belum';
        $this->tutupCatatan();
        $this->tutupPersetujuan();
    }

    public function pilih(int $id): void
    {
        Gate::authorize('verifikasi-keuangan');
        $this->tutupPersetujuan();
        $this->resetValidation('catatan');
        $this->reset('catatan');
        $this->dokumenDipilih = $id;
    }

    public function tutupCatatan(): void
    {
        $this->resetValidation('catatan');
        $this->reset('dokumenDipilih', 'catatan');
    }

    public function verifikasi(int $id): void
    {
        Gate::authorize('verifikasi-keuangan');
        app(VerifikasiKeuangan::class)->decide($id, Auth::user(), 'verified', $this->catatan);
        $this->tutupCatatan();
        $msg = 'Kelengkapan Laporan terverifikasi dan diteruskan ke Manager Keuangan.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function kembalikan(int $id): void
    {
        Gate::authorize('verifikasi-keuangan');
        app(VerifikasiKeuangan::class)->decide($id, Auth::user(), 'returned', $this->catatan);
        $this->tutupCatatan();
        $msg = 'Laporan dikembalikan ke PJ untuk perbaikan.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function batalVerifikasi(int $id): void
    {
        Gate::authorize('verifikasi-keuangan');
        app(VerifikasiKeuangan::class)->decide($id, Auth::user(), 'unverified');
        $msg = 'Verifikasi dibatalkan. Laporan kembali menunggu verifikasi.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function bukaPersetujuan(int $id, string $action = 'setuju'): void
    {
        Gate::authorize('verifikasi-keuangan');
        if (! in_array($action, ['setuju', 'perbaikan', 'batal'], true)) {
            return;
        }

        $ump = Ump::with(['pencairan.permohonan.unit', 'pj.unit'])->findOrFail($id);
        abort_unless((new UmpWorkflow)->canUserApprove(Auth::user(), $ump), 403);

        $this->tutupCatatan();
        $this->resetValidation('approvalNotes');
        $this->approvalNotes = '';
        $this->approvalTargetId = $id;
        $this->approvalAction = $action;
    }

    public function tutupPersetujuan(): void
    {
        $this->resetValidation('approvalNotes');
        $this->reset('approvalTargetId', 'approvalAction', 'approvalNotes');
    }

    public function prosesPersetujuan(int $id): void
    {
        Gate::authorize('verifikasi-keuangan');

        $ump = Ump::with(['pencairan.permohonan.unit', 'pj.unit'])->findOrFail($id);
        $workflow = new UmpWorkflow;
        $user = Auth::user();

        abort_unless($workflow->canUserApprove($user, $ump), 403);

        $action = $this->approvalAction;
        if (! in_array($action, ['setuju', 'perbaikan', 'batal'], true)) {
            return;
        }

        if (in_array($action, ['perbaikan', 'batal'], true)) {
            $this->validate([
                'approvalNotes' => ['required', 'string', 'min:5', 'max:1000'],
            ], [
                'approvalNotes.required' => 'Catatan alasan wajib diisi.',
                'approvalNotes.min' => 'Catatan alasan minimal 5 karakter.',
                'approvalNotes.max' => 'Catatan alasan maksimal 1000 karakter.',
            ]);
        }

        try {
            $msg = match ($action) {
                'setuju' => (function () use ($workflow, $ump, $user): string {
                    $workflow->approve($ump, $user, [
                        'catatan' => trim($this->approvalNotes) !== '' ? trim($this->approvalNotes) : null,
                    ]);

                    return 'Persetujuan Laporan berhasil dicatat.';
                })(),
                'perbaikan' => (function () use ($workflow, $ump, $user): string {
                    $workflow->requestRevision($ump, $user, trim($this->approvalNotes));

                    return 'Laporan dikembalikan untuk perbaikan.';
                })(),
                'batal' => (function () use ($workflow, $ump, $user): string {
                    $workflow->cancel($ump, $user, trim($this->approvalNotes));

                    return 'Laporan berhasil di-void.';
                })(),
            };

            $this->tutupPersetujuan();
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        } catch (RuntimeException $e) {
            $this->addError('approvalNotes', $e->getMessage());
        }
    }

    public function render(VerifikasiKeuangan $verification): View
    {
        Gate::authorize('verifikasi-keuangan');

        $user = Auth::user();
        $workflow = new UmpWorkflow;

        $counts = [
            'belum' => $verification->pending()->count(),
            'menunggu_persetujuan' => $verification->awaitingApproval()->count(),
            'perbaikan' => $verification->returned()->count(),
            'terverifikasi' => $verification->documents()->whereNotNull('verifikasi_keuangan_at')->count(),
            'semua' => $verification->documents()->where(fn ($group) => $verification->active($group)
                ->orWhereNotNull('verifikasi_keuangan_at')
                ->orWhere(fn ($ret) => $verification->returned($ret)))->count(),
        ];

        $query = match ($this->filterStatus) {
            'menunggu_persetujuan' => $verification->awaitingApproval(),
            'perbaikan' => $verification->returned(),
            'terverifikasi' => $verification->documents()->whereNotNull('verifikasi_keuangan_at'),
            'semua' => $verification->documents()->where(fn ($group) => $verification->active($group)
                ->orWhereNotNull('verifikasi_keuangan_at')
                ->orWhere(fn ($ret) => $verification->returned($ret))),
            default => $verification->pending(),
        };

        $documents = $query->with([
            'pencairan.permohonan.unit',
            'pencairan.permohonan.kodeAnggaran',
            'pj.unit',
            'verifikator',
            'items' => fn ($q) => $q->withCount('lampiran'),
        ])->withCount('items')->orderByDesc('updated_at')->orderByDesc('id')->get();

        $activeIds = in_array($this->filterStatus, ['terverifikasi', 'semua'], true) && $documents->isNotEmpty()
            ? $verification->active()->whereKey($documents->modelKeys())->pluck('id')->flip()
            : null;

        $items = $documents->map(function (Ump $document) use ($verification, $workflow, $user, $activeIds): array {
            $permohonan = $document->pencairan?->permohonan;
            $verified = $document->verifikasi_keuangan_at !== null;
            $returned = $document->status === 'perlu_perbaikan' && ! $verified && $document->verifikasi_keuangan_catatan !== null;
            $isActive = $activeIds === null
                ? ! $returned && in_array($document->status, ['ttd_manager_keuangan', 'ttd_kepala_keuangan'], true)
                : $activeIds->has($document->id);
            $mayReview = $isActive && ! $returned && $verification->mayReview($document, $user);

            return [
                'id' => $document->id,
                'number' => $document->pencairan?->no_ppd ?? 'LAP-'.$document->id,
                'activity' => $permohonan?->nama_kegiatan ?? $document->tujuan,
                'tujuan' => $document->tujuan,
                'unit' => $permohonan?->unit?->nama,
                'budget' => $permohonan?->kodeAnggaran?->kode,
                'pjName' => $document->pj?->name ?? '-',
                'amount' => (int) $document->jumlah_total,
                'usedAmount' => (int) $document->total_penggunaan,
                'saldo' => (int) $document->saldo,
                'kekurangan' => (int) $document->kekurangan,
                'itemsCount' => (int) $document->items_count,
                'buktiCount' => (int) $document->items->sum('lampiran_count'),
                'date' => $document->tgl_terima ?? $document->created_at,
                'url' => $verification->sourceUrl($document->id),
                'status' => $document->status,
                'statusBadge' => $this->resolveStatusBadge($document),
                'note' => $document->verifikasi_keuangan_catatan,
                'verified' => $verified,
                'verifiedAt' => $document->verifikasi_keuangan_at,
                'returned' => $returned,
                'reviewer' => $document->verifikator?->name,
                'isResponsible' => $verification->isResponsible($document, $user),
                'canReview' => $mayReview && ! $verified,
                'canUnverify' => $mayReview && $verified && $document->status === 'ttd_manager_keuangan',
                'canApprove' => $workflow->canUserApprove($user, $document),
            ];
        });

        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            $items = $items->filter(fn (array $item) => str_contains(mb_strtolower(implode(' ', array_filter([
                $item['number'],
                $item['activity'],
                $item['tujuan'],
                $item['unit'],
                $item['budget'],
                $item['pjName'],
            ]))), $term));
        }

        return view('livewire.keuangan.verifikasi-keuangan-index', [
            'items' => $items->values(),
            'counts' => $counts,
        ]);
    }

    /**
     * @return array{label: string, class: string}
     */
    private function resolveStatusBadge(Ump $ump): array
    {
        return match ($ump->status) {
            'perlu_perbaikan' => ['label' => 'Perlu Perbaikan', 'class' => 'bg-amber-50 text-amber-900 border-amber-200'],
            'ttd_manager_keuangan' => $ump->verifikasi_keuangan_at === null
                ? ['label' => 'Menunggu Verifikasi Keuangan', 'class' => 'bg-amber-50 text-amber-800 border-amber-200']
                : ['label' => 'Menunggu TTD Mgr Keuangan', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'ttd_kepala_keuangan' => ['label' => 'Menunggu TTD Kepala Keuangan', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'selesai' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'dibatalkan' => ['label' => 'Void', 'class' => 'bg-red-50 text-red-700 border-red-200'],
            default => ['label' => ucfirst(str_replace('_', ' ', $ump->status)), 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
        };
    }
}
