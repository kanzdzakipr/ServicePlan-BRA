# Panduan Pengujian F5 — Archive, Notifikasi, dan Pengaturan Operasional

Status implementasi: **PASS otomatis (28/28 pemeriksaan), siap diuji di browser**.

---

## Ringkasan Perubahan yang Diterapkan

1. **Pemindahan DDL & Audit Trail Arsip (`archived_items` & `archive_audit_trail`):**
   - DDL otomatis dihapus dari waktu eksekusi `api/archive.php` dan dipindahkan ke skrip migrasi resmi [scripts/migrate_f5_archive_settings_schema_23102026.sql](file:///c:/Users/LENOVO/ServicePlan-BRA/scripts/migrate_f5_archive_settings_schema_23102026.sql).
   - Menambahkan kolom audit pada `archived_items`: `archived_by_user_id`, `archived_by_name`, dan `reason`.
   - Membuat tabel baru `archive_audit_trail` (`audit_id`, `item_type`, `item_id`, `action`, `actor_user_id`, `actor_name`, `reason`, `created_at`) yang mencatat secara permanen setiap aksi pengarsipan (`ARCHIVE`) maupun pemulihan (`RESTORE`).
   - Penambahan verifikasi CSRF token (`X-CSRF-Token`) dan otorisasi izin (`archive.read` pada GET, `archive.write` pada POST).
   - Menambahkan fail-closed failure banner pada antarmuka jika penanda arsip gagal dimuat dari server saat inisialisasi aplikasi.

2. **Integritas CSRF & Sinkronisasi Notifikasi Pengguna:**
   - Menambahkan header token keamanan `X-CSRF-Token` pada seluruh panggilan mutasi notifikasi di [scripts/dashboard.js](file:///c:/Users/LENOVO/ServicePlan-BRA/scripts/dashboard.js) (`MARK_READ`, `MARK_ALL_READ`, dan `CREATE`).
   - Status baca tersimpan permanen pada tabel `notification_recipients` (`read_at`), sehingga angka badge dan status belum dibaca (*unread*) tetap konsisten setelah muat ulang (*refresh*) atau login ulang lintas perangkat.

3. **Tabel & API Pengaturan Operasional Persisten (`operational_settings`):**
   - Membuat tabel database resmi `operational_settings` (`setting_key`, `setting_value`, `category`, `description`, `updated_by_user_id`, `updated_by_name`, `updated_at`).
   - Membangun endpoint baru [api/settings.php](file:///c:/Users/LENOVO/ServicePlan-BRA/api/settings.php) dengan validasi tipe data (angka bulat, desimal, batas min/max, panjang teks) dan otorisasi ketat khusus role `Administrator`.
   - Menghapus manipulasi `localStorage` dan pesan sukses palsu (`alert`). Fungsi `saveSettings()` dan `saveAllSettings()` sekarang langsung melakukan mutasi persisten ke MySQL.
   - Panel yang belum operasional (Master Site Project & Kategori Standard Rates) diberi label tegas **Nonaktif / Read-Only** dan **Referensi Standard**.

---

## Alur Uji di Browser (http://localhost/ServicePlan-BRA/)

### Skenario 1: Simpan & Persistensi Pengaturan Operasional (Threshold, Template, Keamanan)
1. Login sebagai akun **Administrator** (`admin` / `admin123`).
2. Masuk ke menu **Pengaturan** pada navigasi sidebar kiri.
3. Klik submenu **Threshold & SLA Parameter**:
   - Amati nilai yang tampil telah terisi otomatis dari database (misal SLA Tiket WO: 30 menit).
   - Ubah salah satu nilai, misalnya **SLA Tiket WO dari Unit Down** menjadi `45` menit.
   - Klik tombol **Simpan Parameter Threshold**.
   - Pastikan muncul konfirmasi sukses nyata dari server: *"Parameter Threshold & SLA berhasil disimpan ke database."*
4. Muat ulang halaman browser (*Refresh* / F5), lalu buka kembali menu **Pengaturan > Threshold & SLA Parameter**:
   - Pastikan nilai SLA Tiket WO tetap `45` menit (bukan kembali ke default).
5. Klik tombol **Simpan Seluruh Konfigurasi** di bagian kanan atas halaman pengaturan:
   - Pastikan muncul notifikasi sukses penyimpanan seluruh konfigurasi ke database.

### Skenario 2: Uji Otorisasi Negatif Pengaturan (Role Non-Admin)
1. Login sebagai akun non-admin (misal: `martin_planner` / Planner atau Mekanik).
2. Buka menu **Pengaturan**.
3. Coba lakukan modifikasi nilai pengaturan dan simpan:
   - Permintaan akan ditolak tegas oleh server dengan pesan HTTP 403: *"Hanya Administrator yang berwenang mengubah konfigurasi operasional sistem."*

### Skenario 3: Penandaan Baca Notifikasi Persisten (Mark Read / Mark All Read)
1. Perhatikan ikon lonceng notifikasi di bilah navigasi atas (top bar).
2. Klik ikon lonceng untuk membuka panel notifikasi.
3. Klik salah satu notifikasi belum dibaca, atau klik **Tandai Semua Dibaca**:
   - Jumlah badge unread akan berkurang / hilang.
4. Muat ulang halaman (*Refresh* / F5):
   - Notifikasi yang telah dibaca tetap berstatus terbaca (tidak kembali menjadi unread karena token CSRF dan status `read_at` berhasil disimpan ke server).

### Skenario 4: Pengarsipan & Pemulihan Unit beserta Audit Trail
1. Buka menu **Master Asset** atau **P2H & Inspeksi**.
2. Pada salah satu baris data (misal unit `EXC-00001`), klik opsi **Arsipkan**:
   - Dialog konfirmasi akan meminta alasan pengarsipan (misal: *"Perbaikan jangka panjang"*).
   - Klik OK. Unit akan berpindah dari daftar aktif ke menu **Archive Data**.
3. Buka menu **Archive Data**:
   - Unit `EXC-00001` tercatat dalam tabel arsip.
4. Klik tombol **Restore / Kembalikan**:
   - Dialog akan meminta alasan pemulihan (misal: *"Unit selesai servis"*).
   - Klik OK. Unit kembali tampil pada register operasional aktif.
5. Verifikasi di database: riwayat aksi `ARCHIVE` dan `RESTORE` beserta identitas user dan alasannya tercatat rapi pada tabel `archive_audit_trail`.

