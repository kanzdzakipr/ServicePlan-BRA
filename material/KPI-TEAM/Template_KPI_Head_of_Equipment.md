# Template KPI Head of Equipment

## Informasi Workbook

| Field | Informasi |
|---|---|
| Nama file sumber | `Template_KPI_Head_of_Equipment.xlsx` |
| Jumlah sheet | 5 |
| Fungsi utama | Templat penilaian KPI Head of Equipment, rekap nilai bulanan, lampiran monitoring perbaikan unit, rekap JO mekanik/order parts, dan executive summary |
| Kondisi data | Sebagian besar merupakan templat kosong; sheet KPI berisi contoh skor dan catatan |
| Nilai contoh saat ini | 54 dari maksimum 100 |
| Kategori berdasarkan interpretasi pada sheet KPI | Tidak efektif / perlu evaluasi serius |
| Formula yang ditemukan | `H5`, `H15`, dan `Rekap Bulanan!C2` |
| Grafik | Grafik tren bulanan pada sheet `Rekap Bulanan` |
| Data validation | Tidak ditemukan |
| Conditional formatting | Color scale pada skor, total KPI, dan nilai bulanan |

> **Catatan konversi:** isi, istilah, kapitalisasi, dan nama sheet dipertahankan sebagaimana sumber, termasuk penulisan `excutive summary`, `summary jo dan order parts meka`, `koordonasi`, dan `Realisasi JO`. Penjelasan tambahan dan rekomendasi pada bagian akhir bukan perubahan terhadap isi sumber.

---

# 1. Struktur Workbook

| No. | Nama Sheet | Rentang Terpakai | Fungsi |
|---:|---|---:|---|
| 1 | `KPI Head of Equipment` | `A1:I28` | Tabel penilaian 10 indikator KPI dengan skor, bobot, nilai bobot, catatan, dan interpretasi nilai. |
| 2 | `Rekap Bulanan` | `A1:D13` | Rekap total nilai KPI Januari–Desember dan grafik tren kinerja. |
| 3 | `lampiran laporan` | `A1:N26` | Templat monitoring unit down, target RTW, realisasi, PIC, status, dan eskalasi. |
| 4 | `summary jo dan order parts meka` | `A1:F3` | Header rekap bulanan pembuatan JO mekanik dan JO/order parts. |
| 5 | `excutive summary` | `A1:F27` | Templat highlight kinerja periode serta masalah utama dan aksi cepat. |

---

# 2. Sheet `KPI Head of Equipment`

## 2.1 Judul

**HEAD OF EQUIPMENT – KONTROL PERCEPATAN PERBAIKAN**

## 2.2 Tabel KPI

| No. | Aspek KPI | Indikator | Target | Realisasi | Skor (1–5) (contoh) | Bobot (%) | Nilai Bobot | Catatan |
|---:|---|---|---|---|---:|---:|---:|---|
| 1 | RTW & Downtime | % unit selesai ≤ target RTW | ≥ 90% |  | 5 | 15 | 15 | Catatan |
| 2 | RTW & Downtime | Rata-rata downtime per unit | ≤ standar |  | 4 | 20 | 16 | target:<br>❌ Tidak ada unit down > 7 hari<br>❌ Tidak ada unit kronis > 14 hari |
| 3 | RTW & Downtime | Kepatuhan PM tepat waktu | ≥ 95% |  | 4 | 10 | 8 | Rata-rata downtime per unit, Standar acuan: Dump truck: ≤ 5–7 hari (corrective standar), Alat berat: sesuai jenis pekerjaan. ≥ 95% RTW tepat waktu sesuai target yang disepakati RTW harus final, bukan RTW bersyarat tanpa uji fungsi |
| 4 | Percepatan | Waktu respon awal kerusakan | ≤ 24 jam |  | 3 | 10 | 6 | PM on-time (%), Breakdown akibat PM gagal NIHIL, PM dilaksanakan sesuai dengan standar pelaksanaan |
| 5 | Percepatan | Keterlambatan karena spare part | ≤ 10% |  | 1 | 10 | 2 | Penurunan kerugian downtime ≥ 30–40% / bulan |
| 6 | Percepatan | Keterlambatan karena manpower | ≤ 5% |  | 1 | 5 | 1 | Downtime karena parts unavailable = 0 |
| 7 | Biaya & Kualitas | Deviasi biaya corrective | ≤ 110% |  | 1 | 10 | 2 | Indikator: KPI mekanik tercapai (%), Jumlah sanksi / pembinaan menurun |
| 8 | Biaya & Kualitas | Repeat breakdown ≤ 30 hari | ≤ 5% |  | 1 | 10 | 2 | Kerusakan ulang ≤ 30 hari |
| 9 | Kepemimpinan | Monitoring & pelaporan unit | Konsisten |  | 1 | 5 | 1 | (jo mekanik, dll) |
| 10 | Kepemimpinan | Inisiatif percepatan perbaikan | Aktif |  | 1 | 5 | 1 | koordonasi tim, Solusi percepatan (shift, vendor, prioritas) |
|  |  |  |  |  | **TOTAL NILAI** |  | **54** |  |

