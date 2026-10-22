# Checkpoint F2b — alasan wajib saat void laporan

Status: **PARTIAL** sampai uji browser/HTTP oleh pengguna. Perubahan ini tidak mengubah logika stok atau biaya.

## Perubahan

- Menu Riwayat Laporan meminta alasan saat laporan backend di-void. Membatalkan dialog alasan tidak mengirim permintaan ke server.
- `api/reports.php` menolak alasan kosong, bukan teks, atau lebih dari 500 karakter dengan HTTP 422 **sebelum transaksi**. Alasan valid dicatat pada audit void yang sudah ada.
- File kode F2b dipasang secara terarah di Laragon2. Tidak ada migrasi, seed, impor dump, atau perubahan data pengguna. Selisih KPI pada `scripts/dashboard.js` Laragon2 tidak disentuh.

## Bukti otomatis

`php tests/report_void_reason_policy.php`: **6/6 lulus** untuk alasan kosong, spasi, bukan teks, terlalu panjang, dan normalisasi alasan valid. PHP lint dan `node --check` pada file repository serta target Laragon2 lulus. Tes ini tidak menulis database.

## Uji browser aman

1. Buka Riwayat Laporan pada `http://serviceplan-bra.test/` dengan akun yang memang boleh void laporan.
2. Klik Void pada **laporan uji yang disepakati saja**. Pada dialog konfirmasi, pilih Batal: tidak ada perubahan.
3. Bila Anda punya laporan uji yang boleh dibatalkan, konfirmasi lalu kosongkan alasan: aplikasi harus menolak dan laporan tetap FINAL. Masukkan alasan yang jelas, lanjutkan, lalu cek status VOID dan alasan di audit setelah refresh.
4. Jangan void laporan operasional pengguna lain demi pengujian.

## Belum dibuktikan

Tes HTTP terautentikasi, perubahan database pada laporan uji, role/lokasi lain, serta reversal stok dan biaya masih **NOT TESTED** di F2b. F2 secara keseluruhan belum PASS.
