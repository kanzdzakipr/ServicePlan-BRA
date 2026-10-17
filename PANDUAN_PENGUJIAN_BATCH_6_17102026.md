# Pengujian Batch 6 — Rekonsiliasi produktivitas dan biaya

Status: **PARTIAL** sampai uji transaksi biaya dan peran di browser disetujui pengguna. Tes otomatis: 48 tes produktivitas dan 9 audit baca-saja lulus. Database Laragon2 dipertahankan; migrasi WO hanya menambah kolom yang belum ada, tabel ledger dibuat kosong (0 transaksi), tanpa impor dump/seed/reset sandi.

## A. Produktivitas

1. Login dan buka Produktivitas. Pilih satu unit dan bulan yang punya telematika, LHO, serta WO. Catat unit, periode, kategori, dan lokasi.
2. Bandingkan `api/productivity.php?period=Januari%202026&asset_id=ID_UNIT` dengan tabel telematika dan rekonsiliasi. `category=Dump%20Truck` dapat digabungkan; hasil di luar kategori harus kosong. Pengguna berlokasi terbatas tidak boleh melihat unit lokasi lain.
3. LHO pembanding harus hanya menjumlah `report_operation_logs.work_hours` yang tidak dibalik pada **bulan dan unit yang sama**. Jika belum ada LHO, tampil `Belum ada LHO`, bukan nol. Jika LHO ada tetapi 0 jam, tampil 0.
4. Bandingkan total jam/fuel kartu dengan penjumlahan rincian periode yang sama. LHO detail tidak lagi dipotong diam-diam pada 500 baris.
5. Bandingkan downtime WO, PA/MA/UA/EU/MTBF/MTTR dengan inspector Batch 5. Catat jika beberapa WO tumpang-tindih waktunya: saat ini downtime dijumlah dari setiap WO, belum digabung sebagai interval; perlu keputusan aturan bisnis sebelum mengubah perhitungan historis.

## B. Biaya

1. Gunakan aset dan WO **milik aset yang sama**. Catat total awal dari menu Biaya dan `api/costs.php?asset_id=ID_UNIT`; pastikan daftar transaksi dan total aktif cocok.
2. Buat satu transaksi manual bernominal uji. Pastikan total aset dan total WO naik sebesar nominal itu, dengan pembulatan Rupiah benar. Kode biaya eksplisit yang sama tidak boleh membuat transaksi kedua.
3. Coba tautkan biaya ke WO milik aset lain: harus ditolak. Coba isi `location_id` palsu: lokasi transaksi tetap mengikuti lokasi master aset.
4. Balikkan transaksi dengan alasan. Total aktif aset/WO harus turun, tetapi baris tetap ada dengan `reversed_at`, `reversed_by`, dan alasan. Pembatalan kedua harus ditolak.
5. Login sebagai Mekanik Senior: `GET api/costs.php` harus ditolak (403). Logistic Head boleh baca tetapi tidak boleh POST. Planner boleh baca/tulis sesuai cakupan lokasi. Coba reversal transaksi dari unit lokasi lain: harus ditolak tanpa mengubah baris.

## Query audit baca-saja (HeidiSQL)

```sql
SELECT asset_id, period_month, SUM(actual_working_hours) W, SUM(working_hours) T
FROM telematics_logs WHERE asset_id = 'ID_UNIT' AND period_month = 'Januari 2026'
GROUP BY asset_id, period_month;

SELECT asset_id, SUM(work_hours) AS lho_hours
FROM report_operation_logs
WHERE asset_id = 'ID_UNIT' AND reversed_at IS NULL
  AND operation_date >= '2026-01-01' AND operation_date < '2026-02-01'
GROUP BY asset_id;

SELECT transaction_id, cost_code, asset_id, wo_id, amount, reversed_at, reversal_reason
FROM asset_cost_transactions WHERE asset_id = 'ID_UNIT' ORDER BY transaction_id DESC;
```

Catat hasil: filter periode/unit/kategori/lokasi [ ], data hilang vs nol [ ], angka kartu/detail [ ], biaya aktif/reversal [ ], role [ ], WO tumpang-tindih [ ]. Otomatisasi biaya dari PO/SPPU **belum diaktifkan**; pilihan sumber bisnisnya tetap menunggu keputusan terpisah. Jangan lanjut Batch 7 sebelum hasil uji bersama dikonfirmasi.
