# PRD — SIGMA E-Budgeting Lite

Versi 0.1 (draf untuk persetujuan) · 13 Oktober 2026 · Pemilik proses: Sekretariat LAZ Al Azhar

Dokumen ini hanya memuat kebutuhan produk (apa dan mengapa). Pilihan teknologi ada di `TECH-SPEC.md`. Label: **\[D\]** = Disepakati, **\[A\]** = Asumsi sementara (perlu konfirmasi), **\[T\]** = Terbuka (belum diputuskan).

## 1. Ringkasan Produk

SIGMA (PANTAU, KENDALIKAN, PUTUSKAN) adalah aplikasi web internal untuk mengendalikan anggaran melalui satu rantai proses yang terlacak:

**RAB / Kode Anggaran → Permohonan Dana → Persetujuan berjenjang → Pencairan (PPD) → Laporan UMP → Realisasi &amp; Monitoring**

SIGMA mencakup dua dokumen bertanda tangan basah (Form Permohonan dan Laporan UMP), pelacakan dana terikat dan realisasi, serta peringatan sisa anggaran di dalam aplikasi. SIGMA **bukan** sistem akuntansi, ERP, buku besar, maupun pencatatan penerimaan kas.

**Cakupan awal \[D\]:** Struktur organisasi dua tingkat di mana **Divisi adalah Induk (Parent) dan Unit adalah Anak (Child)**: `Divisi > Unit`. Lembaga memiliki 5 Divisi Utama: Divisi Sekretariat, Divisi Keuangan, Divisi Fundraising &amp; Partnership, Divisi Program, dan Divisi Wakaf. Seluruh Kantor Perwakilan Wilayah (KPw Jateng, KPw Jatim, KPw Sulsel, KPw Sumbar, KPw Yogyakarta) berstatus sebagai Unit-Unit Operasional di bawah Divisi Fundraising &amp; Partnership. Tujuan rilis pertama: dipakai penuh di lingkungan Sekretariat tanpa akun Keuangan; petugas input Sekretariat menjalankan tahap Keuangan atas nama Keuangan (FR-PRX). Akses Keuangan, Fundraising (termasuk KPw daerah), dan divisi/unit lain menyusul; rancangan siap menampung seluruh divisi tanpa perubahan struktur.

## 2. Latar Belakang, Tujuan, dan Metrik

**Masalah:** data anggaran dan realisasi tersebar di spreadsheet; rekonsiliasi manual; pimpinan sulit membaca kondisi anggaran; limit atau over budget terlambat diketahui; permohonan dana dan UMP diproses di atas kertas tanpa pelacakan posisi; riwayat perubahan sulit ditelusuri.

**Tujuan:**

1. Satu sumber data anggaran per tahun, bertingkat, dengan sisa yang selalu terhitung otomatis.
2. Permohonan dan UMP mengikuti kewenangan berdasarkan leveling nominal, dengan jejak persetujuan.
3. Anggaran tidak dipakai ganda oleh permohonan paralel (dana terikat dihitung).
4. Peringatan dini saat anggaran mendekati limit atau melewati batas.
5. Dokumen cetak (landscape dan portrait) dan Excel yang konsisten dengan layar.
6. Setiap perubahan penting dapat ditelusuri (siapa, kapan, apa).

**Bukan tujuan:** akuntansi/GL, pengadaan, inventori, penggajian, pajak, penerimaan kas, tanda tangan elektronik tersertifikasi, notifikasi email/WhatsApp (ditunda), integrasi sistem keuangan (ditunda).

**Metrik keberhasilan (target awal, perlu disepakati):**


| Metrik                      | Cara ukur                                                      | Target                          |
| --------------------------- | -------------------------------------------------------------- | ------------------------------- |
| Akurasi angka               | Selisih sistem vs hitung manual pada sampel                    | 0 selisih                       |
| Waktu baca kondisi anggaran | Pimpinan membuka dashboard                                     | &lt; 1 menit                    |
| Adopsi                      | Permohonan dan UMP yang diproses lewat sistem                  | Mendekati 100% pada periode uji |
| Keterlacakan                | Dokumen yang memiliki riwayat persetujuan lengkap + scan arsip | 100%                            |
| Over budget tak terdeteksi  | Kasus over yang tidak diberi peringatan                        | 0                               |


## 3. Status Keputusan

**Disepakati \[D\]**

- Anggaran bertingkat per tahun (1 → 1.1 → 1.1.1), kedalaman bervariasi per cabang; model plafon di induk, anak dialokasikan, ada Belum Dialokasikan.
- Permohonan/realisasi hanya pada node paling bawah (leaf).
- Kode disalin per tahun dengan tautan ke node tahun sebelumnya.
- Struktur organisasi dua tingkat: **Divisi adalah Induk (Parent) dan Unit adalah Anak (Child)**: `Divisi > Unit`. Terdapat 5 Divisi Utama lembaga: Divisi Sekretariat, Divisi Keuangan, Divisi Fundraising &amp; Partnership, Divisi Program, dan Divisi Wakaf. Seluruh Kantor Perwakilan Wilayah (KPw Jateng, KPw Jatim, KPw Sulsel, KPw Sumbar, KPw Yogyakarta) berstatus sebagai Unit-Unit Operasional di bawah Divisi Fundraising &amp; Partnership (bersama Unit Internal dan Eksternal). Divisi/Unit tidak berhubungan dengan kode anggaran; hubungan terjadi di dalam form Permohonan.
- Notifikasi hanya di dalam aplikasi.
- Tanda tangan basah dengan pendekatan hybrid (klik setuju di sistem + cetak + scan final sebagai arsip).
- Terikat vs Realisasi sesuai Bagian 5; Sisa tersedia = Budget − Realisasi − Terikat.
- Satu permohonan = satu kode anggaran (di header).
- Over budget: sistem memperingatkan dan tetap mengizinkan dengan catatan, tidak memblokir.
- Sumber dana: Zakat (Amil), Infak Umum, Infak Khusus (Kemanusiaan). Default: divisi selain Humas = Zakat (Amil); Humas = Infak Umum; dapat diubah manual.
- Asnaf wajib hanya bila sumber dana Zakat.
- Leveling dipakai sebagai batas nominal; nilai yang dinilai = total jumlah permohonan.
- Nomor PPD = nomor UMP, ditentukan Keuangan; ID pengajuan dibuat sistem.
- Selama Keuangan belum memiliki akun, petugas input Sekretariat mencatat tahap Keuangan atas nama Keuangan (Mode Input Atas Nama Keuangan); tanda tangan basah Keuangan tetap di dokumen fisik. Rincian pada FR-PRX.

