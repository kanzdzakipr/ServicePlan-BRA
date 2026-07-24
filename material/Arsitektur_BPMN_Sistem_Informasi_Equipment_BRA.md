# Dokumen Arsitektur Sistem Informasi

## Equipment Management System Berbasis Website

**PT Bina Rekayasa Anugrah**  
Dokumen kebutuhan sistem — versi 1.0 — 24 Juli 2026

BPMN, modul, hak akses, aturan bisnis, data, dashboard, dan acceptance criteria.

> **Catatan konversi:** Struktur judul, paragraf, daftar, dan tabel dipertahankan dalam Markdown. Gambar arsitektur dan diagram BPMN dialihkan menjadi deskripsi tekstual terstruktur.

> **Deskripsi visual sampul:** Arsitektur sistem digambarkan sebagai lima lapisan yang saling terhubung dua arah: **Pengguna & Kanal**, **Aplikasi/Modul**, **Layanan Proses**, **Data Terpadu**, dan **Infrastruktur**. Seluruh dashboard menggunakan satu sumber data (*single source of truth*), sehingga angka dihitung dari transaksi MySQL dan bukan dimasukkan secara manual.


*Rancangan untuk menerjemahkan proses kerja BRA menjadi kebutuhan yang dapat dibangun oleh developer*

Disusun berdasarkan arsip: SOP penerimaan alat; SOP pekerjaan mekanik & pengendalian unit down; SOP pemeriksaan/pemeliharaan; Job Order; plan service; report ban; penggunaan aki; weekly greasing; monitoring spare part; biaya; KPI mekanik; accident; BAST; dan laporan equipment.

# 1. Ringkasan Eksekutif

Sistem yang disarankan adalah CMMS/Equipment Management System berbasis web dengan satu database MySQL. Seluruh data aset, status unit, inspeksi, Work Order, preventive maintenance, spare part, biaya, jam mekanik, condition monitoring, accident, dan lokasi saling terhubung melalui ID unit dan nomor WO.

### Keputusan Desain Utama

- Dashboard tidak menyimpan angka manual. Semua kartu dan grafik dihitung dari transaksi pada database.
- Work Order menjadi pusat transaksi: kerusakan, mekanik, waktu, spare part, biaya, downtime, bukti foto, dan keputusan RTW harus terhubung ke WO.
- Workflow menerapkan gate: JO wajib sebelum pekerjaan/order part; update progres pagi dan sore; penutupan wajib memiliki foto before-after dan verifikasi.
- Website responsif untuk ponsel karena input lapangan membutuhkan foto, HM/KM, lokasi, checklist, dan update progres.
- Setiap perubahan status memiliki timestamp, pengguna, komentar, dan audit trail sehingga laporan dapat dipercaya.

## 1.1 Tujuan Sistem

- Mengetahui secara real-time unit ready, standby, breakdown, maintenance, mobilisasi, dan nonaktif pada setiap lokasi.
- Mengurangi downtime melalui SLA, prioritas WO, peringatan service due, dan eskalasi keterlambatan spare part.
- Menyediakan riwayat biaya dan pekerjaan per unit sejak Oktober 2024 atau periode awal data yang disepakati.
- Mengukur availability, utilization, breakdown rate, kepatuhan PM, MTTR/MTBF, jam mekanik, biaya per unit, dan efisiensi konsumsi.
- Menggantikan duplikasi Excel/WhatsApp dengan transaksi terstruktur tanpa menghilangkan kemampuan ekspor Excel/PDF.

# 2. Ruang Lingkup Modul

| **Kode** | **Modul**              | **Cakupan**                                                                                                      |
|----------|------------------------|------------------------------------------------------------------------------------------------------------------|
| M01      | Master Asset           | Identitas, kode/lambung, serial/plat, kategori, tahun, warranty, ownership, lokasi, nilai aset, dokumen, status. |
| M02      | Penerimaan & Mutasi    | Inspeksi pra-kirim, mobilisasi, commissioning, BAST, mutasi lokasi, histori.                                     |
| M03      | Inspeksi & HM/KM       | Pre-trip, inspeksi berkala, kondisi saat operasi, meter reading, foto, temuan.                                   |
| M04      | Work Order / Breakdown | Tiket, JO, prioritas, klasifikasi, pekerjaan, mekanik, progres, downtime, RTW.                                   |
| M05      | Preventive Maintenance | Interval, forecast, rencana bulanan, due/overdue, kitting, realisasi, next due.                                  |
| M06      | Spare Part & Logistik  | SPB, stok, reservasi, approval, procurement, ETA, receiving, issue ke WO.                                        |
| M07      | Condition Monitoring   | Ban, grease, aki, cutting bit, undercarriage dan major component.                                                |
| M08      | Biaya & Dokumen        | PO, SPPU, invoice, harga jual, biaya WO/PM/part/labor, budget vs actual.                                         |
| M09      | KPI & Tenaga Kerja     | Jam normal/lembur, SPL, produktivitas mekanik, delay spare part, KPI planner/tim.                                |
| M10      | Accident & CAPA        | Laporan insiden, dampak, penyebab, corrective/preventive action, approval, closure.                              |
| M11      | Dashboard & Laporan    | Operasional, lokasi, WO, PM, condition, biaya, KPI, executive dashboard.                                         |
| M12      | Administrasi Sistem    | User, role, lokasi, master status, SLA, ambang, approval matrix, audit log, backup.                              |

