<?php

namespace App\Livewire\Permohonan;

use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PermohonanWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Rincian Permohonan Dana: SIGMA LAZ Al-Azhar')]
class PermohonanDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $permohonanId;

    // Standard Approval Actions Modal
    public bool $showActionModal = false;

    public string $actionType = ''; // 'setuju', 'perbaikan', 'tolak', 'batal'

    public string $actionNotes = '';

    // Form Persetujuan Akhir (Kadiv Divisi / Kadiv Keuangan)
    public array $approvedItemAmounts = [];

    public string $proxyNotes = '';

    // Modal Cairkan Dana (Penerbitan PPD)
    public bool $showCairkanModal = false;

    public string $noPpd = '';

    public string $tglCair = '';

    public ?int $pjId = null;

    public array $penerimaIds = [];

    public $buktiTransferFile = null;

    // Modal Unggah / Perbarui Bukti Transfer PPD
    public bool $showBuktiTransferModal = false;

    public $buktiTransferUpload = null;

    // Modal Alih Pejabat Pemohon (Draft / Perlu Perbaikan)
    public bool $showChangePemohonModal = false;

    public ?int $newPemohonId = null;

    public function mount(int $id): void
    {
        $this->permohonanId = $id;
        $user = Auth::user();
        $permohonan = Permohonan::with(['items', 'unit.parent', 'user.leveling', 'pengusul'])->findOrFail($id);

        abort_unless($user && $user->can('view', $permohonan), 403, 'Anda tidak memiliki hak akses untuk melihat permohonan dari divisi lain.');

        foreach ($permohonan->items as $item) {
            $this->approvedItemAmounts[$item->id] = $item->jumlah_disetujui ?? $item->jumlah_diminta;
        }
    }

    public function getPermohonanProperty(): Permohonan
    {
        $permohonan = Permohonan::with([
            'unit.parent',
            'user.leveling',
            'user.unit.parent',
            'pengusul.leveling',
            'atasan',
            'kodeAnggaran',
            'sumberDana',
            'levelingRequired',
            'items.asnaf',
            'items.programUtama',
            'approvals.approver',
            'lampiran.uploadedBy',
            'pencairan.ump.pj',
            'pencairan.buktiTransfer.uploadedBy',
        ])->findOrFail($this->permohonanId);

        abort_unless(Auth::user()?->can('view', $permohonan), 403, 'Anda tidak memiliki hak akses untuk melihat permohonan dari divisi lain.');

        return $permohonan;
    }

    /**
     * Rantai persetujuan aktif untuk permohonan ini.
     *
     * @return array<string, mixed>
     */
    public function getApprovalChainProperty(): array
    {
        return (new PermohonanWorkflow)->resolveApprovalChainForPermohonan($this->permohonan);
    }

    public function getStatusBadgeProperty(): array
    {
        $status = $this->permohonan->status;

        return match ($status) {
            'draft' => [
                'label' => 'Draft',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            ],
            'diajukan' => [
                'label' => 'Menunggu Persetujuan',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'diketahui_kadiv' => [
                'label' => 'Menunggu Persetujuan Akhir',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'disetujui_keuangan' => [
                'label' => 'Disetujui Keuangan (Terikat)',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'perlu_perbaikan' => [
                'label' => 'Perlu Perbaikan',
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'ditolak' => [
                'label' => 'Ditolak',
                'class' => 'bg-red-50 text-red-700 border-red-200',
            ],
            'dibatalkan' => [
                'label' => 'Void',
                'class' => 'bg-slate-100 text-slate-500 border-slate-200',
            ],
            'dicairkan' => [
                'label' => 'Dicairkan',
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
            ],
            'selesai' => [
                'label' => 'Selesai',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            ],
            default => [
                'label' => ucfirst($status),
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            ],
        };
    }

    public function getIsFinanceStageProperty(): bool
    {
        return $this->permohonan->status === 'diketahui_kadiv';
    }

    public function getCanProxyFinanceApproveProperty(): bool
    {
        return $this->isFinanceStage && $this->canApprove;
    }

    public function getCanCairkanProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $isAuthorized = $user->role === 'keuangan' || $user->role === 'admin' || (bool) $user->is_petugas_input;

        return $this->permohonan->status === 'disetujui_keuangan' && $isAuthorized;
    }

    public function getCanUploadBuktiTransferProperty(): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->is_active) {
            return false;
        }

        return $user->role === 'keuangan' || $user->role === 'admin' || (bool) $user->is_petugas_input;
    }

    public function getCanApproveProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return (new PermohonanWorkflow)->canUserApprove($user, $this->permohonan);
    }

    public function getCanCancelProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        $isOwnerOrAdmin = ($p->user_id === $user->id)
            || ($p->pengusul_id === $user->id)
            || $user->isAdmin();

        return $isOwnerOrAdmin && in_array($p->status, ['draft', 'diajukan', 'diketahui_kadiv', 'perlu_perbaikan'], true);
    }

    public function getCanResubmitProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        $isAuthorized = ($p->user_id === $user->id)
            || ($p->pengusul_id === $user->id)
            || ($user->role === 'admin')
            || (bool) $user->is_petugas_input;

        return $isAuthorized && in_array($p->status, ['draft', 'perlu_perbaikan']);
    }

    public function getCanEditDraftProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        $isAuthorized = ($p->user_id === $user->id)
            || ($p->pengusul_id === $user->id)
            || ($user->role === 'admin')
            || (bool) $user->is_petugas_input;

        return $isAuthorized && in_array($p->status, ['draft', 'perlu_perbaikan'], true);
    }

    public function getCanChangePemohonProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        $isTuOrAdmin = $user->isAdmin() || (bool) $user->is_petugas_input;

        return $isTuOrAdmin && in_array($p->status, ['draft', 'perlu_perbaikan'], true);
    }

    public function getPemohonCandidatesProperty(): array
    {
        return User::with(['unit.parent', 'leveling'])
            ->where('is_active', true)
            ->whereHas('leveling', fn ($l) => $l->where('urutan', '<=', 3))
            ->get()
            ->sortBy(fn (User $u) => [$u->leveling?->urutan ?? 9, $u->name])
            ->values()
            ->map(function (User $u) {
                $jabatan = $u->jabatan ?: $u->leveling?->nama ?? 'Pejabat';
                $limitText = $u->leveling?->batas_atas
                    ? ' • Maks Rp '.number_format($u->leveling->batas_atas, 0, ',', '.')
                    : ' • Tanpa Batas';
                $unitText = '';
                if ($u->unit) {
                    $divisi = $u->unit->parent?->nama;
                    $unitText = ' • '.($divisi ? $divisi.' - ' : '').$u->unit->nama;
                }

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'jabatan' => $jabatan.$limitText.$unitText,
                ];
            })
            ->toArray();
    }

    public function openChangePemohonModal(): void
    {
        abort_unless($this->canChangePemohon, 403, 'Hanya Admin atau Petugas TU yang berwenang mengalihkan pejabat pemohon.');
        $this->newPemohonId = $this->permohonan->user_id;
        $this->showChangePemohonModal = true;
    }

    public function closeChangePemohonModal(): void
    {
        $this->showChangePemohonModal = false;
        $this->resetErrorBag();
    }

    public function updatePejabatPemohon(): void
    {
        abort_unless($this->canChangePemohon, 403, 'Hanya Admin atau Petugas TU yang berwenang mengalihkan pejabat pemohon.');

        $this->validate([
            'newPemohonId' => [
                'required',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $selected = User::with('leveling')->find($value);
                    $urutan = $selected?->leveling?->urutan ?? 0;
                    if (! $selected || ! $selected->is_active || $urutan < 1 || $urutan > 3) {
                        $fail('Pejabat pemohon baru harus pengguna aktif dengan tingkat Supervisor, Manajer, atau Kepala Divisi.');
                    }
                },
            ],
        ], [
            'newPemohonId.required' => 'Pejabat pemohon baru wajib dipilih.',
        ]);

        $p = $this->permohonan;
        $newPejabat = User::with(['unit', 'leveling'])->findOrFail($this->newPemohonId);

        $batasAtas = (new PermohonanWorkflow)->getSubmitterBatasAtas($newPejabat);
        if ($batasAtas !== null && $p->total_diminta > $batasAtas) {
            $this->addError(
                'newPemohonId',
                'Total pengajuan (Rp '.number_format($p->total_diminta, 0, ',', '.').") melebihi batas kewenangan {$newPejabat->name} sebagai ".($newPejabat->jabatan ?? $newPejabat->leveling?->nama ?? 'Pejabat').' (maks. Rp '.number_format($batasAtas, 0, ',', '.').').'
            );

            return;
        }

        $before = $p->toArray();
        $p->user_id = $newPejabat->id;
        if ($newPejabat->unit_id) {
            $p->unit_id = $newPejabat->unit_id;
        }
        $p->save();

        (new AuditLogger)->log('permohonan.pemohon_changed', $p, $before, $p->fresh()->toArray());

        $this->closeChangePemohonModal();
        unset($this->permohonan);

        $msg = "Pejabat pemohon berhasil dialihkan ke {$newPejabat->name}.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function openAction(string $type): void
    {
        if ($type === 'batal') {
            abort_unless($this->canCancel, 403, 'Anda tidak berwenang me-void permohonan ini.');
        } else {
            abort_unless($this->canApprove, 403, 'Anda tidak berwenang melakukan tindakan pada tahap ini.');
        }

        $this->actionType = $type;
        $this->actionNotes = '';
        $this->resetErrorBag('actionNotes');
        $this->showActionModal = true;
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->actionType = '';
        $this->actionNotes = '';
    }

    public function submitAction(): void
    {
        $workflow = new PermohonanWorkflow;
        $user = Auth::user();

        if ($this->actionType === 'batal') {
            abort_unless($this->canCancel, 403, 'Anda tidak berwenang me-void permohonan ini.');
        } elseif ($this->actionType === 'setuju') {
            abort_unless($this->canApprove && $this->permohonan->status === 'diajukan', 403, 'Anda tidak berwenang menyetujui permohonan pada tahap ini.');
        } elseif (in_array($this->actionType, ['perbaikan', 'tolak'], true)) {
            abort_unless($this->canApprove, 403, 'Anda tidak berwenang melakukan tindakan pada tahap ini.');
        } else {
            abort(400, 'Tindakan tidak dikenal.');
        }

        if (in_array($this->actionType, ['perbaikan', 'tolak', 'batal'], true)) {
            $this->validate([
                'actionNotes' => ['required', 'string', 'min:5'],
            ], [
                'actionNotes.required' => 'Catatan alasan wajib diisi.',
                'actionNotes.min' => 'Catatan alasan minimal 5 karakter.',
            ]);
        }

        $msg = '';
        try {
            if ($this->actionType === 'setuju') {
                $p = $this->permohonan;
                $chain = $this->approvalChain;
                $workflow->approve($p, $user, [
                    'catatan' => $this->actionNotes ?: null,
                ]);
                $nextLabel = $chain['step2']['label'] ?? 'Penyetuju Akhir';
                $msg = "Pemeriksaan tercatat. Permohonan diteruskan ke {$nextLabel}.";
            } elseif ($this->actionType === 'perbaikan') {
                $workflow->requestRevision($this->permohonan, $user, $this->actionNotes);
                $msg = 'Permohonan dikembalikan untuk perbaikan.';
            } elseif ($this->actionType === 'tolak') {
                $workflow->reject($this->permohonan, $user, $this->actionNotes);
                $msg = 'Permohonan berhasil ditolak.';
            } elseif ($this->actionType === 'batal') {
                $workflow->cancel($this->permohonan, $user, $this->actionNotes);
                $msg = 'Permohonan berhasil di-void.';
            }
        } catch (RuntimeException $e) {
            $this->addError('actionNotes', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        unset($this->permohonan);

        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        $this->closeActionModal();
    }

    public function submitKeuanganApproval(): void
    {
        abort_unless($this->canProxyFinanceApprove, 403, 'Anda tidak berwenang memberikan persetujuan akhir pada permohonan ini.');

        foreach ($this->approvedItemAmounts as $id => $val) {
            if (is_string($val)) {
                $trimmed = trim($val);
                if (preg_match('/^-\d+$/', $trimmed)) {
                    $this->approvedItemAmounts[$id] = (int) $trimmed;
                } else {
                    $clean = preg_replace('/\D/', '', $trimmed);
                    $this->approvedItemAmounts[$id] = $clean !== '' ? (int) $clean : 0;
                }
            }
        }

        $p = $this->permohonan;
        $rules = [
            'proxyNotes' => ['nullable', 'string', 'max:1000'],
        ];
        $messages = [];

        foreach ($p->items as $item) {
            $rules["approvedItemAmounts.{$item->id}"] = ['required', 'integer', 'min:0', "max:{$item->jumlah_diminta}"];
            $messages["approvedItemAmounts.{$item->id}.required"] = "Nominal disetujui untuk \"{$item->uraian}\" wajib diisi.";
            $messages["approvedItemAmounts.{$item->id}.integer"] = "Nominal disetujui untuk \"{$item->uraian}\" harus berupa angka.";
            $messages["approvedItemAmounts.{$item->id}.min"] = "Nominal disetujui untuk \"{$item->uraian}\" tidak boleh negatif.";
            $messages["approvedItemAmounts.{$item->id}.max"] = "Nominal disetujui untuk \"{$item->uraian}\" maksimal Rp ".number_format($item->jumlah_diminta, 0, ',', '.').'.';
        }

        $this->validate($rules, $messages);

        $totalApproved = 0;
        foreach ($p->items as $item) {
            $totalApproved += (int) ($this->approvedItemAmounts[$item->id] ?? 0);
        }

        if ($totalApproved <= 0) {
            $this->addError('approvedItemAmounts', 'Total nominal yang disetujui harus lebih dari Rp 0.');

            return;
        }

        $chain = $this->approvalChain;
        $step2Label = $chain['step2']['label'] ?? 'Penyetuju Akhir';

        try {
            $workflow = new PermohonanWorkflow;
            $workflow->approve($p, Auth::user(), [
                'items' => $this->approvedItemAmounts,
                'catatan' => $this->proxyNotes ?: "Disetujui oleh {$step2Label}",
            ]);
        } catch (RuntimeException $e) {
            $this->addError('approvedItemAmounts', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        unset($this->permohonan);
        $this->reset('proxyNotes');

        foreach ($this->permohonan->items as $item) {
            $this->approvedItemAmounts[$item->id] = $item->jumlah_disetujui ?? $item->jumlah_diminta;
        }

        $msg = "Persetujuan akhir {$step2Label} berhasil dicatat. Dana kini berstatus TERIKAT.";

        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function submitPermohonan(): void
    {
        abort_unless($this->canResubmit, 403, 'Anda tidak berwenang mengajukan permohonan ini.');

        try {
            $workflow = new PermohonanWorkflow;
            $workflow->submit($this->permohonan);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        unset($this->permohonan);

        $msg = $this->permohonan->status === 'disetujui_keuangan'
            ? "Permohonan dana {$this->permohonan->no_pengajuan} berhasil diajukan dan langsung disetujui."
            : "Permohonan dana {$this->permohonan->no_pengajuan} berhasil diajukan.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function openCairkanModal(): void
    {
        abort_unless($this->canCairkan, 403, 'Anda tidak berwenang menerbitkan pencairan dana.');

        $p = $this->permohonan;
        $this->tglCair = now()->toDateString();
        $year = date('Y');
        $month = date('m');
        $count = Pencairan::whereYear('tgl_cair', $year)->whereMonth('tgl_cair', $month)->count();
        $seq = $count + 1;
        $candidate = sprintf('PPD/%s%s/%04d', $year, $month, $seq);
        while (Pencairan::where('no_ppd', $candidate)->exists()) {
            $seq++;
            $candidate = sprintf('PPD/%s%s/%04d', $year, $month, $seq);
        }
        $this->noPpd = $candidate;
        $defaultPj = (new PermohonanWorkflow)->resolveDefaultPj($p);
        $this->pjId = $defaultPj?->id ?? ($this->pjCandidates()[0]['id'] ?? null);
        $this->penerimaIds = [];
        $this->buktiTransferFile = null;
        $this->showCairkanModal = true;
    }

    public function closeCairkanModal(): void
    {
        $this->showCairkanModal = false;
        $this->buktiTransferFile = null;
        $this->resetErrorBag();
    }

    public function submitCairkan(): void
    {
        abort_unless($this->canCairkan, 403, 'Anda tidak berwenang menerbitkan pencairan dana.');

        $maxKb = max(100, (int) (Setting::get('lampiran.max_kb', 5120) ?: 5120));
        $maxMbLabel = $this->formatMaxKbLabel($maxKb);

        $this->validate([
            'noPpd' => ['required', 'string', 'max:100', 'unique:pencairan,no_ppd'],
            'tglCair' => ['required', 'date'],
            'pjId' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $cand = User::with('leveling')->find($value);
                    if (! $cand || ($cand->leveling?->urutan ?? 0) < 2) {
                        $fail('Penanggung Jawab (PJ) Laporan wajib pejabat berlevel Manager atau lebih tinggi.');
                    }
                },
            ],
            'penerimaIds' => ['array'],
            'penerimaIds.*' => ['integer', 'exists:users,id'],
            'buktiTransferFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', "max:{$maxKb}"],
        ], [
            'noPpd.required' => 'Nomor PPD wajib diisi.',
            'noPpd.unique' => 'Nomor PPD sudah dipakai.',
            'tglCair.required' => 'Tanggal pencairan wajib diisi.',
            'pjId.required' => 'Penanggung Jawab (PJ) Laporan wajib dipilih (minimal level Manager).',
            'penerimaIds.*.exists' => 'Penerima dana yang dipilih tidak valid.',
            'buktiTransferFile.file' => 'Berkas bukti transfer tidak valid.',
            'buktiTransferFile.mimes' => 'Bukti transfer harus berformat PDF, JPG, PNG, atau WEBP.',
            'buktiTransferFile.max' => "Ukuran berkas bukti transfer maksimal {$maxMbLabel}.",
        ]);

        try {
            $workflow = new PermohonanWorkflow;
            $pencairan = $workflow->cairkan($this->permohonan, Auth::user(), [
                'no_ppd' => $this->noPpd,
                'tgl_cair' => $this->tglCair,
                'pj_id' => $this->pjId,
                'penerima_ids' => $this->penerimaIds,
                'bukti_transfer' => $this->buktiTransferFile,
            ]);
            $this->showCairkanModal = false;
            $this->buktiTransferFile = null;
            unset($this->permohonan);
            $msg = "Pencairan PPD {$pencairan->no_ppd} berhasil diterbitkan.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        } catch (RuntimeException $e) {
            $this->addError('noPpd', $e->getMessage());
        }
    }

    public function openBuktiTransferModal(): void
    {
        abort_unless($this->canUploadBuktiTransfer, 403, 'Anda tidak berwenang mengunggah bukti transfer pencairan.');
        abort_unless($this->permohonan->pencairan !== null, 404);

        $this->buktiTransferUpload = null;
        $this->resetErrorBag('buktiTransferUpload');
        $this->showBuktiTransferModal = true;
    }

    public function closeBuktiTransferModal(): void
    {
        $this->showBuktiTransferModal = false;
        $this->buktiTransferUpload = null;
        $this->resetErrorBag('buktiTransferUpload');
    }

    public function submitBuktiTransfer(): void
    {
        abort_unless($this->canUploadBuktiTransfer, 403, 'Anda tidak berwenang mengunggah bukti transfer pencairan.');

        $pencairan = $this->permohonan->pencairan;
        abort_unless($pencairan !== null, 404);

        $maxKb = max(100, (int) (Setting::get('lampiran.max_kb', 5120) ?: 5120));
        $maxMbLabel = $this->formatMaxKbLabel($maxKb);

        $this->validate([
            'buktiTransferUpload' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', "max:{$maxKb}"],
        ], [
            'buktiTransferUpload.required' => 'Berkas bukti transfer wajib dipilih.',
            'buktiTransferUpload.file' => 'Berkas bukti transfer tidak valid.',
            'buktiTransferUpload.mimes' => 'Bukti transfer harus berformat PDF, JPG, PNG, atau WEBP.',
            'buktiTransferUpload.max' => "Ukuran berkas bukti transfer maksimal {$maxMbLabel}.",
        ]);

        try {
            $lampiran = (new PermohonanWorkflow)->simpanBuktiTransfer(
                $pencairan,
                Auth::user(),
                $this->buktiTransferUpload
            );
            $this->closeBuktiTransferModal();
            unset($this->permohonan);

            $msg = "Bukti transfer PPD {$pencairan->no_ppd} (v{$lampiran->version}) berhasil disimpan.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        } catch (RuntimeException $e) {
            $this->addError('buktiTransferUpload', $e->getMessage());
        }
    }

    private function formatMaxKbLabel(int $maxKb): string
    {
        if ($maxKb % 1024 === 0) {
            return ($maxKb / 1024).' MB';
        }

        return number_format($maxKb / 1024, 1, ',', '.').' MB';
    }

    /**
     * Semua pejabat level Manager ke atas (urutan >= 2) di seluruh organisasi.
     * Urutan: PJ default (Kadiv untuk permohonan Kadiv, atau Manager unit untuk Supervisor/Manager),
     * pejabat satu divisi, lalu sisanya; masing-masing A-Z.
     *
     * @return array<int, array{id: int, name: string, sub: string}>
     */
    private function pjCandidates(): array
    {
        $defaultPj = (new PermohonanWorkflow)->resolveDefaultPj($this->permohonan);
        $divisiId = $this->permohonan->unit?->getDivisiId();

        return User::with(['unit', 'leveling'])
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->sortBy(fn (User $u) => [
                $u->is($defaultPj) ? 0 : (($divisiId && $u->unit?->getDivisiId() === $divisiId) ? 1 : 2),
                $u->name,
            ])
            ->map(function (User $u) {
                $jabatan = $u->jabatan ?: $u->leveling?->nama;
                $unitName = $u->unit?->nama;
                $sub = $jabatan;
                if ($unitName) {
                    $normalizedUnit = str_replace(['Unit ', 'Divisi '], '', $unitName);
                    if (! str_contains(strtolower($jabatan), strtolower($normalizedUnit))) {
                        $sub .= ' ('.$unitName.')';
                    }
                }

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'sub' => $sub,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Semua pengguna aktif di divisi permohonan (staf/supervisor/manager/kadiv) untuk dipilih sebagai penerima dana UMP.
     *
     * @return array<int, array{id: int, name: string, sub: string}>
     */
    private function penerimaCandidates(): array
    {
        $divisiId = $this->permohonan->unit?->getDivisiId();

        return User::with(['unit', 'leveling'])
            ->where('is_active', true)
            ->where(function ($q) use ($divisiId) {
                if ($divisiId) {
                    $q->where('unit_id', $divisiId)
                        ->orWhereHas('unit', fn ($u) => $u->where('parent_id', $divisiId));
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'sub' => $u->jabatan ?: ($u->leveling?->nama ?? $u->unit?->nama ?? ''),
            ])
            ->values()
            ->all();
    }

    public function render(): View
    {
        $maxKb = max(100, (int) (Setting::get('lampiran.max_kb', 5120) ?: 5120));

        return view('livewire.permohonan.permohonan-detail', [
            'permohonan' => $this->permohonan,
            'approvalChain' => $this->approvalChain,
            'statusBadge' => $this->statusBadge,
            'canApprove' => $this->canApprove,
            'canCancel' => $this->canCancel,
            'canResubmit' => $this->canResubmit,
            'canCairkan' => $this->canCairkan,
            'canUploadBuktiTransfer' => $this->canUploadBuktiTransfer,
            'maxLampiranLabel' => $this->formatMaxKbLabel($maxKb),
            'pjCandidates' => $this->pjCandidates(),
            'penerimaCandidates' => $this->penerimaCandidates(),
            'isFinanceStage' => $this->isFinanceStage,
            'canProxyFinanceApprove' => $this->canProxyFinanceApprove,
        ]);
    }
}
