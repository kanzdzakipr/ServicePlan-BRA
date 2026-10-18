# Panduan Pengujian Batch 17 — BAPE ke Riwayat Pengiriman Unit

Batch 17 mengintegrasikan form **Penyerahan A2B kepada Ekspedisi (BAPE)** dengan Master Asset, Master Personel, riwayat pengiriman unit, dan tabel `asset_shipments` di Laragon.

> BAPE mencatat penyerahan unit kepada ekspedisi. BAPE tidak mengubah lokasi aktif, status, atau HM/KM unit. Perubahan lokasi resmi tetap dilakukan melalui BAST.

## 1. Hasil yang diharapkan

- Pengirim dipilih dari Master Personel aktif.
- Setiap baris unit dipilih dari Master Asset.
- Satuan otomatis `Unit`, jumlah otomatis `1`, dan jumlah kondisi mulai dari `0`.
- Ekspedisi yang pernah digunakan menjadi pilihan pada BAPE berikutnya; alamat dan kontak ikut terisi.
- Draft tidak membuat riwayat pengiriman.
- Finalisasi membuat satu record pengiriman per unit.
- Riwayat BAPE tampil pada tab **Lokasi & GPS** di detail unit.
- Lokasi aktif, status, dan HM/KM unit tidak berubah.
- Retry tidak menggandakan record dan void menonaktifkan record aktif.

## 2. Persiapan data

Jalankan query berikut di HeidiSQL untuk memilih unit dan personel uji:

```sql
SELECT asset_id, asset_code, category, status, current_location_id, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE is_active = 1
ORDER BY asset_code
LIMIT 10;

SELECT user_id, full_name
FROM u646470441_ServicePlanBRA.users
WHERE is_active = 1
ORDER BY full_name;
```

Data contoh:

| Data | Nilai contoh |
|---|---|
| Nomor BAPE | `UJI-B17-BAPE-001` |
| Pihak ekspedisi | `PT Ekspedisi Uji` |
| Alamat | `Pekanbaru` |
| Kontak/konfirmasi | `0812-0000-0017` |
| Nomor polisi angkutan | `BM 1717 QA` |
| Kontrak angkutan | `KONTRAK-UJI-B17` |

Catat lokasi, status, dan HM/KM unit sebelum pengujian.

## 3. Pengujian otomatisasi form

1. [ ] Buka **Laporan & Form**.
2. [ ] Cari **Penyerahan A2B kepada Ekspedisi** atau `BAPE`.
3. [ ] Klik **Isi Form**.
4. [ ] Isi nomor BAPE dan tanggal penyerahan.
5. [ ] Pilih pengirim dari daftar personel database.
6. [ ] Isi pihak ekspedisi baru, alamat, kontak, nomor polisi, dan kontrak angkutan.
7. [ ] Pada baris barang/unit, pilih unit dari Master Asset.
8. [ ] Pastikan satuan menjadi `Unit`, jumlah menjadi `1`, dan kolom Baik/Rusak/Kurang menjadi `0` bila sebelumnya kosong.
9. [ ] Isi tepat satu kondisi dengan nilai `1`, misalnya Baik `1`, Rusak `0`, Kurang `0`.
10. [ ] Setelah laporan pertama final, buat BAPE baru dan pilih ekspedisi yang sama.
11. [ ] Pastikan alamat serta kontak ekspedisi terisi otomatis.

## 4. Pengujian Draft

1. [ ] Isi header dan satu baris unit.
2. [ ] Klik **Simpan Draft** atau tunggu autosave.
3. [ ] Pastikan query berikut menghasilkan `0`.

```sql
SELECT COUNT(*) AS jumlah_pengiriman
FROM u646470441_ServicePlanBRA.asset_shipments s
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = s.report_id
WHERE r.report_number = 'UJI-B17-BAPE-001';
```

## 5. Pengujian finalisasi

1. [ ] Lengkapi semua field wajib.
2. [ ] Gunakan satu unit berkondisi Baik.
3. [ ] Tambahkan unit kedua dan isi kondisi Rusak jika tersedia.
4. [ ] Klik **Simpan Laporan**.
5. [ ] Pastikan pesan menyebut jumlah unit yang masuk ke riwayat pengiriman.
6. [ ] Buka **Master Asset**, cari unit, lalu buka detail unit.
7. [ ] Pilih tab **Lokasi & GPS**.
8. [ ] Pastikan terdapat entri **Diserahkan ke ekspedisi** yang menampilkan BAPE, tanggal, asal, status, kondisi, kendaraan, dan pengirim.
9. [ ] Pastikan lokasi aktif unit tetap sama seperti sebelum BAPE.
10. [ ] Pastikan status serta HM/KM juga tidak berubah.

