<?php

namespace App\Exports;

use App\Models\Asnaf;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Services\BudgetCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapRealisasiBulananExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    protected int $tahunAnggaranId;

    public function __construct(int $tahunAnggaranId)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
    }

    public function collection(): Collection
    {
        $calculator = new BudgetCalculator;
        $reportData = $calculator->getMonthlyExpenditureReport($this->tahunAnggaranId);

        $collection = collect();

        // 1. Data Hierarki Pohon Anggaran
        foreach ($reportData as $row) {
            $depth = $row['depth'] ?? 1;
            $indent = str_repeat('  ', max(0, $depth - 1));

            $collection->push([
                'kode' => $row['kode'],
                'nama' => $indent.$row['nama'],
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
                'serapan' => $row['persentase_penyerapan'].'%',
            ]);
        }

        // 2. Breakdown Per Sumber Dana
        $collection->push([
            'kode' => '',
            'nama' => '=== REKAP REALISASI PER SUMBER DANA ===',
            'plafon_rab' => '',
            'jan' => '',
            'feb' => '',
            'mar' => '',
            'apr' => '',
            'mei' => '',
            'jun' => '',
            'jul' => '',
            'agu' => '',
            'sep' => '',
            'okt' => '',
            'nov' => '',
            'des' => '',
            'total' => '',
            'sisa' => '',
            'serapan' => '',
        ]);

        $sumberDanaRows = DB::table('ump_items')
            ->join('ump', 'ump.id', '=', 'ump_items.ump_id')
            ->join('pencairan', 'pencairan.id', '=', 'ump.pencairan_id')
            ->join('permohonan', 'permohonan.id', '=', 'pencairan.permohonan_id')
            ->join('kode_anggaran', 'kode_anggaran.id', '=', 'permohonan.kode_anggaran_id')
            ->where('ump.status', 'selesai')
            ->where('kode_anggaran.tahun_anggaran_id', $this->tahunAnggaranId)
            ->get(['permohonan.sumber_dana_id', 'ump_items.tgl', 'ump_items.jumlah']);

        $sumberDanas = SumberDana::all();
        foreach ($sumberDanas as $sd) {
            $months = array_fill(1, 12, 0);
            $sdRows = $sumberDanaRows->where('sumber_dana_id', $sd->id);
            foreach ($sdRows as $r) {
                $m = (int) Carbon::parse($r->tgl)->format('n');
                if ($m >= 1 && $m <= 12) {
                    $months[$m] += (int) $r->jumlah;
                }
            }
            $total = array_sum($months);
            $collection->push([
                'kode' => $sd->kode ?? '-',
                'nama' => $sd->nama,
                'plafon_rab' => 0,
                'jan' => $months[1],
                'feb' => $months[2],
                'mar' => $months[3],
                'apr' => $months[4],
                'mei' => $months[5],
                'jun' => $months[6],
                'jul' => $months[7],
                'agu' => $months[8],
                'sep' => $months[9],
                'okt' => $months[10],
                'nov' => $months[11],
                'des' => $months[12],
                'total' => $total,
                'sisa' => 0,
                'serapan' => '-',
            ]);
        }

        // 3. Breakdown Per Asnaf
        $collection->push([
            'kode' => '',
            'nama' => '=== REKAP REALISASI PER ASNAF ===',
            'plafon_rab' => '',
            'jan' => '',
            'feb' => '',
            'mar' => '',
            'apr' => '',
            'mei' => '',
            'jun' => '',
            'jul' => '',
            'agu' => '',
            'sep' => '',
            'okt' => '',
            'nov' => '',
            'des' => '',
            'total' => '',
            'sisa' => '',
            'serapan' => '',
        ]);

        $asnafRows = DB::table('ump_items')
            ->join('ump', 'ump.id', '=', 'ump_items.ump_id')
            ->join('pencairan', 'pencairan.id', '=', 'ump.pencairan_id')
            ->join('permohonan', 'permohonan.id', '=', 'pencairan.permohonan_id')
            ->join('permohonan_items', 'permohonan_items.permohonan_id', '=', 'permohonan.id')
            ->join('kode_anggaran', 'kode_anggaran.id', '=', 'permohonan.kode_anggaran_id')
            ->where('ump.status', 'selesai')
            ->where('kode_anggaran.tahun_anggaran_id', $this->tahunAnggaranId)
            ->whereNotNull('permohonan_items.asnaf_id')
            ->get(['permohonan_items.asnaf_id', 'ump_items.tgl', 'ump_items.jumlah']);

        $asnafs = Asnaf::all();
        foreach ($asnafs as $asnaf) {
            $months = array_fill(1, 12, 0);
            $aRows = $asnafRows->where('asnaf_id', $asnaf->id);
            foreach ($aRows as $r) {
                $m = (int) Carbon::parse($r->tgl)->format('n');
                if ($m >= 1 && $m <= 12) {
                    $months[$m] += (int) $r->jumlah;
                }
            }
            $total = array_sum($months);
            $collection->push([
                'kode' => $asnaf->kode ?? '-',
                'nama' => $asnaf->nama,
                'plafon_rab' => 0,
                'jan' => $months[1],
                'feb' => $months[2],
                'mar' => $months[3],
                'apr' => $months[4],
                'mei' => $months[5],
                'jun' => $months[6],
                'jul' => $months[7],
                'agu' => $months[8],
                'sep' => $months[9],
                'okt' => $months[10],
                'nov' => $months[11],
                'des' => $months[12],
                'total' => $total,
                'sisa' => 0,
                'serapan' => '-',
            ]);
        }

        return $collection;
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
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0F2F57'],
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
