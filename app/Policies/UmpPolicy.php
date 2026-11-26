<?php

namespace App\Policies;

use App\Models\Ump;
use App\Models\User;
use App\Services\UmpWorkflow;

class UmpPolicy
{
    /**
     * Super-admin bypass (kecuali persetujuan berjenjang yang didelegasikan ke UmpWorkflow).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'approve') {
            return null;
        }

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
        if ($user->isKeuangan() || (bool) $user->is_petugas_input || $ump->pj_id === $user->id) {
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
     * Menyetujui UMP (tahap Diperiksa Oleh, Manager Keuangan, atau Kepala Keuangan).
     */
    public function approve(User $user, Ump $ump): bool
    {
        return (new UmpWorkflow)->canUserApprove($user, $ump);
    }
}
