<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Penomoran;
use App\Models\Permohonan;
use App\Models\Setting;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
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

            if (!$penomoran) {
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

            if (!in_array($locked->status, ['draft', 'perlu_perbaikan'])) {
                throw new RuntimeException("Permohonan dalam status {$locked->status} tidak dapat diajukan.");
            }

            $leaf = KodeAnggaran::findOrFail($locked->kode_anggaran_id);
            if (!$leaf->isLeaf() || !$leaf->is_active) {
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
            $locked->status = 'diajukan';
            $locked->submitted_at = now();
            $locked->save();

            (new AuditLogger())->log('permohonan.submitted', $locked, $before, $locked->fresh()->toArray());

            return $locked;
        });
    }

    /**
     * Persetujuan berjenjang permohonan dana.
     */
    public function approve(Permohonan $permohonan, User $approver, array $data = []): Permohonan
    {
        return DB::transaction(function () use ($permohonan, $approver, $data) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            // Approver tidak boleh pengaju sendiri kecuali mode khusus
            if ($locked->user_id === $approver->id && $approver->role !== 'admin') {
                throw new RuntimeException('Pengaju tidak dapat menyetujui permohonannya sendiri.');
            }

            $currentCycle = (int) $locked->approvals()->max('siklus') ?: 1;
            if ($locked->status === 'perlu_perbaikan') {
                $currentCycle++;
            }

            $approverLevelingUrutan = $approver->leveling ? $approver->leveling->urutan : 0;
            $requiredLevelingUrutan = $locked->levelingRequired ? $locked->levelingRequired->urutan : 1;

            $statusAwal = $locked->status;
            $tahap = '';
            $nextStatus = '';

            if ($statusAwal === 'diajukan') {
                $tahap = 'atasan';

                $isDesignatedAtasan = $locked->atasan_id && $locked->atasan_id === $approver->id;
                $isSameUnitManager = ($locked->unit_id === $approver->unit_id) && ($approverLevelingUrutan >= 2);
                $isDivisiManager = ($locked->unit?->parent_id === $approver->unit_id) && ($approverLevelingUrutan >= 2);
                $isKadivOrHigher = $approverLevelingUrutan >= 3;
                $isAdmin = $approver->role === 'admin';

                if (!$isDesignatedAtasan && !$isSameUnitManager && !$isDivisiManager && !$isKadivOrHigher && !$isAdmin) {
                    throw new RuntimeException('Anda tidak memiliki wewenang untuk menyetujui permohonan pada tahap ini.');
                }

                // Cek leveling pengaju
                $creatorLevelingUrutan = $locked->user?->leveling ? $locked->user->leveling->urutan : 1;

                // Jika approver langsung Kepala Divisi (urutan >= 3) atau pengaju adalah Manager (urutan >= 2)
                if ($approverLevelingUrutan >= 3 || $creatorLevelingUrutan >= 2) {
                    $nextStatus = 'diketahui_kadiv';
                } else {
                    $nextStatus = 'disetujui_atasan';
                }
            } elseif ($statusAwal === 'disetujui_atasan') {
                $tahap = 'kadiv';

                if ($approverLevelingUrutan < 3 && $approver->role !== 'admin') {
                    throw new RuntimeException('Tahap ini memerlukan persetujuan Kepala Divisi (Leveling 3).');
                }

                $nextStatus = 'diketahui_kadiv';
            } elseif ($statusAwal === 'diketahui_kadiv') {
                $tahap = 'keuangan';

                $isFinanceRole = $approver->role === 'keuangan';
                $isPetugasInputKeuangan = (bool) $approver->is_petugas_input;

                if (!$isFinanceRole && !$isPetugasInputKeuangan && $approver->role !== 'admin') {
                    throw new RuntimeException('Hanya Bagian Keuangan yang berwenang memberikan persetujuan pencairan.');
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
            } else {
                throw new RuntimeException("Permohonan dengan status {$statusAwal} tidak dalam antrean persetujuan.");
            }

            // Simpan record Approval
            $signerName = $data['signer_name'] ?? $approver->name;
            $signerJabatan = $data['signer_jabatan'] ?? ($approver->jabatan ?? ucfirst($approver->role));
            $catatan = $data['catatan'] ?? null;

            Approval::create([
                'approvable_type' => Permohonan::class,
                'approvable_id' => $locked->id,
                'siklus' => $currentCycle,
                'tahap' => $tahap,
                'keputusan' => 'setuju',
                'approver_id' => $approver->id,
                'signer_name' => $signerName,
                'signer_jabatan' => $signerJabatan,
                'signed_at' => now(),
                'recorded_by' => Auth::id() ?? $approver->id,
                'catatan' => $catatan,
            ]);

            $before = $locked->toArray();
            $locked->status = $nextStatus;
            $locked->save();

            (new AuditLogger())->log("permohonan.approved.{$tahap}", $locked, $before, $locked->fresh()->toArray());

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

            if (!in_array($locked->status, ['diajukan', 'disetujui_atasan', 'diketahui_kadiv'])) {
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

            (new AuditLogger())->log('permohonan.revision_requested', $locked, $before, $locked->fresh()->toArray());

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

            if (!in_array($locked->status, ['diajukan', 'disetujui_atasan', 'diketahui_kadiv'])) {
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

            (new AuditLogger())->log('permohonan.rejected', $locked, $before, $locked->fresh()->toArray());

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

            (new AuditLogger())->log('permohonan.cancelled', $locked, $before, $locked->fresh()->toArray());

            return $locked;
        });
    }
}
