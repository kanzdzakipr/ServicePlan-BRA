# Dekomposisi Kebutuhan Klien dan Backlog Implementasi ServicePlan-BRA

> **Tanggal penyusunan:** 31 Oktober 2026  
> **Sumber:** demonstrasi, umpan balik, revisi, dan notulen cepat klien  
> **Target bisnis:** uji coba operasional oleh mekanik dan kesiapan implementasi penuh pada minggu pertama November 2026  
> **Status dokumen:** baseline kebutuhan untuk validasi, implementasi, dan UAT  
> **Kode status:** `SUDAH` · `SEBAGIAN` · `BELUM` · `BUTUH KEPUTUSAN`

---

## 1. Abstraksi Kebutuhan

Permintaan klien dapat diringkas menjadi enam hasil bisnis berikut.

1. **Data dapat dipercaya.** Identitas unit, nomor lambung, kategori alat, SKU, kategori part, satuan, meter HM/KM, WO, SPB, dan realisasi PM harus konsisten dan saling terhubung.
2. **Setiap angka dapat dijelaskan.** KPI PA, MA, UA, EU, MTBF, MTTR, produktivitas, dan KPI planner harus menampilkan formula, komponen perhitungan, periode, serta asal data.
3. **Alur kerja mengikuti kondisi lapangan.** WO, PM, SPB, dan surat/laporan harus mendukung revisi atau pembatalan input yang salah tanpa menghapus jejak audit dan tanpa membuat data ganda.
4. **Perencanaan menjadi adaptif.** Forecast PM harus mengikuti HM/KM aktual. Unit `Long Standby` atau `Breakdown` dapat mengalami pengunduran estimasi service secara terkontrol, tetapi target interval meter tidak boleh diubah tanpa otorisasi.
5. **Informasi siap dipakai untuk keputusan.** Pengguna melihat monitoring sesuai job description, daftar part belum terpenuhi, umur tunggu, PIC, status WO, backlog, dan laporan produktivitas dalam format kerja klien.
6. **Akses dan distribusi data terkendali.** Nilai aset hanya untuk Administrator, ekspor tersedia pada data yang relevan, dan setiap mutasi penting memiliki actor, waktu, alasan, serta status sebelum/sesudah.

### 1.1 Alur data sasaran

```text
Master Aset + Personel + Part
             │
             ├── Meter/LHO/Inspeksi ──> Forecast PM ──> Realisasi + JO
             │                               │
             └── Keluhan/Breakdown ────────> WO ───────> SPB/Request Part
                                                     │
                                                     ├── Approval/Print
                                                     ├── Procurement/Fulfilment
                                                     └── Stok/Pemakaian

WO + PM + LHO + Meter ──> KPI/Productivity ──> Tabel asal data ──> Export
Semua perubahan ────────> Audit trail, revisi, void/cancel, dan notifikasi
```

### 1.2 Prinsip implementasi

- `Cancel/void` bukan `DELETE`; record tetap ada untuk audit dan tidak muncul sebagai data aktif.
- `Revisi` membuat versi atau riwayat perubahan; nomor surat tidak boleh diduplikasi atau dipakai ulang setelah void.
- Formula KPI dihitung di backend dari sumber resmi, bukan dari nilai yang dikirim browser.
- Laporan menampilkan nilai `Tidak ada data` ketika sumber belum tersedia; jangan menggantinya dengan nol.
- Satuan cairan disimpan pada **base unit liter**. Drum hanya satuan transaksi dengan faktor konversi yang wajib ditentukan per item/kemasan.
- Hak akses harus ditegakkan di API dan database scope; menyembunyikan tombol di UI saja tidak cukup.
- Ekspor mengikuti filter aktif, urutan kolom yang disepakati, metadata periode/lokasi, dan timezone `Asia/Bangkok`.

---

## 2. Monitoring Berdasarkan Job Description

Pertanyaan Dheny, “apa yang bisa saya monitor sesuai jobdesc?”, diterjemahkan menjadi dashboard berbasis peran berikut.

| Peran | Informasi yang Dimonitor | Aksi Utama | Indikator Minimum |
|---|---|---|---|
| Head/Equipment Manager | kondisi armada, backlog, breakdown kronis, PM compliance, part tertunda, produktivitas | memberi arahan, approval, eskalasi | PA/UA, unit down, WO aging, PM overdue, SPB aging |
| Maintenance Planner | meter HM/KM, forecast service, PM due/overdue, kesiapan part, realisasi dan JO | merencanakan, merevisi forecast, menutup realisasi | PM compliance, schedule compliance, overdue, parts readiness |
| Mekanik/Leader | WO yang ditugaskan, prioritas, unit, kebutuhan part, progres, bukti kerja | menerima WO, update status, isi tindakan dan dokumentasi | WO open/in progress, SLA, kelengkapan penutupan |
| Logistic Head/Warehouse | SPB dari WO/PM, stok, barang belum terpenuhi, umur tunggu dan PIC | proses, fulfil, issue, revisi/cancel sesuai kewenangan | stok, fulfilment rate, aging, permintaan overdue |
| Asset Manager/Admin | master unit, lokasi, dokumen, valuasi dan kelengkapan identitas | validasi master, koreksi dan audit | kelengkapan data, duplikasi, nilai aset |
| HRD/People | mekanik aktif, absensi, lembur, produktivitas personel | upload, koreksi, validasi | hadir, lembur, jam produktif, data tidak cocok |
| Management | ringkasan lintas proyek tanpa detail operasional berlebihan | review dan keputusan | tren biaya, availability, produktivitas, risiko |

