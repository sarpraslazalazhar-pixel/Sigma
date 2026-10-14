<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $table = 'approvals';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'siklus',
        'tahap',
        'keputusan',
        'approver_id',
        'signer_name',
        'signer_jabatan',
        'signed_at',
        'recorded_by',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'siklus' => 'integer',
            'signed_at' => 'datetime',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
