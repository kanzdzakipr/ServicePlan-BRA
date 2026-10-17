# Pengujian Batch 5 — WO/JO dan KPI

Status otomatis: sintaks PHP/JS, 22 tes KPI dan 11 tes tiket WO lulus. Uji browser bersama pengguna masih diperlukan. Semua uji dilakukan pada Laragon2 lokal; jangan impor ulang database atau memakai data milik pengguna lain.

## 1. Work Order normal

1. Login ke `http://serviceplan-bra.test` lalu buka Work Order. Catat ID WO baru dan status awal aset.
2. Buat WO normal, isi seluruh 12 field tiket, dua PIC berbeda, dan unggah foto JPEG/PNG/WebP di bawah 5 MB.
3. Buka Detail WO. Pastikan nilai, foto, kedua PIC dan event pembuatan/upload ada; aset downtime menjadi `BREAKDOWN`.
4. Jalankan transisi status hingga `Closed`. Periksa waktu, tindakan dan timeline; status aset kembali sesuai aturan WO aktif lain/safety hold.
5. Di HeidiSQL, cek `work_orders`, `work_order_timeline`, `assets` dan file `uploads/work_orders/` berdasarkan ID WO tersebut. Jangan mengubah baris langsung.

## 2. Operasi bersyarat

1. Buat WO lain pada unit uji dengan opsi operasi bersyarat/RFU Bersyarat dan mitigasi terisi.
2. Pastikan mitigasi wajib, `is_forced_operation=1`, dan status aset tetap `OPERATING` bila tidak ada safety hold.
3. Tutup WO dan cek timeline. Uji tanpa mitigasi harus ditolak.

## 3. Cetak satu record sebagai WO dan JO

1. Pada Detail WO, pilih `Work Order (WO)` dan klik `Cetak / PDF`. Catat **ID database (tetap)**.
2. Kembali ke detail yang sama, pilih `Job Order (JO)` lalu cetak. Judul berubah, tetapi ID, unit, field, foto, dan isi tetap sama.
3. Cek jumlah baris `work_orders` sebelum/sesudah cetak: tidak bertambah. Tampilan dokumen bersifat baca-saja. Coba URL `doc_alias=lain` dan pastikan ditolak.

## 4. Inspector KPI

1. Buka Produktivitas → panel rumus. Pilih bulan dan unit yang memiliki telematika/WO, klik `Periksa KPI`.
2. Catat W, S, R, T, jumlah kerusakan, jumlah perbaikan selesai dan jumlah baris sumber. Cocokkan dengan `telematics_logs` dan `work_orders` untuk unit/periode itu.
3. Hitung manual: PA=(W+S)/T; MA=W/(W+R); UA=W/(W+S); EU=W/T; MTBF=W/jumlah kerusakan; MTTR=R/jumlah perbaikan selesai. Persentase dikali 100 dan dibulatkan dua desimal. Nilai penyebut nol harus `Tidak Ada Data`.
4. Bandingkan nilai inspector dengan `api/kpi.php?period=Oktober%202026&asset_id=ID_UNIT` saat login. Uji seluruh unit; lokasi di luar hak akses tidak boleh terbaca.
5. Jika sumber telematika/WO tidak selaras, baca peringatan kualitas data. Kartu ringkasan Produktivitas lama memakai endpoint berbeda dan belum menjadi pengganti inspector per unit/periode.

## Hasil bersama

Isi hasil: WO normal [ ], WO bersyarat [ ], foto/PIC/timeline [ ], cetak WO/JO satu ID [ ], KPI manual/API/inspector [ ], role/lokasi [ ]. Laporkan ID unit/WO, periode, nilai yang berbeda, dan screenshot bila ada. Batch 6 menunggu konfirmasi Anda.
