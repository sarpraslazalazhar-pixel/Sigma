<div class="space-y-5">
    <header class="space-y-1">
        <h1 class="text-xl font-bold tracking-tight text-[#0F2F57] sm:text-2xl">Verifikasi Keuangan</h1>
        <p class="max-w-prose text-sm text-[#424750]">
            Periksa kelengkapan rincian dan bukti transaksi Laporan (UMP) sebagai gerbang wajib sebelum persetujuan Manager Keuangan dan Kepala Divisi Keuangan.
        </p>
    </header>

    @if (session()->has('success'))
        <p role="status" class="rounded-2xl border border-emerald-200 bg-[#D0F7E3]/60 px-4 py-3 text-sm font-medium text-[#002114]">
            {{ session('success') }}
        </p>
    @endif

    <section aria-label="Antrean verifikasi Laporan" class="overflow-hidden rounded-2xl border border-[#CBD5E1] bg-white">
        @php
            $statusTabs = [
                'belum' => 'Menunggu Verifikasi',
                'menunggu_persetujuan' => 'Menunggu Persetujuan Mgr / Kadiv Keu',
                'perbaikan' => 'Perlu Perbaikan',
                'terverifikasi' => 'Terverifikasi / Selesai',
                'semua' => 'Semua',
            ];
        @endphp

        <div class="space-y-4 border-b border-[#CBD5E1] bg-[#F1F5F9] p-4 sm:p-5">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter status verifikasi Laporan">
                @foreach ($statusTabs as $tabKey => $tabLabel)
                    @php
                        $isSelected = $filterStatus === $tabKey;
                    @endphp
                    <button
                        type="button"
                        wire:click="setFilterStatus('{{ $tabKey }}')"
                        role="tab"
                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer {{ $isSelected ? 'border-[#0F2F57] bg-[#DCE5F6] text-[#001B3E]' : 'border-[#CBD5E1] bg-white text-[#424750] hover:bg-[#E9EFF6]' }}"
                    >
                        <span>{{ $tabLabel }}</span>
                        <span class="inline-flex min-w-[22px] items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums {{ $isSelected ? 'bg-[#0F2F57] text-white' : 'bg-[#E9EFF6] text-[#0F2F57]' }}">
                            {{ $counts[$tabKey] ?? 0 }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <label class="grid gap-1 text-xs font-medium text-[#424750]">
                    Cari Laporan
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="No. PPD, kegiatan, unit, pos anggaran, atau PJ..."
                        class="min-h-[48px] min-w-0 rounded-xl border border-[#727782] bg-white px-3.5 text-sm font-normal text-[#171C22] focus:border-2 focus:border-[#0F2F57] focus:outline-hidden"
                    >
                </label>
                <label class="grid gap-1 text-xs font-medium text-[#424750]">
                    Status antrean
                    <select
                        wire:model.live="filterStatus"
                        class="min-h-[48px] rounded-xl border border-[#727782] bg-white px-3.5 text-sm font-normal text-[#171C22] focus:border-2 focus:border-[#0F2F57] focus:outline-hidden"
                    >
                        @foreach ($statusTabs as $tabKey => $tabLabel)
                            <option value="{{ $tabKey }}">{{ $tabLabel }} ({{ $counts[$tabKey] ?? 0 }})</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        <p wire:loading role="status" class="border-b border-slate-200 px-4 py-2 text-sm text-[#424750]">Memperbarui antrean…</p>
        @error('catatan')
            <p id="kesalahan-catatan" role="alert" class="border-b border-red-200 bg-[#FFDAD6] px-4 py-3 text-sm font-medium text-[#410002]">{{ $message }}</p>
        @enderror
        @error('approvalNotes')
            <p id="kesalahan-persetujuan" role="alert" class="border-b border-red-200 bg-[#FFDAD6] px-4 py-3 text-sm font-medium text-[#410002]">{{ $message }}</p>
        @enderror

        <ul class="divide-y divide-slate-200">
            @forelse ($items as $item)
                <li wire:key="verifikasi-ump-{{ $item['id'] }}" class="space-y-3.5 p-4 sm:p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $item['url'] }}" wire:navigate
                                    class="inline-flex min-h-[44px] items-center break-all font-mono text-sm font-bold text-[#0F2F57] underline underline-offset-4">
                                    {{ $item['number'] }}
                                </a>
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $item['statusBadge']['class'] }}">
                                    {{ $item['statusBadge']['label'] }}
                                </span>
                                @if ($item['verified'])
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-[#D0F7E3] px-2.5 py-0.5 text-[11px] font-semibold text-[#002114]">
                                        Terverifikasi{{ $item['reviewer'] ? ' · '.$item['reviewer'] : '' }}
                                    </span>
                                @endif
                            </div>

                            <p class="break-words text-sm font-semibold text-[#171C22]">{{ $item['activity'] }}</p>
                            <p class="break-words text-xs text-[#424750]">
                                {{ implode(' · ', array_filter([
                                    $item['unit'],
                                    $item['budget'],
                                    'PJ: '.$item['pjName'],
                                    $item['date']?->format('d/m/Y'),
                                ])) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a href="{{ $item['url'] }}" wire:navigate
                                class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-[#727782] bg-white px-4 text-xs font-semibold text-[#0F2F57] hover:bg-[#F1F5F9] transition-colors">
                                Lihat Detail Laporan
                            </a>

                            @if ($item['canReview'])
                                <button type="button" wire:click="pilih({{ $item['id'] }})"
                                    aria-expanded="{{ $dokumenDipilih === $item['id'] ? 'true' : 'false' }}"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-[#0F2F57] px-5 text-xs font-semibold text-white hover:bg-[#1E60D5] transition-colors cursor-pointer">
                                    Periksa Kelengkapan
                                </button>
                            @elseif ($item['isResponsible'] && ! $item['verified'] && ! $item['returned'])
                                <p class="max-w-[16rem] text-xs text-[#424750]">
                                    Anda adalah PJ Laporan ini (tidak dapat memverifikasi sendiri).
                                </p>
                            @endif

                            @if ($item['canUnverify'])
                                <button type="button" wire:click="batalVerifikasi({{ $item['id'] }})"
                                    wire:confirm="Batalkan verifikasi dan kembalikan Laporan ke antrean?"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-[#727782] px-4 text-xs font-semibold text-[#424750] hover:bg-[#F1F5F9] transition-colors cursor-pointer">
                                    Batalkan Verifikasi
                                </button>
                            @endif

                            @if ($item['canApprove'])
                                <button type="button" wire:click="bukaPersetujuan({{ $item['id'] }}, 'setuju')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-[#006C4C] px-4 text-xs font-semibold text-white hover:bg-emerald-800 transition-colors cursor-pointer">
                                    Setujui
                                </button>
                                <button type="button" wire:click="bukaPersetujuan({{ $item['id'] }}, 'perbaikan')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-amber-600 px-4 text-xs font-semibold text-white hover:bg-amber-700 transition-colors cursor-pointer">
                                    Minta Perbaikan
                                </button>
                                <button type="button" wire:click="bukaPersetujuan({{ $item['id'] }}, 'batal')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-[#BA1A1A] px-4 text-xs font-semibold text-[#BA1A1A] hover:bg-[#FFDAD6]/40 transition-colors cursor-pointer">
                                    Void
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 rounded-xl bg-[#F1F5F9] p-3 sm:grid-cols-4 text-xs">
                        <div>
                            <span class="block text-[11px] text-[#424750]">Dana Diterima</span>
                            <span class="font-bold tabular-nums text-[#0F2F57]">
                                Rp {{ number_format($item['amount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-[#424750]">Total Penggunaan</span>
                            <span class="font-bold tabular-nums text-[#171C22]">
                                Rp {{ number_format($item['usedAmount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-[#424750]">Saldo / Defisit</span>
                            @if ($item['kekurangan'] > 0 || $item['saldo'] < 0)
                                <span class="font-bold tabular-nums text-[#BA1A1A]">
                                    Defisit Rp {{ number_format(abs($item['saldo']), 0, ',', '.') }}
                                </span>
                            @else
                                <span class="font-bold tabular-nums text-[#006C4C]">
                                    Rp {{ number_format($item['saldo'], 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <span class="block text-[11px] text-[#424750]">Rincian & Bukti Transaksi</span>
                            <span class="font-semibold tabular-nums text-[#171C22]">
                                {{ $item['itemsCount'] }} baris · {{ $item['buktiCount'] }} bukti transaksi
                            </span>
                        </div>
                    </div>

                    @if ($item['note'] !== null)
                        <div class="rounded-xl border border-slate-200 bg-[#E9EFF6]/60 px-3.5 py-2.5 text-xs text-[#171C22] space-y-0.5">
                            <span class="block text-[11px] font-semibold text-[#424750]">
                                Catatan Verifikasi{{ $item['reviewer'] ? ' ('.$item['reviewer'].')' : '' }}
                            </span>
                            <p class="whitespace-pre-wrap break-words">{{ $item['note'] }}</p>
                        </div>
                    @endif

                    @if ($item['canReview'] && $dokumenDipilih === $item['id'])
                        <form wire:submit="verifikasi({{ $item['id'] }})"
                            class="space-y-3 rounded-2xl border border-[#CBD5E1] bg-[#F1F5F9] p-4">
                            <label for="catatan-verifikasi-{{ $item['id'] }}" class="block text-sm font-semibold text-[#0F2F57]">
                                Periksa Kelengkapan Laporan
                            </label>
                            <p id="petunjuk-catatan-{{ $item['id'] }}" class="text-xs text-[#424750]">
                                Catatan opsional untuk verifikasi; wajib diisi minimal 5 karakter jika dikembalikan ke PJ untuk perbaikan.
                            </p>
                            <textarea
                                id="catatan-verifikasi-{{ $item['id'] }}"
                                wire:model="catatan"
                                aria-describedby="petunjuk-catatan-{{ $item['id'] }}{{ $errors->has('catatan') ? ' kesalahan-catatan' : '' }}"
                                aria-invalid="{{ $errors->has('catatan') ? 'true' : 'false' }}"
                                rows="3"
                                maxlength="1000"
                                placeholder="Tuliskan catatan pemeriksaan atau alasan perbaikan..."
                                class="w-full rounded-xl border border-[#727782] bg-white px-3.5 py-2.5 text-sm text-[#171C22] focus:border-2 focus:border-[#0F2F57] focus:outline-hidden"
                            ></textarea>

                            <div class="flex flex-wrap gap-2">
                                <button type="submit" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-[#0F2F57] px-5 text-xs font-semibold text-white hover:bg-[#1E60D5] transition-colors cursor-pointer">
                                    Verifikasi & Minta Persetujuan
                                </button>
                                <button type="button" wire:click="kembalikan({{ $item['id'] }})" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-amber-700 bg-[#FEF3C7] px-4 text-xs font-semibold text-[#92400E] hover:bg-amber-200/70 transition-colors cursor-pointer">
                                    Kembalikan untuk Perbaikan
                                </button>
                                <button type="button" wire:click="tutupCatatan" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full px-4 text-xs font-semibold text-[#424750] hover:bg-slate-200 transition-colors cursor-pointer">
                                    Tutup
                                </button>
                            </div>
                        </form>
                    @endif

                    @if ($item['canApprove'] && $approvalTargetId === $item['id'])
                        <form wire:submit="prosesPersetujuan({{ $item['id'] }})"
                            class="space-y-3 rounded-2xl border-2 border-[#DCE5F6] bg-white p-4">
                            <h3 class="text-sm font-bold text-[#0F2F57]">
                                @if ($approvalAction === 'setuju')
                                    Konfirmasi Persetujuan Laporan
                                @elseif ($approvalAction === 'perbaikan')
                                    Minta Perbaikan Laporan
                                @else
                                    Konfirmasi Void Laporan
                                @endif
                            </h3>
                            <p class="text-xs text-[#424750]">
                                @if ($approvalAction === 'setuju')
                                    Persetujuan dicatat dengan nama dan jabatan Anda pada tahap berjalan.
                                @elseif ($approvalAction === 'perbaikan')
                                    Laporan dikembalikan ke Penanggung Jawab untuk perbaikan dan harus melalui Verifikasi Keuangan ulang.
                                @else
                                    Laporan yang di-void tidak dapat diproses lebih lanjut.
                                @endif
                            </p>

                            <div>
                                <label for="approval-notes-{{ $item['id'] }}" class="block text-xs font-semibold text-[#424750] mb-1">
                                    Catatan {{ $approvalAction === 'setuju' ? '(Opsional)' : '(Wajib, minimal 5 karakter)' }}
                                </label>
                                <textarea
                                    id="approval-notes-{{ $item['id'] }}"
                                    wire:model="approvalNotes"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Tuliskan catatan di sini..."
                                    class="w-full rounded-xl border border-[#727782] bg-white px-3.5 py-2.5 text-sm text-[#171C22] focus:border-2 focus:border-[#0F2F57] focus:outline-hidden"
                                ></textarea>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="submit" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full px-5 text-xs font-semibold text-white transition-colors cursor-pointer {{ $approvalAction === 'setuju' ? 'bg-[#006C4C] hover:bg-emerald-800' : ($approvalAction === 'perbaikan' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-[#BA1A1A] hover:bg-red-800') }}">
                                    Konfirmasi
                                </button>
                                <button type="button" wire:click="tutupPersetujuan" wire:loading.attr="disabled"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-[#CBD5E1] px-4 text-xs font-semibold text-[#424750] hover:bg-[#F1F5F9] transition-colors cursor-pointer">
                                    Tutup
                                </button>
                            </div>
                        </form>
                    @endif
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-[#424750]">
                    {{ $search !== '' || $filterStatus !== 'belum'
                        ? 'Tidak ada Laporan yang cocok dengan filter atau pencarian.'
                        : 'Tidak ada Laporan yang menunggu Verifikasi Keuangan.' }}
                </li>
            @endforelse
        </ul>
    </section>
</div>
