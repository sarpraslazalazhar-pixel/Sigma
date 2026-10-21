<?php

namespace App\Livewire\Ump;

use App\Models\Lampiran;
use App\Models\Setting;
use App\Models\Ump;
use App\Services\UmpWorkflow;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Form UMP: SIGMA LAZ Al-Azhar')]
class UmpDetail extends Component
{
    use WithFileUploads;

    public int $umpId;

    /** Baris penggunaan dana (Bagian B): id, tgl, rincian, no_bukti, jumlah. */
    public array $items = [];

    public string $catatanKekurangan = '';

    public $buktiPendukung = null;

    // Panel persetujuan (FR-UMP-4)
    public bool $showApprovalModal = false;

    public string $approvalAction = ''; // 'setuju' | 'perbaikan' | 'batal'

    public string $approvalNotes = '';

    public $scanFinalFile = null;

    public function mount(int $id): void
    {
        $this->umpId = $id;
        $this->loadItems();
    }

    public function getUmpProperty(): Ump
    {
        return Ump::with([
            'pencairan.permohonan.unit',
            'pj',
            'items',
            'approvals',
            'lampiran',
        ])->findOrFail($this->umpId);
    }

    public function getIsEditableProperty(): bool
    {
        return in_array($this->ump->status, ['draft', 'perlu_perbaikan'], true);
    }

