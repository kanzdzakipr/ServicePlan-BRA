# Pengujian Batch 4 — Pencarian universal aset

Pencarian pada header sekarang memakai `GET api/assets.php?action=search&q=...`, bukan daftar aset yang tersimpan di browser. Hasil dibatasi maksimum 20 dan mengikuti cakupan lokasi akun.

## Uji bersama di Laragon2 lokal

1. Login, buka halaman mana saja, lalu tekan `/` atau `Ctrl+K` (`⌘K` di Mac). Fokus harus pindah ke kolom pencarian. `Escape` harus menutup hasil.
2. Pilih satu unit uji yang memiliki kode unit, nomor lambung, SN, dan plat. Cari keempat nilai itu satu per satu. Semua pencarian harus menampilkan **unit yang sama**. Tekan `Enter` atau klik hasil; harus langsung membuka Unit Properties untuk ID unit yang dipilih.
3. Periksa tiap kartu hasil: kode unit, lambung, kategori/model, SN, plat, status, dan lokasi. Nilai yang kosong harus ditulis `-` atau “belum tersedia”, bukan data rekaan.
4. Coba dua unit dengan nomor lambung/plat yang mirip atau sama. Keduanya harus tampil sebagai kartu berbeda menurut kode unit. Coba kata kunci sangat umum: maksimal 20 hasil dan ada pemberitahuan untuk memperjelas pencarian.
5. Coba satu karakter, kata yang tidak ada, `%`, dan `_`. Satu karakter tidak mengirim pencarian; tidak ada hasil harus dijelaskan; `%`/`_` dicari sebagai karakter biasa, bukan wildcard SQL.
6. Login dengan akun yang hanya berhak pada satu site. Cari kode unit dari site lain yang diketahui oleh penguji. Unit tersebut **tidak boleh muncul**. Klik URL API langsung tanpa login: harus ditolak.
7. Coba di layar sempit: tombol ikon pencarian membuka input dan hasil tetap dapat diklik. Ketik cepat dua istilah berbeda; hasil akhir harus sesuai istilah terakhir, bukan respons lama.

## Cek endpoint dan database

Di browser yang sudah login, buka DevTools → Network dan cari `assets.php?action=search`. Respons seharusnya memuat paling banyak 20 baris. Di HeidiSQL, cocokkan ID dan site dengan:

```sql
SELECT a.asset_id, a.asset_code, COALESCE(a.hull_number, a.alias_name) AS lambung,
       a.serial_number, a.license_plate, a.current_location_id, l.location_name
FROM assets a LEFT JOIN locations l ON l.location_id = a.current_location_id
WHERE a.asset_id = 'GANTI_DENGAN_ID_UNIT_UJI';
```

Lulus bila keempat identitas menuju detail yang sama, lokasi lain tidak bocor, hasil duplikat jelas, dan pencarian tetap responsif. Kirim hasil uji sebelum melanjutkan Batch 5.
