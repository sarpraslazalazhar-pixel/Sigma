<?php

namespace App\Http\Controllers;

use App\Models\Lampiran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyajian lampiran privat (TECH-SPEC 5.5): hanya admin, Keuangan, petugas input,
 * pengunggah, pihak terkait dokumen, atau rekan sedivisi pemilik dokumen.
 */
class LampiranController extends Controller
{
    public function show(int $id): StreamedResponse
    {
        $lampiran = Lampiran::findOrFail($id);
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        abort_unless($this->isAuthorized($user, $lampiran), 403);

        $diskName = Storage::disk('public')->exists($lampiran->path) ? 'public' : 'local';
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($lampiran->path), 404);

        return $disk->response($lampiran->path, $lampiran->original_name, [
            'Content-Disposition' => 'inline; filename="'.$lampiran->original_name.'"',
        ]);
    }

    private function isAuthorized(User $user, Lampiran $lampiran): bool
    {
        if (
            $user->role === 'admin'
            || $user->role === 'keuangan'
            || (bool) $user->is_petugas_input
            || $user->id === $lampiran->uploaded_by
        ) {
            return true;
        }

        $parent = $lampiran->attachable;

        if ($parent instanceof Pencairan) {
            $permohonan = $parent->permohonan;
            if ($permohonan && ($permohonan->user_id === $user->id || $permohonan->pengusul_id === $user->id)) {
                return true;
            }

            $ump = $parent->ump;
            if ($ump && ($ump->pj_id === $user->id || $ump->penerima()->where('user_id', $user->id)->exists())) {
                return true;
            }
        }

        $docUnit = $this->resolveUnit($lampiran);
        $docDivisiId = $docUnit?->getDivisiId();
        $userDivisiId = $user->unit?->getDivisiId();

        return $docDivisiId !== null && $userDivisiId !== null && $docDivisiId === $userDivisiId;
    }

    private function resolveUnit(Lampiran $lampiran): ?Unit
    {
        $parent = $lampiran->attachable;

        return match (true) {
            $parent instanceof Permohonan => $parent->unit,
            $parent instanceof Pencairan => $parent->permohonan?->unit,
            $parent instanceof Ump => $parent->pencairan?->permohonan?->unit,
            default => null,
        };
    }
}