**Asumsi sementara \[A\]** (berlaku sampai dikonfirmasi)

- A1. Celah rentang (2.000.001–2.099.999) masuk Manager; batas memakai nilai maksimum.
- A2. Di atas Rp100 juta: penyetuju tambahan dikonfigurasi Admin (Kepala Divisi atau Direktur Utama); bila belum ditetapkan, Kepala Divisi menjadi penyetuju terakhir sebelum Keuangan. Lihat T1.
- A3. Satu permohonan menghasilkan satu pencairan sebesar total jumlah disetujui.
- A4. Status Mendekati Limit dan Over dihitung dari Pemakaian Efektif = (Realisasi + Terikat) ÷ Budget; status Kurang Terserap dari Realisasi vs Target kumulatif.
- A5. Scan dokumen final wajib diunggah sebelum pencairan dicatat (Permohonan) dan sebelum UMP berstatus Selesai.
- A6. Lampiran: JPG, PNG, PDF; maksimum 5 MB per berkas; ukuran dan jumlah dapat dikonfigurasi.
- A7. Tanggal kegiatan boleh satu tanggal atau rentang; bulan terikat mengikuti tanggal mulai.
- A8. Penomoran PPD/UMP: Keuangan mengetik nomor, sistem menyarankan nomor berikutnya (pola NNNN/UMP/BB/TTTT), menjamin keunikan, urutan reset tiap tahun.
- A9. Tahap persetujuan Keuangan pada Permohonan ditandatangani fisik oleh Manager Keuangan; selama Mode Input Atas Nama Keuangan aktif, petugas input mencatatnya di sistem.
- A10. Leveling pada aturan PJ UMP: PJ berlevel Manager atau lebih tinggi daripada pengaju; tanda tangan PJ dapat dilakukan oleh pengaju.
- A11. Daftar Program Utama awal dibuat Admin dan dapat diubah; dipilih sekali di header (T4).
- A12. Dokumen cetak: Permohonan landscape, UMP portrait (penafsiran dari catatan awal).

**Terbuka \[T\]**

- T1. Terjawab: Direksi = Kepala Divisi (Kepala Divisi Sekretariat merangkap Direktur Operasional). Seluruh permohonan di atas Rp10 juta disetujui langsung oleh Kepala Divisi / Direksi, lalu diteruskan ke Keuangan.
- T2. Terjawab: Keuangan mengesahkan budget awal dan menyetujui Budget Revision.
- T3. Terjawab: kelipatan Rp1.000, ke terdekat, dapat dikonfigurasi.
- T4. Terjawab: Program pada header form permohonan diinput secara bebas (diketik), tidak dikunci ke daftar pilihan master.
- T5. Terjawab: Pencairan PPD memiliki dokumen cetak (portrait). Templat final menunggu contoh form fisik dari Keuangan.
- T6. Terjawab: tenggat UMP berupa pengaturan jumlah hari (usulan awal 14) yang dapat diubah atau dinonaktifkan Admin.
- T7. Terjawab: Realisasi diakui penuh sebesar total penggunaan; selisih di atas dana cair dicatat sebagai kekurangan dengan catatan wajib.
- T8. Terjawab: petugas input saat ini staf TU Sekretariat; kelak dipindahkan ke divisi Keuangan. Petugas tidak boleh mencatat dokumen yang ia ajukan sendiri (FR-PRX-5).
- T9. Terjawab: Nomor PPD diinput manual oleh staf TU mengikuti format dari Keuangan; retensi berkas digital permanen selama dokumen aktif.
- T10. Terjawab: Perlu verifikasi ulang oleh Keuangan untuk semua transaksi yang dicatat atas nama Keuangan setelah akun Keuangan aktif.

## 4. Pengguna, Role, dan Leveling

**Leveling bukan role.** Leveling = batas nominal kewenangan. Role = hak akses di sistem.


| Leveling                             | Rentang total permohonan                |
| ------------------------------------ | --------------------------------------- |
| Supervisor                           | Rp0 - Rp2.000.000                       |
| Manager                              | Rp2.000.001 - Rp10.000.000              |
| Kepala Divisi / Direktur Operasional | Di atas Rp10.000.000 (tanpa batas atas) |

Rentang disimpan sebagai pengaturan, bukan ditulis tetap. Staf tanpa leveling dapat mengajukan tetapi tidak dapat menyetujui.


| Role                               | Deskripsi                                                                                                                                                                                                         |
| ---------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Admin                              | Kelola user, master data, pengaturan                                                                                                                                                                              |
| Pengguna Unit                      | Pengaju; juga approver sesuai leveling dan divisinya                                                                                                                                                              |
| Keuangan                           | Manager Keuangan dan Kepala Keuangan: menyetujui, mengisi jumlah disetujui, mencatat PPD, menentukan nomor; melihat lintas unit. Belum memiliki akun pada rilis awal; fungsinya dijalankan Petugas Input (FR-PRX) |
| Petugas Input Keuangan (sementara) | Pengguna Sekretariat yang ditunjuk Admin; mencatat keputusan Keuangan atas nama Keuangan berdasarkan dokumen fisik bertanda tangan; hanya selama Mode Input Atas Nama Keuangan aktif                              |
| Viewer / Pimpinan                  | Hanya melihat dashboard, monitoring, laporan                                                                                                                                                                      |


**Matriks hak akses ringkas**


| Fitur                                       | Admin | Pengguna Unit       | Keuangan / Petugas Input | Viewer |
| ------------------------------------------- | :-----: | :-------------------: | :------------------------: | :------: |
| Kelola user, role, master                   | ✔     | –                   | –                        | –      |
| Kelola kode anggaran &amp; budget           | ✔     | –                   | ✔                        | –      |
| Mengesahkan budget                          | ✔     | –                   | ✔ \[T2\]                 | –      |
| Buat dan ajukan permohonan                  | –     | ✔                   | –                        | –      |
| Menyetujui tahap 1 / mengetahui tahap 2     | –     | ✔ sesuai leveling   | –                        | –      |
| Menyetujui permohonan, isi jumlah disetujui | –     | –                   | ✔                        | –      |
| Catat PPD dan nomor                         | –     | –                   | ✔                        | –      |
| Buat dan tanda tangani UMP                  | –     | ✔                   | ✔ (tahap keuangan)       | –      |
| Dashboard dan monitoring                    | ✔     | ✔ (lingkup sendiri) | ✔ (semua)                | ✔      |
| Laporan dan Excel                           | ✔     | ✔ (lingkup sendiri) | ✔                        | ✔      |
| Audit log                                   | ✔     | –                   | ✔                        | –      |


