# Panduan Pengujian Revisi 4 — Kebutuhan Part dan Layanan dari WO

## Tujuan

Memastikan satu Work Order dapat:

- membuat SPB yang langsung tertaut ke WO dan unit asal;
- mencatat keputusan **Tidak Memerlukan Part** beserta alasannya;
- membuat permintaan bengkel atau layanan perbaikan;
- memantau PIC dan status tindak lanjut layanan;
- mencatat seluruh aktivitas tersebut pada timeline WO.

## Persiapan

1. Pastikan Apache dan MySQL pada Laragon 2 aktif.
2. Buka `http://serviceplan-bra.test` lalu login sebagai Administrator atau role yang dapat mengubah WO.
3. Masuk ke menu **Work Order**.
4. Pilih satu WO uji berstatus **Open** atau **In Progress**.
5. Catat ID WO dan kode unitnya.

## Skenario 1 — Membuat SPB Langsung dari WO

1. Buka detail WO uji.
2. Klik **Kebutuhan & Layanan**.
3. Klik **Buat SPB dari WO**.
4. Pastikan unit dan Work Order pada draft SPB terpilih otomatis.
5. Tambahkan minimal satu item, isi part number, deskripsi, qty, dan catatan.
6. Klik **Ajukan SPB**.
7. Buka lagi detail WO → **Kebutuhan & Layanan**.

Hasil yang diharapkan:

- SPB berhasil dibuat dan tidak muncul pesan sukses jika database gagal;
- status kebutuhan menjadi **Memerlukan suku cadang**;
- nomor SPB, status, jumlah item, qty, dan catatan terlihat pada WO;
- SPB yang sama terlihat pada menu **Spare Part & Logistik** dan **Approval**;
- timeline WO mencatat kebutuhan part dan nomor SPB.

## Skenario 2 — Tidak Memerlukan Suku Cadang

Gunakan WO lain yang belum memiliki SPB aktif.

1. Buka **Kebutuhan & Layanan**.
2. Isi alasan, misalnya `Penyetelan dilakukan tanpa penggantian komponen`.
3. Klik **Tidak Memerlukan Part**.
4. Tutup dan buka kembali modal.

Hasil yang diharapkan:

- status berubah menjadi **Tidak memerlukan suku cadang**;
- alasan tetap tampil setelah halaman dimuat ulang;
- timeline WO mencatat keputusan tersebut.

Uji validasi:

1. Kosongkan alasan lalu klik tombol yang sama.
2. Sistem harus menolak karena alasan wajib diisi.
3. Pada WO yang memiliki SPB aktif, coba pilih **Tidak Memerlukan Part**.
4. Sistem harus menolak agar keputusan tidak bertentangan dengan SPB aktif.

## Skenario 3 — Membuat Permintaan Layanan

1. Pada modal **Kebutuhan & Layanan**, pilih jenis layanan:
   - Bengkel internal;
   - Bengkel eksternal;
   - Layanan lapangan; atau
   - Vendor spesialis.
2. Pilih prioritas.
3. Isi penyedia/bengkel dan PIC bila sudah diketahui.
4. Isi deskripsi pekerjaan, misalnya `Overhaul cylinder dan pressure test`.
5. Klik **Ajukan Layanan**.

Hasil yang diharapkan:

- nomor permintaan berawalan `SRV-` terbentuk;
- permintaan menampilkan WO, unit, jenis layanan, prioritas, penyedia, PIC, dan status;
- bila PIC langsung diisi, status awal menjadi **Assigned**;
- bila PIC kosong, status awal menjadi **Submitted**;
- timeline WO mencatat pembuatan permintaan layanan.

## Skenario 4 — Tindak Lanjut Layanan

1. Pada kartu layanan, isi atau koreksi PIC.
2. Ubah status sesuai urutan:
   - `Submitted` → `Assigned` atau `In Progress`;
   - `Assigned` → `In Progress`;
   - `In Progress` → `Completed`.
3. Klik **Update** pada setiap perubahan.
4. Muat ulang halaman dan buka modal kembali.

Hasil yang diharapkan:

- status dan PIC tetap tersimpan;
- waktu mulai terisi saat status menjadi **In Progress**;
- waktu selesai terisi saat status menjadi **Completed**;
- layanan selesai tidak lagi menampilkan aksi lanjutan;
- setiap perubahan masuk ke timeline WO.

## Verifikasi Database Laragon 2

Ganti `ID_WO_UJI` dengan ID WO yang diuji.

```sql
SELECT
    wo_id,
    asset_id,
    parts_requirement,
    parts_requirement_note
FROM work_orders
WHERE wo_id = 'ID_WO_UJI';
```

```sql
SELECT
    spb_id,
    wo_id,
    asset_id,
    urgency,
    status,
    notes,
    requested_at
FROM purchase_requests
WHERE wo_id = 'ID_WO_UJI';
```

```sql
SELECT
    service_request_id,
    wo_id,
    asset_id,
    service_type,
    provider_name,
    description,
    priority,
    status,
    assigned_to,
    requested_at,
    started_at,
    completed_at
FROM work_order_service_requests
WHERE wo_id = 'ID_WO_UJI'
ORDER BY requested_at DESC;
```

```sql
SELECT title, description, actor_name, occurred_at
FROM work_order_timeline
WHERE wo_id = 'ID_WO_UJI'
ORDER BY occurred_at, timeline_id;
```

## Checklist Hasil

- [ ] SPB selalu memiliki WO dan unit asal yang benar.
- [ ] Catatan dan urgensi SPB tersimpan di database.
- [ ] Pembuatan SPB mengubah kebutuhan WO menjadi `REQUIRED`.
- [ ] Opsi tanpa part menyimpan alasan sebagai `NOT_REQUIRED`.
- [ ] SPB aktif mencegah keputusan tanpa part yang bertentangan.
- [ ] Permintaan layanan menyimpan jenis, penyedia, deskripsi, prioritas, dan PIC.
- [ ] Status layanan dapat ditindaklanjuti sampai selesai.
- [ ] Waktu mulai dan selesai layanan terisi otomatis.
- [ ] Seluruh perubahan tercatat pada timeline WO.
- [ ] Data UI sama dengan database Laragon 2.

