<?php

namespace App\Livewire\Master;

use App\Models\AlokasiAnggaran;
use App\Models\KodeAnggaran;
use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tahun Anggaran: SIGMA LAZ Al-Azhar')]
class TahunAnggaranIndex extends Component
{
    public bool $showModal = false;

    public string $form_tahun = '';

    public string $form_status = 'draft';

    // Rollover Wizard Properties
    public bool $showRolloverModal = false;

    public ?int $rolloverSourceId = null;

    public ?int $rolloverTargetId = null;

    public string $rolloverScheme = 'zero'; // 'zero' | 'full' | 'percentage'

    public string $rolloverPercentage = '0';

    public int $rolloverStep = 1; // 1 = pilih skema, 2 = preview & edit

    public array $rolloverPreview = []; // [{node_id, kode, nama, depth, source_plafon, target_plafon}, ...]

    public string $previewSearch = '';

    public string $previewFilter = 'all'; // 'all' | 'changed' | 'unchanged' | 'zero'

    public static function parseRupiah(mixed $val): int
    {
        if (is_int($val)) {
            return max(0, $val);
        }
        if (is_float($val)) {
            return max(0, (int) round($val));
        }
        if (is_string($val)) {
            $clean = preg_replace('/[^\d]/', '', $val);

            return (int) ($clean ?: 0);
        }

        return 0;
    }

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

    public function openRolloverModal(?int $targetYearId = null): void
    {
        $this->resetValidation();
        $this->rolloverTargetId = $targetYearId;
        $this->rolloverSourceId = TahunAnggaran::where('status', 'aktif')->value('id')
            ?? TahunAnggaran::where('id', '!=', $targetYearId)->orderBy('tahun', 'desc')->value('id');
        $this->rolloverScheme = 'zero';
        $this->rolloverPercentage = '0';
        $this->rolloverStep = 1;
        $this->rolloverPreview = [];
        $this->previewSearch = '';
        $this->previewFilter = 'all';
        $this->showRolloverModal = true;
    }

    public function closeRolloverModal(): void
    {
        $this->showRolloverModal = false;
        $this->rolloverStep = 1;
        $this->rolloverPreview = [];
        $this->previewSearch = '';
        $this->previewFilter = 'all';
    }

    public function previousRolloverStep(): void
    {
        $this->rolloverStep = 1;
        $this->previewSearch = '';
        $this->previewFilter = 'all';
    }

    public function getTotalRolloverSourcePlafonProperty(): int
    {
        return (int) collect($this->rolloverPreview)->sum(fn ($item) => (int) ($item['source_plafon'] ?? 0));
    }

    public function getTotalRolloverTargetPlafonProperty(): int
    {
        return (int) collect($this->rolloverPreview)->sum(fn ($item) => self::parseRupiah($item['target_plafon'] ?? 0));
    }

    public function getTotalRolloverSelisihProperty(): int
    {
        return $this->totalRolloverTargetPlafon - $this->totalRolloverSourcePlafon;
    }

    public function getFilteredRolloverPreviewProperty(): array
    {
        $search = trim(strtolower($this->previewSearch));
        $filter = $this->previewFilter;

        $results = [];
        foreach ($this->rolloverPreview as $originalIndex => $item) {
            $source = (int) ($item['source_plafon'] ?? 0);
            $target = self::parseRupiah($item['target_plafon'] ?? 0);

            if ($filter === 'changed' && $target === $source) {
                continue;
            }
            if ($filter === 'unchanged' && $target !== $source) {
                continue;
            }
            if ($filter === 'zero' && $target !== 0) {
                continue;
            }

            if ($search !== '') {
                $matchKode = str_contains(strtolower($item['kode'] ?? ''), $search);
                $matchNama = str_contains(strtolower($item['nama'] ?? ''), $search);
                if (! $matchKode && ! $matchNama) {
                    continue;
                }
            }

            $results[$originalIndex] = $item;
        }

        return $results;
    }

    public function getPreviewMetricsProperty(): array
    {
        $total = count($this->rolloverPreview);
        $changed = 0;
        $unchanged = 0;
        $zero = 0;

        foreach ($this->rolloverPreview as $item) {
            $source = (int) ($item['source_plafon'] ?? 0);
            $target = self::parseRupiah($item['target_plafon'] ?? 0);

            if ($target === 0) {
                $zero++;
            }
            if ($target !== $source) {
                $changed++;
            } else {
                $unchanged++;
            }
        }

        return [
            'total' => $total,
            'changed' => $changed,
            'unchanged' => $unchanged,
            'zero' => $zero,
            'filtered' => count($this->filteredRolloverPreview),
        ];
    }

