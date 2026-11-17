<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $targets = ['UNT-SEK-DIR', 'UNT-KEU-DIR', 'UNT-FND-DIR'];
        foreach ($targets as $kode) {
            $unit = DB::table('units')->where('kode', $kode)->first();
            if (! $unit) {
                continue;
            }

            $tujuanId = $this->resolveTujuanId($unit);
            if (! $tujuanId) {
                continue;
            }

            DB::table('users')->where('unit_id', $unit->id)->update(['unit_id' => $tujuanId]);
            DB::table('penomoran')->where('unit_id', $unit->id)->update(['unit_id' => $tujuanId]);
            DB::table('permohonan')->where('unit_id', $unit->id)->update(['unit_id' => $tujuanId]);
            DB::table('units')->where('id', $unit->id)->delete();
        }
    }

    /**
     * Unit induk; jika kosong, divisi dengan kode sepadan (UNT-SEK-DIR -> DIV-SEK);
     * jika tetap kosong, divisi pertama. Null berarti unit dibiarkan (tanpa tujuan aman).
     */
    private function resolveTujuanId(object $unit): ?int
    {
        if ($unit->parent_id) {
            return (int) $unit->parent_id;
        }

        $segmen = explode('-', (string) $unit->kode)[1] ?? null;
        if ($segmen) {
            $divisiId = DB::table('units')->where('kode', "DIV-{$segmen}")->value('id');
            if ($divisiId) {
                return (int) $divisiId;
            }
        }

        $divisiId = DB::table('units')->where('tipe', 'divisi')->orderBy('id')->value('id');

        return $divisiId ? (int) $divisiId : null;
    }

    public function down(): void
    {
        // Data cleanup migration: Unit Direksi merged permanently into parent divisions.
    }
};
