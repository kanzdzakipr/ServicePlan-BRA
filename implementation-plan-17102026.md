# Implementation Plan 17102026: Rencana Pengembangan Fitur ServicePlan-BRA

> **Metadata Dokumen**:
> - **Tanggal Penyusunan**: 17 Oktober 2026
> - **Dasar Perencanaan**: Hasil Notulensi & Rekaman/Pertemuan Zoom Tim
> - **Status**: Menunggu injeksi snippet percakapan dan pokok-pokok pembahasan dari pengguna
> - **Target Modul**: 
>   1. Fitur Pembuatan Tiket Work Order (WO)
>   2. Fitur Penambahan Unit Baru (Asset Onboarding)
>   3. Pengembangan Global (Arsitektur, UI/UX, & Sinkronisasi Sistem)

---

## Ringkasan Eksekutif & Struktur Dokumen

Dokumen ini disusun sebagai cetak biru (*implementation plan*) pengembangan lanjutan sistem **ServicePlan-BRA**. Struktur rencana kerja ini difokuskan pada tiga pilar utama dan dirancang fleksibel untuk menampung butir-butir kesepakatan teknis, alur operasional, serta kebutuhan bisnis yang dibahas pada sesi pertemuan Zoom.

---

## 1. Outline 1: Pengembangan Fitur Pembuatan Tiket Work Order (WO)

Rencana pengembangan fitur pembuatan tiket Work Order (WO) dan Job Order (JO) difokuskan pada pelengkapan seluruh parameter teknis operasional lapangan PT Bina Rekayasa Anugerah, dengan prioritas awal pada **perancangan antarmuka (UI Form)** yang ergonomis, modern, dan tidak membebani penggunaan di lapangan/mobile.

---

### 1.1 Spesifikasi Lengkap Field Pembuatan Tiket WO & JO

Berdasarkan kesepakatan notulensi dan arahan operasional, modal form pembuatan tiket akan diperluas untuk memuat 12 atribut utama secara komprehensif:

| No | Nama Field Operasional | Atribut Teknis / DB | Tipe Kontrol UI | Logika Bisnis & Validasi Lapangan |
| :---: | :--- | :--- | :--- | :--- |
| **1** | **Jenis Alat Berat** | `equipment_type` / `category` | Read-only Auto-fill / Select | Terisi otomatis saat unit dipilih (misal: *Excavator, Bulldozer, Dump Truck, Wheel Loader, Motor Grader*). Dapat disesuaikan bila unit sewa belum terdata. |
| **2** | **Nomor Unit** | `asset_id` / `unit_code` | Smart Search Dropdown | Identitas resmi aset BRA (misal: `EX-200`, `DT-01`). Mendukung pencarian instan kode unit. |
| **3** | **Nomor Lambung Project** | `hull_number` | Text Input (Auto-fill & Editable) | Nomor lambung operasional di site proyek. Terisi otomatis dari data aset namun dapat di-*override/rename* langsung jika nomor fisik unit telah berganti. |
| **4** | **HM / KM Aktual** | `current_hm_km` | Numeric Input | Meteran jam kerja (*Hour Meter*) atau odometer (*Kilometer*) saat unit mengalami kendala. Wajib diisi untuk kontrol interval servis dan audit pemakaian. |
| **5** | **Tanggal & Waktu Trouble** | `trouble_at` | Datetime-Local Picker | Waktu persis saat kendala atau temuan breakdown terjadi di lapangan (default: tanggal & jam saat form dibuka, dapat dimundurkan bila ada jeda lapor). |
| **6** | **Lokasi Trouble** | `trouble_location` / `raw_location` | Text Input / Datalist | Titik koordinat / area kerja spesifik kejadian (contoh: *Pit Barat Front 3, Disposal Selatan, Hauling Road KM 14, Workshop Induk*). |
| **7** | **PIC Mekanik / Teknisi** | `assigned_mechanics` | Multi-select Chip (Ketik-Nama) | Penugasan satu atau lebih teknisi penanggung jawab. Mekanisme input menggunakan sistem *type-and-tag* (mirip kolom input email) agar ramah layar sentuh HP/tablet. |
| **8** | **Rentang Pengerjaan (Start s.d. Selesai)** | `repair_started_at` & `repair_completed_at` | Datetime-Local Pickers | - **Start Mulai**: Waktu intervensi fisik dimulai.<br>- **Waktu Selesai**: Otomatis terisi timestamp saat status WO digeser ke *Closed*, namun tetap dapat dikoreksi manual jika pelaporan penutupan tiket tertunda. |
| **9** | **Uraian Trouble & Pekerjaan Mekanik** | `issue_description` & `repair_action` | Textarea (Multi-line) | - **Uraian Trouble**: Gejala kerusakan, indikator panel menyala, atau bunyi abnormal.<br>- **Tindakan Perbaikan**: Langkah teknis perbaikan yang sudah atau akan dikerjakan mekanik di lapangan. |
| **10** | **Sifat Maintenance** | `maintenance_type` | Segmented Button / Select | Klasifikasi pekerjaan pemeliharaan:<br>• `Corrective` (Kerusakan insidental / breakdown)<br>• `Preventive` (Perawatan rutin / PM berkala)<br>• `Inspection` (Temuan pemeriksaan rutin)<br>• `Emergency` (Penanganan darurat langsung di front kerja) |
| **11** | **Kendala Teknik / Tindakan Mitigasi** | `mitigation_action` & `is_forced_operation` | Checkbox + Textarea Khusus | **Fitur Operasi Bersyarat**: Unit tetap dilaporkan rusak di WO, namun disertai penjelasan bahwa alat **sementara tetap dipaksakan beroperasi karena mengejar target produksi proyek**. Status unit diatur menjadi *RFU Bersyarat* dengan catatan mitigasi risiko agar kerusakan tidak menjalar. |
| **12** | **Dokumentasi Foto Lapangan** | `before_photo_url` / attachment | Drag-and-drop Image Upload | Bukti fisik kerusakan awal di lapangan (*before photo*) sebelum perbaikan, komponen yang bocor/patah, atau nota serah terima darurat. |

---

### 1.2 Rencana Awalan: Perancangan Antarmuka UI (Form Layout & UX)

Sesuai strategi awal, fokus pengerjaan tahap pertama adalah **mempersiapkan tata letak antarmuka (UI Mockup & Component Integration)** agar seluruh field tertata bersih, intuitif, dan tidak membingungkan pengguna di lapangan:

1. **Pengelompokan Form Modular (Sectional Card Layout)**:
   Form tidak disajikan sebagai satu kolom panjang yang melelahkan, melainkan dibagi ke dalam 4 blok visual teratur:
   - **Blok A — Identitas Alat & Lokasi Kejadian**:
     - *Baris 1*: Pencarian Unit (`Nomor Unit`) $\rightarrow$ Auto-fill `Jenis Alat` & `Nomor Lambung`.
     - *Baris 2*: `Lokasi Trouble` (Datalist area tambang) & `HM/KM Saat Trouble`.
   - **Blok B — Waktu Kejadian & Pengerjaan**:
     - *Baris 1*: `Waktu Trouble Terjadi` & `Sifat Maintenance` (Corrective / Preventive / Inspection / Emergency).
     - *Baris 2*: `Waktu Mulai Pekerjaan` & `Estimasi / Waktu Selesai`.
   - **Blok C — Diagnosis Masalah & Tindakan Mitigasi**:
     - `Uraian Jenis Trouble` (Keluhan & indikasi awal).
     - `Tindakan Perbaikan yang Dilakukan`.
     - **Card Khusus Mitigasi**: Switch toggle/checkbox: *"Alat Tetap Beroperasi (Target Kejar)"* $\rightarrow$ saat dicentang, membuka textarea *Tindakan Mitigasi & Batasan Operasi* dengan penanda status visual *RFU Bersyarat*.
   - **Blok D — Penugasan Tim & Dokumentasi**:
     - Komponen input multi-PIC mekanik berbasis tag/chip (ketik nama, tekan Enter/klik saran, muncul tag nama teknisi).
     - Kotak upload dokumentasi foto dengan *preview thumbnail* instan.

