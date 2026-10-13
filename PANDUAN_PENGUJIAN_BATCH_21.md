# Panduan Pengujian Batch 21 — Pembatasan Data Berdasarkan Lokasi dan Pemilik Laporan

Batch 21 mengaktifkan kembali pembatasan data pada server. Pengguna biasa hanya dapat melihat data aset pada lokasi yang ditugaskan kepadanya dan hanya dapat mengakses laporan yang dibuatnya sendiri. Akses lintas lokasi atau lintas pemilik hanya diberikan melalui permission khusus.

## Cakupan perubahan

- Permission `scope.all_locations` memberi akses data dari seluruh lokasi.
- Tanpa permission tersebut, data dibatasi oleh `users.assigned_location_id`.
- Permission `reports.read_all` memberi akses ke laporan milik pengguna lain.
- Tanpa permission tersebut, laporan dibatasi oleh `report_records.created_by`.
- Pengguna tanpa lokasi penugasan mendapat hasil kosong, bukan akses global.
- ID aset atau laporan di luar cakupan tidak boleh dapat digunakan untuk melewati pembatasan.
- Administrator, Equipment Manager, dan Asset Manager tetap memiliki akses global melalui matriks RBAC saat ini.

Endpoint yang ikut terlindungi mencakup Asset, Work Order, Fuel, Preventive Maintenance, Condition Monitoring, Inspeksi, Accident, Logistik, Archive, Dashboard, sinkronisasi, referensi laporan, dan Laporan & Form.

## Persiapan

1. [ ] Pastikan Apache dan MySQL Laragon aktif.
2. [ ] Siapkan satu akun global, misalnya Administrator.
3. [ ] Siapkan satu akun terbatas yang memiliki `assigned_location_id` dan tidak memiliki `scope.all_locations`.
4. [ ] Pastikan lokasi akun terbatas mempunyai minimal satu aset agar hasil positif dapat diuji.
5. [ ] Catat satu aset pada lokasi akun dan satu aset pada lokasi lain.
6. [ ] Siapkan dua akun pembuat laporan yang berbeda.

Query untuk memeriksa akun dan lokasi:

```sql
SELECT
    u.user_id,
    u.username,
    u.full_name,
    r.role_name,
    u.assigned_location_id,
    l.location_name,
    COUNT(a.asset_id) AS asset_count
FROM users u
JOIN roles r ON r.role_id = u.role_id
LEFT JOIN locations l ON l.location_id = u.assigned_location_id
LEFT JOIN assets a ON a.current_location_id = u.assigned_location_id
WHERE u.is_active = 1
GROUP BY
    u.user_id,
    u.username,
    u.full_name,
    r.role_name,
    u.assigned_location_id,
    l.location_name
ORDER BY asset_count DESC, u.username;
```

## A. Pengujian akun dengan cakupan lokasi terbatas

1. [ ] Login menggunakan akun terbatas.
2. [ ] Buka Dashboard dan Master Asset.
3. [ ] Pastikan semua aset yang tampil berasal dari `assigned_location_id` akun tersebut.
4. [ ] Cari aset yang diketahui berada pada lokasi lain.
5. [ ] Pastikan aset lintas lokasi tidak muncul pada pencarian atau daftar.
6. [ ] Coba membuka detail aset lintas lokasi menggunakan URL/API langsung.
7. [ ] Pastikan server mengembalikan `404 OBJECT_NOT_FOUND`, bukan data aset.
8. [ ] Ulangi pemeriksaan pada Work Order, PM, Condition Monitoring, Inspeksi, Fuel, dan Archive.

Query pembanding:

```sql
SELECT asset_id, unit_code, asset_name, current_location_id
FROM assets
WHERE current_location_id = ID_LOKASI_AKUN
ORDER BY unit_code;
```

Hasil yang diharapkan: jumlah dan identitas aset pada aplikasi tidak keluar dari hasil query lokasi tersebut.

## B. Pengujian akun dengan cakupan lokasi global

1. [ ] Logout dari akun terbatas.
2. [ ] Login sebagai Administrator, Equipment Manager, atau Asset Manager.
3. [ ] Buka Master Asset dan Dashboard.
4. [ ] Pastikan data dari beberapa lokasi dapat terlihat.
5. [ ] Buka detail aset yang sebelumnya ditolak bagi akun terbatas.
6. [ ] Pastikan detail dapat diakses sesuai permission menu role global tersebut.

## C. Pengujian kepemilikan laporan

