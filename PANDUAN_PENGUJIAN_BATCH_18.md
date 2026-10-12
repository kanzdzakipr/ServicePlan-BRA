# Panduan Pengujian Batch 18 — SPL ke People & KPI

Batch 18 mengintegrasikan **Surat Perintah Lembur (SPL)** dengan menu **People & KPI → Evaluasi Maintenance Planner → Daftar SPL masuk untuk review planner** dan tabel `spl_overtime_records` di Laragon.

## Hasil yang diharapkan

- Nomor SPL menjadi identitas unik laporan.
- Lokasi dipilih dari Master Lokasi.
- Pembuat, pemberi instruksi, dan personel lembur dipilih dari Master Personel.
- Jabatan serta lokasi personel terisi otomatis.
- Durasi dihitung otomatis dari jam mulai dan selesai, termasuk pekerjaan melewati tengah malam.
- Draft tidak membuat register lembur.
- Finalisasi membuat satu record per personel dan langsung memperbarui daftar SPL di People & KPI.
- Retry tidak menggandakan record.
- Void menonaktifkan seluruh record SPL dari register aktif.

## Data uji

| Data | Contoh |
|---|---|
| Nomor SPL | `UJI-B18-SPL-001` |
| Tanggal dan periode | 12 Oktober 2026 |
| Jam personel 1 | `16:00`–`18:30` |
| Jam personel 2 | `22:00`–`01:00` |
| TTD mulai/selesai | Terverifikasi atau Belum diverifikasi |

Pilih personel dan lokasi dari query berikut:

```sql
SELECT u.user_id, u.full_name, r.role_name, l.location_name
FROM u646470441_ServicePlanBRA.users u
JOIN u646470441_ServicePlanBRA.roles r ON r.role_id = u.role_id
LEFT JOIN u646470441_ServicePlanBRA.locations l ON l.location_id = u.assigned_location_id
WHERE u.is_active = 1
ORDER BY u.full_name;

SELECT location_id, location_name
FROM u646470441_ServicePlanBRA.locations
WHERE is_active = 1
ORDER BY location_name;
```

## Pengujian form dan otomatisasi

1. [ ] Buka **Laporan & Form → Surat Perintah Lembur (Yard)**.
2. [ ] Isi nomor SPL, tanggal, dan periode pelaksanaan.
3. [ ] Pilih area/lokasi dari database.
4. [ ] Pilih pembuat dan pemberi instruksi dari Master Personel.
5. [ ] Tambahkan baris dan pilih nama personel.
6. [ ] Pastikan jabatan serta lokasi terisi otomatis.
7. [ ] Isi uraian pekerjaan, jam mulai, jam selesai, dan status TTD.
8. [ ] Pastikan durasi dan total jam-orang dihitung otomatis.
9. [ ] Uji jam `22:00`–`01:00`; hasil yang benar adalah `3` jam.

## Pengujian Draft

1. [ ] Simpan sebagai draft atau tunggu autosave.
2. [ ] Pastikan query berikut menghasilkan `0`.

```sql
SELECT COUNT(*)
FROM u646470441_ServicePlanBRA.spl_overtime_records s
JOIN u646470441_ServicePlanBRA.report_records r ON r.report_id = s.report_id
WHERE r.report_number = 'UJI-B18-SPL-001';
```

## Pengujian finalisasi

1. [ ] Lengkapi minimal satu personel dan klik **Simpan Laporan**.
2. [ ] Pastikan pesan finalisasi menyebut jumlah personel dan jam-orang.
3. [ ] Buka **People & KPI → Evaluasi Maintenance Planner**.
4. [ ] Pastikan SPL muncul pada **Daftar SPL masuk untuk review planner** tanpa reload manual.
5. [ ] Klik **Tinjau** dan periksa personel, pekerjaan, jam, TTD, pembuat, dan pemberi instruksi.

## Verifikasi database

```sql
SELECT
    r.report_number,
    r.status,
    s.spl_number,
    s.spl_date,
    s.period_start,
    s.period_end,
    l.location_name,
    employee.full_name AS personel,
    s.role_snapshot,
    s.start_time,
    s.end_time,
    s.duration_hours,
    s.activity_description,
    prepared.full_name AS dibuat_oleh,
    instructed.full_name AS instruksi_oleh,
    s.start_verified,
    s.end_verified,
    s.reversed_at
FROM u646470441_ServicePlanBRA.spl_overtime_records s
JOIN u646470441_ServicePlanBRA.report_records r ON r.report_id = s.report_id
JOIN u646470441_ServicePlanBRA.locations l ON l.location_id = s.location_id
JOIN u646470441_ServicePlanBRA.users employee ON employee.user_id = s.employee_user_id
JOIN u646470441_ServicePlanBRA.users prepared ON prepared.user_id = s.prepared_by
JOIN u646470441_ServicePlanBRA.users instructed ON instructed.user_id = s.instructed_by
WHERE r.report_number = 'UJI-B18-SPL-001'
ORDER BY s.report_item_position;
```

## Validasi negatif

1. [ ] Personel yang tidak ada pada Master Personel harus ditolak.
2. [ ] Jabatan yang diubah agar berbeda dari Master Personel harus ditolak.
3. [ ] Lokasi baris yang berbeda dari lokasi SPL harus ditolak.
4. [ ] Personel yang sama pada dua baris harus ditolak.
5. [ ] Durasi yang dimanipulasi agar berbeda dari jam mulai/selesai harus ditolak.
6. [ ] Durasi nol atau lebih dari 16 jam harus ditolak.
7. [ ] Tanggal akhir sebelum tanggal mulai harus ditolak.
8. [ ] Status verifikasi selain pilihan resmi harus ditolak backend.

## Retry dan void

1. [ ] Muat ulang setelah finalisasi; jumlah baris tidak boleh bertambah.
2. [ ] Void laporan melalui Riwayat Laporan dengan akun yang berwenang.
3. [ ] Pastikan SPL hilang dari daftar aktif People & KPI.
4. [ ] Pastikan `reversed_at` dan `reversed_by` terisi.
5. [ ] Ulangi void; tidak boleh ada pembalikan ganda.

## Pengujian otomatis

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\report_spl_overtime_integration.php
```

Hasil acuan Batch 18: **16 passed**. Total regresi Batch 1–18: **202 passed**.

## Catatan hasil pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B18-01 | Pilihan lokasi dan personel | NOT RUN |  |
| INT-B18-02 | Autofill jabatan dan lokasi | NOT RUN |  |
| INT-B18-03 | Perhitungan durasi dan jam-orang | NOT RUN |  |
| INT-B18-04 | Draft tidak membuat register | NOT RUN |  |
| INT-B18-05 | Finalisasi tampil di People & KPI | NOT RUN |  |
| INT-B18-06 | Detail review SPL sesuai | NOT RUN |  |
| INT-B18-07 | Validasi backend | NOT RUN |  |
| INT-B18-08 | Retry dan void | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.
