<?php

namespace App\Livewire\Permohonan;

use App\Models\Asnaf;
use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\ProgramUtama;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BudgetCalculator;
use App\Services\PermohonanWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Buat Permohonan Dana: SIGMA LAZ Al-Azhar')]
class PermohonanCreate extends Component
{
    // Header fields
    public string $program = '';

    public string $nama_kegiatan = '';

    public ?int $unit_id = null;

    public string $tgl_mulai = '';

    public ?string $tgl_selesai = null;

    public ?int $sumber_dana_id = null;

    public ?int $kode_anggaran_id = null;

    public string $catatan_over = '';

    public ?int $atasan_id = null;

    // Field khusus Petugas Input / TU: Pejabat Pembuat (Supervisor, Manajer, atau Kadiv)
    public ?int $user_id = null;

    public string $creatorLevel = '';

    // Multi-row items
    public array $items = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->user_id = $user?->id;
        $this->unit_id = $user?->unit_id;
        $this->tgl_mulai = now()->toDateString();

        $defaultSumberDana = null;
        $defaultAsnafId = null;
        if ($this->unit_id) {
            $unit = Unit::with('parent')->find($this->unit_id);
            $isHumas = $unit && (
                str_contains(strtoupper($unit->kode), 'HUM') ||
                str_contains(strtolower($unit->nama), 'humas')
            );

            if ($isHumas) {
                $infakUmum = SumberDana::where('nama', 'like', '%Infak Umum%')->first();
                $defaultSumberDana = $infakUmum?->id ?? $unit?->sumber_dana_default_id;
            } else {
                $zakat = SumberDana::where('nama', 'like', '%Zakat%')->first();
                $defaultSumberDana = $unit?->sumber_dana_default_id ?? $zakat?->id;
                $defaultAsnafId = Asnaf::where('nama', 'Amil')->value('id');
            }
        }

        if (! $defaultSumberDana) {
            $defaultSumberDana = SumberDana::where('is_active', true)->value('id');
        }

        $this->sumber_dana_id = $defaultSumberDana;

        // Default program kosong agar diketik manual oleh pengaju
        $this->program = '';

