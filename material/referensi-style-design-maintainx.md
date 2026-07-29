# Referensi Style Design Website MaintainX

> Dokumen referensi desain untuk pengembangan website atau sistem informasi **equipment & asset management**.
>
> Sumber utama: [MaintainX — Equipment and Asset Management](https://www.getmaintainx.com/use-cases/equipment-and-asset-management)
>
> Tanggal observasi: 29 Juli 2026  
> Status: Referensi visual dan UX, **bukan panduan untuk menyalin identitas merek MaintainX secara persis**.

---

## 1. Ringkasan Gaya Desain

Website MaintainX menggunakan gaya **modern enterprise SaaS** yang dipadukan dengan konteks dunia industri. Desainnya terlihat profesional, mudah dipercaya, dan tetap terasa ringan meskipun informasi yang disampaikan cukup banyak.

Karakter utamanya:

- Clean dan modern.
- Dominasi warna putih, biru terang, dan navy gelap.
- Tipografi sans-serif yang tegas dan mudah dibaca.
- Banyak white space agar halaman tidak terasa padat.
- Penggunaan foto pekerja dan peralatan industri untuk memperkuat konteks nyata.
- Screenshot aplikasi ditampilkan sebagai kartu UI yang bertumpuk.
- CTA utama selalu terlihat jelas.
- Informasi teknis disederhanakan menjadi angka, kartu, status, dan ilustrasi alur kerja.
- Menggunakan social proof: logo perusahaan, angka hasil, award, review, dan testimonial.

### Kata kunci visual

`Enterprise` · `Industrial` · `Reliable` · `Clean` · `Data-driven` · `Operational` · `Mobile-first` · `Professional`

---

## 2. Tujuan Komunikasi Desain

Desain MaintainX tidak hanya menjual software. Halaman tersebut berusaha menyampaikan bahwa sistemnya:

1. Mudah digunakan oleh teknisi di lapangan.
2. Tetap kuat untuk kebutuhan perusahaan besar.
3. Dapat mengurangi downtime dan biaya operasional.
4. Menyatukan data aset, work order, inspeksi, dan histori perawatan.
5. Memberikan hasil yang terukur melalui data.

Prinsip yang dapat diterapkan pada website asset management:

> Jangan hanya menampilkan daftar fitur. Tampilkan masalah operasional, solusi di dalam sistem, lalu hasil bisnisnya.

---

## 3. Struktur Halaman

Pola halaman MaintainX dapat diringkas menjadi struktur berikut:

```text
Navbar
↓
Hero section
↓
Logo perusahaan / trusted by
↓
Statistik hasil atau KPI
↓
Daftar fitur utama dengan visual aplikasi
↓
Award dan sertifikasi
↓
Testimonial atau review
↓
FAQ
↓
Final CTA
↓
Footer lengkap
```

### 3.1 Navbar

Karakter navbar:

- Logo berada di kiri.
- Menu utama berada di tengah atau sisi kanan.
- Menu produk memiliki dropdown atau mega menu.
- Terdapat dua CTA:
  - Secondary CTA: login atau schedule demo.
  - Primary CTA: sign up atau mulai gratis.
- Tinggi navbar cukup lega, sekitar `72–88px`.
- Navigasi terlihat minimal, tanpa border berlebihan.

Rekomendasi menu untuk sistem asset management:

```text
Dashboard
Asset
Monitoring
Work Order
Maintenance
Inventory
Report
Knowledge Base
```

Untuk landing page publik:

```text
Product
Solutions
Industries
Resources
Pricing
Login
Request Demo
```

---

## 4. Hero Section

Hero MaintainX menggunakan kombinasi:

- Headline besar dan berorientasi hasil.
- Paragraf singkat yang menjelaskan manfaat.
- Dua tombol CTA.
- Foto pekerja industri.
- Potongan antarmuka aplikasi sebagai floating UI card.

### Pola copywriting

```text
[Label kategori]

Headline yang berfokus pada hasil utama

Deskripsi singkat tentang bagaimana sistem membantu pengguna
mencapai hasil tersebut.

[Primary CTA] [Secondary CTA]
```

### Contoh adaptasi

```text
ASSET & EQUIPMENT MANAGEMENT

Pantau seluruh aset proyek dalam satu sistem

Kelola lokasi, kondisi, histori perawatan, downtime, dan jadwal
maintenance alat berat secara real-time.

[Mulai Monitoring] [Lihat Demo]
```

### Layout desktop

```text
┌─────────────────────────────────────────────────────────────┐
│ Navbar                                                      │
├───────────────────────────────┬─────────────────────────────┤
│ Label                         │                             │
│ Headline besar                │ Foto pekerja / alat         │
│ Deskripsi                     │ + floating app cards        │
│ CTA utama + CTA sekunder      │                             │
└───────────────────────────────┴─────────────────────────────┘
```

### Rekomendasi ukuran

| Elemen | Desktop | Tablet | Mobile |
|---|---:|---:|---:|
| Heading hero | 56–72px | 44–56px | 36–44px |
| Body hero | 18–20px | 17–18px | 16–18px |
| Lebar teks | 520–620px | 460–540px | 100% |
| Padding section | 96–128px | 72–96px | 56–72px |

---

## 5. Sistem Warna

Warna berikut merupakan **estimasi visual** yang terinspirasi dari halaman dan product UI MaintainX. Gunakan sebagai titik awal, bukan sebagai salinan identitas merek.

### 5.1 Palet utama

| Token | Warna | Penggunaan |
|---|---|---|
| Primary Blue | `#1686FB` | CTA utama, link, status aktif, highlight |
| Primary Hover | `#0875E7` | Hover tombol utama |
| Deep Navy | `#071E3D` | Section gelap, footer, headline kontras |
| Ink Black | `#111827` | Heading dan teks utama |
| Slate Text | `#667085` | Body text dan metadata |
| Light Blue | `#EAF4FF` | Background selected state dan icon container |
| Surface Soft | `#F5F8FB` | Background section alternatif |
| Border | `#E3E8EF` | Garis pembatas dan card outline |
| White | `#FFFFFF` | Main background dan card |
| Success | `#17B26A` | Aset online dan pekerjaan selesai |
| Warning | `#F79009` | Perlu inspeksi dan maintenance segera |
| Danger | `#F04438` | Offline, gagal, downtime, critical |

### 5.2 CSS variables

```css
:root {
  --color-primary: #1686fb;
  --color-primary-hover: #0875e7;
  --color-primary-soft: #eaf4ff;

  --color-navy: #071e3d;
  --color-heading: #111827;
  --color-body: #667085;
  --color-muted: #98a2b3;

  --color-background: #ffffff;
  --color-surface: #f5f8fb;
  --color-border: #e3e8ef;

  --color-success: #17b26a;
  --color-warning: #f79009;
  --color-danger: #f04438;
}
```

### 5.3 Rasio penggunaan warna

```text
65% putih / neutral background
20% navy dan warna teks
10% biru utama
5% semantic colors dan aksen
```

Gunakan biru sebagai penanda aksi penting, bukan sebagai warna untuk semua elemen.

---

## 6. Tipografi

Karakter tipografi MaintainX menyerupai **modern grotesk sans-serif**: bersih, netral, dan memiliki keterbacaan tinggi.

Font alternatif yang aman:

1. `Inter`
2. `Manrope`
3. `Geist`
4. `Plus Jakarta Sans`
5. `Satoshi`

Rekomendasi utama:

```css
font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
```

### Hierarki tipografi

| Style | Ukuran | Weight | Line-height |
|---|---:|---:|---:|
| Display | 64px | 650–700 | 1.05–1.12 |
| H1 | 56px | 650–700 | 1.10 |
| H2 | 40–48px | 650–700 | 1.15 |
| H3 | 24–30px | 600–700 | 1.25 |
| Body Large | 18–20px | 400–500 | 1.55 |
| Body | 16px | 400 | 1.60 |
| Label | 13–14px | 600 | 1.30 |
| Caption | 12–13px | 400–500 | 1.40 |

### Aturan tipografi

- Headline dibuat padat dan tidak terlalu panjang.
- Maksimal 8–12 kata untuk headline utama.
- Gunakan `letter-spacing: -0.02em` pada heading besar.
- Body text jangan terlalu gelap; gunakan warna slate agar lebih lembut.
- Hindari terlalu banyak variasi ukuran font.
- Gunakan weight `600` untuk judul kartu dan tombol.

---

## 7. Grid dan Layout

### 7.1 Container

```css
.page-container {
  width: min(100% - 40px, 1240px);
  margin-inline: auto;
}
```

Ukuran umum:

- Max width: `1200–1280px`.
- Desktop side padding: `32–48px`.
- Mobile side padding: `20–24px`.
- Grid desktop: 12 kolom.
- Jarak antar kolom: `24–32px`.

### 7.2 Spacing system

Gunakan dasar kelipatan `4px`.

```text
4px   = detail kecil
8px   = jarak icon dan label
12px  = internal compact spacing
16px  = elemen form
24px  = card padding kecil
32px  = card padding besar
48px  = jarak antarblok
64px  = section kecil
96px  = section standar
128px = section hero atau CTA besar
```

### 7.3 Komposisi section

Pertahankan pola bergantian:

```text
Section 1: teks kiri, visual kanan
Section 2: visual kiri, teks kanan
Section 3: teks kiri, visual kanan
```

Pola ini menciptakan ritme visual tanpa membutuhkan dekorasi berlebihan.

---

## 8. Button Style

### Primary button

```css
.button-primary {
  min-height: 48px;
  padding: 0 24px;
  border: 0;
  border-radius: 999px;
  background: #1686fb;
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 8px 20px rgb(22 134 251 / 18%);
}

.button-primary:hover {
  background: #0875e7;
  transform: translateY(-1px);
}
```

### Secondary button

```css
.button-secondary {
  min-height: 48px;
  padding: 0 24px;
  border: 1px solid #cfd8e3;
  border-radius: 999px;
  background: #ffffff;
  color: #111827;
  font-weight: 600;
}
```

### Button rules

- Gunakan radius penuh untuk CTA landing page.
- Gunakan radius `8–10px` untuk button di dalam aplikasi dashboard.
- Icon arrow dapat ditempatkan di kanan label.
- Primary dan secondary CTA harus mudah dibedakan.
- Hindari lebih dari dua CTA utama dalam satu area.

---

## 9. Card Style

MaintainX banyak menggunakan card berwarna putih dengan border tipis dan shadow lembut.

```css
.card {
  background: #ffffff;
  border: 1px solid #e3e8ef;
  border-radius: 16px;
  box-shadow:
    0 2px 6px rgb(16 24 40 / 5%),
    0 18px 40px rgb(16 24 40 / 8%);
}
```

### Variasi card

#### Feature card

- Icon kecil di bagian atas.
- Judul pendek.
- Deskripsi maksimal 3–4 baris.
- Background putih atau light blue.

#### KPI card

- Nilai besar.
- Label singkat.
- Tren naik atau turun.
- Gunakan warna semantic hanya pada angka penting.

#### Asset card

```text
[Thumbnail] Nama aset
            Lokasi
            ID aset
            [Online]
```

#### Floating UI card

- Digunakan sebagai dekorasi yang sekaligus menunjukkan fungsi produk.
- Diletakkan saling overlap.
- Memakai shadow lebih kuat dari card biasa.
- Salah satu card dapat dibuat lebih maju untuk membangun depth.

---

## 10. Visualisasi Produk

Salah satu ciri paling kuat dari MaintainX adalah cara produk divisualisasikan.

### Pola visual

```text
Foto alat atau pekerja
+
Screenshot aplikasi
+
Floating status card
+
Shadow dan overlap
```

Contoh isi floating card:

- `Asset Online`
- `Work Order Approved`
- `Inspection Completed`
- `Downtime: 12%`
- `Maintenance Due in 3 Days`
- `New Work Order Trigger`

### Prinsip penggunaannya

- Screenshot tidak harus menampilkan seluruh layar.
- Fokuskan crop pada satu fitur atau satu tindakan.
- Gunakan data yang realistis.
- Tampilkan status dengan bahasa yang mudah dimengerti.
- Jangan menumpuk terlalu banyak card dalam satu visual.
- Gunakan maksimal 2–4 layer.

---

## 11. Photography Style

Foto yang digunakan memiliki karakter:

- Pekerja nyata, bukan ilustrasi abstrak.
- Setting gudang, pabrik, konstruksi, atau fasilitas teknis.
- Peralatan keselamatan terlihat jelas.
- Pencahayaan terang dan natural.
- Warna industri yang hidup tetapi tidak terlalu dramatis.
- Subjek sedang menggunakan perangkat atau melakukan pekerjaan.

### Rekomendasi untuk website alat berat

Gunakan foto seperti:

- Operator sedang memeriksa excavator.
- Teknisi memindai QR code pada alat.
- Site manager melihat tablet di proyek.
- Mekanik melakukan inspeksi mesin.
- Aerial view area proyek dan alat berat.

### Hindari

- Foto stock yang terlalu formal.
- Foto pekerja yang hanya berdiri dan melihat kamera.
- Foto terlalu gelap atau cinematic.
- Ilustrasi AI yang memiliki detail alat tidak realistis.
- Campuran gaya foto yang tidak konsisten.

---

## 12. Iconography

Karakter icon:

- Outline icon.
- Stroke tipis atau medium.
- Bentuk sederhana.
- Radius lembut.
- Biasanya berwarna biru atau slate.
- Diletakkan di dalam background light blue bila perlu.

Ukuran:

```text
16px = inline action
20px = navigation
24px = card icon
32px = feature icon
```

Library yang cocok:

- Lucide Icons
- Phosphor Icons
- Heroicons

Jangan mencampurkan lebih dari satu keluarga icon.

---

## 13. Status dan Semantic Color

Sistem asset management sangat bergantung pada status. Warna tidak boleh menjadi satu-satunya indikator.

```text
● Online       → hijau
● Offline      → merah
● Maintenance  → oranye
● Idle         → abu-abu
● In Progress  → biru
● Overdue      → merah + icon warning
```

Contoh badge:

```css
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 28px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}
```

Gunakan kombinasi:

- Warna.
- Icon atau dot.
- Teks status.

---

## 14. Statistik dan Social Proof

MaintainX menampilkan hasil dalam bentuk persentase dan angka besar. Cara ini efektif karena manfaat software menjadi lebih konkret.

### Format KPI

```text
38%
Peningkatan uptime alat
```

```text
32%
Penurunan downtime tidak terencana
```

### Implementasi pada website sendiri

Gunakan metrik seperti:

- Total aset aktif.
- Aset dalam maintenance.
- Rata-rata downtime.
- Kepatuhan inspeksi.
- Work order selesai tepat waktu.
- Biaya maintenance bulan ini.

### Style KPI

- Angka: `40–64px`, weight `650–700`.
- Label: `14–18px`, warna slate.
- Bisa ditempatkan dalam grid 2–4 kolom.
- Gunakan divider tipis, bukan card berat untuk semua statistik.

---

## 15. Feature Section

Setiap fitur MaintainX dijelaskan dengan pola konsisten:

```text
Judul manfaat
↓
Deskripsi singkat
↓
Visual produk yang relevan
```

Judul lebih menekankan outcome daripada nama fitur.

### Kurang efektif

```text
Work Order Module
```

### Lebih efektif

```text
Percepat penanganan kerusakan alat
```

### Contoh feature blocks

#### Preventive maintenance

```text
Cegah downtime sebelum menghambat proyek

Jadwalkan inspeksi dan maintenance berkala berdasarkan waktu,
jam operasi, atau kondisi alat.
```

#### Work request

```text
Ubah laporan kerusakan menjadi work order secara otomatis

Setiap permintaan dapat diteruskan ke teknisi dan tim yang tepat
tanpa koordinasi manual yang berulang.
```

#### Asset history

```text
Lihat seluruh histori aset dalam satu tempat

Akses riwayat status, pekerjaan, spare part, biaya, dan pengguna
aset dari awal pengadaan hingga disposal.
```

---

## 16. Data Visualization

Grafik pada sistem harus terlihat sederhana dan operasional.

### Jenis chart yang cocok

- Line chart untuk tren downtime.
- Bar chart untuk biaya maintenance per aset.
- Donut chart untuk distribusi status aset.
- Stacked bar untuk planned vs unplanned maintenance.
- Progress bar untuk inspeksi dan preventive maintenance.

### Aturan visual

- Gunakan satu warna utama dan semantic colors seperlunya.
- Hindari terlalu banyak warna kategori.
- Gunakan grid line tipis.
- Tooltip harus jelas dan tidak penuh jargon.
- Label angka penting langsung di chart bila memungkinkan.
- Sediakan filter waktu: `7D`, `1M`, `3M`, `6M`, `1Y`.

---

## 17. Form dan Input

Gaya form:

- Tinggi input `44–48px`.
- Border abu-abu tipis.
- Radius `8–10px`.
- Label diletakkan di atas input.
- Focus ring berwarna biru.
- Error message berada tepat di bawah input.

```css
.input {
  width: 100%;
  min-height: 46px;
  padding: 0 14px;
  border: 1px solid #d8dee8;
  border-radius: 9px;
  background: #ffffff;
  color: #111827;
}

.input:focus {
  outline: none;
  border-color: #1686fb;
  box-shadow: 0 0 0 3px rgb(22 134 251 / 14%);
}
```

---

## 18. Table Style untuk Asset Management

Table harus memprioritaskan scanning cepat.

Kolom rekomendasi:

```text
Asset
ID
Category
Location
Condition
Status
Last Maintenance
Next Schedule
Responsible Team
Action
```

### Visual table

- Header menggunakan background soft gray.
- Row hover menggunakan light blue sangat tipis.
- Zebra row tidak wajib.
- Status memakai badge.
- Action berada dalam menu tiga titik.
- Asset name dapat disertai thumbnail kecil.
- Tabel harus mendukung filter, sort, search, dan pagination.

```css
.table-row:hover {
  background: #f6faff;
}
```

---

## 19. Sidebar Dashboard

MaintainX menggunakan pola navigasi aplikasi yang sederhana dan mudah dipindai.

### Struktur sidebar

```text
Logo / Organization

Dashboard
Work Orders
Requests
Assets
Inventory
Meters
Locations
Teams
Vendors
Reports

Support
Settings
```

### Style

- Lebar `232–272px`.
- Background putih.
- Border kanan tipis.
- Active item memakai background light blue.
- Icon dan teks menggunakan kontras sedang.
- Item aktif menggunakan warna biru.
- Kelompok menu dapat diberi heading kecil.

---

## 20. Dashboard Layout yang Direkomendasikan

```text
┌─────────────┬────────────────────────────────────────────────┐
│ Sidebar     │ Page title                      [Add Asset]     │
│             ├────────────────────────────────────────────────┤
│ Dashboard   │ KPI 1   KPI 2   KPI 3   KPI 4                 │
│ Assets      ├─────────────────────────┬──────────────────────┤
│ Work Order  │ Asset Status Chart      │ Upcoming Maintenance │
│ Maintenance ├─────────────────────────┼──────────────────────┤
│ Inventory   │ Downtime Trend          │ Critical Assets      │
│ Reports     ├─────────────────────────┴──────────────────────┤
│             │ Recent Work Orders                             │
└─────────────┴────────────────────────────────────────────────┘
```

### Urutan informasi

1. Kondisi keseluruhan.
2. Informasi yang membutuhkan tindakan.
3. Tren performa.
4. Aktivitas terbaru.
5. Detail tabel.

---

## 21. FAQ Section

FAQ berfungsi untuk mengurangi keraguan pengguna sebelum melakukan CTA.

Gaya yang disarankan:

- Accordion sederhana.
- Border bawah tipis.
- Icon plus berubah menjadi minus.
- Pertanyaan menggunakan weight `600`.
- Jawaban memiliki line-height lega.
- Lebar konten sekitar `760–900px`.

Contoh pertanyaan:

```text
Apa perbedaan asset management dan inventory management?
Bagaimana cara mencatat downtime alat?
Apakah sistem mendukung QR code?
Bisakah satu aset memiliki beberapa sub-aset?
Bagaimana sistem mengatur preventive maintenance?
Apakah data dapat diakses melalui perangkat mobile?
```

---

## 22. Footer

Footer MaintainX bersifat besar dan informatif.

### Struktur

```text
Logo + deskripsi singkat

Product
Solutions
Industries
Resources
Company
Legal

Social media
Language selector
Copyright
```

### Style

- Background navy gelap atau putih dengan border atas.
- Teks utama putih bila menggunakan navy.
- Link menggunakan warna abu-abu terang.
- Hover link menjadi putih atau biru.
- Gunakan grid `4–6` kolom pada desktop.
- Pada mobile, setiap grup dapat menjadi accordion.

---

## 23. Responsive Behavior

### Desktop

- Hero dua kolom.
- Feature section dua kolom.
- KPI 4 kolom.
- Navbar lengkap.
- Floating card lebih bebas overlap.

### Tablet

- Hero tetap dua kolom dengan heading lebih kecil.
- Feature grid dapat berubah menjadi `55:45`.
- KPI menjadi dua kolom.
- Navbar mulai disederhanakan.

### Mobile

- Semua section menjadi satu kolom.
- Teks berada di atas visual.
- CTA dibuat full width atau stacked.
- Floating card dikurangi jumlahnya.
- Tabel berubah menjadi card list atau horizontal scroll.
- Sidebar berubah menjadi drawer.
- Padding horizontal minimal `20px`.

---

## 24. Motion dan Microinteraction

Animasi sebaiknya halus dan fungsional.

### Rekomendasi

```text
Button hover        150–200ms
Card hover          180–240ms
Accordion           200–300ms
Dropdown            150–220ms
Scroll reveal       400–600ms
Chart transition    300–500ms
```

Gunakan easing:

```css
transition-timing-function: cubic-bezier(0.2, 0.8, 0.2, 1);
```

### Hindari

- Parallax yang terlalu berat.
- Semua elemen bergerak bersamaan.
- Animasi loop yang mengganggu.
- Loading animation terlalu panjang.
- Hover yang mengubah layout.

---

## 25. Accessibility

- Kontras teks minimal mengikuti WCAG AA.
- Tombol dan input memiliki focus state.
- Click target minimal `44 × 44px`.
- Jangan menggunakan warna sebagai satu-satunya indikator status.
- Semua gambar produk memiliki alt text.
- Form memiliki label eksplisit.
- Accordion dapat dioperasikan dengan keyboard.
- Heading mengikuti struktur `H1 → H2 → H3`.
- Tabel memiliki header yang benar.
- Sediakan empty state dan error state yang jelas.

---

## 26. Design Tokens Lengkap

```css
:root {
  /* Color */
  --primary-50: #eef7ff;
  --primary-100: #d9edff;
  --primary-500: #1686fb;
  --primary-600: #0875e7;
  --primary-700: #0562c5;

  --navy-900: #071e3d;
  --gray-25: #fcfcfd;
  --gray-50: #f9fafb;
  --gray-100: #f2f4f7;
  --gray-200: #e4e7ec;
  --gray-300: #d0d5dd;
  --gray-500: #667085;
  --gray-700: #344054;
  --gray-900: #101828;

  --success-500: #17b26a;
  --warning-500: #f79009;
  --danger-500: #f04438;

  /* Radius */
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --radius-pill: 999px;

  /* Shadow */
  --shadow-xs: 0 1px 2px rgb(16 24 40 / 5%);
  --shadow-sm: 0 2px 6px rgb(16 24 40 / 6%);
  --shadow-md: 0 12px 28px rgb(16 24 40 / 10%);
  --shadow-lg: 0 24px 56px rgb(16 24 40 / 14%);

  /* Spacing */
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-8: 32px;
  --space-10: 40px;
  --space-12: 48px;
  --space-16: 64px;
  --space-24: 96px;
  --space-32: 128px;
}
```

---

## 27. Contoh Tailwind Theme

```js
// tailwind.config.js
export default {
  theme: {
    extend: {
      colors: {
        brand: {
          50: "#EEF7FF",
          100: "#D9EDFF",
          500: "#1686FB",
          600: "#0875E7",
          700: "#0562C5"
        },
        navy: "#071E3D",
        surface: "#F5F8FB",
        success: "#17B26A",
        warning: "#F79009",
        danger: "#F04438"
      },
      borderRadius: {
        card: "16px",
        control: "10px"
      },
      boxShadow: {
        card: "0 12px 28px rgba(16, 24, 40, 0.10)",
        floating: "0 24px 56px rgba(16, 24, 40, 0.14)"
      },
      maxWidth: {
        page: "1240px"
      }
    }
  }
};
```

---

## 28. Komponen yang Perlu Dibuat

### Landing page components

```text
Navbar
Hero
Logo Cloud
KPI Statistics
Feature Split Section
Product Mockup
Customer Quote
Review Cards
Award / Security Badges
FAQ Accordion
Final CTA
Footer
```

### Dashboard components

```text
App Sidebar
Topbar
Page Header
KPI Card
Status Badge
Asset Card
Asset Table
Filter Bar
Search Input
Date Range Picker
Chart Card
Work Order Card
Activity Timeline
Maintenance Calendar
Empty State
Confirmation Modal
Toast Notification
```

---

## 29. Adaptasi untuk Website Monitoring Alat Berat

### Landing page

#### Hero

```text
Kelola alat berat, kurangi downtime, dan jaga proyek tetap berjalan
```

#### KPI section

```text
124 Aset Terdaftar
93% Availability
18 Maintenance Aktif
7 Critical Alerts
```

#### Feature section

```text
Pantau lokasi dan status alat
Kelola inspeksi harian
Jadwalkan preventive maintenance
Catat konsumsi bahan bakar
Kelola kerusakan dan work order
Analisis downtime dan biaya operasional
```

### Dashboard

Prioritas utama:

1. Alat yang offline atau rusak.
2. Maintenance yang overdue.
3. Alat dengan jam kerja tinggi.
4. Penggunaan bahan bakar abnormal.
5. Dokumen atau inspeksi yang akan kedaluwarsa.

### Detail asset page

```text
Overview
Specifications
Current Status
Location
Hour Meter
Fuel Usage
Maintenance Schedule
Work Orders
Inspection History
Spare Parts
Documents
Activity Log
```

---

## 30. Content Style

MaintainX menggunakan bahasa yang:

- Langsung.
- Berorientasi hasil.
- Tidak terlalu teknis pada headline.
- Menjelaskan fitur melalui manfaat operasional.
- Menggunakan kalimat aktif.

### Formula copy

```text
Verb + operational benefit + business impact
```

Contoh:

```text
Pantau kondisi alat secara real-time agar tim dapat menangani
masalah sebelum menghambat proyek.
```

### Hindari

```text
Sistem kami menyediakan fitur asset management yang lengkap,
modern, canggih, inovatif, dan terintegrasi.
```

Kalimat tersebut terlalu umum dan tidak menunjukkan hasil konkret.

---

## 31. Do and Don't

### Do

- Gunakan layout yang lega.
- Prioritaskan informasi operasional.
- Gunakan screenshot sistem asli.
- Gunakan foto industri yang realistis.
- Tampilkan angka dan manfaat terukur.
- Gunakan status yang konsisten.
- Buat CTA utama selalu jelas.
- Terapkan satu design system pada landing page dan dashboard.

### Don't

- Menyalin logo atau aset merek MaintainX.
- Menggunakan terlalu banyak gradient.
- Memakai glassmorphism pada seluruh dashboard.
- Membuat semua card memiliki shadow besar.
- Menggunakan terlalu banyak warna status.
- Memenuhi halaman dengan ilustrasi AI generik.
- Membuat teks panjang dalam setiap section.
- Menampilkan grafik hanya sebagai dekorasi.

---

## 32. Prompt Desain untuk AI atau UI Generator

```text
Buat desain website dan dashboard untuk sistem equipment and asset
management dengan gaya modern enterprise SaaS yang terinspirasi oleh
kualitas visual MaintainX, tetapi tidak menyalin identitas mereknya.

Gunakan layout bersih, profesional, industrial, dan data-driven.
Dominasi background putih dan soft gray, dengan primary color biru
cerah, teks navy gelap, border tipis, radius 12–16px, serta shadow yang
lembut.

Landing page harus memiliki navbar sederhana, hero dua kolom dengan
headline yang berorientasi hasil, dua CTA, foto pekerja atau alat berat,
dan floating cards yang memperlihatkan UI aplikasi. Tambahkan logo
client, statistik performa, feature section dengan layout bergantian,
testimonial, FAQ, final CTA, dan footer lengkap.

Dashboard harus memiliki sidebar putih, active navigation berwarna
light blue, topbar, KPI cards, asset status chart, downtime trend,
maintenance schedule, critical asset list, dan tabel recent work orders.
Gunakan icon outline, status badge yang jelas, dan tipografi Inter.

Prioritaskan usability, hierarchy, white space, keterbacaan data, serta
responsive design untuk desktop, tablet, dan mobile. Hindari desain yang
terlihat seperti template AI, gradient berlebihan, glassmorphism, dan
elemen dekoratif yang tidak memiliki fungsi.
```

---

## 33. Prompt Khusus Halaman Dashboard

```text
Design a professional heavy-equipment asset management dashboard with a
clean enterprise SaaS visual language. Use a white app shell, a 248px
left sidebar, a light gray content background, dark navy typography,
and a bright blue primary action color.

Show KPI cards for total assets, online assets, maintenance due, and
critical alerts. Add an asset availability donut chart, downtime trend
line chart, upcoming maintenance list, critical equipment panel, and a
recent work order table.

Each asset status must use a semantic badge with icon and text. Use thin
borders, 12–16px card radius, subtle shadows, consistent 8px spacing,
and Inter typography. The interface must look realistic, operational,
and designed for project managers and field technicians rather than
looking decorative or AI-generated.
```

---

## 34. Checklist Implementasi

### Visual

- [ ] Primary color konsisten.
- [ ] Heading memiliki hierarchy jelas.
- [ ] Radius card konsisten.
- [ ] Shadow tidak berlebihan.
- [ ] Icon berasal dari satu library.
- [ ] Foto memiliki gaya yang sama.
- [ ] Status memiliki color, icon, dan label.

### UX

- [ ] Aksi utama terlihat dalam 3 detik.
- [ ] User dapat menemukan aset dengan search.
- [ ] Filter mudah di-reset.
- [ ] Critical alert muncul di area atas.
- [ ] Tabel mendukung sort dan pagination.
- [ ] Empty, loading, error, dan success state tersedia.
- [ ] Mobile navigation berfungsi dengan baik.

### Content

- [ ] Headline berorientasi manfaat.
- [ ] Deskripsi tidak terlalu panjang.
- [ ] Statistik memiliki konteks.
- [ ] CTA menggunakan kata kerja.
- [ ] Istilah aset konsisten.
- [ ] Data contoh terlihat realistis.

---

## 35. Kesimpulan

Kekuatan desain MaintainX bukan berasal dari dekorasi yang kompleks, melainkan dari kombinasi berikut:

```text
Clear value proposition
+ industrial photography
+ realistic product UI
+ measurable business outcomes
+ strong trust signals
+ consistent enterprise design system
```

Untuk website asset management, fokus utama sebaiknya berada pada:

- Kejelasan status aset.
- Kemudahan melihat masalah yang membutuhkan tindakan.
- Visualisasi histori dan performa alat.
- Workflow maintenance yang mudah dipahami.
- Konsistensi antara landing page dan aplikasi dashboard.

Gunakan MaintainX sebagai referensi dalam hal **hierarchy, credibility, product storytelling, dan operational clarity**, bukan untuk menyalin tampilan secara identik.
