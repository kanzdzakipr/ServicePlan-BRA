# Buku Besar Cashout (28.320 Baris), Utang Vendor & Katalog Perbandingan Harga

> **Sumber Data**: `Equipment - Expenses & Report.xlsx` — Sheet `Cashout` (28.320 baris), Sheet `DASHBOARD` (28.312 baris), Sheet `PERBANDINGAN HARGA` (327 baris), Sheet `Debt` (276 baris), dan Sheet `Additional` (34 baris).  
> **Keterkaitan Fitur Web**: Modul Biaya & Cost Control Armada (`#view-biaya` → Tab **Register PO & Buku Besar Biaya** `#costTabSecPo` / tabel DB `asset_cost_transactions`) dan Modul Inventaris & PO (`#view-inventaris` / tabel DB `parts` & `purchase_orders`).

---

## 1. Ringkasan Buku Besar Pengeluaran Kas Aktual (Sheet `Cashout` & `DASHBOARD`)

Sheet `Cashout` dan `DASHBOARD` merupakan jurnal transaksi induk (General Ledger) yang mencatat **28.320 baris pengeluaran kas** historis lintas tahun dengan total nilai mencapai **Rp 20.757.421.479**:

| No | Kategori Pengeluaran (`Cashout` / `DASHBOARD`) | Total Realisasi Kas (Rp) | Porsi (%) | Pemetaan Kategori pada `CostLedger.php` di Web |
| :---: | :--- | ---: | ---: | :--- |
| 1 | **Sparepart** | **Rp 10.520.078.549** | **50,68%** | `Sparepart` |
| 2 | **Consumable (Oli, Filter, Ban, Aki, Grease)** | **Rp 4.043.903.443** | **19,48%** | `Sparepart` / `Maintenance` |
| 3 | **Biaya Lain-lain (STNK, Pajak, KIR, SILO, Umum)** | **Rp 3.252.354.770** | **15,67%** | `Other` |
| 4 | **Operasional (Transport, Logistik, BBM, Perjadin)** | **Rp 1.223.868.590** | **5,90%** | `Fuel` / `Other` |
| 5 | **Overhead (Gaji Mekanik, Lembur, Operasional Yard)** | **Rp 779.883.634** | **3,76%** | `Other` |
| 6 | **Material Lainnya (Besi, Kawat Las, Plat, Baut)** | **Rp 325.204.169** | **1,57%** | `Sparepart` |
| 7 | **Aksesoris (Lampu, Spion, Kaca, Karpet, Sabuk)** | **Rp 324.467.576** | **1,56%** | `Sparepart` |
| 8 | **Maintenance (Jasa Perbaikan, Bubut, Kalibrasi)** | **Rp 268.476.221** | **1,29%** | `Maintenance` / `External` |
| 9 | **PPN (Pajak Pertambahan Nilai)** | **Rp 17.100.027** | **0,08%** | `Other` |
| 10 | **Admin Bank** | **Rp 2.084.500** | **0,01%** | `Other` |
| **-** | **GRAND TOTAL BUKU BESAR CASHOUT** | **Rp 20.757.421.479** | **100,00%** | **28.320 Baris Jurnal Transaksi** |

### Struktur Kolom Jurnal pada Sheet `Cashout`:
Setiap baris pada Sheet `Cashout` memiliki atribut:
- `Tanggal Transaksi` (Format tanggal transaksi kas keluar)
- `Kode Unit Baru` & `Kode Unit Lama` (Contoh: `DT 01`, `Exca 04`, `Dozer 06`, `SD 01`, atau `NON-EQUIPMENT`)
- `Nama Barang / Uraian Pekerjaan`
- `Kategori` (`Sparepart`, `Consumable`, `Maintenance`, `Operasional`, `Overhead`, `Aksesoris`, `Material Lainnya`, `Biaya Lain-lain`)
- `Sub-Kategori` (`Corrective`, `Preventive`, `Ban`, `Oli`, `Administrasi`, dll.)
- `Qty`, `Satuan`, `Harga Satuan (Rp)`, dan `Total Harga (Rp)`
- `Nama Vendor / Toko` dan `Lokasi Proyek / Site`

---

## 2. Katalog Perbandingan Harga Vendor Ban (Sheet `PERBANDINGAN HARGA`)

Sheet `PERBANDINGAN HARGA` menyajikan daftar komparasi harga dan syarat pembayaran (*Terms of Payment*) dari berbagai pemasok ban truk (`10.00R20`, `11.00R20`, `12.00R20`) untuk pengadaan armada Dump Truck:

