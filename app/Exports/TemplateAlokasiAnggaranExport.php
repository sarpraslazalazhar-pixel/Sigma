<?php

namespace App\Exports;

use App\Models\KodeAnggaran;
use App\Services\BudgetCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateAlokasiAnggaranExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    public function __construct(
        public int $tahunAnggaranId,
        public ?int $prevTahunAnggaranId = null,
    ) {}

    public function collection(): Collection
    {
        $calculator = new BudgetCalculator;
        $currentTree = $calculator->calculateTree($this->tahunAnggaranId);
        $prevTree = $this->prevTahunAnggaranId ? $calculator->calculateTree($this->prevTahunAnggaranId) : collect();

        return KodeAnggaran::sortHierarchically($currentTree)->values()->map(function ($row) use ($prevTree) {
            $prev = $prevTree->get($row['id']) ?? $prevTree->firstWhere('kode', $row['kode']);
            $isLeaf = $row['is_leaf'] ?? false;

            return [
                'kode' => $row['kode'],
                'nama' => $row['nama'],
                'depth' => $row['depth'],
                'is_leaf' => $isLeaf ? 'YA' : 'TIDAK',
                'plafon_tahun_lalu' => $prev['plafon'] ?? 0,
                'realisasi_tahun_lalu' => $prev['realisasi'] ?? 0,
                'plafon_saat_ini' => $row['plafon'] ?? 0,
                'plafon_baru' => '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'KODE',
            'NAMA AKUN',
            'DEPTH',
            'POS LEAF',
            'PLAFON TAHUN LALU',
            'REALISASI TAHUN LALU',
            'PLAFON SAAT INI',
            'PLAFON BARU',
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
            'E' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
        ];
    }
}
