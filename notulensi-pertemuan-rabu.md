# Notulensi Pertemuan Pembahasan Demo & Masukan Sistem Manajemen Equipment
**PT Bina Rekayasa Anugerah**

* **Hari / Tanggal:** Rabu
* **Agenda:** Demo Aplikasi & Pengumpulan Masukan Fitur Manajemen Equipment
* **Fokus Pembahasan:** Work Order (WO) & Job Order (JO), Preventive Maintenance (PM), dan Produktivitas Alat

---

## 1. Hasil Utama

1. **Evaluasi Demo Aplikasi:**
   Pertemuan difokuskan pada demo langsung dan penyerapan masukan operasional lapangan untuk sistem manajemen armada equipment PT Bina Rekayasa Anugerah.
2. **Prioritas Pengembangan:**
   Fokus iterasi saat ini diprioritaskan pada penyempurnaan alur **Work Order (WO)**, **Preventif Maintenance (PM)**, dan **Produktivitas Unit Alat Berat**.
3. **Bagian yang Ditunda ke Iterasi Berikutnya:**
   Modul biaya, telematika satelit, dan ekspor laporan lanjutan belum disentuh pada sesi ini dan dijadwalkan untuk diselesaikan pada iterasi berikutnya.
4. **Sinkronisasi Khusus WO & JO:**
   Bu Widya merekomendasikan sesi sinkronisasi kerja langsung bersama Brian guna mempercepat finalisasi skema Work Order dan Job Order.

---

## 2. Keputusan yang Diambil

### A. Perluasan Form Work Order (WO)
Formulir Work Order disepakati untuk diperluas dengan penambahan atribut operasional lengkap:
* **Identitas Unit:** Jenis unit, nomor lambung, nomor polisi, dan meteran HM/KM terkini.
* **Insiden Trouble:** Tanggal trouble, lokasi kejadian, dan uraian detail trouble/kerusakan.
* **Pelaksana & Penanggung Jawab:** PIC mekanik (mendukung multi-pilih).
* **Waktu Penanganan:** Waktu mulai kerja dan waktu selesai (otomatis terisi saat status diubah menjadi *Close*).
* **Status Penanganan:** Pilihan status (*Breakdown*, *RFU*, atau *Preventive*).
* **Mitigasi & Estimasi:** Tindakan mitigasi perbaikan serta estimasi waktu selesai pengerjaan.
* **Dokumentasi:** Fitur upload foto kondisi unit.

### B. Mekanisme Kebutuhan Spare Part pada WO
* Penginputan kebutuhan suku cadang di WO disajikan dalam bentuk **uraian manual (teks bebas)**, bukan ceklis tetap, untuk mengakomodasi perbedaan terminologi antara mekanik lapangan dan divisi teknik.
* Uraian kebutuhan spare part ini langsung ditautkan (*linked*) ke modul logistik untuk pengecekan ketersediaan stok fisik gudang.

### C. Input Multi-PIC Mekanik (User Interface Mobile-Friendly)
* Penugasan mekanik dapat diisi lebih dari satu personil (multi-PIC).
* Mekanisme input menggunakan pola **ketik-nama-lalu-muncul** (*token/tagging search*, mirip input alamat email) agar antarmuka tetap ringkas dan tidak membebani layar perangkat mobile.

### D. Unifikasi Work Order (WO) dan Job Order (JO)
* Job Order (JO) disepakati memiliki struktur field dan alur pengerjaan yang identik dengan Work Order (WO), sehingga **tidak perlu dibuatkan fitur/modul terpisah**.

### E. Pencarian Multi-Parameter & Rename Nomor Lambung
* Pencarian data unit alat berat harus mendukung kombinasi 3 parameter sekaligus secara simultan:
  1. Kode Aset
  2. Nomor Lambung
  3. Nomor Seri (*Serial Number* / SN)
* Disediakan opsi **rename nomor lambung secara manual**, mengingat nomor lambung di lapangan sering diperbarui/diubah tanpa pemberitahuan sebelumnya ke sistem.

