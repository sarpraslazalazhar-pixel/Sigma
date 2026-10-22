<?php

namespace App\Livewire\Pencairan;

use App\Exports\RekapPpdUmpExport;
use App\Models\Pencairan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Services\AuditLogger;
use App\Services\PermohonanWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Pencairan PPD: SIGMA LAZ Al-Azhar')]
class PencairanIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $unitFilter = '';

    public string $sumberDanaFilter = '';

    public string $tahunFilter = '';

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

    public string $bulanFilter = '';

    public string $umpStatusFilter = '';

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

    public function updatingSumberDanaFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTahunFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBulanFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUmpStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'unitFilter', 'sumberDanaFilter', 'tahunFilter', 'bulanFilter', 'umpStatusFilter']);
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
            $msg = "Pencairan PPD {$pencairan->no_ppd} berhasil dibatalkan.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            $this->tutupModalBatal();
        } catch (RuntimeException $e) {
            $this->addError('alasanBatal', $e->getMessage());
        }
    }

    public function exportExcel(): BinaryFileResponse
    {
        $user = Auth::user();
        $isGlobal = $user && ($user->role === 'admin' || $user->role === 'keuangan' || $user->is_petugas_input);
        $unitId = $this->unitFilter !== '' ? (int) $this->unitFilter : (! $isGlobal ? $user?->unit_id : null);

        $ta = $this->tahunFilter !== ''
            ? TahunAnggaran::where('tahun', (int) $this->tahunFilter)->first()
            : TahunAnggaran::where('status', 'aktif')->first();

        $taId = $ta?->id ?? 1;
        $tahun = $ta?->tahun ?? date('Y');

        (new AuditLogger)->log('export.ppd_ump', null, [], [
            'tahun_anggaran_id' => $taId,
            'unit_id' => $unitId,
        ]);

        return Excel::download(
            new RekapPpdUmpExport($taId, $unitId),
            "Rekap_PPD_dan_UMP_{$tahun}.xlsx"
        );
    }

    public function render(): View
    {
        $user = Auth::user();
        $isGlobal = $user && ($user->role === 'admin' || $user->role === 'keuangan' || $user->is_petugas_input);

        $query = Pencairan::with(['permohonan.unit', 'permohonan.user', 'ump.pj'])
            ->orderBy('tgl_cair', 'desc');

        if ($this->tahunAnggaranId && $this->tahunFilter === '') {
            $query->whereHas('permohonan.kodeAnggaran', fn ($k) => $k->where('tahun_anggaran_id', $this->tahunAnggaranId));
        }
        if (! $isGlobal) {
            $divisiUnitIds = $user?->unit?->getSameDivisiUnitIds() ?? ($user?->unit_id ? [$user->unit_id] : []);
            $query->whereHas('permohonan', function ($q) use ($user, $divisiUnitIds) {
                if (! empty($divisiUnitIds)) {
                    $q->whereIn('unit_id', $divisiUnitIds);
                }
                $q->orWhere('user_id', $user?->id);
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
                            ->orWhere('nama_kegiatan', 'like', $term)
                            ->orWhere('program', 'like', $term)
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
                    })
                    ->orWhereHas('ump.pj', fn ($pj) => $pj->where('name', 'like', $term));
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter !== '') {
            $query->whereHas('permohonan', fn ($p) => $p->where('unit_id', (int) $this->unitFilter));
        }

        if ($this->sumberDanaFilter !== '') {
            $query->whereHas('permohonan', fn ($p) => $p->where('sumber_dana_id', (int) $this->sumberDanaFilter));
        }

        if ($this->tahunFilter !== '') {
            $query->whereYear('tgl_cair', (int) $this->tahunFilter);
        }

        if ($this->bulanFilter !== '') {
            $query->whereMonth('tgl_cair', (int) $this->bulanFilter);
        }

        if ($this->umpStatusFilter !== '') {
            $query->whereHas('ump', fn ($u) => $u->where('status', $this->umpStatusFilter));
        }

        $pencairans = $query->paginate(15);
        $unitsQuery = Unit::query();
        if (! $isGlobal && $user?->unit) {
            $unitsQuery->whereIn('id', $user->unit->getSameDivisiUnitIds());
        }
        $units = $unitsQuery->orderBy('nama')->get();
        $sumberDanas = SumberDana::where('is_active', true)->orderBy('id')->get();
        $daftarTahun = TahunAnggaran::orderBy('tahun', 'desc')->pluck('tahun')->all();
        if (empty($daftarTahun)) {
            $daftarTahun = [(int) date('Y')];
        }

        return view('livewire.pencairan.pencairan-index', [
            'pencairans' => $pencairans,
            'units' => $units,
            'totalPpd' => $totalPpd,
            'totalNominal' => $totalNominal,
            'umpBerjalan' => $umpBerjalan,
            'umpSelesai' => $umpSelesai,
            'sumberDanas' => $sumberDanas,
            'daftarTahun' => $daftarTahun,
            'canManage' => $isGlobal,
        ]);
    }
}
