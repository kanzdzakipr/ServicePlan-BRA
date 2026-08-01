# Audit Kesesuaian dan Rancangan Pengembangan Revisi FleetMonitor V2

## 1. Identitas Dokumen

| Informasi | Keterangan |
|---|---|
| Nama dokumen | Audit Kesesuaian dan Rancangan Pengembangan Revisi FleetMonitor V2 |
| Sistem | Equipment Maintenance & Fleet Monitoring System — FleetMonitor |
| Tanggal audit | 1 Agustus 2026 |
| Versi rancangan | V2 — re-baseline setelah pemeriksaan implementasi terbaru |
| File yang diperiksa | `dashboard(2).html` dan `dashboard.js` |
| Acuan pembanding | `rancangan_pengembangan_fleetmonitor.md` versi sebelumnya |
| Metode audit | Pemeriksaan statis struktur HTML, fungsi JavaScript, alur data, istilah dokumen, status, penyimpanan, integrasi modul, dan kelengkapan fitur |

### 1.1 Batasan audit

Audit ini hanya menilai file yang tersedia. Beberapa dependensi yang direferensikan oleh halaman tetapi tidak ikut diperiksa adalah:

- `data.json`
- `scripts/dashboard.css`
- `scripts/logistics_data.js`
- `scripts/report-xlsx-template.js`
- file aset dan vendor library
- backend, database, API, file storage, email gateway, dan WhatsApp gateway

Karena itu, nilai progres merupakan **baseline hasil audit kode statis**, bukan hasil UAT penuh di lingkungan produksi.

---

# BAGIAN A — KESIMPULAN AUDIT

## 2. Jawaban Utama

Implementasi terbaru **sudah mengikuti sebagian arah rancangan**, terutama pada:

- Work Order dipertahankan sebagai tiket kerusakan.
- Tampilan Work Order diperbaiki menjadi Kanban dan tabel.
- Tersedia pencarian serta filter WO.
- Tersedia filter project/lokasi berbasis Master Asset.
- Preventive Maintenance memiliki tracker `OVERDUE`, `DUE SOON`, `DUE`, dan realisasi.
- Spare Part & Logistik sudah terhubung dengan unit, WO, SPB, approval, pengadaan, dan RTW.
- Tab `Oli & Lubricant` dan `Barang Bekas` sudah ditambahkan.
- Condition Monitoring sudah memiliki form Tire/Ban dan Accu/Aki.
- Laporan & Form sudah mendukung impor dokumen dan lampiran gambar.

Namun implementasi **belum mengikuti rancangan inti secara utuh**, karena alur utama berikut belum tersedia:

```text
Work Order
→ Inspeksi dan Estimasi
→ Approval
→ Penerbitan SPK Mekanik
→ Eksekusi SPK
→ Pengambilan Sparepart berdasarkan SPK
→ Bukti Pekerjaan
→ Test Drive / Functional Test / QC
→ Validasi Admin Workshop
→ Ready for Use
→ Maintenance History
```

Kesenjangan paling besar adalah:

1. Belum ada entitas dan modul **SPK Mekanik**.
2. Istilah dan data **JO** masih tersebar di banyak bagian.
3. Status WO masih hanya `Open`, `In Progress`, dan `Closed`.
4. Penutupan WO masih dapat langsung menyarankan status unit `READY` tanpa QC wajib.
5. Approval masih berupa simulasi antarmuka dan belum membentuk audit trail.
6. Notifikasi PM melalui email/WhatsApp belum tersedia.
7. Upload PDF dan foto belum tertanam langsung pada workflow PM, SPK, dan logistik.
8. Project masih dibentuk dari teks lokasi aset, belum menjadi master project resmi.
9. Data masih mengandalkan `data.json` dan `localStorage` browser.
10. Terdapat sisa **Git merge conflict marker** pada `dashboard.js`.

## 3. Skor Baseline

| Dimensi | Estimasi progres | Penjelasan |
|---|---:|---|
| Kesesuaian arah fitur | 55% | Banyak modul dan konsep sudah mulai diarahkan sesuai kebutuhan |
| Alur workshop end-to-end | 30% | WO tersedia, tetapi SPK, QC, validasi, dan closing chain belum tersedia |
| Kelengkapan PM | 60% | Tracker dan realisasi cukup baik, tetapi notifikasi eksternal dan dokumen bukti belum tersedia |
| Kelengkapan logistik | 55% | SPB, stok, oli, barang bekas, dan linkage sudah ada, tetapi belum terhubung SPK dan dokumen bukti |
| Tire dan Accu | 65% | Form inspeksi cukup lengkap, tetapi masih berdiri di Condition Monitoring |
| Kesiapan produksi multiuser | 20% | Penyimpanan lokal, approval simulasi, dan belum ada backend terpusat |
| **Progres keseluruhan terhadap rancangan sebelumnya** | **±40%** | Estimasi berbobot dari seluruh komponen utama |

> Persentase di atas bukan ukuran jumlah baris kode. Nilainya mengukur seberapa jauh proses bisnis yang direncanakan sudah dapat digunakan secara utuh dan dapat diaudit.

---

# BAGIAN B — HASIL PEMERIKSAAN PER RENCANA

## 4. Matriks Kesesuaian