**Lingkup data:** Pengaju melihat dokumennya sendiri; approver melihat dokumen divisinya; Kepala Divisi melihat seluruh unitnya; Keuangan dan Viewer melihat semua unit. Otorisasi ditegakkan di sisi server, bukan hanya dengan menyembunyikan tombol.

## 5. Konsep dan Rumus


| Istilah               | Definisi                                                                         |
| --------------------- | -------------------------------------------------------------------------------- |
| Budget (RAB)          | Anggaran yang sudah disahkan pada sebuah node kode anggaran                      |
| Terikat               | Dana yang sudah disetujui Keuangan (dan/atau dicairkan) tetapi UMP belum selesai |
| Realisasi             | Total penggunaan dana pada UMP berstatus Selesai                                 |
| Sisa tersedia         | Budget − Realisasi − Terikat                                                     |
| Pemakaian Efektif (%) | (Realisasi + Terikat) ÷ Budget × 100%                                            |
| Serapan (%)           | Realisasi ÷ Budget × 100%                                                        |
| Target kumulatif      | Jumlah target bulanan Jan s.d. bulan terpilih (Rilis 5)                          |
| Variance              | Realisasi kumulatif − Target kumulatif pada periode yang sama                    |
| Belum Dialokasikan    | Plafon induk − jumlah budget anak langsung                                       |


**Momen uang \[D\]**


| Momen                                           | Perlakuan                                                                                                       |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Permohonan disetujui Keuangan                   | Terikat sebesar jumlah disetujui                                                                                |
| Dana dicairkan (PPD)                            | Tetap Terikat (uang muka belum berarti terpakai)                                                                |
| UMP Selesai                                     | Terikat dibebaskan; Realisasi = total penggunaan. Saldo deposit yang dikembalikan otomatis kembali menjadi sisa |
| Permohonan Ditolak/Dibatalkan sebelum pencairan | Tidak ada Terikat                                                                                               |


Permohonan yang masih dalam proses tidak mengikat dana, tetapi ditampilkan sebagai informasi Dalam Proses agar pengaju sadar ada pengajuan paralel.

**Status anggaran** (dievaluasi berurutan, satu node satu status; ambang dapat dikonfigurasi)


| Prioritas | Status          | Kondisi                                                                         |
| ---------: | --------------- | ------------------------------------------------------------------------------- |
| 1         | Over Budget     | Pemakaian Efektif &gt; 100%                                                     |
| 2         | Mendekati Limit | 80% – 100% (termasuk tepat 100%)                                                |
| 3         | Kurang Terserap | Realisasi &lt; 80% Target kumulatif; hanya jika target valid tersedia (Rilis 5) |
| 4         | Aman            | Selain di atas                                                                  |
| –         | Tanpa Budget    | Budget = 0 atau belum diisi; tidak ada pembagian nol                            |


Status induk ditampilkan bersama **status terburuk dari anak-anaknya** agar masalah tidak tersembunyi. Sisa, serapan, variance, dan status **selalu dihitung**, tidak pernah diinput manual.

## 6. Persyaratan Fungsional

Prioritas: **M** = wajib MVP, **S** = Rilis 5 / setelah MVP.

### 6.1 FR-AUTH — Login, User, dan Leveling (M)

- FR-AUTH-1: Login dengan username/email dan kata sandi; kata sandi tidak pernah tersimpan atau tampil polos; sesi kedaluwarsa otomatis; ganti kata sandi.
- FR-AUTH-2: Admin mengelola user: nama, leveling, email, username, divisi, unit, role, status aktif. Username dan email unik.
- FR-AUTH-3: User nonaktif tidak dapat login dan tidak dapat dipilih sebagai approver/PJ; dokumen berjalan miliknya menampilkan peringatan agar Admin mengalihkan.
- FR-AUTH-4: Pengaturan leveling (nama, rentang, urutan) dikelola Admin.
- FR-AUTH-5: Peran Keuangan didefinisikan sejak MVP karena tahap Keuangan bukan opsional, tetapi belum memiliki akun pada rilis awal. Tahap Keuangan dijalankan lewat Mode Input Atas Nama Keuangan (FR-PRX) dan tidak boleh dilewati otomatis.
- FR-AUTH-6: Pelacakan jabatan sesuai data awal Sekretariat dimasukkan saat tahap user, bukan pada tahap master.

*Kriteria penerimaan:* user hanya dapat membuka fitur sesuai role; user nonaktif ditolak login; percobaan akses URL fitur tanpa hak ditolak server.

### 6.2 FR-MST — Master Data (M)

- FR-MST-1 **Tahun anggaran**: status Draft, Aktif, Ditutup; satu tahun aktif pada satu waktu; tahun Ditutup read-only.
- FR-MST-2 **Divisi dan Unit**: struktur organisasi dua tingkat di mana **Divisi adalah Induk (Parent) dan Unit adalah Anak (Child)**: `Divisi > Unit`. Terdapat 5 Divisi Utama lembaga: Divisi Sekretariat, Divisi Keuangan, Divisi Fundraising &amp; Partnership, Divisi Program, dan Divisi Wakaf. Seluruh Kantor Perwakilan Wilayah (KPw Jateng, KPw Jatim, KPw Sulsel, KPw Sumbar, KPw Yogyakarta) berkedudukan sebagai Unit-Unit Operasional di bawah Divisi Fundraising &amp; Partnership. Struktur menyiapkan relasi `parent_id` agar penambahan divisi atau unit kelak tidak mengubah skema. Node dengan histori hanya dapat dinonaktifkan.
- FR-MST-3 **Sumber dana**: Zakat (Amil), Infak Umum, Infak Khusus (Kemanusiaan). Aturan default per divisi seperti pada Bagian 3.
- FR-MST-4 **Asnaf**: Fakir, Miskin, Amil, Muallaf, Riqab, Gharimin, Fisabilillah, Ibnu Sabil.
- FR-MST-5 **Program Utama**: daftar dikelola Admin \[A11\]; dipakai sebagai Program pada header permohonan.
- FR-MST-6: Seluruh master hanya dinonaktifkan, tidak dihapus, bila sudah dipakai.

