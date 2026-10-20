<?php

namespace App\Livewire\Pencairan;

use App\Models\Pencairan;
use App\Models\Unit;
use App\Services\PermohonanWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Pencairan PPD: SIGMA LAZ Al-Azhar')]
class PencairanIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $unitFilter = '';

    public bool $batalModalOpen = false;

    public ?int $selectedPencairanId = null;

    public string $alasanBatal = '';

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

    public function bukaModalBatal(int $id): void
    {
        $this->selectedPencairanId = $id;
        $this->alasanBatal = '';
        $this->batalModalOpen = true;
    }

    public function tutupModalBatal(): void
    {
        $this->batalModalOpen = false;
        $this->selectedPencairanId = null;
        $this->alasanBatal = '';
        $this->resetErrorBag();
    }

    public function prosesBatal(): void
    {
        $this->validate([
            'alasanBatal' => ['required', 'string', 'min:5'],
        ], [
            'alasanBatal.required' => 'Alasan pembatalan wajib diisi.',
            'alasanBatal.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $pencairan = Pencairan::findOrFail($this->selectedPencairanId);

        try {
            (new PermohonanWorkflow)->batalkanPencairan($pencairan, Auth::user(), $this->alasanBatal);
            session()->flash('success', "Pencairan PPD {$pencairan->no_ppd} berhasil dibatalkan.");
            $this->tutupModalBatal();
        } catch (RuntimeException $e) {
            $this->addError('alasanBatal', $e->getMessage());
        }
    }

    public function render(): View
    {
        $user = Auth::user();
        $isGlobal = $user && ($user->role === 'admin' || $user->role === 'keuangan' || $user->is_petugas_input);

        $query = Pencairan::with(['permohonan.unit', 'permohonan.user', 'ump.pj'])
            ->orderBy('tgl_cair', 'desc');

        if (! $isGlobal) {
            $query->whereHas('permohonan', function ($q) use ($user) {
                $q->where('unit_id', $user?->unit_id)
                    ->orWhere('user_id', $user?->id);
            });
        }

        // Metrics base
        $metricsBase = clone $query;
        $totalPpd = (clone $metricsBase)->count();
        $totalNominal = (int) (clone $metricsBase)->where('status', 'dicairkan')->sum('jumlah');
        $umpBerjalan = (clone $metricsBase)->whereHas('ump', fn ($u) => $u->whereNotIn('status', ['selesai', 'dibatalkan']))->count();
        $umpSelesai = (clone $metricsBase)->whereHas('ump', fn ($u) => $u->where('status', 'selesai'))->count();

        // Apply filters
        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('no_ppd', 'like', $term)
                    ->orWhereHas('permohonan', function ($p) use ($term) {
                        $p->where('no_pengajuan', 'like', $term)
                            ->orWhere('nama_kegiatan', 'like', $term);
                    });
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter !== '') {
            $query->whereHas('permohonan', fn ($p) => $p->where('unit_id', (int) $this->unitFilter));
        }

        $pencairans = $query->paginate(15);
        $units = Unit::orderBy('nama')->get();

        return view('livewire.pencairan.pencairan-index', [
            'pencairans' => $pencairans,
            'units' => $units,
            'totalPpd' => $totalPpd,
            'totalNominal' => $totalNominal,
            'umpBerjalan' => $umpBerjalan,
            'umpSelesai' => $umpSelesai,
            'canManage' => $isGlobal,
        ]);
    }
}
