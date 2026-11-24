<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki leveling_required_id dokumen in-flight agar sesuai leveling pemohon,
     * bukan perhitungan berbasis nominal sebelum perubahan aturan leveling.
     */
    public function up(): void
    {
        $levelingIdByUrutan = DB::table('levelings')->pluck('id', 'urutan');
        $urutanByLevelingId = DB::table('levelings')->pluck('urutan', 'id');

        if ($levelingIdByUrutan->isEmpty()) {
            return;
        }

        DB::table('permohonan')
            ->whereIn('status', ['diajukan', 'diketahui_kadiv'])
            ->select(['id', 'user_id', 'leveling_required_id'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($levelingIdByUrutan, $urutanByLevelingId): void {
                $userIds = $rows->pluck('user_id')->filter()->unique()->all();
                $userLevelingMap = empty($userIds)
                    ? collect()
                    : DB::table('users')->whereIn('id', $userIds)->pluck('leveling_id', 'id');

                foreach ($rows as $row) {
                    $userLevelingId = $userLevelingMap->get($row->user_id);
                    $rawUrutan = $userLevelingId !== null ? ($urutanByLevelingId->get($userLevelingId) ?? 1) : 1;
                    $submitterUrutan = min(3, max(1, (int) $rawUrutan));
                    $targetLevelingId = $levelingIdByUrutan->get($submitterUrutan);

                    if ($targetLevelingId !== null && (int) $row->leveling_required_id !== (int) $targetLevelingId) {
                        DB::table('permohonan')
                            ->where('id', $row->id)
                            ->update(['leveling_required_id' => $targetLevelingId]);
                    }
                }
            });
    }

    /**
     * Tidak dapat dikembalikan secara deterministik ke perhitungan berbasis nominal legacy.
     */
    public function down(): void {}
};
