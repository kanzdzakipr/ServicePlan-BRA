# Template Monitoring Progres Pengadaan Spare Part

## Informasi Workbook

| Field | Informasi |
|---|---|
| Nama file sumber | `Template_Monitoring_Progres_Pengadaan_Sparepart.xlsx` |
| Nama sheet | `A_Identitas_SPB` |
| Jumlah sheet | 1 |
| Area terformat | `A1:T18` |
| Kolom informasi yang digunakan | `A:S` atau 19 kolom |
| Kondisi data | Templat kosong |
| Nomor baris yang telah dicontohkan | 1 dan 2 |
| Formula | Tidak ditemukan |
| Data validation/dropdown | Tidak ditemukan |
| Conditional formatting | Tidak ditemukan |
| Filter/table Excel | Tidak ditemukan |
| Freeze pane | Tidak ditemukan |
| Grafik/gambar | Tidak ditemukan |
| Pengaturan cetak | Portrait |
| Tujuan yang tampak | Memonitor identitas SPB, proses pengadaan, waktu kedatangan spare part, kendala, tindakan, status, dan pengaruh terhadap RTW |

> **Catatan konversi:** nama kolom dipertahankan sesuai workbook, termasuk penulisan `No JO mekanik/`, `jam poses le toko`, `Tgl  Barang Tiba`, dan `Unit terdampak Pengaruh ke RTW (ya / tidak)`. Istilah yang tidak dijelaskan oleh sumber tidak otomatis diperbaiki atau diperluas.

> Workbook hanya berisi struktur templat. Tidak terdapat data pengadaan aktual yang dapat dianalisis secara statistik.

---

# 1. Templat Asli dalam Format Markdown

Tabel sumber mempunyai 19 kolom. Baris kedua kosong, sedangkan baris ketiga dan keempat telah diberi nomor 1 dan 2.

| No | No SPB | Tanggal SPB | No JO mekanik/ | ID unit | Nama Spare Part | Spesifikasi / Part No | Qty | Satuan | Tgl / SPB Disetujui | jam poses le toko | Tgl Barang Tiba | Jam Barang Tiba | Total Waktu Aktual (jam) | Uraian Kendala | Aksi Perbaikan | Status | Unit terdampak Pengaruh ke RTW (ya / tidak) | Kesimpulan Akhir |
|---:|---|---|---|---|---|---|---:|---|---|---|---|---|---:|---|---|---|---|---|
| 1 |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| 2 |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |

## Kapasitas Area Templat

Workbook memiliki format baris sampai baris 18:

- baris 1: header;
- baris 2: baris kosong/pemisah;
- baris 3–18: area yang dapat dipakai untuk data;
- nomor 1 dan 2 hanya telah dimasukkan pada baris 3 dan 4.

Secara visual, area tersebut dapat menampung sekitar **16 baris data** tanpa memperluas format. Kolom `T` berada di dalam dimensi workbook, tetapi tidak mempunyai header atau isi.

---

# 2. Tujuan dan Alur Informasi

Struktur templat menunjukkan alur monitoring berikut:

```text
Identitas SPB dan JO
        ↓
Identitas unit dan spare part
        ↓
Jumlah dan satuan
        ↓
Tanggal persetujuan SPB
        ↓
Proses ke toko/vendor
        ↓
Barang tiba
        ↓
Perhitungan waktu aktual
        ↓
Kendala dan tindakan perbaikan
        ↓
Status pengadaan
        ↓
Pengaruh terhadap RTW unit
        ↓
Kesimpulan akhir
```

Templat tidak hanya mencatat barang yang diminta, tetapi juga dimaksudkan untuk menghubungkan proses pengadaan dengan kesiapan unit serta target penyelesaian perbaikan.

---

# 3. Pengelompokan Kolom

## 3.1 Identitas Dokumen dan Pekerjaan

| Kolom | Fungsi |
|---|---|
| No | Nomor urut baris monitoring. |
| No SPB | Nomor dokumen SPB. Kepanjangan SPB tidak dicantumkan pada workbook. |
| Tanggal SPB | Tanggal penerbitan atau pengajuan SPB. |
| No JO mekanik/ | Referensi Job Order mekanik. Tanda garis miring pada akhir judul dipertahankan sesuai sumber. |

## 3.2 Identitas Unit dan Spare Part

