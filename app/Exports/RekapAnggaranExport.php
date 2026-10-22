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

class RekapAnggaranExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
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
        $calculator = new BudgetCalculator;
        $tree = $calculator->calculateTree($this->tahunAnggaranId);

        if ($this->search && trim($this->search) !== '') {
            $term = strtolower(trim($this->search));
            $tree = $tree->filter(function ($row) use ($term) {
                return str_contains(strtolower($row['kode']), $term)
                    || str_contains(strtolower($row['nama']), $term);
            });
        }

        return KodeAnggaran::sortHierarchically($tree)->values()->map(function ($row) {
            $depth = $row['depth'] ?? 1;
            $indent = str_repeat('  ', max(0, $depth - 1));
            $eff = $row['effective_budget'] ?? 0;
            $serapan = $eff > 0 ? round(($row['realisasi'] / $eff) * 100, 2).'%' : '0%';

            return [
                'kode' => $row['kode'],
                'nama' => $indent.$row['nama'],
                'plafon_awal' => $row['plafon_awal'] ?? $row['effective_budget'],
                'plafon_revisi' => $row['plafon_revisi'] ?? '-',
                'plafon_rab' => $row['effective_budget'],
                'dana_terikat' => $row['dana_terikat'],
                'realisasi' => $row['realisasi'],
                'sisa_tersedia' => $row['sisa_tersedia'],
                'serapan' => $serapan,
                'status' => $row['status'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'KODE',
            'POS ANGGARAN',
            'PLAFON AWAL',
            'PLAFON REVISI',
            'PLAFON EFEKTIF',
            'DANA TERIKAT',
            'REALISASI',
            'SISA TERSEDIA',
            'SERAPAN (%)',
            'STATUS',
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
        ];
    }
}
