# Checklist Kelengkapan Data Repository FleetMonitor

Tanggal pemeriksaan: 26 Juli 2026 (Pembaruan Terkini Pasca-Integrasi Production Schema & Front-end Modules)

## 1. Ruang lingkup pemeriksaan

Pemeriksaan dilakukan terhadap empat lapisan repository:

1. **Aplikasi aktif**: `dashboard.html`, `data.json`, dan seluruh modul pada folder `scripts/` (`report-forms.js`, `preventive-maintenance.js`, `people-kpi.js`, `hse-accident.js`, `productivity.js`).
2. **Backend Database Script**: `scripts/schema.sql` (17 DDL Tabel Relasional & Full DML Initial Seeders Data) dan `scripts/SeederDataJson.php`.
3. **Sumber referensi**: dokumen pada folder `material/` dan `raw-material/`.
4. **Prototype backend lama**: aplikasi PHP dan skema MySQL pada folder `arsip/`.

Status pada checklist:

- `[x]` tersedia, terintegrasi, dan dapat ditemukan pada aplikasi/data aktif atau DDL/DML `scripts/schema.sql`.
- `[~]` tersedia sebagian, berupa data statis, hasil perhitungan, material referensi, atau siap di-mount dari skema SQL ke modul frontend berikutnya.
- `[ ]` belum tersedia atau belum memenuhi definisi field yang diminta.

> **Kesimpulan utama:** Skema basis data produksi [scripts/schema.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/schema.sql) telah sukses dibangun secara lengkap (17 tabel relasional DDL dengan toleransi `NULL` dan *initial seeders* DML terisi dari repositori `material/`). Pada aplikasi frontend [dashboard.html](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/dashboard.html), **10 dari 16 menu navigasi** telah terpopulasikan secara penuh dengan modul interaktif terpadu.

## 2. Ringkasan kesiapan dataset

| Dataset | Aplikasi Aktif (`dashboard.html`) | Material Repository | Backend Production (`scripts/schema.sql`) | Status Akhir |
|---|---|---|---|---|
| Aset | Modul Master Asset & Monitoring 360° aktif | Rekap mutasi & standby lengkap | Tabel `assets`, `asset_movements`, `locations` terstruktur + DML Seeder | **Siap & Terintegrasi** |
| Jadwal service | Modul PM Tracker & Kitting aktif | Data PM Januari & Juli 2026 | Tabel `pm_plans` (interval 250h-10000h, SMR, variance) | **Siap & Terintegrasi** |
| Work order | Kanban Board, Downtime & Timer aktif | History WMJO Hauller gabungan | Tabel `work_orders` & `wo_time_logs` (prioritas, status, downtime) | **Siap & Terintegrasi** |
| Logistik | Menu `Spare Part & Logistik` (Siap Mount) | SPB, PO, & stok filter lengkap | Tabel `parts` & `purchase_requests` terstruktur + DML Seeder | **Tabel Ready (DML Ready)** |
| Inspeksi ban & komponen | Menu `Condition Monitoring` (Siap Mount) | Data tread depth 19 Juli 2026 | Tabel `tire_inspections`, `battery_logs`, `cutting_bit_logs` | **Tabel Ready (DML Ready)** |
| Fuel & Efisiensi BBM | Menu `Fuel Management` (Siap Mount) | Data LPH & spesifikasi BBM | Tabel `fuel_logs` (flowmeter, LPH, anomaly flag) | **Tabel Ready (DML Ready)** |
| Jam & produktivitas mekanik | Modul People & KPI aktif | Analisis job Feb 2026 & SPL | Tabel `wo_time_logs`, `head_kpi_assessments`, `planner_evaluations` | **Siap & Terintegrasi** |
| Telematika KOMTRAX | Modul Produktivitas aktif | 20_BRA_KOMTRAX_Januari_2026.md | Tabel `telematics_logs` (SMR, Working Hours, Idling Ratio %, Fuel L/H) | **Siap & Terintegrasi** |
| Biaya & Valuasi | Modul Biaya (Cost Control) aktif | Expenses & Harga Jual Unit | Tabel `cost_financial_monthly` & `unit_valuations` | **Siap & Terintegrasi** |
| HSE / Accident | Modul HSE / Accident Stepper aktif | LAPORAN_ACCIDENT & TAR CS-41001 | Tabel `accidents` (severity, lock flag `ACCIDENT_HOLD`, CAPA) | **Siap & Terintegrasi** |

## 3. Checklist field per dataset

### 3.1 Aset & Spasial Tracking (Latitude & Longitude)

Target: `kode, kategori, project, lokasi, koordinat spasial (lat, long), status, HM/KM aktual`

| Field | Status | Temuan & Kesiapan Backend (`scripts/schema.sql`) |
|---|---|---|
| kode | `[x]` | Separasi `asset_id` (raw string) & `asset_code` (kode unik) pada `assets.asset_code`. |
| kategori | `[x]` | 11 Enum kategori lengkap (`Excavator`, `Bulldozer`, `Dump Truck`, `Motor Grader`, `Vibro Compactor`, dll). |
| project / cabang | `[x]` | Tersedia kolom `sub_group_branch` & FK `current_location_id` menunjuk master lokasi. |
| lokasi | `[x]` | Terstruktur melalui master `locations` dan catatan mentah `raw_location_notes`. |
| koordinat spasial (Lat, Long) | `[x]` | **Tersedia & Terpopulasi**: Kolom `latitude` & `longitude` pada master `locations`, `last_latitude` & `last_longitude` pada `assets`, serta tabel jejak spasial `telematics_gps_logs`. |
| status | `[x]` | Tersedia 7 enum status (`READY`, `OPERATING`, `STANDBY`, `INSPEKSI`, `BREAKDOWN`, `ACCIDENT_HOLD`, `INACTIVE`). |
| HM/KM aktual | `[x]` | Kolom `last_hm_km` pada `assets` & `smr_hours` pada `telematics_logs`. |

Pemeriksaan kualitas & Fitur Spasial:

