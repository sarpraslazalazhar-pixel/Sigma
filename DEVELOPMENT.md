# DEVELOPMENT — SIGMA E-Budgeting Lite

Dokumen pelacakan pengembangan dan audit teknis proyek SIGMA. Setiap perubahan arsitektur, rilis, keputusan teknis, dan catatan migrasi dicatat di sini secara berurutan.

## 1. Metadata Proyek

- **Nama Sistem:** SIGMA (Sistem Informasi & Monitoring Anggaran)
- **Tagline:** Pantau, Kendalikan, Putuskan
- **Pemilik Proses:** Sekretariat LAZ Al Azhar
- **Tech Stack Inti:** Laravel 13 (PHP 8.3+), Livewire 4, Alpine.js, Tailwind CSS 4, MySQL 8
- **Dokumen Terkait:**
  - `PRD.md` — Persyaratan produk (apa & mengapa)
  - `TECH-SPEC.md` — Spesifikasi teknis (bagaimana & skema)
  - `Gambaran & Flow.md` — Catatan flow awal & field form
  - `Gambaran-awal.md` — Draft awal konsep sistem

## 2. Status Rilis

| Rilis | Deskripsi | Target | Status | Tanggal Selesai |
| --- | --- | --- | --- | --- |
| **R0** | Brainstorming, PRD, Tech Spec, form fisik, penguncian T1–T10 | Fondasi | **SELESAI** | 14 Okt 2026 |
| **R1** | Skeleton Laravel 13, Auth, User & Leveling, Master Data, Kode Anggaran bertingkat & RAB, Audit dasar | Core Master | **SELESAI** | 14 Okt 2026 |
| **R2** | Permohonan Dana, Jenjang Persetujuan, Mode Input Atas Nama Keuangan, PDF Landscape | Alur Pengajuan | **SEDANG BERJALAN** | - |
| **R3** | Pencairan PPD, Laporan UMP, Upload Bukti, Penomoran, PDF Portrait, Realisasi | Pertanggungjawaban | TERJADWAL | — |
| **R4** | Dashboard & Monitoring, Filter, Export Excel, Notifikasi In-App | Monitoring | TERJADWAL | — |
| **R5** | Budget Revision, Target Bulanan, Status Kurang Terserap, Verifikasi Keuangan | Kontrol Lanjutan | TERJADWAL | — |

## 3. Log Audit Pengembangan

### 2026-10-14 — R0: Brainstorming & Finalisasi Spesifikasi
- **Aktor:** Tim Dev + Pemilik Proses
- **Aktivitas:**
  1. Penyelarasan PRD dengan alur riil organisasi (LAZ Al Azhar).
  2. Pemilihan framework: Laravel 13 + Livewire 4 (didukung PHP 8.3 di Biznet shared hosting).
  3. Konfirmasi batasan server: cPanel FTP tanpa SSH, cron tersedia tiap menit, storage private lokal di luar `public_html`.
  4. Penyelesaian keputusan terbuka:
     - **T1 (>100 jt):** Fleksibel via setting `approval.direksi_user_id` (Kadiv atau Direktur Utama).
     - **T2 (Pengesahan RAB & Revisi):** Disetujui role Keuangan.
     - **T3 (Pembulatan UMP):** Kelipatan Rp1.000 ke terdekat (dapat diatur).
     - **T4 (Program):** Disatukan di header (program utama per baris dihapus dari tampilan utama, tetap tersedia relasi).
     - **T5 (Cetak PPD):** Template portrait formal pencairan disiapkan.
     - **T6 (Tenggat UMP):** Dikonfigurasi di setting `ump.tenggat_hari` (default 14 hari, dapat dimatikan).
     - **T7 (Kelebihan UMP):** Realisasi diakui penuh, dicatat kekurangan dana.
     - **T8 (Petugas Input Keuangan):** Staf TU Sekretariat; kelak dialihkan ke divisi Keuangan saat akun dibuat.
     - **T9 (Format PPD):** Input manual staf TU mengikuti penomoran fisik dari Keuangan.
     - **T10 (Verifikasi Keuangan):** Kolom `perlu_verifikasi_keuangan` disiapkan pada dokumen untuk verifikasi retrospektif saat Keuangan aktif.
  5. Validasi form fisik nyata:
     - Form Permohonan: A4 Landscape, 3 kotak tanda tangan (Diperiksa, Disetujui, Dibuat).
     - Laporan UMP: A4 Portrait, kolom NO BUKTI teks, 4 kotak tanda tangan dalam 2 grup (Unit & Keuangan).
  6. Penyusunan `TECH-SPEC.md` lengkap (21 tabel, aturan konkurensi, pipeline CI/CD GitHub Actions FTP).

### 2026-10-14 — R1 (Langkah 1): Instalasi Skeleton Laravel 13
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Verifikasi workstation lokal: PHP 8.3.33 (cli), Composer 2.10.2, Node v24.20.0, Git 2.55.0.
  2. Bootstrapping proyek: Laravel v13.36.0 (package `laravel/laravel:v13.11.0`).
  3. Struktur folder diposisikan di root repositori: `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`.
  4. Instalasi dependencies inti sesuai `TECH-SPEC.md`:
     - `livewire/livewire` v4.4.7
     - `barryvdh/laravel-dompdf` v3.1.2
     - `maatwebsite/excel` v4.0.4
     - `@tailwindcss/vite` v4.0 & Vite v8.3.4 (build teruji: 3.97s)
  5. Inisialisasi Git repositori lokal (`.env` aman diabaikan).
  6. Integrasi aturan Anti-Slop (Mode 1: DURING pengerjaan) pada `AGENTS.md`.
  7. Pengujian awal: 2/2 test PHPUnit/Pest berhasil.
  8. Pembuatan file pelacakan `DEVELOPMENT.md`.


