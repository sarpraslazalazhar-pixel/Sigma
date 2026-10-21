<?php

namespace App\Policies;

use App\Models\KodeAnggaran;
use App\Models\User;

class KodeAnggaranPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'keuangan']);
    }

    public function updatePlafon(User $user, KodeAnggaran $node): bool
    {
        return $user->hasRole(['admin', 'keuangan']);
    }

    public function sahkanBudget(User $user, KodeAnggaran $node): bool
    {
        return $user->hasRole(['admin', 'keuangan']);
    }
}