## 2.3 Petunjuk pada Sheet

> Isi kolom `Skor (1-5)`. Nilai Bobot otomatis = Skor × Bobot / 5. Total Nilai otomatis di baris TOTAL.

## 2.4 Formula Penilaian

Rumus konseptual yang dinyatakan pada petunjuk:

```text
Nilai Bobot = Skor × Bobot ÷ 5
Total Nilai = jumlah seluruh Nilai Bobot
```

Contoh perhitungan indikator pertama:

```text
Skor        = 5
Bobot       = 15
Nilai Bobot = 5 × 15 ÷ 5
             = 15
```

Verifikasi total nilai contoh:

| No. | Skor | Bobot | Perhitungan | Nilai |
|---:|---:|---:|---|---:|
| 1 | 5 | 15 | 5 × 15 ÷ 5 | 15 |
| 2 | 4 | 20 | 4 × 20 ÷ 5 | 16 |
| 3 | 4 | 10 | 4 × 10 ÷ 5 | 8 |
| 4 | 3 | 10 | 3 × 10 ÷ 5 | 6 |
| 5 | 1 | 10 | 1 × 10 ÷ 5 | 2 |
| 6 | 1 | 5 | 1 × 5 ÷ 5 | 1 |
| 7 | 1 | 10 | 1 × 10 ÷ 5 | 2 |
| 8 | 1 | 10 | 1 × 10 ÷ 5 | 2 |
| 9 | 1 | 5 | 1 × 5 ÷ 5 | 1 |
| 10 | 1 | 5 | 1 × 5 ÷ 5 | 1 |
|  |  | **100** | **Total** | **54** |

Jumlah bobot adalah **100%**, sehingga total nilai maksimum adalah 100.

## 2.5 Formula yang Benar-Benar Tersimpan di File

| Sel | Formula |
|---|---|
| `H5` | `=IFERROR(F5*G5/5,0)` |
| `H15` | `=SUM(H5:H14)` |

Catatan penting:

- Rumus Nilai Bobot hanya ditemukan pada `H5`.
- Nilai `H6:H14` tersimpan sebagai angka tetap, bukan formula.
- Apabila skor pada `F6:F14` diubah, Nilai Bobot pada baris tersebut **tidak otomatis berubah**.
- Total `H15` tetap menjumlahkan isi `H5:H14`, termasuk angka tetap.
- Agar petunjuk sheet benar-benar berlaku, formula `H5` perlu disalin sampai `H14`.

## 2.6 Conditional Formatting

| Area | Format |
|---|---|
| `F5:F14` | Color scale untuk skor 1–5; nilai tinggi terlihat hijau, nilai menengah kuning, nilai rendah merah. |
| `H15` | Color scale untuk total nilai. |

Tidak ditemukan pembatasan input angka 1–5. Pengguna secara teknis dapat memasukkan angka di luar rentang tersebut karena tidak ada data validation.

## 2.7 Interpretasi Nilai

| Total Nilai | Interpretasi |
|---:|---|
| ≥ 85 | Sangat Baik |
| 75–84 | Baik |
| 65–74 | Cukup, perlu perbaikan |
| < 65 | Tidak efektif / perlu evaluasi serius |

Dengan total contoh **54**, hasil berada pada kategori:

> **Tidak efektif / perlu evaluasi serius**

Nilai tersebut masih berupa contoh karena kolom `Realisasi` seluruhnya kosong.

---

# 3. Penjelasan Terperinci Setiap KPI

