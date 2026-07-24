# Machine Inspection Report - Reclaimer, Spreader, dan Compactor

## Ringkasan

- **Sumber:** `Reclaimer ,Spreader inspection RWI.pdf`
- **Perusahaan:** Bina Rekayasa Anugrah
- **Customer yang tercetak:** PERTAMINA HULU ROKAN
- **Jenis dokumen:** Templat inspeksi harian mesin
- **Jumlah halaman:** 1
- **Kondisi dokumen:** Masih kosong; tidak terdapat hasil inspeksi, identitas mesin, tanggal, atau tanda tangan yang telah diisi

> Istilah dan ejaan sumber dipertahankan, termasuk `FAUL` dan `Tekhnisi’s Name`. Penjelasan tambahan yang tidak tercetak pada formulir diberi konteks sebagai interpretasi atau rekomendasi.

---

# 1. Templat Identitas

## 1.1 Pilihan Mesin

Tandai mesin yang diperiksa:

- [ ] XCMG XLZ2303K
- [ ] XCMG XKC 185
- [ ] RM 500 RECLAIMER
- [ ] XCMG XS113 E
- [ ] XCMG XS205PD
- [ ] SAKAI SV700

## 1.2 Data Mesin

| Field | Isian |
|---|---|
| Machine Type |  |
| Serial Number |  |
| Fleet Number |  |
| Hour Meter |  |

## 1.3 Data Inspeksi dan Pekerjaan

| Field | Isian |
|---|---|
| Date |  |
| Customer | PERTAMINA HULU ROKAN |
| Site |  |
| Job Number |  |
| Mechanic on Duty |  |
| Contact Person |  |

---

# 2. Checklist Komponen

## 2.1 Arti Kolom Status

| Status | Penjelasan |
|---|---|
| OK | Komponen dinilai dapat diterima atau berfungsi saat diperiksa. |
| FAUL | Label yang tercetak pada sumber untuk kondisi bermasalah/gagal. Sumber tidak menjelaskan definisi atau tingkat keparahannya. |
| N/A | Komponen tidak tersedia atau tidak relevan untuk mesin yang diperiksa. |

> Formulir sumber tidak memberikan acceptance criteria. Arti di atas merupakan interpretasi umum atas struktur checklist.

## 2.2 Tabel Inspeksi

| No. | Check all these components | OK | FAUL | N/A |
|---:|---|:---:|:---:|:---:|
| 01 | Chassis/Safety Devices | [ ] | [ ] | [ ] |
| 02 | Operators Stand | [ ] | [ ] | [ ] |
| 03 | Milling Drum | [ ] | [ ] | [ ] |
| 04 | Engine/Motor Group | [ ] | [ ] | [ ] |
| 05 | Hydraulic Oil Feed | [ ] | [ ] | [ ] |
| 06 | Electrical System | [ ] | [ ] | [ ] |
| 07 | Height Adjustment | [ ] | [ ] | [ ] |
| 08 | Advance Drive | [ ] | [ ] | [ ] |
| 09 | Steering System | [ ] | [ ] | [ ] |
| 10 | Conveyor Unit | [ ] | [ ] | [ ] |
| 11 | Levelling Unit | [ ] | [ ] | [ ] |
| 12 | Water System | [ ] | [ ] | [ ] |
| 13 | Cleaner Unit | [ ] | [ ] | [ ] |
| 14 | Screed Connection | [ ] | [ ] | [ ] |
| 15 | Hydraulic Accessories | [ ] | [ ] | [ ] |
| 16 | Binding Unit | [ ] | [ ] | [ ] |
| 17 | Compressed Air System | [ ] | [ ] | [ ] |
| 18 | Lubrication Unit | [ ] | [ ] | [ ] |
| 19 | Screed | [ ] | [ ] | [ ] |
| 20 | Gas Unit | [ ] | [ ] | [ ] |
| 21 | Concrete Unit | [ ] | [ ] | [ ] |
| 22 | Mixing Unit | [ ] | [ ] | [ ] |
| 23 | Towing/Pushing Device | [ ] | [ ] | [ ] |
| 24 | Chip Spreading Unit | [ ] | [ ] | [ ] |
| 25 | Oscillation/Vibration | [ ] | [ ] | [ ] |
| 26 | Dowel-bar Unit | [ ] | [ ] | [ ] |
| 27 | Tools/Accessories | [ ] | [ ] | [ ] |
| 28 | Feeding Systems | [ ] | [ ] | [ ] |
| 29 | Crushers | [ ] | [ ] | [ ] |
| 30 | Screens | [ ] | [ ] | [ ] |

