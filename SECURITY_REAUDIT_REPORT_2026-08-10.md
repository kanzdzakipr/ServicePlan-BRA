# SECURITY RE-AUDIT REPORT

**Project:** ServicePlan-BRA  
**Audit date:** 10 Agustus 2026  
**Scope:** Current source, Git exposure, and read-only Hostinger verification  
**Overall status:** **TIDAK AMAN UNTUK PRODUCTION**

> Laporan ini adalah status terbaru. `SECURITY_AUDIT_REPORT.md` tetap dipertahankan sebagai
> baseline historis. Nilai credential, password, session ID, token, dan hash tidak direproduksi.
> Tidak ada kode, akun, atau database yang diubah. Satu sesi verifikasi login langsung di-logout.

## 1. Executive Summary

| Area | Status | Kesimpulan |
|---|---|---|
| Source terbaru | ⚠️ PERLU PERBAIKAN | Auth, CSRF, CORS, session, IDOR scope, dan upload membaik; XSS, workflow, validation, limiter, dan RBAC fallback belum aman |
| Hostinger staging | ❌ CRITICAL | Credential sementara predictable berhasil login ke akun privileged |
| Repository GitHub | ❌ CRITICAL | Repo public menyediakan SQL dump, `data.json`, material operasional, dan riwayat secret |
| Production readiness | ❌ FAIL | P0 dan P1 wajib selesai sebelum go-live |

Kesimpulan: epic authentication, IDOR, dan upload sudah menjadi fondasi yang baik, tetapi
deployment aktif tidak sama dengan source terbaru. Exposure repository dan login seed yang
berhasil adalah kerentanan nyata, bukan risiko teoretis.

## 2. Scope dan Metode

| Pemeriksaan | Cakupan | Hasil |
|---|---|---|
| Repository mapping | Frontend, 17 API/core PHP, 56 archive PHP, JavaScript, SQL, config, CI | Selesai |
| Static analysis | Auth/RBAC/IDOR, injection, XSS, upload, workflow, errors, storage, secrets | Selesai |
| Git exposure | Current tree, tracked data/dump/material, remote visibility, history metadata | Selesai |
| Automated checks | Unit, static guardrail, PHP lint | 59 PASS, 0 FAIL; 73 PHP lint PASS |
| Hostinger | Anonymous endpoints, guards, sensitive paths, HTTPS, CORS, headers | Selesai, read-only |
| Login seed | Satu login akun privileged lalu logout | **LOGIN BERHASIL — CRITICAL** |
| Live limited-user IDOR | Perlu akun dan objek staging khusus | **NEEDS MANUAL VERIFICATION** |
| DB grants/TLS/restore | Tidak dapat dibuktikan dari source | **NEEDS MANUAL VERIFICATION** |

## 3. Critical Findings

| ID | Severity | Lokasi/bukti | Masalah | Dampak/skenario | Rekomendasi |
|---|---|---|---|---|---|
| REA-001 | **CRITICAL** | Hostinger; `api/auth.php:88-110`; `.env.example` | Hostinger menerima credential sementara predictable untuk akun privileged. Password dimasking `********`. Source terbaru seharusnya menolaknya di production, sehingga deploy mungkin stale, environment salah, atau override seed aktif | Username/pola dari repo dapat dipakai mengambil alih aplikasi. Verifikasi berhasil dan sesi langsung di-logout tanpa membuka data | Batasi/tutup staging; rotasi semua password; set `APP_ENV=production`; pastikan seed override off; deploy source terbaru; invalidasi session; audit login/access log |
| REA-002 | **CRITICAL** | Repo GitHub terverifikasi public; `scripts/u646470441_ServicePlanBRA.sql:992-1013`; `scripts/schema.sql:570`; `archive/database/schema.sql:19`; `data.json`; `material/`; `raw-material/`; history `b16f1e5` | Dump, username, hash, data aset/biaya, dan dokumen internal public. Sebanyak 16 hash dump utama terverifikasi cocok dengan pola password sementara; nilainya tidak dicetak | Offline cracking, credential reuse, account takeover, exposure data operasional/PII/finansial, dan social engineering | Jadikan repo private; rotasi DB dan akun; cabut credential lama; keluarkan dump/data/material; rewrite history setelah rotasi; review clone/fork/artifact; lakukan privacy incident assessment |

## 4. High Priority Findings