> Definisi operasional di bawah merupakan penjelasan dan rekomendasi agar indikator dapat dihitung secara konsisten. Workbook sumber belum menyediakan seluruh formula atau sumber datanya.

## 3.1 % Unit Selesai ≤ Target RTW

### Maksud

Mengukur persentase pekerjaan corrective yang selesai dan dinyatakan **Ready to Work** pada atau sebelum target.

### Formula yang Disarankan

```text
RTW On-Time (%) =
Jumlah pekerjaan RTW tepat waktu
÷
Total pekerjaan RTW yang selesai
× 100%
```

### Data Minimum

- unit;
- tanggal/jam down;
- target RTW;
- realisasi RTW;
- status final;
- hasil test function;
- alasan keterlambatan.

### Catatan

RTW sebaiknya final setelah test function, bukan RTW bersyarat tanpa verifikasi operasi.

## 3.2 Rata-rata Downtime per Unit

### Maksud

Mengukur rata-rata durasi unit tidak siap beroperasi akibat kerusakan.

### Formula yang Disarankan

```text
Downtime rata-rata =
Total durasi downtime seluruh kasus
÷
Jumlah kasus/unit yang dihitung
```

Perlu diputuskan apakah pembaginya adalah:

- jumlah kasus breakdown;
- jumlah unit unik; atau
- seluruh populasi unit.

### Batas pada Catatan

- Tidak ada unit down lebih dari 7 hari.
- Tidak ada unit kronis lebih dari 14 hari.
- Dump truck corrective standar disebut 5–7 hari.
- Alat berat menyesuaikan jenis pekerjaan.

## 3.3 Kepatuhan PM Tepat Waktu

### Maksud

Mengukur preventive maintenance yang diselesaikan sebelum atau pada batas HM/KM/tanggal jatuh tempo.

### Formula yang Disarankan

```text
PM On-Time (%) =
PM selesai tepat waktu
÷
Total PM jatuh tempo
× 100%
```

### Target

≥ 95%.

### Bukti

- schedule PM;
- HM/KM aktual;
- tanggal due;
- tanggal realisasi;
- service sheet;
- parts yang digunakan;
- approval completion.

## 3.4 Waktu Respon Awal Kerusakan

### Maksud

Mengukur kecepatan organisasi maintenance memberikan respons pertama sejak kerusakan dilaporkan.

### Formula yang Disarankan

```text
Response Time =
Waktu respons pertama
-
Waktu laporan kerusakan
```

Respons pertama harus didefinisikan, misalnya:

- mekanik ditugaskan;
- unit diperiksa;
- JO diterbitkan; atau
- troubleshooting dimulai.

Target pada workbook adalah ≤ 24 jam.

## 3.5 Keterlambatan karena Spare Part

### Maksud

Mengukur persentase pekerjaan yang terlambat karena parts belum tersedia.

### Formula yang Disarankan

```text
Delay Parts (%) =
Jumlah job terlambat karena parts
÷
Total job maintenance
× 100%
```

### Target

≤ 10%.

Indikator akan lebih kuat apabila juga mengukur:

- jam menunggu parts;
- nilai parts;
- tanggal request;
- tanggal parts tersedia;
- status PR/PO;
- parts readiness sebelum job start.

## 3.6 Keterlambatan karena Manpower

### Maksud

Mengukur pekerjaan terlambat karena mekanik, welder, vendor, operator, atau personel pendukung belum tersedia.

### Formula yang Disarankan

```text
Delay Manpower (%) =
Jumlah job terlambat karena manpower
÷
Total job maintenance
× 100%
```

### Target

≤ 5%.

Perlu dipisahkan dari delay parts agar penyebab downtime tidak tercampur.

## 3.7 Deviasi Biaya Corrective

### Maksud

Membandingkan biaya corrective aktual dengan anggaran atau estimasi yang disetujui.

### Formula yang Disarankan

```text
Deviasi Biaya (%) =
Biaya corrective aktual
÷
Biaya corrective rencana
× 100%
```

Target `≤ 110%` berarti biaya aktual dapat diterima sampai 110% dari rencana, atau maksimal 10% di atas rencana.

Data yang dibutuhkan:

- estimasi awal;
- parts;
- jasa;
- vendor;
- overtime;
- transportasi;
- biaya aktual;
- alasan perubahan scope.

