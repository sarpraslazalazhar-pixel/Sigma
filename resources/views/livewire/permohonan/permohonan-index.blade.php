<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Permohonan Dana
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola dan pantau permohonan dana operasional serta kegiatan program LAZ Al-Azhar.
            </p>
        </div>
        <div>
            <a
                href="{{ route('permohonan.create') }}"
                wire:navigate
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>+ Buat Permohonan Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-2xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            {{-- Search Box --}}
            <div class="lg:col-span-6">
                <label for="filter-search" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pencarian
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="filter-search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nomor pengajuan, nama kegiatan, atau program..."
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 pl-9 pr-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            {{-- Status Filter --}}
            <div class="lg:col-span-3">
                <label for="filter-status" class="block text-xs font-semibold text-slate-700 mb-1">
                    Status Pengajuan
                </label>
                <select
                    id="filter-status"
                    wire:model.live="statusFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="diajukan">Menunggu Atasan</option>
                    <option value="disetujui_atasan">Menunggu Kadiv</option>
                    <option value="diketahui_kadiv">Menunggu Keuangan</option>
                    <option value="disetujui_keuangan">Disetujui Keuangan</option>
                    <option value="perlu_perbaikan">Perlu Perbaikan</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                    <option value="dicairkan">Dicairkan</option>
                </select>
            </div>

            {{-- Unit Filter --}}
            <div class="lg:col-span-3">
                <label for="filter-unit" class="block text-xs font-semibold text-slate-700 mb-1">
                    Unit / Divisi
                </label>
                <select
                    id="filter-unit"
                    wire:model.live="unitFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Unit / Divisi</option>
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}">{{ $u->nama }} ({{ $u->kode }})</option>
                    @endforeach
                </select>
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

    {{-- VIEW 1: MOBILE VIEW (< 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @forelse ($permohonanList as $item)
            @php
                $badge = $this->getStatusBadge($item->status);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <span class="font-mono text-xs font-bold text-[#0F2F57] block truncate">
                            {{ $item->no_pengajuan ?: 'DRAFT' }}
                        </span>
                        <h2 class="text-sm font-bold text-slate-900 mt-0.5 line-clamp-2">
                            {{ $item->nama_kegiatan }}
                        </h2>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badge['class'] }} shrink-0">
                        {{ $badge['label'] }}
                    </span>
                </div>

                <div class="bg-slate-50 rounded-xl p-3 text-xs space-y-1.5 border border-slate-100">
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Unit / Divisi:</span>
                        <span class="font-medium text-slate-800">{{ $item->unit?->nama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Pos Anggaran:</span>
                        <span class="font-mono text-slate-800 font-medium truncate max-w-[200px]" title="{{ $item->kodeAnggaran?->nama }}">
                            {{ $item->kodeAnggaran?->kode }} - {{ $item->kodeAnggaran?->nama }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 pt-1 border-t border-slate-200/60">
                        <span class="text-slate-400">Total Permohonan:</span>
                        <span class="font-bold text-[#0F2F57] text-sm tabular-nums">
                            Rp {{ number_format($item->total_diminta, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div>
                    <a
                        href="{{ route('permohonan.detail', $item->id) }}"
                        wire:navigate
                        class="min-h-[44px] w-full px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Lihat Rincian</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                Tidak ada dokumen permohonan yang ditemukan.
            </div>
        @endforelse
    </div>

    {{-- VIEW 2: DESKTOP TABLE VIEW (>= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">No. Pengajuan</th>
                    <th class="py-3.5 px-4">Unit / Divisi</th>
                    <th class="py-3.5 px-4">Kegiatan & Pos Anggaran</th>
                    <th class="py-3.5 px-4">Tgl Kegiatan</th>
                    <th class="py-3.5 px-4 text-right">Total Permohonan</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($permohonanList as $item)
                    @php
                        $badge = $this->getStatusBadge($item->status);
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 whitespace-nowrap font-mono font-bold text-slate-900">
                            {{ $item->no_pengajuan ?: 'DRAFT' }}
                            @if ($item->submitted_at)
                                <span class="block text-[11px] font-sans font-normal text-slate-400">
                                    {{ $item->submitted_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-semibold text-slate-800 block">
                                {{ $item->unit?->nama ?? '-' }}
                            </span>
                            <span class="text-[11px] text-slate-400 block">
                                Pengaju: {{ $item->user?->name ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 max-w-[280px]">
                            <span class="font-semibold text-slate-900 block truncate" title="{{ $item->nama_kegiatan }}">
                                {{ $item->nama_kegiatan }}
                            </span>
                            <span class="text-[11px] text-slate-500 font-mono block truncate" title="{{ $item->kodeAnggaran?->nama }}">
                                {{ $item->kodeAnggaran?->kode }} - {{ $item->kodeAnggaran?->nama }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 tabular-nums">
                            {{ $item->tgl_mulai ? $item->tgl_mulai->format('d/m/Y') : '-' }}
                            @if ($item->tgl_selesai)
                                <span class="text-slate-400 block text-[11px]">
                                    s.d {{ $item->tgl_selesai->format('d/m/Y') }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap font-bold text-slate-900 tabular-nums">
                            Rp {{ number_format($item->total_diminta, 0, ',', '.') }}
                            @if ($item->total_disetujui !== null && $item->total_disetujui !== $item->total_diminta)
                                <span class="block text-[11px] font-semibold text-emerald-700">
                                    Acc: Rp {{ number_format($item->total_disetujui, 0, ',', '.') }}
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
                                href="{{ route('permohonan.detail', $item->id) }}"
                                wire:navigate
                                class="min-h-[36px] px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Rincian</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 px-4 text-center text-slate-500 text-xs">
                            Tidak ada dokumen permohonan yang sesuai kriteria pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($permohonanList->hasPages())
        <div class="pt-2">
            {{ $permohonanList->links() }}
        </div>
    @endif
</div>
