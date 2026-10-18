# Pengujian Batch 3 — Onboarding aset dan PM persisten

Gunakan **Laragon2 lokal** dan satu kode unit uji unik, misalnya `QA-BRIAN-1710-01`. Jangan memakai kode yang sedang diuji anggota tim lain. Perubahan database yang telah diterapkan hanya menambah kolom/tabel; data aset dan PM lama tidak dihapus.

## Uji pendaftaran dari menu PM

1. Login sebagai Asset Manager atau Administrator. Buka **Preventive Maintenance → Tambah Unit Baru**.
2. Isi kode unit, lambung yang berbeda, kategori, site dari pilihan database, SN/plat jika ada, meter awal, operator, tanggal mulai operator, servis terakhir, meter saat servis, interval, jenis HM/KM, pemakaian harian, serta tanggal legalitas. Tambahkan dua riwayat servis lampau dengan format `YYYY-MM-DD,meter,jenis` per baris. Coba unggah satu foto JPEG/PNG/WebP dan satu PDF JO berukuran kecil.
3. Periksa angka target di pratinjau: `meter servis terakhir + interval`. Setelah simpan, cari kode uji pada tabel PM 11 kolom. Baris harus berasal dari database dan foto/JO harus menampilkan jumlah/status sebenarnya.
4. Reload, logout lalu login ulang. Cari unit yang sama di PM dan buka Unit Properties. Pastikan operator, riwayat operator, riwayat servis, target, estimasi, legalitas, foto, dan PDF tetap ada.
5. Di Dashboard/Aset, edit unit tersebut: ganti operator dan tambahkan servis terbaru. Periksa histori lama tetap ada, operator sebelumnya memiliki tanggal selesai, target servis berikutnya berubah, dan PM ikut berubah setelah refresh.
6. Dari PM, buka detail dan simpan realisasi dengan tanggal serta meter yang valid. Reload, lalu cek histori servis dan target berikutnya. Data tidak boleh hanya berubah di browser.

## Uji batasan dan kondisi kosong

1. Buat unit uji kedua **tanpa** operator, servis, foto, atau PDF. PM harus menampilkan `Belum tersedia`/`NO DATA`, bukan contoh operator/tanggal/foto/JO.
2. Coba interval negatif, koefisien nol, tanggal tidak valid, foto di atas 5 MB, PDF di atas 10 MB, dan file salah jenis. Sistem harus menolak input/unggahan yang tidak valid.
3. Coba akun tanpa `assets.write` mengubah aset atau mengunggah dokumen: harus ditolak. Coba akun terbatas lokasi mengakses unit di site lain: harus ditolak.
4. Filter PM berdasarkan site dan kategori; bandingkan jumlah/baris dengan site master aset. Pilihan site pada form pendaftaran harus berasal dari database dan sesuai cakupan akun.

## Verifikasi di HeidiSQL (database Laragon2 lokal)

```sql
SELECT asset_id, hull_number, current_operator_name, last_service_date,
       last_service_hm_km, service_interval_val, service_interval_unit,
       daily_usage_coefficient, next_service_due_hm_km,
       next_service_estimated_date, legal_stnk_expiry, legal_kir_expiry,
       legal_silo_expiry, service_manual_pdf_url
FROM assets WHERE asset_id = 'QA-BRIAN-1710-01';

SELECT * FROM asset_operator_history WHERE asset_id = 'QA-BRIAN-1710-01' ORDER BY history_id;
SELECT * FROM asset_service_history WHERE asset_id = 'QA-BRIAN-1710-01' ORDER BY service_log_id;
SELECT photo_id, photo_title, photo_url FROM asset_gallery_photos WHERE asset_id = 'QA-BRIAN-1710-01';
```

Catat hasil per langkah sebelum lanjut Batch 4. Jangan hapus unit uji bila sudah ada WO/riwayat yang perlu diaudit; tandai atau koordinasikan pembersihannya secara terpisah.
