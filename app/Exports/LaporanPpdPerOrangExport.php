<?php

namespace App\Exports;

use App\Models\Pencairan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPpdPerOrangExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    protected string $tahunFilter;

    protected string $unitFilter;

    protected string $search;

    protected string $bulanFilter;

    protected string $sumberDanaFilter;

    protected string $sortBy;

    public function __construct(
        string $tahunFilter = '',
        string $unitFilter = '',
        string $search = '',
        string $bulanFilter = '',
        string $sumberDanaFilter = '',
        string $sortBy = 'nominal_desc'
    ) {
        $this->tahunFilter = $tahunFilter;
        $this->unitFilter = $unitFilter;
        $this->search = $search;
        $this->bulanFilter = $bulanFilter;
        $this->sumberDanaFilter = $sumberDanaFilter;
        $this->sortBy = $sortBy;
    }

    public function collection(): Collection
    {
        $query = Pencairan::with([
            'permohonan.user.leveling',
            'permohonan.user.unit.parent',
            'permohonan.unit.parent',
            'permohonan.kodeAnggaran',
            'permohonan.sumberDana',
        ])
            ->where('status', 'dicairkan')
            ->when($this->tahunFilter !== 'all' && $this->tahunFilter !== '', function ($q) {
                $q->whereYear('tgl_cair', (int) $this->tahunFilter);
            })
            ->when($this->bulanFilter !== '', function ($q) {
                $q->whereMonth('tgl_cair', (int) $this->bulanFilter);
            })
            ->when($this->unitFilter !== '', function ($q) {
                $q->whereHas('permohonan', fn ($p) => $p->where('unit_id', (int) $this->unitFilter));
            })
            ->when($this->sumberDanaFilter !== '', function ($q) {
                $q->whereHas('permohonan', fn ($p) => $p->where('sumber_dana_id', (int) $this->sumberDanaFilter));
            })
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('no_ppd', 'like', $term)
                        ->orWhereHas('permohonan', function ($p) use ($term) {
                            $p->where('no_pengajuan', 'like', $term)
                                ->orWhere('nama_kegiatan', 'like', $term)
                                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('jabatan', 'like', $term));
                        });
                });
            });

        $pencairans = $query->orderBy('tgl_cair', 'desc')->get();

        $no = 1;

        $mapped = $pencairans->groupBy(fn ($p) => $p->permohonan?->user_id ?? 0)
            ->filter(fn ($items, $userId) => $userId > 0)
            ->map(function (Collection $items) use (&$no) {
                $first = $items->first();
                $user = $first->permohonan?->user;
                $uniqueKodeAnggaran = $items->map(fn ($p) => $p->permohonan?->kodeAnggaran)
                    ->filter()
                    ->unique('id')
                    ->map(fn ($k) => $k->kode.' - '.$k->nama)
                    ->implode('; ');

                $unitName = $user?->unit?->nama_lengkap ?? $first->permohonan?->unit?->nama_lengkap ?? '-';

                return [
                    'no' => $no++,
                    'nama' => $user?->name ?? 'Tidak Diketahui',
                    'jabatan' => $user?->jabatan ?? $user?->leveling?->nama ?? 'Pemohon',
                    'unit' => $unitName,
                    'frekuensi_ppd' => $items->count(),
                    'total_nominal' => (int) $items->sum('jumlah'),
                    'pos_anggaran' => $uniqueKodeAnggaran,
                ];
            });

        return match ($this->sortBy) {
            'nominal_asc' => $mapped->sortBy('total_nominal')->values(),
            'ppd_desc' => $mapped->sortByDesc('frekuensi_ppd')->values(),
            'ppd_asc' => $mapped->sortBy('frekuensi_ppd')->values(),
            'name_asc' => $mapped->sortBy('nama')->values(),
            default => $mapped->sortByDesc('total_nominal')->values(),
        };
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Pejabat Pemohon',
            'Jabatan',
            'Unit / Divisi',
            'Frekuensi PPD',
            'Total Nominal Dicairkan (Rp)',
            'Pos Anggaran yang Digunakan',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => '#,##0',
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
}