## 3.8 Repeat Breakdown ≤ 30 Hari

### Maksud

Mengukur kerusakan berulang pada unit atau komponen yang sama dalam 30 hari setelah pekerjaan dinyatakan selesai.

### Formula yang Disarankan

```text
Repeat Breakdown (%) =
Jumlah repair yang berulang ≤ 30 hari
÷
Total repair yang selesai
× 100%
```

### Target

≤ 5%.

Perlu ditentukan apakah repeat dihitung berdasarkan:

- unit;
- sistem;
- komponen;
- symptom;
- failure mode; atau
- job order sebelumnya.

## 3.9 Monitoring dan Pelaporan Unit

### Maksud

Menilai konsistensi pengawasan dan penerbitan informasi seperti:

- JO mekanik;
- update breakdown;
- parts status;
- target RTW;
- realisasi RTW;
- service due;
- report harian;
- escalation list.

Karena targetnya `Konsisten`, diperlukan rubrik kuantitatif, misalnya:

| Skor | Contoh Kriteria |
|---:|---|
| 5 | ≥ 98% laporan lengkap dan tepat waktu |
| 4 | 95–97% |
| 3 | 90–94% |
| 2 | 80–89% |
| 1 | < 80% atau laporan tidak dapat diaudit |

## 3.10 Inisiatif Percepatan Perbaikan

### Maksud

Menilai tindakan proaktif Head of Equipment untuk mempercepat readiness unit, antara lain:

- penambahan shift;
- vendor support;
- prioritas parts;
- redistribusi manpower;
- eskalasi teknis;
- alternatif repair;
- keputusan cannibalization terkontrol;
- koordinasi lintas proyek.

Target `Aktif` perlu diubah menjadi rubrik yang dapat dibuktikan melalui daftar aksi, dampak, dan hasil.

---

# 4. Sheet `Rekap Bulanan`

## 4.1 Templat Rekap

| Bulan | Total Nilai KPI | Kategori | Catatan |
|---|---:|---|---|
| Jan |  |  |  |
| Feb |  |  |  |
| Mar |  |  |  |
| Apr |  |  |  |
| Mei |  |  |  |
| Jun |  |  |  |
| Jul |  |  |  |
| Agu |  |  |  |
| Sep |  |  |  |
| Okt |  |  |  |
| Nov |  |  |  |
| Des |  |  |  |

## 4.2 Formula Kategori yang Ditemukan

Hanya sel `C2` yang mempunyai formula:

```excel
=IF(B2="","",IF(B2>=85,"Sangat Baik",IF(B2>=75,"Baik",IF(B2>=65,"Cukup","Perlu Evaluasi"))))
```

Interpretasi formula:

| Nilai | Kategori pada Formula |
|---:|---|
| ≥ 85 | Sangat Baik |
| 75–84 | Baik |
| 65–74 | Cukup |
| < 65 | Perlu Evaluasi |

Catatan:

- Formula hanya berada pada Januari (`C2`).
- Sel `C3:C13` tidak mempunyai formula.
- Agar kategori otomatis untuk seluruh bulan, formula `C2` perlu disalin sampai `C13`.
- Istilah kategori `<65` berbeda dari sheet KPI: formula memakai `Perlu Evaluasi`, sedangkan interpretasi utama memakai `Tidak efektif / perlu evaluasi serius`.

## 4.3 Grafik Tren

Sheet memuat grafik berjudul visual:

**Tren Kinerja Head of Equipment**

Sumber data grafik:

| Elemen | Referensi |
|---|---|
| Nama seri | `Rekap Bulanan!B1` |
| Kategori bulan | `Rekap Bulanan!A2:A13` |
| Nilai KPI | `Rekap Bulanan!B2:B13` |

Karena seluruh nilai bulanan masih kosong, grafik belum menunjukkan tren aktual.

## 4.4 Conditional Formatting

Rentang `B2:B13` menggunakan color scale. Nilai bulanan yang lebih tinggi dan lebih rendah akan dibedakan secara visual ketika data sudah diisi.

---

# 5. Sheet `lampiran laporan`

## 5.1 Struktur Templat

