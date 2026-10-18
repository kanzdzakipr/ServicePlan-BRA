# Panduan Pengujian Integrasi Batch 1–13

Dokumen ini adalah panduan eksekusi manual untuk menguji integrasi laporan ServicePlan-BRA pada Laragon. Pengujian dilakukan berurutan karena beberapa batch memakai data yang dibuat batch sebelumnya.

## 1. Lingkungan pengujian

| Komponen | Nilai |
|---|---|
| Aplikasi | `http://localhost/ServicePlan-BRA/` |
| Dashboard | `http://localhost/ServicePlan-BRA/dashboard.php` |
| Database | `u646470441_ServicePlanBRA` |
| Pengguna utama | Administrator |
| Browser | Chrome/Edge terbaru |
| Database viewer | HeidiSQL Laragon |

Sebelum mulai:

1. [ ] Jalankan Apache dan MySQL pada Laragon.
2. [ ] Buka aplikasi dan login sebagai Administrator.
3. [ ] Tekan `Ctrl+F5` agar JavaScript terbaru digunakan.
4. [ ] Pastikan menu **Laporan & Form**, **Work Order**, **Preventive Maintenance**, **Inspeksi & P2H**, **Fuel**, **Produktivitas**, dan **Spare Part & Logistik** dapat dibuka.
5. [ ] Gunakan nomor laporan unik dengan awalan `UJI-Bxx`, misalnya `UJI-B08-SPB-001`.
6. [ ] Simpan screenshot kondisi sebelum dan sesudah finalisasi.
7. [ ] Jangan menghapus data langsung menggunakan SQL. Gunakan fungsi **Void** agar mekanisme pembalikan ikut diuji.

## 2. Urutan dan dependensi

```text
Batch 1–2  : Stok masuk/keluar
Batch 3    : P2H → inspeksi dan kondisi unit
Batch 4–5  : LHO → produktivitas, HM, dan Fuel
Batch 6    : Maintenance Board → Preventive Maintenance
Batch 7    : Repair & Overhaul → Work Order
Batch 8    : Work Order → SPB
Batch 9    : SPB → PPB
Batch 10   : Monitoring PPB/SPB + snapshot stok mingguan
Batch 11   : PPB → BAPP → penerimaan stok
Batch 12   : Mutasi barang intern
Batch 13   : Work Order → permintaan parts urgent/SPPU
```

Batch 8 membutuhkan Work Order aktif. Batch 9 membutuhkan SPB aktif. Batch 10–11 membutuhkan SPB/PPB yang belum ditutup. Batch 13 membutuhkan Work Order aktif.

## 3. Data awal yang harus dicatat

### 3.1 Master Part

```sql
SELECT part_id, part_number, part_name, unit_measure, stock_qty, unit_cost
FROM u646470441_ServicePlanBRA.parts
ORDER BY part_id;
```

Pilih satu part dan catat:

| Data | Nilai pengujian |
|---|---|
| Part number |  |
| Nama part |  |
| Satuan |  |
| Stok awal |  |
| Harga |  |

### 3.2 Master Asset dan Work Order

```sql
SELECT
    w.wo_id,
    w.status AS wo_status,
    w.priority,
    a.asset_id,
    a.asset_code,
    a.category,
    a.make_model,
    a.serial_number,
    a.last_hm_km,
    a.status AS asset_status
FROM u646470441_ServicePlanBRA.work_orders w
JOIN u646470441_ServicePlanBRA.assets a ON a.asset_id = w.asset_id
WHERE a.is_active = 1
  AND w.status NOT IN ('Closed', 'Cancelled')
ORDER BY w.reported_at DESC;
```

Catat satu Work Order dan unit aktif untuk Batch 7–13.

## 4. Pola pengujian wajib setiap batch

Untuk setiap laporan, lakukan empat pemeriksaan berikut:

1. **Draft** — isi form dan tunggu autosave. Pastikan tabel operasional belum berubah.
2. **Finalisasi** — tekan **Simpan Laporan**. Pastikan perubahan hanya terjadi satu kali.
3. **Retry** — muat ulang halaman atau buka laporan kembali. Pastikan tidak ada duplikasi.
4. **Void** — void dari Riwayat Laporan. Pastikan perubahan dipulihkan secara aman.

