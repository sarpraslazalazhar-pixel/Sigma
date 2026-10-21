<?php

namespace App\Http\Controllers;

use App\Models\Pencairan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PencairanPdfController extends Controller
{
    public function cetak(int $id): Response
    {
        $pencairan = Pencairan::with([
            'permohonan.unit',
            'permohonan.user',
            'permohonan.kodeAnggaran',
            'permohonan.sumberDana',
            'permohonan.items.asnaf',
            'permohonan.items.programUtama',
            'permohonan.approvals',
            'ump',
        ])->findOrFail($id);

        $logoPath = public_path('images/logo-alazhar.png');
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('pdf.pencairan', [
            'pencairan' => $pencairan,
            'permohonan' => $pencairan->permohonan,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait');

        $cleanNo = str_replace(['/', '\\'], '-', $pencairan->no_ppd) ?: (string) $pencairan->id;
        $filename = "PPD-{$cleanNo}.pdf";

        return $pdf->stream($filename);
    }
}
