# Monitoring Progress: Pendapatan Proyek vs Pengeluaran (Agustus – Oktober 2026)

> **Sumber Data**: `Equipment - Expenses & Report.xlsx` — Sheet `Monitoring Progress`  
> **Cakupan**: Laporan Laba-Rugi Kas (Cashflow Profit/Loss) Divisi Equipment PT Bina Rekayasa Anugrah untuk periode **Agustus 2026**, **September 2026 (Simulasi Cicilan 3 Tahun vs Restrukturisasi 5 Tahun)**, dan **Oktober 2026**.  
> **Keterkaitan Fitur Web**: Modul Biaya & Cost Control Armada (`#view-biaya`) & Modul Kontrak Proyek (`#view-kontrak`).

---

## 1. Ringkasan Eksekutif Laba-Rugi Operasional (Agustus – Oktober 2026)

| Indikator Keuangan | Agustus 2026 (Aktual) | September 2026 (Skema Cicilan 3 Th) | September 2026 (Skema Cicilan 5 Th) | Oktober 2026 (Realisasi Pengeluaran) |
| :--- | ---: | ---: | ---: | ---: |
| **Periode Operasional Alat** | 01 Agu – 31 Agu 2026 | 01 Sep – 30 Sep 2026 | 01 Sep – 30 Sep 2026 | 01 Okt – 31 Okt 2026 |
| **Periode Cut-Off Gaji** | 21 Jul – 20 Agu 2026 | 21 Agu – 20 Sep 2026 | 21 Agu – 20 Sep 2026 | 21 Sep – 20 Okt 2026 |
| **A. Total Pendapatan (Versi Project)** | **Rp 5.222.184.825** | **Rp 6.226.843.327** | **Rp 6.226.843.327** | *Menunggu Rekap Invoice Okt* |
| **B. Total Pengeluaran** | **Rp 9.426.357.179** | **Rp 7.604.625.536** | **Rp 5.325.508.644** | **Rp 4.700.169.191** |
| ├─ 1. Gaji Pekerja Mekanik | Rp 133.481.419 | Rp 139.743.692 | Rp 139.743.692 | *Termasuk di Ops/Rekap* |
| ├─ 2. Payroll HO (Tim Equipment) | Rp 41.655.300 | Rp 41.555.300 | Rp 41.555.300 | *Termasuk di Ops/Rekap* |
| ├─ 3. Down Payment (DP) Alat Baru | Rp 2.486.190.000 | Rp 120.000.000 | Rp 120.000.000 | Rp 271.000.000 |
| ├─ 4. Cicilan Leasing & Bank | Rp 5.136.386.229 | Rp 5.697.792.229 | Rp 3.418.675.337 | Rp 3.418.675.337 |
| └─ 5. Biaya Maintenance & Operasional | Rp 1.628.644.231 | Rp 1.605.534.315 | Rp 1.605.534.315 | Rp 1.010.493.854 |
| **Profit / Defisit (A - B)** | **-Rp 4.204.172.354** | **-Rp 1.377.782.209** | **+Rp 901.334.683** | **-** |
| **Margin Profitabilitas (%)** | **-80,51%** | **-22,13%** | **+14,47%** | **-** |

> **Temuan Kunci Manajemen**:  
> Pada bulan **September 2026**, dengan skema cicilan normal 3 tahun (`Rp 5,697 M/bulan`), divisi mengalami defisit kas **`-Rp 1.377.782.209` (`-22,13%`)**. Namun dengan **Skema Restrukturisasi Cicilan 5 Tahun** (`Rp 3,418 M/bulan`), arus kas berbalik menjadi **Surplus/Profit `+Rp 901.334.683` (`+14,47%`)**.

---

## 2. Rincian Pendapatan per Proyek (Pos A — Agustus vs September 2026)

