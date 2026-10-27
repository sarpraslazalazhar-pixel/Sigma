<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ump_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ump_id')->constrained('ump')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['ump_id', 'user_id']);
        });

        Schema::table('ump_items', function (Blueprint $table) {
            $table->foreignId('penerima_id')->nullable()->after('ump_id')
                ->constrained('ump_penerima')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ump_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penerima_id');
        });

        Schema::dropIfExists('ump_penerima');
    }
};