Setiap kartu atau tabel monitoring harus memiliki kolom/tooltip **Asal Data**, **Periode**, **Terakhir Diperbarui**, dan **Status Kualitas Data**.

---

## 3. Matriks Kebutuhan dan Kondisi Awal

Status awal di bawah berasal dari penelusuran kode aktif pada 31 Oktober 2026. Status `SUDAH` tetap memerlukan UAT dengan data klien.

| ID | Modul | Kebutuhan Atomik | Status Awal | Bukti/Kondisi Saat Ini | Gap dan Tindakan |
|---|---|---|---|---|---|
| KPI-01 | Dashboard KPI | Tampilkan rincian PA, MA, UA, EU, MTBF, MTTR beserta pembilang, penyebut, periode, dan asal data | `SEBAGIAN` | `core/KpiCalculator.php` sudah mempunyai formula dan variabel W/S/R/T | Buat tabel drill-down yang menghubungkan komponen jam ke LHO/WO; samakan istilah `WA/Scheduled Hours` |
| KPI-02 | Dashboard KPI | Jelaskan aliran sumber data sampai angka tersaji | `SEBAGIAN` | Produktivitas sudah membaca kalkulator backend | Tambahkan kolom `asal_data`, jumlah record, data quality warning, dan tautan detail |
| PEO-01 | People & KPI | Hapus tulisan personal “P. Martin” dan “D3 Akuntansi” | `BELUM` | Masih ada di `api/people_kpi.php`, `scripts/dashboard.js`, dan seed skema | Hapus dari UI/default/seed aktif; pertahankan dokumen sumber hanya sebagai arsip terbatas bila memang wajib |
| PEO-02 | People & KPI | Kotak KPI planner di samping standar kinerja | `SUDAH` | UI sudah menampilkan “Ketercapaian aktual service berkala...” dan target minimum 95% | UAT formula dan pastikan tidak memakai angka contoh ketika data periode kosong |
| PEO-03 | People & KPI | Upload absensi dan lembur mekanik | `SUDAH` | UI upload, template, preview, API, dan tabel bulanan sudah tersedia | Uji dengan file asli Mei–Oktober; tampilkan error per baris dan cegah duplikasi periode/personel |
| COST-01 | Biaya | Pindahkan valuasi nilai aset ke bagian keuangan dan hanya Administrator yang dapat melihat | `BELUM` | Modul Biaya masih dapat dibaca Equipment Manager dan Asset Manager pada matriks RBAC | Pisahkan permission `asset_valuation.read`; lindungi endpoint dan field respons, bukan hanya tab UI |
| PROD-01 | Produktivitas | Laporan otomatis sesuai format “Update Dumtruck 30.10.2026” | `BUTUH KEPUTUSAN` | Ekspor produktivitas sudah ada, tetapi file/template yang disebut tidak ditemukan di repo | Klien wajib menyediakan contoh final; sepakati ejaan `Dump Truck`, sheet, kolom, warna status, dan aturan posisi |
| PROD-02 | Produktivitas | Backend laporan produktivitas terhubung ke WO | `SEBAGIAN` | Kalkulasi backend sudah memakai sumber operasional dan WO untuk downtime/repair | Buat rekonsiliasi per unit/periode dan test agar total kartu = total baris sumber |
| EXP-01 | Semua modul | Tambahkan ekspor pada setiap menu | `SEBAGIAN` | PM, People & KPI, Produktivitas, HSE, P2H, Biaya, dan beberapa laporan sudah memiliki ekspor | Audit seluruh view; definisikan ekspor untuk tabel/transaksi, bukan halaman pengaturan/modal |
| GOV-01 | Semua transaksi | Penginput dapat membatalkan data salah tanpa approval atasan | `SEBAGIAN` | Void laporan/SPB sudah ada tetapi beberapa aksi memerlukan permission approval | Terapkan kebijakan owner-cancel sebelum approval; setelah approval gunakan reject/revision sesuai role |
| GOV-02 | Surat menyurat | Revisi tidak membuat nomor/data ganda | `SEBAGIAN` | Void audit dan larangan penggunaan ulang nomor SPB sudah tersedia | Tambahkan version chain, reason wajib, dan idempotency pada semua jenis dokumen yang masuk scope |
| SPB-01 | SPB | Mekanisme reject memiliki pilihan revisi dan cancel | `SEBAGIAN` | Void SPB tersedia; alur revisi belum seragam | Definisikan status `DRAFT → SUBMITTED → REVISION_REQUIRED/APPROVED/CANCELLED` dan siapa yang boleh mengubah |
| SPB-02 | SPB | Setelah approval dapat langsung print | `BUTUH KEPUTUSAN` | Laporan/print ada pada beberapa form | Konfirmasi Bu Widya: print hanya saat `APPROVED` atau juga draft ber-watermark |
| SPB-03 | Logistik | Cocokkan spare part dengan SPB dan WO | `SEBAGIAN` | Request part telah terhubung ke logistik; skema SPB dan item tersedia | Wajibkan linkage `wo_id`/`pm_plan_id`, SKU, qty, UOM, dan unit; tampilkan dua arah |
| PART-01 | Master Part | Kategori Genuine/Non-Genuine, filter udara/filter oli, dan SKU | `BELUM` | Data master ada tetapi klasifikasi klien belum lengkap | Tambah struktur kategori/brand authenticity dan validasi SKU unik; impor data Dany/Fadly |
| PART-02 | Logistik | Laporan part belum terpenuhi: nama, deskripsi, unit, hari tunggu, PIC | `SEBAGIAN` | Data procurement/SPB dan laporan monitoring sudah tersedia | Buat view/API aging tunggal dengan definisi start/end time dan filter lokasi/PIC/status |
| PART-03 | Logistik | Normalisasi drum/liter menjadi liter | `BELUM` | UOM tersedia sebagai teks, belum ada model konversi kemasan | Tambah base UOM, transaction UOM, conversion factor, dan snapshot faktor saat transaksi |
| PART-04 | Logistik UI | Perbaiki scroll/grid dan tampilan master part | `BELUM` | Keluhan UX klien belum mempunyai acceptance test | Uji desktop/mobile; sticky header, horizontal scroll terkontrol, filter tetap terlihat, tanpa kolom terpotong |
| PM-01 | Preventive | Edit/revisi jadwal langsung sesuai kondisi lapangan | `SEBAGIAN` | API PM dan forecast sudah ada | Tambah alasan, actor, timestamp, nilai sebelum/sesudah, dan batas field yang boleh diubah |
| PM-02 | Preventive | Long Standby/Breakdown menggeser estimasi tanggal service | `SEBAGIAN` | Forecast berbasis interval dan laju pemakaian sudah tersedia | Tambah override/status period; target HM/KM tetap, estimasi tanggal dihitung ulang dari meter aktual |
| PM-03 | Preventive | Dokumentasi/JO pada realisasi PM | `SUDAH` | Riwayat service memiliki `jo_report_number` dan dokumentasi JO tersedia | UAT upload/buka dokumen, permission, tipe file, dan relasi ke realisasi |
| PM-04 | Preventive | Generate jadwal bulan berikutnya secara otomatis | `SEBAGIAN` | Realisasi membentuk next plan dan forecast tracker sudah tersedia | Tambah job generate idempoten, preview sebelum publish, horizon bulan, serta exception missing meter |
| PM-05 | Preventive | Ekspor jadwal/forecast | `SUDAH` | Tombol `Export Tracker` dan fungsi ekspor tersedia | Cocokkan kolom dengan kebutuhan planner dan filter aktif |
| PM-06 | Preventive | Perbaiki nama unit/no. lambung dan klasifikasi “Mercy” menjadi Dump Truck | `BELUM` | Masalah berada pada kualitas master aset | Jalankan normalisasi berbasis mapping yang disetujui; jangan mengubah identitas secara heuristik tanpa review |
| WO-01 | Work Order | Status/progres WO dapat diperbarui dan mekanik lebih dari satu | `SEBAGIAN` | Update progres tersedia; dukungan multi-PIC perlu UAT end-to-end | Pastikan semua mekanik tersimpan sebagai relasi, tampil di detail/ekspor, dan mendapat notifikasi |
| WO-02 | Work Order | Mekanik operasional harus menerima WO dan melakukan input harian | `SEBAGIAN` | Alur WO tersedia | Siapkan role/pengguna, video, SOP ringkas, pilot data, dan monitoring adopsi |
| WO-03 | Work Order | Rekapitulasi harian WO | `SEBAGIAN` | Data timeline/status ada | Buat laporan per tanggal, lokasi, unit, mekanik, status, problem, tindakan, dan kebutuhan part |
| DOC-01 | Adopsi | Video demo mekanik untuk input WO/progres | `BELUM` | Belum ada artefak video di repo | Rekam setelah UI pilot stabil; lampirkan versi aplikasi dan tanggal video |
| DATA-01 | Master Aset | Sinkronkan nama unit, no. lambung, kategori, dan lokasi | `BELUM` | Ada temuan nama/no. lambung tidak sesuai | Buat daftar exception; Samsul memvalidasi, Admin mengeksekusi perubahan dengan audit |
| DATA-02 | Master Personel | Hapus mekanik tidak aktif dan tambah mekanik baru | `BELUM` | Pengaturan personel tersedia | Gunakan status aktif/nonaktif; jangan hapus histori mekanik dari WO lama |
| DATA-03 | Import | Pastikan seluruh grid/data terunggah lengkap | `BELUM` | Keluhan umum tanpa daftar file dan jumlah record harapan | Buat reconciliation report: total sumber, diterima, ditolak, duplikat, dan alasan |

