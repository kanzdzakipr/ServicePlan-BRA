# Panduan Pengujian Revisi 8 — Impor dan Ekspor Kontrak CSV

## Tujuan

Memastikan data kontrak per unit dapat diimpor dari CSV, kesalahan per baris dijelaskan, data tersimpan di MySQL Laragon 2, dan CSV hasil ekspor dapat diimpor kembali. Menu juga membandingkan target jam kontrak dengan jam operasi dari LHO final pada periode kontrak.

## Persiapan

1. Jalankan Apache dan MySQL Laragon 2, lalu masuk ke `http://serviceplan-bra.test` sebagai akun yang boleh mengubah Master Asset.
2. Buka **Produktivitas → Data Kontrak CSV**.
3. Klik **Unduh template**. Isi CSV UTF-8 dengan satu baris per unit dan periode kontrak. Gunakan ID unit persis seperti pada Master Asset.

Kolom wajib: `contract_number,project,site,asset_id,start_date,end_date,target_hours`.

Kolom opsional: `notes`. Tanggal memakai `YYYY-MM-DD`; `target_hours` adalah jam target untuk seluruh periode, angka positif maksimal dua desimal. Kolom `actual_hours` dan `achievement_percent` pada hasil ekspor hanya informasi hasil hitung dan diabaikan saat diimpor ulang.

Contoh isi (ganti `ID_UNIT_VALID` dengan ID unit di database):

```csv
contract_number,project,site,asset_id,start_date,end_date,target_hours,notes
KONTRAK-UJI-001,Proyek Uji,Site Uji,ID_UNIT_VALID,2026-10-01,2026-10-31,160.00,Uji revisi 8
```

## Skenario 1 — Impor valid

1. Pilih CSV yang sudah diisi.
2. Klik **Periksa CSV** dan pastikan jumlah baris valid ditampilkan tanpa kesalahan.
3. Klik **Impor data**.
4. Muat ulang halaman dan buka kembali tab **Data Kontrak CSV**.

Hasil: nomor kontrak, proyek/site, unit, periode, target jam, jam aktual LHO, dan persentase pencapaian tampil. Data tetap ada setelah memuat ulang.

## Skenario 2 — Pesan kesalahan per baris

1. Buat CSV dengan beberapa baris salah: ID unit tidak ada, tanggal `2026-02-30`, target jam negatif, atau duplikat kontrak/unit/tanggal mulai.
2. Klik **Periksa CSV**.
3. Perhatikan nomor baris dan pesan kesalahan. Tombol **Impor data** harus tetap nonaktif.
4. Perbaiki CSV, pilih ulang file, dan periksa kembali.

Hasil: file dengan kesalahan tidak menyimpan sebagian baris. File lebih dari 1 MB atau lebih dari 1000 baris juga ditolak.

## Skenario 3 — Ekspor dan impor ulang

1. Klik **Ekspor CSV**.
2. Buka CSV hasil ekspor. Periksa seluruh kolom sumber dan kolom hasil hitung `actual_hours,achievement_percent`.
3. Pilih file ekspor tersebut, klik **Periksa CSV**, lalu **Impor data**.

Hasil: file diterima dan jumlah kontrak tidak bertambah karena kombinasi nomor kontrak, unit, dan tanggal mulai diperbarui.

## Skenario 4 — Perhitungan produktivitas

1. Finalkan LHO untuk unit kontrak dengan tanggal di dalam periode kontrak.
2. Buka kembali tab kontrak atau muat ulang halaman.
3. Bandingkan **Aktual LHO** dengan jumlah `hm_operation` LHO final pada periode tersebut.
4. Void LHO uji, lalu muat ulang tab kontrak.

Hasil: jam aktual dan persentase pencapaian berubah sesuai LHO final aktif. LHO yang di-void tidak dihitung.

## Verifikasi database

```sql
SELECT contract_number, project, site, asset_id, start_date, end_date,
       target_hours, notes, created_at, updated_at
FROM contract_assignments
WHERE contract_number = 'KONTRAK-UJI-001';
```

Penghitungan aktual menggunakan `report_operation_logs.hm_operation` untuk unit dan tanggal dalam periode kontrak, hanya jika laporan berstatus `FINAL` dan transaksi belum dibalik.
