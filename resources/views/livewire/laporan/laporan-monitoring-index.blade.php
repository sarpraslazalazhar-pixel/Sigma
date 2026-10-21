<div class="space-y-6 sm:space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57]">
                Monitoring Anggaran & Rekap Laporan
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pusat unduh laporan resmi Excel dan pemantauan penyerapan anggaran institusi
            </p>
        </div>
    </div>

    {{-- Export Actions Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between hover:border-blue-300 transition-colors">
            <div>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-xs font-bold text-slate-900">Rekap Anggaran (RAB)</h2>
                <p class="text-[11px] text-slate-500 mt-1">Hierarki pos, plafon, keterikatan, realisasi, dan serapan.</p>
            </div>
            <button
                type="button"
                wire:click="exportRekapAnggaran"
                class="mt-3.5 inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl bg-[#0F2F57] text-white text-xs font-semibold hover:bg-slate-800 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Unduh Rekap Anggaran (.xlsx)
            </button>
        </div>

        {{-- Export 2: Rekap Permohonan --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between hover:border-blue-300 transition-colors">
            <div>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-xs font-bold text-slate-900">Rekap Permohonan Dana</h2>
                <p class="text-[11px] text-slate-500 mt-1">Daftar pengajuan, nominal, status persetujuan, dan approver.</p>
            </div>
            <button
                type="button"
                wire:click="exportRekapPermohonan"
                class="mt-3.5 inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl bg-[#0F2F57] text-white text-xs font-semibold hover:bg-slate-800 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Unduh Rekap Permohonan (.xlsx)
            </button>
        </div>

        {{-- Export 3: Rekap PPD & UMP --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between hover:border-blue-300 transition-colors">
            <div>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-xs font-bold text-slate-900">Rekap PPD & UMP</h2>
                <p class="text-[11px] text-slate-500 mt-1">Pencairan kas, pertanggungjawaban UMP, saldo, dan tenggat.</p>
            </div>
            <button
                type="button"
                wire:click="exportRekapPpdUmp"
                class="mt-3.5 inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl bg-[#0F2F57] text-white text-xs font-semibold hover:bg-slate-800 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Unduh Rekap PPD & UMP (.xlsx)
            </button>
        </div>

        {{-- Export 4: Realisasi Bulanan --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between hover:border-blue-300 transition-colors">
            <div>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h2 class="text-xs font-bold text-slate-900">Realisasi Bulanan Matrix</h2>
                <p class="text-[11px] text-slate-500 mt-1">Matriks Jan-Des dengan breakdown per Sumber Dana & Asnaf.</p>
            </div>
            <button
                type="button"
                wire:click="exportRekapRealisasi"
                class="mt-3.5 inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl bg-[#0F2F57] text-white text-xs font-semibold hover:bg-slate-800 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Unduh Realisasi Bulanan (.xlsx)
            </button>
        </div>

    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tahun Anggaran</label>
                <select
                    wire:model.live="tahunAnggaranId"
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2.5 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                >
                    @foreach ($daftarTahun as $ta)
                        <option value="{{ $ta->id }}">TA {{ $ta->tahun }} ({{ ucfirst($ta->status) }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit / Divisi</label>
                <x-searchable-select
                    wire:model.live="unitId"
                    :options="$daftarUnit"
                    placeholder="Semua Unit & Divisi"
                    null-label="Semua Unit & Divisi"
                    search-placeholder="Cari unit atau divisi..."
                />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Pos</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-2.5 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                >
                    <option value="">Semua Status</option>
                    <option value="Aman">Aman</option>
                    <option value="Limit">Limit (&ge;80%)</option>
                    <option value="Over Budget">Over Budget (&gt;100%)</option>
                    <option value="Tanpa Budget">Tanpa Budget</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cari Pos Anggaran</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Kode atau nama pos..."
                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 focus:bg-white focus:border-blue-500 focus:outline-hidden"
                />
            </div>
        </div>
    </div>

    {{-- Monitoring Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Pratinjau Data Anggaran
            </h2>
            <span class="text-xs text-slate-500 font-medium">
                Total Pos: {{ $monitoringRows->count() }}
            </span>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3.5">Kode</th>
                        <th class="py-3 px-3.5">Pos Anggaran</th>
                        <th class="py-3 px-3.5 text-right">Plafon RAB</th>
                        <th class="py-3 px-3.5 text-right">Dana Terikat</th>
                        <th class="py-3 px-3.5 text-right">Realisasi</th>
                        <th class="py-3 px-3.5 text-right">Sisa Tersedia</th>
                        <th class="py-3 px-3.5 text-center">Pemakaian</th>
                        <th class="py-3 px-3.5 text-center">Status</th>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($monitoringRows as $row)
                        @php
                            $depth = $row['depth'] ?? 1;
                            $indentPx = ($depth - 1) * 16;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ ($row['depth'] ?? 1) === 1 ? 'font-semibold bg-slate-50/40' : '' }}">
                            <td class="py-2.5 px-3.5 font-mono text-slate-900 whitespace-nowrap">{{ $row['kode'] }}</td>
                            <td class="py-2.5 px-3.5">
                                <div style="padding-left: {{ $indentPx }}px">
                                    {{ $row['nama'] }}
                                </div>
                            </td>
                            <td class="py-2.5 px-3.5 text-right tabular-nums">
                                @if ($unitId)
                                    <span class="text-slate-400">-</span>
                                @else
                                    Rp {{ number_format($row['effective_budget'], 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="py-2.5 px-3.5 text-right tabular-nums text-amber-700">
                                Rp {{ number_format($row['dana_terikat'], 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right tabular-nums text-emerald-700">
                                Rp {{ number_format($row['realisasi'], 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right tabular-nums font-medium text-slate-900">
                                @if ($unitId)
                                    <span class="text-slate-400">-</span>
                                @else
                                    Rp {{ number_format($row['sisa_tersedia'], 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="py-2.5 px-3.5 text-center tabular-nums">
                                {{ $row['pemakaian_persen'] }}%
                            </td>
                            <td class="py-2.5 px-3.5 text-center whitespace-nowrap">
                                @if ($row['status'] === 'Aman')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aman</span>
                                @elseif ($row['status'] === 'Limit')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Limit</span>
                                @elseif ($row['status'] === 'Over Budget')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Over Budget</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">Tanpa Budget</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                Tidak ada data pos anggaran yang memenuhi kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