| No | Nama Proyek / Lokasi Kontrak | Pendapatan Agustus 2026 (Versi Project) | Porsi Agu (%) | Pendapatan September 2026 (Versi Project) | Porsi Sep (%) | Perubahan (MoM) |
| :---: | :--- | ---: | ---: | ---: | ---: | ---: |
| 1 | **PHR WUR EW** | Rp 3.061.446.774 | 58,62% | Rp 3.055.708.333 | 49,07% | -Rp 5.738.441 |
| 2 | **PHR ROAD AND WELL PAD** | Rp 403.516.667 | 7,73% | Rp 1.088.781.023 | 17,49% | **+Rp 685.264.356** |
| 3 | **TPM - Bahodopi (Morowali)** | Rp 0 | 0,00% | Rp 644.400.000 | 10,35% | **+Rp 644.400.000** |
| 4 | **Tiga Putri - Lahat** | Rp 936.000.000 | 17,92% | Rp 597.433.333 | 9,59% | -Rp 338.566.667 |
| 5 | **PEP Adera** | Rp 369.685.921 | 7,08% | Rp 352.124.538 | 5,65% | -Rp 17.561.383 |
| 6 | **PHR Matting Board** | Rp 298.599.979 | 5,72% | Rp 295.000.000 | 4,74% | -Rp 3.599.979 |
| 7 | **EQUIPMENT (Sewa Eksternal / Umum)** | Rp 56.500.000 | 1,08% | Rp 59.498.100 | 0,96% | +Rp 2.998.100 |
| 8 | **PHR ASR** | Rp 37.000.000 | 0,71% | Rp 53.898.000 | 0,87% | +Rp 16.898.000 |
| 9 | **JUMARNO** | Rp 37.500.000 | 0,72% | Rp 50.000.000 | 0,80% | +Rp 12.500.000 |
| 10 | **PEKANBARU** | Rp 0 | 0,00% | Rp 30.000.000 | 0,48% | +Rp 30.000.000 |
| 11 | **PANGKALAN** | Rp 21.935.484 | 0,42% | Rp 0 | 0,00% | -Rp 21.935.484 |
| **-** | **TOTAL PENDAPATAN (A)** | **Rp 5.222.184.825** | **100,00%** | **Rp 6.226.843.327** | **100,00%** | **+Rp 1.004.658.502 (+19,24%)** |

> **Catatan Rekonsiliasi**: Pada bulan Agustus 2026 tercatat pula *Pendapatan Versi Equipment* sebesar `Rp 4.179.961.979`, sedangkan pada bulan September 2026 tercatat *Pendapatan Versi Equipment* sebesar `Rp 1.630.522.538` (belum termasuk seluruh berita acara proyek). Angka resmi yang digunakan dalam rumus Profit `(A - B)` di Excel adalah **Pendapatan Versi Project**.

---

## 3. Rincian Pengeluaran (Pos B) per Periode

### A. Rincian Gaji Pekerja Mekanik per Site (Agustus vs September 2026)

| No | Lokasi Site / Proyek | Gaji Mekanik Agustus 2026 (21 Jul – 20 Agu) | Gaji Mekanik September 2026 (21 Agu – 20 Sep) |
| :---: | :--- | ---: | ---: |
| 1 | **Yard Duri (KM 12)** | Rp 106.798.774 | Rp 116.890.067 |
| 2 | **Prabumulih (PEP Adera)** | Rp 11.124.205 | Rp 9.373.625 |
| 3 | **Lahat (Tiga Putri)** | Rp 7.829.277 | Rp 7.480.000 |
| 4 | **Bahodopi (Morowali)** | Rp 7.729.163 | Rp 6.000.000 |
| **-** | **TOTAL GAJI PEKERJA MEKANIK** | **Rp 133.481.419** | **Rp 139.743.692** |
| **-** | **TOTAL PAYROLL HO (EQUIPMENT)** | **Rp 41.655.300** | **Rp 41.555.300** |

---

### B. Rincian Down Payment (DP) Pembelian Unit Baru (Agustus – Oktober 2026)

| Periode | Lembaga Pembiayaan / Vendor | Deskripsi Unit | Nilai DP (Rp) |
| :--- | :--- | :--- | ---: |
| **Agustus 2026** | **SHANTUI** | DP Pembelian Unit Baru Shantui | Rp 530.190.000 |
| **Agustus 2026** | **UNITED TRACTOR** | DP Pembelian Unit Baru United Tractors | Rp 1.016.000.000 |
| **Agustus 2026** | **KOBEXINDO** | DP Pembelian Unit Baru Kobexindo | Rp 940.000.000 |
| *Subtotal Agu 2026* | | **Total DP Agustus 2026** | **Rp 2.486.190.000** |
| **September 2026** | **MITSUI** | DP Excavator & Motor Grader (Tahap 1) | **Rp 120.000.000** |
| **Oktober 2026** | **MITSUI** | Pelunasan DP Excavator & Motor Grader | **Rp 271.000.000** |

---

### C. Rincian Kewajiban Cicilan Leasing & Bank per Bulan (Agustus – Oktober 2026)