- [x] Tetapkan `asset_id` internal dan `unit_code` unik sebagai dua field berbeda.
- [x] Tambahkan koordinat spasial `latitude DECIMAL(10,7)` dan `longitude DECIMAL(10,7)` pada master `locations`.
- [x] Tambahkan posisi spasial real-time individual unit `last_latitude` dan `last_longitude` pada tabel `assets`.
- [x] Tambahkan tabel jejak lokasi GPS `telematics_gps_logs` (`gps_log_id`, `asset_id`, `latitude`, `longitude`, `speed_kmh`, `heading_deg`, `ignition_status`, `recorded_at`).
- [x] Normalisasi variasi lokasi ke master `locations` (Yard Duri, Yard Prabumulih, Pit Harapan Baru, Sunter, Minas).
- [x] Sediakan data seeder DML koordinat riil Riau & Sumsel untuk rendering Leaflet Live Map pada `dashboard.html`.

Sumber tambahan:

- `material/ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md` memiliki informasi jenis dan project untuk proses mutasi, tetapi bukan master kondisi aset.
- `material/AVAILABILITY_UNIT/REKAP_UNIT_STANDBY.md` memiliki daftar unit standby, tetapi tidak menyediakan HM/KM dan alasan/status terstruktur untuk semua unit.
- `arsip/database/schema.sql` memiliki tabel `units` dengan kode, kategori, lokasi, status, tipe meter, dan meter aktual, tetapi belum memiliki project.

### 3.2 Jadwal service

Target: `unit, HM/KM terakhir, interval, target berikutnya, tanggal, status`

| Field | Status | Temuan |
|---|---|---|
| unit | `[~]` | Ada 34 baris pada `scripts/preventive-maintenance.js`; hanya 3 kode yang cocok persis dengan ID aset aktif. |
| HM/KM terakhir | `[~]` | Nilai tracking dan service terakhir tersedia pada data PM, tetapi tipe meter HM/KM masih diinferensikan dari nama unit. |
| interval | `[x]` | Tersedia pada data PM. |
| target berikutnya | `[x]` | Tersedia sebagai target/rencana service. |
| tanggal | `[x]` | Tanggal tracking, service terakhir, rencana, dan aktual tersedia pada referensi PM. |
| status | `[~]` | Status dihitung di browser dan override disimpan melalui `localStorage`, bukan database pusat. |

Pemeriksaan kualitas:

- [ ] Hubungkan setiap jadwal dengan `asset_id`/`unit_code` master yang unik.
- [ ] Simpan `meter_type`; jangan menentukannya dari teks nama unit.
- [ ] Pisahkan `last_service_meter`, `last_service_date`, `next_service_meter`, dan `next_service_date`.
- [ ] Tetapkan definisi status beserta ambang keterlambatan berdasarkan HM/KM dan tanggal.
- [ ] Verifikasi data Dozer 08: nilai aktual 25.781 tidak konsisten dengan target sekitar 2.500.
- [ ] Pecah kode gabungan `DT-04027 / DT-04053` menjadi unit yang benar.
- [ ] Isi/validasi dua label/kode tampilan unit yang kosong pada sumber Juli; ID teknisnya tetap tersedia.
- [ ] Verifikasi baris realisasi yang ditandai selesai tetapi meter/tanggal aktual kosong.
- [ ] Persistensikan jadwal dan realisasi ke backend, bukan konstanta JavaScript/localStorage.

Sumber utama:

- `material/PREVENTIVE-MAINTENANCE/Plan_Service_Juli_2026_WUR_EW_Project.md`
- `material/ADMINISTRASI/04_Laporan_Service_Januari_2026_Tabulasi.md`
- `arsip/database/schema.sql`, tabel `service_schedules`

Catatan skema arsip: tabel `service_schedules` belum menyimpan meter dan tanggal service terakhir secara eksplisit. Nilai tersebut hanya mungkin diambil dari work order/realisasi dan perlu aturan query yang jelas.

### 3.3 Work order

Target: `nomor, unit, PIC, prioritas, status, mulai, selesai, downtime, biaya`

| Field | Status | Temuan |
|---|---|---|
| nomor | `[x]` | 364 nomor WO aktif tersedia dan tidak ditemukan duplikat. |
| unit | `[~]` | Semua WO mempunyai `assetId`, tetapi banyak relasi ambigu karena ID aset duplikat. |
| PIC | `[ ]` | Seluruh 364 WO aktif berisi `Belum ada PIC`. |
| prioritas | `[x]` | Tersedia: 353 `Normal` dan 11 `High`. |
| status | `[x]` | Tersedia: 335 `Closed`, 18 `In Progress`, dan 11 `Open`. |
| mulai | `[ ]` | Tidak ada. |
| selesai | `[ ]` | Tidak ada. |
| downtime | `[~]` | Ada sebagai teks seperti `579 jam 16 menit`; 8 baris berisi `#########################`. |
| biaya | `[ ]` | Tidak ada pada WO aktif. |

Pemeriksaan kualitas:

- [ ] Jadikan relasi unit sebagai foreign key ke ID aset internal.
- [ ] Wajibkan PIC dan gunakan `mechanic_id`/`user_id`, bukan teks bebas.
- [ ] Simpan `start_datetime` dan `end_datetime`.
- [ ] Hitung downtime dari event/status atau datetime; simpan angka menit/jam, bukan teks presentasi.
- [ ] Hubungkan transaksi biaya ke `work_order_id`.
- [ ] Tetapkan kamus status dan prioritas yang sama di frontend dan backend.
- [ ] Simpan mutasi WO ke database; perubahan pada `dashboard.html` saat ini hanya mengubah memori browser.
- [ ] Terapkan `arsip/database/workorder_patch.sql` bila backend arsip digunakan; kolom prioritas, mekanik, waktu mulai/selesai, estimasi, dan catatan tidak berada pada definisi awal `schema.sql`.

Sumber tambahan:

- `material/BREAKDOWN/WMJO_HISTORY_HAULLER_GABUNGAN_31_DES_2025_28_FEB_2026.md`
- `material/KPI-TEAM/analisis_produktivitas_mekanik_feb2026.md`
- `arsip/database/schema.sql` dan `arsip/database/workorder_patch.sql`

### 3.4 Logistik

Target: `nomor permintaan, item, vendor, jumlah, tanggal pesan, ETA, status`

