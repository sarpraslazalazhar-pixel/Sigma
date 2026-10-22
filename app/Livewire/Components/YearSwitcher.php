<?php

namespace App\Livewire\Components;

use App\Models\TahunAnggaran;
use Livewire\Attributes\Locked;
use Livewire\Component;

class YearSwitcher extends Component
{
    #[Locked]
    public int $selectedYearId = 0;

    public function mount(): void
    {
        $this->selectedYearId = (int) session(
            'tahun_anggaran_id',
            TahunAnggaran::where('status', 'aktif')->value('id') ?? 0
        );
    }

    public function switchYear(int $yearId): void
    {
        $year = TahunAnggaran::findOrFail($yearId);
        session(['tahun_anggaran_id' => $year->id]);
        $this->selectedYearId = $year->id;
        $this->dispatch('tahun-anggaran-changed', tahunAnggaranId: $year->id);
    }

    public function render()
    {
        $years = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $selectedYear = $years->firstWhere('id', $this->selectedYearId)
            ?? $years->firstWhere('status', 'aktif')
            ?? $years->first();

        return view('livewire.components.year-switcher', [
            'years' => $years,
            'selectedYear' => $selectedYear,
        ]);
    }
}
