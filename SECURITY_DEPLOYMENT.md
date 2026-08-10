# Security Deployment Checklist — SEC-001 dan SEC-002

Kode aplikasi tidak lagi menyimpan credential database production dan seluruh API aktif
menggunakan session authentication, CSRF, serta authorization server-side.

## 1. Environment variables

Pasang seluruh variable pada `.env.example` melalui control panel hosting, Apache `SetEnv`,
PHP-FPM pool, container secret, atau secret manager. Loader `.env` tersedia untuk deployment;
tempatkan file satu tingkat di atas `public_html` bila hosting mengizinkan. Jika terpaksa berada
di document root, verifikasi request HTTP ke `.env` selalu menghasilkan 403/404.

Untuk local development, set `APP_ENV=local`. Production harus menggunakan
`APP_ENV=production` dan `APP_ORIGIN` dengan URL HTTPS final.

`SESSION_SAVE_PATH` harus menunjuk ke folder server yang writable oleh PHP, tidak dapat
diakses melalui web, dan tidak dipakai bersama aplikasi lain yang tidak tepercaya.

## 2. Credential rotation wajib

Credential database yang sebelumnya berada di Git harus dianggap bocor.

1. Buat database user runtime baru.
2. Berikan hanya SELECT, INSERT, UPDATE, dan DELETE pada tabel yang dibutuhkan.
3. Pasang credential baru sebagai environment variable.
4. Nonaktifkan credential lama.
5. Audit database connection log.
6. Bersihkan Git history hanya setelah credential lama dinonaktifkan.

Jangan menulis nilai credential baru ke file repository, SQL dump, screenshot, tiket, atau
dokumentasi.

## 3. Password pengguna

Hash seed yang lama menggunakan password bersama yang telah diketahui. Sebelum membuka
akses production:

- set password acak unik untuk setiap akun;
- paksa distribusi password melalui kanal aman;
- hapus credential contoh dari data production;
- nonaktifkan akun yang tidak diperlukan.

Untuk development lokal saja, `scripts/set_local_temporary_passwords.php` dapat menghasilkan
password sementara dengan pola `username123`. Script menolak environment production, dan
login production juga menolak pola tersebut walaupun hash database lokal ikut tersalin.

## 4. Web server

- Document root harus mengarah ke project ini dengan `.htaccess` aktif.
- `dashboard.html` hanya berisi shell pengarah login dan pada Apache diarahkan ke
  `dashboard.php`.
- `dashboard.view.php` adalah view internal; akses HTTP langsung harus ditolak.
- Tolak akses ke `.git`, `data.json`, `material/`, `raw-material/`, SQL dump, dan config.
- Untuk Nginx, salin aturan tersebut ke konfigurasi Nginx; `.htaccess` tidak berlaku.
- Paksa HTTPS sebelum production.

## 5. Verification

Uji pada staging:

1. `GET /api/assets.php` tanpa cookie menghasilkan HTTP 401.
2. `POST /api/assets.php` tanpa CSRF menghasilkan HTTP 419.
3. User tanpa permission menghasilkan HTTP 403.
4. `GET /dashboard.html` diarahkan ke session-protected `dashboard.php`.
5. `GET /dashboard.view.php` ditolak atau diarahkan kembali ke login.
6. `GET /data.json`, `/.git/HEAD`, `/material/...`, dan `/raw-material/...` ditolak.
7. Login sukses meregenerasi session ID dan logout menghapus cookie.
8. Request dari Origin yang tidak diizinkan menghasilkan HTTP 403.

## 6. Object authorization dan upload storage

- Jalankan ulang `scripts/security_rbac_migration.sql` dengan migration account agar permission
  `scope.all_locations` dan `reports.read_all` tersedia untuk role global.
- User non-global wajib memiliki `assigned_location_id`; nilai kosong menghasilkan deny-all.
- Review report legacy dengan `created_by IS NULL`. Jangan melakukan backfill ownership tanpa
  bukti pemilik yang sah.
- Set `UPLOAD_STORAGE_PATH` ke absolute path yang writable oleh PHP dan berada di luar
  `public_html`/document root.
- Migrasikan upload legacy ke private storage dan pertahankan storage key relatif pada database.
- Pastikan `/archive`, `/storage`, `/tests`, dan maintenance file di `/scripts` memberi 403/404.

## 7. Automated security tests

Jalankan dari PowerShell:

```powershell
.\tests\run_security_tests.ps1
```

Untuk HTTP smoke test read-only:

```powershell
$env:SECURITY_TEST_BASE_URL='https://staging.example.com'
$env:SECURITY_TEST_LIMITED_USER='limited-test-user'
$env:SECURITY_TEST_LIMITED_PASSWORD='[set-via-secure-channel]'
$env:SECURITY_TEST_CROSS_SCOPE_ASSET_ID='KNOWN-ASSET-OUTSIDE-ASSIGNMENT'
.\tests\run_security_tests.ps1
```

Gunakan akun staging terbatas dan aset disposable/read-only. Jangan menaruh credential test di
repository, command history bersama, screenshot, atau laporan. Hapus environment variable setelah
pengujian. HTTP suite tidak melakukan mutasi data.
