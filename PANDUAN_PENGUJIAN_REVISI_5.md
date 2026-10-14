# Panduan Pengujian Revisi 5 — Riwayat Penggunaan Suku Cadang

## Tujuan

Memastikan barang keluar dari BHW-OUT tercatat sebagai pemakaian pada unit dan Work Order (WO) yang benar. Riwayat harus menampilkan tanggal, bukti, nomor part, nama, jumlah, satuan, unit, dan WO. Transaksi dari laporan yang di-void tidak boleh muncul sebagai pemakaian aktif.

## Persiapan

1. Jalankan Apache dan MySQL pada Laragon 2, lalu login ke `http://serviceplan-bra.test`.
2. Pilih satu WO dan catat ID WO serta ID unitnya dari menu **Work Order**.
3. Pastikan Master Part yang akan diuji memiliki stok minimal 2. Catat part number dan stok awalnya.
4. Gunakan nomor log dan nomor bukti unik agar transaksi uji mudah dicari.

## Skenario 1 — Catat barang keluar

1. Buka **Laporan & Form** → **Buku Harian Warehouse (Barang Keluar)**.
2. Isi identitas laporan dan klik **Tambah baris** bila diperlukan.
3. Pada baris barang, pilih **Nomor WO/JO**. Pastikan **ID unit** otomatis terisi sesuai WO; bila belum, pilih ID unit yang tepat.
4. Pilih part dari Master Part. Pastikan part number, nama, satuan, dan persediaan sesuai database.
5. Isi tanggal, nomor bukti, tujuan, dan jumlah diberikan (contoh: `2`). Sisa dihitung otomatis.
6. Finalkan laporan.

Hasil yang diharapkan: stok part berkurang sesuai jumlah diberikan; laporan final tersimpan; tidak ada transaksi kedua jika finalisasi diulang.

## Skenario 2 — Telusuri dari unit dan WO

1. Buka **Spare Part & Logistik**, temukan unit uji, lalu klik **Akses**.
2. Lihat bagian **Riwayat Pemakaian Warehouse**. Pastikan tanggal, nomor bukti, part, jumlah, dan WO sesuai laporan.
3. Buka **Work Order** → detail WO uji → **Kebutuhan & Layanan**.
4. Lihat **Riwayat penggunaan suku cadang pada WO ini**. Pastikan item yang sama muncul dan nomor unit serta WO tepat.
5. Pada tab **Buku Harian Warehouse Barang Keluar**, cari nomor WO atau unit untuk memastikan transaksi yang sama tersedia di daftar logistik.

## Skenario 3 — Validasi hubungan dan void

1. Buat draft BHW-OUT lain menggunakan WO uji tetapi isi ID unit milik WO berbeda.
2. Finalisasi harus ditolak dengan pesan bahwa WO tidak cocok dengan unit. Stok tidak berubah.
3. Void laporan final dari Skenario 1 menggunakan akun berwenang.
4. Muat ulang Logistik dan detail WO.

Hasil yang diharapkan: stok dikembalikan; transaksi yang di-void tidak muncul di riwayat aktif unit atau WO. Data transaksi tetap ada di database sebagai audit dengan `reversed_at` terisi.

## Verifikasi database

Ganti `ID_WO_UJI` dengan ID WO yang diuji.

```sql
SELECT it.transaction_id, it.transaction_date, it.reference_number,
       it.asset_id, it.wo_id, p.part_number, p.part_name,
       it.quantity, it.unit_measure, it.stock_before, it.stock_after,
       it.reversed_at
FROM inventory_transactions it
JOIN parts p ON p.part_id = it.part_id
WHERE it.movement_type = 'OUT' AND it.wo_id = 'ID_WO_UJI'
ORDER BY it.transaction_date DESC, it.transaction_id DESC;
```

Catatan: riwayat aktif pada aplikasi hanya mengambil baris dengan `reversed_at IS NULL`. Laporan lama yang tidak memiliki unit dan WO tidak ditautkan otomatis ke WO karena identitasnya belum dapat dipastikan.
