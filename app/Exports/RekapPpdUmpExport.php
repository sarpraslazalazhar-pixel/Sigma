<?php

namespace App\Exports;

use App\Models\Pencairan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapPpdUmpExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    protected int $tahunAnggaranId;

    protected ?int $unitId;

    public function __construct(int $tahunAnggaranId, ?int $unitId = null)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->unitId = $unitId;
    }

    public function collection(): Collection
    {
        $query = Pencairan::with(['permohonan.kodeAnggaran', 'permohonan.unit', 'ump.pj'])
            ->whereHas('permohonan.kodeAnggaran', fn ($q) => $q->where('tahun_anggaran_id', $this->tahunAnggaranId));

        if ($this->unitId) {
            $query->whereHas('permohonan', fn ($q) => $q->where('unit_id', $this->unitId));
        }

        $today = Carbon::today();

        return $query->orderBy('tgl_cair', 'desc')->get()->map(function ($pencairan) use ($today) {
            $ump = $pencairan->ump;

            $statusTenggat = '-';
            if ($ump && $ump->jatuh_tempo) {
                $jt = Carbon::parse($ump->jatuh_tempo)->startOfDay();
                $diff = (int) $today->diffInDays($jt, false);

                if ($ump->status === 'selesai') {
                    $statusTenggat = 'Selesai';
                } elseif ($diff < 0) {
                    $statusTenggat = 'Lewat';
                } elseif ($diff <= 3) {
                    $statusTenggat = 'Mendekati';
                } else {
                    $statusTenggat = 'Dalam Batas';
                }
            }

            $saldoKekurangan = 0;
            if ($ump) {
                $saldoKekurangan = (int) ($ump->saldo ?? 0) > 0 ? (int) $ump->saldo : -((int) ($ump->kekurangan ?? 0));
            }

            return [
                'no_pengajuan' => $pencairan->permohonan?->no_pengajuan ?? '-',
                'no_ppd' => $pencairan->no_ppd,
                'tgl_cair' => $pencairan->tgl_cair ? Carbon::parse($pencairan->tgl_cair)->format('d/m/Y') : '-',
                'nominal_ppd' => (int) $pencairan->jumlah,
                'status_ump' => $ump ? ucfirst(str_replace('_', ' ', $ump->status)) : 'Belum Ada',
                'tgl_terima' => $ump?->tgl_terima ? Carbon::parse($ump->tgl_terima)->format('d/m/Y') : '-',
                'pj' => $ump?->pj?->name ?? ($pencairan->permohonan?->user?->name ?? '-'),
                'total_penggunaan' => (int) ($ump?->total_penggunaan ?? 0),
                'saldo_kekurangan' => $saldoKekurangan,
                'jatuh_tempo' => $ump?->jatuh_tempo ? Carbon::parse($ump->jatuh_tempo)->format('d/m/Y') : '-',
                'status_tenggat' => $statusTenggat,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'NO PENGAJUAN',
            'NO PPD',
            'TGL CAIR',
            'NOMINAL PPD',
            'STATUS UMP',
            'TGL TERIMA UMP',
            'PJ',
            'TOTAL PENGGUNAAN',
            'SALDO / KEKURANGAN',
            'JATUH TEMPO',
            'STATUS TENGGAT',
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
            'D' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
        ];
    }
}