2. **Karakteristik Responsif Mobile-First**:
   - Di layar desktop/laptop: Tampilan modal melebar 2 kolom (*grid 50-50*) agar efisien ruang.
   - Di layar HP/tablet mekanik: Menyesuaikan otomatis menjadi single-column dengan tombol aksi jempol yang lebar (*finger-friendly tap targets*).

---

### 1.3 Tahapan Eksekusi Teknis (Roadmap Fitur WO)

1. **Tahap 1 (Desain & Mockup UI Frontend)**:
   - Merevisi struktur modal `#modalNewWO` pada [dashboard.view.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/dashboard.view.php) dan [dashboard.html](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/dashboard.html).
   - Menambahkan styling CSS untuk sectional card layout, chip input multi-PIC, toggle mitigasi target kejar, dan upload preview foto.
2. **Tahap 2 (Kesiapan Database & Backend)**:
   - Menambahkan kolom pendukung pada tabel `work_orders`: `trouble_at`, `trouble_location`, `mitigation_action`, `is_forced_operation`, `repair_completed_at`, `assigned_mechanics_json`.
   - Menyesuaikan payload handler pada [api/work_orders.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/api/work_orders.php) dan [api/sync.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/api/sync.php).
3. **Tahap 3 (Validasi Transaksi Status Unit)**:
   - Jika `is_forced_operation = 1`: status unit tidak diubah ke `BREAKDOWN` total, melainkan diset `STANDBY` atau `OPERATING (RFU Bersyarat)` dengan indikator warning kuning pada dashboard fleet monitor.
   - Jika `is_forced_operation = 0` dan unit berhenti total: otomatis update status unit ke `BREAKDOWN` (merah).

---

### 1.4 Actionable Execution Prompt (Siap Eksekusi UI Tiket WO)

> 🚀 **Cara Menggunakan Actionable Prompt Ini**:
> Kapan pun Anda siap memulai implementasi antarmuka, Anda cukup menyalin teks prompt di bawah atau memanggil dokumen ini dengan instruksi: *"Eksekusi prompt Section 1.4 di @implementation-plan-17102026.md"*.

```markdown
Eksekusi Tahap 1: Implementasi Perancangan UI Form Modal Pembuatan Tiket WO & JO pada dashboard.view.php dan dashboard.html sesuai cetak biru Section 1.1 dan 1.2 di implementation-plan-17102026.md:

1. Target Berkas:
   - dashboard.view.php
   - dashboard.html

2. Ruang Lingkup Pekerjaan UI:
   a. Rombak kontainer modal `#modalNewWO` menjadi Sectional Card Grid (4 blok visual terstruktur: Blok A Identitas & Lokasi, Blok B Waktu & Sifat Maintenance, Blok C Diagnosis & Mitigasi Target Kejar, Blok D Multi-PIC & Dokumentasi Foto).
   b. Lengkapi seluruh 12 field input:
      - Nomor Unit (searchable dropdown) dengan auto-fill instan ke Jenis Alat Berat & Nomor Lambung
      - Nomor Lambung Project (editable text input, terisi default dari master tapi bisa di-rename langsung)
      - Jenis Alat Berat (auto-populate dari master unit)
      - HM / KM Aktual saat trouble (numeric input)
      - Tanggal & Waktu Trouble (datetime-local picker default waktu sekarang)
      - Lokasi Trouble (input dengan datalist area tambang BRA: Pit Barat, Front 2, Disposal, Hauling KM 14, Workshop)
      - Sifat Maintenance (segmented button/select: Corrective, Preventive, Inspection, Emergency)
      - Rentang Pengerjaan: Waktu Mulai & Estimasi/Waktu Selesai (datetime-local pickers)
      - Uraian Jenis Trouble (textarea keluhan/indikasi)
      - Tindakan Perbaikan Mekanik (textarea tindakan teknis)
      - Card Kendala Teknik & Mitigasi Operasi: Switch toggle "Alat Tetap Beroperasi (Target Kejar)" yang membuka textarea Tindakan Mitigasi dan menampilkan badge status "RFU Bersyarat"
      - PIC Mekanik Multi-Pilih: Komponen autocomplete tagger / chips (ketik nama, enter/klik saran, muncul chip nama dengan tombol hapus)
      - Dokumentasi Foto Lapangan: Dropzone upload foto dengan instant thumbnail preview
   c. Tambahkan CSS styling terpadu yang responsif untuk desktop (2-kolom) dan mobile (single-column finger-friendly) selaras dengan tema dashboard.
   d. Perbarui JavaScript pendukung: `openNewWOModal()`, listener auto-populate data unit, chip tagger multi-PIC, dan toggle mitigasi.
   e. Eksekusi sinkronisasi hasil ke Laragon menggunakan: powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 1.5 Asesmen Rencana Pengembangan Backend & Database Connection (Tiket WO & JO)

Untuk menjamin seluruh 12 field pada form antarmuka dapat tersimpan, terintegrasi, dan berinteraksi secara konsisten dengan basis data MySQL lokal Laragon, berikut adalah asesmen teknis backend dan rancangan koneksinya:

#### 1. Audit Skema Database & Skrip Migrasi DDL (`work_orders`)
Berdasarkan pemeriksaan struktur tabel eksisting `work_orders`, terdapat sejumlah field yang sudah tersedia dan sejumlah field baru yang wajib ditambahkan melalui migrasi skema SQL:

| Field Form UI | Status di DB Eksisting | Tipe Kolom Target | Rekomendasi Aksi Skema |
| :--- | :---: | :--- | :--- |
| **Nomor Unit (`asset_id`)** | **Sudah Ada** | `varchar(100)` | Dipertahankan (Foreign Key ke `assets`). |
| **Nomor Lambung Project** | *Belum Ada* | `varchar(100) NULL` | **Tambah Kolom**: `hull_number` (Menyimpan snapshot nomor lambung saat trouble terjadi). |
| **Jenis Alat Berat** | *Belum Ada di WO* | `varchar(100) NULL` | **Tambah Kolom**: `equipment_type` (Snapshot kategori unit, atau di-JOIN ke `assets.category`). |
| **HM / KM Aktual Trouble** | *Belum Ada di WO* | `decimal(12,2) NULL` | **Tambah Kolom**: `current_hm_km` (Jam operasi / KM meteran saat breakdown). |
| **Tanggal & Waktu Trouble** | Parsial (`reported_at`) | `datetime NULL` | **Tambah Kolom**: `trouble_at` (Waktu insiden di lapangan, berbeda dari `reported_at`). |
| **Lokasi Trouble** | **Sudah Ada** (`raw_location`) | `varchar(255) NULL` | Gunakan kolom eksisting `raw_location` (dan opsional link `location_id`). |
| **PIC Mekanik Multi-Pilih** | Parsial (`assigned_mechanic`) | `text NULL` | **Tambah Kolom**: `assigned_mechanics_json` (Menyimpan array JSON teknisi), sementara `assigned_mechanic` tetap diisi string gabungan untuk kompatibilitas mundur. |
| **Start s.d. Selesai** | **Sudah Ada** | `timestamp NULL` | Gunakan `repair_started_at` dan `closed_at`. Tambah kolom opsional `estimated_completion_at datetime NULL`. |
| **Uraian Trouble & Tindakan** | **Sudah Ada** | `text NULL` | Gunakan `issue_description` dan `repair_action`. |
| **Sifat Maintenance** | **Sudah Ada** | `enum(...)` | Gunakan `maintenance_type` (Corrective, Preventive, Inspection, Emergency). |
| **Kendala Teknik & Mitigasi** | *Belum Ada* | `tinyint(1)` & `text` | **Tambah Kolom**: `is_forced_operation tinyint(1) DEFAULT 0` dan `mitigation_action text NULL`. |
| **Dokumentasi Foto** | **Sudah Ada** | `varchar(255) NULL` | Gunakan `before_photo_url` (dan `after_photo_url`). |

