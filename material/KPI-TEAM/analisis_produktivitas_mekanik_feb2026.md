# Analisis Produktivitas Mekanik - Februari 2026

## Informasi File

- **Nama sumber:** `analisis_produktivitas_mekanik_feb2026.xlsx`
- **Periode data:** 1–28 Feb 2026
- **Jumlah sheet:** 5
- **Jenis dokumen:** Workbook analisis produktivitas mekanik berdasarkan job yang memiliki waktu mulai/selesai dan daftar job yang tidak dapat dihitung waktunya.
- **Formula dalam workbook:** Tidak ditemukan; isi workbook merupakan hasil analisis statis.

> Catatan konversi: tabel yang sangat panjang dibagi menurut tanggal agar lebih mudah diperiksa. Tanggal Excel dikonversikan ke format `YYYY-MM-DD`. Teks multiline di dalam sel dipertahankan dengan `<br>`. Istilah, ejaan, kode unit, status, dan catatan teknis tidak dikoreksi.

# 1. Metodologi Sumber

| Item | Nilai |
| --- | --- |
| Periode data | 1–28 Feb 2026 |
| Total baris pekerjaan (NO terisi) | 394 |
| Baris dengan Start &amp; End time valid | 308 |
| Coverage pencatatan jam | 78.2% |
| Metode alokasi jam bila &gt;1 nama mekanik | Durasi job dibagi rata ke semua nama mekanik yang tercantum pada baris tersebut. |
| Definisi lembur | Jam setelah 16:00 (porsi waktu setelah 16:00). |
| Catatan | Jika ada job tanpa start/end time, jamnya tidak bisa dihitung dan masuk sheet 'Missing_Time'. |

## Penjelasan Metodologi

Workbook menggunakan dua konsep utama:

1. **Job bertiming** — pekerjaan yang mempunyai Start Time dan End Time sehingga durasi dapat dihitung.
2. **Missing Time** — pekerjaan yang tidak mempunyai pasangan waktu lengkap sehingga jam produktif tidak dapat dihitung.

Apabila satu job mencantumkan lebih dari satu mekanik, durasi job dibagi rata kepada seluruh nama yang tercantum. Karena itu, `Jumlah Job (bertiming)` pada ringkasan mekanik menunjukkan jumlah **partisipasi mekanik pada job**, bukan jumlah job unik perusahaan.

Jam normal dihitung sebagai porsi pekerjaan sampai pukul 16.00, sedangkan jam setelah pukul 16.00 diklasifikasikan sebagai lembur.

# 2. Ringkasan Mekanik Inti

| Ranking | Nama Mekanik | Total Jam Kerja (alokasi) | Jam Normal (≤16:00) | Jam Lembur (&gt;16:00) | Jumlah Job (bertiming) | Job Indikasi Delay Sparepart | Rasio Delay | Standar jam/bulan | Efektivitas vs 208 jam |
| ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 1 | Rahmad K | 92.75 | 72.36 | 20.39 | 46 | 2 | 4% | 208 | 44.6% |
| 2 | Joni (Jhoni Ist Kandar) | 66.83 | 58.82 | 8.01 | 35 | 2 | 6% | 208 | 32.1% |
| 3 | Afriyandi | 60.10 | 54.65 | 5.45 | 28 | 0 | 0% | 208 | 28.9% |
| 4 | Darmawan | 54.55 | 47.70 | 6.85 | 27 | 4 | 15% | 208 | 26.2% |
| 5 | Hendrik | 47.07 | 45.93 | 1.13 | 34 | 3 | 9% | 208 | 22.6% |
| 6 | Suwardi | 35.04 | 28.27 | 6.78 | 20 | 0 | 0% | 208 | 16.8% |
| 7 | Daniel Sitepu | 0.00 | 0.00 | 0.00 | 0 | 0 | 0% | 208 | 0.0% |

## Tabel Perbandingan Tambahan pada Sheet yang Sama

> Bagian berikut muncul pada baris 12–20 sheet `Ringkasan_Mekanik_Inti`. Angka `Jam Tercatat` tidak sama dengan angka alokasi pada tabel utama, sehingga bagian ini dipertahankan sebagai tabel pembanding terpisah dan tidak digabungkan ke perhitungan produktivitas utama.

| Nama Mekanik | Jam Tercatat | Standar | Persentase |
| --- | ---: | ---: | ---: |
| Jhoni | 72 | 208 | 34% |
| Darmawan | 63 | 208 | 30% |
| Hendrik | 55 | 208 | 26% |
| Siregar | 48 | 208 | 23% |
| Suwardi | 40 | 208 | 19% |
| Afriyandi | 37 | 208 | 18% |
| Daniel | 28 | 208 | 13% |
| Rahmad | 24 | 208 | 12% |

# 3. Ringkasan Semua Nama Mekanik

| Nama Mekanik | Total Jam Alokasi | Jam Normal | Jam Lembur | Jumlah Job | Rata-rata Jam/Job | Job Delay Sparepart | Total Jobs | Rasio Delay | Standar Jam/Bulan | Efektivitas | Porsi Lembur dari Jam |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Rahmad K | 92.75 | 72.36 | 20.39 | 46 | 2.02 | 2 | 46 | 4% | 208 | 44.6% | 22.0% |
| Urwatul Usk | 73.88 | 66.54 | 7.34 | 67 | 1.10 | 0 | 67 | 0% | 208 | 35.5% | 9.9% |
| Joni (Jhoni Ist Kandar) | 66.83 | 58.82 | 8.01 | 35 | 1.91 | 2 | 35 | 6% | 208 | 32.1% | 12.0% |
| Afriyandi | 60.10 | 54.65 | 5.45 | 28 | 2.23 | 0 | 28 | 0% | 208 | 28.9% | 9.1% |
| Darmawan | 54.55 | 47.70 | 6.85 | 27 | 2.02 | 4 | 27 | 15% | 208 | 26.2% | 12.6% |
| Hendrik | 47.07 | 45.93 | 1.13 | 34 | 1.38 | 3 | 34 | 9% | 208 | 22.6% | 2.4% |
| Rezeki Siregar | 39.71 | 37.64 | 2.07 | 46 | 0.86 | 2 | 46 | 4% | 208 | 19.1% | 5.2% |
| Suwardi | 35.04 | 28.27 | 6.78 | 20 | 1.75 | 0 | 20 | 0% | 208 | 16.8% | 19.3% |
| Gabriel | 13.47 | 12.98 | 0.49 | 11 | 1.22 | 0 | 11 | 0% | 208 | 6.5% | 3.6% |
| Agung S | 6.22 | 5.22 | 1.00 | 4 | 1.56 | 1 | 4 | 25% | 208 | 3.0% | 16.1% |

## Ringkasan Angka Tim

- **Total jam dialokasikan kepada 10 nama mekanik:** 489.62 jam.
- **Jam normal:** 430.11 jam.
- **Jam lembur:** 59.51 jam.
- **Porsi lembur terhadap total jam:** 12.2%.
- **Total partisipasi mekanik pada job bertiming:** 318.
- **Total partisipasi yang ditandai delay sparepart:** 14.
- **Job signature yang ditandai delay sparepart berdasarkan kombinasi field yang terlihat:** 11.

# 4. Analisis Produktivitas dan Pola Kerja

## 4.1 Temuan Utama per Mekanik

- **Rahmad K** memiliki alokasi jam tertinggi, yaitu **92,75 jam**, dan jam lembur tertinggi, yaitu **20,39 jam**. Efektivitas terhadap standar 208 jam adalah **44,6%**.
- **Urwatul Usk** memiliki jumlah partisipasi job terbanyak, yaitu **67 job**, dengan rata-rata **1,10 jam per job**.
- **Afriyandi** memiliki rata-rata jam per job tertinggi pada tabel semua nama, yaitu **2,23 jam per job**.
- **Darmawan** memiliki jumlah indikasi delay sparepart tertinggi, yaitu **4 partisipasi**, dengan rasio delay **15%**.
- **Agung S** mempunyai rasio delay tertinggi, yaitu **25%**, tetapi basisnya hanya **4 job** dan **1 job delay**, sehingga persentase tersebut perlu dibaca bersama jumlah job.
- **Daniel Sitepu** tercantum pada ringkasan mekanik inti dengan 0 jam dan 0 job, tetapi tidak muncul pada ringkasan semua nama.

## 4.2 Distribusi Mekanik per Job Signature

| Jumlah mekanik pada job signature | Jumlah signature |
| ---: | ---: |
| 1 | 190 |
| 2 | 61 |
| 3 | 2 |

> `Job signature` di atas dibuat dari kombinasi No, tanggal, unit, nomor polisi, damage info, waktu mulai, waktu selesai, dan durasi. Workbook tidak menyediakan ID job unik yang stabil, sehingga angka ini digunakan hanya untuk pemeriksaan pola, bukan sebagai total resmi job.

## 4.3 Unit dengan Jam Alokasi Terbesar

| Kode Unit | Total Jam Alokasi | Partisipasi Mekanik |
| --- | ---: | ---: |
| (kode unit kosong) | 121.57 | 61 |
| DT-06 | 24.02 | 9 |
| DT-033 | 22.01 | 18 |
| DT-010 | 20.94 | 8 |
| DT-013 | 18.55 | 11 |
| DT-048 | 16.33 | 9 |
| DT-098 | 15.01 | 8 |
| DT-020 | 13.52 | 8 |
| DT-056 | 12.61 | 4 |
| LB-02 | 10.30 | 2 |

## 4.4 Tanggal dengan Jam Alokasi Terbesar

| Tanggal | Total Jam Alokasi | Jam Lembur |
| --- | ---: | ---: |
| 2026-02-09 | 35.12 | 10.22 |
| 2026-02-23 | 30.48 | 2.00 |
| 2026-02-12 | 27.63 | 10.85 |
| 2026-02-04 | 27.17 | 1.11 |
| 2026-02-18 | 26.22 | 0.70 |
| 2026-02-06 | 25.97 | 2.98 |
| 2026-02-21 | 25.60 | 0.00 |
| 2026-02-15 | 25.42 | 3.08 |
| 2026-02-27 | 22.81 | 0.57 |
| 2026-02-28 | 20.45 | 3.94 |

## 4.5 Sepuluh Job Berdurasi Terpanjang

| No | Tanggal | Kode Unit | No. Polisi | Durasi Jam | Mekanik | Damage / Informasi |
| ---: | --- | --- | --- | ---: | --- | --- |
| 567 | 2026-02-15 | LB-02 | B 9012 ZEH . | 10.30 | Afriyandi, Darmawan | LORY LOWBOY PADA SUSPENSI PATAH &amp; LEPAS |
| 645 | 2026-02-22 |  |  | 10.00 | Rahmad K, Suwardi | Pabrikasi Whellcock RWI |
| 646 | 2026-02-23 |  |  | 10.00 | Rahmad K, Suwardi | Pabrikasi Whellcock RWI |
| 430 | 2026-02-06 | DT-056 | B 9115 ZYT | 8.92 | Joni (Jhoni Ist Kandar) | DISMANTLE DISC CLUTH LIMIT |
| 618 | 2026-02-20 |  | B 9121 EO | 8.47 | Darmawan, Rahmad K | Proses lanjutan ..<br>~ merubah dimensi underaun d RH/LH<br>~ APAR ( BELUM SELESAI )<br>~pabrikasi bracket lampu rotari &amp; instal |
| 415 | 2026-02-05 | DT-06 | B 9102 ZYT | 8.37 | Afriyandi, Rahmad K, Urwatul Usk | terinfo lanjutan proses pemasangan dish clutch |
| 462 | 2026-02-09 |  | BM 9682 JO | 8.33 | Agung S, Rahmad K | ELECTRICAL SYSTEM ERROR <br>elektrik/ kabel2 ada yg shot. |
| 466 | 2026-02-09 | DT 096 | BM9287JO | 8.30 | Rahmad K, Suwardi | Jenis  Pekerjaan : <br>1~ Engsel Pintu Ombeng kanan/kiri broken ( patah ).<br>2~ Kunci pintu Ombeng Bengkok ( posisi pintu tidak presisi ). |
| 568 | 2026-02-15 | DT-010 | B 9701 PYW | 7.77 | Hendrik, Rahmad K | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). |
| 560 | 2026-02-14 | DT 097 | BM9510QO | 7.58 | Afriyandi | Jenis  Pekerjaan : <br>1:pengantian tingtong atas 4pcs<br>2pengantian alaram mundur<br>3perbaikan kuncipintu kiri<br>4pemasangan karet Septi lumpur kanan kiri.<br>5penambahan oli hidrolik<br>Penggantian karet susu blakang 4 pcs |

## 4.6 Cara Membaca Efektivitas terhadap 208 Jam

Kolom efektivitas dihitung dengan pendekatan:

```text
Efektivitas = Total Jam Kerja Alokasi ÷ 208 × 100%
```

Angka ini menunjukkan porsi jam bertiming yang berhasil dialokasikan kepada mekanik dibanding standar 208 jam per bulan. Angka tersebut **bukan langsung ukuran produktivitas akhir**, karena:

- job tanpa End Time tidak masuk perhitungan;
- aktivitas non-job, briefing, perjalanan, menunggu izin, dan pekerjaan administratif mungkin tidak dicatat;
- pekerjaan dengan banyak mekanik dibagi rata, walaupun kontribusi aktual mungkin berbeda;
- kualitas, tingkat kesulitan, hasil perbaikan, dan repeat failure tidak menjadi faktor; dan
- ketersediaan pekerjaan per mekanik tidak dikendalikan dalam workbook.

# 5. Detail Job Bertiming

Sheet ini memuat **318 baris alokasi mekanik**. Setiap baris merepresentasikan satu nama mekanik pada suatu job. Job yang dikerjakan bersama muncul lebih dari satu kali, sedangkan durasi total dibagi pada kolom alokasi.

## Data 2026-02-01

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 364 | 2026-02-01 |  | B 9135 ZBA | BATERAY / ACCU LOW <br>DIGANTI ACCU BARU <br>YUASA 566LN3 MF | 08.00 | 10.44 | 2.73 | 2.73 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 2.73 | 2.73 | 0.00 |
| 365 | 2026-02-01 | DT-011 | B 9892 PYW | PM SERVICE INTERVAL DI 7500 (PM1)<br>servis berkala <br>   *Ganti filter solar 1pcs (2 baru diganti) <br>   *Ganti oli mesin <br>   *Ganti filter oli 1 pcs | 12.09 | 13.45 | 1.60 | 1.60 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 0.80 | 0.80 | 0.00 |
| 365 | 2026-02-01 | DT-011 | B 9892 PYW | PM SERVICE INTERVAL DI 7500 (PM1)<br>servis berkala <br>   *Ganti filter solar 1pcs (2 baru diganti) <br>   *Ganti oli mesin <br>   *Ganti filter oli 1 pcs | 12.09 | 13.45 | 1.60 | 1.60 | 0.00 | Rezeki Siregar | 2 | Tidak | 0.80 | 0.80 | 0.00 |
| 367 | 2026-02-01 | DT-01 | DT 8126 JE | Ganti filter solar 2 pcs <br>Ganti oli 15 liter.<br>Untuk filter2 oli kita pakek yg lama..stok di gudang lagi kosong..<br>U | 16.13 | 17.19 | 1.10 | 0.00 | 1.10 | Joni (Jhoni Ist Kandar) | 1 | Ya | 1.10 | 0.00 | 1.10 |

## Data 2026-02-02

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 369 | 2026-02-02 | DT-011 | B 9892 PYW | Ganti Lampu H-4 | 9.13 | 9.41 | 0.47 | 0.47 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 371 | 2026-02-02 |  | BM 9174 NU | TAMBAH OLI HYDRAULIC VOLUME TIDAK TERTULIS | 10.08 | 10.24 | 0.27 | 0.27 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.27 | 0.27 | 0.00 |
| 373 | 2026-02-02 | DT-013 | B 9644 KYW | selesai pekerjaan perbaikan master kelos bawah | 10.45 | 14.52 | 4.12 | 4.12 | 0.00 | Urwatul Usk | 1 | Tidak | 4.12 | 4.12 | 0.00 |
| 374 | 2026-02-02 | DT-020 | B 9973 BIS | Tank BBM Bocor pada sisi sebelah bawah ( Repair Tank ).<br>DITEMUKAN BACAAN KM KURANG AKURAT <br>127.935 DI 2/2/2025 SEHARUSNYA SUDAH MASUK SERVICE TERKAIT DAN TELAD TERJADWAL DI MB = 125.775<br>TELAD OLEH PLANNER BACAAN KILOMETER <br>PLAN MEMBACA | 13.17 | 17.01 | 3.73 | 2.72 | 1.02 | Rahmad K | 1 | Tidak | 3.73 | 2.72 | 1.02 |
| 376 | 2026-02-02 | DT-07 | B 9103 ZYT | GANTUNGAN LEHER RUSAK <br>RATCHET TIE DOWN RUSAK <br>Pembaiki handel pintuk | 14.52 | 15.28 | 0.60 | 0.60 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.60 | 0.60 | 0.00 |
| 377 | 2026-02-02 | DT-019 | BG 8367 IJ | GANTI BOOSTER ASSY CLUTCH | 13.55 | 14.54 | 0.98 | 0.98 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.98 | 0.98 | 0.00 |
| 378 | 2026-02-02 | DT-048 | BG 8535 OW | Penambahan mur pada baut tap oil | 15.00 | 15.27 | 0.45 | 0.45 | 0.00 | Hendrik | 1 | Tidak | 0.45 | 0.45 | 0.00 |
| 379 | 2026-02-02 | DT-048 | BG 8535 OW | setelah kita bongkar.terdapat temuan baru.<br>Per no 2 retak.<br>Per no 7 dan 8 retak.<br>Untuk per no 7 dan 8 bisa di pabrikasi.dengan per yg lama. | 11.00 | 16.59 | 5.98 | 5.00 | 0.98 | Gabriel | 2 | Tidak | 2.99 | 2.50 | 0.49 |
| 379 | 2026-02-02 | DT-048 | BG 8535 OW | setelah kita bongkar.terdapat temuan baru.<br>Per no 2 retak.<br>Per no 7 dan 8 retak.<br>Untuk per no 7 dan 8 bisa di pabrikasi.dengan per yg lama. | 11.00 | 16.59 | 5.98 | 5.00 | 0.98 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 2.99 | 2.50 | 0.49 |
| 380 | 2026-02-02 | DT-043 | BG 8639 NI | Mengikat dasbor lampu depan | 17.00 | 17.56 | 0.93 | 0.00 | 0.93 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 0.47 | 0.00 | 0.47 |
| 380 | 2026-02-02 | DT-043 | BG 8639 NI | Mengikat dasbor lampu depan | 17.00 | 17.56 | 0.93 | 0.00 | 0.93 | Rezeki Siregar | 2 | Tidak | 0.47 | 0.00 | 0.47 |
| 382 | 2026-02-02 | DT-058 | B 9117 ZYT | ganti lampu rem belakang kanan. | 18.55 | 19.24 | 0.48 | 0.00 | 0.48 | Rezeki Siregar | 1 | Tidak | 0.48 | 0.00 | 0.48 |

