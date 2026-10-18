# Panduan Pengujian Batch 15 — MDE-02 ke Inspeksi & P2H

Batch 15 mengintegrasikan form **Kartu Pemeriksaan Alat Berat (MDE-02)** dengan menu **Condition Monitoring → Inspeksi & P2H**, tabel `inspections`, serta status dan HM/KM Master Asset.

## 1. Hasil yang diharapkan

- Unit, kategori, model, nomor seri, tahun, HM/OM, pemeriksa, dan penyetuju mengambil referensi database Laragon.
- Draft tidak membuat inspeksi dan tidak mengubah Master Asset.
- Finalisasi membuat tepat satu record `inspections` dan langsung menampilkannya pada Riwayat Inspeksi & P2H.
- Kondisi `Baik` atau `Normal` menghasilkan `PASS`; status unit tetap seperti sebelum pemeriksaan.
- Kondisi `Perlu perbaikan` atau `Tidak ada` menghasilkan `FAIL`; status unit menjadi `INSPEKSI`, kecuali status sebelumnya sudah `BREAKDOWN`.
- HM/KM Master Asset diperbarui dan HM yang menurun ditolak.
- Retry tidak membuat inspeksi ganda.
- Void membatalkan inspeksi dan memulihkan status/HM bila belum ada perubahan lanjutan.

## 2. Persiapan data

Pilih satu unit aktif yang bukan `ACCIDENT_HOLD` atau `INACTIVE`:

```sql
SELECT
    a.asset_id,
    a.asset_code,
    a.category,
    a.make_model,
    a.serial_number,
    a.year_manufacture,
    a.status,
    a.last_hm_km
FROM u646470441_ServicePlanBRA.assets a
WHERE a.is_active = 1
  AND a.status NOT IN ('ACCIDENT_HOLD', 'ACCIDENT HOLD', 'INACTIVE')
ORDER BY a.asset_code
LIMIT 20;
```

Pilih pemeriksa dan penyetuju aktif:

```sql
SELECT user_id, full_name, position, department
FROM u646470441_ServicePlanBRA.users
WHERE is_active = 1
ORDER BY full_name;
```

Catat kondisi awal:

| Data | Nilai |
|---|---|
| Asset ID |  |
| Kode unit |  |
| Status awal |  |
| HM/KM awal |  |
| Pemeriksa |  |
| Penyetuju |  |
| Nomor pemeriksaan | `UJI-B15-MDE02-001` |

## 3. Pengujian pilihan dan isian otomatis

1. [ ] Buka **Laporan & Form**.
2. [ ] Cari `MDE-02` atau **Kartu Pemeriksaan Alat Berat**.
3. [ ] Klik **Isi Form**.
4. [ ] Isi nomor pemeriksaan unik dan tanggal pemeriksaan.
5. [ ] Pilih **Nomor kode alat** dari daftar Master Asset.
6. [ ] Pastikan merek/model, jenis alat, tipe alat, nomor seri, tahun, dan HM/OM terisi sesuai unit terpilih.
7. [ ] Pilih **Diperiksa oleh** dan **Disetujui/diketahui oleh** dari Master Personel.
8. [ ] Pastikan nilai referensi dapat dipilih tanpa mengetik data baru yang belum ada di database.

## 4. Pengujian Draft

1. [ ] Isi sekurangnya satu baris dengan kelompok, uraian, kondisi, dan keterangan.
2. [ ] Klik **Simpan Draft** atau tunggu autosave.
3. [ ] Pastikan belum ada inspeksi dengan nomor uji.
4. [ ] Pastikan status dan HM/KM unit belum berubah.

```sql
SELECT inspection_id, asset_id, inspection_date, overall_result, payload_json
FROM u646470441_ServicePlanBRA.inspections
WHERE JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.id')) = 'UJI-B15-MDE02-001';

SELECT asset_id, status, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE asset_id = 'GANTI_DENGAN_ASSET_ID';
```

## 5. Pengujian PASS

1. [ ] Isi HM/OM sama dengan atau lebih besar dari HM/KM awal.
2. [ ] Isi semua baris yang dipakai dengan kondisi `Baik` atau `Normal`.
3. [ ] Klik **Simpan Laporan**.
4. [ ] Pastikan pesan finalisasi menyatakan laporan masuk ke Riwayat Inspeksi & P2H.
5. [ ] Buka **Condition Monitoring → Inspeksi & P2H**; laporan harus langsung tampil tanpa reload manual.
6. [ ] Cari kode unit atau nomor pemeriksaan.
7. [ ] Pastikan hasilnya `LULUS (PASS)`.
8. [ ] Pastikan HM/KM Master Asset mengikuti nilai pemeriksaan.
9. [ ] Pastikan status unit tidak menurun karena hasil PASS.

