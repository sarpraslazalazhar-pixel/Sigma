<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Pencairan;
use App\Models\Penomoran;
use App\Models\Permohonan;
use App\Models\Setting;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpPenerima;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PermohonanWorkflow
{
    /**
     * Generate nomor pengajuan unik format: {KODE_UNIT}-PMH-{TTTT}-{NNNN}.
     */
    public function generateNoPengajuan(Unit $unit, int $tahun): string
    {
        return DB::transaction(function () use ($unit, $tahun) {
            $penomoran = Penomoran::where('jenis', 'pmh')
                ->where('unit_id', $unit->id)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            if (! $penomoran) {
                $penomoran = Penomoran::create([
                    'jenis' => 'pmh',
                    'unit_id' => $unit->id,
                    'tahun' => $tahun,
                    'last_number' => 0,
                ]);
            }

            $penomoran->increment('last_number');
            $penomoran->refresh();

            return sprintf('%s-PMH-%04d-%04d', strtoupper($unit->kode), $tahun, $penomoran->last_number);
        });
    }

    /**
     * Submit permohonan dana dari status Draft atau Perlu Perbaikan.
     */
    public function submit(Permohonan $permohonan): Permohonan
    {
        return DB::transaction(function () use ($permohonan) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            if (! in_array($locked->status, ['draft', 'perlu_perbaikan'])) {
                throw new RuntimeException("Permohonan dalam status {$locked->status} tidak dapat diajukan.");
            }

            $leaf = KodeAnggaran::findOrFail($locked->kode_anggaran_id);
            if (! $leaf->isLeaf() || ! $leaf->is_active) {
                throw new RuntimeException('Kode anggaran yang dipilih harus berupa pos aktif tingkat terendah (leaf).');
            }

            $ta = TahunAnggaran::findOrFail($leaf->tahun_anggaran_id);
            if ($ta->status !== 'aktif') {
                throw new RuntimeException("Tahun Anggaran {$ta->tahun} tidak berstatus aktif.");
            }

            $itemCount = $locked->items()->count();
            if ($itemCount === 0) {
                throw new RuntimeException('Permohonan harus memiliki minimal satu rincian item.');
            }

            // Hitung total diminta dari rincian item
            $totalDiminta = (int) $locked->items()->sum('jumlah_diminta');
            $locked->total_diminta = $totalDiminta;

            // Tentukan Leveling Required berdasarkan nominal total diminta
            $levelingRequired = Leveling::where('batas_bawah', '<=', $totalDiminta)
                ->where(function ($q) use ($totalDiminta) {
                    $q->whereNull('batas_atas')->orWhere('batas_atas', '>=', $totalDiminta);
                })
                ->orderBy('urutan', 'desc')
                ->first();

            $locked->leveling_required_id = $levelingRequired?->id;

            $locked->butuh_direksi = false;
            // Generate nomor pengajuan jika belum ada (atau jika baru pertama kali diajukan)
            if (empty($locked->no_pengajuan) || str_starts_with($locked->no_pengajuan, 'DRAFT-')) {
                $unit = Unit::findOrFail($locked->unit_id);
                $locked->no_pengajuan = $this->generateNoPengajuan($unit, $ta->tahun);
            }

            $before = $locked->toArray();
            $currentCycle = (int) $locked->approvals()->max('siklus') ?: 1;
            if ($locked->status === 'perlu_perbaikan') {
                $currentCycle++;
            }

            $levelingUrutan = $levelingRequired?->urutan ?? 1;

            // Pengecualian: pemohon adalah Kepala Divisi sendiri dan leveling required urutan >= 3
            // -> Tahap 1 & 2 otomatis selesai, dokumen langsung menunggu Keuangan.
            $autoSkipTahap2 = (bool) ($locked->user?->isKadiv() && $levelingUrutan >= 3);

            if (! $autoSkipTahap2) {
                if ($levelingUrutan <= 1) {
                    $nextApprover = Unit::find($locked->unit_id)?->getManager();
                } else {
                    $nextApprover = $this->getKadivDivisi($locked);
                }
                if ($nextApprover) {
                    $locked->atasan_id = $nextApprover->id;
                }
            }

            $locked->status = $autoSkipTahap2 ? 'diketahui_kadiv' : 'diajukan';
            $locked->submitted_at = now();
            $locked->save();

            (new AuditLogger)->log('permohonan.submitted', $locked, $before, $locked->fresh()->toArray());

            if ($autoSkipTahap2) {
                // Catat pemeriksaan Tahap 2 otomatis atas nama Kepala Divisi pembuat.
                Approval::create([
                    'approvable_type' => Permohonan::class,
                    'approvable_id' => $locked->id,
                    'siklus' => $currentCycle,
                    'tahap' => 'kadiv',
                    'keputusan' => 'setuju',
                    'approver_id' => $locked->user_id,
                    'signer_name' => $locked->user->name,
                    'signer_jabatan' => $locked->user->jabatan ?? 'Kepala Divisi',
                    'signed_at' => now(),
                    'recorded_by' => Auth::id() ?? $locked->user_id,
                    'catatan' => 'Dibuat langsung oleh Kepala Divisi (Tahap 1 & 2 selesai otomatis).',
                ]);

                $this->notifyKeuangan($locked, $currentCycle);
            } else {
                $this->notifyNextApprover($locked, $currentCycle);
            }

            return $locked;
        });
    }

    /**
     * Dapatkan Kepala Divisi dari unit permohonan dana.
     */
    public function getKadivDivisi(Permohonan $permohonan): ?User
    {
        $divisiId = Unit::find($permohonan->unit_id)?->getDivisiId();
        if (! $divisiId) {
            return null;
        }

        return User::where('is_active', true)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
            ->where(fn ($q) => $q->where('unit_id', $divisiId)
                ->orWhereHas('unit', fn ($u) => $u->where('parent_id', $divisiId)))
            ->first();
    }

    /**
     * Notifikasi ke approver berikutnya berdasarkan leveling required.
     */
    public function notifyNextApprover(Permohonan $permohonan, int $siklus): void
    {
        $urutan = $permohonan->levelingRequired?->urutan ?? 1;

        if ($urutan <= 1) {
            $approver = Unit::find($permohonan->unit_id)?->getManager();
            $title = 'Permohonan Dana Menunggu Pemeriksaan Manajer Unit';
        } else {
            $approver = $this->getKadivDivisi($permohonan);
            $title = 'Permohonan Dana Menunggu Pemeriksaan Kadiv Divisi';
        }

        if (! $approver) {
            return;
        }

        NotificationService::kirim(
            $approver,
            'dokumen_menunggu_tindakan',
            [
                'judul' => $title,
                'pesan' => "Permohonan dana {$permohonan->no_pengajuan} dari {$permohonan->user?->name} menunggu pemeriksaan Anda.",
                'url' => route('permohonan.detail', $permohonan->id),
                'jenis' => 'permohonan',
                'entity_id' => $permohonan->id,
            ],
            "tunggu_permohonan_{$permohonan->id}_diajukan_{$siklus}"
        );
    }

    /**
     * Notifikasi ke Kepala Divisi (tahap akhir Supervisor-level).
     */
    public function notifyKadivDivisi(Permohonan $permohonan, int $siklus): void
    {
        $kadiv = $this->getKadivDivisi($permohonan);
        if (! $kadiv) {
            return;
        }

        NotificationService::kirim(
            $kadiv,
            'dokumen_menunggu_tindakan',
            [
                'judul' => 'Permohonan Dana Menunggu Persetujuan Akhir Kadiv',
                'pesan' => "Permohonan dana {$permohonan->no_pengajuan} dari {$permohonan->user?->name} menunggu persetujuan akhir Anda.",
                'url' => route('permohonan.detail', $permohonan->id),
                'jenis' => 'permohonan',
                'entity_id' => $permohonan->id,
            ],
            "tunggu_permohonan_{$permohonan->id}_kadiv_{$siklus}"
        );
    }

    /**
     * Notifikasi ke Bagian Keuangan / Kadiv Keuangan (penyetuju Tahap 3).
     */
    public function notifyKeuangan(Permohonan $permohonan, int $siklus): void
    {
        $keuanganUsers = User::where('is_active', true)
            ->where(function ($q) {
                $q->where(fn ($sub) => $sub->where('role', 'keuangan')->whereHas('leveling', fn ($l) => $l->where('urutan', '>=', 3)))
                    ->orWhere('role', 'admin');
            })
            ->get();

        if ($keuanganUsers->isEmpty()) {
            $keuanganUsers = User::whereIn('role', ['keuangan', 'admin'])->where('is_active', true)->get();
        }

        foreach ($keuanganUsers as $keu) {
            NotificationService::kirim(
                $keu,
                'dokumen_menunggu_tindakan',
                [
                    'judul' => 'Permohonan Dana Menunggu Verifikasi Kadiv Keuangan',
                    'pesan' => "Permohonan dana {$permohonan->no_pengajuan} siap diverifikasi oleh Kadiv Keuangan.",
                    'url' => route('permohonan.detail', $permohonan->id),
                    'jenis' => 'permohonan',
                    'entity_id' => $permohonan->id,
                ],
                "tunggu_permohonan_{$permohonan->id}_keuangan_{$siklus}"
            );
        }
    }

    /**
     * Persetujuan berjenjang permohonan dana.
     */
    public function approve(Permohonan $permohonan, User $approver, array $data = []): Permohonan
    {
        return DB::transaction(function () use ($permohonan, $approver, $data) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            $isTuRecorder = (bool) $approver->is_petugas_input || $approver->role === 'admin' || $approver->role === 'keuangan';
            $recordsOnBehalf = $isTuRecorder && ! empty($data['signer_name']);
            if ($locked->user_id === $approver->id && $approver->role !== 'admin' && ! $recordsOnBehalf) {
                throw new RuntimeException('Pengaju tidak dapat menyetujui permohonannya sendiri.');
            }

            $currentCycle = (int) $locked->approvals()->max('siklus') ?: 1;
            if ($locked->status === 'perlu_perbaikan') {
                $currentCycle++;
            }

            $statusAwal = $locked->status;
            $tahap = '';
            $nextStatus = '';
            $docDivisiId = Unit::find($locked->unit_id)?->getDivisiId();
            $levelingUrutan = $locked->levelingRequired?->urutan ?? 1;

            if ($statusAwal === 'diajukan') {
                $tahap = 'kadiv';

                if ($levelingUrutan <= 1) {
                    // Supervisor level: Manajer unit or higher in same division
                    $canApprove = ($approver->isManager() || $approver->isKadiv())
                        && $approver->canAccessDivision($docDivisiId);
                } else {
                    // Manager/Kadiv level: Kadiv in same division
                    $canApprove = $approver->isKadiv()
                        && $approver->canAccessDivision($docDivisiId);
                }

                if (! $canApprove && ! $recordsOnBehalf && $approver->role !== 'admin') {
                    throw new RuntimeException('Anda tidak berwenang menyetujui permohonan ini pada tahap saat ini.');
                }

                $nextStatus = 'diketahui_kadiv';
            } elseif ($statusAwal === 'diketahui_kadiv') {
                $tahap = 'keuangan';

                if ($levelingUrutan <= 1) {
                    // Supervisor level: final approver = Kadiv Divisi (same division)
                    $canApprove = $approver->isKadiv() && $approver->canAccessDivision($docDivisiId);
                    if (! $canApprove && ! $recordsOnBehalf && $approver->role !== 'admin') {
                        throw new RuntimeException('Hanya Kepala Divisi yang berwenang pada tahap akhir permohonan ini.');
                    }

                    // Auto-approve full amount
                    $locked->total_disetujui = $locked->total_diminta;
                    foreach ($locked->items as $item) {
                        $item->jumlah_disetujui = $item->jumlah_diminta;
                        $item->save();
                    }
                    $nextStatus = 'disetujui_keuangan';
                } else {
                    // Manager/Kadiv level: final approver = Kadiv Keuangan
                    $isKadivKeuangan = $approver->isKadivKeuangan();
                    if (! $isKadivKeuangan && ! $recordsOnBehalf && $approver->role !== 'admin') {
                        throw new RuntimeException('Hanya Kadiv Keuangan yang berwenang memberikan persetujuan akhir.');
                    }

                    // Isi jumlah disetujui per item
                    $itemApprovals = $data['items'] ?? [];
                    $totalDisetujui = 0;

                    foreach ($locked->items as $item) {
                        $nominalSetuju = isset($itemApprovals[$item->id])
                            ? (int) $itemApprovals[$item->id]
                            : (int) $item->jumlah_diminta;

                        $item->jumlah_disetujui = $nominalSetuju;
                        $item->save();
                        $totalDisetujui += $nominalSetuju;
                    }

                    $locked->total_disetujui = $totalDisetujui;
                    $nextStatus = 'disetujui_keuangan'; // Dana resmi TERIKAT
                }
            } else {
                throw new RuntimeException("Permohonan dengan status {$statusAwal} tidak dalam antrean persetujuan.");
            }

            $signerName = $data['signer_name'] ?? $approver->name;
            $signerJabatan = $data['signer_jabatan'] ?? ($approver->jabatan ?? ucfirst($approver->role));
            $catatan = $data['catatan'] ?? null;
            $selectedSigner = ! empty($data['signer_user_id'])
                ? User::where('is_active', true)->find($data['signer_user_id'])
                : null;
            if ($selectedSigner) {
                $signerName = $selectedSigner->name;
                $signerJabatan = $data['signer_jabatan'] ?: ($selectedSigner->jabatan ?? $signerJabatan);
            }
            $signedAt = ! empty($data['signed_at']) ? Carbon::parse($data['signed_at']) : now();

            Approval::create([
                'approvable_type' => Permohonan::class,
                'approvable_id' => $locked->id,
                'siklus' => $currentCycle,
                'tahap' => $tahap,
                'keputusan' => 'setuju',
                'approver_id' => $selectedSigner?->id ?? $approver->id,
                'signer_name' => $signerName,
                'signer_jabatan' => $signerJabatan,
                'signed_at' => $signedAt,
                'recorded_by' => Auth::id() ?? $approver->id,
                'catatan' => $catatan,
            ]);

            $before = $locked->toArray();
            $locked->status = $nextStatus;
            $locked->save();

            (new AuditLogger)->log("permohonan.approved.{$tahap}", $locked, $before, $locked->fresh()->toArray());

            if ($locked->user) {
                NotificationService::kirim(
                    $locked->user,
                    'dokumen_diputuskan',
                    [
                        'judul' => 'Permohonan Dana Disetujui',
                        'pesan' => "Permohonan dana {$locked->no_pengajuan} telah disetujui pada tahap {$tahap}.",
                        'url' => route('permohonan.detail', $locked->id),
                        'jenis' => 'permohonan',
                        'entity_id' => $locked->id,
                    ],
                    "putusan_permohonan_{$locked->id}_{$nextStatus}_{$currentCycle}"
                );
            }

            if ($nextStatus === 'diketahui_kadiv') {
                if ($levelingUrutan <= 1) {
                    $this->notifyKadivDivisi($locked, $currentCycle);
                } else {
                    $this->notifyKeuangan($locked, $currentCycle);
                }
            }

            if ($nextStatus === 'disetujui_keuangan') {
                $taId = $locked->kodeAnggaran?->tahun_anggaran_id;
                if ($taId) {
                    $tree = (new BudgetCalculator)->calculateTree($taId);
                    $nodeData = $tree->get($locked->kode_anggaran_id);
                    $nodeStatus = $nodeData['status'] ?? null;
                    if (in_array($nodeStatus, ['Limit', 'Over Budget'], true)) {
                        $adminKeuangan = User::whereIn('role', ['admin', 'keuangan'])->where('is_active', true)->get();
                        foreach ($adminKeuangan as $ak) {
                            NotificationService::kirim(
                                $ak,
                                'anggaran_berubah_status',
                                [
                                    'judul' => "Peringatan Pos Anggaran {$nodeData['status']}",
                                    'pesan' => "Pos anggaran {$nodeData['kode']} - {$nodeData['nama']} mencapai status {$nodeData['status']}.",
                                    'url' => route('dashboard'),
                                    'jenis' => 'anggaran',
                                    'entity_id' => $locked->kode_anggaran_id,
                                ],
                                "anggaran_status_{$locked->kode_anggaran_id}_{$nodeStatus}"
                            );
                        }
                    }
                }
            }

            return $locked;
        });
    }

    /**
     * Minta perbaikan dokumen (status kembali ke perlu_perbaikan).
     */
    public function requestRevision(Permohonan $permohonan, User $user, string $alasan): Permohonan
    {
        return DB::transaction(function () use ($permohonan, $user, $alasan) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            if (! in_array($locked->status, ['diajukan', 'diketahui_kadiv'], true)) {
                throw new RuntimeException('Hanya dokumen dalam proses persetujuan yang dapat diminta perbaikan.');
            }

            $currentCycle = (int) $locked->approvals()->max('siklus') ?: 1;

            Approval::create([
                'approvable_type' => Permohonan::class,
                'approvable_id' => $locked->id,
                'siklus' => $currentCycle,
                'tahap' => 'revisi',
                'keputusan' => 'perlu_perbaikan',
                'approver_id' => $user->id,
                'signer_name' => $user->name,
                'signer_jabatan' => $user->jabatan,
                'signed_at' => now(),
                'recorded_by' => Auth::id() ?? $user->id,
                'catatan' => $alasan,
            ]);

            $before = $locked->toArray();
            $locked->status = 'perlu_perbaikan';
            $locked->save();

            (new AuditLogger)->log('permohonan.revision_requested', $locked, $before, $locked->fresh()->toArray());

            return $locked;
        });
    }

    /**
     * Tolak permohonan dana.
     */
    public function reject(Permohonan $permohonan, User $user, string $alasan): Permohonan
    {
        return DB::transaction(function () use ($permohonan, $user, $alasan) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            if (! in_array($locked->status, ['diajukan', 'diketahui_kadiv'], true)) {
                throw new RuntimeException('Dokumen tidak dalam status yang dapat ditolak.');
            }

            $currentCycle = (int) $locked->approvals()->max('siklus') ?: 1;

            Approval::create([
                'approvable_type' => Permohonan::class,
                'approvable_id' => $locked->id,
                'siklus' => $currentCycle,
                'tahap' => 'penolakan',
                'keputusan' => 'tolak',
                'approver_id' => $user->id,
                'signer_name' => $user->name,
                'signer_jabatan' => $user->jabatan,
                'signed_at' => now(),
                'recorded_by' => Auth::id() ?? $user->id,
                'catatan' => $alasan,
            ]);

            $before = $locked->toArray();
            $locked->status = 'ditolak';
            $locked->save();

            (new AuditLogger)->log('permohonan.rejected', $locked, $before, $locked->fresh()->toArray());

            return $locked;
        });
    }

    /**
     * Batalkan permohonan dana oleh pengaju.
     */
    public function cancel(Permohonan $permohonan, User $user, string $alasan): Permohonan
    {
        return DB::transaction(function () use ($permohonan, $user, $alasan) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            if (in_array($locked->status, ['dicairkan', 'selesai', 'dibatalkan', 'ditolak'])) {
                throw new RuntimeException('Dokumen tidak dapat dibatalkan pada status saat ini.');
            }

            $currentCycle = (int) $locked->approvals()->max('siklus') ?: 1;

            Approval::create([
                'approvable_type' => Permohonan::class,
                'approvable_id' => $locked->id,
                'siklus' => $currentCycle,
                'tahap' => 'pembatalan',
                'keputusan' => 'batal',
                'approver_id' => $user->id,
                'signer_name' => $user->name,
                'signer_jabatan' => $user->jabatan,
                'signed_at' => now(),
                'recorded_by' => Auth::id() ?? $user->id,
                'catatan' => $alasan,
            ]);

            $before = $locked->toArray();
            $locked->status = 'dibatalkan';
            $locked->save();

            (new AuditLogger)->log('permohonan.cancelled', $locked, $before, $locked->fresh()->toArray());

            return $locked;
        });
    }

    public function cairkan(Permohonan $permohonan, User $user, array $data = []): Pencairan
    {
        return DB::transaction(function () use ($permohonan, $user, $data) {
            $isAuthorized = $user->role === 'keuangan' || $user->role === 'admin' || $user->is_petugas_input;
            if (! $isAuthorized) {
                throw new RuntimeException('Tidak memiliki hak untuk mencairkan dana.');
            }

            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            if ($locked->status !== 'disetujui_keuangan') {
                throw new RuntimeException('Hanya permohonan dengan status Disetujui Keuangan yang dapat dicairkan.');
            }

            $noPpd = trim($data['no_ppd'] ?? '');
            if ($noPpd === '') {
                throw new RuntimeException('Nomor PPD wajib diisi.');
            }

            if (Pencairan::where('no_ppd', $noPpd)->exists()) {
                throw new RuntimeException("Nomor PPD {$noPpd} sudah dipakai.");
            }

            $perluVerifikasi = (bool) ($user->is_petugas_input && $user->role !== 'admin' && $user->role !== 'keuangan');

            $pencairan = Pencairan::create([
                'permohonan_id' => $locked->id,
                'no_ppd' => $noPpd,
                'tgl_cair' => $data['tgl_cair'] ?? now()->toDateString(),
                'jumlah' => $locked->total_disetujui,
                'status' => 'dicairkan',
                'perlu_verifikasi_keuangan' => $perluVerifikasi,
            ]);

            $before = $locked->toArray();
            $locked->status = 'dicairkan';
            $locked->save();

            $defaultManager = $locked->unit?->getManager();
            $customPj = ! empty($data['pj_id']) ? User::with('leveling')->find($data['pj_id']) : null;
            if ($customPj && ($customPj->leveling?->urutan ?? 0) >= 2) {
                $pjId = $customPj->id;
            } elseif ($defaultManager) {
                $pjId = $defaultManager->id;
            } else {
                $fallback = User::whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))->where('is_active', true)->first();
                $pjId = $fallback?->id ?? $locked->user_id;
            }
            $tenggat = (int) Setting::get('ump.tenggat_hari', 14);
            $jatuhTempo = Carbon::parse($pencairan->tgl_cair)->addDays($tenggat)->toDateString();

            $ump = Ump::create([
                'pencairan_id' => $pencairan->id,
                'tgl_terima' => $pencairan->tgl_cair,
                'tujuan' => $locked->nama_kegiatan,
                'pj_id' => $pjId,
                'jumlah_total' => $pencairan->jumlah,
                'total_penggunaan' => 0,
                'saldo' => $pencairan->jumlah,
                'pembulatan' => 0,
                'kekurangan' => 0,
                'status' => 'draft',
                'jatuh_tempo' => $jatuhTempo,
                'perlu_verifikasi_keuangan' => $perluVerifikasi,
            ]);

            (new AuditLogger)->log('permohonan.cairkan', $locked, $before, $locked->fresh()->toArray());

            $penerimaIds = array_values(array_unique(array_map('intval', $data['penerima_ids'] ?? [])));
            foreach ($penerimaIds as $penerimaUserId) {
                $penerima = UmpPenerima::firstOrCreate([
                    'ump_id' => $ump->id,
                    'user_id' => $penerimaUserId,
                ]);

                $penerimaUser = User::find($penerimaUserId);
                if ($penerimaUser && $penerima->wasRecentlyCreated) {
                    NotificationService::kirim(
                        $penerimaUser,
                        'dokumen_diputuskan',
                        [
                            'judul' => 'Anda Ditunjuk sebagai Penerima Dana UMP',
                            'pesan' => "Anda ditunjuk untuk melaporkan penggunaan dana pada UMP {$pencairan->no_ppd}.",
                            'url' => route('ump.detail', $ump->id),
                        ],
                        "ump_penerima_{$ump->id}_{$penerimaUserId}"
                    );
                }
            }

            if ($locked->user) {
                NotificationService::kirim(
                    $locked->user,
                    'dokumen_diputuskan',
                    [
                        'judul' => 'Permohonan Dana Telah Dicairkan',
                        'pesan' => "Permohonan dana {$locked->no_pengajuan} telah dicairkan dengan nomor PPD {$pencairan->no_ppd}.",
                        'url' => route('pencairan.index'),
                    ],
                    "putusan_permohonan_{$locked->id}_dicairkan_{$pencairan->id}"
                );
            }

            return $pencairan;
        });
    }

    public function batalkanPencairan(Pencairan $pencairan, User $user, string $alasan): Pencairan
    {
        return DB::transaction(function () use ($pencairan, $alasan) {
            $lockedPencairan = Pencairan::lockForUpdate()->findOrFail($pencairan->id);
            $lockedPermohonan = Permohonan::lockForUpdate()->findOrFail($lockedPencairan->permohonan_id);
            $lockedUmp = Ump::lockForUpdate()->where('pencairan_id', $lockedPencairan->id)->first();

            $before = $lockedPencairan->toArray();

            $lockedPencairan->status = 'dibatalkan';
            $lockedPencairan->alasan_batal = $alasan;
            $lockedPencairan->save();

            if ($lockedUmp) {
                $lockedUmp->status = 'dibatalkan';
                $lockedUmp->save();
            }

            $lockedPermohonan->status = 'disetujui_keuangan';
            $lockedPermohonan->save();

            (new AuditLogger)->log('pencairan.batal', $lockedPencairan, $before, $lockedPencairan->fresh()->toArray());

            return $lockedPencairan;
        });
    }
}
