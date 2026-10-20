<?php

namespace App\Livewire\Master;

use App\Models\SumberDana;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Unit & Divisi: SIGMA LAZ Al-Azhar')]
class UnitDivisiIndex extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $form_parent_id = null;

    public string $form_kode = '';

    public string $form_nama = '';

    public string $form_tipe = 'divisi';

    public ?int $form_sumber_dana_default_id = null;

    public function openCreateModal(?int $parentId = null): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form_parent_id = $parentId;
        $this->form_kode = '';
        $this->form_nama = '';
        $this->form_tipe = $parentId ? 'unit' : 'divisi';
        $this->form_sumber_dana_default_id = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $unit = Unit::findOrFail($id);
        $this->editingId = $unit->id;
        $this->form_parent_id = $unit->parent_id;
        $this->form_kode = $unit->kode;
        $this->form_nama = $unit->nama;
        $this->form_tipe = $unit->tipe;
        $this->form_sumber_dana_default_id = $unit->sumber_dana_default_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'kode')->ignore($this->editingId),
            ],
            'form_nama' => ['required', 'string', 'max:255'],
            'form_tipe' => ['required', 'in:unit,divisi'],
            'form_parent_id' => ['nullable', 'exists:units,id'],
            'form_sumber_dana_default_id' => ['nullable', 'exists:sumber_dana,id'],
        ]);

        $data = [
            'kode' => strtoupper(trim($this->form_kode)),
            'nama' => trim($this->form_nama),
            'tipe' => $this->form_tipe,
            'parent_id' => $this->form_parent_id,
            'sumber_dana_default_id' => $this->form_sumber_dana_default_id,
            'is_active' => true,
        ];

        $logger = new AuditLogger;

        if ($this->editingId) {
            $unit = Unit::findOrFail($this->editingId);
            $before = $unit->toArray();
            $unit->update($data);
            $logger->log('unit.updated', $unit, $before, $unit->fresh()->toArray());
            session()->flash('success', "Divisi/Unit {$unit->kode} berhasil diperbarui.");
        } else {
            $unit = Unit::create($data);
            $logger->log('unit.created', $unit, null, $unit->toArray());
            session()->flash('success', "Divisi/Unit {$unit->kode} berhasil dibuat.");
        }

        $this->showModal = false;
    }

    public function toggleActive(int $id): void
    {
        $unit = Unit::findOrFail($id);
        $before = $unit->toArray();
        $unit->update(['is_active' => ! $unit->is_active]);

        (new AuditLogger)->log('unit.toggle_active', $unit, $before, $unit->fresh()->toArray());

        $statusStr = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('success', "Unit/Divisi {$unit->kode} berhasil {$statusStr}.");
    }

    public function render()
    {
        $units = Unit::with(['parent', 'children', 'sumberDanaDefault', 'users'])
            ->whereNull('parent_id')
            ->orderBy('kode')
            ->get();

        $allUnits = Unit::with(['parent', 'sumberDanaDefault', 'users'])
            ->orderBy('parent_id')
            ->orderBy('kode')
            ->get();

        $sumberDanaList = SumberDana::where('is_active', true)->get();
        $parentChoices = Unit::whereNull('parent_id')->get();

        return view('livewire.master.unit-divisi-index', [
            'units' => $units,
            'allUnits' => $allUnits,
            'sumberDanaList' => $sumberDanaList,
            'parentChoices' => $parentChoices,
        ]);
    }
}
