# Rancangan Pengembangan Sistem FleetMonitor

## 1. Identitas Dokumen

| Informasi | Keterangan |
|---|---|
| Nama dokumen | Rancangan Pengembangan Sistem FleetMonitor |
| Sistem | Equipment Maintenance & Fleet Monitoring System |
| Fokus | Workshop, Work Order, SPK Mekanik, Preventive Maintenance, Spare Part, Logistik, Tire, Accu, Approval, dan Arsip Pemeliharaan |
| Dasar evaluasi | Masukan operasional workshop dan hasil pemeriksaan file `dashboard.html` |
| Status dokumen | Rancangan pengembangan dan acuan monitoring progres |

---

## 2. Latar Belakang

Sistem FleetMonitor saat ini sudah memiliki struktur dasar untuk monitoring unit, master asset, inspeksi, Work Order, preventive maintenance, spare part dan logistik, condition monitoring, fuel, biaya, laporan, approval, serta pengaturan sistem.

Berdasarkan masukan operasional workshop, terdapat beberapa penyesuaian utama:

1. Istilah **JO** yang digunakan saat ini perlu diarahkan menjadi **SPK Mekanik**.
2. **Work Order** tetap digunakan sebagai permintaan perbaikan dari operator atau driver.
3. SPK harus menjadi dokumen resmi penugasan mekanik setelah inspeksi dan persetujuan.
4. Sistem perlu mendukung alur dari laporan kerusakan sampai unit kembali siap digunakan.
5. Preventive Maintenance membutuhkan notifikasi otomatis, kelengkapan project, upload dokumen, serta bukti realisasi.
6. Modul Spare Part & Logistik membutuhkan penyempurnaan SPB, pemakaian oli, bukti foto, upload laporan, dan pemisahan data berdasarkan project.
7. SPK perlu dilengkapi data **Tire** dan **Accu**.
8. Seluruh proses approval perlu dilakukan secara online dan memiliki riwayat yang dapat diaudit.

---

## 3. Tujuan Pengembangan

Pengembangan sistem diarahkan untuk:

- Memisahkan fungsi Work Order dan SPK secara jelas.
- Membentuk alur kerja workshop yang sesuai operasional lapangan.
- Menghubungkan laporan kerusakan, inspeksi, approval, SPK, sparepart, pelaksanaan, pengujian, dan penutupan pekerjaan.
- Mengurangi pencatatan manual oleh mekanik.
- Menyediakan bukti kerja dan dokumen digital yang terstruktur.
- Meningkatkan keterlacakan biaya, waktu kerja, pemakaian sparepart, Tire, Accu, dan maintenance history.
- Menyediakan monitoring progres pengembangan sistem secara bertahap.

---

# BAGIAN A — KONSEP DOKUMEN DAN ISTILAH

## 4. Perbedaan Work Order, SPK, dan SPB

### 4.1 Work Order

Work Order adalah dokumen permintaan pemeriksaan atau perbaikan yang dibuat oleh operator, driver, pengawas, admin, atau hasil inspeksi/P2H.

Work Order berfungsi untuk mencatat:

- Unit yang mengalami masalah.
- Project atau lokasi unit.
- Nama pelapor.
- Keluhan atau gejala kerusakan.
- Waktu kejadian.
- Kondisi unit saat dilaporkan.
- Tingkat prioritas.
- Apakah unit mengalami downtime.
- Bukti foto awal.

Work Order belum langsung menjadi perintah kerja mekanik. Work Order harus melalui inspeksi, estimasi, dan approval terlebih dahulu apabila perbaikannya membutuhkan persetujuan.

### 4.2 SPK Mekanik

SPK Mekanik adalah dokumen resmi yang diterbitkan admin workshop untuk menugaskan mekanik melakukan pekerjaan tertentu.

SPK diterbitkan setelah:

1. Work Order diterima.
2. Kepala mekanik melakukan inspeksi.
3. Kebutuhan sparepart, waktu, dan biaya dihitung.
4. Perbaikan mendapatkan persetujuan sesuai kewenangan.

SPK menjadi dasar untuk:

- Penugasan mekanik.
- Pelaksanaan pekerjaan.
- Pengambilan sparepart.
- Pencatatan waktu kerja.
- Pencatatan biaya.
- Pencatatan Tire dan Accu.
- Pengujian hasil perbaikan.
- Penutupan pekerjaan.
- Maintenance history.

### 4.3 SPB

SPB adalah dokumen permintaan atau pengeluaran barang/sparepart yang terkait dengan kebutuhan pekerjaan.

