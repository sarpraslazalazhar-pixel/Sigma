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
            if (! $tujuanId || $tujuanId === (int) $unit->id) {
                continue;
            }

            $this->mergePenomoran((int) $unit->id, $tujuanId);
            DB::table('users')->where('unit_id', $unit->id)->update(['unit_id' => $tujuanId]);
            DB::table('permohonan')->where('unit_id', $unit->id)->update(['unit_id' => $tujuanId]);
            DB::table('units')->where('parent_id', $unit->id)->update(['parent_id' => $tujuanId]);

            // Jangan blokir migrasi berikutnya bila masih ada referensi tak terduga.
            if (DB::table('permohonan')->where('unit_id', $unit->id)->exists()) {
                continue;
            }

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

    /**
     * Penomoran unik per (jenis, unit_id, tahun): gabungkan nomor terakhir sebelum pindah unit.
     */
    private function mergePenomoran(int $dariId, int $keId): void
    {
        foreach (DB::table('penomoran')->where('unit_id', $dariId)->get() as $row) {
            $existing = DB::table('penomoran')
                ->where('unit_id', $keId)
                ->where('jenis', $row->jenis)
                ->where('tahun', $row->tahun)
                ->first();

            if (! $existing) {
                DB::table('penomoran')->where('id', $row->id)->update(['unit_id' => $keId]);

                continue;
            }

            DB::table('penomoran')->where('id', $existing->id)
                ->update(['last_number' => max((int) $existing->last_number, (int) $row->last_number)]);
            DB::table('penomoran')->where('id', $row->id)->delete();
        }
    }

    public function down(): void
    {
        // Data cleanup migration: Unit Direksi merged permanently into parent divisions.
    }
};
