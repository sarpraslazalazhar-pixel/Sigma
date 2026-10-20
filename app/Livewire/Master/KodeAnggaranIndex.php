<?php

namespace App\Livewire\Master;

use App\Models\KodeAnggaran;
use App\Models\Setting;
use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use App\Services\BudgetCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Master Kode Anggaran: SIGMA LAZ Al-Azhar')]
class KodeAnggaranIndex extends Component
{
    public int $tahun_anggaran_id;

    public string $search = '';

    public string $statusFilter = '';

    public string $levelFilter = '';

    public string $leafOnly = '';

    public string $nominalFilter = '';

    public string $prefixFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public bool $showDetailModal = false;

    public ?int $detailNodeId = null;

    // Form fields
    public ?int $form_parent_id = null;

    public string $form_kode = '';

    public string $form_nama = '';

    public string $form_deskripsi = '';

    public string $form_plafon = '';

    public string $form_budget_status = 'draft';

    public array $expandedNodes = [];

    public function mount(): void
    {
        $activeYear = TahunAnggaran::where('status', 'aktif')->first()
            ?? TahunAnggaran::orderBy('tahun', 'desc')->first();

        $this->tahun_anggaran_id = $activeYear ? $activeYear->id : 0;

        // Default expand top-level nodes
        if ($this->tahun_anggaran_id) {
            $rootIds = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
                ->whereNull('parent_id')
                ->pluck('id')
                ->toArray();
            $this->expandedNodes = $rootIds;
        }
    }

    public function toggleNode(int $id): void
    {
        if (in_array($id, $this->expandedNodes)) {
            $this->expandedNodes = array_values(array_diff($this->expandedNodes, [$id]));
        } else {
            $this->expandedNodes[] = $id;
        }
    }

    public function openCreateModal(?int $parentId = null): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form_parent_id = $parentId;
        $this->form_nama = '';
        $this->form_deskripsi = '';
        $this->form_plafon = '';
        $this->form_budget_status = 'draft';

        if ($parentId) {
            $parent = KodeAnggaran::find($parentId);
            $maxDepth = (int) Setting::get('kode.max_depth', 4);
            if ($parent && $parent->depth >= $maxDepth) {
                session()->flash('error', "Kedalaman maksimum ({$maxDepth} tingkat) telah tercapai.");

                return;
            }

            // Auto-generate suggested code
            $childCount = KodeAnggaran::where('parent_id', $parentId)->count();
            $this->form_kode = $parent ? $parent->kode.'.'.($childCount + 1) : '';
        } else {
            $rootCount = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
                ->whereNull('parent_id')
                ->count();
            $this->form_kode = (string) ($rootCount + 1);
        }

        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $node = KodeAnggaran::findOrFail($id);
        $this->editingId = $node->id;
        $this->form_parent_id = $node->parent_id;
        $this->form_kode = $node->kode;
        $this->form_nama = $node->nama;
        $this->form_deskripsi = $node->deskripsi ?? '';
        $this->form_plafon = $node->plafon !== null ? (string) $node->plafon : '';
        $this->form_budget_status = $node->budget_status;