| Kolom | Fungsi |
|---|---|
| ID unit | Kode aset/unit yang membutuhkan spare part. |
| Nama Spare Part | Nama komponen atau material yang diminta. |
| Spesifikasi / Part No | Spesifikasi teknis dan/atau part number. |
| Qty | Jumlah kebutuhan. |
| Satuan | Satuan kuantitas, misalnya pcs, set, liter, unit, atau meter. |

## 3.3 Waktu Persetujuan dan Pengadaan

| Kolom | Fungsi |
|---|---|
| Tgl / SPB Disetujui | Tanggal persetujuan SPB. |
| jam poses le toko | Waktu atau durasi proses ke toko/vendor. Arti tepatnya tidak dijelaskan sumber. |
| Tgl Barang Tiba | Tanggal spare part diterima. |
| Jam Barang Tiba | Jam spare part diterima. |
| Total Waktu Aktual (jam) | Total lead time aktual dalam jam. |

## 3.4 Pengendalian Kendala dan Dampak

| Kolom | Fungsi |
|---|---|
| Uraian Kendala | Penyebab keterlambatan atau masalah pengadaan. |
| Aksi Perbaikan | Tindakan korektif/eskalasi untuk menyelesaikan kendala. |
| Status | Posisi proses pengadaan pada saat monitoring. |
| Unit terdampak Pengaruh ke RTW (ya / tidak) | Penanda apakah pengadaan memengaruhi kesiapan atau target RTW unit. |
| Kesimpulan Akhir | Ringkasan hasil akhir pengadaan dan dampaknya. |

---

# 4. Penjelasan Terperinci Setiap Kolom

## 4.1 No

Nomor urut sebaiknya digunakan untuk tampilan, bukan sebagai identitas permanen. Ketika data diurutkan atau dihapus, nomor urut dapat berubah.

Untuk sistem digital, identitas yang lebih aman adalah `procurement_monitoring_id` atau UUID.

## 4.2 No SPB

Kolom ini digunakan untuk mengelompokkan permintaan berdasarkan dokumen SPB.

Sumber tidak menjelaskan:

- kepanjangan SPB;
- format nomor;
- apakah satu SPB dapat memiliki beberapa spare part;
- apakah nomor SPB unik per site atau per tahun; dan
- status revisi SPB.

Apabila satu SPB memuat beberapa parts, nomor SPB akan muncul pada beberapa baris.

## 4.3 Tanggal SPB

Tanggal ini dapat digunakan untuk menghitung:

- umur permintaan;
- waktu dari pengajuan sampai persetujuan;
- total lead time pengadaan; dan
- backlog berdasarkan umur.

Workbook belum menentukan format tanggal atau timezone.

## 4.4 No JO Mekanik

Kolom ini menghubungkan pengadaan dengan pekerjaan maintenance.

Hubungan tersebut penting untuk menjawab:

- spare part diminta untuk kerusakan apa;
- unit mana yang sedang diperbaiki;
- apakah pekerjaan menunggu parts;
- kapan target RTW;
- siapa PIC mekanik; dan
- apakah pekerjaan telah ditutup.

Workbook tidak mempunyai kolom deskripsi JO, tanggal JO, atau status JO.

## 4.5 ID Unit

ID unit sebaiknya mengambil nilai dari master aset agar:

- penulisan konsisten;
- unit tidak tertukar;
- status unit dapat diperiksa;
- lokasi dan model dapat ditarik otomatis; dan
- histori pengadaan dapat dianalisis per unit.

Tidak terdapat dropdown atau validasi unit pada workbook.

## 4.6 Nama Spare Part

Nama spare part masih berupa teks bebas. Tanpa master part, risiko yang muncul adalah:

- variasi ejaan;
- nama yang terlalu umum;
- satu part dicatat dengan beberapa nama;
- kesulitan mengelompokkan transaksi; dan
- sulit mencocokkan dengan stok.

## 4.7 Spesifikasi / Part No

Kolom ini menggabungkan dua konsep:

1. spesifikasi teknis; dan
2. part number.

Untuk pengolahan data yang lebih baik, keduanya sebaiknya dipisahkan:

| Field | Contoh |
|---|---|
| Part Number | 23304-JAF40 |
| Spesifikasi | Fuel filter element |
| Brand/OEM | Hino/Komatsu/dan lain-lain |
| Alternatif Part | Nomor pengganti atau equivalent |