### 2026-10-14 — R1 (Langkah 2): Frontend Login & Dashboard Fondasi
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Integrasi logo resmi SIGMA LAZ Al-Azhar ke `public/images/logo-sigma.png`.
  2. Penyusunan `DESIGN.md` (Navy `#0F2F57`, Cobalt `#1E60D5`, Emerald `#059669`, Dials: ENERGY 1 / RHYTHM 1 / MOTION 1).
  3. Migrasi & Seeder user dasar (Admin IT, Staf TU, Manager HRD, Kepala Divisi, Manajer Keuangan).
  4. Implementasi komponen Livewire 4 `App\Livewire\Auth\Login`:
     - Autentikasi fleksibel (username atau email)
     - Rate limiting 5x/menit per identifier+IP
     - Penolakan akun nonaktif (FR-AUTH-3)
     - Toggle tampil/sembunyi password dengan aksesibilitas keyboard
  5. Redesain Antarmuka Login Modern:
     - Layout Split-Screen Desktop: Panel kiri showcase identitas kelembagaan (Navy `#0A2540`, kartu logo transparan, pilar Pantau-Kendalikan-Putuskan); Panel kanan form login fokus dengan icon SVG interaktif.
     - Pemrosesan logo: `logo-sigma-transparent.png` (transparan tanpa latar kotak putih) dan `logo-mark.png` (simbol S pita transparan).
     - Selector Akun Uji 1-Klik (`fillDemo`) di lingkungan lokal untuk pengujian cepat tanpa ketik manual.
  6. Redesain Dashboard Eksekutif:
     - Navbar modern dengan logo SIGMA transparan, badge tahun anggaran aktif, dan profil user.
     - Kartu Kondisi Anggaran dengan hierarki visual tegas (Plafon RAB, Dana Terikat, Realisasi UMP, dan Sisa Tersedia).
     - Visualizer Rantai Kendali Anggaran (6 tahapan proses sesuai PRD).
     - Kolom Antrean Persetujuan dan Peringatan Sisa Anggaran (Limit & Over) dengan empty state informatif (R-27).
  7. Pengujian fitur `tests/Feature/Auth/LoginTest.php` (11 tests, 38 assertions, 100% PASS).
  8. Lulus seluruh kriteria Delivery Gate Anti-Slop (R-01 s.d R-38).

### 2026-10-14 — R1 (Langkah 3): Penerapan Google Material Design 3 (M3)
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Pemetaan sistem token Material 3 dari brand seed SIGMA ke `resources/css/app.css` (@theme) dan dokumentasi di `DESIGN.md`:
     - Tonal Palettes: Primary (Navy `#0F2F57`), Secondary (Slate `#485F7D`), Tertiary (Emerald `#006C4C`), Error (`#BA1A1A`), Surface Containers (Lowest s.d Highest), Outline, Outline Variant.
     - Shape scale M3: 4px (xs), 8px (sm), 12px (md), 16px (lg), 28px (xl), 9999px (full).
  2. Implementasi M3 pada komponen antarmuka:
     - Form Login: M3 Outlined Text Field (radius 12px, tinggi min 48px, outline tegas, label bersih), M3 Filled Button (radius full, tinggi 48px, loading indicator M3), M3 Suggestion Chips (radius 8px, tinggi 32px), M3 Outlined Card (radius 16px).
     - App Layout: M3 Sidebar Navigation (lebar w-64/xl:w-72, drawer mobile Alpine.js, user card M3 di bawah, thin top bar h-16 dengan breadcrumb dan status chips, independent scroll container: sidebar dan top bar pinned, halaman scroll mandiri), M3 Active Indicator Pill (kapsul aktif `bg-[#DCE5F6] text-[#001B3E] font-semibold`), M3 Assist Chip untuk Tahun Anggaran.
     - Dashboard: M3 Card Hierarchy (Elevated & Outlined cards, radius 16px), M3 Workflow Rail, M3 Empty State (tonal icon container).
  3. Verifikasi build asset Vite: bundle terkompilasi bersih (82.18 kB).
  4. Pengujian otomatis: 11/11 tests pass (38 assertions).
  5. Audit Anti-Slop: 100% bebas karakter em dash (`—`), rasio kontras WCAG AA terpenuhi.
  6. Refinement Aset & Naming: Pembersihan speckle noise pada logo transparan baru, ekstraksi logo-mark, pembaruan layout login floating card centered canvas, dan penyelarasan nama brand dari LAZWaf menjadi LAZ di seluruh views dan dokumentasi.
  7. Desain Liquid Glass Halaman Login: Viewport gradient fluid organic blobs dengan blur-3xl, kanvas luar frosted glass (bg-white/50 backdrop-blur-2xl), floating card kaca (bg-white/80 backdrop-blur-xl dengan specular sheen top highlight), input glass translucent beraksesibilitas WCAG AA, dan tombol submit pill bergradien dengan shadow pendar.

