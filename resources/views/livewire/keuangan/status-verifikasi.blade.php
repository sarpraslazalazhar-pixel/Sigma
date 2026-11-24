@php
    $verification = app(\App\Services\VerifikasiKeuangan::class);
    $user = auth()->user();
    $responsible = $user && $verification->isResponsible($document, $user);
@endphp

@if ($responsible || $user?->can('verifikasi-keuangan'))
    @php
        $active = $verification->isActive($type, $document->id);
        $verified = $document->verifikasi_keuangan_at !== null;
        $note = $document->verifikasi_keuangan_catatan;
    @endphp

    @if ($active || $verified || $note !== null)
        <section aria-labelledby="kelengkapan-judul" class="space-y-2 rounded-2xl border border-slate-300 bg-white p-4 sm:p-5">
            <h2 id="kelengkapan-judul" class="text-sm font-bold text-[#0F2F57]">Verifikasi kelengkapan</h2>
            <p class="text-sm font-semibold {{ $verified ? 'text-emerald-800' : ($note !== null ? 'text-amber-900' : 'text-slate-700') }}">
                {{ $verified ? 'Terverifikasi' : ($note !== null ? 'Perlu perbaikan kelengkapan' : 'Menunggu pemeriksaan') }}
            </p>
            @if ($note !== null)
                <p class="whitespace-pre-wrap break-words text-sm text-slate-800">{{ $note }}</p>
            @endif

            @if ($responsible && ! $verified && $note !== null && $active)
                <form action="{{ route('keuangan.verifikasi.cek-ulang', ['type' => $type, 'id' => $document->id]) }}"
                    method="POST" enctype="multipart/form-data" class="space-y-2 border-t border-slate-200 pt-3">
                    @csrf
                    <label for="bukti-koreksi" class="block text-sm font-semibold text-slate-800">Bukti perbaikan (opsional)</label>
                    <p id="petunjuk-bukti" class="text-xs text-slate-600">JPG, PNG, atau PDF. Maksimal 5 MB.</p>
                    <input type="file" id="bukti-koreksi" name="bukti_koreksi" accept=".jpg,.jpeg,.png,.pdf" aria-describedby="petunjuk-bukti"
                        class="block w-full max-w-md rounded-xl border border-slate-500 bg-white text-sm text-slate-700 file:mr-2 file:min-h-11 file:border-0 file:bg-slate-100 file:px-3 file:font-semibold file:text-slate-900">
                    @error('bukti_koreksi')
                        <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="min-h-11 rounded-full bg-[#0F2F57] px-5 text-sm font-semibold text-white hover:bg-[#1d426d]">
                        Siap Dicek Ulang
                    </button>
                </form>
            @endif
        </section>
    @endif
@endif
