# Adidas-Inspired Website Design Style Reference

> **Tujuan dokumen:** menjadi referensi visual dan UI/UX untuk membangun website dengan karakter tegas, sporty, editorial, modern, dan berorientasi pada konten seperti website adidas.
>
> **Catatan:** gunakan prinsip desainnya, bukan menyalin identitas, logo, aset, foto, atau tampilan adidas secara identik. Semua ukuran dan token pada dokumen ini adalah rekomendasi implementasi berdasarkan pengamatan terhadap website adidas Indonesia dan dokumentasi resmi adidas Design Language/YARN.
>
> **Tanggal observasi:** 29 Juli 2026.

---

## 1. Ringkasan Karakter Desain

Desain website adidas memiliki karakter utama berikut:

- **Monokrom dan sangat kontras:** hitam, putih, dan abu-abu menjadi fondasi UI.
- **Typography-led:** judul besar, tebal, sering menggunakan huruf kapital.
- **Product/content first:** visual produk dan campaign menjadi pusat perhatian.
- **Tegas dan geometris:** sudut tajam, garis tipis, serta sangat sedikit rounded corner.
- **Editorial tetapi tetap fungsional:** tampil seperti majalah olahraga/fashion, tetapi navigasi dan tindakan pengguna tetap jelas.
- **Aksen warna berasal dari foto:** UI tetap netral sehingga warna produk dan campaign menjadi lebih menonjol.
- **Kepadatan informasi terkontrol:** banyak informasi, tetapi dibagi menggunakan grid, hierarchy, dan spacing yang konsisten.

### Kata kunci visual

`Bold` · `Sporty` · `Editorial` · `High Contrast` · `Minimal` · `Direct` · `Dynamic` · `Product-focused`

---

## 2. Prinsip Desain Utama

### 2.1 Berani, bukan dekoratif

Gunakan ukuran teks, komposisi, dan crop gambar untuk menciptakan energi. Hindari dekorasi yang tidak memiliki fungsi.

### 2.2 Konten harus memimpin

Foto, nama produk, data penting, dan CTA harus lebih dominan daripada ornamen antarmuka.

### 2.3 Satu aksi utama per area

Setiap hero, campaign card, panel, atau modal sebaiknya memiliki satu CTA utama yang paling jelas.

### 2.4 Kontras membentuk hierarchy

Gunakan perbedaan berikut untuk membedakan tingkatan informasi:

- Hitam vs putih.
- Bold vs regular.
- Uppercase vs sentence case.
- Ukuran besar vs kecil.
- Full-bleed image vs area kosong.
- Garis solid vs tanpa border.

### 2.5 Bentuk harus terasa presisi

Elemen UI sebaiknya tampak terukur dan disiplin:

- Border tipis.
- Grid lurus.
- Alignment kuat.
- Radius kecil atau tanpa radius.
- Icon sederhana dan konsisten.

---

## 3. Design Tokens

## 3.1 Palet Warna

### Warna inti

| Token | Nilai | Penggunaan |
|---|---:|---|
| `--color-black` | `#000000` | Teks utama, tombol utama, header gelap |
| `--color-white` | `#FFFFFF` | Latar utama, teks di atas hitam |
| `--color-gray-50` | `#F7F7F7` | Latar section ringan |
| `--color-gray-100` | `#ECEFF1` | Latar foto produk dan card |
| `--color-gray-200` | `#E3E5E5` | Divider dan border halus |
| `--color-gray-400` | `#B7B7B7` | State disabled |
| `--color-gray-600` | `#767677` | Teks sekunder dan metadata |
| `--color-gray-900` | `#181818` | Alternatif hitam untuk permukaan gelap |

### Warna status opsional

| Token | Nilai | Penggunaan |
|---|---:|---|
| `--color-success` | `#1E7A46` | Status berhasil/aktif |
| `--color-warning` | `#C56A00` | Peringatan |
| `--color-danger` | `#D71920` | Error dan aksi berbahaya |
| `--color-info` | `#0068B5` | Informasi atau link kontekstual |

### Aturan penggunaan warna

