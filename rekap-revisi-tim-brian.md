# Prompt Eksekusi Revisi Website FleetMonitor — Tim Brian

> Dokumen ini adalah **prompt executable**. Berikan seluruh isi dokumen ini kepada coding agent yang memiliki akses ke repository `ServicePlan-BRA`. Agent harus benar-benar menelusuri, mengubah, dan menguji aplikasi; bukan hanya membuat rencana atau memberi saran.

## Prompt Utama

Anda bertindak sebagai **lead full-stack engineer sekaligus QA engineer** untuk aplikasi ServicePlan-BRA / FleetMonitor milik PT Bina Rekayasa Anugrah. Tugas Anda adalah memahami sistem dari ujung ke ujung, memverifikasi kondisi aktual terhadap permintaan Tim Brian, mengimplementasikan kekurangannya secara aman, lalu melakukan quality control berbasis bukti.

Kerjakan langsung pada repository yang sedang terbuka. Jangan berhenti setelah audit atau menghasilkan rencana. Lanjutkan sampai seluruh item yang dapat diputuskan dari requirement telah diimplementasikan dan diuji. Jika ada satu keputusan bisnis yang benar-benar tidak dapat disimpulkan—khususnya integrasi PO/SPPU—catat sebagai `NEEDS DECISION`, ajukan satu pertanyaan yang spesifik, dan tetap selesaikan pekerjaan lain yang tidak bergantung pada keputusan tersebut.

### Hasil akhir yang wajib dicapai

1. Peta sistem aktual yang menunjukkan aliran UI → JavaScript → API → tabel database → role/permission.
2. Matriks keterlacakan untuk seluruh permintaan Tim Brian: kondisi awal, gap, perubahan, dan bukti QC.
3. Implementasi end-to-end untuk seluruh gap yang telah terkonfirmasi.
4. Migrasi/skema, backend, frontend, RBAC, validasi, dan test selalu konsisten sebagai satu irisan vertikal.
5. QC fungsional, integrasi, database, RBAC, keamanan dasar, responsif, dan regresi.
6. Laporan akhir yang jujur: `PASS`, `PARTIAL`, `FAIL`, atau `BLOCKED` per requirement—dilarang menyebut selesai tanpa bukti.

## Konteks Repository yang Harus Diverifikasi

Gunakan informasi berikut sebagai petunjuk awal, **bukan asumsi kebenaran**:

- Entry point login: `index.html`.
- Entry point dashboard terproteksi: `dashboard.php` yang memuat `dashboard.view.php`.
- Frontend utama: `dashboard.view.php`, `scripts/dashboard.js`, dan `scripts/dashboard.css`.
- Backend: endpoint PHP dalam `api/`, dengan helper/layanan pada `core/`.
- Database utama: MySQL `u646470441_ServicePlanBRA`.
- Skema referensi: `scripts/schema.sql`.
- Dump Laragon: `scripts/u646470441_ServicePlanBRA.sql`.
- Sinkronisasi lokal: `scripts/sync_to_laragon.ps1`.
- URL lokal: `http://localhost/ServicePlan-BRA/` dan `http://localhost/ServicePlan-BRA/dashboard.php`.
- Dokumen uji utama: `PANDUAN_PENGUJIAN_ROLE_DAN_FUNGSI.md`, `PANDUAN_PENGUJIAN_REVISI_3.md`, dan panduan batch lain.
- Test otomatis: `tests/*.php`, `tests/run_security_tests.ps1`, serta audit dalam `scripts/`.
- Data utama yang mungkin terkait: `assets`, `work_orders`, `work_order_timeline`, `wo_time_logs`, `parts`, `purchase_requests`, `purchase_request_items`, `purchase_orders`, `inventory_transactions`, `system_notifications`, `telematics_logs`, `report_*`, `cost_financial_monthly`, `roles`, `permissions`, dan `role_permissions`.

Repository mungkin memiliki perubahan milik pengguna. Periksa `git status` terlebih dahulu, pertahankan perubahan yang tidak terkait, dan jangan menggunakan `git reset --hard`, `git clean`, atau tindakan destruktif lain. Jangan mengubah data produksi. Semua data uji baru wajib memakai prefiks `QA-BRIAN-` dan harus dapat dibedakan dari data nyata.

## Prinsip Kerja Wajib

