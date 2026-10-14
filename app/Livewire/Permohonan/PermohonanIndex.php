<?php

namespace App\Livewire\Permohonan;

use App\Models\Permohonan;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Permohonan Dana: SIGMA LAZ Al-Azhar')]
class PermohonanIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $unitFilter = '';

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
            'diajukan' => [
                'label' => 'Menunggu Atasan',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'disetujui_atasan' => [
                'label' => 'Menunggu Kadiv',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'diketahui_kadiv' => [
                'label' => 'Menunggu Keuangan',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'disetujui_keuangan' => [
                'label' => 'Disetujui Keuangan (Terikat)',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'perlu_perbaikan' => [
                'label' => 'Perlu Perbaikan',
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'ditolak' => [
                'label' => 'Ditolak',
                'class' => 'bg-red-50 text-red-700 border-red-200',
            ],
            'dibatalkan' => [
                'label' => 'Dibatalkan',
                'class' => 'bg-slate-100 text-slate-500 border-slate-200',
            ],
            'dicairkan' => [
                'label' => 'Dicairkan',
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
            ],
            default => [
                'label' => ucfirst($status),
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            ],
        };
    }

    public function render(): View
    {
        $user = Auth::user();

        $query = Permohonan::with(['unit', 'user', 'kodeAnggaran', 'sumberDana'])
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('no_pengajuan', 'like', $term)
                        ->orWhere('nama_kegiatan', 'like', $term)
                        ->orWhere('program', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->unitFilter !== '', function ($q) {
                $q->where('unit_id', $this->unitFilter);
            });

        // Filter per hak akses: staf unit melihat permohonan unitnya atau miliknya
        if ($user && !in_array($user->role, ['admin', 'keuangan'])) {
            $query->where(function ($q) use ($user) {
                if ($user->unit_id) {
                    $q->where('unit_id', $user->unit_id);
                }
                $q->orWhere('user_id', $user->id);
            });
        }

        $permohonanList = $query->orderBy('id', 'desc')->paginate(15);
        $units = Unit::orderBy('nama', 'asc')->get(['id', 'nama', 'kode']);

        return view('livewire.permohonan.permohonan-index', [
            'permohonanList' => $permohonanList,
            'units' => $units,
        ]);
    }
}