SPB harus terhubung dengan:

- Nomor SPK.
- Nomor Work Order.
- Unit.
- Project.
- Pemohon.
- Gudang asal.
- Daftar barang.
- Status approval.
- Status pengeluaran.

### 4.4 Migrasi JO Lama

Data JO lama tidak dihapus. Data tersebut perlu dimigrasikan sebagai berikut:

| Data lama | Data baru |
|---|---|
| Nomor JO | Nomor SPK |
| JO Mekanik | SPK Mekanik |
| No JO / Mekanik pada logistik | No SPK / Mekanik |
| Histori JO | Histori SPK |

---

# BAGIAN B — ALUR OPERASIONAL UTAMA

## 5. Alur Laporan Kerusakan sampai Unit Ready

```text
Operator / Driver menemukan kerusakan
                ↓
Membuat Laporan Kerusakan / Work Order
                ↓
Admin Workshop melakukan registrasi
                ↓
Kepala Mekanik melakukan inspeksi awal
                ↓
Diagnosis, estimasi waktu, sparepart, dan biaya
                ↓
Approval manajemen / pemilik unit
                ↓
Admin Workshop menerbitkan SPK Mekanik
                ↓
Mekanik menerima dan memulai pekerjaan
                ↓
Pengambilan sparepart berdasarkan nomor SPK
                ↓
Pelaksanaan perbaikan
                ↓
Pengisian hasil kerja dan bukti foto
                ↓
Test drive / functional test / quality control
                ↓
Validasi Kepala Mekanik
                ↓
Validasi Admin Workshop
                ↓
Status unit menjadi Ready for Use
                ↓
Dokumen masuk ke Maintenance History
```

## 6. Peran Pengguna

### 6.1 Operator / Driver

- Membuat laporan kerusakan.
- Mengisi keluhan unit.
- Mengunggah foto awal.
- Melihat status laporan.

### 6.2 Admin Workshop

- Menerima dan meregistrasi laporan.
- Mengatur antrean pekerjaan.
- Menerbitkan SPK.
- Memvalidasi dokumen penyelesaian.
- Menutup SPK.
- Mengarsipkan dokumen.

### 6.3 Kepala Mekanik

- Melakukan inspeksi awal.
- Menentukan diagnosis.
- Menentukan kebutuhan pekerjaan.
- Menentukan sparepart.
- Memberikan estimasi waktu dan biaya.
- Melakukan quality control.
- Menentukan hasil test drive.

### 6.4 Mekanik

- Menerima SPK.
- Mengisi waktu mulai.
- Mengisi progres pekerjaan.
- Mencatat tindakan perbaikan.
- Mencatat sparepart aktual.
- Mengunggah bukti foto.
- Mengisi hasil pekerjaan.
- Mengajukan penyelesaian SPK.

### 6.5 Logistik

- Memproses SPB.
- Mengeluarkan sparepart berdasarkan SPK.
- Mencatat barang masuk dan keluar.
- Mencatat barang bekas.
- Mencatat oli dan lubricant.
- Mengunggah bukti barang.

### 6.6 Equipment Manager / Manajemen

- Memberikan approval.
- Memantau biaya, downtime, dan progres pekerjaan.
- Memberikan keputusan untuk pekerjaan besar.
- Menyetujui pelepasan unit apabila diperlukan.

---

# BAGIAN C — RANCANGAN MODUL WORK ORDER DAN SPK

## 7. Perubahan Menu

Menu yang sebelumnya bernama:

```text
Work Order
```

Diarahkan menjadi:

```text
Work Order & SPK
```

Menu memiliki dua tab utama:

1. Work Order.
2. SPK Mekanik.

## 8. Tab Work Order

### 8.1 Status Work Order

```text
Dilaporkan
Menunggu Registrasi
Menunggu Inspeksi
Menunggu Estimasi
Menunggu Persetujuan
Disetujui
Ditolak
SPK Diterbitkan
Selesai
Dibatalkan
```

### 8.2 Data Work Order

