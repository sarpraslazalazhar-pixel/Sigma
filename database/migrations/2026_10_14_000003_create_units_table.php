<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->enum('tipe', ['unit', 'divisi'])->default('divisi');
            $table->foreignId('sumber_dana_default_id')->nullable()->constrained('sumber_dana')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