### 6.3 FR-KDA — Kode Anggaran Bertingkat dan RAB (M; revisi = S)

- FR-KDA-1: Kode hierarkis (mis. 1, 1.1, 1.1.1) unik per tahun anggaran. Hubungan induk-anak disimpan eksplisit, bukan dibaca dari teks kode. Kedalaman maksimum berupa pengaturan global (usulan 4 level); kedalaman tiap cabang boleh berbeda.
- FR-KDA-2: Setiap node memiliki kode, nama, deskripsi, status aktif, dan budget (plafon).
- FR-KDA-3: Plafon induk opsional. Bila kosong, budget induk = jumlah budget anak. Bila terisi, jumlah budget anak tidak boleh melebihi plafon; selisih ditampilkan sebagai Belum Dialokasikan (bukan error).
- FR-KDA-4: Leaf dihitung dari ada tidaknya anak. Permohonan hanya dapat memilih leaf aktif pada tahun Aktif.
- FR-KDA-5: Menambah anak pada leaf yang sudah memiliki permohonan/UMP **ditolak** dengan pesan jelas.
- FR-KDA-6: Memindahkan node ke induk lain dilarang setelah ada transaksi; sebelum itu tercatat di audit log.
- FR-KDA-7: Menonaktifkan node tidak menghapus histori; node nonaktif tidak dapat dipilih; penonaktifan induk menonaktifkan seluruh keturunannya setelah konfirmasi.
- FR-KDA-8: Budget berstatus Draft bebas diedit; **Disahkan** mengunci. Perubahan sesudahnya hanya melalui Budget Revision.
- FR-KDA-9: Salin struktur dari tahun sebelumnya (tanpa budget) dengan tautan ke node asal; nilai tahun lalu tampil sebagai referensi.
- FR-KDA-10 (S): Budget Revision naik atau turun: alasan wajib, nilai lama dan baru, pengusul, penyetuju \[T2\], waktu. Revisi turun tidak boleh di bawah Realisasi + Terikat. Revisi anak yang membuat jumlah anak melebihi plafon induk ditolak atau memicu revisi induk dalam satu paket.
- FR-KDA-11: Tampilan pohon dapat dibuka/ditutup, menampilkan budget, Terikat, Realisasi, Sisa, Belum Dialokasikan, dan status per node.

*Kriteria penerimaan:* contoh Induk 100 jt dengan anak 60 jt dan 30 jt menampilkan Belum Dialokasikan 10 jt; menambah anak 50 jt ditolak; induk tanpa plafon dan anak 60 + 30 jt menampilkan budget 90 jt.

### 6.4 FR-PMH — Permohonan Dana (M)

**Header (tampilan form fisik):** (1) Program, (2) Nama kegiatan / Divisi, (3) Tanggal kegiatan (s.d), (4) Sumber dana, (5) Mata anggaran (kode anggaran leaf), (6) Tanggal form, (7) ID Pengajuan (sistem).

**Rincian (tabel form fisik):** No, Uraian, Asnaf, Program Utama, Jumlah Permohonan, Jumlah Disetujui Keuangan. Di bawah tabel: kolom **Terpakai** (akumulasi realisasi leaf saat form dicetak).

**Kotak tanda tangan fisik:** Diperiksa Oleh (tengah/kiri: Atasan/Kadiv), Disetujui Oleh (Keuangan), Dibuat Oleh (Pengaju).
**Aturan**

- FR-PMH-1: Pengaju mengisi dan menyimpan Draft; satu permohonan = satu kode anggaran.
- FR-PMH-2: Asnaf wajib bila sumber dana Zakat, opsional untuk infak. Jumlah permohonan &gt; 0. Minimal satu baris rincian.
- FR-PMH-3: Sumber dana terisi default menurut divisi dan dapat diubah.
- FR-PMH-4: Saat mengisi dan saat mengajukan, sistem menampilkan sisa tersedia pada kode anggaran terpilih. Bila total permohonan &gt; sisa tersedia: peringatan, catatan wajib, tetap dapat diajukan \[D\].
- FR-PMH-5: Total permohonan dihitung otomatis dan menentukan jenjang persetujuan.
- FR-PMH-6: Pengajuan ganda (klik berulang) tidak membuat dokumen atau persetujuan ganda.
- FR-PMH-7: Kondisi, aktor, dan perubahan angka mengikuti tabel status 7.1.
- FR-PMH-8: Setelah Diajukan, isi tidak dapat diubah kecuali dikembalikan ke Perlu Perbaikan.
- FR-PMH-9: Pembatalan oleh pengaju hanya sebelum pencairan; sesudahnya hanya oleh Keuangan dengan alasan.

**Jenjang persetujuan \[D\]**

1. Tahap 1 (Disetujui Atasan): approver dengan leveling minimal sesuai total permohonan, satu divisi dengan pengaju, bukan pengaju sendiri.
2. Tahap 2 (Diketahui Kepala Divisi): selalu ada, kecuali tahap 1 sudah dilakukan Kepala Divisi.
3. Tahap 3 (Disetujui Keuangan): mengisi jumlah disetujui per baris; menjadikan dana Terikat.
4. Di atas Rp100 juta: tahap tambahan oleh penyetuju yang ditetapkan di pengaturan, setelah tahap 2 dan sebelum Keuangan; bila belum ditetapkan, tahap ini tidak ada \[T1\]. Kebutuhan tahap ini ditetapkan saat diajukan dan tidak berubah meskipun pengaturan diganti di tengah proses.

Setiap keputusan menyimpan approver, waktu, catatan; penolakan dan permintaan perbaikan wajib beralasan. Satu tahap hanya dapat diputuskan sekali, dan hanya oleh pihak berwenang yang aktif.

### 6.5 FR-PCR — Pencairan PPD (M)

