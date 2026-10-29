# Dokumentasi Skema Pencatatan Sewa Alat PT BRA

> **File Sumber:** `Skema Pencatatan Sewa Alat BRA.xlsx`
> **Direktori Output:** `material/SKEMA-PENCATATAN-SEWA-ALAT/`
> **Waktu Konversi:** 2026-10-29 10:16:25

## 1. Ikhtisar Dokumen

File workbook Excel ini memuat skema tata kelola finansial dan operasional armada alat berat dan kendaraan PT Bangun Reka Agung (BRA), mencakup struktur pembiayaan leasing, penentuan tarif sewa proyek vs internal, monitoring timesheet penugasan alat, serta rekapitulasi pendapatan sewa dari tahun 2020 hingga 2026.

## 2. Struktur File Markdown per Tabel

| No | Dokumen Markdown | Sumber Sheet Excel | Rangkuman Isi Data |
| :---: | :--- | :--- | :--- |
| 1 | [01_Rekap_Aset.md](./01_Rekap_Aset.md) | `Rekap Aset` | Sheet kosong bawaan template workbook. |
| 2 | [02_Leasing.md](./02_Leasing.md) | `Leasing` | **153 entri pembiayaan leasing**: harga perolehan, DP, bunga, tenor, nominal cicilan, vendor leasing (Buana Finance, BFI, MTF, BRI Finance, BNI Finance, TAF, dsb.). |
| 3 | [03_Total_Pendapatan_Sewa.md](./03_Total_Pendapatan_Sewa.md) | `Total` | **Rekapitulasi pendapatan sewa 171 unit alat & kendaraan**: historis tahun 2020 - 2026 per bulan beserta analisis overhead, biaya perbaikan, dan kerugian alat rusak. |
| 4 | [04_Sewa_Alat_Operasional.md](./04_Sewa_Alat_Operasional.md) | `Sewa Alat` | **Master operasional 286 unit armada**: tarif pendapatan, tarif sewa proyek, cicilan leasing, dan penjelasan timesheet alokasi proyek harian. |
| 5 | [05_Harga_Sewa_Proyek_dan_Cicilan.md](./05_Harga_Sewa_Proyek_dan_Cicilan.md) | `hagra sewa proyek dan cicilan` | **Evaluasi 295 unit alat**: komparasi tarif sewa proyek vs tarif equipment internal, cicilan 3 thn vs 5 thn, rata-rata biaya perbaikan, dan margin potensi pendapatan. |
| 6 | [06_List_Master_Data.md](./06_List_Master_Data.md) | `List` | **Master lookup data**: 44 lokasi proyek kerja, 60 klasifikasi status/sub-sektor, dan status standby unit. |

## 3. Temuan Kunci Finansial & Operasional

- **Kategori Alat Utama**: Excavator (PC 210, PC 200, CAT 320), Bulldozer (D85, SD16, ZD170), Motor Grader (GD535, GR135), Vibro / Padfoot Compactor (Bomag BW211, CS10), Dump Truck (Hino FM 280 JD Mining), Wheel Loader, Crawler Crane, Cold Recycler, dan Cement Spreader.
- **Status Pembiayaan**: Sebagian besar unit generasi awal telah berstatus `LUNAS`, sedangkan unit baru (pembelian 2024–2026) dibiayai leasing dengan tenor 24 hingga 36 bulan.
- **Integrasi Data**: Nomor seri dan kode unit pada dokumen ini saling sinkron dengan master data aset di aplikasi *ServicePlan-BRA*.