---

## 4. Dekomposisi Pekerjaan per Epic

### 4.1 Epic A — Transparansi KPI dan asal data

**Tujuan:** pengguna dapat menjawab “angka ini berasal dari mana dan dihitung bagaimana?” tanpa membuka kode.

To-do:

- [ ] Samakan definisi `W = Working/Operating`, `S = Standby`, `R = Repair/Breakdown`, dan `T = Scheduled` dengan terminologi lapangan.
- [ ] Tampilkan formula numerik, contoh: `(720 jam terjadwal - 120 jam breakdown) / 720 × 100 = 83,33%`.
- [ ] Tambahkan tabel per unit berisi scheduled, operating, standby, repair, failure, repair completed, dan sumber record.
- [ ] Tampilkan periode, lokasi, jumlah record, formula version, waktu refresh, dan warning kualitas data.
- [ ] Tambahkan rekonsiliasi kartu KPI terhadap total tabel detail.
- [ ] Buat test pembagian nol, data kosong, overlap downtime, filter lokasi, dan boundary tanggal.

Acceptance criteria:

- Nilai kartu dan hasil hitung ulang dari detail berbeda maksimal `0,01` karena pembulatan.
- Klik rincian KPI menampilkan record LHO/WO yang membentuk pembilang dan penyebut.
- Data kosong tampil `Tidak ada data`, bukan `0%`.

