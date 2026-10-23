# Rencana Kerja: Penuntasan Logika & Integrasi Antarfungsi ServicePlan-BRA

**Tanggal Dokumen:** 23 Oktober 2026  
**Status:** Dokumen Kerja Resmi (Actionable Implementation Checklist)  
**Tujuan:** Menyelesaikan seluruh logika yang belum benar dan menghubungkan seluruh modul agar terintegrasi secara utuh (*end-to-end*) antar-fungsi.  
**Target Database:** `u646470441_ServicePlanBRA` (MySQL Laragon)  
**Dokumen Rujukan Audit:** `GRAND_PLAN_FINISHING_BACKEND_20261022.md` & Audit Logika 23 Oktober 2026  

---

## 1. Prinsip Utama Eksekusi

1. **Database Server adalah Satu-Satunya Sumber Kebenaran (*Single Source of Truth*):**
   - Tidak ada lagi perubahan status operasional (Aset, WO, Stok) yang hanya hidup di variabel JavaScript atau `localStorage`.
   - Seluruh mutasi data wajib berhasil tersimpan di tabel MySQL (`assets`, `work_orders`, `purchase_requests`, `parts`, `inspections`).
2. **Transaksi ACID yang Terkunci (*Locked & Idempotent*):**
   - Setiap operasi yang mengubah multi-tabel (misal: WO selesai $\rightarrow$ Asset status $\rightarrow$ Service history) wajib berada di dalam blok `PDO::beginTransaction()` dan `commit()` dengan baris `FOR UPDATE`.
3. **Fail-Closed & Konsisten Lintas Pengguna:**
   - Ketika halaman di-refresh (F5) atau dibuka di browser/perangkat berbeda, status, lokasi, dan hitungan kartu dashboard harus 100% identik.
4. **Pengerjaan Irisan Vertikal Bertahap (*Vertical Slices*):**
   - Setiap batch diselesaikan dari lapis Database $\rightarrow$ Backend Core/API $\rightarrow$ Frontend UI $\rightarrow$ Uji Otomatis & Uji Browser sebelum melangkah ke batch berikutnya.

---

## 2. Roadmap & Checklist Rinci Pengerjaan

```
+---------------------------------------------------------------------------------------------------+
| BATCH 1: Work Order <-> Status Aset (Penutupan WO mengembalikan status unit READY di DB)         |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| BATCH 2: Laporan P2H Gagal/Kritis <-> Pembuatan Otomatis Tiket Work Order di Kanban              |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| BATCH 3: Preventive Maintenance (PM) <-> Master Asset (Update interval HM saat PM selesai)       |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| BATCH 4: Approval SPB <-> Alokasi & Pengeluaran Stok Fisik Gudang <-> Auto-Resume WO             |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| BATCH 5: Dinamisasi Periode Lembur SPL & Evaluasi People & KPI                                    |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| BATCH 6: Penegakan Filter Sistem Arsip (Archive) di Seluruh Kueri API Backend                    |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| BATCH 7: Pembersihan Glitch Frontend & Verifikasi Regresi Menyeluruh                             |
+---------------------------------------------------------------------------------------------------+
```

---

### BATCH 1 (P0): Rekonsiliasi Siklus Work Order $\leftrightarrow$ Status Aset di Database

**Masalah Saat Ini:** Saat WO ditutup (`Closed`), `api/work_orders.php` tidak memperbarui tabel `assets` karena payload tidak menyertakan key meter/downtime. Status `READY` hanya terjadi di JavaScript lokal dan kembali `BREAKDOWN` saat halaman di-refresh.

