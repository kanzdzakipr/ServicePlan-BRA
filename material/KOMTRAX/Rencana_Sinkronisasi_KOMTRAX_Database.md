# Assessment & Rencana Pengembangan Sinkronisasi Database KOMTRAX (Juli, Agustus, September 2026)

Dokumen ini memuat hasil audit teknis terhadap 3 dataset baru pada folder `material/KOMTRAX/`:
1. `Komtrax_Periode_Juli_2026.md` (`01/07/2026 - 31/07/2026`)
2. `Komtrax_Periode_Agustus_2026.md` (`01/08/2026 - 31/08/2026`)
3. `Komtrax_Periode_September_2026.md` (`01/09/2026 - 30/09/2026`)

serta rencana injeksi dan sinkronisasi ke database MySQL (`u646470441_ServicePlanBRA`) yang berdampak pada **Menu Produktivitas** dan **3 menu lainnya** (**Master Asset**, **360° Unit Properties**, dan **Preventive Maintenance**).

---

## 1. Kondisi Saat Ini di Database (`telematics_logs`)

Saat ini tabel `telematics_logs` di database **hanya menyimpan 1 periode**, yaitu **`Januari 2026`** sebanyak **18 baris** (`log_id` 22 s/d 39):

| Periode di `telematics_logs` | Jumlah Unit | Total Working Hours (h) | Total Actual Working Hours (h) | Total Fuel Consumed (L) | Status di DB |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **`Januari 2026`** | 18 Unit | `1.976,4` | `854,9` | `15.111,8` | **Sudah Ada (18 baris)** |
| **`Juli 2026`** | 18 Unit | `2.339,6` | `1.099,2` | `17.771,5` | **Belum Ada (0 baris)** |
| **`Agustus 2026`** | 18 Unit | `2.051,7` | `943,6` | `15.276,1` | **Belum Ada (0 baris)** |
| **`September 2026`** | 18 Unit | `2.087,3` | `920,3` | `15.628,7` | **Belum Ada (0 baris)** |

### Kesiapan Skema Tabel `telematics_logs`
Seluruh 16 metrik pada file Markdown (`serial_number`, `model_type`, `sub_group`, `smr_hours`, `working_days`, `working_hours`, `actual_working_hours`, `actual_working_ratio`, `e_mode_ratio`, `travel_ratio`, `digging_ratio`, `hoist_ratio`, `fuel_consumed_liters`, `fuel_lph`, `idling_ratio`, `last_comm_date`, `period_month`) **100% kompatibel** dengan kolom tabel `telematics_logs` tanpa perlu mengubah tipe data kolom yang sudah ada.
- **Rekomendasi Skema**: Menambahkan `UNIQUE KEY uq_telematics_sn_period (serial_number, period_month)` agar skrip migrasi bersifat *idempotent* (`INSERT ... ON DUPLICATE KEY UPDATE`) dan mencegah duplikasi baris jika dijalankan berulang kali.

---

## 2. Audit Pemetaan 18 `serial_number` ↔ `asset_id` & Temuan Rekonsiliasi Lintas Modul

Ke-18 Serial Number pada laporan KOMTRAX Juli, Agustus, dan September 2026 **identik** dengan 18 unit pada periode Januari 2026. Namun, hasil *cross-check* antara `telematics_logs`, tabel `assets`, dan **Laporan PM Service (September & Oktober 2026)** menemukan beberapa ketidaksinkronan pemetaan yang perlu diluruskan saat injeksi:

