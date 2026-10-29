# LPJ Bulanan Divisi Aset & Equipment: Budget vs Realisasi 2026

> **Sumber Data**: `Equipment - Expenses & Report.xlsx` — Sheet `LPJ BULANAN DIV ASET AND EQ 202` dan Sheet `PENGELUARAN EQUIPMENT DURI 2026`  
> **Cakupan**: Rencana Anggaran Tahunan (Budget Planning 2026) sebesar **Rp 9.286.520.190,37** (`Rp 773.876.682,50 / bulan`), Realisasi Pembayaran Cash Bulanan (`Januari s.d. Juni 2026`), Alokasi Rencana Biaya per 12 Kategori, serta Rincian LPJ Equipment Duri (`Januari s.d. April 2026`).  
> **Keterkaitan Fitur Web**: Modul Biaya & Cost Control Armada (`#view-biaya` → Grafik **Budget vs Actual Cost** `#costChartContainer`, Panel **Ringkasan Alokasi LPJ Equipment Duri** `#costSummaryPanel`, dan Tab **LPJ Bulanan Budget vs Realisasi** `#costTabSecLpj` / tabel DB `cost_financial_monthly`).

---

## 1. Tabel Budget vs Realisasi Pembayaran Cash Bulanan Tahun 2026

Berdasarkan Sheet `LPJ BULANAN DIV ASET AND EQ 202` (dan dikombinasikan dengan realisasi biaya maintenance pada Sheet `Monitoring Progress` untuk Agustus–Oktober 2026):

| Bulan (2026) | Budget Planning Bulanan (Rp) | Realisasi Pembayaran Cash di `LPJ BULANAN` (Rp) | Realisasi Maintenance di `Monitoring Progress` (Rp) | Selisih / Varians terhadap Budget (Rp) | % Realisasi thd Budget | Status Anggaran |
| :--- | ---: | ---: | ---: | ---: | ---: | :---: |
| **Januari 2026** | Rp 773.876.683 | **Rp 801.938.719** | - | -Rp 28.062.037 | 103,63% | Over Budget (+3,63%) |
| **Februari 2026** | Rp 773.876.683 | **Rp 810.292.794** | - | -Rp 36.416.112 | 104,71% | Over Budget (+4,71%) |
| **Maret 2026** | Rp 773.876.683 | **Rp 849.084.485** | - | -Rp 75.207.803 | 109,72% | Over Budget (+9,72%) |
| **April 2026** | Rp 773.876.683 | **Rp 925.757.929** | - | -Rp 151.881.247 | 119,63% | Over Budget (+19,63%) |
| **Mei 2026** | Rp 773.876.683 | **Rp 1.222.546.323** | - | -Rp 448.669.641 | 157,98% | Over Budget (+57,98%) |
| **Juni 2026** | Rp 773.876.683 | **Rp 777.691.954** | - | -Rp 3.815.272 | 100,49% | Over Budget (+0,49%) |
| *Subtotal Semester I (Jan–Jun)* | *Rp 4.643.260.095* | ***Rp 5.387.312.204*** | - | *-Rp 744.052.109* | *116,02%* | *Over Budget (+16,02%)* |
| **Juli 2026** | Rp 773.876.683 | *Belum diisi di LPJ* | *Lihat Rekap All 2026* | - | - | - |
| **Agustus 2026** | Rp 773.876.683 | - | **Rp 1.628.644.231** | -Rp 854.767.549 | 210,45% | Over Budget (+110,45%) |
| **September 2026** | Rp 773.876.683 | - | **Rp 1.605.534.315** | -Rp 831.657.633 | 207,47% | Over Budget (+107,47%) |
| **Oktober 2026** | Rp 773.876.683 | - | **Rp 1.010.493.854** | -Rp 236.617.172 | 130,58% | Over Budget (+30,58%) |
| **November 2026** | Rp 773.876.683 | - | *Belum berjalan* | +Rp 773.876.683 | 0,00% | Sisa Plafon Nov |
| **Desember 2026** | Rp 773.876.683 | - | *Belum berjalan* | +Rp 773.876.683 | 0,00% | Sisa Plafon Des |
| **TOTAL TAHUNAN 2026** | **Rp 9.286.520.190** | **Rp 5.387.312.204 (Jan–Jun)** | **Rp 4.244.672.400 (Agu–Okt)** | **-** | **-** | **Total 9 Bln: Rp 9.631.984.604** |

---

## 2. Alokasi Rencana Biaya Tahunan 2026 per Kategori (`LPJ BULANAN DIV ASET AND EQ 202`)

Rencana kebutuhan anggaran tahunan Divisi Asset & Equipment tahun 2026 sebesar **Rp 9.286.520.190,37** (`Rp 773.876.682,53 / bulan`) dialokasikan ke dalam 5 kelompok utama dan 12 sub-komponen berikut:

