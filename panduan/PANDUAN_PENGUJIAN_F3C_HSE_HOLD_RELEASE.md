# Panduan Pengujian F3c — HSE Hold dan Release Aset

Status implementasi: **PASS berdasarkan konfirmasi pengguna pada 23 Oktober 2026**.

## Hasil verifikasi pengguna

- Unit QA dapat dipilih menggunakan ID kanonis `QA-F3C-BROWSER`.
- Lokasi dan operator terisi dari Master Asset; operator dipilih dari daftar untuk mengurangi salah ketik.
- Alur Moderate berhasil mengubah status `STANDBY -> ACCIDENT_HOLD -> STANDBY` setelah release.
- Laporan, status hold/release, refresh browser, dan pemulihan status dinyatakan lulus.
- Setelah pengujian disetujui, unit QA dan seluruh data HSE terkait dihapus sehingga data kembali ke kondisi sebelum pengujian.

## Perbaikan

- Insiden `Moderate` dan `Critical` sekarang menyimpan `ACCIDENT_HOLD` ke database Master Asset.
- Insiden `Minor` tidak lagi memakai default lock yang keliru.
- Release dilakukan melalui API, wajib memiliki alasan, serta mencatat aktor dan waktu.
- Release mengembalikan status aset sebelum hold, bukan selalu memaksa `READY`.
- Jika satu unit memiliki beberapa hold aktif, pelepasan satu insiden tidak membuka unit.
- Perubahan status aset yang terjadi setelah hold tidak ditimpa oleh proses release.
- Tanggal laporan mengikuti tanggal kejadian yang diisi, bukan tanggal statis.

## Uji browser utama

Gunakan satu unit uji yang aman dan catat status awalnya.

1. Buka **HSE / Accident → Laporkan Insiden Baru**.
2. Pilih unit uji dan isi waktu kejadian, lokasi, operator, serta kronologi.
3. Pilih severitas **Moderate**.
4. Isi dampak, penyebab, corrective action, dan preventive action.
5. Simpan laporan.
6. Muat ulang halaman HSE; laporan harus tetap muncul sebagai `ACCIDENT_HOLD`.
7. Buka **Master Asset**, cari unit yang sama, lalu pastikan statusnya `ACCIDENT_HOLD` setelah refresh.
8. Kembali ke **HSE → CAPA & Release Approval**.
9. Klik **Otorisasi Rilis Unit**, konfirmasi, kemudian isi alasan rilis.
10. Muat ulang HSE dan Master Asset.
11. Pastikan insiden berstatus `Closed`, lock `RELEASED`, dan status aset kembali ke status awal yang dicatat pada langkah pertama.

## Uji negatif singkat

- Batalkan prompt alasan: release tidak boleh terjadi.
- Kirim alasan kosong: harus ditolak.
- Insiden `Minor`: laporan tersimpan tetapi aset tidak berubah menjadi `ACCIDENT_HOLD`.
- Refresh/login ulang: status harus tetap sama dengan database.

## Bukti otomatis

- Integrasi transaksi F3c: 8 pemeriksaan PASS dan seluruh data QA di-rollback.
- Regresi P2H: 14 pemeriksaan PASS.
- Regresi MDE-02: 12 pemeriksaan PASS.
- HTTP Laragon2: create Moderate, hold, release, pemulihan status, dan audit trail PASS.
- Sisa aset/insiden QA: 0.

## Data lama yang tidak diubah

Kondisi sebelum pengujian dipulihkan pada 23 Oktober 2026. Laporan uji `03/ACC/BRA/SITE/2026` yang waktu kejadiannya tanggal 23 Oktober dihapus. Laporan lama `01/ACC/BRA/DURI/2026` kembali `Reported`/locked dan aset `CS-41001` kembali `ACCIDENT_HOLD`. Laporan `02/ACC/BRA/YARD/2026` untuk `DT-00052` tetap `Reported`/locked.

Backup sebelum migrasi tersedia di:

`C:\laragon2\www\ServicePlan-BRA\backup-f3c-hse-20261023\assets_accidents_before.sql`

Backup cleanup data QA tersedia di:

`C:\laragon2\backups\ServicePlan-BRA\qa-f3c-cleanup-20261023-171309`

Backup sebelum pemulihan `CS-41001` dan penghapusan laporan tanggal 23 Oktober tersedia di:

`C:\laragon2\backups\ServicePlan-BRA\cs-41001-restore-20261023-171706`