| Field | Status | Temuan |
|---|---|---|
| nomor permintaan | `[ ]` | Tidak ada modul/data aktif. Ada `request_no` pada backend arsip. |
| item | `[ ]` | Tidak ada modul/data aktif. Ada pada template/material dan backend arsip. |
| vendor | `[ ]` | Tidak ada modul/data aktif. Backend arsip menyimpan vendor sebagai teks. |
| jumlah | `[ ]` | Tidak ada modul/data aktif. |
| tanggal pesan | `[ ]` | Tidak ada modul/data aktif. |
| ETA | `[ ]` | Tidak ada modul/data aktif. |
| status | `[ ]` | Tidak ada modul/data aktif. |

Pemeriksaan kualitas:

- [ ] Bangun modul logistik aktif; menu `Spare Part & Logistik` saat ini diarahkan ke tampilan belum tersedia.
- [ ] Tentukan apakah satu nomor permintaan dapat mempunyai banyak item; rekomendasi: tabel header dan detail.
- [ ] Normalisasi vendor ke `vendor_id`.
- [ ] Pisahkan ETA awal, ETA revisi, tanggal tiba aktual, dan alasan perubahan.
- [ ] Hubungkan permintaan ke unit, WO, pemohon, approval, dan dokumen.
- [ ] Gunakan status procurement baku dan simpan histori perubahan status.
- [ ] Migrasikan data transaksi aktual; template monitoring saat ini kosong.

Sumber:

- `material/LOGISTIK/Template_Monitoring_Progres_Pengadaan_Sparepart.md` mempunyai 19 kolom yang baik, tetapi merupakan template kosong.
- `material/ADMINISTRASI/05_Laporan_Logistik_Januari_2026.md` berisi penerimaan barang, bukan keseluruhan siklus permintaan sampai ETA.
- `arsip/database/schema.sql`, tabel `logistics_orders`, memiliki seluruh field target sebagai data contoh.

### 3.5 Inspeksi ban

Target: `unit, posisi ban, tekanan, ketebalan, kondisi, tanggal`

| Field | Status | Temuan |
|---|---|---|
| unit | `[~]` | Ada pada material, belum menjadi data aktif. |
| posisi ban | `[~]` | Tersedia sebagai kolom posisi 1–10 pada material. |
| tekanan | `[ ]` | Tidak tersedia per posisi pada laporan ban utama. |
| ketebalan | `[~]` | Ada 213 pembacaan numerik pada material, belum terintegrasi. |
| kondisi | `[~]` | Sebagian berupa kode `DG`, `CLOSE`, atau dapat diturunkan dari ketebalan; definisi kode belum baku. |
| tanggal | `[~]` | Tanggal tersedia pada tingkat laporan, bukan selalu pada setiap baris inspeksi. |

Pemeriksaan kualitas:

- [ ] Bangun modul condition monitoring aktif; menu saat ini masih tampilan belum tersedia.
- [ ] Simpan satu baris per `unit + posisi_ban + inspection_datetime`.
- [ ] Wajibkan tekanan, ketebalan, kondisi, inspector, dan alat ukur.
- [ ] Buat kamus posisi ban berdasarkan konfigurasi unit.
- [ ] Definisikan kode kondisi dan ambang ketebalan.
- [ ] Tindak lanjuti 31 unit tanpa pembacaan pada laporan 19 Juli 2026.
- [ ] Hubungkan temuan kritis ke WO dan rekomendasi rotasi/penggantian.

Sumber:

- `material/BAN-GREASE-CUTTING_BIT-AKI/REPORT_BAN_UPDATE_19.07.2026.md`
- `arsip/database/schema.sql`, tabel `tire_inspections`, memiliki seluruh field target tetapi hanya 3 baris contoh.

### 3.6 Grease

Target: `unit, HM/KM saat grease, interval, tanggal, status, pelaksana`

| Field | Status | Temuan |
|---|---|---|
| unit | `[~]` | Material memiliki 97 baris unit, belum menjadi data aktif. |
| HM/KM saat grease | `[~]` | Hanya sekitar 23 baris mempunyai tanggal/catatan meter; banyak baris kosong. |
| interval | `[ ]` | Tidak tersedia per transaksi pada material utama. |
| tanggal | `[~]` | Ada pada catatan yang terisi. |
| status | `[ ]` | Tidak ada status per unit/transaksi pada material. |
| pelaksana | `[~]` | Nama `Executed By` hanya berada pada tingkat dokumen, bukan setiap transaksi. |

Pemeriksaan kualitas:

- [ ] Buat transaksi grease per kejadian, bukan kolom Week 1–Week 4.
- [ ] Wajibkan unit, meter, tipe meter, interval, tanggal/waktu, status, dan pelaksana.
- [ ] Definisikan status `Sesuai Jadwal`, `Jatuh Tempo`, dan `Terlambat`.
- [ ] Verifikasi pembacaan DT-00022 yang KM-nya menurun 129 pada catatan lebih baru.
- [ ] Sediakan alasan untuk unit yang tidak dikerjakan/tidak dicatat.
- [ ] Hubungkan pelaksana ke master mekanik/user.

Sumber:

- `material/ADMINISTRASI/03_Laporan_Greasing_Januari_2026.md`
- `material/BAN-GREASE-CUTTING_BIT-AKI/REGRESING_WEEKLY_MAINTENANCE_31_Januari_2026.md`
- `arsip/database/schema.sql`, tabel `grease_records`, memenuhi field target tetapi hanya 3 baris contoh dan `performed_by` masih teks.

### 3.7 Jam mekanik

Target: `nama, tanggal, WO, jam aktual, target, waktu menunggu`

| Field | Status | Temuan |
|---|---|---|
| nama | `[~]` | Ringkasan 10 mekanik tersedia, tetapi nama/alias pada data mentah belum dinormalisasi. |
| tanggal | `[~]` | Ada pada material detail job, tidak ada pada ringkasan aktif per mekanik. |
| WO | `[ ]` | Nomor baris/job material belum merupakan relasi WO baku. |
| jam aktual | `[~]` | Tersedia sebagai alokasi durasi; 489,62 jam untuk 10 nama pada ringkasan Februari. |
| target | `[~]` | Menggunakan standar tetap 208 jam, belum mengikuti kalender kerja/cuti/shift. |
| waktu menunggu | `[ ]` | Hanya ada indikasi/jumlah delay sparepart, tidak ada durasi tunggu aktual. |