- **Telusuri sebelum mengubah.** Jangan menebak implementasi hanya dari nama menu atau dokumen lama.
- **Bukti lebih penting daripada klaim.** Setiap status harus merujuk file/baris, request-response, query database, hasil test, atau screenshot.
- **Source of truth berurutan:** requirement di dokumen ini → perilaku aplikasi dan API aktif → skema/dump aktif → test/panduan terbaru → dokumen lama/arsip. Bila bertentangan, catat konflik dan pilih perilaku yang memenuhi requirement tanpa merusak alur aktif.
- Folder `archive/` adalah referensi legacy, bukan target implementasi, kecuali entry point aktif memang memakainya dan hal itu telah dibuktikan.
- Jangan sekadar menyembunyikan tombol. Otorisasi harus ditegakkan di backend; frontend hanya mencerminkan izin.
- Jangan menyimpan state penting hanya di browser. Data operasional harus persisten di database dan tetap ada setelah reload/login ulang.
- Validasi bisnis harus ada di server. Validasi frontend hanya untuk UX.
- Gunakan transaksi database untuk perubahan stok/biaya/status yang saling terkait.
- Hindari duplikasi data identitas aset. Gunakan relasi dan satu sumber kebenaran bila memungkinkan.
- Jangan memasukkan secret, password, cookie sesi, atau data sensitif ke laporan/commit/output.
- Jangan membuat ulang seluruh UI. Pertahankan gaya dan fungsi yang sudah ada, kecuali perubahan kecil memang diperlukan untuk memenuhi acceptance criteria.
- Setelah setiap perubahan kecil, jalankan test paling dekat dengan area tersebut. Jangan menumpuk semua pengujian di akhir.
- Jika test gagal karena baseline yang sudah rusak, buktikan bahwa kegagalan memang sudah ada sebelum perubahan dan jangan menyamarkannya.

## Format Checkpoint Setiap Microprompt

Pada akhir setiap microprompt, tampilkan checkpoint singkat dengan format berikut:

```text
MICROPROMPT: Mx — <nama>
STATUS: PASS | PARTIAL | BLOCKED
DITEMUKAN: <fakta penting>
DIUBAH: <file/tabel yang berubah, atau "belum ada">
DIVERIFIKASI: <test/query/bukti>
RISIKO/BERIKUTNYA: <maksimal 3 butir>
```

Jangan lanjut ke implementasi bila gate discovery dan traceability belum selesai. Setelah itu, lanjutkan otomatis dari satu microprompt ke microprompt berikutnya tanpa menunggu persetujuan, kecuali ada tindakan destruktif atau keputusan bisnis yang material.

---

## M0 — Baseline Aman dan Inventaris Repository

**Tujuan:** mengetahui kondisi awal dan mencegah perubahan yang salah sasaran.

1. Jalankan `git status --short` dan catat file yang sudah berubah sebelum pekerjaan dimulai.
2. Cari `AGENTS.md`, README, konfigurasi environment, entry point, manifest dependency, dan script test/build.
3. Inventaris semua file aktif pada root, `api/`, `core/`, `scripts/`, dan `tests/`. Kecualikan dependency/vendor besar saat pencarian awal.
4. Periksa `.htaccess`, `index.html`, `dashboard.php`, dan mekanisme bootstrap API untuk memahami routing, session, CSRF, CORS, database, serta penanganan error.
5. Identifikasi versi runtime yang tersedia: PHP, MySQL/MariaDB, PowerShell, dan browser automation bila ada.
6. Jalankan pemeriksaan baseline yang read-only dan murah: syntax/lint PHP, pencarian conflict marker, serta pencarian mojibake. Simpan hasil sebelum kode diubah.

**Gate M0:** tersedia daftar entry point aktif, stack, test command, perubahan awal pengguna, dan baseline failure. Jangan mengedit fitur pada fase ini.

## M1 — Telusuri dan Pahami Sistem Secara Menyeluruh

**Tujuan:** membangun peta sistem berdasarkan kode aktual, bukan dugaan.

Lakukan penelusuran berikut dengan `rg`, pembacaan file terarah, dan query skema. Jangan membaca file besar secara buta dari awal sampai akhir; cari simbol, selector, endpoint, nama tabel, dan event handler lalu baca konteksnya.

### M1.1 Peta entry point dan runtime

Telusuri:

- login → pembuatan session → pemuatan context user → dashboard;
- bagaimana `dashboard.php`, `dashboard.view.php`, `dashboard.html`, dan `.htaccess` saling berhubungan;
- file JavaScript/CSS yang benar-benar dimuat;
- cara frontend mengambil data awal dan melakukan mutasi;
- konfigurasi database dan perbedaan local/production;
- mekanisme CSRF, session expiry, location scope, dan JSON response.

