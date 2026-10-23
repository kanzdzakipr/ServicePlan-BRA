# Alur pengujian F2 — WO, SPB, stok, laporan, biaya

Status 23 Oktober 2026: **PASS fungsional disetujui pengguna** untuk alur browser dan autofill. Regression otomatis serta rekonsiliasi database juga lulus; uji lintas role/lokasi menyeluruh tetap menjadi bagian regression gate akhir F6. Gunakan `http://serviceplan-bra.test/` pada Laragon2. Jangan impor dump, reset database, menjalankan sinkronisasi penuh, atau mengubah SPB/WO milik pengguna lain.

## Hasil penutupan F2

- Pengguna menyetujui alur browser F2 dan autofill PPB, BHW-IN, BHW-OUT, BAPP, serta Monitoring Pengadaan pada 23 Oktober 2026.
- Tiga belas berkas regression menghasilkan **124 pemeriksaan lulus** untuk WO/SPB, approval/void SPB, SPPU, PPB/PO, approval/pembatalan PO dan biaya, BAPP, BHW-IN/OUT, kirim/terima internal, monitoring pengadaan, serta alasan void.
- Seluruh fixture pengujian memakai transaksi rollback; pemeriksaan pascates menunjukkan data QA sementara tidak tersisa.
- Rekonsiliasi baca-saja database Laragon2: stok negatif `0`, biaya PO aktif ganda `0`, PO disetujui tanpa biaya `0`, PO batal dengan biaya aktif `0`, item PO di luar SPB `0`, transaksi stok yatim `0`, dan transaksi stok aktif pada laporan VOID `0`.
- Status ini menutup acceptance fungsional F2. Matriks keamanan lintas seluruh role/lokasi dan regression desktop/mobile tetap tidak boleh dihilangkan dari release gate F6.

## Persiapan bersama (sebelum membuat data)

1. Sepakati **satu WO, satu aset, satu lokasi, dan akun uji** yang boleh dipakai. Jika belum ada data uji yang aman, berhenti pada pemeriksaan baca-saja. Jangan menganggap WO operasional bebas dipakai.
2. Siapkan nomor unik, misalnya `QA-BRIAN-SPB-20261023-01`; catat `wo_id`, `asset_id`, `part_number`, `report_id`, dan waktu uji. Jangan pakai ulang nomor SPB yang pernah di-void.
3. Catat role: pembuat, approver yang berwenang, dan pembaca tanpa izin approval. Uji lintas lokasi hanya dengan akun uji yang memang tersedia; jangan membuka data lokasi lain sebagai admin untuk menyimpulkan pembatasan berhasil.
4. Buka dua browser/profil terpisah. Sebelum setiap perubahan, catat tangkapan layar dan hasil `SELECT` baca-saja. Setelah perubahan, ulangi pemeriksaan sesudah refresh dan login ulang.
5. Untuk uji penolakan, approval, finalisasi, dan void, **gunakan ID QA saja**. Jangan menguji pada SPB contoh/operasional yang sudah ada. Jangan membersihkan data dengan `DELETE` SQL manual; gunakan aksi void aplikasi bila aman.

Catatan bukti per langkah: `ID | role/lokasi | waktu | aksi | hasil layar | HTTP status | hasil SELECT | PASS/FAIL/NOT TESTED`. Jika aksi gagal, simpan pesan kesalahan dan **berhenti pada subalur itu**; jangan membuat data pengganti yang menyamarkan kegagalan.

## F2c — WO → SPB → Approval → Riwayat Laporan

