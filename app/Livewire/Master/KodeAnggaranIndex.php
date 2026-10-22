<?php

namespace App\Livewire\Master;

use App\Exports\TemplateAlokasiAnggaranExport;
use App\Imports\AlokasiAnggaranImport;
use App\Models\AlokasiAnggaran;
use App\Models\KodeAnggaran;
use App\Models\Setting;
use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use App\Services\BudgetCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Master Kode Anggaran: SIGMA LAZ Al-Azhar')]
class KodeAnggaranIndex extends Component
{
    use WithFileUploads;

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

    public string $form_keterangan_revisi = '';

    public string $form_budget_status = 'draft';

    public array $expandedNodes = [];

    // Batch Edit Mode Properties
    public bool $batchEditMode = false;

    public array $batchEdits = [];

    public string $batchRevisionNote = '';

    // Excel Import Properties
    public bool $showImportModal = false;

    public bool $showDiffModal = false;

    public array $importDiff = [];

    public string $importRevisionNote = '';

    public $importFile = null;

    public function mount(): void
    {
        $this->tahun_anggaran_id = (int) session(
            'tahun_anggaran_id',
            TahunAnggaran::where('status', 'aktif')->value('id')
                ?? TahunAnggaran::orderBy('tahun', 'desc')->value('id')
                ?? 0
        );

        // Default: semua cabang di-hide (hanya level 1 yang tampil), anak-anak bisa di-show saat di-klik
        $this->expandedNodes = [];
    }

    #[On('tahun-anggaran-changed')]
    public function onYearChanged(int $tahunAnggaranId): void
    {
        $this->tahun_anggaran_id = $tahunAnggaranId;
        $this->expandedNodes = [];
    }

    public function expandAll(): void
    {
        $this->expandedNodes = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->whereHas('children')
            ->pluck('id')
            ->toArray();
    }

    public function collapseAll(): void
    {
        $this->expandedNodes = [];
    }

