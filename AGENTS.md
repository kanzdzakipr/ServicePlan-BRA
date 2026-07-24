# Customizations / Rules (Agents)

Aturan ketat untuk AI/Agents selama bekerja pada proyek Equipment Maintenance & Fleet Monitoring System (BRA):

1. **Aesthetic & UI Rules (Web Application Development)**
   - Wajib menggunakan desain UI modern, premium, dan dinamis. Gunakan animasi mikro (micro-animations), hover states, dan hierarki visual yang jelas.
   - Menggunakan Vanilla CSS dengan pendekatan desain terstruktur.
   - **TIDAK BOLEH** menggunakan TailwindCSS, kecuali diperintahkan sebaliknya oleh pengguna.
   - Harus sepenuhnya responsif (Desktop & Mobile), terutama karena pengguna lapangan (mekanik/operator) mengakses via mobile.

2. **Tech Stack Constraints**
   - Frontend: Menggunakan Next.js (App Router) atau Vite + React (sesuai persetujuan di Implementation Plan).
   - Backend/API: Node.js (atau Next.js API Routes).
   - Database: MySQL murni.

3. **Business Logic & Source of Truth**
   - Rujuk direktori `docs/` (terutama `STATUS_RULES.md`, `DATA_MODEL.md`, dan `ACCEPTANCE_CRITERIA.md`) untuk setiap logika bisnis (state machine unit, validasi WO, dsb).
   - Jangan membuat kolom/tabel duplikat jika sudah didefinisikan dalam Data Model.
   - Angka KPI dan Dashboard **tidak boleh di-*hardcode*** dan tidak boleh disimpan di kolom statis. Semuanya harus dikalkulasi berdasarkan transaksi di tabel MySQL (Single source of truth).

4. **Planning & Execution Workflow**
   - Dilarang menulis kode fitur baru sebelum merujuk ke *Implementation Plan* yang sedang berjalan.
   - Fokus selalu kepada pengerjaan fitur MVP sebelum membangun modul lain (Accident, Condition Monitoring ban, dll).
   - Validasi (misal HM/KM tidak boleh mundur) wajib diimplementasikan di level server (Backend/API), bukan sekadar UI.