| Langkah | Aksi di browser | Hasil yang diharapkan |
| --- | --- | --- |
| 1 | Buka detail WO QA dan catat kebutuhan part, SPB tertaut, serta qty awal. | ID WO/aset sesuai; tidak ada perubahan hanya karena dibuka. |
| 2 | Klik **Buat SPB/Minta Part** dari detail WO. | Form P-1 lengkap terbuka; WO, unit, lokasi, tanggal, urgensi, dan item yang tersedia terisi tanpa mengarang project/nomor kontrak. |
| 3 | Ketik sebagian nama/nomor part, pilih saran, lengkapi field wajib, lalu simpan **draft**. | Saran berasal dari master part; draft belum masuk antrean Approval. |
| 4 | Finalisasi SPB QA bernomor unik. Catat `report_id` dan `spb_id`. | Tepat satu laporan final di Riwayat; tepat satu SPB operasional tertaut ke WO; item dan total qty sama; status `Submitted` muncul di Approval. |
| 5 | Cek di browser kedua, refresh, lalu login ulang. | Nomor, qty, dan status tetap sama; tidak ada duplikasi laporan/SPB. |
| 6 | Buat **SPB QA kedua melalui menu Logistik** (bukan tombol WO yang membuka Form P-1), lalu tolak dari Approval dengan alasan. | Approval mencatat `REJECTED`; status operasional kembali `Draft`; kolom status approval dan preview cetak SPB eksternal menampilkan **Rejected**, bukan Draft. |
| 7 | Jika fitur pengajuan ulang tersedia untuk SPB QA yang ditolak, ajukan ulang dan cek cetak sebelum keputusan baru. | Status `Submitted`/Menunggu Approval mengalahkan keputusan rejected lama; audit lama tetap ada. Jangan menganggap langkah ini PASS jika UI pengajuan ulang tidak tersedia. |
| 8 | Untuk menguji **Approved**, gunakan SPB QA eksternal lain. Setujui sebagai approver berwenang. | Status `Approved` di riwayat, cetak, dan DB; keputusan kedua/replay ditolak. |
| 9 | Login sebagai role tanpa izin approval dan, bila ada, lokasi lain. | Tidak ada tombol keputusan/void tanpa izin; API menolak aksi dengan 403/404 tanpa mengubah baris. Jangan mencoba payload yang menyasar data nyata. |
| 10 | Void **hanya SPB QA eksternal** tanpa PO/stok keluar, dengan alasan. Untuk SPB dari Form, void laporan asal. | Baris aktif hilang dari antrean dan detail WO sesuai aturan; audit void tersimpan; nomor lama tak bisa dipakai ulang. Jika laporan Form yang telah disetujui mempertahankan SPB operasional, cek pembatalannya sebagai langkah terpisah. |

Gate F2c: langkah positif/negatif yang relevan cocok di UI, HTTP, dan DB; role/lokasi teruji; refresh/login ulang konsisten; tidak ada data non-QA berubah. **Jangan klaim F2c PASS hanya dari tes otomatis rollback.**

## F2d — SPB → PO → penerimaan/pengeluaran part → saldo

Aturan terbaru: SPB harus `Approved` sebelum PPB/PO dibuat. PPB/PO wajib memakai part yang sama dengan SPB dan total qty PO aktif per part tidak boleh melebihi qty SPB. SPPU belum membukukan biaya. PO `Submitted` muncul di **Approval**; hanya approver yang berwenang dapat menyetujuinya. Saat Approved, menu **Biaya** harus menampilkan tepat satu transaksi `PO_APPROVED` senilai total PO. Tombol **Batalkan PO & biaya** hanya untuk PO Approved yang belum menerima barang; alasan wajib, status menjadi `Cancelled`, dan biaya otomatis menjadi reversed. Nomor PO yang dibatalkan tidak boleh dipakai ulang. Monitoring pengadaan maupun BAPP tidak boleh melewati approval PO ini.

### Uji pengisian barang otomatis dari SPB

1. Buka **Laporan & Form → PPB**, pilih `Nomor SPB` yang Approved. Semua item SPB harus muncul sebagai baris: nama, part number, satuan, dan qty. Harga tetap kosong; isi harga penawaran sendiri. Jangan gunakan nomor PPB yang sudah pernah dibatalkan.
2. Buka **BHW-IN**, pilih `SPB sumber barang`. Item dan saldo awal master part muncul. Isi jumlah yang benar-benar diterima, nomor bukti, dan kolom wajib lain. **BHW-OUT** melakukan hal serupa, termasuk WO/unit; isi jumlah yang benar-benar dikeluarkan. Pemilihan SPB sendiri tidak boleh mengubah stok database.
3. Klik **Tambah baris** untuk barang lain dan pastikan baris otomatis tidak hilang. Untuk PPB, barang baru wajib ditambahkan/revisi dahulu pada SPB; finalisasi PPB akan menolak part atau jumlah di luar SPB. Untuk BHW-IN, part baru boleh dicatat sesuai validasi master; BHW-OUT mensyaratkan part sudah ada di master stok.
4. Ubah jumlah/harga manual, simpan draft, refresh, lalu buka ulang. Pastikan perubahan tidak ditimpa oleh auto-fill. Pilih SPB lain: aplikasi harus meminta konfirmasi sebelum mengganti baris lama.

### Matriks form yang menarik barang dari dokumen lain

Audit seluruh template form menemukan lima alur yang memang memiliki dokumen transaksi sumber. Dropdown master seperti aset, WO, lokasi, personel, dan part tetap mengisi identitas/referensi saja; dropdown tersebut bukan sumber daftar barang.