### 4.2 Epic B — People & KPI yang objektif

To-do:

- [ ] Hapus nama dan latar pendidikan personal dari default API, data contoh frontend, dan seed instalasi baru.
- [ ] Pastikan KPI planner dihitung dari PM jatuh tempo vs realisasi tervalidasi per periode/lokasi.
- [ ] Uji upload absensi/lembur dengan file asli; hasil preview harus menyebut baris valid, invalid, dan duplikat.
- [ ] Personel nonaktif tidak boleh dipilih untuk WO baru tetapi tetap muncul pada histori lama.

Acceptance criteria:

- Tidak ada string `P. Martin` atau `D3 Akuntansi` pada aplikasi aktif maupun response API.
- KPI planner tidak menampilkan angka contoh ketika API gagal atau periode belum memiliki data.
- Upload file yang sama dua kali tidak menggandakan rekap.

### 4.3 Epic C — Keuangan dan kerahasiaan valuasi

To-do:

- [ ] Pisahkan permission modul Biaya dari permission khusus `asset_valuation.read`.
- [ ] Saring valuasi dari response API bagi pengguna tanpa hak akses.
- [ ] Pindahkan panel valuasi ke subbagian keuangan yang hanya dirender untuk Administrator.
- [ ] Catat akses/ekspor valuasi pada audit log.

Acceptance criteria:

- Role selain Administrator mendapat `403` saat memanggil endpoint valuasi secara langsung.
- Response biaya umum tidak membocorkan nilai buku, nilai pasar, penawaran buyer, atau margin jual.

### 4.4 Epic D — Produktivitas dan format “Update Dump Truck”

To-do:

- [ ] Dapatkan file referensi “Update Dumtruck 30.10.2026” dari Widya.
- [ ] Bekukan data contract: kode unit, nomor posisi, lokasi, warna/status, periode, kolom WO, dan urutan sheet.
- [ ] Bentuk dataset backend dari `assets + work_orders + timeline + LHO/operation reports`.
- [ ] Buat exporter yang mengikuti format final, termasuk legenda warna tanpa menjadikan warna sebagai satu-satunya makna status.
- [ ] Tambahkan kolom `Sumber Data` dan `No. WO` untuk keterlacakan.
- [ ] Buat reconciliation test antara ekspor, API, dan WO pada periode yang sama.

Acceptance criteria:

- Setiap baris ekspor dapat ditelusuri ke unit dan, bila terkait downtime/repair, minimal satu WO.
- Jumlah unit/status pada ekspor sama dengan hasil filter aplikasi.
- Template yang belum disetujui tidak disebut final.

### 4.5 Epic E — Revisi, cancel, reject, approval, dan print

Kebijakan status yang direkomendasikan:

| Status | Pemilik Input | Approver | Data Aktif? | Boleh Diedit? |
|---|---|---|---:|---:|
| `DRAFT` | edit/cancel | lihat | Tidak | Ya |
| `SUBMITTED` | tarik kembali sebelum direview | approve/reject/request revision | Ya | Terbatas |
| `REVISION_REQUIRED` | revisi lalu submit ulang | lihat riwayat | Tidak | Ya |
| `APPROVED` | tidak | final/lanjut proses | Ya | Tidak; buat revisi resmi |
| `CANCELLED/VOID` | lihat | lihat | Tidak | Tidak |

To-do:

