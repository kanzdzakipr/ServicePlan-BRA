# Blueprint & Rencana Implementasi Kompleks Sistem Informasi Equipment (ServicePlan-BRA)

> **Status Progress Implementasi Terkini (26 Juli 2026)**:
> - **Frontend Modules**: 10 dari 16 Navbar Menu di [dashboard.html](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/dashboard.html) telah diimplementasikan secara komprehensif (`#view-dashboard`, `#view-monitoring`, `#view-asset`, `#view-wo`, `#view-pm`, `#view-biaya`, `#view-reports`, `#view-people`, `#view-hse`, `#view-productivity`).
> - **Backend Database Schema**: Skema basis data produksi [scripts/schema.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/schema.sql) (17 DDL Tabel Relasional & Full DML Initial Seeders) dan skrip migrasi CLI [scripts/SeederDataJson.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/SeederDataJson.php) telah 100% selesai dan siap digunakan.

Dokumen ini merupakan panduan arsitektur teknis dan analisis sistematis mendalam untuk pengembangan *Equipment Maintenance & Fleet Monitoring System* PT Bina Rekayasa Anugrah (BRA). Seluruh penjabaran disusun berpatokan pada struktur elemen tampilan `dashboard.html`, terintegrasi secara ketat dengan folder `material/` (ARSITEKTUR_BPMN, SOP, Laporan Tabulasi, Efisiensi BBM, Laporan Accident, dan Bank Data).

---

## 1. Arsitektur Enterprise & Filosofi Data Terpadu

Sistem dirancang mengusung prinsip **Single Source of Truth (SSOT)** dan **Event-Driven State Machine** guna menggantikan dependensi laporan manual spreadsheet/WhatsApp.

```
[ Lapangan: Operator / Mekanik / Inspector ]
                   │
                   ▼ (Input Event: Inspeksi, WO, Fuel, Parts Issue)
         [ Real-time Validation Gate ]
                   │
                   ▼
       [ Transactional MySQL Engine ]
                   │
    ┌──────────────┴──────────────┐
    ▼                             ▼
[ State Engine (Auto Status) ]   [ Financial & KPI Calculators ]
    │                             │
    └──────────────┬──────────────┘
                   ▼
  [ Real-Time Executive Dashboard & Leaflet Live Map ]
```

### Prinsip Utama Sistem:
1. **Event-Driven Status Transmutation**: Status unit (`READY`, `OPERATING`, `STANDBY`, `INSPEKSI`, `BREAKDOWN`, `WAITING_PART`, `ACCIDENT_HOLD`, `INACTIVE`) **TIDAK BISA** diubah secara manual tanpa transaksi yang valid. Contoh: Pembuatan Work Order (WO) dengan flag `downtime=true` otomatis mengubah status unit menjadi `BREAKDOWN`.
2. **Workflow Gatekeeper & SLA**: Transisi status WO/SPB memerlukan *gate validation*. WO tidak dapat berstatus `CLOSED` tanpa bukti foto *before-after*, catatan jam mekanik, diagnosis akar masalah, dan verifikasi supervisor (SLA identifikasi 30 menit).
3. **Relasi ID Unik Lintas Modul**: Asset ID (Kode Lambung) dan WO ID menjadi kunci relasi utama (*primary/foreign key*) yang menghubungkan modul Aset, Pemeliharaan, Logistik, Biaya, HSE, hingga KPI.
4. **Multi-User Role Access Control (RBAC) & Scope Sensitivity**: Sistem memisahkan secara tegas informasi **Umum / Publik** (dapat diakses oleh seluruh peran untuk kesadaran operasional) vs **Terbatas / Rahasia** (hanya dapat diakses oleh peran eksekutif tertentu seperti Equipment Manager, Asset Manager, Logistic Head, HRD, dan Administrator). Matriks kewenangan aksi (*Read, Create, Edit, Approve, Override*) dipetakan secara presisi per 16 menu `dashboard.html` pada [DATA_COMPLETENESS_CHECKLIST.md](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/DATA_COMPLETENESS_CHECKLIST.md) Seksi 10.2.

---

## 2. Matriks Integrasi Navbar `dashboard.html` terhadap Modul BPMN & Material

Tabel berikut memetakan 16 menu navigasi di `dashboard.html` ke modul BPMN (`M01`–`M12`), dokumen referensi pada `material/`, dan output transaksi dasarnya.

| No | Navbar Menu (`dashboard.html`) | Modul ID | Referensi Material Utama | Primary Entity & Event Target | Output Transaksi Utama |
|:---|:---|:---|:---|:---|:---|
| 1 | **Dashboard** | `M11` | `ARSITEKTUR_BPMN...md` (Bab 9) | `assets`, `work_orders`, `locations` | Executive Summary KPI, Live Map, WO Emergency List |
| 2 | **Monitoring Unit** | `M01`, `M04` | `REKAP_UNIT_STANDBY.md`, `01_Laporan_Break_Down...` | `assets (status != READY)` | Monitoring Table Unit Non-Ready & Quick Action |
| 3 | **Master Asset** | `M01`, `M02` | `SOP_Penerimaan_dan_Pengiriman...`, `ASSET_REKAP...` | `assets`, `asset_movements`, `BAST` | Asset 360° View, Lifecycle Tracking, Mutasi Unit |
| 4 | **Inspeksi & P2H** | `M03` | `SOP_Pemeriksaan_Penggunaan...`, `Reclaimer_Spreader...` | `inspections`, `meter_readings` | Checklist P2H, Update HM/KM, Pre-trip Findings |
| 5 | **Work Order** | `M04` | `005 Prosedure Breakdown`, `WMJO_HISTORY...` | `work_orders`, `wo_assignments`, `time_logs` | Kanban Board, Downtime Log, Repair Clock, RTW Ticket |
| 6 | **Preventive Maint.** | `M05` | `Plan Service Juli 2026`, `Perbandingan_Rencana_vs...` | `maintenance_plans`, `executions` | Forecast Interval, PM Kitting, Due/Overdue Tracker |
| 7 | **Spare Part & Logistik**| `M06` | `Flowchart_Procurement...`, `05_Laporan_Logistik...` | `purchase_requests`, `parts`, `inventory_tx` | SPB Management, Stock Reservation, ETA Lead Time |
| 8 | **Condition Monitoring**| `M07` | `REPORT_BAN...`, `Form_Kontrol_Cutting_Bit...` | `tires`, `components`, `greasing_logs` | Wear Depth Diagram, Rotasi Ban, Structural Health |
| 9 | **Fuel Management** | `M03/M08`| `EFISIENSI-BBM_Spesifikasi...` | `fuel_logs`, `meter_readings` | LPH Analysis, Fuel Anomaly Alert, Flowmeter Audit |
| 10| **Produktivitas** | `M09` | `Analisis Produktivitas / Absensi / SPL`, `KOMTRAX` | `unit_productivity`, `komtrax_logs` | Hour Meter vs Working Hour, Utilization Rate (%) |
| 11| **Biaya** | `M08` | `Equipment_Expenses...`, `06_Laporan_Cash_Out...` | `cost_transactions`, `purchase_orders` | Budget vs Actual Chart, Cost/Hour, Depreciation |
| 12| **People & KPI** | `M09` | `Template_KPI_Head_of_Equipment.md`, `WMJO...` | `employees`, `mechanic_kpi`, `time_logs` | Mechanic Leaderboard, MTTR/MTBF, Jam Lembur SPL |
| 13| **HSE / Accident** | `M10` | `LAPORAN_ACCIDENT.md`, `TAR_Unit_CS_41001_RWI.md` | `accidents`, `capa_actions` | Safety Incident Stepper, Damage Cost, Unit Hold/Release |
| 14| **Laporan** | `M11` | `LAPORAN_DIVISI_EQUIPMENT_JANUARI_2026.md` | `reports_generated`, `audit_logs` | Multi-Format Export (Excel/PDF), Daily Fleet Summary |
| 15| **Approval** | `M12` | `ARSITEKTUR_BPMN...md` (Bab 3 & 7) | `approvals`, `approval_matrices` | Centralized Approval Inbox, Escalation Queue |
| 16| **Pengaturan** | `M12` | `ARSITEKTUR_BPMN...md` (Bab 12 & 15) | `system_configs`, `users`, `roles`, `sla_rules` | Threshold Settings, User RBAC, SLA Configurator |

