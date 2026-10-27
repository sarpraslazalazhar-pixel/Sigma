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
use App\Livewire\Master\PengaturanIndex;
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
use Illuminate\Support\Facades\Artisan;
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
    Route::get('/master/users', UserIndex::class)->middleware('can:manage-users')->name('master.users');
    Route::get('/master/audit-logs', AuditLogIndex::class)->middleware('can:view-audit-logs')->name('master.audit-logs');
    Route::get('/master/pengaturan', PengaturanIndex::class)->middleware('can:manage-master-data')->name('master.pengaturan');
    Route::get('/laporan/pengeluaran-bulanan', LaporanPengeluaranIndex::class)->name('laporan.pengeluaran-bulanan');
    Route::get('/laporan/monitoring', LaporanMonitoringIndex::class)->name('laporan.monitoring');

    // Permohonan Dana
    Route::get('/permohonan', PermohonanIndex::class)->name('permohonan.index');
    Route::get('/permohonan/buat', PermohonanCreate::class)->middleware('can:create-permohonan')->name('permohonan.create');
    Route::get('/permohonan/{id}', PermohonanDetail::class)->name('permohonan.detail');
    Route::get('/permohonan/{id}/cetak-pdf', [PermohonanPdfController::class, 'cetak'])->name('permohonan.cetak-pdf');

    // Pencairan Dana / PPD
    Route::get('/pencairan', PencairanIndex::class)->name('pencairan.index');
    Route::get('/pencairan/{id}/cetak-pdf', [PencairanPdfController::class, 'cetak'])->name('pencairan.cetak-pdf');
    Route::get('/pencairan/{id}/pdf', [PencairanPdfController::class, 'cetak'])->name('pencairan.pdf');
    // Pertanggungjawaban Dana / UMP
    Route::get('/ump', UmpIndex::class)->name('ump.index');
    Route::get('/ump/{id}', UmpDetail::class)->name('ump.detail');
    Route::get('/ump/{id}/cetak-pdf', [UmpPdfController::class, 'cetak'])->name('ump.cetak-pdf');

    // Verifikasi Keuangan
    Route::get('/keuangan/verifikasi', VerifikasiKeuanganIndex::class)->middleware('can:verifikasi-keuangan')->name('keuangan.verifikasi');

    // Lampiran Download/Stream
    Route::get('/lampiran/{id}', [LampiranController::class, 'show'])->name('lampiran.show');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

// SEMENTARA: Dinonaktifkan otomatis jika ARTISAN_RUNNER_ENABLED=false di .env
Route::get('/artisan-runner', function () {
    if (! env('ARTISAN_RUNNER_ENABLED', true)) {
        abort(404);
    }

    $secret = (string) request()->query('key', '');
    $expectedKey = (string) env('ARTISAN_KEY', 'sigma123');

    if (! hash_equals($expectedKey, $secret)) {
        abort(403, 'Akses ditolak.');
    }

    $allowedCommands = [
        'migrate' => '1. Run migrate',
        'migrate:fresh --seed' => '2. Run migrate:fresh --seed',
        'optimize:clear' => '3. Run optimize:clear (Cache, Config, Route, View)',
        'storage:link' => '4. Run storage:link',
        'key:generate' => '5. Run key:generate',
        'route:list' => '6. View route:list',
        'config:cache' => '7. Run config:cache',
        'route:cache' => '8. Run route:cache',
        'view:cache' => '9. Run view:cache',
        'ump:remind' => '10. Run ump:remind (Pengingat UMP)',
        'permohonan:remind' => '11. Run permohonan:remind (Pengingat Permohonan)',
    ];

    $cmd = request()->query('cmd');
    if (! $cmd) {
        $encodedKey = urlencode($secret);
        $buttons = '';
        foreach ($allowedCommands as $c => $label) {
            $isDanger = str_contains($c, 'fresh');
            $class = $isDanger ? 'btn btn-danger' : 'btn';
            $confirm = $isDanger ? " onclick=\"return confirm('Peringatan: Semua data akan direset. Lanjutkan?')\"" : '';
            $buttons .= "<a class=\"{$class}\" href=\"?key={$encodedKey}&cmd=".urlencode($c)."\"{$confirm}>{$label}</a>\n";
        }

        return response(<<<HTML
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="utf-8">
                <title>Artisan Runner (Temporary)</title>
                <style>
                    body { font-family: system-ui, sans-serif; padding: 24px; max-width: 640px; margin: auto; line-height: 1.5; }
                    .btn-list { display: flex; flex-direction: column; gap: 8px; margin: 16px 0; }
                    a.btn { display: block; padding: 10px 14px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 500; }
                    a.btn-danger { background: #dc2626; }
                    a.btn:hover { opacity: 0.9; }
                    .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 12px; border-radius: 6px; color: #92400e; font-size: 14px; }
                </style>
            </head>
            <body>
                <h2>Temporary Artisan Runner</h2>
                <div class="warning">
                    <strong>Peringatan Keamanan:</strong> Route ini hanya untuk sementara selama setup. Nonaktifkan via <code>ARTISAN_RUNNER_ENABLED=false</code> di <code>.env</code> setelah selesai.
                </div>
                <div class="btn-list" style="margin-top: 16px;">
                    {$buttons}
                </div>
            </body>
            </html>
        HTML);
    }

    if (! array_key_exists($cmd, $allowedCommands)) {
        abort(400, 'Perintah tidak diizinkan. Hanya perintah terdaftar yang dapat dieksekusi.');
    }

    try {
        $destructive = ['migrate', 'migrate:fresh', 'migrate:fresh --seed'];
        $fullCmd = $cmd;
        if (in_array($cmd, $destructive, true) && ! str_contains($cmd, '--force')) {
            $fullCmd .= ' --force';
        }

        Artisan::call($fullCmd);
        $output = Artisan::output() ?: 'Command dijalankan tanpa output teks.';

        return response('<pre style="background:#1e1e1e;color:#4ade80;padding:16px;border-radius:6px;overflow:auto;white-space:pre-wrap;">'
            .e($output)
            .'</pre><p><a href="?key='.e($secret).'" style="font-family:system-ui;text-decoration:none;color:#2563eb;">&larr; Kembali ke Menu</a></p>');
    } catch (Throwable $e) {
        return response('<pre style="background:#fef2f2;color:#dc2626;padding:16px;border-radius:6px;overflow:auto;white-space:pre-wrap;">Error: '
            .e($e->getMessage())
            .'</pre><p><a href="?key='.e($secret).'" style="font-family:system-ui;text-decoration:none;color:#2563eb;">&larr; Kembali ke Menu</a></p>', 500);
    }
})->middleware('throttle:5,1')->withoutMiddleware('web');
