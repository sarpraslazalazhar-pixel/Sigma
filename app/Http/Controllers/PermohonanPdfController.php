<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;
use App\Services\PermohonanWorkflow;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PermohonanPdfController extends Controller
{
    public function cetak(int $id): Response
    {
        $permohonan = Permohonan::with([
            'unit.parent',
            'user.leveling',
            'pengusul',
            'atasan',
            'kodeAnggaran',
            'sumberDana',
            'items.asnaf',
            'items.programUtama',
            'approvals',
        ])->findOrFail($id);

        abort_unless(Auth::user()?->can('view', $permohonan), 403, 'Anda tidak memiliki hak akses untuk mencetak permohonan ini.');

        $approvalChain = (new PermohonanWorkflow)->resolveApprovalChainForPermohonan($permohonan);

        $logoPath = public_path('images/logo-alazhar.png');
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('pdf.permohonan', [
            'permohonan' => $permohonan,
            'approvalChain' => $approvalChain,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'landscape');

        $cleanNo = $permohonan->no_pengajuan ? str_replace(['/', '\\'], '-', $permohonan->no_pengajuan) : (string) $permohonan->id;
        $filename = "Permohonan-Dana-{$cleanNo}.pdf";

        return $pdf->stream($filename);
    }
}
