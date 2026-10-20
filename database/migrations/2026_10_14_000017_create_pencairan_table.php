<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pencairan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->unique()->constrained('permohonan')->restrictOnDelete();
            $table->string('no_ppd', 100)->unique();
            $table->date('tgl_cair');
            $table->unsignedBigInteger('jumlah');
            $table->string('status', 30)->default('dicairkan');
            $table->text('alasan_batal')->nullable();
            $table->boolean('perlu_verifikasi_keuangan')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pencairan');
    }
};