| Field | Keterangan |
|---|---|
| Nomor WO | Dibuat otomatis oleh sistem |
| Tanggal dan waktu | Waktu laporan dibuat |
| Project | Project asal unit |
| Unit ID | Kode unit |
| Nomor polisi / serial number | Identitas unit |
| Pelapor | Operator, driver, atau pengguna |
| Jabatan pelapor | Posisi pelapor |
| Lokasi kejadian | Posisi unit saat rusak |
| Keluhan | Gejala awal kerusakan |
| Kategori kerusakan | Engine, hydraulic, electrical, tire, accu, body, dan lain-lain |
| Prioritas | Normal, urgent, emergency |
| Downtime | Ya atau tidak |
| Kondisi operasi | Berhenti total atau operasi terbatas |
| Foto awal | Bukti kondisi awal |
| Kepala mekanik | Pemeriksa awal |
| Diagnosis | Hasil inspeksi |
| Estimasi waktu | Estimasi durasi pekerjaan |
| Estimasi biaya | Perkiraan biaya |
| Estimasi sparepart | Kebutuhan awal |
| Status approval | Menunggu, disetujui, ditolak |
| Nomor SPK | Diisi setelah SPK diterbitkan |

### 8.3 Tampilan Work Order

Work Order dapat ditampilkan dalam dua mode:

- Kanban berdasarkan status.
- Tabel untuk pencarian, filter, dan export.

Filter yang tersedia:

- Project.
- Unit.
- Tanggal.
- Pelapor.
- Prioritas.
- Status.
- Downtime.
- Kategori kerusakan.

## 9. Tab SPK Mekanik

### 9.1 Status SPK

```text
Draft
Menunggu Penerbitan
Diterbitkan
Diterima Mekanik
In Progress
Menunggu Sparepart
Pekerjaan Tertunda
Menunggu QC
Perlu Perbaikan Ulang
Selesai Teknis
Menunggu Validasi Admin
Ditutup
Dibatalkan
```

### 9.2 Data Header SPK

| Field | Keterangan |
|---|---|
| Nomor SPK | Dibuat otomatis |
| Referensi WO | Nomor Work Order asal |
| Project | Project unit |
| Unit ID | Kode unit |
| Nomor polisi / SN | Identitas unit |
| Lokasi pengerjaan | Workshop atau site |
| Tanggal terbit | Waktu SPK diterbitkan |
| Admin penerbit | Admin workshop |
| Kepala mekanik | Penanggung jawab teknis |
| Mekanik utama | Mekanik penerima tugas |
| Mekanik pendamping | Mekanik tambahan |
| Target mulai | Waktu rencana mulai |
| Target selesai | Waktu rencana selesai |
| Prioritas | Normal, urgent, emergency |

### 9.3 Rincian Pekerjaan SPK

- Jenis pekerjaan.
- Diagnosis kerusakan.
- Langkah pengerjaan.
- Komponen yang diperiksa.
- Sparepart yang direncanakan.
- Estimasi jam kerja.
- Estimasi biaya.
- Catatan keselamatan kerja.
- Alat khusus yang dibutuhkan.

### 9.4 Realisasi Pekerjaan

- Tanggal dan jam mulai aktual.
- Tanggal dan jam selesai aktual.
- Mekanik aktual.
- Tindakan perbaikan.
- Sparepart aktual.
- Barang lama yang dikembalikan.
- Hambatan pekerjaan.
- Waktu tunggu sparepart.
- Total downtime.
- Foto sebelum.
- Foto selama pengerjaan.
- Foto sesudah.
- Bukti barang baru.
- Bukti barang bekas.

### 9.5 Penutupan SPK

SPK tidak boleh ditutup sebelum seluruh syarat berikut terpenuhi:

- Mekanik penanggung jawab terisi.
- Tindakan perbaikan terisi.
- Waktu mulai dan selesai terisi.
- Sparepart aktual tercatat.
- Foto bukti tersedia.
- Pemeriksaan Tire dan Accu diisi apabila terkait.
- Hasil test drive atau functional test tersedia.
- Quality control disetujui kepala mekanik.
- Admin workshop melakukan validasi akhir.

---

# BAGIAN D — APPROVAL ONLINE

## 10. Jenis Approval

Sistem perlu mendukung:

1. Approval estimasi Work Order.
2. Approval biaya perbaikan.
3. Approval penerbitan SPK.
4. Approval SPB.
5. Approval biaya tambahan.
6. Approval pekerjaan outsource.
7. Approval penyelesaian teknis.
8. Approval pelepasan unit menjadi Ready.

## 11. Data Approval

| Field | Keterangan |
|---|---|
| Nomor approval | Nomor transaksi |
| Dokumen asal | WO, SPK, SPB, PM, atau biaya |
| Pemohon | Pengguna yang mengajukan |
| Approver | Pengguna yang menyetujui |
| Level approval | Supervisor, manager, owner |
| Status | Menunggu, disetujui, ditolak, revisi |
| Tanggal dan waktu | Waktu tindakan |
| Catatan | Catatan approver |
| Alasan penolakan | Wajib jika ditolak |
| Nilai awal | Estimasi awal |
| Nilai revisi | Nilai setelah revisi |
| Riwayat | Seluruh aktivitas approval |