## 4.8 Qty

Kuantitas yang diminta. Workbook tidak memberikan pembatasan angka positif dan belum membedakan:

- quantity requested;
- quantity approved;
- quantity ordered;
- quantity received; dan
- quantity outstanding.

Akibatnya, pengadaan sebagian belum dapat dipantau secara terstruktur.

## 4.9 Satuan

Satuan sebaiknya menggunakan daftar baku, misalnya:

- pcs;
- set;
- unit;
- liter;
- kg;
- meter;
- box; atau
- drum.

Workbook tidak menyediakan dropdown satuan.

## 4.10 Tgl / SPB Disetujui

Tanggal ini menjadi titik awal administratif setelah permintaan memperoleh persetujuan.

Untuk mengukur kinerja proses, perlu dibedakan:

- tanggal SPB dibuat;
- tanggal SPB diajukan;
- tanggal disetujui;
- tanggal diterima procurement/logistik;
- tanggal order ke vendor; dan
- tanggal vendor mengonfirmasi.

Templat hanya menyediakan tanggal dibuat dan tanggal disetujui.

## 4.11 `jam poses le toko`

Penulisan sumber dipertahankan. Arti kolom ini belum tegas.

Kemungkinan yang perlu dikonfirmasi:

1. jam ketika dokumen diproses atau dikirim ke toko/vendor;
2. durasi proses menuju toko;
3. jam order dilakukan; atau
4. jam petugas berangkat ke toko.

Ketidakjelasan ini memengaruhi rumus `Total Waktu Aktual`.

## 4.12 Tgl Barang Tiba dan Jam Barang Tiba

Kedua kolom digunakan untuk mencatat waktu penerimaan parts.

Workbook belum membedakan:

- tiba sebagian;
- tiba lengkap;
- tiba di site;
- tiba di warehouse;
- diterima mekanik; atau
- dipasang pada unit.

Untuk pengadaan parsial, perlu menyimpan setiap penerimaan sebagai transaksi tersendiri.

## 4.13 Total Waktu Aktual (jam)

Kolom ini belum mempunyai formula.

Apabila kolom `jam poses le toko` dimaksudkan sebagai **jam mulai proses/order**, formula konseptualnya adalah:

```text
Total Waktu Aktual =
(Tanggal Barang Tiba + Jam Barang Tiba)
-
(Tanggal SPB Disetujui + Jam Proses ke Toko)
```

Dalam Excel, hasil hari perlu dikalikan 24:

```excel
=((L3+M3)-(J3+K3))*24
```

Rumus tersebut hanya valid bila:

- J dan L merupakan tanggal Excel;
- K dan M merupakan jam Excel;
- K benar-benar merupakan waktu mulai;
- proses tidak menggunakan kalender kerja khusus; dan
- tidak ada kebutuhan menghentikan perhitungan saat menunggu approval lain.

Jika K adalah **durasi**, bukan jam mulai, formula harus berbeda. Oleh karena itu, definisi kolom K harus disepakati terlebih dahulu.

## 4.14 Uraian Kendala

Kolom ini menyimpan alasan keterlambatan. Contoh kategori yang mungkin diperlukan:

- menunggu approval;
- spesifikasi belum jelas;
- part number salah;
- stok lokal kosong;
- menunggu quotation;
- harga belum disetujui;
- PR/PO belum terbit;
- vendor tidak tersedia;
- backorder;
- pengiriman terlambat;
- salah kirim;
- barang tidak sesuai;
- kendala pembayaran;
- kendala transportasi; atau
- kondisi force majeure.

Kategori terstruktur dapat digunakan untuk analisis Pareto kendala.

## 4.15 Aksi Perbaikan

Berisi tindakan untuk mempercepat atau menyelesaikan kendala, seperti:

- klarifikasi part number;
- mencari vendor alternatif;
- melakukan eskalasi approval;
- mengambil dari unit/site lain;
- repair existing part;
- menggunakan equivalent part yang disetujui;
- percepatan pengiriman;
- follow-up vendor; atau
- perubahan target RTW.

Aksi sebaiknya dilengkapi PIC dan due date.

## 4.16 Status

Workbook tidak menentukan daftar status.

Status yang disarankan untuk proses pengadaan:

| Tahap | Contoh Status |
|---|---|
| Persiapan | Draft / Menunggu Kelengkapan |
| Persetujuan | Diajukan / Menunggu Approval / Disetujui / Ditolak |
| Sourcing | Mencari Vendor / Menunggu Quotation |
| Order | PR Dibuat / PO Dibuat / Ordered |
| Pengiriman | Dalam Pengiriman / Backorder |
| Penerimaan | Tiba Sebagian / Tiba Lengkap / Tidak Sesuai |
| Penutupan | Diserahkan ke Mekanik / Terpasang / Closed / Dibatalkan |

Daftar final harus mengikuti proses procurement BRA.

## 4.17 Pengaruh ke RTW

Sumber tidak menjelaskan kepanjangan RTW. Dalam konteks operasional equipment, RTW sering digunakan untuk kesiapan unit kembali bekerja, tetapi definisi formal harus mengikuti terminologi perusahaan.

Nilai `Ya/Tidak` belum cukup untuk analisis. Sebaiknya tambahkan:

- target RTW;
- estimasi keterlambatan;
- aktual RTW;
- jumlah jam/hari terdampak;
- unit critical/non-critical; dan
- nilai kerugian downtime.

## 4.18 Kesimpulan Akhir

Kolom ini seharusnya merangkum hasil akhir, misalnya:

- parts tiba lengkap dan tepat waktu;
- parts terlambat karena vendor;
- parts diterima sebagian;
- pengadaan dibatalkan;
- menggunakan parts alternatif;
- unit RTW setelah parts dipasang;
- pengadaan tidak memengaruhi RTW; atau
- diperlukan tindakan lanjutan.

Tanpa standar isi, kesimpulan akan menjadi teks bebas yang sulit direkap.

---

# 5. Format Visual Workbook

## 5.1 Header

- Header berada pada baris 1.
- Tinggi baris header sekitar 65,4 point.
- Teks dipusatkan secara horizontal dan vertikal.
- Sebagian besar header panjang menggunakan wrap text.
- Sel menggunakan garis batas tipis.
- Tidak terdapat warna header khusus; tampilan dominan putih.

## 5.2 Kolom

Kolom-kolom identitas lebih sempit, sedangkan kolom waktu, kendala, aksi, status, dampak RTW, dan kesimpulan lebih lebar.

Struktur yang lebar membantu input teks, tetapi membuat sheet membutuhkan banyak scrolling horizontal.

## 5.3 Area Data

- Baris data menggunakan border.
- Nomor 1 dan 2 telah disediakan.
- Tidak terdapat banded row, table style, atau filter.
- Tidak terdapat freeze pane, sehingga header akan hilang ketika pengguna menggulir ke bawah.
- Tidak terdapat formula otomatis.

## 5.4 Pengaturan Cetak

Workbook menggunakan orientasi **portrait**. Dengan 19 kolom, hasil cetak berpotensi:

- mengecil;
- terpotong ke beberapa halaman; atau
- sulit dibaca.

Untuk pencetakan, orientasi landscape dan fit-to-width kemungkinan lebih sesuai, tetapi perubahan tersebut belum diterapkan pada file sumber.

---

# 6. Informasi yang Dapat dan Tidak Dapat Dipantau

## 6.1 Sudah Dicakup

Templat telah menyediakan tempat untuk:

- nomor dan tanggal SPB;
- referensi JO;
- identitas unit;
- spare part dan part number;
- kuantitas;
- tanggal persetujuan;
- proses ke toko/vendor;
- waktu kedatangan;
- lead time aktual;
- kendala;
- aksi koreksi;
- status;
- dampak terhadap RTW; dan
- kesimpulan.

## 6.2 Belum Dicakup

Tidak terdapat field khusus untuk:

- lokasi/site;
- pemohon;
- PIC procurement/logistik;
- approver;
- tanggal pengajuan;
- vendor;
- quotation;
- PR;
- PO;
- harga satuan;
- nilai total;
- estimasi tanggal tiba;
- lead time target;
- quantity approved;
- quantity ordered;
- quantity received;
- quantity outstanding;
- penerimaan parsial;
- nomor surat jalan;
- kondisi barang;
- penerima barang;
- tanggal penyerahan ke mekanik;
- tanggal pemasangan;
- target RTW;
- aktual RTW;
- jam downtime;
- bukti/lampiran; dan
- histori perubahan.

Ketiadaan field tersebut tidak berarti prosesnya tidak dilakukan; hanya belum ditampung secara terstruktur oleh templat.

