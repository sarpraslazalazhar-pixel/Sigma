<?php

namespace App\Livewire\Ump;

use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan UMP: SIGMA LAZ Al-Azhar')]
class UmpIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $unitFilter = '';

    public ?int $tahunAnggaranId = null;

    public function mount(): void
    {
        $this->tahunAnggaranId = (int) session(
            'tahun_anggaran_id',
            TahunAnggaran::where('status', 'aktif')->value('id') ?? 0
        );
    }

    #[On('tahun-anggaran-changed')]
    public function onYearChanged(int $tahunAnggaranId): void
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUnitFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'unitFilter']);
        $this->resetPage();
    }

    public function getStatusBadge(string $status): array
    {
        return match ($status) {
            'draft' => [
                'label' => 'Draft',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            ],
            'perlu_perbaikan' => [
                'label' => 'Perlu Perbaikan',
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'ttd_pj' => [
                'label' => 'Menunggu TTD Kadiv',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'ttd_manager_keuangan' => [
                'label' => 'Menunggu TTD Mgr Keuangan',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'ttd_kepala_keuangan' => [
                'label' => 'Menunggu TTD Kepala Keuangan',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'dibatalkan' => [
                'label' => 'Dibatalkan',
                'class' => 'bg-red-50 text-red-700 border-red-200',
            ],
            'selesai' => [
                'label' => 'Selesai',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $status)),
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            ],
        };
    }

    public function render(): View
    {
        $user = Auth::user();

        $query = Ump::with(['pencairan.permohonan.unit', 'pj'])
            ->when($this->tahunAnggaranId, function ($q) {
                $q->whereHas('pencairan.permohonan.kodeAnggaran', fn ($k) => $k->where('tahun_anggaran_id', $this->tahunAnggaranId));
            })
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->whereHas('pencairan', function ($q2) use ($term) {
                        $q2->where('no_ppd', 'like', $term);
                    })
                        ->orWhere('tujuan', 'like', $term)
                        ->orWhereHas('pj', function ($q2) use ($term) {
                            $q2->where('name', 'like', $term);
                        });
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->unitFilter !== '', function ($q) {
                $q->whereHas('pencairan.permohonan', function ($q2) {
                    $q2->where('unit_id', $this->unitFilter);
                });
            });

        // Filter per hak akses: staf unit melihat UMP semua unit dalam divisi yang sama (misal Sekretariat)
        if ($user && ! in_array($user->role, ['admin', 'keuangan'], true)) {
            $divisiUnitIds = $user->unit?->getSameDivisiUnitIds() ?? ($user->unit_id ? [$user->unit_id] : []);
            $query->whereHas('pencairan.permohonan', function ($q2) use ($divisiUnitIds) {
                if (! empty($divisiUnitIds)) {
                    $q2->whereIn('unit_id', $divisiUnitIds);
                }
            });
        }

        $umpList = $query->orderBy('id', 'desc')->paginate(15);

        $unitsQuery = Unit::query();
        if ($user && ! in_array($user->role, ['admin', 'keuangan'], true) && $user->unit) {
            $unitsQuery->whereIn('id', $user->unit->getSameDivisiUnitIds());
        }
        $units = $unitsQuery->orderBy('nama', 'asc')->get(['id', 'nama', 'kode']);

        // Scope metrics the same way as listing
        $metricsBase = Ump::query()
            ->when($this->tahunAnggaranId, function ($q) {
                $q->whereHas('pencairan.permohonan.kodeAnggaran', fn ($k) => $k->where('tahun_anggaran_id', $this->tahunAnggaranId));
            });
        if ($user && ! in_array($user->role, ['admin', 'keuangan'], true)) {
            $divisiUnitIds = $user->unit?->getSameDivisiUnitIds() ?? ($user->unit_id ? [$user->unit_id] : []);
            $metricsBase->whereHas('pencairan.permohonan', function ($q2) use ($divisiUnitIds) {
                if (! empty($divisiUnitIds)) {
                    $q2->whereIn('unit_id', $divisiUnitIds);
                }
            });
        }
        $activeQuery = (clone $metricsBase)->whereIn('status', ['draft', 'perlu_perbaikan', 'ttd_pj', 'ttd_manager_keuangan', 'ttd_kepala_keuangan']);

        return view('livewire.ump.ump-index', [
            'umpList' => $umpList,
            'units' => $units,
            'metrics' => [
                'berjalan' => (clone $activeQuery)->count(),
                'diterima' => (int) (clone $metricsBase)->sum('jumlah_total'),
                'realisasi' => (int) (clone $metricsBase)->sum('total_penggunaan'),
                'saldo' => (int) (clone $metricsBase)->sum('saldo'),
            ],
        ]);
    }
}