| Tanggal Laporan | Unit / No Lambung | Lokasi | Jenis Kerusakan | Kategori (Teknis / Non-Teknis) | Tanggal & Jam Down | Target RTW | Realisasi RTW | Durasi Down (hari) | Ketepatan Waktu (Tepat / Terlambat) | PIC Mekanik | Status Pekerjaan | Monitoring Head of Equipment | Rekomendasi / Eskalasi |
|---|---|---|---|---|---|---|---|---:|---|---|---|---|---|
|  |  |  |  |  |  |  |  |  |  |  |  |  |  |

Tersedia **24 baris kosong** untuk data, yaitu baris 2–25.

Catatan di baris 26:

> (silakan gunakan template laporan yang sudah biasa digunakan)

## 5.2 Penjelasan Setiap Kolom

| Kolom | Penjelasan |
|---|---|
| Tanggal Laporan | Tanggal update atau tanggal laporan dibuat. |
| Unit / No Lambung | Identitas unit yang mengalami kerusakan. |
| Lokasi | Site, project, yard, pit, atau bengkel. |
| Jenis Kerusakan | Uraian symptom, finding, atau komponen yang rusak. |
| Kategori | Pemisahan kendala teknis dan non-teknis. |
| Tanggal & Jam Down | Awal downtime sebagai dasar pengukuran durasi. |
| Target RTW | Target unit siap beroperasi. |
| Realisasi RTW | Waktu aktual unit dinyatakan siap. |
| Durasi Down | Selisih Realisasi RTW dan waktu down. |
| Ketepatan Waktu | Tepat atau terlambat dibanding target. |
| PIC Mekanik | Mekanik/leader/vendor yang bertanggung jawab. |
| Status Pekerjaan | Open, diagnosis, waiting parts, in progress, testing, atau completed. |
| Monitoring Head of Equipment | Catatan pengawasan, keputusan, atau follow-up. |
| Rekomendasi / Eskalasi | Bantuan, approval, vendor, parts, atau keputusan yang diperlukan. |

## 5.3 Formula yang Belum Tersedia

Workbook belum menghitung secara otomatis:

```text
Durasi Down
Ketepatan Waktu
RTW On-Time
Rata-rata Downtime
Unit down > 7 hari
Unit kronis > 14 hari
```

Formula konseptual yang dapat digunakan:

```text
Durasi Down = Realisasi RTW - Tanggal & Jam Down
```

```text
Ketepatan Waktu =
Jika Realisasi RTW ≤ Target RTW → Tepat
Jika Realisasi RTW > Target RTW → Terlambat
```

Lampiran ini seharusnya menjadi salah satu sumber utama KPI RTW dan downtime, tetapi belum terhubung ke sheet KPI.

---

# 6. Sheet `summary jo dan order parts meka`

## 6.1 Judul

**summary jo order mekanik dan order parts mekanik (/ bulan)**

## 6.2 Format Header

| NO | Nama Mekanik | Tanggal | Membuat JO Mekanik — Ya | Membuat JO Mekanik — Tidak | Jam JO Mekanik dan JO Parts |
|---:|---|---|:---:|:---:|---:|
|  |  |  |  |  |  |

Struktur visual sumber menggunakan merged cells:

- `D2:E2` untuk header `Membuat JO mekanik`;
- `A2:A3`, `B2:B3`, `C2:C3`, dan `F2:F3`;
- subheader `Ya` dan `Tidak` berada pada `D3:E3`.

## 6.3 Maksud Informasi

Sheet tampaknya disiapkan untuk memonitor disiplin mekanik dalam:

- menerbitkan JO mekanik;
- melakukan order parts;
- mencatat tanggal;
- mengidentifikasi mekanik;
- mencatat waktu penerbitan JO dan order parts.

Namun, sheet hanya mempunyai header dan belum menyediakan:

- baris data;
- nomor JO;
- unit;
- kategori pekerjaan;
- waktu laporan kerusakan;
- waktu JO dibuat;
- waktu parts diminta;
- SLA;
- status parts;
- hasil follow-up.

## 6.4 Keterkaitan dengan KPI

Data ini dapat mendukung KPI:

- waktu respon awal kerusakan;
- monitoring dan pelaporan unit;
- keterlambatan karena spare part;
- inisiatif percepatan perbaikan.

Agar dapat dihitung, perlu menyimpan timestamp, bukan hanya tanggal dan keterangan `Ya/Tidak`.

---

# 7. Sheet `excutive summary`