- [x] **1.1 Backend (`api/work_orders.php` - Method PUT):**
  - Pada blok penanganan `$newStatus === 'Closed'` (sekitar baris 617–665):
    - Cek apakah unit masih memiliki Work Order aktif lain (`status IN ('Open', 'In Progress', 'Waiting Part', 'Testing') AND wo_id != :id`).
    - Jika **TIDAK ADA** WO aktif lain, lakukan query `UPDATE assets SET status = 'READY' WHERE asset_id = :asset_id`.
    - Jika **MASIH ADA** WO aktif lain, pertahankan status aset sesuai prioritas WO tertinggi yang tersisa (misal `BREAKDOWN` jika ada WO Open downtime, atau `STANDBY`).
    - Catat transisi status aset ke timeline Work Order (`wo_insert_timeline`).
- [x] **1.2 Frontend (`dashboard.view.php` & `dashboard.html`):**
  - Pada fungsi `submitWoStatusChange()` (sekitar baris 6013–6063):
    - Pastikan payload `PUT api/work_orders.php` menyertakan `target_unit_status: unitStatus` sebagai parameter eksplisit.
    - Perbaiki pemanggilan `window.syncFleetState()` agar menyertakan parameter objek yang valid:
      ```javascript
      window.syncFleetState({ assetId: assetId, workOrderId: woId });
      ```
    - Tangani respons error jika server menolak pembaruan status aset.
- [x] **1.3 Verifikasi Batch 1:**
  - Ambil unit uji dengan status `BREAKDOWN` dan WO aktif.
  - Jalankan penutupan WO melalui tombol konfirmasi di Kanban/Tabel.
  - Refresh browser (F5) dan cek query langsung ke database:
    ```sql
    SELECT asset_id, status FROM assets WHERE asset_id = 'UNIT_UJI';
    SELECT wo_id, status FROM work_orders WHERE wo_id = 'WO_UJI';
    ```
  - **Kriteria Lulus:** Baik di browser maupun di tabel `assets` MySQL, status unit terbukti berubah menjadi `READY`. *(Terverifikasi via `tests/test_batch1_wo_asset_status.php`)*

---

### BATCH 2 (P1): Laporan P2H Kritis $\leftrightarrow$ Pembuatan Otomatis Tiket Work Order

**Masalah Saat Ini:** Laporan P2H yang berstatus `FAIL` mengubah aset menjadi `BREAKDOWN` di database, tetapi tidak menerbitkan tiket pekerjaan Work Order sehingga tidak muncul di antrean penanganan mekanik.

- [x] **2.1 Backend (`core/ReportIntegration.php`):**
  - Pada fungsi integrasi P2H (`applyP2hInspection`, sekitar baris 3580–3670):
    - Jika `$overallResult === 'FAIL'`, di dalam transaksi yang sama:
      - Generate ID Work Order baru: `WO-{yy}{mm}-{rand4}` (misal: `WO-2610-8921`).
      - Query `INSERT INTO work_orders` dengan:
        - `asset_id`: `$asset['asset_id']`
        - `issue_description`: `"Temuan Kritis P2H ({$reportNumber}): " . $summary`
        - `status`: `'Open'`
        - `priority`: `'High'` (atau `'Emergency'` jika ada kegagalan rem/kemudi)
        - `maintenance_type`: `'Corrective'`
        - `is_downtime`: `1`
        - `raw_location`: Lokasi unit saat inspeksi
        - `reported_at`: Waktu inspeksi
      - Catat timeline pembuatan WO: `"Work Order otomatis diterbitkan dari hasil inspeksi P2H {$reportNumber}"`.
      - Tautkan ID WO baru ke tabel integrasi laporan (`report_wo_integrations` atau `report_inspection_integrations`).
- [x] **2.2 Integrasi Temuan Bahaya Condition Monitoring (`core/ConditionMonitoring.php`):**
  - Pada fungsi `saveTire`, `saveGrease`, `saveBattery`:
    - Jika `$status === 'danger'`, perbarui `assets.status = 'INSPEKSI'` jika status saat ini bukan `BREAKDOWN`.