        $this->showModal = true;
    }

    public function save(): void
    {
        $maxDepth = (int) Setting::get('kode.max_depth', 4);

        if (is_string($this->form_plafon) && $this->form_plafon !== '') {
            $cleanPlafon = preg_replace('/\D/', '', $this->form_plafon);
            $this->form_plafon = $cleanPlafon !== '' ? $cleanPlafon : '';
        }

        $this->validate([
            'form_kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kode_anggaran', 'kode')
                    ->where('tahun_anggaran_id', $this->tahun_anggaran_id)
                    ->ignore($this->editingId),
            ],
            'form_nama' => ['required', 'string', 'max:255'],
            'form_deskripsi' => ['nullable', 'string', 'max:1000'],
            'form_plafon' => ['nullable', 'numeric', 'min:0'],
            'form_parent_id' => ['nullable', 'exists:kode_anggaran,id'],
        ]);

        $depth = 1;
        if ($this->form_parent_id) {
            $parent = KodeAnggaran::find($this->form_parent_id);
            $depth = $parent ? $parent->depth + 1 : 1;
        }

        if ($depth > $maxDepth) {
            $this->addError('form_parent_id', "Kedalaman kode melebihi batas sistem ({$maxDepth}).");

            return;
        }

        $plafonValue = $this->form_plafon !== '' ? (int) $this->form_plafon : null;

        $data = [
            'tahun_anggaran_id' => $this->tahun_anggaran_id,
            'parent_id' => $this->form_parent_id,
            'kode' => trim($this->form_kode),
            'nama' => trim($this->form_nama),
            'deskripsi' => trim($this->form_deskripsi) ?: null,
            'depth' => $depth,
            'plafon' => $plafonValue,
            'budget_status' => $this->form_budget_status,
            'is_active' => true,
        ];

        $logger = new AuditLogger;

        if ($this->editingId) {
            $node = KodeAnggaran::findOrFail($this->editingId);
            $before = $node->toArray();

            // Direct plafon edit on disahkan nodes with floor & ceiling protection
            if ($node->budget_status === 'disahkan' && $plafonValue !== $node->plafon) {
                // Role Guard
                if (! in_array(Auth::user()?->role, ['admin', 'keuangan'])) {
                    $this->addError('form_plafon', 'Hanya admin dan divisi keuangan yang dapat mengubah plafon yang telah disahkan.');

                    return;
                }

                // Floor Guard (R5 Invariant)
                $calc = (new BudgetCalculator)->calculateTree($this->tahun_anggaran_id)->get($node->id);
                $floor = ($calc['realisasi'] ?? 0) + ($calc['dana_terikat'] ?? 0);
                if ($plafonValue < $floor) {
                    $this->addError('form_plafon', 'Plafon tidak boleh lebih kecil dari Realisasi + Terikat (Rp '.number_format($floor, 0, ',', '.').').');

                    return;
                }

                // Parent Ceiling Guard
                if ($node->parent_id) {
                    $parent = KodeAnggaran::find($node->parent_id);
                    if ($parent && $parent->plafon !== null) {
                        $siblingsSum = KodeAnggaran::where('parent_id', $parent->id)
                            ->where('is_active', true)
                            ->where('id', '!=', $node->id)
                            ->sum('plafon') + $plafonValue;

                        if ($siblingsSum > $parent->plafon) {
                            $this->addError('form_plafon', 'Total plafon anak (Rp '.number_format($siblingsSum, 0, ',', '.').') melebihi plafon induk (Rp '.number_format($parent->plafon, 0, ',', '.').'). Ubah plafon induk terlebih dahulu.');

                            return;
                        }
                    }
                }

                // Children Floor Guard: Plafon node tidak boleh lebih kecil dari jumlah plafon anak-anaknya
                $childrenPlafonSum = KodeAnggaran::where('parent_id', $node->id)
                    ->where('is_active', true)
                    ->whereNotNull('plafon')
                    ->sum('plafon');

                if ($plafonValue !== null && $childrenPlafonSum > 0 && $plafonValue < $childrenPlafonSum) {
                    $this->addError('form_plafon', 'Plafon tidak boleh lebih kecil dari total alokasi anak (Rp '.number_format($childrenPlafonSum, 0, ',', '.').').');

                    return;
                }
            }

            $node->update($data);
            $logger->log('kode_anggaran.updated', $node, $before, $node->fresh()->toArray());

            if ($node->budget_status === 'disahkan' && $plafonValue !== ($before['plafon'] ?? null)) {
                session()->flash('success', "Plafon pos anggaran {$node->kode} berhasil diperbarui dari Rp ".number_format($before['plafon'] ?? 0, 0, ',', '.').' menjadi Rp '.number_format($node->plafon, 0, ',', '.').'.');
            } else {
                session()->flash('success', "Kode anggaran {$node->kode} berhasil diperbarui.");
            }
        } else {
            $node = KodeAnggaran::create($data);
            $logger->log('kode_anggaran.created', $node, null, $node->toArray());
            session()->flash('success', "Kode anggaran {$node->kode} berhasil ditambahkan.");

            if ($this->form_parent_id && ! in_array($this->form_parent_id, $this->expandedNodes)) {
                $this->expandedNodes[] = $this->form_parent_id;
            }
        }

        $this->showModal = false;
    }

    public function sahkanBudget(int $id): void
    {
        $node = KodeAnggaran::findOrFail($id);
        $before = $node->toArray();
        $node->update(['budget_status' => 'disahkan']);

        (new AuditLogger)->log('kode_anggaran.disahkan', $node, $before, $node->fresh()->toArray());

        session()->flash('success', "Budget kode anggaran {$node->kode} berhasil disahkan.");
    }

    public function toggleActive(int $id): void
    {
        $node = KodeAnggaran::findOrFail($id);
        $before = $node->toArray();
        $node->update(['is_active' => ! $node->is_active]);

        (new AuditLogger)->log('kode_anggaran.toggle_active', $node, $before, $node->fresh()->toArray());

        $statusStr = $node->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('success', "Kode anggaran {$node->kode} berhasil {$statusStr}.");
    }

    public function openDetailModal(int $id): void
    {
        $this->detailNodeId = $id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailNodeId = null;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->levelFilter = '';
        $this->leafOnly = '';
        $this->nominalFilter = '';
        $this->prefixFilter = '';
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return ! empty($this->search)
            || ! empty($this->statusFilter)
            || ! empty($this->levelFilter)
            || ! empty($this->leafOnly)
            || ! empty($this->nominalFilter)
            || ! empty($this->prefixFilter);
    }

    public function render()
    {
        $years = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $selectedYear = TahunAnggaran::find($this->tahun_anggaran_id);

        $calculator = new BudgetCalculator;
        $calculatedData = $this->tahun_anggaran_id ? $calculator->calculateTree($this->tahun_anggaran_id) : collect();

        // Prefix list for filter dropdown (Distinct parent / sub-parent codes like 1, 2, 2.1, 2.4, 2.4.1, 2.4.2)
        $prefixOptions = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->where('depth', '<=', 3)
            ->whereHas('children')
            ->orderBy('kode', 'asc')
            ->get(['id', 'kode', 'nama', 'depth']);

        // Query nodes with all interactive filters
        $query = KodeAnggaran::query()
            ->where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('kode', 'like', "%{$this->search}%")
                        ->orWhere('nama', 'like', "%{$this->search}%");
                });
            })
            ->when($this->prefixFilter, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('kode', $this->prefixFilter)
                        ->orWhere('kode', 'like', "{$this->prefixFilter}.%");
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('budget_status', $this->statusFilter);
            })
            ->when($this->levelFilter !== '', function ($q) {
                $q->where('depth', (int) $this->levelFilter);
            })
            ->when($this->leafOnly === '1', function ($q) {
                $q->whereDoesntHave('children');
            })
            ->when($this->nominalFilter, function ($q) {
                match ($this->nominalFilter) {
                    'gt_100m' => $q->where('plafon', '>', 100000000),
                    '10m_100m' => $q->whereBetween('plafon', [10000000, 100000000]),
                    'lt_10m' => $q->where('plafon', '<', 10000000)->whereNotNull('plafon'),
                    'has_plafon' => $q->whereNotNull('plafon'),
                    'null_plafon' => $q->whereNull('plafon'),
                    default => null,
                };
            })
            ->orderBy('kode', 'asc');

        $allNodes = $query->get();

        // Build parent-child tree
        $nodesByParent = $allNodes->groupBy('parent_id');
        $rootNodes = $nodesByParent->get(null, collect());

        // Potential parent choices for create modal
        $parentChoices = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->where('depth', '<', (int) Setting::get('kode.max_depth', 4))
            ->when($this->editingId, function ($q) {
                $q->where('id', '!=', $this->editingId);
            })
            ->orderBy('kode')
            ->get();

        // Detail node inspection if open
        $detailNode = null;
        $detailMetrics = null;
        if ($this->showDetailModal && $this->detailNodeId) {
            $detailNode = KodeAnggaran::with([
                'parent',
                'children' => fn ($q) => $q->orderBy('kode'),
                'tahunAnggaran',
            ])->find($this->detailNodeId);
            $detailMetrics = $calculatedData->get($this->detailNodeId);
        }

        return view('livewire.master.kode-anggaran-index', [
            'years' => $years,
            'selectedYear' => $selectedYear,
            'allNodes' => $allNodes,
            'nodesByParent' => $nodesByParent,
            'rootNodes' => $rootNodes,
            'calculatedData' => $calculatedData,
            'parentChoices' => $parentChoices,
            'prefixOptions' => $prefixOptions,
            'detailNode' => $detailNode,
            'detailMetrics' => $detailMetrics,
        ]);
    }
}
