# Panduan Pengujian Batch 16 — LPK ke Register Kalibrasi

Batch 16 mengintegrasikan **Laporan Pelaksanaan Kalibrasi (LPK)** dengan register pada menu **Preventive Maintenance → Kalibrasi Alat Ukur** dan tabel `calibration_records` di Laragon.

## 1. Hasil yang diharapkan

- Lokasi pengesahan dipilih dari Master Lokasi.
- Pembuat dan pemeriksa dipilih dari Master Personel aktif.
- Alat yang pernah tercatat dapat dipilih kembali berdasarkan nama atau nomor identifikasi; nama, identifikasi, dan merk/type otomatis saling melengkapi.
- Draft tidak membuat record kalibrasi.
- Finalisasi membuat satu record untuk setiap baris terisi.
- Hasil langsung tampil pada tab **Kalibrasi Alat Ukur**.
- Retry tidak menggandakan record.
- Void menyembunyikan record dari register aktif tanpa menghapus jejak audit.

## 2. Persiapan

Pilih lokasi dan personel aktif:

```sql
SELECT location_id, location_name, location_type
FROM u646470441_ServicePlanBRA.locations
WHERE is_active = 1
ORDER BY location_name;

SELECT u.user_id, u.full_name, r.role_name, l.location_name
FROM u646470441_ServicePlanBRA.users u
LEFT JOIN u646470441_ServicePlanBRA.roles r
    ON r.role_id = u.role_id
LEFT JOIN u646470441_ServicePlanBRA.locations l
    ON l.location_id = u.assigned_location_id
WHERE u.is_active = 1
ORDER BY u.full_name;
```

Gunakan data uji berikut:

| Data | Nilai contoh |
|---|---|
| Nomor laporan | `UJI-B16-LPK-001` |
| Periode | Oktober 2026 |
| Nama alat | Torque Wrench |
| Nomor identifikasi | `TW-UJI-B16-001` |
| Merk/type | Gedore 200 Nm |

## 3. Pengujian referensi otomatis

1. [ ] Buka **Laporan & Form**.
2. [ ] Cari `LPK` atau **Laporan Pelaksanaan Kalibrasi**.
3. [ ] Klik **Isi Form**.
4. [ ] Isi nomor laporan dan periode.
5. [ ] Pilih lokasi dari Master Lokasi.
6. [ ] Pilih **Dibuat oleh** dan **Diperiksa oleh** dari Master Personel.
7. [ ] Jika register sudah mempunyai alat, pilih nomor identifikasinya.
8. [ ] Pastikan nama alat dan merk/type ikut terisi.
9. [ ] Untuk alat baru, isi nama, identifikasi, dan merk/type secara manual; setelah laporan final, alat tersebut harus menjadi pilihan pada laporan berikutnya.

## 4. Pengujian Draft

1. [ ] Isi satu baris alat, rencana, pelaksanaan, metode, hasil, dan tindak lanjut.
2. [ ] Klik **Simpan Draft** atau tunggu autosave.
3. [ ] Pastikan query berikut belum menghasilkan record aktif untuk nomor laporan uji.

```sql
SELECT cr.*
FROM u646470441_ServicePlanBRA.calibration_records cr
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = cr.report_id
WHERE r.report_number = 'UJI-B16-LPK-001'
  AND cr.reversed_at IS NULL;
```

## 5. Pengujian finalisasi

1. [ ] Lengkapi baris pertama dengan hasil `Memenuhi` dan tindak lanjut `Selesai`.
2. [ ] Tambahkan baris kedua dengan hasil `Tidak memenuhi` dan tindak lanjut `Kalibrasi ulang`.
3. [ ] Klik **Simpan Laporan**.
4. [ ] Pastikan pesan menyebut jumlah alat yang masuk ke Register Kalibrasi.
5. [ ] Buka **Preventive Maintenance → Kalibrasi Alat Ukur**.
6. [ ] Pastikan kedua baris langsung tampil tanpa reload manual.
7. [ ] Pastikan identifikasi, alat, merk/type, lokasi, rencana, pelaksanaan, metode, hasil, tindak lanjut, nomor laporan, dan pemeriksa sesuai.
8. [ ] Pastikan KPI alat terdaftar, hasil memenuhi, dan perlu tindak lanjut ikut berubah.