> Nama sheet pada workbook ditulis `excutive summary`, sedangkan judul di dalam sheet adalah `Executive Summary`.

## 7.1 A. Highlight Kinerja Periode Ini

| No. | Highlight |
|---:|---|
| 1 | Ketersediaan unit (Availability): ____% |
| 2 | Utilisasi (Utilization): ____% |
| 3 | Downtime total: ____ jam / ____ hari |
| 4 | Unit Down kritikal: ____ unit (sebutkan kode) |
| 5 | Pencapaian PM on-time: ____% |
| 6 | Corrective selesai sesuai target (RTW on-time): ____% |
| 7 | Biaya maintenance (MTD): Rp ____ |
| 8 | Temuan utama & keputusan yang dibutuhkan: ____ |

## 7.2 B. Masalah Utama & Aksi Cepat

| No. | Masalah | Dampak | Aksi | Target |
|---:|---|---|---|---|
| 1 |  |  |  |  |
| 2 |  |  |  |  |
| 3 |  |  |  |  |
| 4 |  |  |  |  |

## 7.3 Penjelasan Metrik Executive Summary

### Availability

Contoh formula umum:

```text
Availability (%) =
Waktu tersedia
÷
Waktu kalender terjadwal
× 100%
```

Definisi final perlu menentukan apakah yang digunakan adalah PA, MA, atau availability lain.

### Utilization

```text
Utilization (%) =
Waktu unit benar-benar bekerja
÷
Waktu unit tersedia
× 100%
```

### Downtime Total

Akumulasi waktu down seluruh unit dalam periode.

### Unit Down Kritikal

Perlu aturan, misalnya:

- down > 7 hari;
- kronis > 14 hari;
- unit production critical;
- target RTW terlewati;
- parts critical belum tersedia.

### PM On-Time

Menggunakan KPI kepatuhan PM tepat waktu.

### RTW On-Time

Menggunakan persentase pekerjaan selesai pada atau sebelum target.

### Biaya Maintenance MTD

Biaya maintenance bulan berjalan, idealnya dipisahkan menjadi:

- preventive;
- corrective;
- parts;
- jasa/vendor;
- overtime;
- transportasi;
- consumable.

### Temuan dan Keputusan

Berisi isu yang memerlukan keputusan manajemen, bukan hanya laporan aktivitas.

---

# 8. Hubungan Informasi Antar-Sheet

```text
Lampiran Laporan
    │
    ├── Target dan Realisasi RTW
    ├── Durasi Down
    ├── PIC dan Status
    └── Eskalasi
          │
          v
KPI Head of Equipment
    │
    ├── Skor 10 indikator
    ├── Nilai Bobot
    └── Total Nilai
          │
          v
Rekap Bulanan
    │
    ├── Nilai Jan–Des
    ├── Kategori
    └── Grafik Tren
          │
          v
Executive Summary

Summary JO & Order Parts
    │
    ├── Respons penerbitan JO
    ├── Disiplin dokumentasi
    └── Delay parts
          │
          └──────────────> KPI Head of Equipment
```

Hubungan tersebut masih bersifat konseptual. Workbook belum mempunyai formula atau lookup yang menghubungkan sheet-sheet tersebut.

---

# 9. Temuan Penting pada Desain Workbook

## 9.1 Nilai Saat Ini Merupakan Contoh

Kolom `Realisasi` kosong, tetapi skor dan nilai bobot sudah terisi. Dengan demikian, total 54 belum dapat dianggap sebagai hasil evaluasi aktual berbasis data.

## 9.2 Formula Nilai Bobot Tidak Terisi ke Seluruh Baris

Petunjuk menyatakan Nilai Bobot otomatis, tetapi hanya `H5` yang berformula. Baris 6–14 merupakan angka tetap.

Risiko:

- skor diubah tetapi nilai bobot tidak berubah;
- total nilai menjadi tidak konsisten;
- pengguna menganggap seluruh tabel otomatis.

## 9.3 Formula Kategori Bulanan Hanya untuk Januari

Hanya `C2` yang otomatis menentukan kategori. Februari–Desember harus diisi atau disalin secara manual.

## 9.4 Tidak Ada Formula Penghubung KPI ke Rekap

Nilai KPI bulanan tidak otomatis ditarik dari `KPI Head of Equipment`. Pengguna harus memasukkan total secara manual.

