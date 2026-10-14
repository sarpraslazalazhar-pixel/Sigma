<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanItem extends Model
{
    protected $table = 'permohonan_items';

    protected $fillable = [
        'permohonan_id',
        'uraian',
        'asnaf_id',
        'program_utama_id',
        'jumlah_diminta',
        'jumlah_disetujui',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diminta' => 'integer',
            'jumlah_disetujui' => 'integer',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    public function programUtama(): BelongsTo
    {
        return $this->belongsTo(ProgramUtama::class, 'program_utama_id');
    }
}
