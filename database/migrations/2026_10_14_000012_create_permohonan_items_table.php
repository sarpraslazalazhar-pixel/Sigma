<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->text('uraian');
            $table->foreignId('asnaf_id')->nullable()->constrained('asnaf')->nullOnDelete();
            $table->foreignId('program_utama_id')->nullable()->constrained('program_utama')->nullOnDelete();
            $table->unsignedBigInteger('jumlah_diminta');
            $table->unsignedBigInteger('jumlah_disetujui')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_items');
    }
};
