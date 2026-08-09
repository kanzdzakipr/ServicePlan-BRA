# SECURITY ATTACK SIMULATION & RE-AUDIT CHECKLIST

## 1. Informasi Audit

| Atribut | Nilai |
|---|---|
| Tanggal audit | 9 Agustus 2026 |
| Target dinamis | `http://localhost/ServicePlan-BRA/` |
| Target source | Repository `ServicePlan-BRA` dan salinan Laragon |
| Metode | Static analysis dan pengujian HTTP non-destruktif |
| Batas pengujian | Tidak melakukan brute force berkecepatan tinggi, perubahan data bisnis, upload payload, DoS, atau serangan sistem eksternal |
| Secret handling | Seluruh nilai secret dimasking |
| Kesimpulan | **❌ TIDAK AMAN UNTUK PRODUCTION / SHARED LAN** |

| Catatan keselamatan pengujian | Hasil |
|---|---|
| Reachability check | Satu pemeriksaan dilakukan terhadap `scripts/populate_assets.php` |
| Dampak | Script berhenti karena SQL dump tidak tersedia di webroot |
| Verifikasi perubahan | Timestamp diperiksa; tidak ada file atau data yang berubah |
| Utility berisiko | Utility yang menjalankan DDL/UPDATE tidak dipanggil |

## 2. Ringkasan Temuan

| Severity | Jumlah | Status |
|---|---:|---|
| CRITICAL | 3 | ❌ Harus ditutup segera |
| HIGH | 8 | ❌ Harus ditutup sebelum production |
| MEDIUM | 7 | ⚠️ Wajib masuk hardening backlog |
| LOW | 4 | ⚠️ Perbaikan tambahan |
| **Total** | **22** | **❌ Belum production-ready** |

## 3. Hasil Simulasi Attacker

### 3.1 Authentication, Session, CSRF, dan CORS

| No. | Pengujian | Hasil | Status | Catatan |
|---:|---|---|---|---|
| 1 | Buka `dashboard.php` tanpa session | HTTP `302` ke login | ✅ PASS | Dashboard guard bekerja |
| 2 | Buka `dashboard.view.php` langsung | HTTP `403` | ✅ PASS | View internal tidak dapat dipanggil langsung |
| 3 | Akses endpoint data `/api/*.php` tanpa session | HTTP `401` | ✅ PASS | Authentication API aktif |
| 4 | Akses `api/db.php` dan `api/security.php` | HTTP `403` | ✅ PASS | Bootstrap internal diblokir |
| 5 | Login dengan credential lokal valid | Berhasil | ✅ PASS | Authentication server-side bekerja |
| 6 | Bandingkan session ID sebelum/sesudah login | Berubah | ✅ PASS | Session fixation dimitigasi |
| 7 | Request dengan Origin `https://evil.example` | HTTP `403` | ✅ PASS | CORS allowlist bekerja |
| 8 | Login tanpa CSRF token | Ditolak dengan `CSRF_INVALID` | ✅ PASS | Validasi CSRF efektif |
| 9 | Status transport untuk CSRF invalid | HTTP `500`, bukan `419` | ❌ FAIL | Mengganggu monitoring dan client handling |

### 3.2 Authorization dan IDOR/BOLA

| No. | Pengujian | Hasil | Status | Dampak |
|---:|---|---|---|---|
| 1 | Login sebagai helper lokasi 4 | Berhasil | ✅ PASS | Role dapat diuji |
| 2 | Ambil daftar aset sebagai helper | 282 aset dari 18 lokasi | ❌ FAIL | Data lintas lokasi terekspos |
| 3 | Baca langsung aset lokasi lain dengan parameter ID | Berhasil | ❌ FAIL | BOLA/IDOR terkonfirmasi |
| 4 | Pemeriksaan penggunaan `assigned_location_id` pada query | Tidak digunakan | ❌ FAIL | Session scope tidak diterapkan |
| 5 | Pemeriksaan ownership report | Tidak difilter dengan `created_by` | ❌ FAIL | Report user lain berpotensi dapat diakses |
| 6 | Route permission deny-by-default | Aktif | ✅ PASS | Unknown route ditolak |
| 7 | Permission tambahan untuk void report | `reports.approve` | ✅ PASS | Critical action memiliki gate tambahan |