## 6. Verifikasi database

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    cr.calibration_id,
    cr.report_item_position,
    cr.period_month,
    l.location_name,
    cr.instrument_name,
    cr.identification_no,
    cr.brand_type,
    cr.planned_date,
    cr.performed_date,
    cr.execution_type,
    cr.calibration_result,
    cr.follow_up_status,
    cr.notes,
    prepared.full_name AS dibuat_oleh,
    checked.full_name AS diperiksa_oleh,
    cr.reversed_at
FROM u646470441_ServicePlanBRA.calibration_records cr
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = cr.report_id
JOIN u646470441_ServicePlanBRA.locations l
    ON l.location_id = cr.location_id
JOIN u646470441_ServicePlanBRA.users prepared
    ON prepared.user_id = cr.prepared_by
JOIN u646470441_ServicePlanBRA.users checked
    ON checked.user_id = cr.checked_by
WHERE r.report_number = 'UJI-B16-LPK-001'
ORDER BY cr.report_item_position;
```

Hasil yang diharapkan: tepat dua record aktif dengan `report_item_position` berbeda.

## 7. Pengujian validasi

1. [ ] Ketik lokasi yang tidak ada di Master Lokasi; finalisasi harus ditolak.
2. [ ] Ketik pembuat atau pemeriksa yang tidak ada pada Master Personel; finalisasi harus ditolak.
3. [ ] Kosongkan nama, identifikasi, merk/type, rencana, pelaksanaan, metode, hasil, atau tindak lanjut; finalisasi harus ditolak.
4. [ ] Gunakan nomor identifikasi yang sama pada dua baris dalam satu laporan; finalisasi harus ditolak.
5. [ ] Ubah pilihan hasil atau metode melalui request manual menjadi nilai yang tidak dikenal; backend harus menolak.
6. [ ] Pastikan tidak ada record setengah jadi setelah validasi gagal.

## 8. Pengujian retry

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Buka kembali Riwayat Laporan.
3. [ ] Pastikan laporan dan register tidak bertambah menjadi duplikat.
4. [ ] Jalankan query berikut; jumlah harus tetap sesuai jumlah baris laporan.

```sql
SELECT COUNT(*) AS jumlah_record
FROM u646470441_ServicePlanBRA.calibration_records cr
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = cr.report_id
WHERE r.report_number = 'UJI-B16-LPK-001';
```

## 9. Pengujian void

1. [ ] Void laporan dari **Riwayat Laporan** menggunakan akun yang memiliki izin approval.
2. [ ] Pastikan seluruh record laporan hilang dari tab Kalibrasi Alat Ukur.
3. [ ] Pastikan `reversed_at` dan `reversed_by` telah terisi.
4. [ ] Ulangi void; tidak boleh ada perubahan ganda atau error baru.

```sql
SELECT
    r.report_number,
    r.status,
    cr.calibration_id,
    cr.reversed_at,
    cr.reversed_by
FROM u646470441_ServicePlanBRA.calibration_records cr
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = cr.report_id
WHERE r.report_number = 'UJI-B16-LPK-001';
```

## 10. Pengujian otomatis

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\report_calibration_integration.php
```

Hasil acuan Batch 16: **13 passed**. Total regression integrasi Batch 1–16: **171 passed**.

## 11. Hasil pengujian pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B16-01 | Referensi lokasi dan personel | NOT RUN |  |
| INT-B16-02 | Pilihan alat lama dan autofill | NOT RUN |  |
| INT-B16-03 | Draft tidak membuat register | NOT RUN |  |
| INT-B16-04 | Finalisasi membuat seluruh baris | NOT RUN |  |
| INT-B16-05 | Data tampil pada tab Kalibrasi | NOT RUN |  |
| INT-B16-06 | KPI kalibrasi diperbarui | NOT RUN |  |
| INT-B16-07 | Validasi data referensi dan baris | NOT RUN |  |
| INT-B16-08 | Retry tidak menduplikasi | NOT RUN |  |
| INT-B16-09 | Void menonaktifkan register | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.

Batch berikutnya hanya dilanjutkan setelah skenario kritis Batch 16 disetujui pengguna.