## 6. Verifikasi database Laragon

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    s.shipment_id,
    s.report_item_position,
    s.asset_id,
    l.location_name AS lokasi_asal,
    s.bape_number,
    s.shipment_date,
    u.full_name AS pengirim,
    s.carrier_name,
    s.carrier_address,
    s.carrier_contact,
    s.transport_plate,
    s.transport_contract,
    s.unit_condition,
    s.shipment_status,
    s.notes,
    s.reversed_at
FROM u646470441_ServicePlanBRA.asset_shipments s
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = s.report_id
JOIN u646470441_ServicePlanBRA.assets a
    ON a.asset_id = s.asset_id
LEFT JOIN u646470441_ServicePlanBRA.locations l
    ON l.location_id = s.origin_location_id
JOIN u646470441_ServicePlanBRA.users u
    ON u.user_id = s.sender_user_id
WHERE r.report_number = 'UJI-B17-BAPE-001'
ORDER BY s.report_item_position;
```

Hasil yang diharapkan: jumlah record sama dengan jumlah unit terisi, `shipment_status` bernilai `IN_TRANSIT` untuk kondisi Baik/Rusak, dan semua `reversed_at` masih `NULL`.

Verifikasi unit tidak berubah:

```sql
SELECT asset_id, status, current_location_id, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE asset_id IN ('GANTI-DENGAN-ID-UNIT-1', 'GANTI-DENGAN-ID-UNIT-2');
```

## 7. Pengujian validasi

1. [ ] Ketik unit yang tidak ada pada Master Asset; finalisasi harus ditolak.
2. [ ] Ketik pengirim yang tidak ada pada Master Personel; finalisasi harus ditolak.
3. [ ] Pilih unit yang sama pada dua baris; finalisasi harus ditolak.
4. [ ] Isi jumlah unit selain `1`; finalisasi harus ditolak.
5. [ ] Isi satuan selain `Unit` atau `Pcs`; finalisasi harus ditolak.
6. [ ] Isi Baik `1` dan Rusak `1` untuk satu unit; finalisasi harus ditolak karena total kondisi melebihi jumlah.
7. [ ] Biarkan seluruh kondisi `0`; finalisasi harus ditolak.
8. [ ] Pastikan kegagalan validasi tidak meninggalkan record pengiriman parsial.

## 8. Pengujian retry dan void

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Pastikan jumlah record BAPE tidak bertambah.
3. [ ] Void laporan melalui **Riwayat Laporan** memakai akun yang berwenang.
4. [ ] Pastikan riwayat BAPE hilang dari detail unit.
5. [ ] Pastikan `reversed_at` dan `reversed_by` terisi.
6. [ ] Pastikan lokasi, status, dan HM/KM unit tetap tidak berubah.
7. [ ] Ulangi void; tidak boleh ada perubahan ganda.

```sql
SELECT r.report_number, r.status, s.asset_id, s.reversed_at, s.reversed_by
FROM u646470441_ServicePlanBRA.asset_shipments s
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = s.report_id
WHERE r.report_number = 'UJI-B17-BAPE-001';
```

## 9. Pengujian otomatis

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\report_asset_shipment_integration.php
```

Hasil acuan Batch 17: **15 passed**. Total regression integrasi Batch 1–17: **186 passed**.

## 10. Hasil pengujian pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B17-01 | Referensi unit dan personel | NOT RUN |  |
| INT-B17-02 | Autofill baris unit | NOT RUN |  |
| INT-B17-03 | Penyimpanan dan autofill ekspedisi | NOT RUN |  |
| INT-B17-04 | Draft tidak membuat pengiriman | NOT RUN |  |
| INT-B17-05 | Finalisasi membuat seluruh baris | NOT RUN |  |
| INT-B17-06 | Riwayat tampil pada detail unit | NOT RUN |  |
| INT-B17-07 | Lokasi, status, dan HM tidak berubah | NOT RUN |  |
| INT-B17-08 | Validasi backend | NOT RUN |  |
| INT-B17-09 | Retry tidak menduplikasi | NOT RUN |  |
| INT-B17-10 | Void menonaktifkan riwayat | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.

Batch berikutnya hanya dilanjutkan setelah skenario kritis Batch 17 disetujui pengguna.
