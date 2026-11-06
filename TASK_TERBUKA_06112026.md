Berikut versi teks untuk dicross-check tim per **06 November 2026**.  
Dari 19 task pada daftar backlog, **5 task telah selesai dikerjakan dan terverifikasi penuh**, sedangkan **14 task lainnya masih terbuka/membutuhkan input klien**.

---

- [ ] 1. `PROD-01` — Finalisasi laporan Update Dump Truck  
   Dibutuhkan: file asli XLSX “Update Dumtruck 30.10.2026”, nama laporan final, susunan sheet/kolom, aturan warna dan status, periode, lokasi, serta contoh tiap status.  
   PIC: Widya.  
   Cross-check: jumlah unit/status sama dengan aplikasi dan setiap downtime terhubung ke WO.

- [ ] 2. `EXP-01` — Ekspor seluruh menu operasional  
   Dibutuhkan: daftar menu wajib ekspor, format CSV/XLSX/PDF, urutan kolom, filter, pola nama file, serta pembatasan data berdasarkan role.  
   PIC: Widya dan pemilik masing-masing modul.  
   Cross-check: jumlah baris ekspor sama dengan tabel terfilter dan tidak ada data sensitif bocor.

- [ ] 3. `SPB-02` — Aturan print SPB  
   Dibutuhkan: keputusan apakah hanya SPB Approved yang boleh dicetak atau Draft juga boleh dengan watermark; teks watermark, status lain yang boleh dicetak, tanda tangan, dan format halaman.  
   PIC: Widya.  
   Cross-check: Draft tidak dapat dianggap sebagai dokumen resmi.

- [ ] 4. `SPB-03` — Hubungan spare part dengan SPB, WO, PM, dan unit  
   Dibutuhkan: contoh transaksi nyata berisi nomor SPB, WO/PM, unit, SKU, qty, UOM, requester, PIC, status, dan waktu permintaan.  
   PIC: Dany/Fadly dan Planner.  
   Cross-check: hubungan dapat ditelusuri dua arah dari WO/PM ke SPB dan sebaliknya.

- [x] 5. `PART-01` — Klasifikasi master part  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: 1.681 master part telah diimpor dan terdaftar di database `parts` dari gudang Dany & Fadly dengan SKU unik, technical name, nama umum, controlled authenticity (`GENUINE`, `NON_GENUINE`, `UNVERIFIED`), brand, kelompok part (`part_group`), base UOM, transaction UOM, dan status aktif. Staging impor dan audit log tersimpan pada tabel `part_import_staging` (1.332 record tervalidasi).  
   *Verifikasi*: Lolos automated test `tests/client_feedback_parts_uom_fulfilment.php` (22/22 PASS).

- [ ] 6. `PART-02` — Laporan part belum terpenuhi  
   Dibutuhkan: definisi awal/akhir aging, penggunaan hari kalender atau kerja, kalender libur, PIC yang ditampilkan, status aktif, serta SLA overdue.  
   PIC: Logistic Head/Dany.  
   Cross-check: hitungan aging dan sisa qty sama dengan perhitungan manual.

- [x] 7. `PART-03` — Konversi drum/pail ke base UOM  
   **STATUS: SELESAI (02 November 2026)**  
   *Implementasi*: Engine konversi [core/UomConversionEngine.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/core/UomConversionEngine.php) aktif untuk konversi satuan kemasan ke base unit liter/kg (Drum 200L, Drum 209L spesifik vendor PT Surya Mandiri Makmur, Pail 20L, Drum 180Kg grease). Transaksi SPB mengabadikan snapshot faktor pada `purchase_request_items` (`conversion_factor_snapshot`, `base_quantity`, `transaction_uom`) sehingga histori transaksi lama terlindungi dari perubahan master.  
   *Verifikasi*: Lolos automated test `tests/client_feedback_parts_uom_fulfilment.php` (22/22 PASS).

- [ ] 8. `PART-04` — Perbaikan grid dan scroll master part  
   Dibutuhkan: screenshot/video masalah, perangkat, browser, resolusi, zoom, kolom prioritas, dan pilihan tampilan mobile.  
   PIC: pengguna logistik dan Widya.  
   Cross-check: tidak ada kolom terpotong, header tetap terlihat, dan filter tetap dapat digunakan.

- [ ] 9. `PM-01` — Revisi jadwal PM  
   Dibutuhkan: field yang boleh diedit, field yang dikunci, daftar alasan revisi, role yang boleh merevisi, aturan approval, dan contoh nilai sebelum/sesudah.  
   PIC: Samsul/Planner.  
   Cross-check: hanya field yang diizinkan berubah dan riwayat before/after tercatat.

- [ ] 10. `PM-02` — Pergeseran forecast Long Standby/Breakdown  
    Dibutuhkan: definisi Long Standby, sumber status unit, periode kejadian, meter aktual, interval service, daily usage, dan contoh perhitungan manual.  
    PIC: Samsul/Planner dan Equipment Head.  
    Cross-check: estimasi tanggal dapat bergeser tetapi target meter tidak berubah.

- [ ] 11. `PM-04` — Generate jadwal bulan berikutnya  
    Dibutuhkan: horizon generate, cut-off meter, unit/lokasi yang dicakup, interval, meter terakhir, daily usage, aturan data kosong, serta role preview/publish.  
    PIC: Samsul/Planner.  
    Cross-check: proses kedua tidak membuat jadwal ganda dan unit yang dilewati memiliki alasan.

