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
use RuntimeException;

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

    // Pencatatan tanda tangan fisik basah pemeriksa (Tahap 2 - Kepala Divisi)
    public ?int $pemeriksaId = null;

    public string $pemeriksaName = '';

    public string $pemeriksaJabatan = '';

    public string $pemeriksaSignedAt = '';

    public string $financeSignerPreset = '';

    // Proxy Form Persetujuan Akhir (Kadiv / Keuangan)
    public array $approvedItemAmounts = [];

    public string $proxySignerName = '';

    public string $proxySignerJabatan = '';

    public string $proxySignedAt = '';

    public string $proxyNotes = '';

    public $proxyScanFile = null;

    // Modal Cairkan Dana (Penerbitan PPD)
    public bool $showCairkanModal = false;

    public string $noPpd = '';

    public string $tglCair = '';

    public ?int $pjId = null;

    public array $penerimaIds = [];

    public function mount(int $id): void
    {
        $this->permohonanId = $id;
        $this->pemeriksaSignedAt = now()->toDateString();

        $user = Auth::user();
        $permohonan = Permohonan::with(['items', 'unit', 'levelingRequired'])->findOrFail($id);

        $levelingUrutan = $permohonan->levelingRequired?->urutan ?? 1;
        $workflow = new PermohonanWorkflow;
        if ($permohonan->status === 'diajukan') {
            $signer = $levelingUrutan <= 1 ? $permohonan->unit?->getManager() : $workflow->getKadivDivisi($permohonan);
        } elseif ($permohonan->status === 'diketahui_kadiv' && $levelingUrutan <= 1) {
            $signer = $workflow->getKadivDivisi($permohonan);
        } else {
            $signer = User::where('is_active', true)->where('role', 'keuangan')->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first()
                ?? User::where('is_active', true)->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first();
        }

        $this->proxySignedAt = now()->toDateString();
        if ($levelingUrutan <= 1) {
            $finalKadiv = $workflow->getKadivDivisi($permohonan);
        } else {
            $finalKadiv = User::where('is_active', true)->where('role', 'keuangan')->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first()
                ?? User::where('is_active', true)->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first();
        }

        if ($finalKadiv) {
            $this->proxySignerName = $finalKadiv->name;
            $this->proxySignerJabatan = $finalKadiv->jabatan ?? ($levelingUrutan <= 1 ? 'Kepala Divisi' : 'Kepala Divisi Keuangan');
            $this->financeSignerPreset = "{$this->proxySignerName}|{$this->proxySignerJabatan}";
        } else {
            $this->financeSignerPreset = 'manual';
        }

        $this->pemeriksaId = $signer?->id;
        $this->pemeriksaName = $signer?->name ?? '';
        $this->pemeriksaJabatan = $signer?->jabatan ?? '';

        if ($user && ! $user->hasRole(['admin', 'keuangan']) && ! $user->is_petugas_input) {
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
        return $this->permohonan->status === 'diketahui_kadiv';
    }

    public function getCanProxyFinanceApproveProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $p = $this->permohonan;
        if ($p->status !== 'diketahui_kadiv') {
            return false;
        }

        $levelingUrutan = $p->levelingRequired?->urutan ?? 1;
        $docDivisiId = $p->unit?->getDivisiId();

        $isAdmin = $user->role === 'admin';
        $isPetugas = (bool) $user->is_petugas_input;

        if ($levelingUrutan <= 1) {
            $isKadivDivisi = $user->isKadiv() && $user->canAccessDivision($docDivisiId);

            return $isAdmin || $isPetugas || $isKadivDivisi;
        }

        $isKadivKeuangan = $user->isKadivKeuangan();
        $isFinanceRole = $user->role === 'keuangan';
        $modeAtasNama = Setting::get('keuangan.mode', 'atas_nama') === 'atas_nama';

        return $isAdmin || $isKadivKeuangan || ($isFinanceRole && $modeAtasNama) || ($isPetugas && $modeAtasNama);
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

        $isTuRecorder = (bool) $user->is_petugas_input || $user->role === 'admin';

        if ($p->user_id === $user->id && $user->role !== 'admin' && ! $isTuRecorder) {
            return false;
        }

        $docDivisiId = $p->unit?->getDivisiId();
        $levelingUrutan = $p->levelingRequired?->urutan ?? 1;

        if ($p->status === 'diajukan') {
            if ($isTuRecorder) {
                return true;
            }

            if ($levelingUrutan <= 1) {
                return ($user->isManager() || $user->isKadiv())
                    && $user->canAccessDivision($docDivisiId);
            }

            return $user->isKadiv() && $user->canAccessDivision($docDivisiId);
        }

        if ($p->status === 'diketahui_kadiv') {
            if ($levelingUrutan <= 1) {
                return $isTuRecorder || ($user->isKadiv() && $user->canAccessDivision($docDivisiId));
            }

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

        return $isOwnerOrAdmin && in_array($p->status, ['draft', 'diajukan', 'diketahui_kadiv', 'perlu_perbaikan'], true);
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

        if ($type === 'setuju' && ! $this->pemeriksaId) {
            $p = $this->permohonan;
            $levelingUrutan = $p->levelingRequired?->urutan ?? 1;
            $workflow = new PermohonanWorkflow;

            if ($p->status === 'diajukan') {
                $signer = $levelingUrutan <= 1 ? $p->unit?->getManager() : $workflow->getKadivDivisi($p);
            } elseif ($p->status === 'diketahui_kadiv' && $levelingUrutan <= 1) {
                $signer = $workflow->getKadivDivisi($p);
            } else {
                $signer = User::where('is_active', true)->where('role', 'keuangan')->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))->first()
                    ?? User::getPejabatKadiv()->first();
            }

            if (! $signer) {
                $signer = User::getKadivSekretariat() ?? User::getPejabatKadiv()->first();
            }

            $this->pemeriksaId = $signer?->id;
            $this->pemeriksaName = $signer?->name ?? '';
            $this->pemeriksaJabatan = $signer?->jabatan ?? '';
            $this->pemeriksaSignedAt = now()->toDateString();
        }

        $this->showActionModal = true;
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->actionType = '';
        $this->actionNotes = '';
    }

    public function updatedPemeriksaId(?int $value): void
    {
        $signer = $value ? User::with('leveling')->find($value) : null;
        $this->pemeriksaName = $signer?->name ?? '';
        $this->pemeriksaJabatan = $signer?->jabatan ?? '';
    }

    public function updatedFinanceSignerPreset(string $value): void
    {
        if ($value === '' || $value === 'manual') {
            return;
        }

        [$name, $jabatan] = array_pad(explode('|', $value, 2), 2, '');
        $this->proxySignerName = $name;
        $this->proxySignerJabatan = $jabatan;
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
            $this->validate([
                'pemeriksaName' => ['required', 'string', 'max:255'],
                'pemeriksaJabatan' => ['required', 'string', 'max:255'],
                'pemeriksaSignedAt' => ['required', 'date'],
            ], [
                'pemeriksaName.required' => 'Nama penanda tangan fisik pemeriksa wajib diisi.',
                'pemeriksaJabatan.required' => 'Jabatan penanda tangan fisik pemeriksa wajib diisi.',
                'pemeriksaSignedAt.required' => 'Tanggal tanda tangan fisik basah wajib diisi.',
            ]);

            $p = $this->permohonan;
            $statusSebelum = $p->status;
            $levelingUrutan = $p->levelingRequired?->urutan ?? 1;

            $workflow->approve($p, $user, [
                'catatan' => $this->actionNotes ?: null,
                'signer_user_id' => $this->pemeriksaId,
                'signer_name' => $this->pemeriksaName,
                'signer_jabatan' => $this->pemeriksaJabatan,
                'signed_at' => $this->pemeriksaSignedAt,
            ]);

            if ($statusSebelum === 'diajukan') {
                if ($levelingUrutan <= 1) {
                    $msg = 'Pemeriksaan tercatat. Permohonan diteruskan ke Kepala Divisi.';
                } else {
                    $msg = 'Pemeriksaan tercatat. Permohonan diteruskan ke Kadiv Keuangan.';
                }
            } else {
                $msg = 'Permohonan disetujui.';
            }
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

        unset($this->permohonan);

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

        $p = $this->permohonan;
        $levelingUrutan = $p->levelingRequired?->urutan ?? 1;

        $this->validate([
            'proxySignerName' => ['required', 'string', 'max:255'],
            'proxySignerJabatan' => ['required', 'string', 'max:255'],
            'proxySignedAt' => ['required', 'date'],
            'approvedItemAmounts.*' => ['required', 'integer', 'min:0'],
            'proxyScanFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'proxySignerName.required' => 'Nama penanda tangan Kepala Divisi wajib diisi.',
            'proxySignerJabatan.required' => 'Jabatan penanda tangan Kepala Divisi wajib diisi.',
            'proxySignedAt.required' => 'Tanggal tanda tangan fisik wajib diisi.',
            'approvedItemAmounts.*.required' => 'Nominal disetujui wajib diisi.',
            'proxyScanFile.required' => 'Berkas scan bukti form bertanda tangan wajib diunggah.',
            'proxyScanFile.mimes' => 'Berkas scan harus berformat PDF, JPG, atau PNG.',
            'proxyScanFile.max' => 'Ukuran berkas scan maksimal 10 MB.',
        ]);

        DB::transaction(function () use ($p, $levelingUrutan) {
            // Simpan file scan (wajib)
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

            $defaultCatatan = $levelingUrutan <= 1
                ? 'Persetujuan akhir Kepala Divisi dicatat sistem'
                : 'Persetujuan akhir Kadiv Keuangan dicatat sistem';

            // Submit approval ke workflow
            $workflow = new PermohonanWorkflow;
            $workflow->approve($p, Auth::user(), [
                'items' => $this->approvedItemAmounts,
                'signer_name' => $this->proxySignerName,
                'signer_jabatan' => $this->proxySignerJabatan,
                'catatan' => $this->proxyNotes ?: $defaultCatatan,
            ]);
        });

        unset($this->permohonan);
        $this->reset('proxyScanFile');

        foreach ($this->permohonan->items as $item) {
            $this->approvedItemAmounts[$item->id] = $item->jumlah_disetujui ?? $item->jumlah_diminta;
        }

        $msg = $levelingUrutan <= 1
            ? 'Persetujuan akhir Kepala Divisi berhasil dicatat. Dokumen berstatus DISETUJUI.'
            : 'Persetujuan akhir Kadiv Keuangan berhasil dicatat. Dana kini berstatus TERIKAT.';

        session()->flash('success', $msg);
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function submitPermohonan(): void
    {
        $workflow = new PermohonanWorkflow;
        $workflow->submit($this->permohonan);

        unset($this->permohonan);

        $msg = "Permohonan dana {$this->permohonan->no_pengajuan} berhasil diajukan.";
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
        $this->pjId = $manager?->id ?? ($this->pjCandidates()[0]['id'] ?? null);
        $this->penerimaIds = [];
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
            'penerimaIds' => ['array'],
            'penerimaIds.*' => ['integer', 'exists:users,id'],
        ], [
            'noPpd.required' => 'Nomor PPD wajib diisi.',
            'noPpd.unique' => 'Nomor PPD sudah dipakai.',
            'tglCair.required' => 'Tanggal pencairan wajib diisi.',
            'pjId.required' => 'Penanggung Jawab (PJ) UMP wajib dipilih (level Manager).',
            'penerimaIds.*.exists' => 'Penerima dana yang dipilih tidak valid.',
        ]);

        try {
            $workflow = new PermohonanWorkflow;
            $pencairan = $workflow->cairkan($this->permohonan, Auth::user(), [
                'no_ppd' => $this->noPpd,
                'tgl_cair' => $this->tglCair,
                'pj_id' => $this->pjId,
                'penerima_ids' => $this->penerimaIds,
            ]);
            $this->showCairkanModal = false;
            unset($this->permohonan);
            $msg = "Pencairan PPD {$pencairan->no_ppd} berhasil diterbitkan.";
            session()->flash('success', $msg);
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        } catch (RuntimeException $e) {
            $this->addError('noPpd', $e->getMessage());
        }
    }

    /**
     * Semua pejabat level Manager ke atas (urutan >= 2) di seluruh organisasi.
     * Urutan: manager unit permohonan, manager satu divisi, lalu sisanya; masing-masing A-Z.
     *
     * @return array<int, array{id: int, name: string, sub: string}>
     */
    private function pjCandidates(): array
    {
        $unitManager = $this->permohonan->unit?->getManager();
        $divisiId = $this->permohonan->unit?->getDivisiId();

        return User::with(['unit', 'leveling'])
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->sortBy(fn (User $u) => [
                $u->is($unitManager) ? 0 : (($divisiId && $u->unit?->getDivisiId() === $divisiId) ? 1 : 2),
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
        $divisiId = $this->permohonan->unit?->getDivisiId();
        $pjCandidates = $this->pjCandidates();

        $levelingUrutan = $this->permohonan->levelingRequired?->urutan ?? 1;
        if ($this->permohonan->status === 'diajukan' && $levelingUrutan <= 1) {
            $pemeriksaCandidates = User::where('is_active', true)
                ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
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
                    'sub' => ($u->jabatan ?: $u->leveling?->nama).($u->unit?->kode ? ' ('.$u->unit->kode.')' : ''),
                ]);
        } else {
            $pemeriksaCandidates = User::getPejabatKadiv()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'sub' => ($u->jabatan ?: $u->leveling?->nama).($u->unit?->kode ? ' ('.$u->unit->kode.')' : ''),
            ]);
        }
        if ($levelingUrutan <= 1) {
            $kadivDivisi = (new PermohonanWorkflow)->getKadivDivisi($this->permohonan);
            $finalSignerCandidates = collect();
            if ($kadivDivisi) {
                $finalSignerCandidates->push([
                    'name' => $kadivDivisi->name,
                    'jabatan' => $kadivDivisi->jabatan ?? 'Kepala Divisi',
                    'sub' => $kadivDivisi->unit?->nama ?? 'Divisi',
                ]);
            }
            $allKadivs = User::where('is_active', true)
                ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
                ->when($kadivDivisi, fn ($q) => $q->where('id', '!=', $kadivDivisi->id))
                ->orderBy('name')
                ->get();
            foreach ($allKadivs as $k) {
                $finalSignerCandidates->push([
                    'name' => $k->name,
                    'jabatan' => $k->jabatan ?? 'Kepala Divisi',
                    'sub' => $k->unit?->nama ?? 'Divisi',
                ]);
            }
        } else {
            $kadivKeu = User::where('is_active', true)
                ->where('role', 'keuangan')
                ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 3))
                ->orderBy('name')
                ->get();
            $finalSignerCandidates = $kadivKeu->map(fn (User $u) => [
                'name' => $u->name,
                'jabatan' => $u->jabatan ?? 'Kepala Divisi Keuangan',
                'sub' => 'Divisi Keuangan',
            ]);
            if ($finalSignerCandidates->isEmpty()) {
                $finalSignerCandidates = collect([
                    ['name' => 'Direktur Keuangan', 'jabatan' => 'Kepala Divisi Keuangan / Direktur Keuangan', 'sub' => 'Divisi Keuangan'],
                ]);
            }
        }

        return view('livewire.permohonan.permohonan-detail', [
            'permohonan' => $this->permohonan,
            'statusBadge' => $this->statusBadge,
            'canApprove' => $this->canApprove,
            'canCancel' => $this->canCancel,
            'canResubmit' => $this->canResubmit,
            'canCairkan' => $this->canCairkan,
            'pjCandidates' => $pjCandidates,
            'penerimaCandidates' => $this->penerimaCandidates(),
            'pemeriksaCandidates' => $pemeriksaCandidates,
            'finalSignerCandidates' => $finalSignerCandidates,
            'isFinanceStage' => $this->isFinanceStage,
            'canProxyFinanceApprove' => $this->canProxyFinanceApprove,
        ]);
    }
}
