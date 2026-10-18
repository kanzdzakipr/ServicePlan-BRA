# Panduan Pengujian Batch 14 — BAST MDE-1

Batch 14 mengintegrasikan form **Berita Acara Serah Terima Alat Berat (MDE-1)** dengan Master Asset dan riwayat perpindahan unit.

## 1. Hasil yang diharapkan

- Pemilihan **Nomor kode alat** menggunakan Master Asset Laragon.
- Jenis alat, model, lokasi asal, dan HM/OM terisi otomatis dari unit terpilih.
- Lokasi asal dan tujuan dipilih dari Master Lokasi.
- Draft tidak mengubah lokasi maupun HM unit.
- Finalisasi membuat tepat satu record `asset_movements`.
- Finalisasi memperbarui `assets.current_location_id` dan `assets.last_hm_km`.
- Retry tidak membuat perpindahan ganda.
- Void memulihkan lokasi dan HM jika belum ada perubahan lanjutan.
- Void mempertahankan kondisi terbaru jika unit sudah dipindahkan atau HM diperbarui lagi.

## 2. Persiapan data

Jalankan query berikut di HeidiSQL untuk memilih satu unit yang mempunyai lokasi aktif:

```sql
SELECT
    a.asset_id,
    a.asset_code,
    a.category,
    a.make_model,
    a.last_hm_km,
    a.current_location_id,
    l.location_name
FROM u646470441_ServicePlanBRA.assets a
JOIN u646470441_ServicePlanBRA.locations l
    ON l.location_id = a.current_location_id
WHERE a.is_active = 1
  AND l.is_active = 1
ORDER BY a.asset_code
LIMIT 20;
```

Pilih lokasi tujuan yang berbeda dari lokasi unit saat ini:

```sql
SELECT location_id, location_name, location_type
FROM u646470441_ServicePlanBRA.locations
WHERE is_active = 1
ORDER BY location_name;
```

Catat sebelum pengujian:

| Data | Nilai |
|---|---|
| Asset ID |  |
| Kode unit |  |
| Lokasi awal |  |
| HM/KM awal |  |
| Lokasi tujuan |  |
| Nomor BAST uji | `UJI-B14-BAST-001` |

## 3. Pengujian pilihan otomatis

1. [ ] Buka **Laporan & Form**.
2. [ ] Cari `MDE-1` atau **Berita Acara Serah Terima Alat Berat**.
3. [ ] Klik **Isi Form**.
4. [ ] Isi **Nomor BAST / urut** dengan nomor unik.
5. [ ] Pilih **Nomor kode alat** dari daftar database.
6. [ ] Pastikan **Jenis alat** mengikuti kategori Master Asset.
7. [ ] Pastikan **Merek / engine model** mengikuti model Master Asset.
8. [ ] Pastikan **Lokasi / project asal** mengikuti lokasi unit saat ini.
9. [ ] Pastikan **HM/OM saat serah terima** mengikuti HM/KM terakhir unit.
10. [ ] Pilih lokasi tujuan yang berbeda dari daftar Master Lokasi.

## 4. Pengujian Draft

1. [ ] Lengkapi tanggal, project, pihak penyerah, pihak penerima, dan jenis serah terima.
2. [ ] Tambahkan satu baris kelengkapan, jumlah, kondisi, dan keterangan.
3. [ ] Klik **Simpan Draft**.
4. [ ] Jalankan query kondisi unit dan pastikan lokasi serta HM belum berubah.

```sql
SELECT a.asset_id, a.last_hm_km, a.current_location_id, l.location_name
FROM u646470441_ServicePlanBRA.assets a
LEFT JOIN u646470441_ServicePlanBRA.locations l
    ON l.location_id = a.current_location_id
WHERE a.asset_id = 'GANTI_DENGAN_ASSET_ID';
```

5. [ ] Pastikan belum ada `asset_movements` dengan nomor BAST uji.

```sql
SELECT *
FROM u646470441_ServicePlanBRA.asset_movements
WHERE bast_number = 'UJI-B14-BAST-001';
```

## 5. Pengujian Finalisasi

1. [ ] Ubah HM/OM menjadi sama atau lebih besar dari HM/KM awal.
2. [ ] Klik **Simpan Laporan**.
3. [ ] Pastikan muncul pesan bahwa lokasi unit berhasil diperbarui.
4. [ ] Muat ulang dashboard dan cari unit pada Master Asset.
5. [ ] Pastikan lokasi unit berubah menjadi lokasi tujuan.
6. [ ] Pastikan HM/KM unit berubah menjadi nilai pada BAST.
7. [ ] Buka detail unit → tab **Lokasi & GPS**, lalu pastikan nomor BAST, rute asal–tujuan, tanggal, dan pembuat tampil pada riwayat perpindahan.
8. [ ] Jalankan query berikut dan pastikan hanya ada satu perpindahan.

