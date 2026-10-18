# Panduan Pengujian Batch 1 — Notifikasi WO per Akun dan Lokasi

Perubahan ini menggunakan tabel baru `notification_recipients`. Event lama di `system_notifications` tetap disimpan, tetapi **tidak dibagikan ulang otomatis** karena penerima historisnya tidak dapat dipastikan. Badge dimulai dari notifikasi baru yang memiliki penerima terverifikasi.

## Persiapan

1. Pastikan aplikasi berjalan di Laragon2 lokal dan login menggunakan akun yang memang sudah ada. Jangan membuat/mengubah akun atau izin produksi untuk tes ini.
2. Gunakan WO uji baru dengan prefiks `QA-BRIAN-` dan pilih aset pada lokasi akun yang akan diuji. **Snapshot Laragon2 saat implementasi:** Planner dan Logistic Head aktif berada di lokasi 4, sedangkan tidak ada aset di lokasi 4. Jangan mengubah cakupan lokasi mereka untuk memaksa tes lulus.
3. Siapkan sesi browser terpisah (atau incognito) untuk Planner, Logistic Head, Mekanik lokasi sama, dan satu pengguna lokasi lain/role yang tidak terkait. Jangan membagikan cookie sesi antarakun.
4. Jangan menjalankan impor dump database. Sinkronisasi kode dilakukan dengan `-LaragonRoot C:\laragon2 -PreserveDatabase`; migrasi penerima dijalankan terpisah dan idempoten.

## Skenario bersama

| Langkah | Hasil yang diharapkan |
|---|---|
| Planner membuat WO `QA-BRIAN-...` | Event `WO_CREATE` muncul satu kali dan tautannya membuka detail WO yang benar. |
| Login Logistic Head pada lokasi aset yang sama (setelah tersedia aset/akun QA yang sesuai) | Notifikasi WO muncul dan badge bertambah; klik membuka WO tersebut. Saat ini skenario positif ini belum dapat dijalankan dengan data lokal yang ada. |
| Login Mekanik pada lokasi yang sama | Notifikasi WO muncul sesuai audience; data unit/WO sesuai lokasi. |
| Login pengguna lokasi lain/role tak terkait | Notifikasi WO tidak muncul; ID notifikasi itu tidak dapat ditandai baca oleh akun ini (API mengembalikan 404). |
| Gunakan aset lokasi 3 dengan Administrator dan Mekanik Senior lokasi 3 | Mekanik menerima notifikasi, Logistic Head lokasi 4 tidak. Ini skenario yang dapat dijalankan tanpa mengubah penugasan akun. |
| Planner menandai satu notifikasi dibaca | Badge Planner berkurang; badge Logistic Head tetap. Setelah refresh/login ulang statusnya tetap. |
| Logistic Head menekan “Tandai semua dibaca” | Hanya notifikasi Logistic Head yang berubah; akun Planner/Mekanik tetap. |
| Ajukan kebutuhan part atau jasa dari WO | Event terkait muncul dengan referensi WO dan permintaan, tanpa notifikasi ganda pada retry cepat. |
| Tutup/batalkan WO uji sesuai alur | Event perubahan WO muncul pada audience yang benar. |
| API tidak dapat dijangkau atau daftar kosong | Badge nol dan daftar kosong; lima contoh notifikasi lama tidak muncul. |

## Pemeriksaan database baca-saja

Jalankan pada database lokal yang benar. Ganti `QA-BRIAN-WO-...` dengan ID uji aktual; jangan gunakan ID WO nyata.

```sql
SELECT n.notification_id, n.action_type, n.title,
       JSON_UNQUOTE(JSON_EXTRACT(n.related_tables_json, '$.wo_id')) AS wo_id,
       JSON_UNQUOTE(JSON_EXTRACT(n.related_tables_json, '$.location_id')) AS location_id,
       nr.user_id, nr.read_at
FROM system_notifications n
JOIN notification_recipients nr ON nr.notification_id = n.notification_id
WHERE JSON_UNQUOTE(JSON_EXTRACT(n.related_tables_json, '$.wo_id')) = 'QA-BRIAN-WO-...'
ORDER BY n.notification_id, nr.user_id;
```

`read_at` harus berbeda per pengguna. Jangan mengubah `is_read` pada `system_notifications`; kolom itu hanya warisan dan bukan sumber status baca baru.

## Tes otomatis yang aman

```powershell
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/notification_audience_unit.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/notification_audience_integration.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/notification_integration.php
```

Kedua tes integrasi membuat notifikasi `QA-BRIAN-` dalam transaksi dan melakukan `ROLLBACK`, sehingga tidak meninggalkan baris uji. Tes browser lintas role tetap perlu dilakukan bersama pengguna sebelum Batch 2 dimulai.
