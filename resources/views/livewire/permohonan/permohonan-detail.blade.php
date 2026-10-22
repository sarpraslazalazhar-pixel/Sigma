<div class="space-y-6">
    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('permohonan.index') }}"
                wire:navigate
                class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                title="Kembali ke daftar permohonan"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight font-mono">
                        {{ $permohonan->no_pengajuan ?: 'DRAFT PERMOHONAN' }}
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadge['class'] }}">
                        {{ $statusBadge['label'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Diajukan oleh <span class="font-semibold text-slate-700">{{ $permohonan->user?->name ?? '-' }}</span>
                    pada {{ $permohonan->submitted_at ? $permohonan->submitted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : ($permohonan->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB') }}
                </p>
            </div>
        </div>

        {{-- Action Buttons Header --}}
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Tombol Cetak PDF --}}
            <a
                href="{{ route('permohonan.cetak-pdf', $permohonan->id) }}"
                target="_blank"
                class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-1.5"
            >
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak PDF</span>
            </a>

            {{-- Resubmit jika Draft / Perlu Perbaikan --}}
            @if ($canResubmit)
                <button
                    type="button"
                    wire:click="submitPermohonan"
                    wire:loading.attr="disabled"
                    class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer m3-press inline-flex items-center gap-2 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="submitPermohonan">Ajukan Permohonan</span>
                    <span wire:loading wire:target="submitPermohonan" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Memproses...</span>
                    </span>
                </button>
            @endif

            {{-- Tombol Batal Pengaju --}}
            @if ($canCancel)
                <button
                    type="button"
                    wire:click="openAction('batal')"
                    class="min-h-[44px] px-4 py-2.5 rounded-full border border-red-200 text-xs font-semibold text-red-700 hover:bg-red-50 transition-colors cursor-pointer"
                >
                    Batalkan Pengajuan
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session()->has('success'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- OVER BUDGET ALERT BANNER --}}
    @if ($permohonan->catatan_over)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-xs space-y-1.5 shadow-2xs">
            <div class="flex items-center gap-2 font-bold text-amber-800">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Dokumen Berstatus Over Budget</span>
            </div>
            <p class="text-amber-950 font-medium pl-6">
                Catatan Alasan: "{{ $permohonan->catatan_over }}"
            </p>
        </div>
    @endif

    {{-- SECTION 1: METADATA UTAMA --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Rincian Dokumen Permohonan
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px]">Nama Kegiatan / Keperluan</span>
                <span class="font-bold text-slate-900 text-sm block mt-0.5">
                    {{ $permohonan->nama_kegiatan }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Program Utama</span>
                <span class="font-semibold text-slate-800 block mt-0.5">
                    {{ $permohonan->program }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Unit / Divisi</span>
                <span class="font-semibold text-slate-800 block mt-0.5">
                    {{ $permohonan->unit?->nama ?? '-' }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Sumber Dana</span>
                <span class="font-semibold text-slate-800 block mt-0.5">
                    {{ $permohonan->sumberDana?->nama ?? '-' }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Pos Anggaran (Mata Anggaran)</span>
                <span class="font-mono font-semibold text-slate-800 block mt-0.5">
                    {{ $permohonan->kodeAnggaran?->kode }} - {{ $permohonan->kodeAnggaran?->nama }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Jadwal Kegiatan</span>
                <span class="font-semibold text-slate-800 block mt-0.5 tabular-nums">
                    {{ $permohonan->tgl_mulai ? $permohonan->tgl_mulai->format('d/m/Y') : '-' }}
                    {{ $permohonan->tgl_selesai ? 's.d ' . $permohonan->tgl_selesai->format('d/m/Y') : '' }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Total Diminta</span>
                <span class="font-bold text-[#0F2F57] text-sm block mt-0.5 tabular-nums">
                    Rp {{ number_format($permohonan->total_diminta, 0, ',', '.') }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Total Disetujui Keuangan</span>
                <span class="font-bold text-emerald-700 text-sm block mt-0.5 tabular-nums">
                    {{ $permohonan->total_disetujui !== null ? 'Rp ' . number_format($permohonan->total_disetujui, 0, ',', '.') : 'Belum Ditentukan' }}
                </span>
            </div>
            @if ($permohonan->atasan)
                <div>
                    <span class="text-slate-400 block text-[11px]">Atasan Langsung Ditunjuk</span>
                    <span class="font-semibold text-slate-800 block mt-0.5">
                        {{ $permohonan->atasan->name }}
                        <span class="text-[11px] text-slate-500 font-normal">({{ $permohonan->atasan->jabatan ?? 'Manager' }})</span>
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- SECTION 2: STEPPER TIMELINE PERSETUJUAN M3 --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Alur & Jenjang Persetujuan (Workflow Timeline)
        </h2>

        @php
            $approvalsByTahap = $permohonan->approvals->keyBy('tahap');
            $signerPemeriksa = $approvalsByTahap->get('kadiv') ?? $approvalsByTahap->get('atasan');
            $creatorLeveling = $permohonan->user?->leveling?->nama ?? ($permohonan->user?->jabatan ?? 'Pemohon');

            $steps = [
                [
                    'key' => 'pengajuan',
                    'title' => 'Pengajuan (Dibuat)',
                    'role' => 'Pembuat: ' . ($permohonan->user?->name ?? '-') . ' (' . $creatorLeveling . ')',
                    'done' => !in_array($permohonan->status, ['draft']),
                    'date' => $permohonan->submitted_at,
                    'signer' => $permohonan->user?->name,
                    'notes' => null,
                ],
                [
                    'key' => 'kadiv',
                    'title' => 'Pemeriksaan (Kadiv)',
                    'role' => 'Pemeriksa: Kepala Divisi / Dirops',
                    'done' => in_array($permohonan->status, ['diketahui_kadiv', 'disetujui_keuangan', 'dicairkan']),
                    'date' => $signerPemeriksa?->signed_at,
                    'signer' => $signerPemeriksa?->signer_name,
                    'notes' => $signerPemeriksa?->catatan,
                ],
                [
                    'key' => 'keuangan',
                    'title' => 'Persetujuan (Keuangan)',
                    'role' => 'Penyetuju: Bagian Keuangan / Treasury',
                    'done' => in_array($permohonan->status, ['disetujui_keuangan', 'dicairkan']),
                    'date' => $approvalsByTahap->get('keuangan')?->signed_at,
                    'signer' => $approvalsByTahap->get('keuangan')?->signer_name,
                    'notes' => $approvalsByTahap->get('keuangan')?->catatan,
                ],
            ];

            if ($permohonan->status === 'dicairkan' || $permohonan->pencairan) {
                $steps[] = [
                    'key' => 'pencairan',
                    'title' => 'Pencairan PPD',
                    'role' => 'PJ: ' . ($permohonan->pencairan?->ump?->pj?->name ?? 'Manager Divisi'),
                    'done' => true,
                    'date' => $permohonan->pencairan?->tgl_cair,
                    'signer' => $permohonan->pencairan?->no_ppd,
                    'notes' => null,
                ];
            }
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ count($steps) }} gap-3">
            @foreach ($steps as $idx => $step)
                <div class="rounded-xl p-3 border {{ $step['done'] ? 'bg-emerald-50/60 border-emerald-200' : 'bg-slate-50 border-slate-200' }} space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800">
                            Tahap {{ $idx + 1 }}: {{ $step['title'] }}
                        </span>
                        @if ($step['done'])
                            <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        @else
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 font-bold text-[10px]">
                                {{ $idx + 1 }}
                            </span>
                        @endif
                    </div>

                    <p class="text-[11px] text-slate-500">
                        {{ $step['role'] }}
                    </p>

                    @if ($step['done'] && isset($step['signer']))
                        <div class="pt-1 border-t border-emerald-100 text-[11px] text-emerald-900">
                            <span class="font-semibold block truncate">{{ $step['signer'] }}</span>
                            @if ($step['date'])
                                <span class="text-slate-500 text-[10px]">
                                    {{ \Carbon\Carbon::parse($step['date'])->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if (!empty($step['notes']))
                        <p class="text-[10px] text-slate-600 italic bg-white/70 p-1.5 rounded border border-slate-200">
                            "{{ $step['notes'] }}"
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- SECTION 3: TABEL RINCIAN ITEM PENGELUARAN --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Rincian Item Anggaran ({{ $permohonan->items->count() }} Baris)
            </h2>
            <span class="font-bold text-sm text-[#0F2F57]">
                Total: Rp {{ number_format($permohonan->total_diminta, 0, ',', '.') }}
            </span>
        </div>

        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4 w-12 text-center">No</th>
                    <th class="py-3 px-4">Uraian Kebutuhan</th>
                    <th class="py-3 px-4">Asnaf</th>
                    <th class="py-3 px-4 text-right">Jumlah Diminta</th>
                    <th class="py-3 px-4 text-right">Jumlah Disetujui</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-sans">
                @foreach ($permohonan->items as $idx => $item)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 text-center font-bold text-slate-400">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $item->uraian }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ $item->asnaf?->nama ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 tabular-nums">
                            Rp {{ number_format($item->jumlah_diminta, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 tabular-nums">
                            {{ $item->jumlah_disetujui !== null ? 'Rp ' . number_format($item->jumlah_disetujui, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                <tr>
                    <td colspan="3" class="py-3 px-4 text-right uppercase text-slate-600">
                        Total Permohonan
                    </td>
                    <td class="py-3 px-4 text-right font-mono text-slate-900 text-sm">
                        Rp {{ number_format($permohonan->total_diminta, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-4 text-right font-mono text-emerald-800 text-sm">
                        {{ $permohonan->total_disetujui !== null ? 'Rp ' . number_format($permohonan->total_disetujui, 0, ',', '.') : '-' }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- SECTION 4: LAMPIRAN & BERKAS SCAN --}}
    @if ($permohonan->lampiran->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-100">
                Berkas Lampiran & Dokumen Scan Fisik
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($permohonan->lampiran as $lamp)
                    <div class="p-3 rounded-xl border border-slate-200 flex items-center justify-between gap-3 text-xs bg-slate-50">
                        <div class="min-w-0">
                            <span class="font-bold text-slate-800 block truncate">
                                {{ $lamp->original_name }}
                            </span>
                            <span class="text-[10px] text-slate-500">
                                {{ ucfirst($lamp->jenis) }} | Diunggah oleh {{ $lamp->uploadedBy?->name ?? 'User' }}
                            </span>
                        </div>
                        <a
                            href="{{ asset('storage/' . $lamp->path) }}"
                            target="_blank"
                            class="min-h-[36px] px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold inline-flex items-center gap-1 shrink-0"
                        >
                            Lihat Berkas
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- SECTION 5: PANEL AKSI PEMERIKSAAN KADIV (Tahap 2) --}}
    @if ($canApprove && !$isFinanceStage)
        <div class="bg-white rounded-2xl border-2 border-blue-200 p-5 shadow-xs space-y-3 bg-blue-50/20">
            <div class="flex items-center justify-between pb-2 border-b border-blue-100">
                <div>
                    <h2 class="text-sm font-bold text-[#0F2F57] uppercase tracking-wider">
                        Panel Aksi Pemeriksaan Dokumen (Kepala Divisi / Dirops)
                    </h2>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Sebagai Kepala Divisi (Pemeriksa Dokumen), Anda dapat menyetujui dokumen ini agar diteruskan ke Bagian Keuangan, atau meminta perbaikan jika terdapat rincian yang perlu direvisi.
                    </p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                    Tahap 2: Pemeriksaan Kadiv
                </span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap pt-1">
                <button
                    type="button"
                    wire:click="openAction('setuju')"
                    class="min-h-[44px] px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                >
                    Setujui Dokumen (Teruskan ke Keuangan)
                </button>
                <button
                    type="button"
                    wire:click="openAction('perbaikan')"
                    class="min-h-[44px] px-4 py-2.5 rounded-full bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                >
                    Minta Perbaikan
                </button>
                <button
                    type="button"
                    wire:click="openAction('tolak')"
                    class="min-h-[44px] px-4 py-2.5 rounded-full bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                >
                    Tolak Pengajuan
                </button>
            </div>
        </div>
    @endif

    {{-- SECTION 6: MODE INPUT ATAS NAMA KEUANGAN (FR-PRX) --}}
    @if ($canProxyFinanceApprove)
        <div class="bg-white rounded-2xl border-2 border-blue-200 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-blue-100">
                <div>
                    <h2 class="text-sm font-bold text-[#0F2F57] uppercase tracking-wider">
                        Persetujuan Bagian Keuangan (Pencatatan Fisik / Input Terpusat)
                    </h2>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Masukkan nominal disetujui per baris, nama penanda tangan fisik Keuangan, dan unggah berkas scan form bertanda tangan.
                    </p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                    FR-PRX Keuangan
                </span>
            </div>

            <form wire:submit="submitKeuanganApproval" class="space-y-4">
                {{-- Penyesuaian Nominal Per Item --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-800">
                        Nominal Disetujui per Baris Rincian (Maksimal sebesar jumlah diminta):
                    </label>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">Uraian</th>
                                    <th class="py-2.5 px-3 text-right">Jumlah Diminta</th>
                                    <th class="py-2.5 px-3 w-48 text-right">Jumlah Disetujui (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($permohonan->items as $item)
                                    <tr>
                                        <td class="py-2.5 px-3 font-medium text-slate-800">
                                            {{ $item->uraian }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono tabular-nums text-slate-600">
                                            Rp {{ number_format($item->jumlah_diminta, 0, ',', '.') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <div
                                                x-data="{
                                                    display: '',
                                                    init() {
                                                        let val = $wire.get('approvedItemAmounts.{{ $item->id }}');
                                                        if (val || val === 0) this.display = new Intl.NumberFormat('id-ID').format(val.toString().replace(/\D/g, ''));
                                                        $watch('$wire.approvedItemAmounts.{{ $item->id }}', (v) => {
                                                            if (!v && v !== 0) { this.display = ''; return; }
                                                            this.display = new Intl.NumberFormat('id-ID').format(v.toString().replace(/\D/g, ''));
                                                        });
                                                    },
                                                    onInput(e) {
                                                        let clean = e.target.value.replace(/\D/g, '');
                                                        this.display = clean ? new Intl.NumberFormat('id-ID').format(clean) : '';
                                                        e.target.value = this.display;
                                                        $wire.set('approvedItemAmounts.{{ $item->id }}', clean ? parseInt(clean, 10) : 0);
                                                    }
                                                }"
                                            >
                                                <input
                                                    type="text"
                                                    x-model="display"
                                                    @input="onInput($event)"
                                                    inputmode="numeric"
                                                    placeholder="0"
                                                    class="w-full min-h-[36px] rounded-lg border border-slate-300 text-xs font-bold text-slate-900 px-2 py-1 text-right tabular-nums focus:border-[#1E60D5] focus:outline-hidden"
                                                    required
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Signer Info & Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="proxySignerName" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Penanda Tangan Keuangan *
                        </label>
                        <input
                            type="text"
                            id="proxySignerName"
                            wire:model="proxySignerName"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            required
                        />
                        @error('proxySignerName')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="proxySignerJabatan" class="block text-xs font-semibold text-slate-700 mb-1">
                            Jabatan Penanda Tangan *
                        </label>
                        <input
                            type="text"
                            id="proxySignerJabatan"
                            wire:model="proxySignerJabatan"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            required
                        />
                        @error('proxySignerJabatan')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="proxySignedAt" class="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Tanda Tangan Fisik *
                        </label>
                        <input
                            type="date"
                            id="proxySignedAt"
                            wire:model="proxySignedAt"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            required
                        />
                        @error('proxySignedAt')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Upload Scan Dokumen Fisik --}}
                <div>
                    <label for="proxyScanFile" class="block text-xs font-semibold text-slate-700 mb-1">
                        Unggah Scan Dokumen Fisik Bertanda Tangan (PDF / Gambar)
                    </label>
                    <input
                        type="file"
                        id="proxyScanFile"
                        wire:model="proxyScanFile"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200"
                    />
                    <p class="text-[11px] text-slate-400 mt-1">
                        Format didukung: PDF, JPG, PNG (Maksimal 10 MB).
                    </p>
                    @error('proxyScanFile')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Catatan Opsional --}}
                <div>
                    <label for="proxyNotes" class="block text-xs font-semibold text-slate-700 mb-1">
                        Catatan Tambahan Keuangan
                    </label>
                    <textarea
                        id="proxyNotes"
                        wire:model="proxyNotes"
                        rows="2"
                        placeholder="Catatan verifikasi atau penyesuaian nominal anggaran..."
                        class="w-full rounded-xl border border-slate-300 text-xs text-slate-800 p-2.5 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                    ></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="openAction('perbaikan')"
                        class="min-h-[44px] px-4 py-2.5 rounded-full border border-amber-300 text-xs font-semibold text-amber-800 hover:bg-amber-50 transition-colors"
                    >
                        Minta Perbaikan
                    </button>
                    <button
                        type="submit"
                        class="min-h-[44px] px-6 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                    >
                        Setujui Atas Nama Keuangan (Ikat Dana)
                    </button>
                </div>
            </form>
        </div>
    @endif
    {{-- SECTION 6B: PENCAIRAN DANA PPD (BAGIAN KEUANGAN) --}}
    @if ($canCairkan)
        <div class="bg-white rounded-2xl border-2 border-emerald-300 p-5 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-emerald-50/20">
            <div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 mb-1">
                    Tahap Pencairan PPD
                </span>
                <h2 class="text-sm font-bold text-slate-900">
                    Permohonan Telah Disetujui Keuangan
                </h2>
                <p class="text-xs text-slate-600 mt-0.5">
                    Total dana disetujui: <strong class="text-emerald-700">Rp {{ number_format($permohonan->total_disetujui, 0, ',', '.') }}</strong>. Anda dapat menerbitkan berkas PPD dan menugaskan Penanggung Jawab (PJ) Manajer sekarang.
                </p>
            </div>
            <button
                type="button"
                wire:click="openCairkanModal"
                class="min-h-[44px] px-6 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
            >
                Cairkan Dana (Terbitkan PPD)
            </button>
        </div>
    @endif

    {{-- SECTION 7: INFORMASI PENCAIRAN PPD & UMP --}}
    @if ($permohonan->pencairan)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-2.5 py-0.5 rounded-full">
                        Dokumen Telah Dicairkan
                    </span>
                    <h2 class="text-base font-bold text-slate-900 mt-1 font-mono">
                        {{ $permohonan->pencairan->no_ppd }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('pencairan.cetak-pdf', $permohonan->pencairan->id) }}"
                        target="_blank"
                        class="min-h-[40px] px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Cetak Bukti PPD (PDF)</span>
                    </a>
                    @if ($permohonan->pencairan->ump)
                        <a
                            href="{{ route('ump.detail', $permohonan->pencairan->ump->id) }}"
                            wire:navigate
                            class="min-h-[40px] px-4 py-2 rounded-xl bg-[#0F2F57] hover:bg-[#1E60D5] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5"
                        >
                            <span>Lihat UMP</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div>
                    <span class="text-slate-400 block text-[11px]">Tanggal Pencairan</span>
                    <span class="font-bold text-slate-800 block mt-0.5">{{ $permohonan->pencairan->tgl_cair->format('d/m/Y') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Nominal Dicairkan</span>
                    <span class="font-bold text-emerald-700 block mt-0.5">Rp {{ number_format($permohonan->pencairan->jumlah, 0, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Penanggung Jawab (PJ)</span>
                    <span class="font-bold text-slate-800 block mt-0.5">
                        {{ $permohonan->pencairan->ump?->pj?->name ?? ($permohonan->user?->name ?? '-') }}
                        <span class="text-[11px] text-slate-500 font-normal">({{ $permohonan->pencairan->ump?->pj?->jabatan ?? 'Manager' }})</span>
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Status PPD</span>
                    <span class="font-semibold text-slate-800 block mt-0.5">{{ ucfirst($permohonan->pencairan->status) }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PENCAIRAN DANA PPD --}}
    @if ($showCairkanModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-300 ease-m3-standard"
            x-transition:enter="transition-opacity duration-300 ease-m3-standard"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-m3-standard"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden transition-all duration-300 ease-m3-emphasized-decel"
                x-transition:enter="transition-all duration-300 ease-m3-emphasized-decel"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition-all duration-200 ease-m3-emphasized-accel"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                @click.outside="closeCairkanModal"
            >
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-[#0F2F57]">
                            Pencairan Permohonan Dana (PPD)
                        </h2>
                        <p class="text-xs text-slate-500">Penerbitan nomor PPD &amp; penunjukan Penanggung Jawab (PJ Manajer)</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeCairkanModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="submitCairkan" class="p-6 space-y-4">
                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-xs text-slate-600">
                        <span class="font-semibold text-slate-800">Total Nominal Disetujui:</span>
                        <span class="font-bold text-emerald-700 ml-1">Rp {{ number_format($permohonan->total_disetujui, 0, ',', '.') }}</span>
                    </div>

                    <div>
                        <label for="noPpd" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nomor PPD *
                        </label>
                        <input
                            type="text"
                            id="noPpd"
                            wire:model="noPpd"
                            placeholder="Contoh: PPD/202610/0001"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('noPpd') border-red-500 @enderror"
                            required
                        />
                        @error('noPpd')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tglCair" class="block text-xs font-semibold text-slate-700 mb-1">
                            Tanggal Pencairan *
                        </label>
                        <input
                            type="date"
                            id="tglCair"
                            wire:model="tglCair"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('tglCair') border-red-500 @enderror"
                            required
                        />
                        @error('tglCair')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pjId" class="block text-xs font-semibold text-slate-700 mb-1">
                            Penanggung Jawab (PJ) PPD &amp; UMP *
                        </label>
                        <x-searchable-select
                            wire:model="pjId"
                            :options="$pjCandidates"
                            placeholder="Pilih Penanggung Jawab (Manager)..."
                            search-placeholder="Cari nama manager atau jabatan..."
                            :nullable="false"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">
                            Sesuai ketentuan, Penanggung Jawab dana pencairan PPD adalah Manager divisi / unit terkait.
                        </p>
                        @error('pjId')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="closeCairkanModal"
                            class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="min-h-[44px] px-6 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-all shadow-xs cursor-pointer m3-press inline-flex items-center gap-2 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="submitCairkan">Terbitkan PPD &amp; Buat UMP</span>
                            <span wire:loading wire:target="submitCairkan" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Menerbitkan PPD...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL AKSI KONFIRMASI (Setuju, Perbaikan, Tolak, Batal) --}}
    @if ($showActionModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-300 ease-m3-standard"
            x-transition:enter="transition-opacity duration-300 ease-m3-standard"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-m3-standard"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-md w-full overflow-hidden transition-all duration-300 ease-m3-emphasized-decel"
                x-transition:enter="transition-all duration-300 ease-m3-emphasized-decel"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition-all duration-200 ease-m3-emphasized-accel"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                @click.outside="closeActionModal"
            >
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-[#0F2F57]">
                        @if ($actionType === 'setuju')
                            Konfirmasi Persetujuan Dokumen
                        @elseif ($actionType === 'perbaikan')
                            Minta Perbaikan Dokumen
                        @elseif ($actionType === 'tolak')
                            Konfirmasi Penolakan Pengajuan
                        @elseif ($actionType === 'batal')
                            Konfirmasi Pembatalan Pengajuan
                        @endif
                    </h2>
                    <button
                        type="button"
                        wire:click="closeActionModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="submitAction" class="p-6 space-y-4">
                    <p class="text-xs text-slate-600">
                        @if ($actionType === 'setuju')
                            Apakah Anda yakin menyetujui permohonan dana ini untuk diteruskan ke tahap selanjutnya?
                        @elseif ($actionType === 'perbaikan')
                            Tuliskan catatan detail bagian mana yang harus diperbaiki oleh staf pengaju:
                        @elseif ($actionType === 'tolak')
                            Tuliskan alasan penolakan permohonan dana ini secara jelas:
                        @elseif ($actionType === 'batal')
                            Apakah Anda yakin membatalkan permohonan ini? Dokumen yang dibatalkan tidak dapat diproses lebih lanjut.
                        @endif
                    </p>

                    <div>
                        <label for="actionNotes" class="block text-xs font-semibold text-slate-700 mb-1">
                            Catatan / Alasan {{ $actionType === 'setuju' ? '(Opsional)' : '*' }}
                        </label>
                        <textarea
                            id="actionNotes"
                            wire:model="actionNotes"
                            rows="3"
                            placeholder="Tuliskan catatan di sini..."
                            class="w-full rounded-xl border border-slate-300 text-xs text-slate-800 p-2.5 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('actionNotes') border-red-500 @enderror"
                        ></textarea>
                        @error('actionNotes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="closeActionModal"
                            class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="min-h-[44px] px-5 py-2.5 rounded-full text-white text-xs font-semibold transition-all shadow-xs cursor-pointer m3-press inline-flex items-center gap-2 disabled:opacity-50 {{ $actionType === 'setuju' ? 'bg-emerald-600 hover:bg-emerald-700' : ($actionType === 'perbaikan' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-red-600 hover:bg-red-700') }}"
                        >
                            <span wire:loading.remove wire:target="submitAction">Konfirmasi</span>
                            <span wire:loading wire:target="submitAction" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