---

## 3. Penjabaran Kompleks & Detail Per Menu Navigasi

---

### 3.1 Dashboard Executive (`view-dashboard`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Elemen utama: `.kpi-grid` (`#kpiTotal`, `#kpiReady`, `#kpiStandby`, `#kpiBreakdown`, `#kpiInspeksi`), `.dashboard-layout` berisi `#map` (Leaflet.js) dan `#attentionList` (Daftar WO Emergency High Priority).

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Dashboard adalah representasi *real-time* dari seluruh agregasi transaksi. Kartu KPI menghitung status efektif dari tabel `assets`. Panel Perhatian menyaring Work Order aktif yang memiliki flag `priority = 'High'` dan status `!= CLOSED` untuk memberikan *early warning* kepada Equipment Manager.

#### C. Spesifikasi Teknis Frontend
* **Leaflet Live Map**: Marker dibuat secara berbasis GeoJSON. Marker memiliki warna terikat status (`#28a745` Ready, `#dc3545` Breakdown, `#17a2b8` Standby).
* **Attention List Engine**: Render otomatis list WO emergency dengan perhitungan lama waktu unit terhenti (*downtime hours*) secara dinamis.

#### D. Spesifikasi Backend & Schema Relasional
```sql
SELECT 
    COUNT(id) AS total_units,
    SUM(CASE WHEN status = 'READY' THEN 1 ELSE 0 END) AS ready_count,
    SUM(CASE WHEN status = 'STANDBY' THEN 1 ELSE 0 END) AS standby_count,
    SUM(CASE WHEN status = 'BREAKDOWN' THEN 1 ELSE 0 END) AS breakdown_count,
    SUM(CASE WHEN status = 'INSPEKSI' THEN 1 ELSE 0 END) AS inspeksi_count
FROM assets WHERE is_active = TRUE;
```
* **REST API**: `GET /api/v1/dashboard/executive-summary` & `GET /api/v1/dashboard/emergency-wos`

#### E. Tabulasi Spesifikasi Data
| Field Name | Type | Source Table | Validation / Display Rule |
|:---|:---|:---|:---|
| `total_units` | Integer | `assets` | Agregat seluruh unit aktif |
| `status_counts` | JSON Object | `assets` | Group by status enum |
| `emergency_wos` | Array | `work_orders` | Priority = 'High' AND status != 'Closed' |
| `geo_coordinates` | Point (Lat, Long) | `locations` / Telematics | Latitude & Longitude terakhir |

#### F. Supporting AI Prompt (Production Ready)
> "Buatkan modul JavaScript terpisah `dashboard-executive.js` yang mengimplementasikan integrasi Leaflet Map dengan custom icon SVG berwarna (Hijau=READY, Merah=BREAKDOWN, Biru=STANDBY). Tambahkan WebSocket listener `onStatusChange` yang meng-update counter KPI `#kpiTotal`, `#kpiReady`, `#kpiBreakdown` dan memperbarui marker map secara smooth tanpa reload halaman."

---

### 3.2 Monitoring Unit Bermasalah (`view-monitoring`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Elemen utama: `#view-monitoring`, input `#searchMonitoring`, tabel `#monitoringTable` dengan `#monTableBody`.

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Berdasarkan dokumen `REKAP_UNIT_STANDBY.md` dan `01_Laporan_Break_Down_Januari_2026`, tim operasional membutuhkan tampilan terisolasi yang hanya berfokus pada alat berat non-operasional (*Non-Ready Fleet*). Kolom "Tindakan Lanjutan" menentukan jalur eskalasi (contoh: jika Breakdown -> link ke WO; jika Inspeksi -> link ke Schedule PM).

#### C. Spesifikasi Teknis Frontend
* Real-time client-side filter menggunakan Regex JS pada event `keyup`.
* Render dinamis tombol aksi pada baris tabel: "Cek Progress WO", "Assign Mekanik", atau "Release Unit".

#### D. Spesifikasi Backend & Schema Relasional
* **Endpoint**: `GET /api/v1/assets/monitoring-non-ready?search={query}&status={filter}`
* **Query Filter**: `WHERE status IN ('BREAKDOWN', 'STANDBY', 'INSPEKSI')`

#### E. Tabulasi Spesifikasi Data
| Field Table | Data Type | Logic / Source | Interaktivitas UI |
|:---|:---|:---|:---|
| Unit ID | String (PK) | `assets.asset_id` | Klik membuka Modal Detail 360 |
| Kategori | String | `assets.category` | Badge Kategori Alat |
| Lokasi | String | `assets.location` | Text site/pit |
| Status Terakhir | Enum | `assets.status` | Color-coded Badge |
| Tindakan Lanjutan | HTML Component | Derived from `work_orders` | Shortcut Button Modal WO / PM |

#### F. Supporting AI Prompt (Production Ready)
> "Kembangkan modul `monitoring-view.js` dengan fungsi filter multi-kategori (Dropdown Filter Site & Filter Status: BREAKDOWN, STANDBY, INSPEKSI). Buatkan parser JS yang menghasilkan tombol aksi dinamis pada kolom 'Tindakan Lanjutan', misal jika unit breakdown akan menampilkan progress bar repair % dan tombol 'BukaWO'."

---

### 3.3 Master Asset (`view-asset`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Elemen utama: `#view-asset`, tabel `#assetTableBody`, modal `#assetModal` (Detail 360°), modal `#modalNewAsset`, dan modal `#modalUpdateStatus`.

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Sesuai `SOP_Penerimaan_dan_Pengiriman_Asset_Tabulasi.md` (BPMN 1), registrasi aset harus memuat spesifikasi lengkap (Merek, Model, Serial Number, Tahun, Ownership, Value). Panel "Detail 360°" menggabungkan seluruh jejak digital unit selama masa operasinya.

#### C. Spesifikasi Teknis Frontend
* **Tab-switching System**: Modal Detail 360 memuat 4 tab (Ringkasan, Lokasi, Breakdown & WO, Inspeksi & P2H).
* **Modal Management**: Form registrasi unit baru (`#modalNewAsset`) dan form update status manual (`#modalUpdateStatus`).