- [x] **2.3 Verifikasi Batch 2:**
  - Simpan dan finalisasi 1 formulir P2H dengan item kritis tidak lulus (`FAIL`).
  - Periksa menu Work Order Kanban: harus langsung muncul 1 kartu WO baru berstatus `Open` pada kolom Open.
  - Lakukan void pada laporan P2H uji: pastikan tiket WO otomatis yang terkait dibatalkan/di-void secara idempoten. *(Terverifikasi via `tests/test_batch2_p2h_wo_generation.php`)*

---

### BATCH 3 (P1): Preventive Maintenance (PM) $\leftrightarrow$ Master Asset $\leftrightarrow$ Work Order

**Masalah Saat Ini:** WO jenis Preventive Maintenance yang diselesaikan tidak memperbarui meter servis terakhir di Master Asset, dan perpindahan ke tab PM tidak memicu refresh data terbaru.

- [x] **3.1 Backend (`api/work_orders.php` & `core/WorkOrderSupport.php`):**
  - Saat WO ditutup (`status = 'Closed'`) dan `maintenance_type === 'Preventive'`:
    - Ambil data aset dan parameter servisnya (`service_interval_val`, `daily_usage_coefficient`).
    - Jalankan fungsi realisasi PM:
      - Update `assets.last_service_date = NOW()`
      - Update `assets.last_service_hm_km = :current_hm_km`
      - Hitung ulang `next_service_due_hm_km = last_service_hm_km + service_interval_val`
      - Hitung estimasi tanggal jatuh tempo berikutnya berdasarkan laju pemakaian harian (`daily_usage_coefficient`).
      - Simpan log ke tabel `asset_service_history` dengan referensi nomor WO.
      - Update status pada tabel `pm_plans` terkait menjadi `'COMPLETED'`.
- [x] **3.2 Frontend (`dashboard.view.php` - Fungsi `showView`):**
  - Tambahkan penanganan navigasi untuk tab PM:
    ```javascript
    if (viewName === 'pm' && window.PreventiveMaintenance?.refresh) {
        window.PreventiveMaintenance.refresh();
    }
    ```
- [x] **3.3 Verifikasi Batch 3:**
  - Buat WO tipe `Preventive` pada unit yang mendekati jadwal servis.
  - Tutup WO tersebut dengan memasukkan HM aktual.
  - Buka tab Master Asset & tab Preventive Maintenance: `next_service_due_hm_km` harus bertambah sesuai interval dan status jadwal kembali hijau (`PLANNED`). *(Terverifikasi via `tests/test_batch3_pm_wo_integration.php`)*

---

### BATCH 4 (P1): Approval SPB $\leftrightarrow$ Alokasi & Pengeluaran Stok Gudang $\leftrightarrow$ Auto-Resume WO

**Masalah Saat Ini:** Approval SPB hanya mengubah teks status menjadi `Approved` tanpa memeriksa ketersediaan suku cadang di gudang, dan suku cadang yang telah siap belum otomatis memindahkan status WO dari `Waiting Part` ke `In Progress`.

- [x] **4.1 Backend (`core/SpbWorkflow.php` & `api/spb_workflow.php`):**
  - Buat aksi baru: `issue_parts` (Pengeluaran Barang dari SPB Disetujui):
    - Input: `spb_id`, `items: [{ part_id, qty_issued }]`.
    - Validasi: Saldo fisik di tabel `parts.stock_qty >= qty_issued`.
    - Di dalam transaksi PDO:
      - Kurangi saldo: `UPDATE parts SET stock_qty = stock_qty - :qty WHERE part_id = :part_id`.
      - Catat histori transaksi: `INSERT INTO inventory_transactions` (Movement: `'OUT'`, Referensi: nomor SPB dan nomor WO).
      - Perbarui status SPB item menjadi `'Issued'`.
      - Jika seluruh item pada SPB telah berstatus `'Issued'`, perbarui status header SPB menjadi `'Issued'`.
