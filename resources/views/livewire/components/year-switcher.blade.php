<div class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = !open"
        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-xs font-semibold text-slate-800 dark:text-slate-100 shadow-2xs transition-all cursor-pointer whitespace-nowrap min-h-[36px]"
        aria-haspopup="true"
        aria-label="Tahun Anggaran {{ $selectedYear?->tahun ?? 'belum dipilih' }}{{ $selectedYear ? ', '.ucfirst($selectedYear->status) : '' }}"
        :aria-expanded="open"
    >
        <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>TA {{ $selectedYear?->tahun ?? '-' }}</span>
        </span>

        @if ($selectedYear)
            @if ($selectedYear->status === 'aktif')
                <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                    Aktif
                </span>
            @elseif ($selectedYear->status === 'draft')
                <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800">
                    Draft
                </span>
            @else
                <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                    Ditutup
                </span>
            @endif
        @endif

        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1.5 z-50 focus:outline-hidden"
        style="display: none;"
    >
        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
            Pilih Tahun Anggaran
        </div>

        <div class="max-h-60 overflow-y-auto py-1">
            @foreach ($years as $year)
                <button
                    type="button"
                    wire:click="switchYear({{ $year->id }})"
                    @click="open = false"
                    class="w-full text-left px-3 py-3 sm:py-2 text-xs flex items-center justify-between transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/70 cursor-pointer {{ $year->id === $selectedYearId ? 'bg-blue-50/60 dark:bg-blue-950/30 text-[#0F2F57] dark:text-blue-300 font-semibold' : 'text-slate-700 dark:text-slate-200' }}"
                >
                    <span class="inline-flex items-center gap-2">
                        @if ($year->id === $selectedYearId)
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <span class="w-3.5 h-3.5"></span>
                        @endif
                        <span>TA {{ $year->tahun }}</span>
                    </span>

                    @if ($year->status === 'aktif')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                            Aktif
                        </span>
                    @elseif ($year->status === 'draft')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800">
                            Draft
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                            Ditutup
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>
