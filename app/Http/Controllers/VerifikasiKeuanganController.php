<?php

namespace App\Http\Controllers;

use App\Services\VerifikasiKeuangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifikasiKeuanganController extends Controller
{
    public function __invoke(Request $request, string $type, int $id, VerifikasiKeuangan $verification): RedirectResponse
    {
        $request->validate([
            'bukti_koreksi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $verification->resubmit($type, $id, $request->user(), $request->file('bukti_koreksi'));

        return redirect($verification->sourceUrl($type, $id))
            ->with('success', 'Dokumen dikirim kembali ke antrean Verifikasi Keuangan.');
    }
}