- [ ] Terapkan owner-cancel untuk `DRAFT` dan penarikan `SUBMITTED` yang belum direview.
- [ ] Wajibkan alasan cancel/reject/revisi dan simpan actor, waktu, nilai sebelum/sesudah.
- [ ] Gunakan version chain (`parent_id`, `revision_no`) untuk revisi surat.
- [ ] Cegah nomor surat yang pernah final/void dipakai sebagai dokumen baru.
- [ ] Konfirmasi aturan print SPB kepada Widya; rekomendasi: draft memakai watermark, approved menjadi dokumen resmi.
- [ ] Tampilkan badge status dan riwayat pada UI dan hasil print.

Acceptance criteria:

- Penginput dapat membatalkan kesalahan sebelum approval tanpa meminta approval pembatalan.
- Record batal tidak hilang, tidak ikut total aktif, dan tidak mengubah stok/biaya.
- Retry request tidak membuat dua revisi atau dua transaksi reversal.

### 4.6 Epic F — Preventive Maintenance adaptif

Aturan forecast yang direkomendasikan:

```text
Target meter berikutnya = meter service terakhir + interval service
Sisa meter               = target meter - meter aktual
Estimasi tanggal          = tanggal meter aktual + (sisa meter / rata-rata pemakaian harian)
```

Untuk `Long Standby`/`Breakdown`, target meter tidak berubah. Sistem memakai pemakaian aktual yang menurun atau periode pengecualian untuk menggeser **estimasi tanggal**. Override manual harus memiliki alasan dan tidak boleh menutupi status overdue meter.

To-do:

- [ ] Tambah alasan revisi forecast: meter correction, long standby, breakdown, perubahan operasi, atau lainnya.
- [ ] Simpan periode status unit dan sumber status.
- [ ] Tampilkan original estimate, revised estimate, target meter, meter aktual, dan alasan.
- [ ] Generate jadwal bulan berikutnya secara idempoten dengan mode preview/publish.
- [ ] Flag unit tanpa meter, interval, atau daily usage coefficient.
- [ ] Pastikan realisasi menyimpan JO/dokumen, mekanik, meter, tanggal, dan membentuk next plan.
- [ ] Ekspor forecast sesuai filter lokasi/bulan/status.

Acceptance criteria:

- Unit standby 10 hari dapat bergeser tanggal tanpa mengubah interval 1.500/1.800 KM yang berlaku.
- Menjalankan generator dua kali tidak menggandakan rencana.
- Revisi forecast terlihat pada audit trail.

### 4.7 Epic G — Master part, SPB, dan fulfilment

Model data minimum:

| Elemen | Contoh | Aturan |
|---|---|---|
| SKU | `FLT-AIR-0001` | unik dan tidak berubah |
| Technical name | Air Filter Outer | wajib |
| Authenticity | Genuine / Non-Genuine | controlled vocabulary |
| Part group | Filter Udara / Filter Oli / Pelumas / Lainnya | master category |
| Base UOM | Liter / Pcs | satuan stok |
| Transaction UOM | Drum | opsional |
| Conversion factor | 1 drum = 200 liter | wajib per kemasan; jangan diasumsikan global |

To-do:

- [ ] Dany memverifikasi nama teknis, SKU, authenticity, dan kelompok part.
- [ ] Fadly mengirim stok, lokasi gudang, UOM, isi kemasan, saldo, dan transaksi pembuka.
- [ ] Buat staging import dengan validasi dan laporan exception.
- [ ] Hubungkan item SPB ke SKU, WO/PM, unit, qty, UOM, requester, dan PIC.
- [ ] Buat laporan `Part Belum Terpenuhi` dengan aging calendar day dan working day bila diperlukan.
- [ ] Perbaiki grid: sticky header, filter, horizontal scroll, empty state, dan responsif.

Acceptance criteria:

- Tidak ada transaksi cairan yang kehilangan satuan asal atau faktor konversi.
- Total base quantity sebelum/sesudah konversi sama.
- Part belum terpenuhi menampilkan nama, deskripsi, unit, jumlah, hari tunggu, PIC, status, WO, dan SPB.

### 4.8 Epic H — Ekspor per menu

Ekspor tidak harus dipaksakan untuk menu pengaturan yang tidak memiliki dataset. Cakupan yang disarankan adalah seluruh **tabel operasional dan laporan**.

| Menu | Dataset Ekspor Minimum | Format |
|---|---|---|
| Dashboard/KPI | ringkasan KPI + detail sumber | XLSX/CSV |
| Monitoring/Master Aset | daftar unit dan kelengkapan | XLSX/CSV |
| Inspeksi/Condition | hasil inspeksi dan exception | XLSX/CSV |
| Work Order | detail, status, PIC, durasi, part | XLSX/CSV |
| Preventive | forecast, due, overdue, realisasi | XLSX/CSV |
| Logistik | stok, SPB, fulfilment, aging | XLSX/CSV |
| Fuel | log konsumsi dan exception | XLSX/CSV |
| Produktivitas | format standar + format Widya | XLSX |
| Biaya | transaksi dan ringkasan sesuai akses | XLSX |
| People & KPI | absensi, lembur, KPI | XLSX/CSV |
| HSE/Laporan | register dan detail | CSV/PDF |

