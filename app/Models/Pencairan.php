<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pencairan extends Model
{
    protected $table = 'pencairan';

    protected $fillable = [
        'permohonan_id',
        'no_ppd',
        'tgl_cair',
        'jumlah',
        'status',
        'alasan_batal',
        'perlu_verifikasi_keuangan',
        'verifikasi_keuangan_at',
        'verifikasi_keuangan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tgl_cair' => 'date',
            'jumlah' => 'integer',
            'perlu_verifikasi_keuangan' => 'boolean',
            'verifikasi_keuangan_at' => 'datetime',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function ump(): HasOne
    {
        return $this->hasOne(Ump::class, 'pencairan_id');
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