Jika objek sudah diproses menu lain, void boleh mempertahankan objek tersebut. Kondisi ini harus dicatat sebagai `PASS` apabila sesuai expected result batch.

## 5. Batch 1 — BHW-IN ke Barang Masuk

Tujuan: membuktikan penerimaan warehouse menambah stok.

1. [ ] Catat stok awal part sebagai `STOK_AWAL`.
2. [ ] Buka **Laporan & Form → Buku Harian Warehouse (Barang Masuk)**.
3. [ ] Isi nomor log `UJI-B01-IN-001`, project, dan tanggal.
4. [ ] Pilih part dari Master Part.
5. [ ] Pastikan nama, part number, satuan, dan saldo lalu terisi sesuai database.
6. [ ] Isi jumlah penerimaan, misalnya `5`.
7. [ ] Pastikan saldo sekarang otomatis menjadi `STOK_AWAL + 5`.
8. [ ] Tunggu autosave; stok database harus tetap `STOK_AWAL`.
9. [ ] Finalkan laporan.
10. [ ] Pastikan stok menjadi `STOK_AWAL + 5`.
11. [ ] Periksa tab **Spare Part & Logistik → Barang Masuk**.
12. [ ] Muat ulang; stok tidak boleh bertambah lagi.
13. [ ] Void laporan; stok harus kembali ke `STOK_AWAL`.

```sql
SELECT p.part_number, p.stock_qty, it.*
FROM u646470441_ServicePlanBRA.inventory_transactions it
JOIN u646470441_ServicePlanBRA.parts p ON p.part_id = it.part_id
WHERE it.reference_number = 'UJI-B01-IN-001'
ORDER BY it.transaction_id DESC;
```

## 6. Batch 2 — BHW-OUT ke Barang Keluar

Tujuan: membuktikan pengeluaran warehouse mengurangi stok tanpa membuat saldo negatif.

1. [ ] Pastikan part mempunyai stok yang cukup dan catat sebagai `STOK_AWAL`.
2. [ ] Buka **Buku Harian Warehouse (Barang Keluar)**.
3. [ ] Isi nomor log `UJI-B02-OUT-001`.
4. [ ] Pilih part dan isi jumlah diberikan, misalnya `2`.
5. [ ] Pastikan persediaan sama dengan stok database dan sisa dihitung otomatis.
6. [ ] Pastikan draft belum mengurangi stok.
7. [ ] Finalkan; stok harus menjadi `STOK_AWAL - 2`.
8. [ ] Periksa tab **Barang Keluar**.
9. [ ] Coba laporan lain dengan jumlah lebih besar dari stok; finalisasi harus ditolak.
10. [ ] Void laporan pertama; stok kembali ke `STOK_AWAL`.

## 7. Batch 3 — P2H ke Inspeksi dan Master Asset

Tujuan: memastikan hasil P2H membuat riwayat inspeksi dan memperbarui kondisi unit.

1. [ ] Pilih template P2H yang sesuai kategori unit: Excavator atau Single Drum Roller.
2. [ ] Pilih unit dari Master Asset.
3. [ ] Pastikan kategori, model, lokasi, dan HM awal terisi otomatis.
4. [ ] Isi HM selesai tidak lebih kecil dari HM awal.
5. [ ] Tandai minimal satu item sebagai `OK — Sudah diperbaiki` untuk skenario `WARNING`.
6. [ ] Pastikan draft belum membuat record `inspections`.
7. [ ] Finalkan laporan.
8. [ ] Periksa **Inspeksi & P2H → Riwayat & Tabulasi P2H**.
9. [ ] Pastikan status unit menjadi `INSPEKSI` dan HM terakhir diperbarui.
10. [ ] Uji laporan lain dengan item `X — Tidak normal`; status unit harus menjadi `BREAKDOWN`.
11. [ ] Void laporan uji; status dan HM dipulihkan jika belum ada perubahan lanjutan.

