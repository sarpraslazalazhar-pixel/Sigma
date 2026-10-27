# Fitur Reminder Configurable + Halaman Pengaturan Sistem

## Context

Saat ini reminder UMP tenggat menggunakan threshold H-3 yang hardcoded di `UmpRemind.php` (line 55: `$diffDays <= 3`). Setting `ump.tenggat_hari` (default 14 hari) sudah ada di tabel `settings` dan digunakan untuk menghitung `jatuh_tempo` saat pencairan, tapi threshold pengingat (berapa hari sebelum jatuh tempo notifikasi dikirim) tidak bisa diubah.

User meminta:
1. **UMP reminder threshold configurable** — admin bisa set H-berapa pengingat dikirim (bukan hanya H-3).
2. **Permohonan reminder baru** — permohonan yang menunggu tindakan di tahap manapun (`diajukan`, `diketahui_kadiv`, `disetujui_keuangan`) lebih dari X hari, kirim reminder ke pihak yang bertanggung jawab.
3. **Halaman Pengaturan Sistem lengkap** — UI admin untuk mengelola semua setting di tabel `settings` (tenggat, reminder, ambang, pembulatan, dll).

Notifikasi cukup dikirim sekali di threshold (bukan berulang). Tampilan merah di tabel yang sudah ada cukup sebagai "lebih diperhatikan."

## Approach

### Step 1: Tambah setting keys baru di seeder + migration-safe

Tambah 2 setting baru di `MasterDataSeeder`:
- `ump.pengingat_hari` → default `3` (artinya H-3 sebelum jatuh tempo). Ini menggantikan hardcoded `3` di `UmpRemind.php`.
- `permohonan.pengingat_hari` → default `3` (artinya 3 hari idle di satu tahap).

File: `database/seeders/MasterDataSeeder.php`
Di array `$settings` (baris 278-286), tambahkan:
```php
'ump.pengingat_hari' => 3,
'permohonan.pengingat_hari' => 3,
```

### Step 2: UmpRemind — ganti hardcoded threshold

File: `app/Console/Commands/UmpRemind.php`

Ganti baris 55 (`$diffDays <= 3`) menjadi:
```php
$pengingatHari = (int) Setting::get('ump.pengingat_hari', 3);
```
Lalu di logika (baris 55):
```php
} elseif ($diffDays <= $pengingatHari) {
```

Load `$pengingatHari` sekali di awal method `handle()`, setelah `$tenggatHari` check (baris 29-34). Letakkan di baris ~35:
```php
$pengingatHari = (int) Setting::get('ump.pengingat_hari', 3);
```

Ganti literal `3` di baris 55 dengan `$pengingatHari`.

### Step 3: Buat command `PermohonanRemind`

File baru: `app/Console/Commands/PermohonanRemind.php`

Command `permohonan:remind`. Logic:
1. Baca `permohonan.pengingat_hari` dari Setting. Jika null atau 0, skip (fitur nonaktif).
2. Query permohonan yang statusnya `diajukan`, `diketahui_kadiv`, atau `disetujui_keuangan`.
3. Untuk tiap permohonan, hitung usia di tahap saat ini: `Carbon::today()->diffInDays($permohonan->updated_at->startOfDay())`.
4. Jika usia >= `$pengingatHari`, kirim notifikasi ke pihak yang bertanggung jawab:
   - `diajukan` → approver: gunakan `$permohonan->atasan_id` (sudah di-set saat submit). Jika null, cari Kadiv divisi via `Unit::find($permohonan->unit_id)->getDivisiId()` lalu `User::where('unit_id', $divisiId)->where(fn($q) => $q->whereHas('leveling', fn($l) => $l->where('urutan', '>=', 3)))`.
   - `diketahui_kadiv` → keuangan: `User::where('is_active', true)->where(fn($q) => $q->where('role', 'keuangan')->orWhere('role', 'admin'))`.
   - `disetujui_keuangan` → keuangan: same as above.
