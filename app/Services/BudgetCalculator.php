<?php

namespace App\Services;

use App\Models\AlokasiAnggaran;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetCalculator
{
    /**
     * Hitung pohon kode anggaran untuk tahun anggaran tertentu.
     *
     * Menghitung untuk setiap node:
     * - Plafon induk (jika diisi) vs total budget anak.
     * - Belum Dialokasikan = Plafon induk - total budget anak (positif = belum dialokasikan).
     * - Jika induk tanpa plafon, budget induk = jumlah budget anak.
     * - Status awal: 'Aman' atau 'Tanpa Budget'.
     *
     * @return Collection<int, array>
     */
    public function calculateTree(int $tahunAnggaranId, ?int $unitId = null, ?int $sumberDanaId = null): Collection
    {

        $nodes = KodeAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('is_active', true)
            ->orderBy('depth', 'desc')
            ->orderBy('kode', 'asc')
            ->get();

        $alokasiByNode = AlokasiAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->get()
            ->keyBy('kode_anggaran_id');
        $danaTerikatQuery = Permohonan::whereIn('status', ['disetujui_keuangan', 'dicairkan'])
            ->whereHas('kodeAnggaran', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId));
        if ($unitId) {
            $danaTerikatQuery->where('unit_id', $unitId);
        }
        if ($sumberDanaId) {
            $danaTerikatQuery->where('sumber_dana_id', $sumberDanaId);
        }
        $danaTerikatByNode = $danaTerikatQuery
            ->groupBy('kode_anggaran_id')
            ->selectRaw('kode_anggaran_id, sum(total_disetujui) as total_terikat')
            ->pluck('total_terikat', 'kode_anggaran_id');

        // Realisasi: sum ump_items pada UMP berstatus selesai, per pos anggaran (FR-UMP-5, FR-CAL)
        $realisasiQuery = DB::table('ump_items')
            ->join('ump', 'ump.id', '=', 'ump_items.ump_id')
            ->join('pencairan', 'pencairan.id', '=', 'ump.pencairan_id')
            ->join('permohonan', 'permohonan.id', '=', 'pencairan.permohonan_id')
            ->where('ump.status', 'selesai')
            ->whereIn('permohonan.kode_anggaran_id', $nodes->pluck('id'));
        if ($unitId) {
            $realisasiQuery->where('permohonan.unit_id', $unitId);
        }
        if ($sumberDanaId) {
            $realisasiQuery->where('permohonan.sumber_dana_id', $sumberDanaId);
        }
        $realisasiByNode = $realisasiQuery
            ->groupBy('permohonan.kode_anggaran_id')
            ->selectRaw('permohonan.kode_anggaran_id as node_id, sum(ump_items.jumlah) as total_realisasi')
            ->pluck('total_realisasi', 'node_id');

        $limitThreshold = (int) Setting::get('ambang.limit', 80) / 100.0;
        $overThreshold = (int) Setting::get('ambang.over', 100) / 100.0;

        $calculated = collect();
        $childrenByParent = $nodes->groupBy('parent_id');

        // Bottom-up calculation: mulai dari depth terdalam
        foreach ($nodes as $node) {
            $children = $childrenByParent->get($node->id, collect());
            $isLeaf = $children->isEmpty();

            $alokasi = $alokasiByNode->get($node->id);
            $nodePlafon = $alokasi ? $alokasi->plafon_efektif : null;
            $plafonAwal = $alokasi?->plafon_awal;
            $plafonRevisi = $alokasi?->plafon_revisi;

            if ($isLeaf) {
                $totalAnak = 0;
                $effectiveBudget = $nodePlafon ?? 0;
                $belumDialokasikan = 0;
                $danaTerikat = (int) ($danaTerikatByNode->get($node->id) ?? 0);
                $realisasi = (int) ($realisasiByNode->get($node->id) ?? 0);
            } else {
                // Jumlahkan effective budget anak-anak langsung yang sudah dihitung
                $totalAnak = $children->sum(function ($child) use ($calculated) {
                    return $calculated->get($child->id)['effective_budget'] ?? 0;
                });

                $danaTerikat = $children->sum(function ($child) use ($calculated) {
                    return $calculated->get($child->id)['dana_terikat'] ?? 0;
                });

                $realisasi = $children->sum(function ($child) use ($calculated) {
                    return $calculated->get($child->id)['realisasi'] ?? 0;
                });

                if ($nodePlafon !== null) {
                    $effectiveBudget = $nodePlafon;
                    $belumDialokasikan = $nodePlafon - $totalAnak;
                } else {
                    $effectiveBudget = $totalAnak;
                    $belumDialokasikan = 0;
                }
            }

            $sisaTersedia = max(0, $effectiveBudget - $realisasi - $danaTerikat);
            $pengeluaranTotal = $realisasi + $danaTerikat;

            if ($effectiveBudget > 0) {
                if ($pengeluaranTotal > $effectiveBudget * $overThreshold) {
                    $status = 'Over Budget';
                } elseif ($pengeluaranTotal >= $effectiveBudget * $limitThreshold) {
                    $status = 'Limit';
                } else {
                    $status = 'Aman';
                }
            } else {
                $status = ($pengeluaranTotal > 0) ? 'Over Budget' : 'Tanpa Budget';
            }

            $calculated->put($node->id, [
                'id' => $node->id,
                'kode' => $node->kode,
                'nama' => $node->nama,
                'depth' => $node->depth,
                'parent_id' => $node->parent_id,
                'is_leaf' => $isLeaf,
                'plafon' => $nodePlafon,
                'plafon_awal' => $plafonAwal,
                'plafon_revisi' => $plafonRevisi,
                'total_anak' => $totalAnak,
                'effective_budget' => $effectiveBudget,
                'belum_dialokasikan' => $belumDialokasikan,
                'dana_terikat' => $danaTerikat,
                'realisasi' => $realisasi,
                'sisa_tersedia' => $sisaTersedia,
                'status' => $status,
                'node' => $node,
            ]);
        }

        return $calculated;
    }

    private function getWorstStatus(array $statuses): string
    {
        $order = [
            'Over Budget' => 1,
            'Limit' => 2,
            'Aman' => 3,
            'Tanpa Budget' => 4,
        ];

        $worst = 'Tanpa Budget';
        $minRank = 999;
        foreach ($statuses as $st) {
            $rank = $order[$st] ?? 999;
            if ($rank < $minRank) {
                $minRank = $rank;
                $worst = $st;
            }
        }

        return $worst;
    }

    /**
     * Nama-nama bulan standar bahasa Indonesia.
     *
     * @return array<int, string>
     */
    public static function monthNames(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    /**
     * Hitung laporan pengeluaran bulanan matriks (Januari - Desember)
     * sesuai format resmi institusi dengan rollup bottom-up.
     *
     * @return Collection<int, array>
     */
    public function getMonthlyExpenditureReport(int $tahunAnggaranId, ?string $search = null): Collection
    {
        $treeCalculation = $this->calculateTree($tahunAnggaranId);

        $nodes = KodeAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('is_active', true)
            ->orderBy('depth', 'desc')
            ->orderBy('kode', 'asc')
            ->get();

        $childrenByParent = $nodes->groupBy('parent_id');
        $monthlyByNode = collect();

        // Realisasi per bulan (aman untuk SQLite & MySQL): tarik baris sekali, kelompokkan via Carbon
        $monthlyRows = DB::table('ump_items')
            ->join('ump', 'ump.id', '=', 'ump_items.ump_id')
            ->join('pencairan', 'pencairan.id', '=', 'ump.pencairan_id')
            ->join('permohonan', 'permohonan.id', '=', 'pencairan.permohonan_id')
            ->join('kode_anggaran', 'kode_anggaran.id', '=', 'permohonan.kode_anggaran_id')
            ->where('ump.status', 'selesai')
            ->where('kode_anggaran.tahun_anggaran_id', $tahunAnggaranId)
            ->get(['permohonan.kode_anggaran_id as node_id', 'ump_items.tgl', 'ump_items.jumlah']);

        $realisasiMonthly = [];
        foreach ($monthlyRows as $row) {
            $tgl = Carbon::parse($row->tgl);
            $realisasiMonthly[(int) $row->node_id][(int) $tgl->format('n')] =
                ($realisasiMonthly[(int) $row->node_id][(int) $tgl->format('n')] ?? 0) + (int) $row->jumlah;
        }

        // Bottom-up rollup untuk pengeluaran bulanan
        foreach ($nodes as $node) {
            $children = $childrenByParent->get($node->id, collect());
            $isLeaf = $children->isEmpty();

            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                if ($isLeaf) {
                    // Realisasi leaf = sum ump_items UMP selesai pada bulan $m
                    $months[$m] = $realisasiMonthly[$node->id][$m] ?? 0;
                } else {
                    // Rollup dari anak-anak
                    $months[$m] = $children->sum(function ($child) use ($monthlyByNode, $m) {
                        return $monthlyByNode->get($child->id)['months'][$m] ?? 0;
                    });
                }
            }

            $totalPengeluaran = array_sum($months);
            $treeItem = $treeCalculation->get($node->id);
            $plafonRab = $treeItem['effective_budget'] ?? ($treeItem['plafon'] ?? 0);
            $sisaAnggaran = max(0, $plafonRab - $totalPengeluaran);
            $persentase = $plafonRab > 0 ? round(($totalPengeluaran / $plafonRab) * 100, 1) : 0;

            $monthlyByNode->put($node->id, [
                'id' => $node->id,
                'kode' => $node->kode,
                'nama' => $node->nama,
                'depth' => $node->depth,
                'parent_id' => $node->parent_id,
                'is_leaf' => $isLeaf,
                'plafon_rab' => $plafonRab,
                'months' => $months,
                'total_pengeluaran' => $totalPengeluaran,
                'sisa_anggaran' => $sisaAnggaran,
                'persentase_penyerapan' => $persentase,
                'node' => $node,
            ]);
        }

        // Urutkan kembali sesuai urutan kode hierarkis dan alami
        $rawOrdered = KodeAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('is_active', true)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->get();

        $ordered = KodeAnggaran::sortHierarchically($rawOrdered)
            ->map(function ($node) use ($monthlyByNode) {
                return $monthlyByNode->get($node->id);
            })
            ->filter();

        return $ordered->values();
    }

    /**
     * Data dana terikat per bulan (1 - 12) berdasarkan tgl_mulai kegiatan.
     *
     * @return array<int, int>
     */
    public function getMonthlyTerikatData(int $tahunAnggaranId, ?int $unitId = null): array
    {
        $query = Permohonan::whereIn('status', ['disetujui_keuangan', 'dicairkan'])
            ->whereHas('kodeAnggaran', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId));

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        $rows = $query->get(['tgl_mulai', 'total_disetujui']);
        $monthly = array_fill(1, 12, 0);

        foreach ($rows as $row) {
            if ($row->tgl_mulai) {
                $m = (int) Carbon::parse($row->tgl_mulai)->format('n');
                if ($m >= 1 && $m <= 12) {
                    $monthly[$m] += (int) $row->total_disetujui;
                }
            }
        }

        return $monthly;
    }

    /**
     * Data realisasi pengeluaran per bulan (1 - 12) dari UMP selesai.
     *
     * @return array<int, int>
     */
    public function getMonthlyRealisasiData(int $tahunAnggaranId, ?int $unitId = null): array
    {
        $query = DB::table('ump_items')
            ->join('ump', 'ump.id', '=', 'ump_items.ump_id')
            ->join('pencairan', 'pencairan.id', '=', 'ump.pencairan_id')
            ->join('permohonan', 'permohonan.id', '=', 'pencairan.permohonan_id')
            ->join('kode_anggaran', 'kode_anggaran.id', '=', 'permohonan.kode_anggaran_id')
            ->where('ump.status', 'selesai')
            ->where('kode_anggaran.tahun_anggaran_id', $tahunAnggaranId);

        if ($unitId) {
            $query->where('permohonan.unit_id', $unitId);
        }

        $rows = $query->get(['ump_items.tgl', 'ump_items.jumlah']);
        $monthly = array_fill(1, 12, 0);

        foreach ($rows as $row) {
            if ($row->tgl) {
                $m = (int) Carbon::parse($row->tgl)->format('n');
                if ($m >= 1 && $m <= 12) {
                    $monthly[$m] += (int) $row->jumlah;
                }
            }
        }

        return $monthly;
    }
}
