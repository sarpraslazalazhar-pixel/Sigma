<div class="relative" x-data @click.outside="if ($wire.showDropdown) $wire.closeDropdown()" @keydown.escape.window="if ($wire.showDropdown) $wire.closeDropdown()">
    {{-- Trigger Bell Button --}}
    <button
        type="button"
        wire:click="toggleDropdown"
        class="relative p-2 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-hidden"
        title="Notifikasi"
        aria-label="Lihat notifikasi"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute top-1 right-1 flex items-center justify-center min-w-4 h-4 px-1 text-[10px] font-bold text-white bg-rose-600 rounded-full border border-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    @if ($showDropdown)
        <div
            class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
            role="dialog"
            aria-label="Daftar Notifikasi"
        >
            {{-- Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Notifikasi</span>
                    @if ($unreadCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700">
                            {{ $unreadCount }} baru
                        </span>
                    @endif
                </div>

                @if ($unreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        class="text-xs font-medium text-blue-700 hover:text-blue-900 hover:underline transition-colors"
                    >
                        Tandai semua dibaca
                    </button>
                @endif
            </div>

            {{-- Body List --}}
            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    @php
                        $data = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);
                        $isUnread = is_null($notification->read_at);
                    @endphp
                    <div
                        wire:key="notif-{{ $notification->id }}"
                        wire:click="markAsRead('{{ $notification->id }}')"
                        class="p-3.5 flex items-start gap-3 cursor-pointer transition-colors text-left w-full {{ $isUnread ? 'bg-blue-50/50 hover:bg-blue-50' : 'bg-white hover:bg-slate-50' }}"
                    >
                        {{-- Icon Type --}}
                        <div class="shrink-0 mt-0.5">
                            @if ($notification->type === 'dokumen_menunggu_tindakan')
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            @elseif ($notification->type === 'anggaran_berubah_status')
                                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            @elseif ($notification->type === 'ump_tenggat')
                                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @elseif ($notification->type === 'permohonan_tertunda')
                                <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Text Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <p class="text-xs font-bold text-slate-900 truncate">
                                    {{ $data['judul'] ?? 'Pemberitahuan' }}
                                </p>
                                @if ($isUnread)
                                    <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-600 leading-snug line-clamp-2">
                                {{ $data['pesan'] ?? '' }}
                            </p>
                            <span class="text-[10px] text-slate-400 mt-1 block">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 px-4 text-center">
                        <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="text-xs text-slate-500 font-medium">Belum ada notifikasi</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
