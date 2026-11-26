<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\KodeAnggaran;
use App\Models\Lampiran;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

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
     * Tentukan siklus persetujuan aktif untuk dokumen permohonan.
     */
    public function getCurrentCycle(Permohonan $permohonan): int
    {
        $maxSiklus = (int) $permohonan->approvals()->max('siklus');
        if ($maxSiklus === 0) {
            return 1;
        }

        if ($permohonan->status === 'perlu_perbaikan') {
            return $maxSiklus;
        }

        $hasRevisionInMaxCycle = $permohonan->approvals()
            ->where('siklus', $maxSiklus)
            ->where('keputusan', 'perlu_perbaikan')
            ->exists();

        return $hasRevisionInMaxCycle ? $maxSiklus + 1 : $maxSiklus;
    }

    /**
     * Tentukan urutan leveling pemohon (Staff tanpa leveling dipetakan ke urutan 1 / Supervisor).
     */
    public function getSubmitterUrutan(User $submitter): int
    {
        $submitter->loadMissing('leveling');

        return max(1, (int) ($submitter->leveling?->urutan ?? 1));
    }

    /**
     * Dapatkan batas atas nominal kewenangan pemohon (Staff tanpa leveling mengikuti batas Supervisor).
     */
    public function getSubmitterBatasAtas(User $submitter): ?int
    {
        $submitter->loadMissing('leveling');

        if ($submitter->leveling) {
            return $submitter->leveling->batas_atas !== null ? (int) $submitter->leveling->batas_atas : null;
        }

        $supervisorLimit = Leveling::where('urutan', 1)->value('batas_atas');

        return $supervisorLimit !== null ? (int) $supervisorLimit : null;
    }

    /**
     * Periksa secara ketat apakah pengguna berada pada divisi tertentu (tanpa bypass role keuangan/admin).
     */
    public function isUserInDivision(User $user, ?int $divisiId): bool
    {
        if ($divisiId === null) {
            return false;
        }

        $user->loadMissing('unit');
        $userDivisiId = $user->unit?->getDivisiId();

        return $userDivisiId !== null && $userDivisiId === $divisiId;
    }

    /**
     * Format label Kepala Divisi yang rapi tanpa duplikasi kata "Divisi".
     */
    protected function formatKadivLabel(string $divisiName): string
    {
        $trimmed = trim($divisiName);
        if ($trimmed === '') {
            return 'Kepala Divisi';
        }

        if (str_starts_with(strtolower($trimmed), 'divisi ')) {
            return "Kepala {$trimmed}";
        }

        return "Kepala Divisi {$trimmed}";
    }

    /**
     * Dapatkan Manajer Unit secara ketat di unit pemohon (urutan === 2).
     */
    public function getManajerUnit(?Unit $unit, ?int $excludeUserId = null): ?User
    {
        return $unit?->getUnitManager($excludeUserId);
    }

    /**
     * Dapatkan Kepala Divisi aktif untuk suatu unit (urutan >= 3 pada divisi terkait, tanpa fallback lintas divisi).
     */
    public function getKadivDivisiForUnit(?Unit $unit, ?int $excludeUserId = null): ?User
    {
        $divisiId = $unit?->getDivisiId();
        if (! $divisiId) {
            return null;
        }

        return User::where('is_active', true)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
            ->when($excludeUserId !== null, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->where(fn ($q) => $q->where('unit_id', $divisiId)
                ->orWhereHas('unit', fn ($u) => $u->where('parent_id', $divisiId)))
            ->orderBy('id')
            ->first();
    }

    /**
     * Dapatkan Kepala Divisi Keuangan aktif.
     */
    public function getKadivKeuangan(?int $excludeUserId = null): ?User
    {
        return User::where('is_active', true)
            ->where('role', 'keuangan')
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
            ->when($excludeUserId !== null, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->orderBy('id')
            ->first();
    }

    /**
     * Single Source of Truth: tentukan rantai persetujuan berdasarkan jabatan pemohon & unitnya.
     *
     * @return array{
     *     submitter_urutan: int,
     *     mode: string,
     *     auto_approve: bool,
     *     auto_skip_step1: bool,
     *     step1: ?array{role_key: string, label: string, approver: ?User},
     *     step2: ?array{role_key: string, label: string, approver: ?User},
     *     missing_approvers: array<int, string>,
     * }
     */
    public function resolveApprovalChain(User $submitter, ?Unit $unit = null, ?int $overrideUrutan = null): array
    {
        $submitter->loadMissing(['leveling', 'unit.parent']);
        $resolvedUnit = $unit ?? $submitter->unit;
        $resolvedUnit?->loadMissing('parent');

        $urutan = $overrideUrutan !== null ? max(1, $overrideUrutan) : $this->getSubmitterUrutan($submitter);
        $unitName = $resolvedUnit?->nama ?? 'Unit';
        $divisiName = $resolvedUnit
            ? ($resolvedUnit->tipe === 'divisi' ? $resolvedUnit->nama : ($resolvedUnit->parent?->nama ?? $resolvedUnit->nama))
            : 'Divisi';

        // 1. Kadiv Keuangan mengajukan -> langsung disetujui otomatis (kecuali snapshot in-flight berada di tingkat lebih rendah)
        if ($submitter->isKadivKeuangan() && ($overrideUrutan === null || $overrideUrutan >= 3)) {
            $roles = $this->resolveStageRoleKeys($submitter, $urutan);

            return [
                'submitter_urutan' => $urutan,
                'mode' => 'kadiv_keuangan',
                'auto_approve' => true,
                'auto_skip_step1' => true,
                'step1' => $roles['step1'],
                'step2' => $roles['step2'],
                'missing_approvers' => [],
            ];
        }

        // 2. Kepala Divisi (non-Keuangan) -> langsung ke Kadiv Keuangan
        if ($urutan >= 3) {
            $roles = $this->resolveStageRoleKeys($submitter, $urutan);
            $kadivKeuangan = $this->getKadivKeuangan($submitter->id);
            $missing = [];
            if (! $kadivKeuangan) {
                $missing[] = 'Kepala Divisi Keuangan belum tersedia. Hubungi admin untuk menetapkan Kepala Divisi Keuangan.';
            }

            return [
                'submitter_urutan' => $urutan,
                'mode' => 'kadiv',
                'auto_approve' => false,
                'auto_skip_step1' => true,
                'step1' => $roles['step1'],
                'step2' => [
                    'role_key' => $roles['step2'] ?? 'kadiv_keuangan',
                    'label' => 'Kepala Divisi Keuangan',
                    'approver' => $kadivKeuangan,
                ],
                'missing_approvers' => $missing,
            ];
        }

        // 3. Manajer -> Kadiv Divisinya -> Kadiv Keuangan (khusus Manajer Keuangan cukup 1 tahap ke Kadiv Keuangan)
        if ($urutan === 2) {
            $kadivDivisi = $this->getKadivDivisiForUnit($resolvedUnit, $submitter->id);
            $kadivKeuangan = $this->getKadivKeuangan($submitter->id);
            $isManagerKeuangan = $kadivDivisi !== null && $kadivDivisi->isKadivKeuangan();
            $roles = $this->resolveStageRoleKeys($submitter, $urutan, $isManagerKeuangan);

            if ($isManagerKeuangan) {
                $missing = [];
                if (! $kadivKeuangan) {
                    $missing[] = 'Kepala Divisi Keuangan belum tersedia. Hubungi admin untuk menetapkan Kepala Divisi Keuangan.';
                }

                return [
                    'submitter_urutan' => $urutan,
                    'mode' => 'manager_keuangan',
                    'auto_approve' => false,
                    'auto_skip_step1' => true,
                    'step1' => $roles['step1'],
                    'step2' => [
                        'role_key' => $roles['step2'] ?? 'kadiv_keuangan',
                        'label' => 'Kepala Divisi Keuangan',
                        'approver' => $kadivKeuangan,
                    ],
                    'missing_approvers' => $missing,
                ];
            }

            $missing = [];
            if (! $kadivDivisi) {
                $missing[] = "Kepala Divisi untuk {$divisiName} belum tersedia. Hubungi admin untuk menetapkan Kepala Divisi.";
            }
            if (! $kadivKeuangan) {
                $missing[] = 'Kepala Divisi Keuangan belum tersedia. Hubungi admin untuk menetapkan Kepala Divisi Keuangan.';
            }

            return [
                'submitter_urutan' => $urutan,
                'mode' => 'manager',
                'auto_approve' => false,
                'auto_skip_step1' => false,
                'step1' => [
                    'role_key' => $roles['step1'] ?? 'kadiv_divisi',
                    'label' => $this->formatKadivLabel($divisiName),
                    'approver' => $kadivDivisi,
                ],
                'step2' => [
                    'role_key' => $roles['step2'] ?? 'kadiv_keuangan',
                    'label' => 'Kepala Divisi Keuangan',
                    'approver' => $kadivKeuangan,
                ],
                'missing_approvers' => $missing,
            ];
        }

        // 4. Supervisor / Staff -> Manajer Unit -> Kadiv Divisinya
        $roles = $this->resolveStageRoleKeys($submitter, $urutan);
        $manajerUnit = $this->getManajerUnit($resolvedUnit, $submitter->id);
        $kadivDivisi = $this->getKadivDivisiForUnit($resolvedUnit, $submitter->id);

        $missing = [];
        if (! $manajerUnit) {
            $missing[] = "Manajer Unit untuk {$unitName} belum tersedia. Hubungi admin untuk menetapkan Manajer Unit.";
        }
        if (! $kadivDivisi) {
            $missing[] = "Kepala Divisi untuk {$divisiName} belum tersedia. Hubungi admin untuk menetapkan Kepala Divisi.";
        }

        return [
            'submitter_urutan' => $urutan,
            'mode' => 'supervisor',
            'auto_approve' => false,
            'auto_skip_step1' => false,
            'step1' => [
                'role_key' => $roles['step1'] ?? 'manajer_unit',
                'label' => "Manajer {$unitName}",
                'approver' => $manajerUnit,
            ],
            'step2' => [
                'role_key' => $roles['step2'] ?? 'kadiv_divisi',
                'label' => $this->formatKadivLabel($divisiName),
                'approver' => $kadivDivisi,
            ],
            'missing_approvers' => $missing,
        ];
    }

    /**
     * Tentukan urutan tingkat efektif pemohon untuk sebuah dokumen Permohonan
     * dengan fallback aman untuk dokumen legacy status 'diajukan'.
     */
    public function resolveEffectiveUrutanForPermohonan(Permohonan $permohonan, User $submitter): int
    {
        $permohonan->loadMissing('levelingRequired');

        if (! in_array($permohonan->status, ['draft', 'perlu_perbaikan'], true) && $permohonan->levelingRequired) {
            $snapshotUrutan = (int) $permohonan->levelingRequired->urutan;
            if ($permohonan->status === 'diajukan' && $snapshotUrutan >= 3) {
                return $this->getSubmitterUrutan($submitter);
            }

            return $snapshotUrutan;
        }

        return $this->getSubmitterUrutan($submitter);
    }

    /**
     * Pemetaan murni (tanpa query DB) antara tingkat pemohon dan role_key tiap tahap persetujuan.
     *
     * @return array{step1: ?string, step2: ?string}
     */
    public function resolveStageRoleKeys(
        User $submitter,
        int $urutan,
        bool $isManagerKeuangan = false,
        bool $isTuFiledKadivKeuangan = false,
    ): array {
        if ($submitter->isKadivKeuangan() && $urutan >= 3) {
            return [
                'step1' => null,
                'step2' => $isTuFiledKadivKeuangan ? 'kadiv_keuangan' : null,
            ];
        }

        if ($urutan >= 3) {
            return [
                'step1' => null,
                'step2' => 'kadiv_keuangan',
            ];
        }

        if ($urutan === 2) {
            return [
                'step1' => $isManagerKeuangan ? null : 'kadiv_divisi',
                'step2' => 'kadiv_keuangan',
            ];
        }

        return [
            'step1' => 'manajer_unit',
            'step2' => 'kadiv_divisi',
        ];
    }

    /**
     * Tentukan rantai persetujuan dari sebuah dokumen Permohonan.
     *
     * @return array{
     *     submitter_urutan: int,
     *     mode: string,
     *     auto_approve: bool,
     *     auto_skip_step1: bool,
     *     step1: ?array{role_key: string, label: string, approver: ?User},
     *     step2: ?array{role_key: string, label: string, approver: ?User},
     *     missing_approvers: array<int, string>,
     * }
     */
    public function resolveApprovalChainForPermohonan(Permohonan $permohonan): array
    {
        $permohonan->loadMissing(['user.leveling', 'user.unit.parent', 'unit.parent', 'levelingRequired']);

        $submitter = $permohonan->user;
        if (! $submitter) {
            return [
                'submitter_urutan' => 1,
                'mode' => 'supervisor',
                'auto_approve' => false,
                'auto_skip_step1' => false,
                'step1' => ['role_key' => 'manajer_unit', 'label' => 'Manajer Unit', 'approver' => null],
                'step2' => ['role_key' => 'kadiv_divisi', 'label' => 'Kepala Divisi', 'approver' => null],
                'missing_approvers' => ['Pejabat pemohon tidak ditemukan.'],
            ];
        }

        $overrideUrutan = $this->resolveEffectiveUrutanForPermohonan($permohonan, $submitter);

        $chain = $this->resolveApprovalChain($submitter, $permohonan->unit, $overrideUrutan);

        // Jika diajukan TU/Admin atas nama Kadiv Keuangan, wajib konfirmasi akhir oleh Kadiv Keuangan (tanpa auto-approve)
        if ($chain['mode'] === 'kadiv_keuangan'
            && ($permohonan->status === 'diketahui_kadiv'
                || ($permohonan->pengusul_id !== null && $permohonan->pengusul_id !== $submitter->id))) {
            $roles = $this->resolveStageRoleKeys($submitter, $overrideUrutan, false, true);
            $chain['auto_approve'] = false;
            $chain['step2'] = [
                'role_key' => $roles['step2'] ?? 'kadiv_keuangan',
                'label' => 'Kepala Divisi Keuangan',
                'approver' => $this->getKadivKeuangan($permohonan->pengusul_id) ?? $submitter,
            ];
        }

        return $chain;
    }

    /**
     * Periksa apakah pengguna merupakan pemohon atau pengusul dokumen yang tidak boleh menyetujui permohonan ini.
     */
    public function isSelfOrPengusul(User $user, Permohonan $permohonan): bool
    {
        if ($permohonan->pengusul_id !== null && $permohonan->pengusul_id === $user->id) {
            return true;
        }

        if ($permohonan->user_id === $user->id) {
            // Pengecualian sah: TU/Admin mengajukan atas nama Kadiv Keuangan -> Kadiv Keuangan berwenang konfirmasi tahap akhir
            $permohonan->loadMissing('user.unit');
            if ($permohonan->status === 'diketahui_kadiv'
                && $permohonan->user?->isKadivKeuangan()
                && $permohonan->pengusul_id !== null
                && $permohonan->pengusul_id !== $user->id) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Single Source of Truth: periksa apakah pengguna berwenang menyetujui / merevisi / menolak permohonan pada tahap saat ini.
     * Admin dan Petugas TU (is_petugas_input) berwenang di semua tahap, kecuali pada permohonan yang ia ajukan atau usulkan sendiri.
     */
    public function canUserApprove(User $user, Permohonan $permohonan): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! in_array($permohonan->status, ['diajukan', 'diketahui_kadiv'], true)) {
            return false;
        }

        if ($this->isSelfOrPengusul($user, $permohonan)) {
            return false;
        }

        if ($user->isAdmin() || $user->is_petugas_input) {
            return true;
        }

        $permohonan->loadMissing(['user.leveling', 'user.unit', 'unit.parent', 'levelingRequired']);
        $user->loadMissing(['leveling', 'unit']);

        $submitter = $permohonan->user;
        if (! $submitter) {
            return false;
        }

        $urutan = $this->resolveEffectiveUrutanForPermohonan($permohonan, $submitter);
        $isTuFiledKadivKeuangan = $submitter->isKadivKeuangan()
            && $permohonan->pengusul_id !== null
            && $permohonan->pengusul_id !== $submitter->id;
        $roles = $this->resolveStageRoleKeys($submitter, $urutan, false, $isTuFiledKadivKeuangan);
        $roleKey = $permohonan->status === 'diajukan' ? $roles['step1'] : $roles['step2'];
        $docDivisiId = $permohonan->unit?->getDivisiId();

        return match ($roleKey) {
            'manajer_unit' => $user->isManager()
                && $user->unit_id !== null
                && $permohonan->unit_id !== null
                && $user->unit_id === $permohonan->unit_id,
            'kadiv_divisi' => $user->isKadiv() && $this->isUserInDivision($user, $docDivisiId),
            'kadiv_keuangan' => $user->isKadivKeuangan(),
            default => false,
        };
    }

    /**
     * Submit permohonan dana dari status Draft atau Perlu Perbaikan.
     */
    public function submit(Permohonan $permohonan): Permohonan
    {
        return DB::transaction(function () use ($permohonan) {
            $locked = Permohonan::lockForUpdate()->findOrFail($permohonan->id);

            if (! in_array($locked->status, ['draft', 'perlu_perbaikan'], true)) {
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

            $pemohon = $locked->user()->with(['leveling', 'unit.parent'])->firstOrFail();
            $unit = Unit::with('parent')->findOrFail($locked->unit_id);

            // Validasi batas atas nominal berdasarkan leveling pemohon
            $batasAtas = $this->getSubmitterBatasAtas($pemohon);
            if ($batasAtas !== null && $totalDiminta > $batasAtas) {
                throw new RuntimeException(
                    'Total pengajuan melebihi batas kewenangan '.$pemohon->name.
                    ' sebagai '.($pemohon->jabatan ?? $pemohon->leveling?->nama ?? 'Supervisor').
                    ' (maks. Rp '.number_format($batasAtas, 0, ',', '.').
                    '). Ubah Pejabat Pemohon ke tingkat yang lebih tinggi.'
                );
            }

            // Validasi ketat keberadaan seluruh approver pada rantai jabatan pemohon ("wajib ada")
            $chain = $this->resolveApprovalChain($pemohon, $unit);
            if (! empty($chain['missing_approvers'])) {
                throw new RuntimeException(implode(' ', $chain['missing_approvers']));
            }

            $submitterUrutan = min(3, max(1, $chain['submitter_urutan']));
            $locked->leveling_required_id = Leveling::where('urutan', $submitterUrutan)->value('id');
            $locked->butuh_direksi = false;

            // Generate nomor pengajuan jika belum ada (atau jika baru pertama kali diajukan)
            if (empty($locked->no_pengajuan) || str_starts_with($locked->no_pengajuan, 'DRAFT-')) {
                $locked->no_pengajuan = $this->generateNoPengajuan($unit, $ta->tahun);
            }

            $before = $locked->toArray();
            $maxSiklus = (int) $locked->approvals()->max('siklus');
            $currentCycle = $locked->status === 'perlu_perbaikan'
                ? ($maxSiklus + 1)
                : max(1, $maxSiklus);

            $actingUserId = Auth::id();
            $isDirectSelfSubmission = $actingUserId !== null
                ? $actingUserId === $pemohon->id
                : ($locked->pengusul_id === null || $locked->pengusul_id === $pemohon->id);

            if ($chain['auto_approve'] && $isDirectSelfSubmission) {
                // Kadiv Keuangan mengajukan -> langsung disetujui otomatis
                $locked->atasan_id = null;
                $locked->status = 'disetujui_keuangan';
                $locked->total_disetujui = $totalDiminta;
                $locked->submitted_at = now();
                $locked->save();

                foreach ($locked->items as $item) {
                    $item->jumlah_disetujui = (int) $item->jumlah_diminta;
                    $item->save();
                }

                Approval::create([
                    'approvable_type' => Permohonan::class,
                    'approvable_id' => $locked->id,
                    'siklus' => $currentCycle,
                    'tahap' => 'keuangan',
                    'keputusan' => 'setuju',
                    'approver_id' => $pemohon->id,
                    'signer_name' => $pemohon->name,
                    'signer_jabatan' => $pemohon->jabatan ?? 'Kepala Divisi Keuangan',
                    'signed_at' => now(),
                    'recorded_by' => Auth::id() ?? $pemohon->id,
                    'catatan' => 'Disetujui otomatis (diajukan langsung oleh Kepala Divisi Keuangan).',
                ]);

                (new AuditLogger)->log('permohonan.submitted', $locked, $before, $locked->fresh()->toArray());
                (new AuditLogger)->log('permohonan.approved.keuangan', $locked, $before, $locked->fresh()->toArray());

                $this->notifyBudgetThresholdIfNeeded($locked);

                return $locked;
            }

            if ($chain['auto_skip_step1']) {
                // Kadiv (non-Keuangan) atau Manajer Keuangan -> langsung menunggu persetujuan akhir Kadiv Keuangan
                $locked->atasan_id = null;
                $locked->total_disetujui = null;
                $locked->status = 'diketahui_kadiv';
                $locked->submitted_at = now();
                $locked->save();

                (new AuditLogger)->log('permohonan.submitted', $locked, $before, $locked->fresh()->toArray());

                if ($chain['mode'] === 'kadiv') {
                    Approval::create([
                        'approvable_type' => Permohonan::class,
                        'approvable_id' => $locked->id,
                        'siklus' => $currentCycle,
                        'tahap' => 'kadiv',
                        'keputusan' => 'setuju',
                        'approver_id' => $pemohon->id,
                        'signer_name' => $pemohon->name,
                        'signer_jabatan' => $pemohon->jabatan ?? 'Kepala Divisi',
                        'signed_at' => now(),
                        'recorded_by' => Auth::id() ?? $pemohon->id,
                        'catatan' => 'Diajukan langsung oleh Kepala Divisi (diteruskan ke Kepala Divisi Keuangan).',
                    ]);
                }

                $this->notifyKeuangan($locked, $currentCycle);

                return $locked;
            }

            // Supervisor -> Manajer Unit, atau Manajer -> Kadiv Divisi
            $locked->atasan_id = $chain['step1']['approver']?->id;
            $locked->total_disetujui = null;
            $locked->status = 'diajukan';
            $locked->submitted_at = now();
            $locked->save();

            (new AuditLogger)->log('permohonan.submitted', $locked, $before, $locked->fresh()->toArray());

            $this->notifyNextApprover($locked, $currentCycle);

            return $locked;
        });
    }

    /**
     * Dapatkan Kepala Divisi dari unit permohonan dana (mengecualikan pemohon sendiri).
     */
    public function getKadivDivisi(Permohonan $permohonan): ?User
    {
        $unit = $permohonan->relationLoaded('unit') ? $permohonan->unit : Unit::find($permohonan->unit_id);

        return $this->getKadivDivisiForUnit($unit, $permohonan->user_id);
    }

    /**
     * Notifikasi ke approver tahap pertama berdasarkan rantai jabatan pemohon.
     */
    public function notifyNextApprover(Permohonan $permohonan, int $siklus): void
    {
        $chain = $this->resolveApprovalChainForPermohonan($permohonan);
        $step1 = $chain['step1'];
        $approver = $step1['approver'] ?? null;

        if (! $approver) {
            return;
        }

        $title = 'Permohonan Dana Menunggu Pemeriksaan '.($step1['label'] ?? 'Atasan');

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
     * Notifikasi ke Kepala Divisi (tahap akhir rantai Supervisor).
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
     * Notifikasi ke Kadiv Keuangan (penyetuju akhir rantai Manajer / Kadiv).
     */
    public function notifyKeuangan(Permohonan $permohonan, int $siklus): void
    {
        $keuanganUsers = User::where('is_active', true)
            ->where(function ($q) {
                $q->where(fn ($sub) => $sub->where('role', 'keuangan')->whereHas('leveling', fn ($l) => $l->where('urutan', '>=', 3)))
                    ->orWhere('role', 'admin');
            })
            ->get();

        foreach ($keuanganUsers as $keu) {
            NotificationService::kirim(
                $keu,
                'dokumen_menunggu_tindakan',
                [
                    'judul' => 'Permohonan Dana Menunggu Persetujuan Kadiv Keuangan',
                    'pesan' => "Permohonan dana {$permohonan->no_pengajuan} menunggu persetujuan akhir Kepala Divisi Keuangan.",
                    'url' => route('permohonan.detail', $permohonan->id),
                    'jenis' => 'permohonan',
                    'entity_id' => $permohonan->id,
                ],
                "tunggu_permohonan_{$permohonan->id}_keuangan_{$siklus}"
            );
        }
    }

    /**
     * Kirim notifikasi peringatan anggaran jika pos mencapai Limit atau Over Budget.
     */
    protected function notifyBudgetThresholdIfNeeded(Permohonan $locked): void
    {
        $taId = $locked->kodeAnggaran?->tahun_anggaran_id;
        if (! $taId) {
            return;
        }

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

    /**
     * Persetujuan berjenjang permohonan dana.
     */
    public function approve(Permohonan $permohonan, User $approver, array $data = []): Permohonan
    {
        return DB::transaction(function () use ($permohonan, $approver, $data) {
            $locked = Permohonan::with(['items', 'user.leveling', 'user.unit', 'unit.parent', 'levelingRequired'])
                ->lockForUpdate()
                ->findOrFail($permohonan->id);

            if ($this->isSelfOrPengusul($approver, $locked)) {
                throw new RuntimeException('Pengaju atau pengusul tidak dapat menyetujui permohonannya sendiri.');
            }

            if (! in_array($locked->status, ['diajukan', 'diketahui_kadiv'], true)) {
                throw new RuntimeException("Permohonan dengan status {$locked->status} tidak dalam antrean persetujuan.");
            }

            $chain = $this->resolveApprovalChainForPermohonan($locked);
            $statusAwal = $locked->status;

            if (! $this->canUserApprove($approver, $locked)) {
                $expectedRole = $statusAwal === 'diajukan'
                    ? ($chain['step1']['label'] ?? 'approver tahap ini')
                    : ($chain['step2']['label'] ?? 'approver tahap akhir');

                throw new RuntimeException("Anda tidak berwenang menyetujui permohonan ini. Tahap ini hanya dapat disetujui oleh {$expectedRole}.");
            }

            $currentCycle = $this->getCurrentCycle($locked);
            $tahap = '';
            $nextStatus = '';

            if ($statusAwal === 'diajukan') {
                $tahap = 'kadiv';
                $nextStatus = 'diketahui_kadiv';
            } else {
                // Tahap akhir ('diketahui_kadiv' -> 'disetujui_keuangan')
                $tahap = 'keuangan';
                $itemApprovals = $data['items'] ?? [];
                $totalDisetujui = 0;

                foreach ($locked->items as $item) {
                    $nominalSetuju = array_key_exists($item->id, $itemApprovals)
                        ? (int) $itemApprovals[$item->id]
                        : (int) $item->jumlah_diminta;

                    if ($nominalSetuju < 0) {
                        throw new RuntimeException("Nominal disetujui untuk \"{$item->uraian}\" tidak boleh negatif.");
                    }

                    if ($nominalSetuju > (int) $item->jumlah_diminta) {
                        throw new RuntimeException(
                            "Nominal disetujui untuk \"{$item->uraian}\" tidak boleh melebihi jumlah diminta (Rp ".
                            number_format((int) $item->jumlah_diminta, 0, ',', '.').').'
                        );
                    }

                    $item->jumlah_disetujui = $nominalSetuju;
                    $item->save();
                    $totalDisetujui += $nominalSetuju;
                }

                if ($totalDisetujui <= 0) {
                    throw new RuntimeException('Total nominal yang disetujui harus lebih dari Rp 0.');
                }

                $locked->total_disetujui = $totalDisetujui;
                $nextStatus = 'disetujui_keuangan'; // Dana resmi TERIKAT
            }

            $signerName = $data['signer_name'] ?? $approver->name;
            $signerJabatan = $data['signer_jabatan'] ?? ($approver->jabatan ?? ucfirst($approver->role));
            $catatan = $data['catatan'] ?? null;
            $signedAt = ! empty($data['signed_at']) ? Carbon::parse($data['signed_at']) : now();

            Approval::create([
                'approvable_type' => Permohonan::class,
                'approvable_id' => $locked->id,
                'siklus' => $currentCycle,
                'tahap' => $tahap,
                'keputusan' => 'setuju',
                'approver_id' => $approver->id,
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
                $stageLabel = $statusAwal === 'diajukan'
                    ? ($chain['step1']['label'] ?? 'Pemeriksaan')
                    : ($chain['step2']['label'] ?? 'Persetujuan Akhir');

                NotificationService::kirim(
                    $locked->user,
                    'dokumen_diputuskan',
                    [
                        'judul' => 'Permohonan Dana Disetujui',
                        'pesan' => "Permohonan dana {$locked->no_pengajuan} telah disetujui oleh {$stageLabel}.",
                        'url' => route('permohonan.detail', $locked->id),
                        'jenis' => 'permohonan',
                        'entity_id' => $locked->id,
                    ],
                    "putusan_permohonan_{$locked->id}_{$nextStatus}_{$currentCycle}"
                );
            }

            if ($nextStatus === 'diketahui_kadiv') {
                if (($chain['step2']['role_key'] ?? null) === 'kadiv_divisi') {
                    $this->notifyKadivDivisi($locked, $currentCycle);
                } else {
                    $this->notifyKeuangan($locked, $currentCycle);
                }
            }

            if ($nextStatus === 'disetujui_keuangan') {
                $this->notifyBudgetThresholdIfNeeded($locked);
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
            $locked = Permohonan::with(['user.leveling', 'user.unit', 'unit.parent', 'levelingRequired'])
                ->lockForUpdate()
                ->findOrFail($permohonan->id);

            if (! in_array($locked->status, ['diajukan', 'diketahui_kadiv'], true)) {
                throw new RuntimeException('Hanya dokumen dalam proses persetujuan yang dapat diminta perbaikan.');
            }

            if ($this->isSelfOrPengusul($user, $locked)) {
                throw new RuntimeException('Pengaju atau pengusul tidak dapat meminta perbaikan permohonannya sendiri.');
            }

            if (! $this->canUserApprove($user, $locked)) {
                throw new RuntimeException('Anda tidak berwenang meminta perbaikan permohonan ini pada tahap saat ini.');
            }

            $currentCycle = $this->getCurrentCycle($locked);

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
            $locked = Permohonan::with(['user.leveling', 'user.unit', 'unit.parent', 'levelingRequired'])
                ->lockForUpdate()
                ->findOrFail($permohonan->id);

            if (! in_array($locked->status, ['diajukan', 'diketahui_kadiv'], true)) {
                throw new RuntimeException('Dokumen tidak dalam status yang dapat ditolak.');
            }

            if ($this->isSelfOrPengusul($user, $locked)) {
                throw new RuntimeException('Pengaju atau pengusul tidak dapat menolak permohonannya sendiri.');
            }

            if (! $this->canUserApprove($user, $locked)) {
                throw new RuntimeException('Anda tidak berwenang menolak permohonan ini pada tahap saat ini.');
            }

            $currentCycle = $this->getCurrentCycle($locked);

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

            if (in_array($locked->status, ['dicairkan', 'selesai', 'dibatalkan', 'ditolak'], true)) {
                throw new RuntimeException('Dokumen tidak dapat di-void pada status saat ini.');
            }

            $canCancel = $user->isAdmin()
                || $locked->user_id === $user->id
                || $locked->pengusul_id === $user->id;
            if (! $canCancel) {
                throw new RuntimeException('Anda tidak berwenang me-void permohonan ini.');
            }

            $currentCycle = $this->getCurrentCycle($locked);

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

    /**
     * Tentukan Penanggung Jawab (PJ) default untuk PPD & Laporan (UMP):
     * - Jika permohonan milik Kepala Divisi (urutan >= 3) atau berada pada unit tingkat Divisi tanpa Manajer: otomatis ke Kepala Divisi terkait.
     * - Jika permohonan milik Manajer atau Supervisor/Staf: otomatis ke Manajer Unit terkait.
     */
    public function resolveDefaultPj(Permohonan $permohonan): ?User
    {
        $permohonan->loadMissing(['user.leveling', 'user.unit.parent', 'unit.parent', 'levelingRequired']);

        $submitter = $permohonan->user;
        $unit = $permohonan->unit;

        $isKadivOwned = $submitter !== null
            ? $submitter->isKadiv()
            : ((int) ($permohonan->levelingRequired?->urutan ?? 0) >= 3);

        if ($isKadivOwned) {
            if ($submitter && $submitter->is_active && $submitter->isKadiv()) {
                return $submitter;
            }

            $kadiv = $this->getKadivDivisiForUnit($unit);
            if ($kadiv) {
                return $kadiv;
            }
        }

        if ($submitter && $submitter->is_active && $submitter->isManager()) {
            if ($unit === null || $submitter->unit_id === $unit->id || $unit->getUnitManager() === null) {
                return $submitter;
            }
        }

        if ($unit && $unit->tipe === 'divisi' && $unit->getUnitManager() === null) {
            $divisiKadiv = $this->getKadivDivisiForUnit($unit);
            if ($divisiKadiv) {
                return $divisiKadiv;
            }
        }

        $unitManager = $unit?->getManager();
        if ($unitManager) {
            return $unitManager;
        }

        $divisiId = $unit?->getDivisiId();
        if ($divisiId) {
            $divisiLeader = User::with(['unit', 'leveling'])
                ->where('is_active', true)
                ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
                ->where(fn ($q) => $q->where('unit_id', $divisiId)
                    ->orWhereHas('unit', fn ($u) => $u->where('parent_id', $divisiId)))
                ->orderBy('id')
                ->first();

            if ($divisiLeader) {
                return $divisiLeader;
            }
        }

        return User::with(['unit', 'leveling'])
            ->where('is_active', true)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
            ->orderBy('id')
            ->first();
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

            $pencairan = Pencairan::create([
                'permohonan_id' => $locked->id,
                'no_ppd' => $noPpd,
                'tgl_cair' => $data['tgl_cair'] ?? now()->toDateString(),
                'jumlah' => $locked->total_disetujui,
                'status' => 'dicairkan',
                'dicairkan_oleh' => $user->id,
            ]);

            $before = $locked->toArray();
            $locked->status = 'dicairkan';
            $locked->save();

            $customPj = ! empty($data['pj_id']) ? User::with('leveling')->find($data['pj_id']) : null;
            if ($customPj && ($customPj->leveling?->urutan ?? 0) >= 2) {
                $pjId = $customPj->id;
            } else {
                $defaultPj = $this->resolveDefaultPj($locked);
                $pjId = $defaultPj?->id ?? $locked->user_id;
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
                            'judul' => 'Anda Ditunjuk sebagai Penerima Dana Laporan',
                            'pesan' => "Anda ditunjuk untuk melaporkan penggunaan dana pada Laporan {$pencairan->no_ppd}.",
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

            if (! empty($data['bukti_transfer']) && $data['bukti_transfer'] instanceof UploadedFile) {
                $this->simpanBuktiTransfer($pencairan, $user, $data['bukti_transfer']);
            }

            return $pencairan;
        });
    }

    /**
     * Unggah atau perbarui lampiran bukti transfer PPD ke disk privat dengan versioning.
     */
    public function simpanBuktiTransfer(Pencairan $pencairan, User $user, UploadedFile $file): Lampiran
    {
        $isAuthorized = $user->is_active
            && ($user->role === 'keuangan' || $user->role === 'admin' || (bool) $user->is_petugas_input);

        if (! $isAuthorized) {
            throw new RuntimeException('Anda tidak berwenang mengunggah atau memperbarui bukti transfer pencairan.');
        }

        $path = null;

        try {
            return DB::transaction(function () use ($pencairan, $user, $file, &$path): Lampiran {
                $lockedPencairan = Pencairan::lockForUpdate()->findOrFail($pencairan->id);

                if ($lockedPencairan->status === 'dibatalkan') {
                    throw new RuntimeException('Bukti transfer tidak dapat diunggah untuk PPD yang berstatus Void.');
                }

                $previousBukti = $lockedPencairan->lampiran()
                    ->where('jenis', 'bukti_transfer')
                    ->orderByDesc('version')
                    ->orderByDesc('id')
                    ->first();

                $version = ((int) ($lockedPencairan->lampiran()->where('jenis', 'bukti_transfer')->max('version') ?? 0)) + 1;

                $storedPath = $file->store('lampiran/pencairan/bukti_transfer', 'local');
                if ($storedPath === false) {
                    throw new RuntimeException('Berkas bukti transfer gagal disimpan.');
                }
                $path = $storedPath;

                $lampiran = $lockedPencairan->lampiran()->create([
                    'jenis' => 'bukti_transfer',
                    'path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?? 'application/octet-stream',
                    'size' => (int) ($file->getSize() ?? 0),
                    'sha256' => hash_file('sha256', $file->getRealPath()) ?: null,
                    'version' => $version,
                    'uploaded_by' => $user->id,
                ]);

                (new AuditLogger)->log(
                    'pencairan.bukti_transfer_uploaded',
                    $lockedPencairan,
                    $previousBukti?->toArray(),
                    $lampiran->toArray()
                );

                return $lampiran;
            });
        } catch (Throwable $e) {
            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }
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