1. [ ] Login sebagai akun terbatas A.
2. [ ] Buat dan simpan satu laporan baru; catat nomor record-nya.
3. [ ] Pastikan laporan tersebut muncul pada Riwayat Laporan akun A.
4. [ ] Logout, lalu login sebagai akun terbatas B.
5. [ ] Pastikan laporan akun A tidak muncul pada daftar akun B.
6. [ ] Coba mengakses atau mengubah ID laporan akun A secara langsung.
7. [ ] Pastikan data tidak dibuka atau diubah.
8. [ ] Login sebagai akun yang memiliki `reports.read_all`.
9. [ ] Pastikan laporan akun A dapat dilihat oleh akun global tersebut.

Query pembanding:

```sql
SELECT
    rr.report_id,
    rt.code AS template_code,
    rr.status,
    rr.created_by,
    u.username AS created_by_username,
    rr.created_at,
    rr.updated_at
FROM report_records rr
JOIN report_templates rt ON rt.template_id = rr.template_id
LEFT JOIN users u ON u.user_id = rr.created_by
ORDER BY rr.updated_at DESC, rr.report_id DESC
LIMIT 50;
```

## D. Pengujian pengguna tanpa lokasi

Pengujian ini sebaiknya memakai akun uji, bukan akun operasional.

1. [ ] Catat nilai `assigned_location_id` awal akun uji.
2. [ ] Set `assigned_location_id` akun uji menjadi `NULL`.
3. [ ] Login sebagai akun tersebut.
4. [ ] Pastikan daftar aset kosong dan aset tidak dapat dibuka langsung.
5. [ ] Pastikan pengguna tidak otomatis mendapat akses seluruh lokasi.
6. [ ] Kembalikan `assigned_location_id` ke nilai awal setelah pengujian.

## E. Pengujian otomatis

Jalankan dari root project:

```powershell
php tests/security_unit.php
php tests/security_static.php
```

Opsional, jalankan pengujian HTTP terhadap hosting lokal:

```powershell
$env:SECURITY_TEST_BASE_URL = 'http://serviceplan-bra.test'
$env:SECURITY_TEST_LIMITED_USER = 'USERNAME_AKUN_TERBATAS'
$env:SECURITY_TEST_LIMITED_PASSWORD = 'PASSWORD_LOKAL'
$env:SECURITY_TEST_CROSS_SCOPE_ASSET_ID = 'ID_ASET_LOKASI_LAIN'
php tests/security_http_smoke.php
```

Hapus environment variable pengujian setelah selesai:

```powershell
Remove-Item Env:SECURITY_TEST_BASE_URL -ErrorAction SilentlyContinue
Remove-Item Env:SECURITY_TEST_LIMITED_USER -ErrorAction SilentlyContinue
Remove-Item Env:SECURITY_TEST_LIMITED_PASSWORD -ErrorAction SilentlyContinue
Remove-Item Env:SECURITY_TEST_CROSS_SCOPE_ASSET_ID -ErrorAction SilentlyContinue
```

## F. Validasi keamanan tambahan

1. [ ] Pengguna terbatas tidak dapat membuat atau memperbarui record memakai `asset_id` dari lokasi lain.
2. [ ] Respons aset yang tidak ada dan aset di luar cakupan sama-sama `404`, sehingga ID tidak mudah ditebak.
3. [ ] Mengubah lokasi pada payload request tidak dapat memperluas cakupan pengguna.
4. [ ] Akun tanpa permission tulis tetap menerima `403 FORBIDDEN` pada operasi tulis.
5. [ ] Data di database tidak terhapus atau berpindah lokasi akibat pengujian baca.

## Catatan hasil pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| SEC-B21-01 | Akun terbatas hanya melihat aset lokasinya | NOT RUN |  |
| SEC-B21-02 | Aset lintas lokasi tidak dapat dibuka langsung | NOT RUN |  |
| SEC-B21-03 | WO, PM, inspeksi, dan menu terkait ikut terfilter | NOT RUN |  |
| SEC-B21-04 | Akun global tetap melihat seluruh lokasi | NOT RUN |  |
| SEC-B21-05 | Pemilik dapat melihat laporannya sendiri | NOT RUN |  |
| SEC-B21-06 | Pengguna lain tidak dapat melihat/mengubah laporan tersebut | NOT RUN |  |
| SEC-B21-07 | Permission `reports.read_all` membuka akses lintas pemilik | NOT RUN |  |
| SEC-B21-08 | Akun tanpa lokasi memperoleh hasil kosong | NOT RUN |  |
| SEC-B21-09 | Pengujian otomatis berhasil | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.
