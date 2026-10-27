@php
    $lampiranList = $itemModel ? $itemModel->lampiran : collect();
@endphp
<div class="space-y-1.5">
    @if ($lampiranList->isNotEmpty())
        <ul class="space-y-0.5">
            @foreach ($lampiranList as $lampiran)
                <li>
                    <a href="{{ route('lampiran.show', $lampiran->id) }}" target="_blank"
                        class="text-[11px] text-[#1E60D5] hover:underline break-all">
                        {{ $lampiran->original_name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($sectionEditable && $row['id'])
        @if ($this->buktiItemTarget === $row['id'])
            <div class="space-y-1.5">
                <input type="file" wire:model="buktiItem" accept=".jpg,.jpeg,.png,.pdf"
                    class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-800" />
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" wire:click="simpanBuktiItem({{ $row['id'] }})"
                        class="min-h-[32px] px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition-colors cursor-pointer">
                        Simpan Bukti
                    </button>
                    <button type="button" wire:click="cancelBuktiItemTarget"
                        class="min-h-[32px] px-3 py-1 rounded-lg border border-slate-300 text-slate-600 text-[11px] font-semibold hover:bg-slate-50 transition-colors cursor-pointer">
                        Batal
                    </button>
                </div>
                <div wire:loading wire:target="buktiItem" class="text-[11px] text-slate-500">Mengunggah berkas...</div>
                @error('buktiItem') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        @else
            <button type="button" wire:click="setBuktiItemTarget({{ $row['id'] }})"
                class="min-h-[32px] px-3 py-1 rounded-lg border border-slate-300 text-slate-700 text-[11px] font-semibold hover:bg-slate-50 transition-colors cursor-pointer">
                {{ $lampiranList->isEmpty() ? 'Unggah Bukti' : 'Tambah Bukti' }}
            </button>
        @endif
    @elseif ($sectionEditable && ! $row['id'])
        <span class="text-[11px] text-slate-400">Simpan draft dulu untuk mengunggah bukti.</span>
    @elseif ($lampiranList->isEmpty())
        <span class="text-[11px] text-slate-400">Tanpa bukti</span>
    @endif
</div>