<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Unit extends Model
{
    protected $table = 'units';

    protected $fillable = [
        'parent_id',
        'kode',
        'nama',
        'tipe',
        'urutan',
        'sumber_dana_default_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id')
            ->orderByRaw('CASE WHEN urutan = 0 THEN 65535 ELSE urutan END ASC')
            ->orderBy('kode');
    }

    /**
     * Urutkan koleksi Divisi dan Unit secara hierarkis:
     * setiap Divisi Induk (urut `urutan`, lalu `kode`) diikuti langsung oleh seluruh Unit Anak-nya.
     *
     * @param  Collection<int, Unit>|null  $units
     * @return Collection<int, Unit>
     */
    public static function sortHierarchically(?Collection $units = null): Collection
    {
        $collection = $units ?? static::with('parent')->get();

        if ($collection->isEmpty()) {
            return collect();
        }

        $unitIds = $collection->pluck('id')->flip();
        $byParent = $collection->groupBy('parent_id');

        $sortFn = static function (Unit $a, Unit $b): int {
            $orderA = ($a->urutan ?? 0) > 0 ? (int) $a->urutan : PHP_INT_MAX;
            $orderB = ($b->urutan ?? 0) > 0 ? (int) $b->urutan : PHP_INT_MAX;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            $kodeCmp = strnatcasecmp((string) $a->kode, (string) $b->kode);
            if ($kodeCmp !== 0) {
                return $kodeCmp;
            }

            return strnatcasecmp((string) $a->nama, (string) $b->nama);
        };

        $roots = $collection
            ->filter(fn (Unit $u) => $u->parent_id === null || ! isset($unitIds[$u->parent_id]))
            ->sort($sortFn)
            ->values();

        $result = collect();
        $visited = [];

        $walk = function (Unit $node) use (&$walk, &$result, &$visited, $byParent, $sortFn): void {
            if (isset($visited[$node->id])) {
                return;
            }

            $visited[$node->id] = true;
            $result->push($node);

            $children = $byParent->get($node->id, collect())->sort($sortFn);
            foreach ($children as $child) {
                $walk($child);
            }
        };

        foreach ($roots as $root) {
            $walk($root);
        }

        foreach ($collection->sort($sortFn) as $remaining) {
            if (! isset($visited[$remaining->id])) {
                $walk($remaining);
            }
        }

        return $result->values();
    }

    /**
     * Format daftar Divisi & Unit terurut hierarkis untuk komponen dropdown `<x-searchable-select>`.
     *
     * @param  Collection<int, Unit>|null  $units
     * @return Collection<int, array{id: int|string, kode: string, nama: string}>
     */
    public static function formatDropdownOptions(?Collection $units = null, bool $stringIds = false): Collection
    {
        $sorted = static::sortHierarchically($units);

        return $sorted->map(fn (Unit $u) => [
            'id' => $stringIds ? (string) $u->id : $u->id,
            'kode' => $u->kode,
            'nama' => $u->parent ? "{$u->parent->nama} — {$u->nama}" : $u->nama,
        ])->values();
    }

    public function sumberDanaDefault(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_default_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_id');
    }

    public function getDivisiId(): ?int
    {
        return $this->tipe === 'divisi' ? $this->id : $this->parent_id;
    }

    public function getDivisiIdAttribute(): ?int
    {
        return $this->getDivisiId();
    }

    public function getNamaLengkapAttribute(): string
    {
        if ($this->parent) {
            return "{$this->parent->nama} — {$this->nama}";
        }

        return $this->nama;
    }

    /**
     * Dapatkan semua ID unit dalam divisi yang sama (divisi induk dan semua unit anak).
     *
     * @return array<int>
     */
    public function getSameDivisiUnitIds(): array
    {
        $divisiId = $this->getDivisiId();
        if (! $divisiId) {
            return [$this->id];
        }

        return static::where('id', $divisiId)
            ->orWhere('parent_id', $divisiId)
            ->pluck('id')
            ->toArray();
    }

    public function getManager(): ?User
    {
        // 1. Prioritaskan Manager langsung di unit ini (urutan 2)
        $unitManager = User::where('unit_id', $this->id)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', 2))
            ->where('is_active', true)
            ->first();

        if ($unitManager) {
            return $unitManager;
        }

        // 2. Jika tidak ada, cari manager di divisi induk
        $divisiId = $this->getDivisiId();
        if ($divisiId && $divisiId !== $this->id) {
            $divisiManager = User::where('unit_id', $divisiId)
                ->whereHas('leveling', fn ($q) => $q->where('urutan', 2))
                ->where('is_active', true)
                ->first();

            if ($divisiManager) {
                return $divisiManager;
            }
        }

        // 3. Cari manager di lingkup divisi yang sama (urutan 2, misal Manager Fundraising untuk unit KPw)
        $sameDivisiUnitIds = $this->getSameDivisiUnitIds();

        $divisiManager = User::whereIn('unit_id', $sameDivisiUnitIds)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', 2))
            ->where('is_active', true)
            ->first();

        if ($divisiManager) {
            return $divisiManager;
        }

        // 4. Fallback: cari pimpinan di lingkup divisi yang sama (urutan >= 2)
        return User::whereIn('unit_id', $sameDivisiUnitIds)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Dapatkan Manajer Unit secara ketat hanya di unit ini (urutan === 2, tanpa fallback lintas unit).
     */
    public function getUnitManager(?int $excludeUserId = null): ?User
    {
        return User::where('unit_id', $this->id)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', 2))
            ->where('is_active', true)
            ->when($excludeUserId !== null, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->orderBy('id')
            ->first();
    }
}
