<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $table = 'units';

    protected $fillable = [
        'parent_id',
        'kode',
        'nama',
        'tipe',
        'sumber_dana_default_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
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
}
