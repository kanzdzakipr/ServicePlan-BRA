# Grand Plan Finishing Backend ServicePlan-BRA

Status: **rencana revisi, bukan sertifikat lulus**. Disusun 22 Oktober 2026 dari audit read-only Batch 0–16 dan dokumen rencana/panduan yang ada. Setiap batch implementasi berhenti untuk uji dan persetujuan pengguna. Tidak ada migrasi, impor dump, sinkronisasi Laragon2, atau perubahan data dalam penyusunan dokumen ini.

Panduan penutupan seluruh checkpoint yang masih partial tersedia di [panduan/PANDUAN_PENGUJIAN_CHECKPOINT_PARTIAL_F0_F1_F4_F5_F6.md](panduan/PANDUAN_PENGUJIAN_CHECKPOINT_PARTIAL_F0_F1_F4_F5_F6.md).

## Keputusan dan aturan sumber kebenaran

- **People & KPI adalah penilaian resmi**, bukan simulator. Skor, periode, penilai, persetujuan, revisi, dan audit trail harus persisten serta tunduk pada role/lokasi.
- Jadwal PM diisi melalui **Master Asset**; menu PM membaca konfigurasi tersebut. Jangan membuat dua sumber jadwal.
- Database operasional Laragon2 `u646470441_ServicePlanBRA` adalah data pengguna bersama. Jangan impor ulang dump, reset, atau menghapusnya untuk tes.
- Status fitur dibedakan menjadi `ADA DI KODE`, `TES OTOMATIS LULUS`, `TERBUKTI DI BROWSER`, dan `TERBUKTI DI DB`. `PASS` akhir memerlukan bukti yang relevan di semua lapis, termasuk role/lokasi dan refresh/login ulang.
- Implementasi mengikuti irisan vertikal: skema → core/API dan izin → UI → tes rollback → uji browser/DB bersama. Tidak memperbaiki semua menu sekaligus.

## Ringkasan temuan yang mengarahkan prioritas

| Prioritas | Alur / batch audit | Risiko dan pekerjaan utama |
| --- | --- | --- |
| P0 | Pengaturan/manajemen pengguna (15) | `api/users.php` menerima aksi tulis dengan izin route `dashboard.read`; fallback izin server memberi `*` kepada beberapa role, sementara tabel `permissions`/`role_permissions` kosong. Tegakkan izin administrasi **per aksi** di server sebelum menguji perubahan akun. |
| P1 | Dashboard/status aset (7, 16) | State browser dan jalur sinkronisasi dapat menimpa status/WO dari API. Tetapkan DB sebagai sumber kebenaran dan transaksi perubahan berbasis satu objek/versi. |
| P1 | WO, SPB, stok, laporan (1–4, 11–12) | Finalisasi/void/reversal dan dampak antarmenu belum konsisten atau belum dibuktikan end-to-end; hilangkan fallback lokal yang menyamarkan kegagalan API. |
| P1 | People & KPI (13) | Skor/evaluasi/leaderboard statis, review SPL di `localStorage`, persetujuan SPL disimpulkan dari nama. Bangun alur penilaian resmi dan audit trail. |
| P1 | Archive (14) | GET melakukan DDL; kegagalan memuat penanda arsip dapat menampilkan item sebagai aktif. Arsip/restore belum punya identitas pelaku dan alasan. |
| P1 | Notifikasi (16) | POST tanda baca di UI tidak mengirim header CSRF; buktikan status baca persisten lintas refresh dan hanya untuk penerima. |
| P2 | PM/asset (5–6) | Konfigurasi PM harus terpusat di Master Asset; audit lokasi aset, termasuk relasi lokasi yatim, sebelum mempercayai filter dan forecast. |
| P2 | Fuel, produktivitas, biaya (8–10) | Bedakan data aktual dari contoh/fallback; rekonsiliasi angka ke baris sumber, deduplikasi dan reversal, serta batas role finansial. |
| P2 | Pengaturan lain (15) | Threshold, master lokasi, kategori/rate, template, dan keamanan masih berupa nilai/tombol statis atau simpan lokal; jangan tampilkan sukses palsu. |

Temuan ini adalah ringkasan audit, **bukan** klaim bahwa semua skenario telah direproduksi di browser. P0 manajemen pengguna diberi prioritas sebelum uji tulis role lain.

## Batch implementasi dan acceptance criteria

### F0 — Pengamanan segera akun dan baseline