### M1.2 Peta domain dan navigasi

Daftar seluruh menu/tab/modal/form yang benar-benar aktif. Untuk setiap modul, catat:

| Modul | UI/selector | Handler JS | Endpoint API | Tabel | Permission | Operasi CRUD/approval |
|---|---|---|---|---|---|---|

Minimal petakan Dashboard, Monitoring, Master Aset, Inspeksi, Work Order, Preventive Maintenance, Logistik/Stok, Condition Monitoring, Fuel, Produktivitas, Biaya, Reports, Approval, Notifications, Settings/RBAC, dan Archive.

### M1.3 Peta data end-to-end

Untuk sembilan kelompok requirement, telusuri satu record dari sumber sampai tampilan:

1. identitas aset;
2. WO dan timeline;
3. kebutuhan part/jasa dari WO;
4. transaksi/pemakaian part;
5. notifikasi;
6. telematika dan produktivitas;
7. impor/ekspor CSV;
8. biaya/transaksi;
9. PO/SPPU bila memang sudah memiliki alur aktif.

Cari semua variasi nama field pada frontend, payload JSON, PHP, dan SQL. Catat mismatch seperti `serialNumber` vs `serial_number`, status yang berbeda ejaan, ID bebas vs foreign key, atau nilai yang hanya ada di mock/localStorage.

### M1.4 Peta role dan alur lintas peran

Ambil role/permission aktual dari kode dan database. Petakan siapa yang:

- membuat dan memperbarui aset;
- membuat, menugaskan, mengerjakan, menguji, dan menutup WO;
- meminta, menyetujui, mengeluarkan, dan menerima part;
- menerima notifikasi;
- melihat biaya, produktivitas, dan data lintas lokasi.

Bandingkan enforcement UI dengan API. Tandai akses yang hanya disembunyikan di UI tetapi masih lolos di endpoint.

### M1.5 Walkthrough aplikasi

Jika Laragon tersedia, buka aplikasi melalui server PHP/Apache—jangan menilai hanya dari file HTML statis. Gunakan akun lokal sesuai panduan repository tanpa mencetak password ke laporan. Lakukan walkthrough minimal dengan Administrator, Maintenance Planner, Mekanik, dan Logistic Head. Periksa Console dan Network pada tiap alur.

**Output M1:** buat atau perbarui bagian `PETA SISTEM AKTUAL` pada catatan kerja dengan diagram tekstual ringkas dan tabel UI → JS → API → database → role. Sertakan daftar dead code, mock data, localStorage, dan jalur legacy yang ditemukan.

**Gate M1:** setiap requirement sudah memiliki jalur kode/data yang diketahui atau bukti eksplisit bahwa jalur tersebut belum ada.

## M2 — Matriks Keterlacakan dan Analisis Gap

**Tujuan:** mengubah request menjadi pekerjaan terukur.

Buat matriks berikut sebelum menulis implementasi:

| ID | Requirement | Status awal | Bukti awal | Gap akar masalah | Irisan file/tabel | Acceptance test | Risiko/dependensi |
|---|---|---|---|---|---|---|---|

Gunakan status awal hanya `SUDAH`, `SEBAGIAN`, `BELUM`, atau `TIDAK DAPAT DIBUKTIKAN`. Pecah requirement besar menjadi baris atomik. Contoh: kelengkapan SN dan validasi alur tambah unit adalah dua baris berbeda.

Untuk setiap gap, tentukan irisan vertikal terkecil: migrasi/schema → API → JS/UI → permission → automated test → manual QC. Urutkan berdasarkan dependensi, bukan urutan tampilan.

**Prioritas:**

- `P0`: integritas data, akses tanpa izin, kerusakan alur utama, atau transaksi stok/biaya tidak konsisten.
- `P1`: requirement inti Tim Brian yang belum berfungsi end-to-end.
- `P2`: UX, pesan kesalahan, polish, dan dokumentasi.

**Gate M2:** tidak boleh ada requirement yang hanya berbunyi “perbaiki” tanpa acceptance test objektif.

## M3 — Master Aset dan Identitas Unit

**Requirement:**