| ID | Severity | Lokasi | Masalah | Dampak/skenario | Rekomendasi |
|---|---|---|---|---|---|
| REA-003 | **HIGH** | `dashboard.view.php:4271-4300,4519,5181-5184,6733-6738`; duplikat `dashboard.html`; API write/sync | Stored DOM XSS masih ada. Data API masuk `innerHTML` tanpa encoding, dan HTML escaping dipakai dalam inline `onclick`, yang tidak aman untuk JavaScript context | User tulis level rendah dapat menyimpan payload yang berjalan saat admin membuka dashboard | Gunakan DOM API/`textContent`, hapus inline handler, schema validation, XSS tests, lalu CSP nonce/hash |
| REA-004 | **HIGH** | `api/init.php:6-36`; `data.json` | Semua role dengan `dashboard.read` menerima `summary.financials` dan seluruh `costs`; hanya asset/WO yang di-scope | User operasional/lokasi terbatas memperoleh data biaya global | Buat DTO per permission; tambah `costs.read`; filter field/lokasi; jangan fallback ke data sensitif generik |
| REA-005 | **HIGH** | `api/fuel_logs.php:45-56`; `api/logistics.php:98-115`; `api/work_orders.php:49-55,91-95`; `api/reports.php:131-151,287-361` | Derived value dan workflow dipercaya dari client: LPH, anomaly, status/qty SPB, status WO, template global | KPI/biaya dapat dipalsukan; approval/status dapat dilewati; template user lain dapat dipengaruhi | Hitung server-side, state machine per role, template-admin terpisah, optimistic lock/idempotency, audit trail |
| REA-006 | **HIGH** | `api/auth.php:83-111`; batch API | Login limiter hanya di session dan dapat di-reset dengan cookie baru; API tidak punya body/batch/per-user limit | Brute force dan resource exhaustion | Shared limiter per IP+username+account; backoff; alert; body/batch/pagination/upload quota |
| REA-007 | **HIGH** | `dashboard.html` ±451 KB; `.htaccess:20-22`; `SECURITY_DEPLOYMENT.md:49-54` | `dashboard.html` masih dashboard penuh, bukan redirect shell. Guard bergantung pada Apache/LiteSpeed rewrite | Live Server/static hosting/Nginx tanpa rule menjadi fail-open; UI/demo data dapat dibuka langsung | Hapus static dashboard dari artifact atau jadikan shell minimal; hanya render view lewat `dashboard.php`; tambah CI/live assertion |
| REA-008 | **HIGH** | Hostinger: security/deployment markdown = 200; `/archive/` = 503; local-password utility = 500; current source mengharapkan 403 | Deployment drift dan internal reconnaissance exposure | Penyerang mendapat peta kelemahan; legacy/utility dapat aktif ketika konfigurasi berubah | Deploy allowlist artifact; blok docs/archive/scripts; verifikasi 403/404; tampilkan deployed commit/checksum |
| REA-009 | **HIGH** | `api/security.php:331-354` | Permission query kosong/gagal memicu built-in fallback. Revocation semua permission mengaktifkan permission lama | Deny-all/revocation tidak efektif; role tertentu kembali mendapat `*`/`sync.write` | Production harus fail closed; fallback hanya local/test via flag; migration dan health check wajib |
| REA-010 | **HIGH** | Runtime DDL di `api/accidents.php`, `inspections.php`, `logistics.php`, `reports.php` | Request biasa menjalankan `ALTER/CREATE TABLE` dan memerlukan DB privilege berlebih | Kompromi runtime mendapat blast radius schema; migration race/lock | Pindahkan DDL ke migration account; runtime hanya CRUD minimum |

## 5. Medium Priority Findings

