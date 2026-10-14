<div class="space-y-6">
    {{-- Header & Export Action --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Laporan Pengeluaran Bulanan
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Matriks realisasi penyerapan anggaran per bulan (Januari s.d Desember) sesuai standar pelaporan institusi.
            </p>
        </div>

        {{-- Excel Export Button (tap target >= 44px) --}}
        <div>
            <button
                type="button"
                wire:click="exportExcel"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Ekspor Excel (.xlsx)</span>
            </button>
        </div>
    </div>

    {{-- Ringkasan Matriks Eksekutif (4 Kartu Mini) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block">Total Plafon RAB</span>
            <p class="text-base sm:text-lg font-bold text-slate-900 tabular-nums mt-1">
                Rp {{ number_format($totalPlafonAll, 0, ',', '.') }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-700 block">Total Realisasi</span>
            <p class="text-base sm:text-lg font-bold text-slate-900 tabular-nums mt-1">
                Rp {{ number_format($totalPengeluaranAll, 0, ',', '.') }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-[#0F2F57] block">Sisa Anggaran</span>
            <p class="text-base sm:text-lg font-bold text-[#0F2F57] tabular-nums mt-1">
                Rp {{ number_format($sisaAll, 0, ',', '.') }}
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700 block">Serapan Kumulatif</span>
            <p class="text-base sm:text-lg font-bold text-emerald-800 tabular-nums mt-1">
                {{ $serapanAll }}%
            </p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4">
        <div class="flex items-center gap-2.5">
            <label for="ta-filter" class="text-xs font-medium text-slate-600 shrink-0">
                Tahun Anggaran:
            </label>
            <select
                id="ta-filter"
                wire:model.live="tahun_anggaran_id"
                class="min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-slate-50 focus:bg-white focus:border-[#1E60D5] focus:outline-hidden"
            >
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">
                        TA {{ $year->tahun }} ({{ ucfirst($year->status) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 sm:max-w-md sm:ml-auto">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kode atau nama pos..."
                    class="block w-full min-h-[44px] pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 placeholder:text-slate-400 bg-white focus:border-[#1E60D5] focus:ring-1 focus:ring-[#1E60D5] focus:outline-hidden"
                />
            </div>

            <select
                wire:model.live="selectedMonth"
                class="min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-700 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden shrink-0"
            >
                <option value="">Semua Bulan (Jan - Des)</option>
                @foreach ($monthNames as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- VIEW 1: MOBILE-FIRST (< 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @forelse ($reportData as $row)
            @php
                $isExpanded = in_array($row['id'], $expandedMobileNodes);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-[#DCE5F6] text-[#001B3E]">
                                {{ $row['kode'] }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                Depth {{ $row['depth'] }}
                            </span>
                        </div>
                        <h2 class="text-sm font-bold text-slate-900 leading-snug">
                            {{ $row['nama'] }}
                        </h2>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="text-[10px] text-slate-400 block">Serapan</span>
                        <span class="text-xs font-bold text-emerald-800 tabular-nums">
                            {{ $row['persentase_penyerapan'] }}%
                        </span>
                    </div>
                </div>

                {{-- Progress Bar Penyerapan --}}
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300 {{ $row['persentase_penyerapan'] > 100 ? 'bg-red-600' : ($row['persentase_penyerapan'] >= 80 ? 'bg-amber-500' : 'bg-emerald-600') }}"
                        style="width: {{ min(100, $row['persentase_penyerapan']) }}%"
                    ></div>
                </div>

                {{-- Ringkasan Nominal (Plafon vs Total) --}}
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">RAB {{ $selectedYear ? $selectedYear->tahun : '2026' }}</span>
                        <span class="font-bold text-slate-900 tabular-nums mt-0.5 block">
                            Rp {{ number_format($row['plafon_rab'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Total Realisasi</span>
                        <span class="font-bold text-slate-900 tabular-nums mt-0.5 block">
                            Rp {{ number_format($row['total_pengeluaran'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Accordion Toggle Button (tap target >= 44px) --}}
                <button
                    type="button"
                    wire:click="toggleMobileNode({{ $row['id'] }})"
                    class="w-full min-h-[44px] flex items-center justify-between px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-xs font-semibold text-slate-700 transition-colors"
                >
                    <span>Rincian Bulanan (Jan - Des)</span>
                    <svg class="w-4 h-4 transform transition-transform {{ $isExpanded ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- Grid 12 Bulan (Tanpa Overflow Horizontal) --}}
                @if ($isExpanded)
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-xs">
                        @foreach ($monthNames as $mNum => $mName)
                            @if ($selectedMonth === null || $selectedMonth == $mNum)
                                <div class="p-2 rounded-lg border border-slate-200/80 bg-slate-50/50">
                                    <span class="text-[10px] font-semibold text-slate-400 block">{{ $mName }}</span>
                                    <span class="font-bold text-slate-800 tabular-nums mt-0.5 block">
                                        Rp {{ number_format($row['months'][$mNum], 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                Tidak ada data kode anggaran untuk laporan pengeluaran.
            </div>
        @endforelse
    </div>

    {{-- VIEW 2: DESKTOP (Horizontal Scroll Container Terisolasi, >= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto max-w-full">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-[#0F2F57] text-white font-semibold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-4 sticky left-0 z-20 bg-[#0F2F57]">Kode Pos</th>
                        <th class="py-3 px-4 sticky left-28 z-20 bg-[#0F2F57]">Keterangan Pos Anggaran</th>
                        <th class="py-3 px-4 text-right bg-[#0F2F57]">RAB {{ $selectedYear ? $selectedYear->tahun : '2026' }}</th>
                        @foreach ($monthNames as $mNum => $mName)
                            <th class="py-3 px-3 text-right bg-[#0F2F57]">{{ strtoupper(substr($mName, 0, 3)) }}</th>
                        @endforeach
                        <th class="py-3 px-4 text-right bg-[#0F2F57]">Total</th>
                        <th class="py-3 px-4 text-right bg-[#0F2F57]">Sisa</th>
                        <th class="py-3 px-3 text-center bg-[#0F2F57]">Serapan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reportData as $row)
                        @php
                            $paddingLeft = ($row['depth'] - 1) * 16;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $row['depth'] === 1 ? 'font-bold bg-slate-50/40' : '' }}">
                            {{-- Sticky Column 1: Kode --}}
                            <td class="py-3 px-4 font-mono sticky left-0 z-10 bg-white group-hover:bg-slate-50">
                                <span class="px-2 py-0.5 rounded-md bg-[#DCE5F6] text-[#001B3E] font-bold text-[11px]">
                                    {{ $row['kode'] }}
                                </span>
                            </td>

                            {{-- Sticky Column 2: Nama --}}
                            <td class="py-3 px-4 sticky left-28 z-10 bg-white group-hover:bg-slate-50 max-w-xs truncate">
                                <span style="margin-left: {{ $paddingLeft }}px" class="inline-flex items-center gap-1.5">
                                    @if ($row['depth'] > 1)
                                        <span class="text-slate-300">└</span>
                                    @endif
                                    <span class="text-slate-900 truncate">{{ $row['nama'] }}</span>
                                </span>
                            </td>

                            {{-- Plafon RAB --}}
                            <td class="py-3 px-4 text-right font-semibold tabular-nums text-slate-900">
                                Rp {{ number_format($row['plafon_rab'], 0, ',', '.') }}
                            </td>

                            {{-- 12 Bulan --}}
                            @foreach ($monthNames as $mNum => $mName)
                                <td class="py-3 px-3 text-right tabular-nums text-slate-600">
                                    {{ $row['months'][$mNum] > 0 ? number_format($row['months'][$mNum], 0, ',', '.') : '-' }}
                                </td>
                            @endforeach

                            {{-- Total Realisasi --}}
                            <td class="py-3 px-4 text-right font-bold tabular-nums text-slate-900 bg-slate-50/50">
                                Rp {{ number_format($row['total_pengeluaran'], 0, ',', '.') }}
                            </td>

                            {{-- Sisa --}}
                            <td class="py-3 px-4 text-right font-semibold tabular-nums text-slate-800">
                                Rp {{ number_format($row['sisa_anggaran'], 0, ',', '.') }}
                            </td>

                            {{-- % Penyerapan --}}
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $row['persentase_penyerapan'] >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $row['persentase_penyerapan'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="py-8 text-center text-slate-500">
                                Tidak ada data pos anggaran yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
