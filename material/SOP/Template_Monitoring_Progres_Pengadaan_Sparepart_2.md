# Template Monitoring Progres Pengadaan Spare Part

## Informasi Workbook

| Field | Informasi |
|---|---|
| Nama file sumber | `Template_Monitoring_Progres_Pengadaan_Sparepart(2).xlsx` |
| Nama sheet | `A_Identitas_SPB` |
| Jumlah sheet | 1 |
| Rentang berisi data | `A1:S4` |
| Jumlah kolom | 19 |
| Kondisi data | Templat kosong |
| Baris contoh | Nomor 1 dan 2 |
| Formula | Tidak ditemukan |
| Grafik/gambar | Tidak ditemukan |

> Nama kolom dipertahankan sesuai sumber, termasuk `No JO mekanik/`, `jam poses le toko`, `Tgl  Barang Tiba`, dan `Unit terdampak Pengaruh ke RTW (ya / tidak)`.

# 1. Tabulasi Templat

| No | No SPB | Tanggal SPB | No JO mekanik/ | ID unit | Nama Spare Part | Spesifikasi / Part No | Qty | Satuan | Tgl / SPB Disetujui | jam poses le toko | Tgl Barang Tiba | Jam Barang Tiba | Total Waktu Aktual (jam) | Uraian Kendala | Aksi Perbaikan | Status | Unit terdampak Pengaruh ke RTW (ya / tidak) | Kesimpulan Akhir |
|---:|---|---|---|---|---|---|---:|---|---|---|---|---|---:|---|---|---|---|---|
| 1 |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| 2 |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |

# 2. Alur Informasi

```text
SPB dan Job Order
        ↓
Unit serta spare part
        ↓
Kuantitas dan satuan
        ↓
Persetujuan SPB
        ↓
Proses ke toko/vendor
        ↓
Barang tiba
        ↓
Lead time aktual
        ↓
Kendala dan aksi perbaikan
        ↓
Status dan pengaruh ke RTW
        ↓
Kesimpulan akhir
```

# 3. Penjelasan Terperinci Setiap Kolom

| Kolom | Penjelasan |
|---|---|
| No | Nomor urut tampilan. Sebaiknya bukan identitas permanen transaksi. |
| No SPB | Nomor dokumen SPB. Kepanjangan dan format SPB tidak dijelaskan di workbook. |
| Tanggal SPB | Tanggal pembuatan/pengajuan SPB dan titik awal umur permintaan. |
| No JO mekanik/ | Referensi Job Order mekanik yang membutuhkan spare part. |
| ID unit | Kode unit/aset yang terdampak. |
| Nama Spare Part | Nama komponen yang diminta. |
| Spesifikasi / Part No | Spesifikasi teknis dan/atau part number. |
| Qty | Jumlah yang diminta. |
| Satuan | Pcs, set, liter, unit, meter, dan sebagainya. |
| Tgl / SPB Disetujui | Tanggal permintaan memperoleh persetujuan. |
| jam poses le toko | Istilah sumber belum tegas; perlu dikonfirmasi apakah berarti jam mulai order, waktu proses, atau durasi perjalanan/proses ke toko. |
| Tgl Barang Tiba | Tanggal barang diterima. |
| Jam Barang Tiba | Jam barang diterima. |
| Total Waktu Aktual (jam) | Lead time aktual. Belum mempunyai formula. |
| Uraian Kendala | Penyebab hambatan, misalnya approval, quotation, stok, vendor, pengiriman, atau spesifikasi. |
| Aksi Perbaikan | Tindakan koreksi/eskalasi untuk menyelesaikan hambatan. |
| Status | Tahapan proses pengadaan. Daftar status belum ditentukan. |
| Unit terdampak Pengaruh ke RTW | Penanda apakah keterlambatan parts memengaruhi kesiapan unit. Kepanjangan RTW tidak dijelaskan pada workbook. |
| Kesimpulan Akhir | Ringkasan hasil akhir proses pengadaan dan dampaknya. |

