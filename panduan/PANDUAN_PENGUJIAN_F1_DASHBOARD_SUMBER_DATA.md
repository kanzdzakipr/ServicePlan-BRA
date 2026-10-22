# Checkpoint F1a — sumber data Dashboard saat refresh

Status F1a: **PASS pada uji browser menurut konfirmasi pengguna**. Irisan ini mencegah cache `fleetmonitor-integrated-state-v1` di browser menimpa aset, WO, dan inspeksi yang baru dimuat dari API; kartu jumlah unit/status memakai hitungan data terfilter termasuk nilai nol. Protokol tulis `api/sync.php` ditangani pada F1b terpisah.

## Yang berubah

- `dashboard.view.php`: pembacaan/penulisan cache operasional lama dihentikan. Saat refresh, `api/init.php` tetap wajib berhasil sebelum Dashboard ditampilkan.
- Kartu Total, READY, STANDBY, BREAKDOWN, dan INSPEKSI menghitung dari daftar aset sesuai filter tanpa mengganti nol dengan ringkasan fallback.
- Tidak ada migrasi SQL atau perubahan data.

## Bukti otomatis

```powershell
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' -l dashboard.view.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/f1_dashboard_source_static.php
```

Hasil checkpoint: lint lulus dan **8/8** pemeriksaan sumber lulus. Ini belum membuktikan perilaku di browser atau konsistensi lintas pengguna.

## Uji manual setelah kode dipasang aman di Laragon2

1. Catat jumlah/status aset dan WO pada Dashboard, serta filter project/lokasi aktif. Buka browser kedua dengan akun/lokasi yang sama dan cocokkan angka setelah refresh.
2. Di browser pertama, jika ada nilai lama dalam `localStorage` dengan kunci `fleetmonitor-integrated-state-v1`, **jangan hapus dulu**. Refresh. Nilai lama tidak boleh mengubah status aset/WO yang diterima dari API. Periksa satu ID dengan GET/API atau query DB baca-saja.
3. Pilih filter lokasi yang tidak memiliki aset status BREAKDOWN. Kartu BREAKDOWN harus `0`, bukan angka global. Ulangi untuk status lain bila ada data yang cocok.
4. Putuskan koneksi API di DevTools hanya pada akun uji; halaman harus menunjukkan kegagalan memuat Master Asset, bukan memakai cache operasional lama sebagai data aktual.
5. Jangan membuat/mengubah WO, P2H, atau status aset untuk uji F1a ini; protokol tulis massal masih perlu subbatch berikutnya.

## Gate sebelum F1 penuh

`api/sync.php` dan semua pemanggil `syncFleetState()` perlu diganti menjadi operasi terarah per ID dengan validasi, otorisasi, precondition/versi, transaksi, dan respons yang diperiksa. Selama itu belum selesai, F1 **tidak PASS** dan perubahan status pada database bersama tidak digunakan sebagai tes otomatis.

`sync_to_laragon.ps1 -PreserveDatabase` tidak dipakai untuk F1a karena tetap menjalankan migrasi/seed. File `dashboard.view.php` **sudah dipasang secara code-only** ke `C:\laragon2\www\ServicePlan-BRA` setelah diff memastikan target hanya tertinggal perubahan F1a. Backup target berada di `C:\laragon2\tmp\ServicePlan-BRA-backups\dashboard.view.php.F1a.20261022-182510.bak`. Hash sumber/target cocok dan lint PHP target lulus. Hitungan DB sesudah salin tetap `assets=282`, `work_orders=366`, `users=16`.