- [x] **4.2 Otomatisasi Status Work Order (`core/WorkOrderSupport.php`):**
  - Setelah SPB berstatus `'Issued'`, sistem otomatis mengeksekusi `WorkOrderSupport::checkAndResumeWorkOrder($db, $woId, $actorId, $actorName)`.
  - Jika tidak ada lagi SPB gantung untuk WO tersebut, status WO otomatis beralih dari `Waiting Part` ke `In Progress` disertai catatan timeline dan notifikasi.
- [x] **4.3 Verifikasi Batch 4:**
  - Buat WO dengan kebutuhan part $\rightarrow$ status WO menjadi `Waiting Part`.
  - Approve SPB dan lakukan aksi pengeluaran barang.
  - Cek tabel `parts`: stok berkurang secara akurat.
  - Cek WO: status WO otomatis berpindah kembali ke `In Progress` tanpa perlu dipindahkan manual oleh mekanik. *(Terverifikasi via `tests/test_batch4_spb_issue_auto_resume.php`)*

---

### BATCH 5 (P2): Dinamisasi Periode Lembur SPL & Evaluasi People & KPI

**Masalah Saat Ini:** Frontend People & KPI memanggil endpoint `api/people_kpi.php?action=overtime_leaderboard&period=2026-01` secara hardcoded, sehingga data lembur tidak merespons perubahan filter bulan.

- [x] **5.1 Frontend (`scripts/dashboard.js`):**
  - Pada fungsi `renderOvertimeAttendance()` (sekitar baris 23223):
    - Ambil nilai periode dari elemen filter aktif:
      ```javascript
      const activePeriod = document.getElementById('selKpiPeriod')?.value 
                        || document.getElementById('selAttendancePeriod')?.value 
                        || new Date().toISOString().slice(0, 7);
      const resp = await fetch(`api/people_kpi.php?action=overtime_leaderboard&period=${encodeURIComponent(activePeriod)}`, { cache: 'no-store' });
      ```
    - Tambahkan event listener `change` pada dropdown filter periode agar memicu re-render tabel lembur dan leaderboard.
- [x] **5.2 Sinkronisasi Evaluasi Planner & Head KPI:**
  - Hubungkan nilai indikator pencapaian (misal persentase ketaatan PM dan rata-rata downtime) dengan hasil kalkulasi riil dari tabel `work_orders` dan `assets` pada periode terpilih.
- [x] **5.3 Verifikasi Batch 5:**
  - Pilih periode yang memiliki dokumen SPL (misal `2026-01`): data lembur muncul.
  - Pilih periode lain yang belum ada data (misal `2026-05`): muncul pesan informatif *"Belum Ada Dokumen Lembur / SPL Terverifikasi pada periode 2026-05"*. *(Terverifikasi via `tests/test_batch5_people_kpi_period.php`)*

---

### BATCH 6 (P2): Penegakan Filter Sistem Arsip (Archive) di Seluruh Kueri Backend

**Masalah Saat Ini:** Sistem arsip hanya memfilter tampilan di browser via JavaScript. Jika API arsip gagal diakses, data arsip langsung muncul kembali. Selain itu, kueri DDL `CREATE TABLE` dieksekusi pada setiap request GET.

- [x] **6.1 Pembersihan DDL & Migrasi Database (`api/archive.php`):**
  - Hapus blok `CREATE TABLE IF NOT EXISTS archived_items` dari baris 41–51 file `api/archive.php`.
  - Pastikan skema tabel `archived_items` tercantum permanen di file migrasi `scripts/u646470441_ServicePlanBRA.sql`.
  - Tambahkan kolom audit pada `archived_items`: `archived_by (INT)`, `reason (VARCHAR(255))`.