| No. | Rencana sebelumnya | Kondisi implementasi terbaru | Status | Progres |
|---:|---|---|---|---:|
| 1 | WO tetap menjadi permintaan operator/driver | Konsep WO sebagai tiket kerusakan sudah ditulis dan workspace WO diperbaiki | Sesuai sebagian | 80% |
| 2 | JO diarahkan menjadi SPK Mekanik | JO masih menjadi field, label, referensi laporan, dan istilah KPI | Tidak sesuai | 10% |
| 3 | Menu dipisah menjadi Work Order dan SPK | Hanya tersedia Manajemen Work Order | Belum | 20% |
| 4 | WO memiliki tahap inspeksi dan estimasi | Ada P2H/Condition Monitoring, tetapi belum menjadi tahap formal dalam satu WO | Belum utuh | 20% |
| 5 | Approval online dan auditable | Approval Inbox ada, tetapi tombol hanya menghapus baris dari tabel | Prototype | 15% |
| 6 | WO yang disetujui menerbitkan SPK | Tidak ada generator nomor maupun record SPK | Belum | 0% |
| 7 | Eksekusi pekerjaan menggunakan SPK | Mekanik dan tindakan masih dicatat langsung pada WO | Tidak sesuai | 30% |
| 8 | SPB wajib tertaut ke SPK dan WO | SPB tertaut ke unit dan WO, tetapi belum ke SPK | Sesuai sebagian | 55% |
| 9 | Penutupan wajib melalui test drive dan QC | Tidak ditemukan tahap test drive/QC wajib | Belum | 0% |
| 10 | Unit Ready hanya setelah QC dan validasi | WO `Closed` masih menyarankan unit `READY` | Tidak sesuai | 20% |
| 11 | Master Project berisi RWI, Matting Board, Lahat, dan project lain | Filter project/lokasi dinamis sudah ada, tetapi bukan master resmi dan data wajib belum lengkap | Sesuai sebagian | 45% |
| 12 | PM memiliki overdue/due soon | Sudah tersedia tracker status otomatis | Sesuai | 80% |
| 13 | PM memiliki pencarian unit | Sudah tersedia pencarian, filter status, dan kategori | Sesuai | 75% |
| 14 | Notifikasi PM via dashboard, email, WhatsApp | Dashboard status ada; email dan WhatsApp belum tersedia | Sesuai sebagian | 30% |
| 15 | Upload laporan PM PDF dan bukti foto | Upload umum tersedia di Laporan & Form, tetapi belum menjadi bagian record PM | Belum utuh | 20% |
| 16 | Buku Oli & Lubricant | Tab dan ledger read-only sudah ada | Sesuai sebagian | 50% |
| 17 | Foto barang masuk/keluar/bekas/baru dan laporan logistik PDF | Belum tersedia pada workflow logistik | Belum | 10% |
| 18 | Tire menjadi bagian SPK | Form inspeksi Tire/Ban sudah ada di Condition Monitoring, belum terhubung SPK | Sesuai sebagian | 65% |
| 19 | Accu menjadi bagian SPK | Form inspeksi Accu/Aki sudah ada di Condition Monitoring, belum terhubung SPK | Sesuai sebagian | 60% |
| 20 | Maintenance History lengkap per unit | Ada history modul tertentu, belum membentuk rantai WO–SPK–SPB–QC–biaya | Belum utuh | 30% |
| 21 | Backend dan database multiuser | Data utama masih dibaca dari JSON dan perubahan disimpan di browser | Belum | 10% |
| 22 | RBAC benar-benar membatasi tindakan | Matriks RBAC tersedia sebagai UI, enforcement belum terlihat | Prototype | 30% |

---

# BAGIAN C — TEMUAN POSITIF

## 5. Work Order Lebih Baik

Implementasi terbaru sudah menambahkan:

- mode tampilan Kanban;
- mode tampilan tabel;
- pencarian nomor WO, unit, mekanik, atau keluhan;
- filter status;
- filter prioritas;
- kolom project/lokasi;
- kolom mekanik;
- kolom downtime;
- detail unit dan linkage ke SPB.

Ini merupakan peningkatan yang benar untuk **tiket kerusakan**, tetapi belum boleh dianggap sebagai implementasi SPK.

## 6. Project Filter Sudah Mulai Terintegrasi

Sistem sudah membentuk daftar project/lokasi dari lokasi yang terdapat pada Master Asset dan menerapkannya ke:

- Dashboard;
- Monitoring Unit;
- Master Asset;
- Work Order;
- refresh Spare Part & Logistik.

Kelebihan pendekatan ini adalah sistem tidak mengarang project fiktif. Kekurangannya, lokasi operasional dan project kontrak masih dianggap sebagai satu data yang sama.

## 7. Preventive Maintenance Sudah Memiliki Fondasi Kuat

Modul PM sudah memiliki:

- forecast HM/KM;
- service terakhir;
- target berikutnya;
- status `COMPLETED`;
- status `OVERDUE`;
- status `DUE SOON`;
- status `DUE`;
- pencarian unit;
- filter status;
- filter kategori;
- kalender realisasi;
- kitting dan validasi part;
- form realisasi PM;
- nomor WO;
- PIC mekanik;
- kesiapan filter;
- kesiapan oli dan grease;
- export tracker CSV.

Fondasi ini dapat dipertahankan. Perbaikan berikutnya harus berfokus pada dokumen bukti, notifikasi, project, approval, dan backend.

## 8. Spare Part & Logistik Sudah Lebih Terhubung

Linkage yang sudah terlihat:

```text
Master Asset
→ P2H / Condition Monitoring
→ Work Order
→ SPB
→ Approval
→ Pengadaan
→ Kesiapan RTW
```

Tersedia pula:

- Barang Masuk;
- Barang Keluar;
- Saldo Stok;
- Oli & Lubricant;
- Barang Bekas;
- draft SPB per unit;
- prioritas SPB;
- dampak terhadap RTW;
- pencarian transaksi;
- riwayat pemakaian warehouse.

Linkage ini sudah baik, tetapi setelah SPK tersedia alurnya harus diperbarui menjadi:

```text
WO → Approval → SPK → SPB → Part Issue → Eksekusi → QC → RTW
```

## 9. Tire dan Accu Sudah Memiliki Form Operasional

### 9.1 Tire/Ban

Sudah tersedia:

- posisi ban;
- tread depth;
- tekanan angin;
- kondisi fisik;
- pola keausan;
- rekomendasi tindakan;
- threshold kritis;
- penyimpanan hasil inspeksi;
- history condition monitoring.