## 9.5 Grafik Belum Dinamis terhadap Data Aktual

Grafik telah mengacu ke Januari–Desember, tetapi seluruh nilai kosong. Grafik juga hanya mempunyai satu seri Total Nilai KPI.

## 9.6 Tidak Ada Data Validation

Tidak ada pembatasan untuk:

- skor 1–5;
- kategori teknis/non-teknis;
- tepat/terlambat;
- status pekerjaan;
- Ya/Tidak;
- bulan;
- PIC;
- kategori hasil.

## 9.7 Catatan KPI Tampak Tidak Selalu Sejajar

Beberapa catatan tidak tampak sesuai dengan indikator pada baris yang sama:

| Indikator | Catatan yang Berada pada Baris |
|---|---|
| Kepatuhan PM tepat waktu | Memuat rata-rata downtime dan RTW tepat waktu |
| Waktu respon awal kerusakan | Memuat PM on-time dan breakdown akibat PM gagal |
| Keterlambatan spare part | Memuat penurunan kerugian downtime |
| Keterlambatan manpower | Memuat downtime karena parts unavailable |
| Deviasi biaya corrective | Memuat KPI mekanik dan sanksi/pembinaan |

Kemungkinan catatan bergeser atau mencampurkan beberapa indikator. Konversi tidak memindahkan atau memperbaikinya.

## 9.8 Target Kualitatif Belum Memiliki Rubrik

Indikator `Konsisten` dan `Aktif` memerlukan rubrik 1–5 agar penilaian tidak subjektif.

## 9.9 Definisi Standar Belum Lengkap

Istilah berikut belum didefinisikan secara formal:

- target RTW;
- unit kronis;
- response awal;
- delay parts;
- delay manpower;
- corrective budget;
- repeat breakdown;
- konsisten;
- aktif;
- unit down kritikal.

## 9.10 Tidak Ada Identitas Periode dan Penilai

Sheet KPI belum mempunyai field:

- bulan/periode;
- nama Head of Equipment;
- evaluator;
- tanggal penilaian;
- lokasi/project;
- approval;
- versi/revisi.

## 9.11 Executive Summary Masih Manual

Executive Summary tidak terhubung dengan KPI, lampiran, atau rekap. Semua placeholder harus diisi manual.

---

# 10. Rekomendasi Penyempurnaan Templat

> Bagian ini merupakan rekomendasi dan bukan isi asli workbook.

## 10.1 Perbaiki Formula

- Salin formula `H5` sampai `H14`.
- Pertahankan `H15 = SUM(H5:H14)`.
- Salin formula kategori `C2` sampai `C13`.
- Hubungkan Total Nilai KPI ke bulan aktif.
- Buat Executive Summary mengambil data dari sumber yang sudah diverifikasi.

## 10.2 Tambahkan Validasi

| Field | Validasi |
|---|---|
| Skor | Angka bulat 1–5 |
| Kategori kerusakan | Teknis / Non-Teknis |
| Ketepatan | Tepat / Terlambat |
| Status | Open / Diagnosis / Waiting Parts / In Progress / Test / Completed |
| Membuat JO | Ya / Tidak |
| Bulan | Jan–Des |
| PIC | Daftar mekanik/leader aktif |

## 10.3 Tambahkan Sheet Parameter

Contoh parameter:

| Parameter | Contoh Nilai |
|---|---:|
| Target RTW on-time | 90% |
| PM on-time | 95% |
| Maksimum response | 24 jam |
| Delay parts maksimum | 10% |
| Delay manpower maksimum | 5% |
| Deviasi biaya maksimum | 110% |
| Repeat breakdown maksimum | 5% |
| Unit down kritikal | > 7 hari |
| Unit kronis | > 14 hari |

## 10.4 Gunakan Scoring Berbasis Realisasi

Contoh scoring KPI dengan target semakin tinggi semakin baik:

| Skor | Pencapaian terhadap Target |
|---:|---|
| 5 | Target tercapai atau lebih baik |
| 4 | 95–99% dari target |
| 3 | 90–94% |
| 2 | 80–89% |
| 1 | < 80% |

Untuk KPI yang semakin rendah semakin baik, arah penilaiannya harus dibalik.

## 10.5 Tambahkan Bukti KPI

Setiap skor sebaiknya memiliki:

- sumber data;
- periode;
- numerator;
- denominator;
- nilai realisasi;
- formula;
- bukti/link;
- evaluator;
- catatan pengecualian.

## 10.6 Pisahkan Issue dan Action Tracking

Tambahkan field:

| Field | Fungsi |
|---|---|
| Issue ID | Identitas unik |
| Unit | Unit terkait |
| Masalah | Temuan |
| Root Cause | Penyebab |
| Action | Tindakan |
| PIC | Penanggung jawab |
| Due Date | Target |
| Status | Open/Closed |
| Actual Finish | Selesai aktual |
| Evidence | Foto/JO/report |
| Escalation | Keputusan yang dibutuhkan |

---

# 11. Rekomendasi Struktur Digital

## 11.1 Tabel KPI

| Field | Fungsi |
|---|---|
| kpi_id | Identitas indikator |
| aspect | RTW, Percepatan, Biaya, Kepemimpinan |
| indicator | Nama indikator |
| target_operator | ≥, ≤, atau kualitatif |
| target_value | Nilai target |
| weight | Bobot |
| score | Skor 1–5 |
| weighted_score | Hasil perhitungan |
| period | Bulan/tahun |
| actual_value | Realisasi |
| evidence | Sumber bukti |
| evaluator | Penilai |
| note | Catatan |

## 11.2 Tabel Breakdown

| Field | Fungsi |
|---|---|
| breakdown_id | Identitas kasus |
| unit_id | Unit |
| down_datetime | Awal down |
| target_rtw | Target |
| actual_rtw | Realisasi |
| downtime_hours | Durasi |
| cause_category | Teknis/non-teknis |
| delay_category | Parts/manpower/vendor/operasional |
| pic | PIC |
| work_status | Status |
| test_result | Hasil test |
| monitoring_note | Catatan Head |
| escalation | Rekomendasi |

## 11.3 Tabel PM

| Field | Fungsi |
|---|---|
| pm_id | Identitas PM |
| unit_id | Unit |
| due_meter/date | Jatuh tempo |
| actual_meter/date | Realisasi |
| on_time | Ya/tidak |
| service_sheet | Bukti |
| parts_ready | Status parts |
| result | Hasil |

## 11.4 Tabel JO dan Parts

| Field | Fungsi |
|---|---|
| jo_id | Nomor JO |
| breakdown_id | Referensi kasus |
| mechanic_id | Mekanik |
| report_datetime | Waktu laporan |
| jo_created_datetime | JO diterbitkan |
| parts_requested_datetime | Parts diminta |
| parts_available_datetime | Parts tersedia |
| response_hours | Durasi response |
| parts_delay_hours | Delay parts |
| status | Status |

---

# 12. Alur Penggunaan yang Disarankan

1. Isi data breakdown dan RTW pada `lampiran laporan`.
2. Isi atau impor data PM, biaya, parts, JO, dan repeat breakdown.
3. Hitung realisasi seluruh KPI.
4. Berikan skor 1–5 berdasarkan rubrik yang disetujui.
5. Pastikan Nilai Bobot dihitung dengan formula.
6. Simpan Total Nilai ke bulan terkait pada `Rekap Bulanan`.
7. Periksa kategori dan grafik tren.
8. Susun Executive Summary dari data yang sama.
9. Catat masalah utama, dampak, aksi, PIC, dan target.
10. Lakukan approval dan kunci periode setelah disahkan.

---

# 13. Kesimpulan

Workbook menyediakan kerangka awal yang cukup lengkap untuk menilai Head of Equipment dari empat aspek utama:

- RTW dan downtime;
- percepatan perbaikan;
- biaya dan kualitas; serta
- kepemimpinan.

Bobot indikator sudah berjumlah 100% dan terdapat rekap bulanan, grafik tren, lampiran monitoring, rekap JO/order parts, serta Executive Summary.

Namun, workbook masih membutuhkan penyempurnaan sebelum digunakan sebagai alat evaluasi resmi. Kolom realisasi belum diisi, sebagian besar formula belum diterapkan ke seluruh baris, sheet belum saling terhubung, definisi KPI belum sepenuhnya operasional, dan beberapa catatan tampak tidak sejajar dengan indikatornya.

Nilai 54 pada file merupakan nilai contoh berdasarkan skor yang telah diisi, bukan hasil yang dapat diverifikasi dari data realisasi.
