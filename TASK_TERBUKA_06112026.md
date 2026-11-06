Berikut versi teks untuk dicross-check tim. Terdapat 19 task yang masih terbuka.

1. `PROD-01` — Finalisasi laporan Update Dump Truck  
   Dibutuhkan: file asli XLSX “Update Dumtruck 30.10.2026”, nama laporan final, susunan sheet/kolom, aturan warna dan status, periode, lokasi, serta contoh tiap status.  
   PIC: Widya.  
   Cross-check: jumlah unit/status sama dengan aplikasi dan setiap downtime terhubung ke WO.

2. `EXP-01` — Ekspor seluruh menu operasional  
   Dibutuhkan: daftar menu wajib ekspor, format CSV/XLSX/PDF, urutan kolom, filter, pola nama file, serta pembatasan data berdasarkan role.  
   PIC: Widya dan pemilik masing-masing modul.  
   Cross-check: jumlah baris ekspor sama dengan tabel terfilter dan tidak ada data sensitif bocor.

3. `SPB-02` — Aturan print SPB  
   Dibutuhkan: keputusan apakah hanya SPB Approved yang boleh dicetak atau Draft juga boleh dengan watermark; teks watermark, status lain yang boleh dicetak, tanda tangan, dan format halaman.  
   PIC: Widya.  
   Cross-check: Draft tidak dapat dianggap sebagai dokumen resmi.

4. `SPB-03` — Hubungan spare part dengan SPB, WO, PM, dan unit  
   Dibutuhkan: contoh transaksi nyata berisi nomor SPB, WO/PM, unit, SKU, qty, UOM, requester, PIC, status, dan waktu permintaan.  
   PIC: Dany/Fadly dan Planner.  
   Cross-check: hubungan dapat ditelusuri dua arah dari WO/PM ke SPB dan sebaliknya.

5. `PART-01` — Klasifikasi master part  
   Dibutuhkan: SKU unik, technical name, nama umum, authenticity, merek, kelompok part, base UOM, transaction UOM, lokasi gudang, saldo awal, dan status aktif.  
   PIC: Dany dan Fadly.  
   Cross-check: total sumber sama dengan valid + invalid + duplikat dan tidak ada SKU aktif ganda.

6. `PART-02` — Laporan part belum terpenuhi  
   Dibutuhkan: definisi awal/akhir aging, penggunaan hari kalender atau kerja, kalender libur, PIC yang ditampilkan, status aktif, serta SLA overdue.  
   PIC: Logistic Head/Dany.  
   Cross-check: hitungan aging dan sisa qty sama dengan perhitungan manual.

7. `PART-03` — Konversi drum/pail ke base UOM  
   Dibutuhkan per SKU: transaction UOM, base UOM, isi bersih/faktor konversi, merek/vendor, kode kemasan, tanggal berlaku, dan sumber faktor.  
   PIC: Dany/Fadly.  
   Cross-check: hasil konversi benar dan faktor transaksi lama tidak berubah ketika master diperbarui.

8. `PART-04` — Perbaikan grid dan scroll master part  
   Dibutuhkan: screenshot/video masalah, perangkat, browser, resolusi, zoom, kolom prioritas, dan pilihan tampilan mobile.  
   PIC: pengguna logistik dan Widya.  
   Cross-check: tidak ada kolom terpotong, header tetap terlihat, dan filter tetap dapat digunakan.

9. `PM-01` — Revisi jadwal PM  
   Dibutuhkan: field yang boleh diedit, field yang dikunci, daftar alasan revisi, role yang boleh merevisi, aturan approval, dan contoh nilai sebelum/sesudah.  
   PIC: Samsul/Planner.  
   Cross-check: hanya field yang diizinkan berubah dan riwayat before/after tercatat.

10. `PM-02` — Pergeseran forecast Long Standby/Breakdown  
    Dibutuhkan: definisi Long Standby, sumber status unit, periode kejadian, meter aktual, interval service, daily usage, dan contoh perhitungan manual.  
    PIC: Samsul/Planner dan Equipment Head.  
    Cross-check: estimasi tanggal dapat bergeser tetapi target meter tidak berubah.