| No | Model | Serial# (KOMTRAX) | SMR Jan 26 | SMR Jul 26 | SMR Agu 26 | SMR Sep 26 | Mapping `asset_id` Saat Ini (`telematics_logs`) | Temuan Cross-Check Master Asset & Laporan PM |
| :-: | :--- | :--- | :---: | :---: | :---: | :---: | :--- | :--- |
| 1 | `D85ESS-2` | `J21020` | 390.3 | 390.3 | 390.3 | 390.3 | `DZ-00001` (DOZER D85ESS-2) | Sesuai (`DZ-00001`). Standby 0 jam kerja s/d Sep 2026. |
| 2 | `GD535-5` | `2066` | 3791.8 | 4695.4 | 4858.7 | 4919.3 | `MG-00005` (GD 535-5 / GR 07) | Di `assets`, SN `2066` juga tercatat pada `MG-00003` (GR 7). Di Laporan PM Okt 2026 tercatat HM `4995` pada 15-Okt-2026. |
| 3 | `GD535-5` | `2259` | 1666.5 | 2794.7 | 2997.1 | 3195.3 | `MG-00002` (GD 535-5 (GR4)) | Sesuai (`MG-00002`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `3195.3`. |
| 4 | `PC195LC-8` | `J10309` | 4098.9 | 4135.7 | 4135.8 | 4136.3 | `EXC-00021` (PC195LC-8) | Sesuai (`EXC-00021`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `4136.3`. |
| 5 | `PC200-10M0` | `C51502` | 6666.0 | 7606.8 | 7735.1 | 7866.6 | `EXC-00012` | **Perlu Perhatian**: Di `assets` & Laporan PM, `C51502` adalah **Exca 20 (`EXC-00002`)** (HM 20-Okt-2026: `7948.0`), sedangkan `EXC-00012` (Exca BRA-23) ber-SN `DBCH1801`. |
| 6 | `PC200-10M0` | `C51503` | 5709.6 | 6569.4 | 6711.2 | 6829.9 | `EXC-00009` | **Perlu Perhatian**: Di `assets` & Laporan PM Sep 2026, `C51503` adalah **Exca 21 (`EXC-00003`)** (HM 09-Sep-2026: `6766.2`, persis di antara Agu `6711.2` dan Sep `6829.9`). |
| 7 | `PC200-10M0` | `DBCH0302` | 8865.6 | 9603.1 | 9687.2 | 9879.3 | `EXC-00007` | Di Laporan PM Okt 2026, `DBCH0302` adalah **Exca 07 (`EXC-1114`)** dengan HM `9880.3` pada 01-Okt-2026 (selisih 1 jam dari KOMTRAX 30-Sep `9879.3`). |
| 8 | `PC200-10M0` | `DBCH0366` | 6553.0 | 7351.7 | 7511.8 | 7683.9 | `EXC-00010` (Exca 13) | Sesuai (`EXC-00010`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `7683.9`. |
| 9 | `PC200-10M0` | `DBCH0380` | 17568.2 | 18481.9 | 18668.2 | 18840.4 | `EXC-00008` (Exca 12) | Sesuai (`EXC-00008`). Di `assets`, `last_hm_km` `18559.0` → dapat di-update ke `18840.4`. |
| 10 | `PC200-10M0` | `DBCH1784` | 5386.6 | 5694.2 | 5697.1 | 5828.0 | `EXC-41001` (EXCA-22) | Sesuai (`EXC-41001`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `5828.0`. |
| 11 | `PC200-10M0` | `DBCH1801` | 5233.6 | 6154.7 | 6324.9 | 6522.9 | `EXC-00022` | Di Laporan PM Okt 2026, `DBCH1801` adalah **Exca BRA-23 (`EXC-00012`)** (HM Last Service 31-Jul-2026: `6154.7` — **100% sama dengan SMR KOMTRAX Juli 2026!**). |
| 12 | `PC200-10M0` | `DBCH2244` | 1699.4 | 2475.4 | 2625.9 | 2768.9 | `EXC-00024` (Exca 27) | Sesuai (`EXC-00024`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `2768.9`. |
| 13 | `PC200-10M0` | `DBCH2245` | 1630.5 | 2437.6 | 2584.6 | 2707.6 | `EXC-00002` | Di `assets`, `DBCH2245` adalah **Exca 28 (`EXC-00006` / `EXC-00025`)**. |
| 14 | `PC200-10M0` | `DBCH2941` | 917.2 | 1570.9 | 1695.1 | 1801.3 | `EXC-00015` | Di Laporan PM Okt 2026, `DBCH2941` adalah **EXCA-29 (`EXC-00009` / `EXC-00026`)** (HM 27-Okt-2026: `1859.0`). |
| 15 | `PC210-10M0` | `C01530` | 12383.0 | 4931.3 | 4932.1 | 4980.6 | `EXC-00013` (Exca 08) | Sesuai (`EXC-00013`). Unik: di Jul–Sep 2026 tercatat `actual_working_hours = 0.0` (`idling_ratio = 100%`). |
| 16 | `PC210-10M0` | `C01531` | 8839.6 | 8839.6 | 8839.6 | 8839.6 | `EXC-00014` (Exca 09) | Sesuai (`EXC-00014`). Komunikasi terakhir satelit putus sejak `20/07/2025` (`working_hours = 0.0`). |
| 17 | `PC210-10M0` | `C07004` | 1699.4 | 2641.2 | 2848.1 | 3032.6 | `EXC-00027` (Exca 25) | Sesuai (`EXC-00027`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `3032.6`. |
| 18 | `PC210-10M0` | `C07076` | 1609.6 | 2603.7 | 2806.4 | 2991.0 | `EXC-00028` (Exca 26) | Sesuai (`EXC-00028`). Di `assets`, `last_hm_km` masih `0.00` → perlu di-update ke `2991.0`. |

---

## 3. Dampak & Rencana Pengembangan pada Menu Produktivitas (`#productivityModule`)

Menu Produktivitas mengambil data dari dua endpoint utama: **`api/productivity.php`** dan **`api/kpi.php`** (melalui `core/ProductivityCalculator.php` dan `core/KpiCalculator.php`). Berikut dampak langsung dan pengembangan UI yang direkomendasikan:

### A. Panel "Periksa KPI" (`#kpi-inspector-panel` & `api/kpi.php`)
- **Kondisi Saat Ini**: Dropdown `#kpi-inspector-period` memilih bulan dengan format `YYYY-MM` (contoh: `2026-07`, `2026-08`, `2026-09`) yang dikonversi oleh `KpiCalculator::formatPeriodMonth()` menjadi `'Juli 2026'`, `'Agustus 2026'`, dan `'September 2026'`. Karena sebelumnya hanya ada `'Januari 2026'`, memilih Juli/Agustus/September menghasilkan peringatan `"Total scheduled hours (T) nol"`.
- **Dampak Setelah Injeksi DB**: Begitu 54 baris (`Juli 2026`, `Agustus 2026`, `September 2026`) disuntikkan ke `telematics_logs`, fitur **Periksa KPI** langsung berfungsi penuh untuk ketiga bulan tersebut (baik *Semua Aset (Fleet)* maupun *Per Unit*), menghitung otomatis:
  - **Juli 2026**: $W = 2.339,6\text{ jam}$, Actual Working $= 1.099,2\text{ jam}$, dikombinasikan dengan jam PM (`S`) & Breakdown (`R`) bulan Juli 2026.
  - **Agustus 2026**: $W = 2.051,7\text{ jam}$, Actual Working $= 943,6\text{ jam}$, dikombinasikan dengan jam PM (`S`) & Breakdown (`R`) bulan Agustus 2026.
  - **September 2026**: $W = 2.087,3\text{ jam}$, Actual Working $= 920,3\text{ jam}$, dikombinasikan dengan jam PM (`S`) & Breakdown (`R`) bulan September 2026 (termasuk data PM Close September 2026 yang baru diimpor!).

### B. Penambahan Filter Periode KOMTRAX pada Header Produktivitas (`dashboard.view.php` & `scripts/dashboard.js`)
- **Kondisi Saat Ini**:
  - Fungsi `loadProductivityData()` di `scripts/dashboard.js` memanggil `api/productivity.php` tanpa parameter `?period=...`, sehingga backend `api/productivity.php` selalu menggunakan default `$period = 'Januari 2026'`.
  - Banner di Tab 2 masih bertuliskan statis: `"Data Telematika KOMTRAX (Periode Januari 2026)"`.
- **Rencana Pengembangan**:
  1. Menambahkan **Dropdown Filter Periode Telematika** (`September 2026 (Terbaru)`, `Agustus 2026`, `Juli 2026`, `Januari 2026`, serta opsi `Kumulatif / Semua Periode`) di bagian atas modul Produktivitas.
  2. Mengubah `loadProductivityData(period)` agar mengirim `api/productivity.php?period=<periode>` dan memperbarui teks badge periode secara dinamis.
  3. **Catatan Penting (Sesuai Instruksi Sebelumnya)**: Kartu utama **Physical Availability (PA)** di KPI Produktivitas tetap dikunci pada **`57,13%`** dan teks rumus di **Periksa KPI** tetap menggunakan rumus standar yang telah ditetapkan.

### C. Tab 2: Rekonsiliasi Telematika (KOMTRAX) vs LHO (`#prodTabKomtrax`)
- Tabel **`#tbKomtraxTable`** (Matriks 18 Unit) akan menampilkan progres SMR, Working Hours, Actual Working Hours, Digging/Travel/Hoist Ratio, dan L/H secara akurat untuk **Juli, Agustus, dan September 2026**.
- Tabel **`#tbReconciliationTable`** (Rekonsiliasi LHO vs KOMTRAX) akan membandingkan `equipment_usage` (LHO) pada bulan Juli (`2026-07`), Agustus (`2026-08`), dan September (`2026-09`) terhadap `working_hours` KOMTRAX pada bulan yang bersangkutan.

### D. Tab 3: Idling Anomaly & Fuel Loss (`#prodTabAnomaly`)
Injeksi data Juli–September 2026 mengungkap **temuan anomali idling baru** (`idling_ratio > 50%`) yang otomatis akan muncul di tabel `#tbIdlingAnomalyBody`:
- **Juli 2026 (6 Unit Anomali > 50%)**:
  - `C01530` (`PC210-10M0`): Working `62,2h`, Actual `0,0h`, **Idling `100%`** (Fuel `151,5 L`)
  - `J10309` (`PC195LC-8`): Working `15,6h`, Actual `5,7h`, **Idling `63%`** (Fuel `126,5 L`)
  - `DBCH2245` (`PC200-10M0`): Working `160,6h`, Actual `65,2h`, **Idling `59%`** (Fuel `982,8 L`)
  - `2259` (`GD535-5`): Working `189,0h`, Actual `82,1h`, **Idling `57%`** (Fuel `1.448,3 L`)
  - `C07004` (`PC210-10M0`): Working `168,8h`, Actual `76,0h`, **Idling `55%`** (Fuel `1.298,9 L`)
  - `C51503` (`PC200-10M0`): Working `187,7h`, Actual `85,7h`, **Idling `54%`** (Fuel `1.279,2 L`)
- **Agustus 2026 (7 Unit Anomali > 50%)**:
  - `C01530` (`100%`), `C51502` (`60%`), `2259` (`60%`), `DBCH0366` (`58%`), `C51503` (`57%`), `C07004` (`54%`), `C07076` (`54%`).
- **September 2026 (10 Unit Anomali > 50%)**:
  - `C01530` (`100%`), `C51502` (`66%`), `2259` (`64%`), `C07076` (`61%`), `DBCH0302` (`57%`), `C51503` (`56%`), `2066` (`55%`), `DBCH2941` (`54%`), `DBCH2244` (`53%`), `DBCH1784` (`52%`).

---

## 4. Dampak & Sinkronisasi pada Menu Lain

### 1. Menu Master Asset (`#assetModule` / Tabel `assets`)
- **Temuan**: Sebanyak **9 unit Komatsu** di tabel `assets` saat ini masih memiliki nilai `last_hm_km = 0.00` (antara lain `MG-00002`, `EXC-00021`, `EXC-00010`, `EXC-41001`, `EXC-00024`, `EXC-00013`, `EXC-00014`, `EXC-00027`, `EXC-00028`), dan beberapa unit lain masih menampilkan HM lama sebelum September 2026.
- **Rencana Sinkronisasi**: Meng-update `assets.last_hm_km = GREATEST(COALESCE(last_hm_km, 0), smr_september_2026)` berdasarkan Serial Number KOMTRAX per 30 September 2026 (tanpa menimpa unit yang sudah memiliki HM Oktober 2026 lebih tinggi dari Laporan PM Oktober 2026).

### 2. Modal 360° Unit Properties (`api/unit_properties.php` & `scripts/unit-properties.js`)
- **Temuan**: Endpoint `api/unit_properties.php` (baris 274–284) sudah memiliki query:
  ```sql
  SELECT log_id, period_month, smr_hours, working_days, working_hours, actual_working_hours,
         actual_working_ratio, idling_ratio, fuel_consumed_liters, fuel_lph, last_comm_date
  FROM telematics_logs WHERE asset_id = :asset_id
  ORDER BY last_comm_date DESC, log_id DESC LIMIT 12
  ```
- **Dampak Otomatis**: Begitu data Juli, Agustus, dan September 2026 masuk ke `telematics_logs` dengan `asset_id` yang tepat, tab **Telematics / KOMTRAX** di dalam modal **360° Unit Properties** (saat pengguna mengklik salah satu unit di Master Asset) akan **otomatis menampilkan histori tren 4 periode** (`September 2026`, `Agustus 2026`, `Juli 2026`, `Januari 2026`) beserta grafik/metrik konsumsi BBM dan rasio idling-nya.

### 3. Menu Preventive Maintenance (`#pmModule` / Tabel `pm_plans`)
- **Temuan**: Beberapa unit Komatsu di `pm_plans` dapat divalidasi silang (`cross-validated`) menggunakan SMR KOMTRAX per 1 Oktober 2026 (`smr_hours` periode September 2026).
- **Rencana Sinkronisasi**: Jika terdapat unit Komatsu di `pm_plans` yang `current_hm_km`-nya masih di bawah `smr_hours` KOMTRAX September 2026 (misalnya unit yang belum tercantum di PDF PM Oktober), nilai `current_hm_km` dan `variance` (`next_service_hm - current_hm_km`) dapat disinkronkan agar indikator keterlambatan servis (*Overdue / Due Soon*) makin akurat.

---

## 5. Tahapan Eksekusi yang Direkomendasikan (Batch 11 Migration)

1. **Tahap 1 — Pembuatan & Eksekusi Skrip Migrasi (`scripts/migrate_batch11_komtrax_jul_sep_2026.php`)**:
   - Menambahkan unique index `(serial_number, period_month)` pada `telematics_logs`.
   - Menyuntikkan **54 baris data KOMTRAX** (`18 unit × 3 periode`: `Juli 2026`, `Agustus 2026`, `September 2026`) sesuai angka presisi di `material/KOMTRAX/*.md`.
   - Menyelaraskan `asset_id` pada `telematics_logs` dan memperbarui `assets.last_hm_km` untuk unit yang HM-nya masih `0.00` atau lebih rendah dari SMR 30 September 2026.
   - Mengekspor ulang dump SQL (`scripts/u646470441_ServicePlanBRA.sql`).
2. **Tahap 2 — Pembaruan UI Filter Periode di Menu Produktivitas (`dashboard.view.php`, `dashboard.html`, `scripts/dashboard.js`, `api/productivity.php`)**:
   - Menambahkan selector periode (`September 2026`, `Agustus 2026`, `Juli 2026`, `Januari 2026`) agar pengguna dapat melihat matriks KOMTRAX, Rekonsiliasi LHO, dan Idling Anomaly untuk masing-masing bulan.
3. **Tahap 3 — Sinkronisasi ke Laragon (`scripts/sync_to_laragon.ps1`)**:
   - Menyinkronkan seluruh perubahan kode dan database ke `C:\laragon\www\ServicePlan-BRA`.