- Setiap unit memiliki asset ID/kode unit, serial number (SN), nomor pelat, merek/model, tahun pembuatan, kategori, dan lokasi.
- Data yang belum tersedia ditandai jelas dan dapat difilter untuk tindak lanjut; jangan mengarang nilai.
- Alur tambah/edit unit memvalidasi field, mencegah ID/SN/pelat duplikat sesuai aturan bisnis, memberi pesan error yang berguna, dan persisten setelah reload.
- Identitas relevan—minimal kode unit, SN, dan nomor pelat—muncul konsisten pada dashboard, WO, dan logistik.

**Microprompt implementasi:**

1. Audit kolom `assets` pada kedua sumber skema dan database runtime. Bandingkan dengan payload `api/assets.php` dan form aset.
2. Putuskan field wajib vs opsional berdasarkan data nyata. Nilai tidak diketahui harus tampil sebagai `Belum tersedia`, bukan string palsu seperti `-` yang ambigu di database.
3. Buat migrasi idempoten bila kolom/index kurang. Sinkronkan `scripts/schema.sql` dan dump/migrasi yang memang menjadi sumber deployment proyek.
4. Perbaiki create/update/list/detail API dengan prepared statement, validasi tipe/range, status HTTP yang benar, normalisasi whitespace/case seperlunya, dan error duplikat yang tidak membocorkan detail database.
5. Perbaiki form tambah/edit serta daftar kelengkapan data.
6. Gunakan satu helper/formatter identitas unit agar dashboard, WO, dan logistik tidak berbeda format.
7. Tambahkan test untuk record lengkap, field kosong, duplikat, location scope, refresh persistence, serta tampilan `Belum tersedia`.

**Acceptance criteria M3:** seluruh field dapat disimpan dan dibaca kembali; identitas sama di tiga konteks; record tidak lengkap terlihat jelas; akses di luar lokasi ditolak; tidak ada error Console/HTTP 500.

## M4 — Detail dan Siklus Hidup Work Order

**Requirement:** WO menyimpan dan menampilkan keluhan/kerusakan, penyebab kerusakan, tindakan perbaikan, mekanik penanggung jawab, jenis pemeliharaan, prioritas, waktu laporan, waktu mulai, waktu selesai, durasi, dan timeline status yang dapat diaudit.

**Microprompt implementasi:**

1. Verifikasi implementasi pada `api/work_orders.php`, tabel `work_orders`, `work_order_timeline`, `wo_time_logs`, kartu Kanban, modal create/edit/detail, serta integrasi laporan Repair & Overhaul.
2. Definisikan transisi status yang diizinkan dan role yang boleh menjalankannya. Tolak lompatan status ilegal di server.
3. Pastikan keluhan, penyebab, dan tindakan adalah field berbeda dan tidak saling menimpa.
4. Isi waktu mulai/selesai pada event yang tepat, hitung durasi dari timestamp server, dan pertahankan histori ketika WO dibuka kembali.
5. Setiap create/update/status transition harus menulis timeline dengan actor dan timestamp. Hindari duplikasi event pada retry.
6. Terapkan validasi penutupan minimal: PIC/mekanik, penyebab, tindakan akhir, dan data lain yang memang disyaratkan alur aktif.
7. Jalankan ulang semua skenario dalam `PANDUAN_PENGUJIAN_REVISI_3.md` dan tambahkan automated test untuk state transition serta timeline.

**Acceptance criteria M4:** kartu dan detail konsisten; refresh tidak menghapus perubahan; timeline urut dan lengkap; durasi benar; WO tidak dapat ditutup jika syarat belum lengkap; report integration tidak membuat WO duplikat.

## M5 — Permintaan Suku Cadang dan Layanan dari WO

**Requirement:** dari detail WO pengguna berwenang dapat memilih salah satu dari tiga kebutuhan:

1. membutuhkan suku cadang;
2. membutuhkan layanan perbaikan/bengkel;
3. tidak membutuhkan suku cadang.

Pilihan harus eksplisit, tidak boleh tersirat dari array kosong.

**Microprompt implementasi:**

1. Audit apakah `purchase_requests`/SPB sudah memiliki `wo_id` atau linkage lain. Jangan memakai catatan bebas sebagai satu-satunya relasi.
2. Rancang relasi yang menjaga satu WO dapat memiliki nol atau lebih permintaan part/jasa, lengkap dengan status, requester, waktu, dan pihak penindak lanjut.
3. Untuk part: simpan item, part number/nama, kuantitas, satuan, alasan, dan status. Validasi kuantitas positif.
4. Untuk jasa: simpan jenis layanan, vendor/bengkel bila sudah diketahui, uraian, estimasi, dan status tanpa memaksa adanya part.
5. Untuk “tidak ada suku cadang”: simpan keputusan, actor, waktu, dan alasan opsional agar dapat diaudit.
6. Dari halaman logistik, tampilkan asal WO dan identitas unit; dari WO, tampilkan status permintaan dan tautan/detail tindak lanjut.
7. Enforcement role dilakukan pada API untuk create, update, approve, fulfil, dan cancel.
8. Tambahkan test untuk tiga cabang, retry/idempotency, WO tidak valid, role terlarang, dan location scope.