### F. Format Tampilan List Preventif Maintenance (PM)
Halaman Preventif Maintenance akan menyajikan daftar unit dalam format tabel/list terstruktur dengan kolom:
1. Kode Unit
2. Nomor Lambung
3. Nomor Polisi / Nomor Seri (SN)
4. Tahun Produksi
5. Nama Operator
6. Waktu Servis Terakhir
7. Interval Servis (HM/KM)
8. Estimasi Tanggal Servis Berikutnya
9. Histori Tanggal Perawatan Sebelumnya
10. Dokumentasi Foto
11. Upload File PDF (JO Servis Berkala)

### G. Penambahan Unit Baru di Halaman PM
* Tombol **"Tambah Unit Baru"** ditambahkan langsung pada tampilan Preventif Maintenance, sehingga pengguna tidak perlu berpindah ke dashboard master aset.

### H. Validasi Formula KPI Produktivitas
* Fitur ekspor laporan produktivitas ditahan terlebih dahulu; seluruh rumus KPI (Availability, Downtime, dll.) harus dipastikan valid dan diverifikasi akurasinya sebelum dijadikan acuan pengambilan keputusan manajemen.

---

## 3. Poin Belum Dikonfirmasi / Pending

1. **Integrasi Request Spare Part (WO → Logistik):** Masih dalam tahap pengembangan dan akan didemokan pada pertemuan berikutnya.
2. **Mekanisme Approval / Waiting Job:** Alur persetujuan atau status *waiting job* pada tiket WO belum diimplementasikan secara eksplisit di sistem.
3. **Modul Biaya:** Tidak ada perubahan dari versi sebelumnya; akan dibahas kembali (*re-call*) agar tidak terlewat.
4. **Tampilan & Hak Akses Logistik:** Kebutuhan antarmuka logistik belum dapat dikonfirmasi karena Mas Fadli berhalangan hadir pada sesi ini.
5. **Agenda Sesi Brian – Bu Widya:** Penjadwalan pertemuan sinkronisasi tatap muka perlu segera ditetapkan.

---

## 4. Pertanyaan Terbuka

| No | Pertanyaan / Isu | Keterangan & Tindak Lanjut |
| :---: | :--- | :--- |
| **1** | Otoritas perubahan nomor lambung | Siapa pihak yang berwenang melakukan *rename* nomor lambung di sistem: apakah *self-edit* oleh user operasional atau wajib melalui otorisasi Aset Manajer? |
| **2** | Pencatatan Helper Mekanik | Apakah helper mekanik wajib dicatat sebagai PIC mandiri tersendiri, atau cukup digabungkan di bawah tanggung jawab Mekanik Senior? |
| **3** | Dashboard Monitoring Efisiensi BBM | Format penyajian visual dan metrik efisiensi konsumsi bahan bakar (BBM) masih menunggu konfirmasi dari Divisi Project. |

---

## 5. Action Items & Rencana Tindak Lanjut

| PIC | Tugas / Tindak Lanjut | Target / Status |
| :--- | :--- | :---: |
| **Brian & Tim Dev** | Memperluas form WO dan JO dengan atribut lengkap sesuai masukan Fajar dan Bu Widya. | Segera |
| **Brian & Tim Dev** | Mengembangkan komponen input multi-PIC mekanik dengan mekanisme *type-to-suggest* (ketik nama lalu muncul). | Segera |
| **Brian & Tim Dev** | Menambahkan fitur pencarian unit 3 parameter (kode aset, nomor lambung, serial number) serta fungsi rename nomor lambung. | Segera |
| **Brian & Tim Dev** | Membangun tampilan list Preventif Maintenance (PM) lengkap dengan kolom histori, estimasi servis, upload foto, dan dokumen PDF. | Segera |
| **Brian & Tim Dev** | Melakukan audit dan verifikasi kebenaran rumus KPI produktivitas armada. | Prioritas |
| **Brian** | Menjadwalkan sesi sinkronisasi tatap muka dengan Bu Widya untuk finalisasi modul WO & JO. | Menunggu Jadwal |
| **Peserta Rapat** | Mengirimkan tangkapan layar (*screenshot*) hasil diskusi rapat ke grup WhatsApp sebagai referensi bersama tim. | Selesai |