## 12. Aturan Approval

- Dokumen tidak boleh hilang dari daftar setelah disetujui.
- Status dokumen harus berubah secara permanen.
- Sistem harus menyimpan identitas approver.
- Sistem harus menyimpan waktu persetujuan.
- Penolakan wajib disertai alasan.
- Pengajuan revisi harus membuat versi baru.
- Seluruh perubahan masuk audit trail.

---

# BAGIAN E — MASTER PROJECT

## 13. Project yang Harus Ditambahkan

Data project minimal:

```text
RWI
Matting Board
Lahat
Sunter Area Stadium
Site Alpha
Yard / Workshop
Project lainnya
```

## 14. Struktur Master Project

| Field | Keterangan |
|---|---|
| Kode project | Kode unik |
| Nama project | Nama project |
| Lokasi | Alamat atau wilayah |
| Status | Aktif atau nonaktif |
| Project manager | Penanggung jawab |
| Admin project | Admin terkait |
| Workshop utama | Workshop penanganan |
| Gudang utama | Lokasi sparepart |
| Daftar unit | Unit aktif pada project |
| Tanggal mulai | Awal project |
| Tanggal selesai | Akhir project |

## 15. Integrasi Project

Master Project harus digunakan oleh:

- Dashboard.
- Monitoring Unit.
- Master Asset.
- Inspeksi & P2H.
- Work Order.
- SPK.
- Preventive Maintenance.
- Spare Part & Logistik.
- Fuel Management.
- Biaya.
- Laporan & Form.
- Approval.

---

# BAGIAN F — PREVENTIVE MAINTENANCE

## 16. Notifikasi Otomatis

Kategori notifikasi:

| Status | Kondisi |
|---|---|
| Due Soon | Sisa ≤ 50 HM atau ≤ 7 hari |
| Priority | Sisa ≤ 20 HM atau ≤ 3 hari |
| Overdue | Jadwal sudah terlewati |
| Critical Overdue | Melewati batas toleransi |

Media notifikasi:

- Notifikasi dashboard.
- Email.
- WhatsApp.
- Badge pada menu.
- Escalation notification.

## 17. Data Notifikasi

- Unit.
- Project.
- Jenis PM.
- Jadwal PM.
- HM/KM saat ini.
- Sisa HM/KM.
- Tanggal jatuh tempo.
- Status notifikasi.
- Penerima.
- Waktu dikirim.
- Status dibaca.
- Status ditindaklanjuti.

## 18. Dokumen PM

Fitur yang dibutuhkan:

- Upload laporan PM PDF dari planner.
- Upload checklist service.
- Upload foto sparepart lama.
- Upload foto sparepart baru.
- Upload bukti barang.
- Upload foto proses pekerjaan.
- Upload bukti pelaksanaan.
- Download seluruh foto.
- Download laporan PDF.
- Download seluruh bukti dalam ZIP.

## 19. Pencarian Unit pada PM

Ketika pengguna mencari unit:

1. Unit hasil pencarian harus muncul paling atas.
2. Baris unit diberi highlight.
3. Sistem otomatis scroll ke hasil.
4. Filter project tetap aktif.
5. Tersedia tombol untuk membuka detail unit.
6. Tersedia tombol untuk membuka histori PM.
7. Tersedia tombol untuk membuat rencana PM.

---

# BAGIAN G — SPARE PART DAN LOGISTIK

## 20. Tab Logistik

Menu Spare Part & Logistik diarahkan memiliki tab:

1. Barang Masuk.
2. Barang Keluar.
3. Saldo Stok.
4. Permintaan Barang / SPB.
5. Oli & Lubricant.
6. Barang Bekas.
7. Transfer Antar-Project.
8. Dokumen Logistik.

## 21. Penyempurnaan SPB

Data SPB:

| Field | Keterangan |
|---|---|
| Nomor SPB | Nomor otomatis |
| Nomor SPK | Referensi SPK |
| Nomor WO | Referensi Work Order |
| Unit ID | Unit penerima |
| Nomor polisi / SN | Identitas unit |
| Project | Project unit |
| Pemohon | Mekanik atau planner |
| Gudang | Gudang asal |
| Tanggal | Tanggal permintaan |
| Daftar barang | Barang yang diminta |
| Status approval | Status persetujuan |
| Status pengeluaran | Belum keluar, sebagian, selesai |
| Bukti foto | Bukti barang |

