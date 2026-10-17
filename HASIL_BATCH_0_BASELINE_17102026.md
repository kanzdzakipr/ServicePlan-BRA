# Hasil Batch 0 — Baseline Aman (17 Oktober 2026)

Status: **PARTIAL untuk kesiapan implementasi; PASS untuk audit baca-saja.** Tidak ada perubahan kode aplikasi, migrasi, sinkronisasi webroot, seed, reset password, atau penulisan database pada batch ini. Satu-satunya keluaran adalah dokumen ini. `RENCANA_BATCH_REVISI_17102026.md` sudah merupakan file baru sebelum audit dimulai dan tidak diubah di batch ini.

## Lingkungan lokal dan isolasi pengguna lain

- Instalasi yang ditemukan: `C:\laragon2`; webroot lokal yang sudah ada: `C:\laragon2\www\ServicePlan-BRA`.
- PHP CLI: `C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe` (PHP 8.3.16). MySQL CLI: `C:\laragon2\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe`.
- Koneksi baca-saja ke `127.0.0.1:3306` berhasil; datadir server menunjuk `C:\laragon2\data\mysql-8.4\`. Nama database yang terbaca oleh server: `u646470441_serviceplanbra` (MySQL mengembalikan nama dalam huruf kecil).
- `scripts/sync_to_laragon.ps1` **tidak dijalankan**. Script saat ini mengunci target di bawah `C:\laragon\www`, memakai `robocopy /MIR`, menambahkan blok `.htaccess` ke target, dan tetap menjalankan migrasi ketika `-PreserveDatabase` aktif. Eksekusi pada Laragon2 akan salah target/ditolak dan `/MIR` dapat menghapus file target yang tidak ada di repo. Jangan mengubah default repo hanya untuk mesin ini.
- Sebelum sync berikutnya, perlukan cara lokal yang eksplisit dan aman: target absolut Laragon2 yang tervalidasi, pengecualian file target milik pengguna, dry-run daftar copy/delete, `-PreserveDatabase`, migrasi terpisah yang sudah direview, serta bukti bahwa tidak ada service/database orang lain yang tersentuh. Tidak ada otorisasi dalam Batch 0 untuk menjalankan operasi tersebut.

## Baseline database (hanya SELECT)

| Objek | Kondisi lokal |
|---|---:|
| Jumlah tabel | 54 |
| `assets` | 282 baris |
| `work_orders` | 366 baris |
| `system_notifications` | 6 baris |
| `roles` | 10 baris |
| `role_permissions` | 0 baris |
| `contract_assignments` | 0 baris |
| `asset_cost_transactions` | tabel belum ada |
| `asset_hull_history`, `asset_gallery_photos`, `asset_operator_history`, `asset_service_history` | tabel belum ada |

`asset_cost_transactions` ada dalam `scripts/schema.sql` tetapi tidak ada di database Laragon2 yang terhubung. Karena itu fitur biaya tidak boleh dinyatakan lulus secara runtime hanya berdasarkan kode. `role_permissions` yang kosong perlu dicocokkan dengan otorisasi aktual dan seed/migrasi yang berlaku; jangan mengisi otomatis tanpa audit izin. Angka-angka di atas hanya snapshot lokal pada saat pemeriksaan.

## Baseline static/unit test baca-saja

| Pemeriksaan | Hasil |
|---|---|
| PHP lint 12 file inti (API aset, WO, support, notifikasi, produktivitas, kontrak, biaya, security; core biaya/produktvitas/KPI/WO support) | 12/12 tanpa syntax error |
| `tests/security_static.php` | 39 PASS, 0 FAIL |
| `tests/security_unit.php` | 22 PASS, 0 FAIL |
| `tests/work_order_ticket_fields.php` | 11 PASS, 0 FAIL |

Tes integrasi PHP yang terhubung database **tidak dijalankan**: beberapa test membuat/menghapus data `QA-BRIAN-`, dan beberapa endpoint melakukan `ensureTable()`/seed bahkan pada akses GET. Browser/UAT lintas role juga belum dijalankan. Pencarian indikatif menemukan teks mojibake di `scripts/dashboard.js`; itu backlog kualitas teks, bukan bukti gagal fungsional Batch 0.

## Peta sistem dan matriks keterlacakan awal

Alur aktif secara umum: `index.html`/login → `dashboard.php` + `dashboard.view.php` → `scripts/dashboard.js` → endpoint `api/*.php` → helper `core/*.php` → MySQL. `api/security.php` memetakan izin route; `api/assets.php` dan `api/work_orders.php` memakai `AuthMiddleware` serta pembatasan lokasi. Status berikut adalah status **bukti kode + snapshot skema lokal**, bukan hasil UAT.

| ID | Alur UI → API → data | Status awal | Gap/tes penerimaan berikutnya |
|---|---|---|---|
| M3 identitas aset | Master Asset/Unit Properties → `api/assets.php` → `assets` | Sebagian | Identitas konsisten lintas dashboard/WO/logistik; audit nomor lambung belum ada. |
| M4 detail WO | Modal/kartu WO → `api/work_orders.php` → `work_orders`, `work_order_timeline` | Sebagian | Form dasar dan unit test ada; UAT transisi, timeline, foto, role, serta refresh belum dilakukan. |
| M5 part/jasa WO | Detail WO → `api/work_order_support.php`/`core/WorkOrderSupport.php` → SPB & permintaan jasa | Sebagian | Uji tiga pilihan, void SPB, dan otorisasi lintas role pada Laragon2. |
| M6 pemakaian part | Laporan BHW-OUT/Logistik → inventory/WO support → `inventory_transactions` | Sebagian | Uji saldo, pembalikan, dan penelusuran dari unit maupun WO. |
| M7 notifikasi | Badge/dropdown → `api/notifications.php` dan event WO → `system_notifications` | Sebagian | Penerima per user/role dan `MARK_ALL_READ` belum terisolasi; uji lokasi dan target WO. |
| M8 produktivitas | Menu Produktivitas → `api/productivity.php`, `api/kpi.php` → telematika/LHO/WO | Sebagian | Kalkulator dan rekonsiliasi ada; uji angka vs sumber, missing data, periode, lokasi. |
| M9 CSV kontrak | Tab kontrak → `api/contracts.php`/`core/ContractCsv.php` → `contract_assignments` | Sebagian | Engine dan test ada; tabel lokal kosong, perlu uji import/export dan RBAC di Laragon2. |
| M10 biaya | Menu Biaya → `api/costs.php`/`core/CostLedger.php` → `asset_cost_transactions` | **Belum siap lokal** | Tabel lokal belum ada; migrasi aman dan keputusan sumber PO/SPPU diperlukan. |
| Outline 1 tiket WO | `#modalNewWO` → `api/work_orders.php`, `api/sync.php` → `work_orders` | Sebagian | Field baru dan tes unit ada; uji runtime pada database lokal dan status unit. |
| Outline 2 onboarding unit | `#modalNewAsset`/PM → `api/assets.php` → `assets` | Sebagian | Histori operator/servis, galeri, PDF, legalitas, estimasi persisten belum ada. |
| GLB-A pencarian | Header → pencarian JS → data browser | Sebagian | API multi-kunci + scope lokasi + buka Unit Properties langsung. |
| GLB-B rename lambung | Modal rename → `api/assets.php` → `assets.alias_name` | Sebagian | Histori audit dan izin khusus belum ada. |
| GLB-C tabel PM | PM Forecast Tracker → `scripts/dashboard.js`/`api/pm_plans.php` → data PM | Sebagian | UI 11 kolom ada; galeri/PDF prototipe lokal dan filter site/persistensi perlu dituntaskan. |
| GLB-D rumus KPI | Panel rumus → `api/kpi.php`/`core/KpiCalculator.php` → sumber telematika/WO | Sebagian | Mesin dan tes ada; cocokkan rumus tertulis UI, inspector, dan data lokal. |
| GLB-E dokumen WO/JO | Detail/cetak WO → reporting | Belum | Alias dokumen WO/JO dari satu record dan `doc_alias` belum ditemukan. |

## Gate menuju Batch 1

Batch 1 notifikasi dapat dimulai pada kode repository, tetapi **jangan sinkronisasi atau migrasi** sebelum jalur Laragon2 yang aman selesai. Pertahankan database lokal dan perubahan pengguna. Setelah implementasi, uji dengan akun/role berwenang dan tidak berwenang, serta lokasi berbeda; hanya data uji baru berprefiks `QA-BRIAN-` dan cleanup yang disetujui secara eksplisit.

Checkpoint: `MICROPROMPT: M0/M1/M2 — baseline awal`; `STATUS: PARTIAL`; `DITEMUKAN: target Laragon2 aktif, script sync salah target, tabel biaya hilang, role_permissions kosong`; `DIUBAH: hanya HASIL_BATCH_0_BASELINE_17102026.md`; `DIVERIFIKASI: SELECT baca-saja, lint 12 file, 72 PASS unit/static`; `RISIKO/BERIKUTNYA: siapkan sinkronisasi Laragon2 aman dan audit RBAC sebelum UAT`.