| ID | Severity | Lokasi | Temuan | Rekomendasi |
|---|---|---|---|---|
| REA-011 | MEDIUM | `api/db.php:55-64`; banyak endpoint | Exception database dikirim ke client | Generic error + request ID; detail hanya di log ter-redact |
| REA-012 | MEDIUM | `.htaccess`; Hostinger headers | Tidak ada HSTS/Permissions-Policy; CSP hanya `upgrade-insecure-requests`; `X-XSS-Protection` obsolete | CSP nonce/hash, HSTS setelah HTTPS stabil, Permissions-Policy |
| REA-013 | MEDIUM | PDF.js 3.11.174; `scripts/dashboard.js:8277-8281` | Versi masuk affected range CVE-2024-4367. Workaround `isEvalSupported:false` aktif | Upgrade patched/current dan pertahankan workaround; malicious-PDF test |
| REA-014 | MEDIUM | ApexCharts/Chart.js/CDN frontend | Versi tidak dipin dan beberapa CDN tanpa SRI; tanpa lockfile/SBOM | Self-host atau exact version+SRI; inventory/checksum/SCA |
| REA-015 | MEDIUM | `scripts/dashboard.js`; `dashboard.view.php:3838-3879` | Draft/import/state/data operasional tersimpan localStorage/IndexedDB tanpa TTL/cleanup menyeluruh | Namespace per user, TTL, minimisasi, cleanup logout |
| REA-016 | MEDIUM | `api/security.php:83-90,156-158` | Fallback origin/scheme percaya `HTTP_HOST` dan `X-Forwarded-Proto` | Wajibkan `APP_ORIGIN`; percaya proxy header hanya dari trusted proxy |
| REA-017 | MEDIUM | Upload security/archive | Upload-to-RCE source tertutup, tetapi malware scan, quota, limiter, dan live private storage belum diverifikasi | Antivirus/CDR sesuai kebutuhan, quota, audit, live staging test |
| REA-018 | MEDIUM | `archive/actions/backup_database.php` | Dump dibuat di tree aplikasi; DB password ada di process argument | Backup platform, encrypted offsite, credential file aman, restore test |
| REA-019 | MEDIUM | `archive/login.php:5-24` | Login legacy tanpa shared limiter; live `/archive/` belum 403 | Blok total bila deprecated atau satukan auth/limiter utama |
| REA-020 | MEDIUM | `api/accidents.php:49`; `archive/app/helpers.php:32` | ID `time()` dan “last+1” rentan collision/race; `REPLACE` dapat overwrite | UUID/atomic sequence, unique constraint, transaction lock, retry |
| REA-021 | MEDIUM | Seluruh API | Audit log kurang untuk denied access, delete, sync, upload/download, export, status | Structured security audit + alerting |
| REA-022 | MEDIUM | Spreadsheet/CSV export | Formula injection belum dinetralkan konsisten | Prefix `=`, `+`, `-`, `@`; regression test |
| REA-023 | MEDIUM | Data/privacy lifecycle | Retention, deletion, classification, purpose limitation, incident process tidak ditemukan | Inventory/DPIA internal, retention, minimisasi, access review |

## 6. Low Priority Findings

| ID | Severity | Lokasi | Temuan | Rekomendasi |
|---|---|---|---|---|
| REA-024 | LOW | Beberapa API | Error sering HTTP 200 dan envelope tidak konsisten | Gunakan status semantik dan `Allow` pada 405 |
| REA-025 | LOW | `.htaccess` | `X-XSS-Protection` obsolete | Hapus setelah CSP/output encoding benar |
| REA-026 | LOW | Dashboard RBAC UI | Override RBAC hanya state browser/alert, bukan server permission | Tandai mock/read-only atau buat API admin yang diaudit |

## 7. Security Strengths

| Kontrol | Status | Bukti |
|---|---|---|
| Server-side authentication | ✅ | Anonymous Hostinger `/api/assets.php` = 401 |
| Dashboard guard | ✅ LIVE | `dashboard.html` → `dashboard.php` → login; direct view = 403 |
| Session cookie | ✅ | `Secure`, `HttpOnly`, `SameSite=Strict`; regeneration tersedia |
| CSRF | ✅ SOURCE | Token diwajibkan untuk mutating API/form |
| CORS | ✅ LIVE | Evil Origin = 403; allowed origin = 200 |
| HTTPS redirect | ✅ LIVE | HTTP = 301 ke HTTPS |
| Object scope | ✅ SOURCE / ⚠️ LIVE REVIEW | Scope lokasi/owner deny-all tersedia; live IDOR belum diuji |
| SQL injection baseline | ✅ | Native prepared statements; tidak ada SQLi terkonfirmasi |
| Upload hardening | ✅ SOURCE / ⚠️ LIVE REVIEW | MIME-derived extension, signature, random name, private storage/controller |
| Sensitive paths | ⚠️ PARTIAL | `.env`, SQL, `.git`, data/material/tests diblokir; docs/archive/utility belum konsisten |
| Automated tests | ✅ TERBATAS | 22 unit + 37 static PASS; belum menguji XSS/workflow/limiter/deploy drift |

## 8. Dependency Vulnerabilities

| Dependency | Versi | Risiko |
|---|---:|---|
| PDF.js | 3.11.174 | HIGH upstream / MEDIUM residual; CVE-2024-4367, workaround aktif |
| SheetJS CE | 0.20.3 | PASS untuk CVE-2024-22363; fixed mulai 0.20.2 |
| JSZip | 3.10.1 | Tidak ada advisory terkonfirmasi dalam audit ini; tanpa SCA |
| Tesseract.js/core | 5.1.1 | Outdated; **NEEDS MANUAL VERIFICATION** |
| Leaflet | 1.9.4 | Exact version + SRI |
| ApexCharts | mutable CDN | FAIL supply-chain governance |
| Chart.js | mutable CDN | FAIL supply-chain governance legacy |
| Font Awesome | 6.4.0 | Exact version, tanpa SRI |
| qrcodejs | 1.0.0 | Exact version, tanpa SRI/inventory |

Tidak ada package/composer manifest atau lockfile. Buat SBOM dan verifikasi checksum vendor.

## 9. Secrets Exposure