    public function updatedTahunAnggaranId(): void
    {
        $this->expandedNodes = [];
        $this->resetFilters();
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
        $this->form_keterangan_revisi = '';
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
        $alokasi = $node->alokasi($this->tahun_anggaran_id);
        $this->form_plafon = $alokasi ? (string) $alokasi->plafon_efektif : '';
        $this->form_keterangan_revisi = $alokasi?->keterangan_revisi ?? '';
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
        $year = TahunAnggaran::findOrFail($this->tahun_anggaran_id);
        if ($year->status === 'ditutup') {
            $this->addError('form_plafon', 'Tahun anggaran telah ditutup. Perubahan tidak diizinkan.');

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
            'budget_status' => $this->form_budget_status,
            'is_active' => true,
        ];

        $logger = new AuditLogger;

        if ($this->editingId) {
            $node = KodeAnggaran::findOrFail($this->editingId);
            $before = $node->toArray();
            $oldPlafon = $node->getPlafonEfektif($this->tahun_anggaran_id);

            // Direct plafon edit on disahkan nodes with floor & ceiling protection
            if ($node->budget_status === 'disahkan' && $plafonValue !== $oldPlafon) {
                // Role Guard
                if (! in_array(Auth::user()?->role, ['admin', 'keuangan'])) {
                    $this->addError('form_plafon', 'Hanya admin dan divisi keuangan yang dapat mengubah plafon yang telah disahkan.');

                    return;
                }

                // Floor Guard (Realisasi + Terikat)
                $calc = (new BudgetCalculator)->calculateTree($this->tahun_anggaran_id)->get($node->id);
                $floor = ($calc['realisasi'] ?? 0) + ($calc['dana_terikat'] ?? 0);
                if ($plafonValue !== null && $plafonValue < $floor) {
                    $this->addError('form_plafon', 'Plafon tidak boleh lebih kecil dari Realisasi + Terikat (Rp '.number_format($floor, 0, ',', '.').').');

                    return;
                }

                // Parent Ceiling Guard
                if ($node->parent_id) {
                    $parent = KodeAnggaran::find($node->parent_id);
                    $parentAlokasi = $parent?->alokasi($this->tahun_anggaran_id);
                    if ($parent && $parentAlokasi && $parentAlokasi->plafon_efektif !== null) {
                        $parentPlafon = $parentAlokasi->plafon_efektif;
                        $siblingIds = KodeAnggaran::where('parent_id', $parent->id)
                            ->where('is_active', true)
                            ->where('id', '!=', $node->id)
                            ->pluck('id');

                        $siblingsSum = (int) AlokasiAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
                            ->whereIn('kode_anggaran_id', $siblingIds)
                            ->sum(DB::raw('COALESCE(plafon_revisi, plafon_awal)')) + ($plafonValue ?? 0);

                        if ($siblingsSum > $parentPlafon) {
                            $this->addError('form_plafon', 'Total plafon anak (Rp '.number_format($siblingsSum, 0, ',', '.').') melebihi plafon induk (Rp '.number_format($parentPlafon, 0, ',', '.').'). Ubah plafon induk terlebih dahulu.');

                            return;
                        }
                    }
                }

                // Children Floor Guard: Plafon node tidak boleh lebih kecil dari jumlah plafon anak-anaknya
                $childIds = KodeAnggaran::where('parent_id', $node->id)
                    ->where('is_active', true)
                    ->pluck('id');

                $childrenPlafonSum = (int) AlokasiAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
                    ->whereIn('kode_anggaran_id', $childIds)
                    ->sum(DB::raw('COALESCE(plafon_revisi, plafon_awal)'));

                if ($plafonValue !== null && $childrenPlafonSum > 0 && $plafonValue < $childrenPlafonSum) {
                    $this->addError('form_plafon', 'Plafon tidak boleh lebih kecil dari total alokasi anak (Rp '.number_format($childrenPlafonSum, 0, ',', '.').').');

                    return;
                }
            }

            // Revision note guard on active year
            if ($year->status === 'aktif' && $plafonValue !== null && $plafonValue !== $oldPlafon && blank($this->form_keterangan_revisi)) {
                $this->addError('form_keterangan_revisi', 'Keterangan revisi wajib diisi untuk perubahan anggaran pada tahun anggaran aktif.');

                return;
            }

            $node->update($data);

            if ($plafonValue !== null) {
                $alokasi = AlokasiAnggaran::firstOrNew([
                    'kode_anggaran_id' => $node->id,
                    'tahun_anggaran_id' => $this->tahun_anggaran_id,
                ]);
                if ($year->status === 'draft') {
                    $alokasi->plafon_awal = $plafonValue;
                } else {
                    $alokasi->plafon_revisi = $plafonValue;
                    $alokasi->keterangan_revisi = $this->form_keterangan_revisi ?: null;
                }
                $alokasi->save();
            } else {
                AlokasiAnggaran::where('kode_anggaran_id', $node->id)
                    ->where('tahun_anggaran_id', $this->tahun_anggaran_id)
                    ->delete();
            }

            $logger->log('kode_anggaran.updated', $node, $before, $node->fresh()->toArray());

            if ($node->budget_status === 'disahkan' && $plafonValue !== $oldPlafon) {
                $msg = "Plafon pos anggaran {$node->kode} berhasil diperbarui dari Rp ".number_format($oldPlafon, 0, ',', '.').' menjadi Rp '.number_format($plafonValue ?? 0, 0, ',', '.').'.';
            } else {
                $msg = "Kode anggaran {$node->kode} berhasil diperbarui.";
            }
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        } else {
            $node = KodeAnggaran::create($data);
            if ($plafonValue !== null) {
                $alokasi = AlokasiAnggaran::firstOrNew([
                    'kode_anggaran_id' => $node->id,
                    'tahun_anggaran_id' => $this->tahun_anggaran_id,
                ]);
                if ($year->status === 'draft') {
                    $alokasi->plafon_awal = $plafonValue;
                } else {
                    $alokasi->plafon_revisi = $plafonValue;
                    $alokasi->keterangan_revisi = $this->form_keterangan_revisi ?: null;
                }
                $alokasi->save();
            }
            $logger->log('kode_anggaran.created', $node, null, $node->toArray());
            $msg = "Kode anggaran {$node->kode} berhasil ditambahkan.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            if ($this->form_parent_id && ! in_array($this->form_parent_id, $this->expandedNodes)) {
                $this->expandedNodes[] = $this->form_parent_id;
            }
        }
        $this->showModal = false;
    }

    public function toggleBatchEditMode(): void
    {
        $this->batchEditMode = ! $this->batchEditMode;
        $this->batchEdits = [];
        $this->batchRevisionNote = '';
    }

    public function saveBatchEdits(): void
    {
        $year = TahunAnggaran::findOrFail($this->tahun_anggaran_id);

        if ($year->status === 'ditutup') {
            $this->addError('batchRevisionNote', 'Tahun anggaran telah ditutup. Perubahan tidak diizinkan.');

            return;
        }

        if ($year->status === 'aktif' && blank($this->batchRevisionNote)) {
            $this->addError('batchRevisionNote', 'Catatan revisi wajib diisi untuk tahun anggaran aktif.');

            return;
        }

        DB::transaction(function () use ($year) {
            $calculator = new BudgetCalculator;
            $tree = $calculator->calculateTree($this->tahun_anggaran_id);

            foreach ($this->batchEdits as $nodeId => $newPlafon) {
                $newPlafon = (int) preg_replace('/\D/', '', (string) $newPlafon);
                $node = KodeAnggaran::find($nodeId);
                if (! $node) {
                    continue;
                }

                $calc = $tree->get($nodeId);
                $floor = ($calc['realisasi'] ?? 0) + ($calc['dana_terikat'] ?? 0);
                if ($newPlafon < $floor) {
                    continue;
                }

                $alokasi = AlokasiAnggaran::firstOrNew([
                    'kode_anggaran_id' => $nodeId,
                    'tahun_anggaran_id' => $this->tahun_anggaran_id,
                ]);
                $before = $alokasi->exists ? $alokasi->toArray() : null;

                if ($year->status === 'draft') {
                    $alokasi->plafon_awal = $newPlafon;
                } else {
                    $alokasi->plafon_revisi = $newPlafon;
                    $alokasi->keterangan_revisi = $this->batchRevisionNote;
                }
                $alokasi->save();

                (new AuditLogger)->log(
                    'alokasi.batch_updated',
                    $alokasi,
                    $before,
                    $alokasi->fresh()->toArray()
                );
            }
        });

        $count = count($this->batchEdits);
        $this->batchEditMode = false;
        $this->batchEdits = [];
        $this->batchRevisionNote = '';

        $msg = "{$count} pos anggaran berhasil diperbarui.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $ta = TahunAnggaran::find($this->tahun_anggaran_id);
        $tahun = $ta ? $ta->tahun : date('Y');
        $prevTa = TahunAnggaran::where('tahun', ($ta?->tahun ?? date('Y')) - 1)->first();

        return Excel::download(
            new TemplateAlokasiAnggaranExport($this->tahun_anggaran_id, $prevTa?->id),
            "Template_Alokasi_Anggaran_{$tahun}.xlsx"
        );
    }

    public function openImportModal(): void
    {
        $this->resetValidation();
        $this->importFile = null;
        $this->importRevisionNote = '';
        $this->showImportModal = true;
    }

    public function uploadImport(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $rows = Excel::toCollection(new AlokasiAnggaranImport, $this->importFile)->first();
        if (! $rows || $rows->isEmpty()) {
            $this->addError('importFile', 'File Excel kosong atau tidak terbaca.');

            return;
        }

        $calculator = new BudgetCalculator;
        $currentTree = $calculator->calculateTree($this->tahun_anggaran_id);

        $diffs = [];
        foreach ($rows as $row) {
            $kode = trim((string) ($row['kode'] ?? ''));
            $plafonBaruRaw = $row['plafon_baru'] ?? null;

            if ($kode === '' || $plafonBaruRaw === null || $plafonBaruRaw === '') {
                continue;
            }

            $cleanPlafon = (int) preg_replace('/\D/', '', (string) $plafonBaruRaw);
            $node = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
                ->where('kode', $kode)
                ->first();

            if (! $node) {
                continue;
            }

            $currentCalc = $currentTree->get($node->id);
            $oldPlafon = (int) ($currentCalc['plafon'] ?? 0);

            if ($cleanPlafon === $oldPlafon) {
                $direction = 'same';
                $pct = 0.0;
            } elseif ($cleanPlafon > $oldPlafon) {
                $direction = 'up';
                $pct = $oldPlafon > 0 ? round((($cleanPlafon - $oldPlafon) / $oldPlafon) * 100, 1) : 100.0;
            } else {
                $direction = 'down';
                $pct = $oldPlafon > 0 ? round((($oldPlafon - $cleanPlafon) / $oldPlafon) * 100, 1) : 0.0;
            }

            $diffs[] = [
                'node_id' => $node->id,
                'kode' => $node->kode,
                'nama' => $node->nama,
                'old_plafon' => $oldPlafon,
                'new_plafon' => $cleanPlafon,
                'change_pct' => $pct,
                'direction' => $direction,
            ];
        }

        if (empty($diffs)) {
            $this->addError('importFile', 'Tidak ada data plafon baru yang valid untuk diperbarui pada file ini.');

            return;
        }

        $this->importDiff = $diffs;
        $this->showImportModal = false;
        $this->showDiffModal = true;
    }

    public function applyImport(): void
    {
        $year = TahunAnggaran::findOrFail($this->tahun_anggaran_id);

        if ($year->status === 'ditutup') {
            $this->addError('importRevisionNote', 'Tahun anggaran telah ditutup. Perubahan tidak diizinkan.');

            return;
        }

        if ($year->status === 'aktif' && blank($this->importRevisionNote)) {
            $this->addError('importRevisionNote', 'Catatan revisi wajib diisi untuk tahun anggaran aktif.');

            return;
        }

        DB::transaction(function () use ($year) {
            $calculator = new BudgetCalculator;
            $tree = $calculator->calculateTree($this->tahun_anggaran_id);

            foreach ($this->importDiff as $diff) {
                $nodeId = $diff['node_id'];
                $newPlafon = (int) $diff['new_plafon'];

                $calc = $tree->get($nodeId);
                $floor = ($calc['realisasi'] ?? 0) + ($calc['dana_terikat'] ?? 0);
                if ($newPlafon < $floor) {
                    continue;
                }

                $alokasi = AlokasiAnggaran::firstOrNew([
                    'kode_anggaran_id' => $nodeId,
                    'tahun_anggaran_id' => $this->tahun_anggaran_id,
                ]);
                $before = $alokasi->exists ? $alokasi->toArray() : null;

                if ($year->status === 'draft') {
                    $alokasi->plafon_awal = $newPlafon;
                } else {
                    $alokasi->plafon_revisi = $newPlafon;
                    $alokasi->keterangan_revisi = $this->importRevisionNote;
                }
                $alokasi->save();

                (new AuditLogger)->log(
                    'alokasi.imported',
                    $alokasi,
                    $before,
                    $alokasi->fresh()->toArray()
                );
            }
        });

        $count = count($this->importDiff);
        $this->showDiffModal = false;
        $this->importDiff = [];
        $this->importFile = null;
        $this->importRevisionNote = '';

        $msg = "Import berhasil: {$count} pos anggaran telah diperbarui.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function sahkanBudget(int $id): void
    {
        $node = KodeAnggaran::findOrFail($id);
        $before = $node->toArray();
        $node->update(['budget_status' => 'disahkan']);

        (new AuditLogger)->log('kode_anggaran.disahkan', $node, $before, $node->fresh()->toArray());

        $msg = "Budget kode anggaran {$node->kode} berhasil disahkan.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function toggleActive(int $id): void
    {
        $node = KodeAnggaran::findOrFail($id);
        $before = $node->toArray();
        $node->update(['is_active' => ! $node->is_active]);

        (new AuditLogger)->log('kode_anggaran.toggle_active', $node, $before, $node->fresh()->toArray());

        $statusStr = $node->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $msg = "Kode anggaran {$node->kode} berhasil {$statusStr}.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
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

        $prevYear = $selectedYear ? TahunAnggaran::where('tahun', $selectedYear->tahun - 1)->first() : null;
        $prevTreeCalculation = collect();
        if ($this->batchEditMode && $prevYear) {
            $prevTreeCalculation = $calculator->calculateTree($prevYear->id);
        }
        // Prefix list for filter dropdown (Distinct parent / sub-parent codes like 1, 2, 2.1, 2.4, 2.4.1, 2.4.2)
        $rawPrefix = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->where('depth', '<=', 3)
            ->whereHas('children')
            ->get(['id', 'kode', 'nama', 'depth', 'parent_id']);
        $prefixOptions = KodeAnggaran::sortHierarchically($rawPrefix);

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
                    'gt_100m' => $q->whereHas('alokasiAnggaran', fn ($a) => $a->where('tahun_anggaran_id', $this->tahun_anggaran_id)->where(DB::raw('COALESCE(plafon_revisi, plafon_awal)'), '>', 100000000)),
                    '10m_100m' => $q->whereHas('alokasiAnggaran', fn ($a) => $a->where('tahun_anggaran_id', $this->tahun_anggaran_id)->whereBetween(DB::raw('COALESCE(plafon_revisi, plafon_awal)'), [10000000, 100000000])),
                    'lt_10m' => $q->whereHas('alokasiAnggaran', fn ($a) => $a->where('tahun_anggaran_id', $this->tahun_anggaran_id)->where(DB::raw('COALESCE(plafon_revisi, plafon_awal)'), '<', 10000000)),
                    'has_plafon' => $q->whereHas('alokasiAnggaran', fn ($a) => $a->where('tahun_anggaran_id', $this->tahun_anggaran_id)),
                    'null_plafon' => $q->whereDoesntHave('alokasiAnggaran', fn ($a) => $a->where('tahun_anggaran_id', $this->tahun_anggaran_id)),
                    default => null,
                };
            })
            ->orderBy('kode', 'asc');

        $rawNodes = $query->get();
        $allNodes = KodeAnggaran::sortHierarchically($rawNodes);

        // Build parent-child tree
        $nodesByParent = $allNodes->groupBy('parent_id');
        $rootNodes = $nodesByParent->get(null, collect());

        $parentsMap = $allNodes->pluck('parent_id', 'id')->toArray();
        $allParentIds = $allNodes->whereIn('id', $nodesByParent->keys())->pluck('id')->values()->toArray();

        // Potential parent choices for create modal
        $rawParentChoices = KodeAnggaran::where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->where('depth', '<', (int) Setting::get('kode.max_depth', 4))
            ->when($this->editingId, function ($q) {
                $q->where('id', '!=', $this->editingId);
            })
            ->get();
        $parentChoices = KodeAnggaran::sortHierarchically($rawParentChoices);
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
            'prevTreeCalculation' => $prevTreeCalculation,
            'prevYear' => $prevYear,
            'allNodes' => $allNodes,
            'nodesByParent' => $nodesByParent,
            'calculatedData' => $calculatedData,
            'parentChoices' => $parentChoices,
            'prefixOptions' => $prefixOptions,
            'detailNode' => $detailNode,
            'detailMetrics' => $detailMetrics,
            'parentsMap' => $parentsMap,
            'allParentIds' => $allParentIds,
        ]);
    }
}
