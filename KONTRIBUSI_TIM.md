# 📊 Laporan Bobot & Persentase Kontribusi Pengerjaan Tim ServicePlan-BRA

> **Repositori**: `kanzdzakipr/ServicePlan-BRA`  
> **Tanggal Audit**: 18 Oktober 2026  
> **Basis Audit Git**: `b39285e` (`main`/`origin/main`, 18 Oktober 2026)  
> **Metode Evaluasi**: Analisis Komparatif Multi-Faktor (jumlah commit, deliverable fitur, dampak arsitektur, risiko produksi yang ditutup, pengujian, data, dan dokumentasi). Angka `Git Numstat` dihitung dari commit non-merge dan dipakai sebagai bukti volume, bukan sebagai satu-satunya penentu bobot.  
> **Skema Periode**: 
> - **Term 1** (Periode Awal: s/d 27 Juli 2026) — `[CLOSED]`
> - **Term 2** (Periode Lanjutan: 28 Juli – 28 Agustus 2026) — `[CLOSED FINAL]`
> - **Term 3** (Periode Berjalan: 29 Agustus – 18 Oktober 2026) — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`

---

## 🏆 Ringkasan Persentase Kontribusi Tim

### 📌 1. Akumulasi Total / All-Time (Term 1 + Term 2 + Term 3 per 18 Oktober 2026)

| Rank | Kontributor / Contributor | Total Commit | Git Numstat `+ / -` (non-merge) | Bobot Kontribusi Fitur & Arsitektur Utama | **Persentase Kontribusi Akumulasi** |
| :---: | :--- | :---: | :---: | :--- | :---: |
| 🥇 | **briannugraha** | 116 (34,0%) | +117.003 / -47.322 | Lead Architecture & Roadmap, Blueprint Implementation Plan 1, 2, & 17 Okt 2026 (747 baris), Hybrid DB Config, Asset Population, Live Map & GPS, Fuel Anomaly Engine, Cost Control, Asset 360°, Productivity & KPI, ApexCharts Engine, Monitoring Unit Refurbishment, Heavy Equipment Properties & Diagnostics Inspector single-view, **Modul User Management CRUD Lengkap (`api/users.php`, UI modals), Redesign Besar Unit Properties (Galeri 3x3, Hyperlink Dokumen STNK/KIR/SILO/JO, Kartu Vitals, Modal Histori Tersembunyi), Hyperlink Hover Unit ID Executive Dashboard**, Mobile navbar/CSS fixes, serta Dev-Ops tooling & Laragon sync automation (`sync_to_laragon.ps1`) | **34.4%** |
| 🥈 | **seagrass489-cell** | 76 (22,3%) | +73.079 / -18.719 | Parser Engine 9.400+ baris, data completeness, backend laporan/form, arsitektur security server-side, authentication/session/CSRF/CORS/RBAC, upload hardening, attack simulation, automated security tests, CI quality gate, **Mobile Responsive Framework** (`mobile-responsive.js`, `dashboard.css`), automated mobile layout audit (`mobile_layout_audit.ps1`), audit rekapitulasi tim (`rekap-revisi-tim-brian.md`), **Implementasi Audit Formula Matematika KPI** (`core/KpiCalculator.php`, `api/kpi.php`, `verify_kpi_math.php`), **Implementasi UI Tiket Work Order 12 Field** (`new_work_order_ui_smoke.js`), serta **Integrasi Work Order & KPI** | **25.4%** |
| 🥉 | **kanzdzakipr** | 71 (20,8%) | +17.526 / -6.918 | Inisialisasi repositori, implementasi RBAC 10 roles & navigation guards, WO target status & autofill workflow, downtime split target/actual & overdue styling, monitoring cards active filter & global reset, maintenance routing, sistem arsip DB-synced (`archived_items`), migrasi Hostinger, anti-cache API, **Core Architecture Engines** (`CostLedger.php`, `ProductivityCalculator.php`, `WorkOrderSupport.php`, `AssetIdentity.php`), 4 Automated Integration Test Suites, **Penuntasan Fitur Non-WO Non-JO** (PM Hull multi-PIC, integrasi dropdown multi-mekanik, sinkronisasi notulensi), serta tata kelola database dump & branch governance | **21.0%** |
| 4 | **bisamelihat** | 78 (22,9%) | +111.910 / -66.598 | Fondasi struktur proyek, Prisma/seeder architecture, dokumentasi model sistem, prototipe dashboard, integrasi skill UI anti-slop, sprint UI overhaul A1–A9, Condition Monitoring interactive modal, Spare Part Card, People & KPI Standar Kinerja / Daftar SPL, **Sprint Integrasi Masif 38 Non-Merge Commits Term 3** (17+ modul logistik & operasional BHW, LHO, SPB, PPB, SPPU, P2H, A2B, BAPP, SPL, MDE, PM, backend database), serta **Penuntasan 8 Batch Rencana Revisi 17 Okt 2026** (Batch 0 Baseline s/d Batch 7 Testing: notifikasi beda role `NotificationAudience`, identitas aset & audit nomor lambung, form tambah unit/onboarding PM `AssetOnboarding`, pencarian global aset `AssetSearch`, konsistensi WO/JO, gap bukti dokumen, anti-regresi biaya, dan smoke testing browser) | **19.2%** |
| **TOTAL** | **4 Kontributor** | **341 Commit** | **+319.518 / -139.557** | **Skor dinormalisasi menjadi 100% berdasarkan bukti Git dan dampak deliverable komprehensif lintas 3 periode per 18 Oktober 2026** | **100.0%** |

---

### 📌 2. Evaluasi Term 1 (Periode Awal: s/d 27 Juli 2026) — `[CLOSED]`

| Rank | Kontributor / Contributor | Commit Term 1 | Git Numstat `+ / -` (non-merge) | Bobot Kontribusi Fitur & Arsitektur Term 1 | **Persentase Term 1** |
| :---: | :--- | :---: | :---: | :--- | :---: |
| 🥇 | **briannugraha** | 45 (43,7%) | +55.120 / -10.289 | Blueprint Implementation Plan, Fuel Anomaly Engine, Cost Control & Valuasi, Leaflet & Google Maps Integration, Asset 360°, Productivity & KPI Modules | **42.5%** |
| 🥈 | **seagrass489-cell** | 27 (26,2%) | +24.875 / -331 | Parser Engine Data 9.400+ baris, Data Completeness Audit Checklist, Tabulasi Keuangan PO & SPB, Testing Master Asset & Form Laporan | **27.0%** |
| 🥉 | **bisamelihat** | 22 (21,4%) | +59.249 / -49.334 | Fondasi Struktur Proyek, Prisma ORM Architecture, Seeder Data Engine (`seeder.js`), PRD & Data Model Documentation, Early Dashboard Prototypes | **22.0%** |
| 4 | **kanzdzakipr** | 9 (8,7%) | +2.491 / -1.697 | Inisialisasi Repositori, HTML Scaffolding (`testkanz*.html`), Branch Governance, & Merge Integrasi Utama | **8.5%** |
| **SUBTOTAL** | **4 Kontributor** | **103 Commit** | **+141.735 / -61.651** | **Baseline Fondasi & Pengembangan Fitur Modul Utama** | **100.0%** |

---

### 📌 3. Evaluasi Term 2 (Periode Lanjutan: 28 Juli – 28 Agustus 2026) — `[CLOSED FINAL]`

| Rank | Kontributor / Contributor | Commit Term 2 | Git Numstat `+ / -` (non-merge) | Bobot Kontribusi Fitur & Arsitektur Term 2 | **Persentase Term 2** |
| :---: | :--- | :---: | :---: | :--- | :---: |
| 🥇 | **briannugraha** | 57 (33,1%) | +56.330 / -36.831 | Hybrid DB, asset population, live maps, Condition Monitoring dual-layer, material Ban & Grease, ApexCharts engine & interactive filtering, People & KPI, notification system, Executive Dashboard, Monitoring Unit grid 3x2 & title cleanup, RBAC master bypass, **Heavy Equipment Properties & Diagnostics Inspector single-view (Top Live Map, CANbus Gauges, Data Listing row-by-row zebra, 10-Tire Diagram, PAP Oil & P2H)**, dan hosting/Laragon sync | **33.0%** |
| 🥈 | **kanzdzakipr** | 56 (32,6%) | +8.453 / -4.723 | RBAC 10 roles & navigation guards, WO target status & autofill workflow, split downtime target vs actual & overdue styling, maintenance type routing, monitoring cards active filter & click reset, asset mapping schemas, sistem arsip MySQL `archived_items`, migrasi Hostinger, anti-cache API, dan Laragon DB protection | **31.0%** |
| 🥉 | **seagrass489-cell** | 42 (24,4%) | +43.178 / -17.858 | Backend laporan/form, security audit & threat modeling, server-side authentication, secure session, CSRF/CORS, RBAC, object-level authorization pada API, secure upload/download, deployment hardening, automated security test suite, dan GitHub Actions quality gate | **27.5%** |
| 4 | **bisamelihat** | 17 (9,9%) | +30.401 / -16.786 | Sprint UI overhaul A1–A9 (interaktivitas chart drilldown, filter status service berkala, refinement dashboard & monitoring), integrasi skill UI anti-slop, resolusi konflik UTF-8, Condition Monitoring interactive modal per kategori, Spare Part Card visual update, People & KPI Standar Kinerja & Daftar SPL, serta sinkronisasi Laragon & maintenance seeder | **8.5%** |
| **SUBTOTAL** | **4 Kontributor** | **172 Commit** | **+138.362 / -76.198** | **RBAC engine, WO workflow enhancement, UI overhaul A1-A9, security architecture & CI, sistem arsip DB, hybrid DB, ApexCharts interactivity, Equipment Properties Inspector, Condition Monitoring category modals, dan Hostinger/Laragon sync** | **100.0%** |

---

### 📌 4. Evaluasi Term 3 (Periode Berjalan: 29 Agustus – 18 Oktober 2026) — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`