| No | Nama Vendor / Pemasok | Tipe / Merk & Ukuran Ban | Harga Satuan / Set (Rp) | Evaluasi Teknis & Syarat Pembayaran (TOP) |
| :---: | :--- | :--- | ---: | :--- |
| 1 | **PT VERON INDONESIA** | `SAILUN 10.00R20 PRS811` | **Rp 3.900.000** | **Termasuk PPN, Tempo 30 Hari** (Harga sangat kompetitif) |
| 2 | **PT VERON INDONESIA** | `SAILUN 10.00R20 PRS850` | **Rp 4.250.000** | **Termasuk PPN, Tempo 30 Hari** |
| 3 | **CV KREATIF JAYA BERSAMA** | `Vikrant 10.00R20 (Made in India)` | **Rp 4.300.000** | **Tempo 30 Hari**, harga relatif sedang, merk bagus |
| 4 | **PT BHINNEKA RODA INDONESIA** | `Neolin 10.00R20` | **Rp 4.350.000** | **Termasuk PPN, Tempo 30 Hari** |
| 5 | **SINSUMO** | `GAO822 10.00R20` | **Rp 4.300.000** | Sistem pembayaran **Tempo 30 Hari**, kualitas merk masih diuji |
| 6 | **KARUNA** | `Gajah Tunggal (GT) 10.00R20` | **Rp 4.150.000** | Kualitas sedang, **Tempo > 30 Hari**, barang ready stok, harga agak tinggi |
| 7 | **Atik** | `Gajah Tunggal (Ragi Cacing) 10.00R20` | **Rp 3.980.000** | Hanya melayani **Cash** (tidak mau stok bila tempo) |
| 8 | **Atik** | `Ban Ragi Tahu 10.00R20` | **Rp 4.400.000** | Hanya melayani **Cash** |
| 9 | **Indo Truck** | `Goodtyre / UNINEST 10.00R20` | **Rp 4.408.800** | Harga terlalu tinggi, kualitas ban sedang |
| 10 | **Indo Truck** | `TIRON 10.00R20` | **Rp 4.468.100** | Harga relatif tinggi |
| 11 | **Indo Truck** | `SAILUN 10.00R20` | **Rp 4.762.000** | Harga lebih tinggi dibanding PT Veron Indonesia |
| 12 | **Indo Truck** | `Neolin 10.00R20` | **Rp 4.792.400** | Harga lebih tinggi dibanding PT Bhinneka Roda Indonesia |
| 13 | **CV MAJU JAYA UTAMA** | `1000-20 EVERPROSPER MT2828 Benang (1 Set)` | **Rp 3.500.000** | Tipe benang (nylon) 1 set |
| 14 | **CV MAJU JAYA UTAMA** | `1000R20 COCREA CR801 Kawat (1 Set)` | **Rp 3.600.000** | Tipe kawat (radial) 1 set |
| 15 | **CV MAJU JAYA UTAMA** | `1000R20 COCREA CR903 Kawat (1 Set)` | **Rp 3.925.000** | Tipe kawat (radial) 1 set |
| 16 | **CV MAJU JAYA UTAMA** | `1100R20 COCREA CR801 Kawat (1 Set)` | **Rp 4.080.000** | Ukuran `11.00R20` kawat 1 set |
| 17 | **CV MAJU JAYA UTAMA** | `1100R20 COCREA CR903 Kawat (1 Set)` | **Rp 4.580.000** | Ukuran `11.00R20` kawat kasar 1 set |
| 18 | **CV KOMERSIAL BATINDO** | `Ban GT Miler RFD Cacing 10.00-20` | **Rp 3.350.000** | Ban luar GT halus/cacing |
| 19 | **CV KOMERSIAL BATINDO** | `Ban Luar GT Supergrip Cangkul 10.00-20` | **Rp 3.600.000** | Ban luar GT tapak cangkul |
| 20 | **CV KOMERSIAL BATINDO** | `Ban GT Maxtraction 10.00-20` | **Rp 3.750.000** | Ban luar GT semi-kasar |
| 21 | **CV KOMERSIAL BATINDO** | `Ban Kawat Chengshan CST 126 / 327A 10.00R20` | **Rp 3.900.000 – Rp 4.000.000** | Ban kawat radial |
| 22 | **CV KOMERSIAL BATINDO** | `Ban India KOMBAT K100+HD 10.00-20` | **Rp 4.450.000** | Heavy duty |
| 23 | **SUMBER BAN (Pekanbaru)** | `1000-20 16PR GT Miler + Ban Dalam & Selendang` | **Rp 3.900.000** | Paket lengkap 1 set |
| 24 | **SUMBER BAN (Pekanbaru)** | `1000-20 16PR GT Super Grip + Dalam & Selendang` | **Rp 4.195.000** | Paket lengkap 1 set (Kasar) |
| 25 | **SUMBER BAN (Pekanbaru)** | `1000-20 16PR GT Max Traction + Dalam & Selendang` | **Rp 4.310.000** | Paket lengkap 1 set (Semi Kasar) |
| 26 | **SUMBER BAN (Pekanbaru)** | `Ban Golden Crown CB905 12.00R20` | **Rp 5.500.000** | Ukuran besar `12.00R20` (Termasuk PPN, Area Pekanbaru) |
| 27 | **PT KUMALA SUKSES BERSAMA** | `Ban ROATIGER RT752 12.00R20` | **Rp 5.350.000** | Ukuran besar `12.00R20` (Termasuk PPN, Area Kendari) |
| 28 | **SAILUN DIRECT** | `SAILUN S811 (11.00R20 / 10.00R16)` | **Rp 3.650.000** | Belum termasuk PPN, *Cash Before Delivery (CBD)* |
| 29 | **SAILUN DIRECT** | `SAILUN S850 11.00R20 / SUPERFLEET S989 11.00R20` | **Rp 4.550.000 – Rp 4.600.000** | Belum termasuk PPN, *Cash Before Delivery (CBD)* |