#### D. Spesifikasi Backend & Schema Relasional
```sql
CREATE TABLE assets (
    asset_id VARCHAR(50) PRIMARY KEY,
    serial_number VARCHAR(100) UNIQUE,
    category ENUM('Excavator', 'Bulldozer', 'Dump Truck', 'Motor Grader', 'Vibro') NOT NULL,
    make_model VARCHAR(100),
    year_manufacture INT,
    ownership ENUM('Owned', 'Rented', 'Leased'),
    acquisition_value DECIMAL(15,2),
    status ENUM('READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE') DEFAULT 'READY',
    current_location VARCHAR(100),
    last_hm_km DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### E. Tabulasi Spesifikasi Data Detail 360°
| Tab Name | Sub-Data Entity | Source API Endpoint | Render Component |
|:---|:---|:---|:---|
| Ringkasan | Asset Specs, Status, Value | `GET /api/v1/assets/{id}` | Info Grid & Badge Status |
| Lokasi | Movement History, BAST | `GET /api/v1/assets/{id}/movements` | Vertical Timeline Map |
| Breakdown & WO | All Historical & Active WOs | `GET /api/v1/assets/{id}/work-orders` | Mini DataTable + Status WO |
| Inspeksi & P2H | Daily Pre-trip Checklists | `GET /api/v1/assets/{id}/inspections` | Inspection Cards + Pass/Fail Badge |

#### F. Supporting AI Prompt (Production Ready)
> "Tuliskan kode JavaScript untuk menangani tab-switching di dalam `#assetModal`. Saat tab 'Breakdown & WO' diklik, lakukan fetch data AJAX ke `/api/v1/assets/{id}/work-orders`, lalu render hasilnya ke dalam tabel ringkas yang menampilkan WO ID, Tanggal Down, Diagnosis, Downtime (Jam), dan Status WO."

---

### 3.4 Inspeksi & P2H (`view-uc` -> Inspeksi)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Inspeksi & P2H".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Mengacu pada `SOP_Pemeriksaan_Penggunaan_Pemeliharaan_Perbaikan_Alat_Berat_Tabulasi.md`. Operator/Inspector wajib mengisi P2H (Pelaksanaan Perawatan Harian) sebelum unit beroperasi. Jika ditemukan item inspek "CRITICAL/FAIL", sistem secara otomatis memicu pemblokiran unit (status -> `INSPEKSI` / `BREAKDOWN`) dan membuat tiket WO DRAFT.

#### C. Spesifikasi Teknis Frontend
* UI Form Inspeksi Berbasis Checklist (Pass / Fail / Warning).
* Modul kamera HTML5 / File Upload dengan kompresi otomatis client-side untuk bukti foto kerusakan.
* Input validasi bacaan Meter Reading (HM/KM tidak boleh lebih kecil dari bacaan terakhir).

#### D. Spesifikasi Backend & Schema Relasional
* **Rule Engine**: `IF inspection_item.severity = 'CRITICAL' AND result = 'FAIL' THEN TRIGGER_CREATE_WO()`.
* **Database**: Tabel `inspections` (Header) & `inspection_details` (Items JSON / Child rows).

#### E. Tabulasi Struktur Checklist P2H
| Category Item | Inspection Point | Options | Trigger Action on Fail |
|:---|:---|:---|:---|
| Engine System | Oli Mesin & Air Radiator | Pass / Fail | Auto-WO Status: High Priority |
| Hydraulic System| Kebocoran Selang / Hose | Pass / Fail / Warning | Flag Component Warning |
| Safety Device | Rem, Klakson, Lampu, APAR | Pass / Fail | Block Unit Operation (HOLD) |
| Undercarriage | Ketegangan Track / Baut Roda | Pass / Fail | Trigger Maintenance Task |

#### F. Supporting AI Prompt (Production Ready)
> "Buatkan komponen UI Form P2H (Pre-Trip Inspection) interaktif di `#view-uc`. Form harus memiliki: Input HM/KM awal, grup checklist dengan switch Pass/Fail, area upload gambar dengan preview, serta logika validasi JS yang menampilkan warning jika HM baru lebih kecil dari HM terakhir."

---

### 3.5 Work Order (`view-wo`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Elemen utama: `#view-wo`, `#woKanbanBoard` (Kolom: `#col-Open`, `#col-In-Progress`, `#col-Closed`), dan modal `#modalNewWO`.

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Berdasarkan `005 Prosedure Penanganan Unit Breakdown` dan `WMJO_HISTORY...`, WO adalah inti operasional.
* **SLA 30 Menit**: Tiket WO/Job Order harus diterbitkan maks 30 menit sejak unit down.
* **Gate Closing**: WO tidak bisa ditutup tanpa:
  1. Catatan Jam Mekanik (*time log* normal & lembur SPL).
  2. Foto *Before & After* perbaikan.
  3. Hasil Uji Fungsi (*Testing/Commissioning*).
  4. Verifikasi Supervisor/Foreman.

#### C. Spesifikasi Teknis Frontend
* **Kanban Engine**: Render card dinamis berdasarkan prioritas (`High` = Border Merah).
* **Modal Lapor Breakdown (`#modalNewWO`)**: Menangkap keluhan, prioritas, dan status unit berhenti total (downtime).

#### D. Spesifikasi Backend & State Machine
```
[DRAFT] ➔ [SUBMITTED] ➔ [APPROVED] ➔ [IN_PROGRESS] ➔ [PAUSED/WAITING_PART] ➔ [TESTING] ➔ [CLOSED]
```
```sql
CREATE TABLE work_orders (
    wo_id VARCHAR(50) PRIMARY KEY,
    asset_id VARCHAR(50),
    priority ENUM('Normal', 'High') DEFAULT 'Normal',
    issue_description TEXT,
    is_downtime BOOLEAN DEFAULT TRUE,
    status ENUM('Open', 'In Progress', 'Waiting Part', 'Testing', 'Closed') DEFAULT 'Open',
    assigned_mechanic_id VARCHAR(50),
    down_time_start TIMESTAMP,
    repair_time_start TIMESTAMP,
    repair_time_end TIMESTAMP,
    verification_supervisor_id VARCHAR(50),
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
);
```

#### E. Tabulasi Matriks WO Kanban Status
| Column Name | Allowed Status Transition | Required Form Field Gate | Output Event Status Unit |
|:---|:---|:---|:---|
| **Open / Submitted** | -> In Progress, Cancelled | Job Order Number & Priority | Status Unit: `BREAKDOWN` |
| **In Progress** | -> Waiting Part, Testing | Mechanic PIC Assignment | Repair Clock Active |
| **Closed / Ready** | Terminal State | Photos, Test Result, Sup Approval | Status Unit: `READY` (RTW) |

#### F. Supporting AI Prompt (Production Ready)
> "Buatkan skrip JavaScript Kanban Board lengkap untuk `#woKanbanBoard` dengan fitur HTML5 Drag and Drop. Ketika card WO digeser dari 'In Progress' ke 'Closed', munculkan modal verifikasi yang memvalidasi bahwa mekanik telah mengunggah foto perbaikan dan memasukkan jam kerja sebelum melepaskan card ke status Closed."

---

### 3.6 Preventive Maintenance (`view-uc` -> PM)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Preventive Maintenance".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Sesuai `Plan Service Juli 2026` dan `Perbandingan_Rencana_vs_Realisasi_PM_Jan_2026`. Sistem membandingkan last service HM dengan interval servis berkala (contoh: PM 250, 500, 1000, 2000 Jam).
* **Warning Window**: Peringatan diterbitkan pada H-3 atau sisa 50 HM sebelum jatuh tempo (`Due Soon`).
* **Overdue Flag**: Jika HM melewati batas interval tanpa ada eksekusi PM, status berubah menjadi `Overdue`.