> *Status: Periode Term 3 mencatat 65 commit aktif dengan lonjakan akselerasi pengerjaan 8 batch revisi operasional, modul User Management, perombakan besar Unit Properties, formula audit KPI, antarmuka tiket WO 12 field, hyperlink hover Unit ID Executive Dashboard, dan automated test suites.*

| Rank | Kontributor / Contributor | Commit Term 3 | Git Numstat `+ / -` (non-merge) | Fokus Deliverable & Dampak Teknis Term 3 | **Persentase Term 3** |
| :---: | :--- | :---: | :---: | :--- | :---: |
| 🥇 | **bisamelihat** | 39 (60,0%) | +22.817 / -1.173 | **Eksekutor Integrasi Terbesar & Penuntasan 8 Batch Revisi**: Memimpin 38 commit non-merge meliputi integrasi 17+ modul logistik & operasional (BHW-IN, BHW-OUT, LHO 1 & 2, SPB, PPB, Procurement, SPPU, P2H, A2B, BAPP, SPL, MDE, PM, backend DB), serta penuntasan 8 batch rencana revisi 17 Okt (Batch 0 Baseline, Batch 1 Notifikasi Multi-Role `NotificationAudience`, Batch 2 Identitas Aset & Audit Nomor Lambung, Batch 3 Form Tambah Unit Onboarding PM `AssetOnboarding`, Batch 4 Pencarian Global Aset GLB-A `AssetSearch`, Batch 5 & 6 Konsistensi WO/JO, Gap Dokumen & Proteksi Regresi Biaya/Produktivitas, Batch 7 Smoke Testing Browser Otomatis & Rencana Penuntasan) | **38.0%** |
| 🥈 | **briannugraha** | 13 (20,0%) | +4.514 / -306 | **Strategic Blueprint, User Management, Redesign Unit Properties & Interaktivitas Dashboard**: Master Blueprint 17 Okt 2026 (`implementation-plan-17102026.md` 747 baris), pembangunan modul **User Management (Manajemen Pengguna)** backend CRUD (`api/users.php` 311 baris) & UI modals Executive Dashboard, **Redesign Komprehensif Unit Properties** (Galeri Foto Grid 3x3 responsif & Lightbox, Dokumen Legalitas STNK/KIR/SILO/JO Hyperlink Hover Modal, Kartu Vitals Operasional Terpadu, Modal Histori Operator & Servis Tersembunyi, Audit Trail Lambung), **Interaktivitas Hyperlink Hover Unit ID Executive Dashboard** (`.unit-prop-hover-link` modal Tabulasi Semua Unit, WO Aktif, Attention Panel), DevOps Laragon sync utility hardening, serta tata kelola merge branch | **23.5%** |
| 🥉 | **kanzdzakipr** | 6 (9,2%) | +6.582 / -498 | **Enterprise Core Engines, Testing Suite & Penuntasan Non-WO Non-JO**: 4 enterprise core engines (`core/CostLedger.php` 522 baris, `core/ProductivityCalculator.php` 461 baris, `core/WorkOrderSupport.php` 129 baris, `core/AssetIdentity.php` 70 baris), penguatan controller API (`costs.php`, `productivity.php`, `work_orders.php`, `assets.php`), 4 automated integration test suites (+700 baris test), **Penuntasan Fitur Non-WO Non-JO** (PM Hull multi-PIC, integrasi dropdown dinamis multi-mekanik, pembaruan notulensi rapat `notulensi-pertemuan-rabu.md`), skrip mobile sidebar, dan sinkronisasi dump database SQL | **20.5%** |
| 4 | **seagrass489-cell** | 7 (10,8%) | +5.026 / -530 | **Mobile Framework Architecture, Formula Audit KPI & UI Tiket WO**: Mobile Responsive Framework (`scripts/mobile-responsive.js` 368 baris, `scripts/dashboard.css` 1.044 baris, `tests/mobile_layout_audit.ps1` 317 baris), audit rekapitulasi tim (`rekap-revisi-tim-brian.md`), **Implementasi Audit Formula Matematika KPI** (`core/KpiCalculator.php` 192 baris, `api/kpi.php` 151 baris, `tests/verify_kpi_math.php` 142 baris), **Implementasi UI Tiket WO 12 Field** (`scripts/dashboard.css` +622 baris, `scripts/dashboard.js` +385 baris, `tests/new_work_order_ui_smoke.js` 151 baris), serta **Integrasi Work Order & KPI** (`scripts/migrate_wo_fields_17102026.sql`, `tests/work_order_ticket_fields.php`) | **18.0%** |
| **SUBTOTAL** | **4 Kontributor** | **65 Commit** | **+38.939 / -2.507** | **Penyelesaian 8 batch revisi, master blueprint 17 Okt, User Management, Redesign Unit Properties, Hyperlink Hover Unit ID, Mobile Responsive Framework, 4 core engines, formula audit KPI, tiket WO 12 field, dan automated testing suites** | **100.0%** |

---

## 📐 Matriks Bobot Evaluasi Multi-Kriteria

Penilaian kontribusi menggunakan dua lapisan bukti:

1. **Bukti kuantitatif Git**: jumlah commit, commit non-merge, serta `numstat` penambahan/penghapusan baris.
2. **Dampak deliverable**: keluasan modul, kebaruan fitur, keputusan arsitektur, risiko produksi yang ditutup, pengujian otomatis, data, dan dokumentasi.

Snapshot bukti kuantitatif Term 3 (Periode 29 Agustus – 18 Oktober 2026):

| Kontributor | Porsi semua commit | Porsi commit non-merge | Porsi lines added | Skor Term 3 setelah audit deliverable |
| :--- | ---: | ---: | ---: | ---: |
| **bisamelihat** | 60,0% | 64,4% | 58,6% | **38,0%** |
| **briannugraha** | 20,0% | 18,6% | 11,6% | **23,5%** |
| **kanzdzakipr** | 9,2% | 6,8% | 16,9% | **20,5%** |
| **seagrass489-cell** | 10,8% | 10,2% | 12,9% | **18,0%** |

`Lines added` tidak langsung disamakan dengan kontribusi karena dapat terdistorsi oleh impor data, pemindahan file, hasil generator, atau reformat besar. Skor akhir menormalkan bukti Git dengan dampak deliverable yang dapat diuji dan digunakan.

Formula bobot periode akumulasi (Term 1, Term 2, & Term 3):

```
Total Skor Akumulasi = (Bobot Term 1 × 35%) + (Bobot Term 2 × 45%) + (Bobot Term 3 × 20%)
```

### Breakdown Distribusi Kontribusi:

```mermaid
pie title Distribusi Persentase Kontribusi Akumulasi Total (All-Time per 18 Oktober 2026)
    "briannugraha (34.4%)" : 34.4
    "seagrass489-cell (25.4%)" : 25.4
    "kanzdzakipr (21.0%)" : 21.0
    "bisamelihat (19.2%)" : 19.2
```

#### Perbandingan Distribusi per Term:

```mermaid
pie title Distribusi Kontribusi Term 1 (s/d 27 Juli 2026) - CLOSED
    "briannugraha (42.5%)" : 42.5
    "seagrass489-cell (27.0%)" : 27.0
    "bisamelihat (22.0%)" : 22.0
    "kanzdzakipr (8.5%)" : 8.5
```

