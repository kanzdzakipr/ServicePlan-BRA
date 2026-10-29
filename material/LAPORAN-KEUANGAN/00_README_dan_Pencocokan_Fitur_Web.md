# Dokumentasi Laporan Keuangan Divisi Aset & Equipment & Pencocokan Fitur Web

> **Sumber Dokumen**: `Equipment - Expenses & Report.xlsx` (Google Spreadsheet ID: `14MvzJ1nA5la69VtuI3cA3sSxf8o2PqNI3OsKINIENWg`)  
> **Entitas**: PT Bina Rekayasa Anugrah — Divisi Asset & Equipment  
> **Periode Data**: Tahun 2021 s.d. **31 Oktober 2026** (26 Sheet / Tabulasi)  
> **Lokasi Folder Dokumentasi**: `material/LAPORAN-KEUANGAN/`

---

## 1. Daftar Dokumen Markdown dalam Folder `material/LAPORAN-KEUANGAN/`

| No | Nama File Markdown | Sheet Sumber di Excel | Ringkasan Isi Dokumen |
| :---: | :--- | :--- | :--- |
| **00** | [`00_README_dan_Pencocokan_Fitur_Web.md`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/LAPORAN-KEUANGAN/00_README_dan_Pencocokan_Fitur_Web.md) | Seluruh 26 Sheet | Indeks seluruh 26 sheet laporan keuangan serta **Matriks Pencocokan (Mapping) terhadap Fitur UI & Tabel Database di Web ServicePlan-BRA**. |
| **01** | [`01_Monitoring_Progress_Pendapatan_vs_Pengeluaran_Agu_Okt_2026.md`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/LAPORAN-KEUANGAN/01_Monitoring_Progress_Pendapatan_vs_Pengeluaran_Agu_Okt_2026.md) | `Monitoring Progress` | Laporan Laba-Rugi Operasional bulanan **Agustus 2026**, **September 2026 (Skema Cicilan 3 Th & 5 Th)**, dan **Oktober 2026** (Pendapatan per Proyek vs Gaji Mekanik, Payroll HO, DP, Cicilan Leasing/Bank, dan Rincian Biaya Maintenance). |
| **02** | [`02_LPJ_Bulanan_Budget_vs_Realisasi_2026.md`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/LAPORAN-KEUANGAN/02_LPJ_Bulanan_Budget_vs_Realisasi_2026.md) | `LPJ BULANAN DIV ASET AND EQ 202`, `Progress`, `PENGELUARAN EQUIPMENT DURI 2026` | Tabel Budget Planning 2026 (`Rp 773,88 Jt/bln` / `Rp 9,286 M/thn`), Realisasi Cash Bulanan `Jan–Jun 2026`, Rencana Biaya per 12 Kategori, serta Rincian LPJ Equipment Duri `Jan–Apr 2026`. |
| **03** | [`03_Dashboard_Equipment_Rekap_Per_Alat_dan_Sewa_Excavator.md`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/LAPORAN-KEUANGAN/03_Dashboard_Equipment_Rekap_Per_Alat_dan_Sewa_Excavator.md) | `DASHBOARD EQUIPMENT`, `REKAP`, `Sewa Excavator`, `Rekap All 2026` | Top 5 Unit Biaya Tertinggi per Kategori Alat, Top 10 Rate Biaya Tertinggi, Top 5 Sparepart Paling Sering Dibeli, Rekapitulasi Akumulasi Pengeluaran Seluruh Armada (`Rp 16,257 M`), dan 6 Kontrak Sewa Alat Berat Vendor Eksternal. |
| **04** | [`04_Ranking_Pengeluaran_111_Dump_Truck_dan_Valuasi_Jual.md`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/LAPORAN-KEUANGAN/04_Ranking_Pengeluaran_111_Dump_Truck_dan_Valuasi_Jual.md) | `Rank unit dt`, `10 DT REKON` | Tabel lengkap **111 Unit Dump Truck** (2021 s.d. September 2026: Grand Total `Rp 8,525 M`, Ban `Rp 2,618 M`, Non-Ban `Rp 5,906 M`, Khusus 2026 `Rp 2,934 M`), Evaluasi 4 DT Kandidat Pelepasan, dan Valuasi 7 Unit Utama. |
| **05** | [`05_Buku_Besar_Cashout_dan_Perbandingan_Harga_Vendor.md`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/LAPORAN-KEUANGAN/05_Buku_Besar_Cashout_dan_Perbandingan_Harga_Vendor.md) | `Cashout`, `DASHBOARD`, `PERBANDINGAN HARGA`, `Debt`, `Additional` | Ringkasan Buku Besar 28.320 transaksi `Cashout` (`Rp 20,757 M`), Katalog Perbandingan Harga Vendor Ban & Pelumas/Oli lintas pulau, serta struktur Utang (`Debt`) & Kalkulasi Bunga (`Additional`). |