**Skrip SQL Migrasi Target (`scripts/migrate_wo_fields_17102026.sql`)**:
```sql
ALTER TABLE work_orders
    ADD COLUMN IF NOT EXISTS hull_number VARCHAR(100) NULL AFTER asset_id,
    ADD COLUMN IF NOT EXISTS equipment_type VARCHAR(100) NULL AFTER hull_number,
    ADD COLUMN IF NOT EXISTS current_hm_km DECIMAL(12,2) NULL AFTER equipment_type,
    ADD COLUMN IF NOT EXISTS trouble_at DATETIME NULL AFTER current_hm_km,
    ADD COLUMN IF NOT EXISTS is_forced_operation TINYINT(1) NOT NULL DEFAULT 0 AFTER is_downtime,
    ADD COLUMN IF NOT EXISTS mitigation_action TEXT NULL AFTER is_forced_operation,
    ADD COLUMN IF NOT EXISTS assigned_mechanics_json TEXT NULL AFTER assigned_mechanic,
    ADD COLUMN IF NOT EXISTS estimated_completion_at DATETIME NULL AFTER repair_started_at;
```

---

#### 2. Arsitektur Interaksi Backend Controller (`api/work_orders.php` & `api/sync.php`)

1. **Penanganan Permintaan POST (`api/work_orders.php`)**:
   - Menerima payload JSON lengkap dari modal frontend.
   - Melakukan validasi *server-side* (ID Unit, Uraian Trouble, Jam/HM numerik valid).
   - Mengonversi array PIC mekanik (`["Apeng", "Maman"]`) menjadi format `assigned_mechanics_json` serta menggabungkannya ke string `assigned_mechanic` ("Apeng, Maman").
   - **Atomic Transaction & Sinkronisasi Status Unit**:
     - Jika `is_forced_operation = 1`: status unit di master `assets` **TIDAK** diubah menjadi `BREAKDOWN`, melainkan diset `STANDBY` atau `OPERATING` dengan catatan mitigasi aktif (*RFU Bersyarat*).
     - Jika `is_forced_operation = 0` dan downtime = `Yes`: status unit otomatis diubah menjadi `BREAKDOWN`.
     - Update meteran `last_hm_km` pada aset jika `current_hm_km` lebih tinggi dari data master.
   - **Audit Trail & Notifikasi**: Otomatis menyuntikkan event `CREATED` ke `work_order_timeline` dan menerbitkan notifikasi ke `system_notifications`.

2. **Sinkronisasi Transaksional (`api/sync.php`)**:
   - Memperbarui kueri *Prepared Statement* `$stmtWO` (`INSERT ... ON DUPLICATE KEY UPDATE`) agar turut menyertakan kolom `hull_number`, `equipment_type`, `current_hm_km`, `trouble_at`, `is_forced_operation`, `mitigation_action`, `assigned_mechanics_json`, dan `estimated_completion_at`.

3. **Media Upload Handler Foto Dokumentasi**:
   - Menyediakan endpoint penerima upload file gambar berbatas ukuran (maks 5MB, format JPEG/PNG/WebP) yang disimpan di direktori `uploads/work_orders/` dan menghasilkan path aman untuk disimpan pada field `before_photo_url`.

---

### 1.6 Actionable Execution Prompt (Backend Connection & Migration Tiket WO)

> 🚀 **Cara Menggunakan Actionable Prompt Ini**:
> Kapan pun Anda siap mengeksekusi integrasi backend dan migrasi database untuk tiket WO, salin teks prompt di bawah atau panggil dengan instruksi: *"Eksekusi prompt Section 1.6 di @implementation-plan-17102026.md"*.

```markdown
Eksekusi Tahap 2: Implementasi Backend Connection & Database Migration Fitur Pembuatan Tiket WO & JO sesuai Section 1.5 di implementation-plan-17102026.md:

1. Target Berkas:
   - scripts/migrate_wo_fields_17102026.sql (Berkas Migrasi SQL baru)
   - api/work_orders.php (Controller Utama Work Orders)
   - api/sync.php (Controller Upsert Transaksional)
   - core/WorkOrderSupport.php (Helper Data WO & Logistik)
   - scripts/sync_to_laragon.ps1 (Script Sinkronisasi)

2. Ruang Lingkup Pekerjaan Backend:
   a. Buat berkas migrasi SQL `scripts/migrate_wo_fields_17102026.sql` yang menambahkan kolom:
      - `hull_number` VARCHAR(100)
      - `equipment_type` VARCHAR(100)
      - `current_hm_km` DECIMAL(12,2)
      - `trouble_at` DATETIME
      - `is_forced_operation` TINYINT(1) DEFAULT 0
      - `mitigation_action` TEXT
      - `assigned_mechanics_json` TEXT
      - `estimated_completion_at` DATETIME
   b. Eksekusi skrip migrasi tersebut ke basis data lokal MySQL Laragon `u646470441_ServicePlanBRA` menggunakan CLI mysql.exe.
   c. Perbarui `api/work_orders.php`:
      - Di blok POST: Ambil dan validasi seluruh field baru (hull_number, equipment_type, current_hm_km, trouble_at, raw_location, is_forced_operation, mitigation_action, assigned_mechanics, estimated_completion_at, before_photo_url).
      - Tangani logika `is_forced_operation`: jika bernilai 1, unit diset status 'STANDBY' / 'OPERATING' (RFU Bersyarat), bukan 'BREAKDOWN'.
      - Simpan JSON array untuk `assigned_mechanics_json` dan string koma untuk `assigned_mechanic`.
      - Di blok GET: Sertakan seluruh kolom baru pada kueri SELECT agar terbaca di detail Kanban dan modal.
      - Di blok PUT: Dukung pembaruan kolom-kolom baru.
   d. Perbarui `api/sync.php`:
      - Sesuaikan query `$stmtWO` pada bagian INSERT dan ON DUPLICATE KEY UPDATE untuk mendukung field baru.
   e. Verifikasi integritas relasi dan jalankan sinkronisasi ke Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

## 2. Outline 2: Pengembangan Fitur Penambahan Unit Baru (Asset Onboarding)

Rencana pengembangan modul penambahan unit baru (*Asset Onboarding*) dan pemeliharaan preventif (*Preventive Maintenance*) difokuskan pada penguatan integritas data master aset PT Bina Rekayasa Anugerah, penyatuan alur kerja servis berkala, serta penyediaan galeri dokumentasi fisik terpadu.

Sesuai metodologi yang disepakati, rencana kerja disusun dalam 4 pilar: **Perancangan Antarmuka UI**, **Actionable Prompt UI**, **Koneksi Backend & Skema Database**, serta **Actionable Prompt Backend**.

---

### 2.1 Step Awalan & Perancangan Antarmuka UI (Form & Unit Properties)

#### 1. Quick-Win Awalan: Tombol "Tambah Unit Baru" di Menu Preventive Maintenance
- **Kondisi Eksisting**: Tombol registrasi unit saat ini hanya tersedia di halaman Dashboard dan Tabulasi Aset. Pengguna di menu Preventive Maintenance harus berpindah modul hanya untuk menambahkan alat baru.
- **Implementasi UI**:
  - Menempatkan tombol aksi `+ Tambah Unit Baru` pada header toolbar halaman Preventive Maintenance (`#view-pm`).
  - Menghubungkan tombol tersebut langsung ke modal `#modalNewAsset` secara konsisten tanpa reload halaman.