**Acceptance criteria M5:** ketiga pilihan dapat dibedakan di database/UI; seluruh permintaan dapat ditelusuri dua arah ke WO; logistik melihat konteks unit; perubahan status persisten dan diaudit.

## M6 — Riwayat Penggunaan Suku Cadang

**Requirement:** penggunaan part dapat ditelusuri berdasarkan unit dan WO, mencakup part, jumlah, satuan, waktu, pelaksana, dan saldo sebelum/sesudah.

**Microprompt implementasi:**

1. Audit `inventory_transactions` dan seluruh proses barang keluar. Tentukan apakah semua pengeluaran sudah memerlukan `asset_id` dan `wo_id`.
2. Tambahkan foreign key/index atau migrasi aman bila relasi belum kuat. Jangan menggandakan transaksi lama.
3. Pengeluaran part harus atomik: lock/cek stok, tolak stok negatif, tulis transaksi, perbarui saldo, dan audit dalam satu transaksi database.
4. Tampilkan riwayat dari detail unit, detail WO, dan logistik dengan filter tanggal/part/unit/WO.
5. Tangani reversal/cancel sebagai transaksi/audit, bukan delete histori.
6. Uji concurrent/repeated request, stok tidak cukup, reversal, pembatasan role, dan rekonsiliasi saldo.

**Acceptance criteria M6:** satu transaksi dapat ditemukan dari unit maupun WO; perhitungan saldo konsisten; tidak ada stok negatif akibat race/retry; histori tidak hilang saat request dibatalkan.

## M7 — Notifikasi WO Baru

**Requirement:** WO baru dan perubahan penting memberi notifikasi kepada logistik serta pihak terkait, dengan tautan/konteks menuju WO.

**Microprompt implementasi:**

1. Audit `system_notifications`, `api/notifications.php`, pemanggilan notifikasi dari WO, badge/dropdown UI, dan audience filtering.
2. Jangan membuat notifikasi global yang membocorkan WO lintas lokasi/role. Simpan recipient user/role/location atau model audience yang dapat ditegakkan server.
3. Minimal event: WO dibuat, kebutuhan part/jasa diajukan, status kebutuhan berubah, WO ditutup/dibatalkan.
4. Pastikan satu event tidak membuat notifikasi ganda pada retry.
5. Implementasikan unread/read dan tautan membuka detail WO yang benar.
6. Email/WhatsApp tidak otomatis termasuk scope. Jika belum ada gateway/keputusan eksplisit, catat sebagai integrasi eksternal yang belum diputuskan—jangan memalsukan pengiriman.

**Acceptance criteria M7:** recipient yang tepat menerima satu notifikasi; role/lokasi lain tidak; badge/read state akurat; klik membuka WO yang benar; tidak ada klaim email/WhatsApp tanpa bukti gateway.

## M8 — Pemantauan Produktivitas dan Telematika

**Requirement:** ketersediaan alat, downtime, tingkat kerusakan, KPI, dan rekonsiliasi telematika dapat dipantau per periode, dengan sumber data dan rumus yang jelas.

**Microprompt implementasi:**

1. Petakan sumber setiap metrik: operasi/LHO, WO, telematics, inspection, fuel, atau manual input.
2. Tulis definisi numerik untuk availability, downtime, failure/breakdown rate, MTBF/MTTR bila ditampilkan, dan KPI lain. Nyatakan pembilang, penyebut, unit, timezone, aturan data kosong, dan rentang tanggal inklusif/eksklusif.
3. Hitung derived value di server dari data sumber; jangan percaya nilai KPI kiriman client.
4. Bedakan data aktual, estimasi, dan tidak tersedia. Jangan menampilkan nol bila maknanya “tidak ada data”.
5. Buat rekonsiliasi telematika vs catatan manual dengan toleransi yang terdokumentasi dan daftar exception yang dapat ditindaklanjuti.
6. Pastikan filter periode, unit, kategori, dan lokasi konsisten pada kartu ringkasan serta tabel detail.
7. Uji boundary tanggal, pembagian nol, overlapping downtime, data telematika hilang, lokasi berbeda, dan dataset besar yang wajar.