#### C. Spesifikasi Teknis Frontend
* Dashboard dual-panel: Panel Kiri = PM Forecast Tracker (Due Soon/Overdue), Panel Kanan = Calendar View Maintenance Schedule.
* Kitting checklist indikator (Kesiapan Filter, Oli, Grease).

#### D. Spesifikasi Backend & Cron Scheduler
* **Background Worker**: Cron job harian yang mengkalkulasi `(current_hm - last_service_hm)` terhadap `interval_hm`.
* **Endpoint**: `GET /api/v1/pm/forecast` & `POST /api/v1/pm/schedule-kitting`.

#### E. Tabulasi Interval & Kitting Material PM
| Service Level | Interval HM | Material Kitting Wajib | Standard Duration |
|:---|:---|:---|:---|
| PM 250 | Setiap 250 Jam | Ganti Oli Mesin, Filter Oli, Filter Solar | 4 Jam |
| PM 500 | Setiap 500 Jam | PM 250 + Filter Udara, Filter Transmisi | 6 Jam |
| PM 1000 | Setiap 1000 Jam | PM 500 + Oli Transmisi, Oli Hidrolik, Coolant | 8 Jam |
| PM 2000 | Setiap 2000 Jam | Major Service, Replacement All Fluids & Belts | 12 Jam |

#### F. Supporting AI Prompt (Production Ready)
> "Tuliskan modul UI JS untuk Preventive Maintenance yang memuat widget 'PM Due Tracker'. Widget menampilkan daftar unit dengan progress bar linier % pencapaian HM interval, dan mengubah warna progress bar menjadi Kuning jika Sisa HM < 50 Jam (Due Soon), dan Merah jika Sisa HM <= 0 (Overdue)."

---

### 3.7 Spare Part & Logistik (`view-uc` -> Logistik)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Spare Part & Logistik".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Mengacu pada `Flowchart_Procurement_dan_Logistik_BRA_Tabulasi.md` & `05_Laporan_Logistik_Januari_2026`.
* **Prasyarat SPB**: Surat Permintaan Barang (SPB) **wajib** mencantumkan Nomor WO atau Nomor PM yang valid (`BR-02`).
* **SLA Tracking**: Pengadaan diukur dari tanggal disetujuinya SPB hingga part tiba di Site (*Sourcing Lead Time*).

#### C. Spesifikasi Teknis Frontend
* Autocomplete Part Number Search dari Master Inventory.
* UI Multi-item Cart untuk permintaan kitting sparepart per WO.
* Tracker Status SPB (Submitted -> Approved -> PO Created -> In Transit -> Received -> Issued).

#### D. Spesifikasi Backend & Schema Relasional
```sql
CREATE TABLE purchase_requests (
    spb_id VARCHAR(50) PRIMARY KEY,
    wo_id VARCHAR(50),
    requested_by VARCHAR(50),
    urgency ENUM('Normal', 'Emergency') DEFAULT 'Normal',
    status ENUM('Draft', 'Submitted', 'Approved', 'Ordered', 'Received', 'Issued') DEFAULT 'Submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wo_id) REFERENCES work_orders(wo_id)
);
```

#### E. Tabulasi SLA & Status Pengadaan Part
| Status SPB | Responsible Role | Target SLA | Action Trigger |
|:---|:---|:---|:---|
| `SUBMITTED` | Supervisi Logistik | Maks 2 Jam | Cek Stok Gudang Lokal |
| `RESERVED` | Logistik Gudang | Maks 1 Jam | Potong Stok Reservasi -> Ready Issue |
| `SOURCING` | Procurement Purchasing | Maks 24 Jam | Penerbitan PO Vendor |
| `SHIPPED` | Ekspedisi Logistik | ETA Monitoring | Tracking Resi / Posisi Barang |
| `ISSUED` | Mekanik / Logistik | Immediate | Part terpasang ke WO -> WO Repair Clock Resume |

#### F. Supporting AI Prompt (Production Ready)
> "Buatkan komponen Form SPB (Surat Permintaan Barang) interaktif pada `#view-uc` Logistik. Form mencakup dropdown pilih WO ID aktif, tabel dinamis untuk input Part Number, Deskripsi, Jumlah, Satuan, dan tombol 'Submit SPB' yang memvalidasi bahwa minimal 1 item telah diinput."

---

### 3.8 Condition Monitoring (`#view-condition`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Condition Monitoring merupakan halaman kerja per-unit, bukan lagi sketsa ban yang berdiri sendiri. Selector menggunakan `asset.id` asli sebagai foreign key lintas modul, sedangkan prefix lambung (`DT-*`, `RM-*`, dan lain-lain) hanya dipakai untuk memilih layout/form yang relevan. Halaman terdiri dari identitas unit, health score, status empat domain, tab inspeksi, audit trail, serta tombol tindak lanjut.

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Acuan data berasal dari `material/BAN-GREASE-CUTTING_BIT-AKI/` dan dokumen mentah di `raw-material/BAN, GREASE, CUTTING BIT, AKI/`.

* **Ban:** laporan 19 Juli 2026 memuat 55 dump truck × 10 posisi (550 potensi posisi), dengan 213 pengukuran numerik, 19 posisi `DG`, 8 posisi `CLOSE`, dan 310 posisi kosong. Hasil harus membedakan “belum diukur” dari “baik”.
* **Grease:** regreasing dikendalikan oleh selisih HM aktual terhadap HM terakhir dan interval unit. Titik fokus: pin-bushing, steering linkage, undercarriage, dan propeller shaft/universal joint.
* **Cutting bit:** harga referensi Rp441.441/pcs; batas pemakaian 30 bit/hari, 3 bit/HM, dan 5 bit/1.000 m²; return rate target 100%; minimum stok 100 pcs. Kehilangan lebih dari nol otomatis `OVER LIMIT`.
* **Aki:** bank data mencatat 31 transaksi/51 aki pada April–Desember 2025; merek GS 41 unit (80,4%), Bosch 5, dan Yuasa 5. Inspeksi tetap berbasis tegangan, CCA, terminal, elektrolit, casing, tipe, serta usia pemasangan.
* Komponen yang tidak sesuai tipe unit tampil sebagai `N/A`, bukan dipaksakan sebagai data kosong. Contoh: cutting bit hanya aktif untuk recycler/milling (`RM-*`).

#### C. Spesifikasi Teknis Frontend

* Selector unit dilengkapi pencarian ID/kategori/lokasi dan menyimpan unit terpilih.
* Ringkasan empat domain: Ban/Undercarriage, Grease, Cutting Bit, dan Aki; setiap kartu membuka form terkait.
* Skematik ban adaptif: 10 posisi untuk dump truck dan 4 posisi untuk unit roda lain. Z-level ditetapkan: chassis `z-index:1`, ban `z-index:3`, ban terpilih `z-index:20`.
* Form ban mencatat tread, PSI, kerusakan fisik, pola aus, dan rekomendasi.
* Form grease mencatat HM, interval, jenis/jumlah grease, titik pelumasan, dan catatan mekanik.
* Form cutting bit menghitung bit/HM, bit/1.000 m², return rate, stok akhir, dan biaya shift otomatis.
* Form aki mencatat voltase, CCA, merek/tipe, tanggal pasang, terminal, elektrolit, casing, dan catatan load test.
* Riwayat menyimpan audit trail ID inspeksi, waktu, domain, status, dan ringkasan.

#### D. Integrasi Lintas Menu & Threshold Evaluator

