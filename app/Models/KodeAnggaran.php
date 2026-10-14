<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeAnggaran extends Model
{
    protected $table = 'kode_anggaran';

    protected $fillable = [
        'tahun_anggaran_id',
        'parent_id',
        'kode',
        'nama',
        'deskripsi',
        'depth',
        'plafon',
        'budget_status',
        'prev_year_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'plafon' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class, 'tahun_anggaran_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(KodeAnggaran::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(KodeAnggaran::class, 'parent_id');
    }

    public function prevYear(): BelongsTo
    {
        return $this->belongsTo(KodeAnggaran::class, 'prev_year_id');
    }

    public function isLeaf(): bool
    {
        if ($this->relationLoaded('children')) {
            return $this->children->where('is_active', true)->isEmpty();
        }

        return !$this->children()->where('is_active', true)->exists();
    }
}
