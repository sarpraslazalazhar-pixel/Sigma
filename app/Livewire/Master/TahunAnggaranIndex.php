<?php

namespace App\Livewire\Master;

use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tahun Anggaran: SIGMA LAZ Al-Azhar')]
class TahunAnggaranIndex extends Component
{
    public bool $showModal = false;

    public string $form_tahun = '';

    public string $form_status = 'draft';

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $latestYear = TahunAnggaran::max('tahun');
        $this->form_tahun = (string) (($latestYear ?? 2026) + 1);
        $this->form_status = 'draft';
        $this->showModal = true;
    }

    public function saveYear(): void
    {
        $this->validate([
            'form_tahun' => ['required', 'integer', 'min:2020', 'max:2100', 'unique:tahun_anggaran,tahun'],
            'form_status' => ['required', 'in:draft,aktif,ditutup'],
        ]);

        $status = $this->form_status;
        if ($status === 'aktif') {
            TahunAnggaran::where('status', 'aktif')->update(['status' => 'draft']);
        }

        $ta = TahunAnggaran::create([
            'tahun' => (int) $this->form_tahun,
            'status' => $status,
        ]);

        (new AuditLogger)->log('tahun_anggaran.created', $ta, null, $ta->toArray());

        $this->showModal = false;
        session()->flash('success', "Tahun Anggaran {$ta->tahun} berhasil dibuat.");
    }

    public function activateYear(int $id): void
    {
        $target = TahunAnggaran::findOrFail($id);
        $before = $target->toArray();

        // Hanya satu tahun anggaran yang berstatus aktif
        TahunAnggaran::where('status', 'aktif')->where('id', '!=', $id)->update(['status' => 'draft']);
        $target->update(['status' => 'aktif']);

        (new AuditLogger)->log('tahun_anggaran.activated', $target, $before, $target->fresh()->toArray());

        session()->flash('success', "Tahun Anggaran {$target->tahun} sekarang aktif.");
    }

    public function closeYear(int $id): void
    {
        $target = TahunAnggaran::findOrFail($id);
        $before = $target->toArray();

        $target->update(['status' => 'ditutup']);

        (new AuditLogger)->log('tahun_anggaran.closed', $target, $before, $target->fresh()->toArray());

        session()->flash('success', "Tahun Anggaran {$target->tahun} telah ditutup (read-only).");
    }

    public function render()
    {
        $years = TahunAnggaran::withCount('kodeAnggaran')
            ->orderBy('tahun', 'desc')
            ->get();

        return view('livewire.master.tahun-anggaran-index', [
            'years' => $years,
        ]);
    }
}