Acceptance criteria umum:

- Ekspor mengikuti filter aktif dan scope lokasi/role.
- Nama file mengandung modul, periode, lokasi, dan timestamp.
- Data sensitif yang tidak terlihat di UI juga tidak boleh muncul di ekspor.
- Formula spreadsheet tidak dibentuk dari input mentah yang berawalan `=`, `+`, `-`, atau `@` tanpa sanitasi.

---

## 5. Prioritas dan Urutan Pelaksanaan

### P0 — Wajib sebelum pilot penuh

1. `PEO-01`: hapus informasi personal yang diminta klien.
2. `COST-01`: lindungi valuasi aset hanya untuk Administrator.
3. `GOV-01/GOV-02`: owner-cancel/revisi dengan audit dan tanpa data ganda.
4. `DATA-01/DATA-02`: bersihkan master unit dan personel pilot.
5. `WO-01/WO-02`: pastikan mekanik dapat menerima dan memperbarui WO dengan multi-PIC.
6. `SPB-03`: pastikan WO → request part → logistik dapat ditelusuri.
7. `KPI-01/KPI-02`: tampilkan formula dan sumber agar angka bisa divalidasi lapangan.

### P1 — Operasi minggu pertama

1. `PM-01/PM-02/PM-04`: revisi forecast dan otomasi rencana bulan berikutnya.
2. `PART-01/PART-02/PART-03`: master part, ageing unfulfilled, dan konversi UOM.
3. `PROD-01/PROD-02`: format Update Dump Truck dan rekonsiliasi WO.
4. `EXP-01`: ekspor dataset utama.
5. `WO-03`: rekap WO harian.

### P2 — Adopsi dan penyempurnaan

1. `PART-04`: perbaikan grid/scroll.
2. `DOC-01`: video demo mekanik dan SOP satu halaman.
3. Penyempurnaan print, legenda warna, empty state, dan dashboard per role.

### Gate rilis

| Gate | Syarat Kelulusan |
|---|---|
| G0 — Data | unit/personel pilot tervalidasi; tidak ada ID ganda |
| G1 — Alur inti | WO → part/logistik → progres → close dapat dilakukan sesuai role |
| G2 — Governance | cancel/revisi/void tidak menghapus audit atau menggandakan transaksi |
| G3 — Reporting | KPI dan laporan dapat direkonsiliasi ke sumber |
| G4 — UAT | Dheny, Fajar, Samsul, Dany/Fadly, dan Widya menyetujui skenario terkait |
| G5 — Adopsi | akun mekanik siap, video/SOP tersedia, PIC support ditetapkan |

---

## 6. RACI dan Input yang Dibutuhkan

| Deliverable | Responsible | Approver/Validator | Input Wajib |
|---|---|---|---|
| Implementasi aplikasi, migrasi, test | Brian/tim developer | Widya | keputusan bisnis dan template final |
| Master part | Dany + Fadly | Logistic Head/Dany | SKU, technical name, kategori, UOM, faktor konversi |
| Master unit/no. lambung | Samsul | Asset/Equipment Manager | mapping kode lama → kode benar |
| Pilot WO | Dheny + Fajar + mekanik | Equipment Manager | daftar mekanik aktif dan unit pilot |
| Format produktivitas | Tim developer | Widya | file “Update Dumtruck 30.10.2026” |
| UAT PM | Samsul + Planner | Equipment Manager | meter aktual, interval, status standby/breakdown |
| UAT cancel/revisi SPB | Dany/Fadly | Widya | aturan status, print, dan approval |
| Video demo | Brian | Dheny/Fajar | UI pilot yang sudah dibekukan |

---

## 7. File Aplikasi yang Perlu Diubah

Daftar ini menunjuk file aktif yang ditemukan di repository. Perubahan final tetap harus mengikuti hasil trace per requirement.

