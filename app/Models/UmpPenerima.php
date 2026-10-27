<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UmpPenerima extends Model
{
    protected $table = 'ump_penerima';

    protected $fillable = [
        'ump_id',
        'user_id',
    ];

    public function ump(): BelongsTo
    {
        return $this->belongsTo(Ump::class, 'ump_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UmpItem::class, 'penerima_id');
    }
}