| Kode | Kelompok & Komponen Biaya Rencana 2026 | Anggaran Tahunan (Rp) | Anggaran Rata-rata / Bulan (Rp) | Persentase (%) | Keterangan Penggunaan |
| :---: | :--- | ---: | ---: | ---: | :--- |
| **A** | **BARANG MATERIAL (SPARE PARTS & CONSUMABLE)** | **Rp 7.012.521.838** | **Rp 584.376.820** | **75,51%** | |
| A.1 | Spare parts *Preventive Maintenance* | Rp 0 | Rp 0 | 0,00% | Digabung ke pos A.2 (Service Berkala) |
| A.2 | **Spare part *Service Berkala (PM)*** | **Rp 1.314.359.432** | Rp 109.529.953 | **14,15%** | Filter, part berkala PM 250/500/1000/2000 |
| A.3 | **Spare parts *Corrective Maintenance*** | **Rp 4.243.974.666** | Rp 353.664.556 | **45,70%** | Perbaikan kerusakan & penggantian komponen |
| A.4 | **Pelumas (Oli & Grease)** | **Rp 346.287.740** | Rp 28.857.312 | **3,73%** | Oli mesin, hidrolik, transmisi, gardan, gemuk |
| A.5 | **Ban (Tires)** | **Rp 1.107.900.000** | Rp 92.325.000 | **11,93%** | Kegiatan maintenance penggantian ban DT/LV |
| **B** | **UPAH / JASA TEKNIS** | **Rp 178.926.154** | **Rp 14.910.513** | **1,93%** | |
| B.1 | **Jasa Freelance / Bengkel Luar** | **Rp 115.000.000** | Rp 9.583.333 | **1,24%** | Jasa mekanik freelance, bubut, kalibrasi |
| B.2 | **Mekanik dan Tim Equipment (Lembur/Insentif)** | **Rp 63.926.154** | Rp 5.327.180 | **0,69%** | Dukungan tim mekanik internal |
| **C** | **OPERASIONAL PENDUKUNG** | **Rp 277.247.065** | **Rp 23.103.922** | **2,99%** | |
| C.1 | **Transport / Logistik** | **Rp 103.064.573** | Rp 8.588.714 | **1,11%** | Pengambilan & pengiriman sparepart ke site |
| C.2 | **Operasional Lain** | **Rp 174.182.492** | Rp 14.515.208 | **1,88%** | Pendukung operasional lapangan |
| **D** | **ADMINISTRASI & LEGALITAS ARMADA** | **Rp 1.817.825.133** | **Rp 151.485.428** | **19,57%** | |
| D.1 | **STNK dan Pajak Kendaraan** | **Rp 910.233.776** | Rp 75.852.815 | **9,80%** | Perpanjangan pajak tahunan & STNK 5 tahunan |
| D.2 | **KIR (Uji Berkala Dishub)** | **Rp 284.250.000** | Rp 23.687.500 | **3,06%** | Uji berkala kelayakan jalan Dump Truck & LV |
| D.3 | **SILO (Surat Izin Laik Operasi Alat Berat)** | **Rp 250.200.000** | Rp 20.850.000 | **2,69%** | Sertifikasi K3 / SILO alat berat di proyek migas/tambang |
| D.4 | **Non-Equipment (R)** | **Rp 373.141.357** | Rp 31.095.113 | **4,02%** | Kebutuhan lintas divisi / non-unit |
| **E** | **YARD (MANPOWER & MAINTENANCE)** | **Rp 0** | **Rp 0** | **0,00%** | Dialokasikan pada pos A–D |
| **-** | **TOTAL KEBUTUHAN ANGGARAN 2026** | **Rp 9.286.520.190** | **Rp 773.876.683** | **100,00%** | **Plafon Resmi Divisi Asset & Equipment** |

---

## 3. Rincian Pengeluaran Equipment Duri (Januari – April 2026)

Berdasarkan Sheet `PENGELUARAN EQUIPMENT DURI 2026`, berikut adalah rincian komponen pengeluaran yang masih tersimpan nilainya secara eksplisit (sebagian sel formula merujuk ke sheet eksternal yang menghasilkan `#REF!`, namun nilai aktual SDM, Operasional, Preventive DT, dan Ban masih tercatat lengkap):

| No | Deskripsi Komponen Biaya | Tipe Unit / Sub-Pos | Januari 2026 (Rp) | Februari 2026 (Rp) | Maret 2026 (Rp) | April 2026 (Rp) |
| :---: | :--- | :--- | ---: | ---: | ---: | ---: |
| **1** | **Corrective: Perbaikan (Spareparts)** | Alat Berat, Dump Truck, LV | *Rp 38.608.000* *(Data DB Web)* | *Mengacu ke Rekap All 2026* | *Mengacu ke Rekap All 2026* | *Mengacu ke Rekap All 2026* |
| **2** | **Preventive: Service & Maintenance** | Dump Truck (Oli, Filter, Aki, Service) | *Rp 94.714.684* *(Total PM Jan di DB)* | Rp 32.802.500 | Rp 22.505.000 | Rp 9.640.000 |
| **-** | ├─ *Ban (Amortisasi 12 Bulan)* | Dump Truck | - | - | Rp 79.915.350 | Rp 74.587.017 |
| **-** | └─ *Ban (Pembelian Aktual Cash)* | Dump Truck | - | - | Rp 1.740.000 | Rp 5.000.000 |
| **3** | **SDM (Gaji Karyawan)** | Mekanik & Tim Equipment | **Rp 104.108.227** | **Rp 93.914.451** | **Rp 109.846.112** | **Rp 144.609.251** |
| **4** | **Biaya Operasional** | STNK, SILO, KIR, Aksesoris, Additional, Lembur Tim | **Rp 404.938.983** | **Rp 152.127.214** | **Rp 237.834.241** | **Rp 223.243.222** |
| **-** | **Total Pembayaran Cash Bulanan (`LPJ BULANAN`)** | **Seluruh Pos Divisi Equipment** | **Rp 801.938.719** | **Rp 810.292.794** | **Rp 849.084.485** | **Rp 925.757.929** |

> **Catatan Audit dari Sheet `PENGELUARAN EQUIPMENT DURI 2026`**:  
> *"Preventif Dump Truck terdiri atas: Ban, Oli, Grease, Aki, dan Komponen Service Berkala."*

