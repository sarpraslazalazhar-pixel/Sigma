<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lampiran', function (Blueprint $table): void {
            $table->enum('jenis', ['bukti', 'scan_final', 'bukti_tf', 'bukti_transfer'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lampiran', function (Blueprint $table): void {
            $table->enum('jenis', ['bukti', 'scan_final', 'bukti_tf'])->change();
        });
    }
};