| No | Lembaga Leasing / Bank | Cicilan Agustus 2026 (Aktual) | Cicilan September 2026 (Skema 3 Tahun) | Cicilan September & Oktober 2026 (Skema 5 Tahun) |
| :---: | :--- | ---: | ---: | ---: |
| 1 | **ORIX** | Rp 1.158.131.000 | Rp 1.158.131.000 | Rp 694.878.600 |
| 2 | **CSUL (Chandra Sakti Utama Leasing)** | Rp 1.076.969.200 | Rp 1.076.969.200 | Rp 646.181.520 |
| 3 | **BRI** | Rp 963.173.442 | Rp 963.173.442 | Rp 577.904.065 |
| 4 | **MITSUI** | Rp 352.971.000 | Rp 914.377.000 | Rp 548.626.200 |
| 5 | **BFI Finance** | Rp 695.072.500 | Rp 695.072.500 | Rp 417.043.500 |
| 6 | **MANDIRI** | Rp 441.552.712 | Rp 441.552.712 | Rp 264.931.627 |
| 7 | **MNC Leasing** | Rp 216.178.400 | Rp 216.178.400 | Rp 129.707.040 |
| 8 | **HEXINDO** | Rp 98.402.000 | Rp 98.402.000 | Rp 59.041.200 |
| 9 | **SBF (Surya Artha Nusantara / SBF)** | Rp 71.751.600 | Rp 71.751.600 | Rp 43.050.960 |
| 10 | **BCA** | Rp 41.326.875 | Rp 41.326.875 | Rp 24.796.125 |
| 11 | **TRAKNUS (Traktor Nusantara)** | Rp 20.857.500 | Rp 20.857.500 | Rp 12.514.500 |
| **-** | **TOTAL CICILAN PER BULAN** | **Rp 5.136.386.229** | **Rp 5.697.792.229** | **Rp 3.418.675.337** |

---

### D. Rincian Biaya Maintenance & Operasional Equipment (Agustus, September, dan Oktober 2026)

Pada bulan **Agustus 2026** (`Rp 1.628.644.231`) dan **September 2026** (`Rp 1.605.534.315`), biaya maintenance disajikan secara agregat, sedangkan pada bulan **Oktober 2026** (`Rp 1.010.493.854`), Sheet `Monitoring Progress` merinci pengeluaran ke dalam **14 Pos Pengeluaran Maintenance**:

| No | Pos Pengeluaran Maintenance (Oktober 2026) | Nilai Realisasi (Rp) | Porsi (%) | Keterangan / Kategori Cost Ledger Web |
| :---: | :--- | ---: | ---: | :--- |
| 1 | **Stok (Sparepart & Ban untuk Persediaan Gudang)** | Rp 359.457.000 | 35,57% | `Sparepart` (Inventory Stock In) |
| 2 | **Pembelian Ban (Langsung Pakai Unit)** | Rp 193.570.000 | 19,16% | `Sparepart` / `Ban` |
| 3 | **Sparepart (Corrective & Penggantian Komponen)** | Rp 168.925.200 | 16,72% | `Sparepart` |
| 4 | **Administrasi, STNK, Pajak, KIR & SILO** | Rp 133.736.119 | 13,23% | `Other` / `Administrasi` |
| 5 | **Filter (Preventive Maintenance)** | Rp 36.340.000 | 3,60% | `Maintenance` (PM Service) |
| 6 | **Jasa Mekanik Freelance / Outsource** | Rp 28.450.000 | 2,82% | `External` |
| 7 | **Grease (Gemuk Pelumas)** | Rp 16.224.000 | 1,61% | `Maintenance` (Consumable) |
| 8 | **BBM & E-Toll Operasional Tim Equipment** | Rp 15.628.800 | 1,55% | `Fuel` |
| 9 | **Oli / Pelumas Mesin & Hidrolik** | Rp 14.377.000 | 1,42% | `Maintenance` (Consumable) |
| 10 | **Pengiriman / Ekspedisi Logistik Sparepart** | Rp 13.781.935 | 1,36% | `Other` (Logistik) |
| 11 | **Uang Makan Lembur Tim Mekanik** | Rp 9.815.000 | 0,97% | `Other` (SDM Operasional) |
| 12 | **Kebutuhan Operasional Yard** | Rp 8.677.300 | 0,86% | `Other` (Overhead Yard) |
| 13 | **Perjalanan Dinas (Perjadin) Tim Equipment** | Rp 7.361.500 | 0,73% | `Other` (Perjadin) |
| 14 | **Aki / Battery** | Rp 4.150.000 | 0,41% | `Sparepart` |
| **-** | **TOTAL BIAYA MAINTENANCE OKTOBER 2026** | **Rp 1.010.493.854** | **100,00%** | **-** |