---

## 3. Katalog Perbandingan Harga Pelumas / Oli Drum 200L–209L Lintas Wilayah (`PERBANDINGAN HARGA`)

| Jenis Pelumas / Oli | PT Surya Mandiri Makmur (209L) | PT King Indo (200L) | PT Giar Putra Mahkota (200L) | PT Riodi Jaya (Pekanbaru/Plg) | Cahaya Pengajaran Abadi (Maluku/Kal/Sul) | PT Riau Muda Jasa Guna (Pku) | Pertamina (Kendari) | PT Sumber Mas Inti Nusa (Jkt) |
| :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| **Oli Mesin SAE 15W40 (Exc PPN)** | **Rp 8.569.000** | Rp 8.649.000 | Rp 8.400.000 | Rp 9.739.400 | Rp 9.945.497 | Rp 10.200.000 | Rp 11.300.000 | Rp 11.730.600 |
| **Oli Mesin SAE 15W40 (Inc PPN)** | **Rp 7.387.500*** | Rp 8.412.800* | - | Rp 10.810.734 | Rp 11.039.502 | Rp 11.322.000 | Rp 12.543.000 | Rp 13.020.966 |
| **Oli SAE 30 / Transmisi (Exc PPN)** | **Rp 7.387.500** | Rp 8.412.800 | - | Rp 9.530.400 | Rp 9.744.021 | - | - | Rp 9.478.777 |
| **Oli SAE 30 / Transmisi (Inc PPN)** | - | - | - | Rp 10.578.744 | Rp 10.815.863 | - | - | Rp 10.521.442 |

Inoltre, harga satuan drum pada **PT Riodi Jaya** untuk kemasan standar tercatat:
- `OIL SAE 15W40`: **Rp 6.553.500**
- `HYDRAULIC OIL`: **Rp 6.216.000**
- `TRANSMISI OIL`: **Rp 7.936.500**
- `GARDAN OIL`: **Rp 8.325.000**

---

## 4. Struktur Pelacakan Utang Vendor (`Debt`) & Kalkulasi Bunga Modal Kerja (`Additional`)

1. **Sheet `Debt` (276 Baris)**:
   - Menyediakan skema monitoring kewajiban utang usaha (*Accounts Payable*) ke vendor sparepart/ban dengan kolom: `No`, `Vendor`, `Item`, `Category`, `Description`, `Invoice Date`, `Qty`, `Sat`, `Price`, `Total`, diikuti 7 tahap cicilan pelunasan (`Payment 1` s.d. `Payment 7`), `Remaining Payment`, dan `Rencana Pembayaran`.
   - Pada snapshot file saat ini, saldo utang berjalan di baris ringkasan atas tercatat `Rp 0` (sebagian baris referensi bawah bernilai `#N/A` karena sheet sumber eksternal tidak dilampirkan).
2. **Sheet `Additional` (34 Baris)**:
   - Menyediakan model perhitungan biaya bunga modal kerja sebesar **1,4% per bulan** atas selisih antara *Kumulatif Expense* terhadap *Kumulatif Pencairan Termin Proyek*:
     - Rumus 1: `Bunga = 1,4% × Expense × Lama Bunga`
     - Rumus 2: `Nilai Kena Bunga = Kumulatif Expense (D) - Pencairan Bulan Berjalan (C)` → `Bunga = 1,4% × (D - C)`
     - Rumus 3: `Nilai Kena Bunga Bersih = Kumulatif Expense (D) - Kumulatif Pencairan (F)` → `Bunga = 1,4% × (D - F)`

