<?php

namespace App\Livewire\Ump;

use App\Models\Lampiran;
use App\Models\Setting;
use App\Models\Ump;
use App\Models\UmpItem;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\UmpWorkflow;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Form Laporan: SIGMA LAZ Al-Azhar')]
class UmpDetail extends Component
{
    use WithFileUploads;

    public int $umpId;

    /**
     * Baris penggunaan dana (Bagian B) dikelompokkan per penerima.
     * Key: id record ump_penerima (string) atau 'pj' untuk baris milik PJ/tanpa penerima.
     *
     * @var array<string, array<int, array{id: ?int, tgl: string, rincian: string, no_bukti: string, jumlah: int, is_editing: bool}>>
     */
    public array $itemsByPenerima = [];

    /**
     * Berkas bukti sementara per baris (belum/sedang diunggah sebelum atau saat simpan baris).
     *
     * @var array<string, array<int, mixed>>
     */
    public array $pendingRowFiles = [];

    /** Daftar id user yang ditunjuk sebagai penerima (untuk form sinkronisasi). */
    public array $selectedPenerimaIds = [];

    public string $catatanKekurangan = '';

    /** Berkas bukti transaksi per baris item. */
    public $buktiItem = null;

    /** id UmpItem yang sedang diunggah buktinya. */
    public ?int $buktiItemTarget = null;

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
            'pencairan.buktiTransfer',
            'pj',
            'items.lampiran',
            'penerima.user',
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
            'ttd_pj' => ['label' => 'Menunggu Diperiksa', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
            'ttd_manager_keuangan' => ['label' => 'Menunggu TTD Mgr Keuangan', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'ttd_kepala_keuangan' => ['label' => 'Menunggu TTD Kepala Keuangan', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'selesai' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'bg-red-50 text-red-700 border-red-200'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'bg-slate-100 text-slate-600 border-slate-200'],
        };
    }

    public function getTotalPenggunaanProperty(): int
    {
        return (int) collect($this->itemsByPenerima)->flatten(1)->sum('jumlah');
    }

    /**
     * Penerima yang belum mengisi satu baris pun (soft warning sebelum PJ submit).
     *
     * @return array<int, string>
     */
    public function getIncompletePenerimaNamesProperty(): array
    {
        return $this->ump->penerima
            ->filter(fn ($penerima) => ! isset($this->itemsByPenerima[(string) $penerima->id])
                || count($this->itemsByPenerima[(string) $penerima->id]) === 0)
            ->map(fn ($penerima) => $penerima->user?->name ?? 'Penerima #'.$penerima->id)
            ->values()
            ->all();
    }

    /**
     * Key section milik pengguna saat ini: id ump_penerima atau 'pj'.
     */
    public function getActivePenerimaKeyProperty(): string
    {
        $user = Auth::user();
        if (! $user) {
            return 'pj';
        }

        $penerima = $this->ump->penerima->firstWhere('user_id', $user->id);

        return $penerima ? (string) $penerima->id : 'pj';
    }

    /**
     * Apakah pengguna berwenang mengubah baris pada section tertentu.
     * PJ bertanggung jawab atas seluruh UMP; admin/TU/keuangan bisa mengelola semua; penerima hanya section miliknya.
     */
    public function canEditSection(string $key): bool
    {
        if (! $this->isEditable) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin' || (bool) $user->is_petugas_input || $user->isKeuangan()) {
            return true;
        }

        if ($this->ump->pj_id === $user->id) {
            return true;
        }

        if ($key === 'pj') {
            return false;
        }

        $penerima = $this->ump->penerima->firstWhere('id', (int) $key);

        return $penerima !== null && $penerima->user_id === $user->id;
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
            if ($user->role === 'admin' || (bool) $user->is_petugas_input) {
                return true;
            }

            $pj = $this->ump->pj ?: $this->ump->pencairan?->permohonan?->user;
            if ($pj && $user->id === $pj->id) {
                return false;
            }

            $pjDivisiId = $pj?->unit?->getDivisiId() ?? $this->ump->pencairan?->permohonan?->unit?->getDivisiId();
            $pjUnitId = $pj?->unit_id ?? $this->ump->pencairan?->permohonan?->unit_id;

