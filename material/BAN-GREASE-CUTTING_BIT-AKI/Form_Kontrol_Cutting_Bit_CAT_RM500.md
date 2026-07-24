# Form Kontrol Cutting Bit CAT RM500

## Ringkasan Dokumen

File ini merupakan **templat kontrol operasional dan biaya cutting bit untuk Caterpillar RM500**. Workbook belum berisi transaksi harian; isinya berupa struktur siap pakai yang terdiri dari:

| Sheet | Rentang | Fungsi Utama |
|---|---:|---|
| `Parameter` | `A1:D15` | Menyimpan harga, ambang batas pemakaian, target pengembalian, stok minimum, serta definisi status. |
| `Input Harian` | `A1:W204` | Form input harian dengan kapasitas **200 baris transaksi**, perhitungan otomatis, status, validasi daftar, dan pewarnaan kondisi. |
| `Dashboard` | `A1:L48` | Menampilkan KPI, jumlah status, monitoring beberapa baris input, tabel tren, dan grafik pemakaian cutting bit. |
| `Form Cetak` | `A1:H27` | Form serah terima dan penggantian cutting bit yang dirancang untuk diisi atau dicetak. |

> Workbook adalah **templat kosong**. Karena 200 baris pada `Input Harian` belum berisi data operasional, konversi Markdown tidak menuliskan 200 baris kosong satu per satu. Struktur kolom, rumus, aturan, dan format templat dijelaskan secara lengkap di bawah.

---

# 1. Sheet `Parameter`

## 1.1 Struktur Parameter Utama

| Parameter | Nilai | Fungsi |
|---|---:|---|
| Harga Cutting Bit / pcs | Rp 441.441 | Dasar perhitungan biaya harian. |
| Standar maksimum Bit/Hari | 30 | Ambang jumlah cutting bit baru yang dipasang dalam satu hari. |
| Standar maksimum Bit/HM | 3 | Ambang rasio cutting bit terhadap jam kerja. |
| Standar maksimum Bit/1.000 m² | 5 | Ambang rasio cutting bit terhadap hasil produksi. |
| Target pengembalian bit bekas | 100% | Target seluruh bit yang diganti harus dikembalikan. |
| Stok minimum | 100 | Batas minimum stok yang diharapkan. |

## 1.2 Klasifikasi Status

| Status | Kriteria Tertulis | Warna | Tindakan |
|---|---|---|---|
| NORMAL | ≤ standar | Hijau | Lanjutkan operasi dan monitor. |
| WARNING | 101%–120% standar | Kuning | Cek setting, operator, dan kondisi material. |
| OVER LIMIT | >120% standar | Merah | Investigasi dan persetujuan atasan. |
| DATA TIDAK LENGKAP | Jam kerja/produksi kosong | Abu-abu | Lengkapi data sebelum approval. |

## 1.3 Format Visual

- Judul berada pada sel gabungan `A1:D1` dengan latar biru tua dan teks putih.
- Kepala tabel parameter berada pada `A3:B3`.
- Kepala tabel status berada pada `A11:D11`.
- Nilai harga menggunakan format angka ribuan.
- Target pengembalian ditampilkan dalam format persentase.

---

# 2. Sheet `Input Harian`

## 2.1 Format Templat

- Judul: **FORM KONTROL HARIAN CUTTING BIT — CATERPILLAR RM500**.
- Judul digabung pada `A1:W1`.
- Petunjuk pengisian digabung pada `A2:W2`:

> Isi kolom putih setiap hari. Kolom perhitungan dan status akan terisi otomatis. Bit baru yang keluar harus disertai pengembalian bit bekas dan foto bukti.

- Header berada pada baris 4.
- Data tersedia pada baris 5–204, sehingga kapasitas templat adalah **200 transaksi**.
- Area tersebut dibuat sebagai Excel Table bernama **`KontrolCuttingBit`** dengan rentang `A4:W204`.
- Gaya tabel menggunakan baris berselang agar mudah dibaca.
- Kolom perhitungan otomatis diberi latar hijau muda.
- Kolom input menggunakan pola tabel biru muda/putih.

## 2.2 Tabel Kolom

