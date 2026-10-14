<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#0F2F57">

        <title>{{ $title ?? 'SIGMA: LAZ Al-Azhar' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-full font-sans antialiased text-slate-800 bg-slate-50 selection:bg-blue-600 selection:text-white" x-data="{ sidebarOpen: false }">
        <div class="h-screen flex overflow-hidden bg-slate-50">
            {{-- Mobile Backdrop --}}
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 lg:hidden"
                aria-hidden="true"
                x-cloak
            ></div>

            {{-- Left Sidebar --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="w-64 xl:w-72 h-screen bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 fixed inset-y-0 left-0 z-50 lg:static transition-transform duration-200 ease-in-out"
            >
                    {{-- Header Brand --}}
                    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-100 shrink-0">
                        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
                            <img
                                src="{{ asset('images/logo-sigma-transparent.png') }}"
                                alt="SIGMA LAZ Al-Azhar"
                                class="h-9 w-auto object-contain"
                            >
                        </a>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200 hidden sm:inline-block lg:hidden xl:inline-block">
                                Unit Sekretariat
                            </span>
                            {{-- Close button for mobile drawer --}}
                            <button
                                type="button"
                                @click="sidebarOpen = false"
                                class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                aria-label="Tutup menu navigasi"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Nav Menus --}}
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                        {{-- Menu Utama --}}
                        <div>
                            <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                MENU UTAMA
                            </p>
                            <div class="space-y-1">
                                {{-- Active Dashboard Link --}}
                                <a
                                    href="{{ route('dashboard') }}"
                                    wire:navigate
                                    @click="sidebarOpen = false"
                                    class="{{ request()->routeIs('dashboard') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center gap-3 px-3.5 py-2.5 text-sm transition-colors"
                                >
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <span class="flex-1 truncate">Dashboard</span>
                                </a>

                                {{-- Master Anggaran --}}
                                <a
                                    href="{{ route('master.kode-anggaran') }}"
                                    wire:navigate
                                    @click="sidebarOpen = false"
                                    class="{{ request()->routeIs('master.kode-anggaran') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                >
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                        <span class="truncate">Master Anggaran</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('master.kode-anggaran') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">R1</span>
                                </a>

                                {{-- Unit & Divisi --}}
                                <a
                                    href="{{ route('master.unit-divisi') }}"
                                    wire:navigate
                                    @click="sidebarOpen = false"
                                    class="{{ request()->routeIs('master.unit-divisi') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                >
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span class="truncate">Unit & Divisi</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('master.unit-divisi') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">R1</span>
                                </a>

                                {{-- Permohonan Dana --}}
                                <a
                                    href="{{ route('permohonan.index') }}"
                                    wire:navigate
                                    @click="sidebarOpen = false"
                                    class="{{ request()->routeIs('permohonan.*') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                >
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="truncate">Permohonan Dana</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('permohonan.*') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">R2</span>
                                </a>

                                {{-- Pencairan PPD --}}
                                <span class="text-slate-400 rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm cursor-not-allowed">
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="truncate">Pencairan PPD</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-semibold">R3</span>
                                </span>

                                {{-- Laporan UMP --}}
                                <span class="text-slate-400 rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm cursor-not-allowed">
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span class="truncate">Laporan UMP</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-semibold">R3</span>
                                </span>

                                {{-- Monitoring & Laporan --}}
                                <a
                                    href="{{ route('laporan.pengeluaran-bulanan') }}"
                                    wire:navigate
                                    @click="sidebarOpen = false"
                                    class="{{ request()->routeIs('laporan.pengeluaran-bulanan') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                >
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span class="truncate">Monitoring & Laporan</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('laporan.pengeluaran-bulanan') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">R4</span>
                                </a>
                            </div>
                        </div>

                        {{-- Pengaturan --}}
                        <div>
                            <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                PENGATURAN
                            </p>
                            <div class="space-y-1">
                                <a
                                    href="{{ route('master.tahun-anggaran') }}"
                                    wire:navigate
                                    @click="sidebarOpen = false"
                                    class="{{ request()->routeIs('master.tahun-anggaran') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                >
                                    <span class="flex items-center gap-3 truncate">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="truncate">Tahun Anggaran</span>
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">TA 2026 Aktif</span>
                                </a>

                                @if (Auth::user()?->role === 'admin')
                                    <a
                                        href="{{ route('master.users') }}"
                                        wire:navigate
                                        @click="sidebarOpen = false"
                                        class="{{ request()->routeIs('master.users') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                    >
                                        <span class="flex items-center gap-3 truncate">
                                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span class="truncate">Pengguna & Leveling</span>
                                        </span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('master.users') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">Admin</span>
                                    </a>
                                @endif
                                @if (in_array(Auth::user()?->role, ['admin', 'keuangan']))
                                    <a
                                        href="{{ route('master.audit-logs') }}"
                                        wire:navigate
                                        @click="sidebarOpen = false"
                                        class="{{ request()->routeIs('master.audit-logs') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center justify-between px-3.5 py-2.5 text-sm transition-colors"
                                    >
                                        <span class="flex items-center gap-3 truncate">
                                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="truncate">Audit Trail</span>
                                        </span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('master.audit-logs') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">R1</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </nav>

                    {{-- Bottom Section: User Card M3 --}}
                    @auth
                        <div class="p-3 border-t border-slate-200 bg-slate-50/50 shrink-0">
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200 flex items-center justify-between gap-2.5 shadow-2xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-[#0F2F57] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 truncate leading-tight">
                                            {{ Auth::user()->name }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 truncate leading-tight mt-0.5">
                                            {{ Auth::user()->jabatan ?? ucfirst(Auth::user()->role) }}
                                        </p>
                                        @if (Auth::user()->is_petugas_input)
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-semibold bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200 mt-1">
                                                Petugas Keuangan
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Logout Button --}}
                                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-slate-400 hover:text-red-700 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors"
                                        title="Keluar dari sistem"
                                        aria-label="Keluar dari sistem"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
            @endauth
            </aside>

            {{-- Main Content Area --}}
            <div class="flex-1 flex flex-col h-screen min-w-0 overflow-hidden">
                {{-- Top Bar tetap di atas (pinned) --}}
                <header class="h-16 shrink-0 bg-white border-b border-slate-200 z-20 flex items-center justify-between px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        {{-- Hamburger Button --}}
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-hidden transition-colors"
                            aria-label="Buka menu navigasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        {{-- Breadcrumb --}}
                        <nav class="flex items-center text-xs font-medium text-slate-500" aria-label="Breadcrumb">
                            <span class="text-slate-800 font-semibold">
                                @if (request()->routeIs('dashboard'))
                                    Dashboard
                                @elseif (request()->routeIs('master.kode-anggaran'))
                                    Master Anggaran
                                @elseif (request()->routeIs('master.tahun-anggaran'))
                                    Tahun Anggaran
                                @elseif (request()->routeIs('master.unit-divisi'))
                                    Unit & Divisi
                                @elseif (request()->routeIs('laporan.pengeluaran-bulanan'))
                                    Laporan
                                @elseif (request()->routeIs('master.users'))
                                    Pengaturan
                                @else
                                    SIGMA
                                @endif
                            </span>
                            <span class="mx-2 text-slate-300">/</span>
                            <span class="text-slate-500">
                                @if (request()->routeIs('dashboard'))
                                    Ringkasan Anggaran
                                @elseif (request()->routeIs('master.kode-anggaran'))
                                    Pohon Kode & Plafon RAB
                                @elseif (request()->routeIs('master.tahun-anggaran'))
                                    Daftar Tahun Buku
                                @elseif (request()->routeIs('master.unit-divisi'))
                                    Struktur Organisasi
                                @elseif (request()->routeIs('laporan.pengeluaran-bulanan'))
                                    Pengeluaran Bulanan
                                @elseif (request()->routeIs('master.users'))
                                    Pengguna & Leveling
                                @else
                                    Al-Azhar
                                @endif
                            </span>
                        </nav>
                    </div>

                    {{-- Status Chips --}}
                    <div class="flex items-center gap-2 sm:gap-3">
                        <span class="hidden md:inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-[#0F2F57] text-xs font-medium border border-blue-100">
                            Mode Input Atas Nama Keuangan
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>TA 2026</span>
                        </span>
                    </div>
                </header>

                {{-- Scroll Container Mandiri untuk Halaman Konten --}}
                <div class="flex-1 overflow-y-auto flex flex-col">
                    <main class="p-6 sm:p-8 flex-1 max-w-7xl w-full">
                        {{ $slot }}
                    </main>

                    <footer class="bg-white border-t border-slate-200 py-3.5 px-6 shrink-0">
                        <div class="max-w-7xl w-full flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500 gap-2">
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('images/logo-mark.png') }}" alt="SIGMA" class="h-4 w-auto">
                                <p>&copy; {{ date('Y') }} LAZ Al Azhar. Sistem Informasi & Monitoring Anggaran.</p>
                            </div>
                            <div class="flex items-center gap-3 text-slate-400 text-[11px]">
                                <span>Versi 0.1</span>
                                <span>•</span>
                                <span>Unit Sekretariat</span>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