| Area | File Existing | Perubahan yang Dibutuhkan |
|---|---|---|
| Shell/UI utama | `dashboard.view.php` | penempatan panel keuangan, modal/status, tombol ekspor, tabel sumber KPI |
| Frontend domain | `scripts/dashboard.js` | People/KPI, PM, produktivitas, logistik, cancel/revisi, ekspor, dan penghapusan data personal hardcoded |
| Styling/responsif | `scripts/dashboard.css`, `scripts/mobile-responsive.js` | grid/scroll master part, badge status, tabel traceability |
| RBAC frontend | `scripts/rbac.js` | permission granular untuk valuasi, cancel owner, ekspor sensitif |
| KPI backend | `core/KpiCalculator.php`, `api/kpi.php` | breakdown sumber, formula metadata, data-quality warning |
| Produktivitas | `core/ProductivityCalculator.php`, `api/productivity.php` | dataset WO per unit/periode dan export contract |
| People & KPI | `api/people_kpi.php` | hapus default personal; validasi KPI planner dan upload absensi |
| Preventive | `api/pm.php`, `api/pm_plans.php`, `core/AssetOnboarding.php` | override forecast, audit revision, generate next month, realisasi JO |
| Work Order | `api/work_orders.php`, `api/work_order_support.php`, `core/WorkOrderSupport.php` | multi-mekanik, daily recap, linkage part, audit status |
| SPB/logistik | `api/logistics.php`, `api/spb_workflow.php`, `core/SpbWorkflow.php` | revision state, owner cancel, fulfilment aging, SKU/UOM conversion |
| Reports/void | `api/reports.php`, `core/ReportVoidPolicy.php`, `core/ReportIntegration.php` | kebijakan owner-cancel, version chain, idempotency, print status |
| Biaya/valuasi | `api/costs.php`, `core/CostLedger.php` | field-level authorization dan audit ekspor valuasi |
| Autentikasi/API guard | `api/init.php`, `core/AuthMiddleware.php` | permission backend baru dan scope lokasi |
| Skema referensi | `scripts/schema.sql` | tabel/kolom/index/permission baru dan seed tanpa data personal |
| Dump lokal | `scripts/u646470441_ServicePlanBRA.sql` | diperbarui setelah migrasi tervalidasi |
| Sinkronisasi | `scripts/sync_to_laragon.ps1` | daftarkan migrasi baru bila mekanisme deploy memerlukannya |

---

## 8. File Baru yang Direkomendasikan

Nama file berikut adalah rancangan; nomor/tanggal migrasi harus disesuaikan dengan urutan aktual repository.

| Jenis | File Baru | Fungsi |
|---|---|---|
| Migrasi governance | `scripts/migrate_client_feedback_governance_20261031.sql` | status revision/cancel, version chain, audit actor/reason |
| Migrasi part/UOM | `scripts/migrate_parts_classification_uom_20261031.sql` | SKU, authenticity, group, base UOM, conversion factor |
| Migrasi PM | `scripts/migrate_pm_forecast_adjustment_20261031.sql` | original/revised estimate, adjustment reason, audit |
| Migrasi permission | `scripts/migrate_asset_valuation_permission_20261031.sql` | permission valuasi admin-only |
| Service export | `core/ExportService.php` | sanitasi dan contract ekspor lintas modul |
| Service part | `core/PartUnitConversion.php` | validasi dan konversi transaction UOM ke base UOM |
| Endpoint fulfilment | `api/unfulfilled_parts.php` | laporan aging part belum terpenuhi |
| Endpoint export | `api/exports.php` | ekspor server-side sesuai role/filter |
| Test KPI | `tests/client_feedback_kpi_traceability.php` | formula, sumber, empty data, filter |
| Test governance | `tests/client_feedback_cancel_revision.php` | owner cancel, revision, audit, idempotency |
| Test valuation | `tests/client_feedback_asset_valuation_rbac.php` | admin allowed, role lain 403/no field leak |
| Test PM | `tests/client_feedback_pm_forecast.php` | standby/breakdown shift, generator idempotent |
| Test part | `tests/client_feedback_parts_uom_fulfilment.php` | klasifikasi, konversi, aging, linkage SPB/WO |
| Test productivity | `tests/client_feedback_productivity_export.php` | format template dan rekonsiliasi WO |
| UAT | `PANDUAN_UAT_FEEDBACK_KLIEN_20261031.md` | skenario per role dan bukti sign-off |
| Data template | `material/TEMPLATE_MASTER_PART_KLIEN.csv` | format input Dany/Fadly |
| Data mapping | `material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv` | validasi Samsul |
| Video guide | `material/PANDUAN_VIDEO_DEMO_MEKANIK.md` | storyboard input WO/progres/part/close |

Catatan: jangan membuat seluruh file baru sekaligus bila perubahan dapat dimasukkan dengan aman pada service yang sudah ada. File baru dipilih untuk memisahkan concern yang benar-benar lintas modul atau membutuhkan migrasi/audit tersendiri.

---

## 9. Struktur Data Minimum yang Perlu Ditambahkan atau Ditampilkan

### 9.1 Audit revisi/cancel

| Field | Fungsi |
|---|---|
| `entity_type`, `entity_id` | objek yang berubah |
| `action` | `CANCEL`, `WITHDRAW`, `REVISE`, `REJECT`, `APPROVE` |
| `revision_no`, `parent_id` | rantai versi |
| `old_status`, `new_status` | transisi audit |
| `reason` | alasan wajib |
| `actor_user_id`, `acted_at` | pelaku dan waktu |
| `before_json`, `after_json` | snapshot perubahan penting |
| `idempotency_key` | mencegah request ganda |

### 9.2 Penyesuaian forecast PM

