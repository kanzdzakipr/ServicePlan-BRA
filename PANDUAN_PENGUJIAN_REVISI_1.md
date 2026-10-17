# Panduan Pengujian Revisi 1 — Master Aset Armada (BR-01 / M3)

## Tujuan

Memastikan seluruh kebutuhan **Revisi 1 (Master Aset)** sesuai dokumen rekap tim Brian telah terpenuhi:

1. Identitas aset lengkap tersimpan dan tampil konsisten: Serial Number (`serial_number`), Nomor Pelat (`license_plate`), Merek & Model Spesifik (`make_model`), Tahun Pembuatan (`year_manufacture`), Kategori, dan Lokasi Aktif.
2. Data yang belum tersedia secara transparan ditandai *"Belum tersedia"* dan unit dikelompokkan dengan status kelengkapan data (*Lengkap* vs *Belum Lengkap*).
3. Tabel Master Asset menyediakan filter khusus kelengkapan data (*Semua*, *Lengkap*, *Belum Lengkap*) dan filter identitas unit (ID, Merek/Tahun, SN/Plat, Kategori, Lokasi, Status) beserta tombol *Reset*.
4. Alur pendaftaran unit baru (`modalNewAsset`) dan pengeditan/melengkapi unit (`modalEditAsset`) berfungsi penuh dengan validasi duplikasi (Unit ID, SN, Plat) dan rentang tahun (1970–2099).
5. Modal Detail 360° menampilkan spesifikasi identitas aktual unit, bukan data placeholder tiruan.

---

## Persiapan

1. Pastikan Laragon dan service MySQL aktif (Port 3306).
2. Akses aplikasi melalui browser di `http://localhost/ServicePlan-BRA/` atau virtual host Laragon `http://serviceplan-bra.test/`.
3. Login menggunakan akun berwenang (misal akun Workshop / Admin / Planning).
4. Masuk ke menu **Master Asset** dari sidebar navigasi.

---

## Skenario 1 — Tampilan Tabel Master Asset & Indikator Kelengkapan

1. Buka menu **Master Asset**.
2. Periksa header tabel: pastikan terdapat 8 kolom utama:
   - `Asset ID / Kode`
   - `Merek & Tahun`
   - `SN & Plat Kendaraan`
   - `Kategori`
   - `Lokasi Aktif`
   - `Kelengkapan Data`
   - `Status Efektif`
   - `Aksi`
3. Amati baris unit yang belum memiliki SN atau nomor pelat (misal unit-unit lama bawaan):
   - Kolom SN & Plat menampilkan teks miring berwarna abu-abu: *"Belum tersedia"*.
   - Kolom Kelengkapan Data menampilkan badge kuning: `Belum Lengkap` disertai rincian field yang kurang (misal: *Kurang: SN / Plat, Merek / Model*).
4. Amati unit yang identitasnya telah terisi lengkap:
   - Kolom Kelengkapan Data menampilkan badge hijau: `Lengkap`.

---

## Skenario 2 — Filter Kelengkapan Data & Pencarian

1. Pada baris filter tabel, cari kolom **Kelengkapan Data** (dropdown):
   - Pilih opsi **Belum Lengkap**: tabel hanya menampilkan unit-unit yang masih membutuhkan pengisian data.
   - Pilih opsi **Lengkap**: tabel hanya menampilkan unit-unit yang datanya sudah 100% lengkap.
   - Pilih opsi **Semua**: seluruh unit armada kembali ditampilkan.
2. Coba filter pencarian teks:
   - Ketik `PC200` pada filter *Merek/Tahun* -> tabel menampilkan unit model terkait.
   - Ketik sebagian SN pada filter *SN/Plat* -> tabel memfilter baris yang sesuai.
3. Klik tombol **Reset** di kolom paling kanan:
   - Semua input filter teks dan dropdown kembali kosong dan tabel menampilkan seluruh armada secara instan.

---

## Skenario 3 — Registrasi Unit Baru Lengkap