---

## 2. Peta Struktur 26 Sheet pada `Equipment - Expenses & Report.xlsx`

| No | Nama Sheet | Status | Dimensi (Baris) | Fungsi Utama Sheet |
| :---: | :--- | :---: | :---: | :--- |
| 1 | `Monitoring Progress` | Visible | 183 baris | Laporan Eksekutif Pendapatan Proyek (A) vs Pengeluaran Gaji, DP, Cicilan Leasing & Maintenance (B) periode **Agustus, September (3Th & 5Th), dan Oktober 2026**. |
| 2 | `Rekap All 2026` | Visible | 335 baris | Matriks realisasi pengeluaran bulanan per unit seluruh armada (Vibroroller, Grader, Dozer, Excavator, DT, LV, Trado) periode **Januari s.d. Oktober 2026**. |
| 3 | `Rekap All 2024-2025` | Visible | 313 baris | Matriks historis pengeluaran bulanan per unit periode **Januari 2024 s.d. Desember 2025**. |
| 4 | `Cashout` | Visible | 28.320 baris | Buku besar (General Ledger) seluruh transaksi pengeluaran kas aktual (Sparepart, Consumable, Operasional, Overhead, Maintenance, dll.) beserta tanggal, vendor, kode unit, qty, dan harga. |
| 5 | `ALL` | Visible | 298 baris | Master daftar seluruh aset/unit beserta anggaran biaya rencana lama vs baru per bulan (2024–2026). |
| 6 | `10 DT REKON` | Visible | 46 baris | Analisis valuasi nilai buku, harga beli, pendapatan, biaya repair, harga pasaran, dan penawaran buyer untuk kandidat unit jual/rekondisi (`DT 01`, `DT 02`, `DT 15`, `DT 16`, `DT 30`, `DT 32`, `Exca 04`). |
| 7 | `Rank unit dt` | Visible | 188 baris | Ranking pengeluaran **111 Unit Dump Truck** dari terbesar ke terkecil (2021 s.d. September 2026), dipisahkan antara biaya Ban dan Non-Ban, plus profil evaluasi 4 DT kandidat jual. |
| 8 | `Sewa Excavator` | Visible | 9 baris | Rekap 6 kontrak sewa alat berat vendor eksternal (Excavator, Vibro, Bulldozer, Grader) di proyek WUR, Lahat, dan Morowali beserta pembayaran Termin 1 & 2. |
| 9 | `Copy of Rekap All 2026` | Visible | 328 baris | Salinan kerja dari `Rekap All 2026`. |
| 10 | `Pivot 1` | Visible | 317 baris | Tabel pivot agregasi pengeluaran per kode unit dari sheet `Cashout`. |
| 11 | `Copy of Pivot Table 1` | Visible | 169 baris | Salinan tabel pivot agregasi biaya per unit. |
| 12 | `PLANNING VS REALISASI 2026` | Visible | 303 baris | Perbandingan Rencana Biaya Service/Perbaikan vs Realisasi Bulanan per unit tahun 2026. |
| 13 | `DASHBOARD EQUIPMENT` | Visible | 33 baris | Ringkasan Top 5 Unit dengan Biaya Maintenance Tertinggi per kategori alat (2024–2026), Top 10 Unit Rate Biaya Tertinggi, dan Top 5 Sparepart Paling Sering Dibeli. |
| 14 | `Corrective  Perbaikan (Sparepar` | Visible | 302 baris | Rincian biaya *Corrective Maintenance (Perbaikan)* per unit per bulan (2024–2026). |
| 15 | `Preventive  Service & Maintenan` | Visible | 304 baris | Rincian biaya *Preventive Maintenance (Service Berkala)* per unit per bulan (2024–2026). |
| 16 | `BAN` | Visible | 250 baris | Rincian pengeluaran penggantian Ban per unit per bulan beserta perhitungan amortisasi 12 bulan. |
| 17 | `PENGELUARAN EQUIPMENT DURI 2026` | Visible | 31 baris | Rekap LPJ Pengeluaran Equipment Duri (`Januari–April 2026`) per pos Corrective, Preventive, Ban (Amortisasi vs Aktual), SDM Mekanik, dan Operasional (STNK/SILO/KIR). |
| 18 | `LPJ BULANAN DIV ASET AND EQ 202` | Visible | 36 baris | Rekap Budget Planning 2026 (`Rp 773.876.682,5/bln`) vs Pembayaran Cash (`Jan–Jun 2026`) serta rincian alokasi rencana biaya tahunan (`Rp 9.286.520.190,37`) ke 12 komponen biaya. |
| 19 | `Sheet52` | Visible | 26 baris | Tabel bantu rekapitulasi. |
| 20 | `PERBANDINGAN HARGA ` | Visible | 327 baris | Katalog komparasi harga & termin pembayaran (Cash vs Tempo 30 Hari) vendor **Ban** (`10.00R20`, `11.00R20`, `12.00R20`) dan **Pelumas/Oli** (`SAE 15W40`, `Hydraulic`, `Transmisi`, `Gardan`) lintas wilayah. |
| 21 | `Calendar` | Visible | 46 baris | Kalender kerja referensi. |
| 22 | `REKAP` | Visible | 72 baris | Rekapitulasi total pengeluaran historis per unit untuk 5 kelompok armada (`Excavator`, `Dozer & Grader`, `Compactor`, `Dump Truck`, `Kendaraan Operasional`) + `Non-Equipment` (**Grand Total `Rp 16.257.857.996`**). |
| 23 | `DASHBOARD` | Visible | 28.312 baris | Mirror analitik dari buku besar `Cashout` dengan ringkasan per kategori biaya di baris atas. |
| 24 | `Debt` | Visible | 276 baris | Template pelacakan utang vendor (Accounts Payable) & jadwal pembayaran bertahap (Payment 1 s.d. Payment 7). |
| 25 | `Progress` | Hidden | 64 baris | Kertas kerja perhitungan MC (Monthly Certificate) & Opname proyek (`Main Road`, PPN 10%, PPh 3%, Diskonto 2,375%). |
| 26 | `Additional` | Visible | 34 baris | Kalkulasi beban bunga modal kerja (`1,4% x Expense x Lama Bunga` vs Kumulatif Pencairan). |

