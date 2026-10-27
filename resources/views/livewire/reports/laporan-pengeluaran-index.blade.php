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
                id="bulan-filter"
                aria-label="Filter bulan"
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

    @php
        $isMonthFiltered = $selectedMonth !== null;
        $visibleMonths = $isMonthFiltered ? [$selectedMonth => $monthNames[$selectedMonth]] : $monthNames;
        $highlightMonth = ($selectedYear && (int) $selectedYear->tahun === (int) now()->year) ? (int) now()->month : null;
        $tableWidthClass = $isMonthFiltered ? 'w-full table-fixed' : 'w-[1888px] table-fixed';

        $headKode = $isMonthFiltered ? '' : 'sticky left-0 z-30';
        $headKeterangan = $isMonthFiltered ? '' : 'sticky left-[96px] z-30 border-r border-white/10 shadow-[6px_0_8px_-6px_rgba(0,0,0,0.45)]';
        $headTotal = $isMonthFiltered ? '' : 'sticky right-[168px] z-30 border-l border-white/10 shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.45)]';
        $headSisa = $isMonthFiltered ? '' : 'sticky right-[56px] z-30';
        $headSerapan = $isMonthFiltered ? '' : 'sticky right-0 z-30';

        $bodyKode = $isMonthFiltered ? '' : 'sticky left-0 z-10';
        $bodyKeterangan = $isMonthFiltered ? '' : 'sticky left-[96px] z-10 border-r shadow-[6px_0_8px_-6px_rgba(15,47,87,0.25)]';
        $bodyTotal = $isMonthFiltered ? '' : 'sticky right-[168px] z-10 border-l shadow-[-6px_0_8px_-6px_rgba(15,47,87,0.25)]';
        $bodySisa = $isMonthFiltered ? '' : 'sticky right-[56px] z-10';
        $bodySerapan = $isMonthFiltered ? '' : 'sticky right-0 z-10';
    @endphp

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
                    <span>Rincian Bulanan {{ $isMonthFiltered ? $monthNames[$selectedMonth] : '(Jan - Des)' }}</span>
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
    <div
        class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs"
        x-data="{
            isScrollable: {{ ! $isMonthFiltered ? 'true' : 'false' }},
            isDown: false,
            isDragging: false,
            startX: 0,
            scrollLeft: 0,
            canScrollLeft: false,
            canScrollRight: true,
            init() {
                if (!this.isScrollable) return;
                this.$nextTick(() => {
                    this.updateScrollState();
                });
                const container = this.$refs.matrixContainer;
                if (container) {
                    container.addEventListener('scroll', () => this.updateScrollState(), { passive: true });
                    window.addEventListener('resize', () => this.updateScrollState(), { passive: true });
                }
            },
            updateScrollState() {
                const el = this.$refs.matrixContainer;
                if (!el) return;
                this.canScrollLeft = el.scrollLeft > 10;
                this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 10);
            },
            scrollBy(amount) {
                const el = this.$refs.matrixContainer;
                if (!el) return;
                el.scrollBy({ left: amount, behavior: 'smooth' });
            },
            scrollToStart() {
                const el = this.$refs.matrixContainer;
                if (!el) return;
                el.scrollTo({ left: 0, behavior: 'smooth' });
            },
            scrollToEnd() {
                const el = this.$refs.matrixContainer;
                if (!el) return;
                el.scrollTo({ left: el.scrollWidth, behavior: 'smooth' });
            },
            startDrag(e) {
                if (!this.isScrollable || e.button !== 0) return;
                this.isDown = true;
                this.isDragging = false;
                this.startX = e.pageX;
                const el = this.$refs.matrixContainer;
                this.scrollLeft = el.scrollLeft;
            },
            doDrag(e) {
                if (!this.isDown || !this.isScrollable) return;
                const walk = e.pageX - this.startX;
                if (Math.abs(walk) > 3) {
                    this.isDragging = true;
                    e.preventDefault();
                    const el = this.$refs.matrixContainer;
                    el.scrollLeft = this.scrollLeft - walk;
                }
            },
            stopDrag() {
                if (!this.isDown) return;
                this.isDown = false;
                setTimeout(() => {
                    this.isDragging = false;
                }, 50);
            }
        }"
        @mouseup.window="stopDrag()"
        @mousemove.window="doDrag($event)"
    >
        @if (! $isMonthFiltered)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-2.5 border-b border-slate-200 bg-slate-50/80 text-xs text-slate-600">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-[#DCE5F6] text-[#0F2F57] shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </span>
                    <p class="text-[11px] sm:text-xs text-slate-600 font-medium">
                        <span class="font-semibold text-slate-900">Geser Matriks:</span>
                        Klik &amp; seret kursor mouse untuk menggeser bulan, atau gunakan tombol navigasi. Kolom kode &amp; ringkasan tetap terkunci.
                    </p>
                </div>

                {{-- Tombol Navigasi Geser Horizontal --}}
                <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
                    <button
                        type="button"
                        @click="scrollToStart()"
                        :disabled="!canScrollLeft"
                        :class="!canScrollLeft ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300 shadow-2xs cursor-pointer'"
                        class="min-h-[30px] px-2.5 py-1 rounded-lg border text-[11px] font-semibold inline-flex items-center gap-1 transition-colors"
                        title="Geser ke awal (Januari)"
                        aria-label="Geser ke awal Januari"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                        <span>Awal</span>
                    </button>

                    <button
                        type="button"
                        @click="scrollBy(-320)"
                        :disabled="!canScrollLeft"
                        :class="!canScrollLeft ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300 shadow-2xs cursor-pointer'"
                        class="min-h-[30px] px-2.5 py-1 rounded-lg border text-[11px] font-semibold inline-flex items-center gap-1 transition-colors"
                        title="Geser ke kiri"
                        aria-label="Geser ke kiri"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kiri</span>
                    </button>

                    <button
                        type="button"
                        @click="scrollBy(320)"
                        :disabled="!canScrollRight"
                        :class="!canScrollRight ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300 shadow-2xs cursor-pointer'"
                        class="min-h-[30px] px-2.5 py-1 rounded-lg border text-[11px] font-semibold inline-flex items-center gap-1 transition-colors"
                        title="Geser ke kanan"
                        aria-label="Geser ke kanan"
                    >
                        <span>Kanan</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="scrollToEnd()"
                        :disabled="!canScrollRight"
                        :class="!canScrollRight ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300 shadow-2xs cursor-pointer'"
                        class="min-h-[30px] px-2.5 py-1 rounded-lg border text-[11px] font-semibold inline-flex items-center gap-1 transition-colors"
                        title="Geser ke akhir (Desember)"
                        aria-label="Geser ke akhir Desember"
                    >
                        <span>Akhir</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div
            x-ref="matrixContainer"
            @mousedown="startDrag($event)"
            class="overflow-x-auto max-w-full focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[#1E60D5] [&::-webkit-scrollbar]:h-2.5 [&::-webkit-scrollbar-track]:bg-slate-100 [&::-webkit-scrollbar-thumb]:bg-slate-300 hover:[&::-webkit-scrollbar-thumb]:bg-slate-400 [&::-webkit-scrollbar-thumb]:rounded-full transition-[cursor]"
            :class="{
                'cursor-grab': isScrollable && !isDragging,
                'cursor-grabbing select-none [&_*]:select-none': isDragging
            }"
            tabindex="0"
            role="region"
            aria-label="Matriks laporan pengeluaran bulanan"
        >
            <table
                class="{{ $tableWidthClass }} text-left text-xs whitespace-nowrap"
                :class="isScrollable ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : ''"
            >
                <colgroup>
                    @if ($isMonthFiltered)
                        <col class="w-[88px]">
                        <col>
                        <col class="w-[100px]">
                        <col class="w-[100px]">
                        <col class="w-[100px]">
                        <col class="w-[100px]">
                        <col class="w-[56px]">
                    @else
                        <col class="w-[96px]">
                        <col class="w-[200px]">
                        <col class="w-[112px]">
                        @foreach ($visibleMonths as $mNum => $mName)
                            <col class="w-[100px]">
                        @endforeach
                        <col class="w-[112px]">
                        <col class="w-[112px]">
                        <col class="w-[56px]">
                    @endif
                </colgroup>
                <thead class="bg-[#0F2F57] text-white font-semibold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th scope="col" class="py-3 px-2 text-left bg-[#0F2F57] {{ $headKode }}">Kode Pos</th>
                        <th scope="col" class="py-3 px-3 text-left bg-[#0F2F57] truncate {{ $headKeterangan }}" title="Keterangan Pos Anggaran">Keterangan Pos Anggaran</th>
                        <th scope="col" class="py-3 px-2 text-right bg-[#0F2F57] text-[10px] tracking-normal">RAB {{ $selectedYear ? $selectedYear->tahun : '2026' }} (Rp)</th>
                        @foreach ($visibleMonths as $mNum => $mName)
                            <th scope="col" class="py-3 px-2 text-right {{ $highlightMonth === $mNum ? 'bg-[#1E60D5]' : 'bg-[#0F2F57]' }}">
                                {{ $isMonthFiltered ? strtoupper($mName) : strtoupper(substr($mName, 0, 3)) }}
                            </th>
                        @endforeach
                        <th scope="col" class="py-3 px-2 text-right bg-[#0F2F57] {{ $headTotal }}">Total (Rp)</th>
                        <th scope="col" class="py-3 px-2 text-right bg-[#0F2F57] {{ $headSisa }}">Sisa (Rp)</th>
                        <th scope="col" class="py-3 px-1 text-center bg-[#0F2F57] text-[10px] tracking-normal {{ $headSerapan }}">Serapan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reportData as $row)
                        @php
                            $paddingLeft = ($row['depth'] - 1) * 16;
                            $rowBg = $row['depth'] === 1 ? 'bg-slate-50 group-hover:bg-slate-100' : 'bg-white group-hover:bg-slate-50';
                            $summaryCellBg = $row['depth'] === 1 ? 'bg-slate-100 group-hover:bg-slate-200/70' : 'bg-slate-50 group-hover:bg-slate-100';
                        @endphp
                        <tr class="group transition-colors {{ $row['depth'] === 1 ? 'font-bold' : '' }}">
                            {{-- Sticky Column 1: Kode --}}
                            <td class="py-3 px-2 font-mono {{ $bodyKode }} {{ $rowBg }}">
                                <span class="px-2 py-0.5 rounded-md bg-[#DCE5F6] text-[#001B3E] font-bold text-[11px]">
                                    {{ $row['kode'] }}
                                </span>
                            </td>

                            {{-- Sticky Column 2: Nama --}}
                            <td class="py-3 px-3 overflow-hidden {{ $bodyKeterangan }} {{ $rowBg }}" title="{{ $row['nama'] }}">
                                <span class="flex min-w-0 items-center gap-1.5" style="padding-left: {{ $paddingLeft }}px">
                                    @if ($row['depth'] > 1)
                                        <span class="text-slate-300 shrink-0">└</span>
                                    @endif
                                    <span class="truncate min-w-0 text-slate-900">{{ $row['nama'] }}</span>
                                </span>
                            </td>

                            {{-- Plafon RAB --}}
                            <td class="py-3 px-2 text-right font-semibold tabular-nums text-slate-900 {{ $rowBg }}">
                                {{ number_format($row['plafon_rab'], 0, ',', '.') }}
                            </td>

                            {{-- Bulan (terfilter / 12 bulan) --}}
                            @foreach ($visibleMonths as $mNum => $mName)
                                <td class="py-3 px-2 text-right tabular-nums text-slate-600 {{ $highlightMonth === $mNum ? 'bg-[#DCE5F6] group-hover:bg-[#D3DFF4]' : $rowBg }}">
                                    {{ $row['months'][$mNum] > 0 ? number_format($row['months'][$mNum], 0, ',', '.') : '-' }}
                                </td>
                            @endforeach

                            {{-- Total Realisasi --}}
                            <td class="py-3 px-2 text-right font-bold tabular-nums text-slate-900 {{ $bodyTotal }} {{ $summaryCellBg }}">
                                {{ number_format($row['total_pengeluaran'], 0, ',', '.') }}
                            </td>

                            {{-- Sisa --}}
                            <td class="py-3 px-2 text-right font-semibold tabular-nums text-slate-800 {{ $bodySisa }} {{ $summaryCellBg }}">
                                {{ number_format($row['sisa_anggaran'], 0, ',', '.') }}
                            </td>

                            {{-- % Penyerapan --}}
                            <td class="py-3 px-1 text-center {{ $bodySerapan }} {{ $summaryCellBg }}">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $row['persentase_penyerapan'] >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $row['persentase_penyerapan'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + count($visibleMonths) }}" class="py-8 text-center text-slate-500">
                                Tidak ada data pos anggaran yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