| Kolom | Nama Kolom | Jenis | Isi / Tujuan | Rumus, Validasi, atau Catatan |
|---:|---|---|---|---|
| A | No | Otomatis | Nomor urut transaksi. | `=ROW()-4` |
| B | Tanggal | Input | Tanggal pencatatan/penggantian. | Menjadi pemicu agar status mulai dihitung. |
| C | Shift | Input terkontrol | Shift operasi. | Dropdown: `Shift 1`, `Shift 2`, `Shift 3`. |
| D | Unit | Input terkontrol | Unit yang dikontrol. | Dropdown hanya berisi `CAT RM500`. |
| E | Lokasi/STA | Input | Lokasi proyek atau stationing pekerjaan. | Teks bebas. |
| F | Operator | Input | Nama operator unit. | Teks bebas. |
| G | Mekanik | Input | Nama mekanik yang melakukan pemeriksaan/penggantian. | Teks bebas. |
| H | HM Awal | Input | Hour meter pada awal periode kerja. | Angka. |
| I | HM Akhir | Input | Hour meter pada akhir periode kerja. | Angka. |
| J | Jam Kerja | Otomatis | Selisih HM akhir dan awal. | `=IF(OR(H5="",I5=""),"",MAX(0,I5-H5))` |
| K | Produksi (m²) | Input | Luas produksi harian. | Dipakai untuk menghitung Bit/1.000 m². |
| L | Stok Awal | Input | Stok cutting bit pada awal hari. | Angka. |
| M | Bit Masuk | Input | Tambahan cutting bit yang masuk ke stok. | Angka; kosong dianggap 0 dalam rumus stok. |
| N | Bit Baru Terpasang | Input | Jumlah cutting bit baru yang dipasang. | Dibandingkan dengan standar Bit/Hari. |
| O | Bit Bekas Kembali | Input | Jumlah cutting bit bekas yang dikembalikan. | Dipakai untuk menghitung bit tidak kembali. |
| P | Bit Hilang/Tidak Kembali | Otomatis | Selisih bit baru dan bit bekas yang kembali. | `=IF(OR(N5="",O5=""),"",MAX(0,N5-O5))` |
| Q | Stok Akhir | Otomatis | Stok setelah penerimaan dan pemakaian. | `=IF(L5="","",L5+IF(M5="",0,M5)-IF(N5="",0,N5))` |
| R | Bit/HM | Otomatis | Cutting bit terpasang per jam kerja. | `=IF(OR(J5="",J5=0,N5=""),"",N5/J5)` |
| S | Bit/1.000 m² | Otomatis | Cutting bit terpasang per 1.000 m² produksi. | `=IF(OR(K5="",K5=0,N5=""),"",N5/K5*1000)` |
| T | Biaya/Hari (Rp) | Otomatis | Estimasi biaya cutting bit harian. | `=IF(N5="","",N5*Parameter!$B$4)` |
| U | Status | Otomatis | Klasifikasi NORMAL, WARNING, OVER LIMIT, atau DATA TIDAK LENGKAP. | Menggunakan parameter standar pada sheet `Parameter`. |
| V | Penyebab/Keterangan | Input | Penjelasan penyebab keausan, kondisi material, setting, operator, atau tindakan. | Teks bebas. |
| W | No. Foto/Bukti | Input | Referensi foto atau dokumen bukti. | Teks/nomor referensi. |

> Nomor baris pada rumus di atas menggunakan contoh baris pertama (`5`). Rumus yang sama diterapkan sampai baris `204`.

## 2.3 Templat Baris Markdown

| No | Tanggal | Shift | Unit | Lokasi/STA | Operator | Mekanik | HM Awal | HM Akhir | Jam Kerja | Produksi (m²) | Stok Awal | Bit Masuk | Bit Baru Terpasang | Bit Bekas Kembali | Bit Hilang/Tidak Kembali | Stok Akhir | Bit/HM | Bit/1.000 m² | Biaya/Hari (Rp) | Status | Penyebab/Keterangan | No. Foto/Bukti |
|---:|---|---|---|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|---|---|
| 1 |  | Shift 1/2/3 | CAT RM500 |  |  |  |  |  | otomatis |  |  |  |  |  | otomatis | otomatis | otomatis | otomatis | otomatis | otomatis |  |  |

## 2.4 Logika Perhitungan

