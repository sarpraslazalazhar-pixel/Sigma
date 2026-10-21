<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

        return ! $this->children()->where('is_active', true)->exists();
    }

    /**
     * Urutkan koleksi kode anggaran secara hierarkis (preorder) dan alami (natural sort: 1.1.1, 1.1.2, ..., 1.1.9, 1.1.10).
     *
     * @param  Collection<int, KodeAnggaran>  $nodes
     * @return Collection<int, KodeAnggaran>
     */
    public static function sortHierarchically(Collection $nodes): Collection
    {
        if ($nodes->isEmpty()) {
            return collect();
        }

        $nodeIds = $nodes->map(fn ($n) => is_array($n) ? $n['id'] : $n->id)->flip();
        $byParent = $nodes->groupBy(fn ($n) => is_array($n) ? $n['parent_id'] : $n->parent_id);

        // Root adalah node dengan parent_id null atau parent-nya tidak ada dalam koleksi saat ini (misal hasil filter)
        $roots = $nodes->filter(function ($n) use ($nodeIds) {
            $parentId = is_array($n) ? $n['parent_id'] : $n->parent_id;

            return $parentId === null || ! isset($nodeIds[$parentId]);
        })->sort(function ($a, $b) {
            $kodeA = is_array($a) ? $a['kode'] : $a->kode;
            $kodeB = is_array($b) ? $b['kode'] : $b->kode;

            return strnatcmp($kodeA, $kodeB);
        });

        $result = collect();
        $walker = function ($parent) use (&$walker, &$result, $byParent) {
            $result->push($parent);
            $parentId = is_array($parent) ? $parent['id'] : $parent->id;
            $children = $byParent->get($parentId, collect())
                ->sort(function ($a, $b) {
                    $kodeA = is_array($a) ? $a['kode'] : $a->kode;
                    $kodeB = is_array($b) ? $b['kode'] : $b->kode;

                    return strnatcmp($kodeA, $kodeB);
                });
            foreach ($children as $child) {
                $walker($child);
            }
        };

        foreach ($roots as $root) {
            $walker($root);
        }

        return $result;
    }
}