Nomor polisi tidak dijadikan nomor SPB. Nomor polisi hanya menjadi salah satu identitas unit.

## 22. Oli & Lubricant

Field yang dibutuhkan:

- Tanggal transaksi.
- Project.
- Gudang.
- Jenis oli.
- Merek.
- Spesifikasi SAE atau viskositas.
- Satuan.
- Penerimaan.
- Pemakaian.
- Saldo.
- Unit penerima.
- HM/KM saat pengisian.
- Nomor SPK.
- Mekanik.
- Jumlah oli bekas.
- Foto bukti.
- Catatan.

## 23. Upload Bukti Logistik

Sistem harus menyediakan:

- Upload foto barang masuk.
- Upload foto barang keluar.
- Upload foto barang bekas.
- Upload foto barang baru.
- Upload bukti penerimaan.
- Upload laporan PDF admin logistik.
- Download bukti.
- Preview file.
- Penamaan file otomatis.
- Relasi file dengan SPB, SPK, unit, dan project.

## 24. Stok per Project

Setiap project memiliki:

- Gudang atau lokasi penyimpanan.
- Saldo stok masing-masing.
- Barang masuk.
- Barang keluar.
- Barang dalam proses transfer.
- Minimum stock.
- Reorder point.
- PIC logistik.

---

# BAGIAN H — TIRE DAN ACCU

## 25. Tire pada SPK

Field Tire:

| Field | Keterangan |
|---|---|
| Posisi ban | FL, FR, R1L, R1R, dan seterusnya |
| Merek | Merek ban |
| Ukuran | Ukuran ban |
| Nomor seri | Identitas ban |
| Tread depth | Kedalaman alur |
| Tekanan | Tekanan angin |
| Kondisi awal | Kondisi sebelum pengerjaan |
| Tindakan | Rotasi, repair, replacement |
| Kondisi akhir | Kondisi setelah pengerjaan |
| Status ban | Lama atau baru |
| Nomor stok | Referensi gudang |
| Foto sebelum | Bukti awal |
| Foto sesudah | Bukti akhir |
| Alasan penggantian | Penjelasan teknis |

## 26. Accu pada SPK

Field Accu:

| Field | Keterangan |
|---|---|
| Merek | Merek accu |
| Tipe | Tipe accu |
| Nomor seri | Identitas accu |
| Tegangan | Voltase |
| Kapasitas | Ah |
| Tanggal pemasangan | Tanggal instalasi |
| Kondisi terminal | Baik, korosi, longgar |
| Hasil tes | Tegangan atau CCA |
| Status accu | Lama atau baru |
| Nomor stok | Referensi gudang |
| Foto sebelum | Bukti awal |
| Foto sesudah | Bukti akhir |
| Catatan | Catatan teknis |

## 27. Aturan Integrasi Tire dan Accu

- Data Tire dan Accu terhubung dengan unit.
- Data terhubung dengan nomor SPK.
- Penggantian wajib mengurangi stok.
- Barang lama wajib dicatat.
- Riwayat pemasangan tersimpan.
- Sistem dapat menampilkan umur pemakaian.
- Sistem dapat memberi notifikasi inspeksi atau penggantian.

---

# BAGIAN I — TEST DRIVE DAN QUALITY CONTROL

## 28. Tahap Pengujian

Jenis pengujian:

- Test drive.
- Idle test.
- Load test.
- Hydraulic test.
- Electrical test.
- Brake test.
- Steering test.
- Leak inspection.
- Tire inspection.
- Battery/Accu test.

## 29. Form Quality Control

- Nomor SPK.
- Unit.
- Jenis pengujian.
- Hasil pengujian.
- Temuan.
- Tindakan tambahan.
- Status lulus atau tidak lulus.
- Nama pemeriksa.
- Tanggal dan waktu.
- Foto atau video.
- Tanda tangan digital.

## 30. Aturan Status Ready

Unit hanya dapat menjadi `READY` apabila:

- Tidak ada SPK aktif.
- QC dinyatakan lulus.
- Tidak ada temuan kritis.
- Dokumen penyelesaian lengkap.
- Kepala mekanik sudah menyetujui.
- Admin workshop sudah memvalidasi.

---

# BAGIAN J — MAINTENANCE HISTORY

## 31. Data Riwayat Perawatan

Setiap unit memiliki histori:

- Work Order.
- SPK.
- SPB.
- Preventive Maintenance.
- Corrective Maintenance.
- Breakdown.
- Inspeksi dan P2H.
- Tire.
- Accu.
- Sparepart.
- Oli.
- Biaya.
- Downtime.
- Foto.
- PDF laporan.
- Approval.

## 32. Tampilan Riwayat

Riwayat dapat ditampilkan dalam:

- Timeline.
- Tabel.
- Kalender.
- Rekap biaya.
- Rekap downtime.
- Rekap sparepart.
- Rekap Tire dan Accu.

---

# BAGIAN K — ARSIP DAN DOKUMEN

## 33. Jenis Dokumen

- Work Order.
- SPK.
- SPB.
- Laporan kerusakan.
- Laporan inspeksi.
- Laporan PM.
- Laporan logistik.
- Laporan QC.
- Bukti foto.
- Bukti barang.
- Dokumen approval.

## 34. Standar Penyimpanan File

Struktur penamaan:

```text
[PROJECT]_[UNIT]_[JENIS-DOKUMEN]_[NOMOR]_[TANGGAL]
```

Contoh:

```text
RWI_DT-054_SPK_SPK-2026-001_2026-08-01.pdf
```

Folder logis:

```text
Project
└── Unit
    ├── Work Order
    ├── SPK
    ├── SPB
    ├── Preventive Maintenance
    ├── Foto
    ├── Tire
    ├── Accu
    └── Approval
```

---

# BAGIAN L — BACKEND DAN DATA

## 35. Kondisi Prototype

Versi saat ini masih menggunakan data awal berbasis file dan penyimpanan lokal browser. Pendekatan ini sesuai untuk prototype, tetapi belum cukup untuk penggunaan multi-user.

## 36. Kebutuhan Backend

Backend minimal harus mendukung:

- Database terpusat.
- Login dan autentikasi.
- Role-based access control.
- API untuk seluruh modul.
- Upload file.
- Penyimpanan file terpusat.
- Audit trail.
- Versioning dokumen.
- Backup.
- Restore data.
- Notifikasi.
- Log aktivitas.

## 37. Entitas Data Utama

```text
users
roles
permissions
projects
assets
asset_movements
work_orders
work_order_inspections
approvals
spk
spk_tasks
spk_progress
spk_mechanics
spk_parts
spb
inventory_items
inventory_transactions
lubricant_transactions
tire_records
battery_records
pm_schedules
pm_realizations
notifications
quality_controls
attachments
maintenance_history
audit_logs
```

---

# BAGIAN M — HAK AKSES

## 38. Hak Akses per Peran

| Peran | Hak utama |
|---|---|
| Administrator | Pengaturan penuh sistem |
| Equipment Manager | Approval, monitoring, dan keputusan akhir |
| Maintenance Planner | Jadwal PM, estimasi, dan kebutuhan sparepart |
| Kepala Mekanik | Diagnosis, pembagian pekerjaan, dan QC |
| Mekanik | Pelaksanaan dan laporan hasil kerja |
| Operator / Driver | Laporan kerusakan dan monitoring status |
| Logistik | SPB, stok, barang masuk dan keluar |
| HSE | Inspeksi keselamatan dan insiden |
| Finance | Monitoring biaya dan pembayaran |
| Viewer / Auditor | Akses baca dan audit trail |

## 39. Prinsip Hak Akses

- Pengguna hanya melihat project yang diizinkan.
- Mekanik hanya dapat mengubah SPK yang ditugaskan.
- Approval hanya dapat dilakukan oleh approver yang berwenang.
- Data yang sudah ditutup tidak dapat dihapus sembarangan.
- Perubahan kritis harus tercatat pada audit trail.

---

# BAGIAN N — ROADMAP PENGEMBANGAN

## 40. Urutan Sprint

### Sprint 1 — Finalisasi Istilah dan Data Model

Target:

- Menetapkan Work Order, SPK, SPB, Tire, Accu, dan Project.
- Mengubah label JO menjadi SPK.
- Menyiapkan skema data.
- Menentukan format nomor dokumen.

Output:

- Dokumen data model.
- Daftar field.
- Flow final.
- Prototype struktur menu.

### Sprint 2 — Work Order dan Inspeksi

Target:

- Membuat form laporan kerusakan.
- Menambahkan foto awal.
- Menambahkan inspeksi kepala mekanik.
- Menambahkan diagnosis dan estimasi.

Output:

- Work Order dapat dibuat.
- Work Order dapat diperiksa.
- Estimasi dapat diajukan.