## 2.3 Instruksi Sumber

> Check all these components.  
> Perform this check every day. If there are any problems with this device, you can add information about solutions and repairs:

## 2.4 Area Temuan, Solusi, dan Perbaikan

```text
Nomor komponen:
Komponen:
Temuan/gejala:
Kondisi saat ditemukan:
Tindakan sementara:
Solusi/perbaikan:
Suku cadang/material:
Nomor work order:
Status setelah tindakan:
Pekerjaan lanjutan:
Keterangan:
```

---

# 3. Pengesahan

| Peran | Nama | Signature |
|---|---|---|
| Operator |  |  |
| Tekhnisi |  |  |

Label yang tercetak pada sumber:

- `Operator’s Name`
- `Signature`
- `Tekhnisi’s Name`
- `Signature`

---

# 4. Penjelasan Format Templat

## 4.1 Susunan Halaman

Formulir dibagi menjadi lima area utama:

1. **Header perusahaan dan judul** di kiri atas.
2. **Pilihan tipe mesin** di bagian tengah atas.
3. **Identitas mesin dan pekerjaan** di bagian atas kanan dan baris di bawahnya.
4. **Checklist 30 komponen** di bagian tengah; pada sumber ditampilkan sebagai dua kelompok berdampingan, nomor 01-15 dan 16-30.
5. **Area catatan serta tanda tangan** di bagian bawah.

Dalam Markdown, checklist dinormalisasi menjadi satu tabel vertikal agar lebih mudah dicari, disalin, dan dimasukkan ke sistem.

## 4.2 Fungsi Field Identitas

### Machine Type

Model mesin yang diperiksa. Isian ini seharusnya konsisten dengan kotak pilihan mesin.

### Serial Number

Nomor seri pabrikan untuk membedakan unit dengan model yang sama.

### Fleet Number

Kode armada atau kode unit internal perusahaan.

### Hour Meter

Pembacaan jam operasi pada saat inspeksi. Formulir hanya menyediakan satu nilai HM dan tidak menghitung selisih dengan inspeksi sebelumnya.

### Date

Tanggal inspeksi. Tidak tersedia field jam atau shift.

### Customer

Nilai PERTAMINA HULU ROKAN sudah tercetak, sehingga templat tampaknya disiapkan untuk customer tersebut.

### Site

Lokasi kerja atau site. Tidak ada field terpisah untuk STA atau lokasi detail.

### Job Number

Nomor pekerjaan atau referensi pekerjaan. Formatnya tidak dijelaskan pada sumber.

### Mechanic on Duty

Mekanik yang sedang bertugas. Field ini terpisah dari nama teknisi pada bagian tanda tangan.

### Contact Person

Orang yang dapat dihubungi. Formulir tidak memisahkan nama, jabatan, dan nomor telepon.

---

# 5. Penjelasan Umum Komponen

> Uraian berikut merupakan interpretasi umum untuk membantu pembacaan. Sumber hanya mencantumkan nama komponen tanpa prosedur atau standar inspeksi.

