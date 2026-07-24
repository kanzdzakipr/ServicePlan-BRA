# User Roles & Akses
Panduan ini mengatur matriks kewenangan per peran (role) dalam sistem sesuai dokumen BPMN arsitektur.

## 1. Admin (Admin Asset / Finance)
**Aksi Utama:**
- Input/ubah data master (aset, user, lokasi).
- Input dokumen awal, mutasi.
- Input manual HM/KM (jika tidak dari lapangan).
- Cetak laporan.
**Kontrol/Batasan:** 
- Tidak boleh menyetujui transaksi (approval) miliknya sendiri.

## 2. Planner
**Aksi Utama:**
- Membuat rencana PM, penjadwalan.
- Perencanaan WO (Work Order planning), estimasi waktu dan parts.
- Analisis due/overdue meter.
**Kontrol/Batasan:**
- Tidak dapat menutup WO tanpa verifikasi supervisi.

## 3. Mekanik
**Aksi Utama:**
- Menerima pekerjaan dari WO.
- Memulai (Start), menunda (Pause), dan menyelesaikan (End) pekerjaan/log waktu.
- Mengisi checklist, diagnosis.
- Mengunggah foto before-after (pekerjaan dan hasil test).
**Kontrol/Batasan:**
- Tidak bisa mulai bekerja (Start) tanpa WO yang *approved*.
- Tidak bisa memutuskan Return to Work (RTW) sendiri (harus diverifikasi supervisor).

## 4. Logistik (Inventory / Procurement)
**Aksi Utama:**
- Cek ketersediaan stok, reservasi parts.
- Proses issuing / return part ke WO.
**Kontrol/Batasan:**
- *(Sebagian besar fungsi akan dibangun pasca-MVP, namun pada MVP peran ini disiapkan di RBAC)*. Issue part wajib menunjuk ke referensi WO/PM yang sah.

## 5. Supervisi / Foreman
**Aksi Utama:**
- Validasi inspeksi dan klasifikasi kerusakan.
- Melakukan assign WO ke mekanik spesifik.
- Memverifikasi pekerjaan mekanik selesai dan memutuskan Return to Work (RTW).
**Kontrol/Batasan:**
- Approval dibatasi oleh batas kewenangan.

## 6. Manager Equipment
**Aksi Utama:**
- Menentukan prioritas operasional.
- Approval biaya.
- Melakukan Reopen / Void transaksi yang terkunci (melalui audit trail).
- Review KPI, keputusan downtime/aset.
**Kontrol/Batasan:**
- Memiliki akses dashboard lintas lokasi.

## 7. General Manager
**Aksi Utama:**
- Executive review.
- Approval transaksi bernilai tinggi atau investasi/disposal (high risk).
**Kontrol/Batasan:**
- Sifat akses cenderung *read-heavy* (melihat laporan/dashboard) dan terfokus pada approval kritis.

## 8. Operator / Pelapor
**Aksi Utama:**
- Melakukan inspeksi awal (Pre-trip).
- Melaporkan keluhan / breakdown dengan foto/video awal.
- Input HM/KM harian di lapangan.
**Kontrol/Batasan:**
- Hanya dapat melihat/memproses unit dan area yang ditugaskan kepadanya.
