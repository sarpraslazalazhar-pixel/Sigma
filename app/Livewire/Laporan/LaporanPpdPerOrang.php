<?php

namespace App\Livewire\Laporan;

use App\Exports\LaporanPpdPerOrangExport;
use App\Models\Pencairan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Laporan Rekap PPD per Orang: SIGMA LAZ Al-Azhar')]
class LaporanPpdPerOrang extends Component
{
    public ?int $tahunAnggaranId = null;

    public string $tahunFilter = ''; // '' = ikuti TA terpilih/aktif, 'all' = semua tahun

    public string $bulanFilter = '';

    public string $unitFilter = '';

    public string $sumberDanaFilter = '';

    public string $search = '';

    public string $sortBy = 'nominal_desc'; // 'nominal_desc', 'nominal_asc', 'ppd_desc', 'ppd_asc', 'name_asc'

    public string $activeTab = 'rekap'; // 'rekap' | 'transaksi'

    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $this->tahunAnggaranId = (int) session(
            'tahun_anggaran_id',
            TahunAnggaran::where('status', 'aktif')->value('id')
                ?? TahunAnggaran::orderBy('tahun', 'desc')->value('id')
                ?? 0
        );
        $this->tahunFilter = '';
    }

    #[On('tahun-anggaran-changed')]
    public function onYearChanged(int $tahunAnggaranId): void
    {
        $this->tahunAnggaranId = $tahunAnggaranId;
        $this->tahunFilter = '';
    }

    public function switchTahunAnggaran(?int $yearId): void
    {
        if ($yearId) {
            $this->tahunAnggaranId = $yearId;
            $this->tahunFilter = '';
            session(['tahun_anggaran_id' => $yearId]);
            $this->dispatch('tahun-anggaran-changed', tahunAnggaranId: $yearId);
        } else {
            $this->tahunFilter = 'all';
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['rekap', 'transaksi'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function openUserDetail(int $userId): void
    {
        $this->selectedUserId = $userId;
    }

    public function closeUserDetail(): void
    {
        $this->selectedUserId = null;
    }

    public function getActiveFilterCountProperty(): int
    {
        $count = 0;
        if ($this->tahunFilter === 'all') {
            $count++;
        }
        if ($this->bulanFilter !== '') {
            $count++;
        }
        if ($this->unitFilter !== '') {
            $count++;
        }
        if ($this->sumberDanaFilter !== '') {
            $count++;
        }
        if ($this->search !== '') {
            $count++;
        }
        if ($this->sortBy !== 'nominal_desc') {
            $count++;
        }

        return $count;
    }

    public function removeFilter(string $key): void
    {
        match ($key) {
            'tahun' => $this->tahunFilter = '',
            'bulan' => $this->bulanFilter = '',
            'unit' => $this->unitFilter = '',
            'sumber_dana' => $this->sumberDanaFilter = '',
            'search' => $this->search = '',
            'sort' => $this->sortBy = 'nominal_desc',
            default => null,
        };
    }

    public function getSortLabelProperty(): string
    {
        return match ($this->sortBy) {
            'nominal_asc' => 'Nominal Terkecil',
            'ppd_desc' => 'PPD Terbanyak',
            'ppd_asc' => 'PPD Terendah',
            'name_asc' => 'Nama (A-Z)',
            default => 'Nominal Terbesar',
        };
    }

    public function resetFilters(): void
    {
        $this->tahunFilter = '';
        $this->bulanFilter = '';
        $this->unitFilter = '';
        $this->sumberDanaFilter = '';
        $this->search = '';
        $this->sortBy = 'nominal_desc';
        $this->selectedUserId = null;
    }

    public function exportExcel(): BinaryFileResponse
    {
        $selectedYear = $this->tahunAnggaranId ? TahunAnggaran::find($this->tahunAnggaranId) : null;
        $tahunLabel = $this->tahunFilter === 'all'
            ? 'Semua_Tahun'
            : ($selectedYear ? (string) $selectedYear->tahun : (string) date('Y'));
        $filename = "Rekap_PPD_Per_Orang_{$tahunLabel}.xlsx";

        return Excel::download(
            new LaporanPpdPerOrangExport(
                $this->tahunAnggaranId,
                $this->tahunFilter,
                $this->unitFilter,
                $this->search,
                $this->bulanFilter,
                $this->sumberDanaFilter,
                $this->sortBy
            ),
            $filename
        );
    }

    public function render(): View
    {
        $selectedYear = $this->tahunAnggaranId ? TahunAnggaran::find($this->tahunAnggaranId) : null;
        if (! $selectedYear) {
            $selectedYear = TahunAnggaran::where('status', 'aktif')->first()
                ?? TahunAnggaran::orderBy('tahun', 'desc')->first();
            $this->tahunAnggaranId = $selectedYear?->id ?? 0;
        }

        $pencairanQuery = Pencairan::with([
            'permohonan.user.leveling',
            'permohonan.user.unit.parent',
            'permohonan.unit.parent',
            'permohonan.kodeAnggaran',
            'permohonan.sumberDana',
        ])
            ->where('status', 'dicairkan')
            ->when($this->tahunFilter !== 'all' && $this->tahunAnggaranId, function ($q) {
                $q->whereHas('permohonan.kodeAnggaran', fn ($k) => $k->where('tahun_anggaran_id', $this->tahunAnggaranId));
            })
            ->when($this->bulanFilter !== '', function ($q) {
                $q->whereMonth('tgl_cair', (int) $this->bulanFilter);
            })
            ->when($this->unitFilter !== '', function ($q) {
                $q->whereHas('permohonan', fn ($p) => $p->where('unit_id', (int) $this->unitFilter));
            })
            ->when($this->sumberDanaFilter !== '', function ($q) {
                $q->whereHas('permohonan', fn ($p) => $p->where('sumber_dana_id', (int) $this->sumberDanaFilter));
            })
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('no_ppd', 'like', $term)
                        ->orWhereHas('permohonan', function ($p) use ($term) {
                            $p->where('no_pengajuan', 'like', $term)
                                ->orWhere('nama_kegiatan', 'like', $term)
                                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('jabatan', 'like', $term));
                        });
                });
            });

        $allPencairans = $pencairanQuery->orderBy('tgl_cair', 'desc')->get();
        $totalNominal = (int) $allPencairans->sum('jumlah');
        $totalPpd = $allPencairans->count();

        $rekapPerUser = $allPencairans->groupBy(fn ($p) => $p->permohonan?->user_id ?? 0)
            ->filter(fn ($items, $userId) => $userId > 0)
            ->map(function (Collection $items, int $userId) use ($totalNominal) {
                $first = $items->first();
                $user = $first->permohonan?->user;
                $uniqueKodeAnggaran = $items->map(fn ($p) => $p->permohonan?->kodeAnggaran)
                    ->filter()
                    ->unique('id')
                    ->values();

                $unitName = $user?->unit?->nama_lengkap ?? $first->permohonan?->unit?->nama_lengkap ?? '-';
                $userTotal = (int) $items->sum('jumlah');
                $persentase = $totalNominal > 0 ? round(($userTotal / $totalNominal) * 100, 1) : 0;

                // Initials for avatar
                $nameParts = explode(' ', trim($user?->name ?? 'User'));
                $initials = count($nameParts) >= 2
                    ? strtoupper(substr($nameParts[0], 0, 1).substr($nameParts[1], 0, 1))
                    : strtoupper(substr($nameParts[0], 0, 2));

                return [
                    'user' => $user,
                    'user_id' => $userId,
                    'name' => $user?->name ?? 'Tidak Diketahui',
                    'initials' => $initials,
                    'jabatan' => $user?->jabatan ?? $user?->leveling?->nama ?? 'Pemohon',
                    'leveling_nama' => $user?->leveling?->nama ?? 'Staf',
                    'leveling_urutan' => $user?->leveling?->urutan ?? 0,
                    'unit' => $unitName,
                    'ppd_count' => $items->count(),
                    'total_nominal' => $userTotal,
                    'persentase' => $persentase,
                    'pos_anggaran' => $uniqueKodeAnggaran,
                    'pencairan_items' => $items,
                ];
            });

        // Apply dynamic sorting
        $rekapPerUser = match ($this->sortBy) {
            'nominal_asc' => $rekapPerUser->sortBy('total_nominal')->values(),
            'ppd_desc' => $rekapPerUser->sortByDesc('ppd_count')->values(),
            'ppd_asc' => $rekapPerUser->sortBy('ppd_count')->values(),
            'name_asc' => $rekapPerUser->sortBy('name')->values(),
            default => $rekapPerUser->sortByDesc('total_nominal')->values(),
        };

        $selectedUserSummary = null;
        if ($this->selectedUserId) {
            $selectedUserSummary = $rekapPerUser->firstWhere('user_id', $this->selectedUserId);
        }

        $tahunList = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $unitOptions = Unit::with('parent')
            ->orderBy('nama', 'asc')
            ->get()
            ->map(fn (Unit $u) => [
                'id' => (string) $u->id,
                'kode' => $u->kode,
                'nama' => $u->parent ? "{$u->parent->nama} — {$u->nama}" : $u->nama,
            ])
            ->toArray();

        $sumberDanaList = SumberDana::where('is_active', true)->orderBy('id')->get();
        $rataRata = $totalPpd > 0 ? (int) round($totalNominal / $totalPpd) : 0;

        return view('livewire.laporan.laporan-ppd-per-orang', [
            'rekapPerUser' => $rekapPerUser,
            'allPencairans' => $allPencairans,
            'totalPpd' => $totalPpd,
            'totalNominal' => $totalNominal,
            'totalOrang' => $rekapPerUser->count(),
            'rataRata' => $rataRata,
            'tahunList' => $tahunList,
            'selectedYear' => $selectedYear,
            'unitOptions' => $unitOptions,
            'sumberDanaList' => $sumberDanaList,
            'selectedUserSummary' => $selectedUserSummary,
        ]);
    }
}
