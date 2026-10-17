# Rencana Batch Revisi Tim Brian dan Implementation Plan 17 Oktober 2026

Status: rencana kerja, **bukan** laporan bahwa seluruh item telah selesai. Acuan: `rekap-revisi-tim-brian.md` dan `implementation-plan-17102026.md`. Setiap batch berhenti pada checkpoint; batch berikutnya dimulai setelah pengguna menguji dan menyatakan hasilnya sesuai.

## Aturan pelaksanaan setiap batch

1. Catat `git status --short`, alur UI → JS → API → tabel, dan data uji awal. Jangan menimpa perubahan pengguna.
2. Implementasikan irisan lengkap: skema/migrasi idempoten → API dan izin → UI → pengujian otomatis → pengujian manual. Data uji baru memakai prefiks `QA-BRIAN-`.
3. Uji dengan sedikitnya dua peran: peran berwenang dan peran yang harus ditolak. Uji juga refresh/login ulang, data beda lokasi, dan void/reversal bila relevan.
4. Sinkronisasi kode ke Laragon lokal **hanya setelah target terverifikasi**, dengan database dipertahankan. Jangan impor ulang dump data uji. Catat perintah, target, dan hasilnya pada checkpoint.
5. Laporkan `PASS`, `PARTIAL`, atau `BLOCKED` berikut bukti file/test/query. Bila ada kegagalan, perbaiki dalam batch yang sama sebelum meminta persetujuan lanjut.

**Perhatian Laragon2:** `scripts/sync_to_laragon.ps1` saat ini memiliki target default `C:\laragon\www\ServicePlan-BRA`, pembatasan target ke `C:\laragon\www`, dan `robocopy /MIR`. Riwayat percakapan menyebut Laragon2, tetapi script ini belum mengizinkan target tersebut. Sebelum sinkronisasi batch pertama, verifikasi instalasi aktif, target absolut, file target yang sudah ada, dan jalur database. Perubahan script untuk Laragon2 harus aman bagi pengguna lain (parameter konfigurasi, bukan hard-code lokal). Gunakan `-PreserveDatabase`; jangan jalankan sync default yang dapat mengimpor ulang dump. Jika verifikasi target belum selesai, implementasi dan tes repository tetap boleh berjalan, tetapi status sync dilaporkan `BLOCKED`.

## Batch 0 — Baseline dan matriks penerimaan

**Lingkup:** audit ulang fitur aktif, uji otomatis yang tersedia, akun/peran, lokasi, serta kondisi Laragon2. Petakan requirement M3–M10 dan WO/onboarding/GLB-A–E ke bukti kode dan skema. Tidak mengubah data operasional.

**Lulus bila:** ada matriks status per requirement, baseline test, target Laragon2 yang terverifikasi, dan daftar data uji yang aman. Batch ini menjadi syarat sebelum perubahan lain.

## Batch 1 — Keamanan notifikasi WO (M7)

**Lingkup:** ubah penerima notifikasi dari daftar nama umum menjadi audience yang dapat ditegakkan server (user/role/lokasi); batasi GET, tandai baca, dan tandai semua baca ke pengguna yang berhak. Pertahankan event WO dibuat, kebutuhan part/jasa, perubahan status, WO ditutup/dibatalkan, tautan detail WO, dan pencegahan duplikat.

**Uji bersama:** buat WO di lokasi A; pengguna logistik/planner terkait menerima satu notifikasi dan dapat membuka WO; pengguna di lokasi B atau role tanpa hak tidak menerima/membaca; `mark all read` tidak memengaruhi akun lain; refresh mempertahankan status baca.

**Selesai bila:** API, badge, detail WO, dan pengujian lintas role/lokasi konsisten. Jangan mengklaim email/WhatsApp tanpa gateway.

## Batch 2 — Fondasi identitas aset dan audit nomor lambung (M3, GLB-B)

**Lingkup:** tetapkan satu sumber kebenaran untuk kode unit permanen vs nomor lambung proyek. Tambahkan riwayat perubahan lambung (nilai lama/baru, aktor, waktu, alasan, tanggal efektif), endpoint rename dengan izin khusus dan transaksi, serta riwayat di Unit Properties. Pertahankan kompatibilitas data `alias_name` lama melalui migrasi yang jelas, bukan menimpa diam-diam.

**Uji bersama:** ubah lambung satu unit uji; kode unit dan SN/plat tetap; perubahan tampil di Dashboard, WO, PM, dan Logistik; riwayat tetap setelah reload; pengguna tanpa izin ditolak; perubahan lintas lokasi ditolak.

**Selesai bila:** revisi identitas lintas menu dan audit rename dapat dibuktikan dari satu unit yang sama.

## Batch 3 — Onboarding aset dan data PM persisten (Outline 2, GLB-C)