## Data 2026-02-03

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 386 | 2026-02-03 | DT-020 | B 9973 BIS | Ganti fuel filter element 23304 JAC10 | 9.10 | 9.57 | 0.78 | 0.78 | 0.00 | Afriyandi | 1 | Tidak | 0.78 | 0.78 | 0.00 |
| 387 | 2026-02-03 | DT-012 | B 9642 KYW | Penggantian 1 bh <br>GulI guli handle porsneling | 9.06 | 10.16 | 1.17 | 1.17 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 1.17 | 1.17 | 0.00 |
| 388 | 2026-02-03 | DT-033 | BG 8230 MX | Pengelasan Muffler yang patah<br>Fabrikasi breacket sapoet muffler<br>Ganti Baut Gantungan Knalpot | 09.45 | 11.05 | 1.33 | 1.33 | 0.00 | Hendrik | 2 | Tidak | 0.67 | 0.67 | 0.00 |
| 388 | 2026-02-03 | DT-033 | BG 8230 MX | Pengelasan Muffler yang patah<br>Fabrikasi breacket sapoet muffler<br>Ganti Baut Gantungan Knalpot | 09.45 | 11.05 | 1.33 | 1.33 | 0.00 | Rezeki Siregar | 2 | Tidak | 0.67 | 0.67 | 0.00 |
| 389 | 2026-02-03 | DT-011 | B 9892 PYW | Handel pintu sebelah kiri | 10.00 | 10.38 | 0.63 | 0.63 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.63 | 0.63 | 0.00 |
| 390 | 2026-02-03 | DT-01 | DT 8126 JE | safety tangki patah | 10.04 | 11.54 | 1.83 | 1.83 | 0.00 | Gabriel | 2 | Tidak | 0.92 | 0.92 | 0.00 |
| 390 | 2026-02-03 | DT-01 | DT 8126 JE | safety tangki patah | 10.04 | 11.54 | 1.83 | 1.83 | 0.00 | Hendrik | 2 | Tidak | 0.92 | 0.92 | 0.00 |
| 392 | 2026-02-03 | DT-061 | B 9122 ZYT | Proses penggantian Ban 4 pcs | 13.10 | 15.39 | 2.48 | 2.48 | 0.00 | Hendrik | 1 | Tidak | 2.48 | 2.48 | 0.00 |
| 393 | 2026-02-03 | DT-048 | BG 8535 OW | Ganti filter oli 2 pcs<br>Ganti filter solar 3 pcs<br>Oli mesin 26 Liter <br>Dan temuan baru unit dt 00031<br>Per depan sebelah kiri patah.<br>No : 1 dan nomor 4Jenis  Pekerjaan : pergantian per, ganti oli mesin, bohel,pen per | 11.10 | 17.25 | 6.25 | 4.83 | 1.42 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 6.25 | 4.83 | 1.42 |
| 393 | 2026-02-03 | DT-013 | B 9644 KYW | permintaan seperpat untuk dt 00053<br>~pompa minyak ( hand pump ) | 15.30 | 16.51 | 1.35 | 0.50 | 0.85 | Urwatul Usk | 1 | Tidak | 1.35 | 0.50 | 0.85 |
| 394 | 2026-02-03 | DT-020 | B 9121 ZYT | PEMINDAHAN BAN KE DT 012 | 13.10 | 15.39 | 2.48 | 2.48 | 0.00 | Hendrik | 1 | Tidak | 2.48 | 2.48 | 0.00 |

## Data 2026-02-04

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 395 | 2026-02-04 | DT-049 | B 9107 ZYT | Pasang septy lumpur depan ki ka | 8.20 | 9.30 | 1.17 | 1.17 | 0.00 | Rezeki Siregar | 1 | Tidak | 1.17 | 1.17 | 0.00 |
| 396 | 2026-02-04 | DT-021 | BG 8163 NK | Proses lanjutan over houl <br>DISMANTLE KOMPONEN FUSO TAYO | 9.14 | 16.00 | 6.77 | 6.77 | 0.00 | Afriyandi | 2 | Tidak | 3.38 | 3.38 | 0.00 |
| 396 | 2026-02-04 | DT-021 | BG 8163 NK | Proses lanjutan over houl <br>DISMANTLE KOMPONEN FUSO TAYO | 9.14 | 16.00 | 6.77 | 6.77 | 0.00 | Darmawan | 2 | Tidak | 3.38 | 3.38 | 0.00 |
| 399 | 2026-02-04 | DT-064 | B 9125 ZYT | 1~ Underround Protection Broken/Patah di bagian ujung sebelah kanan dan pada Frame sisi kiri.. | 10.29 | 13.47 | 3.30 | 3.30 | 0.00 | Rahmad K | 2 | Tidak | 1.65 | 1.65 | 0.00 |
| 399 | 2026-02-04 | DT-064 | B 9125 ZYT | 1~ Underround Protection Broken/Patah di bagian ujung sebelah kanan dan pada Frame sisi kiri.. | 10.29 | 13.47 | 3.30 | 3.30 | 0.00 | Suwardi | 2 | Tidak | 1.65 | 1.65 | 0.00 |
| 400 | 2026-02-04 | DT-022 | Z 9109 AB | Lampu kota mati/putus<br>Mengganti dengan lampu kota yang baru | 10.01 | 10.44 | 0.72 | 0.72 | 0.00 | Rahmad K | 2 | Tidak | 0.36 | 0.36 | 0.00 |
| 400 | 2026-02-04 | DT-022 | Z 9109 AB | Lampu kota mati/putus<br>Mengganti dengan lampu kota yang baru | 10.01 | 10.44 | 0.72 | 0.72 | 0.00 | Suwardi | 2 | Tidak | 0.36 | 0.36 | 0.00 |
| 401 | 2026-02-04 | WT-001 | B 9018 KFA | Proses pengerjaan | 11.00 | 15.38 | 4.63 | 4.63 | 0.00 | Urwatul Usk | 1 | Tidak | 4.63 | 4.63 | 0.00 |
| 402 | 2026-02-04 | DT-011 | B 9892 PYW | Slmt siang aku mintak tolong beli kan head pump dt 00056 makasil atas kerja sama ya,thsk<br>Mobik udh hidup | 10.45 | 13.22 | 2.62 | 2.62 | 0.00 | Rezeki Siregar | 1 | Tidak | 2.62 | 2.62 | 0.00 |
| 403 | 2026-02-04 | DT-043 | BG 8639 NI | 1~ perbaikan/fabrikasi gantungan ban serep | 9.30 | 10.45 | 1.25 | 1.25 | 0.00 | Gabriel | 2 | Tidak | 0.62 | 0.62 | 0.00 |
| 403 | 2026-02-04 | DT-043 | BG 8639 NI | 1~ perbaikan/fabrikasi gantungan ban serep | 9.30 | 10.45 | 1.25 | 1.25 | 0.00 | Hendrik | 2 | Tidak | 0.62 | 0.62 | 0.00 |
| 404 | 2026-02-04 | DT-048 | BG 8535 OW | 1~ Underround Protection samping safety tangki bbm | 10.50 | 11.30 | 0.67 | 0.67 | 0.00 | Gabriel | 2 | Tidak | 0.33 | 0.33 | 0.00 |
| 404 | 2026-02-04 | DT-048 | BG 8535 OW | 1~ Underround Protection samping safety tangki bbm | 10.50 | 11.30 | 0.67 | 0.67 | 0.00 | Hendrik | 2 | Tidak | 0.33 | 0.33 | 0.00 |
| 406 | 2026-02-04 |  | BM 8174 NU | Ganti bohlam lampu besar sebelah kiri H-4<br>MOHON SIAPAPUN YANG BACA INI TERUTAMA MEKANIK UNIT INI DITAHUN 2025 SUDAH DISERVICE DI KM 74.618 DITANGGAL: 4/8/2025 jika SAAT INI TERIHAT PROSES PERBAIKAN DI KM 81.245 KM AKAN TIBA MASA SERVICE DI KM 82.118 BERARTI KURANG 873 KM MOHON MULAI DIMONITOR DARI SEKARANG | 13.55 | 14.23 | 0.47 | 0.47 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 407 | 2026-02-04 | DT-013 | B 9644 KYW | permintaan seperpat untuk dt 00053<br>~pompa minyak ( hand pump )<br>selesai pekerjaan ganti pompa minyak (hand pump) | 14.00 | 15.37 | 1.62 | 1.62 | 0.00 | Rezeki Siregar | 1 | Tidak | 1.62 | 1.62 | 0.00 |
| 408 | 2026-02-04 |  | BM 9509 QO | selesai pekerjaan setel rem semu roda<br>MOHON SIAPAPUN YANG BACA INI TERUTAMA MEKANIK UNIT INI DITAHUN 2025 SUDAH DISERVICE DI KM 75.000 DITANGGAL: 30/8/2025 jika SAAT INI TERIHAT PROSES PERBAIKAN DI KM 83.244 KM BAHWA UNIT INI TERLAMBAT S/D 8.244 | 14.06 | 15.39 | 1.55 | 1.55 | 0.00 | Urwatul Usk | 1 | Tidak | 1.55 | 1.55 | 0.00 |
| 409 | 2026-02-04 | DT-06 | B 9102 ZYT | Selesai proses penurunan transmisi DT00047 yang habis kain klos izin pak buk dilanjut bsok | 15.41 | 16.02 | 0.35 | 0.32 | 0.03 | Urwatul Usk | 1 | Tidak | 0.35 | 0.32 | 0.03 |
| 410 | 2026-02-04 | DT-01 | DT 8126 JE | Ganti piston master clos bawah.<br>Dan perbaikan kunci pintu kanan | 15.45 | 16.30 | 0.75 | 0.25 | 0.50 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.75 | 0.25 | 0.50 |
| 411 | 2026-02-04 |  | BM 9834 DK | 1.PERBAIKAN UJUNG PIPA SOLAR <br>SOLAR BOCOR SUDAH DI TADAH DENGAN DERIGEN DAN DI TUANGKAN KEMBALI <br>2.REPLACE PELUMAS 15W/40 SHELL<br>5 liter DI KM 418.679 <br>KEMBALI DI KM 423.679 | 15.15 | 16.35 | 1.33 | 0.75 | 0.58 | Rahmad K | 1 | Tidak | 1.33 | 0.75 | 0.58 |

## Data 2026-02-05

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 412 | 2026-02-05 | DT-01 | DT 8126 JE | Ganti bola lampu besar h4 sebanyak 2pcs | 8.04 | 8.10 | 0.10 | 0.10 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.10 | 0.10 | 0.00 |
| 413 | 2026-02-05 |  | BM 9949 JO | Menambang oil hidroliy sebanyak 2 liter | 8.11 | 8.22 | 0.18 | 0.18 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.18 | 0.18 | 0.00 |
| 414 | 2026-02-05 | DT-044 | BG 8041 NI | Jenis  Pekerjaan :  <br>✅1~ Underround Protection Broken <br>✅2~ Underround Safety Samping broken <br>✅3~ Safety Bareket Apar broken <br>✅4~ Safety penahan lumpur Samping kiri bagian belakang broken. | 8.03 | 9.54 | 1.85 | 1.85 | 0.00 | Rahmad K | 2 | Tidak | 0.92 | 0.92 | 0.00 |
| 414 | 2026-02-05 | DT-044 | BG 8041 NI | Jenis  Pekerjaan :  <br>✅1~ Underround Protection Broken <br>✅2~ Underround Safety Samping broken <br>✅3~ Safety Bareket Apar broken <br>✅4~ Safety penahan lumpur Samping kiri bagian belakang broken. | 8.03 | 9.54 | 1.85 | 1.85 | 0.00 | Suwardi | 2 | Tidak | 0.92 | 0.92 | 0.00 |
| 415 | 2026-02-05 | DT-06 | B 9102 ZYT | terinfo lanjutan proses pemasangan dish clutch | 10.09 | 18.31 | 8.37 | 5.85 | 2.52 | Afriyandi | 3 | Tidak | 2.79 | 1.95 | 0.84 |
| 415 | 2026-02-05 | DT-06 | B 9102 ZYT | terinfo lanjutan proses pemasangan dish clutch | 10.09 | 18.31 | 8.37 | 5.85 | 2.52 | Rahmad K | 3 | Tidak | 2.79 | 1.95 | 0.84 |
| 415 | 2026-02-05 | DT-06 | B 9102 ZYT | terinfo lanjutan proses pemasangan dish clutch | 10.09 | 18.31 | 8.37 | 5.85 | 2.52 | Urwatul Usk | 3 | Tidak | 2.79 | 1.95 | 0.84 |
| 416 | 2026-02-05 | DT-098 | BM 8174 NU | 1~ welding engsel ombeng atas sisi kiri broken<br>MOHON DIBANTU P JHONI ESOK SERVICE <br>KURANG 814 KM LAGI TIBA WAKTU SERVICE | 10.00 | 13.45 | 3.75 | 3.75 | 0.00 | Gabriel | 2 | Tidak | 1.88 | 1.88 | 0.00 |
| 416 | 2026-02-05 | DT-098 | BM 8174 NU | 1~ welding engsel ombeng atas sisi kiri broken<br>MOHON DIBANTU P JHONI ESOK SERVICE <br>KURANG 814 KM LAGI TIBA WAKTU SERVICE | 10.00 | 13.45 | 3.75 | 3.75 | 0.00 | Hendrik | 2 | Tidak | 1.88 | 1.88 | 0.00 |
| 417 | 2026-02-05 | DT-013 | B 9644 KYW | INFORMASI DENSO PRIMPING PUMP | 11.38 | 11.57 | 0.32 | 0.32 | 0.00 | Rezeki Siregar | 1 | Ya | 0.32 | 0.32 | 0.00 |
| 418 | 2026-02-05 | DT-011 | B 9892 PYW | Ganti Lampu H4 Mati | 11.59 | 12.10 | 0.18 | 0.18 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.18 | 0.18 | 0.00 |
| 420 | 2026-02-05 | DT-035 | B 9914 JYT | Semprong saring an udara | 13.58 | 14.15 | 0.28 | 0.28 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.28 | 0.28 | 0.00 |
| 421 | 2026-02-05 | DT-43 | B 9091 ZYT | Jenis  Pekerjaan :  <br>✅1~ pergantian fuel filter Solah bawah JAF20<br>✅2~ penyetelan belt ac | 10.25 | 10.59 | 0.57 | 0.57 | 0.00 | Rahmad K | 1 | Tidak | 0.57 | 0.57 | 0.00 |
| 422 | 2026-02-05 |  | BM 9969 JO | Pebaiki selang angin cramber blry  dan menyeter pto<br>PERNAH DIINFOMASIKAN KEMBALI YANG KE 4 INI UNIT INI TERJADWAL SERVICE DI KM 80.236 MENGAPA DIKARENAKAN PM SERVICE LAMPAU SAAT DI VADANA DI KM 70.236 DITANGGAL11/7/2025<br>JIKA DI TANGGAL : 5/5/2026 TERBACA KM SAAT INI 80.413 KM MAKA UNIT INI KETERLAMBATAN SERVICE 177 KM <br>MOHON P JHONI &amp; REGAR SERVICE KAN UNIT INI | 15.30 | 16.15 | 0.75 | 0.50 | 0.25 | Rezeki Siregar | 1 | Tidak | 0.75 | 0.50 | 0.25 |
| 423 | 2026-02-05 | DT-047 | BG 8976 IX | Ganti bola lampu belakang sebelah kiri mati | 15.55 | 16.26 | 0.52 | 0.08 | 0.43 | Rezeki Siregar | 1 | Tidak | 0.52 | 0.08 | 0.43 |
| 424 | 2026-02-05 | DT-070 | B 9131 ZYT | Selesai stel fambel AC | 16.00 | 16.31 | 0.52 | 0.00 | 0.52 | Afriyandi | 1 | Tidak | 0.52 | 0.00 | 0.52 |
| 426 | 2026-02-05 |  | BM 9509 QO | Jenis  Pekerjaan :  <br>✅1~ pergantian fuel filter Solah bawah dan atas <br>JAA10<br>JAC70<br>JAF40<br>JAE15 | 16.08 | 17.34 | 1.43 | 0.00 | 1.43 | Rahmad K | 1 | Tidak | 1.43 | 0.00 | 1.43 |

## Data 2026-02-06

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 427 | 2026-02-06 | LB-01 | B 9012 ZEH . | Jenis  Pekerjaan :  <br>✅1~ pergantian fuel filter Solar <br>bawah<br>5223022134<br>Atas<br>23964910 | 8.43 | 9.49 | 1.10 | 1.10 | 0.00 | Rahmad K | 1 | Tidak | 1.10 | 1.10 | 0.00 |
| 429 | 2026-02-06 |  | BM 9510 QO | Proses perbaikan kebocoran | 10.22 | 11.07 | 0.75 | 0.75 | 0.00 | Afriyandi | 2 | Tidak | 0.38 | 0.38 | 0.00 |
| 429 | 2026-02-06 |  | BM 9510 QO | Proses perbaikan kebocoran | 10.22 | 11.07 | 0.75 | 0.75 | 0.00 | Urwatul Usk | 2 | Tidak | 0.38 | 0.38 | 0.00 |
| 430 | 2026-02-06 | DT-056 | B 9115 ZYT | DISMANTLE DISC CLUTH LIMIT | 08.10 | 17.05 | 8.92 | 7.83 | 1.08 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 8.92 | 7.83 | 1.08 |
| 431 | 2026-02-06 | DT-053 | B 9133 ZYT | Selesai pergantian bola lampu kabut unit | 10.50 | 11.11 | 0.35 | 0.35 | 0.00 | Afriyandi | 2 | Tidak | 0.18 | 0.18 | 0.00 |
| 431 | 2026-02-06 | DT-053 | B 9133 ZYT | Selesai pergantian bola lampu kabut unit | 10.50 | 11.11 | 0.35 | 0.35 | 0.00 | Urwatul Usk | 2 | Tidak | 0.18 | 0.18 | 0.00 |
| 432 | 2026-02-06 | DT-09 | B 9105 ZYT | Kerusakan: selang angin klakson bocor. | 10.04 | 11.17 | 1.22 | 1.22 | 0.00 | Afriyandi | 2 | Tidak | 0.61 | 0.61 | 0.00 |
| 432 | 2026-02-06 | DT-09 | B 9105 ZYT | Kerusakan: selang angin klakson bocor. | 10.04 | 11.17 | 1.22 | 1.22 | 0.00 | Urwatul Usk | 2 | Tidak | 0.61 | 0.61 | 0.00 |
| 434 | 2026-02-06 | DT-06 | B 9102 ZYT | WIPER KACA | 12.55 | 13.23 | 0.47 | 0.47 | 0.00 | Urwatul Usk | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 436 | 2026-02-06 | LB-01 | KT 9287 KU | Ganti 2pcs fuell filter atas &amp; bawah ( LOW POWER ) | 13.30 | 15.57 | 2.45 | 2.45 | 0.00 | Darmawan | 1 | Tidak | 2.45 | 2.45 | 0.00 |
| 437 | 2026-02-06 |  | BM 9819 QO | Ganti 2pcs fuell filter atas &amp; bawah ( LOW POWER ) | 15.10 | 15.59 | 0.82 | 0.82 | 0.00 | Darmawan | 1 | Tidak | 0.82 | 0.82 | 0.00 |
| 438 | 2026-02-06 | DT-02 | DT-8973 IE | Penggantian filter udara | 16.07 | 16.35 | 0.47 | 0.00 | 0.47 | Urwatul Usk | 1 | Tidak | 0.47 | 0.00 | 0.47 |
| 439 | 2026-02-06 |  | BM 9244 NU | Perbaikan booster close bawah<br>PER BAWAH MATAHARI | 10.29 | 16.40 | 6.18 | 5.52 | 0.67 | Urwatul Usk | 1 | Tidak | 6.18 | 5.52 | 0.67 |
| 440 | 2026-02-06 | DT-058 | B 9117 ZYT | Perbaikan steep depan bengkok | 13.45 | 15.00 | 1.25 | 1.25 | 0.00 | Gabriel | 2 | Tidak | 0.62 | 0.62 | 0.00 |
| 440 | 2026-02-06 | DT-058 | B 9117 ZYT | Perbaikan steep depan bengkok | 13.45 | 15.00 | 1.25 | 1.25 | 0.00 | Hendrik | 2 | Tidak | 0.62 | 0.62 | 0.00 |
| 441 | 2026-02-06 |  | BM 9819 QO | Jenis  Pekerjaan : <br>✅Perbaikan Pada Pin Crosshead bagian kanan belakang.( Terlepas ). | 14.47 | 16.46 | 1.98 | 1.22 | 0.77 | Rahmad K | 2 | Tidak | 0.99 | 0.61 | 0.38 |
| 441 | 2026-02-06 |  | BM 9819 QO | Jenis  Pekerjaan : <br>✅Perbaikan Pada Pin Crosshead bagian kanan belakang.( Terlepas ). | 14.47 | 16.46 | 1.98 | 1.22 | 0.77 | Suwardi | 2 | Tidak | 0.99 | 0.61 | 0.38 |

