<?php

namespace App\Livewire;

use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Services\BudgetCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard Eksekutif: SIGMA LAZ Al-Azhar')]
class Dashboard extends Component
{
    #[Url]
    public ?int $tahunAnggaranId = null;

    #[Url]
    public int $bulanSd = 12;

    #[Url]
    public ?int $kodeAnggaranId = null;

    #[Url]
    public ?int $sumberDanaId = null;

    #[Url]
    public ?int $unitId = null;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortBy = 'kode';

    #[Url]
    public string $sortDir = 'asc';

    public function mount(): void
    {
        if ($this->tahunAnggaranId === null) {
            $activeYear = TahunAnggaran::where('status', 'aktif')->first()
                ?? TahunAnggaran::orderBy('tahun', 'desc')->first();
            $this->tahunAnggaranId = $activeYear?->id;
        }

        if (empty($this->bulanSd)) {
            $this->bulanSd = (int) now()->format('n');
        }
    }

    public function toggleSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function setStatusFilter(string $status): void
    {
        if ($this->statusFilter === $status) {
            $this->statusFilter = '';
        } else {
            $this->statusFilter = $status;
        }
    }

    public function resetFilters(): void
    {
        $this->kodeAnggaranId = null;
        $this->sumberDanaId = null;
        $this->unitId = null;
        $this->statusFilter = '';
        $this->search = '';
        $this->sortBy = 'kode';
        $this->sortDir = 'asc';
        $this->bulanSd = (int) now()->format('n');
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        $daftarTahun = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $daftarUnit = Unit::orderBy('nama', 'asc')->get();
        $daftarSumberDana = SumberDana::where('is_active', true)->orderBy('nama', 'asc')->get();
        $daftarKodeAnggaran = $this->tahunAnggaranId
            ? KodeAnggaran::where('tahun_anggaran_id', $this->tahunAnggaranId)->where('is_active', true)->orderBy('kode', 'asc')->get()
            : collect();

        $calculator = new BudgetCalculator;
        $treeData = $this->tahunAnggaranId
            ? $calculator->calculateTree($this->tahunAnggaranId, $this->unitId, $this->sumberDanaId)
            : collect();

        // 1. KPI Cards (5 metrics)
        $rootNodes = $treeData->filter(fn ($item) => ($item['depth'] ?? 1) === 1 || is_null($item['parent_id']));

        if ($this->unitId) {
            // PRD FR-DSH: Filter divisi hanya menampilkan Terikat dan Realisasi
            $kpiBudget = null;
            $kpiSisa = null;
            $kpiTerikat = (int) $rootNodes->sum('dana_terikat');
            $kpiRealisasi = (int) $rootNodes->sum('realisasi');
            $kpiSerapan = null;
        } else {
            $kpiBudget = (int) $rootNodes->filter(fn ($n) => ($n['node']->budget_status ?? '') === 'disahkan')->sum('effective_budget');
            $kpiTerikat = (int) $rootNodes->sum('dana_terikat');
            $kpiRealisasi = (int) $rootNodes->sum('realisasi');
            $kpiSisa = max(0, $kpiBudget - $kpiTerikat - $kpiRealisasi);
            $kpiSerapan = $kpiBudget > 0 ? round(($kpiRealisasi / $kpiBudget) * 100, 1) : 0;
        }

        // 2. Monthly Chart Data
        $allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartLabels = array_slice($allMonths, 0, $this->bulanSd);

        $terikatMonthly = $this->tahunAnggaranId ? $calculator->getMonthlyTerikatData($this->tahunAnggaranId, $this->unitId) : [];
        $realisasiMonthly = $this->tahunAnggaranId ? $calculator->getMonthlyRealisasiData($this->tahunAnggaranId, $this->unitId) : [];

        $chartTerikat = [];
        $chartRealisasi = [];
        $chartBudget = [];

        for ($m = 1; $m <= $this->bulanSd; $m++) {
            $chartTerikat[] = $terikatMonthly[$m] ?? 0;
            $chartRealisasi[] = $realisasiMonthly[$m] ?? 0;
            $chartBudget[] = $kpiBudget ?? 0;
        }

        // 3. Budget Health Summary
        $leafNodes = $treeData->filter(fn ($n) => $n['is_leaf'] ?? false);
        $healthSummary = [
            'Aman' => $leafNodes->where('status', 'Aman')->count(),
            'Limit' => $leafNodes->where('status', 'Limit')->count(),
            'Over Budget' => $leafNodes->where('status', 'Over Budget')->count(),
            'Tanpa Budget' => $leafNodes->where('status', 'Tanpa Budget')->count(),
        ];

        // 4. Panel "Perlu Perhatian" (top 5 leaf nodes with worst status)
        $perluPerhatian = $leafNodes->filter(function ($n) {
            return in_array($n['status'], ['Over Budget', 'Limit'], true);
        })->map(function ($n) {
            $eff = $n['effective_budget'] ?? 0;
            $pemakaian = ($n['dana_terikat'] ?? 0) + ($n['realisasi'] ?? 0);
            $n['pemakaian_persen'] = $eff > 0 ? round(($pemakaian / $eff) * 100, 1) : ($pemakaian > 0 ? 100 : 0);
            $n['severity'] = match ($n['status']) {
                'Over Budget' => 2,
                'Limit' => 1,
                default => 0,
            };

            return $n;
        })->sortByDesc('severity')
            ->sortByDesc('pemakaian_persen')
            ->take(5)
            ->values();

        // 5. Panel "Antrean Persetujuan Saya"
        $antreanPersetujuan = $this->getAntreanPersetujuan();

        // 6. Monitoring Table
        $monitoringRows = $this->getFilteredMonitoringRows($treeData);

        $timestamp = Carbon::now()->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';

        return view('livewire.dashboard', [
            'daftarTahun' => $daftarTahun,
            'daftarUnit' => $daftarUnit,
            'daftarSumberDana' => $daftarSumberDana,
            'daftarKodeAnggaran' => $daftarKodeAnggaran,
            'kpiBudget' => $kpiBudget,
            'kpiTerikat' => $kpiTerikat,
            'kpiRealisasi' => $kpiRealisasi,
            'kpiSisa' => $kpiSisa,
            'kpiSerapan' => $kpiSerapan,
            'chartLabels' => $chartLabels,
            'chartBudget' => $chartBudget,
            'chartTerikat' => $chartTerikat,
            'chartRealisasi' => $chartRealisasi,
            'healthSummary' => $healthSummary,
            'perluPerhatian' => $perluPerhatian,
            'antreanPersetujuan' => $antreanPersetujuan,
            'monitoringRows' => $monitoringRows,
            'timestamp' => $timestamp,
        ]);
    }

