# MVP Scope
**Fokus Pengembangan Tahap 1 (Minimum Viable Product)**

MVP ini mencakup fondasi dari sistem informasi dan fitur transaksional dasar untuk memonitor kerusakan serta operasional dasar.

## Fitur yang Dicakup pada MVP:
1. **Authentication & Authorization**
   - Login system.
   - Role-Based Access Control (RBAC).

2. **Project dan Lokasi**
   - Manajemen daftar lokasi kerja/project.
   - User hanya dapat melihat/memproses data pada lokasi yang diizinkan (atau All Locations untuk Manager/GM).

3. **Master Asset (Unit Alat Berat)**
   - CRUD data aset (ID, kode lambung, plat, kategori/tipe, tahun, kepemilikan, lokasi).
   - Jenis unit didukung: Bulldozer, Excavator, Vibro Compactor / Roller, Motor Grader, Dump Truck, Trado, Fuel Tank Truck.

4. **Status Unit & Lokasi Unit**
   - Tracking 11 status utama (READY, OPERATING, STANDBY, INSPECTION, PM, BREAKDOWN, WAITING_PART, MOBILIZATION, ACCIDENT_HOLD, INACTIVE, DISPOSED).
   - Riwayat perpindahan lokasi/project unit.

5. **HM / KM (Meter Reading)**
   - Input HM (Hour Meter) / KM harian.
   - Validasi HM/KM tidak boleh mundur (lebih kecil dari bacaan sebelumnya).

6. **Breakdown Report & Work Order (Dasar)**
   - Laporan temuan kerusakan oleh operator (Tiket breakdown).
   - Pembuatan Work Order (WO).
   - Penugasan mekanik (PIC).
   - Update progres dan input time log oleh mekanik.
   - Proses verifikasi dan penutupan WO.

7. **Attachment**
   - Upload foto kerusakan (sebelum pekerjaan).
   - Upload foto perbaikan (setelah pekerjaan).

8. **Dashboard Monitoring**
   - Tampilan KPI dasar (availability, breakdown rate, jumlah open WO, total unit, komposisi status unit).
   - Filter berdasarkan lokasi.

9. **Notification Dasar & Audit Trail**
   - Notifikasi in-app untuk perubahan status penting atau WO emergency.
   - Audit trail untuk mencatat setiap perubahan data kritis (siapa mengubah apa, kapan).

## Fitur yang TIDAK Dicakup pada MVP (Ditunda ke Fase Berikutnya):
- Modul Inventory & Logistik penuh (PO, SPPU, Receiving).
- Preventive Maintenance (PM) scheduling otomatis yang kompleks (kitting parts).
- Modul Condition Monitoring (Ban, Aki, Undercarriage rinci).
- Modul Accident & CAPA penuh.
- Kalkulasi biaya invoice/transaksi rupiah rinci.
