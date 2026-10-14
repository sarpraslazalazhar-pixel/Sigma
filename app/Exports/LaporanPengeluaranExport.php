<?php

namespace App\Exports;

use App\Models\TahunAnggaran;
use App\Services\BudgetCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPengeluaranExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected int $tahunAnggaranId;
    protected ?string $search;

    public function __construct(int $tahunAnggaranId, ?string $search = null)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $calculator = new BudgetCalculator();
        $reportData = $calculator->getMonthlyExpenditureReport($this->tahunAnggaranId, $this->search);

        return $reportData->map(function ($row) {
            return [
                'kode' => $row['kode'],
                'nama' => $row['nama'],
                'plafon_rab' => $row['plafon_rab'],
                'jan' => $row['months'][1],
                'feb' => $row['months'][2],
                'mar' => $row['months'][3],
                'apr' => $row['months'][4],
                'mei' => $row['months'][5],
                'jun' => $row['months'][6],
                'jul' => $row['months'][7],
                'agu' => $row['months'][8],
                'sep' => $row['months'][9],
                'okt' => $row['months'][10],
                'nov' => $row['months'][11],
                'des' => $row['months'][12],
                'total' => $row['total_pengeluaran'],
                'sisa' => $row['sisa_anggaran'],
                'serapan' => $row['persentase_penyerapan'] . '%',
            ];
        });
    }

    public function headings(): array
    {
        $ta = TahunAnggaran::find($this->tahunAnggaranId);
        $tahun = $ta ? $ta->tahun : date('Y');

        return [
            'KODE ANGGARAN',
            'KETERANGAN / POS ANGGARAN',
            "RAB {$tahun}",
            'JANUARI',
            'FEBRUARI',
            'MARET',
            'APRIL',
            'MEI',
            'JUNI',
            'JULI',
            'AGUSTUS',
            'SEPTEMBER',
            'OKTOBER',
            'NOVEMBER',
            'DESEMBER',
            'TOTAL PENGELUARAN',
            'SISA ANGGARAN',
            'PENYERAPAN ANGGARAN',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0F2F57'], // Navy Al-Azhar
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
            'N' => '#,##0',
            'O' => '#,##0',
            'P' => '#,##0',
            'Q' => '#,##0',
        ];
    }
}
