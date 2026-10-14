<?php

namespace App\Livewire;

use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\TahunAnggaran;
use App\Services\BudgetCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard: SIGMA LAZ Al-Azhar')]
class Dashboard extends Component
{
    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        $activeYear = TahunAnggaran::where('status', 'aktif')->first()
            ?? TahunAnggaran::orderBy('tahun', 'desc')->first();

        $calculator = new BudgetCalculator();
        $calculatedData = $activeYear ? $calculator->calculateTree($activeYear->id) : collect();

        $rootNodes = $activeYear
            ? KodeAnggaran::where('tahun_anggaran_id', $activeYear->id)
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->get()
            : collect();

        $totalPlafonDisahkan = $rootNodes->where('budget_status', 'disahkan')->sum(function ($root) use ($calculatedData) {
            return $calculatedData->get($root->id)['effective_budget'] ?? ($root->plafon ?? 0);
        });

        $nodeCount = $activeYear
            ? KodeAnggaran::where('tahun_anggaran_id', $activeYear->id)->where('is_active', true)->count()
            : 0;
        $danaTerikat = $activeYear
            ? Permohonan::whereIn('status', ['disetujui_keuangan', 'dicairkan'])
                ->whereHas('kodeAnggaran', fn ($q) => $q->where('tahun_anggaran_id', $activeYear->id))
                ->sum('total_disetujui')
            : 0;
        $realisasi = 0;
        $sisaTersedia = max(0, $totalPlafonDisahkan - $danaTerikat - $realisasi);
        $isDisahkan = $rootNodes->where('budget_status', 'disahkan')->isNotEmpty();

        return view('livewire.dashboard', [
            'user' => Auth::user(),
            'activeYear' => $activeYear,
            'totalPlafonDisahkan' => $totalPlafonDisahkan,
            'nodeCount' => $nodeCount,
            'danaTerikat' => $danaTerikat,
            'realisasi' => $realisasi,
            'sisaTersedia' => $sisaTersedia,
            'isDisahkan' => $isDisahkan,
        ]);
    }
}