1. Kunci `users.php` per aksi: daftar sesuai izin administrasi yang diputuskan, create/toggle/delete/reset password hanya untuk administrator yang berhak; ganti password sendiri mensyaratkan password lama. Jangan memberi semua pemilik `dashboard.read` akses ke daftar akun penuh.
2. Audit fallback server `*` untuk Asset/Equipment Manager; definisikan matriks izin minimal di server. Putuskan apakah tabel permission diisi melalui migrasi atau fallback dibatasi sementara. UI RBAC hanyalah cerminan, bukan penentu izin API.
3. Tambah tes HTTP/API: pengguna tanpa izin mendapat 403; admin berizin berhasil pada data uji terisolasi; lokasi lain tidak bocor; CSRF salah ditolak; tidak ada perubahan pada akun nyata.
4. Gate: uji role negatif dan positif lulus, seluruh POST akun diaudit, serta tidak ada endpoint lain dengan pola izin baca untuk operasi tulis. **Jangan lanjut uji akun tulis di DB bersama sebelum gate ini.**

### F1 — Sumber kebenaran dashboard, status aset, dan sinkronisasi

1. Petakan semua penulis status/WO; hentikan penimpaan massal dari cache browser. Gunakan operasi server spesifik dengan precondition/versi atau transaksi, dan catat aktor/sumber.
2. Saat API penting gagal, tampilkan error/ketidaklengkapan, bukan angka atau item fallback yang tampak valid. Nol adalah nol; jangan gunakan operator `||` untuk mengganti hitungan sah.
3. Gate: dua browser/role melihat status sama setelah refresh; konflik perubahan tidak menghapus update pengguna lain; kartu, detail unit, dan query DB cocok.

### F2 — Siklus transaksi WO → SPB/part/stok → laporan → biaya

Status 23 Oktober 2026: **PASS fungsional / selesai untuk dilanjutkan**. Alur browser dan autofill disetujui pengguna; 124 pemeriksaan otomatis serta rekonsiliasi database lulus. Cakupan lintas seluruh role/lokasi dan perangkat tetap dibawa ke regression gate F6, bukan dianggap gugur.

1. Selesaikan invariant WO close/void, tautan SPB aktif vs void, reservasi/penerimaan/pengeluaran part, dan finalisasi/void laporan. Setiap perubahan stok/biaya mempunyai referensi sumber unik, transaksi, dan reversal idempoten.
2. Hilangkan mock/fallback operasional dari laporan dan menu hilir. Data contoh hanya boleh tampil di mode demo berlabel jelas.
3. Gate per subalur kecil: satu ID uji dapat ditelusuri UI → API → tabel → menu lain, kemudian void/reversal mengembalikan saldo/total tanpa menghapus audit. Role/lokasi lain ditolak dan refresh tetap konsisten.

### F3 — Master Asset → PM, pemeriksaan, dan HSE

Status F3 keseluruhan 23 Oktober 2026: **PASS berdasarkan konfirmasi pengguna untuk F3a, F3b, dan F3c**.

Status F3a 23 Oktober 2026: **PASS berdasarkan konfirmasi pengguna**. Form tambah dan edit Master Asset menyediakan konfigurasi PM lengkap, nilai bertahan setelah refresh, dan Forecast membaca sumber tersebut. Aset tanpa konfigurasi tetap tampil belum diatur sampai data resmi diisi.

Status F3b 23 Oktober 2026: **PASS berdasarkan konfirmasi pengguna**. Bukti data menunjukkan seluruh 87 referensi yatim mengarah ke lokasi ID 15 dengan catatan `WUR EW Project`/`WUR EW`, sehingga lokasi tersebut dipulihkan secara aditif sebagai `WUR EW Project`. Skema registrasi Master Asset juga diselaraskan tanpa reseed data: 11 kolom API tersedia, 87 aset tetap terhubung, dan referensi lokasi yatim turun dari 87 menjadi 0. Temuan uji browser menunjukkan posisi sudah persisten di DB tetapi sempat dibuang saat payload API lengkap digabungkan dengan data ringkas dashboard; pemetaan refresh dikoreksi dan pengisian lokasi/posisi setelah refresh telah disetujui pengguna. Audit otomatis F3b lulus 18/18; integrasi onboarding lulus 46/46; smoke UI PM/refresh lulus 17/17.

