# Panduan Pengujian F3a — Master Asset sebagai sumber jadwal PM

Status: **PASS berdasarkan konfirmasi pengguna pada 23 Oktober 2026**.

## Hasil verifikasi

- Konfigurasi PM dapat diubah dari Master Asset.
- Nilai tetap tersimpan setelah refresh.
- Forecast Preventive Maintenance membaca konfigurasi dari Master Asset.
- Pengujian otomatis dan pengujian browser pengguna dinyatakan lulus.

## Tujuan

Memastikan jadwal Preventive Maintenance diisi dan diubah melalui Master Asset. Menu Preventive Maintenance hanya membaca data tersebut; unit tanpa konfigurasi tetap tampil sebagai `NO DATA`/belum diatur.

## Uji positif

1. Buka `Master Asset`, pilih satu unit uji, lalu klik **Edit**.
2. Pada bagian **Konfigurasi Preventive Maintenance**, isi contoh yang konsisten:
   - Meter unit saat ini: `1100`
   - Tanggal servis terakhir: tanggal uji yang disepakati
   - Meter servis terakhir: `1000`
   - Interval servis: `250`
   - Jenis meter: `HM`
   - Pemakaian rata-rata per hari: `10`
3. Simpan perubahan. Form harus tertutup dan menampilkan pesan berhasil.
4. Buka kembali unit yang sama. Keenam nilai harus tetap sama setelah refresh/login ulang.
5. Buka **Preventive Maintenance** dan cari unit tersebut.
6. Pastikan target berikutnya `1250 HM`, data servis terakhir `1000 HM`, interval `250 HM`, serta sumber ditandai terhubung ke Master Asset.

## Uji negatif

1. Isi hanya interval dan kosongkan tanggal/meter servis serta pemakaian harian. Simpan harus ditolak.
2. Isi meter unit `900` dan meter servis terakhir `1000`. Simpan harus ditolak karena meter unit tidak boleh lebih kecil.
3. Isi interval `0`, pemakaian harian `0`, atau jenis meter selain HM/KM. Simpan harus ditolak.
4. Batalkan form edit setelah mengubah nilai. Database dan Forecast PM tidak boleh berubah.

## Unit tanpa jadwal

1. Cari unit yang seluruh konfigurasi PM-nya kosong.
2. Di Preventive Maintenance, unit tetap harus terlihat dengan status `NO DATA` dan kolom jadwal kosong.
3. Aplikasi tidak boleh mengisi interval, target, atau tanggal contoh secara otomatis.

## Bukti yang dicatat

- ID unit uji dan role/lokasi pengguna.
- Nilai sebelum dan sesudah di Master Asset.
- Baris unit yang sama pada Forecast PM setelah refresh.
- Pesan validasi pada tiga skenario negatif.

Jangan memakai unit operasional tanpa persetujuan. Gunakan unit QA atau catat nilai awal agar perubahan dapat dikembalikan melalui form resmi.