# 3. Peran dan Hak Akses

| **Peran**           | **Aksi utama**                                                                              | **Kontrol**                                                   |
|---------------------|---------------------------------------------------------------------------------------------|---------------------------------------------------------------|
| Admin               | Input/ubah master, tiket, dokumen, HM/KM, transaksi biaya; cetak laporan.                   | Tidak boleh menyetujui transaksi sendiri.                     |
| Planner             | Rencana PM, scheduling, WO planning, estimasi waktu/parts, analisis due/overdue.            | Tidak menutup WO tanpa verifikasi supervisi.                  |
| Mekanik             | Terima pekerjaan, mulai/pause/selesai, checklist, time log, diagnosis, parts request, foto. | Tidak bisa bekerja tanpa WO approved; tidak bisa RTW sendiri. |
| Logistik            | Cek stok, reservasi, SPB/PO progress, receiving, issue/return part, SLA.                    | Issue part harus menunjuk WO/PM atau transaksi sah.           |
| Supervisi / Foreman | Validasi inspeksi, klasifikasi kerusakan, assign mekanik, verifikasi pekerjaan/RTW.         | Approval sesuai batas kewenangan.                             |
| Manager Equipment   | Prioritas, approval biaya, reopen/void, review KPI, keputusan downtime/asset.               | Memiliki dashboard lintas lokasi dan audit.                   |
| General Manager     | Approval nilai/risiko tinggi, executive review, keputusan investasi/disposal.               | Read-heavy; transaksi terbatas pada approval.                 |
| Operator / Pelapor  | Pre-trip, HM/KM, lokasi, keluhan, foto/video, acknowledgment.                               | Hanya unit/area yang ditugaskan.                              |

# 4. Arsitektur Sistem

### Gambar 1 — Arsitektur Logis Sistem

**Deskripsi:** Diagram menampilkan arsitektur berlapis dengan panah dua arah di antara setiap lapisan.

1. **Pengguna & kanal:** Admin, Planner, Mekanik, Logistik, Supervisi, Manager, General Manager, serta akses melalui perangkat mobile dan web.
2. **Aplikasi/modul:** Master Asset, Inspeksi, WO/Breakdown, Preventive Maintenance, Spare Part, Ban/Grease/Aki/Cutting Bit, Accident, Biaya, dan KPI.
3. **Layanan proses:** Workflow BPMN, approval, SLA dan eskalasi, notifikasi, dokumen/foto, audit trail, dan reporting.
4. **Data terpadu:** Asset dan lokasi, HM/KM, status, WO, parts, inventory, biaya, tenaga kerja, attachment, dan histori.
5. **Infrastruktur:** REST API, MySQL, object storage, backup, RBAC, log aktivitas, serta integrasi impor Excel/PDF.

**Prinsip utama:** Sistem memakai satu sumber data (*single source of truth*); seluruh angka dashboard dihitung dari transaksi MySQL.

## 4.1 Prinsip Integrasi

- ID unit adalah kunci utama lintas modul; nomor polisi/lambung bukan pengganti primary key karena dapat berubah.
- Setiap WO memiliki child records: assignment, time log, progress update, parts, cost, attachment, approval, dan downtime event.
- Status unit diturunkan dari event aktif berprioritas tertinggi; misalnya WO breakdown aktif mengubah status menjadi Breakdown.
- File foto/PDF disimpan di object storage; metadata dan relasinya disimpan di MySQL.
- Impor data lama wajib menggunakan staging table, validasi duplikat, mapping kode unit, preview, lalu commit.

# 5. BPMN Terpadu dan Subproses

Legenda BPMN: lingkaran = event mulai/akhir; kotak sudut membulat = task; belah ketupat = gateway keputusan; swimlane = pemilik aktivitas. Diagram berikut adalah spesifikasi proses bisnis yang harus diwujudkan sebagai workflow pada website.

