@php
    $user = auth()->user();
    $verified = $document->verifikasi_keuangan_at !== null;
    $note = $document->verifikasi_keuangan_catatan;
    $latestRevision = $document->approvals->where('keputusan', 'perlu_perbaikan')->sortByDesc('id')->first();
    $returnedByVerification = $document->status === 'perlu_perbaikan'
        && ! $verified
        && $note !== null
        && $latestRevision?->tahap !== 'revisi';
    $isWaitingVerification = $document->status === 'ttd_manager_keuangan' && ! $verified;
    $canOpenVerification = $user && $user->can('verifikasi-keuangan');
@endphp

@if ($isWaitingVerification || $verified || $returnedByVerification)
    <section aria-labelledby="kelengkapan-judul" class="rounded-2xl border p-4 sm:p-5 space-y-3 {{ $verified ? 'border-emerald-200 bg-[#D0F7E3]/35' : ($returnedByVerification ? 'border-amber-300 bg-[#FEF3C7]/60' : 'border-[#CBD5E1] bg-[#E9EFF6]/60') }}">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="space-y-1">
                <h2 id="kelengkapan-judul" class="text-xs font-bold uppercase tracking-wider text-[#0F2F57]">
                    Verifikasi Keuangan
                </h2>
                @if ($verified)
                    <p class="text-sm font-semibold text-[#006C4C]">
                        Terverifikasi{{ $document->verifikator?->name ? ' oleh '.$document->verifikator->name : '' }}
                        @if ($document->verifikasi_keuangan_at)
                            <span class="font-normal text-xs text-[#424750] tabular-nums">
                                • {{ $document->verifikasi_keuangan_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </p>
                @elseif ($returnedByVerification)
                    <p class="text-sm font-semibold text-[#92400E]">
                        Dikembalikan Verifikasi Keuangan untuk Perbaikan{{ $document->verifikator?->name ? ' ('.$document->verifikator->name.')' : '' }}
                    </p>
                @else
                    <p class="text-sm font-semibold text-[#0F2F57]">
                        Menunggu Verifikasi Keuangan
                    </p>
                    <p class="text-xs text-[#424750]">
                        Kelengkapan rincian dan bukti transaksi harus lolos Verifikasi Keuangan sebelum persetujuan Manager Keuangan.
                    </p>
                @endif
            </div>

            @if ($isWaitingVerification && $canOpenVerification)
                <a href="{{ route('keuangan.verifikasi') }}" wire:navigate
                    class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-full bg-[#0F2F57] px-5 text-xs font-semibold text-white hover:bg-[#1E60D5] transition-colors">
                    Buka Verifikasi Keuangan
                </a>
            @endif
        </div>

        @if ($note !== null && ($verified || $returnedByVerification))
            <p class="whitespace-pre-wrap break-words rounded-xl border border-slate-200 bg-white/90 px-3.5 py-2.5 text-xs text-[#171C22]">{{ $note }}</p>
        @endif
    </section>
@endif
