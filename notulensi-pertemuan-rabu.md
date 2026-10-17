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

### F. Format Tampilan Listing & Manajemen Preventif Maintenance (PM)
Halaman Preventif Maintenance (PM) disepakati untuk disajikan dalam bentuk **listing terstruktur terpadu** yang dilengkapi tombol aksi langsung serta parameter pemantauan servis yang komprehensif:

#### 1. Tombol Aksi Cepat
* **Tombol "Tambah Unit Baru":** Ditambahkan langsung di bagian header tampilan listing Preventif Maintenance, sehingga pengguna/planner dapat mendaftarkan unit ke jadwal perawatan berkala tanpa harus berpindah ke modul Master Asset.

#### 2. Kolom & Parameter Listing Perawatan Unit
Tabel/listing menyajikan detail unit dan status perawatan dengan atribut:
1. **Kode Unit:** Identitas unik aset internal perusahaan (contoh: `DT-00052`, `EXC-00001`).
2. **Nomor Lambung:** Nomor lambung fisik aktual di lapangan (contoh: `BRA-45`, `DZ-00005`).
3. **Plat Kendaraan / Serial Number (SN):** Nomor polisi atau serial number chasis resmi pabrikan.
4. **Tahun Produksi:** Tahun pembuatan/perakitan armada.
5. **Nama Operator / PIC (Listing Waktu):** Nama operator/PIC yang mengoperasikan unit dengan riwayat penugasan/listing jam kerja operasional.
6. **Waktu & Tanggal Terakhir Servis:** Pencatatan presisi jam dan tanggal pelaksanaan servis berkala terakhir kali dilakukan.
7. **Interval Servis:** Konfigurasi batas interval servis berkala sesuai rekomendasi pabrikan (misal: kelipatan per 10.000 KM untuk dump truck/LV, atau per 250 / 500 HM untuk alat berat excavator/dozer).
8. **KM / HM Selanjutnya & Estimasi Tanggal Servis Berikutnya:**
   - Target pembacaan meteran servis berikutnya: `Meteran Servis Terakhir + Interval`.
   - **Formula Proyeksi Tanggal Servis:** Dihitung otomatis berdasarkan rata-rata beban kerja harian (*acuan koefisien jam/jarak kerja*):
     - *Alat Berat:* Estimasi rata-rata kerja harian (contoh acuan: 7 jam/hari).
     - *Dump Truck:* Estimasi jarak tempuh harian (contoh acuan: 150 km/hari).
     - Proyeksi tanggal jatuh tempo = `Tanggal Terakhir Servis + (Sisa HM/KM Menuju Servis / Rata-rata Pemakaian Harian)`.
9. **Histori Tanggal Perawatan Berkelanjutan:**
   - Rekap jejak riwayat servis berurutan:
     - *Tanggal Perawatan 1* (`dd/mm/yyyy`)
     - *Tanggal Perawatan 2* (`dd/mm/yyyy`)
     - *Tanggal Perawatan 3* (`dd/mm/yyyy`), dst.
   - Membentuk log histori siklus perawatan unit secara utuh dari waktu ke waktu.
10. **Dokumentasi Fisik (Galeri Global):**
    - Dokumentasi foto kondisi fisik unit, penggantian filter/oli, dan bagian komponen disajikan dalam bentuk **galeri visual terpadu (*photo gallery*)**, bukan sekadar link tunggal, agar mudah diperiksa secara visual.
11. **Pemberkasan PDF JO Servis Berkala:**
    - Fitur upload dan penautan file PDF pemberkasan dari Job Order (JO) Servis Berkala sebagai dokumen acuan teknis pelaksanaan servis dan arsip digital resmi.

### G. Validasi Formula KPI Produktivitas
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
| **Brian & Tim Dev** | Membangun tampilan listing Preventif Maintenance (PM) lengkap dengan tombol Tambah Unit, koefisien estimasi tanggal servis (jam/km harian), linimasa histori tanggal perawatan berurutan, galeri dokumentasi foto, dan berkas PDF JO servis berkala. | Segera |
| **Brian & Tim Dev** | Melakukan audit dan verifikasi kebenaran rumus KPI produktivitas armada. | Prioritas |
| **Brian** | Menjadwalkan sesi sinkronisasi tatap muka dengan Bu Widya untuk finalisasi modul WO & JO. | Menunggu Jadwal |
| **Peserta Rapat** | Mengirimkan tangkapan layar (*screenshot*) hasil diskusi rapat ke grup WhatsApp sebagai referensi bersama tim. | Selesai |