### Gambar 2 — BPMN Level 0: Siklus Terpadu Pengelolaan Equipment

**Swimlane:** Operasional/Pemohon, Admin & Planner, Mekanik/Logistik, Supervisi, dan Manager/GM.

**Alur proses:**

1. Proses dimulai dari kebutuhan atau kejadian di lapangan.
2. Operasional/Pemohon memasukkan data inspeksi, HM/KM, lokasi, atau keluhan.
3. Admin & Planner memvalidasi data lalu membuat rencana atau Work Order.
4. Supervisi menentukan jenis proses melalui gateway, kemudian mengarahkan pekerjaan ke proses PM, WO, atau asset.
5. Mekanik/Logistik menjalankan maintenance, proses parts, atau inspeksi.
6. Supervisi memverifikasi hasil, pencapaian SLA, dan status unit.
7. Bila biaya atau risiko memerlukan approval, Manager/GM memilih menyetujui, menolak, atau meminta revisi. Bila approval tidak diperlukan, proses dapat langsung difinalisasi.
8. Proses berakhir pada data final dan dashboard.

**Catatan:** Setiap event, task, gateway, approval, dan perubahan status menghasilkan histori serta audit trail.

### Gambar 3 — BPMN 1: Penerimaan, Registrasi, Mobilisasi, dan Mutasi Asset

**Swimlane:** Tim Investasi/Dealer, Admin Asset, Tim Inspeksi, Supervisi, dan Manager.

**Alur proses:**

1. Proses dimulai setelah pembayaran final dan informasi asset diterima dari Tim Investasi/Dealer.
2. Admin Asset mencatat warranty, invoice, BAST, dan identitas asset.
3. Tim Inspeksi melakukan inspeksi kelayakan sebelum pengiriman.
4. Supervisi memutuskan apakah asset layak dikirim.
   - **Tidak layak:** asset dikembalikan ke dealer atau menjalani perbaikan, lalu dilakukan inspeksi ulang.
   - **Layak:** Admin Asset menyiapkan pengiriman, termasuk penjadwalan driver dan dokumen perjalanan.
5. Tim Inspeksi memonitor mobilisasi dan kedatangan.
6. Supervisi melaksanakan commissioning dan BAST.
7. Manager mengaktifkan asset dan menetapkan lokasi awal.
8. Proses berakhir dengan status asset aktif.

**Mutasi berikutnya:** permintaan lokasi → persetujuan → serah terima → pembaruan lokasi dan histori.

### Gambar 4 — BPMN 2: Inspeksi, Breakdown, Work Order, dan Return to Work

**Swimlane:** Operator/Pelapor, Admin/Planner, Supervisi/Foreman, Mekanik, dan Manager.

**Alur proses:**

1. Proses dimulai dari temuan kerusakan.
2. Operator/Pelapor menghentikan unit dan merekam foto/video, HM/KM, serta lokasi.
3. Admin/Planner membuat tiket dan JO paling lambat 30 menit.
4. Supervisi/Foreman mengklasifikasikan pekerjaan sebagai minor/major, menentukan sebab, dan menetapkan prioritas.
5. Gateway menentukan kebutuhan spare part.
   - **Perlu spare part:** jalankan subproses permintaan spare part; pekerjaan dimulai setelah part tersedia.
   - **Tidak perlu spare part:** pekerjaan dapat langsung dimulai.
6. Mekanik memulai pekerjaan, mengisi checklist K3, dan mencatat time log.
7. Mekanik memperbarui progres pagi/sore beserta kendala.
8. Supervisi/Foreman menilai hasil uji fungsi. Jika belum lulus, pekerjaan dan pembaruan progres dilanjutkan.
9. Setelah lulus uji, Mekanik mengunggah foto sesudah pekerjaan dan mengajukan rekomendasi RTW.
10. Setelah verifikasi, WO ditutup dan unit berstatus RTW.

**Gate sistem:** pekerjaan atau order part tidak dapat dilakukan tanpa JO; WO tidak dapat ditutup tanpa foto before-after, jam selesai, hasil uji fungsi, dan verifikasi.

### Gambar 5 — BPMN 3: Permintaan dan Pengadaan Spare Part

**Swimlane:** Mekanik/Pemohon, Admin/Planner, Supervisi, Logistik/Procurement, dan Manager/GM.

**Alur proses:**