## 6. Pengujian FAIL

1. [ ] Buat MDE-02 kedua dengan nomor unik.
2. [ ] Pilih unit yang sama dan masukkan HM/OM yang tidak lebih kecil dari HM/KM terakhir.
3. [ ] Isi satu baris dengan kondisi `Perlu perbaikan` atau `Tidak ada` serta keterangan temuan.
4. [ ] Finalkan laporan.
5. [ ] Pastikan hasil pada Riwayat Inspeksi & P2H adalah `GAGAL (PERLU PEMERIKSAAN)`.
6. [ ] Pastikan status unit menjadi `INSPEKSI`; bila sebelumnya `BREAKDOWN`, status harus tetap `BREAKDOWN`.
7. [ ] Pastikan temuan tampil pada catatan/detail pemeriksaan.

## 7. Verifikasi database

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    i.inspection_id,
    i.asset_id,
    i.inspection_date,
    i.current_hm_km,
    i.overall_result,
    i.findings_summary,
    rii.previous_asset_status,
    rii.applied_asset_status,
    rii.previous_hm,
    rii.applied_hm,
    rii.reversed_at
FROM u646470441_ServicePlanBRA.report_inspection_integrations rii
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = rii.report_id
LEFT JOIN u646470441_ServicePlanBRA.inspections i
    ON i.inspection_id = rii.inspection_id
WHERE r.report_number LIKE 'UJI-B15-MDE02-%'
ORDER BY rii.integration_id DESC;
```

Verifikasi kondisi Master Asset:

```sql
SELECT asset_id, asset_code, status, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE asset_id = 'GANTI_DENGAN_ASSET_ID';
```

## 8. Pengujian validasi

1. [ ] Ubah HM/OM menjadi lebih kecil dari HM/KM Master Asset; finalisasi harus ditolak.
2. [ ] Ubah jenis alat, tipe/model, nomor seri, atau tahun agar berbeda dari Master Asset; finalisasi harus ditolak.
3. [ ] Ketik unit yang tidak terdaftar; finalisasi harus ditolak.
4. [ ] Ketik pemeriksa atau penyetuju yang tidak aktif/tidak terdaftar; finalisasi harus ditolak.
5. [ ] Kosongkan semua baris pemeriksaan; finalisasi harus ditolak.
6. [ ] Kosongkan kelompok, uraian, atau kondisi pada baris yang dipakai; finalisasi harus ditolak.

## 9. Pengujian retry dan void

### Retry

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Pastikan laporan hanya tampil satu kali pada Riwayat Inspeksi & P2H.
3. [ ] Pastikan ledger integrasi hanya mempunyai satu baris untuk laporan tersebut.
4. [ ] Pastikan HM dan status tidak diterapkan dua kali.

### Void tanpa perubahan lanjutan

1. [ ] Void laporan MDE-02 dari Riwayat Laporan.
2. [ ] Pastikan laporan hilang dari Riwayat Inspeksi & P2H.
3. [ ] Pastikan status dan HM unit kembali ke kondisi sebelum finalisasi.
4. [ ] Pastikan `reversed_at` pada ledger integrasi terisi.
5. [ ] Ulangi void; proses harus tetap aman dan tidak mengubah data lagi.

### Void setelah perubahan lanjutan

1. [ ] Buat dan finalkan MDE-02 baru.
2. [ ] Perbarui lagi status atau HM unit melalui transaksi berikutnya.
3. [ ] Void MDE-02 tersebut.
4. [ ] Pastikan status/HM terbaru tidak ditimpa oleh nilai lama.

## 10. Pengujian otomatis

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\report_mde02_integration.php
```

Hasil acuan Batch 15: **12 passed**. Total regression integrasi Batch 1–15: **158 passed**.

## 11. Hasil pengujian pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B15-01 | Referensi unit dan personel dari Laragon | NOT RUN |  |
| INT-B15-02 | Autofill spesifikasi unit dan HM/OM | NOT RUN |  |
| INT-B15-03 | Draft tidak mengubah data operasional | NOT RUN |  |
| INT-B15-04 | PASS masuk ke Riwayat Inspeksi & P2H | NOT RUN |  |
| INT-B15-05 | FAIL mengubah status menjadi INSPEKSI | NOT RUN |  |
| INT-B15-06 | Validasi HM dan data Master Asset | NOT RUN |  |
| INT-B15-07 | Validasi personel dan baris pemeriksaan | NOT RUN |  |
| INT-B15-08 | Retry tidak menduplikasi inspeksi | NOT RUN |  |
| INT-B15-09 | Void memulihkan data secara aman | NOT RUN |  |
| INT-B15-10 | Void mempertahankan perubahan lanjutan | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.

Batch berikutnya hanya dilanjutkan setelah skenario kritis Batch 15 disetujui pengguna.