```sql
SELECT i.*, a.status, a.last_hm_km
FROM u646470441_ServicePlanBRA.inspections i
JOIN u646470441_ServicePlanBRA.assets a ON a.asset_id = i.asset_id
ORDER BY i.inspection_id DESC
LIMIT 10;
```

## 8. Batch 4 — LHO ke Produktivitas dan HM Asset

Tujuan: memastikan LHO menjadi ledger operasi dan memperbarui HM.

1. [ ] Catat HM unit sebagai `HM_MASTER`.
2. [ ] Buka **Laporan Harian Operasi Alat**.
3. [ ] Pilih unit, operator, dan site dari database.
4. [ ] Isi HM awal baris pertama sama dengan `HM_MASTER`.
5. [ ] Isi jam awal, jam akhir, HM akhir, dan status `Terverifikasi`.
6. [ ] Jika ada beberapa baris, HM awal baris berikutnya harus sama dengan HM akhir sebelumnya.
7. [ ] Pastikan jam kerja dan HM operasi dihitung otomatis.
8. [ ] Finalkan laporan.
9. [ ] Periksa **Produktivitas → LHO Operasional**.
10. [ ] Pastikan `assets.last_hm_km` menjadi HM akhir baris terakhir.
11. [ ] Retry tidak boleh membuat ledger ganda.
12. [ ] Void harus menonaktifkan ledger dan memulihkan HM bila aman.

## 9. Batch 5 — LHO ke Fuel Management

Tujuan: memastikan konsumsi BBM pada LHO masuk ke Fuel.

1. [ ] Gunakan LHO baru dengan langkah Batch 4.
2. [ ] Isi BBM lebih besar dari `0`, misalnya `20` liter.
3. [ ] Pastikan HM operasi lebih besar dari `0`.
4. [ ] Finalkan laporan.
5. [ ] Buka menu **Fuel**.
6. [ ] Pastikan transaksi menampilkan nomor laporan, unit, operator, site, liter, dan konsumsi `BBM ÷ HM operasi`.
7. [ ] Baris LHO dengan BBM `0` tidak boleh membuat transaksi Fuel.
8. [ ] Retry tidak boleh menggandakan transaksi.
9. [ ] Void harus menonaktifkan transaksi LHO tanpa menghapus transaksi Fuel manual.

## 10. Batch 6 — Maintenance Board ke Preventive Maintenance

Tujuan: memastikan rencana maintenance dari laporan tampil pada menu PM.

1. [ ] Buka **Maintenance Board A2B**.
2. [ ] Pilih kode unit dari Master Asset.
3. [ ] Pastikan jenis unit, HM awal, dan tanggal HM terisi otomatis.
4. [ ] Pilih interval, misalnya `500 HM`.
5. [ ] Pastikan target HM adalah `HM awal + interval`.
6. [ ] Pastikan draft belum membuat `pm_plans`.
7. [ ] Finalkan laporan.
8. [ ] Buka **Preventive Maintenance** dan cari unit tersebut.
9. [ ] Pastikan status rencana sesuai target: `PLANNED`, `DUE_SOON`, `OVERDUE`, atau `COMPLETED`.
10. [ ] Retry tidak boleh membuat rencana ganda.
11. [ ] Void menghapus rencana milik laporan yang belum diubah.
12. [ ] Rencana yang sudah diubah planner harus tetap dipertahankan saat void.

## 11. Batch 7 — Repair & Overhaul ke Work Order

Tujuan: memastikan laporan perbaikan membuat Work Order.

1. [ ] Buka **Surat Permohonan Perbaikan (Repair & Overhaul)**.
2. [ ] Pilih kode unit; pastikan asset, serial number, dan HM terisi.
3. [ ] Pilih part dari Master Part.
4. [ ] Isi temuan, urgensi, estimasi biaya, solusi, PIC, dan target.
5. [ ] Pastikan draft belum membuat Work Order.
6. [ ] Finalkan laporan.
7. [ ] Buka menu **Work Order** dan cari nomor laporan.
8. [ ] Pastikan prioritas sesuai: `Normal`, `High`, atau `Emergency`.
9. [ ] Retry tidak boleh membuat Work Order ganda.
10. [ ] Void menghapus Work Order yang belum diproses.
11. [ ] Work Order yang sudah memiliki aktivitas lanjutan harus dipertahankan.