1. Proses dimulai dari kebutuhan part yang terhubung dengan JO.
2. Admin/Planner membuat SPB yang memuat nomor part, kuantitas, unit, dan urgensi.
3. Supervisi memvalidasi JO dan kebenaran kebutuhan. Jika tidak valid, SPB dikembalikan untuk revisi.
4. Logistik/Procurement mengecek stok dan melakukan reservasi.
5. Gateway menentukan ketersediaan stok.
   - **Tersedia:** part diterbitkan (*issue*) ke JO dan diserahkan kepada mekanik.
   - **Tidak tersedia:** Manager/GM memberi approval sesuai batas nilai, lalu Logistik/Procurement membuat PO/order, memonitor ETA dan kendala, serta menerima dan melakukan QC barang.
6. Proses berakhir ketika part diterima mekanik.

**SLA dan status:** SLA dihitung sejak SPB disetujui sampai barang tiba. Urutan status: DRAFT → SUBMITTED → APPROVED → SOURCING → ORDERED → SHIPPED → RECEIVED → ISSUED/CLOSED.

### Gambar 6 — BPMN 4: Preventive Maintenance Berbasis HM/KM

**Swimlane:** Operator/Admin, Planner, Supervisi, Logistik, dan Mekanik.

**Alur proses:**

1. Operator/Admin memasukkan HM/KM harian.
2. Planner menghitung due berdasarkan interval dan HM/KM terkini.
3. Gateway menilai apakah jadwal mendekati jatuh tempo.
   - **Belum:** proses kembali menunggu input HM/KM berikutnya.
   - **Ya/H-3:** Planner membuat rencana PM dan mereservasi slot unit.
4. Logistik menyiapkan kitting filter, oli, grease, dan parts.
5. Supervisi memeriksa kesiapan unit dan material. Jika belum siap, jadwal diubah atau dijadwalkan ulang.
6. Jika siap, Mekanik menjalankan PM, mengisi checklist, dan mencatat time log.
7. Supervisi memverifikasi realisasi dan menetapkan next due.
8. Proses berakhir dengan PM berstatus closed.

**Peringatan:** Sistem membedakan Due Soon, Due, dan Overdue. Dashboard membandingkan rencana dengan realisasi serta mencatat alasan keterlambatan.

### Gambar 7 — BPMN 5: Ban, Grease, Aki, Cutting Bit, dan Major Component

**Swimlane:** Inspector/Mekanik, Admin/Planner, Supervisi, Logistik, dan Manager.

**Alur proses:**

1. Proses dimulai dari jadwal inspeksi atau weekly maintenance.
2. Inspector/Mekanik memasukkan posisi, ukuran/pengukuran, kondisi, HM/KM, dan foto.
3. Admin/Planner mengevaluasi apakah kondisi melewati ambang.
   - **Normal:** histori umur pakai dan biaya diperbarui.
   - **Merah/kuning:** Supervisi menentukan tindakan monitor, rotate, repair, atau replace.
4. Logistik mengecek stok dan menyiapkan material.
5. Inspector/Mekanik melaksanakan tindakan dan mencatat serial serta posisi.
6. Admin/Planner memperbarui histori umur pakai dan biaya.
7. Proses berakhir dengan kondisi terbaru.

**Contoh ambang ban:** merah <3,2 mm atau rusak; kuning 3,2–8,5 mm; hijau >8,5 mm. Ambang lain harus dapat dikonfigurasi.

### Gambar 8 — BPMN 6: Pelaporan Accident dan Corrective/Preventive Action

**Swimlane:** Pelapor/Operator, Supervisi/HSE, Admin Equipment, Manager, dan General Manager.

**Alur proses:**

1. Proses dimulai ketika accident terjadi.
2. Pelapor/Operator mengamankan area, menghentikan unit, dan mengumpulkan bukti awal.
3. Supervisi/HSE memvalidasi kronologi dan dampak.
4. Admin Equipment meregistrasikan laporan dan mengaitkannya dengan asset/WO.
5. Supervisi/HSE menganalisis faktor manusia, mekanis, lingkungan, dan sistem.
6. Manager menilai apakah risiko atau biaya tergolong tinggi.
   - **Tidak:** Manager menetapkan corrective dan preventive action.
   - **Ya:** General Manager melakukan review dan mengambil keputusan eskalasi, kemudian tindakan ditetapkan.
7. Proses berakhir setelah CAPA diverifikasi dan bukti dinyatakan selesai.

**Data wajib:** data umum, lingkungan, kerusakan, downtime, biaya, sebab awal, tindakan segera, dan pencegahan.

### Gambar 9 — BPMN 7: Biaya, Produktivitas Mekanik, KPI, dan Pelaporan Manajemen

**Swimlane:** Admin/Finance, Planner, Supervisi, Manager, dan General Manager.

**Alur proses:**