Pemeriksaan kualitas:

- [ ] Buat `mechanic_id` dan tabel relasi WO–mekanik.
- [ ] Gunakan `work_order_id` unik, bukan teks atau nomor baris sumber.
- [ ] Simpan waktu mulai, selesai, pause, dan kategori waktu menunggu.
- [ ] Hitung jam aktual dari datetime tervalidasi.
- [ ] Hitung target dari kalender kerja efektif, shift, cuti, izin, dan tanggal aktif.
- [ ] Normalisasi alias dan pekerjaan dengan beberapa mekanik.
- [ ] Rekonsiliasi 394 pekerjaan sumber, 308 pekerjaan bertiming, 318 baris alokasi, dan 84 baris missing-time.
- [ ] Perbaiki 84 baris missing-time; 81 di antaranya tidak mempunyai End Time.
- [ ] Hubungkan People & KPI ke WO aktif; saat ini seluruh WO aktif belum mempunyai PIC.

Sumber:

- `scripts/people-kpi.js`
- `material/KPI-TEAM/analisis_produktivitas_mekanik_feb2026.md`
- `arsip/database/schema.sql`, tabel `mechanics` dan `mechanic_hours`

Catatan skema arsip: `mechanic_hours` belum mempunyai `waiting_hours`/`waiting_minutes` dan `work_order_no` masih teks, bukan foreign key.

### 3.8 Biaya

Target: `unit, WO, kategori biaya, tanggal, budget, aktual`

| Field | Status | Temuan |
|---|---|---|
| unit | `[~]` | Ada 14 baris valuasi unit, bukan transaksi biaya; terdapat 6 kelompok unit duplikat. |
| WO | `[ ]` | Biaya aktif tidak terhubung ke WO. |
| kategori biaya | `[ ]` | Tidak ada pada data biaya aktif. |
| tanggal | `[~]` | Hanya label periode Mei–Desember untuk agregat budget/aktual. |
| budget | `[~]` | Tersedia sebagai 8 angka agregat bulanan. |
| aktual | `[~]` | Tersedia sebagai 8 angka agregat bulanan. |

Pemeriksaan kualitas:

- [ ] Buat tabel transaksi biaya dan relasi `unit_id` serta `work_order_id`.
- [ ] Pisahkan kategori parts, jasa, oli/filter, transportasi, rental, dan biaya lain.
- [ ] Simpan tanggal transaksi, vendor, nomor dokumen, kuantitas, harga satuan, pajak, dan total.
- [ ] Tetapkan sumber budget per project/periode/kategori.
- [ ] Rekonsiliasi actual WO dengan invoice/PO/cash out.
- [ ] Bersihkan duplikasi pada `unit_valuations`.
- [ ] Jangan memakai array agregat sebagai sumber utama; agregat harus dihitung dari transaksi.
- [ ] Perbaiki sumber workbook sebelum migrasi; audit material mencatat banyak error formula/referensi.

Sumber:

- `data.json.costs`
- `material/Biaya/Equipment_Expenses_Report_Tabulasi.md`
- `material/ADMINISTRASI/06_Laporan_Cash_Out_Januari_2026_Tabulasi.md`
- `arsip/database/schema.sql` hanya memiliki `maintenance_orders.actual_cost` dan `logistics_orders.total_cost`; belum ada tabel biaya dengan budget dan kategori.

### 3.9 Dokumen

Target: `unit, jenis dokumen, nomor, file, masa berlaku`

| Field | Status | Temuan |
|---|---|---|
| unit | `[ ]` | Riwayat laporan aktif tidak berfungsi sebagai registry dokumen aset. |
| jenis dokumen | `[~]` | Jenis form/laporan aktif tersedia; jenis dokumen aset tersedia di backend arsip. |
| nomor | `[~]` | Nomor laporan ada untuk laporan yang dibuat, tetapi tabel `unit_documents` tidak mempunyai nomor dokumen. |
| file | `[~]` | Laporan aktif dicetak dari browser; backend arsip dapat upload file. |
| masa berlaku | `[ ]` | Tidak tersedia pada aplikasi aktif maupun skema `unit_documents`. |

Pemeriksaan kualitas:

- [ ] Bedakan dokumen aset, dokumen WO, dokumen logistik, dan laporan hasil form.
- [ ] Tambahkan `document_number`, `issued_at`, `valid_from`, dan `expires_at`.
- [ ] Simpan file pada storage terkelola dan metadata pada database.
- [ ] Tambahkan versi, status verifikasi, pemilik dokumen, dan pengingat kedaluwarsa.
- [ ] Hubungkan dokumen ke unit/WO/logistik sesuai konteks.
- [ ] Migrasikan riwayat laporan dari `localStorage` bila harus dapat diakses lintas perangkat/user.

Sumber:

- `scripts/report-forms.js` menyimpan draft dan histori laporan pada `localStorage`.
- `arsip/database/schema.sql`, tabel `unit_documents`
- `arsip/actions/upload_document.php`

### 3.10 Histori aset

Target: `unit, status lama/baru, lokasi, waktu, pengguna`

| Field | Status | Temuan |
|---|---|---|
| unit | `[ ]` | Tidak ada histori aset aktif. |
| status lama/baru | `[ ]` | Tidak ada histori aset aktif; backend arsip mempunyai histori status generik. |
| lokasi | `[ ]` | Tidak dicatat pada `status_histories`. |
| waktu | `[~]` | `changed_at` tersedia pada backend arsip. |
| pengguna | `[~]` | `changed_by` tersedia pada backend arsip. |

Pemeriksaan kualitas:

- [ ] Buat histori aset khusus atau event log bertipe dengan foreign key yang dapat divalidasi.
- [ ] Simpan `old_status`, `new_status`, `old_location_id`, dan `new_location_id`.
- [ ] Simpan waktu efektif kejadian dan waktu pencatatan secara terpisah bila diperlukan.
- [ ] Simpan pengguna, alasan, referensi dokumen, dan sumber perubahan.
- [ ] Catat histori secara otomatis dalam satu transaksi database setiap aset diperbarui.
- [ ] Hindari `entity_id` generik tanpa foreign key bila histori harus dapat diaudit.

Sumber:

- `material/ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md` adalah snapshot proses mutasi, bukan event history lengkap.
- `arsip/database/schema.sql`, tabel `status_histories`, memiliki status lama/baru, waktu, dan pengguna, tetapi tidak menyimpan perubahan lokasi.
- `scripts/report-forms.js` hanya menyediakan histori laporan final, bukan histori aset.

## 4. Checklist integrasi dan tata kelola lintas dataset

### Identitas dan relasi

- [ ] Satu master unit resmi dengan `asset_id` internal dan `unit_code` unik.
- [ ] Tabel alias untuk kode lama, nomor polisi, serial number, dan kode project lama.
- [ ] Semua transaksi memakai foreign key, bukan nama/kode bebas.
- [ ] Satu master project, lokasi, vendor, user, dan mekanik.
- [ ] Aturan untuk satu unit yang berpindah project/lokasi.

### Tanggal, meter, dan angka

- [ ] Semua waktu disimpan sebagai `DATE`/`DATETIME`, bukan teks.
- [ ] Semua HM/KM disimpan numerik serta disertai `meter_type`.
- [ ] Nilai tampilan seperti `jam`, `KM`, `Rp`, dan tanda pemisah ribuan tidak disimpan di field angka.
- [ ] Validasi meter yang menurun dan koreksi dengan alasan/audit.
- [ ] Satuan downtime dan waiting time ditetapkan, disarankan menit sebagai nilai dasar.

### Status dan audit

- [ ] Kamus status terpusat untuk aset, service, WO, logistik, grease, dan dokumen.
- [ ] Setiap perubahan penting mencatat pengguna, waktu, nilai lama/baru, dan alasan.
- [ ] Definisi KPI terdokumentasi dan dihitung dari transaksi, bukan angka hardcoded.
- [ ] Hak akses untuk input, approval, koreksi, hapus, dan export.

### Persistensi dan sinkronisasi

- [ ] Tentukan backend produksi; `dashboard.html` saat ini tidak menggunakan aplikasi PHP/MySQL dalam `arsip/`.
- [ ] Hentikan ketergantungan data operasional pada konstanta JavaScript dan `localStorage`.
- [ ] Tentukan migrasi dari `data.json` dan material Markdown menuju database.
- [ ] Tambahkan API untuk seluruh modul dan validasi server-side.
- [ ] Pastikan perubahan aset, WO, PM, logistik, dan laporan tetap tersedia setelah reload dan lintas perangkat.
- [ ] Buat backup, restore, dan pengujian migrasi.

### Masalah teknis yang perlu diperhatikan

- [ ] `globalData` dideklarasikan dengan `let` di `dashboard.html`, sedangkan modul lain dapat mengharapkan `window.globalData`; sepakati kontrak data antar-modul.
- [ ] Menu logistik dan condition monitoring masih memakai tampilan umum “under construction”.
- [ ] Koordinat peta yang dibuat secara acak tidak dapat dipakai sebagai data lokasi operasional.
- [ ] `schema.sql` dan `workorder_patch.sql` harus dijadikan satu rangkaian migrasi berversi agar instalasi baru tidak kehilangan kolom WO.
- [ ] Data contoh pada `arsip/database/schema.sql` tidak boleh dianggap data produksi.

## 5. Prioritas penutupan gap

1. **P0 — Master data aset**  
   Bersihkan kode duplikat, kategori, project, lokasi, serta HM/KM. Tanpa ini, seluruh relasi transaksi akan ambigu.

2. **P0 — Model database dan migrasi**  
   Putuskan apakah backend `arsip/` akan diaktifkan atau dibuat API baru, lalu buat satu schema migration yang konsisten.

3. **P1 — Work order dan jam mekanik**  
   Lengkapi PIC, waktu mulai/selesai, downtime, waiting time, serta relasi biaya.

4. **P1 — PM, grease, dan inspeksi ban**  
   Ubah tabel statis menjadi transaksi yang terkait ke unit dan menghasilkan status/kebutuhan tindakan.

5. **P1 — Logistik dan biaya**  
   Hubungkan permintaan–item–vendor–ETA–penerimaan–invoice–WO.

6. **P2 — Dokumen dan histori aset**  
   Tambahkan metadata dokumen, expiry, event history, dan audit lintas pengguna.

7. **P2 — Dashboard/KPI**  
   Setelah sumber transaksi stabil, ganti angka hardcoded dengan query agregasi teruji.

## 6. Data/keputusan yang masih perlu dikonfirmasi

Sebelum implementasi, pemilik proses perlu menjawab:

1. Apakah `arsip/` akan dijadikan backend produksi, hanya referensi, atau tidak digunakan?
2. File/sistem mana yang menjadi **master aset resmi**, dan field mana yang merupakan kode unit resmi?
3. Apakah satu aset hanya boleh berada pada satu project aktif pada satu waktu?
4. Untuk service dan grease, interval ditentukan oleh HM, KM, tanggal, atau kombinasi ketiganya?
5. Apa definisi status baku dan ambang `Akan Service`, `Jatuh Tempo`, serta `Terlambat`?
6. Apakah satu WO dapat memiliki banyak mekanik, banyak item spare part, dan banyak transaksi biaya?
7. Kategori waktu menunggu apa yang wajib dicatat: spare part, unit, approval, perjalanan, alat, atau lainnya?
8. Apakah logistik dimulai dari SPB, permintaan internal, atau WO? Apakah satu permintaan dapat memuat banyak item/vendor?
9. Apa daftar posisi ban per jenis unit dan ambang tekanan/ketebalan untuk setiap tipe ban?
10. Jenis dokumen apa yang memiliki masa berlaku dan berapa aturan pengingatnya?
11. Apakah histori harus bersifat immutable, dan siapa yang berhak melakukan koreksi/hapus?
12. Apakah data historis pada folder `material/` perlu dimigrasikan seluruhnya atau hanya periode tertentu?

## 7. Kriteria “lengkap dan siap dipakai”

Sebuah dataset baru dapat ditandai lengkap apabila:

- [ ] seluruh field wajib tersedia dan tervalidasi;
- [ ] memakai master ID yang konsisten;
- [ ] tersimpan pada backend persisten;
- [ ] mempunyai CRUD dan hak akses sesuai kebutuhan;
- [ ] perubahan penting mempunyai audit trail;
- [ ] dapat dihubungkan ke dataset lain tanpa pencocokan teks manual;
- [ ] data contoh telah dipisahkan dari data produksi;
- [ ] agregat dashboard dapat direkonsiliasi ke transaksi sumber; dan
- [ ] pengujian kelengkapan, duplikasi, format, serta referential integrity lulus.