### 2026-10-14 - R1 (Langkah 4): Fondasi Database, Model, Seeder, dan Service Master Data
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Migrasi Database (10 tabel/modifikasi sesuai TECH-SPEC.md Bagian 4):
     - `tahun_anggaran` (tahun smallint unique, status draft/aktif/ditutup)
     - `sumber_dana` (nama, butuh_asnaf bool, is_active bool)
     - `units` (parent_id self-ref, kode unique, nama, tipe unit/divisi, sumber_dana_default_id, is_active)
     - `asnaf` (nama, is_active)
     - `program_utama` (nama, is_active)
     - `levelings` (nama, batas_bawah, batas_atas nullable, urutan unique)
     - `kode_anggaran` (tahun_anggaran_id, parent_id self-ref, kode, nama, deskripsi, depth, plafon BIGINT, budget_status draft/disahkan, prev_year_id, is_active, unique(tahun_anggaran_id, kode))
     - `audit_logs` (user_id, on_behalf_of, action, entity_type, entity_id, before, after, ip, user_agent, created_at useCurrent, tanpa updated_at)
     - `settings` (key unique, value JSON)
     - Foreign keys constraint pada tabel `users` (`unit_id` dan `leveling_id` nullOnDelete).
  2. Eloquent Models:
     - `TahunAnggaran` (scopeAktif, relation kodeAnggaran)
     - `Unit` (parent, children, sumberDanaDefault, users)
     - `SumberDana` (units)
     - `Asnaf`, `ProgramUtama`, `Leveling` (users)
     - `KodeAnggaran` (tahunAnggaran, parent, children, prevYear, helper isLeaf)
     - `AuditLog` (append-only: updating & deleting melempar RuntimeException)
     - `Setting` (helper statis Setting::get dan Setting::set)
     - Model `User` diperbarui dengan relasi unit() dan leveling().
  3. Services:
     - `AuditLogger.php`: method log($action, $entity, $before, $after, $onBehalfOf)
     - `BudgetCalculator.php`: method calculateTree($tahunAnggaranId) kalkulasi bottom-up pohon anggaran (plafon induk vs total budget anak, belum dialokasikan, effective budget, status).
  4. Seeders:
     - `MasterDataSeeder.php`: TA 2026, 3 Sumber Dana, 5 Units/Divisi (SEK, DIR, KLM, HUM, DKL), 8 Asnaf, 5 Program Utama, 4 Leveling persetujuan, 7 default settings sistem.
     - `KodeAnggaranSeeder.php`: 7 pohon kode anggaran bertingkat tahun 2026 (Plafon Rp500.000.000 disahkan).
     - `UserSeeder.php`: sinkronisasi unit_id dan leveling_id 5 akun dinas.
     - `DatabaseSeeder.php`: orkestrasi seeder berurutan.
  5. Verifikasi & Pengujian:
     - `php artisan migrate:fresh --seed` sukses (100%).
     - `tests/Feature/MasterDataTest.php` dibuat (8 skenario uji).
     - `php artisan test`: 19/19 tests PASS (72 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R1 (Langkah 5): Antarmuka Master Data & Integrasi Dashboard
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Pembangunan Komponen Livewire Mobile-First & Desktop:
     - `KodeAnggaranIndex` (`/master/kode-anggaran`): Kartu pohon bertingkat di mobile dengan level badge, tombol buka/tutup cabang tap target >= 44px, format rupiah tanpa overflow horizontal. Tabel hirarkis M3 di desktop dengan pencarian instan, filter status, pengesahan budget (BR-2), dan modal tambah/edit pos.
     - `TahunAnggaranIndex` (`/master/tahun-anggaran`): Pengelolaan tahun buku, aktivasi satu tahun aktif, dan penutupan tahun. Kartu mobile & tabel desktop M3.
     - `UnitDivisiIndex` (`/master/unit-divisi`): Struktur organisasi Unit Sekretariat dan Divisi, pemetaan sumber dana default per divisi.
  2. Pembaruan Navigasi & Layout:
     - Rute terdaftar di `routes/web.php` dalam middleware `auth`.
     - Sidebar `layouts/app.blade.php`: Link aktif dengan Active Indicator Pill M3 dan auto-close mobile drawer pada navigasi.
     - Breadcrumb dinamis pada top bar mengikuti halaman yang dibuka.
  3. Integrasi Dashboard Eksekutif:
     - Menghubungkan Plafon RAB, Sisa Tersedia, dan jumlah node aktif ke data riil tahun aktif via `BudgetCalculator`.
  4. Verifikasi & Pengujian:
     - Pembuatan `tests/Feature/MasterUiTest.php` (6 skenario uji UI, depth limit, pengesahan, dan aktivasi).
     - Build asset Vite sukses (85.38 kB).
     - `php artisan test`: 25/25 tests PASS (91 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R1 (Langkah 6): Laporan Pengeluaran Bulanan & Penyesuaian Default Seeder
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Penyesuaian Data Awal:
     - `KodeAnggaranSeeder.php` dikosongkan agar database tidak memuat pos anggaran dummy secara default (sesuai arahan institusi).
     - Seluruh pengujian fitur (`MasterDataTest.php` dan `MasterUiTest.php`) menggunakan sample data terisolasi dalam pengujian masing-masing.
  2. Logika Backend Matriks Bulanan:
     - Penambahan method `getMonthlyExpenditureReport` pada `BudgetCalculator`: kalkulasi matriks 12 bulan (Januari s.d Desember), bottom-up rollup dari pos anak ke pos induk, kalkulasi sisa anggaran dan persentase serapan.
  3. Ekspor Excel:
     - Pembuatan kelas `LaporanPengeluaranExport` (`maatwebsite/excel`) dengan format tabel organisasi resmi (Kode, Pos, RAB, 12 Bulan, Total, Sisa, Serapan) dan penataan nomor moneter (#,##0).
  4. Antarmuka Mobile-First & Desktop:
     - Pembuatan komponen Livewire `LaporanPengeluaranIndex` (`/laporan/pengeluaran-bulanan`).
     - Mobile: 4 kartu ringkasan, kartu per pos dengan bar penyerapan, dan drawer rincian 12 bulan interaktif (tap target >= 44px).
     - Desktop: Tabel matriks horizontal dengan sticky columns dan tombol ekspor Excel.
  5. Navigasi Sidebar & Rute:
     - Menu `Monitoring & Laporan` di sidebar diaktifkan mengarah ke `/laporan/pengeluaran-bulanan` dengan active indicator pill.
  6. Verifikasi & Pengujian:
     - `tests/Feature/LaporanPengeluaranTest.php` dibuat (4 skenario uji termasuk ekspor Excel).
     - `php artisan migrate:fresh --seed` sukses dengan kode anggaran default kosong.
     - `npm run build` sukses (86.28 kB).
     - `php artisan test`: 29/29 tests PASS (109 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R1 (Langkah 7): Seeding Pos Anggaran Standar Zakat (Pos 2 & 2.4 Hak Kelola Amil)
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Inspeksi Node Awal: Menemukan node tingkat 1 yang telah dibuat user ('1' Penghimpunan dan '2' Penyaluran).
  2. Pemutakhiran `KodeAnggaranSeeder.php`:
     - Menggunakan `updateOrCreate` berbasis `tahun_anggaran_id` dan `kode` agar terhubung mulus tanpa duplikasi ke node eksisting.
     - Pos Induk Tingkat 1 & 2: `2` Penyaluran Dana ZIS & Operasional Amil, `2.1` Zakat, `2.2` Infak Kemanusiaan, `2.3` Infak Umum, `2.4` Penyaluran Dana Amil.
     - Pos Sub-Induk Tingkat 3: `2.4.1` Biaya Kepegawaian dan `2.4.2` Biaya Utilitas.
     - 19 Pos Leaf Tingkat 4 di bawah `2.4.1` (Gaji Amil Rp4,56M, BPJS Rp448Jt, Tunjangan Kesehatan Rp380Jt, THR Rp380Jt, dsb.) dengan total plafon Rp6.914.093.472.
     - 16 Pos Leaf Tingkat 4 di bawah `2.4.2` (Listrik, Internet, Akomodasi, Sarana PC/Laptop, IT Sistem, dsb.) dengan total plafon Rp1.192.861.681.
     - Total Plafon Terakumulasi `2.4`: Rp8.106.955.153 (Rollup otomatis via `BudgetCalculator`).
  3. Eksekusi Seeder: `php artisan db:seed --class=KodeAnggaranSeeder` sukses (total 43 pos anggaran aktif terdaftar).
  4. Verifikasi & Pengujian:
     - `php artisan test`: 29/29 tests PASS (109 assertions).
     - `npm run build`: sukses (86.28 kB).
     - Bebas karakter em dash.

### 2026-10-14 - R1 (Langkah 8): Manajemen Pengguna & Leveling (FR-AUTH-2, FR-AUTH-4)
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Pembangunan Komponen Livewire:
     - `App\Livewire\Master\UserIndex` (`/master/users`): Otorisasi terbatas untuk role admin (abort 403 jika bukan admin).
     - Pencarian komprehensif (nama, username, email, jabatan) dan filter (role, unit/divisi, leveling, status aktif).
     - Modal create & edit pengguna: nama, username unik (mendukung titik/karakter dinas), email unik, password terenkripsi bcrypt, role, divisi unit, leveling approval, jabatan, status kepegawaian, flag petugas keuangan input, status aktif.
     - Modal reset password cepat dan toggle status akun aktif/nonaktif dengan proteksi akun admin mandiri.
     - Pencatatan seluruh mutasi akun ke `AuditLogger`.
  2. Antarmuka Responsif:
     - Mobile (< 1024px): Kartu pengguna dengan avatar inisial, role chip, leveling pill, badge status, dan tombol aksi tap target >= 44px.
     - Desktop (>= 1024px): Tabel M3 terstruktur dengan sticky header dan penataan aksi cepat.
  3. Rute & Navigasi:
     - Rute `/master/users` didaftarkan dalam middleware `auth`.
     - Sidebar `layouts/app.blade.php`: Menu "Pengguna & Leveling" di bawah `PENGATURAN` (hanya tampil untuk admin) dengan active indicator pill.
  4. Verifikasi & Pengujian:
     - `tests/Feature/UserManagementTest.php` dibuat (6 skenario uji otorisasi, create, edit, toggle, dan reset password).
     - `npm run build`: sukses (88.81 kB).
     - `php artisan test`: 41/41 tests PASS (155 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R1 (Langkah 9): Viewer Audit Trail (FR-AUD)
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Pembangunan Komponen Livewire:
     - `App\Livewire\Master\AuditLogIndex` (`/master/audit-logs`): Otorisasi terbatas untuk role `admin` dan `keuangan` (abort 403 untuk selainnya).
     - Filter pencarian aksi, rentang tanggal (dari/sampai), aktor pengguna, dan pencarian teks.
     - Pagination 15 per halaman.
     - Modal interaktif Rincian Perubahan (Diff Viewer) menampilkan nilai `before` dan `after` dalam format yang jelas dan mudah dibaca.
  2. Antarmuka Responsif:
     - Mobile (< 1024px): Kartu timeline audit dengan nama aktor, avatar inisial, jam & tanggal WIB, badge aksi berwarna M3 (create=emerald, update=blue, nonaktif=amber), dan tombol buka rincian (tap target >= 44px).
     - Desktop (>= 1024px): Tabel M3 terstruktur dengan kolom Waktu, Aktor, Aksi, Entitas & ID, IP Address, dan tombol aksi.
  3. Navigasi & Rute:
     - Rute `/master/audit-logs` diaktifkan di `routes/web.php`.
     - Sidebar `layouts/app.blade.php`: Menu "Audit Trail" terhubung aktif dengan pill indikator M3.
  4. Verifikasi & Pengujian:
     - `tests/Feature/AuditLogUiTest.php` dibuat (6 skenario uji: guest redirect, unauthorized 403, admin/keuangan 200, rendering log, diff modal inspection).
     - `php artisan test`: 41/41 tests PASS (155 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 1): Fondasi Database, Model, Service Alur Permohonan & Motion One
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Integrasi Motion One:
     - Instalasi paket `motion` resmi.
     - Inisialisasi micro-animation di `resources/js/app.js` (stagger fade & scale pada card, livewire:navigated support, mematuhi `prefers-reduced-motion` dan antislop MOTION 1).
  2. Migrasi Database R2 (5 tabel):
     - `permohonan` (`no_pengajuan` unik, `unit_id`, `user_id`, `kode_anggaran_id`, `program`, `nama_kegiatan`, `tgl_mulai`, `tgl_selesai`, `sumber_dana_id`, `status` 10 enum, `total_diminta`, `total_disetujui`, `catatan_over`, `leveling_required_id`, `butuh_direksi`, `perlu_verifikasi_keuangan`, `submitted_at`, index).
     - `permohonan_items` (`permohonan_id`, `uraian`, `asnaf_id`, `program_utama_id`, `jumlah_diminta`, `jumlah_disetujui`).
     - `approvals` (`approvable_type/id`, `siklus`, `tahap`, `keputusan` 4 enum, `approver_id`, `signer_name`, `signer_jabatan`, `signed_at`, `recorded_by`, `catatan`, unique index morph+siklus+tahap).
     - `lampiran` (`attachable_type/id`, `jenis`, `path`, `original_name`, `mime`, `size`, `sha256`, `version`, `uploaded_by`).
     - `penomoran` (`jenis`, `unit_id`, `tahun`, `last_number`, unique index).
  3. Eloquent Models:
     - `Permohonan`, `PermohonanItem`, `Approval`, `Lampiran`, `Penomoran` beserta relasi lengkap.
  4. Service `PermohonanWorkflow`:
     - Penomoran otomatis format `{KODE_UNIT}-PMH-{TTTT}-{NNNN}` berbasis `lockForUpdate`.
     - `submit()`: Validasi leaf aktif & tahun aktif, hitung nominal, tentukan leveling required (Supervisor s.d Direksi), set status 'diajukan'.
     - `approve()`: Transaksi aman, evaluasi leveling & divisi, alur berjenjang (atasan -> kadiv -> direksi -> keuangan), pengisian nominal disetujui per item pada tahap keuangan yang mengikat anggaran (`dana_terikat`).
     - `requestRevision()`: Kembalikan status ke 'perlu_perbaikan' dengan siklus naik.
     - `reject()` dan `cancel()`.
  5. Integrasi `BudgetCalculator`:
     - Perhitungan `dana_terikat` pos leaf dari sum `total_disetujui` permohonan berstatus 'disetujui_keuangan' dan 'dicairkan'.
     - Rollup bottom-up ke node induk.
  6. Verifikasi & Pengujian:
     - Pembuatan `tests/Feature/PermohonanWorkflowTest.php` (4 skenario uji alur lengkap).
     - `npm run build`: sukses (88.89 kB CSS, 62.74 kB JS dengan motion).
     - `php artisan test`: 45/45 tests PASS (176 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 2): Antarmuka Permohonan Dana, Alur Approval Berjenjang & Cetak PDF Landscape
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Pembangunan Komponen Livewire:
     - `App\Livewire\Permohonan\PermohonanIndex` (`/permohonan`): Daftar permohonan, filter status, unit, search, tampilan kartu mobile-first dan tabel desktop M3.
     - `App\Livewire\Permohonan\PermohonanCreate` (`/permohonan/buat`): Header form sesuai form fisik Al-Azhar (Program, Nama Kegiatan, Divisi, Tanggal Kegiatan, Sumber Dana, Mata Anggaran leaf), kartu info sisa anggaran real-time (Plafon, Terikat, Realisasi, Sisa Tersedia) dengan deteksi over budget dan wajib catatan over (BR-8), tabel rincian multi-baris dinamis (uraian, asnaf wajib jika zakat, nominal requested), auto-kalkulasi total & leveling required.
     - `App\Livewire\Permohonan\PermohonanDetail` (`/permohonan/{id}`): Rincian lengkap dokumen, stepper linimasa approval M3, panel aksi persetujuan (Setuju, Perlu Perbaikan, Tolak, Batal), panel Mode Input Atas Nama Keuangan (FR-PRX) dengan pengisian jumlah disetujui per baris, identitas penanda tangan fisik keuangan, dan upload scan arsip bukti fisik.
  2. Cetak Dokumen PDF Landscape:
     - `App\Http\Controllers\PermohonanPdfController` (`/permohonan/{id}/cetak-pdf`): Engine dompdf murni tabel A4 Landscape.
     - Format persis dokumen fisik organisasi: Logo resmi Al-Azhar, alamat Head Office, No Pengajuan, judul "FORM PERMOHONAN DANA", tabel rincian per pos, baris akumulasi TERPAKAI, 3 kotak tanda tangan basah (Diperiksa Oleh, Disetujui Oleh Keuangan, Dibuat Oleh), dan footer hak cipta.
  3. Navigasi & Rute:
     - Rute `/permohonan`, `/permohonan/buat`, `/permohonan/{id}`, `/permohonan/{id}/cetak-pdf` didaftarkan dalam middleware `auth`.
     - Sidebar `layouts/app.blade.php`: Menu "Permohonan Dana" aktif dengan active indicator pill M3 dan auto-close mobile drawer.
  4. Verifikasi & Pengujian:
     - Pembuatan `tests/Feature/PermohonanUiTest.php` (6 skenario uji: render index/create, submit multi-baris validasi, render detail, aksi approval, cetak PDF).
     - `npm run build`: sukses (88.89 kB CSS, 62.74 kB JS).
     - `php artisan test`: 51/51 tests PASS (214 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 3): Penyesuaian Roster Jabatan & Alur Persetujuan IT, GA, Kelembagaan, Diklat
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Penyelarasan Roster Struktur Organisasi Riil Sekretariat:
     - `UserSeeder.php` diperbarui dengan 11 akun resmi:
       - Kepala Divisi Sekretariat / Dirops (`rahmatullah.sidik`, Kadiv / Level 3, Divisi DIR)
       - Manager Kelembagaan (`deden.salim`, Manager / Level 2, Divisi KLM)
       - Manager Humas, IT, dan GA (`manager.humas`, Manager / Level 2, Divisi HUM)
       - Manager Diklat Litbang (`manager.diklat`, Manager / Level 2, Divisi DKL)
       - Koordinator Sekretariat / Staf TU (`tu.sekretariat`, Supervisor / Level 1, Divisi DKL, is_petugas_input = true)
       - Koordinator General Affair (`koordinator.ga`, Supervisor / Level 1, Divisi HUM)
       - Koordinator IT (`koordinator.it`, Supervisor / Level 1, Divisi HUM)
       - Koordinator Kelembagaan (`koordinator.kelembagaan`, Supervisor / Level 1, Divisi KLM)
       - Koordinator Komunikasi (`koordinator.komunikasi`, Supervisor / Level 1, Divisi HUM)
       - Administrator IT (`admin`, Supervisor / Level 1, Divisi HUM, role admin)
       - Manajer Anggaran & Treasury (`rochadi.kohar`, Manager / Level 2, Divisi SEK, role keuangan)
  2. Penyesuaian Alur Persetujuan & Pemilihan Atasan:
     - Migrasi `2026_10_14_000016_add_atasan_id_to_permohonan_table.php` menambahkan kolom `atasan_id` pada permohonan.
     - `PermohonanCreate`: Dropdown interaktif "Pilih Atasan Langsung", auto-default ke Manager divisi pengaju (misal: pengaju IT/GA otomatis mengarah ke `Manager Humas, IT, dan GA`), dan auto-switch sumber dana ke Zakat (Amil) saat pos anggaran `2.4` dipilih.
     - `PermohonanWorkflow`: Alur bertingkat Koordinator -> Manager -> Kadiv -> Keuangan, serta shortcut otomatis pengajuan Manager langsung ke Kadiv (melewati Tahap 2).
     - `PermohonanDetail`: Otorisasi dinamis `canApprove` mengevaluasi hak atasan langsung, Manager divisi, Kadiv, dan Keuangan.
     - PDF Landscape: Menampilkan pejabat atasan pemeriksa di kotak DIPERIKSA OLEH.
  3. Verifikasi & Pengujian:
     - Pembuatan `tests/Feature/PermohonanItGaApprovalTest.php` (3 skenario uji alur end-to-end Koordinator IT/GA -> Manager Humas -> Kadiv -> Keuangan -> dana terikat di BudgetCalculator).
     - `npm run build`: sukses (92.38 kB CSS, 62.74 kB JS).
     - `php artisan test`: 54/54 tests PASS (234 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 4): Penguncian Keputusan T1 (Direksi = Kepala Divisi) & Penyelarasan Leveling 3 Tingkat
- **Aktor:** Tim Dev + Pemilik Proses
- **Aktivitas:**
  1. Penguncian Keputusan Bisnis T1:
     - Dikonfirmasi resmi bahwa Direksi = Kepala Divisi (Kepala Divisi Sekretariat merangkap Direktur Operasional / Dirops).
     - Tidak ada tahap otorisasi unit tambahan direksi di atas Kepala Divisi; seluruh pengajuan nominal di atas Rp10.000.000 disetujui langsung oleh Kepala Divisi / Dirops sebelum diteruskan ke Keuangan.
  2. Penyelarasan Leveling Menjadi 3 Tingkat:
     - Supervisor (urutan 1): Rp0 - Rp2.000.000.
     - Manager (urutan 2): Rp2.000.001 - Rp10.000.000.
     - Kepala Divisi / Direktur Operasional (urutan 3): Di atas Rp10.000.000 (tanpa batas atas).
     - Penghapusan jenjang buatan terpisah "Di atas Rp100 juta" di PRD dan Tech Spec.
  3. Penyederhanaan Alur Persetujuan:
     - Alur persetujuan berjenjang disederhanakan: Kadiv / Dirops adalah otorisasi unit tertinggi sebelum Keuangan.
     - Untuk pengajuan nominal besar (misal Rp150 juta): disetujui langsung oleh Kepala Divisi / Dirops (tahap 2 dilewati jika pengaju adalah Manager), lalu dilanjutkan ke Tahap Keuangan.
  4. Pembaruan Dokumen Spesifikasi:
     - `PRD.md`: Bagian 3 butir T1, tabel leveling Bagian 4, dan Lampiran C diperbarui.
     - `TECH-SPEC.md`: Bagian 5.4 Otorisasi, tabel Data Awal Leveling, dan Pertanyaan Teknis Terbuka (TT7) diperbarui.
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 5): Format Titik Pemisah Ribuan Otomatis pada Input Nominal
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Komponen Interaktif Format Nominal (Alpine.js):
     - `permohonan-create.blade.php`: Input rincian `jumlah_diminta` otomatis memformat angka dengan titik pemisah ribuan standar Indonesia (`Intl.NumberFormat('id-ID')`) saat diketik, sementara nilai yang disimpan ke Livewire dan database tetap bilangan bulat murni.
     - `kode-anggaran-index.blade.php`: Input `form_plafon` dilengkapi format titik pemisah ribuan otomatis dengan Alpine.js.
     - `permohonan-detail.blade.php`: Input `approvedItemAmounts` pada modal persetujuan Keuangan menerapkan format titik otomatis yang sama.
  2. Lapisan Pengaman Ganda Backend (PHP):
     - `PermohonanCreate.php`: Sanitasi input string dengan pembersih non-digit sebelum validasi dan penyimpanan draft/submit.
     - `KodeAnggaranIndex.php`: Sanitasi input `form_plafon` sebelum validasi dan penyimpanan pos anggaran.
     - `PermohonanDetail.php`: Sanitasi array `approvedItemAmounts` sebelum validasi persetujuan Keuangan.
  3. Verifikasi & Pengujian:
     - Penambahan unit test `test_can_submit_permohonan_with_dot_formatted_nominal` di `PermohonanUiTest.php`.
     - `npm run build`: sukses (93.92 kB CSS, 62.74 kB JS).
     - `php artisan test`: 55/55 tests PASS (238 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 6): Inspeksi Pos Detail (Lihat Satu-Satu) & Filter Multi-Kriteria Kode Anggaran
- **Aktor:** Tim Dev
- **Aktivitas:**
  1. Fitur Inspeksi Pos Anggaran Satu per Satu (Detail Modal M3):
     - `KodeAnggaranIndex.php`: Penambahan method `openDetailModal(int $id)` dan `closeDetailModal()`.
     - Dialog Detail M3 (`resources/views/livewire/master/kode-anggaran-index.blade.php`): Menyajikan metrik anggaran komprehensif (Plafon Pos, Total Alokasi Anak, Dana Terikat, Realisasi, Sisa Anggaran Tersedia), navigasi interaktif ke pos induk, daftar sub-pos anak langsung dengan plafonnya, keterangan peruntukan, dan pintasan aksi (Edit, Tambah Anak, Sahkan Budget).
     - Tombol akses "Lihat Detail" hadir di tampilan Mobile (kartu) dan Desktop (kolom aksi).
  2. Filter Multi-Kriteria & Pencarian Fleksibel:
     - Filter Kelompok Kode Angka (`prefixFilter`): Memfilter berdasarkan kelompok kode (misal `2.4`, `2.4.1`, `2.4.2`).
     - Filter Kisaran Nominal (`nominalFilter`): Kategori > Rp 100 Jt, Rp 10 Jt - Rp 100 Jt, < Rp 10 Jt, berplafon tertulis, atau tanpa plafon.
     - Filter Tingkat Kedalaman (`levelFilter`): Level 1 Induk s.d Level 4 Leaf.
     - Filter Pos Leaf (`leafOnly`): Memfilter khusus pos ujung hierarki yang dapat ditransaksikan.
     - Pencarian teks instan (`search`): Pencarian langsung kode spesifik (misal `2.4.1.1`, `2.4.1.2`) atau nama pos.
     - Tombol Reset Filter cepat untuk mengembalikan seluruh kriteria.
  3. Verifikasi & Pengujian:
     - Penambahan unit test `test_can_open_detail_modal_in_kode_anggaran`, `test_can_filter_kode_anggaran_by_prefix`, dan `test_can_filter_kode_anggaran_by_nominal` di `MasterUiTest.php`.
     - `npm run build`: sukses (94.57 kB CSS, 62.74 kB JS).
     - `php artisan test`: 58/58 tests PASS (249 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 7): Penyelarasan Struktur Hierarki Organisasi (Divisi membawahi Unit)
- **Aktor:** Tim Dev + Pemilik Proses
- **Aktivitas:**
  1. Penyelarasan Hierarki Organisasi Riil (Divisi Induk > Unit Anak):
     - `MasterDataSeeder.php` diperbarui:
       - 4 Divisi Induk (parent_id null, tipe divisi): `DIV-SEK` (Divisi Sekretariat), `DIV-HUM` (Divisi Humas, GA, dan IT), `DIV-KLM` (Divisi Kelembagaan), `DIV-DKL` (Divisi Diklat & Litbang).
       - 6 Unit Anak (parent_id ke Divisi Induk, tipe unit): `UNT-TU`, `UNT-IT`, `UNT-GA`, `UNT-KOM`, `UNT-KLM`, `UNT-DKL`.
       - Pembersihan data unit kode legacy (`SEK`, `DIR`, `KLM`, `HUM`, `DKL`).
  2. Pemetaan Ulang Akun Pengguna (`UserSeeder.php`):
     - Pimpinan dan Manager ditempatkan di tingkat Divisi Induk (`DIV-SEK`, `DIV-HUM`, `DIV-KLM`, `DIV-DKL`).
     - Koordinator dan staf pelaksana ditempatkan di tingkat Unit Anak (`UNT-TU`, `UNT-IT`, `UNT-GA`, `UNT-KOM`, `UNT-KLM`, `UNT-DKL`).
  3. Penyesuaian Komponen & Antarmuka (`UnitDivisiIndex.php` & Blade):
     - Default modal create: Tanpa parent = Tambah Divisi Induk (`tipe = 'divisi'`); Dengan parent = Tambah Unit Anak (`tipe = 'unit'`).
     - Judul antarmuka dan label tombol disesuaikan: "Master Divisi & Unit (Divisi membawahi Unit)".
     - Penataan tabel hierarkis: Divisi sebagai row induk utama dan Unit-unit sebagai sub-baris berjenjang.
  4. Penyesuaian Pengujian Fitur:
     - Seluruh tes disesuaikan dengan kode unit dan hierarki baru (`MasterDataTest`, `MasterUiTest`, `PermohonanUiTest`, `PermohonanWorkflowTest`, `PermohonanItGaApprovalTest`, `UserManagementTest`).
  5. Verifikasi & Pengujian:
     - `npm run build`: sukses (94.57 kB CSS, 62.74 kB JS).
     - `php artisan test`: 58/58 tests PASS (249 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 8): Penerapan Roster Lengkap Struktur Organisasi Lembaga (5 Divisi Pusat, 5 Divisi KPw, 13 Unit)
- **Aktor:** Tim Dev + Pemilik Proses
- **Aktivitas:**
  1. Pendaftaran Struktur Lengkap Divisi Induk & Unit Anak (`MasterDataSeeder.php`):
     - Divisi Kantor Pusat (5 Divisi Induk): `DIV-SEK` (Sekretariat), `DIV-KEU` (Keuangan), `DIV-FND` (Fundraising & Partnership), `DIV-PRG` (Program), `DIV-WKF` (Wakaf).
     - Divisi Kantor Perwakilan Wilayah (5 Divisi KPw): `DIV-KPW-JTG` (Jawa Tengah), `DIV-KPW-JTM` (Jawa Timur), `DIV-KPW-SSL` (Sulawesi Selatan), `DIV-KPW-SMT` (Sumatera Barat), `DIV-KPW-YOG` (Yogyakarta).
     - 13 Unit Kerja Anak di bawah Divisi masing-masing:
       - Sekretariat: `UNT-SEK-DIR`, `UNT-SEK-KLM`, `UNT-SEK-HUM`, `UNT-SEK-DKL`.
       - Keuangan: `UNT-KEU-DIR`, `UNT-KEU-KLR`, `UNT-KEU-AKN`, `UNT-KEU-TRM`.
       - Fundraising: `UNT-FND-DIR`, `UNT-FND-INT`, `UNT-FND-EKS`.
       - Program: `UNT-PRG-INT`.
       - Wakaf: `UNT-WKF-INT`.
  2. Pemetaan Lengkap Akun Pengguna & Pejabat Kunci (`UserSeeder.php`):
     - 18 Akun resmi didaftarkan lengkap sesuai departemen, unit kerja, peran, dan leveling persetujuan resmi.
     - Penambahan akun kepemimpinan: Direktur Keuangan, Manager Pengeluaran Keuangan, Direktur LAZ, Manager Fundraising Internal/Eksternal, Manager Program, Direktur Wakaf.
  3. Penyesuaian Pencarian Atasan Langsung & Otorisasi:
     - `PermohonanCreate`: Logika pencarian atasan langsung memprioritaskan Manager di unit pengaju, lalu meluas ke divisi induk atau unit se-divisi secara terurut.
  4. Verifikasi & Pengujian:
     - `npm run build`: sukses (94.57 kB CSS, 62.74 kB JS).
     - `php artisan test`: 58/58 tests PASS (249 assertions).
     - Bebas karakter em dash.

### 2026-10-14 - R2 (Langkah 9): Penyelarasan Kantor Perwakilan Wilayah (KPw) ke Divisi Fundraising & Partnership
- **Aktor:** Tim Dev + Pemilik Proses
- **Aktivitas:**
  1. Penyelarasan KPw ke Divisi Fundraising & Partnership:
     - Ditetapkan bahwa seluruh Kantor Perwakilan Wilayah (KPw Jateng, KPw Jatim, KPw Sulsel, KPw Sumbar, KPw Yogyakarta) berstatus sebagai Unit-Unit Operasional di bawah Divisi Fundraising & Partnership, bukan divisi terpisah.
     - Total Divisi Induk lembaga menjadi 5 Divisi Utama:
       1. Divisi Sekretariat (`DIV-SEK`)
       2. Divisi Keuangan (`DIV-KEU`)
       3. Divisi Fundraising & Partnership (`DIV-FND`)
       4. Divisi Program (`DIV-PRG`)
       5. Divisi Wakaf (`DIV-WKF`)
  2. Struktur Unit di bawah Fundraising & Partnership:
     - Membawahi Unit Fundraising Internal, Unit Fundraising Eksternal & Kemitraan, serta seluruh Kantor Perwakilan Wilayah daerah (KPw Jateng, KPw Jatim, KPw Sulsel, KPw Sumbar, KPw Yogyakarta).
  3. Pembaruan Dokumen Spesifikasi:
     - `PRD.md`: Bagian 1 Ringkasan Produk, Bagian 3 Status Keputusan, Bagian 6.2 (FR-MST-2), dan Lampiran A diselaraskan.
     - `TECH-SPEC.md`: Bagian 6.2 Master Data Struktur Organisasi diselaraskan dengan 5 Divisi Induk Utama dan penempatan KPw sebagai unit di bawah `DIV-FND`.
  4. Implementasi Seeder & Pembersihan Record Legacy:
     - `MasterDataSeeder.php`: Mendaftarkan unit KPw (`UNT-FND-KPW-JTG`, `UNT-FND-KPW-JTM`, `UNT-FND-KPW-SSL`, `UNT-FND-KPW-SMT`, `UNT-FND-KPW-YOG`) di bawah `DIV-FND`, serta menghapus entri divisi legacy `DIV-KPW-*`.
     - `UserSeeder.php`: Menghubungkan akun pimpinan KPw ke unit KPw masing-masing.
  5. Verifikasi & Pengujian:
     - `npm run build`: sukses (94.57 kB CSS, 62.74 kB JS).
     - `php artisan test`: 58/58 tests PASS (249 assertions).
     - Bebas karakter em dash.

Setiap perubahan di masa depan yang mengubah arsitektur atau business rules wajib dicatat di bawah ini dengan format:
- **Tanggal:**
- **Aturan yang Berubah:**
- **Alasan:**
- **Dampak Kode & Database:**