### 9.2 Accu/Aki

Sudah tersedia:

- tegangan;
- CCA;
- merek;
- tipe/kapasitas;
- tanggal pemasangan;
- kondisi terminal;
- level elektrolit;
- kondisi casing;
- catatan;
- penyimpanan hasil inspeksi.

Data tersebut dapat digunakan ulang pada SPK tanpa membuat form dari nol.

## 10. Laporan & Form Sudah Memiliki Mesin Impor

Modul Laporan & Form sudah mendukung:

- Markdown;
- Word;
- PDF;
- Excel;
- CSV/TSV;
- gambar;
- pemilihan folder;
- OCR;
- lampiran foto;
- keterangan foto;
- validasi bukti;
- preview dan cetak.

Kekurangannya, engine ini masih menjadi modul umum. Dokumen yang diimpor belum otomatis menjadi lampiran record PM, SPK, SPB, atau QC.

---

# BAGIAN D — KETIDAKSESUAIAN UTAMA

## 11. SPK Mekanik Belum Ada

### 11.1 Masalah

Di implementasi terbaru hanya terdapat satu penjelasan bahwa JO lama tidak otomatis diubah menjadi SPK. Namun belum tersedia:

- menu/tab SPK;
- nomor SPK;
- record SPK;
- penerbit SPK;
- tanggal penerbitan;
- mekanik penerima;
- daftar pekerjaan;
- estimasi waktu;
- estimasi biaya;
- daftar part disetujui;
- waktu mulai dan selesai;
- status SPK;
- hasil pekerjaan;
- tanda tangan atau approval elektronik;
- penutupan SPK.

### 11.2 Koreksi rancangan

JO lama **tidak boleh diubah secara buta**. Migrasi harus dikendalikan:

| Field | Aturan baru |
|---|---|
| `legacyJoNo` | Menyimpan nomor JO lama apa adanya |
| `spkId` | Nomor SPK baru setelah record diverifikasi |
| `migrationStatus` | `UNMAPPED`, `REVIEWED`, `MIGRATED`, atau `REJECTED` |
| `migrationNote` | Alasan pemetaan atau penolakan |
| `sourceDocument` | Referensi dokumen sumber JO |

Dengan demikian, data lama tetap terlacak tetapi istilah operasional baru menggunakan SPK.

## 12. Status Work Order Masih Terlalu Pendek

Status saat ini:

```text
Open → In Progress → Closed
```

Status target:

```text
DILAPORKAN
→ MENUNGGU_REGISTRASI
→ MENUNGGU_INSPEKSI
→ MENUNGGU_ESTIMASI
→ MENUNGGU_APPROVAL
→ DISETUJUI
→ DITOLAK
→ SPK_DITERBITKAN
→ DALAM_PENGERJAAN
→ SELESAI
```

Status pengerjaan mekanik tidak seharusnya ditaruh seluruhnya pada WO. Setelah `SPK_DITERBITKAN`, progres detail dilanjutkan pada SPK.

## 13. Belum Ada Inspeksi dan Estimasi Formal pada WO

P2H dan Condition Monitoring sudah ada, tetapi belum tersedia satu record **WO Inspection & Estimate** yang berisi:

- diagnosis;
- root cause sementara;
- komponen terdampak;
- tindakan yang diusulkan;
- estimasi durasi;
- estimasi downtime;
- estimasi biaya;
- daftar part;
- kebutuhan vendor;
- tingkat urgensi;
- rekomendasi approval;
- nama kepala mekanik pemeriksa;
- tanggal pemeriksaan.

Tanpa record ini, approval tidak mempunyai dasar keputusan yang terstruktur.

## 14. Approval Belum Menjadi Transaksi

Fungsi approval saat ini hanya:

- menghapus baris dari tabel;
- menampilkan alert disetujui;
- meminta alasan penolakan;
- menghapus baris setelah ditolak.

Data berikut belum tersimpan:

- siapa yang menyetujui;
- kapan persetujuan diberikan;
- level approval;
- nominal yang disetujui;
- data sebelum dan sesudah revisi;
- komentar approver;
- SLA approval;
- histori approval;
- signature/hash;
- akibat persetujuan terhadap WO/SPK/SPB.

## 15. Unit Dapat Menjadi Ready Terlalu Cepat

Saat WO dipindahkan ke `Closed`, sistem menyarankan status unit `READY`. Ini belum sesuai karena belum ada quality gate berikut:

- seluruh task SPK selesai;
- part issue sudah direkonsiliasi;
- foto sebelum dan sesudah tersedia;
- time log lengkap;
- test function selesai;
- test drive selesai jika relevan;
- tidak ada temuan QC kritis;
- kepala mekanik menyetujui;
- admin workshop memvalidasi;
- dokumen closing lengkap.

Status `READY` harus menjadi hasil akhir QC, bukan efek langsung dari menutup WO.

## 16. Project Belum Menjadi Master Resmi

Project saat ini diturunkan dari teks `asset.location`. Ini membantu filter, tetapi belum cukup karena project harus memiliki identitas sendiri.

Data project target:

- Project ID;
- nama project;
- kode project;
- lokasi/site;
- client;
- tanggal mulai;
- tanggal selesai;
- status aktif;
- PIC project;
- equipment manager;
- workshop/gudang penanggung jawab;
- koordinat;
- daftar unit;
- daftar user;
- SLA;
- budget center.

Project wajib yang perlu disiapkan sebagai data master:

- RWI;
- Matting Board;
- Lahat;
- Sunter Area Stadium;
- Site Alpha;
- Yard / Workshop;
- project lain berdasarkan data tervalidasi.

## 17. PM Belum Memiliki Notifikasi Eksternal

Tracker PM sudah menampilkan due status, tetapi belum ditemukan implementasi:

- email;
- WhatsApp;
- recipient list;
- message template;
- send log;
- retry log;
- read/acknowledgement;
- escalation.