---

#### 2. Spesifikasi Pokok Field Penambahan Unit Baru
Modal form registrasi unit (`#modalNewAsset`) dirombak menjadi antarmuka modular (*Sectional Cards*) dengan field-field pokok berikut:

| No | Parameter Pokok | Kontrol Form UI | Logika Lapangan & Karakteristik Data |
| :---: | :--- | :--- | :--- |
| **1** | **Kode Unit** | Text Input (`na-id`) | ID unik master aset resmi BRA (misal: `EXC-00099`, `DT-00049`). Bersifat permanen sebagai *primary key* aset. |
| **2** | **Nomor Lambung** | Text Input (`na-hull-number`) | Nomor lambung operasional di project (misal: `EX-201`, `DT-18`). **Dipisahkan dari Kode Unit** karena nomor lambung sering berganti sesuai project site. |
| **3** | **Nomor Pelat & Nomor Seri (SN)** | 2 Text Inputs (`na-plate`, `na-sn`) | - **Nomor Pelat**: Nomor registrasi kendaraan (misal: `BM 9012 RWI`).<br>- **Serial Number**: Nomor sasis / rangka pabrikan (misal: `HINO-49821A7C`). |
| **4** | **Tahun Produksi** | Number Input (`na-year`) | Tahun pembuatan alat (misal: `2022`). Digunakan untuk menghitung umur ekonomis alat. |
| **5** | **Nama Operator / PIC Unit** | Autocomplete / Input (`na-operator`) | Nama operator penanggung jawab unit saat serah terima awal, dilengkapi tombol tambah riwayat operator. |
| **6** | **Waktu & Tanggal Terakhir Servis** | Datetime-Local Picker (`na-last-service-date`) | Tanggal dan jam pelaksanaan servis berkala terakhir kali dilakukan. |
| **7** | **Meteran Terakhir Servis (HM / KM)** | Number Input (`na-last-service-hm`) | Angka *Hour Meter* atau *Kilometer* saat servis terakhir tersebut selesai dikerjakan. |
| **8** | **Interval Servis Berkala** | Dropdown / Input (`na-service-interval`) | Ambang batas servis berulang (Pilihan standar: per `250 Jam`, `500 Jam`, `1.000 Jam`, atau per `10.000 KM` untuk Dump Truck). |
| **9** | **Estimasi Servis Berikutnya & Target HM/KM** | Auto-Calculated Fields | - **Target Due HM/KM**: Dihitung otomatis: $\text{Meter Terakhir Servis} + \text{Interval Servis}$.<br>- **Estimasi Tanggal Servis**: Dihitung otomatis dari rata-rata jam kerja per hari (koefisien acuan):<br>$$\text{Sisa Hari} = \frac{\text{Target Due HM} - \text{HM Terkini}}{\text{Koefisien Utilisasi Harian (contoh: 7 Jam/Hari HE atau 150 KM/Hari DT)}}$$ |
| **10** | **Histori Tanggal Perawatan (1, 2, dst)** | Dynamic Sub-table Input | Pencatatan riwayat tanggal perawatan lampau yang pernah dilalui unit sebelum masuk sistem (Tanggal Servis 1, Servis 2, dst). |
| **11** | **Galeri Dokumentasi Global** | Multi-Image Upload Dropzone | Upload multi-foto fisik unit (tampak depan, samping kanan/kiri, kabin operator, ruang mesin/engine bay, plat nomor seri). Disajikan dalam format galeri interaktif. |
| **12** | **PDF Pemberkasan JO Servis Berkala** | File Input PDF (`na-service-pdf`) | Upload dokumen acuan SOP checklist dan berkas resmi Job Order servis berkala awal dari vendor/workshop. |

---

#### 3. Integrasi Tampilan Baru pada Lembar Unit Properties (`openUnitProperties`)
Data yang diinputkan di atas akan disajikan pada antarmuka **Unit Properties**:
1. **Section Galeri Dokumentasi 360° Fisik**: Menampilkan galeri foto multi-sudut pandang dengan fitur *lightbox viewer* untuk inspeksi visual kondisi unit.
2. **Section Histori Operator / PIC**: Tabel log pergantian operator (Nama Operator, Tanggal Mulai Ditugaskan, Tanggal Berakhir, Shift, dan Catatan Serah Terima).
3. **Section Berkas PDF JO Servis Berkala**: Viewer / tombol unduh langsung dokumen PDF acuan servis berkala.
4. **Timeline Perawatan Lampau**: Visualisasi runut tanggal perawatan 1, 2, dst dengan indikator delta HM antar servis.

---

### 2.2 Asesmen Information Gap & Informasi Sekunder (Kelengkapan Unit Properties)

Berdasarkan perbandingan antara modal penambahan aset saat ini dan tampilan analitik pada [dashboard.view.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/dashboard.view.php) (Unit Properties), teridentifikasi sejumlah celah informasi (*information gaps*) penting yang direkomendasikan masuk sebagai **informasi sekunder** saat onboarding unit:

| Kategori Informasi | Informasi yang Sudah Ada di Unit Properties | Celah Informasi (*Information Gap*) yang Perlu Dilengkapi |
| :--- | :--- | :--- |
| **Identitas & Penomoran** | Unit ID, Kategori, Merek/Model, Status Operasional | **Pemisahan tegas Kode Unit vs Nomor Lambung Proyek**. Di lapangan, nomor lambung sering diubah sesuai site project sementara kode unit tetap. |
| **Legalitas & Sertifikasi** | Belum ada di properties | **Masa Berlaku Dokumen K3 & Perizinan**: Tanggal jatuh tempo Pajak STNK, Uji Kelayakan KIR, Sertifikat Laik Operasi (SILO/SIA Kemenaker & ESDM), serta Polis Asuransi Unit. |
| **Finansial & Kepemilikan** | Biaya servis bulanan | **Status Kepemilikan & Nilai Akuisisi**: Status (Milik Sendiri / Sewa Rental / Leasing / Subkon), Nama Perusahaan Vendor Sewa, Nilai Aset Awal, dan Nilai Tarif Sewa per Jam. |
| **Spesifikasi Mesin & Kapasitas** | RPM, Coolant Temp, Oil Press, Voltase (Live CANbus) | **Parameter Teknis Statis**: Tipe Mesin (*Engine Model*), Daya (*Horsepower*), Kapasitas Bucket/Vessel ($m^3$ atau Ton), dan Jenis Bahan Bakar/Kapasitas Tangki Liter. |
| **Parameter Utilisasi Harian** | Total Jam Kerja (HM) | **Koefisien Jam Kerja Harian**: Nilai rata-rata jam kerja normal per hari (misal 7-10 jam/hari) sebagai basis formula prediksi tanggal jatuh tempo servis berikutnya. |
| **Telematika & Tracking** | Peta Geofence, Log Koordinat GPS | **Identitas Device IoT**: IMEI GPS Tracker, Vendor Provider GSM, dan Serial Sensor CANbus. |

---

### 2.3 Actionable Execution Prompt (Tahap 1: UI Penambahan Unit, Tombol PM & Galeri Properties)

> 🚀 **Cara Menggunakan Actionable Prompt Ini**:
> Salin teks prompt di bawah atau panggil dengan instruksi: *"Eksekusi prompt Section 2.3 di @implementation-plan-17102026.md"*.

