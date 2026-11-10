<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('permohonan.detail', $permohonanId) }}"
                    wire:navigate
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    title="Kembali ke rincian permohonan"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                    Edit Permohonan Dana
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 pl-7">
                Perbarui data kegiatan, pos anggaran, atau rincian item permohonan dana.
            </p>
        </div>
        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <button
                type="button"
                wire:click="saveDraft"
                class="min-h-[44px] px-4 py-2.5 rounded-full border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
            >
                Simpan Perubahan Draft
            </button>
            <button
                type="button"
                wire:click="submitNow"
                class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
            >
                Simpan & Ajukan Sekarang
            </button>
        </div>
    </div>

    {{-- Form Utama --}}
    <div class="space-y-6">

        {{-- KHUSUS PETUGAS TU / ADMIN / KEUANGAN: Pilih pejabat pemohon --}}
        @if ($isTu)
            <div class="bg-blue-50/60 border border-blue-200 rounded-2xl p-4 sm:p-5 space-y-3.5 shadow-2xs">
                <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-blue-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#1E60D5] text-white flex items-center justify-center text-xs font-bold shrink-0">
                            TU
                        </span>
                        <div>
                            <h2 class="text-xs font-bold text-[#0F2F57] uppercase tracking-wider">
                                Pengajuan oleh Petugas TU
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Pilih pejabat yang mengajukan / meminta permohonan dana (Supervisor, Manajer, atau Kepala Divisi)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="creatorLevel" class="block text-xs font-semibold text-slate-700 mb-1">
                            Filter Jabatan Pemohon
                        </label>
                        <select
                            id="creatorLevel"
                            wire:model.live="creatorLevel"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                        >
                            <option value="">Semua</option>
                            <option value="1">Supervisor</option>
                            <option value="2">Manajer</option>
                            <option value="3">Kadiv</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="user_id" class="block text-xs font-semibold text-slate-700 mb-1">
                            Pejabat Pemohon (Dibuat Oleh) *
                        </label>
                        <x-searchable-select
                            wire:model.live="user_id"
                            :options="$creatorCandidates"
                            placeholder="Pilih Pejabat Pemohon..."
                            search-placeholder="Cari nama pejabat atau jabatan..."
                            :nullable="false"
                        />
                        @error('user_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @endif

        @if (!$isTu && $this->atasNamaUser && $this->atasNamaUser->id !== auth()->id())
            <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 flex items-center gap-3 text-xs text-amber-900 shadow-2xs">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    Pengajuan ini dibuat atas nama atasan: <strong>{{ $this->atasNamaUser->name }}</strong> ({{ $this->atasNamaUser->jabatan ?? $this->atasNamaUser->leveling?->nama ?? 'Supervisor Unit' }}).
                </span>
            </div>
        @endif

        {{-- SECTION 1: HEADER INFORMASI KEGIATAN --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <h2 class="text-sm font-bold text-[#0F2F57] uppercase tracking-wider pb-2 border-b border-slate-100">
                Informasi Pengajuan & Kegiatan
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Program (Nama Mata Anggaran) --}}
                <div>
                    <label for="program" class="block text-xs font-semibold text-slate-700 mb-1">
                        Program (Nama Mata Anggaran) *
                    </label>
                    <input
                        type="text"
                        id="program"
                        wire:model="program"
                        readonly
                        placeholder="Otomatis dari nama kode anggaran yang dipilih"
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3.5 py-2.5 bg-slate-50 cursor-default focus:border-[#1E60D5] focus:outline-hidden @error('program') border-red-500 @enderror"
                        required
                    />
                    @error('program')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unit / Divisi --}}
                <div>
                    <label for="unit_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Unit / Divisi Pengaju *
                    </label>
                    @if ($this->hasAutoUnit)
                        <div class="relative">
                            <input
                                type="text"
                                id="unit_id_display"
                                readonly
                                value="{{ $this->unitDisplayText }}"
                                title="{{ $this->unitDisplayText }}"
                                class="w-full min-h-[44px] truncate rounded-xl border border-slate-300 text-xs font-medium text-slate-900 px-3.5 py-2.5 pr-20 bg-slate-50 cursor-default focus:border-[#1E60D5] focus:outline-hidden @error('unit_id') border-red-500 @enderror"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Otomatis
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Otomatis dari unit/divisi {{ $this->atasNamaUser->name }} ({{ $this->atasNamaUser->jabatan ?? $this->atasNamaUser->leveling?->nama ?? 'Pemohon' }}).
                        </p>
                    @elseif ($isTu && ! $this->user_id)
                        <div class="relative">
                            <input
                                type="text"
                                id="unit_id_display"
                                readonly
                                disabled
                                placeholder="Otomatis terisi setelah memilih Pejabat Pemohon di atas..."
                                class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-500 px-3.5 py-2.5 bg-slate-100 cursor-not-allowed focus:outline-hidden"
                            />
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Pilih Pejabat Pemohon di atas untuk menentukan unit/divisi secara otomatis.
                        </p>
                    @else
                        <x-searchable-select
                            wire:model.live="unit_id"
                            :options="$units"
                            placeholder="Pilih Unit / Divisi Pengaju..."
                            search-placeholder="Cari nama atau kode unit pengaju..."
                            :nullable="false"
                        />
                        @if ($this->atasNamaUser && ! $this->atasNamaUser->unit_id)
                            <p class="text-[11px] text-amber-600 mt-1">
                                Pejabat pemohon belum memiliki unit/divisi di sistem, silakan pilih unit secara manual.
                            </p>
                        @endif
                    @endif
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
                                const sel = this.options.find(o => o.id == @js($kode_anggaran_id));
                                return sel ? sel.kode + ' — ' + sel.nama : 'Pilih Pos Anggaran Aktif...';
                            },
                            select(id) {
                                $wire.set('kode_anggaran_id', id);
                                this.open = false;
                                this.search = '';
                            }
                        }"
                        @click.outside="open = false"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs px-3.5 py-2.5 text-left flex items-center justify-between gap-2 bg-white hover:bg-slate-50 transition-colors focus:border-[#1E60D5] focus:outline-hidden @error('kode_anggaran_id') border-red-500 @enderror cursor-pointer shadow-2xs"
                        >
                            <span class="truncate font-medium text-slate-800" x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition
                            class="absolute z-50 mt-1.5 w-full bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden"
                            style="display: none;"
                        >
                            <div class="p-2 border-b border-slate-100 bg-slate-50">
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="Ketik kode atau nama anggaran..."
                                    class="w-full text-xs rounded-lg border border-slate-200 bg-white px-3 py-1.5 focus:border-[#1E60D5] focus:outline-hidden"
                                    @keydown.escape="open = false"
                                />
                            </div>
                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 p-1">
                                <template x-for="item in filteredOptions" :key="item.id">
                                    <button
                                        type="button"
                                        @click="select(item.id)"
                                        class="w-full text-left px-3 py-2 text-xs rounded-lg hover:bg-blue-50/70 transition-colors flex items-center justify-between gap-2 group cursor-pointer"
                                        :class="item.id == @js($kode_anggaran_id) ? 'bg-blue-50 text-[#1E60D5] font-semibold' : 'text-slate-700'"
                                    >
                                        <div class="min-w-0">
                                            <span class="font-mono text-[11px] font-bold text-slate-500 group-hover:text-[#1E60D5]" x-text="item.kode"></span>
                                            <span class="block truncate" x-text="item.nama"></span>
                                        </div>
                                        <svg x-show="item.id == @js($kode_anggaran_id)" class="w-4 h-4 text-[#1E60D5] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="filteredOptions.length === 0" class="py-4 text-center text-xs text-slate-400">
                                    Tidak ada pos anggaran yang cocok
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('kode_anggaran_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Live Budget Monitor Card --}}
            @if ($budgetInfo)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-xl {{ $isOverBudget ? 'bg-red-50/70 border border-red-200' : 'bg-slate-50 border border-slate-100' }}">
                        <div>
                            <span class="text-[11px] text-slate-500 block">Plafon Disahkan</span>
                            <span class="text-xs font-bold text-slate-800">
                                Rp {{ number_format($budgetInfo['plafon'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-500 block">Dana Terikat (Disetujui)</span>
                            <span class="text-xs font-bold text-blue-700">
                                Rp {{ number_format($budgetInfo['dana_terikat'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-500 block">Realisasi (Cair)</span>
                            <span class="text-xs font-bold text-emerald-700">
                                Rp {{ number_format($budgetInfo['realisasi'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-500 block">Sisa Plafon Anggaran</span>
                            <span class="text-xs font-bold {{ $budgetInfo['sisa_tersedia'] <= 0 ? 'text-red-600' : 'text-slate-900' }}">
                                Rp {{ number_format($budgetInfo['sisa_tersedia'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Warning Over Budget --}}
                    @if ($isOverBudget)
                        <div class="mt-3 p-3.5 rounded-xl bg-red-100/60 border border-red-300 text-xs text-red-900 space-y-2">
                            <div class="flex items-center gap-2 font-semibold">
                                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Perhatian: Total Pengajuan Melebihi Sisa Plafon Anggaran Tersedia!</span>
                            </div>
                            <p class="text-[11px] text-red-800">
                                Sisa anggaran untuk pos ini adalah <strong>Rp {{ number_format($budgetInfo['sisa_tersedia'], 0, ',', '.') }}</strong>, namun total yang Anda minta mencapai <strong>Rp {{ number_format($totalDiminta, 0, ',', '.') }}</strong>. Pengajuan over budget wajib menyertakan justifikasi tertulis dan akan memerlukan persetujuan khusus.
                            </p>
                            <div>
                                <label for="catatan_over" class="block text-[11px] font-semibold text-red-950 mb-1">
                                    Catatan Justifikasi Over Budget * (Minimal 10 karakter)
                                </label>
                                <textarea
                                    id="catatan_over"
                                    wire:model="catatan_over"
                                    rows="2"
                                    placeholder="Jelaskan urgensi atau alasan penambahan anggaran ini..."
                                    class="w-full text-xs rounded-lg border border-red-300 bg-white p-2 text-slate-800 focus:border-red-500 focus:outline-hidden"
                                ></textarea>
                                @error('catatan_over')
                                    <p class="text-[11px] text-red-700 mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- SECTION 2: MULTI-ROW ITEMS RINCIAN --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 flex-wrap gap-2">
                <div>
                    <h2 class="text-sm font-bold text-[#0F2F57] uppercase tracking-wider">
                        Rincian Item Permohonan Dana
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Daftar rincian kebutuhan dana, uraian pengeluaran, asnaf, dan nominal.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="addItem"
                    class="min-h-[44px] px-3.5 py-2 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100/70 text-[#1E60D5] text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Tambah Baris</span>
                </button>
            </div>

            @error('items')
                <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">
                    {{ $message }}
                </div>
            @enderror

            {{-- Table / Cards Container --}}
            <div class="space-y-3">
                @foreach ($items as $index => $item)
                    <div
                        wire:key="item-row-{{ $index }}"
                        class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-3 relative transition-all hover:border-slate-300"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px] font-bold">
                                    {{ $index + 1 }}
                                </span>
                                Baris Item #{{ $index + 1 }}
                            </span>
                            @if (count($items) > 1)
                                <button
                                    type="button"
                                    wire:click="removeItem({{ $index }})"
                                    class="text-xs text-red-500 hover:text-red-700 p-1 rounded-md hover:bg-red-50 transition-colors flex items-center gap-1 cursor-pointer"
                                    title="Hapus baris ini"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            {{-- Uraian --}}
                            <div class="md:col-span-{{ $needsAsnaf ? '5' : '8' }}">
                                <label for="items_{{ $index }}_uraian" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Uraian / Kebutuhan *
                                </label>
                                <textarea
                                    id="items_{{ $index }}_uraian"
                                    wire:model="items.{{ $index }}.uraian"
                                    rows="2"
                                    placeholder="Uraian pengeluaran, spesifikasi barang, atau rincian kegiatan..."
                                    class="w-full text-xs rounded-xl border border-slate-300 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('items.' . $index . '.uraian') border-red-500 @enderror"
                                ></textarea>
                                @error('items.' . $index . '.uraian')
                                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Asnaf (Wajib jika Zakat) --}}
                            @if ($needsAsnaf)
                                <div class="md:col-span-3">
                                    <label for="items_{{ $index }}_asnaf" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                        Asnaf *
                                    </label>
                                    <select
                                        id="items_{{ $index }}_asnaf"
                                        wire:model="items.{{ $index }}.asnaf_id"
                                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden @error('items.' . $index . '.asnaf_id') border-red-500 @enderror"
                                    >
                                        <option value="">Pilih Asnaf</option>
                                        @foreach ($asnafList as $asnaf)
                                            <option value="{{ $asnaf->id }}">
                                                {{ $asnaf->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('items.' . $index . '.asnaf_id')
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Nominal Jumlah Diminta --}}
                            <div class="md:col-span-4">
                                <label for="items_{{ $index }}_jumlah" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Jumlah Diminta (Rp) *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        type="text"
                                        id="items_{{ $index }}_jumlah"
                                        x-data
                                        x-on:input="
                                            let val = $el.value.replace(/\D/g, '');
                                            $el.value = val ? parseInt(val).toLocaleString('id-ID') : '';
                                        "
                                        wire:model.live.debounce.400ms="items.{{ $index }}.jumlah_diminta"
                                        placeholder="0"
                                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs font-bold text-slate-900 pl-9 pr-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden tabular-nums @error('items.' . $index . '.jumlah_diminta') border-red-500 @enderror"
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
            <div class="pt-4 border-t border-slate-100 flex flex-col gap-3 bg-slate-50/70 p-4 rounded-2xl">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <span class="text-xs text-slate-500 block">Tingkat Pengajuan Pemohon:</span>
                        <span class="inline-flex items-center gap-1.5 font-bold text-slate-900 text-sm mt-0.5">
                            <svg class="w-4 h-4 text-[#1E60D5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Tingkat {{ $requiredApprover?->nama ?? 'Supervisor' }} (Leveling {{ $requiredApprover?->urutan ?? 1 }})</span>
                        </span>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Tahap 1 (Pemohon): Supervisor, Manajer, atau Kadiv (dapat diinput oleh TU) &bull; Tahap 2 (Pemeriksa): Kepala Divisi Sekretariat &bull; Tahap 3 (Penyetuju): Bagian Keuangan
                        </p>
                    </div>

                    <div class="text-left sm:text-right">
                        <span class="text-xs text-slate-500 block">Total Permohonan Dana:</span>
                        <span class="text-xl sm:text-2xl font-bold text-[#0F2F57] tabular-nums">
                            Rp {{ number_format($totalDiminta, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if ($this->isOverPemohonLimit)
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-start gap-2 text-xs text-red-800">
                        <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z"/>
                        </svg>
                        <span>
                            Total melebihi batas kewenangan <strong>{{ $this->atasNamaUser?->name }}</strong>
                            sebagai {{ $this->atasNamaUser?->jabatan ?? $this->atasNamaUser?->leveling?->nama ?? 'Pemohon' }}
                            (maks. <strong>Rp {{ number_format($this->pemohonLimit, 0, ',', '.') }}</strong>).
                            Minta TU untuk mengubah Pejabat Pemohon ke tingkat yang lebih tinggi, atau minta Manajer/Kadiv membuat pengajuan langsung.
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action Buttons Bottom --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a
                href="{{ route('permohonan.detail', $permohonanId) }}"
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
                Simpan Perubahan Draft
            </button>
            <button
                type="button"
                wire:click="submitNow"
                class="min-h-[44px] px-6 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
            >
                Simpan & Ajukan Sekarang
            </button>
        </div>
        @error('pemohon_limit')
            <p class="text-xs text-red-600 text-right mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
