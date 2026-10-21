<?php

namespace App\Livewire\Permohonan;

use App\Models\Lampiran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\Setting;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Rincian Permohonan Dana: SIGMA LAZ Al-Azhar')]
class PermohonanDetail extends Component
{
    use WithFileUploads;

    public int $permohonanId;

    // Standard Approval Actions Modal
    public bool $showActionModal = false;

    public string $actionType = ''; // 'setuju', 'perbaikan', 'tolak', 'batal'

    public string $actionNotes = '';

    // Proxy Keuangan Form
    public array $approvedItemAmounts = [];

    public string $proxySignerName = 'Rochadi Kohar';

    public string $proxySignerJabatan = 'Manajer Anggaran & Treasury';

    public string $proxySignedAt = '';

    public string $proxyNotes = '';

    public $proxyScanFile = null;

    // Modal Cairkan Dana (Penerbitan PPD)
    public bool $showCairkanModal = false;

    public string $noPpd = '';

    public string $tglCair = '';

    public ?int $pjId = null;

    public function mount(int $id): void
    {
        $this->permohonanId = $id;
        $this->proxySignedAt = now()->toDateString();

        $user = Auth::user();
        $permohonan = Permohonan::with(['items', 'unit'])->findOrFail($id);

        if ($user && ! in_array($user->role, ['admin', 'keuangan'])) {
            $docDivisiId = $permohonan->unit?->getDivisiId();
            $userDivisiId = $user->unit?->getDivisiId();
            $sameDivisi = ($docDivisiId !== null && $userDivisiId !== null && $docDivisiId === $userDivisiId);
            $isOwner = ($permohonan->user_id === $user->id);

            abort_unless($sameDivisi || $isOwner, 403, 'Anda tidak memiliki hak akses untuk melihat permohonan dari divisi lain.');
        }

        foreach ($permohonan->items as $item) {
            $this->approvedItemAmounts[$item->id] = $item->jumlah_disetujui ?? $item->jumlah_diminta;
        }
    }

    public function getPermohonanProperty(): Permohonan
    {
        return Permohonan::with([
            'unit',
            'user.leveling',
            'atasan',
            'kodeAnggaran',
            'sumberDana',
            'levelingRequired',
            'items.asnaf',
            'items.programUtama',
            'approvals.approver',
            'lampiran.uploadedBy',
            'pencairan.ump.pj',
        ])->findOrFail($this->permohonanId);
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
                'label' => 'Menunggu Atasan',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
            'disetujui_atasan' => [
                'label' => 'Menunggu Kadiv',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'diketahui_kadiv' => [
                'label' => 'Menunggu Keuangan',
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
                'label' => 'Dibatalkan',
                'class' => 'bg-slate-100 text-slate-500 border-slate-200',
            ],
            'dicairkan' => [
                'label' => 'Dicairkan',
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
            ],
            default => [
                'label' => ucfirst($status),
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            ],
        };
    }

    public function getIsFinanceStageProperty(): bool
    {
        $p = $this->permohonan;

        return $p->status === 'diketahui_kadiv';
    }

    public function getCanProxyFinanceApproveProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $keuanganMode = Setting::get('keuangan.mode', 'atas_nama');
        $isPetugas = (bool) $user->is_petugas_input;
        $isFinanceRole = $user->role === 'keuangan';
        $isAdmin = $user->role === 'admin';

        return $this->isFinanceStage && ($isAdmin || $isFinanceRole || ($isPetugas && $keuanganMode === 'atas_nama'));
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

    public function getCanApproveProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        // Pengaju tidak boleh approve sendiri kecuali admin
        if ($p->user_id === $user->id && $user->role !== 'admin') {
            return false;
        }

        if ($user->role === 'admin') {
            return in_array($p->status, ['diajukan', 'disetujui_atasan', 'diketahui_kadiv']);
        }

        $urutan = $user->leveling?->urutan ?? 0;

