<?php

namespace App\Http\Middleware;

use App\Models\TahunAnggaran;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetTahunAnggaran
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('tahun_anggaran_id')) {
            try {
                if (Schema::hasTable('tahun_anggaran')) {
                    $aktif = TahunAnggaran::where('status', 'aktif')->first();
                    if ($aktif) {
                        session(['tahun_anggaran_id' => $aktif->id]);
                    }
                }
            } catch (\Throwable) {
                // Ignore if DB not migrated yet
            }
        }

        return $next($request);
    }
}
