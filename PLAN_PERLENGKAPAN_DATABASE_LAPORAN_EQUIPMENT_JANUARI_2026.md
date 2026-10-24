# Rencana Kerja: Perlengkapan Database ServicePlan-BRA Berdasarkan Laporan Divisi Equipment Januari 2026

**Tanggal Dokumen:** 24 Oktober 2026  
**Status:** Dokumen Kerja Resmi (Actionable Database Enrichment & Integration Plan)  
**Branch:** `main`  
**Target Database:** `u646470441_ServicePlanBRA` / `serviceplan_bra` (MySQL Laragon)  
**Dokumen Sumber Data:** 
- Folder Markdown Terstruktur: [`material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/material/ASSET/LAPORAN%20DIVISI%20EQUIPMENT%20JANUARI%202026/) (28 berkas, 21 tabel granular)
- Berkas Indeks: [`material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/INDEX.md`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/material/ASSET/LAPORAN%20DIVISI%20EQUIPMENT%20JANUARI%202026/INDEX.md)
- Dokumen PDF Asli: [`raw-material/ADMINISTRASI/LAPORAN DIVISI EQUIPMENT JANUARI 2026.pdf`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/raw-material/ADMINISTRASI/LAPORAN%20DIVISI%20EQUIPMENT%20JANUARI%202026.pdf) (97 halaman)

---

## 1. Ringkasan Eksekutif & Tujuan

Laporan Divisi Equipment Januari 2026 (97 halaman) memuat catatan operasional terlengkap PT Bina Rekayasa Anugrah (BRA) yang mencakup:
1. Riwayat 364 kasus unit breakdown dan downtime jam/hari.
2. Catatan 376 tindakan perbaikan harian mekanik (*Daily Repairs*).
3. Jadwal dan realisasi *Weekly Greasing* 97 unit hauling.
4. Tracking servis berkala dan paket kebutuhan filter & pelumas Tahap 1 s.d. 4.
5. Mutasi pergudangan logistik (BHW-IN: 210 penerimaan, BHW-OUT: 355 pemakaian, dan master kartu stok 1.346 item suku cadang, ban, baterai, dan pelumas).
6. Realisasi pengeluaran kas operasional (*Cash Out*) per unit alat berat.
7. Pendapatan sewa alat 235 unit dan matriks hari kerja 31 hari di proyek ADERA, RWI, PHR, PEP, dan WUR.

Tujuan rencana ini adalah **menginjeksi dan merekonsiliasi seluruh data operasional riil tersebut ke dalam skema database MySQL ServicePlan-BRA secara aman (*fail-safe*), bertahap (*batched*), bebas inkonsistensi, dan teruji secara otomatis (*regression-tested*) tanpa merusak data master yang sudah ada**.

---

## 2. Hasil Audit Konsistensi Data & Penanganan Kolom Kosong (*Blanks*)

Sebelum eksekusi migrasi, seluruh data sumber telah diaudit melalui script inspeksi PHP terhadap tabel master database. Berikut temuan konsistensi dan aturan imputasi (*default imputation rules*) untuk kolom-kolom kosong:

| Modul Data Sumber | Total Baris | Kecocokan ke DB (`assets`) | Kolom Kosong (*Blanks*) Signifikan | Analisis Penyebab & Aturan Penanganan (*Imputation Rule*) |
| :--- | :---: | :---: | :--- | :--- |
| **01. Breakdown** (`01_Laporan_Break_Down_...`) | 364 | 330 / 364 (90,7%) | `estimasi_selesai`: 351 (96,4%)<br>`project`: 189 (51,9%)<br>`km_hm`: 112 (30,8%)<br>`operator`: 52 (14,3%) | - **Estimasi Selesai**: Lapangan memantau progress harian dinamis. Aturan: Jika unit sudah READY, isi tanggal `closed_at`. Jika status Open/In Progress, isi `trouble_at + 3 hari`.<br>- **Project**: Kosong karena unit standby/breakdown di Workshop Yard KM 12. Aturan: Isi `'YARD_KM_12'`.<br>- **Operator**: Kosong saat unit tidak ditugaskan sopir spesifik. Aturan: Default `'OPERATOR_UNASSIGNED'`. |
| **02. Daily Repairs** (`02_Daily_Mechanical_...`) | 376 | 359 / 376 (95,5%) | `km`: 97 (25,8%)<br>`hm`: 69 (18,4%)<br>`pic`: 43 (11,4%)<br>`no_polisi`: 9 (2,4%) | - **KM vs HM**: *Konsistensi Valid*. Alat berat crawler (Excavator, Dozer) berbasis HM, sedangkan truk berbasis KM. Kolom yang tidak berlaku dibiarkan `NULL`.<br>- **PIC Mekanik**: Kosong pada pekerjaan bubut/sublet vendor eksternal. Aturan: Default `'BENGKEL_REKANAN_LUAR'`. |
| **03. Greasing** (`03_Laporan_Greasing_...`) | 97 | 77 / 97 (79,4%) | `km_akhir`: 97 (100%)<br>`minggu_2-4`: 95-97 (98%)<br>`delay`: 97 (100%) | - **KM Akhir**: Template lama belum diisi admin. Diambil dari data `assets.last_hm_km`.<br>- **Minggu 2-4 Kosong**: Greasing unit hauling dirotasi berkala (siklus 250 jam/2 minggu), aktivitas Januari terkonsentrasi di Minggu 1 (23 unit aktif). Hanya unit bertanggal riil yang diinjeksi ke `grease_logs`. |
| **04. Service & PM** (`04_01` & `04_02`) | 180 | 100% | Part Number pelumas: 45 item | - Pelumas/oli dan air radiator pada laporan servis dicatat berdasarkan volume liter ("± 218 L", "13 Ltr"), bukan kode OEM part number. Aturan: Di-link ke master part pelumas `GEN-OIL-xxx` sesuai viskositas (SAE 15W-40, ISO VG46, SAE 90, SAE 140). |
| **05. Master Stok Gudang** (`05_03`) | 1.346 | 673 match `parts` | `part_number`: 627 (46,6%) | - **Part Number Kosong**: Merupakan baut, mur, klep selang, nepel, cat pilox, kain majun (*general hardware/consumables*). Aturan: Diberikan kode SKU auto-increment berformat `GEN-HW-0001` s/d `GEN-HW-0627`. |
| **06. Cash Out Unit** (`06_02`) | 255 | 37 unit berbiaya riil | `biaya_januari`: 218 unit bernilai `-Rp - -` (85,5%) | - **Biaya Kosong/Nol**: Normal, karena hanya 37 unit yang melakukan penggantian suku cadang corrective/preventive berbayar kas di Januari 2026. Unit berbiaya nol tidak dibuatkan mutasi debit. |
| **07. Pendapatan Sewa** (`07_01` & `07_02`) | 235 | 184 unit aktif | `omzet_sewa`: 51 unit bernilai `-Rp - -` (21,7%) | - **Unit Pendapatan Nol**: Unit standby pool cadangan atau breakdown jangka panjang di Yard. Di-link ke proyek `'STANDBY_POOL'` dengan tarif 0. |

---

## 3. Diagram Alur Pelaksanaan 6 Batch

```
+-------------------------------------------------------------------------------------------------------+
| BATCH 1: Standardisasi Identitas Aset & Master Suku Cadang Baru                                       |
| Target: assets (alias_name, hull_number), parts (46 PN baru + 627 SKU consumable)                      |
+-------------------------------------------------------------------------------------------------------+
                                                   |
                                                   v
+-------------------------------------------------------------------------------------------------------+
| BATCH 2: Rekonsiliasi Work Order, Downtime & Riwayat Operator                                        |
| Target: work_orders (trouble_at, closed_at, downtime_minutes, mechanic), asset_operator_history       |
+-------------------------------------------------------------------------------------------------------+
                                                   |
                                                   v
+-------------------------------------------------------------------------------------------------------+
| BATCH 3: Preventive Maintenance, Greasing Hauling & Battery Logs                                      |
| Target: grease_logs (23 log baru), battery_logs (6 log baru), assets (last_service_hm_km, interval)   |
+-------------------------------------------------------------------------------------------------------+
                                                   |
                                                   v
+-------------------------------------------------------------------------------------------------------+
| BATCH 4: Mutasi Pergudangan, Penerimaan & Pengeluaran Part (BHW-IN & OUT)                             |
| Target: inventory_transactions (210 IN, 355 OUT, 33 Oli, 16 Ban), parts.stock_qty                     |
+-------------------------------------------------------------------------------------------------------+
                                                   |
                                                   v
+-------------------------------------------------------------------------------------------------------+
| BATCH 5: Ledger Biaya Operasional & Finansial Bulanan Equipment                                      |
| Target: asset_cost_transactions (37 transaksi pemeliharaan), cost_financial_monthly (rekap Jan 2026)    |
+-------------------------------------------------------------------------------------------------------+
                                                   |
                                                   v
+-------------------------------------------------------------------------------------------------------+
| BATCH 6: Penugasan Kontrak Sewa, Alokasi Proyek & Matriks Jam Operasi                                 |
| Target: contract_assignments (184 kontrak proyek), telematics_logs / asset daily utilization          |
+-------------------------------------------------------------------------------------------------------+
```

---

## 4. Rincian Pelaksanaan, Step-by-Step, Panduan Testing & Keterdampakan Menu/Console per Batch

---

### 📦 BATCH 1: Standardisasi Identitas Aset & Master Katalog Suku Cadang Baru `[SELESAI & TERVERIFIKASI - 24 OKT 2026]`

> **Status Eksekusi:** ✅ **BERHASIL DISELESAIKAN SECARA PERMANEN (10/10 TEST PASSED)**  
> **Skrip Migrasi:** [`scripts/migrate_batch1_assets_parts.php`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/migrate_batch1_assets_parts.php)  
> **Skrip Verifikasi:** [`tests/test_batch1_identity_and_parts.php`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/tests/test_batch1_identity_and_parts.php)  
> **Hasil:** 30 unit aset diperkaya dengan alias operasional & serial number, 672 suku cadang baru diingest (45 OEM + 627 SKU `GEN-HW-xxxx`), saldo stok 419 part diperbarui, total katalog menjadi **1.393 item** (*Zero Duplicates*).

#### 1. Tujuan & Sasaran Tabel
- Melengkapi kamus alias panggil operasional lapangan (`alias_name`, `hull_number`) pada tabel `assets`.
- Mengingest 45 suku cadang baru dengan OEM Part Number valid ke tabel `parts`.
- Menstandarkan 627 item general hardware/consumables tanpa part number menjadi SKU berformat `GEN-HW-xxxx`.

#### 2. Langkah-Langkah Eksekusi (*Step-by-Step*)
1. Buat skrip migrasi `scripts/migrate_batch1_assets_parts.php`.
2. Muat kamus alias dari `06_02` dan `07_01`:
   - Single Drum Compactor: `SD 01` s/d `SD 07` $\rightarrow$ Map ke `CS-41001` dst.
   - Padfoot Compactor: `PF 01` s/d `PF 03` $\rightarrow$ Map ke serial number Bomag Padfoot.
   - Excavator: `EXCA 01` s/d `EXCA 25` $\rightarrow$ Map ke `EXC-00001` dst.
   - Dozer: `DZ 01` s/d `DZ 10` $\rightarrow$ Map ke `DZ-00001` dst.
3. Eksekusi `UPDATE assets SET alias_name = :alias, hull_number = :hull WHERE asset_id = :id` hanya jika kolom saat ini masih kosong/belum seragam.
4. Parse tabel `05_03_Laporan_Stok_Penerimaan_dan_Pemakaian_Spare_Part.md`:
   - Ekstrak 46 item yang memiliki part number unik dan belum ada di tabel `parts`.
   - Ekstrak 627 item tanpa part number, berikan part number sistem `GEN-HW-0001` s/d `GEN-HW-0627`.
   - Masukkan ke tabel `parts` via `INSERT INTO parts (part_number, part_name, category, unit_measure, stock_qty, unit_cost, location_warehouse) VALUES (...)`.

#### 3. Panduan Testing & Verifikasi (*Verification Guide*)
- **SQL Verification Query:**
  ```sql
  -- Cek alias yang berhasil diupdate
  SELECT asset_id, asset_code, alias_name, hull_number, category FROM assets WHERE alias_name IS NOT NULL LIMIT 20;
  
  -- Cek total parts setelah penambahan
  SELECT category, COUNT(*) as total_items FROM parts GROUP BY category;
  -- Expected: Total parts bertambah dari 721 menjadi 1.394 item (721 lama + 46 baru + 627 SKU hardware)
  ```
- **CLI Test:** Buat skrip uji `tests/test_batch1_identity_and_parts.php` yang memverifikasi tidak ada duplikasi part number (`COUNT(DISTINCT part_number) = COUNT(*)`).

#### 4. Keterdampakan pada Menu, Konsol & Layar UI
- 🖥️ **Menu Master Asset (`#menu-asset`)**:
  - Kolom pencarian aset (*search input*) dapat mencari unit berdasarkan nama panggil lapangan (misal mengetik "SD 01" atau "EXCA 04" langsung menampilkan unit terkait).
  - Autocomplete pada form tambah WO/P2H otomatis mengenali kode lambung baru.
- 🖥️ **Menu Spare Part & Logistik (`#menu-logistics`)**:
  - Tabel master stok gudang menampilkan seluruh 1.300+ item persediaan dengan pencarian instan.
  - Form Permintaan Barang (SPB) memiliki opsi suku cadang yang lengkap tanpa penolakan "Part Not Found".
- 📟 **Console Browser**:
  - Tidak ada error `Uncaught TypeError` saat mapping autocomplete unit.
  - Respon API `GET /api/assets.php` dan `GET /api/parts.php` tetap di bawah 200ms dengan pagination.

---

### 📦 BATCH 2: Rekonsiliasi Work Order, Downtime & Riwayat Penugasan Operator `[SELESAI & TERVERIFIKASI - 24 OKT 2026]`

> **Status Eksekusi:** ✅ **BERHASIL DISELESAIKAN SECARA PERMANEN (12/12 TEST PASSED)**  
> **Skrip Migrasi:** [`scripts/migrate_batch2_work_orders.php`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/migrate_batch2_work_orders.php)  
> **Skrip Verifikasi:** [`tests/test_batch2_work_orders_enrichment.php`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/tests/test_batch2_work_orders_enrichment.php)  
> **Hasil:** 364 record `work_orders` diperkaya dengan tanggal breakdown (`trouble_at`), waktu selesai (`closed_at`), durasi downtime menit/formatted, assigned mechanic + JSON array, tindakan perbaikan komprehensif, dan root cause analysis dari data riil. Relasi dummy seed 101-103 disinkronkan ke unit riil. Riwayat operator bertambah 29 entri baru menjadi total 196 entri di `asset_operator_history`. Zero negative downtime (`closed_at >= trouble_at`).

#### 1. Tujuan & Sasaran Tabel
- Melengkapi 364 record `work_orders` dengan data riil breakdown: tanggal/jam gangguan, durasi downtime menit/hari, tindakan mekanik, akar masalah, dan estimasi.
- Mengingest 312 catatan pengemudi/operator aktif ke `asset_operator_history`.

#### 2. Langkah-Langkah Eksekusi (*Step-by-Step*)
1. Buat skrip `scripts/migrate_batch2_work_orders.php`.
2. Baca file `01_Laporan_Break_Down_Januari_2026.md` dan `02_Daily_Mechanical_Repairs_Januari_2026.md`.
3. Rekonsiliasi dengan tabel `work_orders`:
   - Hubungkan nomor unit ke `asset_id` yang sudah distandardisasi di Batch 1.
   - Parse tanggal dan jam gangguan: `Waktu Breakdown - Tanggal` + `Jam` $\rightarrow$ `trouble_at`.
   - Parse waktu selesai: `Waktu Ready - Tanggal` + `Jam` $\rightarrow$ `closed_at`.
   - Hitung downtime menit: jika `Downtime - Hari` = 15,00 dan jam = 368 jam, simpan `downtime_minutes = 22080`.
   - Set status: Jika `Update Kondisi` = `'READY'`, status WO = `'Closed'`. Jika dalam penanganan, status WO = `'In Progress'`.
   - Set mekanik yang menangani: simpan string nama mekanik ke `assigned_mechanic` dan array JSON ke `assigned_mechanics_json`.
   - Update tindakan perbaikan dari kolom `Permasalahan / Trouble` dan `Progres Saat Ini`.
4. Parse nama operator: simpan ke `asset_operator_history` dengan `assigned_date = '2026-01-01'`.

#### 3. Panduan Testing & Verifikasi (*Verification Guide*)
- **SQL Verification Query:**
  ```sql
  -- Verifikasi pengisian field breakdown
  SELECT wo_id, asset_id, status, downtime_minutes, trouble_at, closed_at, assigned_mechanic 
  FROM work_orders 
  WHERE downtime_minutes > 0 LIMIT 15;
  
  -- Pastikan tidak ada downtime negatif
  SELECT COUNT(*) FROM work_orders WHERE closed_at < trouble_at; -- Expected: 0
  ```
- **CLI Test:** Buat `tests/test_batch2_work_orders_enrichment.php` untuk memvalidasi integritas relasi foreign key antara `work_orders.asset_id` dan `assets.asset_id`.

#### 4. Keterdampakan pada Menu, Konsol & Layar UI
- 🖥️ **Menu Work Order (`#menu-wo`)**:
  - Kolom Kanban ("Closed", "In Progress") terisi kartu-kartu tiket riil dengan badge downtime yang akurat.
  - Modal detail WO (`#modalWoDetail`) menampilkan timeline perbaikan mekanik, deskripsi kerusakan, dan PIC mekanik secara lengkap.
- 🖥️ **Menu Dashboard (`#menu-dashboard`)**:
  - Kartu KPI "Unit Breakdown" dan "Unit Ready" sinkron dengan rekap bulanan Januari 2026.
  - Metrik MTTR (*Mean Time to Repair*) dan MTBF (*Mean Time Between Failures*) otomatis terhitung dari `downtime_minutes`.
- 🖥️ **Menu People & KPI (`#menu-people`)**:
  - Beban kerja per mekanik (Syawal, Wagiman, Rejeki Siregar, Sitepu, Joni) menampilkan jumlah tiket yang diselesaikan.
- 📟 **Console Browser**:
  - Event klik kartu WO di Kanban merender detail tiket tanpa `null pointer exception` pada properti waktu breakdown.

---

### 📦 BATCH 3: Preventive Maintenance, Greasing Hauling & Battery Health Logs `[SELESAI & TERVERIFIKASI - 25 OKT 2026]`

> **Status Eksekusi:** ✅ **BERHASIL DISELESAIKAN SECARA PERMANEN (11/11 TEST PASSED)**  
> **Skrip Migrasi:** [`scripts/migrate_batch3_pm_grease_battery.php`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/migrate_batch3_pm_grease_battery.php)  
> **Skrip Verifikasi:** [`tests/test_batch3_pm_grease_battery.php`](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/tests/test_batch3_pm_grease_battery.php)  
> **Hasil:** Tabel `grease_logs` terisi **25 log pelumasan** mingguan hauling (23 unit dump truck, Minggu 1 & 2) dengan titik pelumasan JSON terstruktur. Tabel `battery_logs` terisi **12 log kondisi baterai baru** (6 transaksi BKTBI dari `05_05` + 6 penggantian aki riil dari WO breakdown). Tabel `assets` dan `pm_plans` tersinkronisasi untuk 13 unit RSV1 dengan status dinamis (`OVERDUE`, `DUE_SOON`), interval KM/HM, dan tanggal jatuh tempo servis.

#### 1. Tujuan & Sasaran Tabel
- Mengisi tabel `grease_logs` (saat ini 0 baris) dengan 23 log greasing mingguan hauling.
- Mengisi tabel `battery_logs` (saat ini 0 baris) dengan 6 transaksi pemakaian baterai/aki baru.
- Mengupdate parameter servis berkala pada tabel `assets` (`last_service_hm_km`, `next_service_due_hm_km`, `service_interval_val`).

#### 2. Langkah-Langkah Eksekusi (*Step-by-Step*)
1. Buat skrip `scripts/migrate_batch3_pm_grease_battery.php`.
2. Baca file `03_Laporan_Greasing_Januari_2026.md`:
   - Filter 23 baris yang memiliki tanggal dan KM/HM pada Minggu 1.
   - Insert ke tabel `grease_logs`:
     - `asset_id`: ID unit Dump Truck (`DT-00004`, `DT-00005`, dll).
     - `current_hm`: KM/HM pelumasan minggu 1.
     - `grease_type`: `'NLGI 2 / Cobra EP'`.
     - `lubrication_points`: `["Chassis", "Propeller Shaft", "Trunnion", "Spring Hanger"]`.
     - `status_level`: `'success'`.
     - `inspected_by`: User ID petugas greasing (Mekanik).
     - `inspected_at`: Tanggal pelaksanaan pelumasan.
3. Baca file `05_05_Baterai_Accu_Pemakaian.md`:
   - Ekstrak 6 unit penerima baterai baru: `DT-00049`, `DT-00055`, `DT-00059`, `DT-00060`, `DT-00064`, `EXC-00003`.
   - Insert ke tabel `battery_logs`:
     - `voltage_val`: 24.0 (untuk dump truck/alat berat 24V) atau 12.0.
     - `water_level`: `'GOOD'`.
     - `terminal_condition`: `'CLEAN'`.
     - `installation_date`: Tanggal pemasangan di laporan.
     - `status_level`: `'success'`.
4. Baca file `04_01_Tracking_HM_KM_dan_Rencana_Service.md`:
   - Update tabel `assets` untuk 13 unit yang memiliki tracking aktif:
     - `last_service_hm_km` = kolom Realisasi Service HM/KM.
     - `last_service_date` = Tanggal Realisasi.
     - `service_interval_val` = Interval Service (500 HM atau 7.500/10.000 KM).
     - `next_service_due_hm_km` = Rencana Service HM/KM.

#### 3. Panduan Testing & Verifikasi (*Verification Guide*)
- **SQL Verification Query:**
  ```sql
  -- Verifikasi isi grease_logs
  SELECT COUNT(*) as total_grease FROM grease_logs; -- Expected: 23
  SELECT asset_id, current_hm, grease_type, inspected_at FROM grease_logs LIMIT 5;
  
  -- Verifikasi isi battery_logs
  SELECT COUNT(*) as total_battery FROM battery_logs; -- Expected: 6
  SELECT asset_id, battery_brand, voltage_val, installation_date FROM battery_logs;
  
  -- Verifikasi update servis master asset
  SELECT asset_code, last_service_hm_km, next_service_due_hm_km, service_interval_val 
  FROM assets WHERE last_service_hm_km IS NOT NULL;
  ```
- **CLI Test:** Buat `tests/test_batch3_pm_monitoring.php` untuk memverifikasi endpoint `api/pm.php` dan `api/condition.php`.

#### 4. Keterdampakan pada Menu, Konsol & Layar UI
- 🖥️ **Menu Condition Monitoring (`#menu-condition`)**:
  - Tab "Greasing" yang sebelumnya kosong kini menampilkan riwayat pelumasan unit hauling per tanggal.
  - Tab "Battery Monitoring" menampilkan status baterai terpasang dan tanggal peremajaan aki.
- 🖥️ **Menu Preventive Maintenance (`#menu-pm`)**:
  - Kalender servis PM menampilkan jadwal jatuh tempo unit RSV1 (`DZ-00004`, `EXC-00003`, `DT-00031`, dll).
  - Badge peringatan "Service Overdue" dan "Due Soon" terhitung dinamis dari selisih `last_hm_km` terhadap `next_service_due_hm_km`.
- 🖥️ **Menu Dashboard (`#menu-dashboard`)**:
  - Vital Cards menampilkan status kesehatan pelumasan dan baterai armada secara real-time.
- 📟 **Console Browser**:
  - Panggilan API `GET /api/grease_logs.php` dan `GET /api/battery_logs.php` mengembalikan payload HTTP 200 OK dengan JSON array valid.

---

### 📦 BATCH 4: Mutasi Pergudangan, Penerimaan & Pengeluaran Suku Cadang (BHW-IN/OUT)

#### 1. Tujuan & Sasaran Tabel
- Mengingest riwayat pergerakan gudang ke tabel `inventory_transactions`:
  - 210 transaksi Parts Masuk (`05_01`).
  - 355 transaksi Parts Keluar (`05_02`).
  - 9 transaksi Ban Masuk (`05_07`) & 16 Ban Keluar (`05_08`).
  - 6 transaksi Oli Masuk (`05_10`) & 33 Oli Keluar (`05_11`).
- Memperbarui saldo stok akhir (`stock_qty`) pada tabel `parts`.

#### 2. Langkah-Langkah Eksekusi (*Step-by-Step*)
1. Buat skrip `scripts/migrate_batch4_warehouse_transactions.php`.
2. Pastikan tabel `inventory_transactions` memiliki relasi ke `part_id`, `asset_id`, dan `wo_id`.
3. Ingest Transaksi Penerimaan (`IN`):
   - Type = `'IN'`, `reference_no` = nomor bukti BHW/Surat Jalan, `supplier` = vendor pengirim.
   - Tambahkan saldo `parts.stock_qty`.
4. Ingest Transaksi Pengeluaran (`OUT`):
   - Type = `'OUT'`, `reference_no` = nomor bukti pengeluaran BHW.
   - Tautkan ke nomor unit peminta (`asset_id`) dan nomor tiket WO jika ada.
   - Kurangi saldo `parts.stock_qty`.
5. Validasi konsistensi stok akhir: Nilai `parts.stock_qty` di database harus sama dengan kolom `Stok Akhir` pada file `05_03_Laporan_Stok_Penerimaan_dan_Pemakaian_Spare_Part.md`.

#### 3. Panduan Testing & Verifikasi (*Verification Guide*)
- **SQL Verification Query:**
  ```sql
  -- Total mutasi gudang yang masuk
  SELECT transaction_type, COUNT(*) as total_trans, SUM(quantity) as total_qty 
  FROM inventory_transactions GROUP BY transaction_type;
  
  -- Cek kecocokan stok akhir beberapa sampel part
  SELECT p.part_number, p.part_name, p.stock_qty 
  FROM parts p WHERE p.part_number IN ('1R-0739', '1R-0749', '600-319-5610', '23304-JAC70');
  ```
- **CLI Test:** Buat `tests/test_batch4_warehouse_flow.php` untuk memverifikasi alur transaksi tidak menyebabkan stok negatif pada part-part yang memiliki mutasi.

#### 4. Keterdampakan pada Menu, Konsol & Layar UI
- 🖥️ **Menu Spare Part & Logistik (`#menu-logistics`)**:
  - Tab "Buku Harian Warehouse (BHW)" terisi riwayat keluar-masuk barang yang dapat difilter per tanggal atau per unit.
  - Kartu indikator "Fast Moving Out of Stock" dan "Reorder Warning" aktif secara presisi.
- 🖥️ **Menu Work Order (`#menu-wo`)**:
  - Modal alokasi part WO (`#modalWoParts`) menampilkan riwayat pengeluaran part riil yang pernah dipasang pada unit tersebut.
- 🖥️ **Menu Approval (`#menu-approval`)**:
  - Permintaan SPB dapat memverifikasi ketersediaan fisik stok gudang secara otomatis (*auto-stock check*).
- 📟 **Console Browser**:
  - Grid tabel logistik merender riwayat pergerakan stok tanpa error formatting mata uang/desimal.

---

### 📦 BATCH 5: Ledger Biaya Operasional & Finansial Bulanan Equipment

#### 1. Tujuan & Sasaran Tabel
- Mengingest 37 transaksi perbaikan berbiaya kas dari `06_02_Laporan_Realisasi_Penggunaan_Dana.md` ke dalam `asset_cost_transactions`.
- Mengingest rekapitulasi pengeluaran kas bulanan divisi equipment Januari 2026 ke dalam `cost_financial_monthly`.

#### 2. Langkah-Langkah Eksekusi (*Step-by-Step*)
1. Buat skrip `scripts/migrate_batch5_cost_ledger.php`.
2. Baca file `06_02_Laporan_Realisasi_Penggunaan_Dana.md`:
   - Filter 37 unit yang memiliki nilai rupiah pada kolom `Januari 2026`.
   - Hubungkan kode unit (`SD 01`, `EXCA 01`, `DT 001`, dll) ke `asset_id` resmi.
   - Insert ke `asset_cost_transactions`:
     - `cost_code`: `COST-202601-xxxx`.
     - `asset_id`: ID unit terkait.
     - `transaction_date`: `'2026-01-31'`.
     - `cost_category`: `'Maintenance'` atau `'Sparepart'`.
     - `amount`: Nilai rupiah riil (misal Rp 265.000 untuk SD 01, Rp 10.631.000 untuk Alat Berat, dll).
     - `source_type`: `'MANUAL'`.
     - `description`: `'Realisasi Biaya Operasional Equipment Januari 2026'`.
3. Baca file `06_01_Ringkasan_Pengeluaran_Equipment_Duri_2026.md`:
   - Ingest total anggaran dan realisasi ke `cost_financial_monthly`:
     - Bulan/Tahun: Januari 2026.
     - Corrective Spareparts: Rp 38.608.000,00.
     - Preventive Maintenance & Ban: Rp 94.714.684,00.
     - SDM Mekanik: Rp 104.108.227,00.
     - Biaya Operasional (Silo, KIR, Aditional): Rp 442.845.253,00.
     - Total Realisasi Kas Keluar Januari 2026: **Rp 680.276.164,00**.

#### 3. Panduan Testing & Verifikasi (*Verification Guide*)
- **SQL Verification Query:**
  ```sql
  -- Total transaksi biaya unit
  SELECT COUNT(*) as total_trans, SUM(amount) as total_biaya FROM asset_cost_transactions WHERE transaction_date >= '2026-01-01';
  
  -- Rekapitulasi bulanan
  SELECT month_label, year_period, actual_amount FROM cost_financial_monthly WHERE month_label = 'Januari' AND year_period = 2026;
  ```
- **CLI Test:** Buat `tests/test_batch5_cost_ledger_audit.php` untuk memvalidasi bahwa total biaya di `asset_cost_transactions` konsisten dengan laporan ringkasan eksekutif.

#### 4. Keterdampakan pada Menu, Konsol & Layar UI
- 🖥️ **Menu Biaya (`#menu-biaya`)**:
  - Tabel Ledger Biaya Aset terisi 37 catatan transaksi dengan nominal dan tanggal riil.
  - Grafik breakdown biaya menampilkan perbandingan kategori (Corrective, Preventive, SDM, Operasional).
  - Kartu "Total Pengeluaran Bulan Ini" menampilkan angka aktual Rp 680.276.164,00 (bukan data dummy).
- 🖥️ **Menu Master Asset (`#menu-asset`)**:
  - Tab "Cost Analysis" pada modal detail aset menampilkan total biaya perawatan yang pernah dihabiskan unit tersebut.
- 🖥️ **Menu Dashboard (`#menu-dashboard`)**:
  - Executive Financial Summary card otomatis membaca data ledger terbaru.
- 📟 **Console Browser**:
  - Endpoint `GET /api/costs.php` dan `GET /api/asset_cost_transactions.php` merender data agregat tanpa latency tinggi.

---

### 📦 BATCH 6: Penugasan Kontrak Sewa, Alokasi Proyek & Matriks Jam Operasi

#### 1. Tujuan & Sasaran Tabel
- Mengingest 184 penugasan sewa proyek aktif dari `07_01_Identitas_dan_Ringkasan_Pendapatan_Sewa.md` ke dalam tabel `contract_assignments`.
- Mengingest data utilisasi harian dari `07_02_Pencatatan_Proyek_Harian_Sewa_Alat.md` (31 hari operasi kerja).
- Memperbarui kolom `project_name` dan `operational_position` pada tabel `assets`.

#### 2. Langkah-Langkah Eksekusi (*Step-by-Step*)
1. Buat skrip `scripts/migrate_batch6_rental_contracts.php`.
2. Baca file `07_01_Identitas_dan_Ringkasan_Pendapatan_Sewa.md`:
   - Ekstrak 184 unit yang memiliki alokasi omzet di proyek ADERA, RWI, PHR, PEP, atau WUR.
   - Hubungkan kode/serial number ke `assets.asset_id`.
   - Tentukan proyek dominan per unit berdasarkan alokasi omzet terbesar.
   - Insert ke `contract_assignments`:
     - `contract_number`: `KTR-202601-` + kode proyek + `-` + kode unit.
     - `project`: Nama proyek (ADERA / RWI / PHR / PEP / WUR).
     - `site`: Lokasi kerja Duri / Bropit / Minas.
     - `start_date`: `'2026-01-01'`.
     - `end_date`: `'2026-01-31'`.
     - `target_hours`: `Total Hari Kerja x 8 jam`.
3. Update atribut master aset (`assets`):
   - `project_name` = Proyek aktif dari kontrak sewa.
   - `status`: Jika unit bekerja $\ge 1$ hari, set status = `'OPERATING'`. Jika hari kerja = 0 dan ada di Yard, set status = `'STANDBY'`.
4. Ingest ringkasan utilisasi bulanan ke tabel performa produktivitas.

#### 3. Panduan Testing & Verifikasi (*Verification Guide*)
- **SQL Verification Query:**
  ```sql
  -- Distribusi unit per proyek sewa
  SELECT project, COUNT(*) as total_unit, SUM(target_hours) as total_jam_operasi 
  FROM contract_assignments 
  GROUP BY project;
  
  -- Sinkronisasi status master asset
  SELECT status, project_name, COUNT(*) 
  FROM assets 
  GROUP BY status, project_name;
  ```
- **CLI Test:** Buat `tests/test_batch6_contracts_and_utilization.php` untuk memvalidasi bahwa seluruh unit yang membukukan sewa memiliki status proyek yang valid di database.

#### 4. Keterdampakan pada Menu, Konsol & Layar UI
- 🖥️ **Menu Monitoring Unit (`#menu-monitoring`)**:
  - Filter lokasi proyek (ADERA, RWI, PHR, PEP, WUR) menampilkan unit-unit riil yang sedang ditugaskan.
- 🖥️ **Menu Produktivitas (`#menu-productivity`)**:
  - Rasio utilisasi armada (*Fleet Utilization Rate*) terhitung akurat dari jam kerja sewa riil (basis 31 hari).
  - Grafik produktivitas per jenis alat (Dump Truck vs Excavator vs Dozer) menampilkan tren riil.
- 🖥️ **Menu Master Asset (`#menu-asset`)**:
  - Badge proyek pada kartu aset berubah sesuai kontrak sewa aktif (misal tag: `ADERA`, `PHR`).
- 📟 **Console Browser**:
  - Respon filter proyek di dashboard memuat data secara instan tanpa missing reference.

---

## 5. Matriks Pengujian & Verifikasi Menyeluruh (*End-to-End Test Suite*)

Setelah seluruh batch selesai dieksekusi, jalankan suite pengujian komprehensif berikut untuk memastikan *zero regression*:

```bash
# 1. Verifikasi integritas skema dan kueri database
php tests/test_batch1_identity_and_parts.php
php tests/test_batch2_work_orders_enrichment.php
php tests/test_batch3_pm_monitoring.php
php tests/test_batch4_warehouse_flow.php
php tests/test_batch5_cost_ledger_audit.php
php tests/test_batch6_contracts_and_utilization.php

# 2. Verifikasi seluruh checkpoint backend dan flow transaksi
php tests/f4_checkpoint_verification.php
php tests/f4_people_kpi_integration.php
php tests/test_archive_notification_flow.php
php tests/verify_http_dashboard.php

# 3. Ekspor master dump database terbaru & sinkronisasi Laragon
mysqldump -u root -p u646470441_ServicePlanBRA > scripts/u646470441_ServicePlanBRA.sql
powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
```

---

## 6. Protokol Perlindungan Data (*Rollback & Safety Protocol*)

1. **Backup Pra-Migrasi**:
   - Sebelum menjalankan setiap script migrasi batch, buat cadangan snapshot database dengan timestamp spesifik (misal: `backup_pre_batch1_20261024.sql`).
2. **ACID Transaction Locking**:
   - Setiap script migrasi wajib dibungkus dalam blok `try { $db->beginTransaction(); ... $db->commit(); } catch { $db->rollBack(); }`.
3. **Idempotensi Eksekusi**:
   - Script migrasi menggunakan klausul `ON DUPLICATE KEY UPDATE` atau pengecekan keberadaan data (`IF NOT EXISTS`) sehingga aman dijalankan berulang kali tanpa membuat data ganda.
4. **Preservasi Database Laragon**:
   - Skrip `scripts/sync_to_laragon.ps1` selalu dijalankan dengan parameter `-PreserveDatabase` agar database lokal Laragon tidak ter-drop secara tidak sengaja.