## Data 2026-02-07

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 442 | 2026-02-07 | DT-049 | B 9107 ZYT | GANTI BOHLAM SEIN &amp; KOTA MATI | 7.55 | 8.25 | 0.50 | 0.50 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.50 | 0.50 | 0.00 |
| 443 | 2026-02-07 | DT-058 | B 9117 ZYT | Mepebaiki tangang sebelah kiri rusak dt0010 | 8.40 | 8.51 | 0.18 | 0.18 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.18 | 0.18 | 0.00 |
| 444 | 2026-02-07 | DT-047 | BG 8976 IX | GANTI BOHLAM SEIN &amp; KOTA MATI | 8.38 | 8.41 | 0.05 | 0.05 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.05 | 0.05 | 0.00 |
| 445 | 2026-02-07 |  | BM 9291 JO | ASSEMBLY TENSIONER | 8.45 | 10.50 | 2.08 | 2.08 | 0.00 | Agung S | 2 | Tidak | 1.04 | 1.04 | 0.00 |
| 445 | 2026-02-07 |  | BM 9291 JO | ASSEMBLY TENSIONER | 8.45 | 10.50 | 2.08 | 2.08 | 0.00 | Darmawan | 2 | Tidak | 1.04 | 1.04 | 0.00 |
| 446 | 2026-02-07 | DT-035 | B 9914 JYT | GANTI LAMPU KOTA | 8.49 | 9.52 | 1.05 | 1.05 | 0.00 | Rezeki Siregar | 1 | Tidak | 1.05 | 1.05 | 0.00 |
| 447 | 2026-02-07 | DT-022 | Z 9109 AB | Ganti selang angin dan konetor selang bocor | 10.55 | 11.31 | 0.60 | 0.60 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.60 | 0.60 | 0.00 |
| 448 | 2026-02-07 | DT-019 | BG 8367 IJ | Mengglas dudu kqn ban serap | 10.59 | 11.30 | 0.52 | 0.52 | 0.00 | Hendrik | 1 | Tidak | 0.52 | 0.52 | 0.00 |
| 449 | 2026-02-07 |  | BM 9510 QO | selesai pekerjaan setel rem semua roda | 12.45 | 13.46 | 1.02 | 1.02 | 0.00 | Urwatul Usk | 1 | Tidak | 1.02 | 1.02 | 0.00 |
| 450 | 2026-02-07 | DT-013 | B 9644 KYW | Proses pengerjaan buka ban bocor | 13.47 | 15.30 | 1.72 | 1.72 | 0.00 | Urwatul Usk | 1 | Tidak | 1.72 | 1.72 | 0.00 |
| 451 | 2026-02-07 | LB-01 | B 9012 ZEH | Sambung Rantai untuk kebutuhan Unit lowboy.( Selesai ) | 13.59 | 14.22 | 0.38 | 0.38 | 0.00 | Darmawan | 2 | Tidak | 0.19 | 0.19 | 0.00 |
| 451 | 2026-02-07 | LB-01 | B 9012 ZEH | Sambung Rantai untuk kebutuhan Unit lowboy.( Selesai ) | 13.59 | 14.22 | 0.38 | 0.38 | 0.00 | Suwardi | 2 | Tidak | 0.19 | 0.19 | 0.00 |
| 452 | 2026-02-07 |  | BM 9551 JO | KUNCI OMBENG | 14.59 | 15.30 | 0.52 | 0.52 | 0.00 | Hendrik | 1 | Tidak | 0.52 | 0.52 | 0.00 |
| 453 | 2026-02-07 |  | BM 9289 JO | RUNNING ENGINE DAN CARGE ACCU | 16.17 | 17.10 | 0.88 | 0.00 | 0.88 | Rahmad K | 1 | Tidak | 0.88 | 0.00 | 0.88 |

## Data 2026-02-08

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 454 | 2026-02-08 | DT-103 | BM 9285 JO | Melanjutkan proses pemasangan disk clutch | 10.09 | 15.31 | 5.37 | 5.37 | 0.00 | Afriyandi | 1 | Tidak | 5.37 | 5.37 | 0.00 |
| 456 | 2026-02-08 | DT-091 | BM 9949 JO | Jenis  Pekerjaan :  <br>✅1~ pergantian fuel filter Solah bawah dan atas <br>JAA10<br>JAC70<br>JAF40<br>JAE15 | 12.05 | 14.19 | 2.23 | 2.23 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 1.12 | 1.12 | 0.00 |
| 456 | 2026-02-08 | DT-091 | BM 9949 JO | Jenis  Pekerjaan :  <br>✅1~ pergantian fuel filter Solah bawah dan atas <br>JAA10<br>JAC70<br>JAF40<br>JAE15 | 12.05 | 14.19 | 2.23 | 2.23 | 0.00 | Rezeki Siregar | 2 | Tidak | 1.12 | 1.12 | 0.00 |

## Data 2026-02-09

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 461 | 2026-02-09 |  |  | GANTI ACCU BARU GS N 70 X2 12 VOLT | 8.10 | 9.30 | 1.33 | 1.33 | 0.00 | Agung S | 2 | Tidak | 0.67 | 0.67 | 0.00 |
| 461 | 2026-02-09 |  |  | GANTI ACCU BARU GS N 70 X2 12 VOLT | 8.10 | 9.30 | 1.33 | 1.33 | 0.00 | Rahmad K | 2 | Tidak | 0.67 | 0.67 | 0.00 |
| 462 | 2026-02-09 |  | BM 9682 JO | ELECTRICAL SYSTEM ERROR <br>elektrik/ kabel2 ada yg shot. | 9.40 | 18.00 | 8.33 | 6.33 | 2.00 | Agung S | 2 | Tidak | 4.17 | 3.17 | 1.00 |
| 462 | 2026-02-09 |  | BM 9682 JO | ELECTRICAL SYSTEM ERROR <br>elektrik/ kabel2 ada yg shot. | 9.40 | 18.00 | 8.33 | 6.33 | 2.00 | Rahmad K | 2 | Tidak | 4.17 | 3.17 | 1.00 |
| 464 | 2026-02-09 | DT-046 | BG 8638 NI | Proses welding underaun kiri,bracket mufler <br>DIKM SAAT INI TERBACA SEMESTINYA PLANNER SUDAH MEMBUAT JADWAL PM 5 YANG TERDIRI DARI :<br>1.GANTI OLI FILTER TRANSMISSI + GANTI OLINYA <br>2.GANTI OLI FILTER DIFERENSIAL <br>+ Ganti Olinya <br>3.CLUTH FLUIN<br>4.MINYAK P.STEERUNG <br>5.GRASE WELL BEARING | 8.55 | 9.57 | 1.03 | 1.03 | 0.00 | Darmawan | 1 | Tidak | 1.03 | 1.03 | 0.00 |
| 466 | 2026-02-09 | DT 096 | BM9287JO | Jenis  Pekerjaan : <br>1~ Engsel Pintu Ombeng kanan/kiri broken ( patah ).<br>2~ Kunci pintu Ombeng Bengkok ( posisi pintu tidak presisi ). | 10.43 | 19.01 | 8.30 | 5.28 | 3.02 | Rahmad K | 2 | Tidak | 4.15 | 2.64 | 1.51 |
| 466 | 2026-02-09 | DT 096 | BM9287JO | Jenis  Pekerjaan : <br>1~ Engsel Pintu Ombeng kanan/kiri broken ( patah ).<br>2~ Kunci pintu Ombeng Bengkok ( posisi pintu tidak presisi ). | 10.43 | 19.01 | 8.30 | 5.28 | 3.02 | Suwardi | 2 | Tidak | 4.15 | 2.64 | 1.51 |
| 467 | 2026-02-09 | DT-14 | B 9683 KYW | Proses welding safety Tanki fuell patah | 11.01 | 11.28 | 0.45 | 0.45 | 0.00 | Darmawan | 1 | Tidak | 0.45 | 0.45 | 0.00 |
| 470 | 2026-02-09 | DT-105 | BM 9398 JO | penyetelan tojok close  penyetelan V-Belt AC | 10.55 | 11.56 | 1.02 | 1.02 | 0.00 | Afriyandi | 2 | Tidak | 0.51 | 0.51 | 0.00 |
| 470 | 2026-02-09 | DT-105 | BM 9398 JO | penyetelan tojok close  penyetelan V-Belt AC | 10.55 | 11.56 | 1.02 | 1.02 | 0.00 | Urwatul Usk | 2 | Tidak | 0.51 | 0.51 | 0.00 |
| 471 | 2026-02-09 | DT-100 | BM 9509 QO | Proses perbaikan selang solar atas bocor | 12.09 | 18.09 | 6.00 | 3.85 | 2.15 | Afriyandi | 2 | Tidak | 3.00 | 1.92 | 1.08 |
| 471 | 2026-02-09 | DT-100 | BM 9509 QO | Proses perbaikan selang solar atas bocor | 12.09 | 18.09 | 6.00 | 3.85 | 2.15 | Urwatul Usk | 2 | Tidak | 3.00 | 1.92 | 1.08 |
| 472 | 2026-02-09 | DT-020 | B 9973 BIS | 1.per jiwa belakang no1 patah | 13.44 | 18.03 | 4.32 | 2.27 | 2.05 | Afriyandi | 2 | Tidak | 2.16 | 1.13 | 1.02 |
| 472 | 2026-02-09 | DT-020 | B 9973 BIS | 1.per jiwa belakang no1 patah | 13.44 | 18.03 | 4.32 | 2.27 | 2.05 | Urwatul Usk | 2 | Tidak | 2.16 | 1.13 | 1.02 |
| 473 | 2026-02-09 | DT-033 | DT 8669 KE | SAFETY ACCU PROBLEM | 12.57 | 14.01 | 1.07 | 1.07 | 0.00 | Darmawan | 1 | Ya | 1.07 | 1.07 | 0.00 |
| 474 | 2026-02-09 | DT-056 | B 9115 ZYT | Proses perbaikan unitpipa dalam tangki solar patah. | 14.27 | 17.00 | 2.55 | 1.55 | 1.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 2.55 | 1.55 | 1.00 |
| 475 | 2026-02-09 |  | BM 8460 NU | kendala unit tidak mau nyala<br>ACCU SOAK | 14.28 | 15.10 | 0.70 | 0.70 | 0.00 | Agung S | 2 | Ya | 0.35 | 0.35 | 0.00 |
| 475 | 2026-02-09 |  | BM 8460 NU | kendala unit tidak mau nyala<br>ACCU SOAK | 14.28 | 15.10 | 0.70 | 0.70 | 0.00 | Rahmad K | 2 | Ya | 0.35 | 0.35 | 0.00 |

## Data 2026-02-10

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 485 | 2026-02-10 | DT-049 | B 9107 ZYT | Jenis  Pekerjaan : <br>1~ Tank Hidrolik pecah pada sisi tengah bagian<br>Proses cuci/cleaning pada perangkat Level Tank...dan Tank Hidrolik.. | 8.07 | 10.18 | 2.18 | 2.18 | 0.00 | Rahmad K | 2 | Tidak | 1.09 | 1.09 | 0.00 |
| 485 | 2026-02-10 | DT-049 | B 9107 ZYT | Jenis  Pekerjaan : <br>1~ Tank Hidrolik pecah pada sisi tengah bagian<br>Proses cuci/cleaning pada perangkat Level Tank...dan Tank Hidrolik.. | 8.07 | 10.18 | 2.18 | 2.18 | 0.00 | Suwardi | 2 | Tidak | 1.09 | 1.09 | 0.00 |
| 486 | 2026-02-10 |  | BM 8460 NU | 2 unit accu sudah di cas 1 sudah tidak dapat menyimpan arus , unit ini untuk projeck meating bord. | 15.33 | 17.44 | 2.18 | 0.45 | 1.73 | Rahmad K | 1 | Tidak | 2.18 | 0.45 | 1.73 |
| 488 | 2026-02-10 | DT-056 | B 9115 ZYT | Proses  pengelasan kawat Kuningan pipa hisap tangki solar | 9.18 | 10.26 | 1.13 | 1.13 | 0.00 | Darmawan | 2 | Tidak | 0.57 | 0.57 | 0.00 |
| 488 | 2026-02-10 | DT-056 | B 9115 ZYT | Proses  pengelasan kawat Kuningan pipa hisap tangki solar | 9.18 | 10.26 | 1.13 | 1.13 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 0.57 | 0.57 | 0.00 |
| 489 | 2026-02-10 | DT-033 | DT 8669 KE | Penggantian 1 bh <br>Kabel seri baterai | 8.59 | 9.24 | 0.42 | 0.42 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.42 | 0.42 | 0.00 |
| 490 | 2026-02-10 | DT-048 | BG 8535 OW | Proses welding breaket ganjal ban | 10.05 | 10.55 | 0.83 | 0.83 | 0.00 | Darmawan | 1 | Tidak | 0.83 | 0.83 | 0.00 |
| 492 | 2026-02-10 | DT-033 | BG 8230 MX | selang angin induk compressor menuju tabung angin bocor | 11.00 | 11.52 | 0.87 | 0.87 | 0.00 | Darmawan | 1 | Tidak | 0.87 | 0.87 | 0.00 |
| 493 | 2026-02-10 | DT-010 | B 9701 PYW | PERBAIKAN SELANG ANGIN BOCOR | 11.00 | 11.43 | 0.72 | 0.72 | 0.00 | Urwatul Usk | 1 | Tidak | 0.72 | 0.72 | 0.00 |
| 495 | 2026-02-10 | DT-09 | B 9105 ZYT | 1.Boster clos bawah bocor | 13.44 | 14.20 | 0.60 | 0.60 | 0.00 | Afriyandi | 1 | Tidak | 0.60 | 0.60 | 0.00 |
| 496 | 2026-02-10 | DT-024 | B 9079 ZYT | Ganti spakbor depan sebelah kiri. | 13.55 | 14.49 | 0.90 | 0.90 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.90 | 0.90 | 0.00 |
| 497 | 2026-02-10 | DT-08 | B 9104 ZYT | Ijin permintaan seperpat untuk dt 00049<br>~bering/lahar poly AC 1 pcs | 15.01 | 15.29 | 0.47 | 0.47 | 0.00 | Urwatul Usk | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 499 | 2026-02-10 | DT-035 | B 9914 JYT | low power<br>ganti filter solar 1pcs bawah | 15.34 | 16.34 | 1.00 | 0.43 | 0.57 | Darmawan | 1 | Tidak | 1.00 | 0.43 | 0.57 |
| 501 | 2026-02-10 | DT-029 | B 9137 ZYT | GANTI BAUT DORA PUTIS | 12.30 | 15.38 | 3.13 | 3.13 | 0.00 | Urwatul Usk | 1 | Tidak | 3.13 | 3.13 | 0.00 |
| 502 | 2026-02-10 | DT-55 | B 9114 ZYT | Stel tojok KLOS. | 15.06 | 16.00 | 0.90 | 0.90 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.90 | 0.90 | 0.00 |
| 506 | 2026-02-10 |  | BM 9291 JO | pasang accu baru GS N 70 X 2 12 V | 13.05 | 17.53 | 4.80 | 2.92 | 1.88 | Rahmad K | 1 | Tidak | 4.80 | 2.92 | 1.88 |

## Data 2026-02-11

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 507 | 2026-02-11 | 101 | BM 9682 JO | Jenis  Pekerjaan : <br>✅1 • perbaikan unit low power dan electrical eror dan proses running unit DT - 04032 sesaat close / ready unit | 08.08 | 10.22 | 2.23 | 2.23 | 0.00 | Rahmad K | 1 | Tidak | 2.23 | 2.23 | 0.00 |
| 508 | 2026-02-11 |  | BG 8638 NI | Jenis  Pekerjaan : service berkala<br>-oli mesin 25Ltr<br>-oli transmisi proses pengecekan<br>-oli gardan proses pengecekan<br>-filter oli 2 pcs<br>-filter solar 3pcs | 10.00 | 14.00 | 4.00 | 4.00 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 4.00 | 4.00 | 0.00 |
| 509 | 2026-02-11 |  | BM 9244 NU | ASSEMBLY DISC CLUTH SET :<br>Jenis  Pekerjaan : <br>1.disc clutch <br>2.bearing <br>3.stel V-Belt AC | 10.48 | 14.10 | 3.37 | 3.37 | 0.00 | Afriyandi | 2 | Tidak | 1.68 | 1.68 | 0.00 |
| 509 | 2026-02-11 |  | BM 9244 NU | ASSEMBLY DISC CLUTH SET :<br>Jenis  Pekerjaan : <br>1.disc clutch <br>2.bearing <br>3.stel V-Belt AC | 10.48 | 14.10 | 3.37 | 3.37 | 0.00 | Urwatul Usk | 2 | Tidak | 1.68 | 1.68 | 0.00 |
| 514 | 2026-02-11 | DT-069 | B 9130 ZYT | Jenis  Pekerjaan : <br>1~perbaikan safety kunci ombeng  (kanan.kiri) | 10.32 | 11.45 | 1.22 | 1.22 | 0.00 | Hendrik | 1 | Tidak | 1.22 | 1.22 | 0.00 |
| 515 | 2026-02-11 | DT-014 | B 9683 KYW | Ganti Kunci Kontak Baru | 13.02 | 13.30 | 0.47 | 0.47 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 517 | 2026-02-11 |  | BM 9285 JO | 1.penambahan oli mesin 3L | 14.55 | 15.03 | 0.13 | 0.13 | 0.00 | Afriyandi | 2 | Tidak | 0.07 | 0.07 | 0.00 |
| 517 | 2026-02-11 |  | BM 9285 JO | 1.penambahan oli mesin 3L | 14.55 | 15.03 | 0.13 | 0.13 | 0.00 | Urwatul Usk | 2 | Tidak | 0.07 | 0.07 | 0.00 |
| 519 | 2026-02-11 | DT-101 | BM 9682 JO | Jenis  Pekerjaan : <br>✅1~ pemasangan bracket  intercoler<br>LAPORAN RUNNING KED 2 SETELAH DI RPM 3300 TERINFO SUDAH CLOSE PROBLEM MOHON ESOK AGAR DRUVER CEK ULANG KELUHAN JIKA MASIH TERJADI | 16.03 | 16.33 | 0.50 | 0.00 | 0.50 | Rahmad K | 1 | Tidak | 0.50 | 0.00 | 0.50 |
| 521 | 2026-02-11 | DT-043 | BG 8367 IJ | CUCI FILTER SOLAR | 17.37 | 18.03 | 0.43 | 0.00 | 0.43 | Rezeki Siregar | 1 | Tidak | 0.43 | 0.00 | 0.43 |

## Data 2026-02-12

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 522 | 2026-02-12 | WT-01 | BA 9016 QU | Jenis  Pekerjaan : <br>1.filter solar tidak mau di pompa | 8.34 | 10.44 | 2.17 | 2.17 | 0.00 | Afriyandi | 1 | Tidak | 2.17 | 2.17 | 0.00 |
| 524 | 2026-02-12 |  | BM 9551 JO | Jenis  Pekerjaan : <br>1.wellding keduduk breacket apar | 9.32 | 9.52 | 0.33 | 0.33 | 0.00 | Hendrik | 1 | Tidak | 0.33 | 0.33 | 0.00 |
| 528 | 2026-02-12 | DT-033 | BG 8230 MX | Jenis  Pekerjaan : <br>1.wellding breacket muffler | 10.00 | 10.43 | 0.72 | 0.72 | 0.00 | Hendrik | 1 | Tidak | 0.72 | 0.72 | 0.00 |
| 533 | 2026-02-12 |  | B 9819 QO | Jenis  Pekerjaan : <br>1.wellding speackboard retak sisi kanan(temuan TPE) | 13.35 | 14.12 | 0.62 | 0.62 | 0.00 | Hendrik | 1 | Ya | 0.62 | 0.62 | 0.00 |
| 534 | 2026-02-12 | DT-072 | B 9133ZYT | ~gerising<br>~membersihkan saringan udara | 13.55 | 14.32 | 0.62 | 0.62 | 0.00 | Urwatul Usk | 1 | Tidak | 0.62 | 0.62 | 0.00 |
| 536 | 2026-02-12 | DT-06 | B 9102 ZYT | Jenis  Pekerjaan : <br>1.baut roda putus/patah<br>2.stel rem | 11.11 | 16.17 | 5.10 | 4.82 | 0.28 | Afriyandi | 1 | Tidak | 5.10 | 4.82 | 0.28 |
| 537 | 2026-02-12 | DT-046 | BG 8638 NI | Jenis  Pekerjaan : <br>1.perbaikan keduduk'an kaca spion bengkok | 16.30 | 16.50 | 0.33 | 0.00 | 0.33 | Hendrik | 1 | Tidak | 0.33 | 0.00 | 0.33 |
| 538 | 2026-02-12 | DT-098 | BM 9174 NU. | Jenis  Pekerjaan : <br>✅1~ Repair Muffler broken/bocor<br>✅2~ Repair Safety Underround Samping kiri broken.<br>PM SERVICE PADA INTERVAL INI DI KM 82.118 KM SAAT INI SUDAH TIBA DI KM 81.863 KURANG DARI 255 KM <br>UNIT INI SUDAH DAPAT DISERVICE <br>PIC TEPU FUSO - USKHA | 10.51 | 18.04 | 7.22 | 5.15 | 2.07 | Rahmad K | 2 | Tidak | 3.61 | 2.58 | 1.03 |
| 538 | 2026-02-12 | DT-098 | BM 9174 NU. | Jenis  Pekerjaan : <br>✅1~ Repair Muffler broken/bocor<br>✅2~ Repair Safety Underround Samping kiri broken.<br>PM SERVICE PADA INTERVAL INI DI KM 82.118 KM SAAT INI SUDAH TIBA DI KM 81.863 KURANG DARI 255 KM <br>UNIT INI SUDAH DAPAT DISERVICE <br>PIC TEPU FUSO - USKHA | 10.51 | 18.04 | 7.22 | 5.15 | 2.07 | Suwardi | 2 | Tidak | 3.61 | 2.58 | 1.03 |
| 539 | 2026-02-12 | DT-01 | DT 8126 JE | Peroses saat ini dismantle paking cylinder head , terdapat kebocoran kompresi pada cylinder no 6 , untuk pengecekan sementara , status di lanjut lusa , lanjut ke team welding .DT 00050, bersama | 13.40 | 18.15 | 4.58 | 2.33 | 2.25 | Darmawan | 2 | Ya | 2.29 | 1.17 | 1.12 |
| 539 | 2026-02-12 | DT-01 | DT 8126 JE | Peroses saat ini dismantle paking cylinder head , terdapat kebocoran kompresi pada cylinder no 6 , untuk pengecekan sementara , status di lanjut lusa , lanjut ke team welding .DT 00050, bersama | 13.40 | 18.15 | 4.58 | 2.33 | 2.25 | Rahmad K | 2 | Ya | 2.29 | 1.17 | 1.12 |
| 540 | 2026-02-12 | DT-02 | DT-8973 IE. | Jenis  Pekerjaan : <br>1. underround Protection (Broken) | 18.19 | 22.01 | 3.70 | 0.00 | 3.70 | Rahmad K | 2 | Tidak | 1.85 | 0.00 | 1.85 |
| 540 | 2026-02-12 | DT-02 | DT-8973 IE. | Jenis  Pekerjaan : <br>1. underround Protection (Broken) | 18.19 | 22.01 | 3.70 | 0.00 | 3.70 | Suwardi | 2 | Tidak | 1.85 | 0.00 | 1.85 |
| 541 | 2026-02-12 | DT-09 | B 9105 ZYT | Broken : ~mufler patah<br> ~tiang spion patah<br> ~underan patah beberapa titik<br> ~Breaket lampu belakang retak<br> ~Tangga pijakan Driver broken | 18.45 | 21.00 | 2.25 | 0.00 | 2.25 | Darmawan | 2 | Tidak | 1.12 | 0.00 | 1.12 |
| 541 | 2026-02-12 | DT-09 | B 9105 ZYT | Broken : ~mufler patah<br> ~tiang spion patah<br> ~underan patah beberapa titik<br> ~Breaket lampu belakang retak<br> ~Tangga pijakan Driver broken | 18.45 | 21.00 | 2.25 | 0.00 | 2.25 | Rahmad K | 2 | Tidak | 1.12 | 0.00 | 1.12 |