**Acceptance criteria M8:** setiap angka dapat ditelusuri ke sumber dan rumus; total kartu cocok dengan detail; filter konsisten; missing data tidak disamarkan sebagai nol; scope lokasi berlaku.

## M9 — Ekspor dan Impor CSV

**Requirement:** data kontrak dapat diimpor dan diekspor dalam CSV untuk analisis produktivitas, dengan format terdokumentasi dan error per baris.

**Microprompt implementasi:**

1. Temukan definisi “data kontrak” dari kode/data/dokumen aktif. Jika istilah memiliki lebih dari satu arti dan mengubah model data secara material, tandai `NEEDS DECISION`; tetap audit engine CSV yang sudah ada.
2. Tetapkan template kolom, encoding UTF-8, delimiter, header wajib, tipe data, format tanggal/angka, nilai enum, dan contoh aman.
3. Import harus memiliki preview/dry-run, validasi per baris, nomor baris, pesan kesalahan spesifik, ringkasan accepted/rejected, dan aturan duplikat.
4. Hindari partial import diam-diam. Gunakan transaksi atau hasil parsial yang eksplisit sesuai keputusan desain.
5. Export harus mengikuti filter dan scope pengguna, memiliki header stabil, serta menetralkan formula injection untuk nilai berawalan `=`, `+`, `-`, atau `@`.
6. Uji CSV valid, header hilang, delimiter salah, UTF-8, nilai berbahaya, duplikat, file besar, role terlarang, dan round-trip export → import.

**Acceptance criteria M9:** template tersedia; error menunjuk baris/kolom; hasil import persisten; export dapat dibuka dan diimpor ulang; formula injection dinetralkan; data lintas lokasi tidak bocor.

## M10 — Biaya, Transaksi, PO, dan SPPU

**Requirement terkonfirmasi:** biaya perbaikan/pengeluaran dapat ditelusuri berdasarkan unit dan WO, mencakup kategori, nilai, tanggal, vendor/pihak, referensi, serta sumber pencatatannya.

**Microprompt implementasi:**

1. Audit sumber biaya aktual: `cost_financial_monthly`, purchase request/order, inventory issue, report SPPU, atau struktur lain.
2. Tetapkan satu model ledger/transaksi atau read model yang menghindari double counting.
3. Kaitkan biaya ke `asset_id` dan `wo_id` bila relevan; tampilkan drill-down dari ringkasan ke transaksi sumber.
4. Hitung agregasi di server dengan aturan currency, pembulatan, void/reversal, dan periode yang jelas.
5. Terapkan permission biaya karena data finansial tidak otomatis boleh dilihat semua role.
6. Uji total per unit, per WO, per periode, reversal, record tanpa WO, location scope, dan role tanpa izin.

**Decision gate PO/SPPU:** integrasi penuh dengan PO dan formulir SPPU masih berupa usulan. Jangan menetapkannya sebagai keputusan rapat. Audit dahulu apakah relasi aktif sudah ada. Bila perilaku yang diinginkan belum jelas, ajukan pertanyaan tunggal berikut dengan opsi konkret: “Apakah biaya WO harus otomatis terbentuk dari (A) PO yang disetujui, (B) SPPU yang difinalkan, (C) keduanya dengan deduplikasi, atau (D) tetap dicatat manual?” Tandai hanya subfitur itu `NEEDS DECISION`; jangan memblokir ledger biaya dasar.

**Acceptance criteria M10:** total dapat ditelusuri ke transaksi; tidak ada double counting; reversal tidak menghapus audit; akses finansial dibatasi; keputusan PO/SPPU dilaporkan secara eksplisit.

## M11 — QC Berlapis dan Regression Gate

**Tujuan:** membuktikan kesesuaian website dengan request, bukan hanya membuktikan kode dapat dijalankan.

### M11.1 Static dan syntax QC

Jalankan pemeriksaan yang tersedia di repository, minimal:

- PHP lint untuk seluruh file PHP aktif yang berubah dan akhirnya seluruh file PHP aktif;
- pencarian conflict marker;
- audit blok JavaScript/HTML yang tersedia;
- pencarian mojibake pada file yang disentuh;
- pemeriksaan tidak ada secret/debug dump baru;
- review diff untuk perubahan tak sengaja.

### M11.2 Automated test