1. Klik tombol **Registrasi Unit Baru** di kanan atas tabel.
2. Modal registrasi terbuka dengan 9 kolom isian:
   - **Unit ID**: `QA-EXC-2026-99`
   - **Kategori**: `Excavator`
   - **Merek & Model**: `Komatsu PC200-10M0 CE`
   - **Tahun Pembuatan**: `2024`
   - **Nomor Seri (SN)**: `QA-SN-2026-99`
   - **Nomor Pelat**: `BM 8899 QA`
   - **Lokasi**: Pilih salah satu lokasi, misal `Borrow Pit Harapan Baru`
   - **Status Operasional**: `READY`
   - **Meter Awal**: `120.0`
3. Klik tombol **Simpan Unit Aset**.
4. Verifikasi hasil:
   - Muncul notifikasi sukses: *Unit aset 'QA-EXC-2026-99' berhasil didaftarkan ke database*.
   - Unit baru langsung muncul di baris paling atas tabel Master Asset.
   - Kolom *Kelengkapan Data* bertanda hijau: `Lengkap`.
   - Ringkasan KPI Armada (Total Unit & Ready) bertambah otomatis.

---

## Skenario 4 — Validasi Duplikasi & Batasan Input

1. Buka kembali form **Registrasi Unit Baru**.
2. Masukkan Unit ID yang sudah ada: `QA-EXC-2026-99`.
3. Klik **Simpan Unit Aset**:
   - Sistem menolak dan memunculkan pesan error: *"Unit ID atau Kode Unit 'QA-EXC-2026-99' sudah terdaftar dalam sistem."*
4. Ganti Unit ID menjadi `QA-EXC-NEW`, namun gunakan Serial Number yang sama: `QA-SN-2026-99`.
5. Klik **Simpan Unit Aset**:
   - Sistem menolak dan memunculkan pesan error duplikasi Serial Number.
6. Masukkan Tahun Pembuatan di luar rentang valid, misal: `1965` atau `2105`:
   - Sistem memvalidasi dan meminta tahun antara 1970 hingga 2099.

---

## Skenario 5 — Edit & Melengkapi Data Unit yang Masih Belum Lengkap

1. Gunakan filter *Kelengkapan Data* -> pilih **Belum Lengkap**.
2. Pada salah satu baris unit, klik tombol **Edit** (ikon pensil biru).
3. Modal **Edit Data Unit** terbuka dengan data unit terisi otomatis.
4. Lengkapi field yang sebelumnya belum tersedia:
   - Isi Serial Number chasis.
   - Isi Nomor Pelat (jika berlaku).
   - Isi Merek/Model spesifik.
   - Isi Tahun Pembuatan.
5. Klik tombol **Simpan Perubahan**.
6. Verifikasi hasil:
   - Muncul notifikasi sukses pembaruan.
   - Status kelengkapan unit berubah menjadi `Lengkap`.
   - Data langsung ter-refresh di tabel tanpa perlu reload halaman.

---

## Skenario 6 — Verifikasi Detail 360° Unit

1. Klik pada salah satu baris unit yang sudah lengkap untuk membuka **Detail 360° Unit**.
2. Pada panel spesifikasi unit (sisi kanan Ringkasan):
   - **Merek / Model**: Menampilkan merek model aktual (bukan teks statis dummy).
   - **Tahun Manufaktur**: Menampilkan tahun aktual unit.
   - **Nomor Seri (SN)**: Menampilkan nomor seri aktual.
   - **Nomor Pelat**: Menampilkan plat kendaraan atau *Belum tersedia*.
   - **Status Kelengkapan**: Menampilkan badge `Lengkap` atau `Belum Lengkap`.
   - **HM / KM Terakhir**: Menampilkan angka HM/KM aktual.

---

## Skenario 7 — Pengujian Otomatis via CLI (Regression Test)

Untuk verifikasi backend API, database schema, duplicate prevention, dan transaction rollback secara menyeluruh, jalankan perintah pengujian otomatis berikut:

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" tests/master_asset_integration.php
```

Hasil yang diharapkan:
- Seluruh 32 pengujian berstatus `[PASS]` (0 FAILED).
- Data uji dibersihkan secara otomatis di akhir pengujian tanpa menyisakan sampah di database.
