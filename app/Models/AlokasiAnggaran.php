<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiAnggaran extends Model
{
    protected $table = 'alokasi_anggaran';

    protected $fillable = [
        'kode_anggaran_id',
        'tahun_anggaran_id',
        'plafon_awal',
        'plafon_revisi',
        'keterangan_revisi',
    ];

    protected function casts(): array
    {
        return [
            'plafon_awal' => 'integer',
            'plafon_revisi' => 'integer',
        ];
    }

    public function getPlafonEfektifAttribute(): int
    {
        return $this->plafon_revisi ?? $this->plafon_awal ?? 0;
    }

    public function kodeAnggaran(): BelongsTo
    {
        return $this->belongsTo(KodeAnggaran::class, 'kode_anggaran_id');
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class, 'tahun_anggaran_id');
    }
}
