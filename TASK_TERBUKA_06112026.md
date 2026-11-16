# Rekap Status Backlog & Revisi Website ServicePlan-BRA

Berikut pembaruan status pengerjaan per **16 November 2026**.  
Dari 19 task pada daftar backlog klien, **12 task telah selesai dikerjakan dan terverifikasi penuh dengan automated test**, **1 task selesai sebagian pada tahap integrasi data**, dan **6 task lainnya membutuhkan data riil/keputusan operasional lapangan**.

---

### Daftar Task dan Status Aktual

- [-] 1. `PROD-01` — Finalisasi laporan Update Dump Truck  
   **STATUS: TAHAP INTEGRASI DATA SELESAI (09–16 November 2026)**  
   *Implementasi*: 13 PDF operasional harian (26 Okt – 09 Nov 2026) telah ditranskripsikan ke 13 file Markdown terstruktur di [material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/). Sebanyak 986 snapshot tersimpan pada tabel `asset_operational_snapshots` dan 75 aset fisik unik berhasil diselaraskan ke Master Asset (termasuk koreksi pelat, penonaktifan duplikat lama `DT-00152` ke `DT-00056`, dan pencatatan unit breakdown).  
   *Verifikasi*: Lolos automated test [tests/dumptruck_update_20261109_integration.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/dumptruck_update_20261109_integration.php) (19/19 PASS).  
   *Sisa*: Menunggu contoh format final spreadsheet XLSX dari Bu Widya untuk generator template ekspor otomatis.

- [x] 2. `EXP-01` — Ekspor seluruh menu operasional  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Engine ekspor terpusat [core/ExportService.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/core/ExportService.php) dan endpoint [api/exports.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/exports.php) aktif dengan perlindungan keamanan anti-formula injection spreadsheet (menetralisir karakter `=`, `@`, `+`, `-`), encoding UTF-8 BOM, penamaan file terstruktur (`modul_periode_lokasi_timestamp.csv`), dan penegakan RBAC. Tombol ekspor terintegrasi pada Work Orders, PM Forecast Tracker, Part Belum Terpenuhi, Biaya, dan Valuasi.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_daily_recap_and_spb_print.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_daily_recap_and_spb_print.php) (21/21 PASS).

- [x] 3. `SPB-02` — Aturan cetak/print SPB  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Halaman cetak [api/spb_print.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/spb_print.php) menerapkan aturan dual-state watermarking: SPB berstatus Draft/Submitted otomatis memuat watermark `DRAFT · TIDAK RESMI`, sedangkan SPB berstatus `Approved`, `Ordered`, `Issued`, atau `Closed` dicetak bersih sebagai dokumen resmi. Tombol *"Cetak SPB"* ditambahkan di list riwayat, builder modal revisi, dan item detail spare part pada [scripts/dashboard.js](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.js).  
   *Verifikasi*: Lolos automated test [tests/client_feedback_daily_recap_and_spb_print.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_daily_recap_and_spb_print.php) (21/21 PASS).

- [ ] 4. `SPB-03` — Hubungan spare part dengan SPB, WO, PM, dan unit  
   Dibutuhkan: contoh transaksi nyata berisi nomor SPB, WO/PM, unit, SKU, qty, UOM, requester, PIC, status, dan waktu permintaan.  
   PIC: Dany/Fadly dan Planner.  
   Cross-check: hubungan dapat ditelusuri dua arah dari WO/PM ke SPB dan sebaliknya.

- [x] 5. `PART-01` — Klasifikasi master part  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: 1.681 master part telah diimpor dan terdaftar di database `parts` dari gudang Dany & Fadly dengan SKU unik, technical name, nama umum, controlled authenticity (`GENUINE`, `NON_GENUINE`, `UNVERIFIED`), brand, kelompok part (`part_group`), base UOM, transaction UOM, dan status aktif. Staging impor dan audit log tersimpan pada tabel `part_import_staging` (1.332 record tervalidasi).  
   *Verifikasi*: Lolos automated test [tests/client_feedback_parts_uom_fulfilment.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_parts_uom_fulfilment.php) (22/22 PASS).

