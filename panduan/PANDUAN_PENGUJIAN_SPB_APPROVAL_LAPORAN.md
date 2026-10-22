# Uji integrasi SPB → Approval → Laporan

Status: **PARTIAL** sampai diuji di browser dengan SPB uji yang disepakati. Perubahan kode sudah dipasang di Laragon2 secara selektif; dump tidak diimpor ulang. Tabel audit `spb_void_audit` dibuat secara aditif, tanpa mengubah baris SPB pengguna.

## Alur yang sekarang berlaku

- SPB dari Form masuk alur setelah **Finalisasi** (menyimpan draft saja belum mengajukan SPB). SPB dari WO/Logistik masuk setelah pengajuan berhasil di server. Ketiganya memakai `purchase_requests` dan `purchase_request_items` sebagai data operasional yang sama.
- Approval menampilkan SPB berstatus `Submitted` dari database, bukan baris contoh. Hanya Administrator, Equipment Manager, dan Logistic Head yang dapat memutuskan SPB, dengan pemeriksaan role, lokasi, dan CSRF di server.
- Setuju mengubah SPB menjadi `Approved`; Tolak membutuhkan alasan dan mengembalikannya ke `Draft`. Keduanya membuat baris audit `approvals`. Permintaan kedua terhadap SPB yang sama ditolak.
- Pada daftar riwayat dan preview cetak, SPB dengan status operasional `Draft` serta keputusan approval terakhir `REJECTED` ditampilkan sebagai **Rejected**. Jika diajukan ulang (`Submitted`), tampil **Menunggu Approval**, bukan keputusan lama.
- Menu Laporan → Riwayat kini memakai **satu tabel**: laporan final dari Form dan SPB dari WO/Logistik. SPB yang sudah punya laporan Form final tidak ditampilkan dua kali. Kolom status approval hanya relevan untuk SPB dari luar Form.
- Tombol **Lihat/Cetak** pada SPB dari WO/Logistik membuka preview data operasional. Cetak Form final tetap memakai isi lengkap laporan.
- Tombol **Void** pada laporan Form memakai mekanisme void laporan dan reversal integrasi yang sudah ada. SPB dari WO/Logistik dapat di-void dengan alasan dan audit bila belum ada PO atau stok keluar; bila sudah ada transaksi lanjutan, sistem menolak sampai transaksi tersebut dibereskan.
- Detail WO menampilkan `qtyRequested` yang benar dan tanggal pengajuan dari database. SPB yang telah diproses tidak boleh ditimpa oleh pengajuan Logistik ulang.

## Bukti otomatis

`php tests/spb_workflow_rollback.php`: **7/7 lulus**. Tes membuat SPB QA dalam transaksi, memeriksa approval dan void beserta audit, lalu rollback. Jumlah baris approval dan audit void sebelum/sesudah tetap sama; SPB QA tersisa 0. Sintaks PHP/JS repository dan Laragon2 lulus. Belum ada tes browser terautentikasi otomatis.

## Langkah uji browser

1. Tekan Ctrl+F5 di `http://serviceplan-bra.test/`. Buka Approval. SPB contoh lama tidak boleh muncul. SPB yang memang `Submitted` di database harus muncul dengan item dan total qty yang benar. Jangan putuskan SPB nyata yang bukan data uji.
2. Buka Laporan → Riwayat Laporan. Tidak boleh ada lagi blok **Dokumen SPB operasional** terpisah. Nomor SPB dari WO/Logistik harus terlihat di tabel riwayat bersama laporan Form, dengan status approval. Buka detail WO dan cocokkan qty.
   Klik **Lihat/Cetak** pada SPB uji, cek item/qty/satuan/status pada preview, lalu pilih **Cetak / Simpan PDF**. Pastikan tidak mencetak SPB lain secara tidak sengaja.
3. Dengan WO/unit uji yang Anda izinkan, ajukan SPB baru dari WO atau Logistik. Setelah refresh, cek SPB yang sama di Approval, Riwayat Laporan, dan detail WO. Untuk Form SPB, **finalkan** laporan uji; draft saja tidak masuk antrean.
4. Login sebagai role pembaca tanpa kewenangan memutuskan: daftar bisa dibaca sesuai izin/lokasi, tetapi tombol Setuju/Tolak tidak tersedia. Role berwenang yang berbeda dapat memutuskan SPB QA; refresh dan pastikan status tetap sama.
5. Uji Void hanya pada SPB QA milik Anda: isi alasan, pastikan baris hilang dari riwayat aktif, Approval, dan detail WO, sedangkan `spb_void_audit` menyimpan jejaknya. Uji SPB yang sudah punya PO/stok keluar harus ditolak tanpa mengubah data. Jangan void data operasional pengguna lain.

Query baca-saja untuk ID uji:

```sql
SELECT spb_id, wo_id, asset_id, status FROM purchase_requests WHERE spb_id = 'ID-SPB-UJI';
SELECT part_number, qty_requested FROM purchase_request_items WHERE spb_id = 'ID-SPB-UJI';
SELECT status, rejection_reason, action_at FROM approvals
WHERE document_type = 'SPB' AND document_id = 'ID-SPB-UJI' ORDER BY approval_id;
SELECT spb_id, reason, voided_at FROM spb_void_audit WHERE spb_id = 'ID-SPB-UJI';
```

## Batasan

Jenis laporan selain SPB **belum** dimasukkan ke antrean Approval; perlu keputusan jenis dan approver per dokumen. Approval SPB belum diuji lintas browser/role/lokasi pada data QA nyata. Jangan menyebut seluruh modul Approval PASS hanya dari tes rollback.
# Uji SPB dari detail Work Order (Form P-1 lengkap)

1. Muat ulang `http://serviceplan-bra.test` dengan Ctrl+F5, lalu buka detail WO dan klik **Buat SPB / Minta Part**.
2. Pastikan yang terbuka adalah **Laporan & Form → SPB (P-1)**, bukan modal Logistik singkat. Nomor WO, kode unit, lokasi, tanggal, urgensi, dan item WO yang tersedia harus terisi otomatis. Project hanya diisi otomatis jika tercatat; lengkapi nomor kontrak, nomor SPB, penerima, perusahaan, tanggal kesiapan, dan rincian item sebelum finalisasi.
3. Bila ada draft SPB lama, sistem harus meminta konfirmasi sebelum menggantinya. Pilih Batal untuk memastikan draft lama tidak tertimpa.
4. Pada kolom **Spesifikasi SC / PN** atau **Nama barang**, ketik sebagian nomor/nama part dan pilih saran dari database. Di modal Logistik, kolom Master Part juga kini dapat diketik; hasil saran menampilkan nomor dan nama part.
5. Simpan/finalisasi satu SPB uji yang unik, lalu cek **Riwayat Laporan** (satu baris saja), **Approval**, dan detail WO. Jangan gunakan nomor SPB yang sudah ada.

Catatan: Cetak SPB operasional membaca `purchase_requests` dan `purchase_request_items`; Form P-1 lengkap tersimpan di `report_records` dan `report_items`. Karena itu, gunakan cetak dari Riwayat Laporan untuk memastikan seluruh field Form P-1 tampil; cetak operasional masih berupa proyeksi data inti.
