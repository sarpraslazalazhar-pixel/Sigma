<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Pengaturan Sistem
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola parameter global sistem anggaran, batas ambang, aturan pembulatan saldo, pengingat tenggat, dan lampiran.
            </p>
        </div>

        {{-- Action Buttons (Top) --}}
        <div class="flex items-center gap-2.5 flex-wrap">
            <button
                type="button"
                wire:click="resetToDefaults"
                wire:confirm="Yakin ingin mengembalikan semua pengaturan ke nilai default awal sistem?"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Reset ke Default</span>
            </button>

            <button
                type="button"
                wire:click="save"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Simpan Pengaturan</span>
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

    @if (session()->has('error'))
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Setting Groups --}}
    <div class="grid grid-cols-1 gap-6">
        @foreach ($groups as $groupName => $entries)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                {{-- Group Header --}}
                <div class="px-5 sm:px-6 py-4 bg-slate-50/70 border-b border-slate-200 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#1E60D5] flex items-center justify-center shrink-0">
                        @if ($groupName === 'Anggaran')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        @elseif ($groupName === 'Pembulatan')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        @elseif ($groupName === 'UMP & Reminder')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif ($groupName === 'Lampiran')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                        @elseif ($groupName === 'Notifikasi')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">
                            {{ $groupName }}
                        </h2>
                    </div>
                </div>

                {{-- Group Settings List --}}
                <div class="divide-y divide-slate-100 p-5 sm:p-6 space-y-4 sm:space-y-0">
                    @foreach ($entries as $entry)
                        <div class="py-3 sm:py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="max-w-xl">
                                <label for="setting-{{ str_replace('.', '-', $entry['key']) }}" class="block text-xs sm:text-sm font-semibold text-slate-800">
                                    {{ $entry['label'] }}
                                </label>
                                @if (isset($entry['hint']))
                                    <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">
                                        {{ $entry['hint'] }}
                                    </p>
                                @endif
                                @error('values.' . $entry['key'])
                                    <p class="text-[11px] text-red-600 mt-1 font-medium">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="w-full sm:w-64 shrink-0">
                                @if ($entry['type'] === 'select')
                                    <select
                                        id="setting-{{ str_replace('.', '-', $entry['key']) }}"
                                        wire:model="values.{{ $entry['key'] }}"
                                        class="block w-full min-h-[44px] px-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-900 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                                    >
                                        @foreach ($entry['options'] as $option)
                                            <option value="{{ $option }}">
                                                {{ ucfirst(str_replace('_', ' ', $option)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input
                                        id="setting-{{ str_replace('.', '-', $entry['key']) }}"
                                        type="number"
                                        wire:model="values.{{ $entry['key'] }}"
                                        placeholder="{{ $entry['default'] }}"
                                        class="block w-full min-h-[44px] px-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-900 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                                    />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Action Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
        <button
            type="button"
            wire:click="resetToDefaults"
            wire:confirm="Yakin ingin mengembalikan semua pengaturan ke nilai default awal sistem?"
            class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer w-full sm:w-auto"
        >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Reset ke Default</span>
        </button>

        <button
            type="button"
            wire:click="save"
            class="min-h-[44px] inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Simpan Pengaturan</span>
        </button>
    </div>
</div>
