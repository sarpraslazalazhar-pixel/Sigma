<div class="space-y-6 sm:space-y-8">
    {{-- Modern Welcome & Organization Banner --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-[#0F2F57] text-xs font-semibold border border-blue-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1E60D5]"></span>
                        Tahun Anggaran {{ $activeYear ? $activeYear->tahun : date('Y') }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">
                        Unit Sekretariat
                    </span>
                    @if ($user->is_petugas_input)
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                            Petugas Input Keuangan
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#0F2F57]">
                    Selamat Datang, {{ $user->name }}
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl leading-relaxed">
                    Sistem kendali anggaran LAZ Al Azhar. Pantau alokasi, kendalikan permohonan berjenjang, dan putuskan dengan data akurat.
                </p>
            </div>

            {{-- Quick Stats Badge or Status --}}
            <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-2 shrink-0">
                <div class="px-3 py-1.5 rounded-full bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    <span class="text-slate-400">Jabatan:</span>
                    <strong class="text-slate-800 ml-1">{{ $user->jabatan ?? ucfirst($user->role) }}</strong>
                </div>
                <div class="px-3 py-1.5 rounded-full bg-emerald-50/80 border border-emerald-200 text-xs text-emerald-800 font-medium">
                    Tahap: R1 Master Aktif
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan Anggaran (Executive Budget Health) --}}
    <div>
        <div class="flex items-center justify-between mb-3.5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">
                Kondisi Anggaran Tahun {{ $activeYear ? $activeYear->tahun : date('Y') }}
            </h2>
            <span class="text-xs text-slate-500 font-medium">
                Data real-time Unit Sekretariat
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Plafon Budget Disahkan --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-2.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Plafon RAB</span>
                    <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </span>
                </div>
                <p class="text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    Rp {{ number_format($totalPlafonDisahkan, 0, ',', '.') }}
                </p>
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Status: {{ $isDisahkan ? 'Disahkan' : 'Belum disahkan' }}</span>
                    <span class="text-slate-400">{{ $nodeCount }} node</span>
                </div>
            </div>

            {{-- Card 2: Dana Terikat --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-2.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Dana Terikat</span>
                    <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                </div>
                <p class="text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    Rp 0
                </p>
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Permohonan disetujui / cair</span>
                    <span class="text-slate-400">0 berkas</span>
                </div>
            </div>

            {{-- Card 3: Realisasi --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-2.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Realisasi</span>
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                    Rp 0
                </p>
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Laporan UMP selesai</span>
                    <span class="text-slate-400">Serapan 0%</span>
                </div>
            </div>

            {{-- Card 4: Sisa Tersedia (Key Decision Metric) --}}
            <div class="bg-white rounded-2xl border-2 border-blue-200 p-5 shadow-xs hover:border-blue-300 transition-colors relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 mb-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#0F2F57]">Sisa Tersedia</span>
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-[#1E60D5] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </span>
                </div>
                <p class="text-2xl font-extrabold tracking-tight text-[#0F2F57] tabular-nums">
                    Rp {{ number_format($sisaTersedia, 0, ',', '.') }}
                </p>
                <div class="mt-2.5 pt-2.5 border-t border-blue-100 flex items-center justify-between text-xs text-slate-600 font-medium">
                    <span>Budget - Terikat - Realisasi</span>
                    <span class="text-emerald-700">100% Sisa</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Rantai Proses SIGMA (Workflow Chain Visualizer) --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-[#0F2F57]">
                    Rantai Kendali Anggaran Terpadu
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Alur operasional dokumen dari permohonan hingga realisasi UMP
                </p>
            </div>
            <span class="text-xs text-slate-400 hidden sm:inline">6 Tahapan Inti</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Tahap 1</span>
                <p class="text-xs font-bold text-slate-900 mt-1">Kode Anggaran & RAB</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Hierarki pohon per tahun</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-blue-700 bg-blue-100/60 px-2 py-0.5 rounded-full">Rilis 1</span>
            </div>

            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Tahap 2</span>
                <p class="text-xs font-bold text-slate-900 mt-1">Permohonan Dana</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Header & rincian asnaf</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-slate-600 bg-slate-200/60 px-2 py-0.5 rounded-full">Rilis 2</span>
            </div>

            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Tahap 3</span>
                <p class="text-xs font-bold text-slate-900 mt-1">Approval Leveling</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Supervisor s.d Kadiv</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-slate-600 bg-slate-200/60 px-2 py-0.5 rounded-full">Rilis 2</span>
            </div>

            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Tahap 4</span>
                <p class="text-xs font-bold text-slate-900 mt-1">Pencairan PPD</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Nomor PPD fisik & cetak</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-slate-600 bg-slate-200/60 px-2 py-0.5 rounded-full">Rilis 3</span>
            </div>

            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Tahap 5</span>
                <p class="text-xs font-bold text-slate-900 mt-1">Laporan UMP</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Bukti nota & saldo deposit</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-slate-600 bg-slate-200/60 px-2 py-0.5 rounded-full">Rilis 3</span>
            </div>

            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Tahap 6</span>
                <p class="text-xs font-bold text-slate-900 mt-1">Realisasi & Sisa</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Monitoring & drill-down</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-slate-600 bg-slate-200/60 px-2 py-0.5 rounded-full">Rilis 4</span>
            </div>
        </div>
    </div>

    {{-- Dua Kolom Aksi & Monitoring --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Kolom Kiri: Antrean Persetujuan --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <h3 class="text-sm font-bold text-slate-900">
                        Antrean Persetujuan Saya
                    </h3>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    0 Dokumen
                </span>
            </div>

            {{-- Empty State (R-27) --}}
            <div class="py-10 text-center border border-dashed border-slate-200 rounded-xl">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-700">Tidak ada dokumen yang menunggu tindakan Anda</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Semua permohonan dana dan laporan UMP telah diproses secara tertib.</p>
            </div>
        </div>

        {{-- Kolom Kanan: Status Kesehatan Anggaran --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="text-sm font-bold text-slate-900">
                        Peringatan Sisa Anggaran (Limit & Over)
                    </h3>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    Aman
                </span>
            </div>

            {{-- Empty State (R-27) --}}
            <div class="py-10 text-center border border-dashed border-slate-200 rounded-xl">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-700">Tidak ada kode anggaran yang mendekati limit atau over budget</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Seluruh pos belanja berada di bawah ambang batas (Limit: 80%, Over: 100%).</p>
            </div>
        </div>
    </div>
</div>
