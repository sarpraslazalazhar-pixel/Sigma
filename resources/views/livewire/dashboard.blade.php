<div class="space-y-6 sm:space-y-8">
    {{-- Header & Timestamp --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-semibold text-slate-500">Selamat Datang, {{ auth()->user()?->name }}</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500">Kondisi Anggaran Tahun {{ $daftarTahun->firstWhere('id', $tahunAnggaranId)?->tahun ?? date('Y') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57]">
                Dashboard Monitoring Anggaran
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pemantauan alokasi dana, realisasi program, dan antrean persetujuan dokumen institusi
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs font-medium text-slate-500 bg-white border border-slate-200 px-3 py-1.5 rounded-full shadow-2xs self-start sm:self-auto">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Data per: <strong class="text-slate-700">{{ $timestamp }}</strong></span>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0F2F57]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter Monitoring
            </span>
            <button
                type="button"
                wire:click="resetFilters"
                class="text-xs font-medium text-blue-700 hover:text-blue-900 transition-colors"
            >
                Reset Filter
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Tahun Anggaran --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tahun Anggaran</label>
                <select
                    wire:model.live="tahunAnggaranId"
                    class="w-full text-xs rounded-xl border border-slate-300 bg-white min-h-[44px] py-2.5 px-3 text-slate-800 focus:border-[#1E60D5] focus:outline-hidden"
                >
                    @foreach ($daftarTahun as $ta)
                        <option value="{{ $ta->id }}">TA {{ $ta->tahun }} ({{ ucfirst($ta->status) }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Bulan s.d. --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Bulan s.d.</label>
                <select
                    wire:model.live="bulanSd"
                    class="w-full text-xs rounded-xl border border-slate-300 bg-white min-h-[44px] py-2.5 px-3 text-slate-800 focus:border-[#1E60D5] focus:outline-hidden"
                >
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

            {{-- Kode Anggaran --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kode Anggaran</label>
                <x-searchable-select
                    wire:model.live="kodeAnggaranId"
                    :options="$daftarKodeAnggaran"
                    placeholder="Semua Pos Anggaran"
                    null-label="Semua Pos Anggaran"
                    search-placeholder="Cari kode atau nama pos..."
                />
            </div>

            {{-- Sumber Dana --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sumber Dana</label>
                <x-searchable-select
                    wire:model.live="sumberDanaId"
                    :options="$daftarSumberDana"
                    placeholder="Semua Sumber Dana"
                    null-label="Semua Sumber Dana"
                    search-placeholder="Cari sumber dana..."
                />
            </div>

            {{-- Unit / Divisi --}}
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
        </div>
    </div>

    {{-- KPI Cards (5 metrics) --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        {{-- Card 1: Total Budget Disahkan --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Plafon RAB</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-[#0F2F57] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </span>
                </div>
                @if (is_null($kpiBudget))
                    <p class="text-sm font-semibold text-slate-400 mt-2">N/A (filter divisi)</p>
                @else
                    <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                        Rp {{ number_format($kpiBudget, 0, ',', '.') }}
                    </p>
                @endif
            </div>
            <p class="text-[10px] text-slate-400 mt-3 pt-2 border-t border-slate-100">Plafon disahkan tingkat induk</p>
        </div>

        {{-- Card 2: Dana Terikat --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Dana Terikat</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                </div>
                <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    Rp {{ number_format($kpiTerikat, 0, ',', '.') }}
                </p>
            </div>
            <p class="text-[10px] text-slate-400 mt-3 pt-2 border-t border-slate-100">Permohonan disetujui keuangan & cair</p>
        </div>

        {{-- Card 3: Realisasi --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Realisasi</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    Rp {{ number_format($kpiRealisasi, 0, ',', '.') }}
                </p>
            </div>
            <p class="text-[10px] text-slate-400 mt-3 pt-2 border-t border-slate-100">Berdasarkan dokumen UMP selesai</p>
        </div>

        {{-- Card 4: Sisa Tersedia --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Sisa Tersedia</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </span>
                </div>
                @if (is_null($kpiSisa))
                    <p class="text-sm font-semibold text-slate-400 mt-2">N/A (filter divisi)</p>
                @else
                    <p class="text-xl sm:text-2xl font-bold tracking-tight text-blue-900 tabular-nums">
                        Rp {{ number_format($kpiSisa, 0, ',', '.') }}
                    </p>
                @endif
            </div>
            <p class="text-[10px] text-slate-400 mt-3 pt-2 border-t border-slate-100">Budget - Terikat - Realisasi</p>
        </div>

        {{-- Card 5: Serapan (%) --}}
        <div class="col-span-2 lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Serapan Anggaran</span>
                    <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold">%</span>
                    </span>
                </div>
                @if (is_null($kpiSerapan))
                    <p class="text-sm font-semibold text-slate-400 mt-2">N/A</p>
                @else
                    <p class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                        {{ $kpiSerapan }}%
                    </p>
                @endif
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100">
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min(100, $kpiSerapan ?? 0) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Chart --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Tren Pengeluaran & Keterikatan Dana Bulanan
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Perbandingan alokasi budget, dana terikat permohonan, dan realisasi UMP s.d. bulan dipilih
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium text-slate-600">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#0F2F57]"></span> Budget
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#D97706]"></span> Terikat
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#059669]"></span> Realisasi
                </span>
            </div>
        </div>

        <div class="h-64 sm:h-72 w-full relative" wire:ignore>
            <canvas id="budgetChart"></canvas>
        </div>
    </div>

    {{-- Budget Health Summary Chips --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Kesehatan Pos Anggaran:</span>
            <span class="text-xs text-slate-500">(Klik chip untuk filter tabel)</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="setStatusFilter('Aman')"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all {{ $statusFilter === 'Aman' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}"
            >
                <span class="w-2 h-2 rounded-full {{ $statusFilter === 'Aman' ? 'bg-white' : 'bg-emerald-500' }}"></span>
                <span>Aman ({{ $healthSummary['Aman'] }})</span>
            </button>

            <button
                type="button"
                wire:click="setStatusFilter('Limit')"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all {{ $statusFilter === 'Limit' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}"
            >
                <span class="w-2 h-2 rounded-full {{ $statusFilter === 'Limit' ? 'bg-white' : 'bg-amber-500' }}"></span>
                <span>Limit ({{ $healthSummary['Limit'] }})</span>
            </button>

            <button
                type="button"
                wire:click="setStatusFilter('Over Budget')"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all {{ $statusFilter === 'Over Budget' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100' }}"
            >
                <span class="w-2 h-2 rounded-full {{ $statusFilter === 'Over Budget' ? 'bg-white' : 'bg-rose-500' }}"></span>
                <span>Over Budget ({{ $healthSummary['Over Budget'] }})</span>
            </button>

            <button
                type="button"
                wire:click="setStatusFilter('Tanpa Budget')"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all {{ $statusFilter === 'Tanpa Budget' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200' }}"
            >
                <span class="w-2 h-2 rounded-full {{ $statusFilter === 'Tanpa Budget' ? 'bg-white' : 'bg-slate-400' }}"></span>
                <span>Tanpa Budget ({{ $healthSummary['Tanpa Budget'] }})</span>
            </button>

            @if ($statusFilter !== '')
                <button
                    type="button"
                    wire:click="setStatusFilter('{{ $statusFilter }}')"
                    class="text-xs text-slate-400 hover:text-slate-600 underline ml-1"
                >
                    Hapus Filter Status
                </button>
            @endif
        </div>
    </div>

    {{-- Two Column Panels: Perlu Perhatian & Antrean Persetujuan --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Panel Kiri: Perlu Perhatian --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            Perlu Perhatian (Pos Kritis)
                        </h2>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-100">
                        {{ $perluPerhatian->count() }} Pos
                    </span>
                </div>

                @if ($perluPerhatian->isEmpty())
                    <div class="py-12 text-center border border-dashed border-slate-200 rounded-xl">
                        <svg class="w-10 h-10 mx-auto text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <p class="text-xs font-semibold text-slate-700">Kondisi Anggaran Prima</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada pos anggaran yang melampaui batas Limit (80%) atau Over Budget (100%).</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($perluPerhatian as $item)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-[#0F2F57]">{{ $item['kode'] }}</span>
                                        <span class="text-xs font-medium text-slate-800 truncate">{{ $item['nama'] }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Pemakaian: Rp {{ number_format(($item['dana_terikat'] ?? 0) + ($item['realisasi'] ?? 0), 0, ',', '.') }} /
                                        Plafon: Rp {{ number_format($item['effective_budget'] ?? 0, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $item['status'] === 'Over Budget' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $item['pemakaian_persen'] }}% • {{ $item['status'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <p class="text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-100">Ambang: Limit &ge; 80%, Over &gt; 100%</p>
        </div>

        {{-- Panel Kanan: Antrean Persetujuan Saya --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            Antrean Persetujuan Saya
                        </h2>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                        {{ $antreanPersetujuan->count() }} Dokumen
                    </span>
                </div>

                @if ($antreanPersetujuan->isEmpty())
                    <div class="py-12 text-center border border-dashed border-slate-200 rounded-xl">
                        <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-xs font-semibold text-slate-700">Tidak ada dokumen pending</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Semua permohonan dana dan laporan UMP untuk Anda telah tuntas diproses.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($antreanPersetujuan as $doc)
                            <a
                                href="{{ $doc['url'] }}"
                                wire:navigate
                                class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/80 -mx-2 px-2 rounded-xl transition-colors block"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $doc['jenis'] === 'ump' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ strtoupper($doc['jenis']) }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-900 truncate">{{ $doc['no_dokumen'] }}</span>
                                    </div>
                                    <p class="text-xs text-slate-600 truncate mt-0.5">{{ $doc['judul'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $doc['pengaju'] }} • {{ $doc['unit'] }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-bold text-slate-900 tabular-nums">Rp {{ number_format($doc['nominal'], 0, ',', '.') }}</p>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ $doc['status_label'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            <p class="text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-100">Klik baris dokumen untuk memproses persetujuan</p>
        </div>
    </div>

    {{-- Monitoring Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Tabel Monitoring Hierarki & Pos Anggaran
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Rincian alokasi, keterikatan, realisasi, sisa anggaran, dan persentase penyerapan
                </p>
            </div>
            <div class="w-full sm:w-64">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kode atau nama pos..."
                    class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3 text-slate-800 focus:border-blue-500 focus:outline-hidden"
                />
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3.5 cursor-pointer hover:bg-slate-100" wire:click="toggleSort('kode')">
                            Kode {!! $sortBy === 'kode' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 cursor-pointer hover:bg-slate-100" wire:click="toggleSort('nama')">
                            Pos Anggaran {!! $sortBy === 'nama' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 text-right cursor-pointer hover:bg-slate-100" wire:click="toggleSort('effective_budget')">
                            Plafon RAB {!! $sortBy === 'effective_budget' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 text-right cursor-pointer hover:bg-slate-100" wire:click="toggleSort('dana_terikat')">
                            Dana Terikat {!! $sortBy === 'dana_terikat' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 text-right cursor-pointer hover:bg-slate-100" wire:click="toggleSort('realisasi')">
                            Realisasi {!! $sortBy === 'realisasi' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 text-right cursor-pointer hover:bg-slate-100" wire:click="toggleSort('sisa_tersedia')">
                            Sisa Tersedia {!! $sortBy === 'sisa_tersedia' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 text-center cursor-pointer hover:bg-slate-100" wire:click="toggleSort('pemakaian_persen')">
                            Pemakaian {!! $sortBy === 'pemakaian_persen' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                        <th class="py-3 px-3.5 text-center cursor-pointer hover:bg-slate-100" wire:click="toggleSort('status')">
                            Status {!! $sortBy === 'status' ? ($sortDir === 'asc' ? '↑' : '↓') : '' !!}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($monitoringRows as $row)
                        @php
                            $depth = $row['depth'] ?? 1;
                            $indentPx = ($depth - 1) * 16;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ ($row['depth'] ?? 1) === 1 ? 'font-semibold bg-slate-50/40' : '' }}">
                            <td class="py-2.5 px-3.5 font-mono text-slate-900 whitespace-nowrap">
                                <a
                                    href="{{ route('master.kode-anggaran') }}"
                                    class="hover:underline text-blue-700"
                                    title="Buka Master Anggaran"
                                >
                                    {{ $row['kode'] }}
                                </a>
                            </td>
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
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Aman
                                    </span>
                                @elseif ($row['status'] === 'Limit')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        Limit
                                    </span>
                                @elseif ($row['status'] === 'Over Budget')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        Over Budget
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                        Tanpa Budget
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                Tidak ada data pos anggaran yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Chart.js CDN script --}}
    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endassets

@script
<script>
    let chart = null;

    const renderChart = (d) => {
        const canvas = document.getElementById('budgetChart');
        if (!canvas) return;

        if (chart) chart.destroy();

        chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [
                    {
                        label: 'Realisasi UMP',
                        data: d.realisasi,
                        backgroundColor: '#059669',
                        borderRadius: 6,
                        order: 3,
                    },
                    {
                        label: 'Dana Terikat',
                        data: d.terikat,
                        backgroundColor: '#D97706',
                        borderRadius: 6,
                        order: 4,
                    },
                    {
                        label: 'Total Budget Disahkan',
                        data: d.budget,
                        type: 'line',
                        borderColor: '#0F2F57',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 3,
                        pointBackgroundColor: '#0F2F57',
                        fill: false,
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) =>
                                (ctx.dataset.label || '') + ': Rp ' +
                                new Intl.NumberFormat('id-ID').format(ctx.parsed.y || 0),
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => {
                                if (v >= 1000000000) return (v / 1000000000) + ' M';
                                if (v >= 1000000) return (v / 1000000) + ' Jt';
                                return v;
                            },
                        },
                    },
                },
            },
        });
    };

    // Render pertama kali
    renderChart({
        labels: @js($chartLabels),
        terikat: @js($chartTerikat),
        realisasi: @js($chartRealisasi),
        budget: @js($chartBudget),
    });

    // Update saat filter berubah
    $wire.on('chart-updated', (d) => renderChart(d));
</script>
@endscript
</div>
