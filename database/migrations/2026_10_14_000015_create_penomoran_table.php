<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penomoran', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['jenis', 'unit_id', 'tahun'], 'penomoran_jenis_unit_tahun_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penomoran');
    }
};