5. Gunakan `NotificationService::kirim()` dengan:
   - type: `'permohonan_tertunda'`
   - dedup_key: `"pengingat_permohonan_{$permohonan->id}_{$permohonan->status}"` — idempoten per status.
   - payload:
     ```php
     [
         'judul' => "Permohonan Dana Tertunda {$hariIdle} Hari",
         'pesan' => "Permohonan {$permohonan->no_pengajuan} dari {$permohonan->user?->name} sudah {$hariIdle} hari menunggu tindakan di tahap {$labelTahap}.",
         'url' => route('permohonan.detail', $permohonan->id),
         'jenis' => 'permohonan',
         'entity_id' => $permohonan->id,
     ]
     ```

Label tahap mapping:
```php
$labelMap = [
    'diajukan' => 'Pemeriksaan Atasan',
    'diketahui_kadiv' => 'Verifikasi Keuangan',
    'disetujui_keuangan' => 'Pencairan Dana',
];
```

### Step 4: Daftarkan schedule untuk kedua command

File: `routes/console.php`

Tambahkan:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ump:remind')->dailyAt('07:00');
Schedule::command('permohonan:remind')->dailyAt('07:00');
```

Saat ini `routes/console.php` kosong (hanya default). Ini sesuai TECH-SPEC §5.6 yang menyebut `ump:remind` dijadwalkan harian 07:00. `permohonan:remind` dijadwalkan bersamaan.

### Step 5: Tambah icon `permohonan_tertunda` di notification bell

File: `resources/views/livewire/components/notification-bell.blade.php`

Di block `@if/$notification->type` (setelah baris 79 — blok `ump_tenggat`), tambahkan `@elseif ($notification->type === 'permohonan_tertunda')` dengan icon jam/warning berwarna violet:
```blade
@elseif ($notification->type === 'permohonan_tertunda')
    <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
