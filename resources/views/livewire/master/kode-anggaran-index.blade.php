<div class="space-y-6">
    {{-- Header & Breadcrumb Info --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Master Kode Anggaran & Plafon RAB
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola pohon hierarki kode anggaran, plafon per tingkat, dan pengesahan budget tahunan.
            </p>
        </div>

        {{-- Primary Action Button (Touch target >= 44px) --}}
        <div class="flex items-center gap-2.5 shrink-0">
            <button
                type="button"
                wire:click="openCreateModal()"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Kode Utama</span>
            </button>
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

    @if (session()->has('error'))
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Filters & Selector Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-2xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Tahun Anggaran Selector --}}
            <div>
                <label for="ta-select" class="block text-xs font-semibold text-slate-700 mb-1">
                    Tahun Anggaran:
                </label>
                <select
                    id="ta-select"
                    wire:model.live="tahun_anggaran_id"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-slate-50 focus:bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    @foreach ($years as $year)
                        <option value="{{ $year->id }}">
                            TA {{ $year->tahun }} ({{ ucfirst($year->status) }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Kelompok Kode / Prefix Filter --}}
            <div>
                <label for="prefix-filter" class="block text-xs font-semibold text-slate-700 mb-1">
                    Kelompok Kode Angka:
                </label>
                <select
                    id="prefix-filter"
                    wire:model.live="prefixFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Kelompok Kode</option>
                    @foreach ($prefixOptions as $pref)
                        <option value="{{ $pref->kode }}">
                            Pos {{ $pref->kode }} : {{ $pref->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Nominal Plafon --}}
            <div>
                <label for="nominal-filter" class="block text-xs font-semibold text-slate-700 mb-1">
                    Kisaran Nominal Plafon:
                </label>
                <select
                    id="nominal-filter"
                    wire:model.live="nominalFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Nominal</option>
                    <option value="gt_100m">> Rp 100 Juta</option>
                    <option value="10m_100m">Rp 10 Juta - Rp 100 Juta</option>
                    <option value="lt_10m">< Rp 10 Juta</option>
                    <option value="has_plafon">Memiliki Plafon Tertulis</option>
                    <option value="null_plafon">Tanpa Plafon (Mengikuti Anak)</option>
                </select>
            </div>

            {{-- Filter Tingkat / Depth --}}
            <div>
                <label for="level-filter" class="block text-xs font-semibold text-slate-700 mb-1">
                    Tingkat Kedalaman:
                </label>
                <select
                    id="level-filter"
                    wire:model.live="levelFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Tingkat</option>
                    <option value="1">Level 1 (Induk Utama)</option>
                    <option value="2">Level 2 (Sub-Induk)</option>
                    <option value="3">Level 3</option>
                    <option value="4">Level 4 (Leaf / Akun)</option>
                </select>
            </div>
        </div>

        {{-- Row 2: Search, Status, Leaf Only, Reset --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 pt-2 border-t border-slate-100">
            {{-- Search Input --}}
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kode (2.4.1.1) atau nama pos anggaran..."
                    class="block w-full min-h-[44px] pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 placeholder:text-slate-400 bg-white focus:border-[#1E60D5] focus:ring-1 focus:ring-[#1E60D5] focus:outline-hidden"
                />
            </div>

            {{-- Leaf Only Dropdown --}}
            <select
                wire:model.live="leafOnly"
                class="min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-700 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden shrink-0"
            >
                <option value="">Semua Pos (Induk & Leaf)</option>
                <option value="1">Hanya Pos Leaf (Transaksi)</option>
            </select>

            {{-- Status Filter --}}
            <select
                wire:model.live="statusFilter"
                class="min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-700 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden shrink-0"
            >
                <option value="">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="disahkan">Disahkan</option>
            </select>

            {{-- Reset Filter Button --}}
            @if ($this->hasActiveFilters)
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 transition-colors inline-flex items-center gap-1.5 shrink-0 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
            @endif
        </div>
    </div>

    {{-- VIEW 1: TAMPILAN MOBILE-FIRST (Card Tree, < 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @if ($allNodes->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-700">Belum ada kode anggaran</p>
                <p class="text-xs text-slate-500 mt-1">Silakan mulai dengan menambahkan kode tingkat pertama.</p>
            </div>
        @else
            @foreach ($allNodes as $node)
                @php
                    $calc = $calculatedData->get($node->id);
                    $hasChildren = $nodesByParent->has($node->id);
                    $isExpanded = in_array($node->id, $expandedNodes);
                    $levelLabel = match($node->depth) {
                        1 => 'Level 1 Induk',
                        2 => 'Level 2 Sub-Induk',
                        3 => 'Level 3 Leaf',
                        default => 'Level ' . $node->depth,
                    };
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs space-y-3 {{ !$node->is_active ? 'opacity-60 bg-slate-50' : '' }}">
                    {{-- Top Header Row --}}
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-[#DCE5F6] text-[#001B3E]">
                                {{ $node->kode }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ $levelLabel }}
                            </span>
                            @if ($node->budget_status === 'disahkan')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Disahkan
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Draft
                                </span>
                            @endif
                        </div>

                        {{-- Expand/Collapse Branch Button (tap target >= 44x44px) --}}
                        @if ($hasChildren)
                            <button
                                type="button"
                                wire:click="toggleNode({{ $node->id }})"
                                class="min-w-[44px] min-h-[44px] inline-flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors shrink-0"
                                aria-label="{{ $isExpanded ? 'Tutup cabang' : 'Buka cabang' }}"
                            >
                                <svg class="w-4 h-4 transform transition-transform {{ $isExpanded ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Nama & Deskripsi --}}
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 leading-snug">
                            {{ $node->nama }}
                        </h2>
                        @if ($node->deskripsi)
                            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                {{ $node->deskripsi }}
                            </p>
                        @endif
                    </div>

                    {{-- Data Anggaran Grid (No Horizontal Scroll Leak) --}}
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block">Plafon Budget</span>
                            <span class="font-bold text-slate-900 tabular-nums mt-0.5 block">
                                {{ $node->plafon !== null ? 'Rp ' . number_format($node->plafon, 0, ',', '.') : 'Mengikuti Anak' }}
                            </span>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block">Total Anak</span>
                            <span class="font-bold text-slate-900 tabular-nums mt-0.5 block">
                                Rp {{ number_format($calc['total_anak'] ?? 0, 0, ',', '.') }}
                            </span>
                        </div>

                        @if ($node->plafon !== null && !$calc['is_leaf'])
                            <div class="col-span-2 p-2.5 rounded-xl bg-blue-50/70 border border-blue-100">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-700 block">Belum Dialokasikan</span>
                                <span class="font-bold text-[#0F2F57] tabular-nums mt-0.5 block">
                                    Rp {{ number_format($calc['belum_dialokasikan'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Action Row (Tap targets >= 44px) --}}
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                        <button
                            type="button"
                            wire:click="openDetailModal({{ $node->id }})"
                            class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                            title="Lihat Detail Pos"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Detail</span>
                        </button>

                        @if ($node->depth < 4)
                            <button
                                type="button"
                                wire:click="openCreateModal({{ $node->id }})"
                                class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ Anak</span>
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="openEditModal({{ $node->id }})"
                            class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                        >
                            Edit
                        </button>
                        @if ($node->budget_status === 'draft')
                            <button
                                type="button"
                                wire:click="sahkanBudget({{ $node->id }})"
                                class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 transition-colors cursor-pointer"
                            >
                                Sahkan
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="toggleActive({{ $node->id }})"
                            class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold {{ $node->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} transition-colors cursor-pointer"
                        >
                            {{ $node->is_active ? 'Nonaktif' : 'Aktifkan' }}
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- VIEW 2: TAMPILAN DESKTOP (Hierarchical Table, >= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Kode Pos</th>
                        <th class="py-3.5 px-4">Nama Pos Anggaran</th>
                        <th class="py-3.5 px-3 text-center">Tingkat</th>
                        <th class="py-3.5 px-4 text-right">Plafon Induk</th>
                        <th class="py-3.5 px-4 text-right">Total Alokasi Anak</th>
                        <th class="py-3.5 px-4 text-right">Belum Dialokasikan</th>
                        <th class="py-3.5 px-3 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($allNodes as $node)
                        @php
                            $calc = $calculatedData->get($node->id);
                            $paddingLeft = ($node->depth - 1) * 20;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ !$node->is_active ? 'opacity-50 bg-slate-50/50' : '' }}">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <span style="margin-left: {{ $paddingLeft }}px" class="inline-flex items-center gap-1.5">
                                    @if ($node->depth > 1)
                                        <span class="text-slate-300">└</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-md bg-[#DCE5F6] text-[#001B3E] text-[11px]">
                                        {{ $node->kode }}
                                    </span>
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-medium text-slate-900">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $node->nama }}</p>
                                    @if ($node->deskripsi)
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $node->deskripsi }}</p>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    Depth {{ $node->depth }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-semibold tabular-nums text-slate-900 whitespace-nowrap">
                                {{ $node->plafon !== null ? 'Rp ' . number_format($node->plafon, 0, ',', '.') : '-' }}
                            </td>

                            <td class="py-3.5 px-4 text-right tabular-nums text-slate-700 whitespace-nowrap">
                                Rp {{ number_format($calc['total_anak'] ?? 0, 0, ',', '.') }}
                            </td>

                            <td class="py-3.5 px-4 text-right tabular-nums font-semibold whitespace-nowrap {{ ($calc['belum_dialokasikan'] ?? 0) < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                @if ($node->plafon !== null && !$calc['is_leaf'])
                                    Rp {{ number_format($calc['belum_dialokasikan'] ?? 0, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <div class="inline-flex flex-col items-center gap-1">
                                    @if ($node->budget_status === 'disahkan')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disahkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Draft
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <button
                                        type="button"
                                        wire:click="openDetailModal({{ $node->id }})"
                                        class="p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Lihat Detail Satu per Satu"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    @if ($node->depth < 4)
                                        <button
                                            type="button"
                                            wire:click="openCreateModal({{ $node->id }})"
                                            class="p-1.5 rounded-lg text-blue-700 hover:bg-blue-50 transition-colors"
                                            title="Tambah Sub-Kode Anak"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $node->id }})"
                                        class="p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
                                        title="Edit Node"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    @if ($node->budget_status === 'draft')
                                        <button
                                            type="button"
                                            wire:click="sahkanBudget({{ $node->id }})"
                                            class="p-1.5 rounded-lg text-emerald-700 hover:bg-emerald-50 transition-colors"
                                            title="Sahkan Budget"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $node->id }})"
                                        class="p-1.5 rounded-lg {{ $node->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                                        title="{{ $node->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                Tidak ada kode anggaran yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL TAMBAH / EDIT KODE ANGGARAN (M3 Dialog) --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden"
                @click.outside="$wire.set('showModal', false)"
            >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-[#0F2F57]">
                            {{ $editingId ? 'Edit Pos Anggaran' : 'Tambah Pos Anggaran Baru' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $form_parent_id ? 'Menambahkan sub-kode di bawah induk' : 'Kode tingkat teratas' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('showModal', false)"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-6 space-y-4">
                    {{-- Parent Code Selector (if create) --}}
                    @if (!$editingId)
                        <div>
                            <label for="form_parent_id" class="block text-xs font-semibold text-slate-700 mb-1">
                                Kode Induk (Parent)
                            </label>
                            <select
                                id="form_parent_id"
                                wire:model.live="form_parent_id"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            >
                                <option value="">Tanpa Induk (Tingkat 1 Teratas)</option>
                                @foreach ($parentChoices as $pChoice)
                                    <option value="{{ $pChoice->id }}">
                                        {{ $pChoice->kode }} : {{ $pChoice->nama }} (Depth {{ $pChoice->depth }})
                                    </option>
                                @endforeach
                            </select>
                            @error('form_parent_id')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Kode --}}
                        <div class="sm:col-span-1">
                            <label for="form_kode" class="block text-xs font-semibold text-slate-700 mb-1">
                                Kode Pos *
                            </label>
                            <input
                                type="text"
                                id="form_kode"
                                wire:model="form_kode"
                                placeholder="Contoh: 1.1"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-mono font-bold text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_kode') border-red-500 @enderror"
                                required
                            />
                            @error('form_kode')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nama --}}
                        <div class="sm:col-span-2">
                            <label for="form_nama" class="block text-xs font-semibold text-slate-700 mb-1">
                                Nama Pos Anggaran *
                            </label>
                            <input
                                type="text"
                                id="form_nama"
                                wire:model="form_nama"
                                placeholder="Contoh: Operasional Kantor"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_nama') border-red-500 @enderror"
                                required
                            />
                            @error('form_nama')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Plafon (Rupiah utuh) --}}
                    <div>
                        <label for="form_plafon" class="block text-xs font-semibold text-slate-700 mb-1">
                            Plafon Budget (Rp)
                        </label>
                        <div
                            x-data="{
                                display: '',
                                init() {
                                    let val = $wire.get('form_plafon');
                                    if (val || val === 0) this.display = new Intl.NumberFormat('id-ID').format(val.toString().replace(/\D/g, ''));
                                    $watch('$wire.form_plafon', (v) => {
                                        if (!v && v !== 0) { this.display = ''; return; }
                                        this.display = new Intl.NumberFormat('id-ID').format(v.toString().replace(/\D/g, ''));
                                    });
                                },
                                onInput(e) {
                                    let clean = e.target.value.replace(/\D/g, '');
                                    this.display = clean ? new Intl.NumberFormat('id-ID').format(clean) : '';
                                    e.target.value = this.display;
                                    $wire.set('form_plafon', clean ? clean : '');
                                }
                            }"
                        >
                            <input
                                type="text"
                                id="form_plafon"
                                x-model="display"
                                @input="onInput($event)"
                                inputmode="numeric"
                                placeholder="Kosongkan jika mengikuti total anak"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs tabular-nums text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_plafon') border-red-500 @enderror"
                            />
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Bila dikosongkan pada kode induk, plafon akan otomatis dihitung dari total anak.
                        </p>
                        @error('form_plafon')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label for="form_deskripsi" class="block text-xs font-semibold text-slate-700 mb-1">
                            Deskripsi / Catatan Pos
                        </label>
                        <textarea
                            id="form_deskripsi"
                            wire:model="form_deskripsi"
                            rows="2"
                            placeholder="Catatan peruntukan pos anggaran ini..."
                            class="w-full rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                        ></textarea>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="$set('showModal', false)"
                            class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer shadow-xs"
                        >
                            Simpan Pos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL POS ANGGARAN (Lihat Satu-Satu) --}}
    @if ($showDetailModal && $detailNode)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                @click.outside="$wire.closeDetailModal()"
            >
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between sticky top-0 bg-white z-10">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-[#DCE5F6] text-[#001B3E]">
                                {{ $detailNode->kode }}
                            </span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                Level {{ $detailNode->depth }} ({{ $detailNode->isLeaf() ? 'Pos Leaf' : 'Pos Induk' }})
                            </span>
                            @if ($detailNode->budget_status === 'disahkan')
                                <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Disahkan
                                </span>
                            @else
                                <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Draft
                                </span>
                            @endif
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-[#0F2F57] leading-snug">
                            {{ $detailNode->nama }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        wire:click="closeDetailModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        aria-label="Tutup Detail"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body Content --}}
                <div class="p-6 space-y-6">
                    {{-- Financial Metrics Grid --}}
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">
                            Ringkasan Anggaran Pos
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Plafon Pos</span>
                                <span class="font-bold text-slate-900 text-sm tabular-nums mt-1 block">
                                    {{ $detailNode->plafon !== null ? 'Rp ' . number_format($detailNode->plafon, 0, ',', '.') : 'Mengikuti Anak' }}
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Total Alokasi Anak</span>
                                <span class="font-bold text-slate-900 text-sm tabular-nums mt-1 block">
                                    Rp {{ number_format($detailMetrics['total_anak'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Dana Terikat</span>
                                <span class="font-bold text-amber-700 text-sm tabular-nums mt-1 block">
                                    Rp {{ number_format($detailMetrics['dana_terikat'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase block">Realisasi</span>
                                <span class="font-bold text-emerald-700 text-sm tabular-nums mt-1 block">
                                    Rp {{ number_format($detailMetrics['realisasi'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-blue-50/70 border-2 border-blue-200 col-span-2 sm:col-span-2">
                                <span class="text-[10px] font-bold text-[#0F2F57] uppercase block">Sisa Anggaran Tersedia</span>
                                <span class="font-extrabold text-[#0F2F57] text-base tabular-nums mt-1 block">
                                    Rp {{ number_format($detailMetrics['sisa_tersedia'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Context Hierarki (Induk & Anak) --}}
                    <div class="space-y-3 pt-2 border-t border-slate-100">
                        {{-- Parent Node Info --}}
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                                Pos Induk (Parent)
                            </p>
                            @if ($detailNode->parent)
                                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-slate-200 text-slate-800">
                                                {{ $detailNode->parent->kode }}
                                            </span>
                                            <span class="text-xs font-semibold text-slate-900 truncate">
                                                {{ $detailNode->parent->nama }}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="openDetailModal({{ $detailNode->parent->id }})"
                                        class="min-h-[36px] px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-blue-700 hover:bg-blue-50 transition-colors shrink-0 cursor-pointer"
                                    >
                                        Lihat Induk
                                    </button>
                                </div>
                            @else
                                <p class="text-xs text-slate-500 italic">
                                    Pos ini adalah tingkat tertinggi (Level 1 Induk Utama).
                                </p>
                            @endif
                        </div>

                        {{-- Direct Children List --}}
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                                Daftar Pos Anak Langsung (Sub-Pos)
                            </p>
                            @if ($detailNode->children->isNotEmpty())
                                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                                    @foreach ($detailNode->children as $child)
                                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between gap-2 text-xs">
                                            <div class="min-w-0 flex items-center gap-2">
                                                <span class="font-mono font-bold text-slate-800 px-1.5 py-0.5 rounded bg-white border border-slate-200 shrink-0">
                                                    {{ $child->kode }}
                                                </span>
                                                <span class="font-medium text-slate-900 truncate">
                                                    {{ $child->nama }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="font-semibold text-slate-700 tabular-nums">
                                                    {{ $child->plafon !== null ? 'Rp ' . number_format($child->plafon, 0, ',', '.') : '-' }}
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="openDetailModal({{ $child->id }})"
                                                    class="p-1 rounded-lg text-blue-700 hover:bg-blue-50 transition-colors cursor-pointer"
                                                    title="Lihat Pos Ini"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Pos Leaf (Ujung Hierarki): Pos ini siap digunakan sebagai akun belanja pada Form Permohonan Dana.</span>
                                </div>
                            @endif
                        </div>

                        {{-- Deskripsi / Catatan Pos --}}
                        @if ($detailNode->deskripsi)
                            <div class="pt-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                                    Deskripsi & Catatan
                                </p>
                                <div class="p-3 rounded-2xl bg-slate-50 text-slate-700 text-xs leading-relaxed border border-slate-200/80">
                                    {{ $detailNode->deskripsi }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-2.5 bg-slate-50/50">
                    <button
                        type="button"
                        wire:click="closeDetailModal"
                        class="min-h-[44px] px-4 py-2 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>

                    <div class="flex items-center gap-2 flex-wrap">
                        @if ($detailNode->depth < 4)
                            <button
                                type="button"
                                wire:click="openCreateModal({{ $detailNode->id }}); closeDetailModal();"
                                class="min-h-[44px] px-3.5 py-2 rounded-full text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ Tambah Anak</span>
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="openEditModal({{ $detailNode->id }}); closeDetailModal();"
                            class="min-h-[44px] px-4 py-2 rounded-full text-xs font-semibold text-slate-800 bg-slate-200 hover:bg-slate-300 transition-colors cursor-pointer"
                        >
                            Edit Pos
                        </button>

                        @if ($detailNode->budget_status === 'draft')
                            <button
                                type="button"
                                wire:click="sahkanBudget({{ $detailNode->id }})"
                                class="min-h-[44px] px-4 py-2 rounded-full text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 transition-colors cursor-pointer"
                            >
                                Sahkan Budget
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
