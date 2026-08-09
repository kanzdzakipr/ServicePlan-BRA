# AssetPro V2 - Website Asset & Equipment Management untuk Laragon

## Fitur utama
- Dashboard unit, service, biaya, logistik, ban, grease, mekanik, dan condition monitoring.
- Hak akses: Asset Manager, Head of Equipment, Planner, Mekanik, Logistik, Viewer.
- Upload foto kerusakan, quotation, purchase order, invoice, dan BAST.
- Approval permintaan spare part.
- Riwayat perubahan status dan audit trail pengguna.
- Notifikasi service terlambat/jatuh tempo.
- Nomor work order dan nomor permintaan spare part otomatis.
- Export Excel dan halaman PDF siap cetak.
- Dashboard per lokasi kerja.
- Availability, utilization, MTBF, MTTR, dan biaya per HM/KM.
- QR Code untuk setiap unit.
- Backup database manual dan otomatis melalui Windows Task Scheduler.

## Instalasi Laragon
1. Ekstrak folder ini ke `C:\laragon\www\asset-manager-laragon-v2`.
2. Jalankan Apache dan MySQL dari Laragon.
3. Import `database\schema.sql` melalui phpMyAdmin atau HeidiSQL.
4. Set `APP_ENV=local` dan konfigurasi database melalui environment variable pada `.env.example`.
5. Buka `http://asset-manager-laragon-v2.test` atau `http://localhost/asset-manager-laragon-v2`.

## Provisioning akun

Jangan menggunakan akun atau password bersama. Buat akun melalui proses provisioning
administrator, gunakan password acak unik, dan distribusikan credential melalui kanal aman.
Login production menolak hash password seed yang telah diketahui.

## Matriks akses ringkas
- Asset Manager: seluruh menu, pengguna, backup, audit.
- Head of Equipment: pengelolaan unit, maintenance, approval, analitik, laporan, audit.
- Planner: unit, jadwal service, work order, permintaan spare part, inspeksi, analitik.
- Mekanik: unit baca, work order, inspeksi, foto kerusakan, jam kerja.
- Logistik: permintaan spare part, status logistik, quotation/PO, export.
- Viewer: hanya membaca dashboard dan laporan.

## Upload dokumen
Folder berikut harus writable oleh Apache:
- `uploads\damage`
- `uploads\quotations`
- `uploads\purchase_orders`
- `uploads\documents`

Ukuran maksimum pada aplikasi adalah 10 MB. Bila upload gagal karena konfigurasi PHP, ubah `upload_max_filesize` dan `post_max_size` pada `php.ini` Laragon.

## Backup otomatis
1. Buka `scripts\backup_database.bat`.
2. Sesuaikan lokasi `mysqldump.exe` dengan versi MySQL Laragon Anda.
3. Jalankan file BAT untuk menguji.
4. Buka Windows Task Scheduler.
5. Buat Basic Task, jadwalkan harian pukul 23.00.
6. Action: Start a program, pilih `scripts\backup_database.bat`.
7. Backup lebih dari 30 hari akan dihapus oleh perintah `forfiles`.

## PDF
Menu laporan membuka halaman cetak. Klik **Cetak / Simpan PDF**, lalu pilih printer **Save as PDF** atau **Microsoft Print to PDF**.

## QR Code
QR Code memakai pustaka qrcodejs dari CDN. Komputer memerlukan internet ketika pertama kali membuka menu QR. Setelah QR tercetak, hasil scan membuka `public-unit.php?token=...`.

## Catatan KPI
KPI akurat hanya jika event Ready for Use, Breakdown, dan Downtime diinput konsisten beserta durasinya.
- Availability = (jam periode - breakdown - downtime) / jam periode.
- Utilization = jam operasi tercatat / jam tersedia.
- MTBF = jam operasi / jumlah breakdown.
- MTTR = total jam breakdown dan downtime / jumlah breakdown.
- Biaya/HM atau KM = total biaya maintenance / meter aktual.

## Keamanan sebelum produksi
- Ganti seluruh password contoh.
- Aktifkan HTTPS ketika dipasang di server.
- Simpan folder backup di luar public web root untuk produksi.
- Batasi tipe upload dan lakukan antivirus scanning jika sistem dapat diakses publik.
- Gunakan akun MySQL khusus, bukan root.