Status F3c 23 Oktober 2026: **PASS berdasarkan konfirmasi pengguna**. Hold/release HSE persisten, transaksional, teraudit, idempoten, mematuhi beberapa hold aktif, dan memulihkan status sebelum hold tanpa menimpa perubahan status yang lebih baru. Integrasi transaksi lulus 8/8, regresi P2H 14/14, regresi MDE-02 12/12, serta pengujian Laragon2 membuktikan `STANDBY -> ACCIDENT_HOLD -> STANDBY`. Seluruh data QA telah dibersihkan setelah pengujian. Laporan uji `03/ACC/BRA/SITE/2026` dengan waktu kejadian 23 Oktober dihapus, laporan lama `01/ACC/BRA/DURI/2026` dipulihkan menjadi locked, dan `CS-41001` dikembalikan ke `ACCIDENT_HOLD`.

1. Jadwal/interval PM disimpan melalui Master Asset; PM forecast memakai data tersebut dan menandai unit tanpa konfigurasi sebagai `belum diatur`, bukan mengarang jadwal.
2. Rekonsiliasi lokasi aset yatim dan validasi ID lokasi saat edit/migrasi. Hubungkan pemeriksaan/P2H dan HSE hold/release ke status aset dengan aturan prioritas yang eksplisit.
3. Gate: satu aset uji menghasilkan forecast yang sama di Master Asset, PM, dan DB; pemeriksaan/hold/release berefek sesuai aturan setelah login ulang. Unit tanpa jadwal/lokasi tetap terlihat sebagai exception.

### F4 — People & KPI resmi

Status F4 23 Oktober 2026: **IMPLEMENTED / Tes Otomatis Lulus (26/26), siap diuji di browser**. Skema siklus hidup penilaian resmi (`DRAFT → SUBMITTED → APPROVED / REJECTED`), otorisasi role approver (Administrator / General Manager), penghapusan `localStorage` review SPL digantikan tabel database `spl_planner_reviews`, serta leaderboard lembur jujur dari akumulasi `spl_overtime_records` telah selesai dibangun dan disinkronkan ke Laragon.

1. Tetapkan model periode, indikator, bobot, target, penilai, status `DRAFT → SUBMITTED → APPROVED/REJECTED`, versi/revisi, lampiran bukti, dan audit event. Gunakan tabel `head_kpi_assessments`/`planner_evaluations` yang ada hanya setelah migrasi kompatibilitas diperiksa; jangan menimpa 10/5 baris yang sudah ada.
2. Skor dan nilai bobot dihitung/validasi server; periode dan lokasi wajib. Review SPL serta persetujuan Head memakai identitas user, timestamp, keputusan, dan otorisasi server—bukan nama pada dokumen atau `localStorage`.
3. Leaderboard lembur memakai SPL final/absensi yang terverifikasi. Bila sumber absensi belum tersedia, tampilkan `data belum tersedia`; pisahkan dokumen contoh historis dari register DB.
4. CSV hanya mengekspor versi tersimpan dengan status dan sumber; draf/simulasi diberi label tegas.
5. Gate: penilai berwenang membuat/mengirim; approver berbeda menyetujui; role/lokasi lain ditolak; edit pasca-approval mengikuti revisi; CSV = DB setelah refresh dan di browser lain; audit mencatat setiap transisi.

### F5 — Archive, notifikasi, dan pengaturan operasional

