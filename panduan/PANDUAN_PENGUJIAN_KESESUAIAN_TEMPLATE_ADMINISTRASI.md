# Pengujian kesesuaian template administrasi

## BHW-IN (barang masuk)

1. Buka **Laporan & Form → Template Form → BHW-IN**.
2. Buat laporan baru. Pastikan tabel transaksi memiliki **Merk** dan **No. SPB** di samping kolom barang yang sudah ada.
3. Isi satu baris dengan part yang sudah ada di master, jumlah dan saldo yang cocok dengan stok saat ini. Isi Merk serta No. SPB, lalu simpan sebagai draf.
4. Buka kembali draf dan pastikan kedua nilai tetap tampil. Finalisasi hanya jika transaksi stok memang ingin dibukukan; setelah itu periksa jumlah stok part di menu Logistik.
5. Ulangi dengan Merk dan No. SPB kosong. Kedua kolom ini opsional karena dokumen sumber juga berisi banyak sel kosong.

## BAST beberapa unit

1. Buka **Laporan & Form → Template Form → BAST-UNIT**. Template ini mengacu pada `material/ADMINISTRASI/ADMINISTRASI-011_BAST_Unit_Dump_Truck_Trip_Ke_2_2_unit.md`.
2. Isi nomor berita acara, tanggal, tempat, project, serta identitas kedua pihak.
3. Tambahkan dua baris unit berbeda. Periksa ID unit, jenis alat, nomor polisi, SN engine, nomor rangka, PM service terakhir, dan GPS.
4. Simpan draf, buka ulang, dan periksa kedua baris tetap ada. Periksa juga pratinjau/hasil cetak sebelum finalisasi.
5. **Penting:** BAST-UNIT adalah dokumen pencatatan. Finalisasi tidak mengubah lokasi atau status aset secara otomatis. Form MDE-1 satu unit yang lama tetap tersedia dan memiliki integrasi aset tersendiri.

## Batas cakupan

Perbaikan ini menyelaraskan dua form transaksi dengan dokumen administrasi. Tujuh rekap bulanan Januari 2026 (breakdown, perbaikan harian, greasing, service, logistik gabungan, cash out, dan pendapatan sewa) adalah laporan agregat lintas sumber, bukan formulir transaksi. Mereka belum dijadikan generator rekap otomatis; perlu aturan periode, sumber data, dan perhitungan yang disepakati sebelum hasilnya bisa dianggap setara dokumen sumber.