**Lingkup:** lengkapi form tambah/edit dan Unit Properties dengan operator serta riwayatnya, histori servis, interval HM/KM, estimasi servis, galeri foto, PDF JO, dan data legalitas yang telah disepakati. Simpan melalui API/skema; validasi upload dan otorisasi. Tabel PM 11 kolom **sudah ada**, jadi fokus pada sumber data persisten, filter site, dan penghapusan ketergantungan data prototipe/browser untuk data operasional.

**Uji bersama:** tambah unit `QA-BRIAN-...` dari menu PM, isi data dan lampiran, logout/login ulang, lalu cocokkan form, Unit Properties, dan 11 kolom PM. Periksa estimasi HM/KM, unit tanpa data, filter site, file tidak valid, dan role tanpa izin.

**Selesai bila:** data tetap ada setelah reload dan dapat dilihat sesuai izin; bukan hanya tersimpan pada objek JS/localStorage.

## Batch 4 — Pencarian universal aset (GLB-A)

**Lingkup:** endpoint pencarian terbatasi lokasi untuk kode unit, lambung, SN, dan plat; overlay hasil berisi identitas/status/lokasi; klik membuka Unit Properties unit yang dipilih. Tambahkan hotkey dan batas hasil tanpa membocorkan data lokasi lain.

**Uji bersama:** cari unit yang sama menggunakan empat identitas berbeda; semua menuju detail yang sama; lokasi lain tidak muncul; data kosong/duplikat ditangani jelas; pencarian tetap responsif pada data besar.

**Selesai bila:** hasil tidak bergantung hanya pada dataset browser dan navigasi tepat ke unit.

## Batch 5 — Konsistensi WO/JO dan rumus KPI (Outline 1, GLB-D, GLB-E)

**Lingkup:** regresi 12 field tiket WO, upload foto, multi-PIC, operasi bersyarat, sinkronisasi status aset, dan timeline. Selaraskan teks rumus pada UI dengan `KpiCalculator`; tampilkan inspector nilai dan sumber per unit/periode. Tambahkan pilihan tampilan/cetak WO atau JO dari **satu record** tanpa menggandakan data.

**Uji bersama:** buat dan tutup satu WO normal serta satu operasi bersyarat; periksa database, status aset, foto, mekanik, dan timeline. Hitung contoh PA/MA/UA/EU/MTBF/MTTR manual lalu cocokkan API dan UI. Cetak record yang sama sebagai WO dan JO; ID database tidak berubah.

**Selesai bila:** rumus UI/API/tes sama, dokumen WO/JO hanya berbeda presentasi, dan alur WO lama tidak rusak.

## Batch 6 — Rekonsiliasi produktivitas dan biaya dasar (M8, M10)

**Lingkup:** fokus pada gap bukti dan regresi, karena kalkulator produktivitas, rekonsiliasi LHO–telematika, dan ledger biaya sudah ada. Uji filter periode/unit/kategori/lokasi, data hilang vs nol, downtime tumpang-tindih, total kartu vs detail, transaksi biaya per unit/WO, pembulatan, reversal, deduplikasi, serta izin finansial. Perbaiki hanya ketidaksesuaian yang terbukti.

**Uji bersama:** pilih satu unit dan periode yang memiliki LHO, telematika, WO, serta biaya; cocokkan angka ke baris sumber. Buat biaya uji, balikkan, pastikan total berkurang namun audit tetap ada. Peran tanpa izin biaya tidak dapat membaca endpoint.

**Selesai bila:** setiap angka yang diuji dapat ditelusuri ke sumber dan tidak ada perhitungan ganda.

**Keputusan terpisah:** otomatisasi biaya dari (A) PO disetujui, (B) SPPU final, (C) keduanya dengan deduplikasi, atau (D) tetap manual. Jangan mengimplementasikan sumber otomatis sebelum pilihan bisnis ini diputuskan.

## Batch 7 — Regression gate dan serah terima

**Lingkup:** jalankan ulang panduan revisi 1, 3, 4, 5, 8, tes kontrak CSV, tes WO/SPB/PM/KPI/biaya/notifikasi, uji role dan lokasi, serta smoke test browser/mobile. Periksa skema dan data di Laragon2 tanpa menghapus data pengguna.

**Lulus bila:** matriks M3–M10 dan Outline 1–3/GLB-A–E memiliki status dan bukti; semua gap yang belum selesai dilaporkan jujur; pengguna menyetujui hasil pengujian akhir.

## Format checkpoint yang disampaikan setelah setiap batch

```text
BATCH: <nomor dan nama>
STATUS: PASS | PARTIAL | BLOCKED
PERUBAHAN: <UI/API/skema/file>
BUKTI: <tes otomatis, skenario manual, query aman>
LARAGON2: <target terverifikasi, sync preserve DB, atau alasan belum sync>
UJI PENGGUNA: <langkah singkat dan hasil yang diharapkan>
GATE: menunggu konfirmasi pengguna sebelum batch berikutnya
```
