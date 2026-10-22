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

    public ?int $tempPlafon = null;

    protected static function booted(): void
    {
        static::saved(function (self $node) {
            if ($node->tempPlafon !== null && $node->tahun_anggaran_id) {
                AlokasiAnggaran::updateOrCreate(
                    [
                        'kode_anggaran_id' => $node->id,
                        'tahun_anggaran_id' => $node->tahun_anggaran_id,
                    ],
                    [
                        'plafon_awal' => (int) $node->tempPlafon,
                    ]
                );
                $node->tempPlafon = null;
            }
        });
    }

    public function getPlafonAttribute(): ?int
    {
        $alokasi = $this->alokasi();

        return $alokasi ? $alokasi->plafon_efektif : null;
    }

    public function setPlafonAttribute($value): void
    {
        $val = $value !== null && $value !== '' ? (int) $value : null;
        if ($this->exists && $this->tahun_anggaran_id) {
            if ($val !== null) {
                AlokasiAnggaran::updateOrCreate(
                    [
                        'kode_anggaran_id' => $this->id,
                        'tahun_anggaran_id' => $this->tahun_anggaran_id,
                    ],
                    [
                        'plafon_awal' => $val,
                    ]
                );
            } else {
                AlokasiAnggaran::where('kode_anggaran_id', $this->id)
                    ->where('tahun_anggaran_id', $this->tahun_anggaran_id)
                    ->delete();
            }
        } else {
            $this->tempPlafon = $val;
        }
    }

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
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

    public function alokasiAnggaran(): HasMany
    {
        return $this->hasMany(AlokasiAnggaran::class, 'kode_anggaran_id');
    }

    public function alokasi(?int $tahunAnggaranId = null): ?AlokasiAnggaran
    {
        $taId = $tahunAnggaranId ?? $this->tahun_anggaran_id;

        return $this->alokasiAnggaran()->where('tahun_anggaran_id', $taId)->first();
    }

    public function getPlafonEfektif(?int $tahunAnggaranId = null): int
    {
        $alokasi = $this->alokasi($tahunAnggaranId);

        return $alokasi ? $alokasi->plafon_efektif : 0;
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
