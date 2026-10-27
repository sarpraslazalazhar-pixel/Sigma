<?php

namespace App\Policies;

use App\Models\Ump;
use App\Models\User;

class UmpPolicy
{
    /**
     * Super-admin bypass.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Melihat daftar UMP.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Melihat detail dokumen UMP.
     */
    public function view(User $user, Ump $ump): bool
    {
        if ($user->isKeuangan() || $ump->pj_id === $user->id) {
            return true;
        }

        if ($ump->penerima()->where('user_id', $user->id)->exists()) {
            return true;
        }

        $docDivisiId = $ump->pencairan?->permohonan?->unit?->getDivisiId();

        return $user->canAccessDivision($docDivisiId);
    }

    /**
     * Memperbarui/mengisi item rincian UMP.
     */
    public function update(User $user, Ump $ump): bool
    {
        if (! in_array($ump->status, ['draft', 'perlu_perbaikan'], true)) {
            return false;
        }

        if ($user->isKeuangan() || $ump->pj_id === $user->id) {
            return true;
        }

        if ($ump->penerima()->where('user_id', $user->id)->exists()) {
            return true;
        }

        $docDivisiId = $ump->pencairan?->permohonan?->unit?->getDivisiId();

        return $user->canAccessDivision($docDivisiId);
    }

    /**
     * Menyetujui UMP (tahap Kadiv, Manager Keuangan, atau Kepala Keuangan).
     */
    public function approve(User $user, Ump $ump): bool
    {
        $status = $ump->status;
        if ($status === 'ttd_pj') {
            if ($user->isAdmin() || (bool) $user->is_petugas_input) {
                return true;
            }

            $pj = $ump->pj ?: $ump->pencairan?->permohonan?->user;
            if ($pj && $user->id === $pj->id) {
                return false;
            }

            $pjDivisiId = $pj?->unit?->getDivisiId() ?? $ump->pencairan?->permohonan?->unit?->getDivisiId();
            $pjUnitId = $pj?->unit_id ?? $ump->pencairan?->permohonan?->unit_id;

            $userDivisiId = $user->unit?->getDivisiId();
            $sameDivisi = $pjDivisiId !== null && $userDivisiId !== null && $pjDivisiId === $userDivisiId;
            $sameUnit = $pjUnitId !== null && $user->unit_id !== null && $pjUnitId === $user->unit_id;
            $urutan = $user->leveling?->urutan ?? 0;

            return ($sameDivisi || $sameUnit) && $urutan >= 2;
        }

        if (in_array($status, ['ttd_manager_keuangan', 'ttd_kepala_keuangan'], true)) {
            return $user->isKeuangan() || (bool) $user->is_petugas_input;
        }

        return false;
    }
}
