# Panduan Testing Integrasi Update Dump Truck 09 November 2026

Tujuan pengujian ini adalah memastikan 13 PDF sudah ditranskripsikan, 75 aset fisik unik pada laporan terbaru sudah terhubung ke Master Asset, data ganda sudah direkonsiliasi, dan aset lain tidak ikut tercampur.

## 1. Persiapan Laragon

1. Pastikan Apache dan MySQL Laragon aktif.
2. Dari root proyek, jalankan:

   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts\sync_to_laragon.ps1
   ```

3. Pastikan proses berakhir tanpa error dan menampilkan bahwa migrasi additive sudah diverifikasi.

Perintah tersebut tidak mereset seluruh database secara default. Migrasi dump truck bersifat idempoten: aman dijalankan ulang dan tidak menambah snapshot/hull history ganda.

## 2. Tes otomatis database dan berkas

Jalankan:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' tests\dumptruck_update_20261109_integration.php
```

Hasil yang benar adalah seluruh pemeriksaan `PASS`, termasuk:

- 13 PDF dan 13 Markdown tersedia;
- 986 snapshot dari 13 tanggal;
- laporan 9 November berisi 76 baris tetapi hanya 75 pelat unik;
- tidak ada snapshot terbaru yang belum terhubung;
- tidak ada pelat aktif ganda pada aset yang diintegrasikan;
- 9 aset yang sebelumnya belum ada sudah tersedia;
- 9 identitas kanonis lama yang sempat kosong/hilang pada database lokal juga dipulihkan;
- `DT-00152` nonaktif dan riwayatnya sudah pindah ke `DT-00056`;
- konflik sumber `DT 1429`/`DT 1440` tetap tercatat tanpa membuat aset kedua.

## 3. Double-check pada website

1. Buka `http://localhost/ServicePlan-BRA/` dan login.
2. Masuk ke menu **Master Asset**.
3. Cari contoh berikut satu per satu:

   | Pencarian | Hasil yang diharapkan |
   |---|---|
   | `B 9110 ZYT` | Asset ID `DT-00002`, lambung `DT 1102`, status Operating |
   | `B 9078 ZYT` | Asset ID `DT-00037`, lambung `DT 1137` |
   | `B 9892 PYW` | Hanya satu record aktif: `DT-00056`, lambung `DT 1156`, status Breakdown, posisi Yard KM 12 |
   | `BM 9505 QO` | Asset ID `BRA-DT-1469`, status Breakdown |
   | `BM 9819 QO` | Hanya satu record aktif: `DT-04029`, lambung `DT 1429` |

4. Pada kolom **Kondisi Data**, pastikan muncul label sumber seperti **Update DT 09 Nov 2026**. Arahkan mouse ke label tersebut untuk melihat keterangan dari PDF.
5. Filter status **Breakdown** lalu pastikan unit broken dari laporan muncul, misalnya `DT 1101`, `DT 1156`, `DT 1462`, `DT 1469`, dan `DT 1174`.
6. Filter posisi atau cari `YARD KM 12`; contoh yang harus berada di sana adalah `DT 1147`, `DT 1156`, `DT 1162`, `DT 1425`, `DT 1461`, dan `DT 1174`.

## 4. Pemeriksaan isolasi data yang tidak terkait

1. Pilih tiga aset non-Dump Truck yang sudah dikenal sebelumnya, misalnya satu Excavator, satu Vibro Compactor, dan satu Light Vehicle.
2. Catat Asset ID, nomor seri, status, lokasi, dan jumlah Work Order-nya.
3. Jalankan ulang sinkronisasi dan refresh browser.
4. Pastikan kelima nilai tadi tetap sama.

Secara teknis migrasi hanya mencocokkan 75 nomor polisi dari snapshot 9 November. Test otomatis juga memastikan tepat 75 Master Asset memiliki provenance laporan ini; aset di luar daftar itu tidak menjadi target `UPDATE`.

## 5. Membandingkan dengan dokumen sumber

Indeks seluruh dokumen tersedia di:

`material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/README.md`

Setiap file Markdown memuat hash PDF, tabel terstruktur, dan transkripsi tabel asli. Untuk sampling, buka Markdown 9 November lalu cocokkan minimal 10 baris acak terhadap PDF asli. Nomor polisi, kode unit, grup KSA/BAL, status, dan keterangannya harus sama.

