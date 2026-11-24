<?php

namespace App\Policies;

use App\Models\Permohonan;
use App\Models\User;
use App\Services\PermohonanWorkflow;

class PermohonanPolicy
{
    /**
     * Super-admin bypass: Admin memiliki akses ke seluruh operasi kecuali persetujuan permohonan (delegasi ke policy & workflow).
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['approveKadiv', 'approveKeuangan'], true)) {
            return null;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Melihat daftar permohonan dana.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Melihat detail dokumen permohonan dana.
     * Hanya boleh dilihat oleh:
     * - Admin
     * - Bagian Keuangan
     * - Petugas input TU
     * - Pemohon / Pengusul sendiri
     * - Pengguna dari divisi yang sama
     */
    public function view(User $user, Permohonan $permohonan): bool
    {
        if (
            $user->isKeuangan()
            || $user->is_petugas_input
            || $permohonan->user_id === $user->id
            || $permohonan->pengusul_id === $user->id
        ) {
            return true;
        }

        $docDivisiId = $permohonan->unit?->getDivisiId();

        return $user->canAccessDivision($docDivisiId);
    }

    /**
     * Membuat permohonan dana baru.
     * Viewer tidak boleh membuat permohonan.
     */
    public function create(User $user): bool
    {
        return ! $user->isViewer();
    }

    /**
     * Memperbarui dokumen permohonan dana.
     * Pemohon, pengusul, petugas TU, dan admin boleh memperbarui saat status draft atau perlu_perbaikan.
     */
    public function update(User $user, Permohonan $permohonan): bool
    {
        $isAuthorized = $permohonan->user_id === $user->id
            || $permohonan->pengusul_id === $user->id
            || (bool) $user->is_petugas_input;

        return $isAuthorized && in_array($permohonan->status, ['draft', 'perlu_perbaikan'], true);
    }

    /**
     * Membatalkan permohonan dana.
     */
    public function cancel(User $user, Permohonan $permohonan): bool
    {
        $isOwnerOrPengusul = $permohonan->user_id === $user->id
            || $permohonan->pengusul_id === $user->id;

        return $isOwnerOrPengusul && in_array($permohonan->status, ['draft', 'diajukan', 'diketahui_kadiv', 'perlu_perbaikan'], true);
    }

    /**
     * Memeriksa dokumen pada tahap pertama (status diajukan).
     */
    public function approveKadiv(User $user, Permohonan $permohonan): bool
    {
        if ($permohonan->status !== 'diajukan') {
            return false;
        }

        return (new PermohonanWorkflow)->canUserApprove($user, $permohonan);
    }

    /**
     * Menyetujui dokumen pada tahap akhir (status diketahui_kadiv).
     */
    public function approveKeuangan(User $user, Permohonan $permohonan): bool
    {
        if ($permohonan->status !== 'diketahui_kadiv') {
            return false;
        }

        return (new PermohonanWorkflow)->canUserApprove($user, $permohonan);
    }

    /**
     * Menerbitkan pencairan dana (PPD).
     */
    public function cairkan(User $user, Permohonan $permohonan): bool
    {
        $isAuthorized = $user->isKeuangan() || (bool) $user->is_petugas_input;

        return $isAuthorized && $permohonan->status === 'disetujui_keuangan';
    }
}
