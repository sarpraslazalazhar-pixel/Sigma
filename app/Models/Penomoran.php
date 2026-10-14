<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penomoran extends Model
{
    protected $table = 'penomoran';

    protected $fillable = [
        'jenis',
        'unit_id',
        'tahun',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'last_number' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