- [x] 12. `PM-06` — Koreksi nama unit, nomor lambung, dan kategori Mercy  
    **STATUS: SELESAI (02 November 2026)**  
    *Implementasi*: Dinormalisasi melalui [scripts/migrate_normalize_assets_samsul.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/migrate_normalize_assets_samsul.php). Seluruh 367 unit telah memiliki nomor lambung resmi dan kategori valid: Unit Mercy masuk ke `Dump Truck`, unit `CS-41001` & `RM-02/03` (`RM-41002`) masuk ke `Reclaimer Spreader`, unit `WT-41002` masuk ke `Water Truck`, `PM 03` masuk ke `Prime Mover`, `TR 4` masuk ke `Light Vehicle`, dan `SD 06` masuk ke `Vibro Compactor`. Seluruh pemetaan tervalidasi di [material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv).  
    *Verifikasi*: Lolos automated test `tests/client_feedback_unit_and_mechanic_status.php` (19/19 PASS).

- [ ] 13. `WO-01` — Update progres dan multi-mekanik  
    Dibutuhkan: daftar mekanik aktif, mekanik lead/anggota, aturan perubahan PIC, status progres, aturan notifikasi, dan contoh WO multi-PIC.  
    PIC: Dheny/Fajar dan mekanik pilot.  
    Cross-check: seluruh PIC melihat WO dan muncul pada detail, timeline, serta ekspor.

- [ ] 14. `WO-02` — Mekanik menerima dan mengisi WO harian  
    Dibutuhkan: akun mekanik, role, lokasi, unit/WO pilot, perangkat, shift, field input minimum, periode pilot, dan PIC support.  
    PIC: Dheny/Fajar dan HR/Admin.  
    Cross-check: mekanik dapat login, menerima WO, mengisi progres, mengunggah bukti, dan melihat hasil setelah login ulang.

- [ ] 15. `WO-03` — Rekapitulasi harian WO  
    Dibutuhkan: contoh format laporan, cut-off hari, timezone, kolom wajib, aturan satu baris per WO/per hari, filter, dan format ekspor.  
    PIC: Dheny/Fajar dan Widya.  
    Cross-check: total per status sama dengan timeline sumber.

- [ ] 16. `DOC-01` — Video demo mekanik  
    Dibutuhkan: UI pilot final, akun demo, unit/WO demo, alur rekaman, durasi, bahasa, resolusi, tempat penyimpanan, dan reviewer.  
    PIC: Brian dan Dheny/Fajar.  
    Cross-check: versi aplikasi dan tanggal tercantum serta tidak ada data/kredensial sensitif.

- [x] 17. `DATA-01` — Normalisasi master aset  
    **STATUS: SELESAI (02 November 2026)**  
    *Implementasi*: Seluruh 367 aset di tabel `assets` telah dinormalisasi; 0 aset memiliki hull number kosong. Lokasi telah dipetakan (Yard KM 12, WUR EW, RWI, Matting Board, Lahat). Sebanyak 270 record mutasi/normalisasi dicatat pada `input_revision_audit` (`entity_type = 'ASSET'`, `action = 'NORMALIZE_SAMSUL'`, `actor_user_id = 3`). File pemetaan [material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv) (368 baris) telah tervalidasi Samsul Bahri (Planner) dengan status `VALIDATED`.  
    *Verifikasi*: Lolos automated test `tests/client_feedback_unit_and_mechanic_status.php` (19/19 PASS).

- [x] 18. `DATA-02` — Personel aktif/nonaktif  
    **STATUS: SELESAI (02 November 2026)**  
    *Implementasi*: User ID 3 diperbarui menjadi `Samsul Bahri (Planner)` dengan role Maintenance Planner. Sebanyak 17 personil mekanik operasional PT BRA aktif didaftarkan/diaktifkan (`is_active = 1`). Personil nonaktif (`is_active = 0`) diblokir dari pembuatan WO baru (`INACTIVE_PERSONNEL`, HTTP 422) di backend [api/work_orders.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/api/work_orders.php) dan difilter pada modal WO di [dashboard.view.php](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/dashboard.view.php) serta [scripts/dashboard.js](file:///c:/Users/KANZ/Documents/Kanz/Proyekan/ServicePlan-BRA/scripts/dashboard.js). Seluruh riwayat mekanik pada 443 Work Order lama tetap terjaga utuh tanpa ada yang terhapus.  
    *Verifikasi*: Lolos automated test `tests/client_feedback_unit_and_mechanic_status.php` (19/19 PASS).

- [ ] 19. `DATA-03` — Rekonsiliasi seluruh data import  
    Dibutuhkan per file: nama batch, file/versi, pemilik, periode, lokasi, sheet, jumlah record harapan, primary key, aturan duplikat, field wajib, dan total kontrol.  
    PIC: pemilik setiap file sumber.  
    Cross-check: total sumber sama dengan valid + invalid + duplicate/skipped dan total kontrol sesuai sumber.

---

### Prioritas Pengumpulan Tindak Lanjut:

1. **Keputusan Widya**: `PROD-01`, `EXP-01`, `SPB-02`, `WO-03`.
2. **Data & Alur Samsul/Planner**: `PM-01`, `PM-02`, `PM-04`.
3. **Data & Keputusan Logistik**: `SPB-03`, `PART-02`, `PART-04`.
4. **Data Mekanik & Adopsi Lapangan (HRD / Dheny / Fajar)**: `WO-01`, `WO-02`, `DOC-01`.
5. **Manifest Seluruh File Import**: `DATA-03`.
