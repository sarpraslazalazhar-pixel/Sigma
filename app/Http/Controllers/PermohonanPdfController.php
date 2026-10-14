<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PermohonanPdfController extends Controller
{
    public function cetak(int $id): Response
    {
        $permohonan = Permohonan::with([
            'unit',
            'user',
            'atasan',
            'kodeAnggaran',
            'sumberDana',
            'items.asnaf',
            'items.programUtama',
            'approvals',
        ])->findOrFail($id);

        $logoPath = public_path('images/logo-sigma.png');
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('pdf.permohonan', [
            'permohonan' => $permohonan,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'landscape');

        $cleanNo = $permohonan->no_pengajuan ? str_replace(['/', '\\'], '-', $permohonan->no_pengajuan) : (string) $permohonan->id;
        $filename = "Permohonan-Dana-{$cleanNo}.pdf";

        return $pdf->stream($filename);
    }
}