### Sprint 3 — Approval dan Penerbitan SPK

Target:

- Membuat approval online.
- Menghasilkan SPK dari WO.
- Menetapkan mekanik.
- Menetapkan target waktu.

Output:

- WO yang disetujui menghasilkan SPK.
- SPK memiliki nomor resmi.

### Sprint 4 — Eksekusi SPK dan QC

Target:

- Pencatatan progres.
- Pencatatan tindakan.
- Pencatatan sparepart.
- Upload bukti.
- Test drive.
- Quality control.

Output:

- Mekanik dapat menyelesaikan laporan kerja.
- Kepala mekanik dapat melakukan QC.

### Sprint 5 — Preventive Maintenance

Target:

- Notifikasi due soon dan overdue.
- Email dan WhatsApp.
- Upload PDF planner.
- Upload bukti realisasi.
- Pencarian unit.

Output:

- PM dapat dimonitor berdasarkan HM atau tanggal.

### Sprint 6 — Logistik dan Oli

Target:

- Penyempurnaan SPB.
- Buku oli.
- Upload bukti barang.
- Stok per project.
- Transfer stok.

Output:

- Pemakaian barang dapat dilacak sampai SPK dan unit.

### Sprint 7 — Tire dan Accu

Target:

- Form Tire.
- Form Accu.
- Histori pemasangan.
- Integrasi stok.
- Notifikasi kondisi.

Output:

- Tire dan Accu tercatat sebagai bagian maintenance history.

### Sprint 8 — Backend dan Integrasi

Target:

- Database.
- API.
- Multi-user.
- File storage.
- Audit trail.
- Backup.

Output:

- Sistem siap digunakan bersama.

### Sprint 9 — UAT dan Go-Live

Target:

- Pengujian admin workshop.
- Pengujian mekanik.
- Pengujian planner.
- Pengujian logistik.
- Pengujian manager.
- Perbaikan temuan.

Output:

- Sistem siap implementasi operasional.

---

# BAGIAN O — MONITORING PROGRES

## 41. Standar Persentase Progres

| Progres | Kriteria |
|---:|---|
| 0% | Belum ada rancangan atau kode |
| 25% | Tampilan atau form tersedia |
| 50% | Data dapat dibuat, dibaca, diubah, dan divalidasi |
| 75% | Sudah terintegrasi dengan modul lain dan approval |
| 90% | Sudah menggunakan database, file storage, dan hak akses |
| 100% | Sudah lulus UAT dan digunakan operasional |

## 42. Baseline Progres Awal

| Modul | Kondisi awal | Progres baseline |
|---|---|---:|
| Work Order | Sudah ada form dan Kanban, masih bercampur dengan SPK | 45% |
| SPK Mekanik | Belum ada entitas terpisah | 0% |
| Approval | Sudah ada tampilan prototype | 25% |
| Preventive Maintenance | Struktur modul tersedia, perlu audit file eksternal | 25% |
| Notifikasi PM | Parameter warning tersedia, pengiriman belum ada | 20% |
| Master Project | Struktur dasar tersedia, project belum lengkap | 25% |
| Spare Part & Logistik | Barang masuk, keluar, dan stok tersedia | 50% |
| Oli & Lubricant | Belum tersedia | 0% |
| Upload dokumen dan foto | Belum lengkap | 10% |
| Tire | Inspeksi dasar tersedia, belum masuk SPK | 30% |
| Accu | Belum menjadi fitur terstruktur | 10% |
| Quality Control | Belum menjadi tahap wajib | 0% |
| Maintenance History | Sebagian riwayat unit tersedia | 30% |
| Backend multi-user | Belum tersedia | 0% |

## 43. Format Laporan Progres

```text
Nama Fitur:
PIC:
Tanggal Mulai:
Target Selesai:
Progress Saat Ini:
Status:

Selesai:
- ...
- ...

Sedang Dikerjakan:
- ...
- ...

Belum Dikerjakan:
- ...
- ...

Kendala:
- ...

Rencana Berikutnya:
- ...
```

## 44. Contoh Monitoring

```text
Nama Fitur: Penerbitan SPK
PIC: Tim Developer
Progress Saat Ini: 25%
Status: In Progress

Selesai:
- Struktur form SPK
- Format penomoran SPK

Sedang Dikerjakan:
- Integrasi WO ke SPK
- Pemilihan mekanik

Belum Dikerjakan:
- Approval
- Relasi SPB
- Upload foto
- Tire dan Accu
- Test drive
- Tanda tangan
- Maintenance history

Kendala:
- Format SPK operasional final belum diterima

Rencana Berikutnya:
- Finalisasi template SPK
- Implementasi approval
```