    private function getAntreanPersetujuan(): Collection
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        $antrean = collect();
        $levelingUrutan = $user->leveling?->urutan ?? 0;
        $isFinance = $user->role === 'keuangan' || $user->is_petugas_input || $user->role === 'admin';
        $isAdmin = $user->role === 'admin';

        // Permohonan
        $pmhDiajukan = Permohonan::with(['user', 'unit'])
            ->where('status', 'diajukan')
            ->where(function ($q) use ($user, $levelingUrutan, $isAdmin) {
                if ($isAdmin) {
                    return;
                }
                $q->where('atasan_id', $user->id)
                    ->orWhere(function ($sub) use ($user, $levelingUrutan) {
                        if ($levelingUrutan >= 2) {
                            $sub->where('unit_id', $user->unit_id);
                        }
                    });
            })->get();

        $pmhKadiv = Permohonan::with(['user', 'unit'])
            ->where('status', 'disetujui_atasan')
            ->where(function ($q) use ($user, $levelingUrutan, $isAdmin) {
                if ($isAdmin) {
                    return;
                }
                if ($levelingUrutan >= 3) {
                    $divisiId = $user->unit?->parent_id ?? $user->unit_id;
                    $q->whereHas('unit', fn ($u) => $u->where('parent_id', $divisiId)->orWhere('id', $divisiId));
                } else {
                    $q->whereRaw('0 = 1');
                }
            })->get();

        $pmhFinance = collect();
        if ($isFinance) {
            $pmhFinance = Permohonan::with(['user', 'unit'])
                ->where('status', 'diketahui_kadiv')
                ->get();
        }