## Data 2026-02-13

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 543 | 2026-02-13 | DT-06 | B 9102 ZYT | PM SERVICE INTERVAL DI 7500 (PM1)<br>servis berkala <br>*Ganti Oli 15 Ltr <br>*Ganti filter solar 2Pcs<br>*Ganti oli mesin <br>*Ganti filter oli 1 pcs <br>*GANTI Klason &amp; Neole Minyak | 8.10 | 11.06 | 2.93 | 2.93 | 0.00 | Urwatul Usk | 1 | Tidak | 2.93 | 2.93 | 0.00 |
| 545 | 2026-02-13 | DT-44 | BG 8640 NI | Jenis  Pekerjaan : <br>1.baut Dora dol/habis drat :<br>DIDALAM KM SERVICE YANG DITERBITKAN PLANNER DI JAN 26<br>UNIT INI TERJADWAL DI KM 66.455<br>SEHARUSNYA DISIMAK DI GROUP JIKA MENYIMAK UNIT INI TERJDWAL DAN KM LEWAT ESOK MOHON DISERVICE JIKA SAAT INI DITEMUS DI WS LAKUKAN SEKALIAN DI KM 66.659 | 8.29 | 9.28 | 0.98 | 0.98 | 0.00 | Afriyandi | 1 | Tidak | 0.98 | 0.98 | 0.00 |
| 547 | 2026-02-13 | DT-057/WT-02 | BK 9121 QU | Proses penggantian ban untuk unit Water truck ban di canibal dari unit ex 19 | 10.44 | 12.10 | 1.43 | 1.43 | 0.00 | Afriyandi | 1 | Tidak | 1.43 | 1.43 | 0.00 |
| 548 | 2026-02-13 | DT-064 | B 9125 ZYT | Ganti bell Ting AC baru. 1 bh <br>Belting AC yg lama hilang. | 10.55 | 11.17 | 0.37 | 0.37 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.37 | 0.37 | 0.00 |
| 549 | 2026-02-13 | DT-020 | B 9973 BIS | Jenis  Pekerjaan : <br>1.wellding underround protection sisi kiri | 11.00 | 11.20 | 0.33 | 0.33 | 0.00 | Hendrik | 1 | Tidak | 0.33 | 0.33 | 0.00 |
| 550 | 2026-02-13 | DT-09 | B 9105 ZYT | gerising<br>~membersihkan saringan udara <br>~ganti kit jemberit/exhaust brake<br>~ganti filter solar bawah 1 pcs<br>~ganti piston master kelos bawah<br>2701 KM LAGI UNIT INI AKAN TIBA WAKTU SERVICE | 11.07 | 12.00 | 0.88 | 0.88 | 0.00 | Urwatul Usk | 1 | Tidak | 0.88 | 0.88 | 0.00 |
| 551 | 2026-02-13 | DT-020 | B 9973 BIS | Pemasangan belting AC yg lepas. | 14.04 | 14.52 | 0.80 | 0.80 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.80 | 0.80 | 0.00 |
| 552 | 2026-02-13 | DT-032 | B 9094 ZYT | Ganti bohlam lampu besar sebelah kiri | 15.10 | 15.59 | 0.82 | 0.82 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.82 | 0.82 | 0.00 |
| 553 | 2026-02-13 | DT-032 | B 9094 ZYT | selesai pekerjaan <br>~gerising <br>~membersihkan saringan udara | 16.00 | 16.42 | 0.70 | 0.00 | 0.70 | Urwatul Usk | 1 | Tidak | 0.70 | 0.00 | 0.70 |
| 554 | 2026-02-13 | DT-035 | B 9914 JYT | selesai pekerjaan <br>~gerising<br>~membersihkan saringan udara | 16.45 | 17.15 | 0.50 | 0.00 | 0.50 | Urwatul Usk | 1 | Tidak | 0.50 | 0.00 | 0.50 |

## Data 2026-02-14

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 560 | 2026-02-14 | DT 097 | BM9510QO | Jenis  Pekerjaan : <br>1:pengantian tingtong atas 4pcs<br>2pengantian alaram mundur<br>3perbaikan kuncipintu kiri<br>4pemasangan karet Septi lumpur kanan kiri.<br>5penambahan oli hidrolik<br>Penggantian karet susu blakang 4 pcs | 8.25 | 16.00 | 7.58 | 7.58 | 0.00 | Afriyandi | 1 | Tidak | 7.58 | 7.58 | 0.00 |
| 561 | 2026-02-14 | DT-44 | BG 8640 NI | Service pekerjaan :BELUM MAKSUD SERVICE GENERAL <br>~servis berkala <br>  *Ganti filter oli 2 pcs<br>  *Ganti filter solar 3 pcs<br>  *Oli 25 liter<br>~gerising<br>~membersihkan saringan udara | 9.15 | 12.10 | 2.92 | 2.92 | 0.00 | Urwatul Usk | 1 | Tidak | 2.92 | 2.92 | 0.00 |
| 562 | 2026-02-14 | DT-045 | BG 8641 NI | Servis berkala Seharusnya PM 6 artinya service GENERAL <br>  *Ganti filter solar 3 pcs<br>  *Ganti filter oli 2 pcs<br>  *Ganti oli mesin 25 liter<br>~gerising<br>~membersihkan saringan udara | 12.22 | 14.02 | 1.67 | 1.67 | 0.00 | Urwatul Usk | 1 | Tidak | 1.67 | 1.67 | 0.00 |
| 563 | 2026-02-14 |  | B 9080 ZYT | REGRESING | 14.04 | 15.08 | 1.07 | 1.07 | 0.00 | Urwatul Usk | 1 | Tidak | 1.07 | 1.07 | 0.00 |
| 564 | 2026-02-14 |  | B 9093 ZYT | Ganti tutup tengki solar baru | 15.10 | 15.31 | 0.35 | 0.35 | 0.00 | Urwatul Usk | 1 | Tidak | 0.35 | 0.35 | 0.00 |

## Data 2026-02-15

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 566 | 2026-02-15 | DT-010 | B 9701 PYW | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). | 8.21 | 15.42 | 7.35 | 7.35 | 0.00 | Darmawan | 2 | Tidak | 3.68 | 3.68 | 0.00 |
| 566 | 2026-02-15 | DT-010 | B 9701 PYW | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). | 8.21 | 15.42 | 7.35 | 7.35 | 0.00 | Hendrik | 2 | Tidak | 3.68 | 3.68 | 0.00 |
| 567 | 2026-02-15 | LB-02 | B 9012 ZEH . | LORY LOWBOY PADA SUSPENSI PATAH &amp; LEPAS | 8.47 | 19.05 | 10.30 | 7.22 | 3.08 | Afriyandi | 2 | Tidak | 5.15 | 3.61 | 1.54 |
| 567 | 2026-02-15 | LB-02 | B 9012 ZEH . | LORY LOWBOY PADA SUSPENSI PATAH &amp; LEPAS | 8.47 | 19.05 | 10.30 | 7.22 | 3.08 | Darmawan | 2 | Tidak | 5.15 | 3.61 | 1.54 |
| 568 | 2026-02-15 | DT-010 | B 9701 PYW | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). | 7.56 | 15.42 | 7.77 | 7.77 | 0.00 | Hendrik | 2 | Tidak | 3.88 | 3.88 | 0.00 |
| 568 | 2026-02-15 | DT-010 | B 9701 PYW | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). | 7.56 | 15.42 | 7.77 | 7.77 | 0.00 | Rahmad K | 2 | Tidak | 3.88 | 3.88 | 0.00 |

## Data 2026-02-16

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 569 | 2026-02-16 | DT-014 | B 9386 KYW | Ganti gantung ban serap | 8.01 | 8.45 | 0.73 | 0.73 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.73 | 0.73 | 0.00 |
| 570 | 2026-02-16 |  | B 9134 ZYT | PERBAIKAN DASBOARD LAMPU | 8.55 | 9.10 | 0.25 | 0.25 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.25 | 0.25 | 0.00 |
| 571 | 2026-02-16 | DT-098 | BM 9174 NU. | Pebaiki kepala batere longgar | 9.30 | 9.53 | 0.38 | 0.38 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.38 | 0.38 | 0.00 |
| 572 | 2026-02-16 | DT-47 | BG 8976 IX | Slmt pagi aku mintak tolong beli kan penti lampu kaki 2 sebanyak 2 pcs untuk dt 00032 ,makasil atas kerja sama ya | 9.54 | 11.04 | 1.17 | 1.17 | 0.00 | Rezeki Siregar | 1 | Tidak | 1.17 | 1.17 | 0.00 |
| 573 | 2026-02-16 |  | BK 9121 EQ | Jenis  Pekerjaan : <br>1~Fabrikasi dan merubah dimensi tinggi/rendah Safety Underround samping kanan dan kiri.<br>2~ Pasang Bereket Apar.<br>3~ Pasang Bereket dan lampu rotari.<br>4~ Pasang dan instal Emergency Stop.<br>~5 Pasang karet Susu  belakang kanan/kiri 4 pcs.<br>6~ Pasang Safety U Bolt Spring depan dan belakang kanan/kiri 8 pcs.SELESAI OLEH YANDIKARYA | 9.19 | 13.49 | 4.50 | 4.50 | 0.00 | Afriyandi | 3 | Tidak | 1.50 | 1.50 | 0.00 |
| 573 | 2026-02-16 |  | BK 9121 EQ | Jenis  Pekerjaan : <br>1~Fabrikasi dan merubah dimensi tinggi/rendah Safety Underround samping kanan dan kiri.<br>2~ Pasang Bereket Apar.<br>3~ Pasang Bereket dan lampu rotari.<br>4~ Pasang dan instal Emergency Stop.<br>~5 Pasang karet Susu  belakang kanan/kiri 4 pcs.<br>6~ Pasang Safety U Bolt Spring depan dan belakang kanan/kiri 8 pcs.SELESAI OLEH YANDIKARYA | 9.19 | 13.49 | 4.50 | 4.50 | 0.00 | Darmawan | 3 | Tidak | 1.50 | 1.50 | 0.00 |
| 573 | 2026-02-16 |  | BK 9121 EQ | Jenis  Pekerjaan : <br>1~Fabrikasi dan merubah dimensi tinggi/rendah Safety Underround samping kanan dan kiri.<br>2~ Pasang Bereket Apar.<br>3~ Pasang Bereket dan lampu rotari.<br>4~ Pasang dan instal Emergency Stop.<br>~5 Pasang karet Susu  belakang kanan/kiri 4 pcs.<br>6~ Pasang Safety U Bolt Spring depan dan belakang kanan/kiri 8 pcs.SELESAI OLEH YANDIKARYA | 9.19 | 13.49 | 4.50 | 4.50 | 0.00 | Rahmad K | 3 | Tidak | 1.50 | 1.50 | 0.00 |

## Data 2026-02-18

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 582 | 2026-02-18 | DT-072 | B 9133 ZYT | Jenis  Pekerjaan : <br>❎1.~ Underround Protection Patah <br>❎2~ Kotrek Gantungan Ban Serep Broken.<br>❎3~ Fabrikasi Whellchock.<br>❎4~ Ganti Filter Solar bagian Bawah. Close | 8.02 | 11.06 | 3.07 | 3.07 | 0.00 | Rahmad K | 2 | Tidak | 1.53 | 1.53 | 0.00 |
| 582 | 2026-02-18 | DT-072 | B 9133 ZYT | Jenis  Pekerjaan : <br>❎1.~ Underround Protection Patah <br>❎2~ Kotrek Gantungan Ban Serep Broken.<br>❎3~ Fabrikasi Whellchock.<br>❎4~ Ganti Filter Solar bagian Bawah. Close | 8.02 | 11.06 | 3.07 | 3.07 | 0.00 | Suwardi | 2 | Tidak | 1.53 | 1.53 | 0.00 |
| 583 | 2026-02-18 | DT-033 | BG 8230 MX | ASSEMBY RADIATOR ( REPAIR ) <br>KURANG 3.600 KM AKAN TIBA SERVICE DI KM 196374 | 8.29 | 11.04 | 2.58 | 2.58 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 1.29 | 1.29 | 0.00 |
| 583 | 2026-02-18 | DT-033 | BG 8230 MX | ASSEMBY RADIATOR ( REPAIR ) <br>KURANG 3.600 KM AKAN TIBA SERVICE DI KM 196374 | 8.29 | 11.04 | 2.58 | 2.58 | 0.00 | Rezeki Siregar | 2 | Tidak | 1.29 | 1.29 | 0.00 |
| 584 | 2026-02-18 | DT-019 | BG 8367 IJ | Proses repair low power &amp; mobil mati mendadak <br>ADA INDIKASI JARANG MENYIMAK KM SEHINGGA UNIT INI TERLAMBAT SERVICE 848 KM MOHON DISERVICE SEKALIAN | 8.45 | 14.53 | 6.13 | 6.13 | 0.00 | Darmawan | 2 | Tidak | 3.07 | 3.07 | 0.00 |
| 584 | 2026-02-18 | DT-019 | BG 8367 IJ | Proses repair low power &amp; mobil mati mendadak <br>ADA INDIKASI JARANG MENYIMAK KM SEHINGGA UNIT INI TERLAMBAT SERVICE 848 KM MOHON DISERVICE SEKALIAN | 8.45 | 14.53 | 6.13 | 6.13 | 0.00 | Rahmad K | 2 | Tidak | 3.07 | 3.07 | 0.00 |
| 585 | 2026-02-18 | DT-020 | B 9973 BIS | selesai pekerjaan <br>~ganti piston master kelos bawah<br>DI KM 132.575 UNIT INI AKAN SERVICE SAAT INI KURANG 3.639 KM | 9.01 | 10.06 | 1.08 | 1.08 | 0.00 | Urwatul Usk | 1 | Tidak | 1.08 | 1.08 | 0.00 |
| 586 | 2026-02-18 | DT-057 | B 9116 ZYT | Jenis  Pekerjaan : <br>❎1.~ Pabrikasi safety Bumper belakang(broken) | 09.00 | 10.25 | 1.42 | 1.42 | 0.00 | Hendrik | 1 | Tidak | 1.42 | 1.42 | 0.00 |
| 588 | 2026-02-18 | DT-013 | B 9644 KYW | ~nambah oli hidrolik 4 L<br>~perbaikan alaram mundur tidak hidup<br>PERLU PENANGANAN LEBIH DETAIL KARNA PENAMBAHAN S/D 4 LITER | 9.45 | 10.25 | 0.67 | 0.67 | 0.00 | Urwatul Usk | 1 | Tidak | 0.67 | 0.67 | 0.00 |
| 589 | 2026-02-18 | DT-061 | B 9122 ZYT | selesai pekerjaan <br>~penambahan oli hidrolik 6 liter<br>PERLU PENANGANAN LEBIH DETAIL KARNA PENAMBAHAN S/D 6 LITER <br>MOHON BANTU P MARTIN DT INI PAS SERVICE WARANTY DIINFO KAN SEKALIAN KE HINO | 10.28 | 10.50 | 0.37 | 0.37 | 0.00 | Urwatul Usk | 1 | Tidak | 0.37 | 0.37 | 0.00 |
| 591 | 2026-02-18 | DT-075 | B 9136 ZYT | pekerjaan <br>~setel rem semua roda <br>~penambahan oli hidrolik 3 liter<br>PERLU PENANGANAN LEBIH DETAIL KARNA PENAMBAHAN S/D 5 LITER | 11.11 | 11.26 | 0.25 | 0.25 | 0.00 | Urwatul Usk | 1 | Tidak | 0.25 | 0.25 | 0.00 |
| 592 | 2026-02-18 | DT-078 | B 9139 ZYT | Jenis  Pekerjaan : <br>1~Wellding safety keduduk'an Dump | 10.50 | 11.25 | 0.58 | 0.58 | 0.00 | Hendrik | 1 | Tidak | 0.58 | 0.58 | 0.00 |
| 593 | 2026-02-18 | DT-092 | BM 9819 QO | Telah selesai pengantian karet susu belakang 4pcs<br>TERJADWAL SERVICE DI KM 76.239<br>DISAAT INI KM PERBAIKAN 78.688 JIKA MEKANIK TELITI SAAT DIINFO DI TANGGAL 16 JANUARI 2026 SEHARUSNYA BISA LANGSUNG SERVICE GASWAT KALAU BEGINI TERUS (SUDAH TERLAMBAT ) | 08.10 | 11.29 | 3.32 | 3.32 | 0.00 | Rezeki Siregar | 1 | Tidak | 3.32 | 3.32 | 0.00 |
| 594 | 2026-02-18 | DT-033 | BG 8230 MX | Tambang oil hridoliy<br>PERLU PERHATIAN KHUSU PERBAIKAN INI TAMBAH OLI SAMPAI LAH=BIH DARI 1 LITER | 11.30 | 11.41 | 0.18 | 0.18 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.18 | 0.18 | 0.00 |
| 596 | 2026-02-18 | DT-043 | BG 8639 NI | Jenis  Pekerjaan : <br>1~ Wellding safety underround protection sisi kiri | 13.25 | 13.40 | 0.25 | 0.25 | 0.00 | Hendrik | 1 | Tidak | 0.25 | 0.25 | 0.00 |
| 597 | 2026-02-18 | DT-093 | BM 9296 JO | Jenis  Pekerjaan : <br>❎1~ Kunci Pintu ombeng patah sebelah kiri. | 13.27 | 14.18 | 0.85 | 0.85 | 0.00 | Rahmad K | 2 | Tidak | 0.42 | 0.42 | 0.00 |
| 597 | 2026-02-18 | DT-093 | BM 9296 JO | Jenis  Pekerjaan : <br>❎1~ Kunci Pintu ombeng patah sebelah kiri. | 13.27 | 14.18 | 0.85 | 0.85 | 0.00 | Suwardi | 2 | Tidak | 0.42 | 0.42 | 0.00 |
| 599 | 2026-02-18 | DT-02 | DT 8973 IE | pekerjaan <br>~servis berkala <br>  *Ganti oli mesin 15 L<br>  *Ganti filter solar 2 pcs<br>  *Ganti filter oli 1 pcs<br>~membersih kan saringan udara | 13.30 | 14.14 | 0.73 | 0.73 | 0.00 | Urwatul Usk | 1 | Tidak | 0.73 | 0.73 | 0.00 |
| 600 | 2026-02-18 | DT-0100 | BM 9509 QO | selesai pekerjaan <br>~gerising<br>~membersikan saringan udara <br>~gunci baut sasis kendor <br>~ganti bola lampu rem 1 pcs | 13.39 | 14.24 | 0.75 | 0.75 | 0.00 | Urwatul Usk | 1 | Tidak | 0.75 | 0.75 | 0.00 |
| 601 | 2026-02-18 | DT-060 | B 9121 ZYT | selesai ganti bola lampu kabut 1 pcs | 14.25 | 14.44 | 0.32 | 0.32 | 0.00 | Urwatul Usk | 1 | Tidak | 0.32 | 0.32 | 0.00 |
| 602 | 2026-02-18 | DT-073 | B 9134 ZYT | Jenis  Pekerjaan : <br>❎1~ Tank Hydrolic Pecah pada atas , bagian belakang Tank | 14.34 | 15.38 | 1.07 | 1.07 | 0.00 | Rahmad K | 2 | Tidak | 0.53 | 0.53 | 0.00 |
| 602 | 2026-02-18 | DT-073 | B 9134 ZYT | Jenis  Pekerjaan : <br>❎1~ Tank Hydrolic Pecah pada atas , bagian belakang Tank | 14.34 | 15.38 | 1.07 | 1.07 | 0.00 | Suwardi | 2 | Tidak | 0.53 | 0.53 | 0.00 |
| 603 | 2026-02-18 | TMC-01 | B 9008 ZIN | Proses welding sub casis TMC 4 Titik retak | 15.14 | 16.24 | 1.17 | 0.77 | 0.40 | Darmawan | 1 | Ya | 1.17 | 0.77 | 0.40 |
| 604 | 2026-02-18 | DT-070 | B 9131 ZYT | gerising <br>~membersihkan saringan udara | 14.45 | 15.15 | 0.50 | 0.50 | 0.00 | Urwatul Usk | 1 | Tidak | 0.50 | 0.50 | 0.00 |
| 605 | 2026-02-18 | DT-075 | B 9136 ZYT | gerising <br>~membersihkan saringan udara | 15.20 | 15.55 | 0.58 | 0.58 | 0.00 | Urwatul Usk | 1 | Tidak | 0.58 | 0.58 | 0.00 |
| 606 | 2026-02-18 | DT-029 | B 9091 ZYT | gerising <br>~membersihkan saringan udara | 15.56 | 16.18 | 0.37 | 0.07 | 0.30 | Urwatul Usk | 1 | Tidak | 0.37 | 0.07 | 0.30 |

