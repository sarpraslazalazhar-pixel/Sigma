<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumberDana extends Model
{
    protected $table = 'sumber_dana';

    protected $fillable = [
        'nama',
        'butuh_asnaf',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'butuh_asnaf' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'sumber_dana_default_id');
    }
}
