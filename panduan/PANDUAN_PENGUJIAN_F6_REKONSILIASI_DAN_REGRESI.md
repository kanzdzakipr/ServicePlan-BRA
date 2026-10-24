# Panduan Pengujian F6 — Rekonsiliasi Analitik dan Regression Gate

Dokumen ini memandu pengujian menyeluruh untuk **Batch F6**, mencakup transparansi rekonsiliasi analitik Fuel & Biaya, kebijakan buku besar finansial, pertahanan ekspor CSV, serta eksekusi *regression gate* BR-01 sampai BR-09B.

---

## 1. Uji Otomatis via Terminal / PowerShell (37 Checks)

Buka PowerShell di folder kerja atau gunakan PHP CLI Laragon untuk menjalankan seluruh skenario regresi secara aman (memakai *database transaction rollback*, 0 baris QA tertinggal):

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe tests/f6_analytics_reconciliation_regression.php
```

**Hasil yang diharapkan:**
```text
ALL 37 REGRESSION CHECKS PASSED SUCCESSFULLY!
TRANSACTION ROLLBACK: All test records cleanly reverted. Zero QA rows left in database.
```

---

## 2. Uji Tampilan & Fungsional di Browser

Buka peramban (Chrome / Edge / Firefox) dan buka URL lokal Laragon:
👉 **`http://localhost/ServicePlan-BRA/`**

---

### Skenario 1: Uji Rekonsiliasi Analitik Bahan Bakar (Fuel Dashboard)

1. Login sebagai akun **Administrator** (`admin` / `admin123`).
2. Pada navigasi sidebar kiri, klik menu **Fuel & Efisiensi** (atau **Dashboard Bahan Bakar**).
3. **Periksa Banner Rekonsiliasi Fuel** di bagian atas konten:
   - Terdapat kartu info biru bertajuk **"Rekonsiliasi Data Konsumsi Bahan Bakar"**.
   - **Data Sumber:** Menampilkan total baris data pengisian aktif yang sedang dimuat dari database.
   - **Filter Aktif:** Menampilkan filter lokasi (misal: *Semua Lokasi Operasional* atau nama site yang dipilih).
   - **Formula Valuasi:** Tertulis jelas rumus transparansi `Total Liter × Rp 12.000/L`.
   - **Threshold Anomali:** Tertulis jelas kriteria deteksi anomali: `((LPH - Baseline)/Baseline) > 20%`.
   - **Integritas:** Status bertuliskan `Data Lengkap & Tervalidasi` jika tidak ada log yang hilang.
4. Ubah filter lokasi (misal pilih lokasi tertentu seperti *Site Duri* atau *WUR EW Project*):
   - Amati banner langsung memperbarui informasi baris sumber dan filter lokasi secara responsif.

---

### Skenario 2: Uji Rekonsiliasi Buku Besar Biaya (Cost & Budget)

1. Pada navigasi sidebar kiri, klik menu **Biaya & Finansial** (atau **Dashboard Biaya**).
2. **Periksa Banner Rekonsiliasi Biaya** di bawah ringkasan finansial:
   - Terdapat kartu info biru bertajuk **"Rekonsiliasi Buku Besar Biaya & Status Sumber Transaksi"**.
   - **Transaksi Aktif:** Menampilkan jumlah baris transaksi sah yang masuk ke pembukuan (misal: `X transaksi aktif`).
   - **Transaksi Reversed / Void:** Menampilkan jumlah transaksi yang dibatalkan/direverse (misal: `0 transaksi reversed / void`). Angka kas tidak mencampurkan transaksi void.
   - **Kebijakan Sumber:** Tertulis `MANUAL (Verified Journal & Closed WO)`.
   - **Posting Otomatis PO / SPPU:** Tertulis jelas `NONAKTIF (Mencegah Double Count)`. Ini membuktikan transparansi batasan bisnis bahwa sistem tidak mencatat biaya ganda antara dokumen logistik dan keuangan sebelum ada deduplikasi resmi.
3. Perhatikan kartu **Target Budget vs Aktual**:
   - Angka deviasi anggaran (`Deviasi Budget`) dihitung dinamis secara matematis `Aktual - Anggaran`.

---

### Skenario 3: Uji Ekspor CSV dan Pertahanan Injeksi Formula (BR-08)

1. Pada menu laporan / data aset, coba lakukan ekspor data ke format **CSV**.
2. Jika ada nama unit atau deskripsi yang diawali dengan simbol formula spreadsheet (`=`, `+`, `-`, `@`), sistem secara otomatis menambahkan tanda kutip pembuka `'` di file ekspor.
3. Buka file CSV di Excel / Notepad:
   - Karakter berbahaya tidak dieksekusi sebagai formula melainkan terbaca sebagai teks biasa murni (`'=1+2`).

---

### Skenario 4: Uji Otorisasi Negatif Finansial (Role Mekanik vs Admin)

1. Keluar (*Logout*) dari sistem.
2. Login sebagai akun **Mekanik Junior** (misal: `mekanik1` / `mekanik1123`).
3. Coba akses menu atau endpoint mutasi biaya (`api/costs.php?action=create_transaction`):
   - Server menolak akses dengan HTTP `403 Forbidden` (*Akses ditolak: role tidak memiliki izin cost.write*).
   - Membuktikan bahwa keamanan finansial ditegakkan di server backend, bukan hanya disembunyikan tombolnya di frontend.

