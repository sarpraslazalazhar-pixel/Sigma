<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UmpItem extends Model
{
    protected $table = 'ump_items';

    protected $fillable = [
        'ump_id',
        'penerima_id',
        'tgl',
        'rincian',
        'no_bukti',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'tgl' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function ump(): BelongsTo
    {
        return $this->belongsTo(Ump::class, 'ump_id');
    }

    public function penerimaPivot(): BelongsTo
    {
        return $this->belongsTo(UmpPenerima::class, 'penerima_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }
}