Notifikasi target:

| Status | Trigger | Penerima |
|---|---|---|
| Due Soon | ≤50 HM atau ≤7 hari | Planner dan admin workshop |
| Priority | ≤20 HM atau ≤3 hari | Planner, kepala mekanik, project |
| Overdue | Meter/tanggal melewati target | Planner, kepala mekanik, equipment manager |
| Critical Overdue | Melewati toleransi kritis | Equipment manager dan manajemen |

## 18. Upload Bukti Belum Terikat ke Workflow

Laporan & Form memiliki upload yang cukup baik, tetapi bukti tersebut belum memiliki relasi langsung dengan:

- `pmExecutionId`;
- `woId`;
- `spkId`;
- `spbId`;
- `partIssueId`;
- `qcId`;
- `assetId`;
- `projectId`.

Setiap file harus disimpan sebagai attachment dengan metadata dan relasi dokumen, bukan hanya disimpan dalam draft browser.

## 19. Oli & Lubricant Masih Read-Only

Tab oli sudah tersedia, tetapi masih berupa ledger pembacaan data. Belum tersedia transaksi operasional lengkap:

- input barang masuk;
- input pemakaian;
- saldo sebelum dan sesudah;
- project;
- gudang;
- HM/KM;
- nomor SPK;
- mekanik;
- status oli bekas;
- bukti foto;
- approval koreksi;
- transfer antar-project.

## 20. Tire dan Accu Belum Menjadi Bagian SPK

Form Tire dan Accu cukup baik, tetapi belum mencatat:

- nomor SPK;
- nomor task SPK;
- kondisi sebelum dan sesudah;
- komponen lama dan baru;
- nomor seri komponen;
- nomor stok/part issue;
- foto sebelum dan sesudah;
- alasan penggantian;
- mekanik pelaksana;
- QC result;
- biaya aktual.

## 21. Backend dan Audit Trail Belum Tersedia

Data masih dibaca dari `data.json`, sedangkan perubahan penting disimpan menggunakan `localStorage` atau `sessionStorage`.

Risikonya:

- data berbeda antar-browser;
- data hilang ketika storage dibersihkan;
- tidak aman untuk banyak user;
- tidak ada locking/concurrency;
- tidak ada audit log server;
- tidak ada approval yang dapat dipertanggungjawabkan;
- file lampiran tersimpan di perangkat lokal;
- status unit dapat berbeda antar-user.

## 22. Temuan Teknis Kritis: Merge Conflict Marker

Pada `dashboard.js` masih terdapat teks:

```text
<<<<<<< HEAD
=======
>>>>>>> 33a5c879b0b12838945e5282b1259e314bb1d0e6
```

Marker tersebut berada di dalam template tabel Barang Keluar. JavaScript tetap dapat lolos pemeriksaan sintaks karena marker berada di dalam template string, tetapi teks konflik dapat ikut dirender ke halaman dan menunjukkan proses merge belum selesai.

Perbaikan wajib:

1. Pilih satu implementasi ID unit.
2. Hapus seluruh marker Git.
3. Jalankan lint dan test ulang.
4. Pastikan `formatUnitId()` digunakan konsisten.

## 23. Arsitektur Frontend Terlalu Monolitik

Kondisi saat ini:

- `dashboard(2).html` memuat ribuan baris HTML dan satu blok JavaScript inline besar;
- `dashboard.js` memuat banyak modul dalam satu file;
- banyak fungsi global pada `window`;
- banyak data seed dan tampilan bercampur;
- beberapa fungsi masih berupa `alert()` simulasi.

Target pemisahan:

```text
src/
├── core/
│   ├── api-client.js
│   ├── auth.js
│   ├── event-bus.js
│   ├── storage.js
│   └── validators.js
├── modules/
│   ├── assets/
│   ├── work-orders/
│   ├── inspections/
│   ├── approvals/
│   ├── spk/
│   ├── preventive-maintenance/
│   ├── logistics/
│   ├── condition-monitoring/
│   ├── reports/
│   └── projects/
├── shared/
│   ├── components/
│   ├── formatters/
│   └── constants/
└── app.js
```

---

# BAGIAN E — RANCANGAN TARGET REVISI

## 24. Arsitektur Proses Bisnis Target

```text
[Operator / Driver / P2H / Condition Monitoring]
                         │
                         ▼
               LAPORAN KERUSAKAN / WO
                         │
                         ▼
             REGISTRASI ADMIN WORKSHOP
                         │
                         ▼
            INSPEKSI + DIAGNOSIS + ESTIMASI
                         │
                         ▼
                     APPROVAL
              ┌──────────┴──────────┐
              ▼                     ▼
           DITOLAK              DISETUJUI
                                     │
                                     ▼
                          PENERBITAN SPK MEKANIK
                                     │
                   ┌─────────────────┼─────────────────┐
                   ▼                 ▼                 ▼
               TASK KERJA        SPB/PART ISSUE     TIME LOG
                   │                 │                 │
                   └─────────────────┼─────────────────┘
                                     ▼
                          EKSEKUSI DAN BUKTI FOTO
                                     │
                                     ▼
                     TEST FUNCTION / TEST DRIVE / QC
                                     │
                   ┌─────────────────┴─────────────────┐
                   ▼                                   ▼
              GAGAL QC                            LULUS QC
                   │                                   │
                   └── REWORK / SPK LANJUTAN           ▼
                                             VALIDASI ADMIN WORKSHOP
                                                         │
                                                         ▼
                                                  READY FOR USE
                                                         │
                                                         ▼
                                                MAINTENANCE HISTORY
```

## 25. Struktur Menu Target

```text
Dashboard
Monitoring Unit
Master Asset
Inspeksi & P2H
Work Order & SPK
├── Work Order
├── Inspeksi & Estimasi
├── Approval
├── SPK Mekanik
├── Quality Control
└── Riwayat Pekerjaan
Preventive Maintenance
Spare Part & Logistik
Condition Monitoring
Fuel
Produktivitas
Biaya
People & KPI
HSE / Accident
Laporan & Form
Approval Inbox
Pengaturan
```

