<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DOCUMENT_TABLES = ['permohonan', 'pencairan', 'ump'];

    public function up(): void
    {
        foreach (self::DOCUMENT_TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->text('verifikasi_keuangan_catatan')->nullable();
            });
        }

        Schema::table('pencairan', function (Blueprint $table): void {
            $table->foreignId('dicairkan_oleh')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('permohonan')->select('id')
            ->whereExists(fn ($query) => $query->select('id')->from('pencairan')->whereColumn('permohonan_id', 'permohonan.id'))
            ->chunkById(250, function ($requests): void {
                $ids = $requests->pluck('id');
                $payouts = DB::table('pencairan')->whereIn('permohonan_id', $ids)
                    ->orderBy('id')->get(['id', 'permohonan_id'])->groupBy('permohonan_id');
                $issuances = DB::table('audit_logs')->where('action', 'permohonan.cairkan')
                    ->where('entity_type', 'App\Models\Permohonan')->whereIn('entity_id', $ids)
                    ->orderBy('id')->get(['entity_id', 'user_id'])->groupBy('entity_id');

                foreach ($payouts as $id => $documents) {
                    $events = $issuances->get($id);
                    if ($events === null || $events->count() !== $documents->count()) {
                        continue;
                    }

                    foreach ($documents as $index => $document) {
                        if ($events[$index]->user_id !== null) {
                            DB::table('pencairan')->where('id', $document->id)
                                ->update(['dicairkan_oleh' => $events[$index]->user_id]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('pencairan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dicairkan_oleh');
        });

        foreach (self::DOCUMENT_TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('verifikasi_keuangan_catatan');
            });
        }
    }
};