    public function adjustItemPlafon(int $index, int $delta): void
    {
        if (isset($this->rolloverPreview[$index])) {
            $current = self::parseRupiah($this->rolloverPreview[$index]['target_plafon'] ?? 0);
            $this->rolloverPreview[$index]['target_plafon'] = max(0, $current + $delta);
        }
    }

    public function resetItemToSource(int $index): void
    {
        if (isset($this->rolloverPreview[$index])) {
            $this->rolloverPreview[$index]['target_plafon'] = (int) $this->rolloverPreview[$index]['source_plafon'];
        }
    }

    public function setItemToZero(int $index): void
    {
        if (isset($this->rolloverPreview[$index])) {
            $this->rolloverPreview[$index]['target_plafon'] = 0;
        }
    }

    public function resetAllPreviewToSource(): void
    {
        foreach ($this->rolloverPreview as $idx => $item) {
            $this->rolloverPreview[$idx]['target_plafon'] = (int) $item['source_plafon'];
        }
    }

    public function setAllPreviewToZero(): void
    {
        foreach ($this->rolloverPreview as $idx => $item) {
            $this->rolloverPreview[$idx]['target_plafon'] = 0;
        }
    }

    public function applyBulkPercentage(float $pct): void
    {
        foreach ($this->filteredRolloverPreview as $idx => $item) {
            $source = (int) ($item['source_plafon'] ?? 0);
            $this->rolloverPreview[$idx]['target_plafon'] = max(0, (int) round($source * (1 + ($pct / 100))));
        }
    }

    public function updatedRolloverPreview($value, $key): void
    {
        if (str_ends_with((string) $key, '.target_plafon')) {
            $parts = explode('.', (string) $key);
            $idx = (int) $parts[0];
            if (isset($this->rolloverPreview[$idx])) {
                $this->rolloverPreview[$idx]['target_plafon'] = self::parseRupiah($value);
            }
        }
    }

    public function generateRolloverPreview(): void
    {
        $this->validate([
            'rolloverSourceId' => ['required', 'exists:tahun_anggaran,id'],
            'rolloverTargetId' => ['required', 'exists:tahun_anggaran,id', 'different:rolloverSourceId'],
            'rolloverScheme' => ['required', 'in:zero,full,percentage'],
            'rolloverPercentage' => ['required_if:rolloverScheme,percentage', 'numeric'],
        ]);

        $source = TahunAnggaran::findOrFail($this->rolloverSourceId);
        $target = TahunAnggaran::findOrFail($this->rolloverTargetId);

        if ($target->status !== 'draft') {
            $this->addError('rolloverTargetId', 'Rollover hanya dapat dilakukan ke tahun anggaran berstatus Draft.');

            return;
        }

        $sourceNodes = KodeAnggaran::where('tahun_anggaran_id', $source->id)
            ->with('children')
            ->get();
        $sortedSource = KodeAnggaran::sortHierarchically($sourceNodes);
        $sourceAlokasi = AlokasiAnggaran::where('tahun_anggaran_id', $source->id)
            ->get()
            ->keyBy('kode_anggaran_id');

        $pct = (float) $this->rolloverPercentage;
        $this->rolloverPreview = [];

        foreach ($sortedSource as $sNode) {
            if (! $sNode->isLeaf()) {
                continue;
            }

            $alokasi = $sourceAlokasi->get($sNode->id);
            $sourcePlafon = $alokasi ? (int) $alokasi->plafon_efektif : 0;

            $targetPlafon = match ($this->rolloverScheme) {
                'zero' => 0,
                'full' => $sourcePlafon,
                'percentage' => (int) round($sourcePlafon * (1 + ($pct / 100))),
            };

            $this->rolloverPreview[] = [
                'node_id' => $sNode->id,
                'kode' => $sNode->kode,
                'nama' => $sNode->nama,
                'depth' => $sNode->depth,
                'source_plafon' => $sourcePlafon,
                'target_plafon' => max(0, $targetPlafon),
            ];
        }

        $this->rolloverStep = 2;
    }