1. Pertahankan sekitar **80–90% interface dalam warna netral**.
2. Gunakan warna aksen hanya untuk status, data penting, atau campaign.
3. Jangan menggunakan banyak warna aksen secara bersamaan.
4. Jangan menjadikan gradient sebagai fondasi desain.
5. Untuk gambar campaign, biarkan foto menjadi sumber warna utama.

---

## 3.2 Typography

Dokumentasi adidas YARN membedakan font heading dan font dasar. Untuk implementasi yang mudah dan legal digunakan, gunakan:

- **Heading:** `Poppins`, `Arial Narrow`, atau sans-serif condensed lain.
- **Body/UI:** `Roboto`, `Arial`, `Helvetica`, sans-serif.
- **Monospace/data teknis:** `Roboto Mono` atau `IBM Plex Mono`.

> Gunakan font resmi adidas hanya apabila proyek memiliki lisensi dan izin penggunaannya.

### Skala typography rekomendasi

| Style | Desktop | Mobile | Weight | Case | Line height |
|---|---:|---:|---:|---|---:|
| Display | 64 px | 40 px | 800–900 | Uppercase | 0.95–1.0 |
| H1 | 48 px | 32 px | 800 | Uppercase | 1.05 |
| H2 | 36 px | 28 px | 800 | Uppercase | 1.1 |
| H3 | 28 px | 22 px | 700 | Uppercase/Title | 1.15 |
| H4 | 22 px | 19 px | 700 | Title | 1.2 |
| Body Large | 18 px | 17 px | 400 | Sentence | 1.5 |
| Body | 16 px | 16 px | 400 | Sentence | 1.5 |
| Body Small | 14 px | 14 px | 400 | Sentence | 1.45 |
| Label/Nav | 13 px | 13 px | 700 | Uppercase | 1.2 |
| Caption | 12 px | 12 px | 400–600 | Sentence | 1.35 |

### Aturan typography

- H1 dan H2 boleh dibuat uppercase untuk menghasilkan kesan kuat.
- Gunakan letter spacing kecil pada judul besar: `-0.02em` sampai `0`.
- Gunakan letter spacing lebih lebar pada label/menu: `0.04em` sampai `0.08em`.
- Batasi body text sekitar `60–75ch` agar nyaman dibaca.
- Hindari terlalu banyak jenis weight dalam satu layar.
- Gunakan bold untuk menyampaikan hierarchy, bukan untuk semua teks.

---

## 3.3 Spacing System

Gunakan kelipatan dasar 4 px.

```text
4, 8, 12, 16, 24, 32, 48, 64, 80, 96, 128
```

### Rekomendasi pemakaian

| Konteks | Spacing |
|---|---:|
| Jarak icon dengan label | 8 px |
| Padding input kecil | 12 px 16 px |
| Padding tombol utama | 14–16 px 24–32 px |
| Gap isi card | 8–16 px |
| Gap antar-card | 1–24 px, tergantung tipe grid |
| Padding section mobile | 40–64 px |
| Padding section desktop | 64–96 px |
| Jarak heading ke konten | 24–32 px |
| Margin antarseksi besar | 80–128 px |

---

## 3.4 Grid dan Container

### Desktop

- Gunakan **12-column grid**.
- Maksimum lebar konten: `1440px` atau `1600px`.
- Padding halaman: `24–48px`.
- Gutter: `16–24px`.
- Campaign card dapat menggunakan 3 atau 4 kolom.
- Product listing dapat menggunakan 3 atau 4 kolom.

### Tablet

- Gunakan 8-column grid.
- Padding halaman: `24px`.
- Grid card: 2–3 kolom.

### Mobile

- Gunakan 4-column grid.
- Padding halaman: `16px`.
- Gutter: `12–16px`.
- Product card dapat tetap 2 kolom untuk katalog, tetapi 1 kolom untuk konten editorial.

### Full-bleed section

Hero, campaign imagery, atau banner besar dapat menyentuh sisi viewport. Isi teks tetap mengikuti safe area agar tidak terlalu dekat dengan tepi layar.

---

## 3.5 Border, Radius, dan Shadow

### Border

- Default: `1px solid #000000` untuk elemen aktif/tegas.
- Subtle: `1px solid #E3E5E5` untuk divider dan container.
- Focus: `2px solid #000000` dengan offset 2–3 px.