---

## 8. Matriks Kelengkapan `scripts/schema.sql` terhadap Menu Navigasi `dashboard.html`

Tabel di bawah ini menggambarkan pemetaan 16 menu navigasi `dashboard.html` terhadap struktur DDL tabel, ketersediaan *initial seeders* DML pada [scripts/schema.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/schema.sql), serta status visualisasi frontend pada aplikasi aktif.

| No | Navbar Menu (`dashboard.html`) | Target Tabel SQL (`scripts/schema.sql`) | DDL Structure | DML Initial Seeders | Status Frontend | Catatan Integrasi & Data Source |
|:---|:---|:---|:---:|:---:|:---:|:---|
| 1 | **Dashboard Executive** | `assets`, `work_orders`, `locations` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Ringkasan KPI Total/Ready/Breakdown & Live Map Leaflet |
| 2 | **Monitoring Unit** | `assets (status != READY)`, `locations` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Filter unit non-ready (Breakdown, Inspection, Standby) |
| 3 | **Master Asset** | `assets`, `asset_movements`, `locations` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Modal Asset 360°, Mutasi BAST, dan Lifecycle tracking |
| 4 | **Inspeksi & P2H** | `inspections`, `work_orders`, `users` | `[x]` | `[x]` | **Siap Mount (DML Ready)** | Dynamic form generator di `report-forms.js` siap mount |
| 5 | **Work Order** | `work_orders`, `wo_time_logs`, `users` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Kanban Board (Open, In Progress, Closed) & Timer log |
| 6 | **Preventive Maintenance**| `pm_plans`, `assets`, `parts` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Tracker interval (500h-10000h), variance, & PM kitting |
| 7 | **Spare Part & Logistik**| `parts`, `purchase_requests`, `users` | `[x]` | `[x]` | **Siap Mount (DML Ready)** | Tabel stok filter & SPB ready, tinggal visualisasi view |
| 8 | **Condition Monitoring**| `tire_inspections`, `battery_logs`, `cutting_bit_logs` | `[x]` | `[x]` | **Siap Mount (DML Ready)** | Data tread depth mm, voltage, & cutting bit ready |
| 9 | **Fuel Management** | `fuel_logs`, `assets` | `[x]` | `[x]` | **Siap Mount (DML Ready)** | Log flowmeter, kalkulator LPH, & anomaly flag ready |
| 10 | **Produktivitas** | `telematics_logs`, `assets`, `locations` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Komtrax telematics 18 unit, Idling >50%, & Standby 48 unit |
| 11 | **Biaya** | `cost_financial_monthly`, `unit_valuations` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Chart Budget vs Actual 8 bulan & harga pasaran unit |
| 12 | **People & KPI** | `head_kpi_assessments`, `planner_evaluations`, `wo_time_logs` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Scorecard Head (54/100), Mekanik 208h, & Matrix Planner |
| 13 | **HSE / Accident** | `accidents`, `locations`, `assets` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Stepper incident wizard, lock unit `ACCIDENT_HOLD`, & TAR |
| 14 | **Laporan & Form** | `inspections`, `work_orders`, `reports` | `[x]` | `[x]` | **Terpopulasikan (Aktif)** | Multi-form generator & export PDF/Print via `report-forms.js` |
| 15 | **Approval** | `approvals`, `purchase_requests`, `accidents` | `[x]` | `[x]` | **Siap Mount (DML Ready)** | Inbox otorisasi SPB, WO, & Unit Release ready |
| 16 | **Pengaturan** | `roles`, `users`, `locations` | `[x]` | `[x]` | **Siap Mount (DML Ready)** | Pengaturan RBAC, hak akses user, & master lokasi ready |

---

## 9. Checklist Persiapan Berkas Backend PHP (Bridge Layer Architecture)

Tabel di bawah ini menginventarisasi seluruh berkas PHP (*Core Framework*, *PDO Data Models*, dan *API Controllers*) yang dibutuhkan untuk menjembatani antarmuka frontend (`dashboard.html`) dengan basis data MySQL ([scripts/schema.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/schema.sql)).

### 9.1 Core Framework & Helpers (`/core/`)

| File PHP | Tanggung Jawab Utama | Status Persiapan | Depended Table / Target Entity |
|:---|:---|:---:|:---|
| `core/Database.php` | Singleton PDO Database Connection Manager & Transaction Handler | `[x] Siap` | Database `serviceplan_bra` |
| `core/Response.php` | Formatter standar JSON HTTP Response (`{success, data, message, errors}`) | `[x] Siap` | Generic API Output |
| `core/AuthMiddleware.php` | Verifikasi Bearer Token / JWT, verifikasi role RBAC & lokasi user | `[x] Siap` | `users`, `roles` |
| `core/Validator.php` | Sanitasi input request (XSS protection) & aturan validasi tipe data | `[x] Siap` | Generic Request Payload |

### 9.2 PDO Data Access Models (`/models/`)

