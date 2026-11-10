<div class="space-y-6 sm:space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#1E60D5]"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Laporan Keuangan</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57] mt-1">
                Rekap PPD per Orang
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pemantauan frekuensi penerbitan PPD, total realisasi dana cair, dan sebaran pos anggaran per pejabat pemohon.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="exportExcel"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="exportExcel" class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Excel (.xlsx)</span>
                </span>
                <span wire:loading wire:target="exportExcel" class="inline-flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Mengunduh...</span>
                </span>
            </button>
        </div>
    </div>

    {{-- Metric Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        {{-- Total PPD --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total PPD Dicairkan</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <p class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57] tabular-nums">
                    {{ number_format($totalPpd, 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-400">PPD</span>
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Berkas pencairan kas & transfer</p>
            </div>
        </div>

        {{-- Total Dana --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Total Realisasi Cair</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <p class="text-xl sm:text-2xl font-bold tracking-tight text-emerald-700 tabular-nums">
                    Rp {{ number_format($totalNominal, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi pencairan resmi</p>
            </div>
        </div>

        {{-- Pejabat Pemohon --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Pejabat Pemohon</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <p class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57] tabular-nums">
                    {{ number_format($totalOrang, 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-400">Orang</span>
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Penerima dana terdaftar</p>
            </div>
        </div>

        {{-- Rata-rata per PPD --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Rata-rata per PPD</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2">
                <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    Rp {{ number_format($rataRata, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Rata-rata nominal cair</p>
            </div>
        </div>
    </div>

    {{-- Main Container Card: Tabs, Filters, Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        {{-- Tab Switcher Bar --}}
        <div class="px-5 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
            <div class="inline-flex p-1 rounded-xl bg-slate-200/70 text-xs font-semibold">
                <button
                    type="button"
                    wire:click="setTab('rekap')"
                    class="px-3.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $activeTab === 'rekap' ? 'bg-white text-[#0F2F57] shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Rekapitulasi per Orang ({{ $totalOrang }})
                </button>
                <button
                    type="button"
                    wire:click="setTab('transaksi')"
                    class="px-3.5 py-1.5 rounded-lg transition-all cursor-pointer {{ $activeTab === 'transaksi' ? 'bg-white text-[#0F2F57] shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Semua Transaksi PPD ({{ $totalPpd }})
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Tahun Anggaran:</span>
                @if ($tahunFilter === 'all')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        <span>Semua Waktu (Lintas Tahun)</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#0F2F57] border border-blue-200 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-[#1E60D5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>TA {{ $selectedYear?->tahun ?? '-' }}</span>
                        @if ($selectedYear?->status === 'aktif')
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Aktif</span>
                        @elseif ($selectedYear?->status === 'draft')
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">Draft</span>
                        @else
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-700">Ditutup</span>
                        @endif
                    </span>
                @endif
            </div>
        </div>
        {{-- Filter Inputs Card --}}
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-white space-y-4">
            {{-- Top Row: Primary Search + Quick Year Pills + Filter Counter & Reset --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                {{-- Search Bar --}}
                <div class="relative flex-1 max-w-lg">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama pejabat, jabatan, no PPD, atau kegiatan..."
                        class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-9 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#1E60D5] focus:outline-hidden transition-all shadow-2xs"
                    />
                    @if ($search)
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Hapus pencarian"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                {{-- Quick Year Selector (Pills) & Reset --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex items-center gap-1 p-1 rounded-xl bg-slate-100 text-xs font-semibold">
                        <button
                            type="button"
                            wire:click="$set('tahunFilter', '')"
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer {{ $tahunFilter === '' ? 'bg-white text-[#0F2F57] shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            TA {{ $selectedYear?->tahun ?? '-' }} (Sesuai Pilihan)
                        </button>
                        <button
                            type="button"
                            wire:click="$set('tahunFilter', 'all')"
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer {{ $tahunFilter === 'all' ? 'bg-white text-[#0F2F57] shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            Semua Waktu
                        </button>
                    </div>

                    @if ($this->activeFilterCount > 0)
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            <span>Reset ({{ $this->activeFilterCount }})</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Second Row: Detailed Multi-Filter Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-slate-100">
                {{-- Filter 1: Unit / Divisi (Searchable Select) --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit / Divisi</label>
                    <x-searchable-select
                        wire:model.live="unitFilter"
                        :options="$unitOptions"
                        placeholder="Semua Unit & Divisi"
                        null-label="Semua Unit & Divisi"
                        search-placeholder="Ketik nama unit atau divisi..."
                    />
                </div>

                {{-- Filter 2: Bulan Cair --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Bulan Pencairan</label>
                    <select
                        id="bulanFilter"
                        wire:model.live="bulanFilter"
                        class="w-full text-xs rounded-xl border border-slate-300 bg-white min-h-[44px] py-2.5 px-3 text-slate-800 focus:border-[#1E60D5] focus:outline-hidden transition-colors shadow-2xs cursor-pointer"
                    >
                        <option value="">Semua Bulan (1 Tahun Penuh)</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                {{-- Filter 3: Sumber Dana --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sumber Dana</label>
                    <select
                        id="sumberDanaFilter"
                        wire:model.live="sumberDanaFilter"
                        class="w-full text-xs rounded-xl border border-slate-300 bg-white min-h-[44px] py-2.5 px-3 text-slate-800 focus:border-[#1E60D5] focus:outline-hidden transition-colors shadow-2xs cursor-pointer"
                    >
                        <option value="">Semua Sumber Dana</option>
                        @foreach ($sumberDanaList as $sd)
                            <option value="{{ $sd->id }}">{{ $sd->nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 4: Urutan Tampilan (Sorting) --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Urutkan Data</label>
                    <select
                        id="sortBy"
                        wire:model.live="sortBy"
                        class="w-full text-xs rounded-xl border border-slate-300 bg-white min-h-[44px] py-2.5 px-3 text-slate-800 focus:border-[#1E60D5] focus:outline-hidden transition-colors shadow-2xs cursor-pointer"
                    >
                        <option value="nominal_desc">Nominal Terbesar (#1 Teratas)</option>
                        <option value="nominal_asc">Nominal Terkecil</option>
                        <option value="ppd_desc">Frekuensi PPD Terbanyak</option>
                        <option value="ppd_asc">Frekuensi PPD Terendah</option>
                        <option value="name_asc">Nama Pejabat (A - Z)</option>
                    </select>
                </div>
            </div>

            {{-- Active Filter Chips Bar --}}
            @if ($this->activeFilterCount > 0)
                <div class="flex items-center gap-1.5 flex-wrap pt-2 border-t border-dashed border-slate-100 text-xs">
                    <span class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider mr-1">Filter Aktif:</span>

                    @if ($tahunFilter === 'all')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">
                            <span>Waktu: Semua Tahun</span>
                            <button type="button" wire:click="removeFilter('tahun')" class="hover:text-amber-950 cursor-pointer font-bold">&times;</button>
                        </span>
                    @endif

                    @if ($bulanFilter !== '')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-[#1E60D5] border border-blue-200 text-xs font-semibold">
                            <span>Bulan: {{ \Carbon\Carbon::create(2026, (int)$bulanFilter, 1)->translatedFormat('F') }}</span>
                            <button type="button" wire:click="removeFilter('bulan')" class="hover:text-blue-900 cursor-pointer font-bold">&times;</button>
                        </span>
                    @endif

                    @if ($unitFilter !== '')
                        @php $uName = collect($unitOptions)->firstWhere('id', (string)$unitFilter)['nama'] ?? 'Unit Terpilih'; @endphp
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-[#1E60D5] border border-blue-200 text-xs font-semibold">
                            <span class="truncate max-w-[200px]">Unit: {{ $uName }}</span>
                            <button type="button" wire:click="removeFilter('unit')" class="hover:text-blue-900 cursor-pointer font-bold">&times;</button>
                        </span>
                    @endif

                    @if ($sumberDanaFilter !== '')
                        @php $sdName = $sumberDanaList->firstWhere('id', (int)$sumberDanaFilter)?->nama ?? 'Sumber Dana'; @endphp
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-[#1E60D5] border border-blue-200 text-xs font-semibold">
                            <span>Sumber: {{ $sdName }}</span>
                            <button type="button" wire:click="removeFilter('sumber_dana')" class="hover:text-blue-900 cursor-pointer font-bold">&times;</button>
                        </span>
                    @endif

                    @if ($search !== '')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-[#1E60D5] border border-blue-200 text-xs font-semibold">
                            <span>Cari: "{{ $search }}"</span>
                            <button type="button" wire:click="removeFilter('search')" class="hover:text-blue-900 cursor-pointer font-bold">&times;</button>
                        </span>
                    @endif

                    @if ($sortBy !== 'nominal_desc')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold">
                            <span>Urut: {{ $this->sortLabel }}</span>
                            <button type="button" wire:click="removeFilter('sort')" class="hover:text-slate-900 cursor-pointer font-bold">&times;</button>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- TAB 1: REKAPITULASI PER ORANG --}}
        @if ($activeTab === 'rekap')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-100">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 text-center w-12">#</th>
                            <th class="py-3 px-4">Pejabat Pemohon</th>
                            <th class="py-3 px-4">Unit / Divisi</th>
                            <th class="py-3 px-4 text-center">Frekuensi PPD</th>
                            <th class="py-3 px-4 text-right">Total Realisasi (Rp)</th>
                            <th class="py-3 px-4 w-44">Kontribusi</th>
                            <th class="py-3 px-4">Pos Anggaran</th>
                            <th class="py-3 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        @forelse ($rekapPerUser as $index => $row)
                            <tr class="hover:bg-blue-50/20 transition-colors">
                                {{-- Ranking Badge --}}
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                    @if ($index === 0 && $row['total_nominal'] > 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 text-xs font-bold" title="Peringkat #1 Penyerapan">
                                            1
                                        </span>
                                    @elseif ($index === 1 && $row['total_nominal'] > 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-800 text-xs font-bold" title="Peringkat #2 Penyerapan">
                                            2
                                        </span>
                                    @elseif ($index === 2 && $row['total_nominal'] > 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-50 text-amber-900 border border-amber-200 text-xs font-bold" title="Peringkat #3 Penyerapan">
                                            3
                                        </span>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>

                                {{-- Pejabat Pemohon --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-[#0F2F57] text-white flex items-center justify-center font-bold text-xs shrink-0 tracking-wider">
                                            {{ $row['initials'] }}
                                        </div>
                                        <div class="min-w-0">
                                            <button
                                                type="button"
                                                wire:click="openUserDetail({{ $row['user_id'] }})"
                                                class="font-bold text-slate-900 hover:text-[#1E60D5] text-xs text-left truncate block transition-colors cursor-pointer"
                                            >
                                                {{ $row['name'] }}
                                            </button>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[11px] text-slate-500 truncate">{{ $row['jabatan'] }}</span>
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                    {{ $row['leveling_nama'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Unit / Divisi --}}
                                <td class="py-3.5 px-4 text-slate-600 text-xs">
                                    {{ $row['unit'] }}
                                </td>

                                {{-- Frekuensi PPD --}}
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#1E60D5] border border-blue-200 tabular-nums">
                                        {{ $row['ppd_count'] }}x PPD
                                    </span>
                                </td>

                                {{-- Total Nominal --}}
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900 tabular-nums text-sm">
                                    Rp {{ number_format($row['total_nominal'], 0, ',', '.') }}
                                </td>

                                {{-- Kontribusi Serapan --}}
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium">
                                            <span>Porsi Serapan</span>
                                            <span class="font-bold text-slate-700 tabular-nums">{{ $row['persentase'] }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-[#1E60D5] h-1.5 rounded-full" style="width: {{ min(100, $row['persentase']) }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Pos Anggaran Digunakan --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-[#0F2F57] border border-slate-200">
                                            {{ count($row['pos_anggaran']) }} Pos
                                        </span>
                                        @foreach ($row['pos_anggaran']->take(2) as $pos)
                                            <span class="inline-flex items-center font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-50 text-slate-600 border border-slate-200" title="{{ $pos->nama }}">
                                                {{ $pos->kode }}
                                            </span>
                                        @endforeach
                                        @if (count($row['pos_anggaran']) > 2)
                                            <span class="text-[10px] text-slate-400">+{{ count($row['pos_anggaran']) - 2 }} lainnya</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Action Button --}}
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        wire:click="openUserDetail({{ $row['user_id'] }})"
                                        class="px-3 py-1.5 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100/70 text-[#1E60D5] text-xs font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer"
                                    >
                                        <span>Rincian PPD</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-16 text-center">
                                    <div class="max-w-xs mx-auto text-center space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xs font-bold text-slate-800">Tidak Ada Data Pencairan PPD</h3>
                                            <p class="text-[11px] text-slate-500 mt-1">
                                                Belum ada permohonan yang dicairkan pada tahun anggaran atau filter unit yang dipilih.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="resetFilters"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        {{-- TAB 2: SEMUA TRANSAKSI PPD INDIVIDUAL --}}
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-100">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">No. PPD</th>
                            <th class="py-3 px-4">Tanggal Cair</th>
                            <th class="py-3 px-4">Pejabat Pemohon</th>
                            <th class="py-3 px-4">Unit / Divisi</th>
                            <th class="py-3 px-4">No. Dokumen & Kegiatan</th>
                            <th class="py-3 px-4">Pos Anggaran</th>
                            <th class="py-3 px-4 text-right">Nominal (Rp)</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        @forelse ($allPencairans as $p)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-[#1E60D5]">
                                    {{ $p->no_ppd }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 tabular-nums">
                                    {{ $p->tgl_cair ? $p->tgl_cair->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $p->permohonan?->user?->name ?? '-' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $p->permohonan?->user?->jabatan ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $p->permohonan?->unit?->nama_lengkap ?? '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800">{{ $p->permohonan?->nama_kegiatan ?? '-' }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">{{ $p->permohonan?->no_pengajuan ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @if ($p->permohonan?->kodeAnggaran)
                                        <div class="font-mono text-[11px] font-bold text-[#0F2F57]">{{ $p->permohonan->kodeAnggaran->kode }}</div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-[200px]">{{ $p->permohonan->kodeAnggaran->nama }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900 tabular-nums">
                                    Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a
                                        href="{{ route('permohonan.detail', $p->permohonan_id) }}"
                                        wire:navigate
                                        class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-[11px] font-semibold inline-flex items-center gap-1"
                                    >
                                        <span>Buka</span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Tidak ada data transaksi PPD pada filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- MODAL RINCIAN PPD PEJABAT PEMOHON --}}
    @if ($selectedUserSummary)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div
                @click.outside="$wire.closeUserDetail()"
                class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 space-y-5 border border-slate-100 animate-in fade-in zoom-in-95 duration-150"
            >
                {{-- Modal Header --}}
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-[#0F2F57] text-white flex items-center justify-center font-bold text-base shrink-0 tracking-wider shadow-sm">
                            {{ $selectedUserSummary['initials'] }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-[#0F2F57]">{{ $selectedUserSummary['name'] }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $selectedUserSummary['jabatan'] }} &bull; <span class="font-semibold text-slate-700">{{ $selectedUserSummary['unit'] }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeUserDetail"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer"
                        title="Tutup rincian"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Mini Summary Cards --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 block">Frekuensi PPD</span>
                        <span class="text-lg font-bold text-[#0F2F57] block mt-0.5 tabular-nums">
                            {{ $selectedUserSummary['ppd_count'] }} kali pencairan
                        </span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Total Realisasi</span>
                        <span class="text-lg font-bold text-emerald-800 block mt-0.5 tabular-nums">
                            Rp {{ number_format($selectedUserSummary['total_nominal'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Rata-rata per PPD</span>
                        <span class="text-lg font-bold text-slate-800 block mt-0.5 tabular-nums">
                            Rp {{ number_format(round($selectedUserSummary['total_nominal'] / max(1, $selectedUserSummary['ppd_count'])), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Pos Anggaran Digunakan --}}
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-700 block">Pos / Mata Anggaran yang Digunakan:</span>
                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto p-1 bg-slate-50/50 rounded-xl border border-slate-100">
                        @foreach ($selectedUserSummary['pos_anggaran'] as $pos)
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 shadow-2xs">
                                <span class="font-mono font-bold text-[#0F2F57]">{{ $pos->kode }}</span>
                                <span class="text-slate-500 truncate max-w-[220px]">{{ $pos->nama }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Riwayat Tabel PPD --}}
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-700 block">Riwayat Seluruh Penerbitan PPD:</span>
                    <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs divide-y divide-slate-100">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase sticky top-0">
                                <tr>
                                    <th class="py-2.5 px-3">No. PPD</th>
                                    <th class="py-2.5 px-3">Tanggal Cair</th>
                                    <th class="py-2.5 px-3">Kegiatan</th>
                                    <th class="py-2.5 px-3 text-right">Nominal (Rp)</th>
                                    <th class="py-2.5 px-3 text-center">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                @foreach ($selectedUserSummary['pencairan_items'] as $p)
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-2 px-3 font-mono font-bold text-[#1E60D5]">
                                            {{ $p->no_ppd }}
                                        </td>
                                        <td class="py-2 px-3 tabular-nums">
                                            {{ $p->tgl_cair ? $p->tgl_cair->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="font-semibold text-slate-800">{{ $p->permohonan?->nama_kegiatan ?? '-' }}</div>
                                            <div class="font-mono text-[10px] text-slate-400">{{ $p->permohonan?->no_pengajuan }}</div>
                                        </td>
                                        <td class="py-2 px-3 text-right font-bold text-slate-900 tabular-nums">
                                            Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <a
                                                href="{{ route('permohonan.detail', $p->permohonan_id) }}"
                                                wire:navigate
                                                class="px-2 py-1 rounded-lg border border-slate-200 text-[#1E60D5] hover:bg-blue-50 text-[10px] font-semibold"
                                            >
                                                Buka
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        wire:click="closeUserDetail"
                        class="min-h-[40px] px-5 py-2 rounded-full bg-[#0F2F57] hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