| Form tujuan | Dokumen sumber yang dipilih | Data barang yang ditarik otomatis | Tetap diisi pengguna |
|---|---|---|---|
| PPB | SPB berstatus `Approved`/`Ordered` | Nama, part number/SC, satuan, qty | Vendor, penawaran, harga, batas/tempat penyerahan |
| BHW-IN | SPB berstatus `Approved`/`Ordered`/`Issued` | Part number, nama, satuan, saldo awal, nomor SPB | Qty yang benar-benar diterima, BAPB, asal, keterangan |
| BHW-OUT | SPB berstatus `Approved`/`Ordered`/`Issued` | Part number, nama, satuan, stok, WO, unit | Qty yang benar-benar keluar, bukti kirim, tujuan, keterangan |
| BAPP | PO berstatus `Approved`/`Ordered` | Seluruh item PO, satuan, qty order, vendor, tanggal PO | Kondisi baik/rusak/kurang, faktur, kendaraan, alamat |
| Monitoring Pengadaan | SPB yang dipilih pada kolom `No SPB` | Satu baris per item SPB: tanggal, WO, unit, nama, part number, qty, satuan, status awal | Waktu proses/tiba, kendala, tindakan, dampak RTW, kesimpulan |

Aturan pengujian bersama:

1. Pilih dokumen sumber pada form yang masih kosong; seluruh item harus muncul tanpa simpan/finalisasi lebih dulu.
2. Bila dokumen berisi lebih dari satu item, jumlah baris hasil harus sama dengan jumlah item sumber.
3. Isi satu baris secara manual, lalu pilih dokumen sumber lain. Pilih **Batal** pada konfirmasi; nomor sumber, vendor/tanggal terkait, dan seluruh baris lama harus tetap sama.
4. Ulangi dan pilih **OK**; baris lama diganti oleh seluruh item sumber. Tombol **Tambah baris** tetap dapat dipakai untuk melengkapi data sesuai aturan bisnis form.
5. Simpan draft, refresh, dan buka ulang. Data hasil tarik dan dokumen sumber harus tetap ada serta tidak dimuat ganda.
6. Memilih dokumen sumber saja tidak boleh mengubah stok, status SPB/PO, biaya, atau tabel transaksi. Perubahan database bisnis baru boleh terjadi saat laporan difinalkan.

Jangan gunakan PO `11 / Dept-Equip / 11 / 2026` sebagai fixture baru: pada 23 Oktober 2026 pengguna sengaja membatalkannya karena item PO tidak cocok dengan SPB; statusnya `Cancelled` dan biaya Rp44.400.000 sudah reversed. Buat SPB dan PPB baru berawalan `QA-F2-` untuk pengujian lanjutan.

Jalankan hanya setelah F2c disetujui dan alur PO/stok untuk data QA disepakati. Periksa apakah modul memang menyediakan aksi yang diperlukan; jangan menyusun transaksi SQL manual untuk menutup kekurangan UI.

1. Catat saldo awal part QA di menu stok dan tabel `parts`. Pastikan satuan dan part number sama. Catat juga PO/SPB/WO yang telah ada.
2. Buat PO/PPB QA dari SPB QA yang sudah Approved, jika alur UI mengizinkan. Periksa satu PO saja, nilai item/qty/vendor, dan keterkaitannya ke SPB/WO. Ulangi klik/simpan untuk menguji pencegahan duplikasi.
3. Bila ada penerimaan barang, catat satu transaksi QA. Periksa stok sesudah = stok sebelum + qty diterima, transaksi `IN` mempunyai referensi laporan/PO yang benar, dan angka di menu Logistik/WO sesuai setelah refresh.
4. Bila ada pengeluaran barang ke WO, catat satu transaksi QA. Periksa stok sesudah = stok sebelum − qty keluar, transaksi `OUT` tertaut ke WO/aset, dan qty SPB tidak berubah secara keliru. Uji permintaan lebih besar dari stok: harus ditolak tanpa saldo negatif.
5. Coba Void SPB yang sudah punya PO atau stok `OUT` **tanpa menekan konfirmasi pada data nyata**. Pada data QA, sistem harus menolak dengan alasan jelas; PO/transaksi turunan harus direversal melalui alur resminya terlebih dahulu. Jangan menghapus PO atau baris stok langsung dari HeidiSQL.
6. Uji reversal transaksi QA bila memang ada tombol resmi dan aturan bisnisnya jelas. Pastikan `reversed_at`/audit terisi, saldo pulih tepat satu kali, dan percobaan reversal kedua tidak mengubah saldo lagi. Jika tombol/aturan belum ada, tandai **FAIL/NEEDS DECISION**, bukan PASS.

