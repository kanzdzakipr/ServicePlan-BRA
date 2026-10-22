# Checkpoint F2a — tautan SPB aktif pada detail WO

Status: **PARTIAL** sampai pengguna memverifikasi di browser. Ini hanya subalur pertama F2; stok, finalisasi/void laporan, dan biaya belum dinyatakan lulus.

## Perubahan dan bukti

- Detail WO (`core/WorkOrderSupport.php::overview`) kini menampilkan SPB yang dibuat manual atau masih mempunyai tautan laporan aktif. Jika seluruh tautan laporannya telah dibalik (`reversed_at` terisi), SPB tidak lagi muncul sebagai permintaan aktif, termasuk SPB yang bukan dibuat oleh integrasi laporan. Baris SPB dan audit laporan tetap di database.
- Tes `tests/work_order_support_integration.php` membuat WO, SPB, dan tautan laporan khusus QA di dalam satu transaksi; sebelum rollback, dibuktikan bahwa SPB aktif terlihat, lalu tautan yang dibalik tidak terlihat tetapi baris SPB tetap ada. Hasil: **11/11 lulus**. Tes tidak memakai DDL dan transaksi selalu di-rollback.
- Patch kode saja dipasang di `C:\laragon2\www\ServicePlan-BRA\core\WorkOrderSupport.php`; backup: `C:\laragon2\tmp\ServicePlan-BRA-backups\F2a-20261022-191357`. PHP lint target lulus. Tidak ada migrasi, dump, seed, atau perubahan data pengguna.

## Uji browser singkat

1. Muat ulang `http://serviceplan-bra.test/` dan buka detail WO yang punya SPB aktif. SPB tersebut harus tetap terlihat pada tab kebutuhan/part.
2. Buka detail WO yang laporan sumber SPB-nya **sudah void**. SPB dari tautan yang dibalik tidak boleh muncul sebagai SPB aktif. Jangan membuat/void laporan baru pada database bersama hanya untuk uji ini.
3. Jika tersedia, bandingkan riwayat laporan dan daftar SPB di database secara baca-saja; catatan void tetap ada. Laporkan ID WO/SPB tanpa data pribadi bila hasil tidak sesuai.

## Batasan

- Skema `purchase_requests.status` hanya memiliki `Draft`, `Submitted`, `Approved`, `Ordered`, `Issued`, `Closed`; tidak ada `Cancelled`. Karena itu F2a memakai penanda pembalikan integrasi laporan, bukan mengarang status baru.
- SPB manual tanpa tautan laporan tetap ditampilkan; keputusan bisnis mengenai pembatalannya masih perlu alur tersendiri.
- Pengujian role/lokasi, refresh lintas akun, dan rekonsiliasi stok/biaya tetap **NOT TESTED** untuk F2a.
