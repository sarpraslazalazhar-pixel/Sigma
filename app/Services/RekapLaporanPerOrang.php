<?php

namespace App\Services;

use App\Models\Ump;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RekapLaporanPerOrang
{
    /**
     * Bangun data rekapitulasi Laporan (UMP) per orang beserta daftar dokumen Laporan.
     * Setiap dokumen Laporan menghitung PJ serta seluruh penerima dana (`ump_penerima`) dan baris pengeluarannya (`ump_items`) tanpa penghitungan ganda.
     *
     * @return array{
     *     rekap_per_user: Collection<int, array<string, mixed>>,
     *     all_umps: Collection<int, Ump>,
     *     ump_participants: array<int, array<int, array{user: User, user_id: int, name: string, is_pj: bool, is_penerima: bool, peran: string, item_count: int, nominal_penggunaan: int}>>,
     *     total_laporan: int,
     *     total_dana_diterima: int,
     *     total_penggunaan: int,
     *     total_orang: int,
     *     rata_rata: int,
     * }
     */
    public function build(
        ?int $tahunAnggaranId = null,
        string $tahunFilter = '',
        string $unitFilter = '',
        string $search = '',
        string $bulanFilter = '',
        string $sumberDanaFilter = '',
        string $statusFilter = '',
        string $sortBy = 'nominal_desc',
    ): array {
        $searchTrimmed = trim($search);
        $termLower = mb_strtolower($searchTrimmed);

        $query = Ump::with([
            'pj.leveling',
            'pj.unit.parent',
            'penerima.user.leveling',
            'penerima.user.unit.parent',
            'items.penerimaPivot.user.leveling',
            'items.penerimaPivot.user.unit.parent',
            'pencairan.permohonan.unit.parent',
            'pencairan.permohonan.kodeAnggaran',
            'pencairan.permohonan.sumberDana',
        ])
            ->where('status', '!=', 'dibatalkan')
            ->whereHas('pencairan', fn (Builder $payout) => $payout->where('status', 'dicairkan'))
            ->when($tahunFilter !== 'all' && $tahunAnggaranId, function (Builder $q) use ($tahunAnggaranId): void {
                $q->whereHas('pencairan.permohonan.kodeAnggaran', fn (Builder $k) => $k->where('tahun_anggaran_id', $tahunAnggaranId));
            })
            ->when($bulanFilter !== '', function (Builder $q) use ($bulanFilter): void {
                $month = (int) $bulanFilter;
                $q->where(function (Builder $sub) use ($month): void {
                    $sub->whereMonth('tgl_terima', $month)
                        ->orWhere(fn (Builder $fallback) => $fallback->whereNull('tgl_terima')->whereMonth('created_at', $month));
                });
            })
            ->when($unitFilter !== '', function (Builder $q) use ($unitFilter): void {
                $q->whereHas('pencairan.permohonan', fn (Builder $p) => $p->where('unit_id', (int) $unitFilter));
            })
            ->when($sumberDanaFilter !== '', function (Builder $q) use ($sumberDanaFilter): void {
                $q->whereHas('pencairan.permohonan', fn (Builder $p) => $p->where('sumber_dana_id', (int) $sumberDanaFilter));
            })
            ->when($statusFilter !== '', function (Builder $q) use ($statusFilter): void {
                match ($statusFilter) {
                    'selesai' => $q->where('status', 'selesai'),
                    'proses' => $q->whereIn('status', ['ttd_pj', 'ttd_manager_keuangan', 'ttd_kepala_keuangan']),
                    'draft' => $q->whereIn('status', ['draft', 'perlu_perbaikan']),
                    default => $q->where('status', $statusFilter),
                };
            })
            ->when($searchTrimmed !== '', function (Builder $q) use ($searchTrimmed): void {
                $like = '%'.$searchTrimmed.'%';
                $q->where(function (Builder $sub) use ($like): void {
                    $sub->where('tujuan', 'like', $like)
                        ->orWhereHas('pencairan', function (Builder $p) use ($like): void {
                            $p->where('no_ppd', 'like', $like)
                                ->orWhereHas('permohonan', function (Builder $pmh) use ($like): void {
                                    $pmh->where('no_pengajuan', 'like', $like)
                                        ->orWhere('nama_kegiatan', 'like', $like)
                                        ->orWhereHas('kodeAnggaran', fn (Builder $ka) => $ka->where('kode', 'like', $like)->orWhere('nama', 'like', $like));
                                });
                        })
                        ->orWhereHas('pj', fn (Builder $u) => $u->where('name', 'like', $like)->orWhere('jabatan', 'like', $like))
                        ->orWhereHas('penerimaUsers', fn (Builder $u) => $u->where('name', 'like', $like)->orWhere('jabatan', 'like', $like));
                });
            });

        $allUmps = $query->orderByDesc('tgl_terima')->orderByDesc('id')->get();

        $totalLaporan = $allUmps->count();
        $totalDanaDiterima = (int) $allUmps->sum('jumlah_total');
        $totalPenggunaan = 0;

        /** @var array<int, array<int, array{user: User, user_id: int, name: string, is_pj: bool, is_penerima: bool, peran: string, item_count: int, nominal_penggunaan: int}>> $umpParticipants */
        $umpParticipants = [];

        /** @var array<int, array<string, mixed>> $userAccumulator */
        $userAccumulator = [];

        foreach ($allUmps as $ump) {
            $participantsInUmp = $this->extractUmpParticipants($ump);
            $umpDocumentPenggunaan = $ump->items->isNotEmpty()
                ? (int) $ump->items->sum('jumlah')
                : (int) $ump->total_penggunaan;
            $totalPenggunaan += $umpDocumentPenggunaan;

            $umpParticipants[$ump->id] = array_values($participantsInUmp);

            $matchesDoc = $termLower !== '' && $this->matchesDocumentFields($ump, $termLower);

            foreach ($participantsInUmp as $userId => $entry) {
                $user = $entry['user'];
                $matchesPerson = $termLower !== '' && (
                    str_contains(mb_strtolower($user->name ?? ''), $termLower)
                    || str_contains(mb_strtolower($user->jabatan ?? ''), $termLower)
                );

                if ($termLower !== '' && ! $matchesDoc && ! $matchesPerson) {
                    continue;
                }

                if (! isset($userAccumulator[$userId])) {
                    $userAccumulator[$userId] = [
                        'user' => $user,
                        'user_id' => $userId,
                        'pj_count' => 0,
                        'penerima_count' => 0,
                        'item_count' => 0,
                        'total_penggunaan' => 0,
                        'total_dana_laporan' => 0,
                        'pos_anggaran' => collect(),
                        'laporan_items' => collect(),
                    ];
                }

                if ($entry['is_pj']) {
                    $userAccumulator[$userId]['pj_count']++;
                }
                if ($entry['is_penerima']) {
                    $userAccumulator[$userId]['penerima_count']++;
                }

                $userAccumulator[$userId]['item_count'] += $entry['item_count'];
                $userAccumulator[$userId]['total_penggunaan'] += $entry['nominal_penggunaan'];
                $userAccumulator[$userId]['total_dana_laporan'] += (int) $ump->jumlah_total;

                $kodeAnggaran = $ump->pencairan?->permohonan?->kodeAnggaran;
                if ($kodeAnggaran) {
                    $userAccumulator[$userId]['pos_anggaran']->push($kodeAnggaran);
                }

                $otherParticipants = collect($participantsInUmp)
                    ->reject(fn (array $other, int $otherId) => $otherId === $userId)
                    ->map(fn (array $other) => [
                        'user_id' => $other['user_id'],
                        'name' => $other['name'],
                        'peran' => $other['peran'],
                        'nominal_penggunaan' => $other['nominal_penggunaan'],
                    ])
                    ->values()
                    ->all();

                $userAccumulator[$userId]['laporan_items']->push([
                    'ump' => $ump,
                    'peran' => $entry['peran'],
                    'is_pj' => $entry['is_pj'],
                    'is_penerima' => $entry['is_penerima'],
                    'item_count' => $entry['item_count'],
                    'nominal_penggunaan' => $entry['nominal_penggunaan'],
                    'total_penggunaan_laporan' => $umpDocumentPenggunaan,
                    'peserta_lain' => $otherParticipants,
                    'total_peserta' => count($participantsInUmp),
                ]);
            }
        }

        $rekapPerUser = collect($userAccumulator)->map(function (array $acc) use ($totalPenggunaan): array {
            /** @var User $user */
            $user = $acc['user'];
            $laporanCount = $acc['laporan_items']->count();
            $userTotalPenggunaan = (int) $acc['total_penggunaan'];
            $persentase = $totalPenggunaan > 0 ? round(($userTotalPenggunaan / $totalPenggunaan) * 100, 1) : 0;

            $firstLaporan = $acc['laporan_items']->first();
            $unitName = $user->unit?->nama_lengkap
                ?? $firstLaporan['ump']->pencairan?->permohonan?->unit?->nama_lengkap
                ?? '-';

            $roleParts = [];
            if ($acc['pj_count'] > 0) {
                $roleParts[] = "PJ ({$acc['pj_count']})";
            }
            if ($acc['penerima_count'] > 0) {
                $roleParts[] = "Penerima ({$acc['penerima_count']})";
            }

            return [
                'user' => $user,
                'user_id' => $acc['user_id'],
                'name' => $user->name ?? 'Tidak Diketahui',
                'initials' => $this->makeInitials($user->name ?? 'User'),
                'jabatan' => $user->jabatan ?: ($user->leveling?->nama ?? 'Pelaksana'),
                'leveling_nama' => $user->leveling?->nama ?? 'Staf',
                'leveling_urutan' => $user->leveling?->urutan ?? 0,
                'unit' => $unitName,
                'laporan_count' => $laporanCount,
                'pj_count' => $acc['pj_count'],
                'penerima_count' => $acc['penerima_count'],
                'peran_ringkas' => implode(', ', $roleParts),
                'item_count' => $acc['item_count'],
                'total_penggunaan' => $userTotalPenggunaan,
                'total_dana_laporan' => (int) $acc['total_dana_laporan'],
                'persentase' => $persentase,
                'pos_anggaran' => $acc['pos_anggaran']->unique('id')->values(),
                'laporan_items' => $acc['laporan_items'],
            ];
        });

        $rekapPerUser = $this->sortRekap($rekapPerUser, $sortBy);
        $rataRata = $totalLaporan > 0 ? (int) round($totalPenggunaan / $totalLaporan) : 0;

        return [
            'rekap_per_user' => $rekapPerUser,
            'all_umps' => $allUmps,
            'ump_participants' => $umpParticipants,
            'total_laporan' => $totalLaporan,
            'total_dana_diterima' => $totalDanaDiterima,
            'total_penggunaan' => $totalPenggunaan,
            'total_orang' => $rekapPerUser->count(),
            'rata_rata' => $rataRata,
        ];
    }

    /**
     * Label dan kelas badge status untuk dokumen Laporan (UMP).
     *
     * @return array{label: string, class: string}
     */
    public static function statusBadge(Ump $ump): array
    {
        if ($ump->status === 'ttd_manager_keuangan') {
            if ($ump->verifikasi_keuangan_at === null) {
                return [
                    'label' => 'Menunggu Verifikasi Keuangan',
                    'class' => 'bg-amber-50 text-amber-800 border-amber-200',
                ];
            }

            return [
                'label' => 'Menunggu TTD Mgr Keuangan',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ];
        }

        return match ($ump->status) {
            'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
            'perlu_perbaikan' => ['label' => 'Perlu Perbaikan', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'],
            'ttd_pj' => ['label' => 'Menunggu Diperiksa', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
            'ttd_kepala_keuangan' => ['label' => 'Menunggu TTD Kepala Keuangan', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'selesai' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'dibatalkan' => ['label' => 'Void', 'class' => 'bg-red-50 text-red-700 border-red-200'],
            default => ['label' => ucfirst(str_replace('_', ' ', $ump->status)), 'class' => 'bg-slate-100 text-slate-600 border-slate-200'],
        };
    }

    /**
     * Kumpulkan seluruh pengguna yang terlibat pada satu dokumen UMP (PJ + semua penerima) beserta nominal masing-masing.
     *
     * @return array<int, array{user: User, user_id: int, name: string, is_pj: bool, is_penerima: bool, peran: string, item_count: int, nominal_penggunaan: int}>
     */
    private function extractUmpParticipants(Ump $ump): array
    {
        $participants = [];

        if ($ump->pj_id && $ump->pj) {
            $participants[$ump->pj_id] = [
                'user' => $ump->pj,
                'user_id' => $ump->pj_id,
                'name' => $ump->pj->name,
                'is_pj' => true,
                'is_penerima' => false,
                'peran' => 'PJ',
                'item_count' => 0,
                'nominal_penggunaan' => 0,
            ];
        }

        foreach ($ump->penerima as $penerimaPivot) {
            if (! $penerimaPivot->user_id || ! $penerimaPivot->user) {
                continue;
            }

            $uid = (int) $penerimaPivot->user_id;
            if (! isset($participants[$uid])) {
                $participants[$uid] = [
                    'user' => $penerimaPivot->user,
                    'user_id' => $uid,
                    'name' => $penerimaPivot->user->name,
                    'is_pj' => false,
                    'is_penerima' => true,
                    'peran' => 'Penerima',
                    'item_count' => 0,
                    'nominal_penggunaan' => 0,
                ];
            } else {
                $participants[$uid]['is_penerima'] = true;
            }
        }

        foreach ($ump->items as $item) {
            $ownerUser = $item->penerimaPivot?->user ?? $ump->pj;
            $ownerId = $ownerUser?->id;

            if (! $ownerId || ! $ownerUser) {
                continue;
            }

            $isItemPenerima = $item->penerima_id !== null;
            if (! isset($participants[$ownerId])) {
                $participants[$ownerId] = [
                    'user' => $ownerUser,
                    'user_id' => $ownerId,
                    'name' => $ownerUser->name,
                    'is_pj' => ! $isItemPenerima && $ownerId === (int) $ump->pj_id,
                    'is_penerima' => $isItemPenerima,
                    'peran' => $isItemPenerima ? 'Penerima' : 'PJ',
                    'item_count' => 0,
                    'nominal_penggunaan' => 0,
                ];
            } elseif ($isItemPenerima) {
                $participants[$ownerId]['is_penerima'] = true;
            }

            $participants[$ownerId]['item_count']++;
            $participants[$ownerId]['nominal_penggunaan'] += (int) $item->jumlah;
        }

        if ($ump->items->isEmpty() && (int) $ump->total_penggunaan > 0 && $ump->pj_id && isset($participants[$ump->pj_id])) {
            $participants[$ump->pj_id]['nominal_penggunaan'] = (int) $ump->total_penggunaan;
        }

        foreach ($participants as $uid => $entry) {
            $participants[$uid]['peran'] = match (true) {
                $entry['is_pj'] && $entry['is_penerima'] => 'PJ & Penerima',
                $entry['is_pj'] => 'PJ',
                default => 'Penerima',
            };
        }

        uasort($participants, function (array $a, array $b): int {
            if ($a['is_pj'] !== $b['is_pj']) {
                return $a['is_pj'] ? -1 : 1;
            }

            if ($a['nominal_penggunaan'] !== $b['nominal_penggunaan']) {
                return $b['nominal_penggunaan'] <=> $a['nominal_penggunaan'];
            }

            return strnatcasecmp($a['name'], $b['name']);
        });

        return $participants;
    }

    private function matchesDocumentFields(Ump $ump, string $termLower): bool
    {
        $fields = [
            $ump->tujuan,
            $ump->pencairan?->no_ppd,
            $ump->pencairan?->permohonan?->no_pengajuan,
            $ump->pencairan?->permohonan?->nama_kegiatan,
            $ump->pencairan?->permohonan?->kodeAnggaran?->kode,
            $ump->pencairan?->permohonan?->kodeAnggaran?->nama,
        ];

        foreach ($fields as $value) {
            if ($value !== null && $value !== '' && str_contains(mb_strtolower((string) $value), $termLower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rekap
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRekap(Collection $rekap, string $sortBy): Collection
    {
        return match ($sortBy) {
            'nominal_asc' => $rekap->sortBy([
                ['total_penggunaan', 'asc'],
                ['laporan_count', 'asc'],
                ['name', 'asc'],
            ])->values(),
            'laporan_desc' => $rekap->sortBy([
                ['laporan_count', 'desc'],
                ['total_penggunaan', 'desc'],
                ['name', 'asc'],
            ])->values(),
            'laporan_asc' => $rekap->sortBy([
                ['laporan_count', 'asc'],
                ['total_penggunaan', 'asc'],
                ['name', 'asc'],
            ])->values(),
            'name_asc' => $rekap->sortBy(fn (array $row) => mb_strtolower($row['name']))->values(),
            default => $rekap->sortBy([
                ['total_penggunaan', 'desc'],
                ['laporan_count', 'desc'],
                ['name', 'asc'],
            ])->values(),
        };
    }

    private function makeInitials(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', trim($name))));
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0] ?? 'U', 0, 2));
    }
}