## Data 2026-02-19

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 608 | 2026-02-19 | DT 097 | BM9510QO | pekerjaan <br>~servis berkala <br>  *Ganti oli 15 L<br>  *Ganti filter oli 1 pcs<br>  *Ganti filter solar 3 pcs<br>Loc beropit BAB | 9.18 | 10.16 | 0.97 | 0.97 | 0.00 | Urwatul Usk | 1 | Tidak | 0.97 | 0.97 | 0.00 |
| 609 | 2026-02-19 | DT 097 | BM9510QO | Ganti bola lampu H4  2pcs | 10.16 | 11.15 | 0.98 | 0.98 | 0.00 | Urwatul Usk | 1 | Tidak | 0.98 | 0.98 | 0.00 |
| 610 | 2026-02-19 | DT-059 | B 9120 ZYT | Stel tojok KLOS | 10.12 | 10.55 | 0.72 | 0.72 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.72 | 0.72 | 0.00 |
| 613 | 2026-02-19 | DT-0100 | BM 9509 QO | safety belt problem | 14.24 | 14.34 | 0.17 | 0.17 | 0.00 | Urwatul Usk | 1 | Tidak | 0.17 | 0.17 | 0.00 |

## Data 2026-02-20

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 616 | 2026-02-20 |  | BM 9949 JO | Ganti bola lampu | 08.01 | 9.40 | 1.65 | 1.65 | 0.00 | Rezeki Siregar | 1 | Tidak | 1.65 | 1.65 | 0.00 |
| 617 | 2026-02-20 | DT-068 | B 9129 ZYT | Jenis  Pekerjaan : <br>❎1~ Tank Hydrolic pecah.<br>( Sisi lain Tank sdh pernah mengalami hal yang sama/pecah)<br>❎2~ Underround Protection Patah di sisi kanan dan kiri.<br>❎ Hour Meter ( HM ) mati . | 8.09 | 11.21 | 3.20 | 3.20 | 0.00 | Rahmad K | 2 | Tidak | 1.60 | 1.60 | 0.00 |
| 617 | 2026-02-20 | DT-068 | B 9129 ZYT | Jenis  Pekerjaan : <br>❎1~ Tank Hydrolic pecah.<br>( Sisi lain Tank sdh pernah mengalami hal yang sama/pecah)<br>❎2~ Underround Protection Patah di sisi kanan dan kiri.<br>❎ Hour Meter ( HM ) mati . | 8.09 | 11.21 | 3.20 | 3.20 | 0.00 | Suwardi | 2 | Tidak | 1.60 | 1.60 | 0.00 |
| 618 | 2026-02-20 |  | B 9121 EO | Proses lanjutan ..<br>~ merubah dimensi underaun d RH/LH<br>~ APAR ( BELUM SELESAI )<br>~pabrikasi bracket lampu rotari &amp; instal | 8.21 | 16.49 | 8.47 | 7.65 | 0.82 | Darmawan | 2 | Tidak | 4.23 | 3.82 | 0.41 |
| 618 | 2026-02-20 |  | B 9121 EO | Proses lanjutan ..<br>~ merubah dimensi underaun d RH/LH<br>~ APAR ( BELUM SELESAI )<br>~pabrikasi bracket lampu rotari &amp; instal | 8.21 | 16.49 | 8.47 | 7.65 | 0.82 | Rahmad K | 2 | Tidak | 4.23 | 3.82 | 0.41 |
| 620 | 2026-02-20 | DT-033 | BG 8230 MX | Ganti bola lampu besar H 4 | 9.10 | 9.43 | 0.55 | 0.55 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.55 | 0.55 | 0.00 |
| 621 | 2026-02-20 | DT-06 | B 9102 ZYT | Jenis  Pekerjaan : <br>❎1~ Sub Chasis Dump belakang sisi sebelah kanan patah.<br>❎2~ Tangga Sisi sebelah kiri cabin patah ( kropos ) | 10.36 | 15.49 | 5.22 | 5.22 | 0.00 | Rahmad K | 1 | Tidak | 5.22 | 5.22 | 0.00 |
| 622 | 2026-02-20 | DT-02 | DT 8973 IE | penyetelan bealting AC dan belting kipas radiator | 13.30 | 13.30 |  | 0.00 | 0.00 | Afriyandi | 1 | Tidak |  | 0.00 | 0.00 |
| 624 | 2026-02-20 | DT-072 | B 9133 ZYT | Jenis  Pekerjaan : penggantian ban serap ke ban yang pecah | 14.20 | 15.15 | 0.92 | 0.92 | 0.00 | Afriyandi | 2 | Tidak | 0.46 | 0.46 | 0.00 |
| 624 | 2026-02-20 | DT-072 | B 9133 ZYT | Jenis  Pekerjaan : penggantian ban serap ke ban yang pecah | 14.20 | 15.15 | 0.92 | 0.92 | 0.00 | Gabriel | 2 | Tidak | 0.46 | 0.46 | 0.00 |
| 625 | 2026-02-20 | DT-09 | B 9105 ZYT | Jenis  Pekerjaan : ganti lampu kota sebelah kanan 1pcs dan ganti sekring | 15.30 | 15.48 | 0.30 | 0.30 | 0.00 | Afriyandi | 2 | Tidak | 0.15 | 0.15 | 0.00 |
| 625 | 2026-02-20 | DT-09 | B 9105 ZYT | Jenis  Pekerjaan : ganti lampu kota sebelah kanan 1pcs dan ganti sekring | 15.30 | 15.48 | 0.30 | 0.30 | 0.00 | Gabriel | 2 | Tidak | 0.15 | 0.15 | 0.00 |

## Data 2026-02-21

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 627 | 2026-02-21 | DT-045 | BG 8641 NI | Ganti bola lampu besar H 4 | 7.45 | 8.18 | 0.55 | 0.55 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.55 | 0.55 | 0.00 |
| 629 | 2026-02-21 | DT-010 | B 9701 PYW | Pasang Seal Hydraulic Dump | 8.40 | 10.45 | 2.08 | 2.08 | 0.00 | Afriyandi | 2 | Tidak | 1.04 | 1.04 | 0.00 |
| 629 | 2026-02-21 | DT-010 | B 9701 PYW | Pasang Seal Hydraulic Dump | 8.40 | 10.45 | 2.08 | 2.08 | 0.00 | Gabriel | 2 | Tidak | 1.04 | 1.04 | 0.00 |
| 630 | 2026-02-21 | DT-014 | B 9386 KYW | Mengecangkan baut as tarik longgar semua dan ganti fuly ac | 8.19 | 8.40 | 0.35 | 0.35 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.35 | 0.35 | 0.00 |
| 632 | 2026-02-21 | DT-033 | DT-8669 KE | Ganti kepala batere ,menjampreng batere mobik udh selesai di pos merah | 8.45 | 9.31 | 0.77 | 0.77 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.77 | 0.77 | 0.00 |
| 634 | 2026-02-21 | DT-098 | BM 9174 NU | selesai pekerjaan <br>~gerising<br>~membersih kan saringan udara <br>~ganti bola lampu H4 1pcs<br>~ganti kepala baterai 1pcs | 8.05 | 9.49 | 1.73 | 1.73 | 0.00 | Urwatul Usk | 1 | Tidak | 1.73 | 1.73 | 0.00 |
| 636 | 2026-02-21 | PM-02 | B 9012 ZEH . | REGRESING | 9.52 | 10.25 | 0.55 | 0.55 | 0.00 | Urwatul Usk | 1 | Tidak | 0.55 | 0.55 | 0.00 |
| 637 | 2026-02-21 | DT-092 | BM 9819 QO |  | 9.33 | 10.29 | 0.93 | 0.93 | 0.00 | Rezeki Siregar | 1 | Ya | 0.93 | 0.93 | 0.00 |
| 638 | 2026-02-21 |  | BM 9824 QO | Menjampreng batere mobik | 10.30 | 10.50 | 0.33 | 0.33 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.33 | 0.33 | 0.00 |
| 640 | 2026-02-21 |  | BM 9949 JO | Ganti bola lampu besar sebelah kanan 1 pcs | 10.53 | 11.27 | 0.57 | 0.57 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.57 | 0.57 | 0.00 |
| 641 | 2026-02-21 | WL-02 | SEM-665 | SAMBUNGAN FORK PALLET PROBLEM | 11.29 | 14.45 | 3.27 | 3.27 | 0.00 | Rahmad K | 2 | Tidak | 1.63 | 1.63 | 0.00 |
| 641 | 2026-02-21 | WL-02 | SEM-665 | SAMBUNGAN FORK PALLET PROBLEM | 11.29 | 14.45 | 3.27 | 3.27 | 0.00 | Suwardi | 2 | Tidak | 1.63 | 1.63 | 0.00 |
| 642 | 2026-02-21 |  | BK 9121 EQ | merubah dimensi underaund LH/RH<br>~ instal alarm mundur<br>~instal lampu rotari &amp; bracket <br>~Ganti Safety  belt driver<br>~install karet susu &amp; U bollt spring<br>~ install emergency stop<br>~ pabrikasi bracket apar &amp; instal<br>~ install lampu belakang (ganti baru L/H) | 08.10 | 15.21 | 7.18 | 7.18 | 0.00 | Darmawan | 2 | Tidak | 3.59 | 3.59 | 0.00 |
| 642 | 2026-02-21 |  | BK 9121 EQ | merubah dimensi underaund LH/RH<br>~ instal alarm mundur<br>~instal lampu rotari &amp; bracket <br>~Ganti Safety  belt driver<br>~install karet susu &amp; U bollt spring<br>~ install emergency stop<br>~ pabrikasi bracket apar &amp; instal<br>~ install lampu belakang (ganti baru L/H) | 08.10 | 15.21 | 7.18 | 7.18 | 0.00 | Rahmad K | 2 | Tidak | 3.59 | 3.59 | 0.00 |
| 643 | 2026-02-21 | DT-043 | BG 8639 MI | Buang angin minyak tadi mobik habis | 13.00 | 15.13 | 2.22 | 2.22 | 0.00 | Rezeki Siregar | 1 | Tidak | 2.22 | 2.22 | 0.00 |
| 644 | 2026-02-21 | DT-033 | BG 8230 MX | Problem unit : ganti kabel handel porsneling. 1 pcs<br>Dan perbaikan elektrik eror. | 10.30 | 15.35 | 5.08 | 5.08 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 5.08 | 5.08 | 0.00 |

## Data 2026-02-22

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 645 | 2026-02-22 |  |  | Pabrikasi Whellcock RWI | 08.00 | 18.00 | 10.00 | 8.00 | 2.00 | Rahmad K | 2 | Tidak | 5.00 | 4.00 | 1.00 |
| 645 | 2026-02-22 |  |  | Pabrikasi Whellcock RWI | 08.00 | 18.00 | 10.00 | 8.00 | 2.00 | Suwardi | 2 | Tidak | 5.00 | 4.00 | 1.00 |

## Data 2026-02-23

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 646 | 2026-02-23 |  |  | Pabrikasi Whellcock RWI | 08.00 | 18.00 | 10.00 | 8.00 | 2.00 | Rahmad K | 2 | Tidak | 5.00 | 4.00 | 1.00 |
| 646 | 2026-02-23 |  |  | Pabrikasi Whellcock RWI | 08.00 | 18.00 | 10.00 | 8.00 | 2.00 | Suwardi | 2 | Tidak | 5.00 | 4.00 | 1.00 |
| 647 | 2026-02-23 |  | BM 9824 Q0 | ACCU 70 AMPERE SOAK <br>STEEL PULLEY AC | 8.30 | 15.05 | 6.58 | 6.58 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 6.58 | 6.58 | 0.00 |
| 649 | 2026-02-23 | DT-013 | B 9644 KYW | Jenis  Pekerjaan : DISMANTLE <br>1.per no 5,6,7,8 patah<br>2.lampu besar <br>3.lampu kabut<br>4.stel V-Belt ac | 8.51 | 15.35 | 6.73 | 6.73 | 0.00 | Afriyandi | 2 | Tidak | 3.37 | 3.37 | 0.00 |
| 649 | 2026-02-23 | DT-013 | B 9644 KYW | Jenis  Pekerjaan : DISMANTLE <br>1.per no 5,6,7,8 patah<br>2.lampu besar <br>3.lampu kabut<br>4.stel V-Belt ac | 8.51 | 15.35 | 6.73 | 6.73 | 0.00 | Gabriel | 2 | Tidak | 3.37 | 3.37 | 0.00 |
| 650 | 2026-02-23 | DT-098 | BM 9174 NU | pekerjaan <br>~servis berkala <br>  *Ganti filter solar 2 pcs <br>  *Ganti filter oli 1 pcs <br>  *Ganti oli 15 liter | 8.10 | 9.41 | 1.52 | 1.52 | 0.00 | Urwatul Usk | 1 | Tidak | 1.52 | 1.52 | 0.00 |
| 653 | 2026-02-23 |  | BK 9121 EQ | Izin menyampaikan info , mengganti kotrek ban serap water tank yang rusak dan ban serap, di ambil dari DT- HINO 260 TI, B 9613 TYT | 8.30 | 11.58 | 3.47 | 3.47 | 0.00 | Rahmad K | 1 | Tidak | 3.47 | 3.47 | 0.00 |
| 659 | 2026-02-23 | DT-049 | B 9107 ZYT | Jenis  Pekerjaan : <br>❎1~ fabrikasi pen engsel ombeng sisi kanan | 13.30 | 14.30 | 1.00 | 1.00 | 0.00 | Hendrik | 1 | Tidak | 1.00 | 1.00 | 0.00 |
| 660 | 2026-02-23 | DT-104 | BM 9398 JO | pekerjaan <br>~gerising<br>~membersikan saringan udara <br>~perbaikan sambung selang Riley Valve angin rem<br>~penguncian baut pen per<br>~SERVIS BERKALA<br>  *ganti filter solar 3 pcs <br>  *Ganti filter oli 1 pcs<br>  *Ganti oli mesin 15 liter | 13.55 | 14.32 | 0.62 | 0.62 | 0.00 | Urwatul Usk | 1 | Tidak | 0.62 | 0.62 | 0.00 |
| 662 | 2026-02-23 | DT-031 | B 9093 ZYT | Stel PULLEY AC | 15.13 | 15.46 | 0.55 | 0.55 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.55 | 0.55 | 0.00 |

## Data 2026-02-24

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 663 | 2026-02-24 | DT-027 | B 9082 ZYT | Semprot Saringan Udara | 8.01 | 8.29 | 0.47 | 0.47 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 664 | 2026-02-24 | DT-013 | B 9644 KYW | TERMINAL ACCU KROPOS | 8.14 | 9.48 | 1.57 | 1.57 | 0.00 | Rahmad K | 2 | Tidak | 0.78 | 0.78 | 0.00 |
| 664 | 2026-02-24 | DT-013 | B 9644 KYW | TERMINAL ACCU KROPOS | 8.14 | 9.48 | 1.57 | 1.57 | 0.00 | Suwardi | 2 | Tidak | 0.78 | 0.78 | 0.00 |
| 667 | 2026-02-24 | DT-033 | DT-8669 KE | GANTI LAMPU H 4 MATI KEMBALI <br>SLANG HYDRAULIC BOCOR | 8.30 | 9.09<br>16.33 | 0.65 | 0.65 | 0.00 | Rezeki Siregar | 1 | Tidak | 0.65 | 0.65 | 0.00 |
| 668 | 2026-02-24 | DT-014 | B 9683 KYW | Slmt pagi semua apa boleh aku pesan baut pen per depan sebanyak 2 pcs | 9.55 | 10.55 | 1.00 | 1.00 | 0.00 | Rezeki Siregar | 1 | Tidak | 1.00 | 1.00 | 0.00 |
| 671 | 2026-02-24 | DT-035 | B 9914 JYT | Jenis  Pekerjaan : <br>❎1~ perbaikan steep kaki kanan/kiri ( broken ) | 10.00 | 10.50 | 0.83 | 0.83 | 0.00 | Hendrik | 1 | Tidak | 0.83 | 0.83 | 0.00 |
| 674 | 2026-02-24 | DT-057 | B 9116 ZYT | ganti bola lampu kabut 1 | 11.00 | 11.32 | 0.53 | 0.53 | 0.00 | Urwatul Usk | 1 | Tidak | 0.53 | 0.53 | 0.00 |
| 675 | 2026-02-24 | DT-023 | B 9078 ZYT | SERVIS BERKALA <br>  *ganti filter solar 3 pcs <br>  *Ganti filter oli 1 pcs <br>  *Ganti oli 15 liter <br>SEPERTI NYA BELUM MEMAHAMI DI INTERVAL 60.000 KM INI APA SAJA YANG DI GANTI,SEPERTINYA KURANG RESPON DAN TIDAK MAU BERTANYA | 9.55 | 10.25 | 0.50 | 0.50 | 0.00 | Urwatul Usk | 1 | Tidak | 0.50 | 0.50 | 0.00 |
| 676 | 2026-02-24 | DT-027 | B 9082 ZYT | SERVIS BERKALA <br>  *ganti filter solar 3 pcs <br>  *Ganti filter oli 1 pcs <br>  *Ganti oli 15 liter <br>TELAD 200 KM TERJADWAL 67.55 <br>SEPERTI NYA BELUM MEMAHAMI DI INTERVAL 60.000 KM INI APA SAJA YANG DI GANTI,SEPERTINYA KURANG RESPON DAN TIDAK MAU BERTANYA | 10.30 | 11.32 | 1.03 | 1.03 | 0.00 | Urwatul Usk | 1 | Tidak | 1.03 | 1.03 | 0.00 |
| 677 | 2026-02-24 | DT-033 | BG 8230 MX | dam tidak mau naik.. | 11.49 | 14.39 | 2.83 | 2.83 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 2.83 | 2.83 | 0.00 |
| 679 | 2026-02-24 | DT-012 | B 9642 KYW | Jenis  Pekerjaan : <br>❎1~ pabrikasi kedudukan accu | 13.20 | 14.12 | 0.87 | 0.87 | 0.00 | Hendrik | 1 | Tidak | 0.87 | 0.87 | 0.00 |
| 682 | 2026-02-24 | DT-069 | B 9130 ZYT | selesai pekerjaan <br>~gerising<br>~membersihkan saringan udara | 13.55 | 14.27 | 0.53 | 0.53 | 0.00 | Urwatul Usk | 1 | Tidak | 0.53 | 0.53 | 0.00 |
| 683 | 2026-02-24 | DT-049 | B 9107 ZYT | selesai pekerjaan <br>~gerising<br>~membersihkan saringan udara | 13.55 | 14.27 | 0.53 | 0.53 | 0.00 | Urwatul Usk | 1 | Tidak | 0.53 | 0.53 | 0.00 |