        if ($p->status === 'diajukan') {
            $requiredUrutan = $p->levelingRequired?->urutan ?? 1;
            $isDesignatedAtasan = $p->atasan_id && $p->atasan_id === $user->id;
            $isSameUnitOrDivisi = ($p->unit_id === $user->unit_id) || ($p->unit?->parent_id === $user->unit_id);
            $isSameUnitSupervisor = $isSameUnitOrDivisi && ($urutan >= 1) && ($requiredUrutan <= 1);
            $isSameUnitManager = $isSameUnitOrDivisi && ($urutan >= 2);
            $isKadivOrHigher = $urutan >= 3;

            return $isDesignatedAtasan || $isSameUnitSupervisor || $isSameUnitManager || $isKadivOrHigher;
        }

        if ($p->status === 'disetujui_atasan') {
            // Kepala Divisi (Leveling 3 ke atas)
            return $urutan >= 3;
        }

        if ($p->status === 'diketahui_kadiv') {
            return $this->canProxyFinanceApprove;
        }

        return false;
    }

    public function getCanCancelProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        $isOwnerOrAdmin = ($p->user_id === $user->id) || ($user->role === 'admin');

        return $isOwnerOrAdmin && in_array($p->status, ['draft', 'diajukan', 'disetujui_atasan', 'diketahui_kadiv', 'perlu_perbaikan']);
    }

    public function getCanResubmitProperty(): bool
    {
        $user = Auth::user();
        $p = $this->permohonan;

        if (! $user) {
            return false;
        }

        $isOwnerOrAdmin = ($p->user_id === $user->id) || ($user->role === 'admin');

        return $isOwnerOrAdmin && in_array($p->status, ['draft', 'perlu_perbaikan']);
    }

    public function openAction(string $type): void
    {
        $this->actionType = $type;
        $this->actionNotes = '';
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

        if (in_array($this->actionType, ['perbaikan', 'tolak', 'batal'])) {
            $this->validate([
                'actionNotes' => ['required', 'string', 'min:5'],
            ], [
                'actionNotes.required' => 'Catatan alasan wajib diisi.',
                'actionNotes.min' => 'Catatan alasan minimal 5 karakter.',
            ]);
        }

        $msg = '';
        if ($this->actionType === 'setuju') {
            $workflow->approve($this->permohonan, $user, [
                'catatan' => $this->actionNotes ?: null,
            ]);
            $msg = 'Permohonan berhasil disetujui.';
        } elseif ($this->actionType === 'perbaikan') {
            $workflow->requestRevision($this->permohonan, $user, $this->actionNotes);
            $msg = 'Permohonan dikembalikan untuk perbaikan.';
        } elseif ($this->actionType === 'tolak') {
            $workflow->reject($this->permohonan, $user, $this->actionNotes);
            $msg = 'Permohonan berhasil ditolak.';
        } elseif ($this->actionType === 'batal') {
            $workflow->cancel($this->permohonan, $user, $this->actionNotes);
            $msg = 'Permohonan berhasil dibatalkan.';
        }

        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        $this->closeActionModal();
    }

    public function submitKeuanganApproval(): void
    {
        foreach ($this->approvedItemAmounts as $id => $val) {
            if (is_string($val)) {
                $clean = preg_replace('/\D/', '', $val);
                $this->approvedItemAmounts[$id] = $clean !== '' ? (int) $clean : 0;
            }
        }

        $this->validate([
            'proxySignerName' => ['required', 'string', 'max:255'],
            'proxySignerJabatan' => ['required', 'string', 'max:255'],
            'proxySignedAt' => ['required', 'date'],
            'approvedItemAmounts.*' => ['required', 'integer', 'min:0'],
            'proxyScanFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'proxySignerName.required' => 'Nama penanda tangan Keuangan wajib diisi.',
            'proxySignerJabatan.required' => 'Jabatan penanda tangan Keuangan wajib diisi.',
            'proxySignedAt.required' => 'Tanggal tanda tangan fisik wajib diisi.',
            'approvedItemAmounts.*.required' => 'Nominal disetujui wajib diisi.',
            'proxyScanFile.mimes' => 'Berkas scan harus berformat PDF, JPG, atau PNG.',
            'proxyScanFile.max' => 'Ukuran berkas scan maksimal 10 MB.',
        ]);

        DB::transaction(function () {
            $p = $this->permohonan;

            // Simpan file scan jika diunggah
            if ($this->proxyScanFile) {
                $path = $this->proxyScanFile->store('lampiran/scan_final', 'public');
                Lampiran::create([
                    'attachable_type' => Permohonan::class,
                    'attachable_id' => $p->id,
                    'jenis' => 'scan_final',
                    'path' => $path,
                    'original_name' => $this->proxyScanFile->getClientOriginalName(),
                    'mime' => $this->proxyScanFile->getMimeType() ?? 'application/pdf',
                    'size' => $this->proxyScanFile->getSize() ?? 0,
                    'sha256' => hash_file('sha256', $this->proxyScanFile->getRealPath()),
                    'version' => 1,
                    'uploaded_by' => Auth::id(),
                ]);
            }

            // Submit approval ke workflow
            $workflow = new PermohonanWorkflow;
            $workflow->approve($p, Auth::user(), [
                'items' => $this->approvedItemAmounts,
                'signer_name' => $this->proxySignerName,
                'signer_jabatan' => $this->proxySignerJabatan,
                'catatan' => $this->proxyNotes ?: 'Persetujuan Keuangan dicatat sistem',
            ]);
        });
        $msg = 'Persetujuan Keuangan berhasil dicatat. Dana kini berstatus TERIKAT.';
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function submitPermohonan(): void
    {
        $workflow = new PermohonanWorkflow;
        $workflow->submit($this->permohonan);

        $msg = "Permohonan dana {$this->permohonan->fresh()->no_pengajuan} berhasil diajukan.";
        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function openCairkanModal(): void
    {
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
        $manager = $p->unit?->getManager();
        $this->pjId = $manager?->id;
        $this->showCairkanModal = true;
    }

    public function closeCairkanModal(): void
    {
        $this->showCairkanModal = false;
        $this->resetErrorBag();
    }

    public function submitCairkan(): void
    {
        $this->validate([
            'noPpd' => ['required', 'string', 'max:100', 'unique:pencairan,no_ppd'],
            'tglCair' => ['required', 'date'],
            'pjId' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $cand = User::with('leveling')->find($value);
                    if (! $cand || ($cand->leveling?->urutan ?? 0) < 2) {
                        $fail('Penanggung Jawab (PJ) UMP wajib pejabat berlevel Manager atau lebih tinggi.');
                    }
                },
            ],
        ], [
            'noPpd.required' => 'Nomor PPD wajib diisi.',
            'noPpd.unique' => 'Nomor PPD sudah dipakai.',
            'tglCair.required' => 'Tanggal pencairan wajib diisi.',
            'pjId.required' => 'Penanggung Jawab (PJ) UMP wajib dipilih (level Manager).',
        ]);

        try {
            $workflow = new PermohonanWorkflow;
            $pencairan = $workflow->cairkan($this->permohonan, Auth::user(), [
                'no_ppd' => $this->noPpd,
                'tgl_cair' => $this->tglCair,
                'pj_id' => $this->pjId,
            ]);
            $this->showCairkanModal = false;
            $msg = "Pencairan PPD {$pencairan->no_ppd} berhasil diterbitkan.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        } catch (RuntimeException $e) {
            $this->addError('noPpd', $e->getMessage());
        }
    }

    public function render(): View
    {
        $unitId = $this->permohonan->unit_id;
        $divisiId = $this->permohonan->unit?->getDivisiId();

        $pjCandidates = User::where(function ($q) use ($unitId, $divisiId) {
            $q->where('unit_id', $unitId);
            if ($divisiId) {
                $q->orWhere('unit_id', $divisiId);
            }
        })
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($pjCandidates->isEmpty()) {
            $pjCandidates = User::whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $unitManager = $this->permohonan->unit?->getManager();
        if ($unitManager) {
            $pjCandidates = $pjCandidates->reject(fn ($u) => $u->id === $unitManager->id)->prepend($unitManager);
        }

        return view('livewire.permohonan.permohonan-detail', [
            'permohonan' => $this->permohonan,
            'statusBadge' => $this->statusBadge,
            'canApprove' => $this->canApprove,
            'canCancel' => $this->canCancel,
            'canResubmit' => $this->canResubmit,
            'canCairkan' => $this->canCairkan,
            'pjCandidates' => $pjCandidates,
            'isFinanceStage' => $this->isFinanceStage,
            'canProxyFinanceApprove' => $this->canProxyFinanceApprove,
        ]);
    }
}
