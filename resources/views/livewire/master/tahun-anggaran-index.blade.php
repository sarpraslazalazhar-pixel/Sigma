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
        <div class="flex items-center gap-2.5 flex-wrap">
            <button
                type="button"
                wire:click="openRolloverModal"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>Rollover Anggaran</span>
            </button>

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
                    @if ($y->status === 'draft')
                        <button
                            type="button"
                            wire:click="openRolloverModal({{ $y->id }})"
                            class="min-h-[44px] px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors cursor-pointer"
                        >
                            Rollover
                        </button>
                    @endif
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
                                @if ($y->status === 'draft')
                                    <button
                                        type="button"
                                        wire:click="openRolloverModal({{ $y->id }})"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors cursor-pointer"
                                        title="Rollover pos anggaran ke tahun ini"
                                    >
                                        Rollover
                                    </button>
                                @endif
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

    {{-- MODAL ROLLOVER ANGGARAN --}}
    @if ($showRolloverModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl {{ $rolloverStep === 2 ? 'max-w-5xl' : 'max-w-lg' }} w-full max-h-[92vh] flex flex-col overflow-hidden transition-all"
                @click.outside="$wire.set('showRolloverModal', false)"
            >
                @if ($rolloverStep === 1)
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div>
                            <h2 class="text-base font-bold text-[#0F2F57]">
                                Budget Rollover Wizard
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Langkah 1 dari 2 · Pilih tahun sumber, tahun tujuan, dan skema awal.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeRolloverModal"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="generateRolloverPreview" class="flex flex-col flex-1 overflow-hidden">
                        <div class="p-6 overflow-y-auto space-y-4 flex-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="rolloverSourceId" class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tahun Sumber *
                                    </label>
                                    <select
                                        id="rolloverSourceId"
                                        wire:model="rolloverSourceId"
                                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('rolloverSourceId') border-red-500 @enderror"
                                    >
                                        <option value="">-- Pilih Sumber --</option>
                                        @foreach ($years as $src)
                                            <option value="{{ $src->id }}">
                                                TA {{ $src->tahun }} ({{ ucfirst($src->status) }} - {{ $src->kode_anggaran_count }} pos)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('rolloverSourceId')
                                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="rolloverTargetId" class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tahun Tujuan (Harus Draft) *
                                    </label>
                                    <select
                                        id="rolloverTargetId"
                                        wire:model="rolloverTargetId"
                                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('rolloverTargetId') border-red-500 @enderror"
                                    >
                                        <option value="">-- Pilih Tujuan --</option>
                                        @foreach ($years->where('status', 'draft') as $tgt)
                                            <option value="{{ $tgt->id }}">
                                                TA {{ $tgt->tahun }} (Draft)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('rolloverTargetId')
                                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-2">
                                    Skema Salin Alokasi Nominal *
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                        <input type="radio" wire:model.live="rolloverScheme" value="zero" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <span class="text-xs font-semibold text-slate-800 block">Salin Struktur Saja (Zero-Based)</span>
                                            <span class="text-[11px] text-slate-500">Seluruh pohon kode anggaran disalin, namun plafon awal diatur menjadi Rp 0.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                        <input type="radio" wire:model.live="rolloverScheme" value="full" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <span class="text-xs font-semibold text-slate-800 block">Salin Plafon 100% (Identik)</span>
                                            <span class="text-[11px] text-slate-500">Plafon awal tahun tujuan disamakan persis dengan plafon efektif tahun sumber.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                        <input type="radio" wire:model.live="rolloverScheme" value="percentage" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                        <div class="flex-1">
                                            <span class="text-xs font-semibold text-slate-800 block">Salin + Penyesuaian Persentase (%)</span>
                                            <span class="text-[11px] text-slate-500">Terapkan penyesuaian untuk seluruh pos leaf (misal: 10 untuk +10%, atau -5 untuk -5%).</span>
                                            @if ($rolloverScheme === 'percentage')
                                                <div class="mt-2 flex items-center gap-2">
                                                    <input
                                                        type="number"
                                                        step="0.1"
                                                        wire:model="rolloverPercentage"
                                                        placeholder="+5 atau -10"
                                                        class="w-32 min-h-[38px] rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 font-bold"
                                                    />
                                                    <span class="text-xs text-slate-600 font-semibold">%</span>
                                                </div>
                                                @error('rolloverPercentage')
                                                    <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                                                @enderror
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2.5 bg-slate-50 shrink-0">
                            <button
                                type="button"
                                wire:click="closeRolloverModal"
                                class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-white transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer shadow-xs flex items-center gap-1.5"
                            >
                                <span>Lanjutkan ke Preview</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                @else
                    {{-- STEP 2: PREVIEW & EDIT PER-ITEM --}}
                    @php
                        $srcYear = $years->firstWhere('id', $rolloverSourceId);
                        $tgtYear = $years->firstWhere('id', $rolloverTargetId);
                        $schemeName = match ($rolloverScheme) {
                            'zero' => 'Zero-Based (Rp 0)',
                            'full' => 'Salin Plafon 100%',
                            'percentage' => 'Penyesuaian (' . ((float) $rolloverPercentage >= 0 ? '+' : '') . $rolloverPercentage . '%)',
                            default => $rolloverScheme,
                        };
                        $metrics = $this->previewMetrics;
                    @endphp
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                        <div>
                            <h2 class="text-base font-bold text-[#0F2F57]">
                                Pratinjau & Edit Rollover Anggaran
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                TA {{ $srcYear?->tahun }} → TA {{ $tgtYear?->tahun }} · Skema: <span class="font-medium text-slate-700">{{ $schemeName }}</span> · <span class="font-medium text-slate-700">{{ $metrics['total'] }}</span> pos leaf
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="previousRolloverStep"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span class="hidden sm:inline">Kembali ke Pengaturan</span>
                                <span class="sm:hidden">Kembali</span>
                            </button>
                            <button
                                type="button"
                                wire:click="closeRolloverModal"
                                class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- SEARCH & FILTER BAR --}}
                    <div class="px-6 py-3 border-b border-slate-100 bg-slate-50/70 shrink-0 space-y-2.5">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                            {{-- Search Box --}}
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    wire:model.live.debounce.150ms="previewSearch"
                                    placeholder="Cari kode pos atau nama akun (contoh: 2.4.2 atau Listrik)..."
                                    class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-300 focus:border-[#1E60D5] focus:outline-hidden bg-white text-slate-800 placeholder:text-slate-400"
                                />
                                @if ($previewSearch)
                                    <button
                                        type="button"
                                        wire:click="$set('previewSearch', '')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            {{-- Filter Chips --}}
                            <div class="flex items-center gap-1.5 shrink-0 overflow-x-auto pb-0.5">
                                <button
                                    type="button"
                                    wire:click="$set('previewFilter', 'all')"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $previewFilter === 'all' ? 'bg-[#0F2F57] text-white shadow-2xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                                >
                                    Semua ({{ $metrics['total'] }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('previewFilter', 'changed')"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $previewFilter === 'changed' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                                >
                                    Diubah ({{ $metrics['changed'] }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('previewFilter', 'unchanged')"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $previewFilter === 'unchanged' ? 'bg-slate-700 text-white shadow-2xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                                >
                                    Tetap ({{ $metrics['unchanged'] }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('previewFilter', 'zero')"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $previewFilter === 'zero' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                                >
                                    Rp 0 ({{ $metrics['zero'] }})
                                </button>
                            </div>
                        </div>

                        {{-- Quick Bulk Actions Toolbar --}}
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs pt-2 border-t border-slate-200/60">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-slate-500 font-medium">Ubah massal ({{ $metrics['filtered'] }} pos tampil):</span>
                                <button
                                    type="button"
                                    wire:click="applyBulkPercentage(5)"
                                    class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 font-semibold transition-colors cursor-pointer"
                                >
                                    +5%
                                </button>
                                <button
                                    type="button"
                                    wire:click="applyBulkPercentage(10)"
                                    class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 font-semibold transition-colors cursor-pointer"
                                >
                                    +10%
                                </button>
                                <button
                                    type="button"
                                    wire:click="applyBulkPercentage(15)"
                                    class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 font-semibold transition-colors cursor-pointer"
                                >
                                    +15%
                                </button>
                                <button
                                    type="button"
                                    wire:click="applyBulkPercentage(-5)"
                                    class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300 font-semibold transition-colors cursor-pointer"
                                >
                                    -5%
                                </button>
                            </div>
                            <div class="flex items-center gap-2 self-end sm:self-auto">
                                <button
                                    type="button"
                                    wire:click="resetAllPreviewToSource"
                                    class="px-2.5 py-1 rounded-md bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-medium transition-colors cursor-pointer flex items-center gap-1"
                                >
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Reset Semua ke Sumber</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="setAllPreviewToZero"
                                    class="px-2.5 py-1 rounded-md bg-white border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 font-medium transition-colors cursor-pointer"
                                >
                                    Set Semua ke Rp 0
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE CONTAINER --}}
                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                        <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2.5 px-3 w-28">Kode</th>
                                        <th class="py-2.5 px-3">Nama Akun</th>
                                        <th class="py-2.5 px-3 text-right w-36">Plafon Sumber</th>
                                        <th class="py-2.5 px-3 text-center w-40">Status / Selisih</th>
                                        <th class="py-2.5 px-3 text-right w-64">Plafon Target (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($this->filteredRolloverPreview as $originalIndex => $item)
                                        @php
                                            $sourceVal = (int) ($item['source_plafon'] ?? 0);
                                            $targetVal = \App\Livewire\Master\TahunAnggaranIndex::parseRupiah($item['target_plafon'] ?? 0);
                                            $diffVal = $targetVal - $sourceVal;
                                            $diffPct = $sourceVal > 0 ? round(($diffVal / $sourceVal) * 100, 1) : ($targetVal > 0 ? 100 : 0);
                                        @endphp
                                        <tr wire:key="rollover-preview-{{ $item['node_id'] }}" class="hover:bg-slate-50/70 transition-colors">
                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                                                {{ $item['kode'] }}
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-800 font-medium">
                                                {{ $item['nama'] }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right tabular-nums text-slate-600 whitespace-nowrap font-medium">
                                                Rp {{ number_format($sourceVal, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                @if ($targetVal === 0 && $sourceVal > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                        Rp 0 (Nol)
                                                    </span>
                                                @elseif ($diffVal > 0)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#D0F7E3] text-[#002114]">
                                                        ↑ +Rp {{ number_format($diffVal, 0, ',', '.') }} (+{{ $diffPct }}%)
                                                    </span>
                                                @elseif ($diffVal < 0)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FFDAD6] text-[#410002]">
                                                        ↓ -Rp {{ number_format(abs($diffVal), 0, ',', '.') }} ({{ $diffPct }}%)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                                        = Tetap
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-right">
                                                <div
                                                    x-data="{
                                                        raw: '{{ number_format($targetVal, 0, ',', '.') }}',
                                                        onInput(e) {
                                                            let digits = e.target.value.replace(/\D/g, '');
                                                            let num = digits ? parseInt(digits, 10) : 0;
                                                            this.raw = num > 0 ? num.toLocaleString('id-ID') : (digits === '' ? '' : '0');
                                                            e.target.value = this.raw;
                                                            $wire.set('rolloverPreview.{{ $originalIndex }}.target_plafon', num);
                                                        }
                                                    }"
                                                    class="space-y-1.5"
                                                >
                                                    <div class="relative">
                                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">Rp</span>
                                                        <input
                                                            type="text"
                                                            inputmode="numeric"
                                                            x-model="raw"
                                                            x-on:input="onInput($event)"
                                                            class="w-full min-h-[36px] pl-8 pr-2.5 py-1 text-right text-xs font-bold rounded-lg border border-blue-200 bg-blue-50/60 focus:bg-white focus:border-[#1E60D5] focus:ring-1 focus:ring-blue-500 tabular-nums text-slate-900 transition-colors"
                                                            placeholder="0"
                                                        />
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 flex-wrap">
                                                        <button
                                                            type="button"
                                                            wire:click="adjustItemPlafon({{ $originalIndex }}, 100000)"
                                                            title="Tambah Rp 100.000"
                                                            class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-blue-100 text-[10px] font-semibold text-slate-700 hover:text-blue-700 transition-colors cursor-pointer"
                                                        >
                                                            +100rb
                                                        </button>
                                                        <button
                                                            type="button"
                                                            wire:click="adjustItemPlafon({{ $originalIndex }}, 1000000)"
                                                            title="Tambah Rp 1.000.000"
                                                            class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-blue-100 text-[10px] font-semibold text-slate-700 hover:text-blue-700 transition-colors cursor-pointer"
                                                        >
                                                            +1jt
                                                        </button>
                                                        <button
                                                            type="button"
                                                            wire:click="resetItemToSource({{ $originalIndex }})"
                                                            title="Reset ke Plafon Sumber"
                                                            class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-amber-100 text-[10px] font-medium text-slate-600 hover:text-amber-800 transition-colors cursor-pointer"
                                                        >
                                                            ↺ Sumber
                                                        </button>
                                                        <button
                                                            type="button"
                                                            wire:click="setItemToZero({{ $originalIndex }})"
                                                            title="Set ke Rp 0"
                                                            class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-rose-100 text-[10px] font-medium text-slate-600 hover:text-rose-700 transition-colors cursor-pointer"
                                                        >
                                                            0
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-slate-400">
                                                @if ($previewSearch || $previewFilter !== 'all')
                                                    <p class="font-medium text-slate-600">Tidak ada pos anggaran yang cocok dengan filter / pencarian.</p>
                                                    <button
                                                        type="button"
                                                        wire:click="$set('previewSearch', ''); $set('previewFilter', 'all');"
                                                        class="mt-2 text-xs text-blue-600 hover:underline font-semibold cursor-pointer"
                                                    >
                                                        Reset Filter & Pencarian
                                                    </button>
                                                @else
                                                    Tidak ada pos anggaran (leaf) pada tahun anggaran sumber.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- STICKY FOOTER SUMMARY --}}
                    <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0 bg-slate-50">
                        <div class="text-xs text-slate-600 flex items-center gap-4 flex-wrap">
                            <div>
                                <span class="text-slate-400">Total Sumber:</span>
                                <span class="font-bold tabular-nums text-slate-700 ml-1">
                                    Rp {{ number_format($this->totalRolloverSourcePlafon, 0, ',', '.') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400">Total Target:</span>
                                <span class="font-bold tabular-nums text-blue-700 ml-1">
                                    Rp {{ number_format($this->totalRolloverTargetPlafon, 0, ',', '.') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400">Selisih Total:</span>
                                @php
                                    $selisih = $this->totalRolloverSelisih;
                                    $selisihPct = $this->totalRolloverSourcePlafon > 0 ? round(($selisih / $this->totalRolloverSourcePlafon) * 100, 1) : 0;
                                @endphp
                                <span class="font-bold tabular-nums ml-1 {{ $selisih > 0 ? 'text-emerald-700' : ($selisih < 0 ? 'text-red-600' : 'text-slate-700') }}">
                                    {{ $selisih > 0 ? '+' : '' }}Rp {{ number_format($selisih, 0, ',', '.') }}
                                    ({{ $selisih > 0 ? '+' : '' }}{{ $selisihPct }}%)
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <button
                                type="button"
                                wire:click="previousRolloverStep"
                                class="min-h-[44px] px-4 py-2 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-white transition-colors cursor-pointer"
                            >
                                ← Kembali
                            </button>
                            <button
                                type="button"
                                wire:click="executeRollover"
                                class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer shadow-xs flex items-center gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span>Proses Rollover ({{ $metrics['total'] }} Pos)</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
