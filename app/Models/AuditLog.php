<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'on_behalf_of',
        'action',
        'entity_type',
        'entity_id',
        'before',
        'after',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('AuditLog bersifat append-only dan tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new RuntimeException('AuditLog bersifat append-only dan tidak boleh dihapus.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
