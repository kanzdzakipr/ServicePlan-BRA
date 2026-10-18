# Batch 7 — Regression gate dan serah terima (17 Oktober 2026)

**Status: PARTIAL.** Tes otomatis di bawah lulus, tetapi browser/mobile, pengujian dua role nyata, dan skenario dengan data LHO/biaya belum tuntas. Ini bukan persetujuan rilis. Semua pemeriksaan DB dilakukan pada MySQL lokal `C:\laragon2\data\mysql-8.4`; tidak ada dump yang diimpor ulang atau data pengguna yang dihapus.

## Bukti otomatis yang dijalankan

| Area | Bukti | Hasil |
| --- | --- | --- |
| Keamanan unit/statik/HTTP | `security_unit.php`, `security_static.php`, `security_http_smoke.php` | 22 + 39 + 5 PASS; IDOR lintas akun belum diuji |
| Aset/onboarding/pencarian | `asset_onboarding_unit.php`, `asset_search_integration.php` | PASS; pencarian DB baca-saja |
| WO, SPB, part | `work_order_ticket_fields.php`, `work_order_support_integration.php`, `report_spb_integration.php`, `report_bhwin_integration.php`, `report_ppb_integration.php`, `report_sppu_integration.php` | 11 + 9 + 17 + 19 + 10 + 10 PASS; integrasi memakai rollback |
| KPI, produktivitas, LHO | `verify_kpi_math.php`, `productivity_integration.php`, `batch6_readonly_audit.php`, `report_lho_integration.php` | 22 + 48 + 9 + 18 PASS |
| Notifikasi | `notification_audience_unit.php`, `notification_audience_integration.php`, `notification_integration.php` | PASS; integrasi 14 PASS dan rollback |
| CSV kontrak | `contract_csv_integration.php` | PASS; impor/ekspor/duplikasi dalam transaksi rollback |
| Condition monitoring | `condition_monitoring_integration.php` | 19 PASS dengan rollback |
| Browser | `contract_csv_browser_smoke.js` | **FAIL/inconclusive:** browser tidak menghasilkan hasil dalam 60 detik; belum ada bukti UI |

Skrip `tests/cost_integration.php`, `tests/master_asset_integration.php`, dan `tests/pm_hull_multi_pic_integration.php` **tidak** dijalankan pada database pengguna karena menghapus/mengubah data atau tidak menjamin rollback. Tiga skrip smoke browser kini gagal secara eksplisit bila tidak menghasilkan hasil; kode keluar 0 tanpa PASS tidak lagi diterima sebagai keberhasilan.

## Kondisi Laragon2 (query baca-saja)

| Tabel | Baris saat audit |
| --- | ---: |
| `assets` | 282 |
| `work_orders` | 366 |
| `work_order_timeline` | 368 |
| `telematics_logs` | 3 |
| `report_operation_logs` | 0 |
| `asset_cost_transactions` | 0 |
| `contract_assignments` | 0 |

Kolom WO `trouble_at`, `is_forced_operation`, `assigned_mechanics_json`, `before_photo_url`, `maintenance_type` dan kolom aset `hull_number`, `current_operator_name`, `service_interval_val`, `next_service_due_hm_km` ada. Ketiadaan LHO/biaya berarti pengujian kartu vs detail dan reversal **memerlukan data uji baru yang disepakati**, bukan dapat dinyatakan PASS dari data saat ini.

## Matriks penerimaan

`Otomatis lulus` di kolom bukti berarti hanya irisan tes tercantum yang lulus; status akhir tetap menunggu uji manual sesuai acceptance criteria.

