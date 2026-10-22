# Checkpoint F1b — sinkronisasi terarah aset/WO

Status: **PARTIAL — kode F1b telah dipasang secara selektif di Laragon2; belum terbukti lewat uji browser/DB end-to-end**. Jangan uji P2H/Condition Monitoring pada DB bersama sebelum data uji disepakati.

## Perubahan

- `api/sync.php` menerima hanya `mode: targeted` dengan maksimal **satu aset dan satu WO terkait**. Payload massal lama mendapat HTTP 422. Status aset dibaca dengan lock dan dibandingkan dengan `expected_status`; bila sudah berubah di server, HTTP 409 tanpa menimpa pengguna lain. HM tidak diturunkan; lokasi mentah hanya berubah jika dikirim eksplisit.
- WO yang sudah ada tidak di-upsert lagi melalui sync; replay identik dilewati, konflik mendapat 409. Perubahan WO yang ada tetap memakai `api/work_orders.php`.
- `dashboard.view.php` tidak lagi mengirim seluruh `globalData` pada setiap `syncFleetState()`. Panggilan tanpa `assetId`/`workOrderId` hanya refresh tampilan. Form status manual mengirim satu aset/WO dengan token CSRF.
- `scripts/dashboard.js` mengirim ID terarah pada P2H dan Condition Monitoring. Simpan P2H yang ditolak server tidak lagi diteruskan menjadi perubahan lokal/WO. POST terkait menyertakan token CSRF.
- Tidak ada migrasi atau perubahan DB dalam implementasi dan tes ini.

## Bukti otomatis dan batasnya

```powershell
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/f1_targeted_sync_policy.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/security_static.php
node --check scripts/dashboard.js
```

Hasil awal: policy **16/16**, keamanan statis **39/39**, lint PHP/JS lulus. Belum ada tes HTTP role/lokasi, konflik dua browser, rollback transaksi, atau browser end-to-end. `PASS` akhir belum boleh diberikan.

## Pemasangan dan batas sinkronisasi Laragon2

Perubahan F1b pada `api/sync.php`, `dashboard.view.php`, dan `scripts/dashboard.js` sudah dipasang sebagai patch terarah, disertai `core/FleetSyncPolicy.php`. Backup target ada di `C:\laragon2\tmp\ServicePlan-BRA-backups\F1b-20261022-190829`. Lint PHP/JS di target lulus; jumlah DB sebelum/sesudah tetap: aset 282, WO 366, inspeksi 0. Tidak ada migrasi/seed.

Koreksi: bagian KPI di `scripts/dashboard.js` justru **ada di repository tetapi belum ada di Laragon2**. Bagian itu tidak termasuk F1b, sehingga sengaja tidak ikut dipasang. Jangan menyimpulkan seluruh file target identik dengan repository. Jangan menjalankan sync penuh tanpa audit efeknya; `-PreserveDatabase` tetap menjalankan migrasi/seed.

## Uji bersama sesudah pemasangan

1. Pada DB/akun uji terisolasi, ambil satu aset uji dan catat `status`, HM, WO, serta jumlah total aset/WO. Ubah status satu unit dari UI, lalu cocokkan hanya ID tersebut di DB. ID lain tidak boleh berubah.
2. Di browser kedua, ubah status aset uji yang sama lebih dulu. Permintaan dari browser pertama yang belum refresh harus menerima 409 dan meminta reload; perubahan browser kedua tetap utuh.
3. Simpan P2H kritis satu kali: tepat satu inspeksi dan paling banyak satu WO baru; retry tidak membuat duplikat. Uji juga P2H normal, data invalid, role tanpa `sync.write`, dan lokasi lain.
4. Simpan temuan Condition Monitoring berbahaya dan cek status aset/WO sesudah refresh dan login ulang.
5. Kirim payload massal dengan **ID fiktif** dan token valid pada lingkungan uji: API harus 422 sebelum transaksi. Jangan gunakan data pengguna nyata untuk uji mutasi.
6. Catat respons HTTP, query `SELECT` sebelum/sesudah, dan rollback/cleanup yang benar-benar aman. Jika salah satu alur gagal, F1b tetap PARTIAL.

## Keterbatasan yang masih terbuka

- `sync.write` saat ini tidak diberikan kepada semua role yang dapat mengisi P2H/Condition Monitoring. Jangan memperluas izin generik itu tanpa keputusan role dan endpoint khusus; uji role negatif/positif diperlukan.
- Status tersimpan dan penampilan sukses pada beberapa form masih perlu diuji pada kegagalan jaringan/409. Jangan menganggap alert UI sebagai bukti DB.
- `api/sync.php` adalah jalur kompatibilitas terbatas. Tujuan akhirnya: endpoint domain spesifik untuk transisi status, hasil inspeksi, dan WO dengan audit event serta precondition server.