### 3.3 Rate Limiting

| Pengujian | Hasil | Status |
|---|---|---|
| Percobaan gagal 1–5 dalam session sama | `401, 401, 401, 401, 401` | ✅ PASS |
| Percobaan keenam dalam session sama | `429` | ✅ PASS |
| Percobaan dengan cookie/session baru | Kembali `401`, bukan `429` | ❌ FAIL |
| Limit berdasarkan IP + username + account | Tidak tersedia | ❌ FAIL |

### 3.4 Direct File Exposure

| Resource | HTTP | Status | Risiko |
|---|---:|---|---|
| `/scripts/sync_to_laragon.ps1` | 200 | ❌ FAIL | Deployment/configuration disclosure |
| `/temp_seed.json` | 200 | ❌ FAIL | Seed/internal data disclosure |
| `/SECURITY_AUDIT_REPORT.md` | 200 | ❌ FAIL | Security weaknesses disclosure |
| `/Blueprint Website Monitoring.pdf` | 200 | ❌ FAIL | Internal architecture disclosure |
| `/archive/uploads/damage/*.png` | 200 | ❌ FAIL | Upload tanpa authorization |
| `/archive/login.php` | 200 | ❌ FAIL | Legacy attack surface dipublikasikan |
| `/archive/public-unit.php?token=invalid` | 200 + stack trace | ❌ FAIL | SQL error dan absolute path disclosure |
| `/scripts/*.php` | Executable | ❌ FAIL | Maintenance utility dapat dipanggil dari web |
| `/.env` | 403 | ✅ PASS | Environment file diblokir |
| `/.git/config` | 403 | ✅ PASS | Git metadata diblokir |
| `/data.json` | 403 | ✅ PASS | Data source diblokir |
| `/scripts/*.sql` | 403/tidak tersalin | ✅ PASS | SQL dump tidak dapat diunduh |

### 3.5 Injection dan File Handling

| Area | Hasil | Status | Catatan |
|---|---|---|---|
| SQL Injection | Tidak terkonfirmasi | ✅ PASS | Mayoritas query memakai prepared statement |
| Dynamic update column | Menggunakan allowlist | ✅ PASS | Nama kolom tidak langsung dari client |
| NoSQL Injection | Tidak applicable | ➖ N/A | Tidak ada NoSQL datastore |
| LDAP Injection | Tidak applicable | ➖ N/A | Tidak ada LDAP |
| Server-side Template Injection | Tidak ditemukan | ➖ N/A | Tidak ada template engine terkait |
| Stored DOM XSS | Masih memungkinkan | ❌ FAIL | Data API masuk ke `innerHTML`/inline handler |
| CSV/XLSX Formula Injection | Belum dapat dipastikan | ⚠️ NEEDS REVIEW | Perlu pengujian export spreadsheet |
| Upload size limit | Maksimal 10 MB | ✅ PASS | Sudah dibatasi |
| MIME validation | Menggunakan `finfo` | ✅ PASS | Validasi dasar tersedia |
| Stored extension | Dari filename pengguna | ❌ FAIL | Ekstensi executable dapat dipertahankan |
| Upload location | Di bawah webroot | ❌ FAIL | File dapat diakses langsung |
| PHP execution pada upload | Handler PHP global aktif | ❌ FAIL | Potensi webshell/polyglot |
| Malware scanning | Tidak tersedia | ❌ FAIL | Tidak ada AV/re-encoding |

### 3.6 Infrastructure dan Database