| No. | Komponen | Cakupan Umum yang Dapat Diperiksa |
|---:|---|---|
| 01 | Chassis/Safety Devices | Struktur, guard, akses, alarm, emergency device, dan perangkat keselamatan. |
| 02 | Operators Stand | Kursi, kontrol, pijakan, akses, visibilitas, dan area operator. |
| 03 | Milling Drum | Drum, cutting tools, holder, pengikat, putaran, dan keausan yang terlihat. |
| 04 | Engine/Motor Group | Engine/motor, mounting, kebocoran, suara, temperatur, dan indikator. |
| 05 | Hydraulic Oil Feed | Reservoir, level, hose, pipa, sambungan, suplai, dan kebocoran oli. |
| 06 | Electrical System | Battery, kabel, konektor, lampu, instrument, sensor, dan fungsi kelistrikan. |
| 07 | Height Adjustment | Mekanisme, aktuator, sensor, dan respons pengaturan tinggi. |
| 08 | Advance Drive | Sistem propel/penggerak maju dan respons pergerakan. |
| 09 | Steering System | Respons kemudi, linkage, cylinder, hose, dan kelonggaran. |
| 10 | Conveyor Unit | Belt/chain, roller, tension, tracking, guard, dan aliran material. |
| 11 | Levelling Unit | Sensor dan kontrol pengaturan level/kerataan. |
| 12 | Water System | Tangki, pump, hose, nozzle, spray, level, dan kebocoran. |
| 13 | Cleaner Unit | Perangkat pembersih yang tersedia pada konfigurasi mesin. |
| 14 | Screed Connection | Pin, locking, hose, kabel, dan kekencangan sambungan screed. |
| 15 | Hydraulic Accessories | Auxiliary circuit, valve, coupling, hose, dan attachment hidraulik. |
| 16 | Binding Unit | Unit penanganan/distribusi binder apabila tersedia. |
| 17 | Compressed Air System | Compressor, pressure, hose, fitting, receiver, dan kebocoran udara. |
| 18 | Lubrication Unit | Grease/oil system, pump, line, nipple, dan level pelumas. |
| 19 | Screed | Kondisi fisik, sambungan, pengaturan, dan fungsi screed. |
| 20 | Gas Unit | Perangkat atau suplai gas pada mesin yang memilikinya. |
| 21 | Concrete Unit | Modul penanganan beton pada konfigurasi yang relevan. |
| 22 | Mixing Unit | Chamber, paddle, drive, guard, kebersihan, dan fungsi pencampuran. |
| 23 | Towing/Pushing Device | Titik tarik/dorong, pin, locking, dan kondisi struktur. |
| 24 | Chip Spreading Unit | Hopper, gate, roller, feeder, dan mekanisme penyebaran chip. |
| 25 | Oscillation/Vibration | Sistem vibrasi/oscillation, mounting, respons, dan suara abnormal. |
| 26 | Dowel-bar Unit | Modul dowel bar, alignment, guide, feed, dan penggerak. |
| 27 | Tools/Accessories | Kelengkapan alat, kunci, aksesori, hose, dan peralatan pendukung. |
| 28 | Feeding Systems | Hopper, conveyor, auger, feeder, gate, sensor, dan aliran material. |
| 29 | Crushers | Drive, guard, wear component, sumbatan, dan suara abnormal. |
| 30 | Screens | Screen/mesh, mounting, kebersihan, kerusakan, dan penyumbatan. |

## 5.1 Fungsi N/A

Daftar mesin mencakup reclaimer, spreader, dan compactor dengan konfigurasi berbeda. Komponen seperti milling drum, screed, crusher, screens, dowel-bar unit, atau chip spreading unit tidak selalu tersedia pada setiap model.

Status `N/A` penting untuk membedakan komponen yang memang tidak berlaku dari komponen yang belum diperiksa. Formulir tidak menyediakan status khusus `Not Checked`, sehingga semua baris sebaiknya diberi tanda.

---

# 6. Alur Pengisian yang Disarankan

> Bagian ini merupakan rekomendasi penggunaan, bukan instruksi tambahan dari sumber.

1. Pilih satu tipe mesin.
2. Isi identitas mesin dan pembacaan HM.
3. Isi tanggal, site, job number, mekanik, dan contact person.
4. Periksa 30 item.
5. Tandai tepat satu pilihan: OK, FAUL, atau N/A.
6. Untuk setiap FAUL, tulis nomor komponen, temuan, tindakan sementara, dan rencana perbaikan.
7. Cantumkan nomor work order bila pekerjaan lanjutan diperlukan.
8. Operator dan teknisi mengisi nama serta tanda tangan.
9. Simpan formulir sebagai histori inspeksi unit.

