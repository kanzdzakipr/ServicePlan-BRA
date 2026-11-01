# Panduan UAT feedback klien

Gunakan database pilot dan catat bukti screenshot, aktor, waktu, hasil aktual, serta keputusan validator pada setiap skenario.

| ID | Skenario | Aktor | Hasil wajib |
|---|---|---|---|
| UAT-01 | Buat WO dan tugaskan dua mekanik | Planner | Kedua PIC tersimpan dan muncul di rekap/ekspor |
| UAT-02 | Perbarui progres harian | Mekanik | Timeline menyimpan aktor, waktu, status, problem, tindakan |
| UAT-03 | Ajukan part dari WO | Mekanik/Planner | SPB menyimpan WO, unit, SKU, qty, UOM, faktor konversi |
| UAT-04 | Batalkan Draft SPB | Pembuat | Status Cancelled, alasan dan snapshot audit tersimpan, stok tidak berubah |
| UAT-05 | Tarik Submitted | Pembuat | Kembali Draft sebelum review, tanpa duplikasi transaksi |
| UAT-06 | Buat revisi formal | Pembuat/Approver | Dokumen lama tetap ada; versi baru memiliki parent dan revision number |
| UAT-07 | Revisi forecast long standby | Planner | Estimasi tanggal berubah, target meter tetap, alasan tercatat |
| UAT-08 | Generate PM dua kali | Planner | Publish kedua tidak menggandakan rencana |
| UAT-09 | Konversi dua drum oli | Logistik | Satuan asal/faktor tersimpan; base quantity tepat menurut kemasan |
| UAT-10 | Lihat part belum terpenuhi | Head/Logistik | Nama, unit, jumlah, aging, PIC, status, WO, SPB terlihat |
| UAT-11 | Ekspor CSV berisi nilai `=...` | Admin | Spreadsheet menampilkan teks, tidak mengeksekusi formula |
| UAT-12 | Akses valuasi sebagai non-admin | Asset Manager | UI tersembunyi dan API membalas 403 tanpa field sensitif |

Sign-off: Dheny/Fajar (WO), Samsul/Planner (PM & unit), Dany/Fadly (part/SPB), Widya (format laporan dan governance).