### Radius

- Default: `0px`.
- Form/input opsional: `0–2px`.
- Hindari radius besar seperti `16px`, `24px`, atau bentuk pill kecuali chip/filter memang membutuhkannya.

### Shadow

Gunakan sangat sedikit shadow. Untuk CTA khas editorial, gunakan efek offset tegas.

```css
box-shadow: 4px 4px 0 #ffffff;
```

Pada latar putih:

```css
box-shadow: 4px 4px 0 #000000;
```

Jangan menggunakan shadow blur yang lembut pada setiap card.

---

## 4. Anatomi Halaman

## 4.1 Utility Bar

Bar tipis paling atas untuk informasi sekunder seperti:

- Store finder.
- Bantuan.
- Pelacakan pesanan.
- Membership.
- Promo atau benefit pengiriman.

### Spesifikasi

- Tinggi: `28–36px`.
- Font: `10–12px`, uppercase atau semibold.
- Alignment: kanan pada desktop.
- Latar: hitam atau putih, tergantung hierarchy header.
- Jangan menaruh terlalu banyak informasi.

---

## 4.2 Main Header

Struktur desktop:

```text
Logo | Menu utama | Search | Account | Wishlist | Cart
```

### Karakter

- Tinggi sekitar `64–80px`.
- Latar putih.
- Border bawah tipis.
- Logo dan menu memiliki ruang yang cukup.
- Menu menggunakan uppercase dan weight 600–700.
- Search terlihat sebagai tindakan penting, bukan sekadar icon tersembunyi.

### Mobile

```text
Menu | Logo | Search | Cart
```

Gunakan drawer atau full-screen navigation. Jangan memadatkan seluruh menu desktop ke dalam layar kecil.

---

## 4.3 Hero Section

Hero adidas biasanya mengandalkan visual campaign berukuran besar dengan copy yang singkat.

### Struktur

```text
[Full-width campaign image/video]
    Eyebrow opsional
    Heading tebal
    Deskripsi 1–2 baris
    Primary CTA
    Secondary CTA opsional
```

### Aturan

- Gunakan copy singkat dan langsung.
- Maksimum 2 CTA.
- CTA harus kontras dengan background.
- Posisi teks dapat kiri bawah, kiri tengah, atau berada pada blok di bawah gambar.
- Crop gambar harus tetap menunjukkan subjek/produk secara jelas pada semua breakpoint.
- Gunakan overlay hanya bila benar-benar diperlukan untuk keterbacaan.

### Rasio gambar

- Desktop hero: `16:7`, `16:8`, atau full viewport.
- Mobile hero: `4:5` atau `3:4`.

---

## 4.4 Discovery Rail / Category Carousel

Digunakan untuk membantu pengguna menemukan kategori atau lini produk dengan cepat.

### Struktur item

```text
Thumbnail
Label singkat
```

### Aturan

- Gunakan horizontal scroll di mobile.
- Pertahankan indikator arah yang jelas.
- Label maksimal 1–2 kata.
- Jangan menyembunyikan semua item di dalam carousel bila jumlahnya sedikit.
- Gunakan ukuran thumbnail yang konsisten.

---

## 4.5 Campaign Card

Card campaign lebih editorial daripada card aplikasi biasa.

### Struktur

```text
Image 4:5 atau 3:4
Heading pendek
Supporting copy 1 baris
Text link / CTA
```

### Aturan visual

- Gambar menjadi 70–80% bagian card.
- Teks ditempatkan di bawah gambar atau di dalam gambar dengan kontras kuat.
- Tidak memerlukan shadow.
- Border tidak wajib.
- Radius 0 px.
- Heading dapat uppercase.
- CTA dapat berupa link bergaris bawah dengan icon panah.

---

## 4.6 Product Card

Product card adidas bersifat bersih dan informatif.

### Struktur

```text
[Wishlist icon]
[Product image pada latar abu-abu terang]
Badge/status opsional
Harga
Nama produk
Kategori/gender/brand line
Jumlah warna
Rating opsional
```

### Spesifikasi rekomendasi

