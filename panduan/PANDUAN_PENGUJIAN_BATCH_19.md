# Panduan Pengujian Batch 19 — Laporan Cutting Bit ke Condition Monitoring

Batch 19 mengintegrasikan **Laporan Cutting Bit (CB-RM)** dengan tabel `cutting_bit_logs` di Laragon dan menu **Condition Monitoring → Pengecekan Cutting Bit**.

## Hasil yang diharapkan

- Kode unit dipilih dari Master Asset.
- Mesin/tipe unit dan lokasi terisi otomatis setelah unit dipilih.
- Tahun dan periode terisi otomatis jika masih kosong.
- Pembuat, pemeriksa, dan penyetuju dipilih dari Master Personel.
- Target total, kebutuhan dasar, safety stock, dan total kebutuhan dihitung otomatis.
- Selisih dan realisasi setiap baris dihitung otomatis.
- Draft belum membuat catatan pemakaian di `cutting_bit_logs`.
- Finalisasi membuat satu catatan database untuk setiap tanggal pemakaian.
- Catatan final langsung muncul pada unit terkait di Condition Monitoring.
- Retry tidak menggandakan data dan void menonaktifkan data dari register aktif.

## Data uji yang disarankan

| Data | Nilai contoh |
|---|---|
| Kode unit | `RM-00001` atau unit recycler/milling aktif lainnya |
| Material | `Laterit/Tanah Padat` |
| Periode | `Oktober 2026` |
| Tahun | `2026` |
| Kebutuhan harian | `30` pcs |
| Target harian | `250` meter |
| Hari kerja | `10` |
| Safety stock | `10`% |
| Baris 1 | 12 Oktober, planning `30`, actual `32` |
| Baris 2 | 13 Oktober, planning `30`, actual `28` |

Hasil perhitungan header yang benar:

- Target total: `2.500` meter.
- Kebutuhan dasar: `300` pcs.
- Safety stock minimum: `30` pcs.
- Total kebutuhan: `330` pcs.

## Pengujian pilihan database dan otomatisasi

1. [ ] Buka **Laporan & Form → Laporan Cutting Bit**.
2. [ ] Pada **Kode unit**, pilih unit `RM-...` dari pilihan database.
3. [ ] Pastikan **Mesin/unit** otomatis berisi kategori dan model aset.
4. [ ] Pastikan **Lokasi** otomatis mengikuti lokasi Master Asset, tetapi tetap dapat dipilih dari Master Lokasi bila pekerjaan dilakukan di lokasi lain.
5. [ ] Pastikan tahun dan periode otomatis terisi jika sebelumnya kosong.
6. [ ] Pilih material dari opsi yang tersedia.
7. [ ] Pilih pembuat, pemeriksa, dan penyetuju dari Master Personel.
8. [ ] Isi kebutuhan harian, target harian, hari kerja, dan persentase safety stock.
9. [ ] Pastikan empat field hasil perhitungan terisi otomatis dan tidak dapat diedit manual.

## Pengujian tabulasi harian

1. [ ] Isi bulan, tanggal, planning, dan actual pada dua baris.
2. [ ] Pastikan `selisih = actual - planning`.
3. [ ] Pastikan `realisasi = actual / planning × 100%`.
4. [ ] Isi planning `0` dan actual lebih dari `0` pada satu baris uji tambahan.
5. [ ] Pastikan realisasi tetap kosong dan baris dipertahankan sebagai anomali.
6. [ ] Coba tanggal yang tidak valid, misalnya `31 April`; form harus menolak finalisasi.
7. [ ] Coba tanggal yang sama dua kali; form harus menolak duplikasi.

## Pengujian draft

1. [ ] Tunggu autosave atau simpan sebagai draft.
2. [ ] Catat nomor laporan yang dipakai.
3. [ ] Jalankan query berikut dan pastikan hasilnya `0`.

```sql
SELECT COUNT(*)
FROM u646470441_ServicePlanBRA.cutting_bit_logs c
JOIN u646470441_ServicePlanBRA.report_records r ON r.report_id = c.report_id
WHERE r.report_number = 'NOMOR-LAPORAN-UJI';
```

## Pengujian finalisasi dan menu tujuan