```mermaid
pie title Distribusi Kontribusi Term 2 (28 Juli - 28 Agustus 2026) - CLOSED FINAL
    "briannugraha (33.0%)" : 33.0
    "kanzdzakipr (31.0%)" : 31.0
    "seagrass489-cell (27.5%)" : 27.5
    "bisamelihat (8.5%)" : 8.5
```

```mermaid
pie title Distribusi Kontribusi Term 3 (29 Agustus - 18 Oktober 2026) - UPDATED
    "bisamelihat (38.0%)" : 38.0
    "briannugraha (23.5%)" : 23.5
    "kanzdzakipr (20.5%)" : 20.5
    "seagrass489-cell (18.0%)" : 18.0
```

---

## 🔍 Detail Rincian Kontribusi per Kontributor

### 1. `briannugraha` — **34.4%** *(Lead Feature, System Architect & Strategic Roadmap)*
* **Total Commit**: 116 Commit (34,0% dari total 341 commit repositori).
* **Breakdown Commit**: 45 Commit (Term 1) + 57 Commit (Term 2) + 13 Commit (Term 3).
* **Kontribusi Kunci & Kebaruan Fitur**:
  * **Term 1**:
    - **Implementation Plan 1 & 2**: Merancang arsitektur menyeluruh sistem monitoring alat berat ServicePlan-BRA (`implementation-plan-1.md`, `implementation-plan-2.md`).
    - **Modul Fuel Management Engine**: Mengembangkan sistem deteksi anomali konsumsi solar real-time (LPH & KPL), penanganan modal FRM-BBM-01 & FRM-STK-02 sounding tangki.
    - **Modul Biaya & Cost Control**: Mengembangkan dashboard valuasi 7 unit utama, LPJ bulanan budget vs actual, register PO sparepart, dan perbandingan PM.
    - **Interaktif Spatial & Maps**: Integrasi Leaflet Live Map lokasi unit dan integrasi dinamis tombol Google Maps GPS pada Modal Detail 360° Unit.
    - **Modul Produktivitas & KPI**: Integrasi telematika KOMTRAX 18 unit, penandaan idling anomaly (>50%), dan audit armada standby 48 unit.
    - **Konversi Materi**: Menyusun dan mengonversi berkas spek material 1 hingga 14 ke dalam format markdown terstruktur.
  * **Term 2**:
    - **Database Normalization & Hybrid Config**: Menambahkan support konfigurasi database hybrid pada `api/db.php`, normalisasi skema SQL `ServicePlanBRA.sql`, dan sinkronisasi Laragon local environment (`7288c5d`, `8ed94b0`, `e703e3f`).
    - **Asset Population & Live Maps Update**: Penambahan populasi data aset tahap 1 & 2 (`bd0f1bc`, `260dcab`), perbaikan komponen Leaflet Live Maps dan tombol koordinat GPS (`63dcd5a`).
    - **UI Micro-Fixes & Status Badge**: Perbaikan encoding teks, penyelarasan rendering status badge UI, dan integrasi logogram resmi BRA (`8f348cf`, `a2dbe85`).
    - **Audit Data Completeness**: Melakukan peninjauan menyeluruh kesiapan 16 menu navigasi, update status dataset aktif, serta merumuskan 5 mekanisme teknis adaptasi API Controller.
    - **Reform Condition Monitoring**: Implementasi Dual-Layer Modal Rendering (`#globalCmModalContainer` at `document.body` level) & Universal Asset ID Extractor Regex (`^([A-Z0-9]{1,6}-\d{2,5})`) untuk seluruh 400+ entri `data.json`.
    - **Refurbish Master Asset & In-Place Popup**: Integrasi modal popup Kondisi tanpa pemindahan view navigasi, sinkronisasi `window.resolveAsset`, dan pembersihan duplikasi skrip.
    - **Refurbish Sparepart & Logistik, Biaya, & Pengaturan**: Penyesuaian layout responsive, manajemen role pengeluaran barang, dan visual audit brand.
    - **Populasi Datasets Material Ban & Grease**: Mempopulasikan data real dari laporan ban dan greasing ke modul Condition Monitoring & Executive Dashboard.
    - **ApexCharts Engine Full Implementation & Linkage**: Menggantikan grafik statis pada Executive Dashboard dengan ApexCharts interaktif (Donut Service Status, Donut Asset Category, Donut WO Status, Bar Jam Kerja Mekanik dengan target 176 jam, dan Area Gradient Tren Biaya Perbaikan Bulanan), serta linkage klik slice/legend chart ke filter tabel monitoring.
    - **Enhancement & Redesign People & KPI**: Membangun ApexCharts Area Chart interaktif untuk *Rekapitulasi Trend Nilai KPI Bulanan (Jan-Des 2026)* dengan annotation line serta redesign kartu KPI Head berikon FontAwesome (`fa-award`, `fa-clock-rotate-left`, `fa-bolt`, `fa-coins`).
    - **Heavy Equipment Properties & Diagnostics Inspector Subpage (`7c4b763`, `328f5c5`)**:
      - Membangun tampilan subpage mandiri `view-unit-properties` (SPA terintegrasi tanpa menambah menu sidebar baru).
      - Menata 4 modul data menjadi **1 halaman inspektur terpadu (*single continuous flow*)**.
      - Menempatkan **Peta Live Real-Time & Geofence Site** di bagian paling atas (Section 1) berdampingan dengan 4 Live CANbus Gauges (RPM, Coolant, Oil Pressure, Alternator) & tabel koordinat GPS / BAST.
      - Mentransformasi spesifikasi teknik dari kartu menjadi **Technical Data Listing 2-kolom** dengan pola belang berselang-seling horizontal per baris (*row-by-row zebra striping*) dan label tebal (*bold*).
      - Mengintegrasikan diagram visual 10 posisi roda ban 3 gandar (PSI/RTD), Program Analisa Pelumas (Oil PAP Lab Sampling), inspeksi P2H harian operator, dan log investigasi insiden TAR.
    - **Pembersihan Antarmuka Monitoring Unit**: Mengganti judul menjadi *"Monitoring Unit"*, menghapus subjudul deskripsi, menghapus capsule badge, dan membersihkan diagram alur integrasi status.
    - **RBAC Master Bypass & Admin Access Fix (`21cb18a`)**: Memastikan akun Administrator memiliki hak akses master penuh ke seluruh modul, implementasi fallback role auto-detection, dan proteksi event guard navigasi.
  * **Term 3** — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`:
    - **Master Implementation Blueprint 17 Oktober 2026 (`3bddec0`)**: Menyusun berkas cetak biru komprehensif `implementation-plan-17102026.md` (747 baris) berdasarkan rekaman Zoom BRA; merumuskan arsitektur 12 field WO & mitigasi RFU bersyarat target kejar, penambahan unit di modul PM dengan 12 field onboarding & audit information gap, serta 5 inisiatif global modular (GLB-A s/d GLB-E) yang masing-masing dilengkapi actionable executable prompts.
    - **Modul User Management / Manajemen Pengguna Terpadu (`9cf390c`)**:
      - Membangun backend controller lengkap `api/users.php` (+311 baris) dengan dukungan CRUD user, assignment role berbasis RBAC matriks, status keaktifan user, dan hashing kata sandi aman.
      - Mengembangkan antarmuka User Management pada Executive Dashboard (`dashboard.view.php` +490 baris, `dashboard.html` +488 baris) dengan modal tambah/edit pengguna, modal reset password, filter role dinamis, dan tabel user responsif.
    - **Redesign Besar Antarmuka Unit Properties (`578a526`)**:
      - Memisahkan galeri foto dokumentasi unit menjadi kartu mandiri di kolom kanan dengan layout grid 3x3 responsif, indikator sudut pandang foto, dan modal lightbox preview foto (`modalPropPhotoPreview`).
      - Mentransformasi dokumen legalitas unit (STNK, KIR, SILO, PDF JO) menjadi hyperlink hover interaktif berstatus badge dengan modal viewer terintegrasi (`modalPropDocumentViewer`) untuk membuka/mengunduh berkas PDF.
      - Menata ulang metrik operasional vital (Operator saat ini, Servis terakhir, Interval, Target berikutnya & estimasi) menjadi kartu metrik terpadu yang selaras dengan vitals HM dan geolokasi.
      - Menyembunyikan tabulasi riwayat operator dan riwayat perawatan ke dalam modal popup terdedikasi (`modalPropOperatorHistory`, `modalPropServiceHistory`) yang dipicu dari ikon riwayat pada kartu masing-masing.
      - Meleburkan riwayat nomor lambung ke dalam hyperlink hover kode lambung lengkap dengan modal audit trail (`modalPropHullHistory`) dan tombol ganti nomor lambung.
    - **Interaktivitas Hyperlink Hover Unit ID Executive Dashboard (`b39285e`)**:
      - Mengembangkan stylesheet `.unit-prop-hover-link`, `.unit-code-text`, dan `.unit-prop-hover-icon` pada `scripts/unit-properties.css` dengan micro-interaction visual, highlight warna primer, dan efek garis bawah dotted-to-solid.
      - Mengintegrasikan hyperlink hover pada kolom Unit ID di modal Tabulasi Semua Unit Alat (`modalStatusDetail`), tabel Work Order Aktif, dan badge kode unit pada Panel Perhatian WO Emergency di Executive Dashboard, memungkinkan navigasi instan satu-klik ke lembar spesifikasi Unit Properties.
    - **DevOps, Laragon Sync & Database Protection (`3bddec0`, `07e59e8`, `d04aee6`)**: Hardening skrip sinkronisasi Laragon `scripts/sync_to_laragon.ps1` (IPv4 host binding eksplisit `-h 127.0.0.1 -P 3306`), perbaikan file migrasi database `scripts/migrate_wo_fields_17102026.sql`, sinkronisasi skema SQL, dan tata kelola merge branch integrasi (`390b46c`, `999f009`, `b83aac4`).
    - **Mobile Navbar & Responsive Styling (`5632ca4`, `ebf54c7`, `2df57a2`)**: Mengembangkan dan menyempurnakan stylesheet `scripts/dashboard.css` untuk adaptasi layar mobile, perbaikan navigasi navbar pada layar sentuh, dan penataan mobile checkpoint view.

---

### 2. `seagrass489-cell` — **25.4%** *(Data Engineering, Security Architecture & Mobile Lead)*
* **Total Commit**: 76 Commit (22,3% dari total 341 commit repositori).
* **Breakdown Commit**: 27 Commit (Term 1) + 42 Commit (Term 2) + 7 Commit (Term 3).
* **Kontribusi Kunci & Kebaruan Fitur**:
  * **Term 1**:
    - **Sistem Parser Data 9.400+ Baris**: Mengamankan dan mengunci parser data workbook besar (`ab602ab FINAL_LOCKDOWN: Sistem parser 9400 baris sukses diamankan`).
    - **Data Completeness Checklist**: Menyusun dan memperbarui `DATA_COMPLETENESS_CHECKLIST.md` untuk memastikan 100% kelengkapan data lapangan.
    - **Tabulasi Data Keuangan & Logistik**: Menyusun dokumen tabulasi detail untuk PO Trakindo Utama (`PO_DFT_01508`), PO United Tractors (`PO 10969`), SPPU Zona 4, dan Rencana PM Feb 2026.
    - **Pengujian & Verifikasi UI**: Menguji keterhubungan antarmuka Master Asset, Form Laporan, Inspeksi P2H, dan Dashboard executive.
  * **Term 2**:
    - **Backend Laporan & Form (`268e576`)**: Mengembangkan integrasi backend untuk modul laporan dan form operasional agar data tidak berhenti pada state browser saja.
    - **Security Audit & Threat Modeling (`d045e01`, `75e6468`)**: Menyusun audit menyeluruh terhadap authentication, authorization, injection, file handling, database privilege, secrets, dependency, dan production readiness; melengkapinya dengan attack-simulation checklist berbasis bukti PASS/FAIL.
    - **Server-Side Authentication & Session Security (`d045e01`)**: Membangun `api/auth.php`, shared security bootstrap `api/security.php`, adaptive password hashing, session regeneration, idle/absolute timeout, CSRF token, CORS allowlist, dan logout server-side.
    - **RBAC & Route Authorization (`d045e01`, `9e198d5`)**: Menerapkan permission matrix deny-by-default, migrasi RBAC, serta object/location authorization pada controller aset, work order, laporan, logistik, inspeksi, fuel, PM, accident, archive, init, dan sync.
    - **Credential & Deployment Hardening (`a2f0367`, `e7bdd38`)**: Memindahkan konfigurasi sensitif ke environment, menyediakan local temporary-password tooling, memperketat `.htaccess`, dan memperbaiki alur sinkronisasi Laragon tanpa mengekspos kredensial produksi.
    - **Secure Upload & Authorized Download (`9e198d5`)**: Menambahkan validasi MIME-ekstensi, nama file acak, pencegahan eksekusi PHP, helper upload terpusat, serta download controller yang memerlukan authorization.
    - **Automated Security Quality Gate (`9e198d5`, `a3cce11`)**: Menambahkan unit, static, dan HTTP smoke tests (`tests/security_unit.php`, `tests/security_static.php`, `tests/security_http_smoke.php`), runner PowerShell, serta workflow GitHub Actions `.github/workflows/security-tests.yml`.
  * **Term 3** — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`:
    - **Mobile Responsive Framework Architecture (`0cbdba3`, `3e67bc4`)**: Mengembangkan arsitektur antarmuka mobile komprehensif dengan penambahan 1.044 baris penataan responsif pada `scripts/dashboard.css`, 368 baris controller interaksi gesture sentuh & navigasi drawer pada `scripts/mobile-responsive.js`, serta optimasi mobile layout untuk lembar teknis `scripts/unit-properties.css`.
    - **Automated Mobile Layout Quality Gate (`0cbdba3`, `3e67bc4`)**: Merancang dan mengintegrasikan suite pengujian otomatis `tests/mobile_layout_audit.ps1` (317 baris PowerShell automation) untuk memverifikasi konsistensi tampilan elemen, tabel, dan kartu pada seluruh breakpoint layar ponsel/tablet.
    - **Implementasi Audit Formula Matematika KPI (`58c7350`)**:
      - Membangun engine perhitungan KPI armada alat berat `core/KpiCalculator.php` (192 baris) dan controller `api/kpi.php` (151 baris).
      - Mengimplementasikan formula standar Physical Availability (PA), Mechanical Availability (MA), Use of Availability (UA), MTBF, MTTR, serta benchmark target 176 jam kerja mekanik.
      - Membangun automated verification script `tests/verify_kpi_math.php` (142 baris) untuk memvalidasi akurasi perhitungan terhadap data historis.
    - **Implementasi UI Tiket Work Order 12 Field (`60607e0`)**:
      - Mengembangkan antarmuka tiket Work Order baru pada `dashboard.view.php` & `dashboard.html`.
      - Menambahkan stylesheet tiket WO pada `scripts/dashboard.css` (+622 baris) dan alur autofill cerdas pada `scripts/dashboard.js` (+385 baris).
      - Membangun automated browser smoke test `tests/new_work_order_ui_smoke.js` (151 baris).
    - **Integrasi Work Order dan KPI (`b197b54`)**: Menghubungkan transisi siklus tiket WO dengan kalkulasi metrik downtime dan KPI operasional, controller `api/sync.php` dan `api/work_orders.php` (+229 baris), migrasi database `scripts/migrate_wo_fields_17102026.sql` (75 baris), serta testing `tests/work_order_ticket_fields.php` (93 baris).
    - **Cross-Team Revision Review & Audit Documentation (`900c058`, `124b6d0`)**: Menyusun dokumen rekapitulasi teknis `rekap-revisi-tim-brian.md` (434 baris) sebagai checklist perbandingan, verifikasi kesiapan deliverable, dan tata kelola merge branch `main`.

