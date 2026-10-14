<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leveling extends Model
{
    protected $table = 'levelings';

    protected $fillable = [
        'nama',
        'batas_bawah',
        'batas_atas',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'batas_bawah' => 'integer',
            'batas_atas' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'leveling_id');
    }
}