- FR-PCR-1: Setelah Disetujui Keuangan dan scan final terunggah \[A5\], Keuangan (atau Petugas Input atas nama Keuangan) mencatat pencairan: nomor PPD (custom \[A8\]), tanggal cair, jumlah (= total disetujui \[A3\]), bukti transfer opsional.
- FR-PCR-2: Nomor PPD unik; duplikat ditolak dengan pesan jelas. Nomor yang sudah dipakai tidak dapat dihapus, hanya dibatalkan dengan alasan.
- FR-PCR-3: Dana tetap Terikat setelah pencairan.
- FR-PCR-4: Pencairan menghasilkan kewajiban UMP; daftar UMP belum diserahkan, umurnya, dan jatuh tempo ditampilkan. Tenggat (hari) berupa pengaturan yang dapat dinonaktifkan; bila nonaktif, tidak ada jatuh tempo maupun notifikasi tenggat \[T6\].
- FR-PCR-5: Dokumen cetak pencairan (portrait): nomor PPD, tanggal cair, ID pengajuan, kode anggaran, kegiatan, rincian dan jumlah disetujui, total dicairkan, kotak tanda tangan Keuangan dan penerima \[T5; templat final menunggu form fisik\].

### 6.6 FR-UMP — Laporan UMP (M)

**Header (tampilan form fisik):** Judul laporan, No PPD / No Uang Muka, Tanggal Penerimaan Dana, Tujuan Pengajuan, Penanggung Jawab.

**Struktur tabel fisik:**
- **A. JUMLAH DITERIMA**: total dana dicairkan.
- **B. PENGGUNAAN DANA**: No, Tgl, Rincian Pengeluaran, **No Bukti** (teks: transfer, nota, kuitansi), Jumlah. Subtotal: Total Penggunaan Dana.
- **C. SALDO/DEFISIT (A - B)**.
- **D. Pembulatan Nominal**.

**Kotak tanda tangan fisik (4 kotak, 2 grup):**
- Grup Unit: Dibuat oleh (PJ), Diperiksa Oleh (Kepala Divisi/Sekretariat).
- Grup Divisi Keuangan: Pemeriksa (Manajer Anggaran & Treasury), Disetujui Oleh (Kepala Keuangan).

**Aturan**

- FR-UMP-1: Satu pencairan memiliki satu UMP.
- FR-UMP-2: Setiap baris penggunaan wajib memiliki tanggal, rincian, jumlah &gt; 0, dan minimal satu bukti (JPG/PNG/PDF) \[D\].
- FR-UMP-3: PJ wajib berleveling Manager atau lebih tinggi daripada pengaju; tanda tangan PJ dapat dilakukan pengaju \[A10\].
- FR-UMP-4: Tanda tangan berjenjang: PJ → Kepala Divisi → Manager Keuangan → Kepala Keuangan → Selesai.
- FR-UMP-5: Saat Selesai, Realisasi tercatat per baris penggunaan menurut bulan **tanggal penggunaan**, Terikat dibebaskan, saldo deposit kembali menjadi sisa.
- FR-UMP-6: Total penggunaan &gt; dana dicairkan: Realisasi diakui penuh sebesar total penggunaan; kekurangan ditandai dan wajib catatan \[T7 terjawab\].
- FR-UMP-7: Tanggal penggunaan tidak boleh di luar tahun anggaran permohonan dan tidak boleh sebelum tanggal pencairan tanpa catatan.
- FR-UMP-8: Scan UMP final wajib sebelum Selesai \[A5\].
- FR-UMP-9: UMP yang ditolak tahap mana pun kembali ke Perlu Perbaikan dengan alasan.

### 6.7 FR-LMP — Bukti dan Arsip (M)

- Unggah JPG, PNG, PDF; validasi jenis, ukuran, dan isi berkas; nama berkas dinormalkan.
- Berkas hanya dapat diakses pengguna berwenang dan tidak dapat dibuka lewat tautan publik.
- Berkas tidak dihapus bila dokumen sudah melewati Diajukan; penggantian menyimpan versi sebelumnya.
- Kegagalan simpan berkas tidak boleh menampilkan pesan sukses.

### 6.8 FR-CTK — Cetak Dokumen (M)

- Form Permohonan: **landscape**; memuat header, tabel rincian, total, kotak tanda tangan sesuai jenjang, ID pengajuan, dan status.
- Laporan UMP: **portrait**; memuat header, tabel penggunaan, total, saldo deposit, pembulatan, dan kotak tanda tangan PJ, Kepala Divisi, Manager Keuangan, Kepala Keuangan.
- Cetak menggunakan data yang sama dengan layar; dokumen berstatus Draft diberi tanda DRAFT.
- Tanda tangan basah di kertas; sistem menyimpan status klik setuju dan scan arsip \[D\].

### 6.9 FR-CAL — Perhitungan Anggaran (M)

- FR-CAL-1: Satu aturan perhitungan bersama dipakai dashboard, monitoring, detail, dan Excel.
- FR-CAL-2: Perhitungan per leaf, lalu diakumulasi ke induk; hasil induk = jumlah anak, kecuali plafon.
- FR-CAL-3: Realisasi per bulan menurut tanggal penggunaan UMP; Terikat per bulan menurut tanggal kegiatan.
- FR-CAL-4: Ambang 80% dan 100% dapat dikonfigurasi.
- FR-CAL-5: Setiap angka dapat ditelusuri (drill-down) hingga permohonan, PPD, dan UMP sumbernya.

### 6.10 FR-DSH — Dashboard dan Monitoring (M)

- Kartu: Total Budget Disahkan, Terikat, Realisasi, Sisa tersedia, Serapan.
- Grafik bulanan Budget/Target vs Terikat vs Realisasi.
- Ringkasan Budget Health (jumlah dan porsi per status); setiap status dapat diklik menjadi daftar.
- Panel Perlu Perhatian (node dengan status terburuk) dan Antrean Persetujuan Saya.
- Filter: tahun, bulan s.d., kode anggaran, sumber dana, divisi/unit. **Catatan:** karena divisi tidak berhubungan dengan kode anggaran, filter divisi hanya menampilkan Terikat dan Realisasi dari permohonan divisi tersebut, tanpa Budget dan Sisa.
- Label status berupa teks (tidak hanya warna); tampilan responsif; tertulis Data per tanggal/jam.
- Tabel monitoring: kode, nama, budget, terikat, realisasi, sisa, pemakaian, status; pencarian, urut, dan drill-down.