```

### Step 6: Buat halaman Pengaturan Sistem (Livewire + Blade)

#### 6a. Livewire Component

File baru: `app/Livewire/Master/PengaturanIndex.php`

Komponen full-page Livewire. Mount memuat semua settings dari DB. Render form tabel dengan masing-masing setting bisa diedit inline.

Setting registry — hardcode daftar setting yang dikelola, termasuk label, tipe input, default, group:

```php
private const SETTING_REGISTRY = [
    // Anggaran
    ['key' => 'ambang.limit', 'label' => 'Ambang Peringatan Limit (%)', 'type' => 'number', 'default' => 80, 'group' => 'Anggaran', 'hint' => 'Persentase pemakaian efektif yang memicu status Mendekati Limit.'],
    ['key' => 'ambang.over', 'label' => 'Ambang Over Budget (%)', 'type' => 'number', 'default' => 100, 'group' => 'Anggaran', 'hint' => 'Persentase pemakaian efektif yang memicu status Over Budget.'],
    ['key' => 'kode.max_depth', 'label' => 'Kedalaman Maksimum Kode Anggaran', 'type' => 'number', 'default' => 4, 'group' => 'Anggaran', 'hint' => 'Jumlah level kedalaman pohon kode anggaran.'],
    // Pembulatan
    ['key' => 'pembulatan.kelipatan', 'label' => 'Kelipatan Pembulatan (Rp)', 'type' => 'number', 'default' => 1000, 'group' => 'Pembulatan', 'hint' => 'Nominal kelipatan pembulatan saldo UMP.'],
    ['key' => 'pembulatan.arah', 'label' => 'Arah Pembulatan', 'type' => 'select', 'options' => ['terdekat', 'atas', 'bawah'], 'default' => 'terdekat', 'group' => 'Pembulatan', 'hint' => 'Arah pembulatan: terdekat, selalu ke atas, atau selalu ke bawah.'],
    // UMP & Tenggat
    ['key' => 'ump.tenggat_hari', 'label' => 'Jatuh Tempo UMP (Hari)', 'type' => 'number', 'default' => 14, 'group' => 'UMP & Reminder', 'hint' => 'Jumlah hari dari tanggal cair hingga jatuh tempo UMP. Kosong = nonaktif.', 'nullable' => true],
    ['key' => 'ump.pengingat_hari', 'label' => 'Pengingat UMP Sebelum Jatuh Tempo (Hari)', 'type' => 'number', 'default' => 3, 'group' => 'UMP & Reminder', 'hint' => 'Kirim notifikasi pengingat H-X sebelum jatuh tempo UMP.'],
    ['key' => 'permohonan.pengingat_hari', 'label' => 'Pengingat Permohonan Tertunda (Hari)', 'type' => 'number', 'default' => 3, 'group' => 'UMP & Reminder', 'hint' => 'Kirim notifikasi jika permohonan menunggu tindakan lebih dari X hari.'],
    // Lampiran
    ['key' => 'lampiran.max_kb', 'label' => 'Ukuran Maks Lampiran (KB)', 'type' => 'number', 'default' => 5120, 'group' => 'Lampiran', 'hint' => 'Ukuran maksimum per berkas lampiran dalam kilobyte (5120 = 5 MB).'],
    // Keuangan
    ['key' => 'keuangan.mode', 'label' => 'Mode Keuangan', 'type' => 'select', 'options' => ['atas_nama', 'langsung'], 'default' => 'atas_nama', 'group' => 'Keuangan', 'hint' => '"atas_nama" = petugas input mencatat atas nama Keuangan. "langsung" = Keuangan langsung.'],
];
```

Public properties:
- `$values` → `array` keyed by setting key, masing-masing berisi value saat ini.

Method `mount()`:
```php
public function mount(): void
{
    foreach (self::SETTING_REGISTRY as $entry) {
        $this->values[$entry['key']] = Setting::get($entry['key'], $entry['default']);
    }
}
```

Method `save()`:
```php
public function save(): void
{
    foreach (self::SETTING_REGISTRY as $entry) {
        $key = $entry['key'];
        $val = $this->values[$key];
        if (isset($entry['nullable']) && $entry['nullable'] && ($val === '' || $val === null)) {
            Setting::set($key, null);
        } else {
            Setting::set($key, $val);
        }
    }
    session()->flash('success', 'Pengaturan berhasil disimpan.');
    $this->dispatch('notify', ['type' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);
}
```

Method `resetToDefaults()`:
```php
public function resetToDefaults(): void
{
    foreach (self::SETTING_REGISTRY as $entry) {
        $this->values[$entry['key']] = $entry['default'];
    }
}
```

Render method returns `view('livewire.master.pengaturan-index')`.

Akses hanya admin: di `mount()`, tambahkan guard:
```php
if (Auth::user()?->role !== 'admin') {
    abort(403, 'Akses terbatas untuk Administrator.');
}
```

#### 6b. Blade View

File baru: `resources/views/livewire/master/pengaturan-index.blade.php`

Ikuti styling sibling views (`user-index.blade.php`, `tahun-anggaran-index.blade.php`): Material Design 3 card, flash notifications, form sections.

Layout:
- Judul: "Pengaturan Sistem"
- Group settings berdasarkan `group` key dari registry.
- Setiap group = card dengan heading.
- Setiap setting = row dengan label, hint, dan input:
  - `type: number` → `<input type="number" wire:model="values.{key}">`
  - `type: select` → `<select wire:model="values.{key}">` dengan options
- Tombol bawah: "Simpan Pengaturan" (`wire:click="save"`) + "Reset ke Default" (`wire:click="resetToDefaults"` dengan konfirmasi).

#### 6c. Route

File: `routes/web.php`

Tambah route di grup `auth`, setelah audit-logs (baris 41):
```php
Route::get('/master/pengaturan', \App\Livewire\Master\PengaturanIndex::class)
    ->middleware('can:manage-master-data')
    ->name('master.pengaturan');
```

Gunakan gate `manage-master-data` yang sudah ada (hanya admin).

#### 6d. Sidebar Navigation

File: `resources/views/layouts/app.blade.php`

Di blok sidebar, setelah link Audit Log (baris ~315, sebelum `</div>` penutup nav section "Pengaturan & Sistem"), tambahkan link Pengaturan:

```blade
@can('manage-master-data')
    <a
        href="{{ route('master.pengaturan') }}"
        wire:navigate
        @click="sidebarOpen = false"
        class="{{ request()->routeIs('master.pengaturan') ? 'bg-[#DCE5F6] text-[#001B3E] font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }} rounded-xl flex items-center gap-3 px-3.5 py-2.5 text-sm transition-colors"
    >
        <span class="flex items-center gap-3 truncate">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Pengaturan Sistem
        </span>
        <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ request()->routeIs('master.pengaturan') ? 'bg-blue-200 text-[#001B3E]' : 'bg-slate-100 text-slate-500' }} font-semibold">Admin</span>
    </a>