- Rasio gambar: `1:1` atau `4:5`.
- Latar gambar: `#ECEFF1` atau abu-abu sangat terang.
- Padding gambar: `8–16px`.
- Harga: `14–16px`, medium/semibold.
- Nama produk: `14px`, regular/medium.
- Metadata: `12–13px`, warna `#767677`.
- Wishlist: kanan atas, hit area minimal `44 × 44px`.
- Gap antar-card dapat dibuat kecil agar grid terasa seperti katalog.

### Hover desktop

- Gambar dapat berganti ke foto kedua.
- Card dapat mendapat border hitam tipis.
- Wishlist dan quick action menjadi lebih jelas.
- Hindari zoom berlebihan.

---

## 4.7 Filter dan Sorting

Halaman katalog adidas memberi banyak opsi filter, tetapi tetap menjaga tindakan utamanya terlihat.

### Desktop

- Filter dapat berupa horizontal filter bar atau sidebar/drawer.
- Tampilkan jumlah hasil.
- Letakkan `Filter & Sort` dekat dengan judul dan hasil.
- Tampilkan filter aktif sebagai removable chip atau ringkasan teks.

### Mobile

- Gunakan bottom sheet atau full-screen drawer.
- Tombol `FILTER & SORT` dapat sticky di bagian bawah.
- Setelah filter diterapkan, tampilkan jumlah filter aktif.

### Aturan

- Gunakan divider, bukan card bertumpuk.
- Checkbox dan radio harus memiliki hit area yang besar.
- Beri opsi `Clear all` yang terlihat tetapi tidak menyaingi CTA `Apply`.
- Jangan menyembunyikan harga dan urutan sorting dalam menu yang terlalu dalam.

---

## 4.8 Product Detail Page

Struktur rekomendasi desktop:

```text
┌───────────────────────────────┬─────────────────────┐
│ Product gallery               │ Product information │
│ 2-column image grid           │ Name                │
│                               │ Price               │
│                               │ Color               │
│                               │ Size selector       │
│                               │ Add to bag          │
│                               │ Wishlist            │
└───────────────────────────────┴─────────────────────┘
```

### Aturan

- Gallery visual harus dominan.
- Panel informasi dapat dibuat sticky.
- Tombol `Add to bag` menggunakan hitam penuh.
- Size selector memakai grid kotak, bukan dropdown, bila opsi masih dapat ditampilkan.
- Beri rekomendasi ukuran secara singkat.
- Informasi promosi atau pengecualian diskon ditempatkan dekat harga/CTA.
- Detail produk lanjutan menggunakan accordion atau section panjang dengan divider.

---

## 4.9 Footer

Footer adidas bersifat informatif dan terstruktur.

### Desktop

Gunakan beberapa kolom:

- Products.
- Sports/categories.
- Collections.
- Company information.
- Support.
- Social links.

### Mobile

- Ubah menjadi accordion.
- Tampilkan link penting lebih dahulu.
- Pisahkan legal links pada area bawah.

### Visual

- Heading footer uppercase dan bold.
- Body link kecil tetapi tetap terbaca.
- Latar putih atau hitam.
- Divider horizontal yang jelas.

---

## 5. Komponen UI

## 5.1 Primary Button

```css
.btn-primary {
  min-height: 48px;
  padding: 0 28px;
  border: 1px solid #000;
  border-radius: 0;
  background: #000;
  color: #fff;
  font-weight: 700;
  font-size: 13px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.btn-primary:hover {
  background: #1f1f1f;
}

.btn-primary:active {
  transform: translate(2px, 2px);
}

.btn-primary:focus-visible {
  outline: 2px solid #000;
  outline-offset: 3px;
}
```

### Karakter

- Hitam penuh.
- Teks putih uppercase.
- Icon panah dapat diletakkan di kanan.
- Sudut tajam.
- Tinggi minimal 48 px.

---

## 5.2 Secondary Button

```css
.btn-secondary {
  min-height: 48px;
  padding: 0 28px;
  border: 1px solid #000;
  border-radius: 0;
  background: #fff;
  color: #000;
  font-weight: 700;
  text-transform: uppercase;
}
```