## 12. Batch 8 — SPB ke Spare Part & Logistik

Tujuan: membuat Purchase Request dari Work Order.

1. [ ] Buka **SPB (Surat Permintaan Barang)**.
2. [ ] Isi nomor `UJI-B08-SPB-001`.
3. [ ] Pilih Work Order aktif.
4. [ ] Pastikan unit, project/lokasi, dan urgensi terisi otomatis.
5. [ ] Pilih part dari Master Part dan isi jumlah.
6. [ ] Unggah bukti gambar dan isi keterangannya.
7. [ ] Pastikan draft belum membuat `purchase_requests`.
8. [ ] Finalkan laporan.
9. [ ] Buka **Spare Part & Logistik** dan cari unit/SPB tersebut.
10. [ ] Pastikan satu header dan item sesuai jumlah baris terbentuk.
11. [ ] Work Order dan kode unit yang tidak cocok harus ditolak.
12. [ ] Retry tidak boleh membuat item ganda.
13. [ ] Void menghapus SPB yang belum diproses, tetapi mempertahankan SPB yang sudah disetujui/diproses.

## 13. Batch 9 — PPB ke Purchase Order

Tujuan: membuat Purchase Order dari SPB aktif.

1. [ ] Buka **PPB**.
2. [ ] Isi nomor `UJI-B09-PPB-001`.
3. [ ] Pilih SPB hasil Batch 8.
4. [ ] Pastikan project dan tempat penyerahan mengikuti lokasi SPB.
5. [ ] Isi vendor, penawaran, dan batas penyerahan.
6. [ ] Pilih item dari Master Part dan isi jumlah serta harga.
7. [ ] Pastikan subtotal, PPN 11%, dan total dihitung otomatis.
8. [ ] Finalkan laporan dan periksa status pengadaan pada menu Logistik.
9. [ ] Coba ubah jumlah harga agar tidak sesuai; finalisasi harus ditolak.
10. [ ] Retry tidak boleh membuat PPB/item ganda.
11. [ ] Void menghapus PPB yang belum diproses dan mempertahankan PPB berstatus `Approved`/`Ordered`.

## 14. Batch 10 — Procurement Monitoring dan Parts Weekly

### 14.1 Procurement Monitoring

1. [ ] Buka **Monitoring Progres Pengadaan Spare Part**.
2. [ ] Pilih SPB; pastikan JO dan unit terisi otomatis.
3. [ ] Pilih part yang memang ada pada SPB tersebut.
4. [ ] Isi kuantitas dan status pengadaan.
5. [ ] Finalkan laporan.
6. [ ] Pastikan status Purchase Request, item SPB, dan PPB berubah sesuai status laporan.
7. [ ] Uji status `Dipesan`, lalu `Tiba` menggunakan laporan baru bila diperlukan.
8. [ ] Retry tidak boleh membuat log monitoring ganda.
9. [ ] Void memulihkan status sebelumnya selama belum diubah proses lain.

### 14.2 Parts Weekly

1. [ ] Buka **Report Parts Weekly**.
2. [ ] Isi yard, pekan, tahun, dan tanggal.
3. [ ] Pilih part; pastikan satuan dan harga terisi.
4. [ ] Isi penerimaan dan pengeluaran kumulatif.
5. [ ] Pastikan saldo dan nilai saldo dihitung otomatis.
6. [ ] Catat stok aktual sebelum finalisasi.
7. [ ] Finalkan laporan.
8. [ ] Pastikan snapshot dan variance terbentuk, tetapi stok aktual tidak berubah.
9. [ ] Periksa sumber snapshot pada tab Stok.
10. [ ] Void menonaktifkan snapshot tanpa mengubah stok.

## 15. Batch 11 — BAPP ke Penerimaan Barang

Tujuan: menerima barang dari PPB secara parsial atau lengkap.