- [x] 6. `PART-02` — Laporan part belum terpenuhi  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Endpoint [api/unfulfilled_parts.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/unfulfilled_parts.php) menyajikan daftar part aktif yang belum terpenuhi (`base_quantity > fulfilled_quantity`) lengkap dengan perhitungan aging hari kalender dan aging hari kerja (Senin–Jumat), kuantitas outstanding dalam base UOM, SKU, unit, lokasi, requester, dan PIC pemenuhan. Modal UI interaktif *"Part belum terpenuhi"* dan tombol unduh CSV terpasang di toolbar Logistik [scripts/dashboard.js](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.js).  
   *Verifikasi*: Lolos automated test [tests/client_feedback_daily_recap_and_spb_print.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_daily_recap_and_spb_print.php) (21/21 PASS).

- [x] 7. `PART-03` — Konversi drum/pail ke base UOM  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: Engine konversi [core/UomConversionEngine.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/core/UomConversionEngine.php) aktif untuk konversi satuan kemasan ke base unit liter/kg (Drum 200L, Drum 209L spesifik vendor PT Surya Mandiri Makmur, Pail 20L, Drum 180Kg grease). Transaksi SPB mengabadikan snapshot faktor pada `purchase_request_items` (`conversion_factor_snapshot`, `base_quantity`, `transaction_uom`) sehingga histori transaksi lama terlindungi dari perubahan master.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_parts_uom_fulfilment.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_parts_uom_fulfilment.php) (22/22 PASS).

- [x] 8. `PART-04` — Perbaikan grid dan scroll master part  
   **STATUS: SELESAI (06 November 2026)**  
   *Implementasi*: Grid & layout katalog Master Part diperbaiki menyeluruh di [scripts/dashboard.css](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.css) dan [scripts/dashboard.js](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.js) dengan sticky header, horizontal scroll terkontrol, modal filter 1.681 item suku cadang, dan visual picker pada form Draft SPB.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_master_part_grid_ux.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_master_part_grid_ux.php) (17/17 PASS).

- [x] 9. `PM-01` — Revisi jadwal PM  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Fitur penyesuaian jadwal PM dengan alasan terstruktur terpasang di modal detail PM [scripts/dashboard.js](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.js) dan endpoint [api/pm_plans.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/pm_plans.php). Setiap perubahan mencatat alasan wajib (5–500 karakter), jenis penyesuaian, tanggal estimasi baru, actor, serta riwayat lengkap pada tabel `pm_forecast_adjustments`.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_pm_forecast.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_pm_forecast.php) (21/21 PASS).

- [x] 10. `PM-02` — Pergeseran forecast Long Standby/Breakdown  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Engine [core/PmForecast.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/core/PmForecast.php) dan API [api/pm_plans.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/pm_plans.php) mengimplementasikan aturan bisnis: unit berstatus Long Standby atau Breakdown dapat mengalami pergeseran estimasi tanggal (`revised_estimated_date`), namun target meter servis (`target_due_hm`) bersifat terkunci dan tidak berubah. Periode status dan sumber status (WO/laporan operasi) tercatat di audit log.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_pm_forecast.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_pm_forecast.php) (21/21 PASS).

- [x] 11. `PM-04` — Generate jadwal bulan berikutnya  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Generator jadwal otomatis [api/pm_plans.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/pm_plans.php) mendukung aksi `preview_next_month` dan `publish_next_month`. Menjalankan publish dua kali bersifat idempoten karena menggunakan kunci unik `generation_key` (`YYYY-MM:asset_id`), sehingga unit yang sama tidak pernah diduplikasi. Tombol preview dan publish tersedia di toolbar Preventive Maintenance.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_pm_forecast.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_pm_forecast.php) (21/21 PASS).

- [x] 12. `PM-06` — Koreksi nama unit, nomor lambung, dan kategori Mercy  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: Dinormalisasi melalui [scripts/migrate_normalize_assets_samsul.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/migrate_normalize_assets_samsul.php). Seluruh 367 unit telah memiliki nomor lambung resmi dan kategori valid (Mercy masuk ke Dump Truck, Reclaimer Spreader, Water Truck, Prime Mover, Light Vehicle, dan Vibro Compactor).  
   *Verifikasi*: Lolos automated test [tests/client_feedback_unit_and_mechanic_status.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_unit_and_mechanic_status.php) (19/19 PASS).