Gunakan untuk aksi sekunder yang tetap penting, seperti `View Details` atau `Save`.

---

## 5.3 Text Link

- Teks bold atau semibold.
- Uppercase untuk CTA.
- Underline konsisten.
- Dapat memakai icon panah kanan.
- Jangan hanya mengandalkan perubahan warna saat hover.

```css
.text-link {
  color: inherit;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 4px;
}
```

---

## 5.4 Input

```css
.input {
  width: 100%;
  min-height: 48px;
  padding: 12px 14px;
  border: 1px solid #767677;
  border-radius: 0;
  background: #fff;
  color: #000;
}

.input:focus {
  border-color: #000;
  outline: 2px solid #000;
  outline-offset: 1px;
}
```

### Aturan

- Label berada di atas input.
- Placeholder tidak menggantikan label.
- Error message jelas dan dekat dengan field.
- Hindari input dengan background transparan yang sulit dibedakan.

---

## 5.5 Badge dan Status

Gunakan badge secara hemat.

Contoh:

- `NEW`
- `BEST SELLER`
- `SALE`
- `ACTIVE`
- `MAINTENANCE`

### Style

- Font `10–12px`, uppercase, bold.
- Padding `4px 6px`.
- Radius 0–2 px.
- Latar hitam untuk badge netral.
- Warna status hanya saat memiliki makna.

---

## 5.6 Card untuk Sistem Informasi

Untuk dashboard atau sistem internal, jangan menjadikan setiap elemen sebuah rounded card. Gunakan permukaan datar dengan divider.

```text
┌──────────────────────────────────────┐
│ TITLE                         ACTION │
├──────────────────────────────────────┤
│ Main value                           │
│ Supporting information              │
└──────────────────────────────────────┘
```

### Style

- Background putih.
- Border 1 px abu-abu.
- Radius 0–4 px maksimal.
- Header card uppercase kecil.
- Angka utama besar dan tebal.
- Gunakan warna status hanya pada bagian angka/icon penting.

---

## 6. Imagery dan Art Direction

## 6.1 Gaya foto campaign

- Crop dinamis.
- Gerakan tubuh atau produk terlihat jelas.
- Kontras kuat.
- Angle tidak selalu simetris.
- Background dapat minimal atau sangat ekspresif.
- Produk menjadi bagian dari cerita, bukan ditempel secara dekoratif.

## 6.2 Foto produk

- Background netral.
- Pencahayaan konsisten.
- Produk memenuhi area gambar tanpa terpotong.
- Gunakan satu sistem angle untuk seluruh katalog.
- Foto lifestyle digunakan sebagai gambar kedua atau konten campaign.

## 6.3 Rasio yang direkomendasikan

| Konten | Rasio |
|---|---:|
| Hero desktop | 16:7 / 16:8 |
| Hero mobile | 4:5 |
| Campaign card | 4:5 / 3:4 |
| Product image | 1:1 / 4:5 |
| Editorial banner | 16:9 |
| Avatar/user | 1:1 |

## 6.4 Overlay teks

Gunakan overlay bila:

- Area foto cukup tenang.
- Kontras teks tetap tinggi.
- Posisi subjek tidak tertutup.

Bila foto terlalu ramai, tempatkan teks di blok terpisah di bawah atau di samping gambar.

---

## 7. Motion dan Interaction

### Durasi

- Microinteraction: `120–180ms`.
- Drawer/modal: `200–300ms`.
- Carousel: `250–400ms`.

### Easing

```css
transition-timing-function: cubic-bezier(0.2, 0, 0, 1);
```

### Pola motion

- Hover: underline bergerak atau border muncul.
- Button active: bergeser 1–2 px.
- Card image: crossfade ke gambar kedua.
- Drawer: slide dari kanan atau bawah.
- Accordion: tinggi berubah secara halus.

### Hindari

- Bounce berlebihan.
- Parallax berat pada semua section.
- Animasi loading dekoratif yang lama.
- Fade-in seluruh halaman satu per satu.
- Animasi yang menggeser layout secara tiba-tiba.

Hormati preferensi `prefers-reduced-motion`.

---

## 8. Responsive Behavior

