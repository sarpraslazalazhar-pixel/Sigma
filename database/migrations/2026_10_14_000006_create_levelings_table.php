<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levelings', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('batas_bawah');
            $table->unsignedBigInteger('batas_atas')->nullable();
            $table->unsignedSmallInteger('urutan')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levelings');
    }
};
