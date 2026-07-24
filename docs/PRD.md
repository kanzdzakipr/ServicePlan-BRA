# Product Requirements Document (PRD)
**Project**: Equipment Maintenance & Fleet Monitoring System
**Version**: 1.0 (MVP)

## 1. Latar Belakang & Tujuan
Perusahaan alat berat membutuhkan sistem CMMS (Computerized Maintenance Management System) berbasis web yang terpusat dengan satu database (Single Source of Truth). Selama ini, operasional menggunakan Excel/WhatsApp yang tidak terstruktur, sehingga sulit mengukur metrik seperti downtime, ketersediaan alat, dan riwayat biaya secara akurat.

**Tujuan Sistem:**
- Memonitor secara real-time unit yang ready, standby, breakdown, maintenance, dll di setiap lokasi.
- Mengurangi downtime melalui pemantauan SLA (Service Level Agreement), prioritas Work Order (WO), peringatan servis (due/overdue).
- Menyediakan riwayat per unit untuk analisa MTTR, MTBF, efisiensi, dan breakdown rate.

## 2. Ruang Lingkup Sistem (Target Jangka Panjang)
Sistem memiliki 12 modul utama mulai dari Master Asset (M01), Penerimaan (M02), Inspeksi (M03), Work Order (M04), PM (M05), Spare Part (M06), Condition Monitoring (M07), Biaya (M08), KPI (M09), Accident (M10), Dashboard (M11), dan Administrasi (M12).

## 3. Asumsi & Prinsip Integrasi Utama
- **ID Unit** adalah kunci utama lintas modul (bukan lambung/polisi yang bisa berubah).
- WO adalah pusat transaksi; kerusakan, spare part, biaya, downtime, bukti, dll. harus terhubung ke WO.
- Status unit diturunkan otomatis dari event aktif berprioritas tertinggi (misal: WO breakdown aktif otomatis mengubah status unit menjadi Breakdown).
- File foto/dokumen disimpan secara terstruktur (pada MVP menggunakan local storage / object storage).
- Dashboard **tidak menyimpan angka manual**. Semua matriks (ketersediaan, breakdown) dihitung dari transaksi database.

## 4. Pengguna (Target Audience)
Admin, Planner, Mekanik, Logistik, Supervisi / Foreman, Manager, General Manager, dan Operator/Pelapor.
Sistem mendukung antarmuka Mobile/Web yang responsif untuk penggunaan di lapangan.
