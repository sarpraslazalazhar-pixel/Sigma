<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Permohonan extends Model
{
    protected $table = 'permohonan';

    protected $fillable = [
        'no_pengajuan',
        'unit_id',
        'user_id',
        'atasan_id',
        'kode_anggaran_id',
        'program',
        'nama_kegiatan',
        'tgl_mulai',
        'tgl_selesai',
        'sumber_dana_id',
        'status',
        'total_diminta',
        'total_disetujui',
        'catatan_over',
        'leveling_required_id',
        'butuh_direksi',
        'perlu_verifikasi_keuangan',
        'verifikasi_keuangan_at',
        'verifikasi_keuangan_oleh',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
            'total_diminta' => 'integer',
            'total_disetujui' => 'integer',
            'butuh_direksi' => 'boolean',
            'perlu_verifikasi_keuangan' => 'boolean',
            'verifikasi_keuangan_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PermohonanItem::class, 'permohonan_id');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function kodeAnggaran(): BelongsTo
    {
        return $this->belongsTo(KodeAnggaran::class, 'kode_anggaran_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function levelingRequired(): BelongsTo
    {
        return $this->belongsTo(Leveling::class, 'leveling_required_id');
    }

    public function pencairan(): HasOne
    {
        return $this->hasOne(Pencairan::class, 'permohonan_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikasi_keuangan_oleh');
    }
}
