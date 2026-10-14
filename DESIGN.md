# DESIGN: SIGMA LAZ Al-Azhar (Material Design 3)

Panduan sistem desain SIGMA berbasis **Google Material Design 3 (Material You)** yang diadaptasi untuk aplikasi web pengelolaan anggaran institusional LAZ Al Azhar.

## 1. Prinsip Utama Material 3 untuk SIGMA

1. **Tonal Palette Berbasis Brand:** Warna diturunkan dari benih (*seed*) logo resmi SIGMA: Navy Kelembagaan (`#0F2F57`) dan Emerald Zakat (`#006C4C`).
2. **Elevasi Berbasis Warna Permukaan (*Tonal Surface*):** Menghindari bayangan hitam tebal (*drop shadow*) ala template generik. Kedalaman dicapai melalui tingkatan `surface-container` (Lowest, Low, Base, High, Highest).
3. **Bentuk Terstruktur (*M3 Shape Scale*):**
   - Extra Small (4px): Badge kecil, tooltip
   - Small (8px): Filter chip, suggestion chip
   - Medium (12px): Input field, menu popup
   - Large (16px): Card, modal dialog, panel utama
   - Extra Large (28px): Navigasi kontainer, drawer
   - Full (9999px): Tombol utama, active indicator pill
4. **Hierarki Tipografi M3:** Menggunakan skala Display, Headline, Title, Body, dan Label dengan angka tabular (`tabular-nums`) untuk data keuangan.

## 2. Sistem Warna Material 3 (Color Tokens)

### Primary (Navy Kelembagaan Al-Azhar)
- **Primary:** `#0F2F57` (Tombol utama, judul utama, brand mark)
- **On Primary:** `#FFFFFF` (Teks di atas Primary)
- **Primary Container:** `#DCE5F6` (Latar elemen aktif, active pill, seleksi)
- **On Primary Container:** `#001B3E` (Teks di atas Primary Container)

### Secondary (Slate Netral Terstruktur)
- **Secondary:** `#485F7D` (Aksen pendukung, ikon sekunder)
- **On Secondary:** `#FFFFFF`
- **Secondary Container:** `#E7EEF8`
- **On Secondary Container:** `#031D36`

### Tertiary (Emerald Zakat Al-Azhar)
- **Tertiary:** `#006C4C` (Status aman, serapan sehat, indikator positif)
- **On Tertiary:** `#FFFFFF`
- **Tertiary Container:** `#D0F7E3`
- **On Tertiary Container:** `#002114`

### Error & Warning (Status Anggaran)
- **Error:** `#BA1A1A` (Status Over Budget, validasi gagal)
- **On Error:** `#FFFFFF`
- **Error Container:** `#FFDAD6`
- **On Error Container:** `#410002`
- **Warning Container (Limit 80%–100%):** `#FEF3C7` (Amber-100) / Teks `#92400E`

### Surface & Surface Containers
- **Surface:** `#F8FAFC` (Latar belakang utama aplikasi)
- **On Surface:** `#171C22` (Teks primer)
- **On Surface Variant:** `#424750` (Teks sekunder / label)
- **Surface Container Lowest:** `#FFFFFF` (Latar kartu berbingkai / form card)
- **Surface Container Low:** `#F1F5F9` (Latar kartu sekunder / panel bantuan)
- **Surface Container:** `#E9EFF6` (Latar netral kontainer)
- **Surface Container High:** `#E0E7F0` (Komponen elevasi 2)
- **Surface Container Highest:** `#D5DFEB` (Hover state pada list/table)
- **Outline:** `#727782` (Border input field saat idle)
- **Outline Variant:** `#CBD5E1` (Border pemisah halus antar komponen)

## 3. Komponen Inti Material 3

### Outlined Text Field
- Kontainer tinggi minimum 48px (standar aksesibilitas M3 touch target)
- Sudut lengkung 12px (`rounded-md-md` / `rounded-xl`)
- Border 1px `border-md-outline` pada kondisi netral
- Border 2px `border-md-primary` pada kondisi fokus (*State Layer Focus*)
- Teks label di atas input dengan ukuran `text-xs font-medium text-md-on-surface-variant`

### Buttons (M3 Filled & Tonal)
- **Filled Button:** Latar `bg-md-primary`, teks `text-md-on-primary`, sudut `rounded-full`, tinggi minimum 44px. Digunakan untuk aksi utama seperti "Masuk ke Sistem", "Ajukan Permohonan".
- **Tonal Button:** Latar `bg-md-primary-container`, teks `text-md-on-primary-container`, sudut `rounded-full`. Digunakan untuk aksi sekunder.
- **Outlined Button:** Border 1px `border-md-outline`, teks `text-md-primary`, sudut `rounded-full`. Digunakan untuk batal atau filter.

### Cards (M3 Outlined & Filled)
- **Outlined Card:** Latar `bg-md-surface-container-lowest` (Putih), border 1px `border-md-outline-variant`, sudut 16px (`rounded-2xl`).
- **Elevated Card:** Latar `bg-md-surface-container-low`, bayangan halus elevasi 1, sudut 16px.

### Navigation Bar M3
- Ketinggian 64px, posisi lengket (*sticky*)
- Tombol navigasi aktif menggunakan **Active Indicator Pill** M3: kapsul dengan latar `bg-md-primary-container` dan teks `text-md-on-primary-container font-semibold`.

### Chips (Assist, Suggestion, Filter)
- Tinggi 32px, sudut `rounded-lg` (8px), border halus `border-md-outline-variant`, teks `text-xs font-medium`.

## 4. Dials Antislop
- **ENERGY:** 1 (Calm, presisi, kelembagaan terpercaya)
- **RHYTHM:** 2 (Variasi terstruktur sesuai komponen M3)
- **MOTION:** 1 (Transisi halus pada hover, active, dan focus state layers; tanpa animasi mengambang/floating yang tidak perlu)
