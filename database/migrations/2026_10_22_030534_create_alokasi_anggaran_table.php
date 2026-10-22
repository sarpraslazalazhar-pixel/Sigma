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
        Schema::create('alokasi_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kode_anggaran_id')->constrained('kode_anggaran')->cascadeOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->cascadeOnDelete();
            $table->unsignedBigInteger('plafon_awal')->default(0);
            $table->unsignedBigInteger('plafon_revisi')->nullable();
            $table->text('keterangan_revisi')->nullable();
            $table->timestamps();

            $table->unique(['kode_anggaran_id', 'tahun_anggaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alokasi_anggaran');
    }
};