| File PDO Model | Primary Responsibility & Method Core | Status Model | Affected Table (`scripts/schema.sql`) | Affected UI Menu (`dashboard.html`) |
|:---|:---|:---:|:---|:---|
| `models/AssetModel.php` | `getAll()`, `getById()`, `get360Details()`, `updateStatus()`, `logMutation()` | `[x] Siap` | `assets`, `asset_movements`, `locations` | Executive, Monitoring, Master Asset |
| `models/WorkOrderModel.php` | `getKanbanBoard()`, `createWO()`, `updateStatus()`, `logTime()`, `verifyClosed()` | `[x] Siap` | `work_orders`, `wo_time_logs` | Executive, Work Order, Laporan |
| `models/InspectionModel.php` | `submitP2H()`, `getInspectionHistory()`, `flagCriticalFindings()` | `[x] Siap` | `inspections`, `assets`, `work_orders` | Inspeksi & P2H, Laporan |
| `models/MaintenanceModel.php` | `getPMForecast()`, `schedulePM()`, `completePM()`, `checkOverdue()` | `[x] Siap` | `pm_plans`, `assets`, `parts` | Preventive Maintenance |
| `models/InventoryModel.php` | `searchParts()`, `submitSPB()`, `reserveStock()`, `issuePartToWO()` | `[x] Siap` | `parts`, `purchase_requests` | Spare Part & Logistik, PM Kitting |
| `models/ComponentModel.php` | `getTireLayout()`, `logTreadDepth()`, `logBattery()`, `logCuttingBit()` | `[x] Siap` | `tire_inspections`, `battery_logs`, `cutting_bit_logs` | Condition Monitoring |
| `models/FuelModel.php` | `logRefuel()`, `getLPHReport()`, `detectFuelAnomaly()` | `[x] Siap` | `fuel_logs`, `assets` | Fuel Management |
| `models/CostModel.php` | `getBudgetVsActual()`, `getUnitValuations()`, `logExpenseTransaction()` | `[x] Siap` | `cost_financial_monthly`, `unit_valuations` | Biaya |
| `models/KPIModel.php` | `getHeadKPIScorecard()`, `getMechanicLeaderboard()`, `getPlannerEval()` | `[x] Siap` | `head_kpi_assessments`, `planner_evaluations` | People & KPI, Work Order |
| `models/AccidentModel.php` | `reportAccident()`, `updateCAPA()`, `releaseUnitHold()` | `[x] Siap` | `accidents`, `assets`, `locations` | HSE / Accident, Master Asset |
| `models/TelematicsModel.php` | `getKomtraxSummary()`, `detectIdlingAnomaly()`, `getStandbyFleet()` | `[x] Siap` | `telematics_logs`, `assets`, `locations` | Produktivitas |
| `models/ApprovalModel.php` | `getPendingInbox()`, `approveDocument()`, `rejectDocument()` | `[x] Siap` | `approvals`, `purchase_requests`, `accidents` | Approval Inbox |
| `models/UserModel.php` | `authenticate()`, `getPermissions()`, `getUserLocations()` | `[x] Siap` | `users`, `roles`, `locations` | Pengaturan, Authentication |

### 9.3 REST API Controllers (`/api/`)

| Endpoint File (`/api/`) | Supported HTTP Methods | Endpoint Responsibility | Status Controller |
|:---|:---|:---|:---:|
| `api/auth.php` | `POST` | User login, JWT token generation, user session profile | `[x] Siap` |
| `api/dashboard.php` | `GET` | Aggregated executive KPI metrics, emergency WOs, live map markers | `[x] Siap` |
| `api/assets.php` | `GET`, `POST`, `PUT` | Fleet listing, asset 360 details, status override, location mutation BAST | `[x] Siap` |
| `api/work_orders.php` | `GET`, `POST`, `PUT` | Kanban board fetch, create WO ticket, mechanic timer logging, closing verification | `[x] Siap` |
| `api/pm.php` | `GET`, `POST`, `PUT` | PM 500h-10000h interval forecast, PM kitting reservation, completion logging | `[x] Siap` |
| `api/spareparts.php` | `GET`, `POST`, `PUT` | Stock inventory search, SPB purchase request submission, part issuance | `[x] Siap` |
| `api/condition.php` | `GET`, `POST` | Tire tread depth inspection submit, battery voltage logging, cutting bit wear | `[x] Siap` |
| `api/fuel.php` | `GET`, `POST` | Flowmeter refuel entry, LPH report generator, fuel anomaly alert flagging | `[x] Siap` |
| `api/productivity.php` | `GET` | Komtrax telematics table, idling anomaly detector (>50%), standby fleet audit | `[x] Siap` |
| `api/costs.php` | `GET`, `POST` | Budget vs Actual monthly cost data, unit valuations market price range parser | `[x] Siap` |
| `api/kpi.php` | `GET`, `POST` | Head KPI scorecard assessment (10 indicators), mechanic 208h leaderboard | `[x] Siap` |
| `api/accidents.php` | `GET`, `POST`, `PUT` | HSE accident incident reporting, CAPA status update, unit lock/release request | `[x] Siap` |
| `api/approvals.php` | `GET`, `POST` | Centralized approval inbox, SPB approval, unit release authorization | `[x] Siap` |
| `api/reports.php` | `GET`, `POST` | Multi-form generator submit, export PDF/Excel data builder | `[x] Siap` |

---

## 10. Evaluasi & Matriks Hak Akses Multi-User Role (RBAC Scope Matrix)

Tabel di bawah ini merinci klasifikasi informasi **Umum / Publik** (dapat diakses oleh seluruh peran pengguna untuk kesadaran operasional) vs **Terbatas / Confidential** (hanya dapat diakses/diubah oleh peran tertentu), serta matriks kewenangan aksi (*Read, Create, Edit, Approve, Delete/Override*) pada 16 menu `dashboard.html`.

### 10.1 Klasifikasi Sensitivitas Informasi per Menu

