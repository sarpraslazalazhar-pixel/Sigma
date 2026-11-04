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
            $parentId = $unit->parent_id;
            if ($parentId) {
                DB::table('users')->where('unit_id', $unit->id)->update(['unit_id' => $parentId]);
                DB::table('penomoran')->where('unit_id', $unit->id)->update(['unit_id' => $parentId]);
            }
            DB::table('units')->where('id', $unit->id)->delete();
        }
    }

    public function down(): void
    {
        // Data cleanup migration: Unit Direksi merged permanently into parent divisions.
    }
};