### 6.11 FR-RPT — Laporan dan Excel (M untuk butir 1–4)

1. Rekap anggaran per kode (pohon) per tahun/periode.
2. Rekap permohonan dan status persetujuan.
3. Rekap PPD dan UMP (termasuk UMP belum diserahkan).
4. Rekap realisasi per bulan dan per sumber dana/asnaf.
5. (S) Laporan exception: limit, over, kurang terserap; riwayat revisi.

Excel memakai data yang sama dengan layar setelah filter; nominal bertipe angka, tanggal bertipe tanggal; judul, periode, dan waktu cetak jelas.

### 6.12 FR-NTF — Notifikasi dalam Aplikasi (M)

Notifikasi saat: dokumen menunggu tindakan saya, dokumen saya disetujui/ditolak/perlu perbaikan, anggaran berubah status (Limit/Over), UMP mendekati atau melewati tenggat. Notifikasi muncul sekali per perubahan status, bukan berulang. Email/WhatsApp ditunda.

### 6.13 FR-AUD — Audit Trail (M untuk dasar, lengkap di S)

Mencatat siapa, kapan, aksi, entitas, nilai sebelum dan sesudah untuk: login gagal/berhasil penting, perubahan user/leveling, struktur dan budget, setiap keputusan persetujuan, pencairan, pembatalan, ekspor. Log tidak dapat diubah atau dihapus oleh pengguna mana pun. Audit dasar dimulai sejak Rilis 1 karena sulit ditambahkan belakangan.

### 6.14 FR-PRX — Mode Input Atas Nama Keuangan (M, sementara)

Tujuan: sistem dapat dipakai penuh di Sekretariat sebelum Keuangan memiliki akun. Petugas Input Sekretariat mencatat keputusan Keuangan atas nama Keuangan; tanda tangan Keuangan tetap dilakukan di dokumen fisik.

- FR-PRX-1 \[D\]: Pengaturan Mode Keuangan bernilai Input Atas Nama (aktif sekarang) atau Langsung (dipakai saat Keuangan punya akun). Hanya Admin yang dapat mengubahnya; perubahan tercatat di audit log.
- FR-PRX-2 \[D\]: Admin menunjuk satu atau lebih Petugas Input dari pengguna Sekretariat. Petugas hanya memperoleh fungsi tahap Keuangan: mengisi jumlah disetujui, mencatat persetujuan, menentukan nomor PPD/UMP, mencatat pencairan. Petugas tidak mendapat hak Admin.
- FR-PRX-3 \[A\]: Setiap tahap Keuangan yang dicatat wajib memuat nama dan jabatan penanda tangan Keuangan, tanggal tanda tangan pada dokumen fisik, dan scan dokumen bertanda tangan. Permohonan: scan wajib sebelum Disetujui Keuangan dicatat (menggantikan A5 selama mode ini aktif). UMP: scan final wajib sebelum Selesai.
- FR-PRX-4 \[D\]: Sistem merekam dua identitas, penanda tangan (Keuangan) dan pencatat (Petugas), ditampilkan sebagai dicatat oleh X atas nama Y pada riwayat, dokumen cetak, laporan, dan audit log. Tanggal keputusan = tanggal tanda tangan fisik; waktu input sistem tercatat terpisah.
- FR-PRX-5 \[A\]: Petugas tidak boleh mencatat tahap Keuangan untuk dokumen yang ia ajukan sendiri atau di mana ia menjadi PJ. Bila tidak ada petugas lain, Admin yang mencatat. Bila Admin, Petugas, dan pengaju orang yang sama, dokumen ditandai Perlu Tinjau Keuangan pada laporan exception.
- FR-PRX-6 \[A\]: Jumlah disetujui yang diisi Petugas harus sama dengan dokumen fisik; perubahan setelah dicatat hanya lewat Perlu Perbaikan atau pembatalan beralasan.
- FR-PRX-7 \[D\]: Saat beralih ke mode Langsung, dokumen berjalan menyelesaikan tahap berikutnya menurut mode pada saat tahap itu terjadi; riwayat lama tetap bertanda atas nama. Tidak ada migrasi data.
- FR-PRX-8: Kolom `perlu_verifikasi_keuangan` menandai dokumen yang dicatat atas nama Keuangan. Saat mode beralih ke Langsung, modul Keuangan menampilkan antrean verifikasi ulang dokumen masa lampau \[T10 terjawab\].

*Kriteria penerimaan:* hanya Petugas Input yang dapat mencatat tahap Keuangan; pencatatan tanpa scan atau tanpa tanggal tanda tangan ditolak; riwayat menampilkan pencatat dan penanda tangan; pengajuan oleh petugas sendiri ditolak atau ditandai sesuai FR-PRX-5; perubahan mode tercatat di audit log.

## 7. Status dan Transisi

### 7.1 Permohonan Dana


| Dari                    | Ke                      | Aktor            | Prasyarat                                           | Dampak                                |
| ----------------------- | ----------------------- | ---------------- | --------------------------------------------------- | ------------------------------------- |
| Draft                   | Diajukan                | Pengaju          | Validasi lengkap                                    | Tampil di Dalam Proses                |
| Diajukan                | Disetujui Atasan        | Approver tahap 1 | Leveling cukup, bukan pengaju                       | –                                     |
| Disetujui Atasan        | Diketahui Kepala Divisi | Kepala Divisi    | –                                                   | –                                     |
| Diketahui Kepala Divisi | Disetujui Keuangan      | Manager Keuangan | Jumlah disetujui terisi, catatan bila melebihi sisa | **Terikat**                           |
| Disetujui Keuangan      | Dicairkan               | Keuangan         | Scan final, nomor PPD unik                          | Terikat tetap                         |
| Dicairkan               | Selesai                 | Sistem           | UMP Selesai                                         | Terikat → Realisasi                   |
| Tahap mana pun          | Perlu Perbaikan         | Approver         | Alasan wajib                                        | Kembali ke pengaju; riwayat tersimpan |
| Perlu Perbaikan         | Diajukan                | Pengaju          | Revisi isi                                          | Jenjang diulang                       |
| Tahap mana pun          | Ditolak                 | Approver         | Alasan wajib                                        | Tidak ada Terikat                     |
| Sebelum Dicairkan       | Dibatalkan              | Pengaju/Keuangan | Alasan                                              | Terikat dilepas                       |