---

### 3. `kanzdzakipr` — **21.0%** *(Repository Owner, Enterprise Logic Engines & Integration Lead)*
* **Total Commit**: 71 Commit (20,8% dari total 341 commit repositori).
* **Breakdown Commit**: 9 Commit (Term 1) + 56 Commit (Term 2) + 6 Commit (Term 3).
* **Kontribusi Kunci & Kebaruan Fitur**:
  * **Term 1**:
    - **Inisialisasi Repositori**: Membuat repositori utama `kanzdzakipr/ServicePlan-BRA` dan struktur percabangan awal.
    - **Scaffolding HTML Awal**: Mengembangkan berkas prototipe awal (`test.html`, `testkanz.html`, `testkanz2.html`, `testkanz3.html`).
    - **Governance & Integration**: Menangani proses *merge branch main*, penyelarasan prompt, dan koordinasi sinkronisasi tim.
  * **Term 2**:
    - **Implementasi RBAC 10 Roles & Navigation Guards (`065be6c`, `8a7d6b1`, `a37c574`)**: Merancang dan mengintegrasikan matriks RBAC 10 roles di `scripts/rbac.js`, menambahkan pengaman navigasi `showView()`, batasan aksi administratif dan tombol approve, serta modal override hak akses cell.
    - **Work Order Status Workflow & PM Detail Modal (`fb9bd48`)**: Memperbarui alur status Work Order dengan target status yang dapat dipilih secara dinamis, logika autofill cerdas, dan integrasi modal detail Preventive Maintenance baru.
    - **Downtime Split Target vs Actual & Overdue Styling (`e3b324f`)**: Memisahkan kolom downtime menjadi nilai target dan aktual dengan styling penanda overdue visual pada tampilan dashboard.
    - **Maintenance Type Selection & Route Routing (`112f2fd`)**: Menambahkan pilihan jenis pemeliharaan pada formulir Work Order baru dan routing otomatis ke tampilan modul spesifik setelah submit.
    - **Monitoring Cards Active Filter & Global Reset (`8c9b37c`)**: Mengembangkan indikator filter aktif auto-fading dan mekanisme klik global reset untuk kartu ringkasan status monitoring.
    - **Dashboard Script & Operation Form Schemas (`7937d2d`)**: Menambahkan skrip dashboard untuk pemetaan aset serta mendefinisikan skema formulir terstandarisasi untuk logistik dan operasional.
    - **Sistem Manajemen Arsip DB-Synced (`archived_items`)**: Mengembangkan infrastruktur backend arsip terhubung tabel `archived_items` dengan fitur auto-create table pada `archive.php`, binding handler click Master Asset ke `window.archiveData`, serta pembebasan `display:none` pada view arsip (`b53f4df`, `8b1ec15`, `2ea3bbc`, `be14899`).
    - **Global Scope Handlers & Dynamic Table Re-render**: Mengekspos handler `renderHistoryTable` dan `renderAccidentTable` ke scope global `window`, serta mengaitkan `inspectionHistory` & `initialAccidentLogs` agar data yang diarsipkan langsung hilang dari tabel aktif tanpa reload (`8e1849c`, `a18279c`, `cf41233`).
    - **Anti-Cache Headers & Laragon Sync DB Protection**: Menambahkan header anti-browser-caching pada respon API dan menyisipkan skema tabel `archived_items` ke SQL dump untuk mencegah penghapusan tabel saat sync Laragon (`3a29de3`, `58e52fd`).
    - **Migrasi Data Dummy ke Database Hostinger (`9103193`)**: Melakukan migrasi data dummy ke database MySQL Hostinger (`u646470441_ServicePlanBRA`), update `api/init.php` (`b870565`), dan koneksi `api/db.php`.
    - **Form, Logistik & Workflow Integration**: Memperbaiki form backend lintas perangkat (`e11efd6`), mengaktifkan searchbar, memasukkan inspeksi/unit parts, memperbarui plat kendaraan logistik, dan menyelaraskan fuel logs (`5b632f8`, `292ab96`, `3402709`, `f3394cc`).
  * **Term 3** — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`:
    - **Enterprise Core Business Logic Engines (`5d73ab1`, `049a173`)**:
      - `core/CostLedger.php` (+522 baris): Engine enterprise perhitungan buku besar biaya operasional alat berat, alokasi anggaran, dan kalkulasi variansi budget vs actual.
      - `core/ProductivityCalculator.php` (+461 baris): Engine kalkulasi produktivitas armada tambang terstandarisasi (jam kerja, breakdown, standby, scheduled hours).
      - `core/WorkOrderSupport.php` (+129 baris): Engine pendukung alur pembuatan dan penutupan tiket Work Order di backend.
      - `core/AssetIdentity.php` (+70 baris): Engine pemetaan multi-identitas unit alat berat (kode unit, nomor lambung, serial number).
    - **API Hardening & Controller Extensions (`5d73ab1`, `049a173`)**: Memperluas endpoint backend pada `api/costs.php`, `api/notifications.php`, `api/productivity.php`, `api/work_orders.php`, dan `api/assets.php`.
    - **Automated Integration Test Suite (`5d73ab1`, `049a173`)**: Membangun 4 berkas pengujian integrasi otomatis komprehensif: `tests/cost_integration.php` (226 baris), `tests/notification_integration.php` (110 baris), `tests/productivity_integration.php` (152 baris), dan `tests/master_asset_integration.php` (211 baris).
    - **Penuntasan Fitur Non-WO Non-JO (`2818824`)**:
      - Mengembangkan integrasi Preventive Maintenance Hull multi-PIC dan penugasan multi-mekanik pada formulir PM.
      - Memperbarui antarmuka `dashboard.view.php` (+251 baris), `dashboard.html` (+240 baris), dan `scripts/dashboard.js` (+357 baris).
      - Membangun test suite integrasi `tests/pm_hull_multi_pic_integration.php` (+104 baris) dan memperbarui notulensi rapat tim `notulensi-pertemuan-rabu.md` (+53 baris).
    - **Testing & Meeting Documentation (`5d73ab1`, `049a173`)**: Menyusun dokumentasi resmi `notulensi-pertemuan-rabu.md` (105 baris) dan `PANDUAN_PENGUJIAN_REVISI_1.md` (135 baris).
    - **Mobile Sidebar Interaction & Stylesheet (`6fa6653`)**: Mengembangkan skrip interaksi sidebar mobile dan stylesheet pendukung dashboard.
    - **Database Dump Sync & Governance Merge (`d881d4c`, `9aadd01`)**: Memperbarui dump database SQL dan mengelola merge branch utama.

---

### 4. `bisamelihat` — **19.2%** *(Core Foundation, Module Integration Sprint & Database Architect)*
* **Total Commit**: 78 Commit (22,9% dari total 341 commit repositori).
* **Breakdown Commit**: 22 Commit (Term 1) + 17 Commit (Term 2) + 39 Commit (Term 3).
* **Kontribusi Kunci & Kebaruan Fitur**:
  * **Term 1**:
    - **Inisialisasi Struktur & Prisma**: Membangun struktur awal repositori Next.js/Prisma, dokumentasi skill set, dan arsitektur database.
    - **Data Seeder Engine (`seeder.js`)**: Membangun generator data mock realistis yang menyuplai `data.json` (unit aset, work order, riwayat BBM, biaya).
    - **Dokumentasi Sistem Utama**: Menyusun `docs/PRD.md`, `docs/DATA_MODEL.md`, `docs/IMPLEMENTATION_PLAN.md`, `docs/USER_ROLES.md`, dan `docs/STATUS_RULES.md`.
    - **Prototipe Dashboard V2**: Mengembangkan prototipe tampilan awal dashboard dan form antarmuka.
  * **Term 2**:
    - **Sprint UI Overhaul A1–A9 (`4896273` s/d `749e23b`, `e72fdaa`)**: Mengeksekusi rangkaian 9 paket pembaruan antarmuka terstruktur (refinement tata letak kartu, dropdown filter status service berkala, drilldown detail ApexCharts saat diklik, penataan visual tabel monitoring, dan polishing akhir).
    - **Condition Monitoring Category Modals & Cutting Bit Cleanup (`4d67318`)**: Memperbarui menu Condition Monitoring sehingga setiap kartu kategori dapat diklik untuk menampilkan modal informasi detail per kategori dan menghapus entri cutting bit yang sudah tidak relevan.
    - **Spare Part Card UI Component (`ebd17df`)**: Mengembangkan dan menata komponen visual kartu suku cadang.
    - **People & KPI Standar Kinerja & Daftar SPL (`c14d7ea`)**: Menambahkan tabel standar kinerja pegawai dan daftar Surat Perintah Lembur (SPL) pada modul People & KPI.
    - **Skill UI Anti-Slop Integration (`94fd0ba`)**: Menerapkan arsitektur desain anti-slop UI untuk meningkatkan standar estetika dashboard.
    - **Visual Redesign Tipis Antarmuka (`993dd4d`, `07b9fb6`)**: Melakukan penyegaran estetika tipis pada tampilan dashboard.
    - **UTF-8 Encoding Conflict Resolution**: Menyelesaikan konflik pengkodean UTF-8 pada `dashboard.js` dan sinkronisasi Laragon (`6ae6db9`, `3377eef`).
    - **Maintenance Data Seeder & Documentation**: Pemeliharaan data seeder dan penyusunan markdown parts (`f397d5f`, `f927bb5`).
  * **Term 3** — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`:
    - **Sprint Integrasi Masif 38 Non-Merge Commits**: Memimpin volume implementasi fitur operasional dan penuntasan batch revisi tertinggi di Term 3 (+22.817 / -1.173 baris kode).
    - **Penuntasan 8 Batch Rencana Revisi 17 Oktober 2026**:
      - **Batch 0 - Baseline & Environment Sync (`fe32eb5`)**: Menyusun dokumen `HASIL_BATCH_0_BASELINE_17102026.md` dan `RENCANA_BATCH_REVISI_17102026.md`, serta hardening `scripts/sync_to_laragon.ps1`.
      - **Batch 1 - Notifikasi Beda Role (`4304273`)**: Membangun engine audience multi-role `core/NotificationAudience.php` (141 baris), controller `api/notifications.php` (+267 baris), migrasi database `scripts/migrate_notification_recipients_17102026.sql`, integration test `tests/notification_audience_integration.php`, dan panduan `PANDUAN_PENGUJIAN_NOTIFIKASI_BATCH_1.md`.
      - **Batch 2 - Identitas Aset & Audit Nomor Lambung (`1e20e5e`)**: Migrasi database `scripts/migrate_asset_hull_history_17102026.sql`, perluasan `api/assets.php`, visualisasi riwayat lambung di `scripts/unit-properties.js`, dan penyusunan `PANDUAN_PENGUJIAN_BATCH_2_17102026.md`.
      - **Batch 3 - Form Tambah Unit & Onboarding PM (`655bfbd`)**: Membangun `core/AssetOnboarding.php` (87 baris), controller upload berkas `api/asset_files.php` (76 baris), modal tambah unit di PM pada `dashboard.view.php` (+198 baris) & `scripts/dashboard.js` (+214 baris), migrasi `scripts/migrate_asset_onboarding_17102026.sql` (69 baris), unit test `tests/asset_onboarding_unit.php`, dan panduan `PANDUAN_PENGUJIAN_BATCH_3_17102026.md`.
      - **Batch 4 - Pencarian Global Aset GLB-A (`86270fd`)**: Membangun engine pencarian `core/AssetSearch.php` (58 baris), endpoint `api/assets.php`, migrasi indeks SQL `scripts/migrate_asset_search_indexes_17102026.sql`, autocomplete dropdown di `scripts/dashboard.js` (+144 baris), test suite `tests/asset_search_integration.php`, dan panduan `PANDUAN_PENGUJIAN_BATCH_4_17102026.md`.
      - **Batch 5 & 6 - Konsistensi WO & JO, Gap Bukti Fisik & Anti-Regresi Biaya (`5b9941d`)**: Controller dokumen `api/work_order_document.php` (61 baris), verifikasi konsistensi WO/JO, mitigasi regresi kalkulasi biaya `core/CostLedger.php` dan produktivitas `core/ProductivityCalculator.php`, script `scripts/ensure_cost_ledger_local.php`, audit test `tests/batch6_readonly_audit.php`, serta panduan pengujian Batch 5 & Batch 6.
      - **Batch 7 - Smoke Testing Browser & Rencana Penuntasan (`0cd453a`, `a9bbffe`)**: Pengecekan ulang alur kerja dengan automated browser smoke tests (`tests/work_order_detail_browser_smoke.js`, `tests/new_work_order_ui_smoke.js`, `tests/contract_csv_browser_smoke.js`), panduan `PANDUAN_PENGUJIAN_BATCH_7_17102026.md`, dan penyusunan roadmap `RENCANA_PENUNTASAN_BATCH_LANJUTAN.md` (132 baris).
    - **Integrasi Pergudangan & Logistik Lapangan**:
      - Transaksi bukti fisik: Integrasi form BHW-IN (`a725d89`), BHW-OUT (`60040ef`), LHO 1 & 2 (`9eac319`, `28bea5f`).
      - Pengadaan & inventaris: Integrasi laporan SPB (`d1c0483`), PPB ke menu parts (`b633c37`), Procurement Monitoring ke menu parts (`3791504`), dan SPPU Yard (`1de3437`).
      - Administrasi penerimaan: Bukti Kirim/Terima barang (`84fd762`) dan Laporan BAPP (`3917bf6`).
    - **Integrasi Operasional Lapangan & Pemeliharaan**:
      - Form P2H operasional harian operator (`040a38f`).
      - Integrasi unit A2B (`7abae1a`) dan penautan laporan A2B ke titik lokasi koordinat GPS (`abc5363`).
      - Pembuatan tiket Work Order otomatis dari Laporan Repair & Overhaul (`2e89b3b`).
      - Integrasi LPK ke modul Preventive Maintenance (`2cabcf7`).
      - Pemeriksaan alat berat ke modul P2H (`dc41675`) dan integrasi data MDE ke Master Asset (`7969c74`).
    - **Integrasi SDM & Tools Komponen**: Integrasi form Surat Perintah Lembur / SPL (`b74594f`) dan modul pemantauan Cutting Bit (`ffdd5bc`).
    - **Backend Database & Siklus Revisi Rapat**:
      - Eksekusi koneksi backend database (`87fa056`), verifikasi pengecekan akhir dan keamanan (`9b5ffaa`), serta dokumentasi `mdrekap` (`4e3d141`).
      - Penyelesaian rangkaian siklus revisi rapat operasional: Revisi 3 WO (`a55061c`), Revisi 4 SPB dari WO (`5fe7503`), Revisi 5 (`7e981b2`), dan Revisi 8 (`c09b382`).

