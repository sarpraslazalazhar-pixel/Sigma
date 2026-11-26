<?php

namespace App\Livewire\Laporan;

use App\Exports\LaporanUmpPerOrangExport;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Services\RekapLaporanPerOrang;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Rekap Laporan per Orang: SIGMA LAZ Al-Azhar')]
class LaporanUmpPerOrang extends Component
{
    public ?int $tahunAnggaranId = null;

    public string $tahunFilter = ''; // '' = ikuti TA terpilih/aktif, 'all' = semua tahun

    public string $bulanFilter = '';

    public string $unitFilter = '';

    public string $sumberDanaFilter = '';

    public string $statusFilter = '';

    public string $search = '';

    public string $sortBy = 'nominal_desc'; // 'nominal_desc', 'nominal_asc', 'laporan_desc', 'laporan_asc', 'name_asc'

    public string $activeTab = 'rekap'; // 'rekap' | 'laporan'

    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $this->tahunAnggaranId = (int) session(
            'tahun_anggaran_id',
            TahunAnggaran::where('status', 'aktif')->value('id')
                ?? TahunAnggaran::orderBy('tahun', 'desc')->value('id')
                ?? 0
        );
        $this->tahunFilter = '';
    }

    #[On('tahun-anggaran-changed')]
    public function onYearChanged(int $tahunAnggaranId): void
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->tahunFilter = '';
    }

    public function switchTahunAnggaran(?int $yearId): void
    {
        if ($yearId) {
            $this->tahunAnggaranId = $yearId;
            $this->tahunFilter = '';
            session(['tahun_anggaran_id' => $yearId]);
            $this->dispatch('tahun-anggaran-changed', tahunAnggaranId: $yearId);
        } else {
            $this->tahunFilter = 'all';
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['rekap', 'laporan'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function openUserDetail(int $userId): void
    {
        $this->selectedUserId = $userId;
    }

    public function closeUserDetail(): void
    {
        $this->selectedUserId = null;
    }

    public function getActiveFilterCountProperty(): int
    {
        $count = 0;
        if ($this->tahunFilter === 'all') {
            $count++;
        }
        if ($this->bulanFilter !== '') {
            $count++;
        }
        if ($this->unitFilter !== '') {
            $count++;
        }
        if ($this->sumberDanaFilter !== '') {
            $count++;
        }
        if ($this->statusFilter !== '') {
            $count++;
        }
        if ($this->search !== '') {
            $count++;
        }
        if ($this->sortBy !== 'nominal_desc') {
            $count++;
        }

        return $count;
    }

    public function removeFilter(string $key): void
    {
        match ($key) {
            'tahun' => $this->tahunFilter = '',
            'bulan' => $this->bulanFilter = '',
            'unit' => $this->unitFilter = '',
            'sumber_dana' => $this->sumberDanaFilter = '',
            'status' => $this->statusFilter = '',
            'search' => $this->search = '',
            'sort' => $this->sortBy = 'nominal_desc',
            default => null,
        };
    }

    public function getSortLabelProperty(): string
    {
        return match ($this->sortBy) {
            'nominal_asc' => 'Penggunaan Terkecil',
            'laporan_desc' => 'Laporan Terbanyak',
            'laporan_asc' => 'Laporan Terendah',
            'name_asc' => 'Nama (A-Z)',
            default => 'Penggunaan Terbesar',
        };
    }

    public function getStatusFilterLabelProperty(): string
    {
        return match ($this->statusFilter) {
            'selesai' => 'Selesai',
            'proses' => 'Proses Persetujuan',
            'draft' => 'Draft / Perlu Perbaikan',
            default => '',
        };
    }

    public function resetFilters(): void
    {
        $this->tahunFilter = '';
        $this->bulanFilter = '';
        $this->unitFilter = '';
        $this->sumberDanaFilter = '';
        $this->statusFilter = '';
        $this->search = '';
        $this->sortBy = 'nominal_desc';
        $this->selectedUserId = null;
    }

    public function exportExcel(): BinaryFileResponse
    {
        $selectedYear = $this->tahunAnggaranId ? TahunAnggaran::find($this->tahunAnggaranId) : null;
        $tahunLabel = $this->tahunFilter === 'all'
            ? 'Semua_Tahun'
            : ($selectedYear ? (string) $selectedYear->tahun : (string) date('Y'));
        $filename = "Rekap_Laporan_Per_Orang_{$tahunLabel}.xlsx";

        return Excel::download(
            new LaporanUmpPerOrangExport(
                tahunAnggaranId: $this->tahunAnggaranId,
                tahunFilter: $this->tahunFilter,
                unitFilter: $this->unitFilter,
                search: $this->search,
                bulanFilter: $this->bulanFilter,
                sumberDanaFilter: $this->sumberDanaFilter,
                statusFilter: $this->statusFilter,
                sortBy: $this->sortBy,
            ),
            $filename
        );
    }

    public function render(): View
    {
        $selectedYear = $this->tahunAnggaranId ? TahunAnggaran::find($this->tahunAnggaranId) : null;
        if (! $selectedYear) {
            $selectedYear = TahunAnggaran::where('status', 'aktif')->first()
                ?? TahunAnggaran::orderBy('tahun', 'desc')->first();
            $this->tahunAnggaranId = $selectedYear?->id ?? 0;
        }

        $data = (new RekapLaporanPerOrang)->build(
            tahunAnggaranId: $this->tahunAnggaranId,
            tahunFilter: $this->tahunFilter,
            unitFilter: $this->unitFilter,
            search: $this->search,
            bulanFilter: $this->bulanFilter,
            sumberDanaFilter: $this->sumberDanaFilter,
            statusFilter: $this->statusFilter,
            sortBy: $this->sortBy,
        );

        $selectedUserSummary = null;
        if ($this->selectedUserId) {
            $selectedUserSummary = $data['rekap_per_user']->firstWhere('user_id', $this->selectedUserId);
        }

        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $unitOptions = Unit::formatDropdownOptions(null, true)->toArray();
        $sumberDanaList = SumberDana::where('is_active', true)->orderBy('id')->get();

        return view('livewire.laporan.laporan-ump-per-orang', [
            'rekapPerUser' => $data['rekap_per_user'],
            'allUmps' => $data['all_umps'],
            'umpParticipants' => $data['ump_participants'],
            'totalLaporan' => $data['total_laporan'],
            'totalDanaDiterima' => $data['total_dana_diterima'],
            'totalPenggunaan' => $data['total_penggunaan'],
            'totalOrang' => $data['total_orang'],
            'rataRata' => $data['rata_rata'],
            'tahunList' => $tahunList,
            'selectedYear' => $selectedYear,
            'unitOptions' => $unitOptions,
            'sumberDanaList' => $sumberDanaList,
            'selectedUserSummary' => $selectedUserSummary,
        ]);
    }
}
