<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Setting;
use App\Models\Ump;
use App\Models\UmpItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UmpWorkflow
{
    /**
     * Hitung ulang total penggunaan, saldo, kekurangan, dan pembulatan UMP (FR-UMP-6, T3, T7).
     */
    public function calculateTotals(Ump $ump): Ump
    {
        $totalPenggunaan = (int) $ump->items()->sum('jumlah');
        $selisih = (int) $ump->jumlah_total - $totalPenggunaan;

        $ump->total_penggunaan = $totalPenggunaan;
        $ump->kekurangan = $selisih < 0 ? abs($selisih) : 0;
        $ump->saldo = $selisih;

        $kelipatan = (int) (Setting::get('pembulatan.kelipatan', 1000) ?: 1000);
        $ump->pembulatan = (int) (round($ump->saldo / $kelipatan) * $kelipatan);

        $ump->save();

        return $ump;
    }

    /**
     * Simpan meta & sinkronisasi baris penggunaan dana (Bagian B form UMP).
     */
    public function saveItems(Ump $ump, array $itemsData, array $meta = [], ?callable $onItemSaved = null): Ump
    {
        return DB::transaction(function () use ($ump, $itemsData, $meta, $onItemSaved) {
            $this->assertEditable($ump);

            $before = $ump->toArray();

            if ($meta !== []) {
                $ump->fill(Arr::only($meta, ['tujuan', 'pj_id']))->save();
            }

            $keptIds = [];

            foreach ($itemsData as $row) {
                $jumlah = (int) ($row['jumlah'] ?? 0);
                if (trim((string) ($row['rincian'] ?? '')) === '' || $jumlah <= 0) {
                    continue;
                }

                $item = $this->upsertItem($ump, $row, $jumlah);
                $keptIds[] = $item->id;

                if ($onItemSaved !== null) {
                    $onItemSaved($item, $row);
                }
            }

            $removedItems = $ump->items()->with('lampiran')->whereNotIn('id', $keptIds)->get();
            foreach ($removedItems as $removedItem) {
                $this->deleteLampiranAttachments($removedItem);
                $removedItem->delete();
            }

            $this->calculateTotals($ump);

            (new AuditLogger)->log('ump.items_saved', $ump, $before, $ump->fresh()->toArray());

            return $ump;
        });
    }

    /**
     * Simpan atau perbarui satu baris penggunaan dana tanpa menghapus baris lain.
     */
    public function saveSingleItem(Ump $ump, array $row, ?callable $afterSaved = null): UmpItem
    {
        return DB::transaction(function () use ($ump, $row, $afterSaved) {
            $this->assertEditable($ump);

            $jumlah = (int) ($row['jumlah'] ?? 0);
            if (trim((string) ($row['rincian'] ?? '')) === '' || $jumlah <= 0) {
                throw new RuntimeException('Rincian dan jumlah penggunaan dana harus diisi.');
            }

            $before = $ump->toArray();
            $item = $this->upsertItem($ump, $row, $jumlah);

            if ($afterSaved !== null) {
                $afterSaved($item);
            }

            $this->calculateTotals($ump);

            (new AuditLogger)->log('ump.item_saved', $ump, $before, $ump->fresh()->toArray());

            return $item->fresh('lampiran');
        });
    }

    /**
     * Hapus satu baris penggunaan dana beserta lampiran buktinya dan hitung ulang total.
     */
    public function deleteItem(Ump $ump, int $itemId): Ump
    {
        return DB::transaction(function () use ($ump, $itemId) {
            $this->assertEditable($ump);

            $before = $ump->toArray();
            $item = $ump->items()->with('lampiran')->whereKey($itemId)->first();

            if ($item) {
                $this->deleteLampiranAttachments($item);
                $item->delete();
            }

            $this->calculateTotals($ump);

            (new AuditLogger)->log('ump.item_deleted', $ump, $before, $ump->fresh()->toArray());

            return $ump->fresh();
        });
    }

    private function upsertItem(Ump $ump, array $row, int $jumlah): UmpItem
    {
        $attributes = [
            'tgl' => $row['tgl'],
            'rincian' => $row['rincian'],
            'no_bukti' => $row['no_bukti'] ?? null,
            'jumlah' => $jumlah,
            'penerima_id' => ! empty($row['penerima_id']) ? (int) $row['penerima_id'] : null,
        ];

        if (! empty($row['id'])) {
            $item = $ump->items()->whereKey($row['id'])->first();
            if ($item) {
                $item->update($attributes);

                return $item;
            }
        }

        return $ump->items()->create($attributes);
    }

    private function deleteLampiranAttachments(UmpItem $item): void
    {
        foreach ($item->lampiran as $lampiran) {
            if ($lampiran->path) {
                Storage::disk('local')->delete($lampiran->path);
            }
            $lampiran->delete();
        }
    }

    /**
     * Periksa apakah pengguna berwenang menyetujui tahap Manager Keuangan.
     */
    public function canApproveManagerKeuangan(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $user->loadMissing('leveling');

        return $user->role === 'keuangan' && ($user->leveling?->urutan ?? 2) === 2;
    }

    /**
     * Periksa apakah pengguna berwenang menyetujui tahap Kepala Divisi Keuangan.
     */
    public function canApproveKepalaKeuangan(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $user->loadMissing('leveling');

        return $user->role === 'keuangan' && ($user->leveling?->urutan ?? 0) >= 3;
    }

    /**
     * Single Source of Truth: periksa apakah pengguna berwenang menyetujui Laporan pada tahap berjalan.
     */
    public function canUserApprove(User $user, Ump $ump): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($ump->status) {
            'ttd_pj' => $this->canApproveDiperiksaOleh($user, $ump),
            'ttd_manager_keuangan' => $ump->verifikasi_keuangan_at !== null && $this->canApproveManagerKeuangan($user),
            'ttd_kepala_keuangan' => $ump->verifikasi_keuangan_at !== null && $this->canApproveKepalaKeuangan($user),
            default => false,
        };
    }

    /**
     * Periksa apakah pengguna berwenang mengelola keputusan (minta perbaikan / void) sesuai tahap berjalan.
     */
    public function canUserManageDecision(User $user, Ump $ump): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($ump->status) {
            'ttd_pj' => $this->canApproveDiperiksaOleh($user, $ump),
            'ttd_manager_keuangan' => $this->canApproveManagerKeuangan($user),
            'ttd_kepala_keuangan' => $this->canApproveKepalaKeuangan($user),
            default => false,
        };
    }

    private function canApproveDiperiksaOleh(User $user, Ump $ump): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'admin' || (bool) $user->is_petugas_input) {
            return true;
        }

        $ump->loadMissing(['pj.unit', 'pencairan.permohonan.user.unit', 'pencairan.permohonan.unit']);
        $user->loadMissing(['unit', 'leveling']);

        $pj = $ump->pj ?: $ump->pencairan?->permohonan?->user;
        if ($pj && $user->id === $pj->id) {
            return false;
        }

        $pjDivisiId = $pj?->unit?->getDivisiId() ?? $ump->pencairan?->permohonan?->unit?->getDivisiId();
        $pjUnitId = $pj?->unit_id ?? $ump->pencairan?->permohonan?->unit_id;

        $approverDivisiId = $user->unit?->getDivisiId();
        $sameDivisi = $pjDivisiId !== null && $approverDivisiId !== null && $pjDivisiId === $approverDivisiId;
        $sameUnit = $pjUnitId !== null && $user->unit_id !== null && $pjUnitId === $user->unit_id;
        $urutan = $user->leveling?->urutan ?? 0;

        return ($sameDivisi || $sameUnit) && $urutan >= 2;
    }

    /**
     * Ajukan UMP oleh Penanggung Jawab untuk tanda tangan berjenjang (FR-UMP-4).
     */
    public function submitByPj(Ump $ump, User $user, string $catatan = ''): Ump
    {
        return DB::transaction(function () use ($ump, $user, $catatan) {
            $this->assertEditable($ump);

            $this->calculateTotals($ump);

            if ((int) $ump->items()->where('jumlah', '>', 0)->count() === 0) {
                throw new RuntimeException('Laporan harus memiliki minimal satu baris penggunaan dana dengan jumlah lebih dari 0.');
            }

            if ((int) $ump->total_penggunaan > (int) $ump->jumlah_total && trim($catatan) === '') {
                throw new RuntimeException('Total penggunaan melebihi dana diterima. Alasan kekurangan dana wajib diisi.');
            }

            $before = $ump->toArray();

            // Siklus naik saat diajukan ulang setelah Perlu Perbaikan (TECH-SPEC §4)
            $siklus = max(1, (int) $ump->approvals()->max('siklus'));
            if ($ump->status === 'perlu_perbaikan') {
                $siklus++;
            }

            Approval::create([
                'approvable_type' => Ump::class,
                'approvable_id' => $ump->id,
                'siklus' => $siklus,
                'tahap' => 'ttd_pj',
                'keputusan' => 'setuju',
                'approver_id' => $user->id,
                'signer_name' => $user->name,
                'signer_jabatan' => $user->jabatan,
                'signed_at' => now(),
                'recorded_by' => $user->id,
                'catatan' => $catatan !== '' ? $catatan : null,
            ]);

            $ump->status = 'ttd_pj';
            $ump->verifikasi_keuangan_at = null;
            $ump->verifikasi_keuangan_oleh = null;
            $ump->verifikasi_keuangan_catatan = null;
            $ump->save();

            (new AuditLogger)->log('ump.submitted_pj', $ump, $before, $ump->fresh()->toArray());

            // FR-NTF: Notifikasi ke Kadiv / Approver tahap 1 UMP
            $divisiId = $ump->pencairan?->permohonan?->unit?->getDivisiId() ?? $ump->pencairan?->permohonan?->unit_id;
            $kadiv = User::where(function ($q) use ($divisiId) {
                $q->where('unit_id', $divisiId)
                    ->orWhereHas('unit', fn ($uq) => $uq->where('parent_id', $divisiId));
            })->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first();
            if (! $kadiv) {
                $kadiv = User::whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first();
            }
            if ($kadiv) {
                NotificationService::kirim(
                    $kadiv,
                    'dokumen_menunggu_tindakan',
                    [
                        'judul' => 'Dokumen Laporan Menunggu Persetujuan',
                        'no_ppd' => $ump->pencairan?->no_ppd,
                        'jenis' => 'ump',
                        'entity_id' => $ump->id,
                        'url' => route('ump.detail', $ump->id),
                        'pesan' => "Dokumen Laporan No. PPD {$ump->pencairan?->no_ppd} memerlukan tanda tangan persetujuan.",
                    ],
                    "tunggu_ump_{$ump->id}_ttd_kadiv_{$siklus}"
                );
            }

            return $ump;
        });
    }

    /**
     * Persetujuan berjenjang UMP: ttd_pj -> ttd_kadiv -> Verifikasi Keuangan -> ttd_manager_keuangan -> ttd_kepala_keuangan -> selesai.
     */
    public function approve(Ump $ump, User $approver, array $data = []): Ump
    {
        return DB::transaction(function () use ($ump, $approver, $data) {
            $locked = Ump::with([
                'pj.unit',
                'pencairan.permohonan.user.unit',
                'pencairan.permohonan.unit',
                'pencairan.permohonan.kodeAnggaran',
            ])->lockForUpdate()->findOrFail($ump->id);

            $tahap = '';
            $nextStatus = '';

            if ($locked->status === 'ttd_pj') {
                $tahap = 'ttd_kadiv';
                $nextStatus = 'ttd_manager_keuangan';

                if (! $this->canApproveDiperiksaOleh($approver, $locked)) {
                    throw new RuntimeException('Tahap "Diperiksa Oleh" hanya dapat diperiksa oleh TU, Kepala Divisi PJ, atau Manager PJ.');
                }

                $locked->verifikasi_keuangan_at = null;
                $locked->verifikasi_keuangan_oleh = null;
                $locked->verifikasi_keuangan_catatan = null;
            } elseif ($locked->status === 'ttd_manager_keuangan') {
                $tahap = 'ttd_manager_keuangan';
                $nextStatus = 'ttd_kepala_keuangan';

                if (! $this->canApproveManagerKeuangan($approver)) {
                    throw new RuntimeException('Tahap ini memerlukan persetujuan Manager Keuangan.');
                }

                if ($locked->verifikasi_keuangan_at === null) {
                    throw new RuntimeException('Laporan wajib lolos Verifikasi Keuangan terlebih dahulu sebelum disetujui Manager Keuangan.');
                }
            } elseif ($locked->status === 'ttd_kepala_keuangan') {
                $tahap = 'ttd_kepala_keuangan';
                $nextStatus = 'selesai';

                if (! $this->canApproveKepalaKeuangan($approver)) {
                    throw new RuntimeException('Tahap ini memerlukan persetujuan Kepala Divisi Keuangan.');
                }

                if ($locked->verifikasi_keuangan_at === null) {
                    throw new RuntimeException('Laporan wajib lolos Verifikasi Keuangan terlebih dahulu sebelum disetujui Kepala Divisi Keuangan.');
                }

                if (! empty($data['scan_final'])) {
                    $file = $data['scan_final'];
                    $path = $file->store('lampiran/ump/scan_final', 'local');
                    $locked->lampiran()->create([
                        'jenis' => 'scan_final',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize() ?? 0,
                        'sha256' => hash_file('sha256', $file->getRealPath()),
                        'version' => 1,
                        'uploaded_by' => $approver->id,
                    ]);
                }
            } else {
                throw new RuntimeException("Laporan berstatus {$locked->status} tidak dalam antrean persetujuan.");
            }

            $before = $locked->toArray();

            $siklus = max(1, (int) $locked->approvals()->max('siklus'));
            Approval::create([
                'approvable_type' => Ump::class,
                'approvable_id' => $locked->id,
                'siklus' => $siklus,
                'tahap' => $tahap,
                'keputusan' => 'setuju',
                'approver_id' => $approver->id,
                'signer_name' => $data['signer_name'] ?? $approver->name,
                'signer_jabatan' => $data['signer_jabatan'] ?? $approver->jabatan,
                'signed_at' => $data['signed_at'] ?? now(),
                'recorded_by' => Auth::id() ?? $approver->id,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $locked->status = $nextStatus;
            $locked->save();

            if ($nextStatus === 'selesai') {
                $permohonan = $locked->pencairan?->permohonan;
                if ($permohonan) {
                    $permohonan->status = 'selesai';
                    $permohonan->save();
                }
            }

            (new AuditLogger)->log("ump.approved.{$tahap}", $locked, $before, $locked->fresh()->toArray());

            // FR-NTF: Notifikasi ke PJ
            if ($locked->pj) {
                NotificationService::kirim(
                    $locked->pj,
                    'dokumen_diputuskan',
                    [
                        'judul' => 'Dokumen Laporan Disetujui',
                        'no_ppd' => $locked->pencairan?->no_ppd,
                        'jenis' => 'ump',
                        'entity_id' => $locked->id,
                        'url' => route('ump.detail', $locked->id),
                        'keputusan' => 'disetujui',
                        'pesan' => "Dokumen Laporan No. PPD {$locked->pencairan?->no_ppd} telah disetujui pada tahap {$tahap}.",
                    ],
                    "putusan_ump_{$locked->id}_{$nextStatus}_{$siklus}"
                );
            }

            // FR-NTF: Notifikasi ke verifikator atau approver tahap berikutnya
            if ($nextStatus === 'ttd_manager_keuangan') {
                $verifikatorUsers = User::where('is_active', true)
                    ->where(fn ($q) => $q->where('role', 'keuangan')->orWhere('is_petugas_input', true))
                    ->get();
                foreach ($verifikatorUsers as $vu) {
                    NotificationService::kirim(
                        $vu,
                        'dokumen_menunggu_tindakan',
                        [
                            'judul' => 'Laporan Masuk Antrean Verifikasi Keuangan',
                            'no_ppd' => $locked->pencairan?->no_ppd,
                            'jenis' => 'ump',
                            'entity_id' => $locked->id,
                            'url' => route('keuangan.verifikasi'),
                            'pesan' => "Dokumen Laporan No. PPD {$locked->pencairan?->no_ppd} memerlukan Verifikasi Keuangan sebelum persetujuan Manager Keuangan.",
                        ],
                        "tunggu_ump_{$locked->id}_{$nextStatus}_{$siklus}"
                    );
                }
            } elseif ($nextStatus === 'ttd_kepala_keuangan') {
                $kadivKeuanganUsers = User::where('is_active', true)
                    ->where('role', 'keuangan')
                    ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
                    ->get();

                if ($kadivKeuanganUsers->isEmpty()) {
                    $kadivKeuanganUsers = User::where('is_active', true)
                        ->where('role', 'keuangan')
                        ->get();
                }

                foreach ($kadivKeuanganUsers as $kku) {
                    NotificationService::kirim(
                        $kku,
                        'dokumen_menunggu_tindakan',
                        [
                            'judul' => 'Laporan Menunggu TTD Kepala Divisi Keuangan',
                            'no_ppd' => $locked->pencairan?->no_ppd,
                            'jenis' => 'ump',
                            'entity_id' => $locked->id,
                            'url' => route('ump.detail', $locked->id),
                            'pesan' => "Dokumen Laporan No. PPD {$locked->pencairan?->no_ppd} telah disetujui Manager Keuangan dan menunggu persetujuan Kepala Divisi Keuangan.",
                        ],
                        "tunggu_ump_{$locked->id}_{$nextStatus}_{$siklus}"
                    );
                }
            } elseif ($nextStatus === 'selesai') {
                // Cek status pos anggaran (Step 5)
                $permohonan = $locked->pencairan?->permohonan;
                if ($permohonan && $permohonan->kode_anggaran_id) {
                    $tahunAnggaranId = $permohonan->kodeAnggaran?->tahun_anggaran_id;
                    if ($tahunAnggaranId) {
                        $tree = (new BudgetCalculator)->calculateTree($tahunAnggaranId);
                        $nodeData = $tree->get($permohonan->kode_anggaran_id);
                        $nodeStatus = $nodeData['status'] ?? null;
                        if (in_array($nodeStatus, ['Limit', 'Over Budget'], true)) {
                            $adminKeuangan = User::where('is_active', true)
                                ->where(fn ($q) => $q->where('role', 'admin')->orWhere('role', 'keuangan'))
                                ->get();
                            $effectiveBudget = $nodeData['effective_budget'] ?? 0;
                            $pemakaian = ($nodeData['dana_terikat'] ?? 0) + ($nodeData['realisasi'] ?? 0);
                            $persen = $effectiveBudget > 0 ? round(($pemakaian / $effectiveBudget) * 100, 1) : 0;
                            foreach ($adminKeuangan as $ak) {
                                NotificationService::kirim(
                                    $ak,
                                    'anggaran_berubah_status',
                                    [
                                        'kode' => $nodeData['kode'] ?? '',
                                        'nama' => $nodeData['nama'] ?? '',
                                        'status_baru' => $nodeStatus,
                                        'pemakaian_persen' => $persen,
                                        'pesan' => "Pos Anggaran {$nodeData['kode']} - {$nodeData['nama']} mencapai status {$nodeStatus} (Pemakaian: {$persen}%).",
                                    ],
                                    "anggaran_{$permohonan->kode_anggaran_id}_{$nodeStatus}"
                                );
                            }
                        }
                    }
                }
            }

            return $locked;
        });
    }

    /**
     * Minta perbaikan dokumen UMP (kembali ke perlu_perbaikan, siklus berikutnya).
     */
    public function requestRevision(Ump $ump, User $user, string $alasan): Ump
    {
        return DB::transaction(function () use ($ump, $user, $alasan) {
            $locked = Ump::with([
                'pj.unit',
                'pencairan.permohonan.user.unit',
                'pencairan.permohonan.unit',
            ])->lockForUpdate()->findOrFail($ump->id);

            if (! in_array($locked->status, ['ttd_pj', 'ttd_manager_keuangan', 'ttd_kepala_keuangan'], true)) {
                throw new RuntimeException('Hanya Laporan dalam proses persetujuan yang dapat diminta perbaikan.');
            }

            if (! $this->canUserManageDecision($user, $locked)) {
                throw new RuntimeException('Anda tidak berwenang meminta perbaikan Laporan pada tahap ini.');
            }

            $before = $locked->toArray();

            $siklus = max(1, (int) $locked->approvals()->max('siklus'));

            Approval::create([
                'approvable_type' => Ump::class,
                'approvable_id' => $locked->id,
                'siklus' => $siklus,
                'tahap' => 'revisi',
                'keputusan' => 'perlu_perbaikan',
                'approver_id' => $user->id,
                'signer_name' => $user->name,
                'signer_jabatan' => $user->jabatan,
                'signed_at' => now(),
                'recorded_by' => Auth::id() ?? $user->id,
                'catatan' => $alasan,
            ]);

            $locked->status = 'perlu_perbaikan';
            $locked->verifikasi_keuangan_at = null;
            $locked->verifikasi_keuangan_oleh = null;
            $locked->verifikasi_keuangan_catatan = null;
            $locked->save();

            (new AuditLogger)->log('ump.revision_requested', $locked, $before, $locked->fresh()->toArray());

            if ($locked->pj) {
                NotificationService::kirim(
                    $locked->pj,
                    'dokumen_diputuskan',
                    [
                        'judul' => 'Dokumen Laporan Perlu Perbaikan',
                        'no_ppd' => $locked->pencairan?->no_ppd,
                        'jenis' => 'ump',
                        'entity_id' => $locked->id,
                        'url' => route('ump.detail', $locked->id),
                        'keputusan' => 'perlu_perbaikan',
                        'pesan' => "Dokumen Laporan No. PPD {$locked->pencairan?->no_ppd} memerlukan perbaikan: {$alasan}",
                    ],
                    "putusan_ump_{$locked->id}_perlu_perbaikan_{$siklus}"
                );
            }

            return $locked;
        });
    }

    /**
     * Batalkan dokumen UMP.
     */
    public function cancel(Ump $ump, User $user, string $alasan): Ump
    {
        return DB::transaction(function () use ($ump, $user, $alasan) {
            $locked = Ump::with([
                'pj.unit',
                'pencairan.permohonan.user.unit',
                'pencairan.permohonan.unit',
            ])->lockForUpdate()->findOrFail($ump->id);

            if ($locked->status === 'selesai' || $locked->status === 'dibatalkan') {
                throw new RuntimeException('Laporan tidak dapat di-void pada status saat ini.');
            }

            if (! $this->canUserManageDecision($user, $locked)) {
                throw new RuntimeException('Anda tidak berwenang me-void Laporan pada tahap ini.');
            }

            $before = $locked->toArray();

            Approval::create([
                'approvable_type' => Ump::class,
                'approvable_id' => $locked->id,
                'siklus' => max(1, (int) $locked->approvals()->max('siklus')),
                'tahap' => 'pembatalan',
                'keputusan' => 'batal',
                'approver_id' => $user->id,
                'signer_name' => $user->name,
                'signer_jabatan' => $user->jabatan,
                'signed_at' => now(),
                'recorded_by' => Auth::id() ?? $user->id,
                'catatan' => $alasan,
            ]);

            $locked->status = 'dibatalkan';
            $locked->save();

            (new AuditLogger)->log('ump.cancelled', $locked, $before, $locked->fresh()->toArray());

            return $locked;
        });
    }

    private function assertEditable(Ump $ump): void
    {
        if (! in_array($ump->status, ['draft', 'perlu_perbaikan'], true)) {
            throw new RuntimeException("Laporan berstatus {$ump->status} tidak dapat diubah. Hubungi Bagian Keuangan.");
        }
    }
}