### Jam Kerja

```text
Jam Kerja = maksimum dari 0 dan (HM Akhir - HM Awal)
```

Rumus menghindari hasil negatif. Jika HM awal atau HM akhir kosong, hasil juga kosong.

### Bit Hilang/Tidak Kembali

```text
Bit Hilang = maksimum dari 0 dan (Bit Baru Terpasang - Bit Bekas Kembali)
```

Apabila bit bekas kembali lebih banyak daripada bit baru terpasang, hasil tetap 0.

### Stok Akhir

```text
Stok Akhir = Stok Awal + Bit Masuk - Bit Baru Terpasang
```

Bit bekas yang dikembalikan tidak ditambahkan ke stok cutting bit baru.

### Bit/HM

```text
Bit/HM = Bit Baru Terpasang ÷ Jam Kerja
```

### Bit/1.000 m²

```text
Bit/1.000 m² = Bit Baru Terpasang ÷ Produksi × 1.000
```

### Biaya Harian

```text
Biaya/Hari = Bit Baru Terpasang × Harga Cutting Bit per pcs
```

Dengan parameter saat ini:

```text
Biaya/Hari = Bit Baru Terpasang × Rp 441.441
```

## 2.5 Logika Status

Rumus status pada setiap baris mengikuti urutan berikut:

```text
Jika Tanggal kosong:
    Status kosong

Jika Jam Kerja atau Produksi kosong:
    DATA TIDAK LENGKAP

Jika salah satu kondisi berikut terpenuhi:
    Bit Baru Terpasang > 120% × Standar Bit/Hari
    Bit/HM > 120% × Standar Bit/HM
    Bit/1.000 m² > 120% × Standar Bit/1.000 m²
    Bit Hilang/Tidak Kembali > 0
Maka:
    OVER LIMIT

Jika salah satu kondisi berikut terpenuhi:
    Bit Baru Terpasang > Standar Bit/Hari
    Bit/HM > Standar Bit/HM
    Bit/1.000 m² > Standar Bit/1.000 m²
Maka:
    WARNING

Selain itu:
    NORMAL
```

Dengan nilai parameter saat ini:

| Indikator | Normal sampai | Warning | Over Limit |
|---|---:|---:|---:|
| Bit Baru Terpasang per hari | 30 | >30 sampai 36 | >36 |
| Bit/HM | 3 | >3 sampai 3,6 | >3,6 |
| Bit/1.000 m² | 5 | >5 sampai 6 | >6 |
| Bit Hilang/Tidak Kembali | 0 | Tidak ada level warning | Setiap nilai >0 langsung OVER LIMIT |

## 2.6 Conditional Formatting

| Area | Kondisi | Format |
|---|---|---|
| `U5:U204` | Status = NORMAL | Hijau, teks hijau tua tebal. |
| `U5:U204` | Status = WARNING | Kuning, teks cokelat tebal. |
| `U5:U204` | Status = OVER LIMIT | Merah muda, teks merah tua tebal. |
| `U5:U204` | Status = DATA TIDAK LENGKAP | Abu-abu/biru muda, teks abu-abu tebal. |
| `N5:N204` | Bit Baru Terpasang > standar Bit/Hari | Merah. |
| `P5:P204` | Bit Hilang/Tidak Kembali > 0 | Merah. |
| `R5:R204` | Bit/HM > standar | Merah. |
| `S5:S204` | Bit/1.000 m² > standar | Merah. |

---

# 3. Sheet `Dashboard`

## 3.1 Struktur Dashboard

Dashboard dibagi menjadi empat bagian.

### KPI Utama

| KPI | Sumber / Rumus |
|---|---|
| Total Bit Terpakai | `SUM(Input Harian!N5:N204)` |
| Total Biaya | `SUM(Input Harian!T5:T204)` |
| Rata-rata Bit/HM | Rata-rata nilai positif pada `R5:R204`. |
| Jumlah Over Limit | Jumlah status `OVER LIMIT` pada `U5:U204`. |

KPI ditampilkan dalam empat kartu besar:

- `A4:C7` — Total Bit Terpakai
- `D4:F7` — Total Biaya
- `G4:I7` — Rata-rata Bit/HM
- `J4:L7` — Jumlah Over Limit