| Kontrol | Hasil | Status | Catatan |
|---|---|---|---|
| HTTPS/TLS lokal | HTTP saja | ⚠️ NEEDS REVIEW | Production TLS belum dapat diverifikasi |
| Apache bind | Semua interface | ❌ FAIL | Host LAN dapat mengakses Laragon |
| MySQL bind | Semua interface | ❌ FAIL | Database terpapar ke LAN jika firewall mengizinkan |
| Security headers dasar | Sebagian ada | ⚠️ NEEDS REVIEW | CSP dan `Permissions-Policy` belum ada |
| Server banner | Versi Apache/PHP terlihat | ❌ FAIL | Memudahkan fingerprinting |
| Runtime database account | `root` | ❌ FAIL | Privilege berlebihan |
| Runtime DDL | Ada `CREATE/ALTER TABLE` pada request path | ❌ FAIL | Compromise web dapat mengubah schema |
| Backup encryption/restore test | Tidak dapat dibuktikan dari source | ⚠️ NEEDS MANUAL VERIFICATION | Periksa offsite, retention, enkripsi, dan restore drill |

## 4. Critical Findings

| ID | Severity | Lokasi | Masalah | Bukti/Dampak | Rekomendasi |
|---|---|---|---|---|---|
| RA-001 | CRITICAL | `scripts/populate_assets.php`; `scripts/create_*`; `scripts/import_*`; webroot Laragon | Maintenance utility dapat dieksekusi tanpa autentikasi | Dapat menjalankan import, schema change, atau mutasi data melalui HTTP | Gunakan deployment allowlist; jangan deploy `scripts/`; blok `^scripts/` di Apache |
| RA-002 | CRITICAL | Laragon/Apache/MySQL local config | Predictable local credentials pada service yang listen seluruh interface | Client jaringan dapat mencoba pola `<username>123`; nilai aktual dimasking | Bind ke loopback/firewall; password acak unik; jangan gunakan local mode di shared staging |
| RA-003 | CRITICAL (conditional) | `archive/app/helpers.php:32`; `archive/actions/upload_document.php` | Upload mempertahankan ekstensi pengguna di webroot | File polyglot ber-MIME valid dengan ekstensi executable berpotensi menjadi webshell | Hapus legacy app; storage di luar webroot; MIME-extension mapping; download controller; disable PHP |

## 5. High Findings

| ID | Severity | Lokasi | Masalah | Bukti/Dampak | Rekomendasi |
|---|---|---|---|---|---|
| RA-004 | HIGH | `api/assets.php:11-22`; `api/security.php:253-320`; `api/reports.php:230-265` | Tidak ada row-level authorization | Helper lokasi 4 membaca 282 aset dari 18 lokasi | Scope seluruh query dengan location/owner dari session |
| RA-005 | HIGH | `dashboard.view.php:3038-3047,4519,4578,4595-4620`; `scripts/dashboard.js:14243,15833,16974` | Stored DOM XSS dan inline JS context | Banyak assignment `innerHTML`; HTML escape tidak aman dalam `onclick` | DOM API, `textContent`, `dataset`, `addEventListener`, schema validation, CSP |
| RA-006 | HIGH | Webroot deployment | Dokumen operasional dan security material publik | Audit, seed, blueprint, scripts, dan upload dapat diunduh | Artifact allowlist, private storage, authorized download controller |
| RA-007 | HIGH | `scripts/vendor/pdf-3.11.174.min.js` | PDF.js rentan arbitrary JavaScript execution | PDF berbahaya dapat mengeksekusi JS dalam context PDF.js | Upgrade ke versi patched/supported; isolated worker/origin; resource limits |
| RA-008 | HIGH | `api/auth.php:56-64,98` | Rate limit hanya dalam PHP session | Cookie baru langsung mereset counter | Central rate limiter per IP, username, account, dan action |
| RA-009 | HIGH | `api/assets.php`; `work_orders.php`; `fuel_logs.php`; `logistics.php`; `sync.php` | Business logic dan derived value dipercaya dari client | Status, lokasi, HM/KM, LPH, anomaly, actor, dan quantity dapat dimanipulasi | DTO/schema, enum/range, server calculation, state machine, idempotency |
| RA-010 | HIGH | API accidents/inspections/logistics/notifications/archive/reports | Root privilege dan runtime DDL | Request web dapat `CREATE/ALTER`; root memiliki global grants | Migration account terpisah; runtime account minimum privilege |
| RA-011 | HIGH | `api/reports.php:237-403` | Report ownership memakai browser client key | `created_by` disimpan tetapi tidak dipakai untuk read/update scope | Ownership dari authenticated user; cek setiap lifecycle action |

