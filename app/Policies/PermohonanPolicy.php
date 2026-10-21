<?php

namespace App\Policies;

use App\Models\Permohonan;
use App\Models\Setting;
use App\Models\User;

class PermohonanPolicy
{
    /**
     * Super-admin bypass: Admin memiliki akses ke seluruh operasi.
     */
    public function before(User $user, string $ability): ?bool
    {
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
     * - Pengaju / Pembuat sendiri
     * - Pengguna dari divisi yang sama
     */
    public function view(User $user, Permohonan $permohonan): bool
    {
        if ($user->isKeuangan() || $permohonan->user_id === $user->id) {
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
     * Hanya pemohon sendiri dan hanya saat status draft atau perlu_perbaikan.
     */
    public function update(User $user, Permohonan $permohonan): bool
    {
        return $permohonan->user_id === $user->id
            && in_array($permohonan->status, ['draft', 'perlu_perbaikan'], true);
    }

    /**
     * Membatalkan permohonan dana.
     */
    public function cancel(User $user, Permohonan $permohonan): bool
    {
        $isOwner = $permohonan->user_id === $user->id;

        return $isOwner && in_array($permohonan->status, ['draft', 'diajukan', 'disetujui_atasan', 'diketahui_kadiv', 'perlu_perbaikan'], true);
    }

    /**
     * Memeriksa dokumen pada Tahap 2 (Kepala Divisi).
     */
    public function approveKadiv(User $user, Permohonan $permohonan): bool
    {
        if ($permohonan->user_id === $user->id) {
            return false;
        }

        $isKadiv = $user->isKadiv();
        $sameDivisi = $user->canAccessDivision($permohonan->unit?->getDivisiId());

        return $isKadiv && $sameDivisi && in_array($permohonan->status, ['diajukan', 'disetujui_atasan'], true);
    }

    /**
     * Menyetujui dokumen pada Tahap 3 (Bagian Keuangan).
     */
    public function approveKeuangan(User $user, Permohonan $permohonan): bool
    {
        $isFinanceRole = $user->isKeuangan();
        $isPetugasInput = (bool) $user->is_petugas_input;
        $modeAtasNama = Setting::get('keuangan.mode', 'atas_nama') === 'atas_nama';

        return ($isFinanceRole || ($isPetugasInput && $modeAtasNama))
            && $permohonan->status === 'diketahui_kadiv';
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
