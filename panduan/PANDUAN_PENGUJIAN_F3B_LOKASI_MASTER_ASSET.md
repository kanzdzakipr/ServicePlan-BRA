# Panduan Pengujian F3b — Lokasi Master Asset

Status implementasi: **siap diuji di browser** pada Laragon2.

## Perbaikan yang diterapkan

- Skema `assets` telah dilengkapi dengan 11 kolom registrasi yang dipakai API Master Asset.
- Lokasi `WUR EW Project` dipulihkan sebagai lokasi aktif dengan ID `15`.
- Sebanyak 87 aset WUR EW kembali mempunyai relasi lokasi yang valid.
- Sinkronisasi tidak lagi menjalankan migrasi lama yang menimpa nilai warranty dan nomor rangka.
- Migrasi baru bersifat aditif dan idempoten; menjalankannya ulang tidak menambah lokasi duplikat.

## Alur uji browser

1. Buka `http://serviceplan-bra.test` dan login sebagai admin.
2. Masuk ke **Master Asset**.
3. Cari salah satu unit WUR EW, misalnya unit yang sebelumnya terlihat tanpa lokasi.
4. Pastikan kolom Project/Lokasi menampilkan **WUR EW Project**.
5. Klik **Edit** pada satu aset uji yang aman.
6. Pastikan pilihan Project/Lokasi berisi **WUR EW Project**.
7. Isi atau ubah satu nilai nonkritis, misalnya `Posisi operasional`, lalu simpan.
8. Muat ulang halaman dan buka kembali detail aset.
9. Pastikan nilai tetap tersimpan dan tidak muncul pesan kegagalan API.
10. Buka menu **Preventive Maintenance** dan pastikan aset yang sama masih dapat ditemukan.

## Hasil yang diharapkan

- Tidak ada aset WUR EW dengan Project/Lokasi kosong karena referensi lokasi yatim.
- Form tambah/edit Master Asset berhasil disimpan.
- Nilai tetap sama setelah refresh atau login ulang.
- Filter lokasi WUR EW menampilkan aset terkait.
- Forecast PM tetap memakai konfigurasi dari Master Asset.

## Bukti otomatis lokal

- `tests/f3b_asset_schema_location_audit.php`: 18 pemeriksaan, termasuk penulisan data uji dalam transaksi yang selalu di-rollback.
- `tests/batch_b_master_asset_onboarding_integration.php`: 46 pemeriksaan integrasi dan pembersihan data uji.
- `tests/master_asset_pm_edit_ui_smoke.js`: 16 pemeriksaan UI konfigurasi PM.
- Basis data sesudah migrasi: 282 aset, 35 lokasi, 87 aset WUR EW, dan 0 referensi lokasi yatim.

Backup sebelum migrasi tersedia di:

`C:\laragon2\www\ServicePlan-BRA\backup-f3b-asset-location-20261023\assets_locations_before.sql`