Pengajuan ulang setelah perbaikan menghitung ulang jenjang berdasarkan total terbaru. Selama Mode Input Atas Nama Keuangan aktif, setiap tahap Keuangan pada tabel ini dicatat oleh Petugas Input.

### 7.2 Laporan UMP

Draft → TTD PJ → TTD Kepala Divisi → TTD Manager Keuangan → TTD Kepala Keuangan → Selesai. Cabang: Perlu Perbaikan (alasan wajib), Dibatalkan (oleh Keuangan). Tahap TTD Manager Keuangan dan Kepala Keuangan dicatat oleh Petugas Input selama Mode Input Atas Nama Keuangan aktif. Realisasi baru diakui saat **Selesai**.

## 8. Aturan Bisnis (Ringkas)


| No    | Aturan                                                                                 |
| ----- | -------------------------------------------------------------------------------------- |
| BR-1  | Budget tidak negatif; budget terkait node aktif pada tahun yang sama                   |
| BR-2  | Budget yang sudah disahkan tidak diedit langsung                                       |
| BR-3  | Permohonan/UMP hanya pada leaf aktif tahun Aktif                                       |
| BR-4  | Sisa tersedia = Budget − Realisasi − Terikat                                           |
| BR-5  | Jumlah disetujui ≤ jumlah permohonan per baris                                         |
| BR-6  | Pengaju tidak boleh menyetujui permohonannya sendiri pada tahap yang sama              |
| BR-7  | Dokumen yang sudah Selesai/Dicairkan tidak dihapus; koreksi lewat pembatalan beralasan |
| BR-8  | Over budget memberi peringatan dan catatan, tidak memblokir                            |
| BR-9  | Nomor PPD/UMP unik; ID pengajuan oleh sistem                                           |
| BR-10 | Asnaf wajib bila sumber dana Zakat                                                     |
| BR-11 | Tahun Ditutup read-only                                                                |
| BR-12 | Approver nonaktif tidak dapat memutuskan; Admin mengalihkan dengan audit               |


## 9. Edge Case yang Harus Teruji


| Kasus                                                                   | Perilaku yang diharapkan                                                                                                    |
| ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Dua pengajuan paralel pada sisa yang sama                               | Keduanya boleh diajukan dengan peringatan; Terikat dihitung saat disetujui Keuangan; yang melampaui sisa memerlukan catatan |
| Dua approver menekan setuju bersamaan                                   | Hanya satu keputusan tercatat; yang lain diberi tahu sudah diputuskan                                                       |
| Klik Ajukan berulang                                                    | Satu dokumen, satu rantai persetujuan                                                                                       |
| Budget nol/kosong                                                       | Status Tanpa Budget; tidak ada pembagian nol                                                                                |
| Target kosong/tidak valid                                               | Tidak diberi status Kurang Terserap                                                                                         |
| Nominal 0 atau negatif                                                  | Ditolak                                                                                                                     |
| Total tepat Rp2.000.000 / Rp10.000.000 / Rp100.000.000                  | Jatuh ke level bawah (batas memakai nilai maksimum)                                                                         |
| Total Rp2.050.000 (celah rentang)                                       | Manager \[A1\]                                                                                                              |
| Permohonan diedit total setelah perbaikan hingga melewati batas nominal | Jenjang dihitung ulang                                                                                                      |
| Approver dinonaktifkan di tengah proses                                 | Dokumen tidak macet diam-diam; peringatan dan pengalihan oleh Admin                                                         |
| Nomor PPD duplikat                                                      | Ditolak                                                                                                                     |
| Total penggunaan &gt; dana cair                                         | Realisasi penuh; kekurangan ditandai dan wajib catatan                                                                      |
| Saldo deposit sisa                                                      | Terikat dilepas, sisa anggaran kembali                                                                                      |
| Menambah anak pada leaf bertransaksi                                    | Ditolak                                                                                                                     |
| Revisi budget turun di bawah Realisasi + Terikat                        | Ditolak                                                                                                                     |
| Unggah berkas bukan gambar/PDF atau terlalu besar                       | Ditolak dengan pesan jelas                                                                                                  |
| Berkas gagal tersimpan                                                  | Tidak ada pesan sukses                                                                                                      |
| Akses langsung ke bukti tanpa hak                                       | Ditolak                                                                                                                     |
| Tahun ditutup                                                           | Semua data read-only                                                                                                        |


## 10. Kebutuhan Data (Konseptual)


| Entitas                           | Isi utama                                                                                                        |
| --------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Tahun anggaran                    | tahun, status                                                                                                    |
| Kode anggaran                     | tahun, induk, kode, nama, plafon/budget, status, tautan node tahun lalu                                          |
| Budget revision                   | node, nilai lama/baru, alasan, pengusul, penyetuju, waktu                                                        |
| Unit/Divisi                       | nama, tipe, induk, status                                                                                        |
| Sumber dana, Asnaf, Program utama | nama, status                                                                                                     |
| Leveling                          | nama, batas bawah, batas atas, urutan                                                                            |
| User                              | nama, leveling, email, username, kata sandi ter-hash, divisi, unit, role, status                                 |
| Permohonan                        | ID pengajuan, program, kegiatan, divisi/unit, kode anggaran, tanggal kegiatan, sumber dana, status, catatan over |
| Rincian permohonan                | uraian, asnaf, jumlah diminta, jumlah disetujui                                                                  |
| Langkah persetujuan               | dokumen, urutan, approver, keputusan, waktu, catatan                                                             |
| Pencairan                         | nomor PPD, tanggal, jumlah, status                                                                               |
| UMP dan rincian                   | tanggal penerimaan, tujuan, PJ, total, saldo, pembulatan; baris tanggal, rincian, jumlah                         |
| Lampiran                          | jenis dokumen, ukuran, pengunggah, tertaut ke dokumen/baris                                                      |
| Penomoran                         | pola, urutan, periode                                                                                            |
| Notifikasi, Audit log             | sesuai FR-NTF dan FR-AUD                                                                                         |


Hasil perhitungan tidak disimpan sebagai input manual.

## 11. Kebutuhan Non-Fungsional