11. `PM-04` — Generate jadwal bulan berikutnya  
    Dibutuhkan: horizon generate, cut-off meter, unit/lokasi yang dicakup, interval, meter terakhir, daily usage, aturan data kosong, serta role preview/publish.  
    PIC: Samsul/Planner.  
    Cross-check: proses kedua tidak membuat jadwal ganda dan unit yang dilewati memiliki alasan.

12. `PM-06` — Koreksi nama unit, nomor lambung, dan kategori Mercy  
    Dibutuhkan: asset ID, nama valid, nomor lambung valid, kategori, lokasi, nomor polisi/serial, sumber dokumen, dan validator.  
    PIC: Samsul.  
    Cross-check: tidak ada ID ganda dan histori WO/PM/SPB tetap terhubung.

13. `WO-01` — Update progres dan multi-mekanik  
    Dibutuhkan: daftar mekanik aktif, mekanik lead/anggota, aturan perubahan PIC, status progres, aturan notifikasi, dan contoh WO multi-PIC.  
    PIC: Dheny/Fajar dan mekanik pilot.  
    Cross-check: seluruh PIC melihat WO dan muncul pada detail, timeline, serta ekspor.

14. `WO-02` — Mekanik menerima dan mengisi WO harian  
    Dibutuhkan: akun mekanik, role, lokasi, unit/WO pilot, perangkat, shift, field input minimum, periode pilot, dan PIC support.  
    PIC: Dheny/Fajar dan HR/Admin.  
    Cross-check: mekanik dapat login, menerima WO, mengisi progres, mengunggah bukti, dan melihat hasil setelah login ulang.

15. `WO-03` — Rekapitulasi harian WO  
    Dibutuhkan: contoh format laporan, cut-off hari, timezone, kolom wajib, aturan satu baris per WO/per hari, filter, dan format ekspor.  
    PIC: Dheny/Fajar dan Widya.  
    Cross-check: total per status sama dengan timeline sumber.

16. `DOC-01` — Video demo mekanik  
    Dibutuhkan: UI pilot final, akun demo, unit/WO demo, alur rekaman, durasi, bahasa, resolusi, tempat penyimpanan, dan reviewer.  
    PIC: Brian dan Dheny/Fajar.  
    Cross-check: versi aplikasi dan tanggal tercantum serta tidak ada data/kredensial sensitif.

17. `DATA-01` — Normalisasi master aset  
    Dibutuhkan: ID, kode, nomor lambung, kategori, model, serial, nomor polisi, lokasi, status aktif, sumber dokumen, validator, dan catatan konflik untuk seluruh aset.  
    PIC: Samsul dan Asset Manager.  
    Cross-check: total sumber cocok, konflik terselesaikan, dan histori transaksi tetap terhubung.

18. `DATA-02` — Personel aktif/nonaktif  
    Dibutuhkan: employee ID, nama, username, role, lokasi, shift, status, tanggal efektif, atasan, alasan nonaktif, serta daftar personel baru.  
    PIC: HRD dan Dheny/Fajar.  
    Cross-check: personel nonaktif tidak bisa dipilih untuk WO baru tetapi tetap tampil pada histori.

19. `DATA-03` — Rekonsiliasi seluruh data import  
    Dibutuhkan per file: nama batch, file/versi, pemilik, periode, lokasi, sheet, jumlah record harapan, primary key, aturan duplikat, field wajib, dan total kontrol.  
    PIC: pemilik setiap file sumber.  
    Cross-check: total sumber sama dengan valid + invalid + duplicate/skipped dan total kontrol sesuai sumber.

Prioritas pengumpulan yang disarankan:

1. Keputusan Widya: `PROD-01`, `EXP-01`, `SPB-02`, `WO-03`.
2. Data Samsul/Planner: `PM-01`, `PM-02`, `PM-04`, `PM-06`, `DATA-01`.
3. Data Logistik: `SPB-03`, `PART-01` sampai `PART-04`.
4. Data Mekanik/HRD: `WO-01`, `WO-02`, `DOC-01`, `DATA-02`.
5. Manifest seluruh file import: `DATA-03`.