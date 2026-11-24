@php
    $lampiranList = $itemModel ? $itemModel->lampiran : collect();
    $sectionKey = $section['key'];
    $pendingFile = $this->pendingRowFiles[$sectionKey][$rowIndex] ?? null;
    $isEditing = (bool) ($isEditingRow ?? false);
@endphp
<div class="space-y-1.5">
    @if ($lampiranList->isNotEmpty())
        <ul class="space-y-1">
            @foreach ($lampiranList as $lampiran)
                <li class="flex items-center gap-1.5" wire:key="lampiran-{{ $lampiran->id }}-{{ $isEditing ? 'e' : 'l' }}">
                    <a href="{{ route('lampiran.show', $lampiran->id) }}" target="_blank"
                        class="text-[11px] text-[#1E60D5] hover:underline break-all min-w-0 flex-1">
                        {{ $lampiran->original_name }}
                    </a>
                    @if ($sectionEditable && $isEditing)
                        <button
                            type="button"
                            wire:click="hapusLampiranItem({{ $lampiran->id }})"
                            wire:loading.attr="disabled"
                            title="Hapus bukti tersimpan"
                            aria-label="Hapus bukti"
                            class="shrink-0 w-5 h-5 rounded-md bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-colors cursor-pointer"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($sectionEditable && $isEditing)
        <div class="space-y-1.5">
            @if ($pendingFile)
                <div class="flex items-center justify-between gap-1.5 px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800">
                    <span class="truncate font-medium" title="{{ $pendingFile->getClientOriginalName() }}">
                        {{ $pendingFile->getClientOriginalName() }}
                    </span>
                    <button
                        type="button"
                        wire:click="hapusPendingFile('{{ $sectionKey }}', {{ $rowIndex }})"
                        title="Batalkan pilihan berkas"
                        aria-label="Hapus pilihan bukti"
                        class="shrink-0 text-emerald-700 hover:text-red-600 font-bold px-1 cursor-pointer"
                    >
                        ×
                    </button>
                </div>
            @endif

            <input
                type="file"
                wire:model="pendingRowFiles.{{ $sectionKey }}.{{ $rowIndex }}"
                accept=".jpg,.jpeg,.png,.pdf"
                aria-label="Upload bukti transaksi baris {{ $rowIndex + 1 }}"
                class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 cursor-pointer"
            />
            <div wire:loading wire:target="pendingRowFiles.{{ $sectionKey }}.{{ $rowIndex }}" class="text-[11px] text-slate-500">
                Mengunggah berkas...
            </div>
            @error('pendingRowFiles.' . $sectionKey . '.' . $rowIndex)
                <p class="text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @elseif ($sectionEditable && $row['id'] && $this->buktiItemTarget === $row['id'])
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
    @elseif ($lampiranList->isEmpty())
        <span class="text-[11px] text-slate-400">Tanpa bukti</span>
    @endif
</div>