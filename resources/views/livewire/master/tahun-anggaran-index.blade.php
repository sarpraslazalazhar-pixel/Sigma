<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Kelola Tahun Anggaran
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Atur tahun anggaran organisasi. Hanya satu tahun yang dapat berstatus aktif sebagai acuan operasional.
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Tahun Baru</span>
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
        @foreach ($years as $y)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-base font-bold text-[#0F2F57]">
                            TA {{ $y->tahun }}
                        </span>
                        @if ($y->status === 'aktif')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Aktif
                            </span>
                        @elseif ($y->status === 'draft')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                Draft
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                Ditutup
                            </span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-500">
                        {{ $y->kode_anggaran_count }} pos terdaftar
                    </span>
                </div>

                {{-- Mobile Action Row (tap targets >= 44px) --}}
                <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                    @if ($y->status !== 'aktif')
                        <button
                            type="button"
                            wire:click="activateYear({{ $y->id }})"
                            class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors cursor-pointer"
                        >
                            Aktifkan
                        </button>
                    @endif

                    @if ($y->status !== 'ditutup')
                        <button
                            type="button"
                            wire:click="closeYear({{ $y->id }})"
                            class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- VIEW 2: TAMPILAN DESKTOP (M3 Table, >= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">Tahun Anggaran</th>
                    <th class="py-3.5 px-4">Jumlah Pos Anggaran</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($years as $y)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
                            TA {{ $y->tahun }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 tabular-nums">
                            {{ $y->kode_anggaran_count }} pos terdaftar
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if ($y->status === 'aktif')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @elseif ($y->status === 'draft')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Draft
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                    Ditutup
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-2 justify-end">
                                @if ($y->status !== 'aktif')
                                    <button
                                        type="button"
                                        wire:click="activateYear({{ $y->id }})"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors cursor-pointer"
                                    >
                                        Aktifkan
                                    </button>
                                @endif

                                @if ($y->status !== 'ditutup')
                                    <button
                                        type="button"
                                        wire:click="closeYear({{ $y->id }})"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                                    >
                                        Tutup
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- MODAL TAMBAH TAHUN ANGGARAN --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-sm w-full overflow-hidden"
                @click.outside="$wire.set('showModal', false)"
            >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-[#0F2F57]">
                        Tambah Tahun Anggaran
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

                <form wire:submit="saveYear" class="p-6 space-y-4">
                    <div>
                        <label for="form_tahun" class="block text-xs font-semibold text-slate-700 mb-1">
                            Tahun (4 Digit) *
                        </label>
                        <input
                            type="number"
                            id="form_tahun"
                            wire:model="form_tahun"
                            min="2020"
                            max="2100"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-sm font-bold text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('form_tahun') border-red-500 @enderror"
                            required
                        />
                        @error('form_tahun')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="form_status" class="block text-xs font-semibold text-slate-700 mb-1">
                            Status Awal
                        </label>
                        <select
                            id="form_status"
                            wire:model="form_status"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                        >
                            <option value="draft">Draft (Perencanaan)</option>
                            <option value="aktif">Aktif (Operasional)</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Jika dipilih Aktif, tahun anggaran lain yang sedang aktif akan otomatis beralih ke status draft.
                        </p>
                    </div>

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
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
