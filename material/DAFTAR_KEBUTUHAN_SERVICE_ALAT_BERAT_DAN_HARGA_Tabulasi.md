# Daftar Kebutuhan Service Alat Berat dan Harga

> Versi Markdown ini dirapikan agar data anggaran, biaya service, inventaris unit, dan rincian tiap alat lebih mudah dibaca. Data asli tidak dikoreksi atau diselaraskan secara otomatis.

## 1. Ringkasan Workbook

| Keterangan | Nilai |
| --- | ---: |
| Jumlah sheet | 41 |
| Sheet berisi data | 27 |
| Total baris bermakna | 1567 |
| Total sel terisi | 11108 |
| Sheet rincian alat/unit | 32 |
| Error formula terdeteksi | 8 |

## 2. Ringkasan Budgeting 2026

| Komponen | Nilai |
| --- | --- |
| Periode | Januari–April 2026 |
| Total estimasi biaya corrective | Rp169.000.000 |
| Total biaya spare part service | Rp143.901.680,53 |
| Dasar estimasi corrective | Usia alat/dump truck dan riwayat perbaikan 3 tahun |
| Status estimasi | Acuan budgeting; dapat berubah setelah inspeksi tim Equipment |

### Estimasi Corrective per Kelompok Asset

| Kelompok Asset | Jumlah Unit | Estimasi Biaya |
| --- | ---: | ---: |
| VIBROROLLER | 9 | Rp18.000.000 |
| MOTOR GRADER | 2 | Rp5.000.000 |
| BULLDOZER | 6 | Rp30.000.000 |
| EXCAVATOR | 17 | Rp24.000.000 |
| DUMPTRUCK | 40 | Rp80.000.000 |

## 3. Biaya Service Januari 2026

| Keterangan | Nilai |
| --- | --- |
| Total service alat berat A+B+C+D | Rp47.283.268,77 |
| Total biaya spare part service keseluruhan | Rp146.245.508,50 |
| Total estimasi biaya perbaikan pada sheet | Rp146.000.000 |
| Perusahaan | PT Bina Rekayasa Anugrah |

### Biaya Service Berdasarkan Kelompok

| Kelompok | Jumlah Baris Unit | Biaya Service |
| --- | ---: | ---: |
| VIBROROLLER | 9 | Rp23.710.706 |
| MOTOR GRADER | 2 | Rp6.393.888 |
| BULLDOZER | 6 | Rp5.767.648,77 |
| EXCAVATOR | 16 | Rp11.411.026 |
| DUMPTRUCK | 41 | Rp9.248.000 |

## 4. Ringkasan Inventaris dan Rencana Service

| Keterangan | Nilai |
| --- | ---: |
| Total unit yang terdaftar pada Sheet34 | 68 |
| Unit dengan HM terisi | 38 |
| Unit dengan HM error | 2 |
| Kolom perencanaan | Bulan 1–Bulan 12 |
| Komponen biaya | Biaya service dan biaya corrective |

### Jumlah Unit per Kelompok

| Kelompok Unit | Jumlah |
| --- | ---: |
| VIBROROLLER | 19 |
| MOTOR GRADER | 5 |
| BULLDOZER | 10 |
| EXCAVATOR | 25 |
| BACKHOE LOADER | 1 |
| WHEEL LOADER | 2 |
| CRAWLER CRANE | 2 |
| RECYCLING MACHINE | 2 |
| SPREADER | 2 |

### Distribusi Lokasi

| Lokasi | Jumlah Unit |
| --- | ---: |
| DURI | 43 |
| PRABUMULIH | 24 |

## 5. Struktur Data Utama

