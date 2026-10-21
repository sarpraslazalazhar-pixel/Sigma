<?php

namespace App\Http\Controllers;

use App\Models\Ump;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class UmpPdfController extends Controller
{
    public function cetak(int $id): Response
    {
        $ump = Ump::with([
            'pencairan.permohonan.unit',
            'pj',
            'items',
            'approvals',
            'lampiran',
        ])->findOrFail($id);

        $logoPath = public_path('images/logo-alazhar.png');
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('pdf.ump', [
            'ump' => $ump,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait');

        $cleanNo = str_replace(['/', '\\'], '-', $ump->pencairan?->no_ppd ?? '') ?: (string) $ump->id;
        $filename = "UMP-{$cleanNo}.pdf";

        return $pdf->stream($filename);
    }
}
