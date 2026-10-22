<?php

namespace App\Livewire\Laporan;

use App\Exports\RekapAnggaranExport;
use App\Exports\RekapPermohonanExport;
use App\Exports\RekapPpdUmpExport;
use App\Exports\RekapRealisasiBulananExport;
use App\Models\KodeAnggaran;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Services\AuditLogger;
use App\Services\BudgetCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Monitoring Anggaran & Export: SIGMA LAZ Al-Azhar')]
class LaporanMonitoringIndex extends Component
{
    #[Url]
    public ?int $tahunAnggaranId = null;

    #[Url]
    public ?int $unitId = null;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        if ($this->tahunAnggaranId === null) {
            $this->tahunAnggaranId = (int) session(
                'tahun_anggaran_id',
                TahunAnggaran::where('status', 'aktif')->value('id')
                    ?? TahunAnggaran::orderBy('tahun', 'desc')->value('id')
                    ?? 0
            );
        }
    }

    #[On('tahun-anggaran-changed')]
    public function onYearChanged(int $tahunAnggaranId): void
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
    }

    public function exportRekapAnggaran(): BinaryFileResponse
    {
        $tahun = $this->getTahun();
        (new AuditLogger)->log('export.rekap_anggaran', Auth::user(), [], [
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'search' => $this->search,
        ]);

        return Excel::download(
            new RekapAnggaranExport($this->tahunAnggaranId, $this->search),
            "Rekap_Anggaran_{$tahun}.xlsx"
        );
    }

    public function exportRekapPermohonan(): BinaryFileResponse
    {
        $tahun = $this->getTahun();
        (new AuditLogger)->log('export.rekap_permohonan', Auth::user(), [], [
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'unit_id' => $this->unitId,
            'status' => $this->statusFilter,
        ]);

        return Excel::download(
            new RekapPermohonanExport($this->tahunAnggaranId, $this->unitId, $this->statusFilter ?: null),
            "Rekap_Permohonan_{$tahun}.xlsx"
        );
    }

    public function exportRekapPpdUmp(): BinaryFileResponse
    {
        $tahun = $this->getTahun();
        (new AuditLogger)->log('export.rekap_ppd_ump', Auth::user(), [], [
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'unit_id' => $this->unitId,
        ]);

        return Excel::download(
            new RekapPpdUmpExport($this->tahunAnggaranId, $this->unitId),
            "Rekap_PPD_dan_UMP_{$tahun}.xlsx"
        );
    }

    public function exportRekapRealisasi(): BinaryFileResponse
    {
        $tahun = $this->getTahun();
        (new AuditLogger)->log('export.rekap_realisasi', Auth::user(), [], [
            'tahun_anggaran_id' => $this->tahunAnggaranId,
        ]);

        return Excel::download(
            new RekapRealisasiBulananExport($this->tahunAnggaranId),
            "Rekap_Realisasi_Bulanan_{$tahun}.xlsx"
        );
    }

    private function getTahun(): int|string
    {
        $ta = TahunAnggaran::find($this->tahunAnggaranId);

        return $ta ? $ta->tahun : date('Y');
    }

    public function render()
    {
        $daftarTahun = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $daftarUnit = Unit::orderBy('nama', 'asc')->get();

        $calculator = new BudgetCalculator;
        $treeData = $this->tahunAnggaranId
            ? $calculator->calculateTree($this->tahunAnggaranId, $this->unitId)
            : collect();

        // Filter search
        if (trim($this->search) !== '') {
            $term = strtolower(trim($this->search));
            $treeData = $treeData->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['kode']), $term)
                    || str_contains(strtolower($item['nama']), $term);
            });
        }

        // Filter status
        if ($this->statusFilter !== '') {
            $treeData = $treeData->filter(function ($item) {
                return ($item['status'] ?? '') === $this->statusFilter;
            });
        }

        $monitoringRows = KodeAnggaran::sortHierarchically($treeData)->values()->map(function ($item) {
            $eff = $item['effective_budget'] ?? 0;
            $pemakaian = ($item['dana_terikat'] ?? 0) + ($item['realisasi'] ?? 0);
            $item['pemakaian_persen'] = $eff > 0 ? round(($pemakaian / $eff) * 100, 1) : 0;

            return $item;
        });

        // Totals
        $rootNodes = $treeData->filter(fn ($item) => ($item['depth'] ?? 1) === 1 || is_null($item['parent_id']));
        $totalBudget = (int) $rootNodes->sum('effective_budget');
        $totalTerikat = (int) $rootNodes->sum('dana_terikat');
        $totalRealisasi = (int) $rootNodes->sum('realisasi');
        $totalSisa = max(0, $totalBudget - $totalTerikat - $totalRealisasi);

        return view('livewire.laporan.laporan-monitoring-index', [
            'daftarTahun' => $daftarTahun,
            'daftarUnit' => $daftarUnit,
            'monitoringRows' => $monitoringRows,
            'totalBudget' => $totalBudget,
            'totalTerikat' => $totalTerikat,
            'totalRealisasi' => $totalRealisasi,
            'totalSisa' => $totalSisa,
        ]);
    }
}
