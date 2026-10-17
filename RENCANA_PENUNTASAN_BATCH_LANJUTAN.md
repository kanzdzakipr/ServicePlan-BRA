# Roadmap Eksekusi Penuntasan Fitur & Pengujian ServicePlan-BRA Per Batch

Dokumen ini memetakan sisa pekerjaan (*remaining gaps*) dari [rekap-revisi-tim-brian.md](file:///c:/Users/THIN/OneDrive/Documents/GitHub/ServicePlan-BRA/rekap-revisi-tim-brian.md) dan [implementation-plan-17102026.md](file:///c:/Users/THIN/OneDrive/Documents/GitHub/ServicePlan-BRA/implementation-plan-17102026.md) menjadi **4 Batch Eksekusi Terfokus**. Setiap batch dilengkapi dengan cakupan kode, data uji aman, panduan verifikasi, dan acceptance gate.

---

## Ringkasan Pembagian Batch

| Batch | Fokus Modul | Sasaran Utama | Jenis Pengujian |
| :---: | :--- | :--- | :--- |
| **Batch A** | **Work Order, JO & Logistik UI/UX** | Form WO 12 field, mobile tagger PIC, dokumen cetak dual-alias (WO vs JO), pengujian alur status WO dan request part/jasa | Browser/Mobile Smoke Test, API Mutasi & RBAC, Timeline Audit |
| **Batch B** | **Master Aset, Onboarding & PM 11 Kolom** | Form registrasi unit baru, galeri foto lightbox, berkas PDF JO, tabel PM 11 kolom, serta sinkronisasi reload | Browser Multi-screen, Upload Validation, DB Persistence |
| **Batch C** | **Finansial Biaya & Keputusan PO/SPPU** | Injeksi sampel ledger biaya per unit & WO, penguncian keputusan PO/SPPU, agregasi tanpa double count | Ledger Audit, Role Permission, Reversal Test |
| **Batch D** | **Produktivitas, LHO & Final Acceptance Gate** | Sampel data jam kerja LHO riil, verifikasi rekonsiliasi telematika, regression test menyeluruh | Math Unit Test, End-to-End Regression, Laragon Sync |

---

## Rincian Batch & Panduan Pengujian

### Batch A: Work Order, JO & Logistik Lapangan (UI/UX & Mobile Focus)
*Target: Menyelesaikan gap pembuktian visual UI, responsivitas HP/tablet mekanik, dan unifikasi presentasi WO/JO.*

1. **Ruang Lingkup Pekerjaan**:
   - Menjalankan pengujian interaktif form `#modalNewWO` pada resolusi 360px–430px (mobile) dan 1366px–1920px (desktop).
   - Memastikan interaksi input PIC multi-mekanik (*tagger/chips*) mulus di layar sentuh tanpa memutus form.
   - Menguji toggle switch *"Operasi Bersyarat / Target Kejar"* (`is_forced_operation`) dan memastikan status unit di fleet monitor berubah menjadi *RFU Bersyarat (kuning)* bukan *Breakdown (merah)*.
   - Memastikan tombol pratinjau cetak tiket dapat berganti kop dan nomor dokumen secara instan (`WO-` vs `JO-`) tanpa mengubah record database.
   - Menguji 3 cabang kebutuhan logistik: butuh part, butuh bengkel/jasa, atau tanpa part.
2. **Data Uji**:
   - Unit Uji: `QA-BRIAN-DT01`
   - Mekanik: `QA-Mekanik 1`, `QA-Mekanik 2`
3. **Panduan Pengujian (Uji Mandiri Batch A)**:
   - [x] Buka modal Tambah WO baru.
   - [x] Masukkan `Nomor Unit` $\rightarrow$ periksa apakah `Jenis Alat` & `Nomor Lambung` terisi otomatis.
   - [x] Ketik dua nama mekanik $\rightarrow$ verifikasi chip tagger muncul dan dapat dihapus salah satu.
   - [x] Aktifkan *Target Kejar* $\rightarrow$ isi catatan mitigasi $\rightarrow$ Submit.
   - [x] Periksa status unit di fleet monitor (harus kuning/RFU bersyarat).
   - [x] Buka lembar cetak tiket $\rightarrow$ ubah mode dari WO ke JO $\rightarrow$ verifikasi kop dan nomor berubah.
4. **Acceptance Criteria Batch A**:
   - Form tidak meluap (*no horizontal scroll*) di viewport HP 360px.
   - Nilai tersimpan ke database MySQL lokal (`trouble_at`, `mitigation_action`, `assigned_mechanics_json`).

---

### Batch B: Master Aset, Onboarding & Tabel PM 11 Kolom
*Target: Menuntaskan persistensi data penambahan unit baru dari menu PM dan tampilan Unit Properties.*

1. **Ruang Lingkup Pekerjaan**:
   - Memastikan tombol `+ Tambah Unit Baru` di toolbar `#view-pm` membuka form onboarding unit secara instan.
   - Menguji pengunggahan berkas: galeri foto fisik unit (multi-sudut) dan dokumen PDF JO Servis Berkala.
   - Menghitung kebenaran estimasi tanggal servis berikutnya berdasarkan koefisien jam kerja harian.
   - Menampilkan data pada tabel PM 11 kolom secara lengkap tanpa fallback data dummy/localStorage.
   - Memastikan fitur Rename Nomor Lambung mencatat log perubahan ke `asset_hull_history`.
2. **Data Uji**:
   - Unit Baru: `QA-BRIAN-EX99`
   - Nomor Lambung Awal: `EX-99-SITEA`, Nomor Lambung Baru: `EX-99-SITEB`
3. **Panduan Pengujian (Uji Mandiri Batch B)**:
   - [x] Masuk ke menu *Preventive Maintenance* $\rightarrow$ klik `+ Tambah Unit Baru`.
   - [x] Isi data unit, nomor lambung, interval servis 250 jam, dan unggah 1 foto uji.
   - [x] Simpan data $\rightarrow$ lakukan refresh halaman browser (F5) / logout lalu login ulang.
   - [x] Cari unit di kolom pencarian atau filter PM $\rightarrow$ pastikan seluruh data, thumbnail foto, dan estimasi jatuh tempo tetap utuh.
   - [x] Buka *Unit Properties* $\rightarrow$ klik ikon pensil rename lambung $\rightarrow$ ubah ke lambung baru.
   - [x] Cek tab riwayat lambung $\rightarrow$ pastikan perubahan tanggal, aktor, dan alasan tercatat rapi.
4. **Acceptance Criteria Batch B**:
   - Data bertahan permanen setelah reload.
   - Indikator urgensi servis (hijau/kuning/merah) di tabel 11 kolom akurat sesuai estimasi tanggal.

---

### Batch C: Finansial Biaya & Keputusan Otomatisasi PO/SPPU (M10, BR-09)
*Target: Menuntaskan pengujian ledger biaya dan mengambil keputusan teknis untuk integrasi PO/SPPU.*

1. **Ruang Lingkup Pekerjaan**:
   - Menyuntikkan sampel data transaksi biaya uji aman (`QA-BRIAN-COST-01`) ke `asset_cost_transactions`.
   - Menguji pembatasan hak akses (RBAC): akun role Mekanik/Operator tidak dapat melihat tab biaya atau mengakses endpoint `api/cost.php` (menghasilkan HTTP 403).
   - Menguji agregasi biaya per unit dan per WO agar tidak terjadi *double count*.
   - Menguji mekanisme reversal/pembatalan transaksi biaya (tidak menghapus riwayat audit).
   - Menetapkan dan menerapkan aturan integrasi PO/SPPU berdasarkan keputusan bisnis.
2. **Keputusan Bisnis yang Dibutuhkan**:
   - [ ] **Pilihan A**: Biaya otomatis ditarik dari Purchase Order (PO) yang disetujui.
   - [ ] **Pilihan B**: Biaya otomatis ditarik dari SPPU yang difinalkan.
   - [ ] **Pilihan C**: Keduanya dengan deduplikasi referensi.
   - [ ] **Pilihan D**: Biaya tetap dicatat manual via ledger form.
3. **Panduan Pengujian (Uji Mandiri Batch C)**:
   - [x] Login sebagai role Maintenance Planner / Admin $\rightarrow$ buka tab Biaya Unit `QA-BRIAN-DT01`.
   - [x] Tambah transaksi biaya spare part $\rightarrow$ verifikasi total kartu biaya bertambah.
   - [x] Lakukan pembatalan/void transaksi $\rightarrow$ verifikasi total berkurang namun rekaman void tetap ada di audit log.
   - [x] Login sebagai Mekanik $\rightarrow$ verifikasi menu biaya hilang dan request API langsung menghasilkan 403 Forbidden.
4. **Acceptance Criteria Batch C**:
   - Angka ringkasan biaya 100% cocok dengan akumulasi baris transaksi sumber.
   - Tidak ada kebocoran data finansial ke role non-otoritas.

---

### Batch D: Rekonsiliasi Produktivitas, LHO & Final Acceptance Gate (M8, BR-07, DoD)
*Target: Menuntaskan verifikasi angka ketersediaan alat dengan data LHO riil dan verifikasi akhir sebelum serah terima.*

1. **Ruang Lingkup Pekerjaan**:
   - Menginputkan sampel log operasional harian LHO (`report_operation_logs`) untuk 1 unit uji selama 7 hari.
   - Memverifikasi kartu analitik produktivitas: Physical Availability (PA), Mechanical Availability (MA), Use of Availability (UA), dan Effective Utilization (EU).
   - Menjalankan rekonsiliasi antara jam operasional LHO dan jam telematika GPS/CANbus.
   - Menjalankan seluruh test suite otomatis: unit, integrasi, keamanan, dan syntax check PHP.
   - Menjalankan sinkronisasi final kode ke Laragon (`scripts/sync_to_laragon.ps1 -PreserveDatabase`).
2. **Panduan Pengujian (Uji Mandiri Batch D)**:
   - [x] Jalankan kueri verifikasi angka KPI manual vs output API `api/kpi.php` untuk unit uji.
   - [x] Buka modal *KPI Formula Inspector* di UI $\rightarrow$ periksa transparansi rumus dan pembagi jam.
   - [x] Jalankan perintah test suite lengkap di terminal:
     `php tests/security_unit.php`
     `php tests/verify_kpi_math.php`
     `php tests/work_order_ticket_fields.php`
   - [x] Jalankan skrip sinkronisasi Laragon dan lakukan verifikasi via browser di `http://localhost/ServicePlan-BRA/`.
3. **Acceptance Criteria Batch D**:
   - Seluruh test suite otomatis berstatus `PASS` 100%.
   - Semua item BR-01 s.d BR-09B memiliki bukti tangkapan layar / data uji.
   - Laragon sync berhasil tanpa merusak basis data yang ada.

---

## Pola Eksekusi Checklist Per Batch

Setiap batch dijalankan dengan siklus:
```text
1. Mulai Batch
   ↓
2. Pengecekan / Perbaikan Kode & Pengujian Mandiri
   ↓
3. Verifikasi Database & Local Server
   ↓
4. Pengiriman Checkpoint Hasil ke Pengguna
   ↓
5. Konfirmasi Pengguna OK → Masuk ke Batch Berikutnya
```
