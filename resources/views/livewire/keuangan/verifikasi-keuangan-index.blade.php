<div class="space-y-5">
    <header>
        <h1 class="text-xl font-bold tracking-tight text-[#0F2F57] sm:text-2xl">Verifikasi Keuangan</h1>
        <p class="mt-1 max-w-prose text-sm text-slate-600">Periksa kelengkapan Permohonan, PPD, dan Laporan. Verifikasi ini tidak menahan persetujuan atau pencairan.</p>
    </header>

    @if (session()->has('success'))
        <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
            {{ session('success') }}
        </p>
    @endif

    <section aria-label="Antrean verifikasi" class="overflow-hidden rounded-2xl border border-slate-300 bg-white">
        <div class="grid gap-3 border-b border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr]">
            <label class="grid gap-1 text-xs font-semibold text-slate-700 sm:col-span-2 xl:col-span-1">
                Cari dokumen
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nomor, unit, kegiatan, atau pos anggaran"
                    class="min-h-12 min-w-0 rounded-xl border border-slate-500 bg-white px-3 text-sm font-normal text-slate-900">
            </label>
            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                Jenis dokumen
                <select wire:model.live="filterJenis" class="min-h-12 rounded-xl border border-slate-500 bg-white px-3 text-sm font-normal text-slate-900">
                    <option value="semua">Semua jenis</option>
                    <option value="permohonan">Permohonan</option>
                    <option value="pencairan">Pencairan PPD</option>
                    <option value="ump">Laporan</option>
                </select>
            </label>
            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                Status kelengkapan
                <select wire:model.live="filterStatus" class="min-h-12 rounded-xl border border-slate-500 bg-white px-3 text-sm font-normal text-slate-900">
                    <option value="belum">Menunggu pemeriksaan</option>
                    <option value="perbaikan">Perlu perbaikan</option>
                    <option value="terverifikasi">Terverifikasi</option>
                    <option value="semua">Semua status</option>
                </select>
            </label>
        </div>

        <p wire:loading role="status" class="border-b border-slate-200 px-4 py-2 text-sm text-slate-700">Memperbarui antrean…</p>
        @error('catatan')
            <p id="kesalahan-catatan" role="alert" class="border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
        @enderror

        <ul class="divide-y divide-slate-200">
            @forelse ($items as $item)
                @php($key = $item['type'].'-'.$item['id'])
                <li wire:key="verifikasi-{{ $key }}" class="space-y-3 p-4 sm:px-5">
                    <div class="grid gap-2 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] md:items-center md:gap-5">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-slate-600">{{ $item['label'] }}</div>
                            <a href="{{ $item['url'] }}" wire:navigate
                                class="inline-flex min-h-11 items-center break-all text-sm font-bold text-[#0F2F57] underline underline-offset-4">
                                {{ $item['number'] }}
                            </a>
                            <p class="break-words text-sm text-slate-700">{{ $item['activity'] }}</p>
                            <p class="break-words text-xs text-slate-600">
                                {{ implode(' · ', array_filter([$item['unit'], $item['budget'], $item['date']?->format('d/m/Y')])) }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold tabular-nums text-slate-900">Rp {{ number_format($item['amount'], 0, ',', '.') }}</p>
                            @if ($item['verified'])
                                <p class="text-xs font-semibold text-emerald-800">Terverifikasi{{ $item['reviewer'] ? ' · '.$item['reviewer'] : '' }}</p>
                            @elseif ($item['returned'])
                                <p class="text-xs font-semibold text-amber-900">Perlu perbaikan</p>
                            @else
                                <p class="text-xs font-semibold text-slate-700">Menunggu pemeriksaan</p>
                            @endif
                        </div>

                        @if ($item['canReview'])
                            @if ($item['verified'])
                                <button type="button" wire:click="batalVerifikasi('{{ $item['type'] }}', {{ $item['id'] }})"
                                    wire:confirm="Batalkan verifikasi dan kembalikan dokumen ke antrean?"
                                    wire:loading.attr="disabled"
                                    class="min-h-11 justify-self-start rounded-full border border-slate-500 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                    Batalkan verifikasi
                                </button>
                            @else
                                <button type="button" wire:click="pilih('{{ $item['type'] }}', {{ $item['id'] }})"
                                    aria-expanded="{{ $dokumenDipilih === $key ? 'true' : 'false' }}"
                                    wire:loading.attr="disabled"
                                    class="min-h-11 justify-self-start rounded-full bg-[#0F2F57] px-5 text-sm font-semibold text-white hover:bg-[#1d426d]">
                                    Periksa kelengkapan
                                </button>
                            @endif
                        @elseif (! $item['verified'] && ! $item['returned'])
                            <p class="max-w-[15rem] text-xs text-slate-600">
                                {{ $item['unattributedPpd'] ? 'Penerbit PPD tidak tercatat.' : 'Dokumen ini perlu diperiksa oleh petugas lain.' }}
                            </p>
                        @endif
                    </div>

                    @if ($item['note'] !== null)
                        <p class="whitespace-pre-wrap break-words rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-800">{{ $item['note'] }}</p>
                    @endif

                    @if ($item['canReview'] && ! $item['verified'] && $dokumenDipilih === $key)
                        <form wire:submit="verifikasi('{{ $item['type'] }}', {{ $item['id'] }})"
                            class="space-y-3 rounded-xl border border-slate-300 bg-slate-50 p-3 sm:p-4">
                            <label for="catatan-verifikasi" class="block text-sm font-semibold text-slate-800">Catatan pemeriksaan</label>
                            <p id="petunjuk-catatan" class="text-xs text-slate-600">Catatan opsional untuk verifikasi, wajib jika dokumen dikembalikan.</p>
                            <textarea id="catatan-verifikasi" wire:model="catatan" aria-describedby="petunjuk-catatan{{ $errors->has('catatan') ? ' kesalahan-catatan' : '' }}"
                                aria-invalid="{{ $errors->has('catatan') ? 'true' : 'false' }}"
                                rows="3" maxlength="1000"
                                class="w-full rounded-xl border border-slate-500 bg-white px-3 py-2 text-sm text-slate-900"></textarea>

                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="min-h-11 rounded-full bg-[#0F2F57] px-5 text-sm font-semibold text-white hover:bg-[#1d426d]">
                                    Verifikasi dokumen
                                </button>
                                <button type="button" wire:click="kembalikan('{{ $item['type'] }}', {{ $item['id'] }})" wire:loading.attr="disabled"
                                    class="min-h-11 rounded-full border border-amber-800 px-4 text-sm font-semibold text-amber-900 hover:bg-amber-50">
                                    Kembalikan untuk perbaikan
                                </button>
                                <button type="button" wire:click="tutupCatatan" wire:loading.attr="disabled"
                                    class="min-h-11 rounded-full px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                                    Tutup
                                </button>
                            </div>
                        </form>
                    @endif
                </li>
            @empty
                <li class="px-5 py-9 text-center text-sm text-slate-600">
                    {{ $search !== '' || $filterJenis !== 'semua' || $filterStatus !== 'belum'
                        ? 'Tidak ada dokumen yang cocok. Ubah pencarian atau filter.'
                        : 'Tidak ada dokumen yang menunggu pemeriksaan kelengkapan.' }}
                </li>
            @endforelse
        </ul>
    </section>
</div>
