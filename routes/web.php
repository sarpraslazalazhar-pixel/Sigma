<?php

use App\Http\Controllers\LampiranController;
use App\Http\Controllers\PencairanPdfController;
use App\Http\Controllers\PermohonanPdfController;
use App\Http\Controllers\UmpPdfController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Keuangan\VerifikasiKeuanganIndex;
use App\Livewire\Laporan\LaporanMonitoringIndex;
use App\Livewire\Master\AuditLogIndex;
use App\Livewire\Master\KodeAnggaranIndex;
use App\Livewire\Master\TahunAnggaranIndex;
use App\Livewire\Master\UnitDivisiIndex;
use App\Livewire\Master\UserIndex;
use App\Livewire\Pencairan\PencairanIndex;
use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Livewire\Permohonan\PermohonanIndex;
use App\Livewire\Reports\LaporanPengeluaranIndex;
use App\Livewire\Ump\UmpDetail;
use App\Livewire\Ump\UmpIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/master/kode-anggaran', KodeAnggaranIndex::class)->name('master.kode-anggaran');
    Route::get('/master/tahun-anggaran', TahunAnggaranIndex::class)->name('master.tahun-anggaran');
    Route::get('/master/unit-divisi', UnitDivisiIndex::class)->name('master.unit-divisi');
    Route::get('/master/users', UserIndex::class)->name('master.users');
    Route::get('/master/audit-logs', AuditLogIndex::class)->name('master.audit-logs');
    Route::get('/laporan/pengeluaran-bulanan', LaporanPengeluaranIndex::class)->name('laporan.pengeluaran-bulanan');
    Route::get('/laporan/monitoring', LaporanMonitoringIndex::class)->name('laporan.monitoring');

    // Permohonan Dana (R2)
    Route::get('/permohonan', PermohonanIndex::class)->name('permohonan.index');
    Route::get('/permohonan/buat', PermohonanCreate::class)->name('permohonan.create');
    Route::get('/permohonan/{id}', PermohonanDetail::class)->name('permohonan.detail');
    Route::get('/permohonan/{id}/cetak-pdf', [PermohonanPdfController::class, 'cetak'])->name('permohonan.cetak-pdf');

    // Pencairan Dana / PPD (R3)
    Route::get('/pencairan', PencairanIndex::class)->name('pencairan.index');
    Route::get('/pencairan/{id}/cetak-pdf', [PencairanPdfController::class, 'cetak'])->name('pencairan.cetak-pdf');

    // Pertanggungjawaban Dana / UMP (R3)
    Route::get('/ump', UmpIndex::class)->name('ump.index');
    Route::get('/ump/{id}', UmpDetail::class)->name('ump.detail');
    Route::get('/ump/{id}/cetak-pdf', [UmpPdfController::class, 'cetak'])->name('ump.cetak-pdf');

    // Verifikasi Keuangan (Mode Atas Nama)
    Route::get('/keuangan/verifikasi', VerifikasiKeuanganIndex::class)->name('keuangan.verifikasi');

    // Lampiran Download/Stream
    Route::get('/lampiran/{id}', [LampiranController::class, 'show'])->name('lampiran.show');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
