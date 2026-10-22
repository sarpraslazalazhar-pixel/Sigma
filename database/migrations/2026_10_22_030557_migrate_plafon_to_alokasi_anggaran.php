<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rows = DB::table('kode_anggaran')->whereNotNull('plafon')->get();
        $now = now();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'kode_anggaran_id' => $row->id,
                'tahun_anggaran_id' => $row->tahun_anggaran_id,
                'plafon_awal' => $row->plafon,
                'plafon_revisi' => null,
                'keterangan_revisi' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($payload)) {
            DB::table('alokasi_anggaran')->insert($payload);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('alokasi_anggaran')->truncate();
    }
};