| Referensi RA-007 | Keterangan |
|---|---|
| [NVD CVE-2024-4367](https://nvd.nist.gov/vuln/detail/CVE-2024-4367) | Risiko arbitrary JavaScript execution; CVSS 8.8 |

## 6. Medium Findings

| ID | Severity | Masalah | Bukti | Rekomendasi |
|---|---|---|---|---|
| RA-012 | MEDIUM | Error dan software version disclosure | API memakai `$e->getMessage()`; stack trace dan server banner terlihat | Generic client error; correlation ID; log internal; hide server tokens |
| RA-013 | MEDIUM | CSP dan third-party supply chain lemah | CSP tidak ada; ApexCharts/Chart.js tidak pinned/SRI | CSP nonce/hash; pin version; SRI; self-host bila relevan |
| RA-014 | MEDIUM | Data sensitif persisten di browser | Banyak penggunaan `localStorage` dan `sessionStorage` | Minimalkan data; expiry; clear saat logout; server-side storage |
| RA-015 | MEDIUM | Audit logging tidak lengkap | Main schema hanya memiliki report audit; CRUD/auth denial tidak konsisten | Central immutable audit trail dan security alerting |
| RA-016 | MEDIUM | Dependency governance tidak reproducible | Tidak ada manifest, lockfile, SBOM, checksum inventory | Tambahkan manifest/lock/SBOM dan scheduled advisory scan |
| RA-017 | MEDIUM | CSRF invalid dikirim sebagai HTTP 500 | Body benar `CSRF_INVALID`, transport salah | Gunakan status standar yang didukung dan integration test |
| RA-018 | MEDIUM | Permission database kosong | `permissions=0`, `role_permissions=0`, fallback hardcoded digunakan | Seed RBAC tables dan hilangkan fallback berbasis nama role |

| Catatan dependency | Status |
|---|---|
| SheetJS 0.20.3 dibanding affected range `<0.20.2` untuk [CVE-2024-22363](https://github.com/advisories/GHSA-5pgg-2g8v-p4x9) | Versi lokal tidak masuk affected range tersebut, tetapi inventory dan update policy tetap diperlukan |

## 7. Low Findings

| ID | Severity | Masalah | Rekomendasi |
|---|---|---|---|
| RA-019 | LOW | Header `X-XSS-Protection` obsolete | Hapus; gunakan CSP |
| RA-020 | LOW | `Permissions-Policy` belum ada | Tambahkan policy sesuai fitur |
| RA-021 | LOW | Method/error response belum konsisten | Standardisasi HTTP status dan JSON envelope |
| RA-022 | LOW | Tidak ada security contact/runbook | Buat security policy dan incident-response runbook |

## 8. Security Strengths

| Kontrol | Status | Bukti |
|---|---|---|
| Authentication server-side | ✅ PASS | Login melalui `api/auth.php` |
| Adaptive password hashing | ✅ PASS | `password_hash` / `password_verify` |
| Session regeneration | ✅ PASS | Session ID berubah setelah login |
| Secure session defaults | ✅ PASS | HttpOnly, SameSite Strict, strict mode, idle dan absolute timeout |
| Production secure cookie | ✅ PASS | Secure flag aktif saat production/HTTPS |
| CSRF validation | ✅ PASS | Request tanpa token ditolak |
| CORS allowlist | ✅ PASS | Origin tidak dipercaya ditolak 403 |
| Route deny-by-default | ✅ PASS | Route tanpa policy ditolak |
| Dashboard protection | ✅ PASS | Unauthenticated redirect dan direct view 403 |
| Prepared SQL | ✅ PASS | Mayoritas query memakai prepared statements |
| Environment DB configuration | ✅ PASS | Credential utama tidak hardcoded di `api/db.php` |
| Sensitive extension blocking | ✅ PASS | `.env`, `.git`, `data.json`, dan SQL dump diblokir |
| Upload size dan MIME basic validation | ✅ PASS | Batas 10 MB dan `finfo` tersedia |
| Password seed production guard | ✅ PASS | Hash seed yang diketahui ditolak pada mode production |

## 9. Production Readiness Checklist

| No. | Area | Status | Alasan / Syarat Lulus |
|---:|---|---|---|
| 1 | Authentication aman | ⚠️ NEEDS REVIEW | Server-side auth ada; predictable credential dan rate-limit bypass harus ditutup |
| 2 | Authorization aman | ❌ FAIL | BOLA lintas lokasi dan ownership report belum diterapkan |
| 3 | Input validation | ❌ FAIL | Belum ada schema/range/enum konsisten di seluruh endpoint |
| 4 | Injection protection | ⚠️ NEEDS REVIEW | Prepared SQL baik; stored DOM XSS dan formula injection belum tuntas |
| 5 | XSS protection | ❌ FAIL | Unsafe `innerHTML`, inline event handler, dan CSP belum ada |
| 6 | CSRF protection | ⚠️ NEEDS REVIEW | Token efektif; status error salah dan coverage semua state-changing route perlu diuji |
| 7 | Session security | ✅ PASS | Regeneration, strict mode, SameSite, timeout, dan server-side logout tersedia |
| 8 | Password security | ❌ FAIL | Hashing baik, tetapi password sementara predictable tidak aman untuk shared network |
| 9 | API security | ❌ FAIL | BOLA, rate limiting, error disclosure, dan business logic trust |
| 10 | File upload security | ❌ FAIL | Legacy upload berada di webroot dan mempertahankan ekstensi pengguna |
| 11 | Database security | ❌ FAIL | Runtime account root dan runtime DDL |
| 12 | Secret management | ⚠️ NEEDS REVIEW | Current source menggunakan environment; rotasi lama dan Git history perlu diverifikasi |
| 13 | HTTPS | ⚠️ NEEDS MANUAL VERIFICATION | Local HTTP; sertifikat, protocol, cipher, dan redirect production belum diuji |
| 14 | Security headers | ❌ FAIL | CSP dan `Permissions-Policy` belum ada; banner bocor |
| 15 | Rate limiting | ❌ FAIL | Counter berbasis session dapat direset dengan cookie baru |
| 16 | Dependency security | ❌ FAIL | PDF.js vulnerable; tidak ada lockfile/SBOM/scanner reproducible |
| 17 | Logging & monitoring | ❌ FAIL | Audit trail dan alert security event belum menyeluruh |
| 18 | Backup/recovery | ⚠️ NEEDS MANUAL VERIFICATION | Enkripsi, offsite, retention, integrity, dan restore test belum terbukti |
| 19 | Privacy/data protection | ❌ FAIL | Data internal, dokumen, upload, dan data lintas lokasi terekspos |

## 10. Final Priority Pengembangan

### P0 — FIX IMMEDIATELY

| Urutan | Pekerjaan | Finding | Risiko yang Ditutup | Definition of Done |
|---:|---|---|---|---|
| 1 | Tutup `/scripts/`, `/archive/`, report audit, seed, blueprint, dan upload dari webroot | RA-001, RA-003, RA-006 | RCE, mutasi database, informasi internal publik | Hanya artifact production allowlist yang terdeploy; seluruh URL sensitif memberi 403/404 |
| 2 | Batasi Apache dan MySQL ke loopback atau VLAN/firewall terkontrol | RA-002 | Akses LAN tanpa izin | Port hanya terlihat dari host/jaringan yang diizinkan; bukti firewall dan bind config tersimpan |
| 3 | Ganti seluruh password sementara predictable dengan password acak unik | RA-002 | Account takeover | Tidak ada pola `<username>123`; reset wajib saat login pertama; credential lama invalid |
| 4 | Hilangkan upload legacy atau pindahkan storage ke luar webroot | RA-003 | Upload-to-RCE | Extension dari MIME allowlist, script execution disabled, file hanya lewat authorized controller |
| 5 | Terapkan row-level authorization untuk lokasi dan ownership | RA-004, RA-011 | BOLA/IDOR | Helper lokasi 4 hanya menerima objek scope lokasi 4; cross-scope ID selalu 403/404 |

### P1 — FIX BEFORE PRODUCTION

| Urutan | Pekerjaan | Finding | Risiko yang Ditutup | Definition of Done |
|---:|---|---|---|---|
| 1 | Refactor sink XSS dan pasang CSP | RA-005, RA-013 | Session/API action melalui stored XSS | Tidak ada untrusted data ke `innerHTML` atau inline handler; CSP enforcement tanpa `unsafe-inline` |
| 2 | Upgrade PDF.js dan inventaris dependency | RA-007, RA-016 | Known-vulnerable dependency | Versi patched/supported; SBOM, checksum, manifest/lock, dan scan CI tersedia |
| 3 | Implementasi rate limiter terpusat | RA-008 | Brute force dan API abuse | Limit tidak dapat direset dengan cookie baru; kombinasi IP/account/username diuji |
| 4 | Tambahkan validation schema dan state machine | RA-009 | Manipulasi status, meter, harga, quantity, actor | Semua input memiliki type/enum/range; derived value dihitung server; invalid transition ditolak |
| 5 | Pisahkan migration dan runtime DB account | RA-010 | Database takeover melalui web compromise | Runtime user tanpa DDL/global grants; migration tidak berjalan dari request HTTP |
| 6 | Seed RBAC database dan hapus fallback role-name | RA-018 | Privilege drift dan konfigurasi ambigu | Permission table terisi; test deny-by-default per role lulus |
| 7 | Generic error response dan benar-kan status CSRF | RA-012, RA-017, RA-021 | Information leak dan observability error | Client tidak melihat stack/path/query; CSRF failure menghasilkan status yang disepakati dan konsisten |

### P2 — FIX AFTER PRODUCTION

| Urutan | Pekerjaan | Finding | Risiko yang Ditutup | Definition of Done |
|---:|---|---|---|---|
| 1 | Kurangi penyimpanan data di browser | RA-014 | Persistent client data exposure | Hanya preference non-sensitif; expiry dan logout cleanup diuji |
| 2 | Bangun audit trail terpusat | RA-015 | Aksi kritis tanpa jejak | Auth, denial, CRUD, permission, export, dan lifecycle report tercatat immutable |
| 3 | Tambahkan monitoring dan alert | RA-015 | Deteksi insiden terlambat | Alert untuk brute force, authorization denial spike, dan utility probing diuji |
| 4 | Uji formula injection pada export | Review item | Spreadsheet command/link abuse | Nilai berawalan `=`, `+`, `-`, `@` dinetralisasi dan test regresi lulus |

### P3 — OPTIONAL HARDENING

| Urutan | Pekerjaan | Finding | Definition of Done |
|---:|---|---|---|
| 1 | Hapus `X-XSS-Protection` obsolete | RA-019 | Header hilang; CSP menjadi kontrol utama |
| 2 | Tambahkan `Permissions-Policy` minimum | RA-020 | Kamera, mikrofon, geolokasi, dan fitur lain hanya tersedia sesuai kebutuhan |
| 3 | Buat security contact dan incident runbook | RA-022 | Owner, severity, escalation, containment, recovery, dan notification terdokumentasi |
| 4 | Siapkan MFA readiness | Hardening | Schema, recovery, enrollment, dan step-up auth memiliki desain dan test plan |

## 11. Needs Manual Verification

| No. | Area | Apa yang Harus Diperiksa | Bukti yang Diharapkan |
|---:|---|---|---|
| 1 | TLS production | Certificate chain, expiry, TLS 1.2/1.3, cipher, HSTS, redirect HTTP→HTTPS | Hasil SSL scanner dan config reverse proxy |
| 2 | Apache override | `.htaccess` benar-benar dibaca dan `AllowOverride` sesuai | Uji 403/404 untuk seluruh file sensitif |
| 3 | Network exposure | Bind address Apache/MySQL, Windows Firewall, router/VLAN, port forwarding | Port scan dari host LAN yang tidak dipercaya |
| 4 | PHP upload execution | Apakah file `.php` dapat dieksekusi di setiap upload directory | Config vhost/directory dan benign non-executing canary test |
| 5 | Database grants | Grants runtime user, TLS database, network ACL, encryption at rest | `SHOW GRANTS`, DB config, dan infrastructure evidence |
| 6 | Backup/recovery | Enkripsi, offsite copy, retention, access control, integrity, restore drill | Restore test bertanggal dan runbook |
| 7 | Secret rotation | Credential lama telah dinonaktifkan dan Git history/artifact dibersihkan | Rotation log, revoked account/key, repository secret scan |
| 8 | Production environment | `APP_ENV=production`, debug off, secure cookie, error display off | Environment/config snapshot yang dimasking |
| 9 | Dependency transitive | Seluruh library lokal/CDN serta license dan advisory | SBOM dan hasil SCA yang reproducible |
| 10 | Cloud/server operations | IAM, patching, EDR, WAF/reverse proxy, log retention, alert routing | Configuration review dan evidence operasional |
| 11 | Privacy | Data classification, legal basis, retention, subject rights, deletion | Privacy inventory dan retention policy |
| 12 | Concurrency | Race pada approval, void, stock, work order, dan report transitions | Parallel request test pada staging dengan data disposable |

## 12. Final Verdict

| Pertanyaan | Jawaban |
|---|---|
| Apakah autentikasi dasar sudah tersedia? | **Ya**, dan guard utama bekerja |
| Apakah membuka dashboard langsung tanpa login masih bisa pada entry point aktif? | **Tidak** pada `dashboard.php`; request tanpa session dialihkan ke login |
| Apakah user terautentikasi sudah dibatasi ke data miliknya/lokasinya? | **Tidak**; BOLA/IDOR lintas lokasi terkonfirmasi |
| Apakah aman dipakai hanya di komputer sendiri? | **Hanya sementara**, jika Apache/MySQL benar-benar loopback-only dan tidak ada user lain |
| Apakah aman dipakai di LAN/shared staging? | **Tidak** |
| Apakah aman untuk production internet-facing? | **Tidak** |
| Gate minimum sebelum shared use | Selesaikan seluruh P0 dan retest |
| Gate minimum sebelum production | Selesaikan P0 dan P1, lalu lakukan retest independen dan seluruh manual verification kritis |

| Keputusan akhir | Status |
|---|---|
| Production readiness | **❌ BLOCKED** |
| Penyebab utama | Maintenance utilities publik, predictable local credential, BOLA/IDOR, conditional upload RCE, XSS, vulnerable dependency, excessive DB privileges |
| Langkah berikutnya | Eksekusi P0 secara berurutan, kemudian jalankan ulang checklist attacker dari clean session dan host LAN terpisah |
