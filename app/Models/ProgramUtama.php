<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramUtama extends Model
{
    protected $table = 'program_utama';

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