    public function executeRollover(): void
    {
        $this->validate([
            'rolloverSourceId' => ['required', 'exists:tahun_anggaran,id'],
            'rolloverTargetId' => ['required', 'exists:tahun_anggaran,id', 'different:rolloverSourceId'],
            'rolloverScheme' => ['required', 'in:zero,full,percentage'],
            'rolloverPercentage' => ['required_if:rolloverScheme,percentage', 'numeric'],
            'rolloverPreview.*.target_plafon' => ['nullable'],
        ]);

        $source = TahunAnggaran::findOrFail($this->rolloverSourceId);
        $target = TahunAnggaran::findOrFail($this->rolloverTargetId);

        if ($target->status !== 'draft') {
            $this->addError('rolloverTargetId', 'Rollover hanya dapat dilakukan ke tahun anggaran berstatus Draft.');

            return;
        }

        if (empty($this->rolloverPreview)) {
            $this->generateRolloverPreview();
        }

        DB::transaction(function () use ($source, $target) {
            $sourceNodes = KodeAnggaran::where('tahun_anggaran_id', $source->id)->get();
            $targetNodesByKode = KodeAnggaran::where('tahun_anggaran_id', $target->id)->get()->keyBy('kode');
            $targetNodesByPrevId = KodeAnggaran::where('tahun_anggaran_id', $target->id)->whereNotNull('prev_year_id')->get()->keyBy('prev_year_id');

            if ($targetNodesByKode->isEmpty()) {
                $sortedSource = KodeAnggaran::sortHierarchically($sourceNodes);
                $idMap = [];

                foreach ($sortedSource as $sNode) {
                    $newParentId = $sNode->parent_id ? ($idMap[$sNode->parent_id] ?? null) : null;
                    $newNode = KodeAnggaran::create([
                        'tahun_anggaran_id' => $target->id,
                        'parent_id' => $newParentId,
                        'kode' => $sNode->kode,
                        'nama' => $sNode->nama,
                        'deskripsi' => $sNode->deskripsi,
                        'depth' => $sNode->depth,
                        'budget_status' => 'draft',
                        'prev_year_id' => $sNode->id,
                        'is_active' => $sNode->is_active,
                    ]);
                    $idMap[$sNode->id] = $newNode->id;
                }
                $targetNodesByKode = KodeAnggaran::where('tahun_anggaran_id', $target->id)->get()->keyBy('kode');
                $targetNodesByPrevId = KodeAnggaran::where('tahun_anggaran_id', $target->id)->whereNotNull('prev_year_id')->get()->keyBy('prev_year_id');
            }

            $previewByNodeId = collect($this->rolloverPreview)->keyBy('node_id');
            $createdCount = 0;

            foreach ($sourceNodes as $sNode) {
                $preview = $previewByNodeId->get($sNode->id);
                if (! $preview) {
                    continue;
                }

                $tNode = $targetNodesByPrevId->get($sNode->id) ?? $targetNodesByKode->get($sNode->kode);
                if (! $tNode) {
                    continue;
                }
                $newPlafonAwal = self::parseRupiah($preview['target_plafon'] ?? 0);

                AlokasiAnggaran::updateOrCreate(
                    [
                        'kode_anggaran_id' => $tNode->id,
                        'tahun_anggaran_id' => $target->id,
                    ],
                    [
                        'plafon_awal' => $newPlafonAwal,
                        'plafon_revisi' => null,
                        'keterangan_revisi' => null,
                    ]
                );
                $createdCount++;
            }

            (new AuditLogger)->log(
                'rollover.executed',
                $target,
                null,
                [
                    'source_tahun_id' => $source->id,
                    'source_tahun' => $source->tahun,
                    'target_tahun_id' => $target->id,
                    'target_tahun' => $target->tahun,
                    'scheme' => $this->rolloverScheme,
                    'percentage' => $this->rolloverPercentage,
                    'nodes_copied' => $createdCount,
                ]
            );
        });

        $this->showRolloverModal = false;
        $this->rolloverStep = 1;
        $this->rolloverPreview = [];
        session()->flash('success', "Rollover anggaran dari TA {$source->tahun} ke TA {$target->tahun} berhasil diproses.");
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Rollover anggaran berhasil diproses.']);
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
