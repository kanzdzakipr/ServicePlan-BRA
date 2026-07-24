# Implementation Plan (Coding Milestones)
Rencana implementasi dipecah menjadi milestone (tahapan) kecil agar dapat dikerjakan, direview, dan diuji satu per satu. Fokus hanya pada ruang lingkup MVP.

## Milestone 1: Fondasi Sistem (Setup, Auth, RBAC)
- Inisialisasi proyek Next.js / React, konfigurasi server Node.js.
- Setup struktur UI dengan Vanilla CSS (premium, responsive).
- Implementasi koneksi database MySQL dan migrasi awal (skema user, roles).
- Pembuatan fitur Login, validasi sesi (Session timeout/JWT).
- Pembuatan middleware/guard RBAC untuk membatasi rute UI dan API berdasarkan Role.

## Milestone 2: Master Data & Lokasi
- Pembuatan modul Manajemen Lokasi (Site/Yard).
- Pembuatan modul Master Asset (Daftar, Tambah, Edit, Detail Unit).
- Implementasi filter global berdasarkan Lokasi aktif user (Data Scoping).

## Milestone 3: Operasional Unit (Status & Meter Reading)
- Fitur pencatatan Input HM/KM Harian.
- Validasi HM/KM (tidak boleh lebih kecil dari sebelumnya) -> sesuai AC-06.
- Fitur Tracking Status Unit (Unit Detail Page) dengan riwayat transisi status.
- State Machine terpusat untuk mengubah `current_status` di tabel aset.

## Milestone 4: Transaksi Work Order (Breakdown)
- Pembuatan Tiket/Laporan Breakdown oleh Operator/Pelapor.
- Sistem otomatis menghitung SLA 30 menit pembuatan WO (AC-01).
- Workflow WO (Draft → Submitted → Approved).
- Penugasan (Assignment) WO ke PIC / Mekanik.
- Mekanik mengelola Time Log (Start, Pause, Resume, Finish). Implementasi AC-02.
- Upload Attachment (Foto Before-After).

## Milestone 5: Penyelesaian WO & Notifikasi
- Proses verifikasi pekerjaan oleh Supervisor (Return to Work/RTW).
- Logika downtime: Pause WO karena WAITING_PART menghentikan repair-clock tapi downtime unit jalan (AC-04).
- Validasi wajib saat penutupan WO (Foto, time log, diagnosis) -> (AC-05).
- Audit Trail terintegrasi di setiap update WO & Status Aset.
- Notifikasi in-app untuk eskalasi/approval.

## Milestone 6: Dashboard & Pelaporan
- Pembangunan Dashboard Executive & Operasional.
- Kartu KPI: Availability, Utilization, Breakdown Rate (Dihitung dari DB real-time).
- Grafik breakdown trend dan top downtime unit.
- Drill-down list saat filter lokasi/kartu di-klik (AC-08).
- Finalisasi UI/UX dan uji responsivitas mobile.
