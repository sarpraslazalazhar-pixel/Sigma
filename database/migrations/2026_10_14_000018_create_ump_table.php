<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ump', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pencairan_id')->unique()->constrained('pencairan')->restrictOnDelete();
            $table->date('tgl_terima');
            $table->text('tujuan');
            $table->foreignId('pj_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('jumlah_total');
            $table->unsignedBigInteger('total_penggunaan')->default(0);
            $table->bigInteger('saldo')->default(0);
            $table->bigInteger('pembulatan')->default(0);
            $table->bigInteger('kekurangan')->default(0);
            $table->string('status', 40)->default('draft');
            $table->date('jatuh_tempo')->nullable();
            $table->boolean('perlu_verifikasi_keuangan')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ump');
    }
};