```markdown
Eksekusi Tahap 1: Implementasi Perancangan UI Penambahan Unit Baru, Tombol Tambah di PM, dan Galeri Properties sesuai Section 2.1 dan 2.2 di implementation-plan-17102026.md:

1. Target Berkas:
   - dashboard.view.php
   - dashboard.html

2. Ruang Lingkup Pekerjaan UI:
   a. Tambahkan tombol "+ Tambah Unit Baru" pada toolbar header modul Preventive Maintenance (#view-pm) dengan event onclick memanggil openModal('modalNewAsset').
   b. Rombak modal `#modalNewAsset` menjadi Sectional Layout 4 blok teratur:
      - Blok 1: Identitas & Penomoran (Kode Unit 'na-id', Nomor Lambung Project 'na-hull-number', Kategori 'na-cat', Merek & Model 'na-model', Tahun 'na-year', Serial Number 'na-sn', Plat Polisi 'na-plate').
      - Blok 2: Kepemilikan & Lokasi (Status Kepemilikan: Milik Sendiri/Sewa/Leasing, Vendor Sewa, Lokasi Site 'na-loc', Status Awal 'na-status', Meter HM/KM Awal 'na-hm').
      - Blok 3: Parameter PM & Operator (Nama Operator PIC Awal, Tanggal Servis Terakhir, Meter Terakhir Servis, Interval Servis HM/KM, Estimasi Servis Berikutnya otomatis, Koefisien Jam Kerja/Hari).
      - Blok 4: Galeri Dokumentasi & Dokumen PDF (Area upload multi-foto fisik unit dengan instant preview galeri, dan input upload file PDF JO Servis Berkala).
   c. Perbarui Modal Unit Properties (openUnitProperties):
      - Tambahkan Section Tab / Panel "Galeri Dokumentasi Fisik Unit" (menampilkan foto tampak depan, samping, kabin, engine, plat serial dengan lightbox).
      - Tambahkan Tabel "Log Riwayat Operator / PIC Unit" (Nama Operator, Periode, Shift).
      - Tambahkan Kartu Informasi Legalitas (Masa berlaku STNK, KIR, SILO Kemenaker).
      - Tambahkan Widget Tautan Dokumen PDF JO Servis Berkala resmi.
   d. Tambahkan script JS:
      - Kalkulator dinamis estimasi tanggal servis berikutnya berdasarkan HM interval dan koefisien jam kerja per hari.
      - Image gallery preview generator untuk multi-upload foto aset.
   e. Jalankan sinkronisasi Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 2.4 Rencana Backend Connection & Database Preparation (Asset Onboarding)

Agar data penambahan unit, histori operator, riwayat tanggal servis, galeri foto, dan berkas PDF JO tersimpan secara relasional dan dapat dimuat kembali secara cepat:

#### 1. Audit Skema Database & Tabel Relasi Baru
Tabel `assets` diperluas dan didukung oleh 3 tabel relasi pendukung:

1. **Pembaruan Kolom pada Tabel `assets`**:
   ```sql
   ALTER TABLE assets
       ADD COLUMN IF NOT EXISTS hull_number VARCHAR(100) NULL AFTER asset_code,
       ADD COLUMN IF NOT EXISTS current_operator_name VARCHAR(150) NULL AFTER raw_location_notes,
       ADD COLUMN IF NOT EXISTS last_service_date DATE NULL AFTER current_operator_name,
       ADD COLUMN IF NOT EXISTS last_service_hm_km DECIMAL(10,2) NULL AFTER last_service_date,
       ADD COLUMN IF NOT EXISTS service_interval_val INT NULL DEFAULT 250 AFTER last_service_hm_km,
       ADD COLUMN IF NOT EXISTS service_interval_unit ENUM('HM','KM') DEFAULT 'HM' AFTER service_interval_val,
       ADD COLUMN IF NOT EXISTS daily_usage_coefficient DECIMAL(5,2) DEFAULT 7.00 AFTER service_interval_unit,
       ADD COLUMN IF NOT EXISTS next_service_due_hm_km DECIMAL(10,2) NULL AFTER daily_usage_coefficient,
       ADD COLUMN IF NOT EXISTS next_service_estimated_date DATE NULL AFTER next_service_due_hm_km,
       ADD COLUMN IF NOT EXISTS legal_stnk_expiry DATE NULL,
       ADD COLUMN IF NOT EXISTS legal_kir_expiry DATE NULL,
       ADD COLUMN IF NOT EXISTS legal_silo_expiry DATE NULL,
       ADD COLUMN IF NOT EXISTS service_manual_pdf_url VARCHAR(255) NULL;
   ```