### Ringkasan Status

| Status | Jumlah Hari |
|---|---:|
| NORMAL | dihitung dari kolom Status |
| WARNING | dihitung dari kolom Status |
| OVER LIMIT | dihitung dari kolom Status |
| DATA TIDAK LENGKAP | dihitung dari kolom Status |

### Monitoring Data Terakhir

Kolom yang ditampilkan:

| Tanggal | Operator | Jam Kerja | Produksi | Bit Terpasang | Bit/HM | Status |
|---|---|---:|---:|---:|---:|---|

Pada file saat ini, area ini mengambil data langsung dari beberapa baris awal `Input Harian`.

### Tren Pemakaian Cutting Bit

Tabel sumber grafik:

| Tanggal | Bit Terpasang | Standar Maksimum |
|---|---:|---:|

Grafik memiliki dua seri:

- **Bit Terpasang** — `Dashboard!B19:B48`
- **Standar Maksimum** — `Dashboard!C19:C48`

Kategori tanggal menggunakan `Dashboard!A19:A48`, sehingga grafik mencakup 30 baris pertama data harian.

## 3.2 Format Visual

- Judul dashboard berada pada `A1:L1`.
- KPI memakai pita biru dan angka besar.
- Ringkasan status berada di sisi kiri.
- Monitoring data berada di sisi kanan.
- Grafik garis berjudul **Tren Pemakaian Cutting Bit per Hari** berada di bagian bawah.
- KPI Bit/HM dan jumlah Over Limit memiliki peringatan merah melalui conditional formatting.
- Status pada tabel monitoring diberi warna hijau, kuning, atau merah.

---

# 4. Sheet `Form Cetak`

## 4.1 Identitas dan Serah Terima

Judul:

**FORM SERAH TERIMA & PENGGANTIAN CUTTING BIT CAT RM500**

| Bagian Kiri | Isian | Bagian Kanan | Isian |
|---|---|---|---|
| Tanggal |  | Shift |  |
| Unit | CAT RM500 | Lokasi/STA |  |
| HM Saat Penggantian |  | Operator |  |
| Mekanik |  | Supervisor |  |
| Jumlah Bit Baru Diambil |  | Bit Bekas Dikembalikan |  |
| Bit Hilang/Tidak Kembali |  | No. Foto/Bukti |  |

## 4.2 Kondisi Cutting Bit yang Diganti

Templat menyediakan empat baris pencatatan.

| No. | Posisi Drum | Aus Normal | Pecah | Macet/Tidak Berputar | Hilang | Jumlah | Keterangan |
|---:|---|---|---|---|---|---:|---|
| 1 |  |  |  |  |  |  |  |
| 2 |  |  |  |  |  |  |  |
| 3 |  |  |  |  |  |  |  |
| 4 |  |  |  |  |  |  |  |

Kolom kondisi dapat digunakan sebagai tanda centang, jumlah, atau kode pemeriksaan, tetapi file tidak menetapkan validasi khusus.

## 4.3 Penyebab / Hasil Pemeriksaan

Bagian `A18:H21` digabung menjadi area teks besar untuk menulis:

- penyebab keausan;
- kondisi material;
- kondisi holder atau drum;
- cutting bit pecah atau macet;
- setting kedalaman;
- pola pengoperasian;
- hasil pemeriksaan mekanik;
- tindakan koreksi; dan
- rekomendasi tindak lanjut.

## 4.4 Pengesahan

| Dibuat oleh | Diperiksa oleh | Disetujui oleh |
|---|---|---|
| Operator/Mekanik | Supervisor | Equipment Manager |
| Nama: | Nama: | Nama: |

Terdapat ruang kosong untuk nama dan tanda tangan.

## 4.5 Format Visual

- Judul digabung pada `A1:H1`.
- Pita bagian **Kondisi Cutting Bit yang Diganti** digabung pada `A10:H10`.
- Pita bagian **Penyebab / Hasil Pemeriksaan** digabung pada `A17:H17`.
- Area pemeriksaan digabung pada `A18:H21`.
- Templat menggunakan garis tabel dan pita biru agar sesuai untuk pencetakan manual.

---

# 5. Alur Penggunaan Templat

