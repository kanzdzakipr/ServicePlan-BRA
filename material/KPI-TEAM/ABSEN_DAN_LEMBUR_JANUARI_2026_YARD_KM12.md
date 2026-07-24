# ABSEN DAN LEMBUR JANUARI 2026 — YARD KM12

Dokumen ini merupakan konversi terstruktur dari workbook **`ABSEN DAN LEMBUR JANUARI 2026 YARD KM12.xlsx`**. Sel gabungan, warna, border, kolom tersembunyi, dan blok empat baris pada Excel diratakan menjadi tabel Markdown agar mudah dibaca, ditelusuri, dan diproses lebih lanjut. Nilai sumber dipertahankan; setiap koreksi atau hasil audit dipisahkan secara eksplisit dari angka yang tersimpan di workbook.

## 1. Struktur workbook

| Sheet | Status | Periode/fungsi | Ukuran | Jumlah formula | Merged range |
| :--- | :--- | :--- | ---: | ---: | ---: |
| Sheet1 | Tersembunyi | Label sumber: 26 Mei–10 Juni 2025; kolom yang terisi hanya 26 Mei–1 Juni 2025 | 35 × 10 | 0 | 0 |
| Lap. Log Absen | Tersembunyi | 26 Mei–20 Juni 2025 | 66 × 31 | 0 | 2 |
| Sheet2 | Terlihat | 21 Desember 2025–20 Januari 2026 | 57 × 33 | 0 | 2 |
| YARD | Terlihat | 21 Desember 2025–20 Januari 2026 | 115 × 63 | 298 | 291 |
| LAPANGAN | Terlihat | 21 Desember 2025–20 Januari 2026 | 47 × 57 | 83 | 86 |

Tiga sheet terlihat adalah `Sheet2`, `YARD`, dan `LAPANGAN`. Dua sheet tersembunyi (`Sheet1` dan `Lap. Log Absen`) berisi data historis Mei–Juni 2025, sehingga tidak boleh dicampur langsung dengan rekap Januari 2026 tanpa penandaan periode.

## 2. Ringkasan utama

| Lokasi | Personel | Cakupan formula ringkasan | Status valid pada ringkasan sumber | Status kosong (31 hari) | Kode tidak dikenal | Lembur sumber | Lembur audit 31 hari |
| :--- | ---: | :--- | ---: | ---: | :--- | ---: | ---: |
| YARD | 22 | Q:AT (30 hari; 20 Jan tidak masuk formula) | 629 | 32 | K=1 | 1020,5 | 1056 |
| LAPANGAN | 5 | Q:AU (31 hari) | 154 | 1 | — | 341 | 341 |
| Gabungan | 27 | Campuran; lihat catatan | 783 | 33 | K=1 | 1361,5 | 1397 |

Rekap sumber menampilkan **1.361,5 jam lembur**. Setelah tanggal 20 Januari pada sheet `YARD` ikut dijumlahkan, total menjadi **1.397 jam**, atau bertambah **35,5 jam**. Angka 1.397 jam masih mengikuti seluruh nilai yang tersimpan, termasuk satu formula dengan operator minus ganda yang dijelaskan pada bagian temuan kualitas data.

### Distribusi kode status

| Kode | Arti | YARD sumber | YARD 31 hari | LAPANGAN sumber | LAPANGAN 31 hari | Gabungan 31 hari |
| :---: | :--- | ---: | ---: | ---: | ---: | ---: |
| KJ | Kerja | 417 | 436 | 105 | 105 | 541 |
| KL | Kerja di hari libur | 46 | 46 | 16 | 16 | 62 |
| O | Off / libur | 90 | 91 | 27 | 27 | 118 |
| CT | Cuti | 28 | 28 | 1 | 1 | 29 |
| S | Sakit | 0 | 0 | 0 | 0 | 0 |
| I | Izin tidak dibayar / izin pribadi | 48 | 48 | 5 | 5 | 53 |
| MK | Mangkir / alpa / tidak masuk tanpa keterangan | 0 | 0 | 0 | 0 | 0 |
| PR | Prorate / gaji tidak penuh | 0 | 0 | 0 | 0 | 0 |

### Personel dengan total lembur tertinggi

| Peringkat | Lokasi | Nama | Posisi | Lembur sumber | Lembur 31 hari | Selisih |
| ---: | :--- | :--- | :--- | ---: | ---: | ---: |
| 1 | YARD | SUWARDI | Welder | 126 | 129 | 3 |
| 2 | YARD | TAUFIQ H | Security | 120 | 124 | 4 |
| 3 | YARD | HENDRIK | Teknisi Listrik | 115,5 | 118,5 | 3 |
| 4 | YARD | JORLAN SIBATUARA | Koordinator Security | 116 | 116 | 0 |
| 5 | YARD | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 112,5 | 115,5 | 3 |
| 6 | YARD | AFRIYANDI | Mekanik | 85 | 88 | 3 |
| 7 | LAPANGAN | SOLEH AL MUZAKAR | Welder | 75,5 | 75,5 | 0 |
| 8 | LAPANGAN | JONI SEPTIAN | Mekanik | 74,5 | 74,5 | 0 |
| 9 | YARD | DARMAWAN | Mekanik | 68 | 70,5 | 2,5 |
| 10 | LAPANGAN | DANIEL SITEPU | Mekanik | 68 | 68 | 0 |
| 11 | YARD | WAGIMAN BARUTU | MEKANIK | 60,5 | 64 | 3,5 |
| 12 | YARD | SUDIRMAN H | Security | 60 | 64 | 4 |
| 13 | YARD | EDY JAKA S | Security | 60 | 64 | 4 |
| 14 | LAPANGAN | REJEKI SIREGAR | Mekanik | 63 | 63 | 0 |
| 15 | LAPANGAN | URWATUL USKA | Helper Mekanik | 60 | 60 | 0 |

### Total lembur per tanggal

| Tanggal | Hari | YARD | LAPANGAN | Gabungan |
| :---: | :--- | ---: | ---: | ---: |
| 2025-12-21 | Minggu | 40,5 | 23,5 | 64 |
| 2025-12-22 | Senin | 49,5 | 23,5 | 73 |
| 2025-12-23 | Selasa | 55,5 | 27,5 | 83 |
| 2025-12-24 | Rabu | 35 | 18 | 53 |
| 2025-12-25 | Kamis | 30 | 4 | 34 |
| 2025-12-26 | Jumat | 40,5 | 13,5 | 54 |
| 2025-12-27 | Sabtu | 58 | 21,5 | 79,5 |
| 2025-12-28 | Minggu | 12 | 0 | 12 |
| 2025-12-29 | Senin | 43,5 | 20,5 | 64 |
| 2025-12-30 | Selasa | 41 | 17 | 58 |
| 2025-12-31 | Rabu | 20 | 0 | 20 |
| 2026-01-01 | Kamis | 12 | 0 | 12 |
| 2026-01-02 | Jumat | 27,5 | 3,5 | 31 |
| 2026-01-03 | Sabtu | 12 | 0 | 12 |
| 2026-01-04 | Minggu | 4 | 0 | 4 |
| 2026-01-05 | Senin | 19,5 | 0 | 19,5 |
| 2026-01-06 | Selasa | 46,5 | 2 | 48,5 |
| 2026-01-07 | Rabu | 45 | 13,5 | 58,5 |
| 2026-01-08 | Kamis | 43 | 15,5 | 58,5 |
| 2026-01-09 | Jumat | 12 | 0 | 12 |
| 2026-01-10 | Sabtu | 43,5 | 28 | 71,5 |
| 2026-01-11 | Minggu | 22 | 0 | 22 |
| 2026-01-12 | Senin | 32,5 | 14 | 46,5 |
| 2026-01-13 | Selasa | 54 | 13 | 67 |
| 2026-01-14 | Rabu | 49 | 12 | 61 |
| 2026-01-15 | Kamis | 29 | 11,5 | 40,5 |
| 2026-01-16 | Jumat | 12 | 0 | 12 |
| 2026-01-17 | Sabtu | 55 | 36,5 | 91,5 |
| 2026-01-18 | Minggu | 31,5 | 0 | 31,5 |
| 2026-01-19 | Senin | 45 | 13 | 58 |
| 2026-01-20 | Selasa | 35,5 | 9,5 | 45 |

### Rekap lembur menurut posisi

| Lokasi | Posisi | Jumlah personel | Lembur 31 hari |
| :--- | :--- | ---: | ---: |
| YARD | Security | 3 | 252 |
| LAPANGAN | Mekanik | 3 | 205,5 |
| YARD | Mekanik | 2 | 158,5 |
| YARD | Welder | 1 | 129 |
| YARD | Teknisi Listrik | 1 | 118,5 |
| YARD | Koordinator Security | 1 | 116 |
| YARD | HELPER MEKANIK | 1 | 115,5 |
| LAPANGAN | Welder | 1 | 75,5 |
| YARD | MEKANIK | 1 | 64 |
| LAPANGAN | Helper Mekanik | 1 | 60 |
| YARD | Siswa Magang | 3 | 51,5 |
| YARD | Warehouse | 1 | 23,5 |
| YARD | Admin Logistik | 1 | 15,5 |
| YARD | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 1 | 5 |
| YARD | Project Control | 1 | 4 |
| YARD | Operator Road Cold Recycler XCMG XLZ2303K | 1 | 3 |
| YARD | ADMIN | 1 | 0 |
| YARD | Flag Man | 1 | 0 |
| YARD | Petugas K3L | 2 | 0 |

## 3. Cara membaca format dan perhitungan

Pada sheet `YARD` dan `LAPANGAN`, satu karyawan menempati empat baris. Baris `In` menyimpan jam datang, `Out` jam pulang, `Absen` kode kehadiran, dan `Lembur` jumlah jam lembur. Kolom tanggal berjalan dari 21 Desember 2025 sampai 20 Januari 2026. Hari Minggu diberi latar merah dan hari Sabtu diberi arsiran pada Excel; dalam Markdown, pembeda visual itu diganti dengan kolom `Hari`.

Kolom kanan `KJ`, `KL`, `O`, `CT`, `S`, `I`, `MK`, dan `PR` adalah penghitung status. Formula umumnya berbentuk `=COUNTIF(Q15:AU15,$AW$10)`: rentang status harian dihitung berdasarkan kode pada header. Kolom `∑ Total Lembur` menggunakan `SUM` atas baris lembur harian.

| Contoh | Makna |
| :--- | :--- |
| `=COUNTIF(Q15:AU15,$AW$10)` | Menghitung berapa kali kode pada header `AW10` (KJ) muncul pada baris status. |
| `=SUM(Q16:AU16)` | Menjumlahkan jam lembur harian satu karyawan. |
| `=21-0.5-16` | 21:00 dikurangi istirahat 0,5 jam dan batas jam normal 16:00; hasil 4,5 jam. |
| `=22-0.5-17` | 22:00 dikurangi istirahat 0,5 jam dan batas jam normal 17:00; hasil 4,5 jam. |
| `=22-0.5-13` | Pola perhitungan hari Sabtu: 22:00 dikurangi 0,5 jam dan batas normal 13:00; hasil 8,5 jam. |
| Nilai langsung, misalnya `4` | Jam lembur diketik langsung tanpa formula. |

Jumlah formula pada sheet `YARD` adalah **298**, sedangkan pada `LAPANGAN` **83**. Tidak terdapat data validation atau dropdown pada workbook; kode status dan angka lembur dapat diketik manual, sehingga konsistensinya bergantung pada pemeriksaan pengguna.

## 4. Temuan kualitas data dan hal yang perlu diverifikasi

| Temuan | Penjelasan/dampak |
| :--- | :--- |
| Formula `YARD` tidak mencakup 20 Januari | Formula status dan total lembur berakhir di kolom `AT` (19 Januari), padahal kolom `AU` berisi 20 Januari. Dampaknya: 19 status KJ, 1 status O, dan 35,5 jam lembur pada 20 Januari tidak masuk ringkasan sumber. |
| Nomor urut ganda | Sel `B53` memakai `=B45+1`, bukan melanjutkan dari `B49`; akibatnya Arif Randy dan Wagiman Barutu sama-sama bernomor 10, dan 22 orang berakhir pada nomor 21. |
| Formula minus ganda | Sel `AR100` memakai `=23--0.5-13`, yang secara matematis menghasilkan 10,5. Jika maksudnya `=23-0.5-13`, hasilnya 9,5; selisih 1 jam perlu dikonfirmasi. |
| Kode status tidak baku | Kuswanto pada 30 Desember 2025 memakai kode `K`, bukan salah satu kode legenda. |
| Status kosong | Terdapat 32 sel status kosong di YARD dan 1 di LAPANGAN untuk periode 31 hari. Sel kosong tidak otomatis berarti mangkir. |
| Jam kerja tidak lengkap | Pada hari berkode KJ/KL terdapat 3 jam masuk kosong dan 16 jam pulang kosong. |
| Lembur pada status non-kerja | Ditemukan dua baris: Fajar Dwi Chandra pada status CT dengan 1,5 jam, dan Darmawan pada status O dengan 2,5 jam. Keduanya perlu dikonfirmasi sebagai kerja parsial atau kesalahan status. |
| Tanggal pengesahan LAPANGAN | Tertulis 27 Desember 2025, sedangkan periode berakhir 20 Januari 2026. Kemungkinan merupakan tanggal template atau belum diperbarui. |
| Data raw bercampur dan berulang | Sheet2 menyimpan beberapa scan dalam satu sel, termasuk scan duplikat, nilai tunggal, waktu 00:13, simbol `\\`, dan sel kosong. Rekonsiliasi otomatis jam masuk/keluar memerlukan aturan tambahan. |
| Sheet historis tersembunyi | Sheet1 dan Lap. Log Absen berasal dari Mei–Juni 2025. Keduanya dipertahankan sebagai lampiran, tetapi tidak digunakan untuk ringkasan Januari 2026. |

### Jam In/Out yang kosong pada hari berkode KJ/KL

| Lokasi | Nama | Tanggal | Status | Kolom kosong | In | Out | Lembur |
| :--- | :--- | :---: | :---: | :--- | :---: | :---: | ---: |
| YARD | DEAN MARTIN | 2025-12-29 | KJ | Out | 07:32 | — | — |
| YARD | DEAN MARTIN | 2026-01-08 | KJ | Out | 07:41 | — | — |
| YARD | DEAN MARTIN | 2026-01-16 | KJ | Out | 07:52 | — | — |
| YARD | FAJAR DWI CHANDRA | 2025-12-29 | KJ | Out | 07:50 | — | — |
| YARD | FAJAR DWI CHANDRA | 2026-01-08 | KJ | Out | 07:45 | — | — |
| YARD | MARTONO | 2026-01-16 | KL | Out | 08:09 | — | — |
| YARD | AFRIYANDI | 2025-12-27 | KJ | Out | 07:43 | — | — |
| YARD | AFRIYANDI | 2025-12-28 | KL | In, Out | — | — | — |
| YARD | AFRIYANDI | 2025-12-29 | KJ | In, Out | — | — | — |
| YARD | AFRIYANDI | 2025-12-30 | KJ | In, Out | — | — | — |
| YARD | SUWARDI | 2025-12-29 | KJ | Out | 07:44 | — | 5,5 |
| YARD | EDY JAKA S | 2026-01-04 | KJ | Out | 07:00 | — | — |
| YARD | AKBAR MAULANA | 2025-12-27 | KJ | Out | 07:41 | — | — |
| LAPANGAN | DANIEL SITEPU | 2025-12-24 | KJ | Out | 07:53 | — | — |
| LAPANGAN | URWATUL USKA | 2025-12-31 | KJ | Out | 08:18 | — | — |
| LAPANGAN | URWATUL USKA | 2026-01-02 | KJ | Out | 08:00 | — | — |

### Status kosong

| Lokasi | Nama | Tanggal | Hari | In | Out | Lembur |
| :--- | :--- | :---: | :--- | :---: | :---: | ---: |
| YARD | SAMSUL BAHRI | 2025-12-28 | Minggu | — | — | — |
| YARD | FAJAR DWI CHANDRA | 2025-12-28 | Minggu | — | — | — |
| YARD | FAJAR DWI CHANDRA | 2026-01-11 | Minggu | — | — | — |
| YARD | AFRIYANDI | 2026-01-17 | Sabtu | — | — | — |
| YARD | ARIF RANDY | 2025-12-28 | Minggu | — | — | — |
| YARD | AKBAR MAULANA | 2025-12-21 | Minggu | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-10 | Sabtu | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-11 | Minggu | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-12 | Senin | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-13 | Selasa | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-14 | Rabu | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-15 | Kamis | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-16 | Jumat | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-17 | Sabtu | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-18 | Minggu | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-19 | Senin | — | — | — |
| YARD | AKBAR MAULANA | 2026-01-20 | Selasa | — | — | — |
| YARD | ALEX PAULUS | 2026-01-10 | Sabtu | — | — | — |
| YARD | ALEX PAULUS | 2026-01-11 | Minggu | — | — | — |
| YARD | ALEX PAULUS | 2026-01-12 | Senin | — | — | — |
| YARD | ALEX PAULUS | 2026-01-13 | Selasa | — | — | — |
| YARD | ALEX PAULUS | 2026-01-14 | Rabu | — | — | — |
| YARD | ALEX PAULUS | 2026-01-15 | Kamis | — | — | — |
| YARD | ALEX PAULUS | 2026-01-16 | Jumat | — | — | — |
| YARD | ALEX PAULUS | 2026-01-17 | Sabtu | — | — | — |
| YARD | ALEX PAULUS | 2026-01-18 | Minggu | — | — | — |
| YARD | ALEX PAULUS | 2026-01-19 | Senin | — | — | — |
| YARD | ALEX PAULUS | 2026-01-20 | Selasa | — | — | — |
| YARD | GABRIEL MARBUN | 2025-12-21 | Minggu | — | — | — |
| YARD | GABRIEL MARBUN | 2026-01-10 | Sabtu | — | — | — |
| YARD | GABRIEL MARBUN | 2026-01-11 | Minggu | — | — | — |
| YARD | GABRIEL MARBUN | 2026-01-16 | Jumat | — | — | — |
| LAPANGAN | JONI SEPTIAN | 2026-01-11 | Minggu | — | — | — |

### Kode status di luar legenda

| Lokasi | Nama | Tanggal | Kode | In | Out | Lembur |
| :--- | :--- | :---: | :---: | :---: | :---: | ---: |
| YARD | KUSWANTO | 2025-12-30 | K | 07:33 | 15:58 | — |

### Lembur tercatat pada status selain KJ/KL

| Lokasi | Nama | Tanggal | Status | Lembur | Sumber |
| :--- | :--- | :---: | :---: | ---: | :--- |
| YARD | FAJAR DWI CHANDRA | 2025-12-26 | CT | 1,5 | `=19-0.5-17` |
| YARD | DARMAWAN | 2026-01-15 | O | 2,5 | Nilai langsung: `2.5` |

## 5. Sheet `YARD` — Rekapitulasi Absen Yard KM 12 Kulim

Periode: **21 Desember 2025–20 Januari 2026**. Tabel asli menyusun setiap orang dalam empat baris: `In`, `Out`, `Absen`, dan `Lembur`. Dalam Markdown, struktur itu diratakan menjadi satu baris per orang per tanggal.

### Ringkasan sebagaimana tersimpan di workbook

| No. normal | No. sumber | Kode C | Nama | Posisi | KJ | KL | O | CT | S | I | MK | PR | Total lembur |
| ---: | ---: | ---: | :--- | :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 1 | 1 | 2 | DEAN MARTIN | ADMIN | 22 | 2 | 6 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2 | 2 | 15 | SAMSUL BAHRI | Project Control | 21 | 0 | 6 | 2 | 0 | 0 | 0 | 0 | 4 |
| 3 | 3 | — | FAJAR DWI CHANDRA | Admin Logistik | 20 | 1 | 4 | 2 | 0 | 1 | 0 | 0 | 15,5 |
| 4 | 4 | 14 | MARTONO | Warehouse | 19 | 7 | 0 | 4 | 0 | 0 | 0 | 0 | 23,5 |
| 5 | 5 | — | AFRIYANDI | Mekanik | 20 | 6 | 3 | 0 | 0 | 0 | 0 | 0 | 85 |
| 6 | 6 | — | DARMAWAN | Mekanik | 19 | 3 | 6 | 1 | 0 | 1 | 0 | 0 | 68 |
| 7 | 7 | 1 | SUWARDI | Welder | 21 | 7 | 2 | 0 | 0 | 0 | 0 | 0 | 126 |
| 8 | 8 | 16 | HENDRIK | Teknisi Listrik | 20 | 5 | 3 | 0 | 0 | 2 | 0 | 0 | 115,5 |
| 9 | 9 | 16 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 16 | 2 | 6 | 1 | 0 | 5 | 0 | 0 | 3 |
| 10 | 10 | 16 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 21 | 1 | 6 | 1 | 0 | 0 | 0 | 0 | 3 |
| 11 | 10 | 16 | WAGIMAN BARUTU | MEKANIK | 19 | 3 | 6 | 0 | 0 | 2 | 0 | 0 | 60,5 |
| 12 | 11 | 16 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 21 | 7 | 2 | 0 | 0 | 0 | 0 | 0 | 112,5 |
| 13 | 12 | — | AMIRUDIN | Petugas K3L | 20 | 0 | 0 | 2 | 0 | 8 | 0 | 0 | 0 |
| 14 | 13 | — | KUSWANTO | Petugas K3L | 23 | 0 | 0 | 1 | 0 | 5 | 0 | 0 | 0 |
| 15 | 14 | — | DAVID ARITONANG | Flag Man | 22 | 0 | 7 | 1 | 0 | 0 | 0 | 0 | 0 |
| 16 | 15 | — | JORLAN SIBATUARA | Koordinator Security | 29 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 116 |
| 17 | 16 | — | SUDIRMAN H | Security | 15 | 0 | 8 | 7 | 0 | 0 | 0 | 0 | 60 |
| 18 | 17 | — | EDY JAKA S | Security | 16 | 0 | 12 | 2 | 0 | 0 | 0 | 0 | 60 |
| 19 | 18 | — | TAUFIQ H | Security | 30 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 120 |
| 20 | 19 | — | AKBAR MAULANA | Siswa Magang | 11 | 0 | 4 | 2 | 0 | 2 | 0 | 0 | 16,5 |
| 21 | 20 | — | ALEX PAULUS | Siswa Magang | 4 | 0 | 3 | 2 | 0 | 11 | 0 | 0 | 0 |
| 22 | 21 | — | GABRIEL MARBUN | Siswa Magang | 8 | 2 | 5 | 0 | 0 | 11 | 0 | 0 | 31,5 |

> Pada sheet `YARD`, ringkasan sumber menggunakan rentang `Q:AT`, sehingga tanggal 20 Januari pada kolom `AU` tidak ikut dihitung. Angka di atas adalah hasil yang tersimpan di workbook, bukan hasil koreksi.

### Audit perhitungan untuk seluruh 31 hari

| No. | Nama | KJ | KL | O | CT | S | I | MK | PR | Status kosong | Kode lain | Lembur 31 hari | Selisih vs sumber |
| ---: | :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | :--- | ---: | ---: |
| 1 | DEAN MARTIN | 23 | 2 | 6 | 0 | 0 | 0 | 0 | 0 | 0 | — | 0 | 0 |
| 2 | SAMSUL BAHRI | 22 | 0 | 6 | 2 | 0 | 0 | 0 | 0 | 1 | — | 4 | 0 |
| 3 | FAJAR DWI CHANDRA | 21 | 1 | 4 | 2 | 0 | 1 | 0 | 0 | 2 | — | 15,5 | 0 |
| 4 | MARTONO | 20 | 7 | 0 | 4 | 0 | 0 | 0 | 0 | 0 | — | 23,5 | 0 |
| 5 | AFRIYANDI | 21 | 6 | 3 | 0 | 0 | 0 | 0 | 0 | 1 | — | 88 | 3 |
| 6 | DARMAWAN | 20 | 3 | 6 | 1 | 0 | 1 | 0 | 0 | 0 | — | 70,5 | 2,5 |
| 7 | SUWARDI | 22 | 7 | 2 | 0 | 0 | 0 | 0 | 0 | 0 | — | 129 | 3 |
| 8 | HENDRIK | 21 | 5 | 3 | 0 | 0 | 2 | 0 | 0 | 0 | — | 118,5 | 3 |
| 9 | FAIKAR IZZANI FATTAAH | 17 | 2 | 6 | 1 | 0 | 5 | 0 | 0 | 0 | — | 3 | 0 |
| 10 | ARIF RANDY | 22 | 1 | 6 | 1 | 0 | 0 | 0 | 0 | 1 | — | 5 | 2 |
| 11 | WAGIMAN BARUTU | 20 | 3 | 6 | 0 | 0 | 2 | 0 | 0 | 0 | — | 64 | 3,5 |
| 12 | FIRLANDA DOLOK SARIBU | 22 | 7 | 2 | 0 | 0 | 0 | 0 | 0 | 0 | — | 115,5 | 3 |
| 13 | AMIRUDIN | 21 | 0 | 0 | 2 | 0 | 8 | 0 | 0 | 0 | — | 0 | 0 |
| 14 | KUSWANTO | 24 | 0 | 0 | 1 | 0 | 5 | 0 | 0 | 0 | K=1 | 0 | 0 |
| 15 | DAVID ARITONANG | 23 | 0 | 7 | 1 | 0 | 0 | 0 | 0 | 0 | — | 0 | 0 |
| 16 | JORLAN SIBATUARA | 29 | 0 | 2 | 0 | 0 | 0 | 0 | 0 | 0 | — | 116 | 0 |
| 17 | SUDIRMAN H | 16 | 0 | 8 | 7 | 0 | 0 | 0 | 0 | 0 | — | 64 | 4 |
| 18 | EDY JAKA S | 17 | 0 | 12 | 2 | 0 | 0 | 0 | 0 | 0 | — | 64 | 4 |
| 19 | TAUFIQ H | 31 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — | 124 | 4 |
| 20 | AKBAR MAULANA | 11 | 0 | 4 | 2 | 0 | 2 | 0 | 0 | 12 | — | 16,5 | 0 |
| 21 | ALEX PAULUS | 4 | 0 | 3 | 2 | 0 | 11 | 0 | 0 | 11 | — | 0 | 0 |
| 22 | GABRIEL MARBUN | 9 | 2 | 5 | 0 | 0 | 11 | 0 | 0 | 4 | — | 35 | 3,5 |

### Data harian yang dinormalisasi

