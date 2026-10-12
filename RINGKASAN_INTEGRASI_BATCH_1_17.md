# Ringkasan Integrasi Batch 1–17

Dokumen ini merangkum integrasi laporan yang telah diselesaikan sebelum Batch 18.

| Batch | Laporan/Form | Tujuan integrasi | Perubahan utama |
|---:|---|---|---|
| 1 | BHW-IN | Spare Part & Logistik | Finalisasi menambah stok Master Part dan membuat transaksi barang masuk. |
| 2 | BHW-OUT | Spare Part & Logistik | Finalisasi mengurangi stok dan membuat transaksi barang keluar dengan validasi saldo. |
| 3 | P2H Excavator/Roller | Condition Monitoring dan Master Asset | Membuat inspeksi, memperbarui HM/KM, serta menyesuaikan status unit berdasarkan hasil pemeriksaan. |
| 4 | LHO | Produktivitas dan Master Asset | Membuat log operasi, menghitung jam kerja/HM operasi, dan memperbarui HM terakhir unit. |
| 5 | LHO–BBM | Fuel Management | Pemakaian BBM dari LHO masuk ke ledger Fuel tanpa menggandakan log manual. |
| 6 | Maintenance Board | Preventive Maintenance | Membuat atau menautkan rencana PM berdasarkan unit, HM, interval, dan target service. |
| 7 | Repair & Overhaul | Work Order | Membuat Work Order dari laporan kerusakan/perbaikan dengan unit, urgensi, PIC, parts, dan solusi. |
| 8 | SPB | Spare Part & Logistik | Membuat Purchase Request dan item permintaan yang terhubung dengan Work Order serta Master Part. |
| 9 | PPB | Pengadaan | Membuat Purchase Order dari SPB, termasuk vendor, harga, PPN, item, dan jadwal penyerahan. |
| 10 | Monitoring Pengadaan & Parts Weekly | Pengadaan dan stok | Memperbarui status SPB/PPB dan membuat snapshot stok mingguan tanpa mengubah stok aktual. |
| 11 | BAPP | Penerimaan barang | Menambah stok berdasarkan barang baik, mencatat rusak/kurang, dan memperbarui status PPB/SPB. |
| 12 | Bukti Kirim/Terima Intern | Spare Part & Logistik | Transaksi `Kirim` mengurangi stok dan `Terima` menambah stok antar lokasi internal. |
| 13 | SPPU Yard & SPPU-006 | Spare Part & Logistik | Membuat permintaan parts urgent yang terhubung dengan Work Order, unit, analisa, dan Master Part. |
| 14 | BAST MDE-1 | Master Asset | Membuat riwayat perpindahan serta memperbarui lokasi dan HM unit secara aman. |
| 15 | MDE-02 | Inspeksi & P2H | Membuat inspeksi teknis, memperbarui HM, dan mengubah status menjadi `INSPEKSI` bila bermasalah. |
| 16 | LPK Kalibrasi | Preventive Maintenance | Membuat register kalibrasi alat ukur lengkap dengan hasil, tindak lanjut, lokasi, dan personel. |
| 17 | BAPE | Riwayat Pengiriman Master Asset | Mencatat penyerahan unit kepada ekspedisi tanpa mengubah lokasi aktif, status, atau HM/KM unit. |

## Otomatisasi yang berlaku

- Unit, lokasi, personel, Work Order, SPB, PPB, part, kategori, model, dan ekspedisi mengambil referensi database Laragon.
- Pemilihan referensi melengkapi field terkait secara otomatis, misalnya model unit, lokasi, HM, jabatan, satuan, harga, dan stok.
- Draft hanya menyimpan pekerjaan sementara dan tidak mengubah tabel operasional.
- Finalisasi menjalankan integrasi lintas-menu dalam satu transaksi database.
- Retry dibuat idempotent agar tidak menghasilkan data atau saldo ganda.
- Void membalik atau menonaktifkan integrasi dengan tetap menjaga perubahan lanjutan yang sudah terjadi.
- Cakupan lokasi dan hak akses pengguna diterapkan pada data referensi dan hasil menu.

## Tabel integrasi utama

| Area | Tabel utama |
|---|---|
| Stok | `parts`, `inventory_transactions` |
| Inspeksi | `inspections`, `report_inspection_integrations` |
| Operasi dan BBM | `report_operation_logs`, `fuel_logs`, `report_fuel_integrations` |
| Preventive Maintenance | `pm_plans`, `report_pm_integrations`, `calibration_records` |
| Work Order | `work_orders`, `report_work_order_integrations` |
| Pengadaan | `purchase_requests`, `purchase_orders`, item dan ledger integrasinya |
| Perpindahan unit | `asset_movements`, `report_asset_movement_integrations` |
| Pengiriman ekspedisi | `asset_shipments` |

## Status pengujian

- Batch 1–17 telah melewati **186 pengujian integrasi otomatis**.
- Seluruh perubahan telah diuji terhadap database Laragon.
- Panduan rinci Batch 1–13 tersedia pada `PANDUAN_PENGUJIAN_BATCH_1_13.md`.
- Panduan Batch 14–17 tersedia pada file panduan batch masing-masing.