1. [ ] Pastikan PPB hasil Batch 9 belum `Received` atau `Cancelled`.
2. [ ] Buka **Berita Acara Penerimaan/Penyerahan Barang**.
3. [ ] Isi nomor `UJI-B11-BAPP-001` dan tanggal.
4. [ ] Pilih PPB; vendor dan item pesanan harus muncul otomatis.
5. [ ] Untuk penerimaan parsial, isi contoh `Jumlah=5`, `Baik=4`, `Rusak=0`, `Kurang=1`.
6. [ ] Finalkan; stok hanya bertambah `4` dan item menjadi `Parsial`.
7. [ ] Buat BAPP kedua untuk sisa satu barang baik.
8. [ ] Setelah lengkap, PPB harus `Received`, SPB `Issued`, dan item `Tiba`.
9. [ ] Periksa transaksi pada tab Barang Masuk dengan sumber `Laporan BAPP`.
10. [ ] Void BAPP kedua; kondisi kembali parsial.
11. [ ] Void BAPP pertama; stok dan status kembali ke kondisi awal.

## 16. Batch 12 — Bukti Kirim/Terima Barang Intern

### 16.1 Transaksi Terima

1. [ ] Buka **Bukti Kirim/Terima Barang Intern**.
2. [ ] Pilih `Terima` dan nomor `UJI-B12-BT-001`.
3. [ ] Pilih lokasi `Dari` dan `Ke`.
4. [ ] Pilih part dan isi jumlah.
5. [ ] Finalkan; stok bertambah dan transaksi tampil pada Barang Masuk.

### 16.2 Transaksi Kirim

1. [ ] Buat laporan baru dan pilih `Kirim` dengan nomor `UJI-B12-BK-001`.
2. [ ] Pilih part yang stoknya cukup.
3. [ ] Finalkan; stok berkurang dan transaksi tampil pada Barang Keluar.
4. [ ] Pengiriman melebihi stok harus ditolak.
5. [ ] Retry tidak boleh membuat transaksi ganda.
6. [ ] Void kedua transaksi dan pastikan stok kembali ke saldo awal.

## 17. Batch 13 — SPPU Parts Urgent

### 17.1 SPPU Yard

1. [ ] Buka **Surat Permintaan Parts Urgent (Yard)**.
2. [ ] Isi nomor `UJI-B13-SPPU-001`.
3. [ ] Pilih Work Order; pastikan kode unit dan lokasi terisi.
4. [ ] Pilih pengaju, operator, dan part dari database.
5. [ ] Pastikan HM dan satuan part terisi otomatis.
6. [ ] Isi jumlah, analisa, solusi, serta bukti gambar.
7. [ ] Finalkan laporan.
8. [ ] Pastikan Purchase Request tampil pada Logistik dengan urgensi `Emergency`.

### 17.2 SPPU 006

1. [ ] Buka **Template SPPU 006**.
2. [ ] Isi nomor `UJI-B13-SPPU6-001`.
3. [ ] Pilih Work Order; pastikan unit, jenis, serial, lokasi, HM, dan project terisi.
4. [ ] Pilih prioritas, personel pengesahan, dan part.
5. [ ] Isi analisa, dampak, tindak lanjut, dan bukti.
6. [ ] Finalkan dan periksa Purchase Request di menu Logistik.
7. [ ] Work Order–unit yang tidak cocok, part tidak terdaftar, atau satuan salah harus ditolak.
8. [ ] Retry tidak boleh membuat duplikasi.
9. [ ] Void menghapus SPPU yang belum diproses dan mempertahankan yang sudah diproses.

## 18. Query pemeriksaan lintas batch

### Status laporan terbaru

```sql
SELECT r.report_number, rt.template_key, r.status, r.created_at, r.finalized_at, r.voided_at
FROM u646470441_ServicePlanBRA.report_records r
JOIN u646470441_ServicePlanBRA.report_templates rt ON rt.template_id = r.template_id
ORDER BY r.created_at DESC
LIMIT 100;
```

### Seluruh transaksi stok hasil laporan