## 8.1 Breakpoint rekomendasi

```css
--bp-sm: 480px;
--bp-md: 768px;
--bp-lg: 960px;
--bp-xl: 1280px;
--bp-2xl: 1600px;
```

Dokumentasi YARN membedakan pengalaman mobile dan desktop pada sekitar `960px`.

## 8.2 Perubahan utama

| Desktop | Mobile |
|---|---|
| Full navigation | Menu drawer |
| 3–4 campaign cards | Horizontal scroll / 1 card |
| 3–4 product columns | 2 product columns |
| Side panel product info | Stacked content |
| Filter sidebar/bar | Full-screen filter drawer |
| Multi-column footer | Accordion footer |
| Large hero landscape | Portrait hero |

## 8.3 Mobile-first rules

- CTA utama boleh full width.
- Touch target minimal `44 × 44px`.
- Jangan mengandalkan hover.
- Hindari teks di atas bagian foto yang terlalu ramai.
- Pastikan informasi harga/status tidak tertutup oleh carousel atau sticky navigation.

---

## 9. Accessibility

- Kontras teks normal minimal `4.5:1`.
- Kontras teks besar minimal `3:1`.
- Semua interaksi dapat dijangkau dengan keyboard.
- Gunakan focus ring yang terlihat jelas.
- Tombol icon wajib memiliki accessible label.
- Alt text menjelaskan isi dan fungsi gambar, bukan sekadar nama file.
- Jangan menyampaikan status hanya dengan warna.
- Form error harus dikaitkan dengan field terkait.
- Carousel memiliki tombol previous/next dan dapat digunakan melalui keyboard.
- Heading mengikuti urutan semantik H1, H2, H3.
- Ukuran teks body minimal 16 px untuk konten utama.

---

## 10. Cara Membuat Desain Terlihat Lebih Manusia dan Tidak “AI-Generated”

### Hindari pola berikut

- Gradient ungu-biru sebagai background default.
- Glassmorphism dan blur pada setiap elemen.
- Semua card memiliki radius 24 px.
- Terlalu banyak pill button.
- Icon 3D atau ilustrasi generik yang tidak relevan.
- Semua section berada di tengah dan simetris.
- Headline berisi jargon seperti “Revolutionize Your Experience”.
- Shadow besar dan lembut pada setiap card.
- Terlalu banyak badge warna-warni.
- Pola dashboard template yang seragam tanpa hierarchy.
- Foto stok yang tidak memiliki hubungan dengan konteks produk/sistem.

### Terapkan pola berikut

- Gunakan copy spesifik dan fungsional.
- Tampilkan data nyata atau placeholder yang realistis.
- Variasikan komposisi antara full-width, split layout, dan grid.
- Gunakan alignment kiri sebagai default.
- Beri ruang kosong besar pada area yang memang penting.
- Gunakan border dan typography untuk hierarchy.
- Pilih 1–2 gaya gambar yang konsisten.
- Buat state kosong, error, loading, dan disabled dengan sengaja.
- Pertahankan beberapa ketidaksimetrian editorial yang terkontrol.

---

## 11. Adaptasi untuk Website Sistem Informasi / Manajemen Internal

Style adidas dapat diterapkan ke sistem internal tanpa membuatnya terlihat seperti toko online.

## 11.1 Struktur dashboard

```text
UTILITY BAR
MAIN HEADER / GLOBAL SEARCH
PAGE TITLE + PRIMARY ACTION
KPI STRIP
MAIN DATA AREA
SECONDARY INSIGHT / ACTIVITY
FOOTER OR SYSTEM INFO
```

### Contoh

```text
MONITORING OPERASIONAL                          + TAMBAH DATA
Ringkasan performa alat dan aktivitas hari ini

[24 ALAT AKTIF] [3 MAINTENANCE] [92% UTILISASI] [2 ALERT]

┌──────────────────────────────┬─────────────────────────┐
│ Aktivitas alat               │ Alert terbaru           │
│ Grafik / timeline            │ Daftar prioritas        │
└──────────────────────────────┴─────────────────────────┘

DATA ALAT
Filter | Search | Sort
[Data table]
```

## 11.2 KPI

