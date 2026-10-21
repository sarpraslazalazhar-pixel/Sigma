<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'role',
    'unit_id',
    'leveling_id',
    'jabatan',
    'status_kepegawaian',
    'is_petugas_input',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_petugas_input' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function leveling(): BelongsTo
    {
        return $this->belongsTo(Leveling::class, 'leveling_id');
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'user_id');
    }

    public function ump(): HasMany
    {
        return $this->hasMany(Ump::class, 'pj_id');
    }

    public function getNamaLengkapAttribute(): string
    {
        return $this->name;
    }

    // ==========================================
    // ROLE-BASED ACCESS CONTROL (RBAC) HELPERS
    // ==========================================

    /**
     * Periksa apakah pengguna memiliki salah satu role yang ditentukan.
     *
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();

        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isKeuangan(): bool
    {
        return $this->role === 'keuangan';
    }

    public function isUnit(): bool
    {
        return $this->role === 'unit';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isKadiv(): bool
    {
        return ($this->leveling?->urutan ?? 0) >= 3;
    }

    public function isManager(): bool
    {
        return ($this->leveling?->urutan ?? 0) === 2;
    }

    public function isSupervisor(): bool
    {
        return ($this->leveling?->urutan ?? 0) === 1;
    }

    /**
     * Periksa apakah pengguna memiliki hak akses ke divisi tertentu.
     */
    public function canAccessDivision(?int $divisionId): bool
    {
        if ($this->isAdmin() || $this->isKeuangan()) {
            return true;
        }

        $userDivisiId = $this->unit?->getDivisiId();

        return $divisionId !== null && $userDivisiId !== null && $userDivisiId === $divisionId;
    }
}