| No. | Nama | Posisi | Tanggal | Hari | In | Out | Status | Lembur (jam) | Sumber lembur |
| ---: | :--- | :--- | :---: | :--- | :---: | :---: | :---: | ---: | :--- |
| 1 | DEAN MARTIN | ADMIN | 2025-12-21 | Minggu | — | — | O | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-22 | Senin | 07:43 | 19:07 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-23 | Selasa | 07:35 | 18:42 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-24 | Rabu | 07:39 | 18:21 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-25 | Kamis | 08:17 | 21:36 | KL | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-26 | Jumat | 08:50 | 22:19 | KL | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-27 | Sabtu | 07:54 | 16:13 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-28 | Minggu | — | — | O | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-29 | Senin | 07:32 | — | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-30 | Selasa | 07:48 | 18:06 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2025-12-31 | Rabu | 07:38 | 18:25 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-01 | Kamis | — | 14:12 | O | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-02 | Jumat | 07:55 | 17:42 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-03 | Sabtu | 07:26 | 19:18 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-04 | Minggu | — | — | O | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-05 | Senin | 08:08 | 19:23 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-06 | Selasa | 07:36 | 18:42 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-07 | Rabu | 07:42 | 18:55 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-08 | Kamis | 07:41 | — | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-09 | Jumat | 07:54 | 07:54 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-10 | Sabtu | 07:43 | 15:36 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-11 | Minggu | — | — | O | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-12 | Senin | 07:55 | 19:34 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-13 | Selasa | 07:47 | 18:30 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-14 | Rabu | 08:01 | 18:50 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-15 | Kamis | 07:47 | 17:46 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-16 | Jumat | 07:52 | — | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-17 | Sabtu | 08:02 | 17:55 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-18 | Minggu | — | — | O | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-19 | Senin | 08:12 | 19:54 | KJ | — | — |
| 1 | DEAN MARTIN | ADMIN | 2026-01-20 | Selasa | 07:47 | 18:30 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-21 | Minggu | — | — | O | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-22 | Senin | 07:19 | 17:36 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-23 | Selasa | 07:25 | 18:26 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-24 | Rabu | 07:24 | 18:16 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-25 | Kamis | — | — | CT | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-26 | Jumat | — | — | CT | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-27 | Sabtu | 07:18 | 18:10 | KJ | 4 | `=18-14` |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-28 | Minggu | — | — | — | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-29 | Senin | 07:23 | 18:17 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-30 | Selasa | 07:21 | 18:11 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2025-12-31 | Rabu | 07:21 | 17:52 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-01 | Kamis | — | — | O | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-02 | Jumat | 07:21 | 17:39 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-03 | Sabtu | 07:27 | 14:23 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-04 | Minggu | — | — | O | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-05 | Senin | 07:26 | 17:35 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-06 | Selasa | 07:27 | 17:21 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-07 | Rabu | 07:36 | 16:49 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-08 | Kamis | 07:38 | 17:38 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-09 | Jumat | 07:30 | 17:27 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-10 | Sabtu | 07:22 | 15:56 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-11 | Minggu | — | — | O | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-12 | Senin | 07:31 | 17:16 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-13 | Selasa | 07:27 | 07:27 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-14 | Rabu | 07:25 | 17:05 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-15 | Kamis | 07:32 | 17:06 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-16 | Jumat | — | — | O | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-17 | Sabtu | 07:22 | 13:41 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-18 | Minggu | — | — | O | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-19 | Senin | 07:29 | 18:08 | KJ | — | — |
| 2 | SAMSUL BAHRI | Project Control | 2026-01-20 | Selasa | 07:27 | 17:56 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-21 | Minggu | 10:00 | 20:02 | KL | 4 | Nilai langsung: `4` |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-22 | Senin | 19:49 | 19:49 | KJ | 3,5 | `=20-0.5-16` |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-23 | Selasa | 07:41 | 21:25 | KJ | 5 | Nilai langsung: `5` |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-24 | Rabu | 07:47 | 17:28 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-25 | Kamis | — | — | CT | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-26 | Jumat | 07:43 | 18:43 | CT | 1,5 | `=19-0.5-17` |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-27 | Sabtu | 07:45 | 15:21 | KJ | 1,5 | `=15.5-14` |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-28 | Minggu | — | — | — | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-29 | Senin | 07:50 | — | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-30 | Selasa | 07:50 | 16:52 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2025-12-31 | Rabu | 07:45 | 16:56 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-01 | Kamis | — | — | O | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-02 | Jumat | 07:40 | 17:11 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-03 | Sabtu | 07:51 | 14:28 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-04 | Minggu | — | — | O | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-05 | Senin | 07:45 | 07:45 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-06 | Selasa | — | — | I | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-07 | Rabu | 07:44 | 16:54 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-08 | Kamis | 07:45 | — | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-09 | Jumat | 07:48 | 20:14 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-10 | Sabtu | 07:36 | 14:22 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-11 | Minggu | — | — | — | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-12 | Senin | 07:42 | 17:17 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-13 | Selasa | 07:44 | 17:25 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-14 | Rabu | 07:45 | 16:25 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-15 | Kamis | 07:42 | 16:28 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-16 | Jumat | — | — | O | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-17 | Sabtu | 07:41 | 12:32 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-18 | Minggu | — | — | O | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-19 | Senin | 07:48 | 17:00 | KJ | — | — |
| 3 | FAJAR DWI CHANDRA | Admin Logistik | 2026-01-20 | Selasa | 07:52 | 18:02 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2025-12-21 | Minggu | 08:53 | 19:20 | KL | — | — |
| 4 | MARTONO | Warehouse | 2025-12-22 | Senin | 07:32 | 17:33 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2025-12-23 | Selasa | 07:34 | 18:50 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2025-12-24 | Rabu | 07:38 | 16:21 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2025-12-25 | Kamis | 09:53 | 17:32 | KL | — | — |
| 4 | MARTONO | Warehouse | 2025-12-26 | Jumat | 07:29 | 22:01 | KL | 4,5 | Nilai langsung: `4.5` |
| 4 | MARTONO | Warehouse | 2025-12-27 | Sabtu | 07:44 | 20:03 | KJ | 5,5 | `=20-14-0.5` |
| 4 | MARTONO | Warehouse | 2025-12-28 | Minggu | 08:30 | 18:17 | KL | — | — |
| 4 | MARTONO | Warehouse | 2025-12-29 | Senin | 07:36 | 20:56 | KJ | 4,5 | `=21-0.5-16` |
| 4 | MARTONO | Warehouse | 2025-12-30 | Selasa | 07:35 | 20:58 | KJ | 4,5 | `=21-0.5-16` |
| 4 | MARTONO | Warehouse | 2025-12-31 | Rabu | 07:41 | 18:26 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-01 | Kamis | — | — | CT | — | — |
| 4 | MARTONO | Warehouse | 2026-01-02 | Jumat | — | — | CT | — | — |
| 4 | MARTONO | Warehouse | 2026-01-03 | Sabtu | — | — | CT | — | — |
| 4 | MARTONO | Warehouse | 2026-01-04 | Minggu | — | — | CT | — | — |
| 4 | MARTONO | Warehouse | 2026-01-05 | Senin | 17:47 | 17:47 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-06 | Selasa | 07:41 | 21:12 | KJ | 4,5 | `=21-0.5-16` |
| 4 | MARTONO | Warehouse | 2026-01-07 | Rabu | 07:38 | 18:25 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-08 | Kamis | 07:40 | 17:04 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-09 | Jumat | 07:32 | 20:03 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-10 | Sabtu | 07:31 | 17:28 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-11 | Minggu | 08:12 | 18:13 | KL | — | — |
| 4 | MARTONO | Warehouse | 2026-01-12 | Senin | 07:33 | 17:16 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-13 | Selasa | 07:32 | 17:23 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-14 | Rabu | 07:32 | 20:01 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-15 | Kamis | 07:31 | 16:14 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-16 | Jumat | 08:09 | — | KL | — | — |
| 4 | MARTONO | Warehouse | 2026-01-17 | Sabtu | 07:34 | 15:42 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-18 | Minggu | 08:20 | 18:17 | KL | — | — |
| 4 | MARTONO | Warehouse | 2026-01-19 | Senin | 07:37 | 17:00 | KJ | — | — |
| 4 | MARTONO | Warehouse | 2026-01-20 | Selasa | 07:35 | 16:21 | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2025-12-21 | Minggu | 08:02 | 21:26 | KL | 5 | `=21.5-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2025-12-22 | Senin | 07:39 | 20:59 | KJ | 4,5 | Nilai langsung: `4.5` |
| 5 | AFRIYANDI | Mekanik | 2025-12-23 | Selasa | 07:41 | 22:02 | KJ | 5,5 | `=22-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2025-12-24 | Rabu | 07:37 | 21:06 | KJ | 4,5 | Nilai langsung: `4.5` |
| 5 | AFRIYANDI | Mekanik | 2025-12-25 | Kamis | 07:52 | 19:39 | KL | 3 | `=19.5-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2025-12-26 | Jumat | 07:39 | 22:12 | KL | 4,5 | Nilai langsung: `4.5` |
| 5 | AFRIYANDI | Mekanik | 2025-12-27 | Sabtu | 07:43 | — | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2025-12-28 | Minggu | — | — | KL | — | — |
| 5 | AFRIYANDI | Mekanik | 2025-12-29 | Senin | — | — | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2025-12-30 | Selasa | — | — | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2025-12-31 | Rabu | 07:53 | 18:32 | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-01 | Kamis | — | — | O | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-02 | Jumat | 07:40 | 21:00 | KJ | 3,5 | `=21-0.5-17` |
| 5 | AFRIYANDI | Mekanik | 2026-01-03 | Sabtu | 07:53 | 13:56 | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-04 | Minggu | 08:42 | 16:08 | KL | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-05 | Senin | 07:40 | 18:32 | KJ | 2,5 | Nilai langsung: `2.5` |
| 5 | AFRIYANDI | Mekanik | 2026-01-06 | Selasa | 07:47 | 22:28 | KJ | 6 | `=22.5-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2026-01-07 | Rabu | 07:45 | 22:57 | KJ | 6,5 | `=23-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2026-01-08 | Kamis | 07:33 | 19:56 | KJ | 3,5 | `=20-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2026-01-09 | Jumat | 22:16 | 22:16 | KJ | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-10 | Sabtu | 07:40 | 22:03 | KJ | 8,5 | `=22-0.5-13` |
| 5 | AFRIYANDI | Mekanik | 2026-01-11 | Minggu | 08:15 | 18:43 | KL | 2,5 | Nilai langsung: `2.5` |
| 5 | AFRIYANDI | Mekanik | 2026-01-12 | Senin | 07:48 | 19:43 | KJ | 3,5 | `=20-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2026-01-13 | Selasa | 07:37 | 00:00 | KJ | 7,5 | Nilai langsung: `7.5` |
| 5 | AFRIYANDI | Mekanik | 2026-01-14 | Rabu | 00:13 | 22:37 | KJ | 6 | `=22.5-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2026-01-15 | Kamis | 07:27 | 18:56 | KJ | 2,5 | Nilai langsung: `2.5` |
| 5 | AFRIYANDI | Mekanik | 2026-01-16 | Jumat | — | — | O | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-17 | Sabtu | — | — | — | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-18 | Minggu | — | — | O | — | — |
| 5 | AFRIYANDI | Mekanik | 2026-01-19 | Senin | 07:42 | 22:13 | KJ | 5,5 | `=22-0.5-16` |
| 5 | AFRIYANDI | Mekanik | 2026-01-20 | Selasa | 07:41 | 19:15 | KJ | 3 | Nilai langsung: `3` |
| 6 | DARMAWAN | Mekanik | 2025-12-21 | Minggu | 08:05 | 21:20 | KL | 5 | `=21.5-0.5-16` |
| 6 | DARMAWAN | Mekanik | 2025-12-22 | Senin | 07:57 | 17:23 | KJ | 1,5 | Nilai langsung: `1.5` |
| 6 | DARMAWAN | Mekanik | 2025-12-23 | Selasa | 07:45 | 17:05 | KJ | — | — |
| 6 | DARMAWAN | Mekanik | 2025-12-24 | Rabu | 07:48 | 20:06 | KJ | 3,5 | Nilai langsung: `3.5` |
| 6 | DARMAWAN | Mekanik | 2025-12-25 | Kamis | — | — | CT | — | — |
| 6 | DARMAWAN | Mekanik | 2025-12-26 | Jumat | 07:49 | 22:15 | KL | 4,5 | Nilai langsung: `4.5` |
| 6 | DARMAWAN | Mekanik | 2025-12-27 | Sabtu | 07:55 | 22:13 | KJ | 7,5 | `=22-14-0.5` |
| 6 | DARMAWAN | Mekanik | 2025-12-28 | Minggu | — | — | O | — | — |
| 6 | DARMAWAN | Mekanik | 2025-12-29 | Senin | 07:49 | 19:19 | KJ | 3 | `=19.5-0.5-16` |
| 6 | DARMAWAN | Mekanik | 2025-12-30 | Selasa | 07:40 | 21:08 | KJ | 4,5 | `=21-0.5-16` |
| 6 | DARMAWAN | Mekanik | 2025-12-31 | Rabu | 07:42 | 16:24 | KJ | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-01 | Kamis | — | — | O | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-02 | Jumat | 07:48 | 20:06 | KJ | 2,5 | `=20-17-0.5` |
| 6 | DARMAWAN | Mekanik | 2026-01-03 | Sabtu | 07:48 | 13:11 | KJ | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-04 | Minggu | — | — | O | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-05 | Senin | — | — | I | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-06 | Selasa | 07:57 | 16:11 | KJ | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-07 | Rabu | 07:32 | 17:17 | KJ | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-08 | Kamis | 07:53 | 22:48 | KJ | 6,5 | Nilai langsung: `6.5` |
| 6 | DARMAWAN | Mekanik | 2026-01-09 | Jumat | 07:51 | 17:18 | KJ | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-10 | Sabtu | 07:58 | 16:19 | KJ | 3 | Nilai langsung: `3` |
| 6 | DARMAWAN | Mekanik | 2026-01-11 | Minggu | — | — | O | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-12 | Senin | 07:46 | 19:11 | KJ | 3 | `=19-16` |
| 6 | DARMAWAN | Mekanik | 2026-01-13 | Selasa | 07:55 | 19:38 | KJ | 3 | `=19.5-0.5-16` |
| 6 | DARMAWAN | Mekanik | 2026-01-14 | Rabu | 07:53 | 23:00 | KJ | 6,5 | Nilai langsung: `6.5` |
| 6 | DARMAWAN | Mekanik | 2026-01-15 | Kamis | 07:55 | 19:03 | O | 2,5 | Nilai langsung: `2.5` |
| 6 | DARMAWAN | Mekanik | 2026-01-16 | Jumat | — | — | O | — | — |
| 6 | DARMAWAN | Mekanik | 2026-01-17 | Sabtu | 07:50 | 20:04 | KJ | 6,5 | `=20-0.5-13` |
| 6 | DARMAWAN | Mekanik | 2026-01-18 | Minggu | 08:04 | 17:37 | KL | 1,5 | Nilai langsung: `1.5` |
| 6 | DARMAWAN | Mekanik | 2026-01-19 | Senin | 07:54 | 20:11 | KJ | 3,5 | `=20-0.5-16` |
| 6 | DARMAWAN | Mekanik | 2026-01-20 | Selasa | 07:53 | 18:32 | KJ | 2,5 | Nilai langsung: `2.5` |
| 7 | SUWARDI | Welder | 2025-12-21 | Minggu | 07:48 | 21:19 | KL | 5 | Nilai langsung: `5` |
| 7 | SUWARDI | Welder | 2025-12-22 | Senin | 07:36 | 21:33 | KJ | 5 | `=21.5-0.5-16` |
| 7 | SUWARDI | Welder | 2025-12-23 | Selasa | 07:33 | 22:05 | KJ | 5,5 | Nilai langsung: `5.5` |
| 7 | SUWARDI | Welder | 2025-12-24 | Rabu | 07:47 | 21:07 | KJ | 4,5 | Nilai langsung: `4.5` |
| 7 | SUWARDI | Welder | 2025-12-25 | Kamis | 07:13 | 20:30 | KL | 4 | `=20.5-0.5-16` |
| 7 | SUWARDI | Welder | 2025-12-26 | Jumat | 07:35 | 22:14 | KL | 4,5 | Nilai langsung: `4.5` |
| 7 | SUWARDI | Welder | 2025-12-27 | Sabtu | 07:38 | 22:13 | KJ | 7,5 | Nilai langsung: `7.5` |
| 7 | SUWARDI | Welder | 2025-12-28 | Minggu | 07:47 | 16:06 | KL | — | — |
| 7 | SUWARDI | Welder | 2025-12-29 | Senin | 07:44 | — | KJ | 5,5 | `=22-0.5-16` |
| 7 | SUWARDI | Welder | 2025-12-30 | Selasa | 07:33 | 21:08 | KJ | 4,5 | `=21-0.5-16` |
| 7 | SUWARDI | Welder | 2025-12-31 | Rabu | 07:37 | 20:06 | KJ | 3,5 | `=20-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-01 | Kamis | — | — | O | — | — |
| 7 | SUWARDI | Welder | 2026-01-02 | Jumat | 07:32 | 21:04 | KJ | 3,5 | `=21-0.5-17` |
| 7 | SUWARDI | Welder | 2026-01-03 | Sabtu | 07:02 | 13:22 | KJ | — | — |
| 7 | SUWARDI | Welder | 2026-01-04 | Minggu | 07:36 | 16:08 | KL | — | — |
| 7 | SUWARDI | Welder | 2026-01-05 | Senin | 07:36 | 19:12 | KJ | 2,5 | `=19-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-06 | Selasa | 07:34 | 22:31 | KJ | 6 | `=22.5-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-07 | Rabu | 07:38 | 23:02 | KJ | 6,5 | `=23-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-08 | Kamis | 07:49 | 23:05 | KJ | 6,5 | `=23-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-09 | Jumat | 07:40 | 19:10 | KJ | — | — |
| 7 | SUWARDI | Welder | 2026-01-10 | Sabtu | 07:46 | 22:13 | KJ | 8,5 | `=22-0.5-13` |
| 7 | SUWARDI | Welder | 2026-01-11 | Minggu | 07:35 | 19:05 | KL | 2,5 | `=19-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-12 | Senin | 07:40 | 20:00 | KJ | 3,5 | `=20-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-13 | Selasa | 07:39 | 00:00 | KJ | 7,5 | `=24-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-14 | Rabu | 00:13 | 23:00 | KJ | 6,5 | `=23-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-15 | Kamis | 07:46 | 19:06 | KJ | 2,5 | Nilai langsung: `2.5` |
| 7 | SUWARDI | Welder | 2026-01-16 | Jumat | — | — | O | — | — |
| 7 | SUWARDI | Welder | 2026-01-17 | Sabtu | 07:42 | 22:16 | KJ | 9 | `=22.5-0.5-13` |
| 7 | SUWARDI | Welder | 2026-01-18 | Minggu | 07:30 | 22:18 | KL | 6 | `=22.5-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-19 | Senin | 07:35 | 22:12 | KJ | 5,5 | `=22-0.5-16` |
| 7 | SUWARDI | Welder | 2026-01-20 | Selasa | 07:44 | 19:17 | KJ | 3 | Nilai langsung: `3` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-21 | Minggu | 07:54 | 21:12 | KL | 4,5 | Nilai langsung: `4.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-22 | Senin | 07:33 | 21:33 | KJ | 5 | Nilai langsung: `5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-23 | Selasa | 07:38 | 22:06 | KJ | 5,5 | Nilai langsung: `5.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-24 | Rabu | 07:34 | 17:28 | KJ | 1,5 | Nilai langsung: `1.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-25 | Kamis | 07:59 | 20:31 | KL | 4 | Nilai langsung: `4` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-26 | Jumat | 07:30 | 22:15 | KL | 4,5 | Nilai langsung: `4.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-27 | Sabtu | 07:36 | 22:13 | KJ | 7,5 | Nilai langsung: `7.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-28 | Minggu | 08:17 | 16:06 | KL | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-29 | Senin | 07:28 | 22:01 | KJ | 5,5 | Nilai langsung: `5.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-30 | Selasa | 07:30 | 21:08 | KJ | 4,5 | Nilai langsung: `4.5` |
| 8 | HENDRIK | Teknisi Listrik | 2025-12-31 | Rabu | 07:35 | 20:50 | KJ | 4,5 | `=21-0.5-16` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-01 | Kamis | — | — | O | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-02 | Jumat | 07:32 | 20:05 | KJ | 2,5 | Nilai langsung: `2.5` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-03 | Sabtu | — | — | I | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-04 | Minggu | — | — | O | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-05 | Senin | — | — | I | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-06 | Selasa | 07:42 | 22:30 | KJ | 6 | Nilai langsung: `6` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-07 | Rabu | 07:31 | 22:12 | KJ | 5,5 | `=22-0.5-16` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-08 | Kamis | 07:40 | 23:05 | KJ | 6,5 | Nilai langsung: `6.5` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-09 | Jumat | 07:29 | 18:17 | KJ | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-10 | Sabtu | 07:10 | 18:29 | KJ | 5,5 | `=18.5-13` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-11 | Minggu | 08:04 | 18:35 | KL | 2 | Nilai langsung: `2` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-12 | Senin | 07:29 | 20:00 | KJ | 3,5 | Nilai langsung: `3.5` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-13 | Selasa | 07:41 | 00:00 | KJ | 7,5 | `=24-0.5-16` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-14 | Rabu | 00:13 | 23:00 | KJ | 6,5 | `=23-0.5-16` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-15 | Kamis | 07:37 | 19:05 | KJ | 2,5 | Nilai langsung: `2.5` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-16 | Jumat | — | — | O | — | — |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-17 | Sabtu | 07:46 | 22:13 | KJ | 8,5 | `=22-0.5-13` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-18 | Minggu | 07:56 | 22:20 | KJ | 6 | Nilai langsung: `6` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-19 | Senin | 07:24 | 22:12 | KJ | 6 | Nilai langsung: `6` |
| 8 | HENDRIK | Teknisi Listrik | 2026-01-20 | Selasa | 07:48 | 19:17 | KJ | 3 | Nilai langsung: `3` |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-21 | Minggu | — | — | O | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-22 | Senin | 07:49 | 17:57 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-23 | Selasa | 07:52 | 16:23 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-24 | Rabu | 07:50 | 16:42 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-25 | Kamis | — | — | CT | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-26 | Jumat | 08:20 | 16:33 | KL | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-27 | Sabtu | 08:13 | 16:24 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-28 | Minggu | — | — | O | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-29 | Senin | 07:30 | 16:10 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-30 | Selasa | 07:43 | 16:00 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2025-12-31 | Rabu | — | — | I | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-01 | Kamis | — | — | O | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-02 | Jumat | — | — | I | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-03 | Sabtu | — | — | I | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-04 | Minggu | — | — | O | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-05 | Senin | — | — | I | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-06 | Selasa | — | — | I | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-07 | Rabu | 07:18 | 17:37 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-08 | Kamis | 07:34 | 17:17 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-09 | Jumat | 07:34 | 18:08 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-10 | Sabtu | 07:36 | 17:24 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-11 | Minggu | 08:00 | 18:00 | KL | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-12 | Senin | 07:48 | 18:08 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-13 | Selasa | 07:39 | 19:27 | KJ | 3 | `=19.5-0.5-16` |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-14 | Rabu | 08:12 | 19:40 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-15 | Kamis | 08:21 | 17:27 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-16 | Jumat | — | — | O | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-17 | Sabtu | 07:52 | 17:57 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-18 | Minggu | — | — | O | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-19 | Senin | 07:50 | 18:13 | KJ | — | — |
| 9 | FAIKAR IZZANI FATTAAH | Operator Road Cold Recycler XCMG XLZ2303K | 2026-01-20 | Selasa | 08:04 | 18:02 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-21 | Minggu | — | — | O | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-22 | Senin | 07:37 | 18:03 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-23 | Selasa | 07:52 | 17:05 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-24 | Rabu | 07:57 | 16:42 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-25 | Kamis | — | — | CT | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-26 | Jumat | 07:56 | 17:15 | KL | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-27 | Sabtu | 08:00 | 16:25 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-28 | Minggu | — | — | — | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-29 | Senin | 07:36 | 16:10 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-30 | Selasa | 07:40 | 16:23 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2025-12-31 | Rabu | 08:02 | 16:01 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-01 | Kamis | — | — | O | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-02 | Jumat | 08:00 | 17:09 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-03 | Sabtu | 07:38 | 15:35 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-04 | Minggu | — | — | O | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-05 | Senin | 07:35 | 17:03 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-06 | Selasa | 07:38 | 18:13 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-07 | Rabu | 07:20 | 17:20 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-08 | Kamis | 07:33 | 17:21 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-09 | Jumat | 07:34 | 18:08 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-10 | Sabtu | 07:37 | 17:25 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-11 | Minggu | — | — | O | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-12 | Senin | 07:49 | 17:34 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-13 | Selasa | 07:39 | 18:50 | KJ | 3 | `=19-16` |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-14 | Rabu | 08:12 | 19:38 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-15 | Kamis | 08:04 | 17:27 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-16 | Jumat | — | — | O | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-17 | Sabtu | 07:52 | 17:55 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-18 | Minggu | — | — | O | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-19 | Senin | 07:50 | 18:08 | KJ | — | — |
| 10 | ARIF RANDY | Helper Opr. Road Cold Recycler XCMG XLZ2303K | 2026-01-20 | Selasa | 08:03 | 18:03 | KJ | 2 | Nilai langsung: `2` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-21 | Minggu | — | — | O | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-22 | Senin | 07:32 | 21:06 | KJ | 4,5 | Nilai langsung: `4.5` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-23 | Selasa | 07:39 | 22:00 | KJ | 5,5 | Nilai langsung: `5.5` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-24 | Rabu | 07:42 | 21:03 | KJ | 4,5 | Nilai langsung: `4.5` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-25 | Kamis | 08:00 | 18:21 | KL | 2 | Nilai langsung: `2` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-26 | Jumat | 07:47 | 17:11 | KL | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-27 | Sabtu | 07:33 | 19:24 | KJ | 5 | `=19.5-0.5-14` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-28 | Minggu | 07:48 | 16:05 | KL | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-29 | Senin | 07:43 | 20:42 | KJ | 4,5 | `=21-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-30 | Selasa | 07:38 | 20:52 | KJ | 4,5 | `=21-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2025-12-31 | Rabu | — | — | I | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-01 | Kamis | — | — | O | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-02 | Jumat | 07:36 | 19:01 | KJ | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-03 | Sabtu | 07:49 | 14:17 | KJ | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-04 | Minggu | — | — | O | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-05 | Senin | 07:41 | 19:10 | KJ | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-06 | Selasa | 07:40 | 22:31 | KJ | 6 | Nilai langsung: `6` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-07 | Rabu | 07:33 | 22:56 | KJ | 6,5 | `=23-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-08 | Kamis | 07:39 | 20:48 | KJ | 4,5 | `=21-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-09 | Jumat | 07:44 | 18:49 | KJ | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-10 | Sabtu | 07:33 | 15:37 | KJ | 2,5 | `=15.5-13` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-11 | Minggu | — | — | O | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-12 | Senin | — | — | I | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-13 | Selasa | 07:13 | 19:31 | KJ | 3 | `=19.5-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-14 | Rabu | 07:26 | 19:12 | KJ | 2,5 | `=19-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-15 | Kamis | 07:48 | 18:37 | KJ | 2 | Nilai langsung: `2` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-16 | Jumat | — | — | O | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-17 | Sabtu | 07:15 | 17:18 | KJ | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-18 | Minggu | — | — | O | — | — |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-19 | Senin | 07:41 | 19:45 | KJ | 3 | `=19.5-0.5-16` |
| 11 | WAGIMAN BARUTU | MEKANIK | 2026-01-20 | Selasa | 07:37 | 19:56 | KJ | 3,5 | `=20-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-21 | Minggu | 07:48 | 21:20 | KL | 5 | Nilai langsung: `5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-22 | Senin | 07:32 | 21:03 | KJ | 4,5 | Nilai langsung: `4.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-23 | Selasa | 07:39 | 22:00 | KJ | 5,5 | Nilai langsung: `5.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-24 | Rabu | 07:42 | 21:07 | KJ | 4,5 | `=21-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-25 | Kamis | 08:17 | 19:05 | KL | 2,5 | `=19-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-26 | Jumat | 07:49 | 22:12 | KL | 4,5 | Nilai langsung: `4.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-27 | Sabtu | 07:42 | 22:12 | KJ | 7,5 | `=22-14-0.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-28 | Minggu | 08:08 | 16:05 | KL | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-29 | Senin | 07:43 | 22:01 | KJ | 5,5 | `=22-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-30 | Selasa | 06:50 | 21:05 | KJ | 4,5 | Nilai langsung: `4.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2025-12-31 | Rabu | 07:00 | 18:37 | KJ | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-01 | Kamis | — | — | O | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-02 | Jumat | 07:12 | 21:02 | KJ | 3,5 | `=21-0.5-17` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-03 | Sabtu | 07:22 | 13:56 | KJ | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-04 | Minggu | 07:59 | 16:01 | KL | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-05 | Senin | 07:40 | 18:32 | KJ | 2,5 | Nilai langsung: `2.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-06 | Selasa | 07:45 | 22:30 | KJ | 6 | Nilai langsung: `6` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-07 | Rabu | 07:34 | 23:00 | KJ | 6,5 | `=23-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-08 | Kamis | 07:34 | 19:56 | KJ | 3,5 | `=20-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-09 | Jumat | 22:15 | 22:16 | KJ | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-10 | Sabtu | 08:01 | 16:32 | KJ | 3,5 | `=16.5-13` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-11 | Minggu | 07:27 | 19:01 | KL | 3 | Nilai langsung: `3` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-12 | Senin | 07:44 | 19:44 | KJ | 3,5 | `=20-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-13 | Selasa | 07:49 | 00:00 | KJ | 7,5 | Nilai langsung: `7.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-14 | Rabu | 00:13 | 23:00 | KJ | 6,5 | Nilai langsung: `6.5` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-15 | Kamis | 08:03 | 18:56 | KJ | 2,5 | `=19-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-16 | Jumat | — | — | O | — | — |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-17 | Sabtu | 07:43 | 22:03 | KJ | 8,5 | `=22-0.5-13` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-18 | Minggu | 08:05 | 22:20 | KL | 6 | Nilai langsung: `6` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-19 | Senin | 07:54 | 22:12 | KJ | 5,5 | `=22-0.5-16` |
| 12 | FIRLANDA DOLOK SARIBU | HELPER MEKANIK | 2026-01-20 | Selasa | 07:20 | 19:17 | KJ | 3 | Nilai langsung: `3` |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-21 | Minggu | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-22 | Senin | 07:39 | 16:07 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-23 | Selasa | 07:46 | 16:03 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-24 | Rabu | 07:43 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-25 | Kamis | — | — | CT | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-26 | Jumat | — | — | CT | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-27 | Sabtu | 07:34 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-28 | Minggu | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-29 | Senin | 07:42 | 16:02 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-30 | Selasa | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2025-12-31 | Rabu | 07:41 | 16:39 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-01 | Kamis | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-02 | Jumat | 07:42 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-03 | Sabtu | 07:39 | 14:35 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-04 | Minggu | 07:49 | 15:29 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-05 | Senin | 07:41 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-06 | Selasa | 07:44 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-07 | Rabu | 07:45 | 16:01 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-08 | Kamis | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-09 | Jumat | 07:36 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-10 | Sabtu | 07:41 | 15:56 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-11 | Minggu | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-12 | Senin | 07:41 | 16:00 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-13 | Selasa | 07:41 | 16:09 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-14 | Rabu | 07:43 | 19:35 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-15 | Kamis | 07:37 | 16:40 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-16 | Jumat | 07:41 | 15:07 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-17 | Sabtu | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-18 | Minggu | — | — | I | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-19 | Senin | 07:41 | 16:02 | KJ | — | — |
| 13 | AMIRUDIN | Petugas K3L | 2026-01-20 | Selasa | 07:43 | 16:03 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-21 | Minggu | — | — | I | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-22 | Senin | 07:32 | 16:10 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-23 | Selasa | 07:35 | 16:03 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-24 | Rabu | 07:39 | 16:00 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-25 | Kamis | — | — | CT | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-26 | Jumat | 07:39 | 16:11 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-27 | Sabtu | 07:39 | 16:00 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-28 | Minggu | — | — | I | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-29 | Senin | 07:41 | 16:02 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-30 | Selasa | 07:33 | 15:58 | K | — | — |
| 14 | KUSWANTO | Petugas K3L | 2025-12-31 | Rabu | 07:34 | 16:39 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-01 | Kamis | — | — | I | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-02 | Jumat | 07:37 | 16:01 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-03 | Sabtu | 07:38 | 14:36 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-04 | Minggu | 07:49 | 15:30 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-05 | Senin | 07:34 | 16:01 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-06 | Selasa | 07:39 | 16:01 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-07 | Rabu | 07:37 | 16:27 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-08 | Kamis | 07:42 | 16:24 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-09 | Jumat | 07:30 | 16:00 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-10 | Sabtu | 07:34 | 15:56 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-11 | Minggu | — | — | I | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-12 | Senin | 07:35 | 16:02 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-13 | Selasa | 07:32 | 16:09 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-14 | Rabu | 07:37 | 16:18 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-15 | Kamis | 07:37 | 16:41 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-16 | Jumat | 07:42 | 15:08 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-17 | Sabtu | 07:37 | 16:07 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-18 | Minggu | — | — | I | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-19 | Senin | 07:40 | 16:04 | KJ | — | — |
| 14 | KUSWANTO | Petugas K3L | 2026-01-20 | Selasa | 07:35 | 16:03 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-21 | Minggu | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-22 | Senin | 08:09 | 16:01 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-23 | Selasa | 08:05 | 16:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-24 | Rabu | 07:57 | 16:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-25 | Kamis | — | — | CT | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-26 | Jumat | 07:42 | 16:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-27 | Sabtu | 08:03 | 15:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-28 | Minggu | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-29 | Senin | 08:07 | 16:03 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-30 | Selasa | 08:04 | 15:55 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2025-12-31 | Rabu | 08:08 | 11:55 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-01 | Kamis | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-02 | Jumat | 08:36 | 16:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-03 | Sabtu | 07:55 | 14:32 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-04 | Minggu | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-05 | Senin | 07:57 | 16:22 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-06 | Selasa | 08:01 | 16:06 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-07 | Rabu | 07:26 | 16:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-08 | Kamis | 07:45 | 16:08 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-09 | Jumat | 07:41 | 16:00 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-10 | Sabtu | 07:59 | 13:19 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-11 | Minggu | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-12 | Senin | 08:03 | 16:02 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-13 | Selasa | 07:54 | 16:05 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-14 | Rabu | 07:59 | 16:02 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-15 | Kamis | 07:52 | 16:01 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-16 | Jumat | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-17 | Sabtu | 07:50 | 14:15 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-18 | Minggu | — | — | O | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-19 | Senin | 07:31 | 16:03 | KJ | — | — |
| 15 | DAVID ARITONANG | Flag Man | 2026-01-20 | Selasa | 07:55 | 16:02 | KJ | — | — |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-21 | Minggu | 06:57 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-22 | Senin | 19:01 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-23 | Selasa | 07:04 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-24 | Rabu | 07:01 | 18:53 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-25 | Kamis | 07:17 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-26 | Jumat | 06:59 | 18:55 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-27 | Sabtu | 07:01 | 19:06 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-28 | Minggu | 07:01 | 20:00 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-29 | Senin | 07:05 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-30 | Selasa | 07:02 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2025-12-31 | Rabu | 07:01 | 19:10 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-01 | Kamis | 06:58 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-02 | Jumat | 07:01 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-03 | Sabtu | 19:01 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-04 | Minggu | 07:01 | — | O | — | — |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-05 | Senin | 06:58 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-06 | Selasa | 07:01 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-07 | Rabu | 07:03 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-08 | Kamis | 07:02 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-09 | Jumat | 06:56 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-10 | Sabtu | 06:59 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-11 | Minggu | 07:00 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-12 | Senin | 18:58 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-13 | Selasa | 07:20 | 18:57 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-14 | Rabu | 07:00 | 18:57 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-15 | Kamis | 07:02 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-16 | Jumat | 07:02 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-17 | Sabtu | 07:05 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-18 | Minggu | 07:04 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-19 | Senin | 07:01 | 07:01 | KJ | 4 | Nilai langsung: `4` |
| 16 | JORLAN SIBATUARA | Koordinator Security | 2026-01-20 | Selasa | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2025-12-21 | Minggu | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2025-12-22 | Senin | 07:02 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-23 | Selasa | 07:04 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-24 | Rabu | 06:53 | 18:45 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-25 | Kamis | 06:55 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-26 | Jumat | 06:52 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-27 | Sabtu | 06:55 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-28 | Minggu | 06:58 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2025-12-29 | Senin | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2025-12-30 | Selasa | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2025-12-31 | Rabu | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-01 | Kamis | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-02 | Jumat | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-03 | Sabtu | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-04 | Minggu | — | — | CT | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-05 | Senin | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-06 | Selasa | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-07 | Rabu | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-08 | Kamis | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-09 | Jumat | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-10 | Sabtu | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-11 | Minggu | — | — | O | — | — |
| 17 | SUDIRMAN H | Security | 2026-01-12 | Senin | 06:57 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-13 | Selasa | 07:00 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-14 | Rabu | 06:58 | 19:05 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-15 | Kamis | 06:59 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-16 | Jumat | 06:56 | 19:11 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-17 | Sabtu | 06:57 | 19:12 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-18 | Minggu | 07:01 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-19 | Senin | 18:57 | 18:57 | KJ | 4 | Nilai langsung: `4` |
| 17 | SUDIRMAN H | Security | 2026-01-20 | Selasa | 07:02 | 18:57 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2025-12-21 | Minggu | 07:04 | 18:56 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2025-12-22 | Senin | 07:04 | — | O | — | — |
| 18 | EDY JAKA S | Security | 2025-12-23 | Selasa | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2025-12-24 | Rabu | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2025-12-25 | Kamis | — | — | CT | — | — |
| 18 | EDY JAKA S | Security | 2025-12-26 | Jumat | — | — | CT | — | — |
| 18 | EDY JAKA S | Security | 2025-12-27 | Sabtu | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2025-12-28 | Minggu | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2025-12-29 | Senin | 07:08 | 19:07 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2025-12-30 | Selasa | 07:00 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2025-12-31 | Rabu | 07:00 | 19:06 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-01 | Kamis | 06:49 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-02 | Jumat | 07:12 | 19:37 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-03 | Sabtu | 07:05 | 19:05 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-04 | Minggu | 07:00 | — | KJ | — | — |
| 18 | EDY JAKA S | Security | 2026-01-05 | Senin | 07:05 | 19:32 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-06 | Selasa | 07:03 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-07 | Rabu | 07:06 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-08 | Kamis | 07:04 | 19:05 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-09 | Jumat | 07:03 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-10 | Sabtu | 07:02 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-11 | Minggu | 07:26 | 19:14 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-12 | Senin | 07:16 | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-13 | Selasa | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-14 | Rabu | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-15 | Kamis | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-16 | Jumat | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-17 | Sabtu | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-18 | Minggu | — | — | O | — | — |
| 18 | EDY JAKA S | Security | 2026-01-19 | Senin | 06:59 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 18 | EDY JAKA S | Security | 2026-01-20 | Selasa | 06:58 | 19:10 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-21 | Minggu | 07:03 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-22 | Senin | 07:03 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-23 | Selasa | 07:07 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-24 | Rabu | 07:00 | 18:42 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-25 | Kamis | 07:00 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-26 | Jumat | 07:09 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-27 | Sabtu | 07:00 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-28 | Minggu | 07:00 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-29 | Senin | 07:05 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-30 | Selasa | 07:01 | 19:05 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2025-12-31 | Rabu | 07:01 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-01 | Kamis | 07:01 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-02 | Jumat | 07:12 | 19:04 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-03 | Sabtu | 07:05 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-04 | Minggu | 07:03 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-05 | Senin | 07:02 | 18:55 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-06 | Selasa | 07:04 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-07 | Rabu | 07:04 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-08 | Kamis | 07:03 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-09 | Jumat | 07:03 | 18:54 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-10 | Sabtu | 07:02 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-11 | Minggu | 07:01 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-12 | Senin | 07:02 | 18:59 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-13 | Selasa | 07:03 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-14 | Rabu | 07:02 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-15 | Kamis | 07:02 | 19:03 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-16 | Jumat | 07:00 | 19:00 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-17 | Sabtu | 07:02 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-18 | Minggu | 07:07 | 18:58 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-19 | Senin | 05:30 | 19:01 | KJ | 4 | Nilai langsung: `4` |
| 19 | TAUFIQ H | Security | 2026-01-20 | Selasa | 07:01 | 19:02 | KJ | 4 | Nilai langsung: `4` |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-21 | Minggu | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-22 | Senin | 07:56 | 20:59 | KJ | 4,5 | Nilai langsung: `4.5` |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-23 | Selasa | 08:05 | 22:02 | KJ | 5,5 | Nilai langsung: `5.5` |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-24 | Rabu | — | — | I | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-25 | Kamis | — | — | CT | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-26 | Jumat | — | — | CT | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-27 | Sabtu | 07:41 | — | KJ | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-28 | Minggu | — | — | O | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-29 | Senin | 08:11 | 19:19 | KJ | 3 | `=19.5-0.5-16` |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-30 | Selasa | 08:08 | 18:30 | KJ | 2 | Nilai langsung: `2` |
| 20 | AKBAR MAULANA | Siswa Magang | 2025-12-31 | Rabu | 08:02 | 18:32 | KJ | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-01 | Kamis | — | — | O | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-02 | Jumat | — | — | I | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-03 | Sabtu | 07:40 | 13:56 | KJ | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-04 | Minggu | — | — | O | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-05 | Senin | 08:09 | 16:24 | KJ | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-06 | Selasa | 07:45 | 18:02 | KJ | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-07 | Rabu | 08:13 | 17:33 | KJ | 1,5 | Nilai langsung: `1.5` |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-08 | Kamis | 07:34 | 17:33 | O | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-09 | Jumat | 07:37 | 07:37 | KJ | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-10 | Sabtu | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-11 | Minggu | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-12 | Senin | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-13 | Selasa | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-14 | Rabu | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-15 | Kamis | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-16 | Jumat | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-17 | Sabtu | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-18 | Minggu | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-19 | Senin | — | — | — | — | — |
| 20 | AKBAR MAULANA | Siswa Magang | 2026-01-20 | Selasa | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-21 | Minggu | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-22 | Senin | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-23 | Selasa | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-24 | Rabu | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-25 | Kamis | — | — | CT | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-26 | Jumat | — | — | CT | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-27 | Sabtu | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-28 | Minggu | — | — | O | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-29 | Senin | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-30 | Selasa | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2025-12-31 | Rabu | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-01 | Kamis | — | — | O | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-02 | Jumat | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-03 | Sabtu | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-04 | Minggu | — | — | I | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-05 | Senin | 08:09 | 16:24 | KJ | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-06 | Selasa | 18:02 | 18:02 | KJ | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-07 | Rabu | 08:14 | 08:14 | KJ | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-08 | Kamis | — | — | O | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-09 | Jumat | 07:37 | 07:37 | KJ | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-10 | Sabtu | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-11 | Minggu | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-12 | Senin | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-13 | Selasa | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-14 | Rabu | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-15 | Kamis | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-16 | Jumat | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-17 | Sabtu | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-18 | Minggu | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-19 | Senin | — | — | — | — | — |
| 21 | ALEX PAULUS | Siswa Magang | 2026-01-20 | Selasa | — | — | — | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-21 | Minggu | — | — | — | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-22 | Senin | 07:31 | 21:06 | KJ | 4,5 | Nilai langsung: `4.5` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-23 | Selasa | 07:48 | 22:02 | KJ | 5,5 | Nilai langsung: `5.5` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-24 | Rabu | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-25 | Kamis | 14:04 | 19:06 | KL | 2,5 | Nilai langsung: `2.5` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-26 | Jumat | 07:52 | 16:35 | KL | 0 | Nilai langsung: `0` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-27 | Sabtu | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-28 | Minggu | — | — | O | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-29 | Senin | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-30 | Selasa | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2025-12-31 | Rabu | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-01 | Kamis | — | — | O | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-02 | Jumat | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-03 | Sabtu | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-04 | Minggu | — | — | O | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-05 | Senin | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-06 | Selasa | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-07 | Rabu | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-08 | Kamis | — | — | O | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-09 | Jumat | 08:01 | 18:47 | KJ | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-10 | Sabtu | — | — | — | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-11 | Minggu | — | — | — | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-12 | Senin | 07:42 | 20:01 | KJ | 3,5 | Nilai langsung: `3.5` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-13 | Selasa | 08:25 | 19:39 | KJ | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-14 | Rabu | 08:03 | 19:12 | KJ | 2,5 | Nilai langsung: `2.5` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-15 | Kamis | 08:07 | 18:43 | KJ | 2,5 | Nilai langsung: `2.5` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-16 | Jumat | — | — | — | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-17 | Sabtu | 07:57 | 23:54 | KJ | 10,5 | `=23--0.5-13` |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-18 | Minggu | — | — | O | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-19 | Senin | — | — | I | — | — |
| 22 | GABRIEL MARBUN | Siswa Magang | 2026-01-20 | Selasa | 07:59 | 19:56 | KJ | 3,5 | `=20-0.5-16` |

### Informasi administrasi dan pengesahan

| Elemen | Isi |
| :--- | :--- |
| Tanggal dokumen | Duri, 28 Januari 2026 |
| Diketahui oleh | Widya Apriani — Manager Asset |
| Diperiksa oleh | Dany Agung — Head of Equipment |
| Dibuat oleh | Dean Martin — Admin |
| Unit/organisasi | Dept. Equipment PT. BRA |

## 6. Sheet `LAPANGAN` — Rekapitulasi Absen Lokasi (Tidak Finger Print)

Periode: **21 Desember 2025–20 Januari 2026**. Tabel asli menyusun setiap orang dalam empat baris: `In`, `Out`, `Absen`, dan `Lembur`. Dalam Markdown, struktur itu diratakan menjadi satu baris per orang per tanggal.

### Ringkasan sebagaimana tersimpan di workbook

| No. normal | No. sumber | Kode C | Nama | Posisi | KJ | KL | O | CT | S | I | MK | PR | Total lembur |
| ---: | ---: | ---: | :--- | :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 1 | 1 | 12 | JONI SEPTIAN | Mekanik | 22 | 3 | 5 | 0 | 0 | 0 | 0 | 0 | 74,5 |
| 2 | 2 | 16 | DANIEL SITEPU | Mekanik | 20 | 4 | 5 | 0 | 0 | 2 | 0 | 0 | 68 |
| 3 | 3 | 16 | REJEKI SIREGAR | Mekanik | 20 | 2 | 6 | 1 | 0 | 2 | 0 | 0 | 63 |
| 4 | 4 | 16 | SOLEH AL MUZAKAR | Welder | 22 | 4 | 5 | 0 | 0 | 0 | 0 | 0 | 75,5 |
| 5 | 5 | 16 | URWATUL USKA | Helper Mekanik | 21 | 3 | 6 | 0 | 0 | 1 | 0 | 0 | 60 |

> Pada sheet `LAPANGAN`, ringkasan menggunakan rentang `Q:AU`, sehingga seluruh tanggal 21 Desember–20 Januari sudah tercakup.

### Audit perhitungan untuk seluruh 31 hari

| No. | Nama | KJ | KL | O | CT | S | I | MK | PR | Status kosong | Kode lain | Lembur 31 hari | Selisih vs sumber |
| ---: | :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | :--- | ---: | ---: |
| 1 | JONI SEPTIAN | 22 | 3 | 5 | 0 | 0 | 0 | 0 | 0 | 1 | — | 74,5 | 0 |
| 2 | DANIEL SITEPU | 20 | 4 | 5 | 0 | 0 | 2 | 0 | 0 | 0 | — | 68 | 0 |
| 3 | REJEKI SIREGAR | 20 | 2 | 6 | 1 | 0 | 2 | 0 | 0 | 0 | — | 63 | 0 |
| 4 | SOLEH AL MUZAKAR | 22 | 4 | 5 | 0 | 0 | 0 | 0 | 0 | 0 | — | 75,5 | 0 |
| 5 | URWATUL USKA | 21 | 3 | 6 | 0 | 0 | 1 | 0 | 0 | 0 | — | 60 | 0 |

### Data harian yang dinormalisasi

| No. | Nama | Posisi | Tanggal | Hari | In | Out | Status | Lembur (jam) | Sumber lembur |
| ---: | :--- | :--- | :---: | :--- | :---: | :---: | :---: | ---: | :--- |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-21 | Minggu | 07:30 | 21:20 | KL | 5 | `=21.5-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-22 | Senin | 21:07 | 21:07 | KJ | 4,5 | `=21-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-23 | Selasa | 07:56 | 22:02 | KJ | 5,5 | Nilai langsung: `5.5` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-24 | Rabu | 07:37 | 21:07 | KJ | 4,5 | `=21-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-25 | Kamis | 07:52 | 19:39 | KL | 3 | `=19.5-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-26 | Jumat | 07:39 | 22:12 | KL | 4,5 | `=22-0.5-17` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-27 | Sabtu | 07:54 | 14:52 | KJ | 2 | `=15-13` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-28 | Minggu | — | — | O | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-29 | Senin | 07:50 | 20:53 | KJ | 4,5 | `=21-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-30 | Selasa | 07:52 | 18:31 | KJ | 2,5 | Nilai langsung: `2.5` |
| 1 | JONI SEPTIAN | Mekanik | 2025-12-31 | Rabu | 08:42 | 18:48 | KJ | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-01 | Kamis | — | — | O | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-02 | Jumat | 07:53 | 17:30 | KJ | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-03 | Sabtu | 07:55 | 14:30 | KJ | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-04 | Minggu | — | — | O | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-05 | Senin | 08:04 | 18:30 | KJ | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-06 | Selasa | 08:08 | 17:50 | KJ | 2 | Nilai langsung: `2` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-07 | Rabu | 07:43 | 19:30 | KJ | 3 | `=19.5-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-08 | Kamis | 08:07 | 19:41 | KJ | 3 | `=19.5-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-09 | Jumat | 08:06 | 18:08 | KJ | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-10 | Sabtu | 07:30 | 17:48 | KJ | 4,5 | `=17.5-13` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-11 | Minggu | — | — | — | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-12 | Senin | 20:03 | 20:09 | KJ | 3,5 | `=20-0.5-16` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-13 | Selasa | 07:36 | 18:00 | KJ | 2 | Nilai langsung: `2` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-14 | Rabu | 07:30 | 18:00 | KJ | 2 | Nilai langsung: `2` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-15 | Kamis | 07:30 | 18:00 | KJ | 2 | Nilai langsung: `2` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-16 | Jumat | — | — | O | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-17 | Sabtu | 07:30 | 23:54 | KJ | 10,5 | Nilai langsung: `10.5` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-18 | Minggu | — | — | O | — | — |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-19 | Senin | 07:30 | 19:00 | KJ | 3 | Nilai langsung: `3` |
| 1 | JONI SEPTIAN | Mekanik | 2026-01-20 | Selasa | 07:30 | 19:00 | KJ | 3 | Nilai langsung: `3` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-21 | Minggu | 08:05 | 21:12 | KL | 4,5 | Nilai langsung: `4.5` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-22 | Senin | 20:59 | 20:59 | KJ | 4,5 | `=21-0.5-16` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-23 | Selasa | 07:48 | 22:03 | KJ | 5,5 | Nilai langsung: `5.5` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-24 | Rabu | 07:53 | — | KJ | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-25 | Kamis | 07:42 | 16:07 | KL | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-26 | Jumat | 07:38 | 16:54 | KL | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-27 | Sabtu | 07:41 | 19:27 | KJ | 4,5 | `=19-0.5-14` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-28 | Minggu | — | — | O | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-29 | Senin | 07:45 | 20:53 | KJ | 4,5 | Nilai langsung: `4.5` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-30 | Selasa | 07:43 | 18:30 | KJ | 2,5 | Nilai langsung: `2.5` |
| 2 | DANIEL SITEPU | Mekanik | 2025-12-31 | Rabu | — | — | I | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-01 | Kamis | — | — | O | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-02 | Jumat | 07:58 | 21:00 | KJ | 3,5 | `=21-17-0.5` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-03 | Sabtu | 07:35 | 13:56 | KJ | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-04 | Minggu | 07:30 | 16:09 | KL | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-05 | Senin | 07:33 | 18:32 | KJ | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-06 | Selasa | — | — | I | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-07 | Rabu | 07:27 | 22:56 | KJ | 6,5 | `=23-0.5-16` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-08 | Kamis | 07:51 | 22:49 | KJ | 6,5 | `=23-0.5-16` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-09 | Jumat | 07:42 | 17:15 | KJ | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-10 | Sabtu | 07:43 | 18:13 | KJ | 5 | `=18-13` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-11 | Minggu | — | — | O | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-12 | Senin | 07:51 | 18:59 | KJ | 2 | Nilai langsung: `2` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-13 | Selasa | 07:47 | 19:37 | KJ | 3 | Nilai langsung: `3` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-14 | Rabu | 07:39 | 19:12 | KJ | 2,5 | Nilai langsung: `2.5` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-15 | Kamis | 07:37 | 19:06 | KJ | 2,5 | Nilai langsung: `2.5` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-16 | Jumat | — | — | O | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-17 | Sabtu | 07:35 | 22:03 | KJ | 8,5 | `=22-0.5-13` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-18 | Minggu | — | — | O | — | — |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-19 | Senin | 07:45 | 19:44 | KJ | 2 | Nilai langsung: `2` |
| 2 | DANIEL SITEPU | Mekanik | 2026-01-20 | Selasa | 07:48 | 17:12 | KJ | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-21 | Minggu | 08:00 | 21:20 | KL | 5 | Nilai langsung: `5` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-22 | Senin | 07:30 | 21:20 | KJ | 5 | Nilai langsung: `5` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-23 | Selasa | 07:30 | 22:00 | KJ | 5,5 | Nilai langsung: `5.5` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-24 | Rabu | 07:30 | 21:00 | KJ | 4,5 | Nilai langsung: `4.5` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-25 | Kamis | — | — | CT | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-26 | Jumat | 07:35 | 22:00 | KL | 4,5 | `=22-0.5-17` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-27 | Sabtu | 07:35 | 22:00 | KJ | 5 | `=19.5-0.5-14` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-28 | Minggu | — | — | O | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-29 | Senin | 07:35 | 19:00 | KJ | 2,5 | `=19-0.5-16` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-30 | Selasa | 07:35 | 19:00 | KJ | 3 | Nilai langsung: `3` |
| 3 | REJEKI SIREGAR | Mekanik | 2025-12-31 | Rabu | 07:35 | 18:25 | KJ | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-01 | Kamis | — | — | O | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-02 | Jumat | 07:35 | 18:35 | KJ | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-03 | Sabtu | 07:35 | 18:35 | KJ | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-04 | Minggu | — | O | O | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-05 | Senin | — | — | I | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-06 | Selasa | — | — | I | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-07 | Rabu | 07:35 | 18:35 | KJ | 2 | Nilai langsung: `2` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-08 | Kamis | 07:35 | 20:25 | KJ | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-09 | Jumat | 07:35 | 18:35 | KJ | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-10 | Sabtu | 07:35 | 18:35 | KJ | 5 | `=18-13` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-11 | Minggu | — | — | O | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-12 | Senin | 07:35 | 19:15 | KJ | 3 | `=19.5-0.5-16` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-13 | Selasa | 07:30 | 18:35 | KJ | 2 | Nilai langsung: `2` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-14 | Rabu | 07:35 | 19:13 | KJ | 2,5 | Nilai langsung: `2.5` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-15 | Kamis | 07:35 | 19:00 | KJ | 2,5 | Nilai langsung: `2.5` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-16 | Jumat | — | — | O | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-17 | Sabtu | 07:35 | 18:25 | KJ | 5 | `=18-13` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-18 | Minggu | — | — | O | — | — |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-19 | Senin | 07:35 | 19:00 | KJ | 3 | `=19-16` |
| 3 | REJEKI SIREGAR | Mekanik | 2026-01-20 | Selasa | 07:35 | 19:35 | KJ | 3 | `=19.5-0.5-16` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-21 | Minggu | 07:48 | 21:12 | KL | 4,5 | Nilai langsung: `4.5` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-22 | Senin | 07:12 | 21:33 | KJ | 5 | Nilai langsung: `5` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-23 | Selasa | 07:25 | 22:02 | KJ | 5,5 | Nilai langsung: `5.5` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-24 | Rabu | 07:25 | 21:03 | KJ | 4,5 | Nilai langsung: `4.5` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-25 | Kamis | 07:24 | 17:03 | KL | 1 | Nilai langsung: `1` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-26 | Jumat | 07:26 | 22:11 | KL | 4,5 | `=22-0.5-17` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-27 | Sabtu | 07:32 | 19:24 | KJ | 5 | `=19.5-0.5-14` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-28 | Minggu | 07:31 | 16:06 | KL | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-29 | Senin | 07:27 | 20:42 | KJ | 4,5 | Nilai langsung: `4.5` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-30 | Selasa | 07:26 | 20:51 | KJ | 4,5 | `=21-0.5-16` |
| 4 | SOLEH AL MUZAKAR | Welder | 2025-12-31 | Rabu | 07:25 | 17:12 | KJ | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-01 | Kamis | — | — | O | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-02 | Jumat | 07:25 | 19:02 | KJ | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-03 | Sabtu | 07:25 | 14:15 | KJ | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-04 | Minggu | 07:34 | — | O | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-05 | Senin | 07:25 | 19:07 | KJ | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-06 | Selasa | 07:30 | 19:24 | KJ | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-07 | Rabu | 07:30 | 18:21 | KJ | 2 | Nilai langsung: `2` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-08 | Kamis | 07:27 | 20:48 | KJ | 4,5 | `=21-0.5-16` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-09 | Jumat | 07:31 | 18:51 | KJ | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-10 | Sabtu | 07:24 | 22:02 | KJ | 8,5 | `=22-0.5-13` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-11 | Minggu | — | — | O | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-12 | Senin | 07:28 | 20:08 | KJ | 3,5 | `=20-0.5-16` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-13 | Selasa | 07:18 | 19:32 | KJ | 3 | Nilai langsung: `3` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-14 | Rabu | 07:29 | 19:16 | KJ | 2,5 | Nilai langsung: `2.5` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-15 | Kamis | 07:18 | 18:37 | KJ | 2 | Nilai langsung: `2` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-16 | Jumat | — | — | O | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-17 | Sabtu | 07:29 | 17:19 | KJ | 4 | `=17.5-0.5-13` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-18 | Minggu | — | — | O | — | — |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-19 | Senin | 07:25 | 19:46 | KJ | 3 | Nilai langsung: `3` |
| 4 | SOLEH AL MUZAKAR | Welder | 2026-01-20 | Selasa | 07:28 | 19:56 | KJ | 3,5 | `=20-0.5-16` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-21 | Minggu | 08:00 | 21:13 | KL | 4,5 | Nilai langsung: `4.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-22 | Senin | 08:00 | 21:13 | KJ | 4,5 | Nilai langsung: `4.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-23 | Selasa | 08:00 | 22:00 | KJ | 5,5 | Nilai langsung: `5.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-24 | Rabu | 08:00 | 21:00 | KJ | 4,5 | Nilai langsung: `4.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-25 | Kamis | 08:00 | 16:00 | KL | 0 | Nilai langsung: `0` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-26 | Jumat | 08:00 | 16:00 | KL | 0 | Nilai langsung: `0` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-27 | Sabtu | 08:00 | 19:30 | KJ | 5 | Nilai langsung: `5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-28 | Minggu | — | — | O | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-29 | Senin | 08:00 | 20:53 | KJ | 4,5 | Nilai langsung: `4.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-30 | Selasa | 08:00 | 20:52 | KJ | 4,5 | Nilai langsung: `4.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2025-12-31 | Rabu | 08:18 | — | KJ | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-01 | Kamis | — | — | O | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-02 | Jumat | 08:00 | — | KJ | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-03 | Sabtu | 07:51 | 14:15 | KJ | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-04 | Minggu | — | — | O | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-05 | Senin | 08:08 | 19:09 | KJ | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-06 | Selasa | 08:04 | 19:24 | KJ | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-07 | Rabu | — | — | I | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-08 | Kamis | 07:59 | 17:33 | KJ | 1,5 | Nilai langsung: `1.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-09 | Jumat | 07:49 | 17:15 | KJ | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-10 | Sabtu | 08:17 | 18:09 | KJ | 5 | Nilai langsung: `5` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-11 | Minggu | — | — | O | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-12 | Senin | 07:58 | 18:59 | KJ | 2 | Nilai langsung: `2` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-13 | Selasa | 19:31 | 19:31 | KJ | 3 | Nilai langsung: `3` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-14 | Rabu | 07:43 | 19:12 | KJ | 2,5 | Nilai langsung: `2.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-15 | Kamis | 08:10 | 18:56 | KJ | 2,5 | Nilai langsung: `2.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-16 | Jumat | — | — | O | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-17 | Sabtu | 08:12 | 22:02 | KJ | 8,5 | Nilai langsung: `8.5` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-18 | Minggu | — | — | O | — | — |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-19 | Senin | 08:12 | 19:45 | KJ | 2 | Nilai langsung: `2` |
| 5 | URWATUL USKA | Helper Mekanik | 2026-01-20 | Selasa | 08:09 | 17:13 | KJ | — | — |