1. **Atur parameter**  
   Pastikan harga, standar pemakaian, target pengembalian, dan stok minimum pada sheet `Parameter` sesuai kebijakan terbaru.

2. **Isi transaksi harian**  
   Masukkan tanggal, shift, lokasi, personel, HM, produksi, stok, bit terpasang, bit bekas kembali, keterangan, dan bukti pada `Input Harian`.

3. **Periksa hasil otomatis**  
   Tinjau Jam Kerja, Bit Hilang, Stok Akhir, Bit/HM, Bit/1.000 m², Biaya/Hari, dan Status.

4. **Tindak lanjuti warning**  
   Periksa setting mesin, operator, material, holder, drum, kedalaman, dan pola pemakaian.

5. **Investigasi over limit**  
   Lakukan investigasi dan minta persetujuan atasan, khususnya apabila ada bit yang tidak kembali.

6. **Pantau dashboard**  
   Gunakan KPI dan grafik untuk melihat tren konsumsi dan biaya.

7. **Gunakan Form Cetak**  
   Lengkapi form serah terima setiap pengambilan/penggantian cutting bit dan lampirkan foto.

---

# 6. Hubungan Antar-Sheet

```text
Parameter
   │
   ├── Harga Cutting Bit ───────> Input Harian: Biaya/Hari
   ├── Standar Bit/Hari ────────> Input Harian: Status dan warning
   ├── Standar Bit/HM ──────────> Input Harian: Status dan warning
   └── Standar Bit/1.000 m² ────> Input Harian: Status dan warning

Input Harian
   │
   ├── Total Bit ───────────────> Dashboard
   ├── Total Biaya ─────────────> Dashboard
   ├── Bit/HM ──────────────────> Dashboard
   ├── Status ──────────────────> Dashboard
   └── Tanggal dan bit terpasang > Grafik Dashboard

Form Cetak
   └── Form manual; tidak memiliki formula yang menghubungkannya
       secara otomatis dengan Input Harian.
```

---

# 7. Temuan Penting dan Keterbatasan Templat

Bagian ini menjelaskan perilaku workbook sebagaimana ditemukan, tanpa mengubah file sumber.

## 7.1 Parameter yang Belum Dipakai oleh Rumus

- **Target pengembalian bit bekas (`Parameter!B8`)** tidak direferensikan langsung oleh rumus.
- **Stok minimum (`Parameter!B9`)** tidak dipakai pada formula, status, conditional formatting, atau dashboard.
- Target pengembalian 100% diterapkan secara tidak langsung karena setiap `Bit Hilang/Tidak Kembali > 0` langsung menghasilkan `OVER LIMIT`.

## 7.2 Pemeriksaan Kelengkapan Data Belum Menyeluruh

Status `DATA TIDAK LENGKAP` hanya memeriksa:

- Jam Kerja; dan
- Produksi.

Status tidak memeriksa secara eksplisit apakah kolom berikut sudah diisi:

- Stok Awal;
- Bit Baru Terpasang;
- Bit Bekas Kembali;
- Penyebab/Keterangan;
- No. Foto/Bukti;
- operator; atau
- mekanik.

Akibatnya, suatu baris dapat memperoleh status `NORMAL` walaupun beberapa data kontrol bit belum lengkap, selama tanggal, jam kerja, dan produksi tersedia.

## 7.3 Foto Bukti Belum Menjadi Syarat Otomatis

Petunjuk menyatakan bit baru harus disertai foto bukti, tetapi kolom `No. Foto/Bukti` tidak diperiksa oleh rumus status.

## 7.4 Rumus Membatasi Nilai Negatif Menjadi Nol

- Jika `HM Akhir < HM Awal`, Jam Kerja menjadi 0 dan tidak memberi pesan kesalahan khusus.
- Jika `Bit Bekas Kembali > Bit Baru Terpasang`, Bit Hilang menjadi 0.

Perilaku tersebut menjaga angka tidak negatif, tetapi dapat menyembunyikan salah input.

## 7.5 Stok Akhir Dapat Menjadi Negatif

Rumus Stok Akhir tidak membatasi nilai minimum dan tidak memicu status bila stok berada di bawah 0 atau di bawah parameter Stok Minimum.

## 7.6 Ringkasan Status Dashboard Memiliki Referensi Bergeser