Gate F2d: selisih saldo dan seluruh referensi cocok satu per satu; tidak ada stok negatif/duplikasi; void/reversal aman. Jika penerimaan, pengeluaran, atau reversal belum tersedia, F2d tetap PARTIAL.

## F2e — finalisasi/void laporan → WO/SPB/stok/biaya

1. Gunakan laporan QA yang membuat dampak operasional terukur. Catat baris laporan, integrasi, stok, dan biaya sebelum finalisasi. Untuk laporan stok, gunakan part/qty QA yang disepakati.
2. Finalisasi **sekali**. Periksa status `FINAL`, tautan integrasi, baris transaksi sumber, serta tampilan di menu terdampak. Refresh dan login ulang; angka tidak boleh berubah sendiri.
3. Pada laporan QA yang sama, uji dialog Void: pilih **Batal** lalu alasan kosong. Keduanya harus meninggalkan laporan `FINAL` dan seluruh saldo/biaya tidak berubah.
4. Void dengan alasan valid. Periksa status `VOID`, audit aktor/alasan/waktu, `reversed_at` pada integrasi/transaksi terkait, dan dampak pada menu WO, SPB, stok, serta biaya. Lakukan refresh dan login ulang.
5. Coba Void kedua pada ID QA yang sama. Harus ditolak atau menjadi no-op yang jelas, tanpa reversal/saldo ganda.
6. Cocokkan total biaya pada menu dengan `asset_cost_transactions` aktif (`reversed_at IS NULL`). **NEEDS DECISION:** sumber biaya otomatis harus disepakati dahulu (PO approved, SPPU final, keduanya dengan deduplikasi, atau manual). Jangan menyatakan posting biaya otomatis PASS sebelum keputusan ini.

Gate F2e: finalisasi dan void idempoten, audit utuh, saldo/biaya kembali sesuai aturan, role/lokasi dan browser kedua konsisten. Jika reversal turunan belum tersedia atau keputusan biaya belum dibuat, F2 keseluruhan masih PARTIAL.

## Query baca-saja untuk HeidiSQL

Ganti placeholder dengan ID QA yang **dicatat dari UI**. Jalankan satu `SELECT` per kali; jangan menjalankan `UPDATE`, `DELETE`, `INSERT`, atau `ALTER` untuk pengujian ini.

```sql
SELECT spb_id, wo_id, asset_id, status, requested_at
FROM purchase_requests WHERE spb_id = 'ID-SPB-QA';

SELECT id, part_number, description, qty_requested, status
FROM purchase_request_items WHERE spb_id = 'ID-SPB-QA' ORDER BY id;

SELECT approval_id, status, rejection_reason, action_at
FROM approvals WHERE document_type = 'SPB' AND document_id = 'ID-SPB-QA'
ORDER BY approval_id;

SELECT report_id, spb_id, owns_purchase_request, reversed_at
FROM report_purchase_request_integrations WHERE spb_id = 'ID-SPB-QA';

SELECT report_id, report_number, status, finalized_at, voided_at
FROM report_records WHERE report_id = 'ID-REPORT-QA';

SELECT action, actor_id, occurred_at, payload_json
FROM report_audit_logs WHERE report_id = 'ID-REPORT-QA' ORDER BY audit_id;

SELECT spb_id, reason, voided_by, voided_at
FROM spb_void_audit WHERE spb_id = 'ID-SPB-QA';

SELECT ppb_id, spb_id, wo_id, status, total_amount
FROM purchase_orders WHERE spb_id = 'ID-SPB-QA';

SELECT transaction_id, report_id, wo_id, movement_type, quantity,
       stock_before, stock_after, reversed_at
FROM inventory_transactions WHERE report_id = 'ID-REPORT-QA'
ORDER BY transaction_id;

SELECT transaction_id, cost_code, wo_id, amount, source_type,
       source_id, reversed_at
FROM asset_cost_transactions WHERE wo_id = 'ID-WO-QA'
ORDER BY transaction_id;
```

## Cara melaporkan hasil ke saya

Kirim **satu checkpoint per pesan**. Mulai dari F2c: `WO QA`, `SPB QA`, role/lokasi, hasil langkah 1–10, pesan error/HTTP bila ada, dan hasil `SELECT` yang relevan (tanpa nama/password pengguna). Saya akan cocokkan bukti dan menandai PASS/PARTIAL/FAIL sebelum kita lanjut F2d.