---

## 📈 Log Riwayat Graph Commit Utama

### 📌 Log Commit Term 2 (28 Juli – 28 Agustus 2026) — `[CLOSED FINAL]`

```git
* 328f5c5 (briannugraha) minor
* 7c4b763 (briannugraha) add unit properties
* c14d7ea (bisamelihat) people n kpi addin standar kinerja n daftar spl
* ebd17df (bisamelihat) spare part card
* 4d67318 (bisamelihat) G Conditioning monitoring, hapus cutting bit dan masing masing card bisa dipencet dan menampilkan info per kategroi
* 6f37831 (briannugraha) update kontribusi tim per 25 agustus
* fb9bd48 (kanzdzakipr) feat: update WO status workflow with selectable target status, enhanced autofill logic, and new PM detail modal.
* e72fdaa (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 749e23b (bisamelihat) A8 dan A9
* e3b324f (kanzdzakipr) feat: split downtime column into target and actual values with overdue styling in dashboard view
* 112f2fd (kanzdzakipr) feat: add maintenance type selection to new work order form and route to specific views upon submission
* 8c9b37c (kanzdzakipr) feat: add auto-fading active filter indicator and global click reset for monitoring dashboard cards
* 9897e45 (bisamelihat) A6 dan A7
* 8883c69 (bisamelihat) A5 bikin charnta bisa lebih detail saat diklkik
* 220a3f3 (bisamelihat) A4 dropdown di status service berkala
* 21cb18a (briannugraha) fix rbac
* 7937d2d (kanzdzakipr) feat: add dashboard scripts for asset mapping and define form schemas for logistics and operations
* 4a3711b (bisamelihat) A2 dan A3
* 4896273 (bisamelihat) A1
* 8a7d6b1 (kanzdzakipr) Merge pull request #1 from kanzdzakipr/tambahin-role
* a37c574 (kanzdzakipr) feat: implement role-based access control (RBAC) with secure navigation guards and administrative override restrictions.
* 065be6c (kanzdzakipr) tambah role
* 6e25f34 (briannugraha) minor
* 0330472 (briannugraha) redesign UI
* a3cce11 (seagrass489-cell) tes audit baru
* 66b9b9a (seagrass489-cell) tes
* 9e198d5 (seagrass489-cell) update security architecture, object authorization & upload + automated tests
* 241ecbc (briannugraha) fix hosting
* 699898a (briannugraha) update kontribusi tim per 9 agustus
* 75e6468 (seagrass489-cell) tes security
* e7bdd38 (seagrass489-cell) testlogin
* e9f5342 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 368373d (seagrass489-cell) tes
* c896528 (briannugraha) monitoring unit
* a2f0367 (seagrass489-cell) push password
* 9aa8ea5 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 9d8691c (seagrass489-cell) tes audit baru
* a2e321b (briannugraha) refurbish exec dashboard 2 & people-kpi
* 7f3a654 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 5af24cf (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* d045e01 (seagrass489-cell) test security audit
* 19dc7e5 (briannugraha) refurbish exec dashboard
* d48437e (briannugraha) added notification system
* 7cad144 (briannugraha) productivity menu
* f3394cc (kanzdzakipr) revisi fuel logs
* eb119f3 (kanzdzakipr) benerin archive ajg akhirnya
* 268c967 (kanzdzakipr) Fix accident archiving table to use globalData instead of initialAccidentLogs
* 4dff784 (kanzdzakipr) Resolve remaining conflict in dashboard.js
* 696d810 (kanzdzakipr) Resolve merge conflicts by keeping local bugfixes and rejecting mojibake
* bbc84f4 (kanzdzakipr) benerin arsip
* c41ed46 (briannugraha) update kontribusi tim per 8 agustus 18.47
* 249ba9c (briannugraha) fix bug
* 2bc5114 (briannugraha) fix bug
* 8f348cf (briannugraha) fix mojibake, status badge
* 3a29de3 (kanzdzakipr) fix(archive): Prevent browser caching of API data and stop dropping database on Laragon sync
* 8c16f56 (kanzdzakipr) Update dashboard.js
* 2ea3bbc (kanzdzakipr) fix(asset): Correct archive button click handler on Master Asset to use window.archiveData
* 8b1ec15 (kanzdzakipr) feat(archive): Auto-create archived_items table in archive.php to prevent missing table errors
* cf41233 (kanzdzakipr) fix(archive): Force re-render of History and Accident tables to hide archived items after initialization
* a18279c (kanzdzakipr) fix(archive): Expose inspectionHistory and initialAccidentLogs to global window scope so archive data sync works
* a502a9d (kanzdzakipr) fix(css): Remove orphaned css properties causing syntax error in report-counter
* 6ae6db9 (bisamelihat) Merge remote: resolve UTF-8 encoding conflicts in dashboard.js (keep clean HEAD)
* 993dd4d (bisamelihat) redesign
* a2dbe85 (briannugraha) add logogram
* 07b9fb6 (bisamelihat) redesign tipis
* 8e1849c (kanzdzakipr) fix(ui): Expose renderHistoryTable and renderAccidentTable to global window for archive refresh
* 58e52fd (kanzdzakipr) fix(db): Add archived_items table to SQL dump so it survives sync_to_laragon
* b53f4df (kanzdzakipr) feat(archive): Sync archive functionality with database state using archived_items table
* be14899 (kanzdzakipr) fix(ui): Remove display:none from archive view so it shows properly & render on click
* 894629a (kanzdzakipr) fix(ui): Fix javascript syntax error in archive functions & push to team
* 62221e4 (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 9530336 (kanzdzakipr) arsip
* 219b5f5 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 260dcab (briannugraha) populate asset pt.2
* 94fd0ba (bisamelihat) skill buat ui anti slop
* bd0f1bc (briannugraha) populate asset pt.1
* 3377eef (bisamelihat) sync laragon
* 63dcd5a (briannugraha) map update, fix minor
* 7288c5d (briannugraha) normalize database, sync laragon
* e703e3f (briannugraha) upload sql
* 8ed94b0 (briannugraha) hybrid environment support for db config
* b4d5e8c (briannugraha) update kontribusi tim & data completeness per 6 agustus
* 268e576 (seagrass489-cell) laporan & form backend
* 7e98617 (seagrass489-cell) tes
* 9103193 (kanzdzakipr) migrasi dummy ke database hostinger
* e11efd6 (kanzdzakipr) fiksasi semua form untuk backend bekerja dari berbagai device
* b4f8c96 (kanzdzakipr) rollback sebelum diotak atik
* 3fbcb9e (kanzdzakipr) lagi
* b870565 (kanzdzakipr) Update init.php
* a646a11 (kanzdzakipr) Update dashboard.html
* 1871357 (kanzdzakipr) hapus inspeksi p2h
* ed6e8d2 (briannugraha) minor
* 0668d42 (briannugraha) data completeness update (again)
* 677a1a1 (briannugraha) update kontribusi tim per 4 agustus
* 9a31ab9 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 9525c8f (briannugraha) auditing data completeness as of 4th august
* d9cd912 (kanzdzakipr) inspek p2h
* a52c1ed (kanzdzakipr) Update dashboard.html
* d000ac5 (kanzdzakipr) Update dashboard.html
* 7ad3786 (kanzdzakipr) Update SeederDataJson.php
* 7507164 (kanzdzakipr) hilangin CLI seederdatajson
* b16f1e5 (kanzdzakipr) sesuaiin db.php untuk hostinger
* 4cf69f0 (kanzdzakipr) Update schema.sql
* 0b49c00 (kanzdzakipr) penyesuaian backend + pinpoint upload hostinger
* 6355400 (briannugraha) update kontribusi tim per 2 agustus
* 509d335 (kanzdzakipr) Update dashboard.js
* 5b632f8 (kanzdzakipr) bikin searchbarnya kerja
* 3466596 (kanzdzakipr) 1. Kode nomor SPB terinput No polisi untuk Unit 2. tambahkan sheet oli pemakaian atau pemasukan 3. tambahkan fitur upload foto Barang keluar, barang masuk, dan input Laporan PDF dari administrasi logistik
* 9c21ed1 (seagrass489-cell) tes
* d153d22 (seagrass489-cell) Merge branches 'main' and 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 170f0e4 (seagrass489-cell) tes workflow
* 33a5c87 (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 88de28f (kanzdzakipr) formatting DT-000XX
* 005ba3f (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* a506771 (seagrass489-cell) tesgung
* de04dea (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 3402709 (kanzdzakipr) Plat Kendaraan Spare Part Logistik
* 9a0be67 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* adf3dbb (seagrass489-cell) tes
* 90da9cd (kanzdzakipr) input inspeksi dumptruck
* 52d8fa7 (kanzdzakipr) cocokin plat=id unit
* 292ab96 (kanzdzakipr) input SN/Plat kendaraan
* 4b2176a (seagrass489-cell) tes widya
* 0ca3a51 (seagrass489-cell) tesgung
* cc50cee (seagrass489-cell) tesgung
* fdc9965 (kanzdzakipr) update sparepart & logistik + PINPOINT UPLOAD HOSTINGER
* 91e6668 (kanzdzakipr) tambah foto cutting bit
* 1e36a6e (kanzdzakipr) unit parts
* f397d5f (bisamelihat) md parts
* f927bb5 (bisamelihat) parts
* 70a5cf3 (seagrass489-cell) tesgung
* d6acc86 (briannugraha) refurbish master asset
* b3ad2a1 (briannugraha) refurbish pengaturan
* e5c423b (briannugraha) resizing menu biaya
* e7c3fa1 (briannugraha) refurbish sparepart & logistik
* c8e96a4 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* b9f48f1 (briannugraha) reform condition monitoring, minor fix @ laporan&form
* b2ae15a (briannugraha) update kontribusi_tim.md
* 2d6bed2 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* a8b1615 (briannugraha) enrichment condition monitoring menu
* 40d9447 (seagrass489-cell) tesgung
* 62efdd5 (seagrass489-cell) tes
* 854d4fd (briannugraha) override sedikit 3
* afb1474 (seagrass489-cell) tes
* 1e77c1b (briannugraha) resolving dashboard.html
* 7985a33 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 9bc9ce2 (seagrass489-cell) tes
* 204b57f (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 2df2501 (briannugraha) fix work order
* 64efb42 (seagrass489-cell) tes
* 41c06b5 (seagrass489-cell) tes agung
* 94cb004 (seagrass489-cell) tes
* c295ec9 (seagrass489-cell) tes
* 55d6883 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 2cecca9 (seagrass489-cell) tes
* 0c3ee5f (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 541bb19 (briannugraha) checkpoint fix work order
* 683a222 (seagrass489-cell) tes
* 240165b (seagrass489-cell) tes
* 9645db4 (briannugraha) override sedikit 2
* cfda4e2 (briannugraha) override sedikit
* e74a3d1 (briannugraha) upload markdown cutting bit
* b93ff06 (seagrass489-cell) tes
* 56ba898 (briannugraha) insert logo BRA
* 3d9b3a1 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 71cb0aa (briannugraha) fix kpi dashboard
* dd2d30e (seagrass489-cell) tes agug
* e739722 (seagrass489-cell) tesagung
* 0d358b1 (seagrass489-cell) tesmd
* 531190d (seagrass489-cell) tesmonitoring
* da5aa36 (briannugraha) enrichment menu dashboard & work order
* f1d1ec2 (briannugraha) rapi2 file
```