1. Proses dimulai dari transaksi PO, SPB, WO, atau jam kerja.
2. Admin/Finance memvalidasi bukti dan mengalokasikan transaksi ke unit/WO.
3. Planner menghitung downtime, MTTR, PM, biaya, dan jam mekanik.
4. Supervisi meninjau anomali serta kualitas data.
5. Manager menilai apakah hasil melewati target atau budget.
   - **Ya:** Manager melakukan analisis akar masalah dan menyusun action plan.
   - **Tidak:** hasil dapat diteruskan ke review executive.
6. General Manager meninjau executive dashboard dan mengambil keputusan.
7. Proses berakhir ketika periode dikunci dan laporan diterbitkan.

**Catatan:** Semua KPI dihitung dari transaksi. Koreksi setelah periode dikunci harus melalui reopen approval dan tercatat dalam audit trail.

# 6. Status dan Transisi yang Wajib Dikunci Sistem

| **Objek**     | **Status**                                                                                                           | **Aturan**                                              |
|---------------|----------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------|
| Unit          | READY, OPERATING, STANDBY, INSPECTION, PM, BREAKDOWN, WAITING_PART, MOBILIZATION, ACCIDENT_HOLD, INACTIVE/DISPOSED   | Hanya satu status efektif; histori tidak boleh dihapus. |
| WO            | DRAFT → SUBMITTED → APPROVED → ASSIGNED → IN_PROGRESS ↔ PAUSED/WAITING_PART → TESTING → VERIFIED → CLOSED            | REJECTED/CANCELLED/REOPENED wajib alasan dan approval.  |
| SPB/Part      | DRAFT → SUBMITTED → APPROVED → STOCK_CHECK → RESERVED atau SOURCING → ORDERED → SHIPPED → RECEIVED → ISSUED → CLOSED | Tidak dapat APPROVED jika nomor WO kosong.              |
| PM            | FORECAST → PLANNED → MATERIAL_READY → SCHEDULED → IN_PROGRESS → VERIFIED → CLOSED                                    | OVERDUE adalah flag, bukan pengganti status proses.     |
| Accident      | DRAFT → REPORTED → INVESTIGATING → ACTION_PLAN → IMPLEMENTING → VERIFIED → CLOSED                                    | Unit tetap ACCIDENT_HOLD sampai izin release.           |
| Asset receipt | PLANNED → PRE_INSPECTION → NOT_FIT/FIT → IN_TRANSIT → ARRIVED → COMMISSIONING → ACTIVE                               | Dokumen minimum harus lengkap sebelum ACTIVE.           |

# 7. Aturan Bisnis dan SLA

| **ID** | **Aturan**                                                                                                                                   | **Respons sistem**                        |
|--------|----------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------|
| BR-01  | Kerusakan harus diidentifikasi dan tiket/JO dibuat maksimal 30 menit setelah unit dinyatakan down.                                           | Eskalasi ke supervisi dan manager.        |
| BR-02  | Pekerjaan mekanik dan order spare part tidak dapat dimulai tanpa WO/JO approved.                                                             | Tombol Start/Submit SPB disabled.         |
| BR-03  | Progres WO aktif harus dilaporkan minimal dua kali sehari (pagi dan sore).                                                                   | Reminder dan overdue badge.               |
| BR-04  | WO tidak dapat CLOSED tanpa foto before-after, penyebab, tindakan, jam selesai, hasil uji fungsi, dan verifikator.                           | Validasi server-side.                     |
| BR-05  | Minor ≤4 jam dan tanpa bongkar komponen utama; major \>4 jam atau melibatkan mesin/transmisi/hidrolik utama/final drive/undercarriage total. | Klasifikasi dapat dikoreksi dengan audit. |
| BR-06  | PM dihitung dari last service + interval; warning dibuat H-3 atau ambang HM/KM configurable.                                                 | Due soon/due/overdue.                     |
| BR-07  | Ban: merah \<3,2 mm atau damage; kuning 3,2–8,5 mm; hijau \>8,5 mm.                                                                          | Ambang tersimpan di master setting.       |
| BR-08  | SLA spare part dihitung dari waktu SPB disetujui sampai barang diterima; dampak RTW harus ditandai.                                          | Grafik lead time & delay.                 |
| BR-09  | RTW hanya efektif setelah supervisor memverifikasi uji fungsi dan dokumentasi.                                                               | Mekanik hanya mengajukan RTW.             |
| BR-10  | Periode laporan yang dikunci tidak dapat diedit kecuali di-reopen manager dengan alasan.                                                     | Audit trail penuh.                        |

# 8. Data Minimum yang Harus Disimpan