---

# BAGIAN P — KRITERIA PENERIMAAN

## 45. Kriteria Work Order

- Pengguna dapat membuat Work Order.
- Work Order memiliki nomor unik.
- Work Order dapat difilter berdasarkan project dan unit.
- Foto awal dapat diunggah.
- Kepala mekanik dapat mengisi diagnosis.
- Estimasi dapat diajukan untuk approval.

## 46. Kriteria SPK

- SPK hanya dapat dibuat dari WO yang memenuhi syarat.
- SPK memiliki nomor unik.
- Mekanik dapat ditugaskan.
- Sparepart dapat dihubungkan.
- Bukti kerja dapat diunggah.
- Tire dan Accu dapat dicatat.
- QC wajib dilakukan.
- SPK yang ditutup masuk maintenance history.

## 47. Kriteria Approval

- Approval mengubah status data secara permanen.
- Approver tercatat.
- Waktu approval tercatat.
- Penolakan wajib memiliki alasan.
- Audit trail dapat ditampilkan.

## 48. Kriteria PM

- Sistem dapat mendeteksi due soon dan overdue.
- Sistem dapat mengirim notifikasi.
- Dokumen PDF dapat diunggah.
- Foto realisasi dapat diunduh.
- Unit hasil pencarian tampil paling atas.

## 49. Kriteria Logistik

- SPB terhubung dengan SPK.
- Stok berkurang saat barang keluar.
- Barang bekas tercatat.
- Oli masuk dan keluar tercatat.
- Bukti foto dan PDF dapat diunggah.
- Stok dapat dipisahkan per project.

---

# BAGIAN Q — RISIKO DAN CATATAN

## 50. Risiko Pengembangan

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Istilah WO dan SPK belum disepakati | Data dan alur membingungkan | Finalisasi istilah sebelum coding |
| Format SPK belum final | Revisi berulang | Gunakan template resmi workshop |
| Project belum lengkap | Filter data tidak akurat | Bentuk Master Project |
| Data hanya tersimpan lokal | Data tidak sinkron | Implementasikan backend |
| Approval hanya visual | Tidak dapat diaudit | Simpan approval ke database |
| Upload file tanpa aturan | Dokumen sulit dicari | Gunakan standar penamaan |
| Status Ready terlalu cepat | Unit belum benar-benar aman | Wajibkan QC |
| Stok tidak terhubung SPK | Pemakaian barang tidak terlacak | Relasikan SPB-SPK-unit |

---

# BAGIAN R — REKOMENDASI IMPLEMENTASI TERDEKAT

## 51. Fokus Pengembangan Berikutnya

Urutan paling disarankan:

1. Finalisasi perbedaan Work Order dan SPK.
2. Ubah JO menjadi SPK pada seluruh tampilan dan data.
3. Buat tab Work Order dan SPK terpisah.
4. Tambahkan inspeksi dan estimasi.
5. Aktifkan approval online.
6. Buat penerbitan SPK otomatis dari WO.
7. Tambahkan form pelaksanaan dan penyelesaian SPK.
8. Tambahkan QC sebelum Ready.
9. Tambahkan project RWI, Matting Board, dan Lahat.
10. Tambahkan notifikasi PM.
11. Tambahkan upload PDF dan foto.
12. Tambahkan buku oli.
13. Hubungkan SPB dengan SPK.
14. Tambahkan Tire dan Accu.
15. Pindahkan penyimpanan dari browser ke backend/database.

---

# BAGIAN S — KESIMPULAN

Arah pengembangan FleetMonitor berikutnya harus berfokus pada pembentukan alur operasional workshop yang jelas dan dapat diaudit.

Struktur utamanya adalah:

```text
Laporan Kerusakan / Work Order
        ↓
Inspeksi dan Estimasi
        ↓
Approval
        ↓
SPK Mekanik
        ↓
SPB dan Pengeluaran Sparepart
        ↓
Pelaksanaan Perbaikan
        ↓
Tire / Accu / Bukti Kerja
        ↓
Test Drive dan Quality Control
        ↓
Validasi Admin Workshop
        ↓
Ready for Use
        ↓
Maintenance History
```

Dengan struktur tersebut, sistem tidak hanya menjadi dashboard visual, tetapi menjadi sistem operasional workshop yang mendukung pengendalian pekerjaan, waktu, sparepart, biaya, dokumen, dan tanggung jawab setiap pengguna.
