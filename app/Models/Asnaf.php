<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asnaf extends Model
{
    protected $table = 'asnaf';

    protected $fillable = [
        'nama',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