- [x] **6.2 Penegakan Kueri Backend (*Fail-Closed*):**
  - Perbarui kueri aset di [`api/init.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/init.php) dan [`api/assets.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/assets.php):
    ```sql
    AND NOT EXISTS (
        SELECT 1 FROM archived_items arc 
        WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
    )
    ```
  - Perbarui kueri pada [`api/inspections.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/inspections.php) dan [`api/accidents.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/accidents.php) agar mengecualikan item yang telah diarsipkan.
- [x] **6.3 Verifikasi Batch 6:**
  - Arsipkan 1 unit aset uji.
  - Akses `api/init.php` dan `api/assets.php` via curl/Postman/script: unit yang diarsip **sama sekali tidak boleh muncul dalam payload respons**.
  - Buka tab Arsip: unit uji muncul di tabel arsip dengan opsi Pulihkan (*Restore*). *(Terverifikasi via `tests/test_batch6_archive_fail_closed.php`)*

---

### BATCH 7 (P2): Pembersihan Glitch Frontend & Verifikasi Regresi Menyeluruh

- [x] **7.1 Hapus Redefinisi Duplikat Fungsi `refreshAllViews()`:**
  - Pada [`dashboard.view.php` baris 9881–9887](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/dashboard.view.php#L9881-L9887), hapus deklarasi kedua `function refreshAllViews()` yang menimpa implementasi utama di baris 7102.
  - Samakan perbaikan pada file `dashboard.html`.
- [x] **7.2 Sinkronisasi Webroot Laragon:**
  - Jalankan script sinkronisasi tanpa merusak data operasional:
    ```powershell
    powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
    ```
- [x] **7.3 Pengujian Regresi Lintas Modul:**
  - Jalankan seluruh tes unit & integrasi:
    ```powershell
    & 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' tests/f0_user_authorization.php
    & 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' tests/f1_targeted_sync_policy.php
    & 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' tests/work_order_support_integration.php
    & 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' tests/spb_workflow_rollback.php
    ```

---

## 3. Matriks Bukti Kesiapan Rilis (*Release Sign-off Matrix*)

| No. | Skenario Alur Terintegrasi | Bukti Minimum yang Wajib Terpenuhi | Status |
| :---: | :--- | :--- | :---: |
| 1 | **Tutup WO $\rightarrow$ Status Unit READY** | Nilai `status` di tabel `assets` berubah menjadi `READY` setelah WO ditutup, dan tetap `READY` setelah logout/login ulang atau refresh browser. | [x] PASSED |
| 2 | **P2H FAIL $\rightarrow$ Terbit Tiket WO** | Dokumen P2H kritis menghasilkan tiket WO baru di kolom Open Kanban dengan tautan nomor referensi dokumen. | [x] PASSED |
| 3 | **Tutup WO PM $\rightarrow$ Update Interval HM** | HM servis terakhir dan target servis berikutnya di Master Asset bertambah sesuai interval pabrikan. | [x] PASSED |
| 4 | **Issue Part SPB $\rightarrow$ Potong Stok & Resume WO** | Saldo stok suku cadang berkurang di `parts`, mutasi tercatat di `inventory_transactions`, dan WO status `Waiting Part` beralih ke `In Progress`. | [x] PASSED |
| 5 | **Dinamisasi Lembur People & KPI** | Pergantian bulan pada filter secara dinamis memuat rekap SPL yang cocok tanpa data statis Januari 2026. | [x] PASSED |
| 6 | **Isolasi Arsip di Database** | Item yang diarsipkan tidak terkirim di JSON API `init.php` / `assets.php` sehingga aman dari manipulasi sisi klien. | [x] PASSED |
| 7 | **Sinkronisasi Laragon Bersih** | Seluruh perubahan terpasang di `C:\laragon\www\ServicePlan-BRA` dengan kode status HTTP 200 dan konsol bebas dari Javascript error. | [x] PASSED |

---
*Seluruh batch (Batch 1 s/d Batch 7) dan Release Sign-off Matrix telah selesai diimplementasikan, diuji melalui suite pengujian otomatis, dan disinkronkan ke Laragon.*