---

# 7. Informasi yang Dicakup dan Tidak Dicakup

## 7.1 Dicakup oleh Templat

- pilihan enam model mesin;
- machine type, serial number, fleet number, dan HM;
- tanggal, customer, site, job number, mechanic on duty, dan contact person;
- checklist 30 komponen;
- status OK, FAUL, dan N/A;
- area masalah, solusi, dan perbaikan;
- nama dan tanda tangan operator serta teknisi.

## 7.2 Tidak Memiliki Field Khusus

- waktu inspeksi dan shift;
- nomor unik laporan;
- nomor revisi templat;
- severity defect;
- indikator safety critical;
- keputusan stop operation;
- target penyelesaian;
- spare part yang diperlukan;
- PIC perbaikan;
- status open/closed;
- foto atau lampiran;
- supervisor approval;
- verifikasi setelah perbaikan;
- acceptance criteria per komponen.

Ketiadaan field tidak membuktikan proses tersebut tidak dilakukan; informasi tersebut hanya tidak terdokumentasi secara terstruktur dalam formulir.

---

# 8. Catatan Kualitas Templat

1. `FAUL` dipertahankan sesuai sumber; formulir tidak menjelaskan apakah yang dimaksud `FAULT` atau `FAIL`.
2. `Tekhnisi’s Name` menggunakan ejaan sebagaimana tercetak.
3. Daftar komponen bersifat lintas tipe mesin, sehingga banyak baris mungkin N/A.
4. Tidak ada kriteria teknis untuk menentukan OK atau FAUL.
5. Tidak ada kolom temuan per item; seluruh masalah masuk ke satu area catatan.
6. Tidak ada status `Belum Diperiksa`.
7. Customer telah ditetapkan menjadi PERTAMINA HULU ROKAN.
8. `Mechanic on Duty` dan `Tekhnisi’s Name` dapat merujuk orang yang sama atau berbeda, tetapi tidak dijelaskan.
9. Tidak tersedia pengesahan supervisor atau equipment manager.
10. Tidak terdapat nomor laporan untuk penelusuran unik.

---

# 9. Rekomendasi Struktur Digital

> Bagian ini bukan isi asli formulir.

## 9.1 Header Inspeksi

| Field Sistem | Tipe |
|---|---|
| inspection_id | UUID/nomor otomatis |
| inspection_date | Date |
| inspection_time | Time |
| shift | Enum |
| customer | Reference |
| site | Reference |
| job_number | Text |
| machine_model | Reference |
| serial_number | Text |
| fleet_number | Text |
| hour_meter | Decimal |
| mechanic_on_duty | User |
| contact_person | Text |
| operator | User |
| technician | User |

## 9.2 Detail Checklist

| Field Sistem | Tipe |
|---|---|
| inspection_id | Reference |
| component_no | Integer 1-30 |
| component_name | Reference |
| status | OK / FAUL / N/A / NOT CHECKED |
| finding | Text |
| severity | Enum |
| immediate_action | Text |
| work_order | Reference |
| photo | Attachment |
| due_date | Date |
| repair_status | OPEN / IN PROGRESS / CLOSED |

---

# 10. Kesimpulan

Dokumen ini adalah templat inspeksi harian serbaguna untuk enam model alat yang terkait dengan reclaimer, spreader, dan compactor. Templat menggabungkan identitas mesin, checklist 30 komponen, ruang solusi/perbaikan, serta pengesahan operator dan teknisi.

Karena formulir masih kosong, tidak ada kondisi mesin, defect, atau hasil inspeksi aktual yang dapat disimpulkan. Keterbatasan utama templat adalah tidak adanya kriteria pemeriksaan, severity, temuan per komponen, bukti foto, work order terstruktur, dan verifikasi penutupan perbaikan.