## Data 2026-02-25

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 685 | 2026-02-25 | DT-033 | DT-8669 KE | Slmt pagi ini contok barang selang hidroliy yg bocor punyai dt 00072 tolong di bantu ya<br>TAMBAH OLI 8 LITER | 7.53 | 11.19 | 3.43 | 3.43 | 0.00 | Rezeki Siregar | 1 | Tidak | 3.43 | 3.43 | 0.00 |
| 686 | 2026-02-25 | DT-046 | BG 8638 NI | Proses  buka baut roda sebelah kiri belakang Putus | 8.10 | 10.41 | 2.52 | 2.52 | 0.00 | Rezeki Siregar | 1 | Tidak | 2.52 | 2.52 | 0.00 |
| 687 | 2026-02-25 |  | BM- 8371 CU. | Dalam proses perbaikan ( cor )<br>Tindakan Mitigasi:<br>Cor pada kepala Accu yang aus. | 8.56 | 9.33 | 0.62 | 0.62 | 0.00 | Rahmad K | 2 | Tidak | 0.31 | 0.31 | 0.00 |
| 687 | 2026-02-25 |  | BM- 8371 CU. | Dalam proses perbaikan ( cor )<br>Tindakan Mitigasi:<br>Cor pada kepala Accu yang aus. | 8.56 | 9.33 | 0.62 | 0.62 | 0.00 | Suwardi | 2 | Tidak | 0.31 | 0.31 | 0.00 |
| 691 | 2026-02-25 | DT-059 | B 9120 ZYT | SELESAI PEKERJAAM <br>~gerising <br>~membersihkan saringan udara | 10.10 | 11.19 | 1.15 | 1.15 | 0.00 | Urwatul Usk | 1 | Tidak | 1.15 | 1.15 | 0.00 |
| 692 | 2026-02-25 | DT-047 | BG 8976 IX | Jenis  Pekerjaan : <br>1~ repair safety pintu ombeng<br>2~perbaikan kunci ombeng | 11.20 | 14.20 | 3.00 | 3.00 | 0.00 | Hendrik | 1 | Tidak | 3.00 | 3.00 | 0.00 |
| 693 | 2026-02-25 | DT-033 | DT-8669 KE | Dt 00072 selesai pekerjaan <br>~gerising | 12.10 | 12.40 | 0.50 | 0.50 | 0.00 | Urwatul Usk | 1 | Tidak | 0.50 | 0.50 | 0.00 |
| 694 | 2026-02-25 | DT-024 | B 9079 ZYT | Dt 00072 selesai pekerjaan <br>~gerising | 12.45 | 13.13 | 0.47 | 0.47 | 0.00 | Urwatul Usk | 1 | Tidak | 0.47 | 0.47 | 0.00 |
| 695 | 2026-02-25 | DT-070 | B-9131 ZYT. | Jenis  Pekerjaan : <br>❎1~ Underround Protection Patah . | 13.19 | 14.48 | 1.48 | 1.48 | 0.00 | Rahmad K | 2 | Tidak | 0.74 | 0.74 | 0.00 |
| 695 | 2026-02-25 | DT-070 | B-9131 ZYT. | Jenis  Pekerjaan : <br>❎1~ Underround Protection Patah . | 13.19 | 14.48 | 1.48 | 1.48 | 0.00 | Suwardi | 2 | Tidak | 0.74 | 0.74 | 0.00 |
| 697 | 2026-02-25 | DT-033 | DT-8669 KE | Jenis  Pekerjaan : <br>1~ welding safety underround protection sisi kiri <br>2~las timbun safety busing buddy seat | 14.30 | 15.00 | 0.50 | 0.50 | 0.00 | Hendrik | 1 | Tidak | 0.50 | 0.50 | 0.00 |
| 700 | 2026-02-25 | DT-022 | Z 9109 AB | Jenis  Pekerjaan : <br>1~ welding tapak kampas rem yang retak | 15.30 | 16.00 | 0.50 | 0.50 | 0.00 | Hendrik | 1 | Ya | 0.50 | 0.50 | 0.00 |
| 703 | 2026-02-25 | DT-043 | BG 8367 IJ | selesai pekerjaan <br>~servis berkala<br>  *Ganti filter solar 1 pcs<br>  *Ganti filter oli 1 pcs<br>  *Ganti oli 15 liter <br>BISA SAMPAI TERLAMBAT 1111 BAGAIMANA KISAH NYA | 14.55 | 15.45 | 0.83 | 0.83 | 0.00 | Urwatul Usk | 1 | Tidak | 0.83 | 0.83 | 0.00 |
| 704 | 2026-02-25 | DT-065 | B 9126 ZYT | selesai pekerjaan <br>~ganti puliy AC <br>~ganti belting AC <br>KEDUA TEAM INI JARANG SEKALI MEMBERIKAN INFORMASI DIMANA POSISI UNIT YANG DITANGANI | 15.48 | 16.27 | 0.65 | 0.20 | 0.45 | Urwatul Usk | 1 | Tidak | 0.65 | 0.20 | 0.45 |
| 706 | 2026-02-25 | DT-075 | B 9136 ZYT | pemasangan lasing baru<br>( RATCET TIE DOWN ) | 16.10 | 16.36 | 0.43 | 0.00 | 0.43 | Urwatul Usk | 1 | Tidak | 0.43 | 0.00 | 0.43 |
| 707 | 2026-02-25 | LB-41001 |  | Lembur untuk projek RWI. <br>Mengganti konektor selang angin LB 41001 <br>Mulai jam 16.20 WIB<br>Selesai jam 16.45 WIB | 16.20 | 16.45 | 0.42 | 0.00 | 0.42 | Rahmad K | 1 | Tidak | 0.42 | 0.00 | 0.42 |

## Data 2026-02-26

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 708 | 2026-02-26 |  | BM 9810 QO | Jenis  Pekerjaan : <br>1. Disc clutch <br>2. Simpang empat PTO | 9.05 | 16.10 | 7.08 | 6.92 | 0.17 | Afriyandi | 1 | Tidak | 7.08 | 6.92 | 0.17 |
| 710 | 2026-02-26 | DT-026 | B 9091 ZYT | Jenis  Pekerjaan : <br>1.Bongkar Ban belakang sisi kiri (bocor).2 roda | 9.50 | 13.48 | 3.97 | 3.97 | 0.00 | Hendrik | 2 | Tidak | 1.98 | 1.98 | 0.00 |
| 710 | 2026-02-26 | DT-026 | B 9091 ZYT | Jenis  Pekerjaan : <br>1.Bongkar Ban belakang sisi kiri (bocor).2 roda | 9.50 | 13.48 | 3.97 | 3.97 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 1.98 | 1.98 | 0.00 |
| 712 | 2026-02-26 | DT-107 | BM 9244 NU | P @⁨Fajar⁩ minta tolong orderkan baut Ting tong pendek  2 bh | 10.27 | 15.00 | 4.55 | 4.55 | 0.00 | Hendrik | 2 | Ya | 2.28 | 2.28 | 0.00 |
| 712 | 2026-02-26 | DT-107 | BM 9244 NU | P @⁨Fajar⁩ minta tolong orderkan baut Ting tong pendek  2 bh | 10.27 | 15.00 | 4.55 | 4.55 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Ya | 2.28 | 2.28 | 0.00 |
| 716 | 2026-02-26 | DT-047 | BG 8976 IX | Penggantian Ban DEPAN No 1 | 14.01 | 16.36 | 2.58 | 1.98 | 0.60 | Hendrik | 2 | Tidak | 1.29 | 0.99 | 0.30 |
| 716 | 2026-02-26 | DT-047 | BG 8976 IX | Penggantian Ban DEPAN No 1 | 14.01 | 16.36 | 2.58 | 1.98 | 0.60 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 1.29 | 0.99 | 0.30 |
| 720 | 2026-02-26 | DT-088 | BM 9944 JO | Jenis  Pekerjaan : service berkala<br>-oli mesin 15ltr<br>-filter oli 1pcs<br>-filter solar 2pcs | 14.56 | 15.50 | 0.90 | 0.90 | 0.00 | Urwatul Usk | 1 | Tidak | 0.90 | 0.90 | 0.00 |

## Data 2026-02-27

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 724 | 2026-02-27 | TMC-001 | B 9008 ZIN | Jenis  Pekerjaan : <br>❎1~ Sub Chasis Krek bagian depan, sisi kanan dan kiri. | 8.32 | 13.52 | 5.33 | 5.33 | 0.00 | Rahmad K | 2 | Tidak | 2.67 | 2.67 | 0.00 |
| 724 | 2026-02-27 | TMC-001 | B 9008 ZIN | Jenis  Pekerjaan : <br>❎1~ Sub Chasis Krek bagian depan, sisi kanan dan kiri. | 8.32 | 13.52 | 5.33 | 5.33 | 0.00 | Suwardi | 2 | Tidak | 2.67 | 2.67 | 0.00 |
| 725 | 2026-02-27 | LB-01 | B 9012 ZEH . | Penggantian socket konektor | 8.30 | 9.11 | 0.68 | 0.68 | 0.00 | Darmawan | 1 | Tidak | 0.68 | 0.68 | 0.00 |
| 726 | 2026-02-27 | WT-02 | BK 9121 EQ | Start engine WT 41001 di jumper dengan DT | 8.45 | 9.20 | 0.58 | 0.58 | 0.00 | Rahmad K | 1 | Tidak | 0.58 | 0.58 | 0.00 |
| 727 | 2026-02-27 | DT-058 | B 9117 ZYT | Jenis  Pekerjaan : <br>1.perbaikan safety bumper belakang ( broken ) | 9.00 | 11.08 | 2.13 | 2.13 | 0.00 | Hendrik | 1 | Tidak | 2.13 | 2.13 | 0.00 |
| 731 | 2026-02-27 |  | BM 9287 JO | selesai pekerjaan <br>~servis berkala<br>  *Ganti filter oli 1 pcs <br>  *Ganti filter solar 3 pcs <br>   *Ganti oli 15 L | 11.30 | 12.08 | 0.63 | 0.63 | 0.00 | Urwatul Usk | 1 | Tidak | 0.63 | 0.63 | 0.00 |
| 732 | 2026-02-27 | DT-098 | BM 9174 NU | Jenis  Pekerjaan : <br>1. Low power<br>2. Baterai tekor | 10.48 | 11.12 | 0.40 | 0.40 | 0.00 | Urwatul Usk | 1 | Tidak | 0.40 | 0.40 | 0.00 |
| 733 | 2026-02-27 | DT-03 | BM 9285 JO | Jenis  Pekerjaan : <br>1. Puly AC | 12.48 | 13.07 | 0.32 | 0.32 | 0.00 | Urwatul Usk | 1 | Tidak | 0.32 | 0.32 | 0.00 |
| 734 | 2026-02-27 | DT-088 | BM 9944 JO | Jenis  Pekerjaan : <br>1. Low power | 13.18 | 13.27 | 0.15 | 0.15 | 0.00 | Urwatul Usk | 1 | Tidak | 0.15 | 0.15 | 0.00 |
| 735 | 2026-02-27 | DT-02 | DT 8973 IE | Jenis  Pekerjaan : <br>1.wellding Muffler yang korosi<br>2.las timbun baut yang patah ( baut knalpot turbu ) | 13.30 | 16.30 | 3.00 | 2.50 | 0.50 | Hendrik | 1 | Tidak | 3.00 | 2.50 | 0.50 |
| 737 | 2026-02-27 | DT-06 | B 9102 ZYT | Jenis  Pekerjaan : <br>1. Setel tojok kelos<br>2. Buang angin master kelos | 12.40 | 14.15 | 1.58 | 1.58 | 0.00 | Urwatul Usk | 1 | Tidak | 1.58 | 1.58 | 0.00 |
| 738 | 2026-02-27 |  |  | Ijin melapor Bapak/Ibu untuk unit project RWI :<br>~DT - 41001<br>~DT - 41003<br>~DT - 41004<br>~DT - 41005 | 08.05 | 15.04 | 6.98 | 6.98 | 0.00 | Darmawan | 1 | Tidak | 6.98 | 6.98 | 0.00 |
| 740 | 2026-02-27 |  | BM 9503 QO | Ijin melapor Bapak/Ibu ,untuk Dump truk RWI tersisa 1 unit lg ,terinfo yg belum ada Drivernya ,yaitu :<br>Unit : DT - 41002<br>Status : stanby <br>Sembari menunggu driver kita lakukan servis berkala | 15.05 | 15.32 | 0.45 | 0.45 | 0.00 | Darmawan | 1 | Ya | 0.45 | 0.45 | 0.00 |
| 741 | 2026-02-27 |  | BM 9503 QO | Jenis  Pekerjaan : service berkala<br>-oli engine 15ltr | 15.30 | 16.04 | 0.57 | 0.50 | 0.07 | Darmawan | 1 | Tidak | 0.57 | 0.50 | 0.07 |

## Data 2026-02-28

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 743 | 2026-02-28 |  | BM 7680 TU | zin bapak ibuk saya agak telat keyar karna lagi perbaikan bus 0006 yg gak bisa hidup lokasi jalan angurmerah rumah operator.<br>GANTI CLAM ACCU | 7.33 | 9.37 | 2.07 | 2.07 | 0.00 | Afriyandi | 1 | Tidak | 2.07 | 2.07 | 0.00 |
| 744 | 2026-02-28 | ROAD WELLPAD PROJECT |  | Pengguna Whellchock untuk Unit Water Tank kebutuhan project RWI di realisasikan… | 9.42 | 9.49 | 0.12 | 0.12 | 0.00 | Rahmad K | 1 | Tidak | 0.12 | 0.12 | 0.00 |
| 746 | 2026-02-28 | DT-033 | BG 8230 MX | Ganti kepala batrai 1 bh <br>Stel bel Ting AC. | 9.55 | 10.26 | 0.52 | 0.52 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.52 | 0.52 | 0.00 |
| 747 | 2026-02-28 | RWI | BM 9177 LD | Jenis  Pekerjaan : ganti kepala batre+ patah dan jamper batre | 10.20 | 10.45 | 0.42 | 0.42 | 0.00 | Darmawan | 1 | Tidak | 0.42 | 0.42 | 0.00 |
| 748 | 2026-02-28 | DT-032 | BG 8976 IX | Jenis  Pekerjaan : <br>1.wellding/Repair safety underround protection sisi kanan <br>2.kunci ombeng sisi kanan bengkok.<br>3.Ganti heandle pintu kabin sisi kanan<br>(Temuan TPE) | 10.00 | 14.50 | 4.83 | 4.83 | 0.00 | Hendrik | 1 | Tidak | 4.83 | 4.83 | 0.00 |
| 749 | 2026-02-28 | DT-107 | BM 9244 NU | Jenis  Pekerjaan : <br>1. Gantungan ban serap lepas<br>Jenis  Pekerjaan : <br>1. Gantungan ban serap lepas<br>2. Antar ban serap | 11.43 | 12.41 | 0.97 | 0.97 | 0.00 | Urwatul Usk | 1 | Tidak | 0.97 | 0.97 | 0.00 |
| 750 | 2026-02-28 | RWI | BM 9956 JO | Jenis  Pekerjaan : low power, dump turun tidak stabil,stel rem | 11.40 | 17.37 | 5.95 | 4.33 | 1.62 | Darmawan | 1 | Tidak | 5.95 | 4.33 | 1.62 |
| 751 | 2026-02-28 | DT-027 | B 9082 ZYT | Ganti volli AC | 11.40 | 12.06 | 0.43 | 0.43 | 0.00 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 0.43 | 0.43 | 0.00 |
| 752 | 2026-02-28 | DT-013 | B 9644 KYW | Jenis  Pekerjaan : <br>1. Lampu kabut <br>2. Sekring putus | 11.40 | 12.07 | 0.45 | 0.45 | 0.00 | Urwatul Usk | 1 | Tidak | 0.45 | 0.45 | 0.00 |
| 754 | 2026-02-28 | DT-010 | B 9701 PYW | slang angin bocor 2 bh. | 14.38 | 17.39 | 3.02 | 1.37 | 1.65 | Joni (Jhoni Ist Kandar) | 1 | Tidak | 3.02 | 1.37 | 1.65 |
| 756 | 2026-02-28 | wt-02 | bk 9121 eo | Jenis  Pekerjaan : <br>1. Stel langsam <br>2. Cek lampu mundur dan alram mundur | 15.00 | 16.40 | 1.67 | 1.00 | 0.67 | Rahmad K | 1 | Tidak | 1.67 | 1.00 | 0.67 |

## Data 2//2/2026

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | End_Time | Durasi Jam (Start-End) | Jam Normal Total | Jam Lembur Total | Nama Mekanik | n_mech | Indikasi Delay Sparepart | Jam Dialokasikan | Normal Dialokasikan | Lembur Dialokasikan |
| ---: | --- | --- | --- | --- | --- | --- | ---: | ---: | ---: | --- | ---: | --- | ---: | ---: | ---: |
| 372 | 2//2/2026 | DT-048 | BG 8535 OW | penggangian per depan sebelah kiri, nomor 1dan nomor 4 | 08.10 | 10.20 | 2.17 | 2.17 | 0.00 | Gabriel | 2 | Tidak | 1.08 | 1.08 | 0.00 |
| 372 | 2//2/2026 | DT-048 | BG 8535 OW | penggangian per depan sebelah kiri, nomor 1dan nomor 4 | 08.10 | 10.20 | 2.17 | 2.17 | 0.00 | Joni (Jhoni Ist Kandar) | 2 | Tidak | 1.08 | 1.08 | 0.00 |

# 6. Missing Time

Sheet ini memuat **84 baris** yang tidak mempunyai pasangan waktu lengkap dan tidak dapat dimasukkan ke alokasi jam.

## Ringkasan Kelengkapan Waktu

- Start Time kosong: **10 baris**.
- End Time kosong: **81 baris**.
- Start dan End sama-sama kosong: **9 baris**.
- Start terisi tetapi End kosong: **72 baris**.
- Start kosong tetapi End terisi: **1 baris**.

## Distribusi Status Missing Time

| Status | Jumlah Baris |
| --- | ---: |
| (kosong) | 1 |
| BD | 57 |
| RFU | 26 |

> Status `BD`, `RFU`, dan status kosong dipertahankan sebagaimana sumber. Workbook tidak menyediakan kamus atau kepanjangan status, sehingga Markdown tidak menetapkan arti resminya.

## Missing Time 2026-02-01

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 366 | 2026-02-01 | DT-048 | BG 8535 OW | Ganti filter oli 2 pcs<br>Ganti filter solar 3 pcs<br>Untuk ganti oli kita lanjut besok..karna baut pembuangan oli rusak.perlu untuk pengelasan.<br>Dan temuan baru unit dt 00031<br>Per depan sebelah kiri patah.<br>No : 1 dan nomor 4 | 13.45 | JHONI IST KANDAR <br>REJEKINYA P SIREGAR UNTUK ANAK ANAK NYA |  | BAUT DOL | BD |

## Missing Time 2026-02-02

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 368 | 2026-02-02 |  | BG 8163 NK | TROUBLE SHOOTING :<br>1# ring piston no 3 ( rusak )<br>2# main Bering no 5 ( rusak )<br>3# crank shaf  ( rusak)<br>4# liner  no 3           ( rusak ) | 8.51 | DARMAWAN<br>YANDIKARYA <br>LANDA GOLOK 2000 |  | Kasus ini adalah kerusakan domino. Ring piston yang putus akibat panas/kurang oli merusak dinding silinder dan merusak sistem pelumasan, menyebabkan main bearing kekurangan oli dan pecah, yang akhirnya merusak crankshaft. Faktor penyebab utama seringkali adalah keterlambatan ganti oli, oli yang tidak sesuai spesifikasi, atau beban truk yang berlebihan (overload) secara terus-meneru | BD |
| 370 | 2026-02-02 |  | MG -01 | Cabut Bolt Control Valve patah di dalam Block. | 10.18 | APENG SUWARDI RAHMAD |  |  | RFU |
| 375 | 2026-02-02 | DT -061 | B 9122 ZYT | PECAH BAN 5 + Reserve <br>BAN MERK GITI UKURAN BAN :11.00-325.0<br>ASPEK RATIO :95.0-95.0<br>UKURAN RIM : 16-24 | 14.35 | IMAM |  | Tekanan Angin Rendah (Underinflation): Ini adalah penyebab utama ban truk pecah di bagian dinding. Saat ban kurang angin, dinding samping melentur secara berlebihan, menghasilkan panas yang sangat tinggi, merusak struktur kawat, dan mengakibatkan "bead failure" atau robek pada dinding bawah. | BD |