1. [ ] Klik **Simpan Laporan**.
2. [ ] Pastikan pesan sukses menyebut jumlah hari serta total actual/planning.
3. [ ] Buka **Condition Monitoring**.
4. [ ] Cari unit yang dipilih pada bagian **Pengecekan Cutting Bit**.
5. [ ] Pastikan kolom pemakaian menampilkan teks `Laporan actual / planning` terbaru.
6. [ ] Klik **Periksa**, kemudian buka tab **Riwayat**.
7. [ ] Pastikan setiap tanggal laporan tampil sebagai riwayat **Cutting Bit** dengan nomor laporan, actual, planning, dan selisih.

## Verifikasi database Laragon

Ganti nomor laporan pada query berikut:

```sql
SELECT
    r.report_number,
    r.status,
    c.report_item_position,
    c.asset_id,
    c.activity_date,
    l.location_name,
    c.material,
    c.planning_qty,
    c.actual_qty,
    c.variance_qty,
    c.realization_percent,
    c.daily_requirement,
    c.daily_target_m,
    c.working_days,
    c.total_target_m,
    c.safety_stock_minimum,
    c.total_requirement,
    prepared.full_name AS dibuat_oleh,
    checked.full_name AS diperiksa_oleh,
    approved.full_name AS disetujui_oleh,
    c.reversed_at
FROM u646470441_ServicePlanBRA.cutting_bit_logs c
JOIN u646470441_ServicePlanBRA.report_records r ON r.report_id = c.report_id
LEFT JOIN u646470441_ServicePlanBRA.locations l ON l.location_id = c.location_id
LEFT JOIN u646470441_ServicePlanBRA.users prepared ON prepared.user_id = c.prepared_by
LEFT JOIN u646470441_ServicePlanBRA.users checked ON checked.user_id = c.checked_by
LEFT JOIN u646470441_ServicePlanBRA.users approved ON approved.user_id = c.approved_by
WHERE r.report_number = 'NOMOR-LAPORAN-UJI'
ORDER BY c.report_item_position;
```

Yang harus diperiksa:

1. [ ] Jumlah record sama dengan jumlah baris tanggal pada laporan.
2. [ ] `asset_id` dan `location_id` mengarah ke master yang dipilih.
3. [ ] Tanggal Indonesia telah berubah menjadi format database `YYYY-MM-DD`.
4. [ ] Planning, actual, selisih, dan realisasi sesuai hasil hitung.
5. [ ] Ketiga personel approval tersimpan sebagai ID pengguna.
6. [ ] `report_id` menghubungkan record ke laporan final.

## Validasi negatif backend

1. [ ] Unit yang tidak ada pada Master Asset harus ditolak.
2. [ ] Unit non-recycler/milling harus ditolak.
3. [ ] Lokasi yang tidak ada pada Master Lokasi harus ditolak.
4. [ ] Personel approval yang tidak ada pada Master Personel harus ditolak.
5. [ ] Material di luar pilihan resmi harus ditolak.
6. [ ] Nilai hasil hitung header yang dimanipulasi harus ditolak.
7. [ ] Selisih atau realisasi baris yang dimanipulasi harus ditolak.
8. [ ] Tanggal tidak valid dan tanggal duplikat harus ditolak.

## Retry dan void

1. [ ] Muat ulang setelah finalisasi; jumlah baris database tidak boleh bertambah.
2. [ ] Void laporan dari **Riwayat Laporan** menggunakan akun berwenang.
3. [ ] Pastikan catatan laporan hilang dari register aktif Condition Monitoring.
4. [ ] Jalankan query verifikasi dan pastikan `reversed_at` serta `reversed_by` terisi.
5. [ ] Ulangi void; tidak boleh terjadi pembalikan ganda.

## Pengujian otomatis

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests\report_cutting_bit_integration.php
```

Hasil acuan Batch 19: **15 passed**. Total regresi Batch 1–19: **217 passed**.

## Catatan hasil pengguna

| ID | Skenario | Hasil | Catatan |
|---|---|---|---|
| INT-B19-01 | Pilihan unit, lokasi, dan personel | NOT RUN |  |
| INT-B19-02 | Autofill identitas dan kalkulasi header | NOT RUN |  |
| INT-B19-03 | Kalkulasi tabulasi harian | NOT RUN |  |
| INT-B19-04 | Draft tidak membuat register | NOT RUN |  |
| INT-B19-05 | Finalisasi masuk ke database | NOT RUN |  |
| INT-B19-06 | Data tampil di Condition Monitoring | NOT RUN |  |
| INT-B19-07 | Validasi negatif | NOT RUN |  |
| INT-B19-08 | Retry dan void | NOT RUN |  |

Status: `PASS`, `FAIL`, `BLOCKED`, atau `NOT RUN`.
