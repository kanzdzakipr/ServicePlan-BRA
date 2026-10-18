# Panduan Pengujian Batch 20 — Condition Monitoring Ban, Grease, dan Aki

Batch 20 memindahkan penyimpanan langsung pada menu **Condition Monitoring** dari penyimpanan browser (`localStorage`) ke database MySQL Laragon.

## Hasil yang diharapkan

- Inspeksi **Ban**, **Grease**, dan **Aki** tersimpan permanen di database.
- Data tetap sama setelah halaman dimuat ulang atau dibuka dari browser lain.
- Ringkasan kondisi dan tab **Riwayat** langsung membaca record database terbaru.
- Status `Aman`, `Perhatian`, atau `Kritis` dihitung dan divalidasi kembali oleh backend.
- Data selalu terkait dengan unit pada Master Asset dan pengguna yang sedang login.
- Pengguna tanpa izin `tires.write` tidak dapat menyimpan perubahan.
- Data 1.221 inspeksi ban lama tetap dipertahankan.

## Persiapan

1. [ ] Pastikan Apache dan MySQL Laragon aktif.
2. [ ] Buka aplikasi lokal melalui `http://localhost/ServicePlan-BRA/dashboard.html`.
3. [ ] Login dengan akun yang memiliki izin baca dan tulis Condition Monitoring.
4. [ ] Buka **Condition Monitoring**, lalu cari satu unit yang mudah dikenali.
5. [ ] Catat kode unit tersebut untuk dipakai pada query database.

## A. Pengujian inspeksi Ban

1. [ ] Buka detail unit, lalu pilih tab **Ban**.
2. [ ] Pilih salah satu posisi ban, misalnya `FL`.
3. [ ] Isi tread depth `7.5`, tekanan `110`, kondisi `Baik`, pola `Merata`, dan rekomendasi `Monitor`.
4. [ ] Klik **Simpan inspeksi**.
5. [ ] Pastikan muncul nomor record dengan awalan `CM-TIRE-` dan keterangan tersimpan di database Laragon.
6. [ ] Pastikan posisi tersebut berstatus **Perhatian**, karena tread berada di antara 3,2–8,5 mm.
7. [ ] Muat ulang halaman dan buka kembali unit/posisi yang sama.
8. [ ] Pastikan nilai `7.5 mm` dan `110 PSI` tetap tampil.
9. [ ] Buka tab **Riwayat** dan pastikan record **Ban** terbaru muncul.

Validasi negatif:

1. [ ] Pilih kondisi `Baik`, kosongkan tread depth, lalu simpan; sistem harus menolak.
2. [ ] Isi tread di bawah `3.2`; hasil harus **Kritis**.
3. [ ] Pilih kondisi `DG`, `Retak`, `Benjol`, atau `Sobek`; hasil harus **Kritis**.

Query verifikasi:

```sql
SELECT
    t.tire_inspection_id,
    t.asset_id,
    t.tire_position,
    t.tread_depth_mm,
    t.air_pressure_psi,
    t.physical_condition,
    t.wear_pattern,
    t.recommended_action,
    t.status_level,
    u.full_name AS inspector,
    t.inspected_at
FROM u646470441_ServicePlanBRA.tire_inspections t
LEFT JOIN u646470441_ServicePlanBRA.users u ON u.user_id = t.inspected_by
WHERE t.asset_id = 'KODE-UNIT-UJI'
ORDER BY t.inspected_at DESC, t.tire_inspection_id DESC
LIMIT 10;
```

## B. Pengujian Grease

1. [ ] Pilih tab **Grease** pada unit yang sama.
2. [ ] Isi HM aktual `1095`, HM grease terakhir `1000`, dan interval `100`.
3. [ ] Isi jumlah `4.5 kg` dan pilih `Lithium EP-2`.
4. [ ] Tandai sebagian titik pelumasan, lalu isi catatan.
5. [ ] Klik **Simpan regreasing**.
6. [ ] Pastikan nomor record berawalan `CM-GREASE-`.
7. [ ] Pastikan status menjadi **Perhatian**, karena pemakaian sudah mencapai 95% interval.
8. [ ] Muat ulang halaman dan pastikan HM, jumlah, jenis grease, titik pelumasan, dan catatan tetap tampil.
9. [ ] Pastikan record **Grease** muncul pada tab **Riwayat**.

Validasi negatif:

1. [ ] Isi HM aktual lebih kecil daripada HM grease terakhir; sistem harus menolak.
2. [ ] Isi interval `0`; sistem harus menolak.
3. [ ] Isi selisih HM sama dengan atau lebih besar dari interval; hasil harus **Kritis**.

