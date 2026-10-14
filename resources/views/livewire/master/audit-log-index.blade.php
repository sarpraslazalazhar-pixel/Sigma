<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#0F2F57] tracking-tight">
                Jejak Audit (Audit Trail)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pencatatan riwayat perubahan data append-only dan aktivitas pengguna secara transparan.
            </p>
        </div>
        <div class="inline-flex items-center gap-2 self-start sm:self-auto px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold border border-slate-200">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span>Append-Only Immutable Log</span>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-2xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            {{-- Search Box --}}
            <div class="lg:col-span-4">
                <label for="filter-search" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pencarian
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="filter-search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari aksi atau jenis entitas..."
                        class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 pl-9 pr-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            {{-- Action Type Filter --}}
            <div class="lg:col-span-2">
                <label for="filter-action" class="block text-xs font-semibold text-slate-700 mb-1">
                    Tipe Aksi
                </label>
                <select
                    id="filter-action"
                    wire:model.live="actionFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Aksi</option>
                    <option value="create">Create (Tambah)</option>
                    <option value="update">Update (Ubah)</option>
                    <option value="sahkan">Sahkan</option>
                    <option value="toggle">Toggle / Status</option>
                    <option value="activated">Aktivasi</option>
                    <option value="closed">Tutup</option>
                    <option value="delete">Hapus</option>
                </select>
            </div>

            {{-- Actor / User Filter --}}
            <div class="lg:col-span-2">
                <label for="filter-user" class="block text-xs font-semibold text-slate-700 mb-1">
                    Pengguna / Aktor
                </label>
                <select
                    id="filter-user"
                    wire:model.live="userFilter"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                >
                    <option value="">Semua Pengguna</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div class="lg:col-span-2">
                <label for="filter-date-from" class="block text-xs font-semibold text-slate-700 mb-1">
                    Dari Tanggal
                </label>
                <input
                    type="date"
                    id="filter-date-from"
                    wire:model.live="dateFrom"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                />
            </div>

            {{-- Date To --}}
            <div class="lg:col-span-2">
                <label for="filter-date-to" class="block text-xs font-semibold text-slate-700 mb-1">
                    Sampai Tanggal
                </label>
                <input
                    type="date"
                    id="filter-date-to"
                    wire:model.live="dateTo"
                    class="w-full min-h-[44px] rounded-xl border border-slate-300 text-xs text-slate-800 px-3 py-2 bg-white focus:border-[#1E60D5] focus:outline-hidden"
                />
            </div>
        </div>

        @if ($search !== '' || $actionFilter !== '' || $userFilter !== '' || $dateFrom !== '' || $dateTo !== '')
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-500">Filter aktif diterapkan</span>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="text-xs font-semibold text-[#1E60D5] hover:text-[#0F2F57] hover:underline cursor-pointer"
                >
                    Reset Semua Filter
                </button>
            </div>
        @endif
    </div>

    {{-- VIEW 1: TAMPILAN MOBILE (< 1024px) --}}
    <div class="block lg:hidden space-y-3">
        @forelse ($logs as $log)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-[#0F2F57] text-white flex items-center justify-center text-xs font-bold shrink-0">
                            {{ strtoupper(substr($log->user?->name ?? ($log->on_behalf_of ?? 'S'), 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-900 truncate">
                                {{ $log->user?->name ?? 'Sistem' }}
                            </p>
                            @if ($log->on_behalf_of)
                                <p class="text-[11px] text-slate-500 truncate">
                                    a.n. {{ $log->on_behalf_of }}
                                </p>
                            @else
                                <p class="text-[11px] text-slate-400 truncate">
                                    {{ $log->user?->username ?? 'system' }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $this->getActionBadgeClass($log->action) }} shrink-0">
                        {{ $log->action }}
                    </span>
                </div>

                <div class="bg-slate-50 rounded-xl p-2.5 text-xs space-y-1 border border-slate-100">
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Entitas:</span>
                        <span class="font-medium text-slate-800 truncate max-w-[200px]" title="{{ $log->entity_type }}">
                            {{ class_basename($log->entity_type) }}{{ $log->entity_id ? ' #' . $log->entity_id : '' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Waktu:</span>
                        <span class="tabular-nums font-medium text-slate-700">
                            {{ $log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-' }}
                        </span>
                    </div>
                    @if ($log->ip)
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="text-slate-400">IP:</span>
                            <span class="font-mono text-[11px] text-slate-600">{{ $log->ip }}</span>
                        </div>
                    @endif
                </div>

                <div>
                    <button
                        type="button"
                        wire:click="openDiff({{ $log->id }})"
                        class="min-h-[44px] w-full px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Lihat Detail</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs">
                Tidak ada catatan audit yang cocok dengan filter.
            </div>
        @endforelse
    </div>

    {{-- VIEW 2: TAMPILAN DESKTOP (>= 1024px) --}}
    <div class="hidden lg:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">Waktu & Tanggal</th>
                    <th class="py-3.5 px-4">Aktor / Pengguna</th>
                    <th class="py-3.5 px-4">Aksi</th>
                    <th class="py-3.5 px-4">Entitas & ID</th>
                    <th class="py-3.5 px-4">IP / User Agent</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-medium text-slate-900 block tabular-nums">
                                {{ $log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('d M Y') : '-' }}
                            </span>
                            <span class="text-[11px] text-slate-500 block tabular-nums">
                                {{ $log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB' : '' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-[#0F2F57] text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                    {{ strtoupper(substr($log->user?->name ?? ($log->on_behalf_of ?? 'S'), 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">
                                        {{ $log->user?->name ?? 'Sistem' }}
                                    </p>
                                    @if ($log->on_behalf_of)
                                        <p class="text-[10px] text-slate-500 truncate">
                                            a.n. {{ $log->on_behalf_of }}
                                        </p>
                                    @else
                                        <p class="text-[10px] text-slate-400 truncate">
                                            {{ $log->user?->username ?? '-' }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $this->getActionBadgeClass($log->action) }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="text-slate-800 font-medium truncate max-w-[220px]" title="{{ $log->entity_type }}">
                                {{ class_basename($log->entity_type) }}
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono">
                                {{ $log->entity_id ? 'ID: ' . $log->entity_id : 'ID: -' }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 max-w-[200px]">
                            <span class="font-mono text-slate-700 text-[11px] block truncate">
                                {{ $log->ip ?? '-' }}
                            </span>
                            <span class="text-[10px] text-slate-400 truncate block" title="{{ $log->user_agent }}">
                                {{ Str::limit($log->user_agent ?? '-', 28) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <button
                                type="button"
                                wire:click="openDiff({{ $log->id }})"
                                class="min-h-[36px] px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-[#DCE5F6] text-[#001B3E] text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Lihat Rincian</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 px-4 text-center text-slate-500 text-xs">
                            Tidak ada catatan audit yang cocok dengan kriteria filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($logs->hasPages())
        <div class="pt-2">
            {{ $logs->links() }}
        </div>
    @endif

    {{-- MODAL RINCIAN PERUBAHAN (DIFF MODAL) --}}
    @if ($showDiffModal && $selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl border border-slate-200 shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden"
                @click.outside="closeDiffModal"
            >
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/70">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-[#0F2F57]">
                                Rincian Jejak Audit #{{ $selectedLog->id }}
                            </h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $this->getActionBadgeClass($selectedLog->action) }}">
                                {{ $selectedLog->action }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $selectedLog->created_at ? $selectedLog->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' : '-' }}
                            | Aktor: {{ $selectedLog->user?->name ?? 'Sistem' }}
                            {{ $selectedLog->on_behalf_of ? '(a.n. ' . $selectedLog->on_behalf_of . ')' : '' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeDiffModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                        aria-label="Tutup modal"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body (Scrollable) --}}
                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    {{-- Metadata Card --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 rounded-2xl p-3.5 border border-slate-100 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Entitas</span>
                            <span class="font-semibold text-slate-800 break-words">
                                {{ class_basename($selectedLog->entity_type) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">ID Entitas</span>
                            <span class="font-mono font-semibold text-slate-800">
                                {{ $selectedLog->entity_id ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">IP Address</span>
                            <span class="font-mono text-slate-800">
                                {{ $selectedLog->ip ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">User Agent</span>
                            <span class="text-slate-700 truncate block text-[11px]" title="{{ $selectedLog->user_agent }}">
                                {{ Str::limit($selectedLog->user_agent ?? '-', 24) }}
                            </span>
                        </div>
                    </div>

                    {{-- Perbandingan Nilai (Key-Value Diff) --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Perbandingan Data Sebelum vs Sesudah
                            </h3>
                            <span class="text-[11px] text-slate-400">
                                {{ count($diffRows) }} atribut terdeteksi
                            </span>
                        </div>

                        @if (count($diffRows) > 0)
                            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                        <tr>
                                            <th class="py-2.5 px-3 w-1/4">Atribut / Kolom</th>
                                            <th class="py-2.5 px-3 w-1/3">Sebelum (Before)</th>
                                            <th class="py-2.5 px-3 w-1/3">Sesudah (After)</th>
                                            <th class="py-2.5 px-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                        @foreach ($diffRows as $row)
                                            <tr class="{{ $row['status'] === 'modified' ? 'bg-amber-50/50' : ($row['status'] === 'added' ? 'bg-emerald-50/40' : ($row['status'] === 'removed' ? 'bg-rose-50/40' : '')) }}">
                                                <td class="py-2.5 px-3 font-sans font-semibold text-slate-800">
                                                    {{ $row['key'] }}
                                                </td>
                                                <td class="py-2.5 px-3 text-slate-600 break-all">
                                                    @if ($row['old'] === null)
                                                        <span class="italic text-slate-400 font-sans">null</span>
                                                    @elseif (is_bool($row['old']))
                                                        <span class="font-bold">{{ $row['old'] ? 'true' : 'false' }}</span>
                                                    @elseif (is_array($row['old']))
                                                        <span class="text-slate-800">{{ json_encode($row['old'], JSON_UNESCAPED_SLASHES) }}</span>
                                                    @else
                                                        <span>{{ (string) $row['old'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 text-slate-900 font-medium break-all">
                                                    @if ($row['new'] === null)
                                                        <span class="italic text-slate-400 font-sans">null</span>
                                                    @elseif (is_bool($row['new']))
                                                        <span class="font-bold">{{ $row['new'] ? 'true' : 'false' }}</span>
                                                    @elseif (is_array($row['new']))
                                                        <span class="text-slate-800">{{ json_encode($row['new'], JSON_UNESCAPED_SLASHES) }}</span>
                                                    @else
                                                        <span>{{ (string) $row['new'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 text-center font-sans">
                                                    @if ($row['status'] === 'modified')
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                            Berubah
                                                        </span>
                                                    @elseif ($row['status'] === 'added')
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                            Baru
                                                        </span>
                                                    @elseif ($row['status'] === 'removed')
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800">
                                                            Dihapus
                                                        </span>
                                                    @else
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500">
                                                            Sama
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center text-slate-500 text-xs">
                                Tidak ada data atribut before atau after yang tercatat pada log ini.
                            </div>
                        @endif
                    </div>

                    {{-- Formatted JSON Detail (Foldable/Raw view) --}}
                    <div class="space-y-3 pt-2">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Payload JSON Terformat (Raw)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Sebelum (Before):
                                </label>
                                <pre class="bg-slate-900 text-slate-100 rounded-xl p-3 text-[11px] font-mono overflow-x-auto max-h-48 border border-slate-800 leading-relaxed">{{ json_encode($selectedLog->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}' }}</pre>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Sesudah (After):
                                </label>
                                <pre class="bg-slate-900 text-slate-100 rounded-xl p-3 text-[11px] font-mono overflow-x-auto max-h-48 border border-slate-800 leading-relaxed">{{ json_encode($selectedLog->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}' }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50 flex items-center justify-end shrink-0">
                    <button
                        type="button"
                        wire:click="closeDiffModal"
                        class="min-h-[44px] px-5 py-2.5 rounded-full bg-[#1E60D5] hover:bg-[#0F2F57] text-white text-xs font-semibold transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