Gunakan KPI seperti editorial metric, bukan empat rounded cards identik.

```text
24
ALAT AKTIF
+3 DARI KEMARIN
```

- Angka: 36–56 px, bold.
- Label: 12–14 px, uppercase.
- Divider vertikal atau horizontal.
- Warna status hanya pada perubahan atau alert.

## 11.3 Data table

- Header uppercase kecil.
- Tinggi row `48–56px`.
- Border horizontal tipis.
- Hindari zebra striping yang terlalu kuat.
- Kolom penting dibuat bold.
- Status memakai badge kecil.
- Aksi row memakai icon menu atau text link.
- Header tabel dapat sticky.

## 11.4 Sidebar

Untuk gaya yang lebih mirip adidas:

- Gunakan sidebar hitam atau putih yang datar.
- Icon line sederhana.
- Label uppercase kecil.
- Active item ditandai dengan border kiri/kanan tebal atau inversi warna.
- Hindari menu berbentuk pill.

## 11.5 Grafik

- Gunakan satu warna utama dan variasi abu-abu.
- Highlight hanya seri penting.
- Grid line tipis.
- Judul chart singkat.
- Tampilkan angka utama di luar chart bila memungkinkan.
- Hindari chart 3D dan terlalu banyak warna.

---

## 12. Starter CSS Tokens

```css
:root {
  --color-black: #000000;
  --color-white: #ffffff;
  --color-gray-50: #f7f7f7;
  --color-gray-100: #eceff1;
  --color-gray-200: #e3e5e5;
  --color-gray-400: #b7b7b7;
  --color-gray-600: #767677;
  --color-gray-900: #181818;

  --color-success: #1e7a46;
  --color-warning: #c56a00;
  --color-danger: #d71920;
  --color-info: #0068b5;

  --font-heading: "Poppins", "Arial Narrow", sans-serif;
  --font-body: "Roboto", Arial, Helvetica, sans-serif;
  --font-mono: "Roboto Mono", monospace;

  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-6: 24px;
  --space-8: 32px;
  --space-12: 48px;
  --space-16: 64px;
  --space-24: 96px;

  --radius-none: 0;
  --radius-sm: 2px;
  --radius-md: 4px;

  --border-default: 1px solid var(--color-gray-200);
  --border-strong: 1px solid var(--color-black);

  --transition-fast: 150ms cubic-bezier(0.2, 0, 0, 1);
  --transition-base: 240ms cubic-bezier(0.2, 0, 0, 1);

  --container-max: 1440px;
}

body {
  margin: 0;
  background: var(--color-white);
  color: var(--color-black);
  font-family: var(--font-body);
  font-size: 16px;
  line-height: 1.5;
}

h1,
h2,
h3 {
  margin: 0;
  font-family: var(--font-heading);
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -0.02em;
}

.container {
  width: min(100% - 48px, var(--container-max));
  margin-inline: auto;
}

@media (max-width: 767px) {
  .container {
    width: min(100% - 32px, var(--container-max));
  }
}
```

---

## 13. Contoh Struktur HTML Hero

```html
<section class="hero">
  <picture class="hero__media">
    <source media="(max-width: 767px)" srcset="campaign-mobile.webp" />
    <img src="campaign-desktop.webp" alt="Deskripsi visual campaign" />
  </picture>

  <div class="hero__content">
    <p class="hero__eyebrow">NEW COLLECTION</p>
    <h1 class="hero__title">BUILT FOR THE NEXT MOVE</h1>
    <p class="hero__description">
      Produk dan pengalaman yang membantu pengguna bergerak lebih cepat.
    </p>
    <a class="btn-primary" href="#products">
      EXPLORE NOW <span aria-hidden="true">→</span>
    </a>
  </div>
</section>
```

---

## 14. Checklist Figma

### Foundation

- [ ] Warna dibuat sebagai variables/styles.
- [ ] Typography scale dibuat konsisten.
- [ ] Spacing menggunakan kelipatan 4 px.
- [ ] Desktop menggunakan 12-column grid.
- [ ] Mobile menggunakan 4-column grid.
- [ ] Radius dibatasi maksimal 4 px untuk sebagian besar UI.

