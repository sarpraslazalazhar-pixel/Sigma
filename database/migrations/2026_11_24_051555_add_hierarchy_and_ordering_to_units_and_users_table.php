<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('units', 'urutan')) {
            Schema::table('units', function (Blueprint $table) {
                $table->unsignedSmallInteger('urutan')->default(0)->after('tipe')->index();
            });
        }

        if (! Schema::hasColumn('users', 'atasan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('atasan_id')->nullable()->after('unit_id')->index();
                $table->foreign('atasan_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'atasan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['atasan_id']);
                $table->dropColumn('atasan_id');
            });
        }

        if (Schema::hasColumn('units', 'urutan')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('urutan');
            });
        }
    }
};
