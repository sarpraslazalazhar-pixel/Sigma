<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Laporan UMP
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Uang Muka Perjalanan dan realisasi penggunaan dana per pencairan PPD.
            </p>
        </div>
    </div>

    {{-- Metrik Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[11px] text-slate-400 block">Total UMP Berjalan</span>
            <span class="text-xl font-bold text-[#0F2F57] block mt-1 tabular-nums">{{ number_format($metrics['berjalan'], 0, ',', '.') }}</span>
            <span class="text-[11px] text-slate-500 block mt-0.5">Belum berstatus Selesai</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[11px] text-slate-400 block">Total Dana Diterima</span>
            <span class="text-xl font-bold text-slate-900 block mt-1 tabular-nums">Rp {{ number_format($metrics['diterima'], 0, ',', '.') }}</span>
            <span class="text-[11px] text-slate-500 block mt-0.5">Akumulasi jumlah_total UMP</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[11px] text-slate-400 block">Total Realisasi Penggunaan</span>
            <span class="text-xl font-bold text-emerald-700 block mt-1 tabular-nums">Rp {{ number_format($metrics['realisasi'], 0, ',', '.') }}</span>
            <span class="text-[11px] text-slate-500 block mt-0.5">Akumulasi total_penggunaan</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[11px] text-slate-400 block">Saldo Tersisa</span>
            <span class="text-xl font-bold text-[#1E60D5] block mt-1 tabular-nums">Rp {{ number_format($metrics['saldo'], 0, ',', '.') }}</span>
            <span class="text-[11px] text-slate-500 block mt-0.5">Selisih dana diterima - penggunaan</span>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-2xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            <div class="lg:col-span-6">
                <label for="ump-filter-search" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pencarian
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="ump-filter-search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari No PPD, tujuan, atau nama PJ..."
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 pl-9 pr-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="lg:col-span-3">
                <label for="ump-filter-status" class="block text-xs font-semibold text-slate-700 mb-1">
                    Status UMP
                </label>
                <select
                    id="ump-filter-status"
                    wire:model.live="statusFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="perlu_perbaikan">Perlu Perbaikan</option>
                    <option value="ttd_pj">Menunggu TTD Kadiv</option>
                    <option value="ttd_manager_keuangan">Menunggu TTD Mgr Keuangan</option>
                    <option value="ttd_kepala_keuangan">Menunggu TTD Kepala Keuangan</option>
                    <option value="dibatalkan">Dibatalkan</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>

            <div class="lg:col-span-3">
                <label for="ump-filter-unit" class="block text-xs font-semibold text-slate-700 mb-1">
                    Unit / Divisi
                </label>
                <x-searchable-select
                    wire:model.live="unitFilter"
                    :options="$units"
                    placeholder="Semua Unit / Divisi"
                    null-label="Semua Unit / Divisi"
                    search-placeholder="Cari unit atau divisi..."
                />
            </div>
        </div>

        @if ($search !== '' || $statusFilter !== '' || $unitFilter !== '')
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-500">Filter diterapkan</span>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="text-xs font-semibold text-[#1E60D5] hover:text-[#0F2F57] hover:underline cursor-pointer"
                >
                    Reset Filter
                </button>
            </div>
        @endif
    </div>

    {{-- VIEW 1: MOBILE CARDS (< 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @forelse ($umpList as $item)
            @php
                $badge = $this->getStatusBadge($item->status);
                $permohonan = $item->pencairan?->permohonan;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <span class="font-mono text-xs font-bold text-[#0F2F57] block truncate">
                            {{ $item->pencairan?->no_ppd ?? '-' }}
                        </span>
                        <h2 class="text-sm font-bold text-slate-900 mt-0.5 line-clamp-2">
                            {{ $item->tujuan }}
                        </h2>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badge['class'] }} shrink-0">
                        {{ $badge['label'] }}
                    </span>
                </div>

                <div class="bg-slate-50 rounded-xl p-3 text-xs space-y-1.5 border border-slate-100">
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Penanggung Jawab:</span>
                        <span class="font-medium text-slate-800">{{ $item->pj?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Unit / Divisi:</span>
                        <span class="font-medium text-slate-800">{{ $permohonan?->unit?->nama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 pt-1 border-t border-slate-200/60">
                        <span class="text-slate-400">Dana Diterima:</span>
                        <span class="font-bold text-[#0F2F57] text-sm tabular-nums">
                            Rp {{ number_format($item->jumlah_total, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Realisasi:</span>
                        <span class="font-semibold text-emerald-700 tabular-nums">
                            Rp {{ number_format($item->total_penggunaan, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Saldo:</span>
                        <span class="font-semibold {{ $item->saldo < 0 ? 'text-red-600' : 'text-slate-800' }} tabular-nums">
                            Rp {{ number_format($item->saldo, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <a
                    href="{{ route('ump.detail', $item->id) }}"
                    wire:navigate
                    class="min-h-[44px] w-full px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold transition-colors flex items-center justify-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Buka Form UMP</span>
                </a>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                Tidak ada UMP yang ditemukan.
            </div>
        @endforelse
    </div>

    {{-- VIEW 2: DESKTOP TABLE (>= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">No. PPD</th>
                    <th class="py-3.5 px-4">Tujuan & PJ</th>
                    <th class="py-3.5 px-4">Unit / Divisi</th>
                    <th class="py-3.5 px-4">Jatuh Tempo</th>
                    <th class="py-3.5 px-4 text-right">Dana Diterima</th>
                    <th class="py-3.5 px-4 text-right">Realisasi</th>
                    <th class="py-3.5 px-4 text-right">Saldo</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($umpList as $item)
                    @php
                        $badge = $this->getStatusBadge($item->status);
                        $permohonan = $item->pencairan?->permohonan;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 whitespace-nowrap font-mono font-bold text-slate-900">
                            {{ $item->pencairan?->no_ppd ?? '-' }}
                            <span class="block text-[11px] font-sans font-normal text-slate-400">
                                {{ $item->tgl_terima?->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 max-w-[240px]">
                            <span class="font-semibold text-slate-900 block truncate" title="{{ $item->tujuan }}">
                                {{ $item->tujuan }}
                            </span>
                            <span class="text-[11px] text-slate-500 block">
                                PJ: {{ $item->pj?->name ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            {{ $permohonan?->unit?->nama ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap tabular-nums {{ $item->jatuh_tempo && $item->status !== 'selesai' && now()->gt($item->jatuh_tempo) ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                            {{ $item->jatuh_tempo ? $item->jatuh_tempo->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap font-bold text-slate-900 tabular-nums">
                            Rp {{ number_format($item->jumlah_total, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap font-semibold text-emerald-700 tabular-nums">
                            Rp {{ number_format($item->total_penggunaan, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap font-semibold tabular-nums {{ $item->saldo < 0 ? 'text-red-600' : 'text-slate-900' }}">
                            Rp {{ number_format($item->saldo, 0, ',', '.') }}
                            @if ($item->kekurangan > 0)
                                <span class="block text-[11px] font-bold text-red-600">
                                    Kekurangan: Rp {{ number_format($item->kekurangan, 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <a
                                href="{{ route('ump.detail', $item->id) }}"
                                wire:navigate
                                class="min-h-[36px] px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Buka Form UMP</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 px-4 text-center text-slate-500 text-xs">
                            Tidak ada UMP yang sesuai kriteria pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($umpList->hasPages())
        <div class="pt-2">
            {{ $umpList->links() }}
        </div>
    @endif
</div>