        $allPmh = $pmhDiajukan->concat($pmhKadiv)->concat($pmhFinance)->unique('id');
        foreach ($allPmh as $p) {
            $antrean->push([
                'jenis' => 'permohonan',
                'no_dokumen' => $p->no_pengajuan,
                'judul' => $p->nama_kegiatan,
                'pengaju' => $p->user?->name ?? 'Staff',
                'unit' => $p->unit?->nama ?? '',
                'nominal' => $p->total_diminta,
                'status' => $p->status,
                'status_label' => ucfirst(str_replace('_', ' ', $p->status)),
                'url' => route('permohonan.detail', $p->id),
                'updated_at' => $p->updated_at,
            ]);
        }

        // UMP
        $umpList = Ump::with(['pj', 'pencairan.permohonan.unit'])
            ->whereIn('status', ['ttd_pj', 'ttd_manager_keuangan', 'ttd_kepala_keuangan'])
            ->get()
            ->filter(function ($u) use ($user, $levelingUrutan, $isFinance, $isAdmin) {
                if ($isAdmin) {
                    return true;
                }
                if ($u->status === 'ttd_pj') {
                    $docDivisiId = $u->pencairan?->permohonan?->unit?->getDivisiId();
                    $userDivisiId = $user->unit?->getDivisiId();
                    $sameDivisi = $docDivisiId !== null && $userDivisiId !== null && $docDivisiId === $userDivisiId;

                    return $sameDivisi && $levelingUrutan >= 2;
                }
                if (in_array($u->status, ['ttd_manager_keuangan', 'ttd_kepala_keuangan'], true)) {
                    return $isFinance;
                }

                return false;
            });

        foreach ($umpList as $u) {
            $antrean->push([
                'jenis' => 'ump',
                'no_dokumen' => $u->pencairan?->no_ppd ?? 'UMP',
                'judul' => $u->tujuan ?? 'Laporan UMP',
                'pengaju' => $u->pj?->name ?? 'PJ',
                'unit' => $u->pencairan?->permohonan?->unit?->nama ?? '',
                'nominal' => $u->jumlah_total,
                'status' => $u->status,
                'status_label' => ucfirst(str_replace('_', ' ', $u->status)),
                'url' => route('ump.detail', $u->id),
                'updated_at' => $u->updated_at,
            ]);
        }

        return $antrean->sortByDesc('updated_at')->take(10)->values();
    }

    private function getFilteredMonitoringRows(Collection $treeData): Collection
    {
        // 1. Filter subtree if kodeAnggaranId is selected
        $rows = $treeData;
        if ($this->kodeAnggaranId) {
            $target = $treeData->get($this->kodeAnggaranId);
            if ($target) {
                $targetKode = $target['kode'];
                $rows = $rows->filter(function ($item) use ($targetKode) {
                    return $item['kode'] === $targetKode || str_starts_with($item['kode'], $targetKode.'.');
                });
            }
        }

        // 2. Filter search
        if (trim($this->search) !== '') {
            $term = strtolower(trim($this->search));
            $rows = $rows->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['kode']), $term)
                    || str_contains(strtolower($item['nama']), $term);
            });
        }

        // 3. Filter status
        if ($this->statusFilter !== '') {
            $rows = $rows->filter(function ($item) {
                return ($item['status'] ?? '') === $this->statusFilter;
            });
        }

        // 4. Compute pemakaian_persen and format
        $mapped = $rows->map(function ($item) {
            $eff = $item['effective_budget'] ?? 0;
            $pemakaian = ($item['dana_terikat'] ?? 0) + ($item['realisasi'] ?? 0);
            $item['pemakaian_persen'] = $eff > 0 ? round(($pemakaian / $eff) * 100, 1) : 0;

            return $item;
        });

        // 5. Sort
        $isDesc = strtolower($this->sortDir) === 'desc';

        return $mapped->sortBy(function ($item) {
            return match ($this->sortBy) {
                'nama' => strtolower($item['nama']),
                'effective_budget' => $item['effective_budget'],
                'dana_terikat' => $item['dana_terikat'],
                'realisasi' => $item['realisasi'],
                'sisa_tersedia' => $item['sisa_tersedia'],
                'pemakaian_persen' => $item['pemakaian_persen'],
                'status' => $item['status'],
                default => $item['kode'],
            };
        }, SORT_REGULAR, $isDesc)->values();
    }
}