Jalankan test unit, integration, report integration yang terdampak, dan security suite. Gunakan perintah resmi repository bila ada. Catat command, exit code, jumlah pass/fail/skip, dan ringkasan kegagalan. Test yang tidak dijalankan harus diberi alasan—bukan dianggap lulus.

### M11.3 Database QC

Untuk setiap requirement, verifikasi langsung:

- schema/index/foreign key yang diharapkan;
- record yang dibuat atau diperbarui;
- konsistensi relasi asset ↔ WO ↔ request ↔ inventory ↔ cost ↔ notification;
- tidak ada orphan, duplikasi akibat retry, atau saldo negatif;
- data tetap ada setelah reload dan login ulang.

Gunakan query read-only untuk bukti setelah skenario uji. Masking data sensitif wajib.

### M11.4 API dan RBAC QC

Untuk setiap operasi penting, uji matriks minimum:

| Kondisi | Ekspektasi |
|---|---|
| tanpa session | `401` atau redirect login yang sesuai |
| session tanpa permission | `403`, database tidak berubah |
| role benar, payload valid | sukses dengan status HTTP yang benar |
| payload invalid | `400/422`, pesan aman dan spesifik |
| record tidak ditemukan | `404` |
| lokasi di luar scope | `403/404`, tanpa kebocoran data |
| CSRF invalid pada mutasi | ditolak, database tidak berubah |
| method salah | `405` |

Jangan hanya menguji visibility tombol. Kirim request langsung ke API untuk negative-path.

### M11.5 Browser/E2E QC

Jalankan minimal skenario berikut melalui UI server lokal:

1. tambah dan edit aset lengkap;
2. tampilkan identitas aset pada dashboard/WO/logistik;
3. buat WO, tugaskan mekanik, ubah status, lengkapi penyebab/tindakan, lalu tutup;
4. pilih masing-masing cabang kebutuhan part, jasa, dan tanpa part;
5. proses part keluar dan lihat histori dari unit/WO;
6. terima dan baca notifikasi dengan recipient yang benar;
7. filter produktivitas/telematika per periode;
8. dry-run import CSV invalid dan import CSV valid, lalu export;
9. lihat biaya per unit dan WO;
10. logout lalu pastikan Back/refresh tidak membuka dashboard.

Pada semua skenario: cek Console, Network, loading state, empty state, error state, double-click/retry, reload persistence, dan navigasi kembali.

### M11.6 Responsive dan accessibility smoke QC

Uji minimal lebar 360 px, 768 px, 1366 px, dan 1920 px. Pastikan tabel/form/modal/menu tetap dapat digunakan, tidak ada overflow yang memutus aksi utama, fokus keyboard terlihat, label form terhubung, tombol memiliki nama yang jelas, dialog dapat ditutup, dan pesan error dapat dipahami.

### M11.7 Security regression minimum

Uji input XSS pada nama/deskripsi, SQL injection string, IDOR lintas lokasi, formula injection CSV, file/path tidak sah bila ada upload, error leakage, serta mutasi tanpa CSRF. Jangan melakukan serangan pada production atau data nyata.

**Gate M11:** requirement hanya `PASS` bila acceptance test, negative test, persistence/database check, dan regression test relevan semuanya memiliki bukti. Jika satu lapisan gagal, status maksimal `PARTIAL`.

## M12 — Sinkronisasi Laragon dan Final Verification