Rumus jumlah status pada dashboard tidak memakai rentang absolut yang sama untuk semua kategori:

- NORMAL menghitung `U5:U204`;
- WARNING menghitung `U6:U205`;
- OVER LIMIT menghitung `U7:U206`;
- DATA TIDAK LENGKAP menghitung `U8:U207`.

Hal ini dapat menyebabkan jumlah status tidak konsisten karena setiap baris memakai rentang yang bergeser.

## 7.7 “Monitoring Data Terakhir” Belum Benar-Benar Memilih Data Terakhir

Area monitoring mengambil baris input secara langsung dan berurutan dari awal tabel. Tidak ada formula pengurutan berdasarkan tanggal atau pencarian transaksi terbaru.

## 7.8 Tampilan Nol pada Dashboard Kosong

Karena sel dashboard mengacu langsung ke baris kosong tanpa pembungkus `IF`, beberapa nilai kosong ditampilkan sebagai:

- `0`; atau
- tanggal `00-Jan-1900`.

Ini merupakan efek tampilan formula pada templat kosong, bukan transaksi nyata.

## 7.9 Grafik Tidak Dinamis terhadap Data Terisi

Grafik memakai 30 baris tetap, yaitu sumber `A19:C48`, yang mereferensikan 30 baris awal `Input Harian`. Grafik belum otomatis memilih 30 transaksi terakhir atau hanya baris yang terisi.

## 7.10 Templat Input Tidak Memiliki Freeze Pane

Sheet `Input Harian` berisi 200 baris dan 23 kolom, tetapi header tidak dibekukan. Saat pengguna menggulir jauh ke bawah atau ke kanan, nama kolom dapat tidak terlihat.

## 7.11 Form Cetak Berdiri Sendiri

`Form Cetak` tidak mengambil data otomatis dari baris yang dipilih pada `Input Harian`. Pengisian perlu dilakukan kembali secara manual.

## 7.12 Pengaturan Cetak Eksplisit Tidak Ditemukan

Walaupun `Form Cetak` dirancang sebagai form cetak, file tidak memiliki pengaturan page setup atau print area eksplisit yang terlihat pada struktur workbook. Hasil cetak dapat bergantung pada pengaturan aplikasi Excel pengguna.

---

# 8. Rekomendasi Pengembangan Lanjutan

Rekomendasi berikut bukan isi asli file, tetapi pengembangan yang logis berdasarkan struktur templat.

1. Gunakan parameter **Stok Minimum** untuk membuat status stok dan peringatan dashboard.
2. Gunakan parameter **Target Pengembalian** langsung dalam rumus, agar target dapat diubah dari 100%.
3. Perluas validasi `DATA TIDAK LENGKAP` ke kolom bit, stok, personel, dan foto.
4. Tambahkan validasi bahwa HM akhir tidak boleh lebih kecil dari HM awal.
5. Tambahkan validasi stok agar tidak negatif.
6. Perbaiki rentang `COUNTIF` dashboard agar seluruh status memakai `U5:U204`.
7. Buat monitoring terbaru menggunakan pengurutan tanggal atau fungsi lookup.
8. Buat grafik dinamis berdasarkan baris yang telah diisi.
9. Bekukan baris header dan kolom identitas pada `Input Harian`.
10. Hubungkan `Form Cetak` dengan transaksi terpilih dari `Input Harian`.
11. Tambahkan kolom approval, tanggal approval, dan nama approver.
12. Simpan histori harga cutting bit apabila harga berubah dari waktu ke waktu.

---

# 9. Kesimpulan

Templat ini telah memiliki fondasi yang baik untuk:

- pencatatan pemakaian cutting bit harian;
- kontrol pengembalian bit bekas;
- perhitungan stok dan biaya;
- pengukuran konsumsi terhadap jam kerja dan produksi;
- klasifikasi status otomatis;
- monitoring melalui dashboard; dan
- dokumentasi serah terima melalui form cetak.

Fokus utama templat adalah mendeteksi pemakaian yang melebihi standar dan kehilangan cutting bit. Namun beberapa parameter dan persyaratan administratif belum terhubung ke rumus, serta sejumlah formula dashboard masih memerlukan penyempurnaan agar hasil monitoring akurat dan dinamis.
