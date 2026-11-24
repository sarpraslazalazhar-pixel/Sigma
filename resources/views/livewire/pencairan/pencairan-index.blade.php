<div class="space-y-6 sm:space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57]">
                Pencairan PPD (Perintah Pencairan Dana)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Daftar pencairan kas dan transfer bank yang diterbitkan beserta status laporan penggunaan dana
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="exportExcel"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-2xs transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel PPD
            </button>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($ppd)
        <div class="space-y-3 rounded-2xl border border-slate-300 bg-white p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="break-all text-base font-bold text-[#0F2F57]">{{ $ppd->no_ppd }}</h2>
                    <p class="text-sm text-slate-700">{{ $ppd->permohonan->nama_kegiatan }}</p>
                    <p class="mt-1 text-sm tabular-nums text-slate-700">
                        Rp {{ number_format($ppd->jumlah, 0, ',', '.') }} · {{ $ppd->tgl_cair?->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('pencairan.index') }}" wire:navigate class="inline-flex min-h-11 items-center text-sm font-semibold text-[#0F2F57] underline">
                    Tutup detail PPD
                </a>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('pencairan.cetak-pdf', $ppd->id) }}" class="inline-flex min-h-11 max-w-full items-center rounded-full border border-slate-500 px-4 text-sm font-semibold text-[#0F2F57]">
                    Buka PDF PPD
                </a>
                @if ($ppd->buktiTransfer)
                    <a
                        href="{{ route('lampiran.show', $ppd->buktiTransfer->id) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-11 max-w-full items-center gap-1.5 rounded-full bg-[#DCE5F6] px-4 text-sm font-semibold text-[#001B3E] hover:bg-[#CBD8F1] transition-colors"
                    >
                        <span>Lihat Bukti TF</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-bold text-[#0F2F57]">v{{ $ppd->buktiTransfer->version }}</span>
                    </a>
                @elseif ($ppd->status === 'dicairkan')
                    <span class="inline-flex min-h-11 items-center rounded-full border border-slate-300 bg-slate-50 px-4 text-xs font-medium text-slate-500">
                        Belum Ada Bukti TF
                    </span>
                @endif
                @if ($canManageBuktiTransfer && $ppd->status === 'dicairkan')
                    <button
                        type="button"
                        wire:click="bukaModalBuktiTransfer({{ $ppd->id }})"
                        class="inline-flex min-h-11 items-center rounded-full {{ $ppd->buktiTransfer ? 'border border-[#727782] text-[#0F2F57] hover:bg-slate-50' : 'bg-[#0F2F57] text-white hover:bg-[#1E60D5]' }} px-4 text-sm font-semibold transition-colors cursor-pointer"
                    >
                        {{ $ppd->buktiTransfer ? 'Ganti TF' : 'Unggah Bukti TF' }}
                    </button>
                @endif
                @foreach ($ppd->lampiran->where('jenis', '!=', 'bukti_transfer') as $lampiran)
                    <a href="{{ route('lampiran.show', $lampiran->id) }}" class="inline-flex min-h-11 max-w-full items-center break-all rounded-full border border-slate-500 px-4 text-sm text-[#0F2F57]">
                        {{ $lampiran->original_name }}
                    </a>
                @endforeach
            </div>
        </div>
        @include('livewire.keuangan.status-verifikasi', ['document' => $ppd, 'type' => 'pencairan'])
    @endif

    {{-- Metrics Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Berkas PPD</span>
            <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 mt-1 tabular-nums">
                {{ number_format($totalPpd, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-slate-400 mt-2">Seluruh PPD terbit</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Total Dana Dicairkan</span>
            <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 mt-1 tabular-nums">
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-slate-400 mt-2">Status aktif dicairkan</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Laporan Berjalan</span>
            <p class="text-xl sm:text-2xl font-bold tracking-tight text-amber-800 mt-1 tabular-nums">
                {{ number_format($umpBerjalan, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-slate-400 mt-2">Belum selesai dilaporkan</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Laporan Tuntas Selesai</span>
            <p class="text-xl sm:text-2xl font-bold tracking-tight text-blue-900 mt-1 tabular-nums">
                {{ number_format($umpSelesai, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-slate-400 mt-2">Realisasi resmi diakui</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0F2F57]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter Pencairan
            </span>
            <button
                type="button"
                wire:click="resetFilters"
                class="text-xs font-medium text-blue-700 hover:text-blue-900 transition-colors"
            >
                Reset Filter
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div class="lg:col-span-1">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cari No PPD / Pengajuan / Kegiatan / PJ</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nomor PPD, No. Pengajuan, Kegiatan, atau Nama..."
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Pencairan</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2.5 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                >
                    <option value="">Semua Status PPD</option>
                    <option value="dicairkan">Dicairkan</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Laporan</label>
                <select
                    wire:model.live="umpStatusFilter"
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2.5 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                >
                    <option value="">Semua Status Laporan</option>
                    <option value="draft">Draft</option>
                    <option value="menunggu_kadiv">Menunggu Kepala Divisi</option>
                    <option value="menunggu_keuangan">Menunggu Keuangan</option>
                    <option value="disetujui_keuangan">Disetujui Keuangan</option>
                    <option value="selesai">Selesai (LPJ Tuntas)</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sumber Dana</label>
                <select
                    wire:model.live="sumberDanaFilter"
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2.5 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                >
                    <option value="">Semua Sumber Dana</option>
                    @foreach ($sumberDanas as $sd)
                        <option value="{{ $sd->id }}">{{ $sd->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit / Divisi</label>
                <x-searchable-select
                    wire:model.live="unitFilter"
                    :options="$units"
                    placeholder="Semua Unit"
                    null-label="Semua Unit"
                    search-placeholder="Cari unit atau divisi..."
                />
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Bulan Cair</label>
                    <select
                        wire:model.live="bulanFilter"
                        class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                    >
                        <option value="">Semua Bulan</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tahun Cair</label>
                    <select
                        wire:model.live="tahunFilter"
                        class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                    >
                        <option value="">Semua Tahun</option>
                        @foreach ($daftarTahun as $th)
                            <option value="{{ $th }}">{{ $th }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Cards (< 1024px) --}}
    <div class="lg:hidden space-y-3">
        @forelse ($pencairans as $item)
            @php
                $ump = $item->ump;
                $isBatal = $item->status === 'dibatalkan';
                $buktiTf = $item->buktiTransfer;
            @endphp
            <article class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-3 {{ $isBatal ? 'opacity-65 bg-slate-50/70' : '' }}" wire:key="pencairan-card-{{ $item->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ route('pencairan.index', ['ppd' => $item->id]) }}" wire:navigate
                            class="inline-flex min-h-[44px] items-center font-mono text-sm font-bold text-[#0F2F57] underline underline-offset-4 break-all">
                            {{ $item->no_ppd }}
                        </a>
                        <p class="text-[11px] text-slate-500 tabular-nums">
                            Tgl Cair: {{ \Carbon\Carbon::parse($item->tgl_cair)->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        @if ($isBatal)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                Dibatalkan
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-[#006C4C] border border-emerald-200">
                                Dicairkan
                            </span>
                        @endif
                        @if ($ump)
                            <a href="{{ route('ump.detail', $ump->id) }}" wire:navigate
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ $ump->status === 'selesai' ? 'bg-[#DCE5F6] text-[#001B3E]' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                Laporan: {{ ucfirst(str_replace('_', ' ', $ump->status)) }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="space-y-1 text-xs border-t border-slate-100 pt-3">
                    <a href="{{ route('permohonan.detail', $item->permohonan_id) }}" wire:navigate
                        class="font-semibold text-[#1E60D5] hover:underline block">
                        {{ $item->permohonan?->no_pengajuan ?? '-' }}
                    </a>
                    <p class="font-medium text-slate-800">{{ $item->permohonan?->nama_kegiatan ?? '-' }}</p>
                    <p class="text-[11px] text-slate-500">
                        {{ $item->permohonan?->unit?->nama ?? '-' }} &bull; PJ: {{ $ump?->pj?->name ?? ($item->permohonan?->user?->name ?? '-') }}
                    </p>
                    <p class="text-sm font-bold text-[#0F2F57] tabular-nums pt-1">
                        Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                    </p>
                </div>

                <div class="border-t border-slate-100 pt-3 space-y-2">
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="text-[11px] font-semibold text-slate-500">Bukti Transfer</span>
                        @if ($buktiTf)
                            <span class="text-[11px] font-medium text-[#006C4C] truncate max-w-[200px]">
                                v{{ $buktiTf->version }} &bull; {{ $buktiTf->original_name }}
                            </span>
                        @elseif (! $isBatal)
                            <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                Belum Ada Bukti TF
                            </span>
                        @else
                            <span class="text-[11px] text-slate-400">-</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ route('pencairan.cetak-pdf', $item->id) }}"
                            target="_blank"
                            class="inline-flex min-h-[44px] items-center gap-1.5 rounded-full border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>PDF</span>
                        </a>

                        @if ($buktiTf)
                            <a
                                href="{{ route('lampiran.show', $buktiTf->id) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-full bg-[#DCE5F6] px-4 text-xs font-semibold text-[#001B3E] hover:bg-[#CBD8F1] transition-colors"
                            >
                                <span>Lihat Bukti TF</span>
                            </a>
                        @endif

                        @if ($canManageBuktiTransfer && ! $isBatal)
                            <button
                                type="button"
                                wire:click="bukaModalBuktiTransfer({{ $item->id }})"
                                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-full {{ $buktiTf ? 'border border-[#727782] bg-white text-[#0F2F57] hover:bg-slate-50' : 'bg-[#0F2F57] text-white hover:bg-[#1E60D5]' }} px-4 text-xs font-semibold transition-colors cursor-pointer"
                            >
                                <span>{{ $buktiTf ? 'Ganti TF' : 'Unggah Bukti TF' }}</span>
                            </button>
                        @endif

                        @if ($canManage && ! $isBatal && ($ump?->status !== 'selesai'))
                            <button
                                type="button"
                                wire:click="bukaModalBatal({{ $item->id }})"
                                class="inline-flex min-h-[44px] items-center rounded-full border border-rose-200 bg-rose-50/70 px-4 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-xs text-slate-400">
                Tidak ada data pencairan PPD yang sesuai kriteria.
            </div>
        @endforelse
    </div>

    {{-- Desktop Table List (>= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3.5">No PPD</th>
                        <th class="py-3 px-3.5">Tgl Cair</th>
                        <th class="py-3 px-3.5">Permohonan & Kegiatan</th>
                        <th class="py-3 px-3.5">Unit / PJ</th>
                        <th class="py-3 px-3.5 text-right">Nominal PPD</th>
                        <th class="py-3 px-3.5 text-center">Status PPD</th>
                        <th class="py-3 px-3.5 text-center">Status Laporan</th>
                        <th class="py-3 px-3.5 text-center">Bukti TF</th>
                        <th class="py-3 px-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($pencairans as $item)
                        @php
                            $ump = $item->ump;
                            $isBatal = $item->status === 'dibatalkan';
                            $buktiTf = $item->buktiTransfer;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isBatal ? 'opacity-60 bg-slate-50/50' : '' }}" wire:key="pencairan-row-{{ $item->id }}">
                            <td class="py-3 px-3.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <a href="{{ route('pencairan.index', ['ppd' => $item->id]) }}" wire:navigate
                                        class="inline-flex min-h-11 items-center text-[#0F2F57] underline underline-offset-4">
                                        {{ $item->no_ppd }}
                                    </a>
                                </span>
                            </td>
                            <td class="py-3 px-3.5 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->tgl_cair)->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-3.5 min-w-[200px]">
                                <a
                                    href="{{ route('permohonan.detail', $item->permohonan_id) }}"
                                    wire:navigate
                                    class="font-semibold text-blue-700 hover:underline block"
                                >
                                    {{ $item->permohonan?->no_pengajuan ?? '-' }}
                                </a>
                                <p class="text-slate-600 line-clamp-1 mt-0.5">
                                    {{ $item->permohonan?->nama_kegiatan ?? '-' }}
                                </p>
                                @if ($item->permohonan?->sumberDana)
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $item->permohonan->sumberDana->nama }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5 min-w-[140px]">
                                <p class="font-medium text-slate-900">{{ $item->permohonan?->unit?->nama ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">PJ: {{ $ump?->pj?->name ?? ($item->permohonan?->user?->name ?? '-') }}</p>
                            </td>
                            <td class="py-3 px-3.5 text-right font-bold tabular-nums text-slate-900 whitespace-nowrap">
                                Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @if ($isBatal)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Dibatalkan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Dicairkan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @if ($ump)
                                    <a
                                        href="{{ route('ump.detail', $ump->id) }}"
                                        wire:navigate
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold hover:underline {{ $ump->status === 'selesai' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $ump->status)) }}
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    @if ($buktiTf)
                                        <a
                                            href="{{ route('lampiran.show', $buktiTf->id) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="{{ $buktiTf->original_name }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#DCE5F6] hover:bg-[#CBD8F1] text-[#001B3E] text-[11px] font-semibold transition-colors"
                                        >
                                            <span>Lihat Bukti TF</span>
                                            <span class="text-[10px] opacity-80">v{{ $buktiTf->version }}</span>
                                        </a>
                                        @if ($canManageBuktiTransfer && ! $isBatal)
                                            <button
                                                type="button"
                                                wire:click="bukaModalBuktiTransfer({{ $item->id }})"
                                                class="text-[11px] font-semibold text-[#1E60D5] hover:text-[#0F2F57] hover:underline cursor-pointer"
                                            >
                                                Ganti TF
                                            </button>
                                        @endif
                                    @elseif (! $isBatal)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px] font-medium">
                                            Belum Ada Bukti TF
                                        </span>
                                        @if ($canManageBuktiTransfer)
                                            <button
                                                type="button"
                                                wire:click="bukaModalBuktiTransfer({{ $item->id }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#0F2F57] hover:bg-[#1E60D5] text-white text-[11px] font-semibold transition-colors cursor-pointer"
                                            >
                                                Unggah Bukti TF
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- Cetak PDF --}}
                                    <a
                                        href="{{ route('pencairan.cetak-pdf', $item->id) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium shadow-2xs transition-colors"
                                        title="Cetak PDF PPD"
                                    >
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        PDF
                                    </a>

                                    {{-- Batalkan PPD (Keuangan / Admin) --}}
                                    @if ($canManage && ! $isBatal && ($ump?->status !== 'selesai'))
                                        <button
                                            type="button"
                                            wire:click="bukaModalBatal({{ $item->id }})"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-rose-200 bg-rose-50/50 hover:bg-rose-50 text-rose-700 text-xs font-medium transition-colors"
                                            title="Batalkan PPD"
                                        >
                                            Batal
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                Tidak ada data pencairan PPD yang sesuai kriteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pencairans->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $pencairans->links() }}
            </div>
        @endif
    </div>

    @if ($pencairans->hasPages())
        <div class="lg:hidden bg-white rounded-2xl border border-slate-200 p-4">
            {{ $pencairans->links() }}
        </div>
    @endif

    {{-- Modal Unggah / Perbarui Bukti Transfer PPD --}}
    @if ($buktiTransferModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div
                @click.outside="$wire.tutupModalBuktiTransfer()"
                class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[#0F2F57]">
                            {{ $buktiTransferHasExisting ? 'Perbarui Bukti Transfer PPD' : 'Unggah Bukti Transfer PPD' }}
                        </h2>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $buktiTransferNoPpd }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="tutupModalBuktiTransfer"
                        class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="simpanBuktiTransfer" class="space-y-4">
                    <div>
                        <label for="indexBuktiTransferFile" class="block text-xs font-semibold text-slate-700 mb-1">
                            Berkas Bukti Transfer (PDF, JPG, PNG, WEBP) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="file"
                            id="indexBuktiTransferFile"
                            wire:model="buktiTransferFile"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            class="w-full min-h-[44px] text-xs rounded-xl border border-slate-300 bg-white text-slate-800 file:mr-3 file:min-h-[44px] file:px-4 file:border-0 file:bg-[#DCE5F6] file:text-[#001B3E] file:font-semibold hover:file:bg-[#CBD8F1] cursor-pointer @error('buktiTransferFile') border-rose-500 @enderror"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">
                            Maksimal {{ $maxLampiranLabel }}. Unggahan baru otomatis menambah nomor versi.
                        </p>
                        <div wire:loading wire:target="buktiTransferFile" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-[#1E60D5]">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>Mengunggah berkas...</span>
                        </div>
                        @error('buktiTransferFile')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            wire:click="tutupModalBuktiTransfer"
                            class="min-h-[44px] px-4 py-2 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="simpanBuktiTransfer,buktiTransferFile"
                            class="min-h-[44px] px-5 py-2 rounded-full text-xs font-semibold text-white bg-[#0F2F57] hover:bg-[#1E60D5] transition-colors shadow-xs cursor-pointer inline-flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="simpanBuktiTransfer">Simpan Bukti TF</span>
                            <span wire:loading wire:target="simpanBuktiTransfer" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Pembatalan PPD --}}
    @if ($batalModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Batalkan Pencairan PPD</h2>
                        <p class="text-xs text-slate-500">Tindakan ini tidak dapat diurungkan</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Sesuai aturan BR-PCR-2, nomor PPD tidak dapat dihapus, melainkan ditandai Dibatalkan. Permohonan dana terkait akan dikembalikan ke status <strong>Disetujui Keuangan</strong>.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                    <textarea
                        wire:model="alasanBatal"
                        rows="3"
                        placeholder="Tuliskan alasan pembatalan pencairan secara rinci..."
                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 text-slate-800 focus:border-rose-500 focus:outline-hidden"
                    ></textarea>
                    @error('alasanBatal')
                        <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        wire:click="tutupModalBatal"
                        class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="prosesBatal"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-xs"
                    >
                        Konfirmasi Pembatalan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
