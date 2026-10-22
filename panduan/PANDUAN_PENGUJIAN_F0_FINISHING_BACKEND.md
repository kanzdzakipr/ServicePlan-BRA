# Checkpoint F0 — pengamanan manajemen pengguna

Status: **PASS untuk gate akses browser dua role menurut konfirmasi pengguna** (non-Administrator ditolak; Administrator dapat membuka daftar). Uji create/delete/toggle dan ganti password pada akun uji terisolasi masih **NOT TESTED**; ini bukan klaim seluruh skenario F0 lulus. Perubahan F0 hanya pada `api/security.php`, `api/users.php`, dan tes kebijakan `tests/f0_user_authorization.php`; tidak ada perubahan skema atau data.

## Perilaku yang diharapkan

| Aksi | Administrator | Role non-Administrator |
| --- | --- | --- |
| GET daftar pengguna/role/lokasi | Diizinkan | 403 |
| POST create, delete, toggle status, reset password orang lain | Diizinkan sesuai validasi | 403 |
| POST ganti password sendiri | Harus menyertakan password lama | Harus menyertakan password lama |
| POST tanpa token CSRF valid | 403 | 403 |

Role `Asset Manager` dan `Equipment Manager` tidak lagi mendapat wildcard `*` dari izin fallback server; izin operasional mereka tetap eksplisit. Perubahan matriks RBAC di `localStorage` tidak memberi izin API.

## Bukti otomatis read-only

Jalankan dengan PHP Laragon2:

```powershell
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/f0_user_authorization.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/security_unit.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests/security_static.php
```

Hasil saat checkpoint: **35 + 22 + 39 PASS**, tanpa penulisan DB. Baseline Laragon2 sebelum/sesudah: `users=16`, `user_roles=20`, `permissions=0`, `role_permissions=0`.

## Uji browser/HTTP yang masih harus dilakukan

1. **Hanya setelah kode F0 terpasang di Laragon2 secara aman**, login sebagai Administrator dan role non-Administrator dengan akun uji yang sudah ada. Jangan buat/hapus/toggle akun nyata.
2. Sebagai non-admin, buka Pengaturan → manajemen pengguna dan periksa Network: GET `api/users.php` harus 403. Uji POST create/toggle/delete dengan payload **tidak valid atau ID yang tidak ada** hanya dalam lingkungan uji terisolasi; respons harus 403 sebelum validasi/pencarian objek. Jangan kirim payload yang dapat mengubah akun nyata.
3. Sebagai admin, GET daftar pengguna harus 200. Uji create/toggle/delete hanya pada DB uji terisolasi atau akun uji yang disepakati dan dapat dipulihkan.
4. Uji ganti password sendiri hanya pada akun uji: password lama salah ditolak; password lama benar berhasil; login ulang dengan password baru. Jangan uji pada akun bersama.
5. Pastikan GET/POST tanpa sesi ditolak dan POST tanpa CSRF valid ditolak. Catat status HTTP, role, waktu, serta hitungan DB sebelum/sesudah.

## Laragon2

`scripts/sync_to_laragon.ps1 -LaragonRoot C:\laragon2 -TargetDir C:\laragon2\www\ServicePlan-BRA -PreserveDatabase -DryRun` selesai tanpa error. Pengguna kemudian menyalin **hanya dua file PHP F0** ke Laragon2 dengan backup; hash kedua file target telah dicocokkan dengan repository. Script sync penuh tetap **tidak dijalankan** karena `-PreserveDatabase` menjalankan migrasi aditif serta seed/import stok dan ban.

Gate akses role F0 telah dikonfirmasi pengguna untuk pembacaan daftar akun. Skenario aksi tulis dan password sendiri tetap perlu diuji pada DB/akun uji terisolasi sebelum release gate akhir.