        // Default 1 row
        $this->items = [
            [
                'uraian' => '',
                'asnaf_id' => $defaultAsnafId,
                'program_utama_id' => null,
                'jumlah_diminta' => '',
            ],
        ];
    }

    public function updatedUnitId(?int $value): void
    {
        if (! $value) {
            return;
        }

        $unit = Unit::with('parent')->find($value);
        $isHumas = $unit && (
            str_contains(strtoupper($unit->kode), 'HUM') ||
            str_contains(strtolower($unit->nama), 'humas')
        );

        if ($isHumas) {
            $infakUmum = SumberDana::where('nama', 'like', '%Infak Umum%')->first();
            $this->sumber_dana_id = $infakUmum?->id ?? $unit?->sumber_dana_default_id;
        } else {
            $zakat = SumberDana::where('nama', 'like', '%Zakat%')->first();
            $this->sumber_dana_id = $unit?->sumber_dana_default_id ?? $zakat?->id;

            $asnafAmil = Asnaf::where('nama', 'Amil')->first();
            if ($asnafAmil) {
                foreach ($this->items as $idx => $item) {
                    if (empty($this->items[$idx]['asnaf_id'])) {
                        $this->items[$idx]['asnaf_id'] = $asnafAmil->id;
                    }
                }
            }
        }
    }

    public function updatedUserId(?int $value): void
    {
        if (! $value) {
            return;
        }

        $selected = User::with('unit')->find($value);
        if ($selected && $selected->unit_id) {
            $this->unit_id = $selected->unit_id;
            $this->updatedUnitId($selected->unit_id);
        }
    }

    public function getIsTuProperty(): bool
    {
        $user = Auth::user();

        return $user && ($user->is_petugas_input || $user->role === 'admin' || $user->role === 'keuangan');
    }

    public function getSelectedCreatorLevelProperty(): int
    {
        $creatorId = ($this->isTu && $this->user_id) ? $this->user_id : Auth::id();
        $creator = $creatorId ? User::with('leveling')->find($creatorId) : null;

        return $creator?->leveling?->urutan ?? 1;
    }

    public function updatedKodeAnggaranId(?int $value): void
    {
        if ($value) {
            $kode = KodeAnggaran::find($value);
            if ($kode && (str_starts_with($kode->kode, '2.4') || str_contains(strtolower($kode->nama), 'amil'))) {
                $zakat = SumberDana::where('nama', 'like', '%Zakat%')->first();
                if ($zakat) {
                    $this->sumber_dana_id = $zakat->id;
                }
                $asnafAmil = Asnaf::where('nama', 'Amil')->first();
                if ($asnafAmil) {
                    foreach ($this->items as $idx => $item) {
                        if (empty($this->items[$idx]['asnaf_id'])) {
                            $this->items[$idx]['asnaf_id'] = $asnafAmil->id;
                        }
                    }
                }
            }
        }
    }

    public function addItem(): void
    {
        $sumberDana = $this->sumber_dana_id ? SumberDana::find($this->sumber_dana_id) : null;
        $defaultAsnaf = ($sumberDana && $sumberDana->butuh_asnaf)
            ? Asnaf::where('nama', 'Amil')->value('id')
            : null;

        $this->items[] = [
            'uraian' => '',
            'asnaf_id' => $defaultAsnaf,
            'program_utama_id' => null,
            'jumlah_diminta' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function getTotalDimintaProperty(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $raw = $item['jumlah_diminta'] ?? 0;
            if (is_string($raw)) {
                $raw = preg_replace('/\D/', '', $raw);
            }
            $val = is_numeric($raw) ? (int) $raw : 0;
            $total += max(0, $val);
        }

        return $total;
    }

    public function getBudgetInfoProperty(): ?array
    {
        if (! $this->kode_anggaran_id) {
            return null;
        }

        $leaf = KodeAnggaran::find($this->kode_anggaran_id);
        if (! $leaf) {
            return null;
        }

        $calculator = new BudgetCalculator;
        $tree = $calculator->calculateTree($leaf->tahun_anggaran_id);

        return $tree->get($leaf->id);
    }

    public function getIsOverBudgetProperty(): bool
    {
        $info = $this->budgetInfo;
        if (! $info) {
            return false;
        }

        $sisa = $info['sisa_tersedia'] ?? 0;

        return $this->totalDiminta > $sisa;
    }

    public function getRequiredApproverProperty(): ?Leveling
    {
        $total = $this->totalDiminta;

        return Leveling::where('batas_bawah', '<=', $total)
            ->where(function ($q) use ($total) {
                $q->whereNull('batas_atas')->orWhere('batas_atas', '>=', $total);
            })
            ->orderBy('urutan', 'desc')
            ->first();
    }

    protected function validateForm(): void
    {
        foreach ($this->items as $idx => $item) {
            if (isset($item['jumlah_diminta']) && is_string($item['jumlah_diminta'])) {
                $clean = preg_replace('/\D/', '', $item['jumlah_diminta']);
                $this->items[$idx]['jumlah_diminta'] = $clean !== '' ? (int) $clean : null;
            }
        }

        $sumberDana = SumberDana::find($this->sumber_dana_id);
        $needsAsnaf = $sumberDana && $sumberDana->butuh_asnaf;

        $rules = [
            'program' => ['required', 'string', 'max:255'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'unit_id' => ['required', 'exists:units,id'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['nullable', 'date', 'after_or_equal:tgl_mulai'],
            'sumber_dana_id' => ['required', 'exists:sumber_dana,id'],
            'kode_anggaran_id' => ['required', 'exists:kode_anggaran,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uraian' => ['required', 'string', 'max:255'],
            'items.*.jumlah_diminta' => ['required', 'integer', 'min:1'],
        ];

        if ($needsAsnaf) {
            $rules['items.*.asnaf_id'] = ['required', 'exists:asnaf,id'];
        }

        if ($this->isOverBudget) {
            $rules['catatan_over'] = ['required', 'string', 'min:10'];
        }

        $messages = [
            'items.*.uraian.required' => 'Uraian rincian wajib diisi.',
            'items.*.jumlah_diminta.required' => 'Nominal rincian wajib diisi.',
            'items.*.jumlah_diminta.min' => 'Nominal rincian minimal Rp 1.',
            'items.*.asnaf_id.required' => 'Asnaf wajib dipilih untuk sumber dana Zakat.',
            'catatan_over.required' => 'Catatan over budget wajib diisi karena total pengajuan melebihi sisa plafon.',
            'catatan_over.min' => 'Catatan over budget minimal 10 karakter.',
        ];
        if ($this->isTu) {
            $rules['user_id'] = ['required', 'exists:users,id'];
        }
        $this->validate($rules, $messages);
    }

    public function saveDraft(): void
    {
        $this->validateForm();

        $permohonan = DB::transaction(function () {
            $p = Permohonan::create([
                'no_pengajuan' => 'DRAFT-'.strtoupper(Str::random(10)),
                'unit_id' => $this->unit_id,
                'atasan_id' => $this->atasan_id ?: null,
                'user_id' => ($this->isTu && $this->user_id) ? $this->user_id : Auth::id(),
                'kode_anggaran_id' => $this->kode_anggaran_id,
                'program' => $this->program,
                'nama_kegiatan' => $this->nama_kegiatan,
                'tgl_mulai' => $this->tgl_mulai,
                'tgl_selesai' => $this->tgl_selesai ?: null,
                'sumber_dana_id' => $this->sumber_dana_id,
                'status' => 'draft',
                'total_diminta' => $this->totalDiminta,
                'catatan_over' => $this->isOverBudget ? $this->catatan_over : null,
            ]);

            foreach ($this->items as $item) {
                PermohonanItem::create([
                    'permohonan_id' => $p->id,
                    'uraian' => trim($item['uraian']),
                    'asnaf_id' => $item['asnaf_id'] ?: null,
                    'program_utama_id' => $item['program_utama_id'] ?: null,
                    'jumlah_diminta' => (int) $item['jumlah_diminta'],
                ]);
            }

            (new AuditLogger)->log('permohonan.created_draft', $p, null, $p->toArray());

            return $p;
        });

        session()->flash('success', 'Permohonan dana berhasil disimpan sebagai Draft.');
        $this->redirect(route('permohonan.detail', $permohonan->id), navigate: true);
    }

    public function submitNow(): void
    {
        $this->validateForm();

        $permohonan = DB::transaction(function () {
            $p = Permohonan::create([
                'no_pengajuan' => 'DRAFT-'.strtoupper(Str::random(10)),
                'unit_id' => $this->unit_id,
                'atasan_id' => $this->atasan_id ?: null,
                'user_id' => ($this->isTu && $this->user_id) ? $this->user_id : Auth::id(),
                'kode_anggaran_id' => $this->kode_anggaran_id,
                'program' => $this->program,
                'nama_kegiatan' => $this->nama_kegiatan,
                'tgl_mulai' => $this->tgl_mulai,
                'tgl_selesai' => $this->tgl_selesai ?: null,
                'sumber_dana_id' => $this->sumber_dana_id,
                'status' => 'draft',
                'total_diminta' => $this->totalDiminta,
                'catatan_over' => $this->isOverBudget ? $this->catatan_over : null,
            ]);

            foreach ($this->items as $item) {
                PermohonanItem::create([
                    'permohonan_id' => $p->id,
                    'uraian' => trim($item['uraian']),
                    'asnaf_id' => $item['asnaf_id'] ?: null,
                    'program_utama_id' => $item['program_utama_id'] ?: null,
                    'jumlah_diminta' => (int) $item['jumlah_diminta'],
                ]);
            }

            // Submit langsung melalui workflow
            $workflow = new PermohonanWorkflow;

            return $workflow->submit($p);
        });

        session()->flash('success', "Permohonan dana {$permohonan->no_pengajuan} berhasil diajukan untuk proses persetujuan.");
        $this->redirect(route('permohonan.detail', $permohonan->id), navigate: true);
    }

    public function render(): View
    {
        $activeYear = TahunAnggaran::where('status', 'aktif')->first();
        $taId = $activeYear ? $activeYear->id : 0;

        // Ambil leaf kode anggaran saja
        $rawLeaf = KodeAnggaran::where('tahun_anggaran_id', $taId)
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->get();
        $kodeAnggaranList = KodeAnggaran::sortHierarchically($rawLeaf);

        $programs = ProgramUtama::where('is_active', true)->orderBy('nama', 'asc')->get();
        $units = Unit::orderBy('nama', 'asc')->get();
        $sumberDanaList = SumberDana::where('is_active', true)->orderBy('id', 'asc')->get();
        $asnafList = Asnaf::where('is_active', true)->orderBy('id', 'asc')->get();

        $selectedSumberDana = SumberDana::find($this->sumber_dana_id);
        $needsAsnaf = $selectedSumberDana && $selectedSumberDana->butuh_asnaf;
        $creatorsQuery = User::with(['unit', 'leveling'])
            ->where('is_active', true);

        if ($this->creatorLevel === '1') {
            $creatorsQuery->whereHas('leveling', fn ($q) => $q->where('urutan', 1));
        } elseif ($this->creatorLevel === '2') {
            $creatorsQuery->whereHas('leveling', fn ($q) => $q->where('urutan', 2));
        } elseif ($this->creatorLevel === '3') {
            $creatorsQuery->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3));
        }

        $creatorCandidates = $creatorsQuery->orderBy('name')->get()->map(function ($u) {
            $lvlName = $u->leveling?->nama ?? 'Staff';
            $unitCode = $u->unit?->kode ?? '';
            $subText = "[{$lvlName}] ".($u->jabatan ?: $u->unit?->nama ?? '').($unitCode ? " ({$unitCode})" : '');

            return [
                'id' => $u->id,
                'name' => $u->name,
                'sub' => $subText,
            ];
        });

        return view('livewire.permohonan.permohonan-create', [
            'isTu' => $this->isTu,
            'creatorCandidates' => $creatorCandidates,
            'selectedCreatorLevel' => $this->selectedCreatorLevel,
            'programs' => $programs,
            'units' => $units,
            'sumberDanaList' => $sumberDanaList,
            'kodeAnggaranList' => $kodeAnggaranList,
            'asnafList' => $asnafList,
            'needsAsnaf' => $needsAsnaf,
            'budgetInfo' => $this->budgetInfo,
            'isOverBudget' => $this->isOverBudget,
            'requiredApprover' => $this->requiredApprover,
            'totalDiminta' => $this->totalDiminta,
        ]);
    }
}
