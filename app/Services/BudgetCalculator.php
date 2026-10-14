<?php

namespace App\Services;

use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\Setting;
use Illuminate\Support\Collection;

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
    public function calculateTree(int $tahunAnggaranId): Collection
    {
        $nodes = KodeAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('is_active', true)
            ->orderBy('depth', 'desc')
            ->orderBy('kode', 'asc')
            ->get();

        $danaTerikatByNode = Permohonan::whereIn('status', ['disetujui_keuangan', 'dicairkan'])
            ->groupBy('kode_anggaran_id')
            ->selectRaw('kode_anggaran_id, sum(total_disetujui) as total_terikat')
            ->pluck('total_terikat', 'kode_anggaran_id');

        $limitThreshold = (int) Setting::get('ambang.limit', 80) / 100.0;
        $overThreshold = (int) Setting::get('ambang.over', 100) / 100.0;

        $calculated = collect();
        $childrenByParent = $nodes->groupBy('parent_id');

        // Bottom-up calculation: mulai dari depth terdalam
        foreach ($nodes as $node) {
            $children = $childrenByParent->get($node->id, collect());
            $isLeaf = $children->isEmpty();

            if ($isLeaf) {
                $totalAnak = 0;
                $effectiveBudget = $node->plafon ?? 0;
                $belumDialokasikan = 0;
                $danaTerikat = (int) ($danaTerikatByNode->get($node->id) ?? 0);
                $realisasi = 0;
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

                if ($node->plafon !== null) {
                    $effectiveBudget = $node->plafon;
                    $belumDialokasikan = $node->plafon - $totalAnak;
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
                'plafon' => $node->plafon,
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

        // Bottom-up rollup untuk pengeluaran bulanan
        foreach ($nodes as $node) {
            $children = $childrenByParent->get($node->id, collect());
            $isLeaf = $children->isEmpty();

            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                if ($isLeaf) {
                    // Di masa depan / R3: query transaksi realisasi leaf per bulan $m
                    $months[$m] = 0;
                } else {
                    // Rollup dari anak-anak
                    $months[$m] = $children->sum(function ($child) use ($monthlyByNode, $m) {
                        return $monthlyByNode->get($child->id)['months'][$m] ?? 0;
                    });
                }
            }

            $totalPengeluaran = array_sum($months);
            $treeItem = $treeCalculation->get($node->id);
            $plafonRab = $treeItem['effective_budget'] ?? ($node->plafon ?? 0);
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

        // Urutkan kembali sesuai urutan kode hierarkis (kode asc)
        $ordered = KodeAnggaran::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('is_active', true)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->orderBy('kode', 'asc')
            ->get()
            ->map(function ($node) use ($monthlyByNode) {
                return $monthlyByNode->get($node->id);
            })
            ->filter();

        return $ordered->values();
    }
}
