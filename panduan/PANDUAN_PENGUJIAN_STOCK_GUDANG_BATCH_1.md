# Pengujian STOCK_GUDANG — batch 1

## Hasil audit otomatis 19 Oktober 2026

**Status: PARTIAL.** Pratilik impor menunjukkan 716 nomor part sumber sudah ada (`new: 0`, konflik: 0). Tabel `stock_gudang_imports` berisi 716 baris, semuanya tertaut ke 716 nomor unik di `parts`; total master 721 part. Jumlah saldo awal part impor 582 dan saldo saat audit juga 582. Tidak ada part impor dengan nomor kosong.

Tes integrasi berbasis transaksi rollback lulus: BHW-IN/OUT 19, SPB 17, PPB 10. Endpoint referensi laporan dan master Logistik membaca tabel `parts` tanpa batas jumlah baris, sehingga 721 part tersedia bagi pilihan menu. Pengujian browser dengan akun nyata belum dilakukan; hasil otomatis ini belum membuktikan pengalaman UI lintas role.

**Temuan yang perlu diperbaiki sebelum semua menu dinyatakan aman:** 78 kelompok nama part digunakan oleh lebih dari satu nomor (contoh `O RING`, `Gasket`, `Seal`). Pemilihan berdasarkan **nomor part** dapat membedakannya, tetapi pemilihan berdasarkan **nama saja** pada referensi form dan Report Parts Weekly berpotensi menautkan baris ke part pertama yang namanya sama. Untuk sementara, gunakan nomor part pada SPB, PPB, BHW, dan jangan finalisasi Report Parts Weekly untuk nama duplikat sampai lookup-nya diperbaiki.

Sumber: `material/STOCK_GUDANG.md`, Yard KM 12, periode 26 Agustus–25 September 2026. Saldo sumber adalah posisi per **25 September 2026**, bukan transaksi penerimaan baru pada 18 Oktober.

## Hasil impor lokal

- 716 part bernomor unik ditambahkan ke master `parts` di database Laragon2. Empat nomor berulang pada sumber digabung bila satuan dan saldonya sama (5 baris duplikat tambahan).
- 5 part lama tidak diubah. Total master kini 721 part.
- 611 baris tanpa part number **belum diimpor**. Penomoran sementara tanpa persetujuan dapat menyebabkan salah pilih pada SPB/PPB dan stok tercampur.
- `stock_qty` part baru diisi dari kolom `Sisa/Saldo Stok`; `stock_gudang_imports` menyimpan hash dokumen, tanggal sumber, nomor baris, dan saldo asal. Angka kumulatif penerimaan/pemakaian sumber tidak dibuat sebagai transaksi BHW, agar tidak menggandakan riwayat stok.

## Langkah uji

1. Buka **Spare Part & Logistik → Stok**. Cari `6Y3224.AD` (ADAPTER TOOT); saldo harus `0`. Cari part lain yang memiliki saldo positif di dokumen, lalu bandingkan angkanya.
2. Buka form **SPB** dan **PPB**, pilih part bernomor dari daftar master. Nama, nomor, dan satuannya harus terisi dari database. Jangan finalisasi permintaan sungguhan hanya untuk mencoba pilihan.
3. Buka **Laporan & Form → BHW-IN/BHW-OUT**. Part bernomor baru harus muncul sebagai referensi. Nilai saldo awal untuk transaksi baru harus mengikuti stok database saat ini.
4. Jalankan `php scripts/import_stock_gudang.php` dari root proyek: mode pratilik harus menampilkan `new: 0`. Ulangi `--apply` hanya bila memang perlu; skrip tidak menimpa part yang sudah ada.
5. Di HeidiSQL, periksa `SELECT COUNT(*) FROM parts;` (721) dan `SELECT COUNT(*) FROM stock_gudang_imports;` (716) pada database `u646470441_ServicePlanBRA` lokal.

## Batch lanjutan

611 item tanpa part number memerlukan aturan identitas: gunakan nomor resmi dari gudang bila tersedia, atau setujui format nomor sementara yang ditandai jelas. Setelah itu barulah item tersebut dapat masuk master dan semua menu tanpa risiko bercampur dengan part bernomor asli.
