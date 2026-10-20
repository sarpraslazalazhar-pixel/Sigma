<?php

namespace App\Http\Controllers;

use App\Models\Lampiran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\Ump;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Penyajian lampiran privat (TECH-SPEC 5.5): hanya admin, Keuangan,
 * pengunggah, atau rekan sedivisi pemilik dokumen.
 */
class LampiranController extends Controller
{
    public function show(int $id)
    {
        $lampiran = Lampiran::findOrFail($id);
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        $allowed = $user->role === 'admin'
            || $user->role === 'keuangan'
            || $user->id === $lampiran->uploaded_by;

        if (! $allowed) {
            $docUnit = $this->resolveUnit($lampiran);
            $docDivisiId = $docUnit?->getDivisiId();
            $userDivisiId = $user->unit?->getDivisiId();
            $allowed = $docDivisiId !== null && $userDivisiId !== null && $docDivisiId === $userDivisiId;
        }

        abort_unless($allowed, 403);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($lampiran->path), 404);

        return $disk->response($lampiran->path, $lampiran->original_name);
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