```sql
SELECT
    am.movement_id,
    am.asset_id,
    origin.location_name AS lokasi_asal,
    destination.location_name AS lokasi_tujuan,
    am.bast_number,
    am.movement_date,
    am.requested_by
FROM u646470441_ServicePlanBRA.asset_movements am
LEFT JOIN u646470441_ServicePlanBRA.locations origin
    ON origin.location_id = am.from_location_id
JOIN u646470441_ServicePlanBRA.locations destination
    ON destination.location_id = am.to_location_id
WHERE am.bast_number = 'UJI-B14-BAST-001';
```

9. [ ] Periksa ledger integrasi:

```sql
SELECT
    r.report_number,
    r.status,
    i.movement_id,
    i.asset_id,
    i.previous_location_id,
    i.applied_location_id,
    i.previous_hm,
    i.applied_hm,
    i.reversed_at
FROM u646470441_ServicePlanBRA.report_asset_movement_integrations i
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = i.report_id
WHERE r.report_number = 'UJI-B14-BAST-001';
```

## 6. Pengujian validasi

1. [ ] Pilih unit lalu ganti lokasi asal menjadi lokasi lain; finalisasi harus ditolak.
2. [ ] Masukkan HM/OM lebih kecil dari HM/KM Master Asset; finalisasi harus ditolak.
3. [ ] Samakan lokasi tujuan dengan lokasi asal; finalisasi harus ditolak.
4. [ ] Ketik unit yang tidak ada pada Master Asset; finalisasi harus ditolak.
5. [ ] Kosongkan semua baris kelengkapan; finalisasi harus ditolak.
6. [ ] Gunakan kondisi kelengkapan di luar pilihan resmi; backend harus menolak.

## 7. Pengujian retry

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Buka riwayat laporan dan pastikan laporan hanya satu.
3. [ ] Pastikan query `asset_movements` tetap menghasilkan satu baris untuk nomor BAST tersebut.
4. [ ] Pastikan lokasi dan HM tidak berubah untuk kedua kalinya.

## 8. Pengujian Void

### Kondisi tanpa perpindahan lanjutan

1. [ ] Buka **Riwayat Laporan**.
2. [ ] Void laporan BAST uji.
3. [ ] Pastikan lokasi dan HM Master Asset kembali ke nilai sebelum finalisasi.
4. [ ] Pastikan record `asset_movements` buatan BAST tersebut sudah dihapus.
5. [ ] Pastikan `reversed_at` pada ledger integrasi telah terisi.
6. [ ] Ulangi void; tidak boleh ada perubahan atau error baru.

### Kondisi dengan perubahan lanjutan

1. [ ] Buat dan finalkan BAST uji kedua.
2. [ ] Lakukan perpindahan berikutnya atau pembaruan HM pada unit tersebut.
3. [ ] Void BAST uji kedua.
4. [ ] Pastikan lokasi dan HM terbaru tidak dikembalikan ke nilai lama.
5. [ ] Pastikan riwayat perpindahan dipertahankan untuk audit.

## 9. Pengujian otomatis

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\report_bast_mde1_integration.php
```

Hasil acuan Batch 14: **11 passed**. Total regression integrasi Batch 1–14: **146 passed**.

## 10. Hasil pengujian pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B14-01 | Pilihan dan autofill Master Asset | NOT RUN |  |
| INT-B14-02 | Draft tidak mengubah Master Asset | NOT RUN |  |
| INT-B14-03 | Finalisasi membuat perpindahan | NOT RUN |  |
| INT-B14-04 | Lokasi dan HM Master Asset diperbarui | NOT RUN |  |
| INT-B14-04A | Riwayat BAST tampil pada detail unit | NOT RUN |  |
| INT-B14-05 | Validasi lokasi asal/tujuan | NOT RUN |  |
| INT-B14-06 | Validasi HM menurun | NOT RUN |  |
| INT-B14-07 | Retry tidak menduplikasi | NOT RUN |  |
| INT-B14-08 | Void memulihkan perubahan aman | NOT RUN |  |
| INT-B14-09 | Void mempertahankan perubahan lanjutan | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.

Batch 15 hanya dilanjutkan setelah seluruh skenario kritis Batch 14 disetujui pengguna.