# 4. Formula yang Dapat Diterapkan

Apabila `jam poses le toko` merupakan jam mulai proses/order, formula konseptual untuk lead time adalah:

```excel
=IF(OR(J3="",K3="",L3="",M3=""),"",((L3+M3)-(J3+K3))*24)
```

Formula tersebut belum boleh diterapkan sebelum arti kolom K dipastikan.

Formula tambahan yang relevan:

```text
Approval Lead Time = Tanggal SPB Disetujui - Tanggal SPB
Total Lead Time    = Barang Tiba - Tanggal SPB
Aging Open SPB     = Tanggal Hari Ini - Tanggal SPB
Fill Rate          = Qty Diterima / Qty Diminta × 100%
```

# 5. Informasi yang Sudah dan Belum Dicakup

## Sudah Dicakup

- identitas SPB dan JO;
- unit dan spare part;
- jumlah serta satuan;
- tanggal persetujuan;
- waktu barang tiba;
- kendala dan aksi;
- status;
- pengaruh terhadap RTW; dan
- kesimpulan akhir.

## Belum Dicakup

- site/lokasi;
- pemohon dan PIC procurement;
- approver;
- vendor;
- quotation, PR, dan PO;
- harga satuan dan total biaya;
- estimasi kedatangan;
- jumlah approved, ordered, received, dan outstanding;
- penerimaan parsial;
- target dan aktual RTW;
- jam downtime;
- bukti/lampiran; serta
- audit trail perubahan.

# 6. Keterbatasan Templat

1. Tidak terdapat formula otomatis.
2. Tidak terdapat dropdown atau validasi data.
3. Tidak terdapat status baku.
4. Tidak terdapat target kedatangan sehingga ketepatan waktu belum dapat dihitung.
5. Tidak terdapat filter, Excel Table, freeze pane, dashboard, atau conditional formatting.
6. Kolom `jam poses le toko` masih ambigu.
7. Penerimaan parsial belum dapat dicatat dengan baik.
8. Aksi perbaikan tidak mempunyai PIC dan due date.
9. Dampak RTW hanya Ya/Tidak tanpa durasi.
10. Kesimpulan akhir masih berupa teks bebas.

# 7. Rekomendasi Struktur Pengembangan

## Status Pengadaan

| Tahap | Status yang Disarankan |
|---|---|
| Persiapan | Draft / Menunggu Kelengkapan |
| Persetujuan | Diajukan / Menunggu Approval / Disetujui / Ditolak |
| Sourcing | Mencari Vendor / Menunggu Quotation |
| Order | PR Dibuat / PO Dibuat / Ordered |
| Pengiriman | Dalam Pengiriman / Backorder |
| Penerimaan | Tiba Sebagian / Tiba Lengkap / Tidak Sesuai |
| Penutupan | Diserahkan / Terpasang / Closed / Dibatalkan |

## Field Tambahan

| Kelompok | Field |
|---|---|
| Vendor & order | Vendor, quotation, PR, PO, order datetime |
| Target | Expected arrival, SLA, priority |
| Kuantitas | Requested, approved, ordered, received, outstanding |
| Biaya | Unit cost, total cost |
| Kendala | Issue category, PIC, due date, escalation |
| RTW | Target RTW, actual RTW, delay hours |
| Penutupan | Installation result, evidence, closed by, closed datetime |

# 8. Kesimpulan

Workbook merupakan templat dasar untuk memantau pengadaan spare part dari SPB dan JO sampai barang tiba, kendala, tindakan, status, dampak terhadap RTW, dan kesimpulan akhir.

Struktur 19 kolom telah menggambarkan alur utama, tetapi templat belum mempunyai formula, validasi, vendor, biaya, estimasi tiba, penerimaan parsial, dan hubungan otomatis dengan JO maupun inventory. Keputusan pertama yang perlu dilakukan adalah memperjelas arti `jam poses le toko`, karena definisi tersebut menentukan formula `Total Waktu Aktual`.