1. Penyimpanan inspeksi membuat record pada koleksi inspeksi unit sehingga muncul bersama riwayat P2H.
2. Temuan kritis mengubah status unit menjadi `INSPEKSI`; unit baru menjadi `BREAKDOWN` setelah pengguna mengonfirmasi pembuatan WO korektif.
3. Aksi **Buat WO** menghasilkan WO dengan `assetId` yang sama, sumber `Condition Monitoring`, prioritas sesuai severity, dan membuka detail WO.
4. Aksi **Jadwalkan PM** meneruskan konteks unit/temuan ke Preventive Maintenance.
5. Aksi **Minta consumable** mewajibkan WO aktif, lalu membuka SPB dan mengisi rekomendasi ban, grease, cutting bit, atau aki.
6. Asset 360° menyediakan tombol masuk ke Condition Monitoring unit yang sama.
7. Executive Dashboard membaca snapshot KPI ban/grease dari mesin Condition Monitoring, bukan tabel KPI terpisah.

#### E. Tabulasi Ambang Batas Condition Monitoring

| Komponen | Parameter Ukur | Hijau / Normal | Kuning / Warning | Merah / Critical |
|:---|:---|:---|:---|:---|
| Ban dump truck | Tread dan kondisi fisik | > 8,5 mm | 3,2–8,5 mm / aus tidak rata | < 3,2 mm atau `DG`/retak/benjol/sobek |
| Grease | HM sejak grease | < 90% interval | 90–99% interval | ≥ interval |
| Cutting bit | Bit/HM | ≤ 3 | >3 sampai 3,6 | >3,6 |
| Cutting bit | Bit/1.000 m² | ≤ 5 | >5 sampai 6 | >6 |
| Cutting bit | Return/lost/stock | 100%, lost 0, stok ≥100 | stok <100 | lost >0 |
| Aki | Voltage dan CCA | ≥12,6 V dan CCA ≥80% | 12,0–12,5 V atau CCA 60–79% | <12,0 V atau CCA <60% / casing bocor-retak |

#### F. Supporting AI Prompt (Production Ready)
> "Bangun modul Condition Monitoring per-unit yang mengikat setiap inspeksi ke `asset_id`; sediakan layout ban adaptif, form HM grease, kalkulasi cutting-bit, load test aki, audit trail, dan evaluator threshold. Hubungkan temuan ke P2H, PM, Work Order, SPB, Asset 360°, serta KPI Executive Dashboard tanpa menduplikasi sumber data."

---

### 3.9 Fuel Management (`view-uc` -> Fuel)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Fuel Management".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Sesuai dokumen `EFISIENSI-BBM_Spesifikasi_Modul_Monitoring_BBM_BRA.md`.
* **Metrik Utama**: Liter per Jam (LPH) untuk Alat Berat atau KM per Liter (KPL) untuk Dump Truck.
* **Deteksi Anomali**: Jika rasio konsumsi BBM aktual melampaui toleransi standar pabrikan (> 15% dari baseline), sistem menandai transaksi tersebut sebagai `ANOMALY_SUSPECT`.

#### C. Spesifikasi Teknis Frontend
* Line Chart (Chart.js) Trend Konsumsi BBM vs Jam Kerja Unit.
* Form Input Pengisian BBM (Nomor Flowmeter Awal/Akhir, Total Liter, HM/KM Saat Pengisian, Foto Meteran).

#### D. Spesifikasi Backend & Formula Calculation
```
LPH = Total Liter BBM / (HM Pengisian Sekarang - HM Pengisian Sebelumnya)
```
* **Endpoint**: `POST /api/v1/fuel/refuel-log` & `GET /api/v1/fuel/efficiency-report`.

#### E. Tabulasi Standard Fuel Ratio Baseline (Contoh BRA Fleet)
| Kategori Unit | Model Unit | Baseline Standard LPH | Threshold Anomali (+15%) |
|:---|:---|:---|:---|
| Excavator 20 Ton | Komatsu PC200-8 | 16.5 L/Jam | > 18.97 L/Jam |
| Excavator 40 Ton | CAT 345D | 32.0 L/Jam | > 36.80 L/Jam |
| Bulldozer | Komatsu D85ESS | 28.0 L/Jam | > 32.20 L/Jam |
| Dump Truck | Hino FM260TI | 2.2 KM/Liter | < 1.87 KM/Liter |

#### F. Supporting AI Prompt (Production Ready)
> "Buatkan dashboard Mini Fuel Management di `#view-uc`. Tampilkan form transaksi pengisian BBM yang otomatis menghitung selisih Flowmeter Awal & Akhir menjadi Total Liter, serta mengkalkulasi LPH berdasarkan selisih HM. Tambahkan alert peringatan merah jika nilai LPH > 15% dari baseline standard."

---

### 3.10 Produktivitas (`#view-productivity`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Diterapkan pada seksi khusus `#view-productivity` dengan modul independen `scripts/productivity.js` dan `scripts/productivity.css`, terakses secara langsung dari sidebar navigasi `onclick="showView('productivity', '', 'menu-productivity')"`.

#### B. Pemahaman Alur Bisnis & Integration Context (`20_BRA_KOMTRAX...md` & `REKAP_UNIT_STANDBY.md`)
Modul ini mengintegrasikan secara komprehensif data telematika satelit KOMTRAX dan audit armada standby:
1. **`20_BRA_KOMTRAX_Januari_2026.md`**: Rekonsiliasi data telematika 18 unit (Komatsu PC200-10M0, PC210-10M0, GD535, D85ESS). Memantau pembacaan SMR (HM), jam kerja aktual, *Actual Working Hour Ratio %*, *E Mode Ratio %*, *Digging Ratio %*, *Travel Ratio %*, konsumsi bahan bakar (L/H), serta deteksi *idling ratio anomaly* (> 50%).
2. **`REKAP_UNIT_STANDBY.md`**: Analisis kerugian operasional dan potensi pemanfaatan dari 48 unit armada standby di Yard Duri (35 unit) dan Yard Prabumulih (13 unit) yang didominasi Dump Truck (27 unit) dan Excavator (8 unit).

#### C. Spesifikasi Teknis Frontend
* **4 Sub-Modul Navigasi**: Tab 1 (Dashboard Availability & KPI), Tab 2 (Rekonsiliasi Telematika KOMTRAX 18 Unit), Tab 3 (Idling Anomaly & Fuel Loss Matrix), Tab 4 (Audit Fleet Standby 48 Unit).
* **Visualisasi Availability Gauges**: Kartu ringkasan Physical Availability (92.4%), Use of Availability (81.8%), Breakdown Rate (7.6%), Utilization Rate (52.1%), MTBF (114.5 Jam), dan MTTR (3.8 Jam).
* **Deteksi Anomali Pemborosan Solar**: Penandaan baris merah otomatis (*anomaly row highlight*) untuk unit dengan rasio idling > 50% (contoh: PC200 DBCH2941 61.0%, DBCH1801 59.5%, DBCH0366 57.9%) beserta kalkulasi kerugian finansial solar (Rp 14.500/L).

#### D. Spesifikasi Backend & Aggregator
```sql
CREATE TABLE telematics_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(50),
    serial_number VARCHAR(50),
    period_date DATE,
    smr_hours DECIMAL(10,2),
    working_hours DECIMAL(10,2),
    actual_working_hours DECIMAL(10,2),
    actual_working_ratio DECIMAL(5,2),
    e_mode_ratio DECIMAL(5,2),
    idling_ratio DECIMAL(5,2),
    fuel_consumed_liters DECIMAL(10,2),
    fuel_lph DECIMAL(5,2),
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
);
```