1. Selama iterasi setelah database uji berisi data QA penting, sinkronkan kode memakai:

   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase
   ```

2. Impor ulang database penuh hanya jika memang diperlukan dan aman; pahami bahwa mode tanpa `-PreserveDatabase` dapat mereset data uji serta password lokal.
3. Setelah sinkronisasi final, ulangi smoke test dari URL Laragon, bukan dari file statis.
4. Verifikasi file target benar-benar terbarui dan script berakhir sukses (`exit code 0`, atau aturan sukses Robocopy yang dipakai script).
5. Jangan menyatakan produksi siap hanya karena Laragon lulus. Pisahkan hasil local, staging, dan production.

---

## Matriks Acceptance Tim Brian yang Wajib Diisi

Isi tabel ini dengan bukti aktual pada laporan akhir:

| ID | Permintaan | Acceptance criteria ringkas | Status | Bukti kode | Bukti test/UI/DB |
|---|---|---|---|---|---|
| BR-01 | Kelengkapan master aset | SN, pelat, merek/model, tahun, kategori, lokasi persisten; missing jelas |  |  |  |
| BR-02 | Identitas unit lintas modul | kode unit + SN + pelat konsisten di dashboard, WO, logistik |  |  |  |
| BR-03 | Detail WO | cause, action, mechanic, timeline, priority, maintenance type, duration lengkap |  |  |  |
| BR-04A | Part dari WO | request part terhubung dua arah dan dapat ditindaklanjuti |  |  |  |
| BR-04B | Jasa dari WO | request jasa/bengkel terhubung dan berstatus |  |  |  |
| BR-04C | Tanpa part | keputusan eksplisit tersimpan dan dapat diaudit |  |  |  |
| BR-05 | Riwayat penggunaan part | part, qty, waktu, unit, WO, actor, saldo dapat ditelusuri |  |  |  |
| BR-06 | Notifikasi WO | audience benar, tidak ganda, read state dan link berfungsi |  |  |  |
| BR-07A | Availability/downtime | angka per periode memiliki sumber dan rumus |  |  |  |
| BR-07B | Failure rate/KPI | agregat cocok dengan detail dan aman untuk no-data |  |  |  |
| BR-07C | Rekonsiliasi telematika | variance/exception dapat ditelusuri |  |  |  |
| BR-08 | Impor-ekspor CSV | template, dry-run, error per baris, round-trip, formula-safe |  |  |  |
| BR-09A | Biaya/transaksi | biaya per unit dan WO dapat ditelusuri tanpa double count |  |  |  |
| BR-09B | Integrasi PO/SPPU | keputusan bisnis + implementasi/batas scope tercatat jujur |  |  |  |

## Format Laporan Akhir Wajib

Susun jawaban akhir dengan urutan berikut:

1. **Outcome singkat** — berapa item `PASS/PARTIAL/FAIL/BLOCKED` dan apakah layak diuji pengguna.
2. **Peta sistem aktual** — alur entry point, frontend, API, database, serta role dalam bentuk ringkas.
3. **Perubahan yang dilakukan** — kelompokkan per requirement, sertakan file/tabel utama.
4. **Matriks acceptance Tim Brian** — tabel BR-01 sampai BR-09B yang sudah diisi.
5. **Hasil QC** — command/test, pass/fail/skip, browser, API/RBAC, database, responsive, dan security regression.
6. **Kegagalan atau batasan** — tulis akar masalah dan dampak; jangan menyembunyikan test gagal.
7. **Keputusan yang masih dibutuhkan** — khusus hal yang memang tidak bisa disimpulkan, terutama PO/SPPU.
8. **Cara verifikasi oleh pengguna** — langkah singkat dari login sampai alur inti, tanpa membocorkan credential.
9. **Daftar file berubah** — pisahkan perubahan agent dari perubahan awal milik pengguna.

Gunakan tautan file dan nomor baris bila lingkungan mendukung. Hindari laporan panjang yang hanya menceritakan proses; utamakan hasil, bukti, dan sisa risiko.

## Definition of Done Global

Pekerjaan baru boleh dinyatakan selesai jika:

- [ ] sistem telah ditelusuri end-to-end dan peta aktual tersedia;
- [ ] seluruh BR-01 sampai BR-09B memiliki status serta bukti;
- [ ] setiap perubahan data bertahan setelah reload/login ulang;
- [ ] frontend, API, database, RBAC, dan audit trail konsisten;
- [ ] positive path dan negative path telah diuji;
- [ ] tidak ada error JavaScript baru atau HTTP 500 pada alur utama;
- [ ] tidak ada regresi keamanan kritis yang diketahui;
- [ ] tampilan inti usable pada mobile dan desktop;
- [ ] test otomatis relevan lulus, atau kegagalannya dilaporkan jujur;
- [ ] sinkronisasi dan smoke test Laragon berhasil;
- [ ] integrasi PO/SPPU tidak diklaim selesai tanpa keputusan dan bukti;
- [ ] seluruh catatan “sudah berfungsi” memiliki bukti yang dapat direproduksi.

## Catatan Tata Kelola

- Target “sekitar satu minggu” adalah batas perencanaan, bukan alasan mengurangi QC atau memalsukan status.
- Uji coba pengguna dan masukan WhatsApp adalah fase UAT, bukan pengganti automated test atau bukti database.
- Pertemuan finalisasi Senin malam harus memakai matriks acceptance di atas sebagai bahan keputusan.
- Ekspektasi ideal yang belum pernah disetujui harus diberi label `PROPOSED`, bukan dipresentasikan sebagai keputusan rapat.