## Missing Time 2026-02-03

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 383 | 2026-02-03 | DT-073 | B 9134 ZYT | CLEANING ELEMENT FUL FILTER<br>Cuci filter solar bawA kotor |  | REJEKINYA P SIREGAR <br>UNTUK ANAK ANAK NYA |  | LOW POWER ENDAPAN KOTOR | RFU |
| 384 | 2026-02-03 | DT-028 | B 9089 ZYT | Ganti bohlam lampu kabut sebelah kiri. | 8.21 | JHONI IST KANDAR |  | Penggunaan Rem yang Intensif: Seringnya menginjak rem saat beroperasi (karena muatan berat atau jalan menurun) membuat lampu rem menyala dalam waktu lama dan berturut-turut. Ini meningkatkan suhu di dalam rumah lampu dan mempercepat umur pemakaian bohlam | RFU |
| 385 | 2026-02-03 | DT-035 | B 9914 JZT | Ganti bohlam lampu kabut sebelah kiri. | 08.53 | REJEKINYA P SIREGAR <br>UNTUK ANAK ANAK NYA |  | Penggunaan Rem yang Intensif: Seringnya menginjak rem saat beroperasi (karena muatan berat atau jalan menurun) membuat lampu rem menyala dalam waktu lama dan berturut-turut. Ini meningkatkan suhu di dalam rumah lampu dan mempercepat umur pemakaian bohlam | RFU |
| 391 | 2026-02-03 | WT-001 | B 9018 KFA | Proses pengerjaan pergantian karet rem belakang | 14.00 | TEPU FUSO <br>USKHA HAKAN |  | KAMPAS REM LIMIT PERLU PENGGANTIAN | BD |

## Missing Time 2026-02-04

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 397 | 2026-02-04 | DT-012 | B 9642 KYW | Menanbang oil hidoliy  3liter dan kasil oil bekas setiap sudut pen dt ,dt 00052 | 9.35 | REJEKI NYA P SIREGAR UNTUK ANAK ANAK NYA |  | Klem/Fitting Longgar: Getaran mesin truk yang tinggi dapat melonggarkan klem atau sambungan (fitting) selang, menyebabkan rembesan oli | RFU |
| 398 | 2026-02-04 | HT-03 | BM-9371 CU | Izin, berinfo pemasangan acu pada head traktor Hino yang akan operasi trial loading recycling | 10.28 | RAHMAD KAMANDANU |  | PASANG ACCU EXPF 03 TERKAIT ACCU DIPINDAHKAN KE UNIT MANA TIDAK TERINFO | RFU |
| 405 | 2026-02-04 | DT-033 | BG 8230 MX | 1~ welding tapak safety dump(broken)<br>2~pindah posisi kedudukn apar | 13.20 | HENDRIK KE SETROOM<br>BOY GABRIEL SAHULEKA |  | RUSAK AKIBAT PENGOPERASIAN | RFU |

## Missing Time 2026-02-05

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 425 | 2026-02-05 | DT-056 | B 9115 ZYT | DISC CLUTH :<br>X KE 2 DISC CLUTH INI KEMBALI TERJADI KEBOROSAN DICCLUTH PADA KM 4300 tanggal 7/3/2025<br>BERARTI KEMAMPUAN PENGEMUDI MENGOPERASIKAN DISC CLUTH INI HANYA :20.353 km | 17.01 | JHONI IST KANDAR<br>REJEKINYA P SIREGAR <br>BOY GABRIEL |  | Clutch disc (kampas kopling)  cepat habis umumnya disebabkan oleh<br>gesekan terus-menerus yang ekstrem, kebiasaan pengemudian agresif seperti sering menggantung kopling, beban muatan berlebih (overload), serta kebocoran oli transmisi yang mengenai kampas. Hal ini mengakibatkan kopling selip, akselerasi lemah, dan bau gosong. | BD |

## Missing Time 2026-02-06

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 428 | 2026-02-06 | DT-021 | BG 8163 NK | Proses dismantle cilynder head group untuk pengerjaan skir klep | 08.44 | DARMAWAN |  | SETTING KLEP (rekondisi engine ) | BD |
| 433 | 2026-02-06 | DT-030 | B 9092 ZYT | Perbaikan/pabrikasi speackboard sisi kiri ( broken) | 11.00 | YANDI KARYA<br>USKHA <br>LANDA |  | RUSAK AKIBAT PENGOPERASIAN | RFU |
| 435 | 2026-02-06 |  | BM 9285 JO | disc clutch limit | 14.19 | YNDI KARYA LANDA GOLOK 1000<br>USKHA TEPU FUSO |  | Teknik Mengemudi (Sering Setengah Kopling): Mengemudi dengan menahan kopling sebagian atau tidak segera melepas pedal kopling setelah kendaraan berjalan membuat kampas bekerja terus-menerus.<br>Penggunaan Gigi yang Salah: Memulai perjalanan dengan gigi tinggi (bukan gigi 1) saat membawa beban berat membuat beban torsi pada kampas kopling sangat besar.<br>Kualitas Komponen: Meskipun menggunakan suku cadang asli, beban kerja yang ekstrem (overload &amp; medan berat) akan tetap membuat umur kampas kopling jauh lebih pendek dibandingkan penggunaan normal | BD |

## Missing Time 2026-02-08

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 455 | 2026-02-08 |  | BM 9682 JO | ELECTRICAL SYSTEM ERROR <br>elektrik/ kabel2 ada yg shot. | 11.20 | RAHMAD KAMANDANU <br>AGUNG SEPTI |  | Indikasi Kerusakan :Masalah Sistem Elektrikal (Electrical System)<br>    Malfungsi Sensor (Sensor Error): Sensor vital seperti Accelerator Pedal Sensor (P2121-P2128), Boost Pressure Sensor (P0540), atau Crankshaft/Camshaft Position Sensor (P0340) mengalami kerusakan atau kabelnya bermasalah.<br>    Masalah pada Main Relay (P0686): Kerusakan pada relay utama yang menyuplai daya ke ECM dapat menyebabkan sistem tidak stabil atau mesin mati total.<br>    Masalah Komunikasi CAN Bus (U0155, U1001, U1123): Gangguan komunikasi antar modul elektronik (ECU, TCU, ABS) yang sering disebabkan oleh kabel terkelupas, soket kendor, atau korosi pada sambungan kabel.<br>    Kerusakan Solenoid Retarder (P1681/P1682): Hubungan arus pendek pada solenoid retarder dapat memicu lampu check engine dan mengurangi tenaga.<br>    Kabel/Fuse Terbakar (Burned Fuse/Wire): Kabel yang terbakar atau koneksi sekring yang kendor/meleleh pada sistem bahan bakar atau ECU SAAT INI MANAGEMENT BELUM MEMILIKI AHLI ELECTRICAL <br>PERLU PENANGANAN KHUSU MENGEMBALIKAN KONDISI PERFORMA<br>Penyebab Low Power (Tenaga Kurang)<br>    Katup EGR Macet (Stuck EGR Valve): Salah satu penyebab paling sering. Katup EGR yang macet menyebabkan akselerasi hilang, mesin bising, getaran saat idle, dan tenaga drop.<br>    Masalah Suction Control Valve (SCV) / Tekanan Bahan Bakar (P0093, P1266): Tekanan bahan bakar rendah atau SCV kotor/rusak membuat mesin kekurangan bahan bakar, menyebabkan low power.<br>    Filter Udara atau Fuel Filter Tersumbat: Aliran udara atau solar yang tidak lancar membatasi tenaga mesin.<br>    DPR/DPF Tersumbat (Regenerasi Gagal): Pada Euro 4, sistem Diesel Particulate Reduction (DPR) yang kotor dan tidak melakukan regenerasi secara otomatis/manual akan menyumbat buang dan membatasi RPM.<br>    Turbocharger Malfunction: Actuator turbo bermasalah atau kebocoran boost pressure membuat tenaga mesin berkurang drastis | BD |
| 457 | 2026-02-08 | HUJAN JAM 14.40 MEKANIK BERTUGAS : APENG SUWARDI RAHMAD,RAHMAD KAMANDANU,AGUNG SEPTI,REJEKI SIREGAR,JHONI IST KANDAR,YANDI KARYA LANDAK GOLOK 1000,FAJAR DC <br>14.57 berakhir mekanik lain jam 16.00 Off yang masih operasi RAHMAD KAMANDANU &amp; AGUNG SEPTI |  |  |  |  |  |  |  |

## Missing Time 2026-02-09

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 465 | 2026-02-09 | DT-049 | B 9107 ZYT | TANGKI HYDRAULIC PECAH | 10.57 | JHONI ISKANDAR <br>APENG SUWARDI RAHMAD |  |  | BD |
| 483 | 2026-02-09 | DT-029 | B 9137 ZYT | 1.Bocor ban depan <br>2.baut roda patah 1 | 15.31 | YANDI KARYA <br>USKHA <br>LANDA 1000 GOLOK |  |  | BD |

## Missing Time 2026-02-10

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 491 | 2026-02-10 |  | BM 9244 NU | DISMANTLE DISC CLUTH LIMIT <br>Membuka/menurunkan transmisi <br>ORDER PARTS 12.34 WIB | 10.36 | YANDI KARYA <br>LANDAK |  | Disebabkan oleh beban kerja berat, kondisi operasional yang ekstrem, dan perilaku pengemudian. Sebagai dump truck berkapasitas besar yang sering digunakan di sektor tambang atau konstruksi (mengangkut beban 20-30 ton lebih), kopling bekerja ekstra keras | BD |
| 500 | 2026-02-10 | WT-01 | B 9018 KFA | Ijin permintaan seperpat untuk WT-00001<br>~sensor sepido meter 1 pcs | 15.36 | TEPU FUSO <br>USKHA |  | Kerusakan Fisik &amp; Umur Sensor (Material Wear): Sensor kecepatan sering kali mengalami keausan material, terutama pada komponen magnet dan kumparan di dalamnya. Pada beberapa kasus (terutama bus/truk), sensor memiliki karakteristik ekspansi termal yang berbeda saat panas, menyebabkan sirkuit terbuka (open circuit) di dalam sensor. | BD |
| 504 | 2026-02-10 | DT-014 | B 9683 KYW | Kunci kontak unit dt 00054 patah… | 16.56 | JHONI IST KANDAR |  | Lubang Kunci Kotor/Kering: Debu, kotoran, atau karat yang menumpuk di dalam lubang kunci (cylinder lock) menyebabkan gesekan tinggi, membuat kunci sulit diputar dan akhirnya patah. | BD |
| 505 | 2026-02-10 |  | BM 8921 QU | DIEMANTLE BLOCK ENGINE | 17.09 | HENDRICO <br>EKO<br>SANTOSO |  | OVERHOUL ENGINE | BD |

## Missing Time 2026-02-11

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 520 | 2026-02-11 | DT-043 | BG8639NI | RADIATOR JEBOL | 17.25 | JHONI IST KANDAR |  | Benturan dan getaran: Getaran konstan akibat penggunaan berat dan potensi benturan dari puing-puing atau material lain selama operasi juga dapat menyebabkan kerusakan fisik pada komponen radiator seperti kisi-kisi atau selang. | BD |

## Missing Time 2026-02-12

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 523 | 2026-02-12 |  | BM 9174 NU. | Jenis  Pekerjaan : <br>1~ Repair Muffler broken/bocor<br>2~ Repair Safety Underround Samping kiri broken. | 10.51 | APENG S WARDI RAHMAD |  | RUSAK AKIBAT PENGOPERASIAN KONDISI SEPERTI INI BERULANG ULANG UNIT INI DAN MOHON ESOK KITA SERVICE SEGERA | RFU |
| 530 | 2026-02-12 | DT-033 | BG 8230 MX | Radiator bocor | 11.30 | SI BOY |  | Korosi dan Karat (Penyebab Utama): Penggunaan air biasa (bukan coolant kualitas baik) memicu karat di bagian dalam radiator. Korosi ini akhirnya memakan material radiator dan menciptakan lubang kebocoran.<br>Benturan Fisik dan Getaran: Saat beroperasi di medan pertambangan atau konstruksi, radiator rentan terkena benturan benda keras atau getaran berlebihan yang meretakkan komponen. | BD |
| 532 | 2026-02-12 | DT-070 | B 9131 ZYT | Jenis  Pekerjaan : <br>❎1~ Underround Protection Patah.( Proses pengerjaan ).<br>❎2~ Underround Safety Samping sebelah kiri broken.( Proses pengerjaan )<br>❎3~ Safety Apar Parah ( proses pengerjaan). | 13.55 | APENG S WARDI RAHMAD |  | RUSAK AKIBAT PENGOPERASIAN BELUM BISA DIMINIMALISR YANG RUSAK RUSAK BEGINI | RFU |
| 535 | 2026-02-12 | DT-043 | BG8639NI | GANTI RADIATOR BARU HINO 500 350 PD | 14.53 | JHONI IST KANDAR |  | Benturan dan getaran: Getaran konstan akibat penggunaan berat dan potensi benturan dari puing-puing atau material lain selama operasi juga dapat menyebabkan kerusakan fisik pada komponen radiator seperti kisi-kisi atau selang. | BD |
| 542 | 2026-02-12 | DT-010 | B 9701 PYW | MUFLER | 17.3 | APENG SUWARDI RAHMAD |  | KECAPEKAN TEAM DILANJUTKAN ESOK | BD |

## Missing Time 2026-02-13

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 546 | 2026-02-13 | DT-010 | B 9701 PYW | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). | 8.33 | APENG SUWARDI RAHMAD |  | MUFLER KROPOS DAN PACKING RUSAK <br>GANTI GASKET EX MFLD 17104-1580 | BD |

## Missing Time 2026-02-14

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 559 | 2026-02-14 | DT-010 | B 9701 PYW | Jenis  Pekerjaan : <br>❎1. Muffler broken ( proses pengerjaan )<br>❎2~ Lantai Dump Robek di beberapa titik ( proses pengerjaan). | 8.13 | APENG S WARDI RAHMAD |  | Kondensasi Air di Dalam Knalpot (Internal Rust): Saat mesin dimatikan setelah beroperasi, uap air hasil pembakaran di dalam knalpot mendingin dan berubah menjadi cair. Air ini mengendap di muffler, terutama jika truk sering digunakan untuk perjalanan jarak pendek, sehingga knalpot tidak sempat panas cukup lama untuk menguapkan kembali air tersebut.<br>BAK ADA INDIKASI ROBEK OLEH KUKU BUCKET EXCA | BD |

## Missing Time 2026-02-16

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 574 | 2026-02-16 | DT-057 | B 9116 ZYT | Jenis  Pekerjaan : <br>✅1. Fabrikasi safety bumper belakang | 10.00 | HENDRIK KE SETROOM |  | RUSAK AKIBAT METODE KERJA DAN SITE LOKASI DUMPING | BD |
| 575 | 2026-02-16 | DT-022 | Z 9109 AB | Jenis  Pekerjaan : <br>-penggantian kampas rem belakang sebelah kiri<br>-lampu depan sebelah kanan pecah satu set | 9.40 | JHONI IST KANDAR<br>BOY GABRIEL SAHULEKA |  | Sering menginjak rem, terutama saat di jalan menurun atau macet, menyebabkan gesekan terus-menerus yang merusak kampas. | BD |
| 576 | 2026-02-16 | DT-072 | B 9133 ZYT | Jenis  Pekerjaan : <br>1.~ Underround Protection Patah <br>2~ Kotrek Gantungan Ban Serep Broken.<br>3~ Fabrikasi Whellchock.<br>4~ Ganti Filter Solar bagian Bawah. | 11.08 | APENG <br>LANDA GOLOK 1000 |  | NO.1 RUSAK AKIBAT PENGOPERASIAN <br>2.KWALITAS PRODUCT KURANG BAIK<br>3.ADA KEMUNGKINAN HILANG DULU 2024 SUDAH DISIAPKAN <br>4.KEBIASAN DI 3600 KM FILTER ATAS BAWAH KOTOR | BD |
| 577 | 2026-02-16 | DT-47 | BG 8976 IX | Jenipekerjaan <br>~servis berkala <br>  *Ganti oli mesin 25 L<br>  *Ganti filter solar 3 pcs<br>  *Ganti filter oli 2 pcs | 10..45 | Tepu fuso<br>USKHA HAKAN | 11.32 | PM SERVICE SAAT INI :61.092<br>KEMBALI DI KM : 68.592<br>SEHARUSNYA INI PM 6 GENERAL SERVICE | RFU |
| 578 | 2026-02-16 | DT-020 | B 9973 BIS | MASTER KOPLING BAWAH |  |  |  | BELUM DILAKUKAN PERBAIKAN | BD |
| 579 | 2026-02-16 | DT-061 | B 9122 ZYT | KURANG OLI HYDRAULIC 5 liter |  |  |  | BELUM DILAKUKAN PERBAIKAN | BD |
| 580 | 2026-02-16 | DT-060 | B 9121 ZYT | MATI KABUT LAMPU KIRI |  |  |  | BELUM DILAKUKAN PERBAIKAN | BD |
| 581 | 2026-02-16 | TMC-01 | B 9008 ZIN | FRAME TMC RETAK |  |  |  | BELUM DILAKUKAN PERBAIKAN | BD |

## Missing Time 2026-02-18

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 587 | 2026-02-18 | DT-010 | B 9701 PYW | Proses perbaikan sil hidrolik Dau ..Ter info turun sediri… | 10.36 | YANDIKARYA <br>LANDAK GOLOK 1000 |  | Seal Silinder Hidrolik Aus/Bocor (Internal Leakage): Kebocoran di dalam silinder hidrolik menyebabkan oli hidrolik bypass, sehingga beban bak tidak tertahan. | BD |
| 590 | 2026-02-18 | DT-013 | B 9644 KYW | Izin pak buk hasil temuan dari mekanik per depan DT0053 patah nomor 5.6.7.8 | 11.10 | TEPU FUSO <br>USKHA HAKAN |  | disebabkan oleh kombinasi beban kerja ekstrem, kelelahan material (fatigue), dan perawatan yang kurang. Daun per bernomor lebih tinggi (yang lebih pendek dan tebal di bagian bawah) sering patah karena berfungsi menahan beban puncak (overload) saat truk dalam kondisi penuh. | BD |
| 595 | 2026-02-18 | DT-022 | Z 9109 AB | Jenis  Pekerjaan : <br>-penggantian kampas rem belakang sebelah kiri<br>-lampu depan sebelah kanan pecah satu set<br>Kendala Teknis : nunggu boss per dan bola lampu depan sebelah kanan satu set | 13.00 | REJEKI NYA P SIREGAR<br>BOY GABRIEL SAHULEKA |  | Sering menginjak rem, terutama saat di jalan menurun atau macet, menyebabkan gesekan terus-menerus yang merusak kampas. | BD |
| 598 | 2026-02-18 | DT-104 | BM 9398 JO | Prosess penggantian karet susu<br>Jenis  Pekerjaan : <br>1.backup alarm (selesai)<br>2.karet susu 4pcs (selesai)<br>3.lampu kabut depan kanan<br>4.karet bantalan dump<br>5.control Valve rem bocor<br>6.lantai dump<br>7.kisi-kisi dump | 13.01 | 1.YANDI KARYA <br>2.LANDA GOLOK 1000 |  | Keausan Alami (Faktor Usia): Karet stopper merupakan komponen fast moving yang akan mengeras, retak, dan akhirnya getas seiring berjalannya waktu dan intensitas penggunaan.<br>Benturan Keras (Impak): Suspensi yang sering "mentok" (bottoming) akibat jalan rusak membuat benturan fisik yang keras, yang melebihi batas elastisitas karet, sehingga memicu robekan. | BD |
| 607 | 2026-02-18 | WL-02 | SEM-665 | Jenis  Pekerjaan : <br>❎1~ Fork Breaket pecah pada las an. | 16.09 | APENG SUWARDI RAHMAD |  | FRAME PALLET FORK RETAK | RFU |

