---
name: sync-to-laragon
description: Menjalankan sinkronisasi kode ServicePlan-BRA dan database MySQL (scripts/u646470441_ServicePlanBRA.sql) ke Laragon (C:\laragon\www\ServicePlan-BRA) menggunakan scripts/sync_to_laragon.ps1 secara otomatis setiap sesi prompt/perubahan selesai.
---

# Skill: Sync to Laragon (ServicePlan-BRA)

Skill ini digunakan untuk menyelaraskan (sinkronisasi) seluruh file kerja *ServicePlan-BRA*, konfigurasi lingkungan lokal, dan skema database MySQL ke instalasi lokal **Laragon** pengguna menggunakan script PowerShell bawaan [sync_to_laragon.ps1](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/sync_to_laragon.ps1).

---

## 1. Kapan Harus Dijalankan (Execution Triggers)

Jalankan sinkronisasi Laragon pada kondisi berikut:
1. **Setiap Selesai Perubahan Kode/Prompting**: Setiap kali ada penambahan fitur, bugfix, revisi frontend (HTML, CSS, JS) atau backend (PHP, API, Security) sebelum menutup sesi respon ke pengguna.
2. **Perubahan File Database SQL**: Ketika file [scripts/u646470441_ServicePlanBRA.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/u646470441_ServicePlanBRA.sql) diperbarui atau ada skema baru.
3. **Permintaan Pengguna**: Ketika pengguna secara eksplisit meminta sinkronisasi atau pengujian pada server Laragon lokal.

---

## 2. Rincian Proses yang Dilakukan Script

Script [sync_to_laragon.ps1](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/sync_to_laragon.ps1) melakukan langkah-langkah berikut secara otomatis:
1. **Mirroring File Aplikasi**: Melakukan sinkronisasi folder kerja ke webroot Laragon `C:\laragon\www\ServicePlan-BRA` dengan mengecualikan folder internal (`.git`, `.gemini`, `.agents`, `brain`, `scratch`, dsb.).
2. **Injeksi Lingkungan Lokal (`.htaccess`)**: Menambahkan konfigurasi variabel environment lokal (`APP_ENV="local"`, `DB_HOST="127.0.0.1"`, `DB_PORT="3306"`, `DB_NAME="u646470441_ServicePlanBRA"`, dsb.) ke file target `.htaccess`.
3. **Persiapan Session Folder**: Membuat direktori sesi lokal di `C:\laragon\tmp\ServicePlan-BRA-sessions`.
4. **Auto-Detect & Sinkronisasi Database MySQL**:
   - Mendeteksi binary `mysql.exe` di `C:\laragon\bin\mysql` atau `C:\laragon\bin\mariadb`.
   - Membuat database `u646470441_ServicePlanBRA` jika belum ada.
   - Mengimpor file skema & data [scripts/u646470441_ServicePlanBRA.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/u646470441_ServicePlanBRA.sql).
5. **Set Password Sementara Lokal**: Menjalankan `scripts/set_local_temporary_passwords.php` untuk memastikan akun administrator & user lokal siap digunakan.

---

## 3. Perintah Eksekusi Standard

Gunakan tool `run_command` dengan shell PowerShell untuk mengeksekusi script:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1
```

### Parameter Opsional (Jika Menggunakan Konfigurasi Non-Default)
```powershell
powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -TargetDir "C:\laragon\www\ServicePlan-BRA" -DbName "u646470441_ServicePlanBRA" -DbUser "root" -DbPassword ""
```

---

## 4. Validasi & Checklist Pasca-Sinkronisasi

1. Pastikan exit code adalah `0` (atau Robocopy exit code `< 8`).
2. Pastikan file target di `C:\laragon\www\ServicePlan-BRA` telah ter-update.
3. Beritahukan URL lokal yang dapat diakses pengguna:
   - **Login / Landing**: `http://localhost/ServicePlan-BRA/`
   - **Dashboard**: `http://localhost/ServicePlan-BRA/dashboard.html` atau `http://localhost/ServicePlan-BRA/dashboard.php`
