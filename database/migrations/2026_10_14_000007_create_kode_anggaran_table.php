<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('kode_anggaran')->restrictOnDelete();
            $table->string('kode');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedTinyInteger('depth')->default(1);
            $table->unsignedBigInteger('plafon')->nullable();
            $table->enum('budget_status', ['draft', 'disahkan'])->default('draft');
            $table->foreignId('prev_year_id')->nullable()->constrained('kode_anggaran')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tahun_anggaran_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_anggaran');
    }
};