## 26. Rancangan Tab Work Order

### 26.1 Data header

- WO ID;
- tanggal dan waktu laporan;
- sumber laporan;
- pelapor;
- jabatan pelapor;
- operator/driver;
- project;
- lokasi detail;
- asset ID;
- SN/plat;
- HM/KM saat kejadian;
- prioritas;
- downtime;
- keluhan;
- foto awal;
- status;
- SLA respon;
- referensi P2H/Condition Monitoring.

### 26.2 Status

```text
DILAPORKAN
MENUNGGU_REGISTRASI
MENUNGGU_INSPEKSI
MENUNGGU_ESTIMASI
MENUNGGU_APPROVAL
DISETUJUI
DITOLAK
SPK_DITERBITKAN
SELESAI
DIBATALKAN
```

### 26.3 Aturan

- WO tidak boleh langsung masuk `In Progress` tanpa inspeksi.
- WO tidak boleh membuat unit `READY`.
- Satu WO dapat menghasilkan satu atau beberapa SPK.
- WO minor dapat menggunakan jalur approval sederhana sesuai threshold.
- Semua perubahan status masuk audit log.

## 27. Rancangan Inspeksi dan Estimasi

Field wajib:

- Inspection ID;
- WO ID;
- pemeriksa;
- tanggal pemeriksaan;
- diagnosis;
- probable root cause;
- komponen;
- severity;
- pekerjaan yang diusulkan;
- estimasi jam kerja;
- estimasi downtime;
- estimasi biaya;
- kebutuhan part;
- kebutuhan vendor;
- risiko keselamatan;
- rekomendasi status unit;
- foto pemeriksaan;
- catatan.

## 28. Rancangan Approval

### 28.1 Jenis approval

- WO repair approval;
- estimasi biaya;
- penerbitan SPK;
- SPB;
- biaya tambahan;
- perubahan scope;
- penutupan SPK;
- release unit;
- PM overdue exception;
- stock adjustment.

### 28.2 Data approval

- Approval ID;
- document type;
- document ID;
- stage;
- approver role;
- approver user;
- requested at;
- decided at;
- decision;
- comments;
- rejection reason;
- approved amount;
- previous value snapshot;
- final value snapshot;
- signature/hash;
- SLA status;
- audit event.

### 28.3 Status

```text
PENDING
APPROVED
REJECTED
RETURNED_FOR_REVISION
CANCELLED
EXPIRED
```

## 29. Rancangan SPK Mekanik

### 29.1 Header SPK

- SPK ID;
- WO ID;
- legacy JO number;
- project;
- lokasi;
- asset ID;
- SN/plat;
- HM/KM awal;
- admin penerbit;
- kepala mekanik;
- mekanik utama;
- anggota mekanik;
- tanggal terbit;
- target mulai;
- target selesai;
- estimasi jam;
- estimasi biaya;
- prioritas;
- status.

### 29.2 Task SPK

- task ID;
- kelompok pekerjaan;
- uraian tugas;
- komponen;
- standard job time;
- mekanik;
- status;
- mulai;
- selesai;
- hasil;
- rework reason.

### 29.3 Status SPK

```text
DRAFT
WAITING_APPROVAL
ISSUED
ACCEPTED_BY_MECHANIC
IN_PROGRESS
WAITING_PART
WAITING_VENDOR
PAUSED
WAITING_QC
REWORK
COMPLETED
CLOSED
CANCELLED
```

### 29.4 Quality gate SPK

SPK tidak dapat masuk `WAITING_QC` jika:

- task belum selesai;
- mekanik belum tercatat;
- time log belum lengkap;
- part issue belum direkonsiliasi;
- tindakan pekerjaan kosong;
- bukti wajib belum tersedia.

SPK tidak dapat masuk `CLOSED` jika QC belum lulus.

## 30. Rancangan Time Log

- mekanik;
- tanggal;
- jam mulai;
- jam berhenti;
- durasi;
- jenis waktu;
- alasan pause;
- overtime;
- verifikator;
- sumber input;
- lokasi/GPS opsional.

Jenis waktu:

- productive repair;
- inspection;
- waiting part;
- waiting approval;
- waiting vendor;
- test/QC;
- rework;
- administrative.

## 31. Rancangan SPB dan Part Issue

### 31.1 SPB

- SPB ID;
- WO ID;
- SPK ID;
- project;
- asset ID;
- requester;
- request date;
- priority;
- required date;
- RTW impact;
- approval status;
- item list;
- notes.

### 31.2 Part Issue

- issue ID;
- SPB ID;
- SPK ID;
- warehouse;
- item;
- part number;
- qty requested;
- qty approved;
- qty issued;
- qty returned;
- used/new/old status;
- issued by;
- received by;
- timestamp;
- evidence.

## 32. Rancangan Tire pada SPK

Gunakan form Tire yang sudah tersedia, lalu tambahkan:

- SPK ID;
- task ID;
- posisi ban;
- merek;
- ukuran;
- serial number;
- tread before;
- tread after;
- pressure before;
- pressure after;
- physical condition before;
- action;
- old tire status;
- new tire stock reference;
- reason;
- photo before;
- photo after;
- mechanic;
- QC result;
- cost.

## 33. Rancangan Accu pada SPK

Gunakan form Aki yang sudah tersedia, lalu tambahkan:

- SPK ID;
- task ID;
- brand;
- type;
- serial number;
- voltage before;
- voltage after;
- CCA before;
- CCA after;
- installation date;
- terminal condition;
- electrolyte;
- casing;
- old/new status;
- stock reference;
- reason;
- photo before;
- photo after;
- mechanic;
- QC result;
- cost.

## 34. Rancangan Quality Control

### 34.1 Jenis pengujian