#### E. Tabulasi Formula KPI Availability & Utilization
| Indikator KPI | Rumus Perhitungan | Target Standard BRA | Status Aktual Armada |
|:---|:---|:---|:---|
| **Physical Availability (PA)** | `(Scheduled Time - Downtime) / Scheduled Time * 100%` | ≥ 90% | **92.4%** (Achieved) |
| **Use of Availability (UA)** | `Operating Hours / (Scheduled Time - Downtime) * 100%` | ≥ 80% | **81.8%** (Achieved) |
| **Breakdown Rate (BR)** | `Breakdown Hours / Scheduled Time * 100%` | ≤ 10% | **7.6%** (Achieved) |
| **Utilization Rate (UT)** | `Actual Working Hours / Total Calendar Hours * 100%` | ≥ 60% | **52.1%** (Needs Improvement) |
| **MTBF** | `Total Operating Time / Total Breakdown Occurrences` | ≥ 100 Jam | **114.5 Jam** (Achieved) |
| **MTTR** | `Total Repair Time / Total Breakdown Occurrences` | ≤ 4.0 Jam | **3.8 Jam** (Achieved) |

#### F. Supporting AI Prompt (Production Ready)
> "Terapkan modul `scripts/productivity.js` dan `scripts/productivity.css` pada container `#productivityModule`. Render kartu KPI PA/UA/BR/MTBF/MTTR, tabel rekonsiliasi telematika KOMTRAX 18 unit, tabel penandaan idling anomaly (>50%), serta audit 48 unit fleet standby."

---

### 3.11 Biaya (`view-biaya`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Elemen utama: `#view-biaya`, kartu KPI `#kpiBudget` & `#kpiActual`, `#chartContainer`, dan tabel `#valTableBody` (Nilai Aset & Biaya Perbaikan).

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Berdasarkan `Equipment_Expenses_Report_Tabulasi.md`, `Harga_Jual_Unit_Tabulasi.md`, dan `06_Laporan_Cash_Out_Januari_2026`.
Seluruh biaya pemeliharaan (Sparepart, Oli/Oli Filter, Jasa Mekanik Luar, Machining) diakumulasikan secara *real-time* per Unit ID melalui WO.

#### C. Spesifikasi Teknis Frontend
* Bar Chart Grafik *Budget vs Actual Cost* (8 Bulan Terakhir) yang responsif di `#chartContainer`.
* DataTable Nilai Buku Aset vs Harga Pasaran vs Total Biaya Perbaikan Kumulatif.

#### D. Spesifikasi Backend & Cost Calculation Engine
```sql
SELECT 
    a.asset_id,
    a.acquisition_value,
    COALESCE(SUM(c.amount), 0) AS total_repair_cost
FROM assets a
LEFT JOIN cost_transactions c ON a.asset_id = c.asset_id
GROUP BY a.asset_id;
```

#### E. Tabulasi Struktur Klasifikasi Biaya Maintenance
| Biaya Category | Sub-Kategori Transaksi | Direct Source Link | Impact Financial |
|:---|:---|:---|:---|
| Spare Part & Consumable| Filter, Hose, Seal, Fluid, Tire | Issuance Part SPB | Menambah Biaya WO |
| Labor & Overtime | Jam Lembur Mekanik Internal (SPL)| Timesheet Mekanik | Cost Allocation per Unit |
| External Service | Bubut, Recondition, Sub-contractor| PO Jasa Outsource | Accounts Payable |
| Capital Expenses (CAPEX)| Overhaul Mesin, Replacement Engine| Purchase Order Unit | Kapitalisasi Nilai Aset |

#### F. Supporting AI Prompt (Production Ready)
> "Integrasikan library Chart.js pada container `#chartContainer` di `#view-biaya`. Buatkan grouped bar chart yang membandingkan 'Budget Maintenance' vs 'Actual Cost' selama 8 bulan terakhir dengan animasi smooth dan tooltip format mata uang Rupiah (Rp)."

---

### 3.12 People & KPI (`#view-people`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Diterapkan pada seksi khusus `#view-people` dengan modul independen `scripts/people-kpi.js` dan `scripts/people-kpi.css`, terakses secara langsung dari sidebar navigasi `onclick="showView('people', '', 'menu-people')"`.

#### B. Pemahaman Alur Bisnis & Integration Context (`material/KPI-TEAM/`)
Modul ini mengintegrasikan secara erat 5 dokumen utama dari direktori `material/KPI-TEAM/`:
1. **`Template_KPI_Head_of_Equipment.md`**: Penilaian 10 Indikator Kinerja Head of Equipment (Target RTW, Downtime, Kepatuhan PM, Waktu Respon, Delay Parts, Delay Manpower, Deviasi Biaya, Repeat Breakdown, Pelaporan & Inisiatif) dengan kalkulator interaktif terbobot.
2. **`analisis_produktivitas_mekanik_feb2026.md`**: Rekapitulasi produktivitas 10 mekanik (489.62 jam kerja teralokasi, 430.11 jam normal, 59.51 jam lembur, rasio delay sparepart, efektivitas vs standar 208 jam/bulan).
3. **`Evaluasi_P_Martin_dan_Standar_Planner.md`**: Matriks kesenjangan kompetensi Maintenance Planner (Latar belakang D3 Akuntansi vs Standar D3/S1 Teknik Mesin), serta Roadmap Pengembangan 30-60-90 Hari.
4. **`ABSEN_DAN_LEMBUR_JANUARI_2026_YARD_KM12.md`**: Rekapitulasi absensi (KJ, KL, O) dan leaderboard lembur 27 personel (1.397 jam lembur audit).
5. **`SPL_23_JULI_2026_konversi_dan_penjelasan.md`**: Audit verifikasi Surat Perintah Lembur resmi Workshop KM 12 (Suwardi & Hendrik, 16:00-17:00).

#### C. Spesifikasi Teknis Frontend
* **Multi-Tab Architecture**: 4 Sub-Modul (Tab 1: KPI Head of Equipment, Tab 2: Produktivitas Mekanik, Tab 3: Evaluasi Maintenance Planner, Tab 4: Absensi & Lembur Tim).
* **Dynamic Weighted Calculator**: Penilaian Skor 1-5 dengan formula `Nilai Bobot = Skor × Bobot / 5`, yang meng-update total skor dan status kategori secara *real-time*.
* **Interactive Leaderboard & Filters**: Filter pencarian mekanik, visualisasi *progress bar* efektivitas, dan widget audit TTD Surat Perintah Lembur (SPL).

#### D. Spesifikasi Backend & API Aggregator
* **Endpoints**: `GET /api/v1/people/head-kpi`, `GET /api/v1/people/mechanic-productivity`, `GET /api/v1/people/planner-evaluation`, `GET /api/v1/people/overtime-summary`.

