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
use Illuminate\Support\Collection;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'role',
    'unit_id',
    'atasan_id',
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
     * Kedalaman tampilan pohon organisasi pada hasil `sortHierarchically()` (tidak disimpan ke database).
     */
    public int $hierarchyDepth = 0;

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

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'atasan_id');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(self::class, 'atasan_id');
    }

    /**
     * Dapatkan seluruh ID bawahan (langsung maupun tidak langsung) secara aman terhadap siklus.
     *
     * @return array<int>
     */
    public function getDescendantIds(): array
    {
        $descendants = [];
        $visited = [$this->id => true];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $childIds = static::query()
                ->whereIn('atasan_id', $frontier)
                ->pluck('id')
                ->all();

            $nextFrontier = [];
            foreach ($childIds as $childId) {
                $childId = (int) $childId;
                if (isset($visited[$childId])) {
                    continue;
                }

                $visited[$childId] = true;
                $descendants[] = $childId;
                $nextFrontier[] = $childId;
            }

            $frontier = $nextFrontier;
        }

        return $descendants;
    }

    /**
     * Urutkan koleksi pengguna berdasarkan hierarki Divisi → Unit → Pohon Atasan-Bawahan.
     *
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Unit>|null  $sortedUnits
     * @return Collection<int, User>
     */
    public static function sortHierarchically(Collection $users, ?Collection $sortedUnits = null): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $orderedUnits = $sortedUnits ?? Unit::sortHierarchically();
        $unitOrderIds = $orderedUnits->pluck('id')->all();
        $groupedByUnit = $users->groupBy(fn (User $u) => $u->unit_id ?? 0);

        $siblingSortFn = static function (User $a, User $b): int {
            $activeCmp = ($a->is_active ? 0 : 1) <=> ($b->is_active ? 0 : 1);
            if ($activeCmp !== 0) {
                return $activeCmp;
            }

            $adminCmp = ($a->isAdmin() ? 1 : 0) <=> ($b->isAdmin() ? 1 : 0);
            if ($adminCmp !== 0) {
                return $adminCmp;
            }

            $lvlA = (int) ($a->leveling?->urutan ?? 0);
            $lvlB = (int) ($b->leveling?->urutan ?? 0);
            if ($lvlA !== $lvlB) {
                return $lvlB <=> $lvlA;
            }

            $jabCmp = strnatcasecmp((string) ($a->jabatan ?? ''), (string) ($b->jabatan ?? ''));
            if ($jabCmp !== 0) {
                return $jabCmp;
            }

            return strnatcasecmp($a->name, $b->name);
        };

        $orderedGroupKeys = [];
        foreach ($unitOrderIds as $uid) {
            if ($groupedByUnit->has($uid)) {
                $orderedGroupKeys[] = $uid;
            }
        }
        foreach ($groupedByUnit->keys() as $groupKey) {
            if (! in_array($groupKey, $orderedGroupKeys, true)) {
                $orderedGroupKeys[] = $groupKey;
            }
        }

        $result = collect();

        foreach ($orderedGroupKeys as $groupKey) {
            /** @var Collection<int, User> $group */
            $group = $groupedByUnit->get($groupKey, collect());
            $groupUserIds = $group->pluck('id')->flip();
            $childrenByAtasan = $group->groupBy('atasan_id');

            $roots = $group
                ->filter(fn (User $u) => $u->atasan_id === null || ! isset($groupUserIds[$u->atasan_id]))
                ->sort($siblingSortFn)
                ->values();

            $visited = [];

            $walk = function (User $node, int $depth) use (&$walk, &$result, &$visited, $childrenByAtasan, $siblingSortFn): void {
                if (isset($visited[$node->id])) {
                    return;
                }

                $visited[$node->id] = true;
                $node->hierarchyDepth = $depth;
                $result->push($node);

                $children = $childrenByAtasan->get($node->id, collect())->sort($siblingSortFn);
                foreach ($children as $child) {
                    $walk($child, $depth + 1);
                }
            };

            foreach ($roots as $root) {
                $walk($root, 0);
            }

            foreach ($group->sort($siblingSortFn) as $remaining) {
                if (! isset($visited[$remaining->id])) {
                    $walk($remaining, 0);
                }
            }
        }

        return $result->values();
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

    public function isKadivKeuangan(): bool
    {
        return $this->isKeuangan() && $this->isKadiv();
    }

    /**
     * Kode unit divisi Sekretariat (pemeriksa tetap Tahap 2).
     */
    public const KODE_DIVISI_SEKRETARIAT = 'DIV-SEK';

    /**
     * Periksa apakah pengguna adalah Kepala Divisi Sekretariat.
     */
    public function isKadivSekretariat(): bool
    {
        if (! $this->isKadiv()) {
            return false;
        }

        $unit = $this->unit;
        $divisi = $unit?->tipe === 'divisi' ? $unit : $unit?->parent;

        return $divisi?->kode === self::KODE_DIVISI_SEKRETARIAT;
    }

    /**
     * Ambil pejabat Kepala Divisi Sekretariat yang aktif (pemeriksa tetap Tahap 2).
     */
    public static function getKadivSekretariat(): ?User
    {
        return static::query()
            ->where('is_active', true)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
            ->whereHas('unit', fn ($q) => $q
                ->where('kode', self::KODE_DIVISI_SEKRETARIAT)
                ->orWhereHas('parent', fn ($p) => $p->where('kode', self::KODE_DIVISI_SEKRETARIAT)))
            ->orderBy('id')
            ->first();
    }

    /**
     * Ambil seluruh pejabat berjabatan Kepala Divisi (Leveling >= 3) yang aktif,
     * diurutkan dengan Kadiv Sekretariat lebih dulu.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public static function getPejabatKadiv(): Collection
    {
        return static::query()
            ->with(['leveling', 'unit'])
            ->where('is_active', true)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
            ->get()
            ->sortBy(fn (User $u) => [$u->isKadivSekretariat() ? 0 : 1, $u->name])
            ->values();
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