2. **Tabel Riwayat Operator (`asset_operator_history`)**:
   ```sql
   CREATE TABLE IF NOT EXISTS asset_operator_history (
       history_id INT AUTO_INCREMENT PRIMARY KEY,
       asset_id VARCHAR(100) NOT NULL,
       operator_name VARCHAR(150) NOT NULL,
       badge_number VARCHAR(50) NULL,
       start_date DATE NOT NULL,
       end_date DATE NULL,
       shift_type VARCHAR(50) DEFAULT 'Day Shift',
       handover_notes TEXT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

3. **Tabel Galeri Dokumentasi Fisik Unit (`asset_gallery_photos`)**:
   ```sql
   CREATE TABLE IF NOT EXISTS asset_gallery_photos (
       photo_id INT AUTO_INCREMENT PRIMARY KEY,
       asset_id VARCHAR(100) NOT NULL,
       photo_title VARCHAR(150) NOT NULL,
       photo_angle ENUM('Front','Right','Left','Cabin','Engine','SerialPlate','Other') DEFAULT 'Other',
       photo_url VARCHAR(255) NOT NULL,
       uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

4. **Tabel Runut Histori Perawatan Berkala (`asset_service_history`)**:
   ```sql
   CREATE TABLE IF NOT EXISTS asset_service_history (
       service_log_id INT AUTO_INCREMENT PRIMARY KEY,
       asset_id VARCHAR(100) NOT NULL,
       service_sequence INT NOT NULL, -- Perawatan 1, 2, dst
       service_date DATE NOT NULL,
       service_hm_km DECIMAL(10,2) NOT NULL,
       service_type VARCHAR(100) NOT NULL, -- PS 250, PS 500, etc
       mechanic_pic VARCHAR(150) NULL,
       jo_report_number VARCHAR(100) NULL,
       notes TEXT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

---

#### 2. Arsitektur Interaksi Backend Controller (`api/assets.php`)
1. **POST / API Registration**:
   - Menerima payload lengkap penambahan unit.
   - Melakukan validasi duplikasi `asset_id` dan `serial_number`.
   - Menghitung target due servis berikutnya:
     `$nextDue = $lastServiceHm + $serviceInterval;`
   - Menghitung estimasi tanggal servis berikutnya berdasarkan koefisien harian:
     `$daysLeft = ($nextDue - $currentHm) / $dailyUsageCoefficient;`
     `$estimatedDate = date('Y-m-d', strtotime("+{$daysLeft} days"));`
   - Menyimpan rekaman operator awal ke `asset_operator_history`.
   - Mengunggah berkas PDF JO dan file foto galeri ke direktori `uploads/assets/` serta mencatat entri ke `asset_gallery_photos`.
2. **GET / API Details (Unit Properties)**:
   - Query diperluas untuk me-load data aset utama berserta sub-resource relasional: galeri foto, histori operator, dan histori perawatan berkala secara instan.

---

### 2.5 Actionable Execution Prompt (Tahap 2: Backend Connection & Database Migration Penambahan Unit)

> 🚀 **Cara Menggunakan Actionable Prompt Ini**:
> Salin teks prompt di bawah atau panggil dengan instruksi: *"Eksekusi prompt Section 2.5 di @implementation-plan-17102026.md"*.

```markdown
Eksekusi Tahap 2: Implementasi Backend Connection & Database Migration Penambahan Unit Baru sesuai Section 2.4 di implementation-plan-17102026.md:

1. Target Berkas:
   - scripts/migrate_asset_onboarding_17102026.sql (Berkas Migrasi SQL baru)
   - api/assets.php (Controller Master Aset)
   - api/pm.php (Controller Preventive Maintenance)
   - scripts/sync_to_laragon.ps1 (Script Sinkronisasi)

2. Ruang Lingkup Pekerjaan Backend:
   a. Buat berkas migrasi SQL `scripts/migrate_asset_onboarding_17102026.sql` yang:
      - Menambahkan kolom-kolom baru pada tabel `assets`: `hull_number`, `current_operator_name`, `last_service_date`, `last_service_hm_km`, `service_interval_val`, `service_interval_unit`, `daily_usage_coefficient`, `next_service_due_hm_km`, `next_service_estimated_date`, `legal_stnk_expiry`, `legal_kir_expiry`, `legal_silo_expiry`, `service_manual_pdf_url`.
      - Membuat tabel relasi baru: `asset_operator_history`, `asset_gallery_photos`, dan `asset_service_history`.
   b. Eksekusi skrip migrasi tersebut ke MySQL Laragon `u646470441_ServicePlanBRA`.
   c. Perbarui `api/assets.php`:
      - Di blok POST (registrasi unit baru): Ambil seluruh parameter identitas, operator, servis, interval, legalitas, serta file upload foto galeri dan PDF JO.
      - Hitung `next_service_due_hm_km` dan `next_service_estimated_date` secara server-side menggunakan formula koefisien harian.
      - Simpan relasi operator awal ke `asset_operator_history` dan foto galeri ke `asset_gallery_photos`.
      - Di blok GET (detail unit / properties): Sertakan relasi operator history, service history, dan gallery photos dalam payload JSON respons.
   d. Perbarui `api/pm.php`:
      - Sambungkan query PM list dengan kolom nomor lambung, operator, waktu servis terakhir, interval, dan tanggal estimasi servis berikutnya dari tabel assets.
   e. Eksekusi sinkronisasi dan verifikasi:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

## 3. Outline 3: Rencana Pengembangan Global (Sistem, UI/UX, & Integrasi)

Bagian ini menyusun rencana pengembangan global sistem **ServicePlan-BRA** dengan memecah poin-poin hasil pertemuan Zoom menjadi modul-modul independen yang **dapat dikerjakan secara terpisah tanpa tumpang tindih (*non-blocking*)** terhadap pengerjaan utama form WO (Section 1) maupun onboarding unit (Section 2).

Setiap modul independen dilengkapi dengan: **Rancangan UI**, **Koneksi Backend & Skema Data**, serta **Actionable Execution Prompt**.

---

### 3.1 Item Independen 1: Mesin Pencarian Universal 3-Parameter (GLB-A)

Pencarian unit di lapangan sering terhambat karena personil berbeda mengingat unit dengan parameter berbeda (operator mengingat nomor lambung, mekanik mengingat kode aset, dan bagian logistik/security mengingat nomor plat atau nomor seri rangka).

#### 1. Rencana Pengembangan UI
- **Global Search Bar di Header Utama**:
  - Input pencarian universal dengan placeholder: *"Cari Kode Unit, No Lambung, atau Serial/Plat..."*.
  - Dilengkapi 3 pill badge visual penanda kunci aktif: `[Kode Aset]` `[No Lambung]` `[Plat/SN]`.
  - Pintasan keyboard instan: tekan `/` atau `Ctrl + K` untuk langsung fokus ke kolom pencarian.
- **Dropdown Hasil Pencarian Cerdas (Live Search Overlay)**:
  - Menampilkan hasil pencarian instan berformat kartu ringkas (Unit ID, Nomor Lambung, Kategori Alat, Plat/SN, Status Pill, Lokasi Terakhir).
  - Klik pada kartu langsung membuka lembar *Unit Properties* atau modal terkait.

#### 2. Rencana Backend Connection
- **Endpoint API**:
  - Mengoptimalkan kueri pencarian pada [api/assets.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/api/assets.php) (`GET ?action=search&q=...`):
    ```sql
    SELECT a.asset_id, a.asset_code, a.hull_number, a.serial_number, a.license_plate, 
           a.category, a.make_model, a.status, l.location_name
    FROM assets a
    LEFT JOIN locations l ON l.location_id = a.current_location_id
    WHERE a.asset_id LIKE :kw 
       OR a.asset_code LIKE :kw 
       OR a.hull_number LIKE :kw 
       OR a.serial_number LIKE :kw 
       OR a.license_plate LIKE :kw
    ORDER BY a.asset_id ASC LIMIT 25;
    ```
  - Penambahan indeks gabungan (*composite index*) pada kolom `hull_number`, `serial_number`, dan `license_plate` untuk kecepatan kueri di bawah 50ms.

#### 3. Actionable Execution Prompt (GLB-A: Universal 3-Parameter Search)
```markdown
Eksekusi GLB-A: Implementasi Mesin Pencarian Universal 3-Parameter (Kode Aset + No Lambung + Serial Number/Plat) sesuai Section 3.1 di implementation-plan-17102026.md:

1. Target Berkas:
   - dashboard.view.php
   - dashboard.html
   - api/assets.php
   - scripts/sync_to_laragon.ps1

2. Ruang Lingkup Pekerjaan:
   a. UI Frontend:
      - Pasang Universal Search Bar di header dashboard dengan hotkey trigger ('/' dan 'Ctrl+K').
      - Bangun live search overlay popup menampilkan kartu ringkas unit (ID, Lambung, Plat/SN, Kategori, Status Pill).
   b. Backend Connection:
      - Tambahkan handler pencarian multi-kunci di api/assets.php mencakup asset_id, asset_code, hull_number, serial_number, dan license_plate.
   c. Integrasi Navigasi:
      - Klik hasil pencarian otomatis membuka modal Unit Properties (window.openUnitProperties(assetId)).
   d. Eksekusi sinkronisasi Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 3.2 Item Independen 2: Fitur Rename Nomor Lambung Manual & Audit Trail Log (GLB-B)

Di lapangan, nomor lambung sering berganti sesuai kontrak project tambang tanpa pemberitahuan sebelumnya. Sistem membutuhkan fitur ubah nama (*rename*) yang fleksibel namun tetap terkontrol melalui audit trail.

#### 1. Rencana Pengembangan UI
- **Tombol Quick-Rename**:
  - Tombol ikon pensil kecil `[✎ Rename]` di samping display Nomor Lambung pada kartu unit, tabel tabulasi aset, dan tabel PM.
- **Modal Ubah Nomor Lambung (`#modalRenameHull`)**:
  - Menampilkan: Kode Unit (Permanen), Nomor Lambung Saat Ini (Read-only), Input Nomor Lambung Baru, Tanggal Efektif, dan Alasan Perubahan.
  - Otorisasi UI: Tombol hanya aktif untuk pengguna dengan peran **Asset Manager / Planner / Super Admin**.

#### 2. Rencana Backend Connection & Database
- **Tabel Audit Log (`asset_hull_history`)**:
  ```sql
  CREATE TABLE IF NOT EXISTS asset_hull_history (
      history_id INT AUTO_INCREMENT PRIMARY KEY,
      asset_id VARCHAR(100) NOT NULL,
      old_hull_number VARCHAR(100) NULL,
      new_hull_number VARCHAR(100) NOT NULL,
      changed_by_user_id INT NULL,
      changed_by_name VARCHAR(150) NOT NULL,
      change_reason VARCHAR(255) NULL,
      changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ```
- **Controller Logic (`api/assets.php`)**:
  - Endpoint `PUT /api/assets.php?action=rename_hull`.
  - Memverifikasi hak akses pengguna (*RBAC check*).
  - Menjalankan transaksi: update `assets.hull_number` dan insert rekaman baru ke `asset_hull_history`.

#### 3. Actionable Execution Prompt (GLB-B: Rename Nomor Lambung & Audit Log)
```markdown
Eksekusi GLB-B: Implementasi Fitur Rename Nomor Lambung Manual & Audit Trail Log sesuai Section 3.2 di implementation-plan-17102026.md:

1. Target Berkas:
   - scripts/migrate_hull_history_17102026.sql
   - api/assets.php
   - dashboard.view.php
   - dashboard.html
   - scripts/sync_to_laragon.ps1

2. Ruang Lingkup Pekerjaan:
   a. Database Migration:
      - Buat tabel asset_hull_history (history_id, asset_id, old_hull_number, new_hull_number, changed_by_name, change_reason, changed_at).
      - Eksekusi migrasi ke MySQL Laragon u646470441_ServicePlanBRA.
   b. Backend Connection:
      - Tambahkan endpoint PUT rename_hull di api/assets.php dengan validasi otorisasi peran (Planner/Admin) dan pencatatan audit log.
   c. UI Frontend:
      - Tambahkan modal `#modalRenameHull` dan tombol quick-action rename di samping nomor lambung unit.
      - Tambahkan tab/tabel riwayat perubahan nomor lambung di modal Unit Properties.
   d. Eksekusi sinkronisasi Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 3.3 Item Independen 3: Redesain Modul Preventive Maintenance (Format List 11 Kolom) (GLB-C)

Keputusan rapat menyepakati perombakan tampilan utama modul Preventive Maintenance (PM) dari bentuk kanban card menjadi list/tabel terstruktur 11 kolom.

#### 1. Rencana Pengembangan UI
- **Tampilan Datatable Responsif 11 Kolom Wajib**:
  1. *Kode Unit* (`asset_id`) dengan link ke properties.
  2. *Nomor Lambung* (dengan badge project site).
  3. *Plat / Serial Number*.
  4. *Tahun Produksi*.
  5. *Nama Operator PIC*.
  6. *Waktu Servis Terakhir* (Tanggal & HM/KM).
  7. *Interval Servis* (Target HM/KM berulang).
  8. *Estimasi Tanggal Servis Berikutnya* (Dilengkapi indikator warna: Hijau = >7 hari, Kuning = 1-7 hari, Merah = Overdue/Jatuh Tempo).
  9. *Histori Tanggal Perawatan* (Badge ringkas tanggal perawatan lampau yang dapat diklik).
  10. *Dokumentasi Foto* (Thumbnail foto kondisi unit dengan modal popup).
  11. *Upload / Akses PDF JO Servis Berkala* (Tombol unduh / view dokumen acuan servis).
- **Filter Bar Khusus PM**:
  - Filter berdasarkan Kategori Alat, Status Urgensi Servis (Due Soon, Overdue, Safe), dan Site Proyek.

#### 2. Rencana Backend Connection
- **Endpoint API (`api/pm.php`)**:
  - Mengembangkan endpoint `GET /api/pm.php?view=table_list` yang melakukan query terpadu:
    ```sql
    SELECT a.asset_id, a.asset_code, a.hull_number, a.serial_number, a.license_plate, 
           a.year_manufacture, a.current_operator_name, a.last_service_date, a.last_service_hm_km,
           a.service_interval_val, a.next_service_due_hm_km, a.next_service_estimated_date,
           a.service_manual_pdf_url,
           (SELECT photo_url FROM asset_gallery_photos WHERE asset_id = a.asset_id ORDER BY photo_id DESC LIMIT 1) AS thumbnail_photo
    FROM assets a
    WHERE a.is_active = 1
    ORDER BY a.next_service_estimated_date ASC;
    ```

#### 3. Actionable Execution Prompt (GLB-C: Redesain List Tabel Preventif Maintenance)
```markdown
Eksekusi GLB-C: Implementasi Redesain Tampilan Modul Preventif Maintenance Format List/Tabel 11 Kolom sesuai Section 3.3 di implementation-plan-17102026.md:

1. Target Berkas:
   - dashboard.view.php
   - dashboard.html
   - api/pm.php
   - scripts/sync_to_laragon.ps1

2. Ruang Lingkup Pekerjaan:
   a. UI Frontend:
      - Rombak kontainer `#view-pm` menampilkan datatable responsif dengan 11 kolom wajib.
      - Tambahkan badge penanda urgensi servis (Hijau/Kuning/Merah) berdasarkan estimasi jatuh tempo.
      - Tambahkan thumbnail foto popover dan tombol direct view berkas PDF JO Servis Berkala.
   b. Backend Connection:
      - Perbarui api/pm.php untuk menyediakan data list lengkap dengan JOIN histori servis dan dokumen.
   c. Eksekusi sinkronisasi Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 3.4 Item Independen 4: Audit & Penguncian Rumus KPI Produktivitas Alat (GLB-D)

Keputusan rapat menegaskan bahwa fitur ekspor laporan analitik ditunda sampai seluruh rumus KPI divalidasi kebenarannya secara matematis sesuai standar operasional alat berat tambang BRA.

#### 1. Rencana Pengembangan UI
- **Widget Visualizer Rumus KPI (`#modalKpiFormulaInspector`)**:
  - Kartu transparansi rumus pada menu Analitik / Fleet Monitor.
  - Menampilkan rumus acuan, variabel jam yang digunakan (*Operating, Standby, Breakdown, Scheduled*), dan live kalkulasi per unit.
  - Tombol *"Verifikasi Formula"* untuk Planner sebelum mengunduh data mentah.

#### 2. Rencana Backend Connection
- **Standarisasi Formula Analitik ([core/KpiCalculator.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/core/KpiCalculator.php) / [api/kpi.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/api/kpi.php))**:
  - Mengunci formula matematis:
    1. $\text{PA (Physical Availability)} = \frac{W + S}{T} \times 100\%$
    2. $\text{MA (Mechanical Availability)} = \frac{W}{W + R} \times 100\%$
    3. $\text{UA (Use of Availability)} = \frac{W}{W + S} \times 100\%$
    4. $\text{EU (Effective Utilization)} = \frac{W}{T} \times 100\%$
    5. $\text{MTBF} = \frac{\sum W}{\text{Frekuensi Kerusakan}} \quad , \quad \text{MTTR} = \frac{\sum R}{\text{Frekuensi Perbaikan}}$
    *(Keterangan: $W$ = Operating Hours, $S$ = Standby Hours, $R$ = Breakdown/Repair Hours, $T$ = Total Scheduled Hours).*
  - Pembuatan skrip unit-test mandiri `tests/verify_kpi_math.php` untuk memverifikasi angka hasil kalkulasi terhadap database lokal.

#### 3. Actionable Execution Prompt (GLB-D: Audit Rumus KPI Produktivitas)
```markdown
Eksekusi GLB-D: Implementasi Audit & Penguncian Formula KPI Produktivitas Alat Berat sesuai Section 3.4 di implementation-plan-17102026.md:

1. Target Berkas:
   - core/KpiCalculator.php
   - api/kpi.php
   - tests/verify_kpi_math.php
   - dashboard.view.php
   - dashboard.html
   - scripts/sync_to_laragon.ps1

2. Ruang Lingkup Pekerjaan:
   a. Backend Service:
      - Buat helper class core/KpiCalculator.php yang mengunci kalkulasi PA, MA, UA, EU, MTBF, dan MTTR.
      - Pastikan validasi pembagian dengan nol (zero-division guard) aman.
   b. Verification Test:
      - Buat skrip tests/verify_kpi_math.php yang menguji kalkulasi terhadap data nyata unit BRA di database Laragon.
   c. UI Inspector:
      - Tampilkan modal/tooltip verifikasi rumus KPI pada halaman Analitik/Dashboard.
   d. Eksekusi sinkronisasi Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 3.5 Item Independen 5: Unifikasi Presentation Layer Dokumen WO dan JO (GLB-E)

Job Order (JO) dan Work Order (WO) disepakati menggunakan entitas data yang sama di database. Sistem membutuhkan *presentation layer* yang fleksibel untuk mencetak dan menampilkan dokumen dalam dua mode label tersebut.

#### 1. Rencana Pengembangan UI
- **Document Alias Switcher (Toggle Cetak & View)**:
  - Selector opsi pada tampilan lembar tiket / pratinjau cetak:
    `[ Mode Tampilan Dokumen: ○ Work Order (WO)  ● Job Order (JO) ]`
  - Mengubah kop dokumen, judul laporan resmi, dan nomor referensi (misal: `WO-26-XXXX` atau `JO-26-XXXX`) tanpa mengubah ID database inti.

#### 2. Rencana Backend Connection
- **Endpoint Reporting (`api/reports.php`)**:
  - Parameter URL `?doc_alias=JO` atau `?doc_alias=WO`.
  - Format output cetak PDF / print-friendly HTML otomatis menyesuaikan klausul penamaan sesuai pilihan pengguna.

#### 3. Actionable Execution Prompt (GLB-E: Unifikasi Presentation Layer WO & JO)
```markdown
Eksekusi GLB-E: Implementasi Document Presentation Layer Dual-Alias (WO & JO) sesuai Section 3.5 di implementation-plan-17102026.md:

1. Target Berkas:
   - dashboard.view.php
   - dashboard.html
   - api/reports.php
   - scripts/sync_to_laragon.ps1

2. Ruang Lingkup Pekerjaan:
   a. UI Frontend:
      - Tambahkan Document Alias Selector (WO vs JO) pada modal detail tiket dan lembar pratinjau cetak.
   b. Backend Connection:
      - Sesuaikan template cetak api/reports.php untuk mendukung parameter alias penamaan tanpa duplikasi tabel.
   c. Eksekusi sinkronisasi Laragon:
      powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

### 3.6 Matriks Rekapitulasi Actionable Prompts Global

| Kode Modul | Nama Inisiatif Global | Target Komponen | Tipe Eksekusi | Cara Pemanggilan Cepat |
| :---: | :--- | :--- | :---: | :--- |
| **GLB-A** | Universal 3-Parameter Search | Top Navbar, Live Search, API Assets | UI & Backend | *"Eksekusi prompt GLB-A di @implementation-plan-17102026.md"* |
| **GLB-B** | Rename No Lambung & Audit Log | Quick-edit Modal, Tabel Audit, API Assets | UI, DB & Backend | *"Eksekusi prompt GLB-B di @implementation-plan-17102026.md"* |
| **GLB-C** | Redesain List Tabel PM 11 Kolom | View PM Table, Urgency Badges, API PM | UI & Backend | *"Eksekusi prompt GLB-C di @implementation-plan-17102026.md"* |
| **GLB-D** | Audit Rumus KPI Produktivitas | KpiCalculator Core, Formula Inspector, Tests | Engine & Tests | *"Eksekusi prompt GLB-D di @implementation-plan-17102026.md"* |
| **GLB-E** | Dual-Alias Presentation WO & JO | Print Sheets, Document Header, Reports API | UI & Reporting | *"Eksekusi prompt GLB-E di @implementation-plan-17102026.md"* |

---

## 4. Log Notulensi & Snippet Percakapan Hasil Zoom

Berikut adalah dokumentasi otentik hasil pertemuan pembahasan demo dan pengumpulan masukan aplikasi manajemen equipment PT Bina Rekayasa Anugerah (17 Oktober 2026):

```text
Hasil Utama
Pertemuan ini membahas demo dan pengumpulan masukan untuk aplikasi manajemen equipment PT Bina Rekayasa Anugerah. Fokus utama pada pengembangan fitur Work Order (WO), Preventif Maintenance, dan Produktivitas Alat. Sejumlah penambahan fitur disepakati, sementara beberapa bagian (biaya, telematika, ekspor laporan) belum disentuh dan akan diselesaikan di iterasi berikutnya. Bu Widya menyarankan sesi sinkronisasi langsung dengan Brian untuk mempercepat penyelesaian WO dan Job Order. 
---
Keputusan yang Diambil
Form Work Order akan diperluas dengan field: jenis unit, nomor lambung, nomor polisi, HM/KM, tanggal trouble, lokasi, PIC mekanik (multi-pilih), waktu mulai, waktu selesai (otomatis saat status close), uraian trouble, status (Breakdown/RFU/Preventive), tindakan mitigasi, estimasi selesai, dan upload foto. 
Spare part di WO disajikan dalam bentuk uraian manual (bukan ceklis) karena terminologi mekanik dan teknik berbeda, lalu di-link ke logistik untuk pengecekan stok. 
PIC mekanik dapat diisi lebih dari satu orang; mekanisme input menggunakan sistem ketik-nama-lalu-muncul (mirip input e-mail) agar tidak membebani tampilan mobile. 
Job Order (JO) dianggap memiliki isian yang sama dengan WO; tidak perlu fitur terpisah. 
Pencarian unit menggunakan kombinasi kode aset + nomor lambung + serial number sekaligus, dan nomor lambung dapat di-rename secara manual karena sering berubah tanpa pemberitahuan. 
Preventif Maintenance akan menampilkan daftar unit dalam format list/tabel dengan kolom: kode unit, nomor lambung, plat/serial number, tahun produksi, nama operator, waktu servis terakhir, interval servis (HM/KM), estimasi tanggal servis berikutnya, histori tanggal perawatan, dokumentasi foto, dan upload PDF (JO Servis Berkala). 
Tombol tambah unit baru ditambahkan di halaman Preventif Maintenance (tidak hanya di dashboard). 
Fitur ekspor laporan produktivitas belum dikerjakan; rumus KPI harus dipastikan benar sebelum digunakan untuk pengambilan keputusan. 
---
Belum Dikonfirmasi / Pending
Integrasi request spare part WO → logistik masih dalam pengerjaan; akan di-show di pertemuan berikutnya. 
Mekanisme approval/waiting job pada WO belum diimplementasikan secara eksplisit di sistem. 
Bagian biaya tidak ada perubahan dari versi sebelumnya; akan di-recall agar tidak terlewat. 
Akses dan tampilan untuk bagian logistik (Mas Fadli) belum dikonfirmasi karena tidak hadir di sesi ini. 
Sesi sinkronisasi langsung Brian–Bu Widya untuk finalisasi WO dan JO perlu diagendakan. 
---
Pertanyaan Terbuka
Siapa pihak yang berwenang melakukan perubahan nomor lambung di sistem (self-edit atau melalui aset manajer)? 
Apakah helper mekanik perlu dicatat sebagai PIC tersendiri atau cukup di bawah mekanik senior? 
Bagaimana penyajian dashboard monitoring efisiensi BBM — menunggu konfirmasi dari Divisi Project. 
---
Action Items
Brian & Tim Dev: Tambahkan field lengkap pada form WO dan JO sesuai masukan Fajar dan Bu Widya. 
Brian & Tim Dev: Implementasikan input PIC multi-mekanik dengan mekanisme ketik-nama. 
Brian & Tim Dev: Tambahkan fitur pencarian unit dengan tiga parameter (kode aset, nomor lambung, serial number) dan opsi rename nomor lambung.
Brian & Tim Dev: Bangun tampilan list Preventif Maintenance beserta kolom histori, estimasi servis, upload foto, dan PDF.
Brian & Tim Dev: Pastikan kebenaran rumus KPI produktivitas sebelum digunakan. 
Brian: Jadwalkan sesi tatap muka/sinkronisasi dengan Bu Widya untuk finalisasi WO dan JO.
Peserta rapat: Screenshot hasil diskusi dikirim ke grup WA sebagai referensi tim pengembang.
```

---