- visual inspection;
- leak test;
- electrical function;
- hydraulic function;
- brake test;
- steering test;
- load test;
- idle test;
- test drive;
- safety equipment verification;
- Tire verification;
- Accu verification.

### 34.2 Data QC

- QC ID;
- SPK ID;
- asset ID;
- inspector;
- test type;
- checklist;
- measurement;
- result;
- findings;
- photo/video evidence;
- test start/end;
- recommendation;
- rework required;
- approved by;
- approval time.

### 34.3 Status

```text
NOT_STARTED
IN_TEST
FAILED
REWORK_REQUIRED
PASSED
APPROVED
```

## 35. Aturan Ready for Use

Unit hanya dapat menjadi `READY` jika seluruh syarat berikut terpenuhi:

```text
WO approved
AND SPK issued
AND all SPK tasks completed
AND parts reconciled
AND evidence complete
AND QC passed
AND head mechanic approved
AND admin workshop validated
AND no active accident hold
```

## 36. Rancangan Preventive Maintenance Revisi

Pertahankan tracker yang ada dan tambahkan:

### 36.1 Project dan assignment

- project ID;
- workshop;
- planner;
- mechanic team;
- due date;
- due meter;
- service package;
- approval.

### 36.2 Dokumen dan bukti

- upload laporan PM PDF planner;
- checklist PM;
- foto unit sebelum;
- foto filter lama;
- foto filter baru;
- foto oli/grease;
- foto pekerjaan;
- bukti barang;
- invoice/vendor report jika ada;
- dokumen hasil service.

### 36.3 Notifikasi

- in-app notification;
- email queue;
- WhatsApp queue;
- recipient rules;
- escalation rules;
- send history;
- acknowledgement;
- retry status.

### 36.4 Penyempurnaan pencarian

- exact match di urutan pertama;
- highlight hasil;
- auto-scroll;
- hasil tetap mengikuti project aktif;
- dukungan ID unit, lambung, SN, plat, model;
- tombol buka detail;
- tombol buat WO/SPK PM.

## 37. Rancangan Logistik Revisi

### 37.1 Tab

```text
Dashboard Logistik
Barang Masuk
Barang Keluar / Part Issue
Saldo Stok
Oli & Lubricant
Barang Bekas
SPB
Pengadaan
Transfer Antar-Project
Dokumen & Bukti
Stock Adjustment
```

### 37.2 Oli & Lubricant

Tambahkan field:

- transaction ID;
- date;
- project;
- warehouse;
- product;
- brand;
- SAE/grade;
- batch;
- transaction type;
- incoming liter;
- outgoing liter;
- previous balance;
- final balance;
- asset ID;
- HM/KM;
- SPK ID;
- mechanic;
- used oil quantity;
- used oil handling;
- evidence;
- verifier.

### 37.3 Dokumen logistik

- foto barang masuk;
- foto barang keluar;
- foto barang baru;
- foto barang bekas;
- bukti penerimaan;
- surat jalan;
- invoice;
- PDF laporan admin logistik;
- attachment download;
- hash dan audit metadata.

## 38. Rancangan Master Project

Pisahkan:

- `project` sebagai kontrak/organisasi pekerjaan;
- `site` sebagai area kerja;
- `yard/workshop` sebagai fasilitas;
- `warehouse` sebagai lokasi stok;
- `asset location` sebagai posisi unit terkini.

Relasi:

```text
Project 1..n Site
Project 1..n Asset Assignment
Project 1..n User Assignment
Project 1..n Warehouse
Project 1..n WO
Project 1..n SPK
Project 1..n PM Plan
```

## 39. Rancangan Maintenance History

Setiap asset memiliki timeline terpadu:

- P2H;
- condition finding;
- Work Order;
- inspeksi dan estimasi;
- approval;
- SPK;
- task;
- time log;
- SPB;
- part issue;
- Tire;
- Accu;
- PM;
- QC;
- downtime;
- cost;
- attachment;
- status change;
- project movement;
- incident/HSE.

Filter history:

- periode;
- document type;
- project;
- component;
- mechanic;
- status;
- repeat breakdown;
- cost range.

---

# BAGIAN F — MODEL DATA TARGET

## 40. Entitas Utama

```text
users
roles
permissions
user_project_assignments
projects
sites
workshops
warehouses
assets
asset_assignments
asset_status_events
p2h_inspections
condition_inspections
work_orders
work_order_inspections
work_order_estimates
approvals
spk_headers
spk_tasks
spk_assignments
spk_time_logs
spb_headers
spb_items
part_issues
inventory_items
inventory_transactions
lubricant_transactions
tire_inspections
tire_replacements
battery_inspections
battery_replacements
pm_plans
pm_executions
qc_headers
qc_items
attachments
notifications
notification_deliveries
maintenance_history
audit_logs
```

## 41. Relasi Minimum

```text
asset.id → work_orders.asset_id
work_order.id → work_order_inspections.work_order_id
work_order.id → approvals.document_id
work_order.id → spk_headers.work_order_id
spk.id → spk_tasks.spk_id
spk.id → spb_headers.spk_id
spk.id → part_issues.spk_id
spk.id → tire_replacements.spk_id
spk.id → battery_replacements.spk_id
spk.id → qc_headers.spk_id
project.id → seluruh transaksi operasional
attachment.id → attachment_links.document_id
```

## 42. Audit Log

Setiap mutasi penting mencatat:

- event ID;
- user ID;
- role;
- project ID;
- action;
- entity type;
- entity ID;
- before snapshot;
- after snapshot;
- timestamp;
- IP/device metadata;
- reason;
- correlation ID.

---

# BAGIAN G — PRIORITAS IMPLEMENTASI

## 43. P0 — Perbaikan Kritis Sebelum Menambah Fitur