#### E. Tabulasi Matriks 10 Indikator KPI Head of Equipment
| No | Aspek KPI | Indikator Kinerja | Target | Bobot (%) | Formula & Rule Penilaian |
|:---|:---|:---|:---|:---|:---|
| 1 | RTW & Downtime | % Unit Selesai ≤ Target RTW | ≥ 90% | 15% | `RTW On-Time / Total RTW * 100%` |
| 2 | RTW & Downtime | Rata-rata Downtime per Unit | ≤ Standar | 20% | DT Dump Truck ≤ 5-7 Hari; No Unit > 7 Hari |
| 3 | RTW & Downtime | Kepatuhan PM Tepat Waktu | ≥ 95% | 10% | `PM On-Time / Total PM Due * 100%` |
| 4 | Percepatan | Waktu Respon Awal Kerusakan | ≤ 24 Jam | 10% | Elapsed Time sejak laporan sampai JO terbit |
| 5 | Percepatan | Keterlambatan karena Spare Part | ≤ 10% | 10% | `Job Delayed Parts / Total Maintenance Job * 100%` |
| 6 | Percepatan | Keterlambatan karena Manpower | ≤ 5% | 5% | `Job Delayed Manpower / Total Maintenance Job * 100%` |
| 7 | Biaya & Kualitas | Deviasi Biaya Corrective | ≤ 110% | 10% | `Biaya Corrective Actual / Biaya Rencana * 100%` |
| 8 | Biaya & Kualitas | Repeat Breakdown ≤ 30 Hari | ≤ 5% | 10% | `Kerusakan Berulang ≤ 30hr / Total Repair Selesai * 100%` |
| 9 | Kepemimpinan | Monitoring & Pelaporan Unit | Konsisten | 5% | Kepatuhan pengisian JO, RTW, & report harian |
| 10| Kepemimpinan | Inisiatif Percepatan Perbaikan | Aktif | 5% | Penambahan shift, vendor support, & prioritas repair |

#### F. Supporting AI Prompt (Production Ready)
> "Terapkan modul JavaScript `scripts/people-kpi.js` dan CSS `scripts/people-kpi.css` yang merender tampilan 'People & KPI' pada container `#peopleKpiModule`. Sertakan kalkulator interaktif KPI Head of Equipment, tabel produktivitas 10 mekanik dengan efektivitas vs 208 jam, matriks gap kompetensi planner, dan leaderboard lembur Januari 2026."

---

### 3.13 HSE / Accident (`#view-hse`)

#### A. Analisis & Scope HTML (`dashboard.html`)
Diterapkan pada seksi khusus `#view-hse` dengan modul independen `scripts/hse-accident.js` dan `scripts/hse-accident.css`, terakses secara langsung dari sidebar navigasi `onclick="showView('hse', '', 'menu-hse')"`.

#### B. Pemahaman Alur Bisnis & Integration Context (`material/LAPORAN_ACCIDENT.md` & `TAR_Unit_CS_41001_RWI.md`)
Modul ini mengintegrasikan secara rinci format registrasi laporan kecelakaan kerja dan laporan analisis teknis:
1. **`material/LAPORAN_ACCIDENT.md`**: Mengadopsi 7 seksi standar laporan insiden:
   - Seksi I (Data Umum: Dokumen No, Unit Code, Tipe/Merk, Plat, Lokasi, Datetime, Operator & Masa Kerja).
   - Seksi II (Kronologi Kejadian: Deskripsi narasi objektif).
   - Seksi III (Kondisi Lingkungan: Cuaca, Jalan, Penerangan, Kepadatan, Muatan).
   - Seksi IV (Dampak Insiden: Kerusakan fisik, Estimasi downtime, Kerugian finansial).
   - Seksi V (Analisa Awal Penyebab: Checkbox faktor Human, Mechanical, Environmental, Procedural).
   - Seksi VI & VII (Corrective Action & Preventive Action / CAPA Tracking).
2. **`material/BREAKDOWN/TAR_Unit_CS_41001_RWI.md`**: Mengintegrasikan Case Study Technical Analysis Report (TAR No. 01/TAR/05/2026 Unit CS-41001 Powder Binder Spreader XCMG XKC185, trouble 11-Mei-2026 overload cement, penanganan & klaim garansi OEM XCMG).

#### C. Spesifikasi Teknis Frontend
* **Multi-Step Wizard Incident Form**: 3 Step (Step 1: Data Umum & Kronologi, Step 2: Lingkungan & Dampak Finansial, Step 3: Faktor Penyebab & CAPA).
* **Unit Lock Engine (`ACCIDENT_HOLD`)**: Insiden severitas *Moderate* & *Critical* secara otomatis mengubah status unit di `globalData.assets` menjadi `ACCIDENT_HOLD` dan memunculkan *Red Alert Banner* di bagian atas layar.
* **Otorisasi Rilis Unit**: Pengunci unit hanya dapat di-release kembali ke status `READY` setelah mendapatkan verifikasi otorisasi dari Equipment Manager / Safety Head.

#### D. Spesifikasi Backend & Schema Relasional
```sql
CREATE TABLE accidents (
    accident_id VARCHAR(50) PRIMARY KEY,
    asset_id VARCHAR(50),
    report_date DATE,
    incident_datetime TIMESTAMP,
    location VARCHAR(100),
    operator_name VARCHAR(100),
    operator_tenure VARCHAR(50),
    chronology TEXT,
    weather VARCHAR(50),
    road_condition VARCHAR(50),
    lighting_condition VARCHAR(50),
    severity_level ENUM('Minor', 'Moderate', 'Critical') DEFAULT 'Minor',
    physical_damage TEXT,
    estimated_repair_cost DECIMAL(15,2),
    estimated_downtime_days INT,
    total_financial_loss DECIMAL(15,2),
    cause_factors JSON,
    corrective_action TEXT,
    preventive_action TEXT,
    is_unit_locked BOOLEAN DEFAULT TRUE,
    status ENUM('Reported', 'Investigating', 'CAPA_Pending', 'Closed') DEFAULT 'Reported',
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
);
```

#### E. Tabulasi Klasifikasi Severitas Insiden & Alur Rilis
| Level Severitas | Dampak Insiden & Ambang Kerugian | Otoritas Approval Rilis Unit | Tindakan Wajib Sistem |
|:---|:---|:---|:---|
| **Minor** | Kerusakan Ringan, No Injury, Cost < Rp 10 Juta | Equipment Manager & Safety Officer | Unit Tetap Operasional / WO Regular |
| **Moderate** | Kerusakan Komponen Utama, Downtime 1-3 Hari | Equipment Manager & HSE Head | Lock Unit -> Status `ACCIDENT_HOLD` |
| **Critical** | Major Structural Damage, Fatality / High Loss (>Rp 30 Jt) | General Manager & Direktur Operasional | Lock Unit + TAR Mandatory & Audit CAPA |

#### F. Supporting AI Prompt (Production Ready)
> "Terapkan modul `scripts/hse-accident.js` dan `scripts/hse-accident.css` pada container `#hseAccidentModule`. Sertakan multi-step form wizard pelaporan insiden 3 step, banner unit locked `ACCIDENT_HOLD`, tabulasi case study TAR Unit CS-41001 XCMG, serta otorisasi rilis unit kembali ke READY."

---

### 3.14 Laporan (`view-uc` -> Laporan)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Laporan".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Sesuai `LAPORAN_DIVISI_EQUIPMENT_JANUARI_2026.md`.
Modul pelaporan manajemen menyediakan *export generator* otomatis untuk laporan harian, mingguan, dan bulanan dalam format Excel (.xlsx) dan PDF yang siap cetak.

#### C. Spesifikasi Teknis Frontend
* Report Generator Selector (Tipe Laporan, Rentang Tanggal, Filter Site/Lokasi, Kategori Unit).
* Preview Data Table sebelum mengunduh berkas.

#### D. Spesifikasi Backend & Export Engine
* **Export Process**: Stream generator data dari database MySQL ke SheetJS / ExcelJS atau PDFKit backend.
* **Endpoint**: `POST /api/v1/reports/generate-excel` & `POST /api/v1/reports/generate-pdf`.