| Sheet | Fungsi |
| --- | --- |
| BUDGETING 2026 | Ringkasan anggaran corrective, preventive, service, SDM, dan estimasi perbaikan. |
| BIAYA PER BULAN | Biaya service Januari 2026 dan estimasi biaya perbaikan per unit. |
| Sheet34 | Daftar asset, SN, kode unit, lokasi, HM service, rencana service bulanan, dan biaya. |
| catatan hm 2024 / hm 2023 | Riwayat dan perencanaan HM; terdapat beberapa error referensi formula. |
| Sheet unit khusus | Rincian filter, oli, service, dan kebutuhan parts berdasarkan tipe unit. |

## 6. Daftar Sheet Rincian Alat

| No. | Nama Sheet |
| ---: | --- |
| 1 | EXCA CAT 320D |
| 2 | EXCA 01 DAN 02 |
| 3 | BULLDOZER D6R |
| 4 | BULLDOZER D7G |
| 5 | EXCA 07, EXCA 12, EXCA 13 |
| 6 | 07. EXCA CAT GX |
| 7 | EXCA GC |
| 8 | COMPACTOR SAKAI SV700TF |
| 9 | COMPACTOR XCMG XS250PD |
| 10 | MOTOR GRADER GD535-5 |
| 11 | MOTOR GRADER XCMG GR135 MAX |
| 12 | Bulldozer Zoomlion ZD170G |
| 13 | Backhoe Loader JCB 3CX |
| 14 | Wheel Loader Caterpillar 950 GC |
| 15 | Wheel Loader SEM 655D (Pallet F |
| 16 | Crawler Crane Zoomlion ZCC600V |
| 17 | Crawler Crane XCMG XGC35HD |
| 18 | Road Cold Recycler XCMG XLZ2303 |
| 19 | Road Reclaimers Caterpillar RM5 |
| 20 | Powder Binder Spreader XCMG XKC |
| 21 | EXCA KOMATSU |
| 22 | EXCA 08 dan 03 |
| 23 | BULLDOZER D6G&D6G 2XL |
| 24 | CS10GC-T8F |
| 25 | VIBRO C533E |
| 26 | BULLDOZER D85SS |
| 27 | BULLDOZER D70LE |
| 28 | BOMAG KOMATSU deutz |
| 29 | BOMAG BW211D-40SL cummin |
| 30 | GRADER 120K |
| 31 | DUMPTRUCK |
| 32 | exca cat mini |

## 7. Error Formula yang Perlu Diperiksa

| Sheet | Sel | Nilai | Formula |
| --- | --- | --- | --- |
| catatan hm 2024 | K42 | #REF! | #REF!+(8*7) |
| catatan hm 2024 | L42 | #REF! | K42+(8*7) |
| catatan hm 2024 | K43 | #REF! | — |
| catatan hm 2024 | L43 | #REF! | — |
| hm 2023 | J42 | #REF! | #REF!+(8*7) |
| hm 2023 | K42 | #REF! | J42+(8*7) |
| hm 2023 | J43 | #REF! | — |
| hm 2023 | K43 | #REF! | — |

> Error `#REF!` ditemukan pada sheet **catatan hm 2024** dan **hm 2023**. Nilai tersebut dipertahankan sesuai sumber dan tidak diperbaiki pada Markdown.

## 8. Cara Membaca File Lengkap

- File Markdown utama ini berisi ringkasan yang paling penting.
- Arsip ZIP berisi satu file Markdown untuk setiap sheet.
- Sheet yang sangat lebar dipecah menjadi blok 10 kolom agar tetap nyaman dibaca.
- Nomor baris dan huruf kolom Excel dipertahankan pada tabulasi lengkap untuk memudahkan pencocokan dengan workbook asli.

## 9. Catatan Data

- Format angka dan tanggal ditata ulang agar lebih terbaca.
- Teks seperti `HM mati`, `EROR`, `waranty`, `by UT`, dan nama vendor dipertahankan sesuai sumber.
- Anggaran corrective pada workbook merupakan prediksi dan bukan nilai final.
- Beberapa sheet hanya berisi template atau struktur tanpa data sel yang terisi.

**Sumber:** `DAFTAR KEBUTUHAN SERVICE ALAT BERAT DAN HARGA.xlsx`