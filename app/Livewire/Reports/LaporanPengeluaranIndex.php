<?php

namespace App\Livewire\Reports;

use App\Exports\LaporanPengeluaranExport;
use App\Models\TahunAnggaran;
use App\Services\BudgetCalculator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Laporan Pengeluaran Bulanan: SIGMA LAZ Al-Azhar')]
class LaporanPengeluaranIndex extends Component
{
    public int $tahun_anggaran_id;
    public string $search = '';
    public ?int $selectedMonth = null; // null = semua bulan

    public array $expandedMobileNodes = [];

    public function mount(): void
    {
        $activeYear = TahunAnggaran::where('status', 'aktif')->first()
            ?? TahunAnggaran::orderBy('tahun', 'desc')->first();

        $this->tahun_anggaran_id = $activeYear ? $activeYear->id : 0;
    }

    public function toggleMobileNode(int $id): void
    {
        if (in_array($id, $this->expandedMobileNodes)) {
            $this->expandedMobileNodes = array_values(array_diff($this->expandedMobileNodes, [$id]));
        } else {
            $this->expandedMobileNodes[] = $id;
        }
    }

    public function exportExcel(): BinaryFileResponse
    {
        $ta = TahunAnggaran::find($this->tahun_anggaran_id);
        $tahun = $ta ? $ta->tahun : date('Y');
        $filename = "Laporan_Pengeluaran_Bulanan_{$tahun}.xlsx";

        return Excel::download(
            new LaporanPengeluaranExport($this->tahun_anggaran_id, $this->search),
            $filename
        );
    }

    public function render()
    {
        $years = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $selectedYear = TahunAnggaran::find($this->tahun_anggaran_id);

        $calculator = new BudgetCalculator();
        $reportData = $this->tahun_anggaran_id
            ? $calculator->getMonthlyExpenditureReport($this->tahun_anggaran_id, $this->search)
            : collect();

        $monthNames = BudgetCalculator::monthNames();

        // Summary totals
        $totalPlafonAll = $reportData->where('depth', 1)->sum('plafon_rab');
        $totalPengeluaranAll = $reportData->where('depth', 1)->sum('total_pengeluaran');
        $sisaAll = max(0, $totalPlafonAll - $totalPengeluaranAll);
        $serapanAll = $totalPlafonAll > 0 ? round(($totalPengeluaranAll / $totalPlafonAll) * 100, 1) : 0;

        return view('livewire.reports.laporan-pengeluaran-index', [
            'years' => $years,
            'selectedYear' => $selectedYear,
            'reportData' => $reportData,
            'monthNames' => $monthNames,
            'totalPlafonAll' => $totalPlafonAll,
            'totalPengeluaranAll' => $totalPengeluaranAll,
            'sisaAll' => $sisaAll,
            'serapanAll' => $serapanAll,
        ]);
    }
}
