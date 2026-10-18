# Pengujian Batch 2 — Identitas aset dan audit nomor lambung

Batch ini membedakan **kode unit permanen** (`assets.asset_id` / `asset_code`) dari **nomor lambung proyek** (`assets.hull_number`). `alias_name` tetap dibaca sebagai fallback untuk data lama; migrasi tidak menyalin atau menimpa nilai lama secara massal. Saat nomor lambung diubah secara sah, kedua kolom diselaraskan dan jejaknya disimpan di `asset_hull_history` dalam satu transaksi.

## Prasyarat

1. Buka instalasi Laragon2 lokal `serviceplan-bra.test`; gunakan satu **unit uji** yang tidak sedang diubah anggota tim lain.
2. Catat kode unit, nomor lambung, SN, plat, lokasi, dan jumlah riwayat awal. Jangan memakai unit produksi untuk percobaan tanpa persetujuan pemilik data.
3. Masuk sebagai Asset Manager, Equipment Manager, Maintenance Planner, atau Administrator untuk skenario perubahan; siapkan akun lain untuk skenario penolakan.

## Langkah uji

1. Di daftar Aset/Dashboard, cari unit uji. Buka **Rename Nomor Lambung**. Pastikan kode unit ditampilkan tetapi tidak dapat diedit.
2. Masukkan nomor lambung baru, alasan, dan tanggal efektif. Simpan. Pastikan modal tertutup dan nomor baru tampil di daftar.
3. Reload halaman. Buka **Unit Properties** unit yang sama, buka **Riwayat perubahan nomor lambung**. Cocokkan nomor lama, baru, alasan, tanggal efektif, aktor, serta waktu pencatatan. SN, plat, dan kode unit harus tetap.
4. Buka Dashboard, WO, PM, dan Logistik untuk unit yang sama. Nomor lambung pada tampilan identitas terbaru harus sama. WO lama dapat menyimpan snapshot historis nomor saat dibuat; jangan menganggap snapshot lama sebagai rename baru.
5. Coba simpan nomor yang sama, alasan kosong, atau tanggal tidak valid. API harus menolak dan jumlah riwayat tidak bertambah.
6. Coba dengan peran yang bukan empat peran di atas. Perubahan harus ditolak `403`. Coba unit di lokasi di luar cakupan akun; akses harus ditolak.
7. Buat aset uji baru: isi kode unit permanen dan nomor lambung yang berbeda. Pastikan kedua nilai tersimpan berbeda. Jika nomor lambung belum diketahui, biarkan kosong; tampilannya harus **Belum tersedia**, bukan nilai rekaan.

## Cek database lokal di HeidiSQL

```sql
SELECT asset_id, asset_code, hull_number, alias_name, serial_number, license_plate
FROM assets WHERE asset_id = 'GANTI_DENGAN_ID_UNIT_UJI';

SELECT old_hull_number, new_hull_number, change_reason, effective_date,
       changed_by_user_id, changed_by_name, changed_at
FROM asset_hull_history WHERE asset_id = 'GANTI_DENGAN_ID_UNIT_UJI'
ORDER BY history_id DESC;
```

Hasil lulus bila nomor terbaru tersimpan, satu perubahan menghasilkan satu baris audit, kode/SN/plat tetap, dan uji penolakan tidak menghasilkan audit baru. Hentikan sebelum Batch 3 sampai Anda mengonfirmasi hasil uji di Laragon2 lokal.
