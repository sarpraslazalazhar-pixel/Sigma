<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ump extends Model
{
    protected $table = 'ump';

    protected $fillable = [
        'pencairan_id',
        'tgl_terima',
        'tujuan',
        'pj_id',
        'jumlah_total',
        'total_penggunaan',
        'saldo',
        'pembulatan',
        'kekurangan',
        'status',
        'jatuh_tempo',
        'perlu_verifikasi_keuangan',
        'verifikasi_keuangan_at',
        'verifikasi_keuangan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tgl_terima' => 'date',
            'jatuh_tempo' => 'date',
            'jumlah_total' => 'integer',
            'total_penggunaan' => 'integer',
            'saldo' => 'integer',
            'pembulatan' => 'integer',
            'kekurangan' => 'integer',
            'perlu_verifikasi_keuangan' => 'boolean',
            'verifikasi_keuangan_at' => 'datetime',
        ];
    }

    public function pencairan(): BelongsTo
    {
        return $this->belongsTo(Pencairan::class, 'pencairan_id');
    }

    public function pj(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pj_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UmpItem::class, 'ump_id');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikasi_keuangan_oleh');
    }
}