            $userDivisiId = $user->unit?->getDivisiId();
            $sameDivisi = $pjDivisiId !== null && $userDivisiId !== null && $pjDivisiId === $userDivisiId;
            $sameUnit = $pjUnitId !== null && $user->unit_id !== null && $pjUnitId === $user->unit_id;

            $urutan = $user->leveling?->urutan ?? 0;

            return ($sameDivisi || $sameUnit) && $urutan >= 2;
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
            session()->flash('error', 'Anda tidak berwenang memproses persetujuan Laporan pada tahap ini.');

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
                'scanFinalFile.required' => 'Scan fisik Laporan bertanda tangan wajib diunggah sebelum status Selesai.',
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
                $msg = 'Persetujuan Laporan tercatat. Tahap berikutnya diproses sistem.';
            } elseif ($action === 'perbaikan') {
                $workflow->requestRevision($this->ump, $user, $this->approvalNotes);
                $msg = 'Laporan dikembalikan untuk perbaikan.';
            } elseif ($action === 'batal') {
                $workflow->cancel($this->ump, $user, $this->approvalNotes);
                $msg = 'Laporan berhasil dibatalkan.';
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

    public function tambahBaris(string $penerimaKey = 'pj'): void
    {
        if (! $this->canEditSection($penerimaKey)) {
            return;
        }

        $this->itemsByPenerima[$penerimaKey][] = [
            'id' => null,
            'tgl' => '',
            'rincian' => '',
            'no_bukti' => '',
            'jumlah' => 0,
            'is_editing' => true,
        ];
    }

    public function editBaris(string $penerimaKey, int $index): void
    {
        if (! $this->canEditSection($penerimaKey)) {
            return;
        }

        if (! isset($this->itemsByPenerima[$penerimaKey][$index])) {
            return;
        }

        $this->itemsByPenerima[$penerimaKey][$index]['is_editing'] = true;
    }

    public function batalEditBaris(string $penerimaKey, int $index): void
    {
        if (! $this->canEditSection($penerimaKey)) {
            return;
        }

        $row = $this->itemsByPenerima[$penerimaKey][$index] ?? null;
        if (! $row) {
            return;
        }

        if (empty($row['id'])) {
            $this->hapusBaris($penerimaKey, $index);

            return;
        }

        $item = UmpItem::where('ump_id', $this->umpId)->find($row['id']);
        if ($item) {
            $this->itemsByPenerima[$penerimaKey][$index] = $this->formatItemRow($item);
        } else {
            $this->itemsByPenerima[$penerimaKey][$index]['is_editing'] = false;
        }

        unset($this->pendingRowFiles[$penerimaKey][$index]);
        $this->clearRowValidationErrors($penerimaKey, $index);
    }

    public function simpanBaris(string $penerimaKey, int $index): void
    {
        if (! $this->canEditSection($penerimaKey)) {
            session()->flash('error', 'Anda tidak berwenang mengubah baris pada bagian ini.');

            return;
        }

        if (! isset($this->itemsByPenerima[$penerimaKey][$index])) {
            return;
        }

        $rowPrefix = "itemsByPenerima.{$penerimaKey}.{$index}";
        $fileKey = "pendingRowFiles.{$penerimaKey}.{$index}";

        $this->validate([
            "{$rowPrefix}.tgl" => ['required', 'date'],
            "{$rowPrefix}.rincian" => ['required', 'string', 'min:3'],
            "{$rowPrefix}.no_bukti" => ['nullable', 'string', 'max:100'],
            "{$rowPrefix}.jumlah" => ['required', 'integer', 'min:1'],
            $fileKey => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            "{$rowPrefix}.tgl.required" => 'Tanggal pengeluaran wajib diisi.',
            "{$rowPrefix}.tgl.date" => 'Tanggal pengeluaran tidak valid.',
            "{$rowPrefix}.rincian.required" => 'Rincian pengeluaran wajib diisi.',
            "{$rowPrefix}.rincian.min" => 'Rincian pengeluaran minimal 3 karakter.',
            "{$rowPrefix}.jumlah.required" => 'Jumlah pengeluaran wajib diisi.',
            "{$rowPrefix}.jumlah.min" => 'Jumlah pengeluaran harus lebih dari 0.',
            "{$fileKey}.mimes" => 'Berkas harus berformat JPG, PNG, atau PDF.',
            "{$fileKey}.max" => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $row = $this->itemsByPenerima[$penerimaKey][$index];
        $this->assertValidBudgetYear($row['tgl'] ?? null, "{$rowPrefix}.tgl");

        $row['penerima_id'] = $penerimaKey === 'pj' ? null : (int) $penerimaKey;
        $pendingFile = $this->pendingRowFiles[$penerimaKey][$index] ?? null;

        try {
            $savedItem = (new UmpWorkflow)->saveSingleItem(
                $this->ump,
                $row,
                function (UmpItem $item) use ($pendingFile): void {
                    if ($pendingFile) {
                        $this->storeItemLampiran($item, $pendingFile);
                    }
                }
            );
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->itemsByPenerima[$penerimaKey][$index] = $this->formatItemRow($savedItem);
        unset($this->pendingRowFiles[$penerimaKey][$index]);
        $this->clearRowValidationErrors($penerimaKey, $index);
        $this->refreshUmpCache();

        $msg = 'Baris penggunaan dana tersimpan dan terkunci.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function updatedPendingRowFiles(mixed $value, string $key): void
    {
        $this->validateOnly("pendingRowFiles.{$key}", [
            "pendingRowFiles.{$key}" => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            "pendingRowFiles.{$key}.mimes" => 'Berkas harus berformat JPG, PNG, atau PDF.',
            "pendingRowFiles.{$key}.max" => 'Ukuran berkas maksimal 5 MB.',
        ]);
    }

    public function hapusPendingFile(string $penerimaKey, int $index): void
    {
        if (! $this->canEditSection($penerimaKey)) {
            return;
        }

        unset($this->pendingRowFiles[$penerimaKey][$index]);
        $this->resetErrorBag(["pendingRowFiles.{$penerimaKey}.{$index}"]);
    }

    public function hapusLampiranItem(int $lampiranId): void
    {
        $lampiran = Lampiran::where('attachable_type', UmpItem::class)->findOrFail($lampiranId);
        $item = UmpItem::where('ump_id', $this->umpId)->findOrFail($lampiran->attachable_id);

        $penerimaKey = $item->penerima_id ? (string) $item->penerima_id : 'pj';
        if (! $this->canEditSection($penerimaKey)) {
            session()->flash('error', 'Anda tidak berwenang menghapus bukti pada baris ini.');

            return;
        }

        if ($lampiran->path) {
            Storage::disk('local')->delete($lampiran->path);
        }

        $lampiran->delete();
        $this->refreshUmpCache();

        $msg = 'Bukti transaksi dihapus.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function hapusBaris(string $penerimaKey, int $index): void
    {
        if (! $this->canEditSection($penerimaKey)) {
            return;
        }

        if (! isset($this->itemsByPenerima[$penerimaKey][$index])) {
            return;
        }

        $row = $this->itemsByPenerima[$penerimaKey][$index];
        $deletedSavedRow = ! empty($row['id']);

        if ($deletedSavedRow) {
            try {
                (new UmpWorkflow)->deleteItem($this->ump, (int) $row['id']);
            } catch (RuntimeException $e) {
                session()->flash('error', $e->getMessage());
                $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

                return;
            }

            if ($this->buktiItemTarget === (int) $row['id']) {
                $this->reset('buktiItem', 'buktiItemTarget');
            }
        }

        $newPending = [];
        $newIdx = 0;
        foreach ($this->itemsByPenerima[$penerimaKey] as $oldIdx => $_row) {
            if ($oldIdx === $index) {
                continue;
            }

            if (isset($this->pendingRowFiles[$penerimaKey][$oldIdx])) {
                $newPending[$newIdx] = $this->pendingRowFiles[$penerimaKey][$oldIdx];
            }
            $newIdx++;
        }

        if ($newPending === []) {
            unset($this->pendingRowFiles[$penerimaKey]);
        } else {
            $this->pendingRowFiles[$penerimaKey] = $newPending;
        }

        unset($this->itemsByPenerima[$penerimaKey][$index]);
        $this->itemsByPenerima[$penerimaKey] = array_values($this->itemsByPenerima[$penerimaKey]);
        $this->refreshUmpCache();

        if ($deletedSavedRow) {
            $msg = 'Baris penggunaan dana dihapus.';
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        }
    }

    public function simpanDraft(): void
    {
        if (! $this->isEditable) {
            session()->flash('error', 'Laporan tidak dalam status Draft / Perlu Perbaikan.');

            return;
        }

        try {
            $items = $this->cleanedItems();
            (new UmpWorkflow)->saveItems($this->ump, $items, [], function ($item, array $row): void {
                $this->persistPendingFileForRow($item, $row);
            });
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->pendingRowFiles = [];
        $this->loadItems();
        $msg = 'Draft Laporan tersimpan. Saldo dan pembulatan diperbarui.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function ajukanUMP(): void
    {
        if (! $this->isEditable) {
            session()->flash('error', 'Laporan tidak dalam status Draft / Perlu Perbaikan.');

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
            $ump = (new UmpWorkflow)->saveItems($this->ump, $items, [], function ($item, array $row): void {
                $this->persistPendingFileForRow($item, $row);
            });
            (new UmpWorkflow)->submitByPj($ump, $user, $this->catatanKekurangan);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->pendingRowFiles = [];
        $this->loadItems();
        $msg = 'Laporan berhasil diajukan dan menunggu tanda tangan Penanggung Jawab.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    /**
     * Sinkronkan daftar penerima dana (hanya PJ / TU / keuangan / admin saat Laporan editable).
     */
    public function syncPenerima(): void
    {
        if (! $this->isEditable) {
            session()->flash('error', 'Daftar penerima hanya dapat diubah saat Laporan Draft / Perlu Perbaikan.');

            return;
        }

        $user = Auth::user();
        $allowed = $user && (
            $user->id === $this->ump->pj_id
            || $user->is_petugas_input
            || $user->role === 'admin'
            || $user->isKeuangan()
        );

        if (! $allowed) {
            session()->flash('error', 'Anda tidak berwenang mengubah daftar penerima dana.');

            return;
        }

        $this->validate([
            'selectedPenerimaIds' => ['array'],
            'selectedPenerimaIds.*' => ['integer', 'exists:users,id'],
        ], [
            'selectedPenerimaIds.*.exists' => 'Penerima dana yang dipilih tidak valid.',
        ]);

        $newIds = array_values(array_unique(array_map('intval', $this->selectedPenerimaIds)));

        $existing = $this->ump->penerima()->pluck('user_id')->all();
        $toAdd = array_diff($newIds, $existing);

        $this->ump->penerimaUsers()->sync($newIds);

        foreach ($toAdd as $penerimaUserId) {
            $penerimaUser = User::find($penerimaUserId);
            if (! $penerimaUser) {
                continue;
            }

            NotificationService::kirim(
                $penerimaUser,
                'dokumen_diputuskan',
                [
                    'judul' => 'Anda Ditunjuk sebagai Penerima Dana Laporan',
                    'pesan' => 'Anda ditunjuk untuk melaporkan penggunaan dana pada Laporan '.($this->ump->pencairan?->no_ppd ?? '#'.$this->ump->id).'.',
                    'url' => route('ump.detail', $this->ump->id),
                ],
                "ump_penerima_{$this->ump->id}_{$penerimaUserId}"
            );
        }

        $this->loadItems();
        $msg = 'Daftar penerima dana diperbarui.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    /**
     * Unggah bukti transfer/transaksi untuk satu baris penggunaan dana.
     */
    public function simpanBuktiItem(int $itemId): void
    {
        $this->validate([
            'buktiItem' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'buktiItem.required' => 'Berkas bukti transaksi wajib dipilih.',
            'buktiItem.mimes' => 'Berkas harus berformat JPG, PNG, atau PDF.',
            'buktiItem.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $item = UmpItem::where('ump_id', $this->umpId)->findOrFail($itemId);

        $penerimaKey = $item->penerima_id ? (string) $item->penerima_id : 'pj';
        if (! $this->canEditSection($penerimaKey)) {
            session()->flash('error', 'Anda tidak berwenang mengunggah bukti untuk baris ini.');

            return;
        }

        $this->storeItemLampiran($item, $this->buktiItem);

        $this->reset('buktiItem', 'buktiItemTarget');
        $this->refreshUmpCache();
        session()->flash('success', 'Bukti transaksi berhasil diunggah.');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Bukti transaksi berhasil diunggah.']);
    }

    public function setBuktiItemTarget(int $itemId): void
    {
        $this->buktiItemTarget = $itemId;
        $this->buktiItem = null;
    }

    public function cancelBuktiItemTarget(): void
    {
        $this->reset('buktiItem', 'buktiItemTarget');
    }

    /** Buang baris kosong, lalu validasi baris tersisa (FR-UMP-2). */
    protected function cleanedItems(): array
    {
        $flattened = [];
        $remappedPending = [];

        foreach ($this->itemsByPenerima as $key => $rows) {
            $penerimaId = $key === 'pj' ? null : (int) $key;
            $newIdx = 0;

            foreach ($rows as $origIdx => $row) {
                $pendingFile = $this->pendingRowFiles[$key][$origIdx] ?? null;
                $hasContent = trim((string) ($row['rincian'] ?? '')) !== ''
                    || (int) ($row['jumlah'] ?? 0) > 0
                    || ! empty($row['tgl'])
                    || $pendingFile !== null;

                if (! $hasContent) {
                    continue;
                }

                $sectionKey = (string) $key;
                if ($pendingFile !== null) {
                    $remappedPending[$sectionKey][$newIdx] = $pendingFile;
                }

                $row['penerima_id'] = $penerimaId;
                $row['_row_ref'] = ['key' => $sectionKey, 'index' => $newIdx];
                $flattened[] = $row;
                $newIdx++;
            }
        }

        $this->pendingRowFiles = $remappedPending;
        $this->itemsByPenerima = $this->regroupItems($flattened);
        $this->ensureSectionKeys();

        if ($flattened === []) {
            return [];
        }

        $this->validate([
            'itemsByPenerima' => ['array'],
            'itemsByPenerima.*.*.tgl' => ['required', 'date'],
            'itemsByPenerima.*.*.rincian' => ['required', 'string', 'min:3'],
            'itemsByPenerima.*.*.no_bukti' => ['nullable', 'string', 'max:100'],
            'itemsByPenerima.*.*.jumlah' => ['required', 'integer', 'min:1'],
            'pendingRowFiles.*.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'itemsByPenerima.*.*.tgl.required' => 'Tanggal pengeluaran wajib diisi.',
            'itemsByPenerima.*.*.tgl.date' => 'Tanggal pengeluaran tidak valid.',
            'itemsByPenerima.*.*.rincian.required' => 'Rincian pengeluaran wajib diisi.',
            'itemsByPenerima.*.*.rincian.min' => 'Rincian pengeluaran minimal 3 karakter.',
            'itemsByPenerima.*.*.jumlah.required' => 'Jumlah pengeluaran wajib diisi.',
            'itemsByPenerima.*.*.jumlah.min' => 'Jumlah pengeluaran harus lebih dari 0.',
            'pendingRowFiles.*.*.mimes' => 'Berkas harus berformat JPG, PNG, atau PDF.',
            'pendingRowFiles.*.*.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        // FR-UMP-7: item dates must fall within the budget year
        $tahun = $this->ump->pencairan?->permohonan?->kodeAnggaran?->tahunAnggaran?->tahun;
        if ($tahun) {
            foreach ($this->itemsByPenerima as $key => $rows) {
                foreach ($rows as $idx => $row) {
                    if (! empty($row['tgl']) && Carbon::parse($row['tgl'])->year !== (int) $tahun) {
                        $this->addError("itemsByPenerima.{$key}.{$idx}.tgl", "Tanggal pengeluaran harus berada dalam tahun anggaran {$tahun}.");
                    }
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

        return $flattened;
    }

    /**
     * Susun ulang daftar baris menjadi struktur per-penerima.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function regroupItems(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $key = empty($row['penerima_id']) ? 'pj' : (string) $row['penerima_id'];
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    protected function loadItems(): void
    {
        $ump = $this->refreshUmpCache();

        $grouped = $ump->items->groupBy(fn ($item) => $item->penerima_id ?: 'pj');

        $this->itemsByPenerima = [];
        foreach ($grouped as $key => $items) {
            $this->itemsByPenerima[(string) $key] = $items->map(fn (UmpItem $item) => $this->formatItemRow($item))->values()->all();
        }

        $this->ensureSectionKeys();
        $this->selectedPenerimaIds = $ump->penerima->pluck('user_id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * Format satu model UmpItem menjadi struktur state tabel Bagian B.
     *
     * @return array{id: int, tgl: string, rincian: string, no_bukti: string, jumlah: int, is_editing: bool}
     */
    protected function formatItemRow(UmpItem $item, bool $isEditing = false): array
    {
        return [
            'id' => $item->id,
            'tgl' => $item->tgl?->toDateString() ?? '',
            'rincian' => $item->rincian,
            'no_bukti' => $item->no_bukti ?? '',
            'jumlah' => (int) $item->jumlah,
            'is_editing' => $isEditing,
        ];
    }

    /** Pastikan setiap section penerima dan 'pj' selalu memiliki key pada $itemsByPenerima. */
    protected function ensureSectionKeys(): void
    {
        foreach ($this->ump->penerima as $p) {
            $key = (string) $p->id;
            if (! isset($this->itemsByPenerima[$key])) {
                $this->itemsByPenerima[$key] = [];
            }
        }

        if (! isset($this->itemsByPenerima['pj'])) {
            $this->itemsByPenerima['pj'] = [];
        }

        ksort($this->itemsByPenerima);
    }

    protected function refreshUmpCache(): Ump
    {
        $ump = $this->ump;
        // Segarkan atribut & relasi pada instance yang sama (computed $this->ump ter-cache per request),
        // agar UI langsung menampilkan data tersimpan tanpa perlu refresh halaman.
        $ump->refresh();
        $ump->load(['items.lampiran', 'penerima.user']);

        return $ump;
    }

    protected function storeItemLampiran(UmpItem $item, mixed $file): Lampiran
    {
        $path = $file->store('lampiran/ump-item', 'local');

        return Lampiran::create([
            'attachable_type' => UmpItem::class,
            'attachable_id' => $item->id,
            'jenis' => 'bukti_tf',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?? 0,
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'version' => 1,
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function persistPendingFileForRow(UmpItem $item, array $row): void
    {
        $ref = $row['_row_ref'] ?? null;
        if (! is_array($ref)) {
            return;
        }

        $file = $this->pendingRowFiles[$ref['key']][$ref['index']] ?? null;
        if ($file) {
            $this->storeItemLampiran($item, $file);
        }
    }

    protected function assertValidBudgetYear(?string $tgl, string $fieldPath): void
    {
        $tahun = $this->ump->pencairan?->permohonan?->kodeAnggaran?->tahunAnggaran?->tahun;
        if (! $tahun || empty($tgl)) {
            return;
        }

        if (Carbon::parse($tgl)->year !== (int) $tahun) {
            $msg = "Tanggal pengeluaran harus berada dalam tahun anggaran {$tahun}.";
            $this->addError($fieldPath, $msg);

            throw ValidationException::withMessages([$fieldPath => $msg]);
        }
    }

    protected function clearRowValidationErrors(string $penerimaKey, int $index): void
    {
        $prefix = "itemsByPenerima.{$penerimaKey}.{$index}";
        $this->resetErrorBag([
            "{$prefix}.tgl",
            "{$prefix}.rincian",
            "{$prefix}.no_bukti",
            "{$prefix}.jumlah",
            "pendingRowFiles.{$penerimaKey}.{$index}",
        ]);
    }

    /**
     * Kandidat penerima dana: pengguna aktif pada divisi UMP.
     *
     * @return array<int, array{id: int, name: string, sub: string}>
     */
    public function getPenerimaCandidatesProperty(): array
    {
        $divisiId = $this->ump->pencairan?->permohonan?->unit?->getDivisiId();

        $query = User::with(['unit', 'leveling'])->where('is_active', true);

        if ($divisiId) {
            $query->where(function ($q) use ($divisiId) {
                $q->where('unit_id', $divisiId)
                    ->orWhereHas('unit', fn ($u) => $u->where('parent_id', $divisiId));
            });
        }

        return $query->orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'sub' => $u->jabatan ?: ($u->leveling?->nama ?? $u->unit?->nama ?? ''),
        ])->values()->all();
    }

    public function render(): View
    {
        $ump = $this->ump;

        return view('livewire.ump.ump-detail', [
            'ump' => $ump,
            'isEditable' => $this->isEditable,
            'itemsByPenerima' => $this->itemsByPenerima,
            'totalPenggunaan' => $this->totalPenggunaan,
            'selisih' => $this->selisih,
            'defisit' => $this->defisit,
            'pembulatan' => $this->pembulatan,
            'canApprove' => $this->canApprove(),
            'stepProgress' => $this->stepProgress,
            'penerimaCandidates' => $this->penerimaCandidates,
            'incompletePenerimaNames' => $this->incompletePenerimaNames,
        ]);
    }
}