| **Entitas**                        | **Field inti**                                                                                                     |
|------------------------------------|--------------------------------------------------------------------------------------------------------------------|
| assets                             | asset_id, code, lambung, plate, serial, make/model, category, year, ownership, warranty, acquisition_value, status |
| locations & asset_movements        | location_id; origin, destination, request/approval/arrival time, BAST, responsible person                          |
| meter_readings                     | asset_id, HM, KM, reading_at, source, photo, validator                                                             |
| inspections & findings             | template, item, result, severity, note, photo, linked WO                                                           |
| work_orders                        | WO no, asset, type, priority, complaint, diagnosis, cause, planned/actual time, status, downtime                   |
| wo_assignments & time_logs         | mechanic, role, start/pause/resume/end, normal/overtime hours                                                      |
| wo_progress & attachments          | progress %, narrative, constraint, timestamp, before/after/test evidence                                           |
| parts & inventory_tx               | part no, serial/batch, warehouse, stock, reserve, issue, return, unit cost                                         |
| purchase_requests/orders           | SPB, WO, approval, vendor, ETA, receive, delay reason, amount                                                      |
| maintenance_plans & executions     | interval type/value, last/next due, plan date, actual date, materials, checklist                                   |
| tires/components/batteries         | serial, position, measurement, condition, install/remove date, HM/KM, action, cost                                 |
| cost_transactions                  | unit, WO/PM, category, document no, vendor, qty, unit price, tax, total, posting period                            |
| employees/KPI/overtime             | employee, skill, assignment, productive hours, SPL, job count, delay ratio                                         |
| accidents & CAPA                   | event, environment, damage, downtime, financial impact, cause category, action owner/due/evidence                  |
| approvals/audit_logs/notifications | object, action, actor, old/new value, timestamp, comment, notification status                                      |

# 9. Dashboard yang Harus Dinamis

| **Dashboard**          | **Isi dan drill-down**                                                                                                                                        |
|------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Executive              | Total unit; nilai aset; ready/operating/standby/breakdown; availability/utilization; downtime; biaya bulan ini; budget vs actual; top 10 unit biaya/downtime. |
| Work Order             | Open; in progress; waiting spare part; testing; completed; high/emergency priority; average age; MTTR; total downtime; total cost.                            |
| Condition Monitoring   | Ready for use; breakdown; downtime trend harian; status per kategori; ban merah/kuning/hijau; component due; aki/cutting bit/grease compliance.               |
| Preventive Maintenance | Plan vs actual; due soon; due; overdue; compliance; keterlambatan per alasan; kesiapan material.                                                              |
| Lokasi                 | Filter Yard KM 12, Pit, Borrow Pit, Minas, dan lokasi master lain; populasi/status/WO/biaya per lokasi.                                                       |
| Mekanik & KPI          | Jam normal/lembur/produktif; jumlah job; rata-rata durasi; delay spare part; efektivitas; WO callback/repeat failure.                                         |
| Biaya                  | Bulanan dan kumulatif; per unit/kategori/lokasi/vendor/WO; parts/labor/external/service; variance budget; drill-down ke bukti.                                |

## 9.1 Definisi KPI Minimum

| **KPI**                | **Rumus**                                                 |
|------------------------|-----------------------------------------------------------|
| Physical Availability  | (Scheduled time − downtime) / scheduled time × 100%       |
| Utilization            | Operating hours / available hours × 100%                  |
| Breakdown Rate         | Breakdown hours / scheduled hours × 100%                  |
| PM Compliance          | PM selesai tepat waktu / PM jatuh tempo × 100%            |
| MTTR                   | Total repair time / jumlah breakdown selesai              |
| MTBF                   | Total operating time / jumlah breakdown                   |
| Cost per Unit          | Total biaya terkait unit pada periode                     |
| Mechanic Effectiveness | Jam produktif terverifikasi / jam standar tersedia × 100% |

# 10. Menu Website dan Quick Action

| **Menu**     | **Submenu**                                                                      |
|--------------|----------------------------------------------------------------------------------|
| Dashboard    | Executive, Operasional, Maintenance, Biaya, KPI, Lokasi                          |
| Asset        | Daftar asset, detail 360°, penerimaan, mutasi, dokumen, disposal                 |
| Maintenance  | Inspeksi, Breakdown/WO, PM, schedule, history, condition monitoring              |
| Logistik     | Master part, stok, SPB, procurement, receiving, issue/return, lead time          |
| Biaya        | Transaksi, budget, plan vs actual, cost history, approval                        |
| People & KPI | Mekanik, kompetensi, assignment, time log, SPL, produktivitas                    |
| HSE/Accident | Laporan, investigasi, CAPA, release unit                                         |
| Laporan      | Daily equipment, availability, WO, PM, ban, parts, biaya, KPI, accident          |
| Pengaturan   | User/role, lokasi, kategori, SLA, interval, threshold, approval matrix, template |

