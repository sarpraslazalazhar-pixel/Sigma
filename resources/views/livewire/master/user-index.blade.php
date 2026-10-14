<div class="space-y-6">
    {{-- Header & Primary Action --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Manajemen Pengguna & Leveling
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola akun personel kedinasan, penetapan peran (role), divisi unit kerja, dan hak persetujuan leveling.
            </p>
        </div>

        {{-- Primary Action Button (tap target >= 44px) --}}
        <div>
            <button
                type="button"
                wire:click="openCreateModal"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Tambah Pengguna</span>
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

    {{-- Filter & Search Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-2xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Search Input --}}
            <div class="sm:col-span-2 relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama, username, email, jabatan..."
                    class="block w-full min-h-[44px] pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 placeholder:text-slate-400 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                />
            </div>

            {{-- Role Filter --}}
            <div>
                <select
                    wire:model.live="roleFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-700 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Role</option>
                    <option value="admin">Admin IT</option>
                    <option value="unit">Pengguna Unit</option>
                    <option value="keuangan">Keuangan</option>
                    <option value="viewer">Viewer</option>
                </select>
            </div>

            {{-- Divisi / Unit Filter --}}
            <div>
                <select
                    wire:model.live="unitFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-700 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Divisi / Unit</option>
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}">{{ $u->kode }} : {{ $u->nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <select
                    wire:model.live="statusFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-700 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>
    </div>

    {{-- VIEW 1: TAMPILAN MOBILE-FIRST (Card View, < 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @forelse ($users as $user)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3 {{ !$user->is_active ? 'opacity-60 bg-slate-50' : '' }}">
                {{-- Header Card: Avatar & Name --}}
                <div class="flex items-start justify-between gap-2.5">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-[#0F2F57] text-white flex items-center justify-center font-bold text-sm shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-900 truncate">
                                {{ $user->name }}
                            </h2>
                            <p class="text-xs text-slate-500 truncate font-mono">
                                {{ $user->username }}
                            </p>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div>
                        @if ($user->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Badges & Role --}}
                <div class="flex items-center gap-1.5 flex-wrap text-xs">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold text-[10px] bg-[#DCE5F6] text-[#001B3E]">
                        {{ strtoupper($user->role) }}
                    </span>
                    @if ($user->leveling)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold text-[10px] bg-slate-100 text-slate-700 border border-slate-200">
                            L{{ $user->leveling->urutan }} : {{ $user->leveling->nama }}
                        </span>
                    @endif
                    @if ($user->is_petugas_input)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold text-[10px] bg-amber-50 text-amber-800 border border-amber-200">
                            Petugas Keuangan
                        </span>
                    @endif
                </div>

                {{-- Info Grid --}}
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Divisi / Unit</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block truncate">
                            {{ $user->unit ? $user->unit->kode . ' - ' . $user->unit->nama : 'Belum Ditugaskan' }}
                        </span>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Jabatan</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block truncate">
                            {{ $user->jabatan ?? 'Staf' }}
                        </span>
                    </div>
                    <div class="col-span-2 p-2 rounded-xl bg-slate-50">
                        <span class="text-[10px] text-slate-400 block">Email Kedinasan</span>
                        <span class="text-slate-700 mt-0.5 block truncate">
                            {{ $user->email }}
                        </span>
                    </div>
                </div>

                {{-- Action Row (Tap targets >= 44px) --}}
                <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                    <button
                        type="button"
                        wire:click="openResetPasswordModal({{ $user->id }})"
                        class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        Reset Password
                    </button>

                    <button
                        type="button"
                        wire:click="openEditModal({{ $user->id }})"
                        class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors cursor-pointer"
                    >
                        Edit
                    </button>

                    @if ($user->id !== auth()->id())
                        <button
                            type="button"
                            wire:click="toggleActive({{ $user->id }})"
                            class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold {{ $user->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} transition-colors cursor-pointer"
                        >
                            {{ $user->is_active ? 'Nonaktif' : 'Aktifkan' }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                Tidak ada pengguna yang sesuai dengan kriteria filter.
            </div>
        @endforelse
    </div>

    {{-- VIEW 2: TAMPILAN DESKTOP (M3 Table, >= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">Nama & Username</th>
                    <th class="py-3.5 px-4">Email</th>
                    <th class="py-3.5 px-3 text-center">Role</th>
                    <th class="py-3.5 px-4">Divisi / Unit</th>
                    <th class="py-3.5 px-4">Leveling</th>
                    <th class="py-3.5 px-4">Jabatan</th>
                    <th class="py-3.5 px-3 text-center">Status</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ !$user->is_active ? 'opacity-50 bg-slate-50/50' : '' }}">
                        {{-- Nama & Avatar --}}
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-[#0F2F57] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">{{ $user->username }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $user->email }}
                        </td>

                        {{-- Role --}}
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold text-[10px] bg-[#DCE5F6] text-[#001B3E]">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>

                        {{-- Divisi --}}
                        <td class="py-3.5 px-4 text-slate-700">
                            @if ($user->unit)
                                <span class="font-semibold text-slate-900">{{ $user->unit->kode }}</span>
                                <span class="text-slate-400 text-[11px]"> - {{ $user->unit->nama }}</span>
                            @else
                                <span class="text-slate-300">Belum diatur</span>
                            @endif
                        </td>

                        {{-- Leveling --}}
                        <td class="py-3.5 px-4 text-slate-700">
                            @if ($user->leveling)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    L{{ $user->leveling->urutan }} : {{ $user->leveling->nama }}
                                </span>
                            @else
                                <span class="text-slate-300">Tidak ada</span>
                            @endif
                        </td>

                        {{-- Jabatan & Flag --}}
                        <td class="py-3.5 px-4 text-slate-700">
                            <p class="font-medium text-slate-900">{{ $user->jabatan ?? '-' }}</p>
                            @if ($user->is_petugas_input)
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 mt-0.5">
                                    Petugas Keuangan
                                </span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            @if ($user->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 justify-end">
                                <button
                                    type="button"
                                    wire:click="openResetPasswordModal({{ $user->id }})"
                                    class="p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
                                    title="Reset Password"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $user->id }})"
                                    class="p-1.5 rounded-lg text-blue-700 hover:bg-blue-50 transition-colors"
                                    title="Edit Pengguna"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                @if ($user->id !== auth()->id())
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $user->id }})"
                                        class="p-1.5 rounded-lg {{ $user->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                                        title="{{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            Tidak ada data pengguna yang sesuai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MODAL TAMBAH / EDIT PENGGUNA --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
                @click.outside="$wire.set('showModal', false)"
            >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h2 class="text-base font-bold text-[#0F2F57]">
                        {{ $editingId ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
                    </h2>
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
                    {{-- Nama Lengkap --}}
                    <div>
                        <label for="form_name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Lengkap *
                        </label>
                        <input
                            type="text"
                            id="form_name"
                            wire:model="form_name"
                            placeholder="Contoh: Deden Nurdin Salim"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_name') border-red-500 @enderror"
                            required
                        />
                        @error('form_name')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Username & Email --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="form_username" class="block text-xs font-semibold text-slate-700 mb-1">
                                Username (Unik) *
                            </label>
                            <input
                                type="text"
                                id="form_username"
                                wire:model="form_username"
                                placeholder="Contoh: deden.salim"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-mono text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_username') border-red-500 @enderror"
                                required
                            />
                            @error('form_username')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="form_email" class="block text-xs font-semibold text-slate-700 mb-1">
                                Email Kedinasan *
                            </label>
                            <input
                                type="email"
                                id="form_email"
                                wire:model="form_email"
                                placeholder="nama@alazharpeduli.or.id"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_email') border-red-500 @enderror"
                                required
                            />
                            @error('form_email')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Password (Wajib jika create) --}}
                    <div>
                        <label for="form_password" class="block text-xs font-semibold text-slate-700 mb-1">
                            {{ $editingId ? 'Password Baru (Kosongkan bila tidak diubah)' : 'Kata Sandi *' }}
                        </label>
                        <input
                            type="password"
                            id="form_password"
                            wire:model="form_password"
                            placeholder="Minimal 6 karakter"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_password') border-red-500 @enderror"
                            {{ $editingId ? '' : 'required' }}
                        />
                        @error('form_password')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role & Status Kepegawaian --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="form_role" class="block text-xs font-semibold text-slate-700 mb-1">
                                Peran Akun (Role) *
                            </label>
                            <select
                                id="form_role"
                                wire:model="form_role"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                                required
                            >
                                <option value="unit">Pengguna Unit (Pengaju & Approver)</option>
                                <option value="keuangan">Keuangan (Verifikator & Kasir)</option>
                                <option value="admin">Administrator IT</option>
                                <option value="viewer">Viewer (Pemantau Eksekutif)</option>
                            </select>
                        </div>

                        <div>
                            <label for="form_status_kepegawaian" class="block text-xs font-semibold text-slate-700 mb-1">
                                Status Kepegawaian
                            </label>
                            <select
                                id="form_status_kepegawaian"
                                wire:model="form_status_kepegawaian"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            >
                                <option value="Tetap">Karyawan Tetap</option>
                                <option value="Kontrak">Kontrak</option>
                                <option value="Relawan">Relawan</option>
                            </select>
                        </div>
                    </div>

                    {{-- Divisi Unit & Leveling --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="form_unit_id" class="block text-xs font-semibold text-slate-700 mb-1">
                                Unit / Divisi Kerja
                            </label>
                            <select
                                id="form_unit_id"
                                wire:model="form_unit_id"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            >
                                <option value="">Tanpa Divisi (Umum / Global)</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->kode }} : {{ $u->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="form_leveling_id" class="block text-xs font-semibold text-slate-700 mb-1">
                                Tingkat Approval (Leveling)
                            </label>
                            <select
                                id="form_leveling_id"
                                wire:model="form_leveling_id"
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                            >
                                <option value="">Tanpa Leveling (Staf Pelaksana)</option>
                                @foreach ($levelings as $lvl)
                                    <option value="{{ $lvl->id }}">L{{ $lvl->urutan }} : {{ $lvl->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Jabatan --}}
                    <div>
                        <label for="form_jabatan" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Jabatan Kedinasan
                        </label>
                        <input
                            type="text"
                            id="form_jabatan"
                            wire:model="form_jabatan"
                            placeholder="Contoh: Manager HRD / Staf TU Sekretariat"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                        />
                    </div>

                    {{-- Checkboxes --}}
                    <div class="space-y-2 pt-1 border-t border-slate-100">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none text-xs text-slate-700">
                            <input
                                type="checkbox"
                                wire:model="form_is_petugas_input"
                                class="w-4 h-4 rounded border-slate-300 text-[#1E60D5] focus:ring-[#1E60D5]"
                            />
                            <span><strong>Petugas Input Keuangan:</strong> Berwenang mengajukan dan memproses atas nama divisi</span>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer select-none text-xs text-slate-700">
                            <input
                                type="checkbox"
                                wire:model="form_is_active"
                                class="w-4 h-4 rounded border-slate-300 text-[#1E60D5] focus:ring-[#1E60D5]"
                            />
                            <span>Akun Aktif (Dapat masuk ke sistem)</span>
                        </label>
                    </div>

                    {{-- Modal Actions --}}
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
                            Simpan Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL RESET PASSWORD CEPAT --}}
    @if ($showResetPasswordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-sm w-full overflow-hidden"
                @click.outside="$wire.set('showResetPasswordModal', false)"
            >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-[#0F2F57]">
                        Reset Kata Sandi
                    </h2>
                    <button
                        type="button"
                        wire:click="$set('showResetPasswordModal', false)"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveResetPassword" class="p-6 space-y-4">
                    <p class="text-xs text-slate-600">
                        Atur kata sandi baru untuk <strong>{{ $resetUserName }}</strong>:
                    </p>

                    <div>
                        <label for="resetNewPassword" class="block text-xs font-semibold text-slate-700 mb-1">
                            Kata Sandi Baru *
                        </label>
                        <input
                            type="password"
                            id="resetNewPassword"
                            wire:model="resetNewPassword"
                            placeholder="Minimal 6 karakter"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('resetNewPassword') border-red-500 @enderror"
                            required
                            autofocus
                        />
                        @error('resetNewPassword')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="$set('showResetPasswordModal', false)"
                            class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer shadow-xs"
                        >
                            Perbarui Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
