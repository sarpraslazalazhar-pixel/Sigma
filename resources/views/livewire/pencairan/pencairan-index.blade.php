<div class="space-y-6 sm:space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57]">
                Pencairan PPD (Perintah Pencairan Dana)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Daftar pencairan kas dan transfer bank yang diterbitkan beserta status pertanggungjawaban UMP
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
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Kewajiban UMP Berjalan</span>
            <p class="text-xl sm:text-2xl font-bold tracking-tight text-amber-800 mt-1 tabular-nums">
                {{ number_format($umpBerjalan, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-slate-400 mt-2">Belum selesai di-LPJ</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">UMP Tuntas Selesai</span>
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
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status UMP</label>
                <select
                    wire:model.live="umpStatusFilter"
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2.5 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                >
                    <option value="">Semua Status UMP</option>
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

    {{-- Table List --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
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
                        <th class="py-3 px-3.5 text-center">Status UMP</th>
                        <th class="py-3 px-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($pencairans as $item)
                        @php
                            $ump = $item->ump;
                            $isBatal = $item->status === 'dibatalkan';
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isBatal ? 'opacity-60 bg-slate-50/50' : '' }}">
                            <td class="py-3 px-3.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ $item->no_ppd }}
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
                            <td colspan="8" class="py-10 text-center text-slate-400">
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