| Field | Fungsi |
|---|---|
| `asset_id`, `pm_plan_id` | unit dan rencana |
| `original_estimated_date` | estimasi sebelum revisi |
| `revised_estimated_date` | estimasi terbaru |
| `target_meter`, `current_meter` | dasar perhitungan |
| `usage_rate` | rata-rata HM/KM harian |
| `adjustment_type`, `reason` | long standby/breakdown/koreksi |
| `effective_from`, `effective_to` | periode pengecualian |
| `created_by`, `created_at` | audit |

### 9.3 Part dan UOM

| Field | Fungsi |
|---|---|
| `sku`, `technical_name` | identitas part |
| `authenticity` | genuine/non-genuine |
| `part_group_id` | filter udara/oli/dll. |
| `base_uom_id` | satuan stok |
| `transaction_uom_id` | satuan pembelian/pemakaian |
| `conversion_factor` | pengali ke base UOM |
| `conversion_snapshot` | faktor historis pada transaksi |

---

## 10. Paket UAT Minimum

| Skenario | Aktor | Hasil yang Diharapkan |
|---|---|---|
| Buat WO dan tugaskan dua mekanik | Planner | dua mekanik tersimpan, melihat tugas, dan muncul di ekspor |
| Update progres harian | Mekanik | timeline mencatat actor/waktu/status tanpa duplikasi |
| Ajukan part dari WO | Mekanik/Planner | SPB logistik memiliki WO, unit, SKU, qty, UOM |
| Batalkan salah input sebelum approval | Pembuat | berhasil tanpa approval; audit tetap ada |
| Reject untuk revisi | Approver | pembuat dapat mengubah versi baru tanpa nomor ganda |
| PM unit long standby | Planner | tanggal bergeser, target meter tetap, alasan tercatat |
| Realisasi PM dengan JO | Planner/Mekanik | dokumen dapat dibuka dan next plan terbentuk satu kali |
| Upload absensi periode sama | HRD/Admin | preview jelas; upload ulang tidak menggandakan data |
| Akses valuasi sebagai non-admin | Asset/Equipment Manager | tab tersembunyi dan API menolak/tidak membocorkan field |
| Export produktivitas | Widya | kolom, posisi, lokasi, status, warna, dan WO sesuai template |
| Konversi 2 drum oli | Logistik | liter dihitung dari faktor kemasan; satuan asal tetap tersimpan |
| Lihat part belum terpenuhi | Head/Logistik | aging dan PIC benar berdasarkan waktu permintaan |

---

## 11. Keputusan Klien yang Masih Diperlukan

1. Kirim file asli **“Update Dumtruck 30.10.2026”** dan tentukan apakah nama laporan final memakai `Dump Truck`.
2. Apakah SPB boleh dicetak saat draft dengan watermark, atau hanya setelah approved?
3. Siapa yang boleh menarik kembali `SUBMITTED`: hanya pembuat, pembuat + atasannya, atau role tertentu?
4. Setelah dokumen approved, apakah koreksi dilakukan sebagai revisi bernomor (`Rev. 1`) atau void lalu buat nomor baru?
5. Definisi “hari menunggu” memakai hari kalender atau hari kerja, dan kapan hitungan berhenti?
6. Isi satu drum untuk setiap produk cair; sistem tidak boleh menganggap semua drum = 200 liter.
7. Sumber resmi scheduled hours/WA: kalender kerja, shift, kontrak, atau input LHO?
8. Ambang `Long Standby`, unit kronis, dan warna status yang disetujui.

---

## 12. Definition of Done

Suatu requirement hanya dapat dinyatakan selesai bila:

- perubahan skema bersifat idempoten dan dapat diterapkan pada database lokal;
- API melakukan validasi, permission, scope lokasi, dan audit;
- UI tidak menampilkan aksi yang dilarang dan memiliki state loading/error/empty;
- data bertahan setelah refresh dan login ulang;
- automated test terdekat lulus;
- skenario UAT per role lulus dengan data pilot;
- ekspor sama dengan filter/tabel dan tidak membocorkan data sensitif;
- dokumentasi dan dump/skema deployment diperbarui;
- hasil disinkronkan ke Laragon dan diuji melalui `dashboard.php`, bukan file statis.

---

## 13. Ringkasan Eksekutif Backlog

| Kelompok | Sudah | Sebagian | Belum | Butuh Keputusan |
|---|---:|---:|---:|---:|
| KPI & People | 2 | 2 | 1 | 0 |
| Biaya & Produktivitas | 0 | 1 | 1 | 1 |
| Export & Governance | 0 | 3 | 0 | 0 |
| Part, SPB & Logistik | 0 | 3 | 3 | 1 |
| Preventive Maintenance | 2 | 3 | 1 | 0 |
| WO, Data & Adopsi | 0 | 3 | 4 | 0 |

Fokus implementasi bukan menambah menu sebanyak mungkin, melainkan menutup tiga risiko terbesar: **integritas data**, **keterlacakan sumber angka**, dan **alur revisi/cancel yang aman**. Setelah tiga fondasi tersebut stabil, otomasi forecast, format laporan Widya, dan ekspor lintas modul dapat diselesaikan tanpa menambah data ganda atau hasil yang tidak dapat diaudit.