---

### 📌 Log Commit Term 3 (29 Agustus – 18 Oktober 2026) — `[OPEN / ONGOING - UPDATED PER 18 OKTOBER 2026]`

```git
* b39285e (briannugraha) minor detail
* 578a526 (briannugraha) update unit properties
* 9cf390c (briannugraha) add user management, fix unit properties
* 07e59e8 (briannugraha) fix eror
* a9bbffe (bisamelihat) tiket wo
* 0cd453a (bisamelihat) cek ulang
* 5b9941d (bisamelihat) konsistensi wo dan jo, perbaikan gap bukti dan regresi biaya
* 86270fd (bisamelihat) pencarian glb A
* 655bfbd (bisamelihat) form tambah unit
* 1e20e5e (bisamelihat) rev identitas aset dan audit nomor
* 4304273 (bisamelihat) nyelesain notif beda role
* fe32eb5 (bisamelihat) sinkronisasi ken
* 9aadd01 (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 2818824 (kanzdzakipr) kelarin yang non-WO non-JO
* b197b54 (seagrass489-cell) Integrasi WO dan KPI
* 60607e0 (seagrass489-cell) Implementasi UI Tiket WO
* 58c7350 (seagrass489-cell) Implementasi Audit Formula KPI
* b83aac4 (briannugraha) kontribusi tim per 17 okt
* 390b46c (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 3bddec0 (briannugraha) implementation plan 17 okt
* d881d4c (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 5d73ab1 (kanzdzakipr) all revisi pertemuan senin
* 999f009 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* d04aee6 (briannugraha) update database
* 049a173 (kanzdzakipr) revisi 1
* c09b382 (bisamelihat) rev 8
* d42ab40 (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 7e981b2 (bisamelihat) rev 5
* 124b6d0 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 900c058 (seagrass489-cell) tes
* 5fe7503 (bisamelihat) revisi 4 spb dari wo
* a55061c (bisamelihat) revisi 3 WO
* 4e3d141 (bisamelihat) mdrekap
* 9b5ffaa (bisamelihat) pengecekan akhir dan security
* 87fa056 (bisamelihat) backend database
* ffdd5bc (bisamelihat) integrasi cutting bit
* b74594f (bisamelihat) integrasi surat perintah lembuh
* abc5363 (bisamelihat) Laporan A2B ke lokasi GPS
* 2cabcf7 (bisamelihat) LPK ke preventive
* dc41675 (bisamelihat) pemeriksaan alat berat ke p2h
* 7969c74 (bisamelihat) MDE to Master Asset
* 3d20474 (bisamelihat) masalahserver
* 7e19f65 (bisamelihat) testing
* 1de3437 (bisamelihat) integrasi sppu yard
* 84fd762 (bisamelihat) integrasi form bukti kirim/terima
* 3917bf6 (bisamelihat) integrasi laporan bapp
* 3791504 (bisamelihat) integrasi form procuremnet monitoring ke menu parts
* b633c37 (bisamelihat) integrasi ppb ke menu parts
* d1c0483 (bisamelihat) integrasi laporan SPB
* 2e89b3b (bisamelihat) nambah WO dari laporan repair and overhaul
* 7abae1a (bisamelihat)  integrasi A2B
* 824c4ec (bisamelihat) perubahan
* 28bea5f (bisamelihat) integrasi LHO 2
* 9eac319 (bisamelihat) integrasi LHO
* 040a38f (bisamelihat) integrasi p2h
* 60040ef (bisamelihat) integrasi BHW-OUT
* a725d89 (bisamelihat) integrasi BHW-IN.
* 8317a87 (bisamelihat) listcheck
* 5632ca4 (briannugraha) fix navbar in mobile view
* 3e67bc4 (seagrass489-cell) tes
* 0cbdba3 (seagrass489-cell) tes
* ebf54c7 (briannugraha) checkpoint mobile view 2
* 2df57a2 (briannugraha) checkpoint mobile view
* 6fa6653 (kanzdzakipr) feat: add dashboard stylesheet and mobile sidebar interaction scripts
* fd6f9c1 (briannugraha) fix access role permit
* be56f32 (briannugraha) update kontribusi tim per 28 agustus (close term 2)
```