| Kebutuhan | Bukti otomatis / panduan | Status akhir dan gap |
| --- | --- | --- |
| M3 Master Aset | onboarding unit, pencarian DB; [Revisi 1](PANDUAN_PENGUJIAN_REVISI_1.md) | PARTIAL — simpan/edit dan lintas menu/role nyata belum diuji |
| M4 siklus WO | 11 tes tiket; [Revisi 3](PANDUAN_PENGUJIAN_REVISI_3.md), [Batch 5](PANDUAN_PENGUJIAN_BATCH_5_17102026.md) | PARTIAL — buka/tutup WO dan detail browser belum terbukti |
| M5 kebutuhan part/layanan | 9 WO support + 17 SPB; [Revisi 4](PANDUAN_PENGUJIAN_REVISI_4.md) | PARTIAL — alur peran logistik/planner belum diuji |
| M6 riwayat part | 19 BHW-IN/OUT; [Revisi 5](PANDUAN_PENGUJIAN_REVISI_5.md) | PARTIAL — tampilan histori unit/WO manual belum diuji |
| M7 notifikasi | audience unit/integrasi + 14 tes; panduan notifikasi | PARTIAL — badge, refresh, klik detail lintas akun belum diuji |
| M8 produktivitas | 22 KPI + 48 produktivitas + 9 audit; [Batch 6](PANDUAN_PENGUJIAN_BATCH_6_17102026.md) | PARTIAL — LHO riil 0, downtime overlap belum diputuskan |
| M9 CSV | CSV round-trip rollback; [Revisi 8](PANDUAN_PENGUJIAN_REVISI_8.md) | PARTIAL — smoke browser gagal/tidak menghasilkan bukti |
| M10 biaya | 9 audit termasuk izin bawaan; [Batch 6](PANDUAN_PENGUJIAN_BATCH_6_17102026.md) | PARTIAL — ledger 0, reversal dan izin HTTP nyata belum diuji; sumber PO/SPPU belum dipilih |
| Outline 1 / GLB-D / GLB-E | WO 11, KPI 22, [Batch 5](PANDUAN_PENGUJIAN_BATCH_5_17102026.md) | PARTIAL — foto/PIC/timeline/cetak WO–JO perlu browser |
| Outline 2 / GLB-C | onboarding unit + [Batch 3](PANDUAN_PENGUJIAN_BATCH_3_17102026.md) | PARTIAL — unggah, persistensi, PM 11 kolom lintas login belum diuji |
| Outline 3 / GLB-A / GLB-B | pencarian DB + [Batch 2](PANDUAN_PENGUJIAN_BATCH_2_17102026.md), [Batch 4](PANDUAN_PENGUJIAN_BATCH_4_17102026.md) | PARTIAL — rename audit, overlay pencarian, mobile dan role perlu browser |

## Urutan uji bersama untuk menutup gate

1. Jalankan panduan revisi 1, 3, 4, 5, 8 secara berurutan pada unit uji yang tidak dipakai tim lain. Catat ID unit, WO, SPB, dan hasil sebelum/sesudah; jangan hapus data produksi.
2. Jalankan [Batch 5](PANDUAN_PENGUJIAN_BATCH_5_17102026.md) untuk cetak WO/JO satu ID dan inspector KPI; lalu [Batch 6](PANDUAN_PENGUJIAN_BATCH_6_17102026.md) setelah ada LHO dan transaksi biaya uji.
3. Gunakan dua akun nyata: satu berwenang dan satu tanpa izin; tambah uji akun lokasi lain. Verifikasi API 403/404, dashboard, refresh/login ulang, foto, notifikasi, dan biaya.
4. Ulang smoke browser desktop dan audit mobile 320–430 px dengan akun uji; catat screenshot, console, status HTTP. Skrip browser yang tidak mencetak `PASS` belum dianggap lulus.
5. Untuk M10, tentukan sumber biaya otomatis secara terpisah: PO disetujui, SPPU final, keduanya dengan deduplikasi, atau tetap manual. Jangan aktifkan otomatisasi sebelum diputuskan.

Gate Batch 7 baru **PASS** setelah semua gap kritis punya bukti dan pengguna menyetujui hasil akhir. Isi hasil pada dokumen ini atau kirim ID/screenshot yang gagal; perbaikan dapat dilakukan tanpa mengulang dump database.
