<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Verifikasi Keuangan (Mode Atas Nama)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Antrean dokumen keuangan yang diproses secara retrospektif (Mode Atas Nama) dan memerlukan verifikasi staf Keuangan.
            </p>
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

    {{-- Metric Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Total Menunggu</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold {{ $totalPending > 0 ? 'text-amber-600' : 'text-slate-800' }}">
                    {{ $totalPending }}
                </span>
                <span class="text-xs text-slate-500">dokumen</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Permohonan</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-800">{{ $countPendingPermohonan }}</span>
                <span class="text-xs text-slate-500">pending</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Pencairan (PPD)</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-800">{{ $countPendingPencairan }}</span>
                <span class="text-xs text-slate-500">pending</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Pertanggungjawaban (UMP)</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-800">{{ $countPendingUmp }}</span>
                <span class="text-xs text-slate-500">pending</span>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label for="search-input" class="block text-xs font-semibold text-slate-700 mb-1">
                    Cari Dokumen:
                </label>
                <input
                    type="text"
                    id="search-input"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nomor dokumen, unit, pos anggaran..."
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
            </div>

            <div>
                <label for="jenis-filter" class="block text-xs font-semibold text-slate-700 mb-1">
                    Jenis Dokumen:
                </label>
                <select
                    id="jenis-filter"
                    wire:model.live="filterJenis"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="semua">Semua Jenis Dokumen</option>
                    <option value="permohonan">Permohonan Dana</option>
                    <option value="pencairan">Pencairan (PPD)</option>
                    <option value="ump">Uang Muka Pas / UMP</option>
                </select>
            </div>

            <div>
                <label for="status-filter" class="block text-xs font-semibold text-slate-700 mb-1">
                    Status Verifikasi:
                </label>
                <select
                    id="status-filter"
                    wire:model.live="filterStatus"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="belum">Belum Diverifikasi</option>
                    <option value="terverifikasi">Sudah Terverifikasi</option>
                    <option value="semua">Semua Status</option>
                </select>
            </div>
        </div>
    </div>

    {{-- VIEW 1: Mobile Cards (< 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @forelse ($items as $item)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-[#DCE5F6] text-[#001B3E]">
                            {{ $item['no_dokumen'] }}
                        </span>
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $item['jenis'] }}
                        </span>
                    </div>
                    @if ($item['is_verified'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Terverifikasi
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            Belum Diverifikasi
                        </span>
                    @endif
                </div>

                <div class="text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Unit:</span>
                        <span class="font-medium text-slate-800">{{ $item['unit'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Pos Anggaran:</span>
                        <span class="font-medium text-slate-800">{{ $item['kode_anggaran'] }} - {{ $item['nama_pos'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nominal:</span>
                        <span class="font-bold text-slate-900 font-mono">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Dicatat Oleh:</span>
                        <span class="text-slate-700">{{ $item['dicatat_oleh'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal Catat:</span>
                        <span class="text-slate-700">{{ $item['tgl_catat']?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if ($item['is_verified'])
                        <div class="pt-1.5 border-t border-slate-100 text-[11px] text-emerald-700">
                            Diverifikasi oleh {{ $item['verifikator'] ?? '-' }} pada {{ $item['verified_at']?->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>

                <div class="pt-2 border-t border-slate-100 flex justify-end">
                    @if ($item['is_verified'])
                        <button
                            type="button"
                            wire:click="batalVerifikasi('{{ $item['type'] }}', {{ $item['id'] }})"
                            class="min-h-[44px] px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                        >
                            Batal Verifikasi
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="verifikasi('{{ $item['type'] }}', {{ $item['id'] }})"
                            class="min-h-[44px] px-5 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 transition-colors cursor-pointer"
                        >
                            Verifikasi Sekarang
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 text-xs">
                Tidak ada dokumen yang memerlukan verifikasi.
            </div>
        @endforelse
    </div>

    {{-- VIEW 2: Desktop Table (>= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">No. Dokumen</th>
                        <th class="py-3.5 px-3">Jenis</th>
                        <th class="py-3.5 px-4">Unit / Divisi</th>
                        <th class="py-3.5 px-4">Pos Anggaran</th>
                        <th class="py-3.5 px-4 text-right">Nominal</th>
                        <th class="py-3.5 px-4">Dicatat Oleh</th>
                        <th class="py-3.5 px-3">Tgl Catat</th>
                        <th class="py-3.5 px-3 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                {{ $item['no_dokumen'] }}
                            </td>
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $item['jenis'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                {{ $item['unit'] }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800">{{ $item['kode_anggaran'] }}</span>
                                <span class="text-slate-500 block text-[11px] truncate max-w-xs">{{ $item['nama_pos'] }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                                Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                {{ $item['dicatat_oleh'] }}
                            </td>
                            <td class="py-3.5 px-3 text-slate-500 whitespace-nowrap">
                                {{ $item['tgl_catat']?->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if ($item['is_verified'])
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Terverifikasi
                                        </span>
                                        <span class="text-[9px] text-slate-400 mt-0.5">
                                            {{ $item['verifikator'] ?? '-' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Belum Diverifikasi
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                @if ($item['is_verified'])
                                    <button
                                        type="button"
                                        wire:click="batalVerifikasi('{{ $item['type'] }}', {{ $item['id'] }})"
                                        class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                                    >
                                        Batal
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="verifikasi('{{ $item['type'] }}', {{ $item['id'] }})"
                                        class="min-h-[44px] px-4 py-1.5 rounded-xl text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 transition-colors cursor-pointer"
                                    >
                                        Verifikasi
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500 text-xs">
                                Tidak ada dokumen yang memerlukan verifikasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
