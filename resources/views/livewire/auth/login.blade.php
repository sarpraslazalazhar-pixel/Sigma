<div class="bg-gradient-to-br from-slate-100 via-sky-50/60 to-blue-100/50 min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 relative overflow-hidden">
    {{-- Fluid Liquid Blobs (Blurred Glow in Viewport Background) --}}
    <div class="w-96 h-96 rounded-full bg-gradient-to-tr from-blue-500/30 to-cyan-400/25 blur-3xl absolute -top-10 -left-10 pointer-events-none" aria-hidden="true"></div>
    <div class="w-[420px] h-[420px] rounded-full bg-gradient-to-bl from-indigo-500/20 via-sky-400/25 to-teal-400/20 blur-3xl absolute -bottom-10 -right-10 pointer-events-none" aria-hidden="true"></div>

    {{-- Liquid Glass Outer Canvas --}}
    <div class="bg-white/50 backdrop-blur-2xl border border-white/80 shadow-[0_20px_60px_-15px_rgba(15,47,87,0.12)] rounded-3xl p-6 sm:p-10 relative overflow-hidden max-w-5xl w-full min-h-[640px] flex items-center justify-center">
        {{-- Internal Fluid Waves & Refraction Ornaments --}}
        <svg class="absolute -left-20 -bottom-20 w-96 h-96 text-sky-200/40 pointer-events-none" viewBox="0 0 400 400" fill="currentColor" aria-hidden="true">
            <path d="M0,192 C70,160 120,240 180,210 C240,180 270,90 320,120 C370,150 390,260 400,320 L400,400 L0,400 Z" />
        </svg>
        <svg class="absolute -left-12 -top-12 w-80 h-80 text-blue-200/35 pointer-events-none" viewBox="0 0 300 300" fill="currentColor" aria-hidden="true">
            <path d="M0,0 L200,0 C180,70 210,120 160,170 C110,220 50,180 0,160 Z" />
        </svg>
        <svg class="absolute -right-24 -top-20 w-[420px] h-[420px] text-cyan-200/40 pointer-events-none" viewBox="0 0 400 400" fill="currentColor" aria-hidden="true">
            <path d="M200,0 C280,30 350,20 400,80 L400,320 C340,300 290,360 220,320 C150,280 140,180 160,110 C180,40 120,20 200,0 Z" />
        </svg>
        <svg class="absolute -right-16 -bottom-16 w-80 h-80 text-indigo-200/35 pointer-events-none" viewBox="0 0 300 300" fill="currentColor" aria-hidden="true">
            <path d="M120,300 C150,240 210,250 260,200 C310,150 280,80 300,0 L300,300 Z" />
        </svg>

        {{-- Floating Glass Ornaments --}}
        <div class="absolute top-14 left-20 w-20 h-20 rounded-full border border-white/60 bg-white/20 backdrop-blur-xs pointer-events-none" aria-hidden="true"></div>
        <div class="absolute bottom-16 right-24 w-14 h-14 rounded-full border border-sky-300/50 bg-sky-200/20 backdrop-blur-xs pointer-events-none" aria-hidden="true"></div>
        <div class="absolute top-24 right-32 w-7 h-7 rounded-md rotate-45 border border-white/70 bg-blue-100/30 backdrop-blur-xs pointer-events-none" aria-hidden="true"></div>
        <div class="absolute bottom-28 left-28 w-6 h-6 rounded-md rotate-12 border border-sky-300/40 bg-white/30 backdrop-blur-xs pointer-events-none" aria-hidden="true"></div>

        {{-- Main Focal Point: Liquid Glass Floating Card --}}
        <div class="bg-white/80 backdrop-blur-xl border border-white/90 shadow-[0_25px_50px_-12px_rgba(15,47,87,0.15)] rounded-3xl p-8 sm:p-10 relative z-10 max-w-md w-full overflow-hidden">
            {{-- Specular Sheen (Pantulan Kilap Kaca Atas) --}}
            <div class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-white to-transparent pointer-events-none" aria-hidden="true"></div>

            {{-- Header: Brand Logo & Subtext --}}
            <div class="text-center mb-6">
                <img
                    src="{{ asset('images/logo-sigma-transparent.png') }}"
                    alt="SIGMA: Sistem Informasi & Monitoring Anggaran LAZ Al-Azhar"
                    class="h-14 mx-auto object-contain mb-3"
                >
                <h1 class="text-base font-bold text-[#0F2F57] tracking-tight">
                    Sistem Informasi & Monitoring Anggaran
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    LAZ Al Azhar
                </p>
            </div>

            {{-- Form Login --}}
            <form wire:submit="login" class="space-y-4" novalidate>
                {{-- Field: Username / Email --}}
                <div>
                    <label for="login_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Username atau Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="login_id"
                            wire:model="login_id"
                            autocomplete="username"
                            placeholder="admin atau nama@alazharpeduli.or.id"
                            class="block w-full h-11 pl-10 pr-3.5 rounded-xl border text-sm text-slate-900 placeholder:text-slate-400 bg-white/85 backdrop-blur-xs transition-all
                                   @error('login_id') border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200 @else border-slate-200/90 hover:border-slate-300 focus:bg-white focus:border-[#1E60D5] focus:ring-2 focus:ring-[#1E60D5]/20 @enderror"
                            required
                            autofocus
                        />
                    </div>
                    @error('login_id')
                        <p class="mt-1 text-xs text-red-600 font-medium" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Field: Password --}}
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <input
                            type="{{ $show_password ? 'text' : 'password' }}"
                            id="password"
                            wire:model="password"
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            class="block w-full h-11 pl-10 pr-11 rounded-xl border text-sm text-slate-900 placeholder:text-slate-400 bg-white/85 backdrop-blur-xs transition-all
                                   @error('password') border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-200 @else border-slate-200/90 hover:border-slate-300 focus:bg-white focus:border-[#1E60D5] focus:ring-2 focus:ring-[#1E60D5]/20 @enderror"
                            required
                        />
                        <button
                            type="button"
                            wire:click="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 hover:text-slate-600 focus:outline-hidden focus:text-[#1E60D5] transition-colors"
                            aria-label="{{ $show_password ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi' }}"
                        >
                            @if ($show_password)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 font-medium" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember Me & Help Text --}}
                <div class="flex items-center justify-between text-xs pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none text-slate-600">
                        <input
                            type="checkbox"
                            wire:model="remember"
                            class="w-3.5 h-3.5 rounded border-slate-300 text-[#1E60D5] focus:ring-[#1E60D5]"
                        />
                        <span>Ingat saya</span>
                    </label>
                    <span class="text-slate-400 hover:text-slate-600 transition-colors cursor-default">
                        Lupa kata sandi? Hubungi IT
                    </span>
                </div>

                {{-- Submit Button: Pill Shaped with Liquid Glass Glow --}}
                <div class="pt-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full sm:w-3/4 mx-auto block text-center py-2.5 px-6 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-bold uppercase tracking-wider shadow-lg shadow-blue-500/25 hover:shadow-blue-600/35 transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer relative overflow-hidden"
                    >
                        <span wire:loading.remove wire:target="login">Masuk ke Sistem</span>
                        <span wire:loading wire:target="login" class="inline-flex items-center justify-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memverifikasi...</span>
                        </span>
                    </button>
                </div>
            </form>

            {{-- 1-Click Test Account Quick Selector (Dev Environment) --}}
            @if (app()->environment('local'))
                <div class="mt-6 pt-4 border-t border-slate-200/60">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Pilih Akun Uji Cepat (1-Klik)
                        </span>
                        <span class="text-[10px] text-slate-400">Pass: password</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                        <button
                            type="button"
                            wire:click="fillDemo('admin')"
                            class="rounded-lg border border-slate-200/80 bg-white/70 hover:bg-white text-xs px-2 py-1.5 text-left transition-colors"
                        >
                            <div class="font-semibold text-[#0F2F57] text-[11px]">admin</div>
                            <div class="text-[9px] text-slate-500">Admin IT</div>
                        </button>

                        <button
                            type="button"
                            wire:click="fillDemo('tu.sekretariat')"
                            class="rounded-lg border border-slate-200/80 bg-white/70 hover:bg-white text-xs px-2 py-1.5 text-left transition-colors"
                        >
                            <div class="font-semibold text-[#0F2F57] text-[11px]">tu.sekretariat</div>
                            <div class="text-[9px] text-slate-500">Petugas Input</div>
                        </button>

                        <button
                            type="button"
                            wire:click="fillDemo('deden.salim')"
                            class="rounded-lg border border-slate-200/80 bg-white/70 hover:bg-white text-xs px-2 py-1.5 text-left transition-colors"
                        >
                            <div class="font-semibold text-[#0F2F57] text-[11px]">deden.salim</div>
                            <div class="text-[9px] text-slate-500">Manager HRD</div>
                        </button>

                        <button
                            type="button"
                            wire:click="fillDemo('rahmatullah.sidik')"
                            class="rounded-lg border border-slate-200/80 bg-white/70 hover:bg-white text-xs px-2 py-1.5 text-left transition-colors"
                        >
                            <div class="font-semibold text-[#0F2F57] text-[11px]">rahmatullah.sidik</div>
                            <div class="text-[9px] text-slate-500">Kepala Divisi</div>
                        </button>

                        <button
                            type="button"
                            wire:click="fillDemo('rochadi.kohar')"
                            class="rounded-lg border border-slate-200/80 bg-white/70 hover:bg-white text-xs px-2 py-1.5 text-left transition-colors"
                        >
                            <div class="font-semibold text-[#0F2F57] text-[11px]">rochadi.kohar</div>
                            <div class="text-[9px] text-slate-500">Keuangan</div>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