    public function getStatusBadge(string $status): array
    {
        return match ($status) {
            'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
            'perlu_perbaikan' => ['label' => 'Perlu Perbaikan', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'],
            'ttd_pj' => ['label' => 'Menunggu TTD Kadiv', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
            'ttd_manager_keuangan' => ['label' => 'Menunggu TTD Mgr Keuangan', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'ttd_kepala_keuangan' => ['label' => 'Menunggu TTD Kepala Keuangan', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'selesai' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'bg-red-50 text-red-700 border-red-200'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'bg-slate-100 text-slate-600 border-slate-200'],
        };
    }

    public function getTotalPenggunaanProperty(): int
    {
        return (int) collect($this->items)->sum('jumlah');
    }

    /**
     * Hak akses approve tahap berjalan (FR-UMP-4): Kadiv -> Manager Keuangan -> Kepala Keuangan.
     */
    public function canApprove(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $status = $this->ump->status;

        if ($status === 'ttd_pj') {
            $urutan = $user->leveling?->urutan ?? 0;
            $docDivisiId = $this->ump->pencairan?->permohonan?->unit?->getDivisiId();
            $userDivisiId = $user->unit?->getDivisiId();
            $sameDivisi = $docDivisiId !== null && $userDivisiId !== null && $docDivisiId === $userDivisiId;

            return ($sameDivisi && $urutan >= 2)
                || $user->role === 'keuangan'
                || $user->role === 'admin';
        }

        if (in_array($status, ['ttd_manager_keuangan', 'ttd_kepala_keuangan'], true)) {
            return $user->role === 'keuangan'
                || (bool) $user->is_petugas_input
                || $user->role === 'admin';
        }

        return false;
    }

    /** Progres stepper: 0=draft, 1=PJ, 2=Kadiv, 3=Mgr Keuangan, 4=Kepala Keuangan, 5=Selesai. */
    public function getStepProgressProperty(): int
    {
        return match ($this->ump->status) {
            'draft', 'perlu_perbaikan', 'dibatalkan' => 0,
            'ttd_pj' => 1,
            'ttd_manager_keuangan' => 2,
            'ttd_kepala_keuangan' => 3,
            'selesai' => 5,
            default => 0,
        };
    }

    public function openApproval(string $action): void
    {
        $this->approvalAction = $action;
        $this->approvalNotes = '';
        $this->scanFinalFile = null;
        $this->showApprovalModal = true;
    }

    public function closeApprovalModal(): void
    {
        $this->showApprovalModal = false;
        $this->approvalAction = '';
    }

    public function submitApproval(): void
    {
        if (! $this->canApprove()) {
            session()->flash('error', 'Anda tidak berwenang memproses persetujuan UMP pada tahap ini.');

            return;
        }

        $action = $this->approvalAction;

        if (in_array($action, ['perbaikan', 'batal'], true)) {
            $this->validate([
                'approvalNotes' => ['required', 'string', 'min:5'],
            ], [
                'approvalNotes.required' => 'Catatan alasan wajib diisi.',
                'approvalNotes.min' => 'Catatan alasan minimal 5 karakter.',
            ]);
        }

        $isFinalStage = $this->ump->status === 'ttd_kepala_keuangan';
        $hasScan = $this->ump->lampiran()->where('jenis', 'scan_final')->exists();

        if ($action === 'setuju') {
            $this->validate([
                'scanFinalFile' => ($isFinalStage && ! $hasScan)
                    ? ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240']
                    : ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            ], [
                'scanFinalFile.required' => 'Scan fisik UMP bertanda tangan wajib diunggah sebelum status Selesai.',
                'scanFinalFile.mimes' => 'Berkas scan harus berformat PDF, JPG, atau PNG.',
                'scanFinalFile.max' => 'Ukuran berkas scan maksimal 10 MB.',
            ]);
        }

        $workflow = new UmpWorkflow;
        $user = Auth::user();

        try {
            $msg = '';
            if ($action === 'setuju') {
                $workflow->approve($this->ump, $user, [
                    'catatan' => $this->approvalNotes ?: null,
                    'scan_final' => $this->scanFinalFile,
                ]);
                $msg = 'Persetujuan UMP tercatat. Tahap berikutnya diproses sistem.';
            } elseif ($action === 'perbaikan') {
                $workflow->requestRevision($this->ump, $user, $this->approvalNotes);
                $msg = 'UMP dikembalikan untuk perbaikan.';
            } elseif ($action === 'batal') {
                $workflow->cancel($this->ump, $user, $this->approvalNotes);
                $msg = 'UMP berhasil dibatalkan.';
            }

            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
            $this->loadItems();
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->reset('scanFinalFile');
        $this->closeApprovalModal();
    }

    public function getSelisihProperty(): int
    {
        return (int) $this->ump->jumlah_total - $this->totalPenggunaan;
    }

    public function getDefisitProperty(): bool
    {
        return $this->selisih < 0;
    }

    public function getPembulatanProperty(): int
    {
        $kelipatan = (int) (Setting::get('pembulatan.kelipatan', 1000) ?: 1000);

        return (int) (round($this->selisih / $kelipatan) * $kelipatan);
    }

    public function tambahBaris(): void
    {
        if (! $this->isEditable) {
            return;
        }

        $this->items[] = [
            'id' => null,
            'tgl' => '',
            'rincian' => '',
            'no_bukti' => '',
            'jumlah' => 0,
        ];
    }

    public function hapusBaris(int $index): void
    {
        if (! $this->isEditable) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function simpanDraft(): void
    {
        if (! $this->isEditable) {
            session()->flash('error', 'UMP tidak dalam status Draft / Perlu Perbaikan.');

            return;
        }

        try {
            (new UmpWorkflow)->saveItems($this->ump, $this->cleanedItems());
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->loadItems();
        $msg = 'Draft UMP tersimpan. Saldo dan pembulatan diperbarui.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function ajukanUMP(): void
    {
        if (! $this->isEditable) {
            session()->flash('error', 'UMP tidak dalam status Draft / Perlu Perbaikan.');

            return;
        }

        $items = $this->cleanedItems();

        if ($items === []) {
            session()->flash('error', 'Minimal satu baris penggunaan dana dengan jumlah lebih dari 0 wajib diisi.');

            return;
        }

        if ($this->defisit) {
            $this->validate([
                'catatanKekurangan' => ['required', 'string', 'min:5'],
            ], [
                'catatanKekurangan.required' => 'Catatan alasan kekurangan dana wajib diisi (penggunaan melebihi dana diterima).',
                'catatanKekurangan.min' => 'Catatan alasan kekurangan dana minimal 5 karakter.',
            ]);
        }

        $user = Auth::user();

        try {
            $ump = (new UmpWorkflow)->saveItems($this->ump, $items, []);
            (new UmpWorkflow)->submitByPj($ump, $user, $this->catatanKekurangan);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->loadItems();
        $msg = 'UMP berhasil diajukan dan menunggu tanda tangan Penanggung Jawab.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function simpanBuktiPendukung(): void
    {
        if (! $this->isEditable) {
            return;
        }

        $this->validate([
            'buktiPendukung' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'buktiPendukung.required' => 'Berkas bukti pendukung wajib dipilih.',
            'buktiPendukung.mimes' => 'Berkas harus berformat JPG, PNG, atau PDF.',
            'buktiPendukung.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $file = $this->buktiPendukung;
        $path = $file->store('lampiran/ump', 'local');

        Lampiran::create([
            'attachable_type' => Ump::class,
            'attachable_id' => $this->umpId,
            'jenis' => 'bukti',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?? 0,
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'version' => 1,
            'uploaded_by' => Auth::id(),
        ]);

        $this->reset('buktiPendukung');
        session()->flash('success', 'Bukti pendukung UMP berhasil diunggah.');
    }

    /** Buang baris kosong, lalu validasi baris tersisa (FR-UMP-2). */
    protected function cleanedItems(): array
    {
        $this->items = array_values(array_filter($this->items, function ($row) {
            return trim((string) ($row['rincian'] ?? '')) !== ''
                || (int) ($row['jumlah'] ?? 0) > 0
                || ! empty($row['tgl']);
        }));

        if ($this->items === []) {
            return [];
        }

        $this->validate([
            'items' => ['array'],
            'items.*.tgl' => ['required', 'date'],
            'items.*.rincian' => ['required', 'string', 'min:3'],
            'items.*.no_bukti' => ['nullable', 'string', 'max:100'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ], [
            'items.*.tgl.required' => 'Tanggal pengeluaran wajib diisi.',
            'items.*.tgl.date' => 'Tanggal pengeluaran tidak valid.',
            'items.*.rincian.required' => 'Rincian pengeluaran wajib diisi.',
            'items.*.rincian.min' => 'Rincian pengeluaran minimal 3 karakter.',
            'items.*.jumlah.required' => 'Jumlah pengeluaran wajib diisi.',
            'items.*.jumlah.min' => 'Jumlah pengeluaran harus lebih dari 0.',
        ]);
        // FR-UMP-7: item dates must fall within the budget year
        $tahun = $this->ump->pencairan?->permohonan?->kodeAnggaran?->tahunAnggaran?->tahun;
        if ($tahun) {
            foreach ($this->items as $idx => $row) {
                if (! empty($row['tgl']) && Carbon::parse($row['tgl'])->year !== (int) $tahun) {
                    $this->addError("items.{$idx}.tgl", "Tanggal pengeluaran harus berada dalam tahun anggaran {$tahun}.");
                }
            }
            if ($this->getErrorBag()->isNotEmpty()) {
                // Halt: addError already populated the error bag; Livewire renders errors to view.
                // Throwing ValidationException (no rules needed) stops callers cleanly.
                throw new ValidationException(
                    Validator::make([], [])->after(function ($v) {
                        foreach ($this->getErrorBag()->toArray() as $key => $msgs) {
                            foreach ($msgs as $msg) {
                                $v->errors()->add($key, $msg);
                            }
                        }
                    })
                );
            }
        }

        return $this->items;
    }

    protected function loadItems(): void
    {
        $this->items = $this->ump->items->map(fn ($item) => [
            'id' => $item->id,
            'tgl' => $item->tgl->toDateString(),
            'rincian' => $item->rincian,
            'no_bukti' => $item->no_bukti ?? '',
            'jumlah' => (int) $item->jumlah,
        ])->values()->all();
    }

    public function render(): View
    {
        return view('livewire.ump.ump-detail', [
            'ump' => $this->ump,
            'isEditable' => $this->isEditable,
            'totalPenggunaan' => $this->totalPenggunaan,
            'selisih' => $this->selisih,
            'defisit' => $this->defisit,
            'pembulatan' => $this->pembulatan,
            'canApprove' => $this->canApprove(),
            'stepProgress' => $this->stepProgress,
        ]);
    }
}