@endcan
```

#### 6e. Breadcrumb

Di file layout `app.blade.php`, tambahkan breadcrumb case untuk `master.pengaturan`:
- Primary: `Pengaturan`
- Secondary: `Konfigurasi Sistem`

### Step 7: Tambah `artisan-runner` command (opsional)

Tambahkan `permohonan:remind` ke daftar `allowedCommands` di `routes/web.php` baris 88-98 bersama `ump:remind`, agar admin bisa trigger manual via artisan runner saat setup:

```php
'ump:remind' => '10. Run ump:remind (Pengingat UMP)',
'permohonan:remind' => '11. Run permohonan:remind (Pengingat Permohonan)',
```

## Critical Files & Anchors

| File | Symbol/Region | Alasan |
|---|---|---|
| `app/Console/Commands/UmpRemind.php` | Line 55 `$diffDays <= 3` | Hardcoded threshold yang harus jadi configurable |
| `app/Services/PermohonanWorkflow.php` | `notifyNextApprover()`, `notifyKeuangan()` | Pola reuse untuk identify recipients per stage |
| `routes/console.php` | Seluruh file | Saat ini kosong; tempat register schedule |
| `resources/views/layouts/app.blade.php` | Sidebar nav baris ~286-315 | Posisi insert link Pengaturan |
| `database/seeders/MasterDataSeeder.php` | Array `$settings` baris 278-286 | Tempat tambah default setting baru |

## Verification

1. **UMP reminder configurable**: Jalankan `php artisan tinker --execute "App\Models\Setting::set('ump.pengingat_hari', 5);"` lalu `php artisan ump:remind`. Verifikasi bahwa UMP yang jatuh tempo H-5 atau kurang mendapat notifikasi (bukan hanya H-3).

2. **Permohonan reminder**: Buat permohonan test (atau gunakan existing) yang sudah `diajukan` lebih dari 3 hari. Set `permohonan.pengingat_hari` ke 1 untuk testing. Jalankan `php artisan permohonan:remind`. Verifikasi notifikasi terkirim di tabel `notifications` ke approver yang tepat:
   ```bash
   php artisan tinker --execute "DB::table('notifications')->where('type','permohonan_tertunda')->get();"
   ```

3. **Halaman Pengaturan**: Login sebagai admin, navigasi ke `/master/pengaturan`. Verifikasi semua setting tampil dengan nilai saat ini. Ubah satu nilai (misal `ump.pengingat_hari` dari 3 ke 5), klik Simpan, refresh halaman → nilai tetap 5. Verifikasi non-admin mendapat 403.

4. **End-to-end**: Ubah `ump.pengingat_hari` ke 7 via halaman Pengaturan. Jalankan `php artisan ump:remind`. Pastikan UMP yang jatuh tempo H-7 atau kurang mendapat notifikasi.

5. **Existing tests**: Jalankan `php artisan test --filter=NotificationTest` dan `php artisan test --filter=PencairanWorkflowTest` — harus tetap pass (tidak mengubah default behavior, hanya extract hardcoded value ke setting).

## Assumptions & Contingencies

- `updated_at` pada permohonan berubah setiap kali status berubah (karena status update melalui `save()`). Jika ternyata ada update selain status change yang memperbarui `updated_at`, fallback: track `submitted_at` untuk status `diajukan`, dan gunakan `approvals.created_at` (approval terakhir di siklus ini) untuk `diketahui_kadiv` dan `disetujui_keuangan`. Implementer cek dengan `php artisan tinker` apakah `updated_at` berubah selain saat status transition.
- `routes/console.php` saat ini hanya berisi default Artisan::command. Jika sudah ada schedule commands di sana, jangan duplikat — tambahkan di bawah yang sudah ada.