| Aspek          | Kebutuhan                                                                                                                                                         |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Keamanan       | Autentikasi, otorisasi server, validasi input, perlindungan serangan web umum, kata sandi ter-hash, rahasia di luar kode, pesan error tidak membocorkan informasi |
| Integritas     | Perubahan multi-langkah bersifat atomik; konsistensi saat dua pengguna bekerja bersamaan; tanpa penghapusan permanen atas data keuangan; audit append-only        |
| Kinerja        | Dashboard dan pohon anggaran dimuat dalam hitungan detik untuk ratusan node dan ribuan dokumen                                                                    |
| Ketersediaan   | Backup terjadwal dan uji pemulihan berkala; prosedur rollback rilis                                                                                               |
| Kegunaan       | Bahasa Indonesia; format Rupiah, tanggal, dan angka konsisten; empty, loading, error, dan success state; konfirmasi untuk aksi penting; responsif tablet/ponsel   |
| Aksesibilitas  | Status dengan label teks dan ikon                                                                                                                                 |
| Pemeliharaan   | Ambang, rentang leveling, kedalaman, ukuran berkas sebagai pengaturan                                                                                             |
| Kepatuhan      | Retensi bukti dan arsip mengikuti kebijakan organisasi \[T9\]                                                                                                     |
| Kompatibilitas | Dapat dijalankan dan dirawat tim developer pada shared hosting Biznet tanpa SSH (rincian di `TECH-SPEC.md`)                                                       |


## 12. Rilis dan Definisi Selesai


| Rilis | Isi                                                                                                              | Gerbang selesai                                             |
| ----- | ---------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| R0    | Konfirmasi T1, T2, T9, T10, wireframe, templat cetak (termasuk pencairan), contoh data uji                       | Keputusan tertulis; contoh hitung manual tersedia           |
| R1    | Login, user, leveling, master, kode anggaran bertingkat, budget dan pengesahan, audit dasar                      | AC FR-AUTH, FR-MST, FR-KDA lulus                            |
| R2    | Permohonan, jenjang persetujuan, Mode Input Atas Nama Keuangan, notifikasi, perhitungan Terikat, cetak landscape | Seluruh transisi 7.1 teruji, termasuk persetujuan bersamaan |
| R3    | Pencairan PPD dan cetaknya, UMP, bukti, penomoran, cetak portrait, Realisasi                                     | Terikat berubah menjadi Realisasi dengan benar              |
| R4    | Dashboard, monitoring, laporan, Excel, UMP belum diserahkan                                                      | Angka dashboard = Excel = hitung manual                     |
| R5    | Budget Revision, target bulanan dan status Kurang Terserap, audit lengkap                                        | Revisi tidak melanggar Realisasi + Terikat                  |


Perubahan dari rencana awal: perhitungan Terikat dimajukan ke R2 karena dibutuhkan alur persetujuan; Budget Revision tetap R5 namun dicatat sebagai risiko (lihat 13).

Suatu rilis dinyatakan selesai bila implementasi, validasi, hak akses, pengujian, dan dokumentasi terkait telah diperiksa. Pengujian yang belum dijalankan tidak boleh diklaim lulus.

## 13. Risiko


| Risiko                                                                                 | Mitigasi                                                                                                                           |
| -------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Spreadsheet paralel tetap dipakai                                                      | Migrasi data awal; sistem sebagai sumber tunggal; pendampingan                                                                     |
| Scan arsip tidak disiplin                                                              | Wajib sebelum pencairan/Selesai \[A5\]                                                                                             |
| Petugas input menyetujui dokumen yang ia ajukan sendiri (kontrol lemah tanpa Keuangan) | Scan bertanda tangan Keuangan wajib; tercatat atas nama; petugas bukan pengaju bila memungkinkan; masuk laporan exception (FR-PRX) |
| UMP terlambat sehingga dana terikat lama                                               | Tenggat dapat dikonfigurasi, daftar UMP belum diserahkan, notifikasi harian \[T6\]                                                 |
| RAB perlu berubah sebelum R5                                                           | Pertimbangkan memajukan Budget Revision sederhana ke R4                                                                            |
| Leveling dan batas nominal salah                                                       | Rentang dapat dikonfigurasi; tinjau dengan pemilik proses                                                                          |
| Alert fatigue                                                                          | Notifikasi hanya saat status berubah                                                                                               |
| Scope creep ke ERP                                                                     | Pegang batasan Lite                                                                                                                |
| Data awal tidak bersih (mis. duplikasi jabatan)                                        | Validasi dan rekonsiliasi saat memasukkan user                                                                                     |


## 14. Lampiran

**A. Data Divisi/Unit awal:** Struktur dua tingkat (`Divisi > Unit`): 5 Divisi Utama (Divisi Sekretariat, Divisi Keuangan, Divisi Fundraising &amp; Partnership, Divisi Program, Divisi Wakaf). Di bawah Divisi Sekretariat terdapat Unit TU, Unit IT, Unit GA, Unit Komunikasi, Unit Kelembagaan, Unit Diklat. Di bawah Divisi Fundraising &amp; Partnership terdapat Unit Internal, Unit Eksternal, serta seluruh Kantor Perwakilan Wilayah (KPw Jateng, KPw Jatim, KPw Sulsel, KPw Sumbar, KPw Yogyakarta). Daftar jabatan dan status kepegawaian (Tetap/Kontrak/Relawan) dimasukkan pada tahap user.

**B. Contoh perhitungan:** Budget leaf Rp50 jt. Permohonan disetujui Rp20 jt, Terikat 20 jt, Sisa 30 jt, Pemakaian Efektif 40%. UMP Selesai dengan penggunaan Rp18 jt: Terikat 0, Realisasi 18 jt, Sisa 32 jt, Serapan 36%. Saldo deposit 2 jt kembali menjadi sisa.

**C. Contoh jenjang:** total Rp1,5 jt -> tahap 1 Supervisor ke atas, tahap 2 Kepala Divisi / Dirops, tahap 3 Keuangan. Total Rp8 jt -> tahap 1 Manager ke atas, tahap 2 Kepala Divisi / Dirops, tahap 3 Keuangan. Total Rp25 jt -> tahap 1 langsung Kepala Divisi / Dirops (tahap 2 dilewati), lalu Tahap 3 Keuangan. Total Rp150 jt -> tahap 1 langsung Kepala Divisi / Dirops (tahap 2 dilewati), lalu Tahap 3 Keuangan [T1].