Query verifikasi:

```sql
SELECT
    g.grease_log_id,
    g.asset_id,
    g.current_hm,
    g.last_grease_hm,
    g.interval_hm,
    g.quantity_kg,
    g.grease_type,
    g.lubrication_points,
    g.status_level,
    g.notes,
    u.full_name AS inspector,
    g.inspected_at
FROM u646470441_ServicePlanBRA.grease_logs g
LEFT JOIN u646470441_ServicePlanBRA.users u ON u.user_id = g.inspected_by
WHERE g.asset_id = 'KODE-UNIT-UJI'
ORDER BY g.inspected_at DESC, g.grease_log_id DESC
LIMIT 10;
```

## C. Pengujian Aki

1. [ ] Pilih tab **Aki** pada unit yang sama.
2. [ ] Isi tegangan `12.4 V`, CCA `75%`, merek, tipe, dan tanggal pemasangan yang tidak melebihi tanggal hari ini.
3. [ ] Pilih terminal `Korosi ringan`, elektrolit `Normal`, dan casing `Baik`.
4. [ ] Klik **Simpan inspeksi aki**.
5. [ ] Pastikan nomor record berawalan `CM-BATTERY-`.
6. [ ] Pastikan hasil menjadi **Perhatian**.
7. [ ] Muat ulang halaman dan pastikan seluruh nilai aki tetap tampil.
8. [ ] Pastikan record **Aki** muncul pada tab **Riwayat**.

Validasi negatif:

1. [ ] Isi CCA lebih dari `100%`; sistem harus menolak.
2. [ ] Isi tanggal pemasangan di masa depan; backend harus menolak.
3. [ ] Isi tegangan di bawah `12.0 V` atau CCA di bawah `60%`; hasil harus **Kritis**.
4. [ ] Pilih casing `Retak` atau `Bocor`; hasil harus **Kritis**.

Query verifikasi:

```sql
SELECT
    b.battery_log_id,
    b.asset_id,
    b.battery_brand,
    b.battery_type,
    b.voltage_val,
    b.cca_percent,
    b.installation_date,
    b.terminal_condition,
    b.electrolyte_level,
    b.case_condition,
    b.status_level,
    b.notes,
    u.full_name AS inspector,
    b.inspected_at
FROM u646470441_ServicePlanBRA.battery_logs b
LEFT JOIN u646470441_ServicePlanBRA.users u ON u.user_id = b.inspected_by
WHERE b.asset_id = 'KODE-UNIT-UJI'
ORDER BY b.inspected_at DESC, b.battery_log_id DESC
LIMIT 10;
```

## D. Pengujian persistensi dan role

1. [ ] Simpan masing-masing satu record Ban, Grease, dan Aki.
2. [ ] Logout, tutup browser, lalu login kembali.
3. [ ] Pastikan ketiga record masih tampil pada unit yang sama.
4. [ ] Login dari browser/perangkat lain dan pastikan data yang sama dapat dibaca.
5. [ ] Login sebagai role dengan `tires.read` tetapi tanpa `tires.write`; data boleh dilihat, tetapi penyimpanan harus ditolak dengan HTTP `403`.
6. [ ] Login sebagai role dengan cakupan lokasi terbatas; unit dan riwayat di lokasi lain tidak boleh terlihat.

## E. Pengujian otomatis

Jalankan:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\condition_monitoring_integration.php
```

Pengujian otomatis memakai transaksi dan melakukan rollback, sehingga tidak menambah data uji permanen. Pengujian mencakup insert ketiga domain, klasifikasi status, referensi unit/pengguna, checklist JSON, validasi nilai, serta pemeriksaan bahwa jumlah data lama tidak berubah setelah rollback.

## Catatan hasil pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B20-01 | Simpan dan muat ulang inspeksi Ban | NOT RUN |  |
| INT-B20-02 | Simpan dan muat ulang Grease | NOT RUN |  |
| INT-B20-03 | Simpan dan muat ulang Aki | NOT RUN |  |
| INT-B20-04 | Riwayat database tampil pada unit | NOT RUN |  |
| INT-B20-05 | Validasi nilai dan status otomatis | NOT RUN |  |
| INT-B20-06 | Persisten setelah logout/browser lain | NOT RUN |  |
| INT-B20-07 | Pembatasan izin dan lokasi | NOT RUN |  |
| INT-B20-08 | Data ban lama tetap utuh | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.