---

## 3. Pencocokan (Mapping) Data Laporan Keuangan dengan Fitur yang Ada di Web

Berikut adalah pencocokan menyeluruh antara data pada `Equipment - Expenses & Report.xlsx` dengan fitur dan tabel database yang saat ini berjalan pada aplikasi web **ServicePlan-BRA** ([`dashboard.view.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/dashboard.view.php#L1550-L1912), [`api/costs.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/costs.php), [`core/CostLedger.php`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/core/CostLedger.php), dan [`scripts/dashboard.js`](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.js)):

### A. Modul Biaya & Cost Control Armada (`#view-biaya`)

| Komponen Fitur di Web (`dashboard.view.php`) | Tabel DB / Endpoint Terkait | Kondisi Data Saat Ini di Web | Data Terbaru di `Equipment - Expenses & Report.xlsx` | Status Kesesuaian & Rekomendasi |
| :--- | :--- | :--- | :--- | :--- |
| **1. KPI Strip Atas (6 Kartu Metrik)**<br>`#kpiCostBudget`, `#kpiCostActual`, `#kpiCostDev`, `#kpiCostBookValue`, `#kpiCostMarketValue`, `#kpiCostMargin` | `cost_financial_monthly` & JS State | Menampilkan:<br>- Budget Berjalan: `Rp 2.960 Jt`<br>- Pengeluaran Aktual: `Rp 12.144 Jt`<br>- Deviasi: `+310.2% (Over)`<br>- Nilai Buku (7 Unit): `Rp 3.347 Jt`<br>- Penawaran Buyer: `Rp 4.070 Jt`<br>- Net Gain BRA: `+Rp 610 Jt` | - **Budget Planning 2026** (`LPJ BULANAN`): **`Rp 773.876.682,5 / bulan`** (`Rp 9.286.520.190 / tahun`)<br>- **Realisasi Cash Jan–Jun 2026**: `Rp 5.387.312.204`<br>- **Pengeluaran Maintenance Agu–Okt 2026** (`Monitoring Progress`): Agu `Rp 1.628,64 Jt`, Sep `Rp 1.605,53 Jt`, Okt `Rp 1.010,49 Jt`<br>- **Valuasi 7 Unit**: Nilai Buku `Rp 3.347,12 Jt`, Buyer `Rp 4.070 Jt`, Net Gain `+Rp 611,27 Jt` | **Valuasi 7 Unit sudah cocok**, namun **KPI Budget & Aktual Bulanan 2026 perlu di-update** agar mengacu pada Budget resmi `Rp 773,88 Jt/bln` dan realisasi terbaru s.d. Oktober 2026. |
| **2. Grafik Bar `Budget vs Actual Cost` (`#costChartContainer`) & Tab 2: `LPJ Bulanan Budget vs Realisasi` (`#costTabSecLpj`)** | Tabel `cost_financial_monthly` (saat ini berisi 25 baris campuran data awal 2026 & estimasi lama Mei–Des) | Menampilkan bar chart Mei–Des 2026 dengan angka lama (`Mei Rp 393,7 Jt`, `Jun Rp 553,4 Jt`, `Jul Rp 5.425 Jt`, dst.) dan baru memiliki rincian LPJ bulan `Januari 2026` (`Rp 680,27 Jt`). | Sheet `LPJ BULANAN DIV ASET AND EQ 202` memiliki angka **Realisasi Pembayaran Cash 2026** yang sudah diperbarui:<br>- **Jan 2026**: Budget `Rp 773,88 Jt` vs Cash `Rp 801.938.719`<br>- **Feb 2026**: Budget `Rp 773,88 Jt` vs Cash `Rp 810.292.794`<br>- **Mar 2026**: Budget `Rp 773,88 Jt` vs Cash `Rp 849.084.485`<br>- **Apr 2026**: Budget `Rp 773,88 Jt` vs Cash `Rp 925.757.929`<br>- **Mei 2026**: Budget `Rp 773,88 Jt` vs Cash `Rp 1.222.546.323`<br>- **Jun 2026**: Budget `Rp 773,88 Jt` vs Cash `Rp 777.691.954`<br>Ditambah realisasi `Monitoring Progress` **Agu (`Rp 1.628,64 Jt`)**, **Sep (`Rp 1.605,53 Jt`)**, dan **Okt (`Rp 1.010,49 Jt`)**. | **Perlu Sinkronisasi Database (`cost_financial_monthly`)**: Angka Jan–Jun 2026 dan Agu–Okt 2026 di tabel `cost_financial_monthly` belum mencerminkan angka terbaru dari sheet `LPJ BULANAN` & `Monitoring Progress`. |
| **3. Panel `Ringkasan Alokasi LPJ Equipment Duri` (`#costSummaryPanel`)** | UI Panel Samping `#view-biaya` | Menampilkan 3 progress bar statis (`Corrective Repair Rp 326.2 Jt`, `Preventive Maintenance Rp 128.5 Jt`, `Pemeliharaan LV Rp 85.0 Jt`). | Sheet `LPJ BULANAN DIV ASET AND EQ 202` memuat **12 Komponen Rencana Biaya 2026** (`Sparepart Corrective` Rp 4,24 M / 45,70%, `Sparepart Service Berkala` Rp 1,31 M / 14,15%, `Ban` Rp 1,10 M / 11,93%, `STNK & Pajak` Rp 910,23 Jt / 9,80%, `Non-Equipment` Rp 373,14 Jt / 4,02%, `Pelumas` Rp 346,28 Jt / 3,73%, `KIR` Rp 284,25 Jt, `SILO` Rp 250,20 Jt, dll.) serta realisasi **Oktober 2026** per kategori (`Stok Sparepart/Ban` Rp 359,45 Jt, `Ban` Rp 193,57 Jt, `Sparepart` Rp 168,92 Jt, `Adm & Pajak` Rp 133,73 Jt, `Filter` Rp 36,34 Jt, dll.). | **Dapat Diperkaya**: Panel `#costSummaryPanel` dapat diperbarui dengan proporsi alokasi 2026 dan rincian realisasi terbaru s.d. Oktober 2026. |
| **4. Tab 1: `Tabulasi Valuasi & Harga Jual Unit (7 Unit)` (`#costTabSecValuation`)** | `#costValuationTableBody` | Menampilkan **7 Unit Utama** (`DT 01`, `DT 02`, `DT 15`, `DT 16`, `DT 30`, `DT 32`, `Exca 04`) dengan total biaya repair `Rp 2.249.290.290`. | - Di Sheet `Rank unit dt` (s.d. September 2026), **biaya repair kumulatif 7 unit tersebut telah bertambah menjadi `Rp 2.481.185.616`** (misal `DT 01` naik dari `Rp 381,91 Jt` menjadi `Rp 438,02 Jt`; `Exca 04` menjadi `Rp 461,49 Jt`).<br>- Selain itu, Sheet `Rank unit dt` memiliki **Ranking Lengkap 111 Unit Dump Truck** (`Rp 8.525.060.907`) dan Sheet `REKAP` memiliki rekap seluruh alat berat (`Rp 16.257.857.996`). | **Perlu Update Nilai Biaya Repair Kumulatif** pada ke-7 unit s.d. September 2026, serta sangat potensial menambahkan filter/tampilan **Ranking 111 Unit DT & Top 5 Unit per Kategori Alat (`DASHBOARD EQUIPMENT`)**. |
| **5. Tab 3: `Register PO & Pengeluaran Part / Buku Besar Biaya (Cost Ledger)` (`#costTabSecPo`)** | Tabel `asset_cost_transactions` (`api/costs.php`, `core/CostLedger.php`) | Saat ini berisi **65 baris transaksi terverifikasi** dengan total nilai `Rp 358.305.450` (rentang tanggal `2026-01-31` s.d. `2026-08-09`). | - Sheet `Rekap All 2026` memuat rekapitulasi pengeluaran bulanan per unit untuk **Januari s.d. Oktober 2026**.<br>- Sheet `Cashout` & `DASHBOARD` memuat **28.320 baris transaksi historis** senilai **`Rp 20.757.421.479`** (`Sparepart` Rp 10,52 M, `Consumable` Rp 4,04 M, `Biaya Lain-lain` Rp 3,25 M, `Operasional` Rp 1,22 M, `Overhead` Rp 779,88 Jt, `Material Lainnya` Rp 325,20 Jt, `Aksesoris` Rp 324,46 Jt, `Maintenance` Rp 268,47 Jt). | **Perlu Injeksi Transaksi Periode Agustus–Oktober 2026**: Transaksi di `asset_cost_transactions` berhenti pada `9 Agustus 2026`, sedangkan di Excel sudah tersedia realisasi Agustus, September, dan Oktober 2026. |
| **6. Tab 4: `Rencana vs Realisasi PM` (`#costTabSecPmComp`)** | `#costPmCompTableBody` | Menampilkan perbandingan Rencana vs Realisasi PM periode `Jan–Feb 2026`. | Sheet `PLANNING VS REALISASI 2026` dan `Preventive Service & Maintenance` memuat matriks Rencana vs Realisasi per unit untuk tahun **2026 (s.d. Oktober 2026)**. | **Dapat Diperluas** hingga periode berjalan (Agustus–Oktober 2026). |