---

### 📌 Log Commit Term 1 (s/d 27 Juli 2026) — `[CLOSED]`

```git
* f1b6ee3 (briannugraha) google maps support
* 579cafa (seagrass489-cell) tesmasterasset
* 7c3da85 (seagrass489-cell) tesdashboard
* 8f2ccd1 (seagrass489-cell) tesinpeksi
* 124a250 (briannugraha) enrichment dashboard
* e7c2d37 (briannugraha) add new file
* 90f6705 (briannugraha) fix inaccessibity
* 87d55e1 (bisamelihat) seeder
* 2b80316 (bisamelihat) nambah data
* 30d3c0f (bisamelihat) fuel history
* fc83866 (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* da20603 (kanzdzakipr) nyoba lagi
* 7aa8d7a (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* ab265d9 (briannugraha) remove
* 7d99cbc (kanzdzakipr) nyoba ngeprompt yg dari agung
* dc1c00b (briannugraha) remove
* 6e40bd0 (briannugraha) inspection under last commit
* f0d1b0c (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 6645adc (seagrass489-cell) tessimporfile
* ab602ab (seagrass489-cell) FINAL_LOCKDOWN: Sistem parser 9400 baris sukses diamankan
* f0fa786 (briannugraha) .gitignore & .htaccess initialization
* b953741 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 82c4f75 (briannugraha) update multi user role assignment scheme
* 22e71a2 (bisamelihat) admin
* 8acec8f (briannugraha) spatial position support
* ffc3126 (briannugraha) update checklist
* 19c629c (briannugraha) schema.sql preparation & update checklist
* 1dd50ef (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 0f8f897 (seagrass489-cell) checklist for data completeness
* 828da4c (briannugraha) raw material
* 509eecc (briannugraha) menu produktivitas
* 7ca53d6 (seagrass489-cell) tes
* dcd094f (kanzdzakipr) hapus testkanz
* 41c4bdf (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* ca3239f (seagrass489-cell) laporan & form
* ff911fa (briannugraha) menu KPI & accident
* e338dfd (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 36eef80 (seagrass489-cell) testtimoni menu
* 2ef9c6c (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 040d644 (briannugraha) implementation plan 2
* fb0aeef (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* f89ca6a (seagrass489-cell) test
* 6519792 (briannugraha) implementation plan 1
* f34fc17 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* cfc7c18 (seagrass489-cell) tesgung
* 374a4f2 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* f3b59eb (briannugraha) markdown 14
* c7fafa7 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 82948fa (seagrass489-cell) tesgung
* 049b2f0 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 7db9232 (briannugraha) markdown 13
* 370f568 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* e7f2010 (seagrass489-cell) tesgung
* f6b575f (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA into restore-testkanz
* 295e1d4 (bisamelihat) form
* df49286 (briannugraha) markdown 12
* 0e7f3b9 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 6d67285 (briannugraha) markdown 11
* 4a4065d (bisamelihat) dashboard v2
* c535bec (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 41d9917 (briannugraha) markdown 10
* 22da611 (bisamelihat) prompt dashboard
* 843f05e (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* cb1b43c (briannugraha) markdown 9
* 4d76763 (bisamelihat) arsipbuwid
* 89b2ffe (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA into restore-testkanz
* 1450c93 (bisamelihat) purge
* 415f52a (briannugraha) markdown 8
* 89113c2 (briannugraha) markdown 7
* 44218ae (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* f127dc8 (briannugraha) markdown 6
* b15569a (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 94ce0e9 (seagrass489-cell) tesgung
* 4d3f716 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 0f66093 (briannugraha) markdown 5
* 1d79726 (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 4c56428 (bisamelihat) prisma
* 1b700f9 (kanzdzakipr) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 0b06ff7 (kanzdzakipr) Create testkanz3.html
* ecad77e (kanzdzakipr) Create testkanz2.html
* 9ddf2d7 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 3143550 (briannugraha) markdown 4
* 7734782 (bisamelihat) fixed
* 757093c (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* aa68148 (bisamelihat) feat: initialize project structure and add Prisma documentation skill sets for agents.
* 4094109 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 221ab9c (seagrass489-cell) tes
* 6336ea9 (kanzdzakipr) ganti teskanz
* ffe5545 (bisamelihat) milestone1
* 3684071 (seagrass489-cell) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* 6309b6b (seagrass489-cell) agungs
* 4acb276 (bisamelihat) prompt 1
* 595599e (bisamelihat) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* c7c5bfa (briannugraha) markdown 3
* dd12856 (bisamelihat) pdf
* 0a89b10 (briannugraha) Merge branch 'main' of https://github.com/kanzdzakipr/ServicePlan-BRA
* c27910e (briannugraha) markdown 2
* e41d283 (kanzdzakipr) Create test.html
* 34f6ead (briannugraha) markdown 1
* 90bbc22 (bisamelihat) migrate
* 7ac8680 (bisamelihat) test login
```

---

*Laporan ini diperbarui secara otomatis berdasarkan audit komprehensif git log history & graph repositori ServicePlan-BRA per 18 Oktober 2026 (Progres Berjalan Term 3).*