1. Hapus merge conflict marker pada `dashboard.js`.
2. Pastikan tidak ada teks konflik lain yang dirender.
3. Bekukan penambahan fitur baru selama istilah WO/JO/SPK belum final.
4. Finalisasi data model WO, inspection, approval, SPK, QC, dan project.
5. Hentikan aturan `Closed WO → READY` otomatis.
6. Tambahkan feature flag untuk alur baru.
7. Buat test data dan acceptance scenario.

## 44. P1 — Core Workshop Vertical Slice

Target satu alur lengkap:

```text
Buat WO
→ Inspeksi
→ Estimasi
→ Approval
→ Terbit SPK
→ Assign Mekanik
→ SPB
→ Part Issue
→ Eksekusi
→ Bukti
→ QC
→ Ready
→ History
```

Alur ini harus selesai lebih dahulu sebelum memperluas modul non-core.

## 45. P2 — PM dan Logistik

- notifikasi internal;
- email/WhatsApp gateway;
- PDF planner;
- foto realisasi;
- project assignment;
- oil transaction;
- stock transfer;
- attachment storage;
- overdue escalation.

## 46. P3 — Condition Monitoring dan Analitik

- Tire/Accu terhubung SPK;
- repeat breakdown;
- component history;
- failure trend;
- cost per component;
- PM effectiveness;
- planner and mechanic KPI.

## 47. P4 — Production Hardening

- backend API;
- database;
- authentication;
- enforced RBAC;
- audit log;
- object/file storage;
- backup;
- concurrency control;
- monitoring;
- security testing;
- UAT;
- deployment.

---

# BAGIAN H — ROADMAP REVISI

## 48. Sprint 0 — Stabilization dan Terminologi

**Target:** kode bersih dan istilah final.

- hapus conflict marker;
- inventory seluruh pemakaian JO;
- klasifikasikan JO legacy;
- definisikan enum status;
- definisikan document numbering;
- definisikan role dan approval matrix;
- buat migration mapping;
- buat regression checklist.

**Definition of Done:** tidak ada marker konflik, seluruh istilah baru terdokumentasi, dan data model disetujui.

## 49. Sprint 1 — Work Order Intake dan Inspection

- form WO lengkap;
- pelapor/operator/project;
- foto awal;
- SLA;
- inspeksi kepala mekanik;
- diagnosis;
- estimasi waktu, biaya, dan part;
- status WO baru.

**Definition of Done:** WO tidak dapat masuk approval sebelum inspeksi dan estimasi lengkap.

## 50. Sprint 2 — Approval Engine

- approval record;
- multi-level approval;
- revision loop;
- comments;
- rejection reason;
- timestamps;
- history;
- notification in-app.

**Definition of Done:** keputusan approval tersimpan dan mengubah status dokumen secara konsisten.

## 51. Sprint 3 — SPK Mekanik

- nomor SPK;
- generator SPK dari WO approved;
- task list;
- mechanic assignment;
- target time;
- status SPK;
- legacy JO mapping;
- print/export SPK.

**Definition of Done:** mekanik bekerja menggunakan SPK, bukan langsung menggunakan WO.

## 52. Sprint 4 — SPB, Part Issue, dan Time Log

- SPB terhubung SPK;
- approval SPB;
- issue/return;
- old part handling;
- time log;
- waiting reason;
- downtime calculation.

**Definition of Done:** seluruh part dan waktu dapat ditelusuri ke SPK.

## 53. Sprint 5 — Evidence, Tire, dan Accu

- attachment service;
- foto before/after;
- Tire section SPK;
- Accu section SPK;
- serial number;
- stock reference;
- biaya aktual.

**Definition of Done:** penggantian Tire/Accu lengkap dengan bukti, part, mekanik, dan SPK.

## 54. Sprint 6 — QC dan Release Unit

- QC checklist;
- test function;
- test drive;
- rework;
- head mechanic approval;
- admin validation;
- Ready gate;
- maintenance history.

**Definition of Done:** unit tidak dapat `READY` tanpa QC lulus dan validasi.

## 55. Sprint 7 — Preventive Maintenance Completion

- project PM;
- upload PDF planner;
- foto realisasi;
- notification rules;
- email/WhatsApp queue;
- escalation;
- PM approval;
- PM SPK generation.

**Definition of Done:** overdue dapat ditindaklanjuti dari notifikasi sampai SPK PM selesai.

## 56. Sprint 8 — Logistics Completion

- oil input/output;
- transfer project;
- attachment logistik;
- stock adjustment approval;
- PDF report;
- reconciliation;
- warehouse permissions.

**Definition of Done:** stok, oli, dan dokumen dapat diaudit per project dan SPK.

## 57. Sprint 9 — Backend dan Multiuser

- API;
- database;
- authentication;
- RBAC enforcement;
- audit log;
- file storage;
- migration local data;
- backup.

**Definition of Done:** dua user berbeda melihat data yang sama dan setiap perubahan dapat ditelusuri.

## 58. Sprint 10 — UAT dan Go-Live

- scenario testing;
- role testing;
- project testing;
- document testing;
- mobile/responsive testing;
- performance;
- security;
- training;
- SOP;
- pilot project;
- go-live approval.

---

# BAGIAN I — MONITORING PROGRES BARU

## 59. Standar Progress

| Persentase | Definisi |
|---:|---|
| 0% | Belum ada desain atau implementasi |
| 10% | Istilah dan requirement tercatat |
| 25% | UI statis tersedia |
| 40% | Form dan validasi frontend tersedia |
| 50% | Data dapat dibuat dan diperbarui secara lokal |
| 65% | Terintegrasi dengan modul terkait |
| 75% | Workflow, approval, dan audit event tersedia |
| 85% | Backend, RBAC, dan file storage tersedia |
| 95% | Lulus UAT dan perbaikan mayor selesai |
| 100% | Go-live, SOP, training, monitoring, dan support tersedia |

## 60. Baseline Setelah Audit