- [ ] 13. `WO-01` — Update progres dan multi-mekanik  
   Dibutuhkan: daftar mekanik aktif per regu/shift, aturan mekanik lead vs anggota, dan simulasi penugasan WO multi-PIC di lapangan.  
   PIC: Dheny/Fajar dan mekanik pilot.  
   Cross-check: seluruh PIC melihat WO dan muncul pada detail, timeline, serta ekspor.

- [ ] 14. `WO-02` — Mekanik menerima dan mengisi WO harian  
   Dibutuhkan: akun login mekanik per lokasi/shift, unit/WO pilot, SOP perangkat mobile, dan periode uji coba pilot.  
   PIC: Dheny/Fajar dan HR/Admin.  
   Cross-check: mekanik dapat login, menerima WO, mengisi progres, mengunggah bukti, dan melihat hasil setelah login ulang.

- [x] 15. `WO-03` — Rekapitulasi harian WO  
   **STATUS: SELESAI (16 November 2026)**  
   *Implementasi*: Backend [api/work_order_daily_recap.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/work_order_daily_recap.php) menyajikan rekapitulasi WO per tanggal pilihan dengan summary (Total, Aktif, Waiting Part), timeline progres terkini, dan jumlah SPB terbuka. Antarmuka UI modal interaktif *"Rekap Harian WO"* lengkap dengan datepicker dan tombol ekspor CSV disematkan pada header Manajemen Work Order [dashboard.view.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/dashboard.view.php).  
   *Verifikasi*: Lolos automated test [tests/client_feedback_daily_recap_and_spb_print.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_daily_recap_and_spb_print.php) (21/21 PASS).

- [ ] 16. `DOC-01` — Video demo mekanik  
   Dibutuhkan: rekaman video demo operasional mekanik. Storyboard dan panduan skenario teks sudah lengkap di [material/PANDUAN_VIDEO_DEMO_MEKANIK.md](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/PANDUAN_VIDEO_DEMO_MEKANIK.md).  
   PIC: Brian dan Dheny/Fajar.

- [x] 17. `DATA-01` — Normalisasi master aset  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: Seluruh 367 aset di tabel `assets` telah dinormalisasi; 0 aset memiliki hull number kosong. Lokasi telah dipetakan (Yard KM 12, WUR EW, RWI, Matting Board, Lahat). Sebanyak 270 record mutasi/normalisasi dicatat pada `input_revision_audit`.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_unit_and_mechanic_status.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_unit_and_mechanic_status.php) (19/19 PASS).

- [x] 18. `DATA-02` — Personel aktif/nonaktif  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: User ID 3 diperbarui menjadi `Samsul Bahri (Planner)`. Sebanyak 17 personil mekanik operasional PT BRA aktif didaftarkan (`is_active = 1`). Personil nonaktif diblokir dari pembuatan WO baru (`INACTIVE_PERSONNEL`, HTTP 422) di backend [api/work_orders.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/work_orders.php). Seluruh riwayat mekanik pada 443 Work Order lama tetap terjaga utuh.  
   *Verifikasi*: Lolos automated test [tests/client_feedback_unit_and_mechanic_status.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/tests/client_feedback_unit_and_mechanic_status.php) (19/19 PASS).

- [ ] 19. `DATA-03` — Rekonsiliasi seluruh data import  
   Dibutuhkan per file: manifest nama batch, file sumber, jumlah record harapan, primary key, aturan duplikat, dan saldo kontrol dari masing-masing pemilik file.  
   PIC: pemilik setiap file sumber.

---

### Ringkasan Status Saat Ini

* **Selesai & Terverifikasi (12 Task)**: `PART-01`, `PART-02`, `PART-03`, `PART-04`, `PM-01`, `PM-02`, `PM-04`, `PM-06`, `WO-03`, `EXP-01`, `SPB-02`, `DATA-01`, `DATA-02`.
* **Selesai Sebagian (1 Task)**: `PROD-01` (data 13 PDF harian dump truck selesai dinormalisasi dan terintegrasi; menunggu format template XLSX dari Bu Widya).
* **Menunggu Input Klien / Lapangan (6 Task)**: `SPB-03` (contoh transaksi riil part), `WO-01` & `WO-02` (pilot mekanik lapangan), `DOC-01` (rekaman video demo fisik), `DATA-03` (manifest file import historis), `PROD-01` (template final XLSX).