```sql
SELECT r.report_number, rt.template_key, it.movement_type, p.part_number,
       it.quantity, it.stock_before, it.stock_after, it.reversed_at
FROM u646470441_ServicePlanBRA.inventory_transactions it
JOIN u646470441_ServicePlanBRA.report_records r ON r.report_id = it.report_id
JOIN u646470441_ServicePlanBRA.report_templates rt ON rt.template_id = r.template_id
JOIN u646470441_ServicePlanBRA.parts p ON p.part_id = it.part_id
ORDER BY it.transaction_id DESC;
```

### Rantai Work Order → SPB → PPB

```sql
SELECT w.wo_id, w.asset_id, w.status AS wo_status,
       pr.spb_id, pr.status AS spb_status,
       po.ppb_id, po.status AS ppb_status
FROM u646470441_ServicePlanBRA.work_orders w
LEFT JOIN u646470441_ServicePlanBRA.purchase_requests pr ON pr.wo_id = w.wo_id
LEFT JOIN u646470441_ServicePlanBRA.purchase_orders po ON po.spb_id = pr.spb_id
ORDER BY w.reported_at DESC, pr.requested_at DESC;
```

## 19. Menjalankan pengujian otomatis

Jalankan dari root repository:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
Get-ChildItem tests\report_*integration.php | Sort-Object Name | ForEach-Object {
    & $php $_.FullName
    if ($LASTEXITCODE -ne 0) { throw "Test gagal: $($_.Name)" }
}
```

Baseline terakhir setelah Batch 13 adalah **135 pemeriksaan lulus**. Jika jumlah test bertambah pada pengembangan berikutnya, gunakan jumlah terbaru sebagai baseline.

## 20. Lembar hasil pengujian

| Test ID | Batch | Modul | Nomor laporan/data | Draft | Final | Retry | Void | Status | Catatan/bukti |
|---|---:|---|---|---|---|---|---|---|---|
| INT-B01 | 1 | BHW-IN |  |  |  |  |  | NOT RUN |  |
| INT-B02 | 2 | BHW-OUT |  |  |  |  |  | NOT RUN |  |
| INT-B03 | 3 | P2H |  |  |  |  |  | NOT RUN |  |
| INT-B04 | 4 | LHO/Productivitas |  |  |  |  |  | NOT RUN |  |
| INT-B05 | 5 | LHO/Fuel |  |  |  |  |  | NOT RUN |  |
| INT-B06 | 6 | Maintenance Board |  |  |  |  |  | NOT RUN |  |
| INT-B07 | 7 | Repair & Overhaul |  |  |  |  |  | NOT RUN |  |
| INT-B08 | 8 | SPB |  |  |  |  |  | NOT RUN |  |
| INT-B09 | 9 | PPB |  |  |  |  |  | NOT RUN |  |
| INT-B10A | 10 | Procurement Monitoring |  |  |  |  |  | NOT RUN |  |
| INT-B10B | 10 | Parts Weekly |  |  |  |  |  | NOT RUN |  |
| INT-B11 | 11 | BAPP |  |  |  |  |  | NOT RUN |  |
| INT-B12 | 12 | Bukti Kirim/Terima |  |  |  |  |  | NOT RUN |  |
| INT-B13A | 13 | SPPU Yard |  |  |  |  |  | NOT RUN |  |
| INT-B13B | 13 | SPPU 006 |  |  |  |  |  | NOT RUN |  |

Status yang digunakan: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.

## 21. Kriteria kelulusan akhir

Pengujian Batch 1–13 dinyatakan lulus apabila:

- [ ] Semua integrasi kritis berstatus `PASS`.
- [ ] Draft tidak pernah mengubah data operasional.
- [ ] Tidak ada transaksi, inspeksi, rencana, Work Order, SPB, atau PPB ganda akibat retry.
- [ ] Semua validasi stok, HM, unit, Work Order, part, satuan, dan perhitungan bekerja.
- [ ] Void memulihkan data yang aman untuk dipulihkan.
- [ ] Data yang sudah diproses menu lain tidak terhapus oleh void.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Tidak ada defect Critical atau High yang masih terbuka.