---

# 7. Indikator yang Dapat Dibangun

> Bagian ini merupakan rekomendasi pengembangan, bukan isi asli workbook.

## 7.1 Lead Time

```text
Approval Lead Time =
Tanggal disetujui - Tanggal SPB
```

```text
Procurement Lead Time =
Barang tiba - Waktu mulai order/proses
```

```text
Total Lead Time =
Barang tiba - Tanggal SPB
```

## 7.2 On-Time Delivery

Diperlukan field `Estimasi Tiba` atau `Target Tiba`.

```text
On-Time Delivery (%) =
Jumlah item tiba ≤ target
÷
Total item yang tiba
× 100%
```

## 7.3 Backlog dan Aging

```text
Aging SPB =
Tanggal hari ini - Tanggal SPB
```

Kelompok umur dapat dibuat:

- 0–1 hari;
- 2–3 hari;
- 4–7 hari;
- 8–14 hari;
- lebih dari 14 hari.

## 7.4 Fill Rate

```text
Fill Rate (%) =
Qty diterima
÷
Qty diminta
× 100%
```

## 7.5 Pengaruh terhadap RTW

```text
RTW Delay akibat Parts =
Aktual RTW - Target RTW
```

Analisis dapat menunjukkan:

- jumlah unit terdampak;
- total jam/hari downtime karena parts;
- nilai kerugian;
- parts paling sering menyebabkan delay; dan
- vendor dengan keterlambatan tertinggi.

---

# 8. Temuan Penting dan Keterbatasan Templat

1. Workbook hanya mempunyai satu sheet dan belum menyediakan dashboard atau rekap.
2. Tidak terdapat formula untuk `Total Waktu Aktual`.
3. Makna kolom `jam poses le toko` belum jelas.
4. Tidak terdapat estimasi/target kedatangan, sehingga ketepatan waktu tidak dapat dihitung.
5. Tidak terdapat vendor, PR, PO, harga, atau biaya.
6. Tidak terdapat quantity received dan outstanding.
7. Status menggunakan teks bebas tanpa dropdown.
8. Kolom dampak RTW tidak mempunyai validasi Ya/Tidak.
9. Tidak terdapat conditional formatting untuk keterlambatan atau status critical.
10. Tidak terdapat filter atau Excel Table.
11. Tidak terdapat freeze pane.
12. Tidak terdapat identitas PIC dan due date untuk aksi perbaikan.
13. Kesimpulan akhir belum mempunyai format atau kategori.
14. Orientasi cetak portrait kurang ideal untuk tabel 19 kolom.
15. Hanya nomor 1 dan 2 yang telah disediakan, meskipun grid tersedia sampai baris 18.
16. Tidak terdapat timestamp atau audit trail perubahan.
17. Tidak terdapat hubungan otomatis dengan master unit, JO, atau inventory.

---

# 9. Rekomendasi Struktur Digital

## 9.1 Header Permintaan

| Field Sistem | Fungsi |
|---|---|
| request_id | Identitas unik |
| spb_number | Nomor SPB |
| spb_date | Tanggal SPB |
| jo_number | Referensi JO |
| unit_id | Referensi master unit |
| site_id | Lokasi/site |
| requestor | Pemohon |
| priority | Normal/Urgent/Critical |
| target_rtw | Target unit siap |

## 9.2 Detail Spare Part

| Field Sistem | Fungsi |
|---|---|
| request_line_id | Identitas baris |
| part_id | Referensi master part |
| part_number | Part number |
| description | Nama/spesifikasi |
| qty_requested | Jumlah diminta |
| qty_approved | Jumlah disetujui |
| qty_ordered | Jumlah dipesan |
| qty_received | Jumlah diterima |
| uom | Satuan |
| unit_cost | Harga satuan |
| total_cost | Total nilai |

## 9.3 Proses Pengadaan

| Field Sistem | Fungsi |
|---|---|
| approved_datetime | Waktu persetujuan |
| procurement_start | Waktu proses dimulai |
| vendor_id | Vendor |
| quotation_number | Nomor quotation |
| pr_number | Nomor PR |
| po_number | Nomor PO |
| order_datetime | Waktu order |
| expected_arrival | Estimasi tiba |
| actual_arrival | Aktual tiba |
| delivery_note | Surat jalan |
| procurement_status | Status |

