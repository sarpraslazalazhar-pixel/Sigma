<?php

namespace App\Exports;

use App\Models\Permohonan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapPermohonanExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    protected int $tahunAnggaranId;

    protected ?int $unitId;

    protected ?string $status;

    public function __construct(int $tahunAnggaranId, ?int $unitId = null, ?string $status = null)
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->unitId = $unitId;
        $this->status = $status;
    }

    public function collection(): Collection
    {
        $query = Permohonan::with(['unit', 'kodeAnggaran', 'sumberDana', 'user', 'approvals'])
            ->whereHas('kodeAnggaran', fn ($q) => $q->where('tahun_anggaran_id', $this->tahunAnggaranId));

        if ($this->unitId) {
            $query->where('unit_id', $this->unitId);
        }

        if ($this->status && $this->status !== '') {
            $query->where('status', $this->status);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($p) {
            $latestApproval = $p->approvals->sortByDesc('id')->first();
            $approver = $latestApproval ? ($latestApproval->signer_name.' ('.$latestApproval->tahap.')') : '-';
            $tgl = $p->submitted_at ? Carbon::parse($p->submitted_at)->format('d/m/Y') : ($p->created_at ? Carbon::parse($p->created_at)->format('d/m/Y') : '-');

            return [
                'no_pengajuan' => $p->no_pengajuan,
                'tanggal' => $tgl,
                'unit' => $p->unit?->nama ?? '-',
                'kode_anggaran' => $p->kodeAnggaran ? ($p->kodeAnggaran->kode.' - '.$p->kodeAnggaran->nama) : '-',
                'nama_kegiatan' => $p->nama_kegiatan,
                'sumber_dana' => $p->sumberDana?->nama ?? '-',
                'nilai_diminta' => (int) $p->total_diminta,
                'nilai_disetujui' => (int) ($p->total_disetujui ?? 0),
                'status' => ucfirst(str_replace('_', ' ', $p->status)),
                'approver_terakhir' => $approver,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'NO PENGAJUAN',
            'TANGGAL PENGAJUAN',
            'UNIT / DIVISI',
            'KODE ANGGARAN',
            'NAMA KEGIATAN',
            'SUMBER DANA',
            'NILAI DIMINTA',
            'NILAI DISETUJUI',
            'STATUS',
            'APPROVER TERAKHIR',
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
            'G' => '#,##0',
            'H' => '#,##0',
        ];
    }
}