---

### B. Fitur / Data Penting di Excel yang Belum Memiliki Panel Khusus di Web (Peluang Pengembangan)

1. **Monitoring Laba-Rugi Operasional Proyek Bulanan (`Sheet Monitoring Progress` — Agu, Sep 3Th vs 5Th, Okt 2026)**:
   - Di Excel, ini adalah **Sheet Pertama (Utama)** yang memantau **Pendapatan per Proyek (A)** (`PHR WUR EW`, `PHR Road & Well Pad`, `TPM Bahodopi`, `Tiga Putri Lahat`, `PEP Adera`, `PHR Matting Board`, `PHR ASR`, dll.) dibandingkan dengan **Total Pengeluaran (B)** (`Gaji Mekanik`, `Payroll HO`, `DP Alat Baru`, `Cicilan Leasing/Bank 3Th vs 5Th`, dan `Biaya Maintenance`), menghasilkan **Profit/Loss (A - B)** dan **% Margin**.
   - **Di Web**: Belum ada sub-tab khusus di `#view-biaya` yang menampilkan komparasi **Pendapatan Proyek vs Beban Cicilan Leasing + Maintenance (Laba/Rugi Operasional Divisi Equipment)**.
2. **Daftar Kontrak Sewa Alat Berat Vendor Eksternal (`Sheet Sewa Excavator`)**:
   - Mencatat 6 kontrak sewa unit eksternal (`Excavator PC 200`, `Vibro Roller`, `Bulldozer D65P`, `Motor Grader GD 511A`) di proyek `WUR`, `Lahat`, dan `Morowali` beserta nilai bayar Termin 1 (`Rp 217.500.000`) & Termin 2 (`Rp 25.000.000`).
   - **Di Web**: Dapat diintegrasikan ke `#view-biaya` (kategori `External`) atau `#view-kontrak`.
3. **Katalog Perbandingan Harga Vendor Ban & Pelumas (`Sheet PERBANDINGAN HARGA`)**:
   - Mencatat komparasi harga dan termin pembayaran (Cash vs Tempo 30 Hari) dari belasan vendor Ban (`10.00R20`, `11.00R20`, `12.00R20`) dan Oli (`SAE 15W40`, `Hydraulic`, `Transmisi`, `Gardan`) di Pekanbaru, Palembang, Jakarta, Kendari, Maluku, Kalimantan, dan Sulawesi.
   - **Di Web**: Sangat cocok diintegrasikan sebagai referensi harga vendor pada modul **Inventaris & PO (`#view-inventaris` / `#view-biaya`)**.