### Informasi administrasi dan pengesahan

| Elemen | Isi |
| :--- | :--- |
| Tanggal yang tertulis | Duri, 27 Desember 2025 |
| Diketahui oleh | Widya Apriani — Manager Asset |
| Diperiksa oleh | Dany Agung — Head of Equipment |
| Dibuat oleh | Dean Martin — Admin |
| Catatan | Tanggal pengesahan mendahului sebagian besar periode laporan dan perlu diverifikasi. |

## Sheet `Sheet2` — Lap. Detail Absensi

Sheet ini merupakan sumber log absensi rinci untuk periode **21 Desember 2025–20 Januari 2026**, dengan tanggal tabulasi **22 Januari 2026**. Kolom A–B berisi ID dan nama, sedangkan kolom C–AG berisi rekaman pukulan absensi yang digabung sebagai teks. Nilai seperti `07:3007:3017:05` berarti terdapat beberapa scan pada hari yang sama; nilai `\` atau sel kosong dipertahankan sebagai data sumber dan tidak dipaksakan menjadi jam masuk/keluar.

### Daftar personel

| ID sumber | Nama | Hari dengan data scan | Jumlah scan terbaca |
| :--- | :--- | ---: | ---: |
| 0001 | Dany Agung | 26 | 49 |
| 0002 | Guswan Arizal | 17 | 21 |
| 0003 | Heryanto | 0 | 0 |
| 0004 | Gigin Ahmad | 0 | 0 |
| 0008 | Indra | 0 | 0 |
| 0010 | Febri Angga | 0 | 0 |
| 0016 | Fadli | 0 | 0 |
| 0021 | Akirullah | 0 | 0 |
| 0022 | Azril M | 0 | 0 |
| 0023 | Efendi Harahap | 0 | 0 |
| 0026 | Alfiqir | 0 | 0 |
| 0031 | Adi Sarnata | 0 | 0 |
| 41 | WAHYU | 8 | 25 |
| 42 | SOFYAN | 4 | 8 |
| 43 | ILYAS | 4 | 7 |
| 45 | Kuswantoro | 3 | 5 |
| 46 | Adejuanda | 3 | 6 |
| 47 | M.Arief.S | 3 | 10 |
| 48 | BAYU.P | 3 | 8 |
| 49 | ELIESER.S | 3 | 6 |
| 0005 | Samsul Bahri | 22 | 67 |
| 0006 | Dean Martin | 26 | 49 |
| 0007 | Afriyandi | 24 | 63 |
| 0009 | Daniel Sitepu | 24 | 97 |
| 0011 | Suwardi | 29 | 64 |
| 0012 | Joni Septian | 12 | 18 |
| 0013 | Martono | 27 | 57 |
| 33 | ALEXPAULUS | 4 | 8 |
| 32 | AKBARMAULANA | 12 | 38 |
| 0017 | Hendrik | 26 | 73 |
| 0018 | Koeswanto | 25 | 58 |
| 0019 | Amirudin | 21 | 61 |
| 0020 | Darmawan | 24 | 57 |
| 0024 | David Aritonang | 23 | 47 |
| 0025 | Fajar | 23 | 68 |
| 0027 | Jorlan | 30 | 58 |
| 0028 | Sudirman H | 16 | 81 |
| 0029 | Edy Jaka S | 19 | 105 |
| 0030 | Taufiq H | 31 | 125 |
| 34 | FAIKAR | 18 | 63 |
| 35 | GABRIEL | 11 | 47 |
| 36 | wagiman | 23 | 51 |
| 37 | arifrendi | 23 | 49 |
| 38 | firlanda | 29 | 100 |
| 39 | soleh | 27 | 53 |
| 40 | urwatuluska | 15 | 58 |
| 44 | REJEKISIREGAR | 1 | 1 |

### Rekaman scan yang terisi

| ID | Nama | Tanggal | Hari | Nilai mentah | Urutan scan terbaca | Jumlah scan | Scan pertama | Scan terakhir |
| :--- | :--- | :---: | :--- | :--- | :--- | ---: | :---: | :---: |
| 0001 | Dany Agung | 2025-12-21 | Minggu | 06:1421:28 | 06:14<br>21:28 | 2 | 06:14 | 21:28 |
| 0001 | Dany Agung | 2025-12-22 | Senin | 06:3922:32 | 06:39<br>22:32 | 2 | 06:39 | 22:32 |
| 0001 | Dany Agung | 2025-12-23 | Selasa | 07:0422:46 | 07:04<br>22:46 | 2 | 07:04 | 22:46 |
| 0001 | Dany Agung | 2025-12-24 | Rabu | \\\\ | — | 0 | — | — |
| 0001 | Dany Agung | 2025-12-25 | Kamis | 07:3019:40 | 07:30<br>19:40 | 2 | 07:30 | 19:40 |
| 0001 | Dany Agung | 2025-12-26 | Jumat | 06:2522:14 | 06:25<br>22:14 | 2 | 06:25 | 22:14 |
| 0001 | Dany Agung | 2025-12-27 | Sabtu | 06:3022:00 | 06:30<br>22:00 | 2 | 06:30 | 22:00 |
| 0001 | Dany Agung | 2025-12-29 | Senin | 05:4922:14 | 05:49<br>22:14 | 2 | 05:49 | 22:14 |
| 0001 | Dany Agung | 2025-12-30 | Selasa | 07:2321:22 | 07:23<br>21:22 | 2 | 07:23 | 21:22 |
| 0001 | Dany Agung | 2025-12-31 | Rabu | 07:18 | 07:18 | 1 | 07:18 | 07:18 |
| 0001 | Dany Agung | 2026-01-05 | Senin | 06:1818:48 | 06:18<br>18:48 | 2 | 06:18 | 18:48 |
| 0001 | Dany Agung | 2026-01-06 | Selasa | 05:4122:39 | 05:41<br>22:39 | 2 | 05:41 | 22:39 |
| 0001 | Dany Agung | 2026-01-07 | Rabu | 06:0822:53 | 06:08<br>22:53 | 2 | 06:08 | 22:53 |
| 0001 | Dany Agung | 2026-01-08 | Kamis | 06:0623:03 | 06:06<br>23:03 | 2 | 06:06 | 23:03 |
| 0001 | Dany Agung | 2026-01-09 | Jumat | 06:4822:46 | 06:48<br>22:46 | 2 | 06:48 | 22:46 |
| 0001 | Dany Agung | 2026-01-10 | Sabtu | 06:0622:37 | 06:06<br>22:37 | 2 | 06:06 | 22:37 |
| 0001 | Dany Agung | 2026-01-11 | Minggu | 08:1623:08 | 08:16<br>23:08 | 2 | 08:16 | 23:08 |
| 0001 | Dany Agung | 2026-01-12 | Senin | 05:5922:09 | 05:59<br>22:09 | 2 | 05:59 | 22:09 |
| 0001 | Dany Agung | 2026-01-13 | Selasa | 06:1822:56 | 06:18<br>22:56 | 2 | 06:18 | 22:56 |
| 0001 | Dany Agung | 2026-01-14 | Rabu | 06:1923:03 | 06:19<br>23:03 | 2 | 06:19 | 23:03 |
| 0001 | Dany Agung | 2026-01-15 | Kamis | 06:0022:58 | 06:00<br>22:58 | 2 | 06:00 | 22:58 |
| 0001 | Dany Agung | 2026-01-16 | Jumat | 09:4521:59 | 09:45<br>21:59 | 2 | 09:45 | 21:59 |
| 0001 | Dany Agung | 2026-01-17 | Sabtu | 06:0023:5023:51 | 06:00<br>23:50<br>23:51 | 3 | 06:00 | 23:51 |
| 0001 | Dany Agung | 2026-01-18 | Minggu | 09:5222:24 | 09:52<br>22:24 | 2 | 09:52 | 22:24 |
| 0001 | Dany Agung | 2026-01-19 | Senin | 07:08 | 07:08 | 1 | 07:08 | 07:08 |
| 0001 | Dany Agung | 2026-01-20 | Selasa | 06:0522:57 | 06:05<br>22:57 | 2 | 06:05 | 22:57 |
| 0002 | Guswan Arizal | 2025-12-22 | Senin | 08:03 | 08:03 | 1 | 08:03 | 08:03 |
| 0002 | Guswan Arizal | 2025-12-23 | Selasa | 09:27 | 09:27 | 1 | 09:27 | 09:27 |
| 0002 | Guswan Arizal | 2025-12-24 | Rabu | 07:30 | 07:30 | 1 | 07:30 | 07:30 |
| 0002 | Guswan Arizal | 2025-12-26 | Jumat | 07:4922:01 | 07:49<br>22:01 | 2 | 07:49 | 22:01 |
| 0002 | Guswan Arizal | 2025-12-27 | Sabtu | 21:45 | 21:45 | 1 | 21:45 | 21:45 |
| 0002 | Guswan Arizal | 2025-12-29 | Senin | 07:36 | 07:36 | 1 | 07:36 | 07:36 |
| 0002 | Guswan Arizal | 2026-01-02 | Jumat | 07:5019:26 | 07:50<br>19:26 | 2 | 07:50 | 19:26 |
| 0002 | Guswan Arizal | 2026-01-04 | Minggu | 09:58 | 09:58 | 1 | 09:58 | 09:58 |
| 0002 | Guswan Arizal | 2026-01-05 | Senin | 07:4419:15 | 07:44<br>19:15 | 2 | 07:44 | 19:15 |
| 0002 | Guswan Arizal | 2026-01-07 | Rabu | 08:01 | 08:01 | 1 | 08:01 | 08:01 |
| 0002 | Guswan Arizal | 2026-01-08 | Kamis | 07:36 | 07:36 | 1 | 07:36 | 07:36 |
| 0002 | Guswan Arizal | 2026-01-09 | Jumat | 10:10 | 10:10 | 1 | 10:10 | 10:10 |
| 0002 | Guswan Arizal | 2026-01-10 | Sabtu | 08:30 | 08:30 | 1 | 08:30 | 08:30 |
| 0002 | Guswan Arizal | 2026-01-11 | Minggu | 19:22 | 19:22 | 1 | 19:22 | 19:22 |
| 0002 | Guswan Arizal | 2026-01-12 | Senin | 08:03 | 08:03 | 1 | 08:03 | 08:03 |
| 0002 | Guswan Arizal | 2026-01-13 | Selasa | 08:5919:37 | 08:59<br>19:37 | 2 | 08:59 | 19:37 |
| 0002 | Guswan Arizal | 2026-01-20 | Selasa | 08:49 | 08:49 | 1 | 08:49 | 08:49 |
| 41 | WAHYU | 2026-01-08 | Kamis | 08:1716:4516:45 | 08:17<br>16:45<br>16:45 | 3 | 08:17 | 16:45 |
| 41 | WAHYU | 2026-01-09 | Jumat | 07:3316:5516:55 | 07:33<br>16:55<br>16:55 | 3 | 07:33 | 16:55 |
| 41 | WAHYU | 2026-01-10 | Sabtu | 07:3607:3617:0417:04 | 07:36<br>07:36<br>17:04<br>17:04 | 4 | 07:36 | 17:04 |
| 41 | WAHYU | 2026-01-11 | Minggu | 07:3407:34 | 07:34<br>07:34 | 2 | 07:34 | 07:34 |
| 41 | WAHYU | 2026-01-12 | Senin | 07:3807:38 | 07:38<br>07:38 | 2 | 07:38 | 07:38 |
| 41 | WAHYU | 2026-01-13 | Selasa | 07:3707:3717:1917:19 | 07:37<br>07:37<br>17:19<br>17:19 | 4 | 07:37 | 17:19 |
| 41 | WAHYU | 2026-01-14 | Rabu | 07:3607:3616:5516:55 | 07:36<br>07:36<br>16:55<br>16:55 | 4 | 07:36 | 16:55 |
| 41 | WAHYU | 2026-01-15 | Kamis | 07:2007:2016:46 | 07:20<br>07:20<br>16:46 | 3 | 07:20 | 16:46 |
| 42 | SOFYAN | 2026-01-10 | Sabtu | 08:0317:25 | 08:03<br>17:25 | 2 | 08:03 | 17:25 |
| 42 | SOFYAN | 2026-01-12 | Senin | 07:0716:5717:00 | 07:07<br>16:57<br>17:00 | 3 | 07:07 | 17:00 |
| 42 | SOFYAN | 2026-01-13 | Selasa | 07:1216:02 | 07:12<br>16:02 | 2 | 07:12 | 16:02 |
| 42 | SOFYAN | 2026-01-14 | Rabu | 07:21 | 07:21 | 1 | 07:21 | 07:21 |
| 43 | ILYAS | 2026-01-10 | Sabtu | 08:0417:24 | 08:04<br>17:24 | 2 | 08:04 | 17:24 |
| 43 | ILYAS | 2026-01-12 | Senin | 07:0817:00 | 07:08<br>17:00 | 2 | 07:08 | 17:00 |
| 43 | ILYAS | 2026-01-13 | Selasa | 07:1216:02 | 07:12<br>16:02 | 2 | 07:12 | 16:02 |
| 43 | ILYAS | 2026-01-14 | Rabu | 07:15 | 07:15 | 1 | 07:15 | 07:15 |
| 45 | Kuswantoro | 2026-01-12 | Senin | 08:1817:02 | 08:18<br>17:02 | 2 | 08:18 | 17:02 |
| 45 | Kuswantoro | 2026-01-13 | Selasa | 08:0316:36 | 08:03<br>16:36 | 2 | 08:03 | 16:36 |
| 45 | Kuswantoro | 2026-01-14 | Rabu | 08:46 | 08:46 | 1 | 08:46 | 08:46 |
| 46 | Adejuanda | 2026-01-12 | Senin | 09:2117:00 | 09:21<br>17:00 | 2 | 09:21 | 17:00 |
| 46 | Adejuanda | 2026-01-13 | Selasa | 07:0116:02 | 07:01<br>16:02 | 2 | 07:01 | 16:02 |
| 46 | Adejuanda | 2026-01-14 | Rabu | 07:1607:16 | 07:16<br>07:16 | 2 | 07:16 | 07:16 |
| 47 | M.Arief.S | 2026-01-12 | Senin | 09:2317:00 | 09:23<br>17:00 | 2 | 09:23 | 17:00 |
| 47 | M.Arief.S | 2026-01-13 | Selasa | 07:0007:0016:0216:0216:02 | 07:00<br>07:00<br>16:02<br>16:02<br>16:02 | 5 | 07:00 | 16:02 |
| 47 | M.Arief.S | 2026-01-14 | Rabu | 07:1507:1507:15 | 07:15<br>07:15<br>07:15 | 3 | 07:15 | 07:15 |
| 48 | BAYU.P | 2026-01-12 | Senin | 09:2517:00 | 09:25<br>17:00 | 2 | 09:25 | 17:00 |
| 48 | BAYU.P | 2026-01-13 | Selasa | 07:0007:0016:0216:02 | 07:00<br>07:00<br>16:02<br>16:02 | 4 | 07:00 | 16:02 |
| 48 | BAYU.P | 2026-01-14 | Rabu | 07:1507:15 | 07:15<br>07:15 | 2 | 07:15 | 07:15 |
| 49 | ELIESER.S | 2026-01-12 | Senin | 09:2709:2717:00 | 09:27<br>09:27<br>17:00 | 3 | 09:27 | 17:00 |
| 49 | ELIESER.S | 2026-01-13 | Selasa | 07:0116:02 | 07:01<br>16:02 | 2 | 07:01 | 16:02 |
| 49 | ELIESER.S | 2026-01-14 | Rabu | 07:21 | 07:21 | 1 | 07:21 | 07:21 |
| 0005 | Samsul Bahri | 2025-12-22 | Senin | 07:1917:36 | 07:19<br>17:36 | 2 | 07:19 | 17:36 |
| 0005 | Samsul Bahri | 2025-12-23 | Selasa | 07:2518:26 | 07:25<br>18:26 | 2 | 07:25 | 18:26 |
| 0005 | Samsul Bahri | 2025-12-24 | Rabu | 07:2418:16 | 07:24<br>18:16 | 2 | 07:24 | 18:16 |
| 0005 | Samsul Bahri | 2025-12-27 | Sabtu | 07:1818:10 | 07:18<br>18:10 | 2 | 07:18 | 18:10 |
| 0005 | Samsul Bahri | 2025-12-29 | Senin | 07:2318:17 | 07:23<br>18:17 | 2 | 07:23 | 18:17 |
| 0005 | Samsul Bahri | 2025-12-30 | Selasa | 07:2107:2118:1118:11 | 07:21<br>07:21<br>18:11<br>18:11 | 4 | 07:21 | 18:11 |
| 0005 | Samsul Bahri | 2025-12-31 | Rabu | 07:2107:2117:5117:52 | 07:21<br>07:21<br>17:51<br>17:52 | 4 | 07:21 | 17:52 |
| 0005 | Samsul Bahri | 2026-01-02 | Jumat | 07:2107:2117:3917:39 | 07:21<br>07:21<br>17:39<br>17:39 | 4 | 07:21 | 17:39 |
| 0005 | Samsul Bahri | 2026-01-03 | Sabtu | 07:2707:2714:23 | 07:27<br>07:27<br>14:23 | 3 | 07:27 | 14:23 |
| 0005 | Samsul Bahri | 2026-01-05 | Senin | 07:2617:3517:35 | 07:26<br>17:35<br>17:35 | 3 | 07:26 | 17:35 |
| 0005 | Samsul Bahri | 2026-01-06 | Selasa | 07:2707:2717:2117:21 | 07:27<br>07:27<br>17:21<br>17:21 | 4 | 07:27 | 17:21 |
| 0005 | Samsul Bahri | 2026-01-07 | Rabu | 07:3607:3616:4916:4916:49 | 07:36<br>07:36<br>16:49<br>16:49<br>16:49 | 5 | 07:36 | 16:49 |
| 0005 | Samsul Bahri | 2026-01-08 | Kamis | 07:3807:3817:3817:38 | 07:38<br>07:38<br>17:38<br>17:38 | 4 | 07:38 | 17:38 |
| 0005 | Samsul Bahri | 2026-01-09 | Jumat | 07:3007:3017:27 | 07:30<br>07:30<br>17:27 | 3 | 07:30 | 17:27 |
| 0005 | Samsul Bahri | 2026-01-10 | Sabtu | 07:2207:2315:56 | 07:22<br>07:23<br>15:56 | 3 | 07:22 | 15:56 |
| 0005 | Samsul Bahri | 2026-01-12 | Senin | 07:3107:3117:16 | 07:31<br>07:31<br>17:16 | 3 | 07:31 | 17:16 |
| 0005 | Samsul Bahri | 2026-01-13 | Selasa | 07:27 | 07:27 | 1 | 07:27 | 07:27 |
| 0005 | Samsul Bahri | 2026-01-14 | Rabu | 07:2507:2517:05 | 07:25<br>07:25<br>17:05 | 3 | 07:25 | 17:05 |
| 0005 | Samsul Bahri | 2026-01-15 | Kamis | 07:3207:3217:06 | 07:32<br>07:32<br>17:06 | 3 | 07:32 | 17:06 |
| 0005 | Samsul Bahri | 2026-01-17 | Sabtu | 07:2207:2213:41 | 07:22<br>07:22<br>13:41 | 3 | 07:22 | 13:41 |
| 0005 | Samsul Bahri | 2026-01-19 | Senin | 07:2918:0818:08 | 07:29<br>18:08<br>18:08 | 3 | 07:29 | 18:08 |
| 0005 | Samsul Bahri | 2026-01-20 | Selasa | 07:2707:2717:5617:56 | 07:27<br>07:27<br>17:56<br>17:56 | 4 | 07:27 | 17:56 |
| 0006 | Dean Martin | 2025-12-22 | Senin | 07:4319:07 | 07:43<br>19:07 | 2 | 07:43 | 19:07 |
| 0006 | Dean Martin | 2025-12-23 | Selasa | 07:3518:42 | 07:35<br>18:42 | 2 | 07:35 | 18:42 |
| 0006 | Dean Martin | 2025-12-24 | Rabu | 07:3918:21 | 07:39<br>18:21 | 2 | 07:39 | 18:21 |
| 0006 | Dean Martin | 2025-12-25 | Kamis | 08:1721:36 | 08:17<br>21:36 | 2 | 08:17 | 21:36 |
| 0006 | Dean Martin | 2025-12-26 | Jumat | 08:5022:19 | 08:50<br>22:19 | 2 | 08:50 | 22:19 |
| 0006 | Dean Martin | 2025-12-27 | Sabtu | 07:5416:13 | 07:54<br>16:13 | 2 | 07:54 | 16:13 |
| 0006 | Dean Martin | 2025-12-29 | Senin | 07:32 | 07:32 | 1 | 07:32 | 07:32 |
| 0006 | Dean Martin | 2025-12-30 | Selasa | 07:4818:06 | 07:48<br>18:06 | 2 | 07:48 | 18:06 |
| 0006 | Dean Martin | 2025-12-31 | Rabu | 07:3818:25 | 07:38<br>18:25 | 2 | 07:38 | 18:25 |
| 0006 | Dean Martin | 2026-01-01 | Kamis | 14:12 | 14:12 | 1 | 14:12 | 14:12 |
| 0006 | Dean Martin | 2026-01-02 | Jumat | 07:5517:42 | 07:55<br>17:42 | 2 | 07:55 | 17:42 |
| 0006 | Dean Martin | 2026-01-03 | Sabtu | 07:2607:4819:18 | 07:26<br>07:48<br>19:18 | 3 | 07:26 | 19:18 |
| 0006 | Dean Martin | 2026-01-05 | Senin | 08:0819:23 | 08:08<br>19:23 | 2 | 08:08 | 19:23 |
| 0006 | Dean Martin | 2026-01-06 | Selasa | 07:3618:42 | 07:36<br>18:42 | 2 | 07:36 | 18:42 |
| 0006 | Dean Martin | 2026-01-07 | Rabu | 07:4218:55 | 07:42<br>18:55 | 2 | 07:42 | 18:55 |
| 0006 | Dean Martin | 2026-01-08 | Kamis | 07:41 | 07:41 | 1 | 07:41 | 07:41 |
| 0006 | Dean Martin | 2026-01-09 | Jumat | 07:54 | 07:54 | 1 | 07:54 | 07:54 |
| 0006 | Dean Martin | 2026-01-10 | Sabtu | 07:4315:36 | 07:43<br>15:36 | 2 | 07:43 | 15:36 |
| 0006 | Dean Martin | 2026-01-12 | Senin | 07:5519:34 | 07:55<br>19:34 | 2 | 07:55 | 19:34 |
| 0006 | Dean Martin | 2026-01-13 | Selasa | 07:4718:30 | 07:47<br>18:30 | 2 | 07:47 | 18:30 |
| 0006 | Dean Martin | 2026-01-14 | Rabu | 08:0118:50 | 08:01<br>18:50 | 2 | 08:01 | 18:50 |
| 0006 | Dean Martin | 2026-01-15 | Kamis | 07:4717:46 | 07:47<br>17:46 | 2 | 07:47 | 17:46 |
| 0006 | Dean Martin | 2026-01-16 | Jumat | 07:52 | 07:52 | 1 | 07:52 | 07:52 |
| 0006 | Dean Martin | 2026-01-17 | Sabtu | 08:0208:0617:55 | 08:02<br>08:06<br>17:55 | 3 | 08:02 | 17:55 |
| 0006 | Dean Martin | 2026-01-19 | Senin | 08:1219:54 | 08:12<br>19:54 | 2 | 08:12 | 19:54 |
| 0006 | Dean Martin | 2026-01-20 | Selasa | 07:3820:36 | 07:38<br>20:36 | 2 | 07:38 | 20:36 |
| 0007 | Afriyandi | 2025-12-21 | Minggu | 08:0221:26 | 08:02<br>21:26 | 2 | 08:02 | 21:26 |
| 0007 | Afriyandi | 2025-12-22 | Senin | 07:3907:4420:59 | 07:39<br>07:44<br>20:59 | 3 | 07:39 | 20:59 |
| 0007 | Afriyandi | 2025-12-23 | Selasa | 07:4122:02 | 07:41<br>22:02 | 2 | 07:41 | 22:02 |
| 0007 | Afriyandi | 2025-12-24 | Rabu | 07:3721:06 | 07:37<br>21:06 | 2 | 07:37 | 21:06 |
| 0007 | Afriyandi | 2025-12-25 | Kamis | 07:5219:39 | 07:52<br>19:39 | 2 | 07:52 | 19:39 |
| 0007 | Afriyandi | 2025-12-26 | Jumat | 07:3922:12 | 07:39<br>22:12 | 2 | 07:39 | 22:12 |
| 0007 | Afriyandi | 2025-12-27 | Sabtu | 07:43 | 07:43 | 1 | 07:43 | 07:43 |
| 0007 | Afriyandi | 2025-12-31 | Rabu | 07:5318:32 | 07:53<br>18:32 | 2 | 07:53 | 18:32 |
| 0007 | Afriyandi | 2026-01-02 | Jumat | 07:4008:0021:00 | 07:40<br>08:00<br>21:00 | 3 | 07:40 | 21:00 |
| 0007 | Afriyandi | 2026-01-03 | Sabtu | 07:5313:56 | 07:53<br>13:56 | 2 | 07:53 | 13:56 |
| 0007 | Afriyandi | 2026-01-04 | Minggu | 08:4216:0816:0816:0816:0816:08 | 08:42<br>16:08<br>16:08<br>16:08<br>16:08<br>16:08 | 6 | 08:42 | 16:08 |
| 0007 | Afriyandi | 2026-01-05 | Senin | 07:4018:32 | 07:40<br>18:32 | 2 | 07:40 | 18:32 |
| 0007 | Afriyandi | 2026-01-06 | Selasa | 07:4722:28 | 07:47<br>22:28 | 2 | 07:47 | 22:28 |
| 0007 | Afriyandi | 2026-01-07 | Rabu | 07:4522:57 | 07:45<br>22:57 | 2 | 07:45 | 22:57 |
| 0007 | Afriyandi | 2026-01-08 | Kamis | 07:33 | 07:33 | 1 | 07:33 | 07:33 |
| 0007 | Afriyandi | 2026-01-09 | Jumat | 22:1622:16 | 22:16<br>22:16 | 2 | 22:16 | 22:16 |
| 0007 | Afriyandi | 2026-01-10 | Sabtu | 07:4022:0322:0322:0322:03 | 07:40<br>22:03<br>22:03<br>22:03<br>22:03 | 5 | 07:40 | 22:03 |
| 0007 | Afriyandi | 2026-01-11 | Minggu | 08:1518:43 | 08:15<br>18:43 | 2 | 08:15 | 18:43 |
| 0007 | Afriyandi | 2026-01-12 | Senin | 07:4819:43 | 07:48<br>19:43 | 2 | 07:48 | 19:43 |
| 0007 | Afriyandi | 2026-01-13 | Selasa | 07:37 | 07:37 | 1 | 07:37 | 07:37 |
| 0007 | Afriyandi | 2026-01-14 | Rabu | 00:1300:1300:1300:1300:1300:1307:4407:5822:3422:37 | 00:13<br>00:13<br>00:13<br>00:13<br>00:13<br>00:13<br>07:44<br>07:58<br>22:34<br>22:37 | 10 | 00:13 | 22:37 |
| 0007 | Afriyandi | 2026-01-15 | Kamis | 07:2718:56 | 07:27<br>18:56 | 2 | 07:27 | 18:56 |
| 0007 | Afriyandi | 2026-01-19 | Senin | 07:4208:1022:13 | 07:42<br>08:10<br>22:13 | 3 | 07:42 | 22:13 |
| 0007 | Afriyandi | 2026-01-20 | Selasa | 07:4107:43 | 07:41<br>07:43 | 2 | 07:41 | 07:43 |
| 0009 | Daniel Sitepu | 2025-12-21 | Minggu | 08:0521:1221:12 | 08:05<br>21:12<br>21:12 | 3 | 08:05 | 21:12 |
| 0009 | Daniel Sitepu | 2025-12-22 | Senin | 20:5920:59 | 20:59<br>20:59 | 2 | 20:59 | 20:59 |
| 0009 | Daniel Sitepu | 2025-12-23 | Selasa | 07:4807:4822:0322:03 | 07:48<br>07:48<br>22:03<br>22:03 | 4 | 07:48 | 22:03 |
| 0009 | Daniel Sitepu | 2025-12-24 | Rabu | 07:53 | 07:53 | 1 | 07:53 | 07:53 |
| 0009 | Daniel Sitepu | 2025-12-25 | Kamis | 07:4207:4216:0716:07 | 07:42<br>07:42<br>16:07<br>16:07 | 4 | 07:42 | 16:07 |
| 0009 | Daniel Sitepu | 2025-12-26 | Jumat | 07:3807:3816:5416:54 | 07:38<br>07:38<br>16:54<br>16:54 | 4 | 07:38 | 16:54 |
| 0009 | Daniel Sitepu | 2025-12-27 | Sabtu | 07:4107:4119:2719:27 | 07:41<br>07:41<br>19:27<br>19:27 | 4 | 07:41 | 19:27 |
| 0009 | Daniel Sitepu | 2025-12-29 | Senin | 07:4520:5320:53 | 07:45<br>20:53<br>20:53 | 3 | 07:45 | 20:53 |
| 0009 | Daniel Sitepu | 2025-12-30 | Selasa | 07:4307:4318:3018:30 | 07:43<br>07:43<br>18:30<br>18:30 | 4 | 07:43 | 18:30 |
| 0009 | Daniel Sitepu | 2026-01-02 | Jumat | 07:5807:5821:0021:00 | 07:58<br>07:58<br>21:00<br>21:00 | 4 | 07:58 | 21:00 |
| 0009 | Daniel Sitepu | 2026-01-03 | Sabtu | 07:3507:3513:56 | 07:35<br>07:35<br>13:56 | 3 | 07:35 | 13:56 |
| 0009 | Daniel Sitepu | 2026-01-04 | Minggu | 07:3007:3016:0916:09 | 07:30<br>07:30<br>16:09<br>16:09 | 4 | 07:30 | 16:09 |
| 0009 | Daniel Sitepu | 2026-01-05 | Senin | 07:3307:3318:3218:32 | 07:33<br>07:33<br>18:32<br>18:32 | 4 | 07:33 | 18:32 |
| 0009 | Daniel Sitepu | 2026-01-07 | Rabu | 07:2722:5622:5622:56 | 07:27<br>22:56<br>22:56<br>22:56 | 4 | 07:27 | 22:56 |
| 0009 | Daniel Sitepu | 2026-01-08 | Kamis | 07:5107:5122:49 | 07:51<br>07:51<br>22:49 | 3 | 07:51 | 22:49 |
| 0009 | Daniel Sitepu | 2026-01-09 | Jumat | 07:4207:4217:1517:15 | 07:42<br>07:42<br>17:15<br>17:15 | 4 | 07:42 | 17:15 |
| 0009 | Daniel Sitepu | 2026-01-10 | Sabtu | 07:4307:4318:1118:1118:1118:13 | 07:43<br>07:43<br>18:11<br>18:11<br>18:11<br>18:13 | 6 | 07:43 | 18:13 |
| 0009 | Daniel Sitepu | 2026-01-12 | Senin | 07:5107:5107:5118:5918:59 | 07:51<br>07:51<br>07:51<br>18:59<br>18:59 | 5 | 07:51 | 18:59 |
| 0009 | Daniel Sitepu | 2026-01-13 | Selasa | 07:4707:4719:3719:37 | 07:47<br>07:47<br>19:37<br>19:37 | 4 | 07:47 | 19:37 |
| 0009 | Daniel Sitepu | 2026-01-14 | Rabu | 07:3907:3919:1219:1219:12 | 07:39<br>07:39<br>19:12<br>19:12<br>19:12 | 5 | 07:39 | 19:12 |
| 0009 | Daniel Sitepu | 2026-01-15 | Kamis | 07:3707:3707:5819:0519:06 | 07:37<br>07:37<br>07:58<br>19:05<br>19:06 | 5 | 07:37 | 19:06 |
| 0009 | Daniel Sitepu | 2026-01-17 | Sabtu | 07:3507:3517:1817:1817:1922:0222:0222:03 | 07:35<br>07:35<br>17:18<br>17:18<br>17:19<br>22:02<br>22:02<br>22:03 | 8 | 07:35 | 22:03 |
| 0009 | Daniel Sitepu | 2026-01-19 | Senin | 07:4507:4519:4219:4419:44 | 07:45<br>07:45<br>19:42<br>19:44<br>19:44 | 5 | 07:45 | 19:44 |
| 0009 | Daniel Sitepu | 2026-01-20 | Selasa | 07:4807:4817:1217:12 | 07:48<br>07:48<br>17:12<br>17:12 | 4 | 07:48 | 17:12 |
| 0011 | Suwardi | 2025-12-21 | Minggu | 07:4821:19 | 07:48<br>21:19 | 2 | 07:48 | 21:19 |
| 0011 | Suwardi | 2025-12-22 | Senin | 07:3621:33 | 07:36<br>21:33 | 2 | 07:36 | 21:33 |
| 0011 | Suwardi | 2025-12-23 | Selasa | 07:3322:05 | 07:33<br>22:05 | 2 | 07:33 | 22:05 |
| 0011 | Suwardi | 2025-12-24 | Rabu | 07:4721:07 | 07:47<br>21:07 | 2 | 07:47 | 21:07 |
| 0011 | Suwardi | 2025-12-25 | Kamis | 07:1320:30 | 07:13<br>20:30 | 2 | 07:13 | 20:30 |
| 0011 | Suwardi | 2025-12-26 | Jumat | 07:3522:14 | 07:35<br>22:14 | 2 | 07:35 | 22:14 |
| 0011 | Suwardi | 2025-12-27 | Sabtu | 07:3822:1222:13 | 07:38<br>22:12<br>22:13 | 3 | 07:38 | 22:13 |
| 0011 | Suwardi | 2025-12-28 | Minggu | 07:4716:06 | 07:47<br>16:06 | 2 | 07:47 | 16:06 |
| 0011 | Suwardi | 2025-12-29 | Senin | 07:44 | 07:44 | 1 | 07:44 | 07:44 |
| 0011 | Suwardi | 2025-12-30 | Selasa | 07:3321:08 | 07:33<br>21:08 | 2 | 07:33 | 21:08 |
| 0011 | Suwardi | 2025-12-31 | Rabu | 07:3720:06 | 07:37<br>20:06 | 2 | 07:37 | 20:06 |
| 0011 | Suwardi | 2026-01-02 | Jumat | 07:3221:0421:04 | 07:32<br>21:04<br>21:04 | 3 | 07:32 | 21:04 |
| 0011 | Suwardi | 2026-01-03 | Sabtu | 07:0207:4713:22 | 07:02<br>07:47<br>13:22 | 3 | 07:02 | 13:22 |
| 0011 | Suwardi | 2026-01-04 | Minggu | 07:3616:08 | 07:36<br>16:08 | 2 | 07:36 | 16:08 |
| 0011 | Suwardi | 2026-01-05 | Senin | 07:3619:1219:12 | 07:36<br>19:12<br>19:12 | 3 | 07:36 | 19:12 |
| 0011 | Suwardi | 2026-01-06 | Selasa | 07:3422:31 | 07:34<br>22:31 | 2 | 07:34 | 22:31 |
| 0011 | Suwardi | 2026-01-07 | Rabu | 07:3823:02 | 07:38<br>23:02 | 2 | 07:38 | 23:02 |
| 0011 | Suwardi | 2026-01-08 | Kamis | 07:4923:05 | 07:49<br>23:05 | 2 | 07:49 | 23:05 |
| 0011 | Suwardi | 2026-01-09 | Jumat | 07:4019:1019:10 | 07:40<br>19:10<br>19:10 | 3 | 07:40 | 19:10 |
| 0011 | Suwardi | 2026-01-10 | Sabtu | 07:4622:13 | 07:46<br>22:13 | 2 | 07:46 | 22:13 |
| 0011 | Suwardi | 2026-01-11 | Minggu | 07:3519:05 | 07:35<br>19:05 | 2 | 07:35 | 19:05 |
| 0011 | Suwardi | 2026-01-12 | Senin | 07:4020:00 | 07:40<br>20:00 | 2 | 07:40 | 20:00 |
| 0011 | Suwardi | 2026-01-13 | Selasa | 07:39 | 07:39 | 1 | 07:39 | 07:39 |
| 0011 | Suwardi | 2026-01-14 | Rabu | 00:1300:1307:3623:00 | 00:13<br>00:13<br>07:36<br>23:00 | 4 | 00:13 | 23:00 |
| 0011 | Suwardi | 2026-01-15 | Kamis | 07:4619:06 | 07:46<br>19:06 | 2 | 07:46 | 19:06 |
| 0011 | Suwardi | 2026-01-17 | Sabtu | 07:4222:16 | 07:42<br>22:16 | 2 | 07:42 | 22:16 |
| 0011 | Suwardi | 2026-01-18 | Minggu | 07:3022:1822:18 | 07:30<br>22:18<br>22:18 | 3 | 07:30 | 22:18 |
| 0011 | Suwardi | 2026-01-19 | Senin | 07:3522:12 | 07:35<br>22:12 | 2 | 07:35 | 22:12 |
| 0011 | Suwardi | 2026-01-20 | Selasa | 07:4419:17 | 07:44<br>19:17 | 2 | 07:44 | 19:17 |
| 0012 | Joni Septian | 2025-12-22 | Senin | 21:07 | 21:07 | 1 | 21:07 | 21:07 |
| 0012 | Joni Septian | 2025-12-23 | Selasa | 07:5622:02 | 07:56<br>22:02 | 2 | 07:56 | 22:02 |
| 0012 | Joni Septian | 2025-12-24 | Rabu | 07:3721:07 | 07:37<br>21:07 | 2 | 07:37 | 21:07 |
| 0012 | Joni Septian | 2025-12-25 | Kamis | 07:5219:39 | 07:52<br>19:39 | 2 | 07:52 | 19:39 |
| 0012 | Joni Septian | 2025-12-26 | Jumat | 07:3922:12 | 07:39<br>22:12 | 2 | 07:39 | 22:12 |
| 0012 | Joni Septian | 2025-12-27 | Sabtu | 07:54 | 07:54 | 1 | 07:54 | 07:54 |
| 0012 | Joni Septian | 2025-12-29 | Senin | 07:5020:53 | 07:50<br>20:53 | 2 | 07:50 | 20:53 |
| 0012 | Joni Septian | 2025-12-30 | Selasa | 07:5218:31 | 07:52<br>18:31 | 2 | 07:52 | 18:31 |
| 0012 | Joni Septian | 2025-12-31 | Rabu | 08:42 | 08:42 | 1 | 08:42 | 08:42 |
| 0012 | Joni Septian | 2026-01-02 | Jumat | 07:53 | 07:53 | 1 | 07:53 | 07:53 |
| 0012 | Joni Septian | 2026-01-08 | Kamis | 08:07 | 08:07 | 1 | 08:07 | 08:07 |
| 0012 | Joni Septian | 2026-01-12 | Senin | 20:09 | 20:09 | 1 | 20:09 | 20:09 |
| 0013 | Martono | 2025-12-21 | Minggu | 08:5317:2019:20 | 08:53<br>17:20<br>19:20 | 3 | 08:53 | 19:20 |
| 0013 | Martono | 2025-12-22 | Senin | 07:3207:3317:33 | 07:32<br>07:33<br>17:33 | 3 | 07:32 | 17:33 |
| 0013 | Martono | 2025-12-23 | Selasa | 07:3418:50 | 07:34<br>18:50 | 2 | 07:34 | 18:50 |
| 0013 | Martono | 2025-12-24 | Rabu | 07:3816:21 | 07:38<br>16:21 | 2 | 07:38 | 16:21 |
| 0013 | Martono | 2025-12-25 | Kamis | 09:5316:3217:32 | 09:53<br>16:32<br>17:32 | 3 | 09:53 | 17:32 |
| 0013 | Martono | 2025-12-26 | Jumat | 07:2922:01 | 07:29<br>22:01 | 2 | 07:29 | 22:01 |
| 0013 | Martono | 2025-12-27 | Sabtu | 07:4420:03 | 07:44<br>20:03 | 2 | 07:44 | 20:03 |
| 0013 | Martono | 2025-12-28 | Minggu | 08:3018:17 | 08:30<br>18:17 | 2 | 08:30 | 18:17 |
| 0013 | Martono | 2025-12-29 | Senin | 07:3620:56 | 07:36<br>20:56 | 2 | 07:36 | 20:56 |
| 0013 | Martono | 2025-12-30 | Selasa | 07:3520:58 | 07:35<br>20:58 | 2 | 07:35 | 20:58 |
| 0013 | Martono | 2025-12-31 | Rabu | 07:4118:26 | 07:41<br>18:26 | 2 | 07:41 | 18:26 |
| 0013 | Martono | 2026-01-05 | Senin | 17:47 | 17:47 | 1 | 17:47 | 17:47 |
| 0013 | Martono | 2026-01-06 | Selasa | 07:4121:12 | 07:41<br>21:12 | 2 | 07:41 | 21:12 |
| 0013 | Martono | 2026-01-07 | Rabu | 07:3818:25 | 07:38<br>18:25 | 2 | 07:38 | 18:25 |
| 0013 | Martono | 2026-01-08 | Kamis | 07:4017:04 | 07:40<br>17:04 | 2 | 07:40 | 17:04 |
| 0013 | Martono | 2026-01-09 | Jumat | 07:3220:03 | 07:32<br>20:03 | 2 | 07:32 | 20:03 |
| 0013 | Martono | 2026-01-10 | Sabtu | 07:3117:28 | 07:31<br>17:28 | 2 | 07:31 | 17:28 |
| 0013 | Martono | 2026-01-11 | Minggu | 08:1218:1318:13 | 08:12<br>18:13<br>18:13 | 3 | 08:12 | 18:13 |
| 0013 | Martono | 2026-01-12 | Senin | 07:3317:16 | 07:33<br>17:16 | 2 | 07:33 | 17:16 |
| 0013 | Martono | 2026-01-13 | Selasa | 07:3217:23 | 07:32<br>17:23 | 2 | 07:32 | 17:23 |
| 0013 | Martono | 2026-01-14 | Rabu | 07:3216:5020:01 | 07:32<br>16:50<br>20:01 | 3 | 07:32 | 20:01 |
| 0013 | Martono | 2026-01-15 | Kamis | 07:3116:14 | 07:31<br>16:14 | 2 | 07:31 | 16:14 |
| 0013 | Martono | 2026-01-16 | Jumat | 08:09 | 08:09 | 1 | 08:09 | 08:09 |
| 0013 | Martono | 2026-01-17 | Sabtu | 07:3415:42 | 07:34<br>15:42 | 2 | 07:34 | 15:42 |
| 0013 | Martono | 2026-01-18 | Minggu | 08:2018:17 | 08:20<br>18:17 | 2 | 08:20 | 18:17 |
| 0013 | Martono | 2026-01-19 | Senin | 07:3717:00 | 07:37<br>17:00 | 2 | 07:37 | 17:00 |
| 0013 | Martono | 2026-01-20 | Selasa | 07:3516:21 | 07:35<br>16:21 | 2 | 07:35 | 16:21 |
| 33 | ALEXPAULUS | 2026-01-05 | Senin | 08:0916:2416:24 | 08:09<br>16:24<br>16:24 | 3 | 08:09 | 16:24 |
| 33 | ALEXPAULUS | 2026-01-06 | Selasa | 18:0218:02 | 18:02<br>18:02 | 2 | 18:02 | 18:02 |
| 33 | ALEXPAULUS | 2026-01-07 | Rabu | 08:1408:14 | 08:14<br>08:14 | 2 | 08:14 | 08:14 |
| 33 | ALEXPAULUS | 2026-01-09 | Jumat | 07:37 | 07:37 | 1 | 07:37 | 07:37 |
| 32 | AKBARMAULANA | 2025-12-22 | Senin | 07:5607:5620:5920:59 | 07:56<br>07:56<br>20:59<br>20:59 | 4 | 07:56 | 20:59 |
| 32 | AKBARMAULANA | 2025-12-23 | Selasa | 08:0508:0522:0222:02 | 08:05<br>08:05<br>22:02<br>22:02 | 4 | 08:05 | 22:02 |
| 32 | AKBARMAULANA | 2025-12-27 | Sabtu | 07:4107:41 | 07:41<br>07:41 | 2 | 07:41 | 07:41 |
| 32 | AKBARMAULANA | 2025-12-29 | Senin | 08:1108:1119:1919:19 | 08:11<br>08:11<br>19:19<br>19:19 | 4 | 08:11 | 19:19 |
| 32 | AKBARMAULANA | 2025-12-30 | Selasa | 08:0818:3018:30 | 08:08<br>18:30<br>18:30 | 3 | 08:08 | 18:30 |
| 32 | AKBARMAULANA | 2025-12-31 | Rabu | 08:0218:3218:32 | 08:02<br>18:32<br>18:32 | 3 | 08:02 | 18:32 |
| 32 | AKBARMAULANA | 2026-01-03 | Sabtu | 07:4007:4013:5613:56 | 07:40<br>07:40<br>13:56<br>13:56 | 4 | 07:40 | 13:56 |
| 32 | AKBARMAULANA | 2026-01-05 | Senin | 08:0908:0916:2416:24 | 08:09<br>08:09<br>16:24<br>16:24 | 4 | 08:09 | 16:24 |
| 32 | AKBARMAULANA | 2026-01-06 | Selasa | 07:4518:0218:02 | 07:45<br>18:02<br>18:02 | 3 | 07:45 | 18:02 |
| 32 | AKBARMAULANA | 2026-01-07 | Rabu | 08:1308:13 | 08:13<br>08:13 | 2 | 08:13 | 08:13 |
| 32 | AKBARMAULANA | 2026-01-08 | Kamis | 07:3417:3317:33 | 07:34<br>17:33<br>17:33 | 3 | 07:34 | 17:33 |
| 32 | AKBARMAULANA | 2026-01-09 | Jumat | 07:3707:37 | 07:37<br>07:37 | 2 | 07:37 | 07:37 |
| 0017 | Hendrik | 2025-12-21 | Minggu | 07:5421:1221:12 | 07:54<br>21:12<br>21:12 | 3 | 07:54 | 21:12 |
| 0017 | Hendrik | 2025-12-22 | Senin | 07:3321:33 | 07:33<br>21:33 | 2 | 07:33 | 21:33 |
| 0017 | Hendrik | 2025-12-23 | Selasa | 07:3822:06 | 07:38<br>22:06 | 2 | 07:38 | 22:06 |
| 0017 | Hendrik | 2025-12-24 | Rabu | 07:3417:28 | 07:34<br>17:28 | 2 | 07:34 | 17:28 |
| 0017 | Hendrik | 2025-12-25 | Kamis | 07:5920:31 | 07:59<br>20:31 | 2 | 07:59 | 20:31 |
| 0017 | Hendrik | 2025-12-26 | Jumat | 07:3022:1522:15 | 07:30<br>22:15<br>22:15 | 3 | 07:30 | 22:15 |
| 0017 | Hendrik | 2025-12-27 | Sabtu | 07:3622:1322:13 | 07:36<br>22:13<br>22:13 | 3 | 07:36 | 22:13 |
| 0017 | Hendrik | 2025-12-28 | Minggu | 08:1716:0616:06 | 08:17<br>16:06<br>16:06 | 3 | 08:17 | 16:06 |
| 0017 | Hendrik | 2025-12-29 | Senin | 07:2807:2822:0122:01 | 07:28<br>07:28<br>22:01<br>22:01 | 4 | 07:28 | 22:01 |
| 0017 | Hendrik | 2025-12-30 | Selasa | 07:3007:3018:3921:08 | 07:30<br>07:30<br>18:39<br>21:08 | 4 | 07:30 | 21:08 |
| 0017 | Hendrik | 2025-12-31 | Rabu | 07:3520:50 | 07:35<br>20:50 | 2 | 07:35 | 20:50 |
| 0017 | Hendrik | 2026-01-02 | Jumat | 07:3207:3219:4120:05 | 07:32<br>07:32<br>19:41<br>20:05 | 4 | 07:32 | 20:05 |
| 0017 | Hendrik | 2026-01-06 | Selasa | 07:4207:4222:30 | 07:42<br>07:42<br>22:30 | 3 | 07:42 | 22:30 |
| 0017 | Hendrik | 2026-01-07 | Rabu | 07:3122:12 | 07:31<br>22:12 | 2 | 07:31 | 22:12 |
| 0017 | Hendrik | 2026-01-08 | Kamis | 07:4023:05 | 07:40<br>23:05 | 2 | 07:40 | 23:05 |
| 0017 | Hendrik | 2026-01-09 | Jumat | 07:2918:17 | 07:29<br>18:17 | 2 | 07:29 | 18:17 |
| 0017 | Hendrik | 2026-01-10 | Sabtu | 07:1018:29 | 07:10<br>18:29 | 2 | 07:10 | 18:29 |
| 0017 | Hendrik | 2026-01-11 | Minggu | 08:0418:35 | 08:04<br>18:35 | 2 | 08:04 | 18:35 |
| 0017 | Hendrik | 2026-01-12 | Senin | 07:2907:2920:0020:0020:00 | 07:29<br>07:29<br>20:00<br>20:00<br>20:00 | 5 | 07:29 | 20:00 |
| 0017 | Hendrik | 2026-01-13 | Selasa | 07:41 | 07:41 | 1 | 07:41 | 07:41 |
| 0017 | Hendrik | 2026-01-14 | Rabu | 00:1307:3822:5922:5923:0023:0023:0023:00 | 00:13<br>07:38<br>22:59<br>22:59<br>23:00<br>23:00<br>23:00<br>23:00 | 8 | 00:13 | 23:00 |
| 0017 | Hendrik | 2026-01-15 | Kamis | 07:3719:0519:05 | 07:37<br>19:05<br>19:05 | 3 | 07:37 | 19:05 |
| 0017 | Hendrik | 2026-01-17 | Sabtu | 07:4622:1322:13 | 07:46<br>22:13<br>22:13 | 3 | 07:46 | 22:13 |
| 0017 | Hendrik | 2026-01-18 | Minggu | 07:5622:20 | 07:56<br>22:20 | 2 | 07:56 | 22:20 |
| 0017 | Hendrik | 2026-01-19 | Senin | 07:2422:12 | 07:24<br>22:12 | 2 | 07:24 | 22:12 |
| 0017 | Hendrik | 2026-01-20 | Selasa | 07:4819:17 | 07:48<br>19:17 | 2 | 07:48 | 19:17 |
| 0018 | Koeswanto | 2025-12-22 | Senin | 07:3216:10 | 07:32<br>16:10 | 2 | 07:32 | 16:10 |
| 0018 | Koeswanto | 2025-12-23 | Selasa | 07:3516:03 | 07:35<br>16:03 | 2 | 07:35 | 16:03 |
| 0018 | Koeswanto | 2025-12-24 | Rabu | 07:3916:00 | 07:39<br>16:00 | 2 | 07:39 | 16:00 |
| 0018 | Koeswanto | 2025-12-26 | Jumat | 07:3916:11 | 07:39<br>16:11 | 2 | 07:39 | 16:11 |
| 0018 | Koeswanto | 2025-12-27 | Sabtu | 07:3916:00 | 07:39<br>16:00 | 2 | 07:39 | 16:00 |
| 0018 | Koeswanto | 2025-12-29 | Senin | 07:4116:02 | 07:41<br>16:02 | 2 | 07:41 | 16:02 |
| 0018 | Koeswanto | 2025-12-30 | Selasa | 07:3315:58 | 07:33<br>15:58 | 2 | 07:33 | 15:58 |
| 0018 | Koeswanto | 2025-12-31 | Rabu | 07:3416:3916:39 | 07:34<br>16:39<br>16:39 | 3 | 07:34 | 16:39 |
| 0018 | Koeswanto | 2026-01-02 | Jumat | 07:3716:0116:01 | 07:37<br>16:01<br>16:01 | 3 | 07:37 | 16:01 |
| 0018 | Koeswanto | 2026-01-03 | Sabtu | 07:3814:36 | 07:38<br>14:36 | 2 | 07:38 | 14:36 |
| 0018 | Koeswanto | 2026-01-04 | Minggu | 07:4915:30 | 07:49<br>15:30 | 2 | 07:49 | 15:30 |
| 0018 | Koeswanto | 2026-01-05 | Senin | 07:3416:01 | 07:34<br>16:01 | 2 | 07:34 | 16:01 |
| 0018 | Koeswanto | 2026-01-06 | Selasa | 07:3916:01 | 07:39<br>16:01 | 2 | 07:39 | 16:01 |
| 0018 | Koeswanto | 2026-01-07 | Rabu | 07:3716:27 | 07:37<br>16:27 | 2 | 07:37 | 16:27 |
| 0018 | Koeswanto | 2026-01-08 | Kamis | 07:4216:24 | 07:42<br>16:24 | 2 | 07:42 | 16:24 |
| 0018 | Koeswanto | 2026-01-09 | Jumat | 07:3016:00 | 07:30<br>16:00 | 2 | 07:30 | 16:00 |
| 0018 | Koeswanto | 2026-01-10 | Sabtu | 07:3415:5615:56 | 07:34<br>15:56<br>15:56 | 3 | 07:34 | 15:56 |
| 0018 | Koeswanto | 2026-01-12 | Senin | 07:3516:02 | 07:35<br>16:02 | 2 | 07:35 | 16:02 |
| 0018 | Koeswanto | 2026-01-13 | Selasa | 07:3216:09 | 07:32<br>16:09 | 2 | 07:32 | 16:09 |
| 0018 | Koeswanto | 2026-01-14 | Rabu | 07:3716:18 | 07:37<br>16:18 | 2 | 07:37 | 16:18 |
| 0018 | Koeswanto | 2026-01-15 | Kamis | 07:3707:3816:4016:41 | 07:37<br>07:38<br>16:40<br>16:41 | 4 | 07:37 | 16:41 |
| 0018 | Koeswanto | 2026-01-16 | Jumat | 07:4215:0715:08 | 07:42<br>15:07<br>15:08 | 3 | 07:42 | 15:08 |
| 0018 | Koeswanto | 2026-01-17 | Sabtu | 07:3716:0716:07 | 07:37<br>16:07<br>16:07 | 3 | 07:37 | 16:07 |
| 0018 | Koeswanto | 2026-01-19 | Senin | 07:4016:04 | 07:40<br>16:04 | 2 | 07:40 | 16:04 |
| 0018 | Koeswanto | 2026-01-20 | Selasa | 07:3516:0316:03 | 07:35<br>16:03<br>16:03 | 3 | 07:35 | 16:03 |
| 0019 | Amirudin | 2025-12-22 | Senin | 07:3916:07 | 07:39<br>16:07 | 2 | 07:39 | 16:07 |
| 0019 | Amirudin | 2025-12-23 | Selasa | 07:4616:03 | 07:46<br>16:03 | 2 | 07:46 | 16:03 |
| 0019 | Amirudin | 2025-12-24 | Rabu | 07:4316:00 | 07:43<br>16:00 | 2 | 07:43 | 16:00 |
| 0019 | Amirudin | 2025-12-27 | Sabtu | 07:3416:00 | 07:34<br>16:00 | 2 | 07:34 | 16:00 |
| 0019 | Amirudin | 2025-12-29 | Senin | 07:4216:02 | 07:42<br>16:02 | 2 | 07:42 | 16:02 |
| 0019 | Amirudin | 2025-12-31 | Rabu | 07:4116:3916:39 | 07:41<br>16:39<br>16:39 | 3 | 07:41 | 16:39 |
| 0019 | Amirudin | 2026-01-02 | Jumat | 07:4216:0016:00 | 07:42<br>16:00<br>16:00 | 3 | 07:42 | 16:00 |
| 0019 | Amirudin | 2026-01-03 | Sabtu | 07:3907:3914:35 | 07:39<br>07:39<br>14:35 | 3 | 07:39 | 14:35 |
| 0019 | Amirudin | 2026-01-04 | Minggu | 07:4907:4915:2915:29 | 07:49<br>07:49<br>15:29<br>15:29 | 4 | 07:49 | 15:29 |
| 0019 | Amirudin | 2026-01-05 | Senin | 07:4116:0016:00 | 07:41<br>16:00<br>16:00 | 3 | 07:41 | 16:00 |
| 0019 | Amirudin | 2026-01-06 | Selasa | 07:4416:00 | 07:44<br>16:00 | 2 | 07:44 | 16:00 |
| 0019 | Amirudin | 2026-01-07 | Rabu | 07:4507:4516:0116:01 | 07:45<br>07:45<br>16:01<br>16:01 | 4 | 07:45 | 16:01 |
| 0019 | Amirudin | 2026-01-09 | Jumat | 07:3616:00 | 07:36<br>16:00 | 2 | 07:36 | 16:00 |
| 0019 | Amirudin | 2026-01-10 | Sabtu | 07:4115:5615:56 | 07:41<br>15:56<br>15:56 | 3 | 07:41 | 15:56 |
| 0019 | Amirudin | 2026-01-12 | Senin | 07:4116:0016:00 | 07:41<br>16:00<br>16:00 | 3 | 07:41 | 16:00 |
| 0019 | Amirudin | 2026-01-13 | Selasa | 07:4116:0916:09 | 07:41<br>16:09<br>16:09 | 3 | 07:41 | 16:09 |
| 0019 | Amirudin | 2026-01-14 | Rabu | 07:4319:3519:35 | 07:43<br>19:35<br>19:35 | 3 | 07:43 | 19:35 |
| 0019 | Amirudin | 2026-01-15 | Kamis | 07:3707:3716:4016:40 | 07:37<br>07:37<br>16:40<br>16:40 | 4 | 07:37 | 16:40 |
| 0019 | Amirudin | 2026-01-16 | Jumat | 07:4107:4115:07 | 07:41<br>07:41<br>15:07 | 3 | 07:41 | 15:07 |
| 0019 | Amirudin | 2026-01-19 | Senin | 07:4107:4216:0216:02 | 07:41<br>07:42<br>16:02<br>16:02 | 4 | 07:41 | 16:02 |
| 0019 | Amirudin | 2026-01-20 | Selasa | 07:4307:4316:0316:03 | 07:43<br>07:43<br>16:03<br>16:03 | 4 | 07:43 | 16:03 |
| 0020 | Darmawan | 2025-12-21 | Minggu | 08:0521:1921:20 | 08:05<br>21:19<br>21:20 | 3 | 08:05 | 21:20 |
| 0020 | Darmawan | 2025-12-22 | Senin | 07:5717:2317:23 | 07:57<br>17:23<br>17:23 | 3 | 07:57 | 17:23 |
| 0020 | Darmawan | 2025-12-23 | Selasa | 07:4517:0517:05 | 07:45<br>17:05<br>17:05 | 3 | 07:45 | 17:05 |
| 0020 | Darmawan | 2025-12-24 | Rabu | 07:4820:06 | 07:48<br>20:06 | 2 | 07:48 | 20:06 |
| 0020 | Darmawan | 2025-12-26 | Jumat | 07:4922:15 | 07:49<br>22:15 | 2 | 07:49 | 22:15 |
| 0020 | Darmawan | 2025-12-27 | Sabtu | 07:5522:13 | 07:55<br>22:13 | 2 | 07:55 | 22:13 |
| 0020 | Darmawan | 2025-12-29 | Senin | 07:4919:19 | 07:49<br>19:19 | 2 | 07:49 | 19:19 |
| 0020 | Darmawan | 2025-12-30 | Selasa | 07:4021:08 | 07:40<br>21:08 | 2 | 07:40 | 21:08 |
| 0020 | Darmawan | 2025-12-31 | Rabu | 07:4216:24 | 07:42<br>16:24 | 2 | 07:42 | 16:24 |
| 0020 | Darmawan | 2026-01-02 | Jumat | 07:4807:4820:06 | 07:48<br>07:48<br>20:06 | 3 | 07:48 | 20:06 |
| 0020 | Darmawan | 2026-01-03 | Sabtu | 07:4813:11 | 07:48<br>13:11 | 2 | 07:48 | 13:11 |
| 0020 | Darmawan | 2026-01-06 | Selasa | 07:5716:1116:11 | 07:57<br>16:11<br>16:11 | 3 | 07:57 | 16:11 |
| 0020 | Darmawan | 2026-01-07 | Rabu | 07:3207:3217:17 | 07:32<br>07:32<br>17:17 | 3 | 07:32 | 17:17 |
| 0020 | Darmawan | 2026-01-08 | Kamis | 07:5322:48 | 07:53<br>22:48 | 2 | 07:53 | 22:48 |
| 0020 | Darmawan | 2026-01-09 | Jumat | 07:5117:18 | 07:51<br>17:18 | 2 | 07:51 | 17:18 |
| 0020 | Darmawan | 2026-01-10 | Sabtu | 07:5816:19 | 07:58<br>16:19 | 2 | 07:58 | 16:19 |
| 0020 | Darmawan | 2026-01-12 | Senin | 07:4619:11 | 07:46<br>19:11 | 2 | 07:46 | 19:11 |
| 0020 | Darmawan | 2026-01-13 | Selasa | 07:5519:38 | 07:55<br>19:38 | 2 | 07:55 | 19:38 |
| 0020 | Darmawan | 2026-01-14 | Rabu | 07:5307:5323:00 | 07:53<br>07:53<br>23:00 | 3 | 07:53 | 23:00 |
| 0020 | Darmawan | 2026-01-15 | Kamis | 07:5519:02 | 07:55<br>19:02 | 2 | 07:55 | 19:02 |
| 0020 | Darmawan | 2026-01-17 | Sabtu | 07:5020:04 | 07:50<br>20:04 | 2 | 07:50 | 20:04 |
| 0020 | Darmawan | 2026-01-18 | Minggu | 08:0417:3617:37 | 08:04<br>17:36<br>17:37 | 3 | 08:04 | 17:37 |
| 0020 | Darmawan | 2026-01-19 | Senin | 07:5420:1020:11 | 07:54<br>20:10<br>20:11 | 3 | 07:54 | 20:11 |
| 0020 | Darmawan | 2026-01-20 | Selasa | 07:5318:32 | 07:53<br>18:32 | 2 | 07:53 | 18:32 |
| 0024 | David Aritonang | 2025-12-22 | Senin | 08:0916:01 | 08:09<br>16:01 | 2 | 08:09 | 16:01 |
| 0024 | David Aritonang | 2025-12-23 | Selasa | 08:0516:00 | 08:05<br>16:00 | 2 | 08:05 | 16:00 |
| 0024 | David Aritonang | 2025-12-24 | Rabu | 07:5716:00 | 07:57<br>16:00 | 2 | 07:57 | 16:00 |
| 0024 | David Aritonang | 2025-12-26 | Jumat | 07:4216:00 | 07:42<br>16:00 | 2 | 07:42 | 16:00 |
| 0024 | David Aritonang | 2025-12-27 | Sabtu | 08:0315:00 | 08:03<br>15:00 | 2 | 08:03 | 15:00 |
| 0024 | David Aritonang | 2025-12-29 | Senin | 08:0716:03 | 08:07<br>16:03 | 2 | 08:07 | 16:03 |
| 0024 | David Aritonang | 2025-12-30 | Selasa | 08:0415:55 | 08:04<br>15:55 | 2 | 08:04 | 15:55 |
| 0024 | David Aritonang | 2025-12-31 | Rabu | 08:0811:55 | 08:08<br>11:55 | 2 | 08:08 | 11:55 |
| 0024 | David Aritonang | 2026-01-02 | Jumat | 08:3616:00 | 08:36<br>16:00 | 2 | 08:36 | 16:00 |
| 0024 | David Aritonang | 2026-01-03 | Sabtu | 07:5514:32 | 07:55<br>14:32 | 2 | 07:55 | 14:32 |
| 0024 | David Aritonang | 2026-01-05 | Senin | 07:5716:22 | 07:57<br>16:22 | 2 | 07:57 | 16:22 |
| 0024 | David Aritonang | 2026-01-06 | Selasa | 08:0116:06 | 08:01<br>16:06 | 2 | 08:01 | 16:06 |
| 0024 | David Aritonang | 2026-01-07 | Rabu | 07:2616:00 | 07:26<br>16:00 | 2 | 07:26 | 16:00 |
| 0024 | David Aritonang | 2026-01-08 | Kamis | 07:4516:08 | 07:45<br>16:08 | 2 | 07:45 | 16:08 |
| 0024 | David Aritonang | 2026-01-09 | Jumat | 07:4116:00 | 07:41<br>16:00 | 2 | 07:41 | 16:00 |
| 0024 | David Aritonang | 2026-01-10 | Sabtu | 07:5913:19 | 07:59<br>13:19 | 2 | 07:59 | 13:19 |
| 0024 | David Aritonang | 2026-01-12 | Senin | 08:0316:0216:02 | 08:03<br>16:02<br>16:02 | 3 | 08:03 | 16:02 |
| 0024 | David Aritonang | 2026-01-13 | Selasa | 07:5416:05 | 07:54<br>16:05 | 2 | 07:54 | 16:05 |
| 0024 | David Aritonang | 2026-01-14 | Rabu | 07:5916:02 | 07:59<br>16:02 | 2 | 07:59 | 16:02 |
| 0024 | David Aritonang | 2026-01-15 | Kamis | 07:5216:01 | 07:52<br>16:01 | 2 | 07:52 | 16:01 |
| 0024 | David Aritonang | 2026-01-17 | Sabtu | 07:5014:15 | 07:50<br>14:15 | 2 | 07:50 | 14:15 |
| 0024 | David Aritonang | 2026-01-19 | Senin | 07:3116:03 | 07:31<br>16:03 | 2 | 07:31 | 16:03 |
| 0024 | David Aritonang | 2026-01-20 | Selasa | 07:5516:02 | 07:55<br>16:02 | 2 | 07:55 | 16:02 |
| 0025 | Fajar | 2025-12-21 | Minggu | 10:0010:0020:02 | 10:00<br>10:00<br>20:02 | 3 | 10:00 | 20:02 |
| 0025 | Fajar | 2025-12-22 | Senin | 19:4919:49 | 19:49<br>19:49 | 2 | 19:49 | 19:49 |
| 0025 | Fajar | 2025-12-23 | Selasa | 07:4121:2521:25 | 07:41<br>21:25<br>21:25 | 3 | 07:41 | 21:25 |
| 0025 | Fajar | 2025-12-24 | Rabu | 07:4707:5017:28 | 07:47<br>07:50<br>17:28 | 3 | 07:47 | 17:28 |
| 0025 | Fajar | 2025-12-26 | Jumat | 07:4318:43 | 07:43<br>18:43 | 2 | 07:43 | 18:43 |
| 0025 | Fajar | 2025-12-27 | Sabtu | 07:4515:21 | 07:45<br>15:21 | 2 | 07:45 | 15:21 |
| 0025 | Fajar | 2025-12-29 | Senin | 07:50 | 07:50 | 1 | 07:50 | 07:50 |
| 0025 | Fajar | 2025-12-30 | Selasa | 07:5016:5216:52 | 07:50<br>16:52<br>16:52 | 3 | 07:50 | 16:52 |
| 0025 | Fajar | 2025-12-31 | Rabu | 07:4516:5616:56 | 07:45<br>16:56<br>16:56 | 3 | 07:45 | 16:56 |
| 0025 | Fajar | 2026-01-02 | Jumat | 07:4007:4017:1117:11 | 07:40<br>07:40<br>17:11<br>17:11 | 4 | 07:40 | 17:11 |
| 0025 | Fajar | 2026-01-03 | Sabtu | 07:5107:5114:28 | 07:51<br>07:51<br>14:28 | 3 | 07:51 | 14:28 |
| 0025 | Fajar | 2026-01-05 | Senin | 07:4507:45 | 07:45<br>07:45 | 2 | 07:45 | 07:45 |
| 0025 | Fajar | 2026-01-07 | Rabu | 07:4407:4416:5416:54 | 07:44<br>07:44<br>16:54<br>16:54 | 4 | 07:44 | 16:54 |
| 0025 | Fajar | 2026-01-08 | Kamis | 07:4507:4517:1117:11 | 07:45<br>07:45<br>17:11<br>17:11 | 4 | 07:45 | 17:11 |
| 0025 | Fajar | 2026-01-09 | Jumat | 07:4807:4820:1420:14 | 07:48<br>07:48<br>20:14<br>20:14 | 4 | 07:48 | 20:14 |
| 0025 | Fajar | 2026-01-10 | Sabtu | 07:3614:2214:22 | 07:36<br>14:22<br>14:22 | 3 | 07:36 | 14:22 |
| 0025 | Fajar | 2026-01-12 | Senin | 07:4207:4217:1717:17 | 07:42<br>07:42<br>17:17<br>17:17 | 4 | 07:42 | 17:17 |
| 0025 | Fajar | 2026-01-13 | Selasa | 07:4407:4417:2517:25 | 07:44<br>07:44<br>17:25<br>17:25 | 4 | 07:44 | 17:25 |
| 0025 | Fajar | 2026-01-14 | Rabu | 07:4507:4516:2516:25 | 07:45<br>07:45<br>16:25<br>16:25 | 4 | 07:45 | 16:25 |
| 0025 | Fajar | 2026-01-15 | Kamis | 07:4216:28 | 07:42<br>16:28 | 2 | 07:42 | 16:28 |
| 0025 | Fajar | 2026-01-17 | Sabtu | 07:4112:32 | 07:41<br>12:32 | 2 | 07:41 | 12:32 |
| 0025 | Fajar | 2026-01-19 | Senin | 07:4817:0017:00 | 07:48<br>17:00<br>17:00 | 3 | 07:48 | 17:00 |
| 0025 | Fajar | 2026-01-20 | Selasa | 07:5218:0218:02 | 07:52<br>18:02<br>18:02 | 3 | 07:52 | 18:02 |
| 0027 | Jorlan | 2025-12-21 | Minggu | 06:5719:02 | 06:57<br>19:02 | 2 | 06:57 | 19:02 |
| 0027 | Jorlan | 2025-12-22 | Senin | 19:01 | 19:01 | 1 | 19:01 | 19:01 |
| 0027 | Jorlan | 2025-12-23 | Selasa | 07:0418:58 | 07:04<br>18:58 | 2 | 07:04 | 18:58 |
| 0027 | Jorlan | 2025-12-24 | Rabu | 07:0118:53 | 07:01<br>18:53 | 2 | 07:01 | 18:53 |
| 0027 | Jorlan | 2025-12-25 | Kamis | 07:1718:59 | 07:17<br>18:59 | 2 | 07:17 | 18:59 |
| 0027 | Jorlan | 2025-12-26 | Jumat | 06:5918:55 | 06:59<br>18:55 | 2 | 06:59 | 18:55 |
| 0027 | Jorlan | 2025-12-27 | Sabtu | 07:0119:06 | 07:01<br>19:06 | 2 | 07:01 | 19:06 |
| 0027 | Jorlan | 2025-12-28 | Minggu | 07:0120:00 | 07:01<br>20:00 | 2 | 07:01 | 20:00 |
| 0027 | Jorlan | 2025-12-29 | Senin | 07:0507:0519:00 | 07:05<br>07:05<br>19:00 | 3 | 07:05 | 19:00 |
| 0027 | Jorlan | 2025-12-30 | Selasa | 07:0207:0318:58 | 07:02<br>07:03<br>18:58 | 3 | 07:02 | 18:58 |
| 0027 | Jorlan | 2025-12-31 | Rabu | 07:0119:10 | 07:01<br>19:10 | 2 | 07:01 | 19:10 |
| 0027 | Jorlan | 2026-01-01 | Kamis | 06:5819:03 | 06:58<br>19:03 | 2 | 06:58 | 19:03 |
| 0027 | Jorlan | 2026-01-02 | Jumat | 07:0118:59 | 07:01<br>18:59 | 2 | 07:01 | 18:59 |
| 0027 | Jorlan | 2026-01-03 | Sabtu | 19:01 | 19:01 | 1 | 19:01 | 19:01 |
| 0027 | Jorlan | 2026-01-04 | Minggu | 07:01 | 07:01 | 1 | 07:01 | 07:01 |
| 0027 | Jorlan | 2026-01-05 | Senin | 06:5819:00 | 06:58<br>19:00 | 2 | 06:58 | 19:00 |
| 0027 | Jorlan | 2026-01-06 | Selasa | 07:0119:00 | 07:01<br>19:00 | 2 | 07:01 | 19:00 |
| 0027 | Jorlan | 2026-01-07 | Rabu | 07:0319:02 | 07:03<br>19:02 | 2 | 07:03 | 19:02 |
| 0027 | Jorlan | 2026-01-08 | Kamis | 07:0219:02 | 07:02<br>19:02 | 2 | 07:02 | 19:02 |
| 0027 | Jorlan | 2026-01-09 | Jumat | 06:5619:01 | 06:56<br>19:01 | 2 | 06:56 | 19:01 |
| 0027 | Jorlan | 2026-01-10 | Sabtu | 06:5919:01 | 06:59<br>19:01 | 2 | 06:59 | 19:01 |
| 0027 | Jorlan | 2026-01-11 | Minggu | 07:0019:03 | 07:00<br>19:03 | 2 | 07:00 | 19:03 |
| 0027 | Jorlan | 2026-01-12 | Senin | 18:58 | 18:58 | 1 | 18:58 | 18:58 |
| 0027 | Jorlan | 2026-01-13 | Selasa | 07:2018:57 | 07:20<br>18:57 | 2 | 07:20 | 18:57 |
| 0027 | Jorlan | 2026-01-14 | Rabu | 07:0018:57 | 07:00<br>18:57 | 2 | 07:00 | 18:57 |
| 0027 | Jorlan | 2026-01-15 | Kamis | 07:0218:58 | 07:02<br>18:58 | 2 | 07:02 | 18:58 |
| 0027 | Jorlan | 2026-01-16 | Jumat | 07:0207:0218:59 | 07:02<br>07:02<br>18:59 | 3 | 07:02 | 18:59 |
| 0027 | Jorlan | 2026-01-17 | Sabtu | 07:0519:01 | 07:05<br>19:01 | 2 | 07:05 | 19:01 |
| 0027 | Jorlan | 2026-01-18 | Minggu | 07:0418:59 | 07:04<br>18:59 | 2 | 07:04 | 18:59 |
| 0027 | Jorlan | 2026-01-19 | Senin | 07:01 | 07:01 | 1 | 07:01 | 07:01 |
| 0028 | Sudirman H | 2025-12-22 | Senin | 07:0207:0219:0319:0319:03 | 07:02<br>07:02<br>19:03<br>19:03<br>19:03 | 5 | 07:02 | 19:03 |
| 0028 | Sudirman H | 2025-12-23 | Selasa | 07:0407:0419:0319:03 | 07:04<br>07:04<br>19:03<br>19:03 | 4 | 07:04 | 19:03 |
| 0028 | Sudirman H | 2025-12-24 | Rabu | 06:5306:5318:4518:45 | 06:53<br>06:53<br>18:45<br>18:45 | 4 | 06:53 | 18:45 |
| 0028 | Sudirman H | 2025-12-25 | Kamis | 06:5506:5519:0319:0319:03 | 06:55<br>06:55<br>19:03<br>19:03<br>19:03 | 5 | 06:55 | 19:03 |
| 0028 | Sudirman H | 2025-12-26 | Jumat | 06:5206:5206:5219:0219:0219:02 | 06:52<br>06:52<br>06:52<br>19:02<br>19:02<br>19:02 | 6 | 06:52 | 19:02 |
| 0028 | Sudirman H | 2025-12-27 | Sabtu | 06:5506:5506:5519:0419:0419:04 | 06:55<br>06:55<br>06:55<br>19:04<br>19:04<br>19:04 | 6 | 06:55 | 19:04 |
| 0028 | Sudirman H | 2025-12-28 | Minggu | 06:5806:5806:5819:0119:01 | 06:58<br>06:58<br>06:58<br>19:01<br>19:01 | 5 | 06:58 | 19:01 |
| 0028 | Sudirman H | 2026-01-12 | Senin | 06:5706:5706:5719:0419:0419:04 | 06:57<br>06:57<br>06:57<br>19:04<br>19:04<br>19:04 | 6 | 06:57 | 19:04 |
| 0028 | Sudirman H | 2026-01-13 | Selasa | 07:0007:0007:0019:0019:0019:00 | 07:00<br>07:00<br>07:00<br>19:00<br>19:00<br>19:00 | 6 | 07:00 | 19:00 |
| 0028 | Sudirman H | 2026-01-14 | Rabu | 06:5806:5819:0419:05 | 06:58<br>06:58<br>19:04<br>19:05 | 4 | 06:58 | 19:05 |
| 0028 | Sudirman H | 2026-01-15 | Kamis | 06:5906:5906:5919:0319:0319:03 | 06:59<br>06:59<br>06:59<br>19:03<br>19:03<br>19:03 | 6 | 06:59 | 19:03 |
| 0028 | Sudirman H | 2026-01-16 | Jumat | 06:5606:5606:5619:1119:1119:11 | 06:56<br>06:56<br>06:56<br>19:11<br>19:11<br>19:11 | 6 | 06:56 | 19:11 |
| 0028 | Sudirman H | 2026-01-17 | Sabtu | 06:5706:5706:5719:1219:12 | 06:57<br>06:57<br>06:57<br>19:12<br>19:12 | 5 | 06:57 | 19:12 |
| 0028 | Sudirman H | 2026-01-18 | Minggu | 07:0107:0118:5918:5918:59 | 07:01<br>07:01<br>18:59<br>18:59<br>18:59 | 5 | 07:01 | 18:59 |
| 0028 | Sudirman H | 2026-01-19 | Senin | 18:5718:5718:57 | 18:57<br>18:57<br>18:57 | 3 | 18:57 | 18:57 |
| 0028 | Sudirman H | 2026-01-20 | Selasa | 07:0207:0218:5718:5718:57 | 07:02<br>07:02<br>18:57<br>18:57<br>18:57 | 5 | 07:02 | 18:57 |
| 0029 | Edy Jaka S | 2025-12-21 | Minggu | 07:0407:0407:0418:5618:5618:56 | 07:04<br>07:04<br>07:04<br>18:56<br>18:56<br>18:56 | 6 | 07:04 | 18:56 |
| 0029 | Edy Jaka S | 2025-12-22 | Senin | 07:0407:0407:04 | 07:04<br>07:04<br>07:04 | 3 | 07:04 | 07:04 |
| 0029 | Edy Jaka S | 2025-12-29 | Senin | 07:0807:0807:0819:0719:0719:07 | 07:08<br>07:08<br>07:08<br>19:07<br>19:07<br>19:07 | 6 | 07:08 | 19:07 |
| 0029 | Edy Jaka S | 2025-12-30 | Selasa | 07:0007:0107:0119:0219:0219:02 | 07:00<br>07:01<br>07:01<br>19:02<br>19:02<br>19:02 | 6 | 07:00 | 19:02 |
| 0029 | Edy Jaka S | 2025-12-31 | Rabu | 07:0007:0007:0019:0619:0619:06 | 07:00<br>07:00<br>07:00<br>19:06<br>19:06<br>19:06 | 6 | 07:00 | 19:06 |
| 0029 | Edy Jaka S | 2026-01-01 | Kamis | 06:4906:4906:4919:0419:0419:04 | 06:49<br>06:49<br>06:49<br>19:04<br>19:04<br>19:04 | 6 | 06:49 | 19:04 |
| 0029 | Edy Jaka S | 2026-01-02 | Jumat | 07:1207:1207:1219:3719:3719:37 | 07:12<br>07:12<br>07:12<br>19:37<br>19:37<br>19:37 | 6 | 07:12 | 19:37 |
| 0029 | Edy Jaka S | 2026-01-03 | Sabtu | 07:0507:0507:0519:0519:0519:05 | 07:05<br>07:05<br>07:05<br>19:05<br>19:05<br>19:05 | 6 | 07:05 | 19:05 |
| 0029 | Edy Jaka S | 2026-01-04 | Minggu | 07:0007:0007:00 | 07:00<br>07:00<br>07:00 | 3 | 07:00 | 07:00 |
| 0029 | Edy Jaka S | 2026-01-05 | Senin | 07:0507:0507:0519:3219:3219:32 | 07:05<br>07:05<br>07:05<br>19:32<br>19:32<br>19:32 | 6 | 07:05 | 19:32 |
| 0029 | Edy Jaka S | 2026-01-06 | Selasa | 07:0307:0307:0319:0319:0319:03 | 07:03<br>07:03<br>07:03<br>19:03<br>19:03<br>19:03 | 6 | 07:03 | 19:03 |
| 0029 | Edy Jaka S | 2026-01-07 | Rabu | 07:0607:0607:0619:0319:0319:03 | 07:06<br>07:06<br>07:06<br>19:03<br>19:03<br>19:03 | 6 | 07:06 | 19:03 |
| 0029 | Edy Jaka S | 2026-01-08 | Kamis | 07:0407:0407:0519:0519:0519:05 | 07:04<br>07:04<br>07:05<br>19:05<br>19:05<br>19:05 | 6 | 07:04 | 19:05 |
| 0029 | Edy Jaka S | 2026-01-09 | Jumat | 07:0307:0307:0319:0419:0419:04 | 07:03<br>07:03<br>07:03<br>19:04<br>19:04<br>19:04 | 6 | 07:03 | 19:04 |
| 0029 | Edy Jaka S | 2026-01-10 | Sabtu | 07:0207:0207:0219:0119:0119:01 | 07:02<br>07:02<br>07:02<br>19:01<br>19:01<br>19:01 | 6 | 07:02 | 19:01 |
| 0029 | Edy Jaka S | 2026-01-11 | Minggu | 07:2607:2607:2619:1419:1419:14 | 07:26<br>07:26<br>07:26<br>19:14<br>19:14<br>19:14 | 6 | 07:26 | 19:14 |
| 0029 | Edy Jaka S | 2026-01-12 | Senin | 07:1607:1607:16 | 07:16<br>07:16<br>07:16 | 3 | 07:16 | 07:16 |
| 0029 | Edy Jaka S | 2026-01-19 | Senin | 06:5906:5906:5919:0219:0219:02 | 06:59<br>06:59<br>06:59<br>19:02<br>19:02<br>19:02 | 6 | 06:59 | 19:02 |
| 0029 | Edy Jaka S | 2026-01-20 | Selasa | 06:5806:5806:5819:1019:1019:10 | 06:58<br>06:58<br>06:58<br>19:10<br>19:10<br>19:10 | 6 | 06:58 | 19:10 |
| 0030 | Taufiq H | 2025-12-21 | Minggu | 07:0307:0319:0119:01 | 07:03<br>07:03<br>19:01<br>19:01 | 4 | 07:03 | 19:01 |
| 0030 | Taufiq H | 2025-12-22 | Senin | 07:0307:0319:0419:04 | 07:03<br>07:03<br>19:04<br>19:04 | 4 | 07:03 | 19:04 |
| 0030 | Taufiq H | 2025-12-23 | Selasa | 07:0707:0719:0219:02 | 07:07<br>07:07<br>19:02<br>19:02 | 4 | 07:07 | 19:02 |
| 0030 | Taufiq H | 2025-12-24 | Rabu | 07:0007:0018:4218:42 | 07:00<br>07:00<br>18:42<br>18:42 | 4 | 07:00 | 18:42 |
| 0030 | Taufiq H | 2025-12-25 | Kamis | 07:0007:0019:0419:04 | 07:00<br>07:00<br>19:04<br>19:04 | 4 | 07:00 | 19:04 |
| 0030 | Taufiq H | 2025-12-26 | Jumat | 07:0907:0919:0319:03 | 07:09<br>07:09<br>19:03<br>19:03 | 4 | 07:09 | 19:03 |
| 0030 | Taufiq H | 2025-12-27 | Sabtu | 07:0007:0019:0319:03 | 07:00<br>07:00<br>19:03<br>19:03 | 4 | 07:00 | 19:03 |
| 0030 | Taufiq H | 2025-12-28 | Minggu | 07:0007:0019:0019:00 | 07:00<br>07:00<br>19:00<br>19:00 | 4 | 07:00 | 19:00 |
| 0030 | Taufiq H | 2025-12-29 | Senin | 07:0507:0519:0219:02 | 07:05<br>07:05<br>19:02<br>19:02 | 4 | 07:05 | 19:02 |
| 0030 | Taufiq H | 2025-12-30 | Selasa | 07:0107:0107:0207:0219:0519:05 | 07:01<br>07:01<br>07:02<br>07:02<br>19:05<br>19:05 | 6 | 07:01 | 19:05 |
| 0030 | Taufiq H | 2025-12-31 | Rabu | 07:0107:0119:0219:02 | 07:01<br>07:01<br>19:02<br>19:02 | 4 | 07:01 | 19:02 |
| 0030 | Taufiq H | 2026-01-01 | Kamis | 07:0107:0119:0319:03 | 07:01<br>07:01<br>19:03<br>19:03 | 4 | 07:01 | 19:03 |
| 0030 | Taufiq H | 2026-01-02 | Jumat | 07:1207:1219:0419:04 | 07:12<br>07:12<br>19:04<br>19:04 | 4 | 07:12 | 19:04 |
| 0030 | Taufiq H | 2026-01-03 | Sabtu | 07:0507:0519:0319:03 | 07:05<br>07:05<br>19:03<br>19:03 | 4 | 07:05 | 19:03 |
| 0030 | Taufiq H | 2026-01-04 | Minggu | 07:0307:0319:0219:02 | 07:03<br>07:03<br>19:02<br>19:02 | 4 | 07:03 | 19:02 |
| 0030 | Taufiq H | 2026-01-05 | Senin | 07:0207:0218:5518:55 | 07:02<br>07:02<br>18:55<br>18:55 | 4 | 07:02 | 18:55 |
| 0030 | Taufiq H | 2026-01-06 | Selasa | 07:0407:0418:5918:59 | 07:04<br>07:04<br>18:59<br>18:59 | 4 | 07:04 | 18:59 |
| 0030 | Taufiq H | 2026-01-07 | Rabu | 07:0407:0418:5818:58 | 07:04<br>07:04<br>18:58<br>18:58 | 4 | 07:04 | 18:58 |
| 0030 | Taufiq H | 2026-01-08 | Kamis | 07:0307:0318:5918:59 | 07:03<br>07:03<br>18:59<br>18:59 | 4 | 07:03 | 18:59 |
| 0030 | Taufiq H | 2026-01-09 | Jumat | 07:0307:0318:5418:54 | 07:03<br>07:03<br>18:54<br>18:54 | 4 | 07:03 | 18:54 |
| 0030 | Taufiq H | 2026-01-10 | Sabtu | 07:0207:0218:5918:59 | 07:02<br>07:02<br>18:59<br>18:59 | 4 | 07:02 | 18:59 |
| 0030 | Taufiq H | 2026-01-11 | Minggu | 07:0107:0118:5818:59 | 07:01<br>07:01<br>18:58<br>18:59 | 4 | 07:01 | 18:59 |
| 0030 | Taufiq H | 2026-01-12 | Senin | 07:0207:0218:5918:59 | 07:02<br>07:02<br>18:59<br>18:59 | 4 | 07:02 | 18:59 |
| 0030 | Taufiq H | 2026-01-13 | Selasa | 07:0307:0319:0019:00 | 07:03<br>07:03<br>19:00<br>19:00 | 4 | 07:03 | 19:00 |
| 0030 | Taufiq H | 2026-01-14 | Rabu | 07:0207:0218:5818:58 | 07:02<br>07:02<br>18:58<br>18:58 | 4 | 07:02 | 18:58 |
| 0030 | Taufiq H | 2026-01-15 | Kamis | 07:0207:0219:0319:03 | 07:02<br>07:02<br>19:03<br>19:03 | 4 | 07:02 | 19:03 |
| 0030 | Taufiq H | 2026-01-16 | Jumat | 07:0007:0018:5919:00 | 07:00<br>07:00<br>18:59<br>19:00 | 4 | 07:00 | 19:00 |
| 0030 | Taufiq H | 2026-01-17 | Sabtu | 07:0207:0219:0119:01 | 07:02<br>07:02<br>19:01<br>19:01 | 4 | 07:02 | 19:01 |
| 0030 | Taufiq H | 2026-01-18 | Minggu | 07:0707:0718:5818:58 | 07:07<br>07:07<br>18:58<br>18:58 | 4 | 07:07 | 18:58 |
| 0030 | Taufiq H | 2026-01-19 | Senin | 05:3019:0119:01 | 05:30<br>19:01<br>19:01 | 3 | 05:30 | 19:01 |
| 0030 | Taufiq H | 2026-01-20 | Selasa | 07:0107:0119:0219:02 | 07:01<br>07:01<br>19:02<br>19:02 | 4 | 07:01 | 19:02 |
| 34 | FAIKAR | 2025-12-22 | Senin | 07:4917:57 | 07:49<br>17:57 | 2 | 07:49 | 17:57 |
| 34 | FAIKAR | 2025-12-23 | Selasa | 07:5216:23 | 07:52<br>16:23 | 2 | 07:52 | 16:23 |
| 34 | FAIKAR | 2025-12-24 | Rabu | 07:5016:42 | 07:50<br>16:42 | 2 | 07:50 | 16:42 |
| 34 | FAIKAR | 2025-12-26 | Jumat | 08:2016:3316:33 | 08:20<br>16:33<br>16:33 | 3 | 08:20 | 16:33 |
| 34 | FAIKAR | 2025-12-27 | Sabtu | 08:1316:2416:24 | 08:13<br>16:24<br>16:24 | 3 | 08:13 | 16:24 |
| 34 | FAIKAR | 2025-12-29 | Senin | 16:1016:10 | 16:10<br>16:10 | 2 | 16:10 | 16:10 |
| 34 | FAIKAR | 2025-12-30 | Selasa | 07:4307:4316:0016:00 | 07:43<br>07:43<br>16:00<br>16:00 | 4 | 07:43 | 16:00 |
| 34 | FAIKAR | 2026-01-07 | Rabu | 07:1807:1817:3617:37 | 07:18<br>07:18<br>17:36<br>17:37 | 4 | 07:18 | 17:37 |
| 34 | FAIKAR | 2026-01-08 | Kamis | 07:3407:3417:1717:17 | 07:34<br>07:34<br>17:17<br>17:17 | 4 | 07:34 | 17:17 |
| 34 | FAIKAR | 2026-01-09 | Jumat | 07:3407:3418:0818:08 | 07:34<br>07:34<br>18:08<br>18:08 | 4 | 07:34 | 18:08 |
| 34 | FAIKAR | 2026-01-10 | Sabtu | 07:3607:3617:2417:24 | 07:36<br>07:36<br>17:24<br>17:24 | 4 | 07:36 | 17:24 |
| 34 | FAIKAR | 2026-01-12 | Senin | 07:4807:4818:0818:08 | 07:48<br>07:48<br>18:08<br>18:08 | 4 | 07:48 | 18:08 |
| 34 | FAIKAR | 2026-01-13 | Selasa | 07:3907:3919:2719:27 | 07:39<br>07:39<br>19:27<br>19:27 | 4 | 07:39 | 19:27 |
| 34 | FAIKAR | 2026-01-14 | Rabu | 08:1208:1219:3919:40 | 08:12<br>08:12<br>19:39<br>19:40 | 4 | 08:12 | 19:40 |
| 34 | FAIKAR | 2026-01-15 | Kamis | 08:2108:2117:2717:27 | 08:21<br>08:21<br>17:27<br>17:27 | 4 | 08:21 | 17:27 |
| 34 | FAIKAR | 2026-01-17 | Sabtu | 07:5207:5217:5717:57 | 07:52<br>07:52<br>17:57<br>17:57 | 4 | 07:52 | 17:57 |
| 34 | FAIKAR | 2026-01-19 | Senin | 07:5007:5018:1318:13 | 07:50<br>07:50<br>18:13<br>18:13 | 4 | 07:50 | 18:13 |
| 34 | FAIKAR | 2026-01-20 | Selasa | 08:0408:0418:0218:0218:02 | 08:04<br>08:04<br>18:02<br>18:02<br>18:02 | 5 | 08:04 | 18:02 |
| 35 | GABRIEL | 2025-12-22 | Senin | 07:3107:3121:06 | 07:31<br>07:31<br>21:06 | 3 | 07:31 | 21:06 |
| 35 | GABRIEL | 2025-12-23 | Selasa | 07:4807:4822:0222:02 | 07:48<br>07:48<br>22:02<br>22:02 | 4 | 07:48 | 22:02 |
| 35 | GABRIEL | 2025-12-25 | Kamis | 14:0414:0419:0419:0419:0619:06 | 14:04<br>14:04<br>19:04<br>19:04<br>19:06<br>19:06 | 6 | 14:04 | 19:06 |
| 35 | GABRIEL | 2025-12-26 | Jumat | 07:5207:5216:3516:35 | 07:52<br>07:52<br>16:35<br>16:35 | 4 | 07:52 | 16:35 |
| 35 | GABRIEL | 2026-01-09 | Jumat | 08:0108:0118:4718:47 | 08:01<br>08:01<br>18:47<br>18:47 | 4 | 08:01 | 18:47 |
| 35 | GABRIEL | 2026-01-12 | Senin | 07:4207:4220:0120:01 | 07:42<br>07:42<br>20:01<br>20:01 | 4 | 07:42 | 20:01 |
| 35 | GABRIEL | 2026-01-13 | Selasa | 08:2508:2519:3419:3419:3919:39 | 08:25<br>08:25<br>19:34<br>19:34<br>19:39<br>19:39 | 6 | 08:25 | 19:39 |
| 35 | GABRIEL | 2026-01-14 | Rabu | 08:0308:0319:1219:12 | 08:03<br>08:03<br>19:12<br>19:12 | 4 | 08:03 | 19:12 |
| 35 | GABRIEL | 2026-01-15 | Kamis | 08:0708:0718:4318:43 | 08:07<br>08:07<br>18:43<br>18:43 | 4 | 08:07 | 18:43 |
| 35 | GABRIEL | 2026-01-17 | Sabtu | 07:5707:5723:5423:54 | 07:57<br>07:57<br>23:54<br>23:54 | 4 | 07:57 | 23:54 |
| 35 | GABRIEL | 2026-01-20 | Selasa | 07:5907:5919:5619:56 | 07:59<br>07:59<br>19:56<br>19:56 | 4 | 07:59 | 19:56 |
| 36 | wagiman | 2025-12-22 | Senin | 07:3221:0321:06 | 07:32<br>21:03<br>21:06 | 3 | 07:32 | 21:06 |
| 36 | wagiman | 2025-12-23 | Selasa | 07:3922:00 | 07:39<br>22:00 | 2 | 07:39 | 22:00 |
| 36 | wagiman | 2025-12-24 | Rabu | 07:4221:0321:0321:03 | 07:42<br>21:03<br>21:03<br>21:03 | 4 | 07:42 | 21:03 |
| 36 | wagiman | 2025-12-25 | Kamis | 08:0018:21 | 08:00<br>18:21 | 2 | 08:00 | 18:21 |
| 36 | wagiman | 2025-12-26 | Jumat | 07:4717:11 | 07:47<br>17:11 | 2 | 07:47 | 17:11 |
| 36 | wagiman | 2025-12-27 | Sabtu | 07:3319:24 | 07:33<br>19:24 | 2 | 07:33 | 19:24 |
| 36 | wagiman | 2025-12-28 | Minggu | 07:4807:4816:05 | 07:48<br>07:48<br>16:05 | 3 | 07:48 | 16:05 |
| 36 | wagiman | 2025-12-29 | Senin | 07:4320:42 | 07:43<br>20:42 | 2 | 07:43 | 20:42 |
| 36 | wagiman | 2025-12-30 | Selasa | 07:3820:52 | 07:38<br>20:52 | 2 | 07:38 | 20:52 |
| 36 | wagiman | 2026-01-02 | Jumat | 07:3619:01 | 07:36<br>19:01 | 2 | 07:36 | 19:01 |
| 36 | wagiman | 2026-01-03 | Sabtu | 07:4914:17 | 07:49<br>14:17 | 2 | 07:49 | 14:17 |
| 36 | wagiman | 2026-01-05 | Senin | 07:4119:10 | 07:41<br>19:10 | 2 | 07:41 | 19:10 |
| 36 | wagiman | 2026-01-06 | Selasa | 07:4022:31 | 07:40<br>22:31 | 2 | 07:40 | 22:31 |
| 36 | wagiman | 2026-01-07 | Rabu | 07:3322:56 | 07:33<br>22:56 | 2 | 07:33 | 22:56 |
| 36 | wagiman | 2026-01-08 | Kamis | 07:3920:48 | 07:39<br>20:48 | 2 | 07:39 | 20:48 |
| 36 | wagiman | 2026-01-09 | Jumat | 07:4418:49 | 07:44<br>18:49 | 2 | 07:44 | 18:49 |
| 36 | wagiman | 2026-01-10 | Sabtu | 07:3315:37 | 07:33<br>15:37 | 2 | 07:33 | 15:37 |
| 36 | wagiman | 2026-01-13 | Selasa | 07:1319:31 | 07:13<br>19:31 | 2 | 07:13 | 19:31 |
| 36 | wagiman | 2026-01-14 | Rabu | 07:2619:1219:12 | 07:26<br>19:12<br>19:12 | 3 | 07:26 | 19:12 |
| 36 | wagiman | 2026-01-15 | Kamis | 07:4818:37 | 07:48<br>18:37 | 2 | 07:48 | 18:37 |
| 36 | wagiman | 2026-01-17 | Sabtu | 07:1517:18 | 07:15<br>17:18 | 2 | 07:15 | 17:18 |
| 36 | wagiman | 2026-01-19 | Senin | 07:4119:45 | 07:41<br>19:45 | 2 | 07:41 | 19:45 |
| 36 | wagiman | 2026-01-20 | Selasa | 07:3719:56 | 07:37<br>19:56 | 2 | 07:37 | 19:56 |
| 37 | arifrendi | 2025-12-22 | Senin | 07:3718:03 | 07:37<br>18:03 | 2 | 07:37 | 18:03 |
| 37 | arifrendi | 2025-12-23 | Selasa | 07:5217:05 | 07:52<br>17:05 | 2 | 07:52 | 17:05 |
| 37 | arifrendi | 2025-12-24 | Rabu | 07:5716:42 | 07:57<br>16:42 | 2 | 07:57 | 16:42 |
| 37 | arifrendi | 2025-12-26 | Jumat | 07:5617:15 | 07:56<br>17:15 | 2 | 07:56 | 17:15 |
| 37 | arifrendi | 2025-12-27 | Sabtu | 08:0016:25 | 08:00<br>16:25 | 2 | 08:00 | 16:25 |
| 37 | arifrendi | 2025-12-29 | Senin | 07:3616:10 | 07:36<br>16:10 | 2 | 07:36 | 16:10 |
| 37 | arifrendi | 2025-12-30 | Selasa | 07:4016:23 | 07:40<br>16:23 | 2 | 07:40 | 16:23 |
| 37 | arifrendi | 2025-12-31 | Rabu | 08:0216:01 | 08:02<br>16:01 | 2 | 08:02 | 16:01 |
| 37 | arifrendi | 2026-01-02 | Jumat | 08:0017:09 | 08:00<br>17:09 | 2 | 08:00 | 17:09 |
| 37 | arifrendi | 2026-01-03 | Sabtu | 07:3815:35 | 07:38<br>15:35 | 2 | 07:38 | 15:35 |
| 37 | arifrendi | 2026-01-05 | Senin | 07:3517:0317:03 | 07:35<br>17:03<br>17:03 | 3 | 07:35 | 17:03 |
| 37 | arifrendi | 2026-01-06 | Selasa | 07:3818:13 | 07:38<br>18:13 | 2 | 07:38 | 18:13 |
| 37 | arifrendi | 2026-01-07 | Rabu | 07:2017:20 | 07:20<br>17:20 | 2 | 07:20 | 17:20 |
| 37 | arifrendi | 2026-01-08 | Kamis | 07:3317:21 | 07:33<br>17:21 | 2 | 07:33 | 17:21 |
| 37 | arifrendi | 2026-01-09 | Jumat | 07:3418:08 | 07:34<br>18:08 | 2 | 07:34 | 18:08 |
| 37 | arifrendi | 2026-01-10 | Sabtu | 07:3717:25 | 07:37<br>17:25 | 2 | 07:37 | 17:25 |
| 37 | arifrendi | 2026-01-12 | Senin | 07:4917:34 | 07:49<br>17:34 | 2 | 07:49 | 17:34 |
| 37 | arifrendi | 2026-01-13 | Selasa | 07:3918:50 | 07:39<br>18:50 | 2 | 07:39 | 18:50 |
| 37 | arifrendi | 2026-01-14 | Rabu | 08:1219:38 | 08:12<br>19:38 | 2 | 08:12 | 19:38 |
| 37 | arifrendi | 2026-01-15 | Kamis | 08:0417:27 | 08:04<br>17:27 | 2 | 08:04 | 17:27 |
| 37 | arifrendi | 2026-01-17 | Sabtu | 07:5217:55 | 07:52<br>17:55 | 2 | 07:52 | 17:55 |
| 37 | arifrendi | 2026-01-19 | Senin | 07:5018:08 | 07:50<br>18:08 | 2 | 07:50 | 18:08 |
| 37 | arifrendi | 2026-01-20 | Selasa | 08:0308:0318:0218:03 | 08:03<br>08:03<br>18:02<br>18:03 | 4 | 08:03 | 18:03 |
| 38 | firlanda | 2025-12-21 | Minggu | 07:4821:20 | 07:48<br>21:20 | 2 | 07:48 | 21:20 |
| 38 | firlanda | 2025-12-22 | Senin | 07:3221:03 | 07:32<br>21:03 | 2 | 07:32 | 21:03 |
| 38 | firlanda | 2025-12-23 | Selasa | 07:3922:0022:00 | 07:39<br>22:00<br>22:00 | 3 | 07:39 | 22:00 |
| 38 | firlanda | 2025-12-24 | Rabu | 07:4221:07 | 07:42<br>21:07 | 2 | 07:42 | 21:07 |
| 38 | firlanda | 2025-12-25 | Kamis | 08:1719:0519:05 | 08:17<br>19:05<br>19:05 | 3 | 08:17 | 19:05 |
| 38 | firlanda | 2025-12-26 | Jumat | 07:4922:1222:12 | 07:49<br>22:12<br>22:12 | 3 | 07:49 | 22:12 |
| 38 | firlanda | 2025-12-27 | Sabtu | 07:4222:1222:12 | 07:42<br>22:12<br>22:12 | 3 | 07:42 | 22:12 |
| 38 | firlanda | 2025-12-28 | Minggu | 08:0816:05 | 08:08<br>16:05 | 2 | 08:08 | 16:05 |
| 38 | firlanda | 2025-12-29 | Senin | 07:4322:01 | 07:43<br>22:01 | 2 | 07:43 | 22:01 |
| 38 | firlanda | 2025-12-30 | Selasa | 06:5021:0521:05 | 06:50<br>21:05<br>21:05 | 3 | 06:50 | 21:05 |
| 38 | firlanda | 2025-12-31 | Rabu | 07:0007:0018:3718:37 | 07:00<br>07:00<br>18:37<br>18:37 | 4 | 07:00 | 18:37 |
| 38 | firlanda | 2026-01-02 | Jumat | 07:1207:1207:1221:0221:0221:0221:02 | 07:12<br>07:12<br>07:12<br>21:02<br>21:02<br>21:02<br>21:02 | 7 | 07:12 | 21:02 |
| 38 | firlanda | 2026-01-03 | Sabtu | 07:2207:2207:2213:56 | 07:22<br>07:22<br>07:22<br>13:56 | 4 | 07:22 | 13:56 |
| 38 | firlanda | 2026-01-04 | Minggu | 07:5907:5916:0116:0116:0116:0116:01 | 07:59<br>07:59<br>16:01<br>16:01<br>16:01<br>16:01<br>16:01 | 7 | 07:59 | 16:01 |
| 38 | firlanda | 2026-01-05 | Senin | 07:4007:4018:3218:3218:32 | 07:40<br>07:40<br>18:32<br>18:32<br>18:32 | 5 | 07:40 | 18:32 |
| 38 | firlanda | 2026-01-06 | Selasa | 07:4522:3022:3022:3022:30 | 07:45<br>22:30<br>22:30<br>22:30<br>22:30 | 5 | 07:45 | 22:30 |
| 38 | firlanda | 2026-01-07 | Rabu | 07:3407:3423:0023:00 | 07:34<br>07:34<br>23:00<br>23:00 | 4 | 07:34 | 23:00 |
| 38 | firlanda | 2026-01-08 | Kamis | 07:34 | 07:34 | 1 | 07:34 | 07:34 |
| 38 | firlanda | 2026-01-09 | Jumat | 22:1522:16 | 22:15<br>22:16 | 2 | 22:15 | 22:16 |
| 38 | firlanda | 2026-01-10 | Sabtu | 08:0108:0116:3216:32 | 08:01<br>08:01<br>16:32<br>16:32 | 4 | 08:01 | 16:32 |
| 38 | firlanda | 2026-01-11 | Minggu | 07:2707:2707:2719:0119:0119:01 | 07:27<br>07:27<br>07:27<br>19:01<br>19:01<br>19:01 | 6 | 07:27 | 19:01 |
| 38 | firlanda | 2026-01-12 | Senin | 07:4419:44 | 07:44<br>19:44 | 2 | 07:44 | 19:44 |
| 38 | firlanda | 2026-01-13 | Selasa | 07:49 | 07:49 | 1 | 07:49 | 07:49 |
| 38 | firlanda | 2026-01-14 | Rabu | 00:1308:0523:0023:00 | 00:13<br>08:05<br>23:00<br>23:00 | 4 | 00:13 | 23:00 |
| 38 | firlanda | 2026-01-15 | Kamis | 08:0318:5618:56 | 08:03<br>18:56<br>18:56 | 3 | 08:03 | 18:56 |
| 38 | firlanda | 2026-01-17 | Sabtu | 07:4322:0322:03 | 07:43<br>22:03<br>22:03 | 3 | 07:43 | 22:03 |
| 38 | firlanda | 2026-01-18 | Minggu | 08:0508:0522:2022:20 | 08:05<br>08:05<br>22:20<br>22:20 | 4 | 08:05 | 22:20 |
| 38 | firlanda | 2026-01-19 | Senin | 07:5422:1222:12 | 07:54<br>22:12<br>22:12 | 3 | 07:54 | 22:12 |
| 38 | firlanda | 2026-01-20 | Selasa | 07:2007:2019:1719:1719:1719:17 | 07:20<br>07:20<br>19:17<br>19:17<br>19:17<br>19:17 | 6 | 07:20 | 19:17 |
| 39 | soleh | 2025-12-21 | Minggu | 07:4821:12 | 07:48<br>21:12 | 2 | 07:48 | 21:12 |
| 39 | soleh | 2025-12-22 | Senin | 07:1221:33 | 07:12<br>21:33 | 2 | 07:12 | 21:33 |
| 39 | soleh | 2025-12-23 | Selasa | 07:2522:02 | 07:25<br>22:02 | 2 | 07:25 | 22:02 |
| 39 | soleh | 2025-12-24 | Rabu | 07:2521:03 | 07:25<br>21:03 | 2 | 07:25 | 21:03 |
| 39 | soleh | 2025-12-25 | Kamis | 07:24 | 07:24 | 1 | 07:24 | 07:24 |
| 39 | soleh | 2025-12-26 | Jumat | 07:2622:11 | 07:26<br>22:11 | 2 | 07:26 | 22:11 |
| 39 | soleh | 2025-12-27 | Sabtu | 07:3219:24 | 07:32<br>19:24 | 2 | 07:32 | 19:24 |
| 39 | soleh | 2025-12-28 | Minggu | 07:3116:06 | 07:31<br>16:06 | 2 | 07:31 | 16:06 |
| 39 | soleh | 2025-12-29 | Senin | 07:2720:42 | 07:27<br>20:42 | 2 | 07:27 | 20:42 |
| 39 | soleh | 2025-12-30 | Selasa | 07:2620:51 | 07:26<br>20:51 | 2 | 07:26 | 20:51 |
| 39 | soleh | 2025-12-31 | Rabu | 07:2517:12 | 07:25<br>17:12 | 2 | 07:25 | 17:12 |
| 39 | soleh | 2026-01-02 | Jumat | 07:2519:02 | 07:25<br>19:02 | 2 | 07:25 | 19:02 |
| 39 | soleh | 2026-01-03 | Sabtu | 07:2514:15 | 07:25<br>14:15 | 2 | 07:25 | 14:15 |
| 39 | soleh | 2026-01-04 | Minggu | 07:34 | 07:34 | 1 | 07:34 | 07:34 |
| 39 | soleh | 2026-01-05 | Senin | 07:2519:07 | 07:25<br>19:07 | 2 | 07:25 | 19:07 |
| 39 | soleh | 2026-01-06 | Selasa | 07:3019:24 | 07:30<br>19:24 | 2 | 07:30 | 19:24 |
| 39 | soleh | 2026-01-07 | Rabu | 07:3018:21 | 07:30<br>18:21 | 2 | 07:30 | 18:21 |
| 39 | soleh | 2026-01-08 | Kamis | 07:2720:48 | 07:27<br>20:48 | 2 | 07:27 | 20:48 |
| 39 | soleh | 2026-01-09 | Jumat | 07:3118:51 | 07:31<br>18:51 | 2 | 07:31 | 18:51 |
| 39 | soleh | 2026-01-10 | Sabtu | 07:2422:02 | 07:24<br>22:02 | 2 | 07:24 | 22:02 |
| 39 | soleh | 2026-01-12 | Senin | 07:2820:08 | 07:28<br>20:08 | 2 | 07:28 | 20:08 |
| 39 | soleh | 2026-01-13 | Selasa | 07:1819:32 | 07:18<br>19:32 | 2 | 07:18 | 19:32 |
| 39 | soleh | 2026-01-14 | Rabu | 07:2919:1619:16 | 07:29<br>19:16<br>19:16 | 3 | 07:29 | 19:16 |
| 39 | soleh | 2026-01-15 | Kamis | 07:1818:37 | 07:18<br>18:37 | 2 | 07:18 | 18:37 |
| 39 | soleh | 2026-01-17 | Sabtu | 07:2917:19 | 07:29<br>17:19 | 2 | 07:29 | 17:19 |
| 39 | soleh | 2026-01-19 | Senin | 07:2519:46 | 07:25<br>19:46 | 2 | 07:25 | 19:46 |
| 39 | soleh | 2026-01-20 | Selasa | 07:2819:56 | 07:28<br>19:56 | 2 | 07:28 | 19:56 |
| 40 | urwatuluska | 2025-12-31 | Rabu | 08:18 | 08:18 | 1 | 08:18 | 08:18 |
| 40 | urwatuluska | 2026-01-02 | Jumat | 08:00 | 08:00 | 1 | 08:00 | 08:00 |
| 40 | urwatuluska | 2026-01-03 | Sabtu | 07:5114:15 | 07:51<br>14:15 | 2 | 07:51 | 14:15 |
| 40 | urwatuluska | 2026-01-05 | Senin | 08:0819:09 | 08:08<br>19:09 | 2 | 08:08 | 19:09 |
| 40 | urwatuluska | 2026-01-06 | Selasa | 08:0419:24 | 08:04<br>19:24 | 2 | 08:04 | 19:24 |
| 40 | urwatuluska | 2026-01-08 | Kamis | 07:5917:33 | 07:59<br>17:33 | 2 | 07:59 | 17:33 |
| 40 | urwatuluska | 2026-01-09 | Jumat | 07:4917:1517:15 | 07:49<br>17:15<br>17:15 | 3 | 07:49 | 17:15 |
| 40 | urwatuluska | 2026-01-10 | Sabtu | 08:1708:1708:1718:0918:09 | 08:17<br>08:17<br>08:17<br>18:09<br>18:09 | 5 | 08:17 | 18:09 |
| 40 | urwatuluska | 2026-01-12 | Senin | 07:5818:5918:59 | 07:58<br>18:59<br>18:59 | 3 | 07:58 | 18:59 |
| 40 | urwatuluska | 2026-01-13 | Selasa | 19:3119:31 | 19:31<br>19:31 | 2 | 19:31 | 19:31 |
| 40 | urwatuluska | 2026-01-14 | Rabu | 07:4307:4319:1219:12 | 07:43<br>07:43<br>19:12<br>19:12 | 4 | 07:43 | 19:12 |
| 40 | urwatuluska | 2026-01-15 | Kamis | 08:1008:1008:1018:5618:5618:5618:56 | 08:10<br>08:10<br>08:10<br>18:56<br>18:56<br>18:56<br>18:56 | 7 | 08:10 | 18:56 |
| 40 | urwatuluska | 2026-01-17 | Sabtu | 08:1208:1217:1817:1817:1817:1822:0222:0222:0222:02 | 08:12<br>08:12<br>17:18<br>17:18<br>17:18<br>17:18<br>22:02<br>22:02<br>22:02<br>22:02 | 10 | 08:12 | 22:02 |
| 40 | urwatuluska | 2026-01-19 | Senin | 08:1208:1219:4419:4419:45 | 08:12<br>08:12<br>19:44<br>19:44<br>19:45 | 5 | 08:12 | 19:45 |
| 40 | urwatuluska | 2026-01-20 | Selasa | 08:0908:0908:1008:1008:1017:1317:1317:1317:13 | 08:09<br>08:09<br>08:10<br>08:10<br>08:10<br>17:13<br>17:13<br>17:13<br>17:13 | 9 | 08:09 | 17:13 |
| 44 | REJEKISIREGAR | 2026-01-10 | Sabtu | 08:07 | 08:07 | 1 | 08:07 | 08:07 |

## Lampiran — sheet tersembunyi `Sheet1`

Sheet ini berstatus **hidden** dan berisi ringkasan historis. Label periodenya `2025-05-26 ~ 2025-06-10`, tetapi kolom tanggal yang tersedia hanya 26 Mei sampai 1 Juni 2025. Data ini tidak berada dalam periode Januari 2026 dan diperlakukan sebagai arsip/template lama.

### Daftar personel historis

| ID | Urutan | Nama | Hari terisi | Jumlah scan terbaca |
| :--- | ---: | :--- | ---: | ---: |
| 0011 | 1 | Suwardi | 7 | 7 |
| 0007 | 2 | Afriyandi | 5 | 5 |
| 0008 | 3 | Indra | 3 | 3 |
| 0012 | 4 | Joni Septian | 5 | 5 |
| 0016 | 5 | Fadli | 6 | 12 |
| 0010 | 6 | Febri Angga | 5 | 5 |
| 0009 | 7 | Daniel Sitepu | 5 | 5 |
| 0015 | 8 | Heri Suhedi | 4 | 14 |
| 0006 | 9 | Dean Martin | 6 | 6 |
| 0013 | 10 | Martono | 7 | 7 |
| 0005 | 11 | Samsul Bahri | 5 | 5 |
| 0017 | 12 | Hendrik | 7 | 17 |
| 0022 | 13 | Azril M | 0 | 0 |
| 0023 | 14 | Efendi Harahap | 5 | 10 |
| 0020 | 15 | Darmawan | 5 | 9 |
| 0031 | 16 | Adi Sarnata | 5 | 10 |
| 0021 | 17 | Akirullah | 5 | 9 |
| 0014 | 18 | Abdul Karim | 5 | 10 |
| 0026 | 19 | Al Fiqri | 0 | 0 |
| 0025 | 20 | Fajar | 5 | 9 |
| 0019 | 21 | Amirudin | 3 | 6 |
| 0018 | 22 | Koeswanto | 6 | 11 |
| 0024 | 23 | David Aritonang | 5 | 10 |
| 0027 | — | Jorlan | 7 | 16 |
| 0028 | — | Sudirman H | 7 | 15 |
| 0029 | — | Edy Jaka S | 0 | 0 |
| 0030 | — | Taufiq H | 7 | 24 |

### Rekaman historis yang terisi

| ID | Urutan | Nama | Tanggal | Hari | Nilai mentah | Urutan scan | Jumlah scan |
| :--- | ---: | :--- | :---: | :--- | :--- | :--- | ---: |
| 0011 | 1 | Suwardi | 2025-05-26 | Senin | 20:20 | 20:20 | 1 |
| 0011 | 1 | Suwardi | 2025-05-27 | Selasa | 16:12 | 16:12 | 1 |
| 0011 | 1 | Suwardi | 2025-05-28 | Rabu | 18:02 | 18:02 | 1 |
| 0011 | 1 | Suwardi | 2025-05-29 | Kamis | 16:10 | 16:10 | 1 |
| 0011 | 1 | Suwardi | 2025-05-30 | Jumat | 17:11 | 17:11 | 1 |
| 0011 | 1 | Suwardi | 2025-05-31 | Sabtu | 17:01 | 17:01 | 1 |
| 0011 | 1 | Suwardi | 2025-06-01 | Minggu | 17:00 | 17:00 | 1 |
| 0007 | 2 | Afriyandi | 2025-05-26 | Senin | 17:15 | 17:15 | 1 |
| 0007 | 2 | Afriyandi | 2025-05-27 | Selasa | 18:11 | 18:11 | 1 |
| 0007 | 2 | Afriyandi | 2025-05-28 | Rabu | 18:04 | 18:04 | 1 |
| 0007 | 2 | Afriyandi | 2025-05-30 | Jumat | 17:03 | 17:03 | 1 |
| 0007 | 2 | Afriyandi | 2025-05-31 | Sabtu | 14:21 | 14:21 | 1 |
| 0008 | 3 | Indra | 2025-05-26 | Senin | 17:10 | 17:10 | 1 |
| 0008 | 3 | Indra | 2025-05-27 | Selasa | 17:17 | 17:17 | 1 |
| 0008 | 3 | Indra | 2025-05-28 | Rabu | 16:05 | 16:05 | 1 |
| 0012 | 4 | Joni Septian | 2025-05-26 | Senin | 17:33 | 17:33 | 1 |
| 0012 | 4 | Joni Septian | 2025-05-27 | Selasa | 17:06 | 17:06 | 1 |
| 0012 | 4 | Joni Septian | 2025-05-28 | Rabu | 18:01 | 18:01 | 1 |
| 0012 | 4 | Joni Septian | 2025-05-30 | Jumat | 17:10 | 17:10 | 1 |
| 0012 | 4 | Joni Septian | 2025-05-31 | Sabtu | 14:22 | 14:22 | 1 |
| 0016 | 5 | Fadli | 2025-05-26 | Senin | 19:0720:28 | 19:07<br>20:28 | 2 |
| 0016 | 5 | Fadli | 2025-05-27 | Selasa | 07:3116:12 | 07:31<br>16:12 | 2 |
| 0016 | 5 | Fadli | 2025-05-28 | Rabu | 07:2418:01 | 07:24<br>18:01 | 2 |
| 0016 | 5 | Fadli | 2025-05-29 | Kamis | 07:5716:01 | 07:57<br>16:01 | 2 |
| 0016 | 5 | Fadli | 2025-05-30 | Jumat | 07:3217:06 | 07:32<br>17:06 | 2 |
| 0016 | 5 | Fadli | 2025-05-31 | Sabtu | 07:5217:00 | 07:52<br>17:00 | 2 |
| 0010 | 6 | Febri Angga | 2025-05-26 | Senin | 17:15 | 17:15 | 1 |
| 0010 | 6 | Febri Angga | 2025-05-27 | Selasa | 17:02 | 17:02 | 1 |
| 0010 | 6 | Febri Angga | 2025-05-28 | Rabu | 18:04 | 18:04 | 1 |
| 0010 | 6 | Febri Angga | 2025-05-30 | Jumat | 18:29 | 18:29 | 1 |
| 0010 | 6 | Febri Angga | 2025-05-31 | Sabtu | 14:27 | 14:27 | 1 |
| 0009 | 7 | Daniel Sitepu | 2025-05-26 | Senin | 17:12 | 17:12 | 1 |
| 0009 | 7 | Daniel Sitepu | 2025-05-27 | Selasa | 17:02 | 17:02 | 1 |
| 0009 | 7 | Daniel Sitepu | 2025-05-28 | Rabu | 18:04 | 18:04 | 1 |
| 0009 | 7 | Daniel Sitepu | 2025-05-30 | Jumat | 18:30 | 18:30 | 1 |
| 0009 | 7 | Daniel Sitepu | 2025-05-31 | Sabtu | 14:27 | 14:27 | 1 |
| 0015 | 8 | Heri Suhedi | 2025-05-27 | Selasa | 07:5417:3117:31 | 07:54<br>17:31<br>17:31 | 3 |
| 0015 | 8 | Heri Suhedi | 2025-05-28 | Rabu | 07:3807:3818:0518:05 | 07:38<br>07:38<br>18:05<br>18:05 | 4 |
| 0015 | 8 | Heri Suhedi | 2025-05-30 | Jumat | 07:4107:4117:0817:08 | 07:41<br>07:41<br>17:08<br>17:08 | 4 |
| 0015 | 8 | Heri Suhedi | 2025-05-31 | Sabtu | 08:3408:3512:02 | 08:34<br>08:35<br>12:02 | 3 |
| 0006 | 9 | Dean Martin | 2025-05-27 | Selasa | 07:53 | 07:53 | 1 |
| 0006 | 9 | Dean Martin | 2025-05-28 | Rabu | 21:43 | 21:43 | 1 |
| 0006 | 9 | Dean Martin | 2025-05-29 | Kamis | 21:32 | 21:32 | 1 |
| 0006 | 9 | Dean Martin | 2025-05-30 | Jumat | 22:00 | 22:00 | 1 |
| 0006 | 9 | Dean Martin | 2025-05-31 | Sabtu | 19:30 | 19:30 | 1 |
| 0006 | 9 | Dean Martin | 2025-06-01 | Minggu | 21:57 | 21:57 | 1 |
| 0013 | 10 | Martono | 2025-05-26 | Senin | 18:08 | 18:08 | 1 |
| 0013 | 10 | Martono | 2025-05-27 | Selasa | 18:03 | 18:03 | 1 |
| 0013 | 10 | Martono | 2025-05-28 | Rabu | 18:06 | 18:06 | 1 |
| 0013 | 10 | Martono | 2025-05-29 | Kamis | 16:11 | 16:11 | 1 |
| 0013 | 10 | Martono | 2025-05-30 | Jumat | 17:21 | 17:21 | 1 |
| 0013 | 10 | Martono | 2025-05-31 | Sabtu | 17:07 | 17:07 | 1 |
| 0013 | 10 | Martono | 2025-06-01 | Minggu | 17:17 | 17:17 | 1 |
| 0005 | 11 | Samsul Bahri | 2025-05-26 | Senin | 19:41 | 19:41 | 1 |
| 0005 | 11 | Samsul Bahri | 2025-05-27 | Selasa | 19:23 | 19:23 | 1 |
| 0005 | 11 | Samsul Bahri | 2025-05-28 | Rabu | 19:05 | 19:05 | 1 |
| 0005 | 11 | Samsul Bahri | 2025-05-30 | Jumat | 17:00 | 17:00 | 1 |
| 0005 | 11 | Samsul Bahri | 2025-05-31 | Sabtu | 13:56 | 13:56 | 1 |
| 0017 | 12 | Hendrik | 2025-05-26 | Senin | 16:3720:30 | 16:37<br>20:30 | 2 |
| 0017 | 12 | Hendrik | 2025-05-27 | Selasa | 07:3216:12 | 07:32<br>16:12 | 2 |
| 0017 | 12 | Hendrik | 2025-05-28 | Rabu | 07:2407:2518:03 | 07:24<br>07:25<br>18:03 | 3 |
| 0017 | 12 | Hendrik | 2025-05-29 | Kamis | 07:5616:0116:01 | 07:56<br>16:01<br>16:01 | 3 |
| 0017 | 12 | Hendrik | 2025-05-30 | Jumat | 07:3217:09 | 07:32<br>17:09 | 2 |
| 0017 | 12 | Hendrik | 2025-05-31 | Sabtu | 07:3607:3617:04 | 07:36<br>07:36<br>17:04 | 3 |
| 0017 | 12 | Hendrik | 2025-06-01 | Minggu | 07:5717:01 | 07:57<br>17:01 | 2 |
| 0023 | 14 | Efendi Harahap | 2025-05-28 | Rabu | 07:3319:55 | 07:33<br>19:55 | 2 |
| 0023 | 14 | Efendi Harahap | 2025-05-29 | Kamis | 07:0918:16 | 07:09<br>18:16 | 2 |
| 0023 | 14 | Efendi Harahap | 2025-05-30 | Jumat | 07:0717:00 | 07:07<br>17:00 | 2 |
| 0023 | 14 | Efendi Harahap | 2025-05-31 | Sabtu | 07:1717:17 | 07:17<br>17:17 | 2 |
| 0023 | 14 | Efendi Harahap | 2025-06-01 | Minggu | 07:2317:01 | 07:23<br>17:01 | 2 |
| 0020 | 15 | Darmawan | 2025-05-27 | Selasa | 17:38 | 17:38 | 1 |
| 0020 | 15 | Darmawan | 2025-05-28 | Rabu | 07:1619:01 | 07:16<br>19:01 | 2 |
| 0020 | 15 | Darmawan | 2025-05-30 | Jumat | 07:3517:01 | 07:35<br>17:01 | 2 |
| 0020 | 15 | Darmawan | 2025-05-31 | Sabtu | 08:1317:16 | 08:13<br>17:16 | 2 |
| 0020 | 15 | Darmawan | 2025-06-01 | Minggu | 07:4317:01 | 07:43<br>17:01 | 2 |
| 0031 | 16 | Adi Sarnata | 2025-05-28 | Rabu | 08:1319:02 | 08:13<br>19:02 | 2 |
| 0031 | 16 | Adi Sarnata | 2025-05-29 | Kamis | 08:2516:03 | 08:25<br>16:03 | 2 |
| 0031 | 16 | Adi Sarnata | 2025-05-30 | Jumat | 08:1417:08 | 08:14<br>17:08 | 2 |
| 0031 | 16 | Adi Sarnata | 2025-05-31 | Sabtu | 08:2417:21 | 08:24<br>17:21 | 2 |
| 0031 | 16 | Adi Sarnata | 2025-06-01 | Minggu | 07:5518:00 | 07:55<br>18:00 | 2 |
| 0021 | 17 | Akirullah | 2025-05-26 | Senin | 16:17 | 16:17 | 1 |
| 0021 | 17 | Akirullah | 2025-05-27 | Selasa | 07:3218:18 | 07:32<br>18:18 | 2 |
| 0021 | 17 | Akirullah | 2025-05-28 | Rabu | 07:3616:03 | 07:36<br>16:03 | 2 |
| 0021 | 17 | Akirullah | 2025-05-30 | Jumat | 07:3417:00 | 07:34<br>17:00 | 2 |
| 0021 | 17 | Akirullah | 2025-05-31 | Sabtu | 07:5414:26 | 07:54<br>14:26 | 2 |
| 0014 | 18 | Abdul Karim | 2025-05-26 | Senin | 16:4217:06 | 16:42<br>17:06 | 2 |
| 0014 | 18 | Abdul Karim | 2025-05-27 | Selasa | 07:2916:11 | 07:29<br>16:11 | 2 |
| 0014 | 18 | Abdul Karim | 2025-05-28 | Rabu | 07:1916:21 | 07:19<br>16:21 | 2 |
| 0014 | 18 | Abdul Karim | 2025-05-30 | Jumat | 07:2717:02 | 07:27<br>17:02 | 2 |
| 0014 | 18 | Abdul Karim | 2025-05-31 | Sabtu | 08:3412:00 | 08:34<br>12:00 | 2 |
| 0025 | 20 | Fajar | 2025-05-26 | Senin | 15:2117:17 | 15:21<br>17:17 | 2 |
| 0025 | 20 | Fajar | 2025-05-27 | Selasa | 07:24 | 07:24 | 1 |
| 0025 | 20 | Fajar | 2025-05-28 | Rabu | 07:3117:18 | 07:31<br>17:18 | 2 |
| 0025 | 20 | Fajar | 2025-05-30 | Jumat | 07:2917:06 | 07:29<br>17:06 | 2 |
| 0025 | 20 | Fajar | 2025-05-31 | Sabtu | 07:4814:04 | 07:48<br>14:04 | 2 |
| 0019 | 21 | Amirudin | 2025-05-28 | Rabu | 08:1017:06 | 08:10<br>17:06 | 2 |
| 0019 | 21 | Amirudin | 2025-05-30 | Jumat | 10:3417:02 | 10:34<br>17:02 | 2 |
| 0019 | 21 | Amirudin | 2025-05-31 | Sabtu | 08:0217:00 | 08:02<br>17:00 | 2 |
| 0018 | 22 | Koeswanto | 2025-05-26 | Senin | 17:08 | 17:08 | 1 |
| 0018 | 22 | Koeswanto | 2025-05-27 | Selasa | 07:2617:01 | 07:26<br>17:01 | 2 |
| 0018 | 22 | Koeswanto | 2025-05-28 | Rabu | 07:1817:30 | 07:18<br>17:30 | 2 |
| 0018 | 22 | Koeswanto | 2025-05-30 | Jumat | 10:3517:03 | 10:35<br>17:03 | 2 |
| 0018 | 22 | Koeswanto | 2025-05-31 | Sabtu | 07:3117:01 | 07:31<br>17:01 | 2 |
| 0018 | 22 | Koeswanto | 2025-06-01 | Minggu | 07:4717:01 | 07:47<br>17:01 | 2 |
| 0024 | 23 | David Aritonang | 2025-05-26 | Senin | 16:5017:07 | 16:50<br>17:07 | 2 |
| 0024 | 23 | David Aritonang | 2025-05-27 | Selasa | 07:2817:01 | 07:28<br>17:01 | 2 |
| 0024 | 23 | David Aritonang | 2025-05-28 | Rabu | 07:3117:30 | 07:31<br>17:30 | 2 |
| 0024 | 23 | David Aritonang | 2025-05-30 | Jumat | 07:3017:11 | 07:30<br>17:11 | 2 |
| 0024 | 23 | David Aritonang | 2025-05-31 | Sabtu | 07:2117:00 | 07:21<br>17:00 | 2 |
| 0027 | — | Jorlan | 2025-05-26 | Senin | 18:53 | 18:53 | 1 |
| 0027 | — | Jorlan | 2025-05-27 | Selasa | 07:5318:5518:56 | 07:53<br>18:55<br>18:56 | 3 |
| 0027 | — | Jorlan | 2025-05-28 | Rabu | 07:0607:0618:40 | 07:06<br>07:06<br>18:40 | 3 |
| 0027 | — | Jorlan | 2025-05-29 | Kamis | 07:2318:54 | 07:23<br>18:54 | 2 |
| 0027 | — | Jorlan | 2025-05-30 | Jumat | 07:0418:57 | 07:04<br>18:57 | 2 |
| 0027 | — | Jorlan | 2025-05-31 | Sabtu | 07:0907:1718:57 | 07:09<br>07:17<br>18:57 | 3 |
| 0027 | — | Jorlan | 2025-06-01 | Minggu | 07:0618:57 | 07:06<br>18:57 | 2 |
| 0028 | — | Sudirman H | 2025-05-26 | Senin | 16:2219:02 | 16:22<br>19:02 | 2 |
| 0028 | — | Sudirman H | 2025-05-27 | Selasa | 07:2819:01 | 07:28<br>19:01 | 2 |
| 0028 | — | Sudirman H | 2025-05-28 | Rabu | 06:5919:00 | 06:59<br>19:00 | 2 |
| 0028 | — | Sudirman H | 2025-05-29 | Kamis | 07:0419:02 | 07:04<br>19:02 | 2 |
| 0028 | — | Sudirman H | 2025-05-30 | Jumat | 07:0419:00 | 07:04<br>19:00 | 2 |
| 0028 | — | Sudirman H | 2025-05-31 | Sabtu | 07:0518:59 | 07:05<br>18:59 | 2 |
| 0028 | — | Sudirman H | 2025-06-01 | Minggu | 07:0118:5218:52 | 07:01<br>18:52<br>18:52 | 3 |
| 0030 | — | Taufiq H | 2025-05-26 | Senin | 19:00 | 19:00 | 1 |
| 0030 | — | Taufiq H | 2025-05-27 | Selasa | 09:2618:5918:59 | 09:26<br>18:59<br>18:59 | 3 |
| 0030 | — | Taufiq H | 2025-05-28 | Rabu | 07:0907:0918:5618:56 | 07:09<br>07:09<br>18:56<br>18:56 | 4 |
| 0030 | — | Taufiq H | 2025-05-29 | Kamis | 07:0607:0618:5918:59 | 07:06<br>07:06<br>18:59<br>18:59 | 4 |
| 0030 | — | Taufiq H | 2025-05-30 | Jumat | 07:0507:0518:5918:59 | 07:05<br>07:05<br>18:59<br>18:59 | 4 |
| 0030 | — | Taufiq H | 2025-05-31 | Sabtu | 07:0107:0118:5518:55 | 07:01<br>07:01<br>18:55<br>18:55 | 4 |
| 0030 | — | Taufiq H | 2025-06-01 | Minggu | 07:0207:0318:5218:52 | 07:02<br>07:03<br>18:52<br>18:52 | 4 |

## Lampiran — sheet tersembunyi `Lap. Log Absen`

Sheet ini berstatus **hidden** dan memuat log historis **26 Mei–20 Juni 2025**. Setiap personel menggunakan dua baris: baris metadata (`ID`, `Nama`, `Dept.`) dan baris berikutnya untuk scan harian.

### Daftar personel historis

| ID | Nama | Departemen/posisi | Hari terisi | Jumlah scan terbaca |
| :--- | :--- | :--- | ---: | ---: |
| 0001 | Dany Agung | Head Equipment | 18 | 34 |
| 0002 | Guswan Arizal | KA Logistik | 15 | 23 |
| 0003 | Heryanto | Cem Manager | 0 | 0 |
| 0004 | Gigin Ahmad | Cem Inspector | 12 | 21 |
| 0005 | Samsul Bahri | Project Control | 21 | 52 |
| 0006 | Dean Martin | Equipment SPV | 24 | 47 |
| 0007 | Afriyandi | Mekanik | 21 | 51 |
| 0008 | Indra | Mekanik | 17 | 31 |
| 0009 | Daniel Sitepu | Mekanik 2 | 21 | 87 |
| 0010 | Febri Angga | Mekanik 2 | 19 | 57 |
| 0011 | Suwardi | Welder | 23 | 47 |
| 0012 | Joni Septian | Mekanik/ Greasing | 21 | 38 |
| 0013 | Martono | Warehouse | 25 | 50 |
| 0014 | Abdul Karim | Welder | 14 | 27 |
| 0015 | Heri Suhedi | Helper | 14 | 39 |
| 0016 | Fadli | Op Exca / Ass mekanik | 13 | 25 |
| 0017 | Hendrik | Teknisi Listrik | 23 | 70 |
| 0018 | Koeswanto | SMK3L | 15 | 29 |
| 0019 | Amirudin | SMK3L | 19 | 38 |
| 0020 | Darmawan | Mekanik | 21 | 41 |
| 0021 | Akirullah | Mekanik | 16 | 31 |
| 0022 | Azril M | Mekanik A2B | 8 | 12 |
| 0023 | Efendi Harahap | Mekanik A2B | 17 | 33 |
| 0024 | David Aritonang | Flagman | 21 | 42 |
| 0025 | Fajar | Ass admin | 13 | 25 |
| 0026 | Alfiqir | Helper | 8 | 20 |
| 0027 | Jorlan | Security | 20 | 39 |
| 0028 | Sudirman H | Security | 20 | 66 |
| 0029 | Edy Jaka S | Security | 15 | 80 |
| 0030 | Taufiq H | Security | 26 | 101 |
| 0031 | Adi Sarnata | Mekanik A2B | 17 | 34 |

### Rekaman historis yang terisi

| ID | Nama | Departemen/posisi | Tanggal | Hari | Nilai mentah | Urutan scan | Jumlah scan |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- | ---: |
| 0001 | Dany Agung | Head Equipment | 2025-05-26 | Senin | 15:2422:54 | 15:24<br>22:54 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-05-27 | Selasa | 06:1822:33 | 06:18<br>22:33 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-05-28 | Rabu | 06:3220:29 | 06:32<br>20:29 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-05-31 | Sabtu | 05:4220:18 | 05:42<br>20:18 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-02 | Senin | 05:4222:14 | 05:42<br>22:14 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-03 | Selasa | 10:4622:24 | 10:46<br>22:24 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-04 | Rabu | 06:2521:32 | 06:25<br>21:32 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-05 | Kamis | 06:36 | 06:36 | 1 |
| 0001 | Dany Agung | Head Equipment | 2025-06-09 | Senin | 05:5621:24 | 05:56<br>21:24 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-10 | Selasa | 06:1522:04 | 06:15<br>22:04 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-11 | Rabu | 06:0821:05 | 06:08<br>21:05 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-12 | Kamis | 05:4022:02 | 05:40<br>22:02 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-13 | Jumat | 06:3022:18 | 06:30<br>22:18 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-14 | Sabtu | 06:2219:19 | 06:22<br>19:19 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-16 | Senin | 05:4418:27 | 05:44<br>18:27 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-18 | Rabu | 06:3321:07 | 06:33<br>21:07 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-19 | Kamis | 05:4321:22 | 05:43<br>21:22 | 2 |
| 0001 | Dany Agung | Head Equipment | 2025-06-20 | Jumat | 06:02 | 06:02 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-05-30 | Jumat | 07:4618:29 | 07:46<br>18:29 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-05-31 | Sabtu | 07:5517:25 | 07:55<br>17:25 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-02 | Senin | 07:49 | 07:49 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-03 | Selasa | 17:56 | 17:56 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-04 | Rabu | 07:27 | 07:27 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-05 | Kamis | 07:22 | 07:22 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-09 | Senin | 07:5417:43 | 07:54<br>17:43 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-10 | Selasa | 08:0217:07 | 08:02<br>17:07 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-11 | Rabu | 07:1720:10 | 07:17<br>20:10 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-12 | Kamis | 07:1417:56 | 07:14<br>17:56 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-13 | Jumat | 08:5919:08 | 08:59<br>19:08 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-14 | Sabtu | 20:29 | 20:29 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-16 | Senin | 08:44 | 08:44 | 1 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-17 | Selasa | 08:0018:03 | 08:00<br>18:03 | 2 |
| 0002 | Guswan Arizal | KA Logistik | 2025-06-18 | Rabu | 07:15 | 07:15 | 1 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-02 | Senin | 08:3521:34 | 08:35<br>21:34 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-03 | Selasa | 06:3820:40 | 06:38<br>20:40 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-04 | Rabu | 07:0419:36 | 07:04<br>19:36 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-05 | Kamis | 07:1618:43 | 07:16<br>18:43 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-09 | Senin | 17:51 | 17:51 | 1 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-10 | Selasa | 11:2518:20 | 11:25<br>18:20 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-14 | Sabtu | 17:3617:37 | 17:36<br>17:37 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-16 | Senin | 08:1318:12 | 08:13<br>18:12 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-17 | Selasa | 11:5418:09 | 11:54<br>18:09 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-18 | Rabu | 07:03 | 07:03 | 1 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-19 | Kamis | 09:5619:23 | 09:56<br>19:23 | 2 |
| 0004 | Gigin Ahmad | Cem Inspector | 2025-06-20 | Jumat | 10:28 | 10:28 | 1 |
| 0005 | Samsul Bahri | Project Control | 2025-05-26 | Senin | 14:4814:4817:1117:1219:4019:41 | 14:48<br>14:48<br>17:11<br>17:12<br>19:40<br>19:41 | 6 |
| 0005 | Samsul Bahri | Project Control | 2025-05-27 | Selasa | 07:2018:1619:23 | 07:20<br>18:16<br>19:23 | 3 |
| 0005 | Samsul Bahri | Project Control | 2025-05-28 | Rabu | 07:2207:2307:2319:05 | 07:22<br>07:23<br>07:23<br>19:05 | 4 |
| 0005 | Samsul Bahri | Project Control | 2025-05-30 | Jumat | 07:2017:00 | 07:20<br>17:00 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-05-31 | Sabtu | 07:5813:5613:56 | 07:58<br>13:56<br>13:56 | 3 |
| 0005 | Samsul Bahri | Project Control | 2025-06-02 | Senin | 07:1917:39 | 07:19<br>17:39 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-03 | Selasa | 08:5018:20 | 08:50<br>18:20 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-04 | Rabu | 07:2717:0519:25 | 07:27<br>17:05<br>19:25 | 3 |
| 0005 | Samsul Bahri | Project Control | 2025-06-05 | Kamis | 07:2407:2419:19 | 07:24<br>07:24<br>19:19 | 3 |
| 0005 | Samsul Bahri | Project Control | 2025-06-06 | Jumat | 10:2813:53 | 10:28<br>13:53 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-09 | Senin | 07:2117:51 | 07:21<br>17:51 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-10 | Selasa | 07:2019:17 | 07:20<br>19:17 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-11 | Rabu | 07:1619:10 | 07:16<br>19:10 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-12 | Kamis | 07:2419:14 | 07:24<br>19:14 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-13 | Jumat | 07:1818:05 | 07:18<br>18:05 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-14 | Sabtu | 07:2207:2220:07 | 07:22<br>07:22<br>20:07 | 3 |
| 0005 | Samsul Bahri | Project Control | 2025-06-16 | Senin | 07:2221:14 | 07:22<br>21:14 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-17 | Selasa | 08:0519:27 | 08:05<br>19:27 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-18 | Rabu | 07:2620:00 | 07:26<br>20:00 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-19 | Kamis | 07:2419:38 | 07:24<br>19:38 | 2 |
| 0005 | Samsul Bahri | Project Control | 2025-06-20 | Jumat | 07:20 | 07:20 | 1 |
| 0006 | Dean Martin | Equipment SPV | 2025-05-27 | Selasa | 07:53 | 07:53 | 1 |
| 0006 | Dean Martin | Equipment SPV | 2025-05-28 | Rabu | 00:2007:2521:43 | 00:20<br>07:25<br>21:43 | 3 |
| 0006 | Dean Martin | Equipment SPV | 2025-05-29 | Kamis | 08:5721:32 | 08:57<br>21:32 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-05-30 | Jumat | 08:3322:00 | 08:33<br>22:00 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-05-31 | Sabtu | 08:0019:30 | 08:00<br>19:30 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-01 | Minggu | 13:0121:57 | 13:01<br>21:57 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-02 | Senin | 07:2920:07 | 07:29<br>20:07 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-03 | Selasa | 07:2920:28 | 07:29<br>20:28 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-04 | Rabu | 07:3623:08 | 07:36<br>23:08 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-05 | Kamis | 07:3918:59 | 07:39<br>18:59 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-07 | Sabtu | 08:3814:35 | 08:38<br>14:35 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-08 | Minggu | 09:5721:53 | 09:57<br>21:53 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-09 | Senin | 07:3221:40 | 07:32<br>21:40 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-10 | Selasa | 07:3222:57 | 07:32<br>22:57 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-11 | Rabu | 08:2122:48 | 08:21<br>22:48 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-12 | Kamis | 07:4823:26 | 07:48<br>23:26 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-13 | Jumat | 08:0019:58 | 08:00<br>19:58 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-14 | Sabtu | 07:2421:09 | 07:24<br>21:09 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-15 | Minggu | 13:0522:59 | 13:05<br>22:59 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-16 | Senin | 07:2922:15 | 07:29<br>22:15 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-17 | Selasa | 08:1120:54 | 08:11<br>20:54 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-18 | Rabu | 07:5922:43 | 07:59<br>22:43 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-19 | Kamis | 07:2521:21 | 07:25<br>21:21 | 2 |
| 0006 | Dean Martin | Equipment SPV | 2025-06-20 | Jumat | 07:25 | 07:25 | 1 |
| 0007 | Afriyandi | Mekanik | 2025-05-26 | Senin | 17:1117:1417:1517:15 | 17:11<br>17:14<br>17:15<br>17:15 | 4 |
| 0007 | Afriyandi | Mekanik | 2025-05-27 | Selasa | 07:2017:0518:11 | 07:20<br>17:05<br>18:11 | 3 |
| 0007 | Afriyandi | Mekanik | 2025-05-28 | Rabu | 06:4618:0418:0418:04 | 06:46<br>18:04<br>18:04<br>18:04 | 4 |
| 0007 | Afriyandi | Mekanik | 2025-05-30 | Jumat | 07:0317:03 | 07:03<br>17:03 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-05-31 | Sabtu | 07:2107:2114:21 | 07:21<br>07:21<br>14:21 | 3 |
| 0007 | Afriyandi | Mekanik | 2025-06-02 | Senin | 07:1907:2007:2007:2117:49 | 07:19<br>07:20<br>07:20<br>07:21<br>17:49 | 5 |
| 0007 | Afriyandi | Mekanik | 2025-06-03 | Selasa | 07:2717:10 | 07:27<br>17:10 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-04 | Rabu | 07:3717:22 | 07:37<br>17:22 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-05 | Kamis | 07:3117:37 | 07:31<br>17:37 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-06 | Jumat | 10:28 | 10:28 | 1 |
| 0007 | Afriyandi | Mekanik | 2025-06-08 | Minggu | 07:5017:41 | 07:50<br>17:41 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-09 | Senin | 07:1317:48 | 07:13<br>17:48 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-10 | Selasa | 07:3118:49 | 07:31<br>18:49 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-11 | Rabu | 07:2907:30 | 07:29<br>07:30 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-12 | Kamis | 07:3207:3218:32 | 07:32<br>07:32<br>18:32 | 3 |
| 0007 | Afriyandi | Mekanik | 2025-06-13 | Jumat | 07:2018:21 | 07:20<br>18:21 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-16 | Senin | 07:5018:32 | 07:50<br>18:32 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-17 | Selasa | 07:4318:59 | 07:43<br>18:59 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-18 | Rabu | 07:3218:3718:37 | 07:32<br>18:37<br>18:37 | 3 |
| 0007 | Afriyandi | Mekanik | 2025-06-19 | Kamis | 07:3219:18 | 07:32<br>19:18 | 2 |
| 0007 | Afriyandi | Mekanik | 2025-06-20 | Jumat | 07:18 | 07:18 | 1 |
| 0008 | Indra | Mekanik | 2025-05-26 | Senin | 17:10 | 17:10 | 1 |
| 0008 | Indra | Mekanik | 2025-05-27 | Selasa | 07:3117:17 | 07:31<br>17:17 | 2 |
| 0008 | Indra | Mekanik | 2025-05-28 | Rabu | 07:3516:05 | 07:35<br>16:05 | 2 |
| 0008 | Indra | Mekanik | 2025-06-03 | Selasa | 07:4817:01 | 07:48<br>17:01 | 2 |
| 0008 | Indra | Mekanik | 2025-06-04 | Rabu | 07:5917:07 | 07:59<br>17:07 | 2 |
| 0008 | Indra | Mekanik | 2025-06-05 | Kamis | 07:40 | 07:40 | 1 |
| 0008 | Indra | Mekanik | 2025-06-09 | Senin | 07:4617:14 | 07:46<br>17:14 | 2 |
| 0008 | Indra | Mekanik | 2025-06-10 | Selasa | 07:3517:01 | 07:35<br>17:01 | 2 |
| 0008 | Indra | Mekanik | 2025-06-11 | Rabu | 07:3916:26 | 07:39<br>16:26 | 2 |
| 0008 | Indra | Mekanik | 2025-06-12 | Kamis | 07:4716:12 | 07:47<br>16:12 | 2 |
| 0008 | Indra | Mekanik | 2025-06-13 | Jumat | 07:3317:13 | 07:33<br>17:13 | 2 |
| 0008 | Indra | Mekanik | 2025-06-14 | Sabtu | 07:3017:13 | 07:30<br>17:13 | 2 |
| 0008 | Indra | Mekanik | 2025-06-16 | Senin | 08:1016:26 | 08:10<br>16:26 | 2 |
| 0008 | Indra | Mekanik | 2025-06-17 | Selasa | 08:4216:05 | 08:42<br>16:05 | 2 |
| 0008 | Indra | Mekanik | 2025-06-18 | Rabu | 07:5217:04 | 07:52<br>17:04 | 2 |
| 0008 | Indra | Mekanik | 2025-06-19 | Kamis | 08:0416:20 | 08:04<br>16:20 | 2 |
| 0008 | Indra | Mekanik | 2025-06-20 | Jumat | 08:37 | 08:37 | 1 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-05-26 | Senin | 17:12 | 17:12 | 1 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-05-27 | Selasa | 07:5317:0217:02 | 07:53<br>17:02<br>17:02 | 3 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-05-28 | Rabu | 07:3807:3818:0418:04 | 07:38<br>07:38<br>18:04<br>18:04 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-05-30 | Jumat | 07:3907:3918:2918:3018:30 | 07:39<br>07:39<br>18:29<br>18:30<br>18:30 | 5 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-05-31 | Sabtu | 08:0908:0914:27 | 08:09<br>08:09<br>14:27 | 3 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-02 | Senin | 07:3007:3016:1316:13 | 07:30<br>07:30<br>16:13<br>16:13 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-03 | Selasa | 07:3907:3917:1017:10 | 07:39<br>07:39<br>17:10<br>17:10 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-04 | Rabu | 07:4307:4317:2617:3417:34 | 07:43<br>07:43<br>17:26<br>17:34<br>17:34 | 5 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-05 | Kamis | 07:3307:3316:2716:27 | 07:33<br>07:33<br>16:27<br>16:27 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-08 | Minggu | 07:3607:3617:4217:42 | 07:36<br>07:36<br>17:42<br>17:42 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-09 | Senin | 07:2607:2617:3017:30 | 07:26<br>07:26<br>17:30<br>17:30 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-10 | Selasa | 07:3407:3418:4918:49 | 07:34<br>07:34<br>18:49<br>18:49 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-11 | Rabu | 07:2407:2418:3818:38 | 07:24<br>07:24<br>18:38<br>18:38 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-12 | Kamis | 07:4007:4017:5817:58 | 07:40<br>07:40<br>17:58<br>17:58 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-13 | Jumat | 07:3007:3119:0919:0919:09 | 07:30<br>07:31<br>19:09<br>19:09<br>19:09 | 5 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-14 | Sabtu | 07:1907:1918:0518:0518:0918:10 | 07:19<br>07:19<br>18:05<br>18:05<br>18:09<br>18:10 | 6 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-16 | Senin | 07:5307:5317:5517:5518:0018:00 | 07:53<br>07:53<br>17:55<br>17:55<br>18:00<br>18:00 | 6 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-17 | Selasa | 07:4507:4516:0416:0419:0019:0019:00 | 07:45<br>07:45<br>16:04<br>16:04<br>19:00<br>19:00<br>19:00 | 7 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-18 | Rabu | 07:3007:3017:0517:05 | 07:30<br>07:30<br>17:05<br>17:05 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-19 | Kamis | 07:2607:2618:5418:54 | 07:26<br>07:26<br>18:54<br>18:54 | 4 |
| 0009 | Daniel Sitepu | Mekanik 2 | 2025-06-20 | Jumat | 07:4007:40 | 07:40<br>07:40 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-05-26 | Senin | 17:1417:1417:1417:1517:15 | 17:14<br>17:14<br>17:14<br>17:15<br>17:15 | 5 |
| 0010 | Febri Angga | Mekanik 2 | 2025-05-27 | Selasa | 07:2007:2017:0217:02 | 07:20<br>07:20<br>17:02<br>17:02 | 4 |
| 0010 | Febri Angga | Mekanik 2 | 2025-05-28 | Rabu | 06:4618:0318:0318:0418:04 | 06:46<br>18:03<br>18:03<br>18:04<br>18:04 | 5 |
| 0010 | Febri Angga | Mekanik 2 | 2025-05-30 | Jumat | 07:1707:1718:29 | 07:17<br>07:17<br>18:29 | 3 |
| 0010 | Febri Angga | Mekanik 2 | 2025-05-31 | Sabtu | 08:2014:2214:2214:27 | 08:20<br>14:22<br>14:22<br>14:27 | 4 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-02 | Senin | 07:1616:13 | 07:16<br>16:13 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-03 | Selasa | 07:4017:09 | 07:40<br>17:09 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-04 | Rabu | 07:2207:2217:26 | 07:22<br>07:22<br>17:26 | 3 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-05 | Kamis | 07:2216:27 | 07:22<br>16:27 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-08 | Minggu | 07:2817:4117:4117:41 | 07:28<br>17:41<br>17:41<br>17:41 | 4 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-09 | Senin | 07:1617:2917:31 | 07:16<br>17:29<br>17:31 | 3 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-11 | Rabu | 07:26 | 07:26 | 1 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-12 | Kamis | 07:3107:3118:30 | 07:31<br>07:31<br>18:30 | 3 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-13 | Jumat | 07:3107:3107:3107:3118:21 | 07:31<br>07:31<br>07:31<br>07:31<br>18:21 | 5 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-16 | Senin | 07:2618:32 | 07:26<br>18:32 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-17 | Selasa | 07:4318:5918:5918:59 | 07:43<br>18:59<br>18:59<br>18:59 | 4 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-18 | Rabu | 07:3418:37 | 07:34<br>18:37 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-19 | Kamis | 07:2719:36 | 07:27<br>19:36 | 2 |
| 0010 | Febri Angga | Mekanik 2 | 2025-06-20 | Jumat | 07:19 | 07:19 | 1 |
| 0011 | Suwardi | Welder | 2025-05-26 | Senin | 19:0620:30 | 19:06<br>20:30 | 2 |
| 0011 | Suwardi | Welder | 2025-05-27 | Selasa | 07:3116:12 | 07:31<br>16:12 | 2 |
| 0011 | Suwardi | Welder | 2025-05-28 | Rabu | 07:3318:02 | 07:33<br>18:02 | 2 |
| 0011 | Suwardi | Welder | 2025-05-29 | Kamis | 07:5616:10 | 07:56<br>16:10 | 2 |
| 0011 | Suwardi | Welder | 2025-05-30 | Jumat | 07:3817:11 | 07:38<br>17:11 | 2 |
| 0011 | Suwardi | Welder | 2025-05-31 | Sabtu | 07:3317:01 | 07:33<br>17:01 | 2 |
| 0011 | Suwardi | Welder | 2025-06-01 | Minggu | 07:4617:00 | 07:46<br>17:00 | 2 |
| 0011 | Suwardi | Welder | 2025-06-02 | Senin | 07:4416:00 | 07:44<br>16:00 | 2 |
| 0011 | Suwardi | Welder | 2025-06-03 | Selasa | 07:3316:38 | 07:33<br>16:38 | 2 |
| 0011 | Suwardi | Welder | 2025-06-04 | Rabu | 07:3318:04 | 07:33<br>18:04 | 2 |
| 0011 | Suwardi | Welder | 2025-06-05 | Kamis | 07:3217:05 | 07:32<br>17:05 | 2 |
| 0011 | Suwardi | Welder | 2025-06-09 | Senin | 07:3618:05 | 07:36<br>18:05 | 2 |
| 0011 | Suwardi | Welder | 2025-06-10 | Selasa | 07:5617:03 | 07:56<br>17:03 | 2 |
| 0011 | Suwardi | Welder | 2025-06-11 | Rabu | 07:3116:42 | 07:31<br>16:42 | 2 |
| 0011 | Suwardi | Welder | 2025-06-12 | Kamis | 07:3516:42 | 07:35<br>16:42 | 2 |
| 0011 | Suwardi | Welder | 2025-06-13 | Jumat | 07:3217:04 | 07:32<br>17:04 | 2 |
| 0011 | Suwardi | Welder | 2025-06-14 | Sabtu | 07:3017:20 | 07:30<br>17:20 | 2 |
| 0011 | Suwardi | Welder | 2025-06-15 | Minggu | 07:3517:24 | 07:35<br>17:24 | 2 |
| 0011 | Suwardi | Welder | 2025-06-16 | Senin | 07:3218:00 | 07:32<br>18:00 | 2 |
| 0011 | Suwardi | Welder | 2025-06-17 | Selasa | 07:3418:03 | 07:34<br>18:03 | 2 |
| 0011 | Suwardi | Welder | 2025-06-18 | Rabu | 07:3416:2516:25 | 07:34<br>16:25<br>16:25 | 3 |
| 0011 | Suwardi | Welder | 2025-06-19 | Kamis | 07:4618:32 | 07:46<br>18:32 | 2 |
| 0011 | Suwardi | Welder | 2025-06-20 | Jumat | 07:3317:06 | 07:33<br>17:06 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-05-26 | Senin | 17:33 | 17:33 | 1 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-05-27 | Selasa | 07:2017:06 | 07:20<br>17:06 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-05-28 | Rabu | 07:3818:01 | 07:38<br>18:01 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-05-30 | Jumat | 08:2117:10 | 08:21<br>17:10 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-05-31 | Sabtu | 07:2114:22 | 07:21<br>14:22 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-02 | Senin | 07:1918:42 | 07:19<br>18:42 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-03 | Selasa | 07:2718:35 | 07:27<br>18:35 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-04 | Rabu | 07:3718:23 | 07:37<br>18:23 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-05 | Kamis | 07:30 | 07:30 | 1 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-06 | Jumat | 10:28 | 10:28 | 1 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-09 | Senin | 07:1517:2317:23 | 07:15<br>17:23<br>17:23 | 3 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-10 | Selasa | 07:3218:19 | 07:32<br>18:19 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-11 | Rabu | 07:2918:35 | 07:29<br>18:35 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-12 | Kamis | 07:31 | 07:31 | 1 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-13 | Jumat | 07:17 | 07:17 | 1 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-14 | Sabtu | 07:1618:08 | 07:16<br>18:08 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-16 | Senin | 07:4317:54 | 07:43<br>17:54 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-17 | Selasa | 07:4316:0918:59 | 07:43<br>16:09<br>18:59 | 3 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-18 | Rabu | 07:3217:05 | 07:32<br>17:05 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-19 | Kamis | 07:3218:40 | 07:32<br>18:40 | 2 |
| 0012 | Joni Septian | Mekanik/ Greasing | 2025-06-20 | Jumat | 07:52 | 07:52 | 1 |
| 0013 | Martono | Warehouse | 2025-05-26 | Senin | 15:2918:08 | 15:29<br>18:08 | 2 |
| 0013 | Martono | Warehouse | 2025-05-27 | Selasa | 07:2418:03 | 07:24<br>18:03 | 2 |
| 0013 | Martono | Warehouse | 2025-05-28 | Rabu | 07:2618:06 | 07:26<br>18:06 | 2 |
| 0013 | Martono | Warehouse | 2025-05-29 | Kamis | 08:5716:11 | 08:57<br>16:11 | 2 |
| 0013 | Martono | Warehouse | 2025-05-30 | Jumat | 07:2717:21 | 07:27<br>17:21 | 2 |
| 0013 | Martono | Warehouse | 2025-05-31 | Sabtu | 07:5617:07 | 07:56<br>17:07 | 2 |
| 0013 | Martono | Warehouse | 2025-06-01 | Minggu | 08:2317:17 | 08:23<br>17:17 | 2 |
| 0013 | Martono | Warehouse | 2025-06-02 | Senin | 07:1117:37 | 07:11<br>17:37 | 2 |
| 0013 | Martono | Warehouse | 2025-06-03 | Selasa | 07:2317:40 | 07:23<br>17:40 | 2 |
| 0013 | Martono | Warehouse | 2025-06-04 | Rabu | 07:2218:03 | 07:22<br>18:03 | 2 |
| 0013 | Martono | Warehouse | 2025-06-05 | Kamis | 07:1717:31 | 07:17<br>17:31 | 2 |
| 0013 | Martono | Warehouse | 2025-06-07 | Sabtu | 08:3908:4116:00 | 08:39<br>08:41<br>16:00 | 3 |
| 0013 | Martono | Warehouse | 2025-06-08 | Minggu | 09:57 | 09:57 | 1 |
| 0013 | Martono | Warehouse | 2025-06-09 | Senin | 07:2918:02 | 07:29<br>18:02 | 2 |
| 0013 | Martono | Warehouse | 2025-06-10 | Selasa | 07:2617:1118:04 | 07:26<br>17:11<br>18:04 | 3 |
| 0013 | Martono | Warehouse | 2025-06-11 | Rabu | 07:2317:56 | 07:23<br>17:56 | 2 |
| 0013 | Martono | Warehouse | 2025-06-12 | Kamis | 07:2318:00 | 07:23<br>18:00 | 2 |
| 0013 | Martono | Warehouse | 2025-06-13 | Jumat | 07:1718:00 | 07:17<br>18:00 | 2 |
| 0013 | Martono | Warehouse | 2025-06-14 | Sabtu | 07:2518:05 | 07:25<br>18:05 | 2 |
| 0013 | Martono | Warehouse | 2025-06-15 | Minggu | 07:3817:12 | 07:38<br>17:12 | 2 |
| 0013 | Martono | Warehouse | 2025-06-16 | Senin | 07:3018:00 | 07:30<br>18:00 | 2 |
| 0013 | Martono | Warehouse | 2025-06-17 | Selasa | 07:3518:03 | 07:35<br>18:03 | 2 |
| 0013 | Martono | Warehouse | 2025-06-18 | Rabu | 07:1717:16 | 07:17<br>17:16 | 2 |
| 0013 | Martono | Warehouse | 2025-06-19 | Kamis | 07:2517:18 | 07:25<br>17:18 | 2 |
| 0013 | Martono | Warehouse | 2025-06-20 | Jumat | 07:25 | 07:25 | 1 |
| 0014 | Abdul Karim | Welder | 2025-05-26 | Senin | 16:4217:06 | 16:42<br>17:06 | 2 |
| 0014 | Abdul Karim | Welder | 2025-05-27 | Selasa | 07:2916:11 | 07:29<br>16:11 | 2 |
| 0014 | Abdul Karim | Welder | 2025-05-28 | Rabu | 07:1916:21 | 07:19<br>16:21 | 2 |
| 0014 | Abdul Karim | Welder | 2025-05-30 | Jumat | 07:2717:02 | 07:27<br>17:02 | 2 |
| 0014 | Abdul Karim | Welder | 2025-05-31 | Sabtu | 08:3412:00 | 08:34<br>12:00 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-02 | Senin | 07:2716:04 | 07:27<br>16:04 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-03 | Selasa | 07:3416:4216:53 | 07:34<br>16:42<br>16:53 | 3 |
| 0014 | Abdul Karim | Welder | 2025-06-13 | Jumat | 07:2517:05 | 07:25<br>17:05 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-14 | Sabtu | 07:21 | 07:21 | 1 |
| 0014 | Abdul Karim | Welder | 2025-06-16 | Senin | 07:3216:54 | 07:32<br>16:54 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-17 | Selasa | 08:0716:04 | 08:07<br>16:04 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-18 | Rabu | 07:3016:05 | 07:30<br>16:05 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-19 | Kamis | 07:3216:05 | 07:32<br>16:05 | 2 |
| 0014 | Abdul Karim | Welder | 2025-06-20 | Jumat | 07:49 | 07:49 | 1 |
| 0015 | Heri Suhedi | Helper | 2025-05-27 | Selasa | 07:5417:3117:31 | 07:54<br>17:31<br>17:31 | 3 |
| 0015 | Heri Suhedi | Helper | 2025-05-28 | Rabu | 07:3807:3818:0518:05 | 07:38<br>07:38<br>18:05<br>18:05 | 4 |
| 0015 | Heri Suhedi | Helper | 2025-05-30 | Jumat | 07:4107:4117:0817:08 | 07:41<br>07:41<br>17:08<br>17:08 | 4 |
| 0015 | Heri Suhedi | Helper | 2025-05-31 | Sabtu | 08:3408:3512:02 | 08:34<br>08:35<br>12:02 | 3 |
| 0015 | Heri Suhedi | Helper | 2025-06-02 | Senin | 07:3318:4218:42 | 07:33<br>18:42<br>18:42 | 3 |
| 0015 | Heri Suhedi | Helper | 2025-06-03 | Selasa | 07:4207:4218:3618:36 | 07:42<br>07:42<br>18:36<br>18:36 | 4 |
| 0015 | Heri Suhedi | Helper | 2025-06-04 | Rabu | 07:4518:2518:25 | 07:45<br>18:25<br>18:25 | 3 |
| 0015 | Heri Suhedi | Helper | 2025-06-05 | Kamis | 07:5516:4516:45 | 07:55<br>16:45<br>16:45 | 3 |
| 0015 | Heri Suhedi | Helper | 2025-06-09 | Senin | 07:4017:3217:32 | 07:40<br>17:32<br>17:32 | 3 |
| 0015 | Heri Suhedi | Helper | 2025-06-16 | Senin | 07:3316:54 | 07:33<br>16:54 | 2 |
| 0015 | Heri Suhedi | Helper | 2025-06-17 | Selasa | 08:0716:04 | 08:07<br>16:04 | 2 |
| 0015 | Heri Suhedi | Helper | 2025-06-18 | Rabu | 07:5517:04 | 07:55<br>17:04 | 2 |
| 0015 | Heri Suhedi | Helper | 2025-06-19 | Kamis | 07:3216:20 | 07:32<br>16:20 | 2 |
| 0015 | Heri Suhedi | Helper | 2025-06-20 | Jumat | 07:49 | 07:49 | 1 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-05-26 | Senin | 19:0720:28 | 19:07<br>20:28 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-05-27 | Selasa | 07:3116:12 | 07:31<br>16:12 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-05-28 | Rabu | 07:2418:01 | 07:24<br>18:01 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-05-29 | Kamis | 07:5716:01 | 07:57<br>16:01 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-05-30 | Jumat | 07:3217:06 | 07:32<br>17:06 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-05-31 | Sabtu | 07:5217:00 | 07:52<br>17:00 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-02 | Senin | 07:4216:12 | 07:42<br>16:12 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-03 | Selasa | 07:3316:42 | 07:33<br>16:42 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-04 | Rabu | 07:3818:04 | 07:38<br>18:04 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-10 | Selasa | 17:00 | 17:00 | 1 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-11 | Rabu | 07:3216:4416:44 | 07:32<br>16:44<br>16:44 | 3 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-12 | Kamis | 07:2516:42 | 07:25<br>16:42 | 2 |
| 0016 | Fadli | Op Exca / Ass mekanik | 2025-06-14 | Sabtu | 08:18 | 08:18 | 1 |
| 0017 | Hendrik | Teknisi Listrik | 2025-05-26 | Senin | 16:3720:30 | 16:37<br>20:30 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-05-27 | Selasa | 07:3216:12 | 07:32<br>16:12 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-05-28 | Rabu | 07:2407:2518:03 | 07:24<br>07:25<br>18:03 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-05-29 | Kamis | 07:5616:0116:01 | 07:56<br>16:01<br>16:01 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-05-30 | Jumat | 07:3217:09 | 07:32<br>17:09 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-05-31 | Sabtu | 07:3607:3617:04 | 07:36<br>07:36<br>17:04 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-01 | Minggu | 07:5717:01 | 07:57<br>17:01 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-02 | Senin | 07:3316:1016:10 | 07:33<br>16:10<br>16:10 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-03 | Selasa | 07:3316:52 | 07:33<br>16:52 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-04 | Rabu | 07:4018:0418:04 | 07:40<br>18:04<br>18:04 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-05 | Kamis | 07:2817:0517:05 | 07:28<br>17:05<br>17:05 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-09 | Senin | 07:2307:2307:2318:0518:0518:05 | 07:23<br>07:23<br>07:23<br>18:05<br>18:05<br>18:05 | 6 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-10 | Selasa | 07:3217:0017:00 | 07:32<br>17:00<br>17:00 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-11 | Rabu | 07:3416:44 | 07:34<br>16:44 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-12 | Kamis | 07:2516:4216:4216:42 | 07:25<br>16:42<br>16:42<br>16:42 | 4 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-13 | Jumat | 07:3007:3007:3017:0517:05 | 07:30<br>07:30<br>07:30<br>17:05<br>17:05 | 5 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-14 | Sabtu | 07:2517:2117:21 | 07:25<br>17:21<br>17:21 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-15 | Minggu | 08:1217:2417:24 | 08:12<br>17:24<br>17:24 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-16 | Senin | 07:4818:00 | 07:48<br>18:00 | 2 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-17 | Selasa | 07:3118:0318:0318:03 | 07:31<br>18:03<br>18:03<br>18:03 | 4 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-18 | Rabu | 07:2416:5016:5017:04 | 07:24<br>16:50<br>16:50<br>17:04 | 4 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-19 | Kamis | 07:2518:3218:32 | 07:25<br>18:32<br>18:32 | 3 |
| 0017 | Hendrik | Teknisi Listrik | 2025-06-20 | Jumat | 07:2807:2817:06 | 07:28<br>07:28<br>17:06 | 3 |
| 0018 | Koeswanto | SMK3L | 2025-05-26 | Senin | 17:08 | 17:08 | 1 |
| 0018 | Koeswanto | SMK3L | 2025-05-27 | Selasa | 07:2617:01 | 07:26<br>17:01 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-05-28 | Rabu | 07:1817:30 | 07:18<br>17:30 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-05-30 | Jumat | 10:3517:03 | 10:35<br>17:03 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-05-31 | Sabtu | 07:3117:01 | 07:31<br>17:01 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-01 | Minggu | 07:4717:01 | 07:47<br>17:01 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-02 | Senin | 07:1717:00 | 07:17<br>17:00 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-03 | Selasa | 07:2617:02 | 07:26<br>17:02 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-04 | Rabu | 07:1417:06 | 07:14<br>17:06 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-05 | Kamis | 07:3017:27 | 07:30<br>17:27 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-07 | Sabtu | 07:4515:20 | 07:45<br>15:20 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-09 | Senin | 07:2817:04 | 07:28<br>17:04 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-10 | Selasa | 07:1517:03 | 07:15<br>17:03 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-11 | Rabu | 07:2217:00 | 07:22<br>17:00 | 2 |
| 0018 | Koeswanto | SMK3L | 2025-06-12 | Kamis | 07:1417:01 | 07:14<br>17:01 | 2 |
| 0019 | Amirudin | SMK3L | 2025-05-28 | Rabu | 08:1017:06 | 08:10<br>17:06 | 2 |
| 0019 | Amirudin | SMK3L | 2025-05-30 | Jumat | 10:3417:02 | 10:34<br>17:02 | 2 |
| 0019 | Amirudin | SMK3L | 2025-05-31 | Sabtu | 08:0217:00 | 08:02<br>17:00 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-02 | Senin | 07:3517:00 | 07:35<br>17:00 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-03 | Selasa | 07:3017:02 | 07:30<br>17:02 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-04 | Rabu | 07:2717:06 | 07:27<br>17:06 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-05 | Kamis | 07:2417:07 | 07:24<br>17:07 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-07 | Sabtu | 07:4015:20 | 07:40<br>15:20 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-09 | Senin | 07:3717:03 | 07:37<br>17:03 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-10 | Selasa | 07:2717:03 | 07:27<br>17:03 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-11 | Rabu | 07:3417:00 | 07:34<br>17:00 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-12 | Kamis | 07:2917:01 | 07:29<br>17:01 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-13 | Jumat | 07:2717:00 | 07:27<br>17:00 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-14 | Sabtu | 07:3217:30 | 07:32<br>17:30 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-16 | Senin | 07:3717:01 | 07:37<br>17:01 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-17 | Selasa | 07:4217:02 | 07:42<br>17:02 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-18 | Rabu | 07:2717:00 | 07:27<br>17:00 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-19 | Kamis | 07:3416:08 | 07:34<br>16:08 | 2 |
| 0019 | Amirudin | SMK3L | 2025-06-20 | Jumat | 07:3317:00 | 07:33<br>17:00 | 2 |
| 0020 | Darmawan | Mekanik | 2025-05-27 | Selasa | 17:38 | 17:38 | 1 |
| 0020 | Darmawan | Mekanik | 2025-05-28 | Rabu | 07:1619:01 | 07:16<br>19:01 | 2 |
| 0020 | Darmawan | Mekanik | 2025-05-30 | Jumat | 07:3517:01 | 07:35<br>17:01 | 2 |
| 0020 | Darmawan | Mekanik | 2025-05-31 | Sabtu | 08:1317:16 | 08:13<br>17:16 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-01 | Minggu | 07:4317:01 | 07:43<br>17:01 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-02 | Senin | 07:3419:01 | 07:34<br>19:01 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-03 | Selasa | 07:4017:28 | 07:40<br>17:28 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-04 | Rabu | 07:3317:09 | 07:33<br>17:09 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-05 | Kamis | 07:3917:31 | 07:39<br>17:31 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-06 | Jumat | 13:38 | 13:38 | 1 |
| 0020 | Darmawan | Mekanik | 2025-06-09 | Senin | 07:4117:17 | 07:41<br>17:17 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-10 | Selasa | 07:3217:01 | 07:32<br>17:01 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-11 | Rabu | 07:3320:10 | 07:33<br>20:10 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-12 | Kamis | 07:3417:04 | 07:34<br>17:04 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-13 | Jumat | 07:3017:26 | 07:30<br>17:26 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-14 | Sabtu | 07:3617:20 | 07:36<br>17:20 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-16 | Senin | 07:3917:27 | 07:39<br>17:27 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-17 | Selasa | 07:4217:10 | 07:42<br>17:10 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-18 | Rabu | 07:3516:3116:32 | 07:35<br>16:31<br>16:32 | 3 |
| 0020 | Darmawan | Mekanik | 2025-06-19 | Kamis | 07:4016:45 | 07:40<br>16:45 | 2 |
| 0020 | Darmawan | Mekanik | 2025-06-20 | Jumat | 07:3317:04 | 07:33<br>17:04 | 2 |
| 0021 | Akirullah | Mekanik | 2025-05-26 | Senin | 16:17 | 16:17 | 1 |
| 0021 | Akirullah | Mekanik | 2025-05-27 | Selasa | 07:3218:18 | 07:32<br>18:18 | 2 |
| 0021 | Akirullah | Mekanik | 2025-05-28 | Rabu | 07:3616:03 | 07:36<br>16:03 | 2 |
| 0021 | Akirullah | Mekanik | 2025-05-30 | Jumat | 07:3417:00 | 07:34<br>17:00 | 2 |
| 0021 | Akirullah | Mekanik | 2025-05-31 | Sabtu | 07:5414:26 | 07:54<br>14:26 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-02 | Senin | 07:1816:12 | 07:18<br>16:12 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-03 | Selasa | 07:4916:49 | 07:49<br>16:49 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-04 | Rabu | 07:0117:12 | 07:01<br>17:12 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-05 | Kamis | 07:4016:20 | 07:40<br>16:20 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-09 | Senin | 07:3317:12 | 07:33<br>17:12 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-10 | Selasa | 07:3017:01 | 07:30<br>17:01 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-11 | Rabu | 07:1416:29 | 07:14<br>16:29 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-12 | Kamis | 07:4617:40 | 07:46<br>17:40 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-14 | Sabtu | 07:1915:56 | 07:19<br>15:56 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-19 | Kamis | 07:3116:13 | 07:31<br>16:13 | 2 |
| 0021 | Akirullah | Mekanik | 2025-06-20 | Jumat | 07:2116:36 | 07:21<br>16:36 | 2 |
| 0022 | Azril M | Mekanik A2B | 2025-06-02 | Senin | 18:12 | 18:12 | 1 |
| 0022 | Azril M | Mekanik A2B | 2025-06-03 | Selasa | 07:21 | 07:21 | 1 |
| 0022 | Azril M | Mekanik A2B | 2025-06-04 | Rabu | 07:3517:26 | 07:35<br>17:26 | 2 |
| 0022 | Azril M | Mekanik A2B | 2025-06-05 | Kamis | 07:26 | 07:26 | 1 |
| 0022 | Azril M | Mekanik A2B | 2025-06-09 | Senin | 17:50 | 17:50 | 1 |
| 0022 | Azril M | Mekanik A2B | 2025-06-10 | Selasa | 07:3117:12 | 07:31<br>17:12 | 2 |
| 0022 | Azril M | Mekanik A2B | 2025-06-11 | Rabu | 07:2818:39 | 07:28<br>18:39 | 2 |
| 0022 | Azril M | Mekanik A2B | 2025-06-12 | Kamis | 07:2316:44 | 07:23<br>16:44 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-05-28 | Rabu | 07:3319:55 | 07:33<br>19:55 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-05-29 | Kamis | 07:0918:16 | 07:09<br>18:16 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-05-30 | Jumat | 07:0717:00 | 07:07<br>17:00 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-05-31 | Sabtu | 07:1717:17 | 07:17<br>17:17 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-01 | Minggu | 07:2317:01 | 07:23<br>17:01 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-02 | Senin | 06:5518:12 | 06:55<br>18:12 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-03 | Selasa | 07:4117:38 | 07:41<br>17:38 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-04 | Rabu | 07:15 | 07:15 | 1 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-10 | Selasa | 08:0417:07 | 08:04<br>17:07 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-11 | Rabu | 07:2320:10 | 07:23<br>20:10 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-12 | Kamis | 06:5817:04 | 06:58<br>17:04 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-13 | Jumat | 07:2017:18 | 07:20<br>17:18 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-14 | Sabtu | 07:2017:15 | 07:20<br>17:15 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-16 | Senin | 07:3917:27 | 07:39<br>17:27 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-17 | Selasa | 07:1917:03 | 07:19<br>17:03 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-18 | Rabu | 06:3716:37 | 06:37<br>16:37 | 2 |
| 0023 | Efendi Harahap | Mekanik A2B | 2025-06-19 | Kamis | 07:3816:08 | 07:38<br>16:08 | 2 |
| 0024 | David Aritonang | Flagman | 2025-05-26 | Senin | 16:5017:07 | 16:50<br>17:07 | 2 |
| 0024 | David Aritonang | Flagman | 2025-05-27 | Selasa | 07:2817:01 | 07:28<br>17:01 | 2 |
| 0024 | David Aritonang | Flagman | 2025-05-28 | Rabu | 07:3117:30 | 07:31<br>17:30 | 2 |
| 0024 | David Aritonang | Flagman | 2025-05-30 | Jumat | 07:3017:11 | 07:30<br>17:11 | 2 |
| 0024 | David Aritonang | Flagman | 2025-05-31 | Sabtu | 07:2117:00 | 07:21<br>17:00 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-02 | Senin | 07:0717:02 | 07:07<br>17:02 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-03 | Selasa | 07:3917:06 | 07:39<br>17:06 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-04 | Rabu | 07:4717:06 | 07:47<br>17:06 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-05 | Kamis | 07:3017:02 | 07:30<br>17:02 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-07 | Sabtu | 07:4815:19 | 07:48<br>15:19 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-09 | Senin | 07:3317:02 | 07:33<br>17:02 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-10 | Selasa | 07:4917:03 | 07:49<br>17:03 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-11 | Rabu | 07:4017:07 | 07:40<br>17:07 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-12 | Kamis | 07:2017:02 | 07:20<br>17:02 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-13 | Jumat | 07:4617:00 | 07:46<br>17:00 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-14 | Sabtu | 07:5217:07 | 07:52<br>17:07 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-16 | Senin | 07:4717:01 | 07:47<br>17:01 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-17 | Selasa | 08:0417:06 | 08:04<br>17:06 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-18 | Rabu | 07:5017:01 | 07:50<br>17:01 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-19 | Kamis | 07:4517:01 | 07:45<br>17:01 | 2 |
| 0024 | David Aritonang | Flagman | 2025-06-20 | Jumat | 07:4017:00 | 07:40<br>17:00 | 2 |
| 0025 | Fajar | Ass admin | 2025-05-26 | Senin | 15:2117:17 | 15:21<br>17:17 | 2 |
| 0025 | Fajar | Ass admin | 2025-05-27 | Selasa | 07:24 | 07:24 | 1 |
| 0025 | Fajar | Ass admin | 2025-05-28 | Rabu | 07:3117:18 | 07:31<br>17:18 | 2 |
| 0025 | Fajar | Ass admin | 2025-05-30 | Jumat | 07:2917:06 | 07:29<br>17:06 | 2 |
| 0025 | Fajar | Ass admin | 2025-05-31 | Sabtu | 07:4814:04 | 07:48<br>14:04 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-02 | Senin | 07:2716:42 | 07:27<br>16:42 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-03 | Selasa | 07:3216:50 | 07:32<br>16:50 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-04 | Rabu | 07:3217:16 | 07:32<br>17:16 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-05 | Kamis | 07:4016:44 | 07:40<br>16:44 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-09 | Senin | 07:3917:02 | 07:39<br>17:02 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-10 | Selasa | 07:2917:28 | 07:29<br>17:28 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-11 | Rabu | 07:2517:38 | 07:25<br>17:38 | 2 |
| 0025 | Fajar | Ass admin | 2025-06-12 | Kamis | 07:3217:05 | 07:32<br>17:05 | 2 |
| 0026 | Alfiqir | Helper | 2025-06-03 | Selasa | 09:0309:0316:52 | 09:03<br>09:03<br>16:52 | 3 |
| 0026 | Alfiqir | Helper | 2025-06-04 | Rabu | 08:4718:11 | 08:47<br>18:11 | 2 |
| 0026 | Alfiqir | Helper | 2025-06-11 | Rabu | 16:42 | 16:42 | 1 |
| 0026 | Alfiqir | Helper | 2025-06-12 | Kamis | 07:4907:5116:38 | 07:49<br>07:51<br>16:38 | 3 |
| 0026 | Alfiqir | Helper | 2025-06-13 | Jumat | 08:0008:0016:24 | 08:00<br>08:00<br>16:24 | 3 |
| 0026 | Alfiqir | Helper | 2025-06-17 | Selasa | 08:5020:50 | 08:50<br>20:50 | 2 |
| 0026 | Alfiqir | Helper | 2025-06-18 | Rabu | 07:5516:5016:5016:50 | 07:55<br>16:50<br>16:50<br>16:50 | 4 |
| 0026 | Alfiqir | Helper | 2025-06-20 | Jumat | 08:3817:06 | 08:38<br>17:06 | 2 |
| 0027 | Jorlan | Security | 2025-05-26 | Senin | 18:53 | 18:53 | 1 |
| 0027 | Jorlan | Security | 2025-05-27 | Selasa | 07:5318:5518:56 | 07:53<br>18:55<br>18:56 | 3 |
| 0027 | Jorlan | Security | 2025-05-28 | Rabu | 07:0607:0618:40 | 07:06<br>07:06<br>18:40 | 3 |
| 0027 | Jorlan | Security | 2025-05-29 | Kamis | 07:2318:54 | 07:23<br>18:54 | 2 |
| 0027 | Jorlan | Security | 2025-05-30 | Jumat | 07:0418:57 | 07:04<br>18:57 | 2 |
| 0027 | Jorlan | Security | 2025-05-31 | Sabtu | 07:0907:1718:57 | 07:09<br>07:17<br>18:57 | 3 |
| 0027 | Jorlan | Security | 2025-06-01 | Minggu | 07:0618:57 | 07:06<br>18:57 | 2 |
| 0027 | Jorlan | Security | 2025-06-02 | Senin | 07:06 | 07:06 | 1 |
| 0027 | Jorlan | Security | 2025-06-09 | Senin | 06:5019:08 | 06:50<br>19:08 | 2 |
| 0027 | Jorlan | Security | 2025-06-10 | Selasa | 06:5219:06 | 06:52<br>19:06 | 2 |
| 0027 | Jorlan | Security | 2025-06-11 | Rabu | 06:5719:07 | 06:57<br>19:07 | 2 |
| 0027 | Jorlan | Security | 2025-06-12 | Kamis | 06:5719:07 | 06:57<br>19:07 | 2 |
| 0027 | Jorlan | Security | 2025-06-13 | Jumat | 06:4319:08 | 06:43<br>19:08 | 2 |
| 0027 | Jorlan | Security | 2025-06-14 | Sabtu | 06:5919:07 | 06:59<br>19:07 | 2 |
| 0027 | Jorlan | Security | 2025-06-15 | Minggu | 06:5919:03 | 06:59<br>19:03 | 2 |
| 0027 | Jorlan | Security | 2025-06-16 | Senin | 18:58 | 18:58 | 1 |
| 0027 | Jorlan | Security | 2025-06-17 | Selasa | 07:1619:02 | 07:16<br>19:02 | 2 |
| 0027 | Jorlan | Security | 2025-06-18 | Rabu | 07:0418:50 | 07:04<br>18:50 | 2 |
| 0027 | Jorlan | Security | 2025-06-19 | Kamis | 07:0618:48 | 07:06<br>18:48 | 2 |
| 0027 | Jorlan | Security | 2025-06-20 | Jumat | 07:09 | 07:09 | 1 |
| 0028 | Sudirman H | Security | 2025-05-26 | Senin | 16:2219:02 | 16:22<br>19:02 | 2 |
| 0028 | Sudirman H | Security | 2025-05-27 | Selasa | 07:2819:01 | 07:28<br>19:01 | 2 |
| 0028 | Sudirman H | Security | 2025-05-28 | Rabu | 06:5919:00 | 06:59<br>19:00 | 2 |
| 0028 | Sudirman H | Security | 2025-05-29 | Kamis | 07:0419:02 | 07:04<br>19:02 | 2 |
| 0028 | Sudirman H | Security | 2025-05-30 | Jumat | 07:0419:00 | 07:04<br>19:00 | 2 |
| 0028 | Sudirman H | Security | 2025-05-31 | Sabtu | 07:0518:59 | 07:05<br>18:59 | 2 |
| 0028 | Sudirman H | Security | 2025-06-01 | Minggu | 07:0118:5218:52 | 07:01<br>18:52<br>18:52 | 3 |
| 0028 | Sudirman H | Security | 2025-06-02 | Senin | 19:02 | 19:02 | 1 |
| 0028 | Sudirman H | Security | 2025-06-03 | Selasa | 07:0518:49 | 07:05<br>18:49 | 2 |
| 0028 | Sudirman H | Security | 2025-06-04 | Rabu | 07:0419:04 | 07:04<br>19:04 | 2 |
| 0028 | Sudirman H | Security | 2025-06-05 | Kamis | 07:0519:00 | 07:05<br>19:00 | 2 |
| 0028 | Sudirman H | Security | 2025-06-06 | Jumat | 07:1207:1207:1218:5718:5718:57 | 07:12<br>07:12<br>07:12<br>18:57<br>18:57<br>18:57 | 6 |
| 0028 | Sudirman H | Security | 2025-06-07 | Sabtu | 07:0507:0507:0519:0219:02 | 07:05<br>07:05<br>07:05<br>19:02<br>19:02 | 5 |
| 0028 | Sudirman H | Security | 2025-06-08 | Minggu | 07:0307:0318:5818:5818:58 | 07:03<br>07:03<br>18:58<br>18:58<br>18:58 | 5 |
| 0028 | Sudirman H | Security | 2025-06-09 | Senin | 07:0507:05 | 07:05<br>07:05 | 2 |
| 0028 | Sudirman H | Security | 2025-06-16 | Senin | 06:5706:5706:5819:0219:02 | 06:57<br>06:57<br>06:58<br>19:02<br>19:02 | 5 |
| 0028 | Sudirman H | Security | 2025-06-17 | Selasa | 07:0207:0207:0219:0119:0119:01 | 07:02<br>07:02<br>07:02<br>19:01<br>19:01<br>19:01 | 6 |
| 0028 | Sudirman H | Security | 2025-06-18 | Rabu | 06:5806:5806:5819:0419:0419:04 | 06:58<br>06:58<br>06:58<br>19:04<br>19:04<br>19:04 | 6 |
| 0028 | Sudirman H | Security | 2025-06-19 | Kamis | 07:0107:0107:0119:0119:0119:01 | 07:01<br>07:01<br>07:01<br>19:01<br>19:01<br>19:01 | 6 |
| 0028 | Sudirman H | Security | 2025-06-20 | Jumat | 06:5906:5906:59 | 06:59<br>06:59<br>06:59 | 3 |
| 0029 | Edy Jaka S | Security | 2025-06-02 | Senin | 07:2119:05 | 07:21<br>19:05 | 2 |
| 0029 | Edy Jaka S | Security | 2025-06-03 | Selasa | 07:0207:0207:0219:0419:0419:04 | 07:02<br>07:02<br>07:02<br>19:04<br>19:04<br>19:04 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-04 | Rabu | 07:0007:0007:0119:0419:0419:04 | 07:00<br>07:00<br>07:01<br>19:04<br>19:04<br>19:04 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-05 | Kamis | 06:5006:5006:5019:0219:0219:02 | 06:50<br>06:50<br>06:50<br>19:02<br>19:02<br>19:02 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-06 | Jumat | 06:5006:5006:5019:1019:1019:10 | 06:50<br>06:50<br>06:50<br>19:10<br>19:10<br>19:10 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-07 | Sabtu | 07:0207:0207:0219:0419:0419:04 | 07:02<br>07:02<br>07:02<br>19:04<br>19:04<br>19:04 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-08 | Minggu | 06:5906:5906:5919:0419:0419:04 | 06:59<br>06:59<br>06:59<br>19:04<br>19:04<br>19:04 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-09 | Senin | 19:0219:0219:02 | 19:02<br>19:02<br>19:02 | 3 |
| 0029 | Edy Jaka S | Security | 2025-06-10 | Selasa | 07:0307:0307:0319:0019:0019:00 | 07:03<br>07:03<br>07:03<br>19:00<br>19:00<br>19:00 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-11 | Rabu | 07:0307:0307:0318:5918:5918:59 | 07:03<br>07:03<br>07:03<br>18:59<br>18:59<br>18:59 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-12 | Kamis | 07:0307:0307:0318:5918:5918:59 | 07:03<br>07:03<br>07:03<br>18:59<br>18:59<br>18:59 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-13 | Jumat | 07:0207:0207:0218:5818:5818:58 | 07:02<br>07:02<br>07:02<br>18:58<br>18:58<br>18:58 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-14 | Sabtu | 07:0407:0407:0418:5718:5718:57 | 07:04<br>07:04<br>07:04<br>18:57<br>18:57<br>18:57 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-15 | Minggu | 07:0707:0707:0718:2618:2618:26 | 07:07<br>07:07<br>07:07<br>18:26<br>18:26<br>18:26 | 6 |
| 0029 | Edy Jaka S | Security | 2025-06-16 | Senin | 07:0707:0707:07 | 07:07<br>07:07<br>07:07 | 3 |
| 0030 | Taufiq H | Security | 2025-05-26 | Senin | 19:00 | 19:00 | 1 |
| 0030 | Taufiq H | Security | 2025-05-27 | Selasa | 09:2618:5918:59 | 09:26<br>18:59<br>18:59 | 3 |
| 0030 | Taufiq H | Security | 2025-05-28 | Rabu | 07:0907:0918:5618:56 | 07:09<br>07:09<br>18:56<br>18:56 | 4 |
| 0030 | Taufiq H | Security | 2025-05-29 | Kamis | 07:0607:0618:5918:59 | 07:06<br>07:06<br>18:59<br>18:59 | 4 |
| 0030 | Taufiq H | Security | 2025-05-30 | Jumat | 07:0507:0518:5918:59 | 07:05<br>07:05<br>18:59<br>18:59 | 4 |
| 0030 | Taufiq H | Security | 2025-05-31 | Sabtu | 07:0107:0118:5518:55 | 07:01<br>07:01<br>18:55<br>18:55 | 4 |
| 0030 | Taufiq H | Security | 2025-06-01 | Minggu | 07:0207:0318:5218:52 | 07:02<br>07:03<br>18:52<br>18:52 | 4 |
| 0030 | Taufiq H | Security | 2025-06-02 | Senin | 07:0307:0318:5518:55 | 07:03<br>07:03<br>18:55<br>18:55 | 4 |
| 0030 | Taufiq H | Security | 2025-06-03 | Selasa | 07:0507:0519:0019:00 | 07:05<br>07:05<br>19:00<br>19:00 | 4 |
| 0030 | Taufiq H | Security | 2025-06-04 | Rabu | 07:0307:0318:5818:58 | 07:03<br>07:03<br>18:58<br>18:58 | 4 |
| 0030 | Taufiq H | Security | 2025-06-05 | Kamis | 07:0407:0407:0418:5618:56 | 07:04<br>07:04<br>07:04<br>18:56<br>18:56 | 5 |
| 0030 | Taufiq H | Security | 2025-06-06 | Jumat | 09:0309:0318:5118:51 | 09:03<br>09:03<br>18:51<br>18:51 | 4 |
| 0030 | Taufiq H | Security | 2025-06-07 | Sabtu | 07:0407:0418:5518:55 | 07:04<br>07:04<br>18:55<br>18:55 | 4 |
| 0030 | Taufiq H | Security | 2025-06-08 | Minggu | 07:0307:0318:5318:53 | 07:03<br>07:03<br>18:53<br>18:53 | 4 |
| 0030 | Taufiq H | Security | 2025-06-09 | Senin | 07:0407:0419:0019:00 | 07:04<br>07:04<br>19:00<br>19:00 | 4 |
| 0030 | Taufiq H | Security | 2025-06-10 | Selasa | 07:0307:0307:0318:5618:56 | 07:03<br>07:03<br>07:03<br>18:56<br>18:56 | 5 |
| 0030 | Taufiq H | Security | 2025-06-11 | Rabu | 07:0307:0318:5818:58 | 07:03<br>07:03<br>18:58<br>18:58 | 4 |
| 0030 | Taufiq H | Security | 2025-06-12 | Kamis | 07:0307:0318:5818:58 | 07:03<br>07:03<br>18:58<br>18:58 | 4 |
| 0030 | Taufiq H | Security | 2025-06-13 | Jumat | 07:0107:0118:5718:57 | 07:01<br>07:01<br>18:57<br>18:57 | 4 |
| 0030 | Taufiq H | Security | 2025-06-14 | Sabtu | 07:0207:0218:5618:56 | 07:02<br>07:02<br>18:56<br>18:56 | 4 |
| 0030 | Taufiq H | Security | 2025-06-15 | Minggu | 07:0207:0218:5718:5718:57 | 07:02<br>07:02<br>18:57<br>18:57<br>18:57 | 5 |
| 0030 | Taufiq H | Security | 2025-06-16 | Senin | 07:0607:0618:5518:55 | 07:06<br>07:06<br>18:55<br>18:55 | 4 |
| 0030 | Taufiq H | Security | 2025-06-17 | Selasa | 07:3007:3018:5618:56 | 07:30<br>07:30<br>18:56<br>18:56 | 4 |
| 0030 | Taufiq H | Security | 2025-06-18 | Rabu | 07:0307:0318:5518:55 | 07:03<br>07:03<br>18:55<br>18:55 | 4 |
| 0030 | Taufiq H | Security | 2025-06-19 | Kamis | 07:0307:0318:5818:58 | 07:03<br>07:03<br>18:58<br>18:58 | 4 |
| 0030 | Taufiq H | Security | 2025-06-20 | Jumat | 07:1007:10 | 07:10<br>07:10 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-05-28 | Rabu | 08:1319:02 | 08:13<br>19:02 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-05-29 | Kamis | 08:2516:03 | 08:25<br>16:03 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-05-30 | Jumat | 08:1417:08 | 08:14<br>17:08 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-05-31 | Sabtu | 08:2417:21 | 08:24<br>17:21 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-01 | Minggu | 07:5518:00 | 07:55<br>18:00 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-02 | Senin | 08:0019:00 | 08:00<br>19:00 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-03 | Selasa | 08:0716:33 | 08:07<br>16:33 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-04 | Rabu | 07:5817:05 | 07:58<br>17:05 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-05 | Kamis | 08:0016:05 | 08:00<br>16:05 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-10 | Selasa | 08:0316:34 | 08:03<br>16:34 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-12 | Kamis | 07:5716:35 | 07:57<br>16:35 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-13 | Jumat | 07:2917:26 | 07:29<br>17:26 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-14 | Sabtu | 08:2020:29 | 08:20<br>20:29 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-16 | Senin | 07:4016:28 | 07:40<br>16:28 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-18 | Rabu | 07:3416:33 | 07:34<br>16:33 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-19 | Kamis | 07:4116:09 | 07:41<br>16:09 | 2 |
| 0031 | Adi Sarnata | Mekanik A2B | 2025-06-20 | Jumat | 07:3317:07 | 07:33<br>17:07 | 2 |

## 10. Catatan interpretasi

1. Jam lembur pada dokumen ini adalah angka yang tercatat atau hasil formula sederhana di workbook; dokumen tidak memuat tarif lembur, pengali hari kerja/libur, pembulatan payroll, maupun nilai rupiah.
2. Sel status kosong tidak diubah menjadi `MK`, `O`, atau kode lain. Konversi mempertahankan ketidaklengkapan agar tidak menambahkan asumsi.
3. Kolom C pada sheet rekap tersembunyi dan tidak memiliki header yang terlihat. Nilainya dipertahankan sebagai `Kode C`, tetapi tidak ditafsirkan sebagai ID karyawan karena beberapa nilai berulang dan tidak cocok langsung dengan ID pada Sheet2.
4. Nama pada Sheet2 dan rekap kadang berbeda penulisan, misalnya huruf besar/kecil, singkatan, atau nama lengkap. Konversi tidak menyatukan identitas secara otomatis.
5. Untuk keperluan payroll, angka hasil audit sebaiknya diverifikasi oleh HR/Payroll dan atasan terkait sebelum digunakan sebagai dasar pembayaran.
