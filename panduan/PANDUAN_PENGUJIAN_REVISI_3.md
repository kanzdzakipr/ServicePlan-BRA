# Panduan Pengujian Revisi 3 — Detail Work Order

## Tujuan

Memastikan setiap Work Order menyimpan dan menampilkan:

- jenis pemeliharaan;
- prioritas;
- mekanik/PIC;
- keluhan atau detail kerusakan;
- penyebab kerusakan;
- tindakan perbaikan;
- waktu laporan, mulai dikerjakan, dan selesai;
- durasi penanganan;
- timeline perubahan status sampai selesai.

## Persiapan

1. Pastikan Apache dan MySQL Laragon 2 aktif.
2. Buka `http://serviceplan-bra.test` lalu login.
3. Masuk ke menu **Work Order**.
4. Siapkan satu unit uji yang mudah dicari. Catat kode unitnya.

## Skenario 1 — Membuat Work Order Baru

1. Klik **Buat Work Order / Lapor Breakdown**.
2. Pilih unit uji.
3. Pilih prioritas, misalnya **High**.
4. Pilih jenis pemeliharaan, misalnya **Inspection**.
5. Isi keluhan: `UJI R3 - tekanan hidrolik menurun`.
6. Isi penyebab awal: `UJI R3 - indikasi selang getas`.
7. Pilih mekanik/PIC.
8. Pilih kondisi downtime lalu simpan.

Hasil yang diharapkan:

- WO baru muncul pada kolom **Open**;
- kartu menampilkan jenis pemeliharaan, PIC, prioritas, dan unit yang benar;
- keluhan tidak tercampur dengan jenis pemeliharaan atau log tindakan;
- saat detail dibuka, timeline pertama bertuliskan **Work Order dibuat**.

## Skenario 2 — Memulai Pengerjaan

1. Pindahkan kartu WO dari **Open** ke **In Progress**.
2. Pastikan PIC benar.
3. Isi atau koreksi **Penyebab Kerusakan**.
4. Isi **Tindakan Perbaikan**, misalnya `Pemeriksaan tekanan dan pembongkaran selang dilakukan`.
5. Simpan perubahan.
6. Buka kembali detail WO.

Hasil yang diharapkan:

- status menjadi **In Progress**;
- waktu **Mulai Dikerjakan** terisi otomatis;
- penyebab dan tindakan tampil pada tempat terpisah;
- timeline mencatat perubahan **Open menjadi In Progress**, pengguna, waktu, dan tindakannya.

## Skenario 3 — Validasi Penutupan

1. Pilih satu WO uji yang penyebab kerusakannya masih kosong.
2. Coba ubah status menjadi **Closed** tanpa mengisi penyebab.

Hasil yang diharapkan: sistem menolak dan menampilkan pesan bahwa penyebab kerusakan wajib diisi.

3. Isi penyebab kerusakan, pilih PIC, dan isi tindakan akhir.
4. Simpan status **Closed**.
5. Buka detail WO.

Hasil yang diharapkan:

- status menjadi **Closed**;
- waktu selesai terisi otomatis;
- durasi dihitung dari waktu laporan sampai waktu selesai;
- timeline mencatat seluruh perubahan secara urut;
- tindakan akhir tidak mengubah teks keluhan awal.

## Skenario 4 — Membuka Kembali WO

1. Ubah WO **Closed** kembali menjadi **Open**.
2. Buka detail WO.

Hasil yang diharapkan:

- status kembali **Open**;
- waktu selesai dikosongkan karena kasus aktif kembali;
- waktu mulai pertama tetap tersimpan;
- timeline mencatat perubahan **Closed menjadi Open**.

## Skenario 5 — WO dari Laporan Repair & Overhaul

1. Buat dan finalkan satu laporan **Repair & Overhaul**.
2. Buka menu **Work Order** dan cari nomor laporan tersebut.
3. Buka detail WO.

Hasil yang diharapkan:

- satu WO dibuat tanpa duplikasi;
- jenis pemeliharaan tersimpan sebagai **Corrective**;
- PIC dan prioritas mengikuti laporan;
- timeline mencatat bahwa WO dibuat dari laporan Repair & Overhaul.

## Verifikasi Database Laragon 2

Jalankan query berikut di HeidiSQL pada database `u646470441_ServicePlanBRA`.

```sql
SELECT
    wo_id,
    asset_id,
    status,
    priority,
    maintenance_type,
    assigned_mechanic,
    root_cause,
    repair_action,
    reported_at,
    repair_started_at,
    closed_at,
    downtime_minutes
FROM work_orders
WHERE wo_id = 'GANTI_DENGAN_ID_WO_UJI';
```

```sql
SELECT
    event_type,
    from_status,
    to_status,
    title,
    description,
    actor_name,
    occurred_at
FROM work_order_timeline
WHERE wo_id = 'GANTI_DENGAN_ID_WO_UJI'
ORDER BY occurred_at, timeline_id;
```

## Checklist Hasil

- [ ] WO baru menyimpan prioritas dan jenis pemeliharaan sebagai kolom terpisah.
- [ ] PIC tampil pada kartu dan detail WO.
- [ ] Penyebab kerusakan tersimpan terpisah dari keluhan.
- [ ] Tindakan perbaikan tersimpan terpisah dari keluhan.
- [ ] Waktu mulai terisi ketika pengerjaan dimulai.
- [ ] Waktu selesai dan durasi terisi ketika WO ditutup.
- [ ] WO tidak dapat ditutup tanpa penyebab dan PIC.
- [ ] Timeline mencatat pembuatan dan setiap perubahan status.
- [ ] WO dari laporan Repair & Overhaul memiliki timeline awal.
- [ ] Data pada tampilan sama dengan data di database Laragon 2.
