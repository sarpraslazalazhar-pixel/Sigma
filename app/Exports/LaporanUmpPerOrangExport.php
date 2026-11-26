<?php

namespace App\Exports;

use App\Services\RekapLaporanPerOrang;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanUmpPerOrangExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    public function __construct(
        protected ?int $tahunAnggaranId = null,
        protected string $tahunFilter = '',
        protected string $unitFilter = '',
        protected string $search = '',
        protected string $bulanFilter = '',
        protected string $sumberDanaFilter = '',
        protected string $statusFilter = '',
        protected string $sortBy = 'nominal_desc',
    ) {}

    public function collection(): Collection
    {
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

        $no = 1;

        return $data['rekap_per_user']->map(function (array $row) use (&$no): array {
            $posAnggaran = $row['pos_anggaran']
                ->map(fn ($k) => "{$k->kode} - {$k->nama}")
                ->implode('; ');

            return [
                'no' => $no++,
                'nama' => $row['name'],
                'jabatan' => $row['jabatan'],
                'unit' => $row['unit'],
                'peran' => $row['peran_ringkas'],
                'frekuensi_laporan' => $row['laporan_count'],
                'jumlah_baris' => $row['item_count'],
                'total_penggunaan' => $row['total_penggunaan'],
                'pos_anggaran' => $posAnggaran !== '' ? $posAnggaran : '-',
            ];
        })->values();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Pengguna',
            'Jabatan',
            'Unit / Divisi',
            'Peran di Laporan',
            'Frekuensi Laporan',
            'Jumlah Baris Pengeluaran',
            'Total Penggunaan Dana (Rp)',
            'Pos Anggaran yang Digunakan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => '#,##0',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
}
