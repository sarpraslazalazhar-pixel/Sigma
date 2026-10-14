<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('permohonan.index') }}"
                    wire:navigate
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    title="Kembali ke daftar permohonan"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                    Buat Permohonan Dana
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 pl-7">
                Isi form permohonan dana kegiatan atau operasional sesuai ketentuan anggaran institusi.
            </p>
        </div>
        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <button
                type="button"
                wire:click="saveDraft"
                class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
            >
                Simpan Draft
            </button>
            <button
                type="button"
                wire:click="submitNow"
                class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
            >
                Ajukan Sekarang
            </button>
        </div>
    </div>

    {{-- Form Utama --}}
    <div class="space-y-6">
        {{-- SECTION 1: HEADER INFORMASI KEGIATAN --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <h2 class="text-sm font-bold text-[#0F2F57] uppercase tracking-wider pb-2 border-b border-slate-100">
                Informasi Pengajuan & Kegiatan
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Program --}}
                <div>
                    <label for="program" class="block text-xs font-semibold text-slate-700 mb-1">
                        Program *
                    </label>
                    <input
                        type="text"
                        id="program"
                        wire:model="program"
                        list="program-list"
                        placeholder="Ketik nama program (contoh: Operasional Kesekretariatan, Dakwah, dll)"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3.5 py-2.5 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('program') border-red-500 @enderror"
                        required
                    />
                    <datalist id="program-list">
                        @foreach ($programs as $prog)
                            <option value="{{ $prog->nama }}"></option>
                        @endforeach
                    </datalist>
                    @error('program')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unit / Divisi --}}
                <div>
                    <label for="unit_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Unit / Divisi Pengaju *
                    </label>
                    <select
                        id="unit_id"
                        wire:model="unit_id"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('unit_id') border-red-500 @enderror"
                    >
                        <option value="">Pilih Unit / Divisi</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}">{{ $u->nama }} ({{ $u->kode }})</option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sumber Dana --}}
                <div>
                    <label for="sumber_dana_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Sumber Dana *
                    </label>
                    <select
                        id="sumber_dana_id"
                        wire:model.live="sumber_dana_id"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('sumber_dana_id') border-red-500 @enderror"
                    >
                        <option value="">Pilih Sumber Dana</option>
                        @foreach ($sumberDanaList as $sd)
                            <option value="{{ $sd->id }}">
                                {{ $sd->nama }} {{ $sd->butuh_asnaf ? '(Wajib Asnaf)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('sumber_dana_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Kegiatan --}}
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="nama_kegiatan" class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Kegiatan / Keperluan *
                    </label>
                    <input
                        type="text"
                        id="nama_kegiatan"
                        wire:model="nama_kegiatan"
                        placeholder="Contoh: Santunan Yatim Ramadhan 1447 H, Operasional IT Bulanan, dll."
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('nama_kegiatan') border-red-500 @enderror"
                    />
                    @error('nama_kegiatan')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal Mulai --}}
                <div>
                    <label for="tgl_mulai" class="block text-xs font-semibold text-slate-700 mb-1">
                        Tanggal Mulai Kegiatan *
                    </label>
                    <input
                        type="date"
                        id="tgl_mulai"
                        wire:model="tgl_mulai"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('tgl_mulai') border-red-500 @enderror"
                    />
                    @error('tgl_mulai')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal Selesai --}}
                <div>
                    <label for="tgl_selesai" class="block text-xs font-semibold text-slate-700 mb-1">
                        Tanggal Selesai Kegiatan (Opsional)
                    </label>
                    <input
                        type="date"
                        id="tgl_selesai"
                        wire:model="tgl_selesai"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('tgl_selesai') border-red-500 @enderror"
                    />
                    @error('tgl_selesai')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mata Anggaran / Pos Anggaran (Searchable Dropdown) --}}
                <div>
                    <label for="kode_anggaran_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Pos Anggaran (Mata Anggaran) *
                    </label>

                    {{-- Hidden synchronization select for testing & fallback --}}
                    <select
                        id="kode_anggaran_id"
                        wire:model.live="kode_anggaran_id"
                        class="sr-only"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                        <option value="">Pilih Pos Anggaran Aktif</option>
                        @foreach ($kodeAnggaranList as $ka)
                            <option value="{{ $ka->id }}">{{ $ka->kode }} - {{ $ka->nama }}</option>
                        @endforeach
                    </select>

                    <div
                        class="relative"
                        x-data="{
                            open: false,
                            search: '',
                            options: {{ Js::from($kodeAnggaranList->map(fn($k) => ['id' => $k->id, 'kode' => $k->kode, 'nama' => $k->nama])) }},
                            get filteredOptions() {
                                if (!this.search.trim()) return this.options;
                                const term = this.search.toLowerCase();
                                return this.options.filter(o => 
                                    o.kode.toLowerCase().includes(term) || 
                                     o.nama.toLowerCase().includes(term)
                                 );
                             },
                            get selectedLabel() {
                                const found = this.options.find(o => o.id == $wire.kode_anggaran_id);
                                return found ? `${found.kode} - ${found.nama}` : 'Pilih Pos Anggaran Aktif';
                            },
                            select(id) {
                                $wire.set('kode_anggaran_id', id);
                                this.open = false;
                                this.search = '';
                            }
                        }"
                        @click.outside="open = false"
                    >
                        {{-- Trigger Button --}}
                        <button
                            type="button"
                            @click="open = !open; if (open) $nextTick(() => $refs.searchInput.focus())"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-medium text-slate-800 px-3.5 py-2.5 bg-white flex items-center justify-between text-left transition-colors cursor-pointer hover:border-slate-400 focus:border-[#1E60D5] focus:outline-hidden @error('kode_anggaran_id') border-red-500 @enderror"
                            :class="{ 'border-[#1E60D5] ring-2 ring-blue-100': open }"
                            aria-haspopup="listbox"
                            :aria-expanded="open"
                        >
                            <span class="truncate" :class="{ 'text-slate-400 font-normal': !$wire.kode_anggaran_id }" x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0 ml-2 transition-transform duration-200" :class="{ 'rotate-180 text-[#1E60D5]': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Dropdown Panel --}}
                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden text-xs"
                            style="min-width: 280px;"
                        >
                            {{-- Search Input inside Dropdown --}}
                            <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                <div class="relative">
                                    <input
                                        type="text"
                                        x-ref="searchInput"
                                        x-model="search"
                                        placeholder="Ketik kode (misal: 2.4.2) atau nama pos..."
                                        class="w-full min-h-[38px] rounded-xl border border-slate-300 text-xs text-slate-800 pl-8 pr-3 py-1.5 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                                        @keydown.escape.prevent="open = false"
                                    />
                                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            {{-- Options List --}}
                            <div class="max-h-60 overflow-y-auto p-1.5 space-y-1 divide-y divide-slate-50" role="listbox">
                                <template x-for="opt in filteredOptions" :key="opt.id">
                                    <button
                                        type="button"
                                        @click="select(opt.id)"
                                        class="w-full text-left px-3 py-2 rounded-xl transition-colors flex items-center justify-between gap-2 cursor-pointer hover:bg-slate-100"
                                        :class="opt.id == $wire.kode_anggaran_id ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-800'"
                                        role="option"
                                        :aria-selected="opt.id == $wire.kode_anggaran_id"
                                    >
                                        <div class="min-w-0 flex items-center gap-2">
                                            <span class="font-mono text-[11px] px-2 py-0.5 rounded-md bg-white border border-slate-200 shrink-0" :class="opt.id == $wire.kode_anggaran_id ? 'border-blue-300 font-bold text-[#001B3E]' : 'text-slate-600'" x-text="opt.kode"></span>
                                            <span class="truncate text-xs" x-text="opt.nama"></span>
                                        </div>
                                        <template x-if="opt.id == $wire.kode_anggaran_id">
                                            <svg class="w-4 h-4 text-[#1E60D5] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                    </button>
                                </template>

                                {{-- Empty Search State --}}
                                <div x-show="filteredOptions.length === 0" class="py-6 px-4 text-center text-slate-400 text-xs">
                                    Pos anggaran tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('kode_anggaran_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Atasan / Pejabat Penyetuju Langsung --}}
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="atasan_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Pilih Atasan / Pejabat Penyetuju Langsung (Opsional / Rekomendasi)
                    </label>
                    <select
                        id="atasan_id"
                        wire:model="atasan_id"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('atasan_id') border-red-500 @enderror"
                    >
                        <option value="">Pilih Atasan Penyetuju (Default: Sesuai Struktur Divisi)</option>
                        @foreach ($atasanList as $atasan)
                            <option value="{{ $atasan->id }}">
                                {{ $atasan->name }} - {{ $atasan->jabatan ?? 'Manager / Kadiv' }}
                                {{ $atasan->unit_id === $unit_id ? '(Satu Divisi)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Untuk staf / koordinator divisi, sistem otomatis memilihkan Manager divisi bersangkutan (misal: Manager Humas, IT, dan GA untuk divisi Humas/IT).
                    </p>
                    @error('atasan_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- KARTU INFO BUDGET REAL-TIME --}}
            @if ($budgetInfo)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Informasi Anggaran Pos: {{ $budgetInfo['kode'] }} - {{ $budgetInfo['nama'] }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $budgetInfo['status'] === 'Aman' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                            Status: {{ $budgetInfo['status'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 rounded-xl p-3 border border-slate-200 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Plafon Disahkan</span>
                            <span class="font-bold text-slate-800 tabular-nums">
                                Rp {{ number_format($budgetInfo['plafon'] ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Dana Terikat</span>
                            <span class="font-bold text-amber-700 tabular-nums">
                                Rp {{ number_format($budgetInfo['dana_terikat'] ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Realisasi</span>
                            <span class="font-bold text-blue-700 tabular-nums">
                                Rp {{ number_format($budgetInfo['realisasi'] ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Sisa Tersedia</span>
                            <span class="font-bold text-emerald-700 tabular-nums text-sm">
                                Rp {{ number_format($budgetInfo['sisa_tersedia'] ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- PERINGATAN OVER BUDGET JIKA TOTAL DIMINTA MELEBIHI SISA --}}
                    @if ($isOverBudget)
                        <div class="mt-3 p-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs space-y-2">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="font-bold">
                                        Perhatian: Total Permohonan (Rp {{ number_format($totalDiminta, 0, ',', '.') }}) melebihi Sisa Anggaran Tersedia (Rp {{ number_format($budgetInfo['sisa_tersedia'] ?? 0, 0, ',', '.') }}).
                                    </p>
                                    <p class="text-[11px] text-amber-800 mt-0.5">
                                        Pengajuan ini berstatus Over Budget. Anda wajib memberikan alasan/catatan over budget yang jelas untuk pertimbangan approver dan Keuangan.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="catatan_over" class="block font-semibold text-amber-900 mb-1">
                                    Catatan Alasan Over Budget *
                                </label>
                                <textarea
                                    id="catatan_over"
                                    wire:model="catatan_over"
                                    rows="2"
                                    placeholder="Jelaskan justifikasi kebutuhan mendesak dan alasan melampaui sisa plafon anggaran..."
                                    class="w-full rounded-xl border border-amber-300 text-xs text-slate-800 p-2.5 bg-white focus:border-amber-600 focus:outline-hidden @error('catatan_over') border-red-500 @enderror"
                                ></textarea>
                                @error('catatan_over')
                                    <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- SECTION 2: RINCIAN PENGAJUAN (DYNAMIC MULTI-ROW) --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-2 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold text-[#0F2F57] uppercase tracking-wider">
                        Rincian Item Pengajuan
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tambahkan item pengeluaran dengan perincian uraian, asnaf (khusus Zakat), dan jumlah nominal.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="addItem"
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold transition-colors cursor-pointer self-start sm:self-auto"
                >
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ Tambah Baris</span>
                </button>
            </div>

            @error('items')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror

            {{-- Table View for Desktop & Mobile Cards --}}
            <div class="space-y-3">
                @foreach ($items as $index => $row)
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600">
                                Baris #{{ $index + 1 }}
                            </span>
                            @if (count($items) > 1)
                                <button
                                    type="button"
                                    wire:click="removeItem({{ $index }})"
                                    class="min-h-[36px] px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                    title="Hapus baris ini"
                                >
                                    Hapus
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            {{-- Uraian --}}
                            <div class="{{ $needsAsnaf ? 'sm:col-span-6' : 'sm:col-span-8' }}">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                    Uraian / Keterangan Kebutuhan *
                                </label>
                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.uraian"
                                    placeholder="Contoh: Bantuan Biaya Hidup 10 KK, Honor Konsumsi Panitia, dll."
                                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('items.' . $index . '.uraian') border-red-500 @enderror"
                                />
                                @error('items.' . $index . '.uraian')
                                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Asnaf (Jika Sumber Dana Zakat) --}}
                            @if ($needsAsnaf)
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                        Asnaf Zakat *
                                    </label>
                                    <select
                                        wire:model="items.{{ $index }}.asnaf_id"
                                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('items.' . $index . '.asnaf_id') border-red-500 @enderror"
                                    >
                                        <option value="">Pilih Asnaf</option>
                                        @foreach ($asnafList as $asnaf)
                                            <option value="{{ $asnaf->id }}">{{ $asnaf->nama }}</option>
                                        @endforeach
                                    </select>
                                    @error('items.' . $index . '.asnaf_id')
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Jumlah Diminta --}}
                            <div class="{{ $needsAsnaf ? 'sm:col-span-3' : 'sm:col-span-4' }}">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                    Jumlah Permohonan (Rp) *
                                </label>
                                <div
                                    x-data="{
                                        display: '',
                                        init() {
                                            let val = $wire.get('items.{{ $index }}.jumlah_diminta');
                                            if (val || val === 0) this.display = new Intl.NumberFormat('id-ID').format(val.toString().replace(/\D/g, ''));
                                            $watch('$wire.items.{{ $index }}.jumlah_diminta', (v) => {
                                                if (!v && v !== 0) { this.display = ''; return; }
                                                this.display = new Intl.NumberFormat('id-ID').format(v.toString().replace(/\D/g, ''));
                                            });
                                        },
                                        onInput(e) {
                                            let clean = e.target.value.replace(/\D/g, '');
                                            this.display = clean ? new Intl.NumberFormat('id-ID').format(clean) : '';
                                            e.target.value = this.display;
                                            $wire.set('items.{{ $index }}.jumlah_diminta', clean ? parseInt(clean, 10) : null);
                                        }
                                    }"
                                >
                                    <input
                                        type="text"
                                        x-model="display"
                                        @input="onInput($event)"
                                        inputmode="numeric"
                                        placeholder="0"
                                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-bold text-slate-900 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden tabular-nums @error('items.' . $index . '.jumlah_diminta') border-red-500 @enderror"
                                    />
                                </div>
                                @error('items.' . $index . '.jumlah_diminta')
                                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Summary & Leveling Bar --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/70 p-4 rounded-2xl">
                <div>
                    <span class="text-xs text-slate-500 block">Jenjang Persetujuan yang Dibutuhkan:</span>
                    <span class="inline-flex items-center gap-1.5 font-bold text-slate-900 text-sm mt-0.5">
                        <svg class="w-4 h-4 text-[#1E60D5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ $requiredApprover?->nama ?? 'Supervisor' }} (Leveling {{ $requiredApprover?->urutan ?? 1 }})</span>
                    </span>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Dihitung otomatis berdasarkan batasan nominal total permohonan.
                    </p>
                </div>

                <div class="text-left sm:text-right">
                    <span class="text-xs text-slate-500 block">Total Permohonan Dana:</span>
                    <span class="text-xl sm:text-2xl font-bold text-[#0F2F57] tabular-nums">
                        Rp {{ number_format($totalDiminta, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Action Buttons Bottom --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a
                href="{{ route('permohonan.index') }}"
                wire:navigate
                class="min-h-[44px] px-5 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center justify-center"
            >
                Batal
            </a>
            <button
                type="button"
                wire:click="saveDraft"
                class="min-h-[44px] px-5 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
            >
                Simpan Draft
            </button>
            <button
                type="button"
                wire:click="submitNow"
                class="min-h-[44px] px-6 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
            >
                Ajukan Sekarang
            </button>
        </div>
    </div>
</div>