## Quick Action pada Dashboard

- Tambah Aset
- Input HM/KM
- Laporkan Breakdown
- Buat Work Order
- Buat Rencana PM
- Minta Spare Part
- Update Progres WO
- Inspeksi Ban/Komponen
- Laporkan Accident
- Input Biaya

# 11. Notifikasi dan Eskalasi

| **Pemicu**                           | **Penerima**                 | **Perilaku**                           |
|--------------------------------------|------------------------------|----------------------------------------|
| WO emergency/high dibuat             | Foreman, planner, manager    | Segera; ulang jika belum acknowledged. |
| 30 menit sejak down belum ada JO     | Planner, supervisi, manager  | Eskalasi otomatis.                     |
| Update pagi/sore belum masuk         | Mekanik PIC, foreman         | Reminder lalu overdue.                 |
| WO menunggu part melebihi SLA        | Logistik, planner, manager   | Tampilkan dampak RTW.                  |
| PM due soon/due/overdue              | Planner, foreman, manager    | H-3 dan ambang HM/KM.                  |
| Ban/component masuk ambang merah     | Supervisi, planner, logistik | Buat rekomendasi tindakan.             |
| Budget/approval threshold terlampaui | Manager/GM sesuai matrix     | Approval task.                         |
| CAPA jatuh tempo                     | Action owner, manager/HSE    | Reminder dan escalation.               |

# 12. Non-Functional Requirements

- Responsive web/PWA; fungsi lapangan tetap nyaman pada layar ponsel dan koneksi rendah.
- Role-based access control sampai level lokasi dan jenis aksi; password hashing dan session timeout.
- Audit trail immutable untuk perubahan status, approval, biaya, meter reading, dan master kritis.
- Upload JPG/PNG/PDF dengan kompresi, timestamp, checksum, preview, dan antivirus scanning.
- Backup database harian; uji restore berkala; retensi sesuai kebijakan perusahaan.
- Server-side validation; idempotency untuk submit; soft delete; optimistic locking pada transaksi.
- Pagination, filter, saved view, export Excel/PDF, dan API terdokumentasi.
- Target awal yang disarankan: waktu muat dashboard \<3 detik untuk filter umum; availability 99,5% per bulan.

# 13. Acceptance Criteria untuk Developer

| **ID** | **Kriteria uji**                                                                                                 |
|--------|------------------------------------------------------------------------------------------------------------------|
| AC-01  | Saat operator menandai unit down, sistem mencatat waktu down dan menghitung deadline JO 30 menit.                |
| AC-02  | Tombol Start Work tidak aktif jika WO belum approved atau belum memiliki PIC.                                    |
| AC-03  | SPB tidak dapat disubmit jika nomor WO/PM, part, qty, dan urgensi belum lengkap.                                 |
| AC-04  | Perubahan WAITING_PART otomatis menghentikan repair-clock aktif tetapi downtime unit tetap berjalan.             |
| AC-05  | WO tidak dapat closed tanpa foto before-after, time log, cause/action, test result, dan supervisor verification. |
| AC-06  | Input HM/KM baru yang lebih kecil dari bacaan terakhir ditolak atau masuk exception approval.                    |
| AC-07  | PM due/overdue berubah otomatis setelah meter reading masuk dan next due terbentuk saat PM closed.               |
| AC-08  | Dashboard lokasi berubah saat filter dipilih dan semua angka dapat di-drill-down ke daftar transaksi.            |
| AC-09  | Biaya WO sama dengan penjumlahan part issue, jasa eksternal, labor, dan biaya terkait yang valid.                |
| AC-10  | User yang sama tidak dapat mengajukan dan menyetujui transaksi yang membutuhkan segregation of duties.           |
| AC-11  | Semua ekspor memakai filter aktif, timestamp, pembuat laporan, dan data yang sama dengan layar.                  |
| AC-12  | Setiap perubahan status/approval dapat dilihat di timeline detail object.                                        |

# 14. Tahapan Implementasi yang Disarankan

