# Panduan Pengujian F4 — People & KPI Resmi

Status implementasi: **PASS otomatis (26/26 pemeriksaan), siap diuji di browser**.

## Perubahan yang Diterapkan

1. **Siklus Hidup Resmi Penilaian KPI Head of Equipment:**
   - Skema `head_kpi_assessments` dilengkapi kolom siklus resmi: `period_key`, `status` (`DRAFT`, `SUBMITTED`, `APPROVED`, `REJECTED`), `evaluated_user_id`, `assessor_user_id`, `version`, `submitted_at`, `approved_by`, `approved_at`, dan `approval_notes`.
   - Perhitungan skor bobot dihitung dan divalidasi server melalui formula tersimpan: `weighted_score = (score_val * weight_pct) / 5`.
   - Aturan approval ditegakkan di server: hanya role `Administrator` atau `General Manager` yang dapat menyetujui (`APPROVED`), sedangkan role lain (seperti Maintenance Planner / Mekanik) ditolak dengan HTTP 403 `FORBIDDEN`.
   - Modifikasi draf terkunci jika penilaian sudah berstatus `APPROVED`.

2. **Audit Review SPL Planner Persisten di Database:**
   - Menghapus ketergantungan `localStorage` (`SPL_PLANNER_REVIEW_KEY`).
   - Membuat tabel resmi `spl_planner_reviews` (`review_id`, `report_id`, `spl_number`, `planner_user_id`, `review_status`, `review_notes`, `reviewed_at`).
   - Catatan review planner langsung tersimpan ke server dan tetap bertahan lintas browser/sesi.

3. **Rekapitulasi Jam Lembur & Absensi Terverifikasi (Bukan Data Rekayasa):**
   - Menghapus array dummy statis 5 orang Yard KM12.
   - Endpoint `overtime_leaderboard` mengagregasi data aktual dari tabel `spl_overtime_records`.
   - Bila belum ada dokumen SPL yang disetujui pada periode yang dipilih, sistem menampilkan state informatif resmi: *"Belum ada dokumen lembur / SPL terverifikasi pada periode ini"*.
   - Dokumen SPL yang di-void/dibatalkan (`reversed_at IS NOT NULL`) otomatis dikecualikan dari akumulasi lembur.

---

## Alur Uji di Browser

Buka `http://localhost/ServicePlan-BRA/` dan login dengan akun operasional:

### Skenario 1: Simpan Draf & Pengajuan (Submit) KPI Head of Equipment
1. Masuk ke menu **People, Performa & KPI**.
2. Pada tab **KPI Head of Equipment**, amati action bar di atas matriks indikator:
   - Status awal: `DRAFT`.
   - Meta info: `Periode: Januari 2026 · Dany Agung (Head of Equipment)`.
3. Ubah salah satu skor (misal indikator 1 dari 5 menjadi 4). Skor terakumulasi otomatis.
4. Klik tombol **Simpan Draf**. Pastikan muncul notifikasi sukses dan data tersimpan di server.
5. Klik tombol **Ajukan Penilaian (Submit)**. Konfirmasi dialog.
6. Muat ulang halaman (Refresh). Status penilaian harus bertahan sebagai `SUBMITTED`.

### Skenario 2: Otorisasi Approval oleh General Manager / Administrator
1. Login sebagai akun **Administrator** (`admin`).
2. Masuk kembali ke tab **KPI Head of Equipment**.
3. Tombol hijau **Setujui (Approve GM)** akan muncul karena status saat ini adalah `SUBMITTED`.
4. Klik **Setujui (Approve GM)** dan masukkan catatan persetujuan (opsional).
5. Status berubah menjadi `APPROVED` dan tombol submit/edit dinonaktifkan.
6. Muat ulang halaman; label status menampilkan: `Disetujui resmi oleh Administrator Utama pada [tanggal/jam]`.

### Skenario 3: Uji Negatif Otorisasi Role (Non-Admin / Non-GM)
1. Jika login sebagai role non-admin (misal `martin_planner`), tombol **Setujui (Approve GM)** tidak akan ditampilkan di antarmuka.
2. Setiap percobaan POST approval langsung melalui API tanpa role GM/Admin akan ditolak tegas oleh server dengan respons `403 Forbidden`.

### Skenario 4: Review SPL Planner Persisten di DB
1. Masuk ke tab **Evaluasi Maintenance Planner**, buka seksi **Daftar SPL masuk untuk review planner**.
2. Pilih salah satu SPL, lalu klik tombol **Catat sudah ditinjau Planner**.
3. Pastikan status review berubah menjadi `Sudah ditinjau`.
4. Muat ulang halaman di browser berbeda atau buka mode incognito; status review tetap tersimpan di server (bukan hilang seperti saat memakai `localStorage`).

### Skenario 5: Tampilan Leaderboard Lembur Jujur
1. Masuk ke tab **Absensi & Lembur Tim**.
2. Karena pada periode Januari 2026 belum ada transaksi SPL resmi yang disetujui, tabel dengan jujur menampilkan pesan informatif: *"Belum ada dokumen lembur / SPL terverifikasi pada periode ini"*.

---

## Bukti Pengujian Otomatis

Jalankan pengujian integrasi otomatis:
```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' tests/f4_people_kpi_integration.php
```
Hasil: **26/26 PASS** dengan rollback transaksi database terisolasi (0 data QA tertinggal di basis data).

