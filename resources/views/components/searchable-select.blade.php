@props([
    'placeholder' => 'Pilih opsi...',
    'options' => [],
    'nullable' => true,
    'nullLabel' => 'Semua Pilihan',
    'searchPlaceholder' => 'Ketik untuk mencari...',
    'disabled' => false,
])

@php
    $wireModel = $attributes->wire('model');
    $wireProperty = $wireModel->value();

    $normalizedOptions = collect($options)->map(function ($opt) {
        if (is_array($opt)) {
            $val = $opt['value'] ?? $opt['id'] ?? '';
            $sub = $opt['sub'] ?? $opt['kode'] ?? $opt['jabatan'] ?? '';
            $label = $opt['label'] ?? $opt['nama'] ?? $opt['name'] ?? '';
            return [
                'value' => (string) $val,
                'label' => (string) $label,
                'sub' => (string) $sub,
            ];
        } elseif (is_object($opt)) {
            $val = $opt->value ?? $opt->id ?? '';
            $sub = $opt->sub ?? $opt->kode ?? $opt->jabatan ?? '';
            $label = $opt->label ?? $opt->nama ?? $opt->name ?? '';
            return [
                'value' => (string) $val,
                'label' => (string) $label,
                'sub' => (string) $sub,
            ];
        }
        return [
            'value' => (string) $opt,
            'label' => (string) $opt,
            'sub' => '',
        ];
    })->values();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: @if($wireProperty) @entangle($attributes->wire('model')) @else null @endif,
        options: {{ Js::from($normalizedOptions) }},
        nullable: {{ $nullable ? 'true' : 'false' }},
        nullLabel: '{{ $nullLabel }}',
        placeholder: '{{ $placeholder }}',

        get selectedOption() {
            if (this.selected === null || this.selected === '' || this.selected === undefined) {
                return null;
            }
            return this.options.find(o => String(o.value) === String(this.selected)) || null;
        },

        get displayLabel() {
            if (!this.selectedOption) {
                return this.nullable && this.nullLabel ? this.nullLabel : this.placeholder;
            }
            if (this.selectedOption.sub && this.selectedOption.sub !== '') {
                return this.selectedOption.sub + ' - ' + this.selectedOption.label;
            }
            return this.selectedOption.label;
        },

        get isSelected() {
            return this.selected !== null && this.selected !== '' && this.selected !== undefined;
        },

        get filteredOptions() {
            if (!this.search || this.search.trim() === '') {
                return this.options;
            }
            const s = this.search.toLowerCase().trim();
            return this.options.filter(o => {
                const labelMatch = o.label && o.label.toLowerCase().includes(s);
                const subMatch = o.sub && o.sub.toLowerCase().includes(s);
                return labelMatch || subMatch;
            });
        },

        select(val) {
            this.selected = val;
            this.open = false;
            this.search = '';
        },

        clear() {
            this.selected = null;
            this.open = false;
            this.search = '';
        },

        toggle() {
            if ({{ $disabled ? 'true' : 'false' }}) return;
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.$refs.searchInput && this.$refs.searchInput.focus();
                });
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative w-full text-xs"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    {{-- Trigger Button --}}
    <button
        type="button"
        @click="toggle()"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        class="w-full text-left rounded-xl border border-slate-300 bg-white min-h-[44px] py-2.5 px-3 text-slate-800 focus:bg-white focus:border-[#1E60D5] focus:outline-hidden transition-colors flex items-center justify-between gap-2 shadow-2xs {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-50' }}"
        aria-haspopup="listbox"
        :aria-expanded="open"
    >
        <span
            class="truncate"
            :class="!isSelected ? 'text-slate-500' : 'text-slate-900 font-medium'"
            x-text="displayLabel"
        ></span>

        <div class="flex items-center gap-1 shrink-0 text-slate-400">
            <template x-if="nullable && isSelected">
                <span
                    @click.stop="clear()"
                    class="p-0.5 hover:text-slate-600 rounded-full hover:bg-slate-200 transition-colors cursor-pointer"
                    title="Hapus pilihan"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
            </template>
            <svg
                class="w-4 h-4 transition-transform duration-150"
                :class="open ? 'rotate-180' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>

    {{-- Dropdown Menu Popover --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 right-0 mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden"
        style="display: none;"
    >
        {{-- Search Input inside Dropdown --}}
        <div class="p-2 border-b border-slate-100 bg-slate-50/80">
            <div class="relative flex items-center">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    x-ref="searchInput"
                    x-model="search"
                    type="text"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full text-xs rounded-lg border border-slate-200 bg-white pl-8 pr-7 py-1.5 text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:outline-hidden"
                    @keydown.enter.prevent="if (filteredOptions.length > 0) { select(filteredOptions[0].value); }"
                />
                <button
                    type="button"
                    x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus();"
                    class="absolute right-2 text-slate-400 hover:text-slate-600"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Options Scrollable List --}}
        <div class="max-h-56 overflow-y-auto divide-y divide-slate-50 p-1">
            {{-- Nullable Default Option --}}
            @if ($nullable)
                <div
                    @click="clear()"
                    class="px-2.5 py-1.5 rounded-lg cursor-pointer transition-colors flex items-center justify-between text-slate-500 hover:bg-slate-100"
                    :class="!isSelected ? 'bg-blue-50/70 font-semibold text-blue-700' : ''"
                >
                    <span>{{ $nullLabel }}</span>
                    <template x-if="!isSelected">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </div>
            @endif

            {{-- Filtered Options --}}
            <template x-for="opt in filteredOptions" :key="opt.value">
                <div
                    @click="select(opt.value)"
                    class="px-2.5 py-1.5 rounded-lg cursor-pointer transition-colors flex items-center justify-between gap-2 hover:bg-slate-100"
                    :class="String(selected) === String(opt.value) ? 'bg-blue-50/80 font-bold text-blue-900' : 'text-slate-700'"
                >
                    <div class="flex items-center gap-2 truncate">
                        <template x-if="opt.sub && opt.sub !== ''">
                            <span
                                class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-[#0F2F57] shrink-0"
                                x-text="opt.sub"
                            ></span>
                        </template>
                        <span class="truncate" x-text="opt.label"></span>
                    </div>

                    <template x-if="String(selected) === String(opt.value)">
                        <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </div>
            </template>

            {{-- Empty State --}}
            <div
                x-show="filteredOptions.length === 0"
                class="py-6 px-3 text-center text-slate-400 text-xs"
            >
                Tidak ada pilihan yang cocok dengan pencarian
            </div>
        </div>
    </div>
</div>