| **Tahap**                 | **Output**                                                                      |
|---------------------------|---------------------------------------------------------------------------------|
| Fase 1 — Fondasi          | User/role, lokasi, master asset, status, HM/KM, dokumen, impor awal, audit log. |
| Fase 2 — Core Maintenance | Inspeksi, breakdown, WO, mekanik/time log, foto, RTW, dashboard operasional.    |
| Fase 3 — PM & Logistik    | Maintenance plan, due/overdue, stok, SPB, procurement, receiving/issue, SLA.    |
| Fase 4 — Condition & Cost | Ban/grease/aki/cutting bit/component, biaya, budget vs actual, KPI mekanik.     |
| Fase 5 — Management       | Accident/CAPA, executive dashboard, approval GM, optimasi, integrasi lanjutan.  |

> **Ketentuan setiap fase:** prototype UI, review proses, development, migration test, user acceptance test, training, go-live, dan masa stabilisasi.

# 15. Keputusan yang Perlu Dikonfirmasi Sebelum Coding

| **Keputusan**              | **Hal yang harus disepakati**                                                           |
|----------------------------|-----------------------------------------------------------------------------------------|
| Approval biaya             | Batas nominal Supervisor, Manager, dan General Manager.                                 |
| Struktur lokasi            | Daftar final site/yard/pit/borrow pit dan hak akses per lokasi.                         |
| Jam laporan progres        | Jam pasti untuk update pagi dan sore.                                                   |
| Availability & utilization | Kalender scheduled hours, shift, planned downtime, dan treatment standby.               |
| Klasifikasi biaya          | Chart of accounts/cost category yang digunakan BRA.                                     |
| Sumber HM/KM               | Operator, GPS/telematics, admin, atau kombinasi serta mekanisme validasi.               |
| Tanggung jawab repair      | Aturan BRA vs penyewa per kontrak/site; harus configurable.                             |
| Nomor dokumen              | Format nomor WO, SPB, BAST, TAR, accident, dan approval.                                |
| Data awal                  | Tanggal cut-off, file master yang menjadi authoritative source, dan aturan deduplikasi. |

# Lampiran A — Pemetaan Sumber ke Modul

| **Sumber**                                           | **Data/proses yang diterjemahkan**                                    | **Modul**   |
|------------------------------------------------------|-----------------------------------------------------------------------|-------------|
| SOP Penerimaan Alat                                  | Penerimaan, inspeksi pra-kirim, mobilisasi, commissioning, BAST       | M02         |
| 005 Prosedure Penanganan Unit Breakdown              | JO, identifikasi 30 menit, progres 2×, spare part, RTW, sanksi        | M04/M06     |
| SOP Pemeriksaan, Penggunaan, Pemeliharaan, Perbaikan | Pre-trip, minor/major, tanggung jawab, PM interval, laporan kerusakan | M03/M04/M05 |
| Plan Service Juli 2026                               | HM/KM, interval, last service, plan, realisasi, variance, completion  | M05         |
| Mechanical Job Order / WMJO / Estimasi Progress      | Assignment, job detail, waktu, mekanik, lokasi, status, progress      | M04/M09     |
| Template Monitoring Pengadaan Sparepart              | SPB, JO, approval, lead time, kendala, RTW impact                     | M06         |
| Report Ban 19.07.2026                                | Posisi ban, tread depth, ukuran, warna kondisi                        | M07         |
| Weekly Greasing / Pemakaian Aki / Cutting Bit        | Jadwal, HM/KM, serial/part, issue, histori penggunaan                 | M07         |
| Equipment Expenses / PO / SPPU / Plan vs Actual      | Biaya, vendor, bukti, budget, realisasi, alokasi unit                 | M08         |
| Analisis Produktivitas / Absensi / SPL / KPI         | Jam normal/lembur, job count, delay, efektivitas, overtime approval   | M09         |
| Laporan Accident / TAR                               | Insiden, faktor penyebab, dampak, tindakan, technical analysis        | M10         |
| Rekap Unit Standby / Lokasi / Mutasi                 | Status, lokasi, populasi, movement history                            | M01/M02/M11 |

# Lampiran B — Catatan untuk Tim Developer

- Mulai dari model data dan state machine, bukan dari tampilan dashboard. Dashboard yang benar adalah hasil transaksi yang benar.
- Jangan menyimpan status hanya sebagai teks bebas. Gunakan enum/master status dan transition rules.
- Jangan menyalin struktur Excel apa adanya menjadi tabel database. Normalisasi asset, WO, parts, people, time log, dan biaya.
- Sediakan detail 360° per unit: identitas, lokasi, meter, inspeksi, WO, PM, parts, ban/component, biaya, accident, dokumen, dan timeline.
- Setiap angka agregat harus mempunyai drill-down. Jika total WO open = 12, klik angka harus menampilkan 12 WO sumbernya.
- Approval matrix, SLA, lokasi, interval PM, serta threshold condition harus dapat diatur tanpa perubahan kode.