| Epic | Baseline | Target berikutnya |
|---|---:|---:|
| Stabilization kode | 45% | 100% |
| Work Order intake | 65% | 85% |
| Inspection & Estimate | 20% | 75% |
| Approval Engine | 15% | 75% |
| SPK Mekanik | 0% | 75% |
| SPB & Part Issue | 55% | 75% |
| Execution & Time Log | 30% | 75% |
| QC & Ready Gate | 5% | 75% |
| Master Project | 45% | 75% |
| Preventive Maintenance | 60% | 80% |
| Logistics & Oli | 55% | 80% |
| Tire & Accu | 65% | 80% |
| Attachment & Document | 40% | 75% |
| Maintenance History | 30% | 75% |
| Backend & Multiuser | 10% | 85% |
| RBAC Enforcement | 30% | 85% |

## 61. Format Laporan Progres

```text
Epic:
Sprint:
Owner:
Progress sebelumnya:
Progress sekarang:

Selesai:
- ...

Sedang dikerjakan:
- ...

Belum:
- ...

Blocker:
- ...

Bukti:
- Pull request / commit
- Screenshot
- Test result
- API response
- Database migration

Acceptance criteria lulus:
- ...

Next action:
- ...
```

---

# BAGIAN J — ACCEPTANCE CRITERIA

## 62. Work Order

- dapat dibuat oleh user berhak;
- memiliki asset dan project valid;
- memiliki pelapor dan keluhan;
- menyimpan foto awal;
- memiliki SLA;
- status mengikuti transition rule;
- tidak langsung menjadi SPK;
- tidak langsung mengubah unit menjadi Ready.

## 63. Inspection dan Estimate

- terhubung ke WO;
- diagnosis wajib;
- estimasi durasi wajib;
- estimasi biaya sesuai threshold;
- daftar part dapat dibuat;
- pemeriksa tercatat;
- approval tidak dapat dimulai sebelum data lengkap.

## 64. Approval

- tersimpan sebagai record;
- approver sesuai role dan project;
- keputusan mempunyai timestamp;
- rejection mempunyai alasan;
- revisi tidak menghapus histori;
- setiap keputusan memiliki audit event.

## 65. SPK

- hanya berasal dari WO approved atau PM approved;
- memiliki nomor unik;
- memiliki mechanic assignment;
- memiliki task;
- memiliki target;
- mempunyai status workflow;
- part dan time log terhubung;
- tidak dapat ditutup tanpa QC.

## 66. QC dan Ready

- QC checklist tersedia;
- evidence wajib tersedia;
- result tersimpan;
- failed QC membuat rework;
- unit Ready hanya setelah passed dan validated;
- seluruh status tersinkron ke dashboard.

## 67. PM

- status due dihitung otomatis;
- exact search bekerja;
- project dapat dipilih;
- PDF dan foto tersimpan;
- notifikasi mempunyai delivery log;
- realisasi menghasilkan history dan dapat membentuk SPK PM.

## 68. Logistik

- SPB memiliki SPK ID;
- issue tidak melebihi approval;
- stok berkurang secara atomik;
- return menambah stok sesuai aturan;
- oli memiliki saldo;
- barang bekas terlacak;
- seluruh bukti dapat dibuka dan diunduh;
- transaksi dapat difilter per project.

## 69. Backend

- tidak menggunakan localStorage sebagai source of truth;
- data konsisten antar-user;
- API memvalidasi role;
- audit log immutable;
- attachment tersimpan terpusat;
- backup dan restore diuji.

---

# BAGIAN K — CHECKLIST TINDAKAN LANGSUNG

## 70. Urutan Pekerjaan Terdekat

1. Perbaiki merge conflict marker.
2. Buat daftar seluruh field dan label JO.
3. Finalisasi kebijakan migrasi JO legacy.
4. Tambahkan tab `SPK Mekanik` kosong dengan data model final.
5. Tambahkan `WO Inspection & Estimate`.
6. Ubah Approval Inbox menjadi stateful record.
7. Hapus auto-suggestion `Closed → READY`.
8. Buat QC gate.
9. Tambahkan `projectId` pada transaksi utama.
10. Hubungkan Tire dan Accu ke SPK.
11. Tambahkan attachment metadata.
12. Pindahkan state penting dari localStorage ke backend.

## 71. Hal yang Tidak Perlu Dibuat Ulang

Komponen berikut dapat dipertahankan dan dikembangkan:

- workspace WO Kanban/tabel;
- pencarian dan filter WO;
- global project/location filter sebagai prototype;
- PM forecast tracker;
- PM calendar;
- PM kitting;
- form realisasi PM;
- katalog spare part per unit;
- draft SPB;
- tab barang masuk/keluar/stok;
- tab Oli & Lubricant;
- tab Barang Bekas;
- form Tire/Ban;
- form Accu/Aki;
- mesin import Laporan & Form;
- engine lampiran foto;
- asset 360° dan history dasar.

Komponen tersebut harus direlasikan ke data model dan workflow baru, bukan dibuang.

---

# BAGIAN L — KESIMPULAN

Implementasi terbaru sudah bergerak ke arah yang benar pada sisi tampilan, pencarian, integrasi unit, PM tracker, logistik, Tire, Accu, dan laporan. Perubahan tersebut layak dipertahankan.

Tetapi sistem belum memenuhi rancangan operasional workshop karena **SPK Mekanik belum menjadi transaksi utama**, approval belum auditable, QC belum menjadi gerbang Ready, project belum menjadi master resmi, dan penyimpanan masih lokal.

Fokus pengembangan selanjutnya tidak boleh menyebar ke semua menu sekaligus. Prioritas harus berupa satu vertical workflow yang benar-benar selesai:

```text
WO → Inspection → Estimate → Approval → SPK → SPB → Execution → Evidence → QC → Ready → History
```

Setelah alur tersebut stabil, modul PM, logistik, Tire, Accu, biaya, laporan, dan KPI dapat menggunakan sumber data yang sama secara konsisten.