## Missing Time 2026-02-19

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 611 | 2026-02-19 |  | BK 9121 EQ | Melengkapi temuan inspek<br>~ merubah dimensi underaun kanan/ kiri<br>~ lampu Mundur tidak hidup<br>~instal alarm mundur<br>~DLL<br>PASANG E - STOP | 11.01 | DARMAWAN <br>RAHMAD K MANDANU<br>LANDA GOLOK 1000<br>EKO SUKIATNO |  | PENYELESAIAN INSPECT TPE PROJECT RWI | BD |
| 612 | 2026-02-19 | 104 | BM 9398 JO | Jenis  Pekerjaan : <br>❎1~ Lantai Dumping Robek di beberapa titik.<br>❎2~ Sub Chasis Dump Krek Di beberapa titik.<br>❎3~ Spring/pir depan bagian kanan patah 1 pcs ( Spring no 6 ).<br>❎4~ Control Valve Bocor.<br>❎5~ Tambah Oly Engine.<br>❎6~ Regreasing/piapot. | 11.01 | APENG SUWARDI RAHMAD<br>JHONI IST KANDAR |  | UNIT INI TERJADWAL SERVICE DI KM 89.896<br>KM SAAT INI TANGGAL 19/2/2026 90.672<br>UNTUK DI SERVICE SEKALIAN | BD |
| 614 | 2026-02-19 | DT-068 | B 9129 ZYT | Jenis  Pekerjaan : <br>❎1~ Tank Hydrolic pecah.<br>( Sisi lain Tank sdh pernah mengalami hal yang sama/pecah)<br>❎2~ Underround Protection Patah di sisi kanan dan kiri.<br>❎ Hour Meter ( HM ) mati . | 15.41 | APENG SUWARDI RAHMAD |  | JAM 16 OFF | BD |
| 615 | 2026-02-19 |  | B 9116 ZYT | Seal oil pompa hirdoliy bocor | 16.21 | REJEKINYA SIREGAR |  | Usia Komponen dan Keausan (Aging &amp; Wear): Seal yang terbuat dari bahan karet atau elastomer akan mengeras, retak, atau menipis setelah pemakaian jangka panjang.<br>Tekanan Hidrolik Berlebihan (Overpressure): Pengisian oli berlebihan atau beban muatan yang melebihi kapasitas (overload) menyebabkan tekanan sistem terlalu tinggi, yang dapat memaksa seal keluar atau rusak. | BD |

## Missing Time 2026-02-20

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 619 | 2026-02-20 | DT-104 | BM 9398 JO | Jenis  Pekerjaan : <br>❎3~ Spring/pir depan bagian kanan patah 1 pcs ( Spring no 6 ). | 11.01 | YANDI KARYA <br>BOY GABRIEL SAHULEKA |  | UNIT INI TERJADWAL SERVICE DI KM 89.896<br>KM SAAT INI TANGGAL 19/2/2026 90.672<br>UNTUK DI SERVICE SEKALIAN | BD |
| 623 | 2026-02-20 | DT-065 | B 9126 ZYT | penggantian puli AC dan belting AC | 12.10 | YANDI KARYA <br>BOY GABRIEL SAHULEKA |  | Kompresor AC Macet/Macet (Seized): Jika kompresor AC macet akibat kurang oli atau rusak, puli akan berhenti berputar sementara v-belt tetap diputar oleh mesin. Gesekan ekstrim ini membuat belt panas dan putus.<br>Bearing Puli Rusak: Bearing pada puli idler atau puli kompresor yang aus menyebabkan putaran tidak lancar, selip, dan akhirnya putus.<br>Puli Tidak Sejajar (Misalignment): Puli yang miring membuat belt bekerja tidak rata, aus satu sisi, dan cepat putus. | BD |
| 626 | 2026-02-20 | DT-104 | BM 9398 JO | Jenis  Pekerjaan : <br>❎3~ Spring/pir depan bagian kanan patah 1 pcs ( Spring no 6 ). | 11.01 | YANDI KARYA <br>LANDA GOLOK 1000 |  | UNIT INI TERJADWAL SERVICE DI KM 89.896<br>KM SAAT INI TANGGAL 19/2/2026 90.672<br>UNTUK DI SERVICE SEKALIAN | BD |

## Missing Time 2026-02-21

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 628 | 2026-02-21 | DT-104 | BM 9398 JO | Jenis  Pekerjaan : 65% MENUJU 100 % LAMA SEKALI<br>❎1~ Lantai Dumping Robek di beberapa titik.( Proses) | 8.20 | APENG SUWARDI RAHMAD |  | UNIT INI TERJADWAL SERVICE DI KM 89.896<br>KM SAAT INI TANGGAL 19/2/2026 90.672<br>UNTUK DI SERVICE SEKALIAN | BD |
| 633 | 2026-02-21 | DT-047 | BG 8976 IX | Jenis  Pekerjaan :<br>Perbaikan safety underround protection broken sisi kiri | 9.00 | HENDRIK STROOM |  | RUSAK AKIBAT PENGOPERASIAN | RFU |
| 635 | 2026-02-21 |  |  | Jenis  Pekerjaan : <br>❎1~ Fabrikasi Safety Apar untuk kebutuhan Unit Rwi. ( Proses )<br>❎2~ Fabrikasi Whellchock untuk kebutuhan Unit Rwi.( Proses ) |  | APENG SUWARDI HENDRIK RAHMAD |  | UNUTK KELNGKAPAN UNIT RWI | BD |
| 639 | 2026-02-21 | DT-013 | B 9644 KYW | Jenis  Pekerjaan : <br>1.per no 5,6,7,8 patah | 10.48 | YANDI KARYA <br>LANDA GOLOK 1000<br>GABRIEL SAHULEKA |  | disebabkan oleh kombinasi beban kerja ekstrem, kelelahan material (fatigue), dan perawatan yang kurang. Daun per bernomor lebih tinggi (yang lebih pendek dan tebal di bagian bawah) sering patah karena berfungsi menahan beban puncak (overload) saat truk dalam kondisi penuh. | BD |

## Missing Time 2026-02-23

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 655 | 2026-02-23 | WT-01 | B 9018 KFA | SETTING KOPLING BAWAH | 13.23 | TEPU FUSO |  |  | RFU |
| 658 | 2026-02-23 | DT-01 | DT 8126 JE | Izin bapak/ibuk , melanjutkan proses dismentle | 14.07 | RAHMAD KAMANDANU |  |  | BD |

## Missing Time 2026-02-24

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 665 | 2026-02-24 | DT-012 | B 9642 KYW | TERMINAL ACCU KROPOS |  |  |  |  | RFU |
| 666 | 2026-02-24 | DT-033 | BG 8230 MX | TERMINAL ACCU KROPOS |  |  |  |  | RFU |
| 669 | 2026-02-24 |  |  | Jenis  Pekerjaan : <br>❎1~ Fabrikasi Safety Apar untuk kebutuhan Unit Rwi. ( Proses )<br>❎2~ Fabrikasi Whellchock untuk kebutuhan Unit Rwi.( Proses ) | 10.09 | APENG SUWARDI RAHMAD CAR TOLO |  | PROSES KELENGKAPAN WHELL COOCK UNTUK EQUIPMENT RWI ( 50 % ) | RFU |
| 670 | 2026-02-24 | DT-043 | BG 8639 NI | Unit dt 00063 minta servis AC.<br>Dinamo AC sudah rusak. | 10.14 | JHONI IST KHANDAR |  | 1,CEK KONDENSOR <br>1.CEK EVAPORATOR DLL | RFU |
| 672 | 2026-02-24 | DT-065 | B 9126 ZYT | GANTI PULLEY AC | 10.43 | DARMAWAN |  | beban kerja yang berat, lingkungan operasional berdebu, dan panas ekstrem dari mesin. Kerusakan umumnya terjadi pada<br>bearing pulley atau komponen magnetic clutch (kopling magnet). | BD |
| 673 | 2026-02-24 |  | BM 9682 JO | LANJUTAN CEKLIST LOW POWER | 10.10 | RAHMAD K MANDANU <br>MEKANIK HINO |  |  | BD |
| 678 | 2026-02-24 |  | BM 9810 QO | Jenis  Pekerjaan : <br>1.pengecekan disk clutch (tidak mau jalan saat di masukan gigi) | 13.01 | YANDIKARYA <br>LANDA GOLOK 1000 |  |  | BD |
| 680 | 2026-02-24 | DT-043 | BG 8639 NI | cek AC tidak dingin | 13.55 | DARMAWAN |  | kurangnya atau habisnya freon akibat kebocoran, kondensor yang kotor/tersumbat lumpur, kompresor AC aus, filter kabin tersumbat, atau fan belt putus. Masalah ini menyebabkan hembusan angin saja tanpa suhu dingin, terutama saat beroperasi di area berdebu &amp; kotor. | BD |
| 681 | 2026-02-24 | DT-054 | B 9118 ZYT | Jenis  Pekerjaan : <br>1.Dump mau naik tidak mau turun<br>Status Unit: Sudah beroperasi <br>    <br>Kendala Teknis :<br>Tindakan Mitigasi : Menyetel tali handle pto | 14.18 | YANDIKARYA <br>LANDA GOLOK 1000 |  | Temuan : Pompa PTO bocor (Sealnya)<br>Part yang direquest: SEAL POMPA PTO | RFU |
| 684 | 2026-02-24 | DT-023 | B 9078 ZYT | Ganti wiper kaca. |  | REJEKI SIREGAR | 14.42 | Aus dan karet mulai tipis | RFU |

## Missing Time 2026-02-25

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 688 | 2026-02-25 | DT-022 | Z 9109 AB | Pasang Lampu assy depan RH | 9.15 | jhoni ist kandar |  |  | RFU |
| 689 | 2026-02-25 | ROAD WELLPAD PROJECT |  | Jenis Pekerjaan:<br>1~ Fabrikasi Safety apar.( Melengkapi Unit  project RWI )<br>2~ Fabrikasi Whellchock ( Melengkapi Unit project RWI). | 9.31 | APENG SUWARDI RAHMAD |  | PERLENGKAPAN PERALATAN RWI | RFU |
| 690 | 2026-02-25 | DT-01 | DT 8126 JE | Packing cilynder head HOP<br>~Liner 1pcs<br>~Lem packing <br>~Paking karter (bak oli)<br>~Ring piston untuk 1 pcs piston no 6 | 11.19 | DARMAWAN |  |  | BD |
| 696 | 2026-02-25 | DT-026 | B 9091 ZYT | Proses bongkar ban <br>Bocor bagian belakang 2 pcs | 14.09 | JHONI IST KANDAR |  | BAN BOCOR | BD |
| 698 | 2026-02-25 | DT-09 | B 9105 ZYT | Perbaikan baut Ban<br>Jenis  Pekerjaan : <br>❎1~ penggantian baut DORA 1pcs<br>     2~ pemotongan baut DORA yang mutar | 15.00 | YANDI KARYA<br>LANDAL GOLOK 1000<br>BOY BOLANG | 16.5 | Pengencangan yang Tidak Tepat (Improper Torque) <br>    Terlalu Kencang (Overtightening): Mengencangkan baut roda dengan impact wrench secara berlebihan atau diinjak tanpa menggunakan kunci torsi (torque wrench) dapat meregangkan baut melampaui batas elastisitasnya, membuatnya mudah putus. | RFU |
| 701 | 2026-02-25 | DT-07 | B 9103 ZYT | Slmt mlm pak bu aku pesan selang minyak balik bocor punyai dt 00048 | 16.07 | OPUNG REGAR |  | Pecah dan umur selang sudah habis masa nya | BD |
| 705 | 2026-02-25 | DT-03 | BM 9285 JO | ijin permintaan seperpat <br>~Puliy AC 1 pcs | 16.32 | TEPU FUSO <br>USKHAHAKAN |  | kombinasi beban kerja berat, lingkungan operasional yang ekstrem, dan faktor pemeliharaan. V-belt merupakan komponen habis pakai yang menyalurkan putaran mesin ke alternator, pompa air, dan kompresor AC, sehingga beban kerjanya sangat tinggi | BD |

## Missing Time 2026-02-26

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 714 | 2026-02-26 | DT-033 | DT-8669 KE | Pagi pak bu minta izin  mau cabut baut yg patak knapot  punyai <br>KNALPOT BOCOR | 11.37 | OPUNG REGAR |  | Siklus Panas Ekstrem (Thermal Cycling): Manifold knalpot mencapai suhu sangat tinggi saat beroperasi dan mendingin saat mesin mati. Proses ekspansi (memuai) dan kontraksi (menyusut) logam ini berulang kali menyebabkan baut mengalami stres mekanis yang tinggi, menjadi longgar, dan akhirnya patah. | BD |
| 715 | 2026-02-26 | ROAD WELLPAD PROJECT |  | Progres Melanjutkan Fabrikasi <br>Safety Apar<br>Dan Painting Whellchock… | 13.33 | APENG S WARDI RAHMAD |  | MELENGKAPAI UNTK UNIT RWI | BD |
| 718 | 2026-02-26 | DT-043 | BG 8639 NI | Slmt sore pak bu radirator boco lagi  posisi di pos merah | 15.29 | OPUNG REGAR |  | Engine Mount (Bantalan Mesin) Rusak atau Lemah: Ini adalah penyebab paling umum. Jika engine mount (karet dudukan mesin) pecah atau aus, mesin akan bergerak/goyang berlebihan, terutama saat akselerasi atau pengereman mendadak. Goyangan ini mendekatkan kipas ke radiator hingga terjadi benturan.ANALISA | BD |
| 719 | 2026-02-26 | DT-088 | BM 9944 JO | Izin bapak ibuk, permintaan Spertpat untuk DT04012<br>-selang jembrit<br>Sekian terimakasih | 15.35 | BOY BOLANG |  | Benturan dengan objek: Kendaraan mungkin menabrak batu besar, gundukan tanah, atau puing-puing lain di jalan atau area kerja, menyebabkan braket penyok dan selang terjepit atau rusak. | BD |
| 721 | 2026-02-26 | DT-06 | B 9102 ZYT | Izin bapak ibuk permintaan buat <br>-Ganjal ban 2pcs <br>Sekian terimakasih | 16.04 | BOY BOLANG |  | ACCESSORIES WHEEL COCK TIDAK ADA PADAHAL SEBELUM NYA ADA | RFU |
| 722 | 2026-02-26 | DT-104 | BM 9398 JO | permintaan DT 04025<br>-selang 16 , 8meter<br>Sekian terimakasih🙏🏻<br>Sambung selang ukuran 15 soal nya yg lama ukuran 16 kebesaran angin nya tetap bocor | 16.07 | BOY BOLANG |  | SLANG APA YANG DI MAKSUD | RFU |

## Missing Time 2026-02-27

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 736 | 2026-02-27 | ROAD WELLPAD PROJECT |  | Progres Melanjutkan Fabrikasi <br>Safety Apar<br>Dan Painting Whellchock… | 14.01 | APENG S WARDI RAHMAD |  | MELENGKAPAI UNTK UNIT RWI | BD |
| 739 | 2026-02-27 | DT-032 | BG 8976 IX | Udh hidup lampu dan ganti fuse<br>Slmt siang aku pesan handel pintuk sebelah kiri | 15.04 | OPUNG REGAR |  | filamen putus karena umur pakai/panas berlebih, sekring putus, atau sakelar pedal rem rusak. Konslet terjadi akibat kabel terkelupas, soket berkarat, atau penggunaan bohlam yang tidak sesuai standar watt | RFU |

## Missing Time 2026-02-28

| No | Date | Kode_Unit | NoPol | Damage_Info | Start_Time | Mechanic | End_Time | Analisa | Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 742 | 2026-02-28 | ROAD WELLPAD PROJECT |  | Progres Melanjutkan Fabrikasi <br>Safety Apar<br>Dan Painting Whellchock… | 8.37 | APENG S WARDI RAHMAD |  | MELENGKAPAI UNTK UNIT RWI | BD |
| 755 | 2026-02-28 | DT-043 | BG 8639 NI | Mohon izin mau radirator dt 00063 yg bocor di bawa,makasil banyak atas kerja sama ya<br>Temuan penyakit  dalam ,per daun no 5 putus  depan sebelah kanan | 15.26 | OPUNG REGAR |  | Engine Mount (Bantalan Mesin) Rusak atau Lemah: Ini adalah penyebab paling umum. Jika engine mount (karet dudukan mesin) pecah atau aus, mesin akan bergerak/goyang berlebihan, terutama saat akselerasi atau pengereman mendadak. Goyangan ini mendekatkan kipas ke radiator hingga terjadi benturan.ANALISA | BD |

# 7. Catatan Rekonsiliasi Antar-Sheet

Terdapat beberapa angka yang tidak dapat direkonsiliasi secara langsung hanya dari workbook:

1. Sheet Metodologi menyatakan **394 baris pekerjaan**, **308 baris dengan Start & End valid**, dan coverage **78,2%**.
2. `Detail_Job_Bertiming` berisi **318 baris**, karena pekerjaan bersama diperluas menjadi satu baris per mekanik.
3. `Missing_Time` berisi **84 baris**, sedangkan selisih 394 − 308 adalah 86.
4. Tidak tersedia job ID unik yang menghubungkan baris sumber, baris alokasi, dan baris missing-time secara satu-ke-satu.
5. Karena itu, jumlah baris pada sheet hasil tidak boleh dijumlahkan langsung untuk memperoleh total pekerjaan sumber.

Perbedaan tersebut tidak diperbaiki atau dipaksakan dalam konversi. Validasi lebih lanjut membutuhkan data sumber sebelum proses normalisasi dan alokasi.

# 8. Catatan Kualitas Data

1. Nama mekanik pada sheet `Missing_Time` masih berupa teks mentah, kadang berisi beberapa nama, julukan, atau keterangan tambahan dalam satu sel.
2. Nama pada ringkasan telah dinormalisasi, misalnya `Joni (Jhoni Ist Kandar)` dan `Rezeki Siregar`, tetapi pemetaan alias tidak tersedia pada workbook.
3. Format tanggal/waktu tidak konsisten. Dua baris job No. 372 memakai tanggal `2//2/2026`; contoh waktu mencakup `08.00`, `9.13`, `10..45`, angka `17.3`, atau sel kosong.
4. Beberapa kode unit kosong atau ditulis tidak konsisten, misalnya dengan spasi dan tanda hubung berbeda.
5. Kolom Damage_Info dan Analisa mengandung teks lapangan, typo, singkatan, dan percakapan yang dipertahankan.
6. Delay sparepart ditandai sebagai boolean, tetapi kriteria deteksinya tidak dijelaskan dalam sheet metodologi.
7. Jam dibagi rata pada pekerjaan bersama; workbook tidak mendokumentasikan bobot kontribusi aktual.
8. Batas lembur pukul 16.00 diterapkan tanpa informasi jadwal shift atau hari kerja.
9. Standar 208 jam digunakan sama untuk semua mekanik tanpa penyesuaian cuti, izin, tanggal bergabung, atau penugasan lain.
10. Tabel perbandingan tambahan pada sheet Mekanik Inti memiliki angka yang tidak sama dengan tabel alokasi utama.

# 9. Rekomendasi Pengembangan Analisis

Bagian ini merupakan rekomendasi, bukan isi asli workbook.

## 9.1 Identitas Job

Tambahkan `job_id` unik agar satu pekerjaan dapat ditelusuri melalui data mentah, timing, mekanik, parts, status, dan hasil perbaikan.

## 9.2 Struktur Personel

Pisahkan mekanik ke tabel relasi satu baris per job-mekanik dengan `mechanic_id`, nama baku, alias, peran, dan bobot kontribusi.

## 9.3 Timing

Gunakan datetime lengkap, bukan teks waktu. Tambahkan validasi End Time ≥ Start Time, penanganan pekerjaan lintas hari, waktu pause, dan alasan missing time.

## 9.4 Produktivitas

Pisahkan indikator berikut:

- jam hadir;
- jam tersedia;
- jam wrench time;
- jam menunggu parts;
- jam menunggu unit/izin;
- jam perjalanan;
- jam normal dan lembur;
- first-time fix;
- repeat repair;
- job selesai; dan
- backlog.

## 9.5 Delay Sparepart

Simpan waktu request part, waktu tersedia, waktu issue, nama part, part number, dan dampak jam. Dengan demikian delay dapat dihitung dari durasi aktual, bukan hanya jumlah job yang diberi indikasi.

## 9.6 Standar Jam

Standar 208 jam sebaiknya disesuaikan dengan kalender kerja efektif, cuti, izin, training, penugasan lapangan, dan tanggal aktif mekanik.

# 10. Kesimpulan

Workbook memberikan gambaran awal produktivitas mekanik Februari 2026 melalui alokasi durasi job, pemisahan jam normal dan lembur, jumlah partisipasi job, serta indikasi delay sparepart.

Pada ringkasan semua nama, total jam yang dialokasikan adalah **489.62 jam**, terdiri dari **430.11 jam normal** dan **59.51 jam lembur**. Rahmad K memiliki jam tertinggi, sedangkan Urwatul Usk memiliki jumlah partisipasi job terbanyak.

Nilai efektivitas belum dapat dianggap sebagai produktivitas menyeluruh karena coverage waktu tidak penuh, job tanpa End Time dikeluarkan, alokasi pekerjaan bersama dibagi rata, dan workbook belum memasukkan kualitas hasil, tingkat kesulitan, ketersediaan mekanik, atau repeat failure.
