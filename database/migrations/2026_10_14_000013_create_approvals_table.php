<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->unsignedSmallInteger('siklus')->default(1);
            $table->string('tahap', 50);
            $table->enum('keputusan', ['setuju', 'perlu_perbaikan', 'tolak', 'batal']);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name')->nullable();
            $table->string('signer_jabatan')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(
                ['approvable_type', 'approvable_id', 'siklus', 'tahap'],
                'approvals_morph_siklus_tahap_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