Status F5 23 Oktober 2026: **IMPLEMENTED / Tes Otomatis Lulus (28/28), siap diuji di browser**. DDL runtime `archived_items` telah dipindahkan ke migrasi resmi [scripts/migrate_f5_archive_settings_schema_23102026.sql](file:///c:/Users/LENOVO/ServicePlan-BRA/scripts/migrate_f5_archive_settings_schema_23102026.sql), audit trail arsip/restore persisten pada tabel `archive_audit_trail`, penambahan fail-closed failure banner saat penanda arsip gagal termuat, penyertaan token CSRF pada seluruh mutasi notifikasi (`MARK_READ`, `MARK_ALL_READ`, `CREATE`), serta implementasi tabel & API `operational_settings` ([api/settings.php](file:///c:/Users/LENOVO/ServicePlan-BRA/api/settings.php)) yang menggantikan pesan sukses palsu/`localStorage`.

1. Pindahkan DDL `archived_items` dari GET API ke migrasi. Tambah audit aktor/waktu/alasan pada arsip dan restore, serta fail-closed atau indikator kegagalan ketika penanda arsip tidak termuat.
2. Tambah CSRF pada POST notifikasi UI; uji penerima, badge, mark-read, mark-all-read, dan tautan target lintas role/lokasi.
3. Jadikan pengaturan yang benar-benar diperlukan sebagai tabel/API tervalidasi; panel yang belum operasional diberi label nonaktif. Pisahkan manajemen user dari matriks izin persisten dan hapus pesan sukses palsu.
4. Gate: arsip/restore, baca notifikasi, dan perubahan setting tetap sama setelah refresh/login ulang serta terbukti pada DB; role tanpa izin mendapat 403/404.

### F6 — Rekonsiliasi analitik dan regression gate

1. Untuk fuel, produktivitas, KPI, biaya, telematika, dan laporan: tampilkan periode, unit/lokasi, jumlah baris sumber, rumus, kualitas data, dan status sumber hilang. Cocokkan kartu dengan daftar detail dan query agregat DB.
2. Putuskan **NEEDS DECISION**: biaya otomatis berasal dari PO disetujui, SPPU final, keduanya dengan deduplikasi, atau tetap manual. Sampai diputuskan, jangan aktifkan posting otomatis.
3. Jalankan rangkaian regresi role/lokasi, browser desktop/mobile, refresh/login ulang, void/reversal, ekspor, dan rekonsiliasi lintas menu. Catat temuan baru sebagai batch revisi terpisah; jangan menyebut `PASS` dari tes statis saja.

## Protokol migrasi, Laragon2, dan rollback

1. Sebelum setiap batch, catat `git status --short`, hash/versi skema, hitungan tabel terdampak, dan ID uji. Hindari konflik dengan perubahan pengguna lain; jangan commit atau deploy perubahan mereka.
2. Migrasi harus aditif dan idempoten, ditinjau SQL-nya sebelum dijalankan. Untuk tabel besar/DDL MySQL yang implicit commit, **rollback transaksi tidak cukup**: siapkan backup terverifikasi, rencana restore per tabel, dan jendela perubahan yang disepakati.
3. Tes tulis otomatis memakai DB uji terisolasi atau transaksi rollback yang benar-benar menjamin tidak ada DDL/autocommit/efek luar. Data uji browser memakai prefiks `QA-BRIAN-`, lokasi dan akun uji yang disepakati, serta bukti sebelum/sesudah. Jangan hapus data bersama demi pembersihan tes.
4. `scripts/sync_to_laragon.ps1` saat ini menerima `-LaragonRoot`, tetapi `-PreserveDatabase` **tetap menjalankan migrasi aditif**. Sebelum memakai script: verifikasi `C:\laragon2\www\ServicePlan-BRA` sebagai target fisik, periksa dry-run/daftar file dan migrasi, koneksi MySQL/datadir, serta backup. Jangan gunakan `-ImportDatabase` atau dump. Sinkronisasi hanya setelah izin batch implementasi dan efeknya disetujui.
5. Rollback kode menggunakan artefak versi sebelumnya tanpa menghapus perubahan orang lain; rollback DB memakai migrasi balik yang telah diuji atau restore backup spesifik. Catat titik tidak dapat dibalik dan rencana kompensasi sebelum eksekusi.

## Bukti wajib pada setiap checkpoint

| Lapisan | Bukti minimum |
| --- | --- |
| Kode | File:baris untuk UI, JS, API, core, izin, dan tabel; diff terbatas pada batch. |
| Otomatis | Perintah, jumlah PASS/FAIL, cakupan positif/negatif, dan bukti rollback/isolasi. |
| Browser | Akun/role/lokasi, langkah, HTTP status, hasil layar, refresh/login ulang, screenshot bila relevan. |
| Database | Query `SELECT` sebelum/sesudah untuk ID uji; bukti relasi/total dan audit trail, tanpa membuka data sensitif. |
| Risiko | Data pengguna yang mungkin terdampak, strategi rollback, dan status `PASS/PARTIAL/FAIL/NOT TESTED/NEEDS DECISION`. |

## Release gate backend

Backend baru boleh disebut **selesai** bila: seluruh P0/P1 ditutup; tidak ada operasi resmi yang hanya hidup di browser; semua mutasi menegakkan role/lokasi/CSRF/validasi di server; alur create-update-finalize-void/reversal idempoten dan teraudit; angka kartu cocok dengan sumber DB; semua migrasi dan rollback telah diuji; tes otomatis aman lulus; uji browser lintas role/lokasi dan mobile lulus; serta pengguna menyetujui checkpoint akhir. P2 yang ditunda harus beralasan, diberi batas risiko, dan tidak disamarkan sebagai PASS.
