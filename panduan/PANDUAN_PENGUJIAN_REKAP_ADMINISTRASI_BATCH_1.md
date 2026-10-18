# Rekap Administrasi — batch 1 (Logistik)

## Cakupan

Menu **Laporan & Form → Rekap Administrasi** sekarang mempunyai rekap bulanan Logistik dari transaksi `inventory_transactions` yang masih aktif. Barang masuk dan barang keluar dipisahkan, menampilkan tanggal, bukti, pihak terkait, part, jumlah, saldo sebelum/sesudah, serta nomor laporan asal. Tombol CSV mengekspor baris yang sama. Pengguna harus memiliki izin `reports.read` **dan** `logistics.read`.

Tujuh dokumen Januari 2026 tercantum sebagai acuan. Enam rekap selain Logistik belum dihasilkan otomatis. Arsip Januari 2026 **tidak** diimpor menjadi transaksi baru; memilih Januari 2026 dapat menampilkan kosong walaupun PDF lama berisi data. Saldo impor `STOCK_GUDANG.md` juga tidak dihitung sebagai penerimaan bulan September karena itu adalah saldo awal, bukan transaksi BHW.

## Langkah uji di Laragon2

1. Login dengan akun yang boleh membaca Laporan dan Logistik. Buka **Laporan & Form → Rekap Administrasi**.
2. Pilih `2026-08`, klik **Muat Logistik**. Pada database lokal saat panduan dibuat, ada satu baris **Keluar** tanggal 5 Agustus 2026, bukti `12345678`, jumlah `2`; jumlah **Masuk** `0`.
3. Klik **Unduh CSV**. Buka CSV dan cocokkan satu baris itu dengan tabel. Angka pada ringkasan adalah penjumlahan kuantitas, bukan jumlah part unik.
4. Pilih `2026-01`. Hasil kosong berarti belum ada transaksi sistem pada periode itu; jangan menyalin angka dari arsip Januari ke rekap otomatis.
5. Coba periode tidak valid melalui URL API (misalnya `period=2026-13`): harus ditolak. Login dengan akun tanpa izin Logistik: API harus mengembalikan 403, bukan data transaksi.
6. Jika membuat dan memfinalkan BHW-IN/BHW-OUT baru pada bulan uji, muat ulang rekap dan periksa jumlah/baris bertambah. Bila laporan di-void, barisnya tidak lagi masuk rekap aktif. Gunakan data uji yang disepakati tim; jangan mengubah transaksi nyata hanya untuk tes.

## Batch berikutnya

Breakdown dan perbaikan harian memerlukan pemetaan tanggal/jam serta tindakan dari WO yang valid. Greasing dan service memerlukan hasil PM historis; Cash Out memerlukan aturan akses biaya; Pendapatan Sewa memerlukan sumber tarif dan ledger sewa harian. Jangan menyatakan ketujuh rekap sudah sesuai arsip sampai sumber, rumus, serta uji lintas peran disetujui.
