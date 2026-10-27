<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('no_pengajuan')->unique();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('kode_anggaran_id')->constrained('kode_anggaran')->restrictOnDelete();
            $table->string('program');
            $table->string('nama_kegiatan');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->enum('status', [
                'draft',
                'diajukan',
                'diketahui_kadiv',
                'disetujui_keuangan',
                'dicairkan',
                'selesai',
                'perlu_perbaikan',
                'ditolak',
                'dibatalkan',
            ])->default('draft');
            $table->unsignedBigInteger('total_diminta')->default(0);
            $table->unsignedBigInteger('total_disetujui')->nullable();
            $table->text('catatan_over')->nullable();
            $table->foreignId('leveling_required_id')->nullable()->constrained('levelings')->nullOnDelete();
            $table->boolean('butuh_direksi')->default(false);
            $table->boolean('perlu_verifikasi_keuangan')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['kode_anggaran_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan');
    }
};
