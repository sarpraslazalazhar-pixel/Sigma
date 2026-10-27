<div class="space-y-6">
    {{-- Top Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a
                href="{{ route('ump.index') }}"
                wire:navigate
                class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors shrink-0"
                title="Kembali ke daftar UMP"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight font-mono">
                        {{ $ump->pencairan?->no_ppd ?? 'UMP-' . $ump->id }}
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $this->getStatusBadge($ump->status)['class'] }}">
                        {{ $this->getStatusBadge($ump->status)['label'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Diterima {{ $ump->tgl_terima?->format('d/m/Y') }} oleh <span class="font-semibold text-slate-700">{{ $ump->pj?->name ?? '-' }}</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a
                href="{{ route('ump.cetak-pdf', $ump->id) }}"
                target="_blank"
                class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-1.5"
            >
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak PDF UMP</span>
            </a>
            <a
                href="{{ route('ump.index') }}"
                wire:navigate
                class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
            >
                Kembali ke /ump
            </a>
            @if ($ump->pencairan)
                <a
                    href="{{ route('permohonan.detail', $ump->pencairan->permohonan_id) }}"
                    wire:navigate
                    class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                >
                    Lihat Permohonan
                </a>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if (session()->has('success'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- PERLU PERBAIKAN ALERT BANNER UMP --}}
    @if ($ump->status === 'perlu_perbaikan')
        @php
            $latestRevision = $ump->approvals->where('keputusan', 'perlu_perbaikan')->sortByDesc('id')->first();
        @endphp
        <div class="p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-950 text-xs space-y-2.5 shadow-xs">
            <div class="flex items-center gap-2.5 font-bold text-amber-900 text-sm">
                <span class="w-8 h-8 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <span>Dokumen UMP Memerlukan Perbaikan</span>
            </div>

            <div class="bg-white/90 rounded-xl p-3.5 border border-amber-200 space-y-1">
                <p class="text-[11px] font-semibold text-amber-800">
                    Catatan Perbaikan dari: {{ $latestRevision?->signer_name ?? 'Atasan / Keuangan' }} ({{ $latestRevision?->signer_jabatan ?? 'Approver' }})
                    @if ($latestRevision?->signed_at)
                        • {{ $latestRevision->signed_at->format('d/m/Y H:i') }}
                    @endif
                </p>
                <p class="font-medium text-slate-800 text-xs italic">
                    "{{ $latestRevision?->catatan ?: 'Harap periksa dan sesuaikan kembali bukti atau rincian UMP Anda.' }}"
                </p>
            </div>
            <p class="text-[11px] text-amber-900 leading-relaxed">
                <strong>Cara Memperbaiki:</strong> Anda dapat langsung mengubah rincian tanggal, rincian biaya, no bukti nota, atau nominal pada <strong>Bagian B: Penggunaan Dana</strong> di bawah. Setelah perbaikan selesai, klik tombol <strong>Ajukan UMP</strong> di bagian bawah form untuk mengirimkan ulang ke verifikator.
            </p>
        </div>
    @endif
    {{-- Header UMP: No PPD, Tgl Terima, Tujuan, PJ, Jatuh Tempo & Umur --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Informasi UMP (Form Fisik Al-Azhar)
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px]">Nomor PPD</span>
                <span class="font-mono font-bold text-slate-900 text-sm block mt-0.5">
                    {{ $ump->pencairan?->no_ppd ?? '-' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Tanggal Penerimaan Dana</span>
                <span class="font-semibold text-slate-800 block mt-0.5 tabular-nums">
                    {{ $ump->tgl_terima?->format('d/m/Y') ?? '-' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Tujuan Pengajuan</span>
                <span class="font-semibold text-slate-800 block mt-0.5">{{ $ump->tujuan }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Penanggung Jawab</span>
                <span class="font-semibold text-slate-800 block mt-0.5">
                    {{ $ump->pj?->name ?? '-' }}
                    <span class="text-[11px] text-slate-500 font-normal">({{ $ump->pj?->jabatan ?? '-' }})</span>
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Jatuh Tempo</span>
                <span class="font-semibold text-slate-800 block mt-0.5 tabular-nums">
                    {{ $ump->jatuh_tempo ? $ump->jatuh_tempo->format('d/m/Y') : 'Tidak ada tenggat' }}
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Umur Pengajuan</span>
                <span class="font-semibold {{ $ump->jatuh_tempo && $ump->status !== 'selesai' && now()->gt($ump->jatuh_tempo) ? 'text-red-600' : 'text-slate-800' }} block mt-0.5 tabular-nums">
                    {{ $ump->tgl_terima ? abs((int) now()->diffInDays($ump->tgl_terima)) . ' hari sejak diterima' : '-' }}
                    @if ($ump->jatuh_tempo && $ump->status !== 'selesai' && now()->gt($ump->jatuh_tempo))
                        <span class="text-[11px] font-bold">(Lewat tenggat)</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- BAGIAN A: JUMLAH DITERIMA --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Bagian A: Jumlah Diterima
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <span class="text-slate-400 block text-[11px]">Jumlah Dana Diterima (readonly)</span>
                <input
                    type="text"
                    readonly
                    value="Rp {{ number_format($ump->jumlah_total, 0, ',', '.') }}"
                    class="w-full mt-1 min-h-[44px] rounded-xl border border-slate-200 bg-white text-base font-bold text-[#0F2F57] px-3 py-2 tabular-nums cursor-not-allowed"
                />
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <span class="text-slate-400 block text-[11px]">Status Verifikasi Keuangan</span>
                <span class="font-semibold text-slate-800 block mt-2">
                    {{ $ump->perlu_verifikasi_keuangan ? 'Perlu verifikasi ulang oleh Keuangan' : 'Tidak perlu verifikasi ulang' }}
                </span>
            </div>
        </div>
    </div>

    {{-- BAGIAN B: PENGGUNAAN DANA --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
        <div class="pb-2 border-b border-slate-100">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Bagian B: Penggunaan Dana
            </h2>
            <p class="text-[11px] text-slate-400 mt-1">
                Setiap penerima dana mengisi baris pengeluarannya masing-masing; bukti transfer/transaksi diunggah per baris.
            </p>
        </div>

        @php
            $currentUser = auth()->user();
            $canManagePenerima = $isEditable && $currentUser && (
                $ump->pj_id === $currentUser->id
                || $currentUser->is_petugas_input
                || $currentUser->role === 'admin'
                || $currentUser->isKeuangan()
            );
        @endphp

        @if ($canManagePenerima)
            <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4 space-y-3">
                <div>
                    <span class="text-xs font-bold text-[#0F2F57]">Penerima Dana UMP</span>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Pilih siapa saja yang menerima bagian dana dari PPD ini; masing-masing dapat mengisi Penggunaan Dana secara mandiri.
                    </p>
                </div>
                <div x-data="{ search: '' }" class="space-y-2">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Cari nama atau jabatan..."
                        class="w-full min-h-[40px] rounded-xl border border-slate-300 text-xs px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                    />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-60 overflow-y-auto pr-1">
                        @forelse ($penerimaCandidates as $cand)
                            <label
                                x-show="!search || @js(strtolower($cand['name'].' '.$cand['sub'])).includes(search.toLowerCase())"
                                class="flex items-start gap-2.5 px-2.5 py-2 rounded-lg bg-white border border-slate-200 cursor-pointer hover:border-[#1E60D5] transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    wire:model="selectedPenerimaIds"
                                    value="{{ $cand['id'] }}"
                                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#1E60D5] focus:ring-[#1E60D5]"
                                />
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs font-medium text-slate-800 truncate">{{ $cand['name'] }}</span>
                                    @if ($cand['sub'])
                                        <span class="block text-[11px] text-slate-500 truncate">{{ $cand['sub'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <div class="col-span-full py-3 text-center text-xs text-slate-400">
                                Tidak ada pengguna pada divisi ini.
                            </div>
                        @endforelse
                    </div>
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <span class="text-[11px] text-slate-500">{{ count($selectedPenerimaIds) }} penerima dipilih</span>
                        <button
                            type="button"
                            wire:click="syncPenerima"
                            wire:loading.attr="disabled"
                            class="min-h-[40px] px-4 py-2 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer disabled:opacity-50"
                        >
                            Simpan Penerima
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @php
            $sections = collect([
                ['key' => 'pj', 'label' => $ump->pj?->name ?? 'Penanggung Jawab', 'sub' => 'Penanggung Jawab UMP'],
            ]);
            foreach ($ump->penerima->sortBy(fn ($penerimaRow) => $penerimaRow->user?->name ?? '') as $penerimaRow) {
                $sections->push([
                    'key' => (string) $penerimaRow->id,
                    'label' => $penerimaRow->user?->name ?? 'Penerima',
                    'sub' => $penerimaRow->user?->jabatan ?? 'Penerima Dana',
                ]);
            }
        @endphp

        @foreach ($sections as $section)
            @php
                $rows = $itemsByPenerima[$section['key']] ?? [];
                $sectionEditable = $this->canEditSection($section['key']);
                $sectionTotal = (int) collect($rows)->sum('jumlah');
            @endphp
            <div class="rounded-2xl border border-slate-200" wire:key="ump-section-{{ $section['key'] }}">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3 bg-slate-50 border-b border-slate-100 rounded-t-2xl">
                    <div class="min-w-0">
                        <span class="text-xs font-bold text-slate-800">{{ $section['label'] }}</span>
                        <span class="text-[11px] text-slate-500 ml-1.5">{{ $section['sub'] }}</span>
                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-600">{{ count($rows) }} baris</span>
                    </div>
                    @if ($sectionEditable)
                        <button
                            type="button"
                            wire:click="tambahBaris('{{ $section['key'] }}')"
                            class="min-h-[38px] px-3.5 py-1.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer inline-flex items-center gap-1.5 shrink-0"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Baris</span>
                        </button>
                    @endif
                </div>

                {{-- Mobile cards --}}
                <div class="block lg:hidden space-y-3 p-3">
                    @forelse ($rows as $i => $row)
                        @php $itemModel = $row['id'] ? $ump->items->firstWhere('id', $row['id']) : null; @endphp
                        <div class="rounded-2xl border border-slate-200 p-4 space-y-3 bg-slate-50/60" wire:key="m-{{ $section['key'] }}-{{ $i }}-{{ $row['id'] ?? 'new' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-500 uppercase">Baris {{ $i + 1 }}</span>
                                @if ($sectionEditable)
                                    <div class="inline-flex items-center gap-1.5">
                                        @if (empty($row['id']))
                                            <button
                                                type="button"
                                                wire:click="simpanDraft"
                                                wire:loading.attr="disabled"
                                                title="Simpan baris (Draft)"
                                                aria-label="Simpan baris"
                                                class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 flex items-center justify-center transition-colors cursor-pointer disabled:opacity-50"
                                            >
                                                <svg wire:loading.remove wire:target="simpanDraft" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <svg wire:loading wire:target="simpanDraft" class="animate-spin w-3.5 h-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            wire:click="hapusBaris('{{ $section['key'] }}', {{ $i }})"
                                            wire:loading.attr="disabled"
                                            title="Hapus baris"
                                            aria-label="Hapus baris"
                                            class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 flex items-center justify-center transition-colors cursor-pointer disabled:opacity-50"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pengeluaran *</label>
                                <input type="date" wire:model="itemsByPenerima.{{ $section['key'] }}.{{ $i }}.tgl" {{ $sectionEditable ? '' : 'disabled' }}
                                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs px-3 py-2 bg-white @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.tgl') border-red-500 @enderror" />
                                @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.tgl') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Rincian Pengeluaran *</label>
                                <input type="text" wire:model="itemsByPenerima.{{ $section['key'] }}.{{ $i }}.rincian" placeholder="Contoh: Transport tim ke lokasi" {{ $sectionEditable ? '' : 'disabled' }}
                                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs px-3 py-2 bg-white @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.rincian') border-red-500 @enderror" />
                                @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.rincian') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">No Bukti (transfer, nota, kuitansi)</label>
                                <input type="text" wire:model="itemsByPenerima.{{ $section['key'] }}.{{ $i }}.no_bukti" placeholder="Opsional" {{ $sectionEditable ? '' : 'disabled' }}
                                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs px-3 py-2 bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah (Rp) *</label>
                                <input type="text" inputmode="numeric" wire:ignore
                                    value="{{ number_format((int) ($row['jumlah'] ?? 0), 0, ',', '.') }}"
                                    {{ $sectionEditable ? '' : 'disabled' }}
                                    x-on:input="
                                        const raw = $event.target.value.replace(/\D/g, '');
                                        $event.target.value = raw ? Number(raw).toLocaleString('id-ID') : '';
                                        $wire.set('itemsByPenerima.{{ $section['key'] }}.{{ $i }}.jumlah', raw === '' ? 0 : Number(raw));
                                    "
                                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs px-3 py-2 bg-white text-right tabular-nums font-semibold @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.jumlah') border-red-500 @enderror" />
                                @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.jumlah') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-slate-700 mb-1">Bukti Transfer / Transaksi</span>
                                @include('livewire.ump.partials.item-bukti', ['section' => $section, 'row' => $row, 'itemModel' => $itemModel, 'sectionEditable' => $sectionEditable])
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
                            Belum ada baris pengeluaran{{ $sectionEditable ? '. Klik "Tambah Baris".' : '.' }}
                        </div>
                    @endforelse
                </div>

                {{-- Desktop table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3 w-12">No</th>
                                <th class="py-3 px-3">Tanggal Pengeluaran</th>
                                <th class="py-3 px-3">Rincian Pengeluaran</th>
                                <th class="py-3 px-3">No Bukti</th>
                                <th class="py-3 px-3 text-right">Jumlah</th>
                                <th class="py-3 px-3 w-44">Bukti Transaksi</th>
                                <th class="py-3 px-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $i => $row)
                                @php $itemModel = $row['id'] ? $ump->items->firstWhere('id', $row['id']) : null; @endphp
                                <tr class="align-top" wire:key="d-{{ $section['key'] }}-{{ $i }}-{{ $row['id'] ?? 'new' }}">
                                    <td class="py-3 px-3 font-bold text-slate-500">{{ $i + 1 }}</td>
                                    <td class="py-3 px-3">
                                        <input type="date" wire:model="itemsByPenerima.{{ $section['key'] }}.{{ $i }}.tgl" {{ $sectionEditable ? '' : 'disabled' }}
                                            class="w-full min-h-[40px] rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 bg-white @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.tgl') border-red-500 @enderror" />
                                        @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.tgl') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" wire:model="itemsByPenerima.{{ $section['key'] }}.{{ $i }}.rincian" placeholder="Rincian pengeluaran" {{ $sectionEditable ? '' : 'disabled' }}
                                            class="w-full min-h-[40px] rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 bg-white @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.rincian') border-red-500 @enderror" />
                                        @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.rincian') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" wire:model="itemsByPenerima.{{ $section['key'] }}.{{ $i }}.no_bukti" placeholder="transfer / nota / kuitansi" {{ $sectionEditable ? '' : 'disabled' }}
                                            class="w-full min-h-[40px] rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 bg-white" />
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" inputmode="numeric" wire:ignore
                                            value="{{ number_format((int) ($row['jumlah'] ?? 0), 0, ',', '.') }}"
                                            {{ $sectionEditable ? '' : 'disabled' }}
                                            x-on:input="
                                                const raw = $event.target.value.replace(/\D/g, '');
                                                $event.target.value = raw ? Number(raw).toLocaleString('id-ID') : '';
                                                $wire.set('itemsByPenerima.{{ $section['key'] }}.{{ $i }}.jumlah', raw === '' ? 0 : Number(raw));
                                            "
                                            class="w-full min-h-[40px] rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 bg-white text-right tabular-nums font-semibold @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.jumlah') border-red-500 @enderror" />
                                        @error('itemsByPenerima.' . $section['key'] . '.' . $i . '.jumlah') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="py-3 px-3">
                                        @include('livewire.ump.partials.item-bukti', ['section' => $section, 'row' => $row, 'itemModel' => $itemModel, 'sectionEditable' => $sectionEditable])
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @if ($sectionEditable)
                                            <div class="inline-flex items-center justify-center gap-1.5">
                                                @if (empty($row['id']))
                                                    <button
                                                        type="button"
                                                        wire:click="simpanDraft"
                                                        wire:loading.attr="disabled"
                                                        title="Simpan baris (Draft)"
                                                        aria-label="Simpan baris"
                                                        class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 flex items-center justify-center transition-colors cursor-pointer disabled:opacity-50"
                                                    >
                                                        <svg wire:loading.remove wire:target="simpanDraft" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <svg wire:loading wire:target="simpanDraft" class="animate-spin w-3.5 h-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                                <button
                                                    type="button"
                                                    wire:click="hapusBaris('{{ $section['key'] }}', {{ $i }})"
                                                    wire:loading.attr="disabled"
                                                    title="Hapus baris"
                                                    aria-label="Hapus baris"
                                                    class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 flex items-center justify-center transition-colors cursor-pointer disabled:opacity-50"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-slate-500 text-xs">
                                        Belum ada baris pengeluaran{{ $sectionEditable ? '. Klik "Tambah Baris".' : '.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 border-t-2 border-slate-300">
                                <td colspan="4" class="py-3 px-3 text-right font-bold text-slate-700 uppercase">
                                    Subtotal {{ $section['label'] }}:
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-[#0F2F57] tabular-nums">
                                    Rp {{ number_format($sectionTotal, 0, ',', '.') }}
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 border border-slate-200 px-4 py-3 text-xs">
            <span class="font-bold text-slate-700 uppercase">Total Penggunaan Dana</span>
            <span class="font-bold text-[#0F2F57] tabular-nums">Rp {{ number_format($totalPenggunaan, 0, ',', '.') }}</span>
        </div>

        {{-- Lampiran bukti pendukung UMP --}}
        <div class="pt-3 border-t border-slate-100 space-y-3">
            <span class="text-xs font-semibold text-slate-700">Bukti Pendukung UMP (JPG / PNG / PDF, maks 5 MB)</span>
            <div class="flex flex-col sm:flex-row gap-2.5 sm:items-center">
                <input type="file" wire:model="buktiPendukung" accept=".jpg,.jpeg,.png,.pdf" {{ $isEditable ? '' : 'disabled' }}
                    class="text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200" />
                @if ($isEditable)
                    <button type="button" wire:click="simpanBuktiPendukung"
                        class="min-h-[40px] px-4 py-2 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                        Unggah Bukti
                    </button>
                @endif
            </div>
            <div wire:loading wire:target="buktiPendukung" class="text-[11px] text-slate-500">Mengunggah berkas...</div>
            @error('buktiPendukung') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @if ($ump->lampiran->isNotEmpty())
                <ul class="space-y-1 text-xs">
                    @foreach ($ump->lampiran as $lampiran)
                        <li class="flex items-center gap-2 text-slate-600">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2v6h6" />
                            </svg>
                            <a href="{{ route('lampiran.show', $lampiran->id) }}" target="_blank"
                                class="truncate hover:text-[#1E60D5] hover:underline">{{ $lampiran->original_name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- SOFT WARNING: penerima belum mengisi --}}
    @if ($isEditable && ! empty($incompletePenerimaNames))
        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2">
            <svg class="w-4 h-4 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span>
                Penerima dana berikut belum mengisi baris penggunaan:
                <span class="font-semibold">{{ implode(', ', $incompletePenerimaNames) }}</span>.
                UMP tetap dapat diajukan, tetapi pastikan laporan tiap penerima sudah lengkap.
            </span>
        </div>
    @endif

    {{-- BAGIAN C: SALDO / DEFISIT --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Bagian C: Saldo / Defisit
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <span class="text-slate-400 block text-[11px]">Saldo (Jumlah Diterima - Total Penggunaan)</span>
                <span class="block mt-1 text-base font-bold tabular-nums {{ $defisit ? 'text-red-600' : 'text-emerald-700' }}">
                    Rp {{ number_format($selisih, 0, ',', '.') }}
                </span>
            </div>
            <div class="rounded-xl border p-4 {{ $defisit ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50' }}">
                <span class="block text-[11px] {{ $defisit ? 'text-red-500' : 'text-slate-400' }}">Status Penggunaan</span>
                @if ($defisit)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border border-red-300 bg-red-100 text-red-700 mt-1">
                        DEFISIT: Penggunaan melebihi penerimaan
                    </span>
                    <div class="mt-3">
                        <label for="catatanKekurangan" class="block text-xs font-semibold text-red-700 mb-1">
                            Catatan Kekurangan Dana (wajib) *
                        </label>
                        <textarea
                            id="catatanKekurangan"
                            wire:model="catatanKekurangan"
                            rows="2"
                            {{ $isEditable ? '' : 'disabled' }}
                            placeholder="Jelaskan alasan penggunaan melebihi dana diterima..."
                            class="w-full rounded-xl border border-red-300 text-xs p-2.5 bg-white focus:border-red-500 focus:outline-hidden @error('catatanKekurangan') border-red-500 @enderror"
                        ></textarea>
                        @error('catatanKekurangan')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <span class="block mt-1 text-base font-bold text-emerald-700">Penggunaan dalam batas dana diterima</span>
                @endif
            </div>
        </div>
    </div>

    {{-- BAGIAN D: PEMBULATAN NOMINAL --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Bagian D: Pembulatan Nominal
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <span class="text-slate-400 block text-[11px]">Pembulatan Kelipatan Rp 1.000 (terdekat)</span>
                <span class="block mt-1 text-base font-bold text-[#0F2F57] tabular-nums">
                    Rp {{ number_format($pembulatan, 0, ',', '.') }}
                </span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-600 leading-relaxed">
                Pembulatan dihitung dari saldo terhadap kelipatan Rp 1.000 terdekat (dapat dikonfigurasi pada pengaturan
                <span class="font-semibold">pembulatan.kelipatan</span>). Nilai tersimpan pada record UMP saat draft disimpan.
            </div>
        </div>
    </div>

    {{-- STEPPER TIMELINE PERSETUJUAN UMP (FR-UMP-4) --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Alur Tanda Tangan Berjenjang (PJ - Diperiksa Oleh - Manager Keuangan - Kepala Keuangan - Selesai)
        </h2>

        <div class="flex flex-col lg:flex-row lg:items-start gap-3">
            @php
                $steps = [
                    ['label' => 'Tanda Tangan PJ', 'sub' => 'Penanggung Jawab'],
                    ['label' => 'Diperiksa Oleh', 'sub' => 'Kadiv / Manager PJ / TU'],
                    ['label' => 'TTD Manager Keuangan', 'sub' => 'Treasury'],
                    ['label' => 'TTD Kepala Keuangan', 'sub' => 'Kepala Divisi Keuangan'],
                    ['label' => 'Selesai', 'sub' => 'Realisasi dicatat'],
                ];
            @endphp
            @foreach ($steps as $idx => $step)
                @php
                    $stepNo = $idx + 1;
                    $isDone = $ump->status !== 'dibatalkan' && $stepProgress >= $stepNo;
                    $isActive = $ump->status !== 'dibatalkan' && $stepProgress === $idx;
                @endphp
                <div class="flex-1 flex items-start gap-2.5 {{ $idx < count($steps) - 1 ? 'lg:border-r border-slate-200 lg:pr-3' : '' }}">
                    <span class="w-7 h-7 shrink-0 rounded-full text-[11px] font-bold flex items-center justify-center border
                        {{ $isDone ? 'bg-emerald-600 border-emerald-600 text-white' : ($isActive ? 'bg-blue-50 border-blue-400 text-blue-700' : 'bg-slate-50 border-slate-300 text-slate-500') }}">
                        {{ $isDone ? '✓' : $stepNo }}
                    </span>
                    <div class="min-w-0">
                        <span class="block text-xs font-semibold {{ $isDone ? 'text-emerald-700' : ($isActive ? 'text-blue-700' : 'text-slate-500') }}">
                            {{ $step['label'] }}
                        </span>
                        <span class="block text-[11px] text-slate-400">{{ $step['sub'] }}</span>
                        @if ($isDone)
                            <span class="block text-[11px] font-semibold text-emerald-600 mt-0.5">Selesai</span>
                        @elseif ($isActive)
                            <span class="block text-[11px] font-semibold text-blue-600 mt-0.5">Tahap berjalan</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($ump->approvals->isNotEmpty())
            <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                @foreach ($ump->approvals->sortBy('id') as $approval)
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-slate-600">
                            <span class="font-semibold text-slate-800">{{ $approval->tahap }}</span>
                            - {{ $approval->keputusan }} oleh {{ $approval->signer_name ?? '-' }}
                        </span>
                        <span class="text-slate-400 tabular-nums shrink-0">
                            {{ $approval->signed_at?->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    @if ($approval->catatan)
                        <p class="text-[11px] text-slate-500 pl-2 border-l-2 border-slate-200">{{ $approval->catatan }}</p>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- PANEL AKSI PERSETUJUAN --}}
    @if ($canApprove)
        <div class="bg-white rounded-2xl border-2 border-blue-200 p-5 shadow-xs space-y-3">
            <h2 class="text-xs font-bold text-[#0F2F57] uppercase tracking-wider pb-2 border-b border-blue-100">
                Panel Persetujuan UMP
            </h2>
            <p class="text-xs text-slate-600">
                Anda berwenang memproses tahap
                <span class="font-semibold">{{ $this->getStatusBadge($ump->status)['label'] }}</span>.
                @if ($ump->status === 'ttd_kepala_keuangan')
                    Scan fisik UMP bertanda tangan wajib diunggah pada persetujuan akhir ini.
                @endif
            </p>
            <div class="flex items-center gap-2.5 flex-wrap pt-1">
                <button
                    type="button"
                    wire:click="openApproval('setuju')"
                    class="min-h-[44px] px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                >
                    Setuju
                </button>
                <button
                    type="button"
                    wire:click="openApproval('perbaikan')"
                    class="min-h-[44px] px-4 py-2.5 rounded-full bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold transition-colors cursor-pointer"
                >
                    Minta Perbaikan
                </button>
                <button
                    type="button"
                    wire:click="openApproval('batal')"
                    class="min-h-[44px] px-4 py-2.5 rounded-full border border-red-300 text-xs font-semibold text-red-700 hover:bg-red-50 transition-colors cursor-pointer"
                >
                    Batal
                </button>
            </div>
        </div>
    @endif

    {{-- MODAL PERSETUJUAN --}}
    @if ($showApprovalModal)
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
                @click.outside="$wire.closeApprovalModal()"
            >
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-[#0F2F57]">
                        @if ($approvalAction === 'setuju')
                            Konfirmasi Persetujuan UMP
                        @elseif ($approvalAction === 'perbaikan')
                            Minta Perbaikan UMP
                        @else
                            Konfirmasi Pembatalan UMP
                        @endif
                    </h2>
                    <button type="button" wire:click="closeApprovalModal" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="submitApproval" action="#" onsubmit="return false;" class="p-6 space-y-4">
                    <p class="text-xs text-slate-600">
                        @if ($approvalAction === 'setuju')
                            Persetujuan dicatat dengan nama dan jabatan Anda pada tahap berjalan.
                        @elseif ($approvalAction === 'perbaikan')
                            UMP kembali ke Penanggung Jawab untuk perbaikan.
                        @else
                            UMP yang dibatalkan tidak dapat diproses lebih lanjut.
                        @endif
                    </p>

                    @if ($approvalAction === 'setuju' && $ump->status === 'ttd_kepala_keuangan')
                        <div>
                            <label for="scanFinalFile" class="block text-xs font-semibold text-slate-700 mb-1">
                                Scan UMP Final Bertanda Tangan * (PDF / JPG / PNG, maks 10 MB)
                            </label>
                            <input
                                type="file"
                                id="scanFinalFile"
                                wire:model="scanFinalFile"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 @error('scanFinalFile') border-red-500 @enderror"
                            />
                            <div wire:loading wire:target="scanFinalFile" class="text-[11px] text-slate-500 mt-1">Mengunggah berkas...</div>
                            @error('scanFinalFile')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="approvalNotes" class="block text-xs font-semibold text-slate-700 mb-1">
                            Catatan {{ $approvalAction === 'setuju' ? '(Opsional)' : '*' }}
                        </label>
                        <textarea
                            id="approvalNotes"
                            wire:model="approvalNotes"
                            rows="3"
                            placeholder="Tuliskan catatan di sini..."
                            class="w-full rounded-xl border border-slate-300 text-xs text-slate-800 p-2.5 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('approvalNotes') border-red-500 @enderror"
                        ></textarea>
                        @error('approvalNotes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" wire:click="closeApprovalModal"
                            class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            Tutup
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="min-h-[44px] px-5 py-2.5 rounded-full text-white text-xs font-semibold transition-all shadow-xs cursor-pointer disabled:opacity-60 m3-press inline-flex items-center gap-1.5
                            {{ $approvalAction === 'setuju' ? 'bg-emerald-600 hover:bg-emerald-700' : ($approvalAction === 'perbaikan' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-red-600 hover:bg-red-700') }}">
                            <span wire:loading.remove wire:target="submitApproval">Konfirmasi</span>
                            <span wire:loading wire:target="submitApproval" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Memproses...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Tombol Aksi --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
            Aksi Dokumen UMP
        </h2>
        @if ($isEditable)
            <p class="text-xs text-slate-600">
                Simpan draft untuk memperbarui saldo, kekurangan, dan pembulatan. Ajukan setelah seluruh baris pengeluaran lengkap.
            </p>
            <div class="flex items-center gap-2.5 flex-wrap pt-1">
                <button
                    type="button"
                    wire:click="simpanDraft"
                    wire:loading.attr="disabled"
                    class="min-h-[44px] px-5 py-2.5 rounded-full bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold transition-colors cursor-pointer disabled:opacity-60 m3-press inline-flex items-center gap-2"
                >
                    <span wire:loading.remove wire:target="simpanDraft">Simpan Draft</span>
                    <span wire:loading wire:target="simpanDraft" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin w-4 h-4 text-[#001B3E]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Menyimpan...</span>
                    </span>
                </button>
                <button
                    type="button"
                    wire:click="ajukanUMP"
                    wire:loading.attr="disabled"
                    class="min-h-[44px] px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-60 m3-press inline-flex items-center gap-2"
                >
                    <span wire:loading.remove wire:target="ajukanUMP">Ajukan / Tanda Tangan PJ</span>
                    <span wire:loading wire:target="ajukanUMP" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Mengajukan...</span>
                    </span>
                </button>
            </div>
        @else
            <p class="text-xs text-slate-600">
                UMP berstatus <span class="font-semibold">{{ $this->getStatusBadge($ump->status)['label'] }}</span>.
                Form tidak dapat diubah; hubungi Bagian Keuangan untuk perubahan.
            </p>
        @endif
    </div>
</div>