## 9.4 Kendala dan Tindakan

| Field Sistem | Fungsi |
|---|---|
| issue_category | Kategori kendala |
| issue_description | Uraian |
| action | Tindakan |
| action_owner | PIC |
| due_date | Target |
| action_status | Open/Closed |
| escalation_level | Level eskalasi |

## 9.5 RTW dan Penutupan

| Field Sistem | Fungsi |
|---|---|
| rtw_impacted | Ya/tidak |
| delay_hours | Durasi dampak |
| actual_rtw | Aktual RTW |
| installed_datetime | Waktu pemasangan |
| installation_result | Hasil |
| conclusion | Kesimpulan |
| closed_by | Penutup kasus |
| closed_datetime | Waktu penutupan |

---

# 10. Rekomendasi Validasi dan Otomasi Excel

## 10.1 Formula Total Waktu

Setelah arti kolom K dikonfirmasi, gunakan formula otomatis pada kolom N.

Contoh bila K adalah jam mulai proses:

```excel
=IF(OR(J3="",K3="",L3="",M3=""),"",((L3+M3)-(J3+K3))*24)
```

## 10.2 Dropdown

Tambahkan data validation untuk:

- Satuan;
- Status;
- Ya/Tidak RTW;
- Kategori kendala;
- PIC;
- Prioritas; dan
- Kesimpulan.

## 10.3 Conditional Formatting

Contoh:

- merah: overdue atau RTW terdampak;
- kuning: mendekati target;
- hijau: barang tiba/closed;
- abu-abu: dibatalkan;
- data bar: lead time aktual.

## 10.4 Fitur Navigasi

- Freeze baris header.
- Aktifkan filter.
- Jadikan range sebagai Excel Table.
- Tambahkan sheet `Parameter`.
- Tambahkan sheet `Dashboard`.
- Gunakan nomor otomatis.
- Lindungi kolom formula.

---

# 11. Rekomendasi Dashboard

Dashboard dapat menampilkan:

| KPI | Fungsi |
|---|---|
| SPB Open | Jumlah permintaan belum selesai |
| SPB Overdue | Jumlah melewati target |
| Rata-rata Lead Time | Kecepatan pengadaan |
| On-Time Delivery | Ketepatan kedatangan |
| Unit Terdampak RTW | Jumlah unit tertahan parts |
| Total Delay RTW | Jam/hari keterlambatan |
| Top Kendala | Penyebab terbanyak |
| Top Part Delay | Part yang paling sering terlambat |
| Top Vendor Delay | Vendor dengan keterlambatan |
| Nilai Pengadaan | Total biaya periode |

Filter yang berguna:

- periode;
- site;
- unit;
- vendor;
- status;
- priority;
- part category; dan
- dampak RTW.

---

# 12. Alur Penggunaan yang Disarankan

1. Buat atau terima SPB.
2. Masukkan referensi JO dan unit.
3. Pilih part dari master.
4. Masukkan qty dan satuan.
5. Catat waktu approval.
6. Catat waktu procurement/order dimulai.
7. Tetapkan vendor dan estimasi tiba.
8. Update status secara berkala.
9. Catat kendala dan aksi beserta PIC.
10. Catat penerimaan sebagian atau lengkap.
11. Hitung lead time secara otomatis.
12. Tentukan pengaruh ke RTW.
13. Hubungkan dengan pemasangan dan aktual RTW.
14. Isi kesimpulan akhir.
15. Tutup transaksi dengan bukti.

---

# 13. Kesimpulan

Workbook merupakan templat dasar untuk memantau progres pengadaan spare part dari identitas SPB dan JO sampai barang tiba, kendala, tindakan, status, dampak RTW, dan kesimpulan akhir.

Struktur 19 kolom sudah mencakup alur inti pengadaan, tetapi belum mempunyai formula, validasi, filter, dashboard, target kedatangan, vendor, biaya, penerimaan parsial, PIC tindakan, atau histori perubahan.

Hal paling penting sebelum templat digunakan adalah memperjelas makna kolom `jam poses le toko` dan mendefinisikan titik awal perhitungan `Total Waktu Aktual`. Setelah itu, templat dapat dikembangkan menjadi tracker yang lebih objektif dengan formula lead time, status terstandar, indikator overdue, dan hubungan langsung ke JO, inventory, serta target RTW.