#### E. Tabulasi Catalog Laporan Standar Sistem
| Kode Laporan | Nama Laporan | Format Output | Target Audiens |
|:---|:---|:---|:---|
| RPT-01 | Daily Equipment Availability & Status | Excel / PDF | Operational Manager & Site Director |
| RPT-02 | Monthly Work Order Summary & MTTR/MTBF | Excel | Maintenance Planner |
| RPT-03 | Spare Part Consumption & Inventory Value | Excel | Purchasing & Finance |
| RPT-04 | Equipment Cost History & Budget Variance | PDF | Executive Management & Director |

#### F. Supporting AI Prompt (Production Ready)
> "Desain halaman Report Generator pada `#view-uc` Laporan. Buatkan card pilihan laporan (Laporan Availability Harian, Laporan Rekap WO, Laporan Biaya Unit). Setiap card memiliki filter tanggal, dropdown lokasi, dan tombol 'Download Excel' & 'Preview Data'."

---

### 3.15 Approval (`view-uc` -> Approval)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Approval".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Berdasarkan `ARSITEKTUR_BPMN...md` (Bab 3 & 7).
Seluruh transaksi yang memerlukan persetujuan berjenjang (SPB Sparepart > Rp X, WO Major Overhaul, Rilis Unit Insiden, Mutasi Aset) berkumpul pada satu *Central Approval Inbox*.

#### C. Spesifikasi Teknis Frontend
* Unified Approval Inbox dengan filter Tab (Pending, Approved, Rejected).
* Quick Action Modal: Tombol Approve (Hijau) & Tombol Reject (Merah) dengan kewajiban mengisi Textarea "Alasan Penolakan".

#### D. Spesifikasi Backend & Multi-Tier Matrix Engine
```sql
CREATE TABLE approvals (
    approval_id VARCHAR(50) PRIMARY KEY,
    document_type ENUM('SPB', 'WO', 'MUTASI', 'ACCIDENT_RELEASE', 'PO'),
    document_id VARCHAR(50) NOT NULL,
    current_approver_id VARCHAR(50),
    approval_tier INT DEFAULT 1,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### E. Tabulasi Approval Matrix Limits (Contoh BRA Matrix)
| Jenis Dokumen | Rentang Nilai / Kondisi | Approver Tier 1 | Approver Tier 2 | Approver Tier 3 |
|:---|:---|:---|:---|:---|
| SPB Sparepart | < Rp 5.000.000 | Maintenance Foreman | Equipment Manager | - |
| SPB Sparepart | >= Rp 5.000.000 | Equipment Manager | General Manager | - |
| Work Order Major | Biaya Est. > Rp 20.000.000| Equipment Manager | General Manager | Direktur Operasional |
| Rilis Unit Insiden | Severitas Moderate / Critical | HSE Head | Equipment Manager | General Manager |

#### F. Supporting AI Prompt (Production Ready)
> "Tuliskan kode UI JavaScript Inbox Approval pada `#view-uc`. Tampilkan list card item permohonan yang berisi Jenis Dokumen, Pemohon, Nominal/Detail, dan Status. Sertakan modal konfirmasi saat klik 'Reject' yang mewajibkan input teks alasan sebelum melempar request ke backend API."

---

### 3.16 Pengaturan (`view-uc` -> Pengaturan)

#### A. Analisis & Scope HTML (`dashboard.html`)
Ditempatkan pada seksi `#view-uc` dengan judul "Pengaturan".

#### B. Pemahaman Alur Bisnis & Aturan Sistem
Sesuai `ARSITEKTUR_BPMN...md` (Modul M12 - Administrasi Sistem).
Pengaturan mengontrol seluruh konstanta sistem: Management User & Access Role (RBAC), Ambang Batas Condition Monitoring, Master Lokasi/Site, Matriks SLA, dan Template Checklist P2H.

#### C. Spesifikasi Teknis Frontend
* Tab Navigation Setting (Manajemen User, Master Lokasi, Parameter SLA, Threshold Component).
* Interface Management Role Checkbox Matrix (Read, Create, Edit, Delete, Approve per Modul).

#### D. Spesifikasi Backend & Dynamic Config Store
* **Configuration Table**: `system_configs (config_key, config_value, description)`.

#### E. Tabulasi Master Rules & Configuration Keys
| Config Key | Default Value | Description / Scope | Modification Scope |
|:---|:---|:---|:---|
| `SLA_WO_IDENTIFICATION_MINS` | `30` | SLA Pembuatan Tiket WO sejak Unit Down | System Admin |
| `THRESHOLD_TIRE_RED_MM` | `3.2` | Batas Merah Aus Ban (mm) | Fleet Planner |
| `PM_WARNING_WINDOW_HM` | `50` | Batas HM Warning PM Due Soon | Maintenance Planner |
| `FUEL_ANOMALY_PERCENT` | `15.0` | Toleransi % Kebocoran / Anomali BBM | Equipment Manager |

#### F. Supporting AI Prompt (Production Ready)
> "Buatkan halaman System Settings di `#view-uc` Pengaturan dengan UI Tabified: Tab 1 (User & Role RBAC Table), Tab 2 (Konfigurasi Threshold & SLA), dan Tab 3 (Master Lokasi Project). Tambahkan handler JS untuk menyimpan nilai parameter konfigurasi secara real-time."

---

## 4. Arsitektur Relasi Database & Integrasi API Endpoints

### Single Source Schema Mapping (ERD Topology)
```
  ┌──────────────┐          ┌────────────────────┐          ┌─────────────────┐
  │    assets    │1        *│    work_orders     │1        *│   wo_time_logs  │
  ├──────────────┤──────────├────────────────────┤──────────├─────────────────┤
  │ asset_id (PK)│          │ wo_id (PK)         │          │ log_id (PK)     │
  │ status       │          │ asset_id (FK)      │          │ wo_id (FK)      │
  │ location     │          │ priority           │          │ mechanic_id     │
  └──────────────┘          │ status             │          │ hours_spent     │
         │1                 └────────────────────┘          └─────────────────┘
         │                             │1
         │                             │
         │*                            │*
  ┌──────────────┐          ┌────────────────────┐
  │meter_readings│          │ purchase_requests  │
  ├──────────────┤          ├────────────────────┤
  │ reading_id   │          │ spb_id (PK)        │
  │ asset_id (FK)│          │ wo_id (FK)         │
  │ hm_value     │          │ status             │
  └──────────────┘          └────────────────────┘
```

---

## 5. Roadmap Eksekusi & Strategi Refactoring Codebase

Untuk mengubah `dashboard.html` monolitik menjadi aplikasi produksi yang kokoh:

1. **Fase 1: Modularisasi JavaScript (Struktur Berkas)**
   * Pisahkan skrip monolitik ke berkas modular terorganisir:
     * `/js/app.js` (Router & Core State Handler)
     * `/js/views/dashboard.js`
     * `/js/views/asset.js`
     * `/js/views/workorder.js`
     * `/js/views/biaya.js`

2. **Fase 2: Mock API Data Service Layer**
   * Buat service layer terpisah (`/js/services/api.js`) untuk memisahkan logika UI dari sumber data JSON/Backend REST API.

3. **Fase 3: Migrasi ke Frontend Framework (Opsional/Rekomendasi)**
   * Jika kompleksitas form & state bertambah, lakukan migrasi dari Vanilla HTML/JS ke **Vite + React** atau **Next.js** dengan TailwindCSS / Modular CSS.