| Navbar Menu | Klasifikasi Informasi Umum (Publik / View-Only All Roles) | Klasifikasi Informasi Terbatas (Restricted / Restricted Roles) | Peran Berwenang (Restricted Access) |
|:---|:---|:---|:---|
| **1. Executive Dashboard** | Ringkasan jumlah total unit, status umum (Ready, Breakdown, Standby), Map Leaflet lokasi unit. | Daftar WO Emergency High Priority, estimasi downtime kritis, & nilai finansial kerugian. | Administrator, Equipment Manager, Maintenance Planner |
| **2. Monitoring Unit** | Daftar unit non-ready, lokasi perbaikan, & kategori alat. | Alasan kronis breakdown, estimasi tanggal selesai RTW, PIC Mekanik. | Equipment Manager, Maintenance Planner, Inspector K3L |
| **3. Master Asset** | Profil unit, tipe, foto, lokasi terkini, status operasi, & riwayat mutasi BAST. | Harga beli unit, nilai buku akuntansi, total perbaikan kumulatif, & harga pasaran (valuasi). | Asset Manager, Equipment Manager, Administrator |
| **4. Inspeksi & P2H** | Hasil tes akhir P2H (PASS/FAIL), tanggal inspeksi, HM/KM aktual. | Temuan kritis teknis, identitas inspector, & pembuatan WO otomatis. | Inspector K3L, Maintenance Planner, Mekanik |
| **5. Work Order** | Status papan Kanban (Open, In Progress, Closed), nomor WO, deskripsi isu. | Log jam lembur SPL mekanik, alokasi jam kerja internal, & verifikasi supervisor. | Maintenance Planner, Mekanik Senior, Equipment Mgr |
| **6. Preventive Maint.** | Jadwal service (250h-10000h), SMR aktual, & status ketersediaan filter/spare part. | Estimasi biaya PM, garansi diler, & catatan internal planner. | Maintenance Planner, Equipment Manager, Logistic Head |
| **7. Spare Part & Logistik**| Katalog *part number*, nama barang, & stok tersedia di gudang Yard. | Harga beli satuan (unit cost), status SPB nilai tinggi, & vendor supplier. | Logistic Head, Equipment Manager, Maintenance Planner |
| **8. Condition Monitoring**| Kondisi ban (Hijau/Kuning/Merah), voltase aki, & pemakaian cutting bit. | Biaya penggantian ban/komponen, rotasi rekomendasi, & audit klaim garansi. | Maintenance Planner, Equipment Mgr, Asset Mgr |
| **9. Fuel Management** | Total liter pengisian solar, tanggal refuel, & konsumsi rata-rata LPH unit. | Deteksi anomali pemborosan BBM, indikasi pencurian, & kalkulasi kerugian Rp. | Equipment Manager, Logistic Head, Inspector K3L |
| **10. Produktivitas** | SMR Komtrax, jam kerja aktual, *Utilization Rate %*, & unit standby. | Indikator *Idling Ratio >50%*, estimasi liter solar terbuang, & nilai finansial rugi. | Equipment Manager, Asset Manager, Administrator |
| **11. Biaya** | *TIDAK ADA (100% Sensitif & Rahasia Perusahaan)* | Grafik Budget vs Actual bulanan, *Expense Breakdown*, & Valuasi Harga Pasaran Unit. | **HANYA**: Equipment Manager, Asset Manager, Administrator |
| **12. People & KPI** | Leaderboard jam kerja mekanik (total jam), tren kinerja mingguan. | Scorecard Head (54/100), Evaluasi Kompetensi P. Martin, jam lembur SPL Rp, & audit SDM. | **HANYA**: Equipment Manager, HRD Manager, Administrator |
| **13. HSE / Accident** | Status keteruncian unit (`ACCIDENT_HOLD`), tanggal insiden, lokasi kejadian. | Kronologi detail kecelakaan, identitas driver, estimasi biaya kerusakan, & tombol Rilis Hold. | Inspector K3L, Equipment Mgr, Asset Mgr, Admin |
| **14. Laporan & Form** | Template form laporan standar & pencetakan PDF ringkasan operasional. | Export data sensitif biaya, laporan SDM lembur, & audit log transaksi. | Berdasarkan hak akses masing-masing modul sumber |
| **15. Approval Inbox** | *TIDAK ADA (100% Otorisasi Eksekutif)* | Kotak masuk persetujuan SPB, Work Order Closing, Unit Release Hold, & Mutasi BAST. | **HANYA**: Equipment Mgr, Asset Mgr, Logistic Head, HRD |
| **16. Pengaturan System**| *TIDAK ADA (100% System Control)* | Manajemen Akun User, Password Reset, Hak Akses RBAC, & Master Lokasi. | **HANYA**: Administrator Utama |

---

### 10.2 Matriks Kewenangan Aksi Role per 16 Menu Navigasi `dashboard.html`

> **Keterangan Kode Akses**: `R` = Read/View, `C` = Create, `U` = Update/Edit, `A` = Approve/Release, `O` = Admin Override/Delete, `-` = No Access (Akses Ditolak/Menu Tersembunyi).

#### Bagian 1: Menu 1 s.d. 8 (`dashboard.html`)

| User Role (10 Roles) | 1. Executive Dashboard | 2. Monitoring Unit | 3. Master Asset | 4. Inspeksi & P2H | 5. Work Order | 6. Preventive Maint. | 7. Spare Part & Logistik | 8. Condition Monitoring |
|:---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **1. Administrator** | R C U O | R C U O | R C U O | R C U O | R C U O | R C U O | R C U O | R C U O |
| **2. Equipment Manager** | R U A | R U A | R U A | R U A | R C U A | R C U A | R A | R U A |
| **3. Maintenance Planner** | R U | R U | R U | R U | R C U A | R C U A | R C U | R C U |
| **4. Mekanik Senior** | R | R | R | R C | R C U | R | R C | R |
| **5. Mekanik Junior / Helper**| R | R | R | R C | R U | R | - | - |
| **6. Welder / Fabrikator** | R | R | R | R C | R U | - | - | - |
| **7. Inspector K3L / Safety**| R | R U | R | R C U A | R C | - | - | R C U |
| **8. Logistic Head** | R | R | R | - | R | R | R C U A | R |
| **9. HRD Manager** | R | - | - | - | - | - | - | - |
| **10. Asset Manager** | R | R U A | R C U A | - | R | - | - | R U |

#### Bagian 2: Menu 9 s.d. 16 (`dashboard.html`)

| User Role (10 Roles) | 9. Fuel Management | 10. Produktivitas | 11. Biaya | 12. People & KPI | 13. HSE / Accident | 14. Laporan & Form | 15. Approval Inbox | 16. Pengaturan System |
|:---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **1. Administrator** | R C U O | R C U O | R C U O | R C U O | R C U O | R C U O | R A O | R C U O |
| **2. Equipment Manager** | R U A | R U A | R U A | R C U A | R C U A | R C U | R A | - |
| **3. Maintenance Planner** | R U | R U | - | R U | R | R C U | R | - |
| **4. Mekanik Senior** | R | R | - | R | R | R | - | - |
| **5. Mekanik Junior / Helper**| - | R | - | R | - | R | - | - |
| **6. Welder / Fabrikator** | - | R | - | R | - | R | - | - |
| **7. Inspector K3L / Safety**| R C U | R | - | - | R C U A | R C U | - | - |
| **8. Logistic Head** | R C U | R | - | - | - | R C U | R A | - |
| **9. HRD Manager** | - | - | - | R C U A | - | R C U | R A | - |
| **10. Asset Manager** | R | R U A | R C U A | R | R A | R C U | R A | - |




