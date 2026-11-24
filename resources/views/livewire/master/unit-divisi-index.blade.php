<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Master Divisi & Unit
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Struktur organisasi pengelola anggaran LAZ Al Azhar (Divisi membawahi Unit kerja) lengkap dengan urutan tampilan.
            </p>
        </div>

        {{-- Primary Action Button --}}
        <div>
            <button
                type="button"
                wire:click="openCreateModal()"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Divisi Induk</span>
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

    {{-- VIEW 1: TAMPILAN MOBILE (Cards, < 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @foreach ($allUnits as $u)
            @php
                $activeUsers = $u->users->where('is_active', true);
                $leader = $u->tipe === 'divisi'
                    ? $activeUsers->first(fn ($usr) => ($usr->leveling?->urutan ?? 0) >= 3)
                    : $activeUsers->first(fn ($usr) => ($usr->leveling?->urutan ?? 0) === 2);
                $supervisorCount = $activeUsers->filter(fn ($usr) => ($usr->leveling?->urutan ?? 0) === 1 && ! $usr->isAdmin())->count();
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3 {{ $u->tipe === 'unit' ? 'ml-3 border-l-4 border-l-slate-300' : 'border-l-4 border-l-[#0F2F57]' }} {{ !$u->is_active ? 'opacity-60 bg-slate-50' : '' }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-mono text-[10px] font-bold bg-slate-100 text-slate-600 tabular-nums">
                                #{{ $u->urutan > 0 ? $u->urutan : '-' }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-[#DCE5F6] text-[#001B3E]">
                                {{ $u->kode }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $u->tipe === 'divisi' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                {{ $u->tipe === 'divisi' ? 'Divisi (Induk)' : 'Unit (Anak)' }}
                            </span>
                            @if (!$u->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                    Nonaktif
                                </span>
                            @endif
                        </div>
                        <h2 class="text-sm font-bold text-slate-900 mt-1">
                            {{ $u->nama }}
                        </h2>
                        @if ($u->parent)
                            <p class="text-xs text-slate-400 mt-0.5">
                                Divisi Induk: {{ $u->parent->nama }} ({{ $u->parent->kode }})
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            type="button"
                            wire:click="moveUp({{ $u->id }})"
                            class="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                            title="Naikkan urutan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            wire:click="moveDown({{ $u->id }})"
                            class="min-h-[40px] min-w-[40px] inline-flex items-center justify-center rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                            title="Turunkan urutan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">
                            {{ $u->tipe === 'divisi' ? 'Kepala Divisi' : 'Manajer Unit' }}
                        </span>
                        <span class="font-semibold text-slate-800 mt-0.5 block truncate">
                            {{ $leader?->name ?? 'Belum ditetapkan' }}
                        </span>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Personel</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block tabular-nums">
                            {{ $u->users->count() }} User
                            @if ($supervisorCount > 0)
                                <span class="text-[10px] font-normal text-slate-500">({{ $supervisorCount }} Spv)</span>
                            @endif
                        </span>
                    </div>
                    <div class="col-span-2 p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Sumber Dana Default</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block">
                            {{ $u->sumberDanaDefault?->nama ?? 'Fleksibel (Sesuai Pengajuan)' }}
                        </span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                    @if (auth()->user()?->role === 'admin')
                        <a
                            href="{{ route('master.users', ['unitFilter' => $u->id]) }}"
                            wire:navigate
                            class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold text-[#0F2F57] bg-[#DCE5F6]/70 hover:bg-[#DCE5F6] transition-colors"
                        >
                            Lihat Tim →
                        </a>
                    @else
                        <span></span>
                    @endif

                    <div class="flex items-center gap-1.5">
                        @if ($u->tipe === 'divisi')
                            <button
                                type="button"
                                wire:click="openCreateModal({{ $u->id }})"
                                class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors cursor-pointer"
                            >
                                + Unit
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="openEditModal({{ $u->id }})"
                            class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                        >
                            Edit
                        </button>

                        <button
                            type="button"
                            wire:click="toggleActive({{ $u->id }})"
                            class="min-h-[44px] px-3 py-2 rounded-xl text-xs font-semibold {{ $u->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} transition-colors cursor-pointer"
                        >
                            {{ $u->is_active ? 'Nonaktif' : 'Aktif' }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- VIEW 2: TAMPILAN DESKTOP (M3 Table, >= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-3 text-center w-24">Urutan</th>
                    <th class="py-3.5 px-4">Kode</th>
                    <th class="py-3.5 px-4">Nama Divisi / Unit</th>
                    <th class="py-3.5 px-4">Pimpinan & Struktur Tim</th>
                    <th class="py-3.5 px-4">Sumber Dana Default</th>
                    <th class="py-3.5 px-3 text-center">Status</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($units as $divIndex => $divisi)
                    @php
                        $divKadiv = $divisi->users->where('is_active', true)->first(fn ($usr) => ($usr->leveling?->urutan ?? 0) >= 3);
                        $divTotalUsers = $divisi->users->count() + $divisi->children->sum(fn ($c) => $c->users->count());
                    @endphp
                    {{-- Row Induk (Divisi) --}}
                    <tr class="bg-[#DCE5F6]/30 hover:bg-[#DCE5F6]/50 transition-colors {{ !$divisi->is_active ? 'opacity-50' : '' }}">
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <span class="font-mono font-bold text-[#0F2F57] tabular-nums w-5 text-center">
                                    {{ $divisi->urutan > 0 ? $divisi->urutan : '-' }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="moveUp({{ $divisi->id }})"
                                    @disabled($loop->first)
                                    class="p-1 rounded-md text-slate-500 hover:text-[#0F2F57] hover:bg-white/80 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                                    title="Naikkan urutan divisi"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="moveDown({{ $divisi->id }})"
                                    @disabled($loop->last)
                                    class="p-1 rounded-md text-slate-500 hover:text-[#0F2F57] hover:bg-white/80 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                                    title="Turunkan urutan divisi"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-md bg-[#0F2F57] text-white">
                                {{ $divisi->kode }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[#0F2F57]">{{ $divisi->nama }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    Divisi
                                </span>
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ $divKadiv?->name ?? 'Kepala Divisi belum diatur' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 tabular-nums">
                                        {{ $divisi->children->count() }} Unit · {{ $divTotalUsers }} Personel total
                                    </p>
                                </div>
                                @if (auth()->user()?->role === 'admin')
                                    <a
                                        href="{{ route('master.users', ['unitFilter' => $divisi->id]) }}"
                                        wire:navigate
                                        class="px-2.5 py-1 rounded-full text-[11px] font-semibold text-[#0F2F57] bg-white hover:bg-[#DCE5F6] border border-slate-200 transition-colors shrink-0"
                                    >
                                        Lihat Tim →
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $divisi->sumberDanaDefault?->nama ?? 'Sesuai Pengajuan' }}
                        </td>
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $divisi->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                {{ $divisi->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 justify-end">
                                <button
                                    type="button"
                                    wire:click="openCreateModal({{ $divisi->id }})"
                                    class="p-1.5 rounded-lg text-blue-700 hover:bg-white transition-colors cursor-pointer"
                                    title="Tambah Unit Anak"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $divisi->id }})"
                                    class="p-1.5 rounded-lg text-slate-600 hover:bg-white transition-colors cursor-pointer"
                                    title="Edit Divisi"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $divisi->id }})"
                                    class="p-1.5 rounded-lg {{ $divisi->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors cursor-pointer"
                                    title="{{ $divisi->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Rows Anak (Unit) --}}
                    @foreach ($divisi->children as $unit)
                        @php
                            $activeUnitUsers = $unit->users->where('is_active', true);
                            $unitManager = $activeUnitUsers->first(fn ($usr) => ($usr->leveling?->urutan ?? 0) === 2);
                            $unitSpvCount = $activeUnitUsers->filter(fn ($usr) => ($usr->leveling?->urutan ?? 0) === 1 && ! $usr->isAdmin())->count();
                            $unitStaffCount = $activeUnitUsers->filter(fn ($usr) => ($usr->leveling?->urutan ?? 0) === 0)->count();
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ !$unit->is_active ? 'opacity-50' : '' }}">
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <span class="font-mono text-slate-500 tabular-nums w-5 text-center">
                                        {{ $unit->urutan > 0 ? $unit->urutan : '-' }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="moveUp({{ $unit->id }})"
                                        @disabled($loop->first)
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                                        title="Naikkan urutan unit"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="moveDown({{ $unit->id }})"
                                        @disabled($loop->last)
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                                        title="Turunkan urutan unit"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-700 pl-8 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="text-slate-300">└</span>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-800">
                                        {{ $unit->kode }}
                                    </span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-800">{{ $unit->nama }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Unit
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-slate-800">
                                            {{ $unitManager?->name ?? 'Manajer belum diatur' }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 tabular-nums">
                                            {{ $unit->users->count() }} User
                                            @if ($unitSpvCount > 0)
                                                · {{ $unitSpvCount }} Supervisor
                                            @endif
                                            @if ($unitStaffCount > 0)
                                                · {{ $unitStaffCount }} Staf
                                            @endif
                                        </p>
                                    </div>
                                    @if (auth()->user()?->role === 'admin')
                                        <a
                                            href="{{ route('master.users', ['unitFilter' => $unit->id]) }}"
                                            wire:navigate
                                            class="px-2.5 py-1 rounded-full text-[11px] font-semibold text-slate-600 hover:text-[#0F2F57] hover:bg-slate-100 transition-colors shrink-0"
                                        >
                                            Lihat Tim →
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $unit->sumberDanaDefault?->nama ?? 'Sesuai Pengajuan' }}
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $unit->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                    {{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $unit->id }})"
                                        class="p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Edit Unit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $unit->id }})"
                                        class="p-1.5 rounded-lg {{ $unit->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors cursor-pointer"
                                        title="{{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- MODAL TAMBAH / EDIT DIVISI & UNIT --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-md w-full overflow-hidden"
                @click.outside="$wire.set('showModal', false)"
            >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-[#0F2F57]">
                        {{ $editingId ? 'Edit Divisi / Unit' : ($form_parent_id ? 'Tambah Unit Anak' : 'Tambah Divisi Induk') }}
                    </h2>
                    <button
                        type="button"
                        wire:click="$set('showModal', false)"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" action="#" onsubmit="return false;" class="p-6 space-y-4">
                    {{-- Tipe & Urutan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label for="form_tipe" class="block text-xs font-semibold text-slate-700 mb-1">
                                Tipe Organisasi *
                            </label>
                            <select
                                id="form_tipe"
                                wire:model.live="form_tipe"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            >
                                <option value="divisi">Divisi (Tingkat Induk)</option>
                                <option value="unit">Unit (Tingkat Anak di bawah Divisi)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-1">
                            <label for="form_urutan" class="block text-xs font-semibold text-slate-700 mb-1">
                                Urutan *
                            </label>
                            <input
                                type="number"
                                id="form_urutan"
                                wire:model="form_urutan"
                                min="0"
                                max="9999"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-mono tabular-nums text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_urutan') border-red-500 @enderror"
                                required
                            />
                            @error('form_urutan')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Parent Divisi (jika unit anak) --}}
                    @if ($form_tipe === 'unit')
                        <div>
                            <label for="form_parent_id" class="block text-xs font-semibold text-slate-700 mb-1">
                                Divisi Induk *
                            </label>
                            <select
                                id="form_parent_id"
                                wire:model.live="form_parent_id"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                                required
                            >
                                <option value="">Pilih Divisi Induk...</option>
                                @foreach ($parentChoices as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                                @endforeach
                            </select>
                            @error('form_parent_id')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Kode & Nama --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label for="form_kode" class="block text-xs font-semibold text-slate-700 mb-1">
                                Kode *
                            </label>
                            <input
                                type="text"
                                id="form_kode"
                                wire:model="form_kode"
                                placeholder="DIV-SEK / UNT-SEK-HUM"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-mono font-bold uppercase text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_kode') border-red-500 @enderror"
                                required
                            />
                            @error('form_kode')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="form_nama" class="block text-xs font-semibold text-slate-700 mb-1">
                                Nama Divisi/Unit *
                            </label>
                            <input
                                type="text"
                                id="form_nama"
                                wire:model="form_nama"
                                placeholder="Contoh: Unit Humas, GA, dan IT"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_nama') border-red-500 @enderror"
                                required
                            />
                            @error('form_nama')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Sumber Dana Default --}}
                    <div>
                        <label for="form_sumber_dana_default_id" class="block text-xs font-semibold text-slate-700 mb-1">
                            Sumber Dana Default
                        </label>
                        <select
                            id="form_sumber_dana_default_id"
                            wire:model="form_sumber_dana_default_id"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                        >
                            <option value="">Fleksibel (Sesuai Pengajuan)</option>
                            @foreach ($sumberDanaList as $sd)
                                <option value="{{ $sd->id }}">{{ $sd->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="$set('showModal', false)"
                            class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer shadow-xs"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