| Item | Lokasi | Status |
|---|---|---|
| DB credential historis | Git history | ❌ EXPOSED; nilai dimasking, wajib dianggap bocor |
| Username + hash | SQL dump public | ❌ EXPOSED; nilai dimasking |
| Password seed predictable | Dump/local workflow/Hostinger | ❌ EXPOSED DAN DITERIMA; `********` |
| Data operasional/finansial | `data.json`, material/raw-material | ❌ EXPOSED |
| Security/deployment docs | Hostinger + repo | ❌ EXPOSED reconnaissance |
| Private key/JWT key | Current tree | ✅ Tidak ditemukan; JWT tidak dipakai |
| High-confidence API/cloud token | Current tree | ✅ Tidak ditemukan oleh pattern scan |
| `.env` production | Current tree/Hostinger | ✅ Tidak tracked; HTTP `/.env` = 403 |

## 10. Production Readiness

| Kontrol | Status | Catatan |
|---|---|---|
| Authentication aman | ❌ FAIL | Predictable credential accepted |
| Authorization aman | ⚠️ NEEDS REVIEW | Scope membaik; cost exposure/fallback tersisa |
| Input validation | ❌ FAIL | Schema/range/body/batch limit belum konsisten |
| Injection protection | ⚠️ NEEDS REVIEW | SQLi tidak ditemukan; XSS/CSV gagal |
| XSS protection | ❌ FAIL | Stored DOM XSS/inline-handler issue |
| CSRF protection | ✅ PASS | Token server-side |
| Session security | ✅ PASS | Cookie flags, timeout, regeneration |
| Password security | ❌ FAIL | Seed accepted; reset/MFA belum ada |
| API security | ❌ FAIL | Workflow tampering, limiter, response exposure |
| File upload security | ⚠️ NEEDS REVIEW | Source hardened; live storage/quota/malware belum diuji |
| Database security | ⚠️ NEEDS REVIEW | Runtime DDL; grants/TLS/ACL manual |
| Secret management | ❌ FAIL | Public repo/history/dump/login seed |
| HTTPS | ✅ PASS | Redirect dan TLS aktif |
| Security headers | ❌ FAIL | CSP/HSTS/Permissions-Policy tidak efektif/lengkap |
| CORS | ✅ PASS | Live allowlist |
| Rate limiting | ❌ FAIL | Session-only login limiter; API limiter tidak ada |
| Dependency security | ❌ FAIL | Affected PDF.js, mutable CDN, tanpa SCA/SBOM |
| Logging & monitoring | ❌ FAIL | Security trail/alert kurang |
| Backup/recovery | ⚠️ NEEDS REVIEW | Legacy design; restore tidak diketahui |
| Privacy/data protection | ❌ FAIL | Repo public berisi data/dokumen internal |

## 11. Final Priority

| Priority | Tindakan |
|---|---|
| **P0 — FIX IMMEDIATELY** | Tutup/batasi staging; rotasi semua password dan DB credential; invalidasi session; production env + seed override off; repo private; keluarkan dump/data/material; audit access/clone; deploy hardening; blok docs/archive/scripts |
| **P1 — FIX BEFORE PRODUCTION** | Hilangkan XSS/inline handler; DTO+permission costs; server workflow; fail-closed RBAC; shared limiter/request limits; DDL migration; generic error; upgrade PDF.js; pin/self-host dependency; CSP/HSTS; security logs |
| **P2 — BEFORE FULL GO-LIVE** | TTL browser storage; CSV protection; atomic numbering; upload malware/quota; encrypted backup/restore; privacy retention/deletion; password reset/MFA readiness |
| **P3 — OPTIONAL HARDENING** | WAF/anomaly detection, signed artifact, independent pentest, incident tabletop, automated SBOM/update |

## 12. Manual Verification

- Runtime/migration DB grants, database TLS, network ACL, and connection logs.
- Hostinger `APP_ENV`, `APP_ORIGIN`, and seed override without exposing values.
- Limited-user IDOR across assets, WO, SPB, accidents, inspections, archive, reports.
- Upload path outside `public_html`, execution disabled, quota, malware handling.
- TLS protocol/cipher, HSTS rollout, CDN/cache, Host/proxy validation.
- Backup encryption, offsite retention, integrity, and restore drill.
- GitHub forks/clones/releases/actions artifact/cache containing old data.
- Privacy/legal response for public operational/personal documents.

## 13. Safer Code Patterns

```javascript
const option = document.createElement('option');
option.value = asset.id;
option.textContent = `${asset.id} (${asset.category})`;
select.replaceChildren(option);
```

```php
if (app_is_production() && $permissions === []) {
    error_log('RBAC configuration unavailable');
    api_json_response(503, ['status' => 'error', 'code' => 'AUTHZ_UNAVAILABLE']);
}
```

