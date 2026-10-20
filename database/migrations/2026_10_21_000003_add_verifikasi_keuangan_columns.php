<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->timestamp('verifikasi_keuangan_at')->nullable()->after('perlu_verifikasi_keuangan');
            $table->foreignId('verifikasi_keuangan_oleh')->nullable()->after('verifikasi_keuangan_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('pencairan', function (Blueprint $table) {
            $table->timestamp('verifikasi_keuangan_at')->nullable()->after('perlu_verifikasi_keuangan');
            $table->foreignId('verifikasi_keuangan_oleh')->nullable()->after('verifikasi_keuangan_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('ump', function (Blueprint $table) {
            $table->timestamp('verifikasi_keuangan_at')->nullable()->after('perlu_verifikasi_keuangan');
            $table->foreignId('verifikasi_keuangan_oleh')->nullable()->after('verifikasi_keuangan_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ump', function (Blueprint $table) {
            $table->dropForeign(['verifikasi_keuangan_oleh']);
            $table->dropColumn(['verifikasi_keuangan_oleh', 'verifikasi_keuangan_at']);
        });

        Schema::table('pencairan', function (Blueprint $table) {
            $table->dropForeign(['verifikasi_keuangan_oleh']);
            $table->dropColumn(['verifikasi_keuangan_oleh', 'verifikasi_keuangan_at']);
        });

        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropForeign(['verifikasi_keuangan_oleh']);
            $table->dropColumn(['verifikasi_keuangan_oleh', 'verifikasi_keuangan_at']);
        });
    }
};