### Components

- [ ] Button memiliki default, hover, focus, active, dan disabled state.
- [ ] Input memiliki label, hint, error, dan disabled state.
- [ ] Product/data card memiliki state hover dan selected.
- [ ] Filter memiliki active state dan clear action.
- [ ] Header dan navigation memiliki responsive variant.
- [ ] Table memiliki empty, loading, dan error state.

### Content

- [ ] Hanya satu H1 per halaman.
- [ ] CTA menggunakan kata kerja yang jelas.
- [ ] Teks campaign maksimal 1 heading dan 1–2 baris pendukung.
- [ ] Metadata menggunakan style sekunder.
- [ ] Tidak ada placeholder lorem ipsum pada final mockup.

### Accessibility

- [ ] Contrast telah diperiksa.
- [ ] Focus state terlihat.
- [ ] Touch target minimal 44 px.
- [ ] Semua icon action mempunyai label.
- [ ] Navigasi dapat digunakan melalui keyboard.

---

## 15. Checklist Frontend

- [ ] Gunakan semantic HTML.
- [ ] Gunakan `picture` dan responsive images.
- [ ] Gunakan `loading="lazy"` untuk gambar di bawah fold.
- [ ] Jangan lazy-load gambar LCP/hero utama.
- [ ] Sediakan width dan height pada gambar untuk menghindari layout shift.
- [ ] Gunakan CSS variables untuk token.
- [ ] Batasi animasi dan hormati reduced motion.
- [ ] Pastikan drawer, modal, carousel, dan filter keyboard-accessible.
- [ ] Test pada lebar 360, 768, 960, 1280, dan 1440 px.
- [ ] Test empty, loading, success, warning, dan error state.

---

## 16. Ringkasan Do dan Don't

| Do | Don't |
|---|---|
| Gunakan hitam-putih sebagai basis | Memakai banyak warna UI sekaligus |
| Gunakan judul besar dan tegas | Membuat seluruh teks bold |
| Gunakan gambar sebagai pusat perhatian | Menambahkan dekorasi yang bersaing dengan gambar |
| Pertahankan sudut tajam | Menggunakan rounded card besar di semua tempat |
| Gunakan divider dan grid | Mengandalkan shadow untuk semua hierarchy |
| Gunakan CTA langsung | Menggunakan copy generik dan panjang |
| Gunakan komposisi editorial | Membuat setiap section identik dan simetris |
| Buat state interaksi lengkap | Hanya mendesain kondisi ideal/default |

---

## 17. Referensi Resmi

1. **adidas Indonesia homepage**  
   https://www.adidas.co.id/en

2. **adidas Indonesia — Running Shoes listing**  
   https://www.adidas.co.id/en/shoes-running

3. **adidas Design Language**  
   https://designlanguage.adidas.com/

4. **adidas YARN Design System — Overview**  
   https://adidas.github.io/adidas-yarn-design-system/overview/

5. **adidas YARN — Typography**  
   https://adidas.github.io/adidas-yarn-design-system/foundation/typography/

6. **adidas YARN — Components**  
   https://adidas.github.io/adidas-yarn-design-system/components/

7. **adidas Logo History and Meaning**  
   https://www.adidas.com/us/blog/932571-adidas-logos-history-and-meaning

---

## 18. Kesimpulan

Untuk mendapatkan nuansa seperti website adidas, jangan hanya meniru warna hitam-putihnya. Kekuatan desainnya berasal dari kombinasi berikut:

1. Typography yang kuat.
2. Grid yang disiplin.
3. Foto yang dominan dan memiliki arah seni jelas.
4. CTA yang tegas.
5. UI netral dengan warna yang berasal dari konten.
6. Komponen bersudut tajam dan minim dekorasi.
7. Responsive behavior yang tetap menjaga hierarchy.
8. Copy singkat, spesifik, dan berorientasi tindakan.

Formula praktisnya:

```text
MONOCHROME UI
+ BOLD TYPOGRAPHY
+ LARGE IMAGERY
+ SHARP COMPONENTS
+ STRICT GRID
+ DIRECT COPY
= ADIDAS-INSPIRED DIGITAL EXPERIENCE
```
