# Panduan Pengujian Menyeluruh Role dan Fungsi FleetMonitor

## Informasi dokumen

| Item | Nilai |
|---|---|
| Aplikasi | ServicePlan-BRA / FleetMonitor |
| Environment | Laragon lokal |
| URL login | `http://localhost/ServicePlan-BRA/` |
| URL dashboard | `http://localhost/ServicePlan-BRA/dashboard.php` |
| Database | `u646470441_ServicePlanBRA` |
| Jenis pengujian | Functional, RBAC, integrasi, keamanan, responsif, dan regresi |
| Status awal | Belum diuji |

> Gunakan dokumen ini sebagai checklist. Ganti `[ ]` menjadi `[x]` setelah langkah lulus dan lampirkan bukti pada tabel hasil pengujian.

## 1. Tujuan

Pengujian ini memastikan bahwa:

- setiap role hanya melihat menu dan tombol yang diizinkan;
- operasi Read, Create, Update, Approve, dan Override berjalan sesuai matriks RBAC;
- perubahan data tersimpan di database dan tetap ada setelah halaman dimuat ulang;
- alur antar-modul dan antar-role berjalan konsisten;
- akses tanpa autentikasi, akses lintas lokasi, CSRF, dan akses API yang tidak berizin ditolak;
- tampilan tetap dapat digunakan pada desktop dan perangkat mobile.

## 2. Definisi kode akses

| Kode | Arti | Pengujian minimum |
|---|---|---|
| `R` | Read/View | Buka halaman, tabel, detail, pencarian, filter, dan tab |
| `C` | Create | Buat satu record baru berawalan `QA-` |
| `U` | Update/Edit | Edit record, muat ulang halaman, dan verifikasi perubahan |
| `A` | Approve/Release | Approve, reject, release, atau tutup proses |
| `O` | Override/Admin | Archive, delete, override, atau tindakan administratif |
| `-` | No Access | Menu/tombol tersembunyi dan API mengembalikan `403` |

## 3. Aturan data pengujian

- Gunakan awalan `QA-` untuk seluruh data buatan tester.
- Contoh asset ID: `QA-ASSET-001`.
- Contoh deskripsi WO: `QA - pengujian alur work order`.
- Jangan mengubah atau menghapus data produksi yang sudah tersedia pada dump.
- Gunakan satu aset di lokasi akun dan satu aset dari lokasi lain untuk menguji pembatasan lokasi.
- Simpan screenshot sebelum dan sesudah perubahan status.
- Jangan menjalankan `scripts/sync_to_laragon.ps1` di tengah pengujian karena script mengimpor ulang database dan mereset password lokal.

## 4. Akun pengujian lokal

Password berikut hanya boleh digunakan pada Laragon lokal.

| No. | Role | Username | Password |
|---:|---|---|---|
| 1 | Administrator | `admin` | `admin123` |
| 2 | Equipment Manager | `dany_agung` | `dany_agung123` |
| 3 | Maintenance Planner | `martin_planner` | `martin_planner123` |
| 4 | Mekanik Senior | `rahmad_k` | `rahmad_k123` |
| 5 | Mekanik Junior / Helper | `mekanik` | `mekanik123` |
| 6 | Welder / Fabrikator | `welder` | `welder123` |
| 7 | Inspector K3L / Safety | `safety` | `safety123` |
| 8 | Logistic Head | `guswan_arizal` | `guswan_arizal123` |
| 9 | HRD Manager | `rani_simanungkalit` | `rani_simanungkalit123` |
| 10 | Asset Manager | `widya_apriani` | `widya_apriani123` |

## 5. Prasyarat pengujian

- [ ] Laragon berjalan.
- [ ] Apache berjalan.
- [ ] MySQL berjalan.
- [ ] URL login menghasilkan HTTP `200`.
- [ ] Database `u646470441_ServicePlanBRA` dapat diakses.
- [ ] Browser DevTools bagian Console dan Network dibuka.
- [ ] Cache dinonaktifkan selama DevTools terbuka.
- [ ] Folder bukti screenshot telah disiapkan.
- [ ] Tester memahami bahwa setiap role harus diuji pada sesi browser terpisah.

## 6. Matriks akses role

### 6.1 Modul 1–8

| Role | Dashboard | Monitoring | Asset | Inspeksi | WO | PM | Logistik | Condition |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Administrator | RCUO | RCUO | RCUO | RCUO | RCUO | RCUO | RCUO | RCUO |
| Equipment Manager | RUA | RUA | RUA | RUA | RCUA | RCUA | RA | RUA |
| Maintenance Planner | RU | RU | RU | RU | RCUA | RCUA | RCU | RCU |
| Mekanik Senior | R | R | R | RC | RCU | R | RC | R |
| Mekanik Junior / Helper | R | R | R | RC | RU | R | - | - |
| Welder / Fabrikator | R | R | R | RC | RU | - | - | - |
| Inspector K3L / Safety | R | RU | R | RCUA | RC | - | - | RCU |
| Logistic Head | R | R | R | - | R | R | RCUA | R |
| HRD Manager | R | - | - | - | - | - | - | - |
| Asset Manager | R | RUA | RCUA | - | R | - | - | RU |

### 6.2 Modul 9–16

| Role | Fuel | Produktivitas | Biaya | People | HSE | Reports | Approval | Settings |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Administrator | RCUO | RCUO | RCUO | RCUO | RCUO | RCUO | RAO | RCUO |
| Equipment Manager | RUA | RUA | RUA | RCUA | RCUA | RCU | RA | - |
| Maintenance Planner | RU | RU | - | RU | R | RCU | R | - |
| Mekanik Senior | R | R | - | R | R | R | - | - |
| Mekanik Junior / Helper | - | R | - | R | - | R | - | - |
| Welder / Fabrikator | - | R | - | R | - | R | - | - |
| Inspector K3L / Safety | RCU | R | - | - | RCUA | RCU | - | - |
| Logistic Head | RCU | R | - | - | - | RCU | RA | - |
| HRD Manager | - | - | - | RCUA | - | RCU | RA | - |
| Asset Manager | R | RUA | RCUA | R | RA | RCU | RA | - |

> Archive dan Unit Properties harus diuji terpisah. Tampilan frontend memperlakukan Archive sebagai utility, tetapi API tetap dapat membatasi akses berdasarkan role.

## 7. Siklus pengujian standar setiap role

Ulangi prosedur berikut untuk seluruh akun pada bagian 4.

1. [ ] Buka jendela Incognito/InPrivate baru.
2. [ ] Login menggunakan kredensial role yang diuji.
3. [ ] Pastikan nama pengguna dan primary role pada profil benar.
4. [ ] Muat ulang dashboard dan pastikan sesi tetap aktif.
5. [ ] Catat seluruh menu yang terlihat.
6. [ ] Bandingkan menu dengan matriks akses.
7. [ ] Jalankan aksi `R`, `C`, `U`, `A`, dan `O` sesuai izin role.
8. [ ] Untuk izin `-`, pastikan menu dan tombol tidak terlihat.
9. [ ] Coba akses terlarang melalui API; hasil harus HTTP `403`.
10. [ ] Pastikan akses terlarang tidak mengubah database.
11. [ ] Periksa Console; tidak boleh ada error JavaScript.
12. [ ] Periksa Network; tidak boleh ada HTTP `500`.
13. [ ] Logout.
14. [ ] Tekan tombol Back; dashboard tidak boleh dapat digunakan.
15. [ ] Tutup seluruh tab sesi tersebut sebelum menguji role berikutnya.

## 8. Checklist autentikasi dan sesi

- [ ] Login dengan username dan password benar berhasil.
- [ ] Username tidak dikenal menghasilkan pesan generik.
- [ ] Password salah menghasilkan pesan generik.
- [ ] Field username kosong ditolak.
- [ ] Field password kosong ditolak.
- [ ] Refresh dashboard tidak menghilangkan sesi aktif.
- [ ] Logout menghapus sesi.
- [ ] API setelah logout mengembalikan `401`.
- [ ] Dashboard tanpa sesi mengarahkan pengguna ke login.
- [ ] Lima percobaan login gagal mengaktifkan rate limit `429`.
- [ ] Session cookie menggunakan `HttpOnly` dan `SameSite`.
- [ ] Token CSRF berubah setelah sesi baru dibuat.

> Jalankan pengujian rate limit paling akhir menggunakan sesi Incognito khusus agar tidak mengganggu pengujian role lainnya.

## 9. Checklist fungsi per modul

### 9.1 Executive Dashboard

- [ ] Total Fleet sesuai dengan data aset.
- [ ] KPI Ready/Operating sesuai filter status.
- [ ] KPI Standby sesuai filter status.
- [ ] KPI Breakdown sesuai filter status.
- [ ] KPI Inspeksi/PM sesuai filter status.
- [ ] Detail setiap KPI membuka tabulasi yang benar.
- [ ] Filter peta berdasarkan status bekerja.
- [ ] Filter peta berdasarkan lokasi bekerja.
- [ ] Filter peta berdasarkan waktu bekerja.
- [ ] Global project filter memperbarui data terkait.
- [ ] Pencarian global menemukan asset ID dan WO.
- [ ] Notifikasi dapat difilter menjadi Semua dan Belum Dibaca.
- [ ] Tandai Semua Dibaca memperbarui badge notifikasi.
- [ ] Tautan Asset 360°, P2H, WO, dan Condition dari unit bekerja.

### 9.2 Monitoring Unit

- [ ] Filter status unit bekerja.
- [ ] Detail unit menampilkan data yang benar.
- [ ] Perubahan status hanya tersedia untuk role berizin `U`.
- [ ] Perubahan status tersimpan setelah refresh.
- [ ] Tautan dari unit ke P2H bekerja.
- [ ] Tautan dari unit ke WO bekerja.
- [ ] User terbatas lokasi tidak melihat aset lokasi lain.
- [ ] Status unit konsisten dengan Dashboard dan Master Asset.

### 9.3 Master Asset dan Unit Properties

- [ ] Filter kategori bekerja.
- [ ] Filter lokasi bekerja.
- [ ] Filter status bekerja.
- [ ] Role `C` dapat membuat `QA-ASSET-001`.
- [ ] Validasi field wajib mencegah penyimpanan data kosong.
- [ ] Asset 360° menampilkan identitas unit yang dipilih.
- [ ] Unit Properties menampilkan unit yang dipilih.
- [ ] Selector Unit Properties mengganti data unit.
- [ ] Fungsi print Unit Properties bekerja.
- [ ] Role `U` dapat memperbarui metadata.
- [ ] Role `U` dapat memperbarui lokasi/status.
- [ ] Perubahan tetap ada setelah refresh.
- [ ] Histori perubahan/mutasi tercatat.
- [ ] Role `O` dapat mengarsipkan data `QA-*`.
- [ ] Role read-only tidak melihat tombol Simpan atau Arsip.

### 9.4 Inspeksi dan P2H

- [ ] Unit dan template inspeksi dapat dipilih.
- [ ] Form inspeksi PASS dapat disimpan.
- [ ] Form dengan temuan FAIL dapat disimpan.
- [ ] Temuan kritis ditandai dengan benar.
- [ ] Upload bukti menerima tipe file yang diizinkan.
- [ ] Detail inspeksi dapat dibuka kembali.
- [ ] Histori inspeksi menampilkan record baru.
- [ ] Temuan kritis dapat diteruskan menjadi WO.
- [ ] Role `A` dapat approve inspeksi.
- [ ] Field wajib kosong ditolak.
- [ ] Role tanpa akses tidak dapat membuka API inspeksi.

### 9.5 Work Order

- [ ] Tampilan Kanban bekerja.
- [ ] Tampilan tabel bekerja.
- [ ] Filter status bekerja.
- [ ] Filter prioritas bekerja.
- [ ] Role `C` dapat membuat WO untuk `QA-ASSET-001`.
- [ ] Prioritas, tipe, masalah, dan downtime tersimpan.
- [ ] Mekanik dapat ditugaskan.
- [ ] Perubahan `Open` ke `In Progress` bekerja.
- [ ] Perubahan `In Progress` ke `Closed` bekerja sesuai approval.
- [ ] Penutupan oleh role tanpa `A` ditolak.
- [ ] Detail WO membuka aset yang benar.
- [ ] Permintaan part/SPB dari WO membawa referensi WO yang benar.
- [ ] Status aset konsisten dengan status WO.
- [ ] Akses WO lintas lokasi ditolak.

### 9.6 Preventive Maintenance

- [ ] Role `C` dapat membuat rencana PM.
- [ ] Pemilihan unit bekerja.
- [ ] Autofill daftar part bekerja.
- [ ] Interval HM/KM tervalidasi.
- [ ] Due date tersimpan dengan benar.
- [ ] Status Upcoming tampil benar.
- [ ] Status Due tampil benar.
- [ ] Status Overdue tampil benar.
- [ ] Role `U` dapat mengubah rencana.
- [ ] Role `A` dapat approve rencana.
- [ ] PM terhubung dengan stok part dan WO.
- [ ] Nilai HM/KM tidak valid ditolak.

### 9.7 Spare Part dan Logistik

- [ ] Seluruh tab katalog/ledger dapat dibuka.
- [ ] Pencarian part number bekerja.
- [ ] Permintaan part dari WO dapat dibuat.
- [ ] Stok masuk memperbarui saldo.
- [ ] Stok keluar memperbarui saldo.
- [ ] Stok tidak dapat menjadi negatif.
- [ ] Upload XLS/XLSX/CSV bekerja.
- [ ] Upload gambar bekerja.
- [ ] Upload PDF bekerja.
- [ ] Role `A` dapat approve SPB.
- [ ] Reject SPB mewajibkan alasan.
- [ ] Status SPB sinkron dengan WO.
- [ ] Status SPB sinkron dengan Approval Inbox.
- [ ] Role dengan akses `RA` tidak dapat membuat atau mengubah stok.

### 9.8 Condition Monitoring

- [ ] Unit dapat dipilih langsung.
- [ ] Kondisi ban dapat dibuka dan diperbarui sesuai izin.
- [ ] Kondisi aki dapat dibuka dan diperbarui sesuai izin.
- [ ] Data grease dapat dibuka dan diperbarui sesuai izin.
- [ ] Data cutting bit dapat dibuka dan diperbarui sesuai izin.
- [ ] Indikator hijau/kuning/merah tampil benar.
- [ ] Kondisi merah dapat diteruskan menjadi WO.
- [ ] Histori kondisi tercatat.
- [ ] Role tanpa akses tidak dapat membuka atau mengubah data.

### 9.9 Fuel Management

- [ ] Filter periode bekerja.
- [ ] Filter lokasi bekerja.
- [ ] Filter kategori bekerja.
- [ ] Filter status bekerja.
- [ ] Input transaksi BBM dapat disimpan.
- [ ] HM/KM wajib tervalidasi.
- [ ] Volume BBM wajib tervalidasi.
- [ ] Stock sounding dapat disimpan.
- [ ] Anomali konsumsi terdeteksi.
- [ ] Transaksi dapat diverifikasi oleh role berizin.
- [ ] WO dapat dibuat dari anomali.
- [ ] Role read-only tidak melihat tombol Input atau Verifikasi.

### 9.10 Produktivitas

- [ ] Data SMR tampil.
- [ ] Utilization rate dihitung dan ditampilkan.
- [ ] Idle ratio dihitung dan ditampilkan.
- [ ] Data standby tampil.
- [ ] Filter periode bekerja.
- [ ] Filter lokasi bekerja.
- [ ] Detail unit dapat dibuka.
- [ ] Ringkasan sesuai dengan tabel detail.
- [ ] Update/approval hanya tersedia untuk role berizin.

### 9.11 Biaya

- [ ] Menu hanya tampil untuk Administrator, Equipment Manager, dan Asset Manager.
- [ ] Input biaya `QA-*` dapat disimpan.
- [ ] Filter tahun bekerja.
- [ ] Filter lokasi bekerja.
- [ ] Filter kategori bekerja.
- [ ] Total biaya sesuai tabel.
- [ ] Grafik sesuai total biaya.
- [ ] Role `U` dapat mengubah record biaya.
- [ ] Role `A` dapat approve biaya.
- [ ] Export laporan biaya bekerja.
- [ ] Role lain menerima `403` saat mengakses API biaya.

### 9.12 People dan KPI

- [ ] Leaderboard mekanik tampil.
- [ ] Total jam kerja tampil.
- [ ] Data SPL tampil.
- [ ] Scorecard tampil.
- [ ] Evaluasi personel tampil.
- [ ] Role `C` dapat menambah record KPI/SPL.
- [ ] Role `U` dapat memperbarui record.
- [ ] Role `A` dapat approve record.
- [ ] Jam kerja konsisten dengan WO.
- [ ] Data sensitif tidak tampil pada role tanpa izin.

### 9.13 HSE dan Accident

- [ ] Role `C` dapat membuat insiden untuk aset `QA-*`.
- [ ] Kronologi tersimpan.
- [ ] Lokasi kejadian tersimpan.
- [ ] Identitas pengemudi tersimpan.
- [ ] Estimasi kerusakan tersimpan.
- [ ] Bukti insiden dapat diunggah.
- [ ] Status aset berubah menjadi `ACCIDENT_HOLD`.
- [ ] Role biasa tidak dapat melepaskan hold.
- [ ] Role `A` dapat approve/release hold.
- [ ] Audit trail tercatat.
- [ ] Status aset kembali konsisten setelah release.

### 9.14 Laporan dan Form

- [ ] Template laporan dapat dipilih.
- [ ] Autofill aset/WO bekerja.
- [ ] Draft dapat disimpan.
- [ ] Bukti per item dapat diunggah.
- [ ] Histori laporan dapat dibuka.
- [ ] Laporan dapat diduplikasi.
- [ ] Impor DOCX bekerja.
- [ ] Impor PDF bekerja.
- [ ] Impor XLS/XLSX/CSV bekerja.
- [ ] Impor Markdown bekerja.
- [ ] Print bekerja.
- [ ] Export bekerja.
- [ ] Data biaya/SDM hanya tersedia untuk role berwenang.

### 9.15 Approval Inbox

- [ ] WO closing masuk ke inbox approver.
- [ ] SPB masuk ke inbox approver.
- [ ] HSE release masuk ke inbox approver.
- [ ] BAST/mutasi masuk ke inbox approver.
- [ ] Approve memperbarui status modul sumber.
- [ ] Reject mewajibkan alasan.
- [ ] Reject memperbarui status modul sumber.
- [ ] Notifikasi pihak pengaju diperbarui.
- [ ] Role tanpa `A` tidak melihat tombol keputusan.

### 9.16 Pengaturan

- [ ] Hanya Administrator yang dapat membuka menu.
- [ ] Daftar pengguna tampil.
- [ ] Pengaturan lokasi tampil.
- [ ] Matriks RBAC tampil.
- [ ] Perubahan permission dapat disimpan.
- [ ] Permission baru berlaku setelah role target login ulang.
- [ ] Role non-Administrator menerima `403` melalui API langsung.
- [ ] Konfigurasi uji dikembalikan setelah pengujian.

### 9.17 Archive

- [ ] Tab Asset dapat dibuka sesuai permission API.
- [ ] Tab P2H dapat dibuka sesuai permission API.
- [ ] Tab Accident dapat dibuka sesuai permission API.
- [ ] Pencarian archive bekerja.
- [ ] Detail archive dapat dibuka.
- [ ] Download dokumen bekerja.
- [ ] Role tanpa `archive.read` ditolak dengan `403`.
- [ ] Role yang memiliki `archive.write` dapat menjalankan aksi tulis yang tersedia.

## 10. Fokus pengujian per role

### Administrator

- [ ] Seluruh modul dan seluruh aksi administratif dapat digunakan.
- [ ] Override/archive hanya dilakukan pada data `QA-*`.
- [ ] Pengelolaan user, lokasi, dan RBAC bekerja.

### Equipment Manager

- [ ] Approval WO, PM, SPB, HSE, dan People bekerja.
- [ ] Logistik hanya mengizinkan Read dan Approve.
- [ ] Pengaturan ditolak.

### Maintenance Planner

- [ ] CRUD dan approval WO/PM bekerja.
- [ ] Create/Update Logistik, Condition, dan Reports bekerja.
- [ ] Biaya dan Pengaturan ditolak.

### Mekanik Senior

- [ ] Create P2H, Create/Update WO, dan Create permintaan part bekerja.
- [ ] Biaya, Approval, dan Pengaturan ditolak.

### Mekanik Junior / Helper

- [ ] Create P2H dan Update WO yang ditugaskan bekerja.
- [ ] Create WO, Logistik, Condition, Fuel, HSE, Biaya, dan Approval ditolak.

### Welder / Fabrikator

- [ ] Create P2H dan Update WO pekerjaan welding bekerja.
- [ ] PM, Logistik, Condition, Fuel, HSE, Biaya, dan Approval ditolak.

### Inspector K3L / Safety

- [ ] CRUD/Approve P2H dan HSE bekerja.
- [ ] Create WO dari temuan kritis bekerja.
- [ ] CRUD Condition/Fuel/Reports bekerja.
- [ ] People, Biaya, PM, Logistik, dan Approval ditolak.

### Logistic Head

- [ ] CRUD/Approve Logistik bekerja.
- [ ] CRUD Fuel dan Reports bekerja.
- [ ] Approval SPB bekerja.
- [ ] Inspeksi, Biaya, People, HSE, dan Pengaturan ditolak.

### HRD Manager

- [ ] CRUD/Approve People dan Reports bekerja.
- [ ] Approval HRD bekerja.
- [ ] Seluruh modul operasional dan Pengaturan ditolak.

### Asset Manager

- [ ] CRUD/Approve Asset dan Biaya bekerja.
- [ ] Monitoring, Condition, Produktivitas, HSE, Reports, dan Approval bekerja sesuai matriks.
- [ ] Inspeksi, PM, Logistik, dan Pengaturan ditolak.

## 11. Pengujian end-to-end lintas role

1. [ ] Asset Manager membuat `QA-ASSET-001`.
2. [ ] Inspector membuat P2H dengan temuan kritis.
3. [ ] Maintenance Planner membuat WO dari temuan tersebut.
4. [ ] Maintenance Planner membuat rencana PM.
5. [ ] Maintenance Planner atau Mekanik Senior membuat permintaan part.
6. [ ] Logistic Head memproses dan menyetujui SPB.
7. [ ] Mekanik Senior menerima WO dan mengubah status ke `In Progress`.
8. [ ] Mekanik mengisi progres serta menyelesaikan pekerjaan teknis.
9. [ ] Equipment Manager menyetujui penutupan WO.
10. [ ] Inspector melakukan inspeksi akhir.
11. [ ] Asset Manager mengembalikan unit ke Ready/Operating.
12. [ ] HRD memeriksa jam kerja/SPL.
13. [ ] Administrator atau Planner membuat laporan akhir.
14. [ ] Notifikasi setiap pihak tampil pada waktu yang benar.
15. [ ] Status aset, P2H, WO, PM, SPB, dan laporan saling konsisten.

## 12. Pengujian keamanan

- [ ] API tanpa login mengembalikan `401`.
- [ ] Aksi tulis tanpa token CSRF mengembalikan `419`.
- [ ] Aksi di luar permission role mengembalikan `403`.
- [ ] Akses objek lokasi lain tidak membocorkan data.
- [ ] API setelah logout mengembalikan `401`.
- [ ] Origin asing ditolak.
- [ ] Input XSS ditampilkan sebagai teks dan tidak dieksekusi.
- [ ] SQL injection tidak melewati login atau filter.
- [ ] Upload ekstensi yang tidak diizinkan ditolak.
- [ ] `dashboard.view.php` tidak dapat diakses langsung.
- [ ] Pesan error tidak membocorkan stack trace, query SQL, atau kredensial.

### Menjalankan automated security suite

```powershell
$env:SECURITY_TEST_BASE_URL='http://localhost/ServicePlan-BRA'
$env:SECURITY_TEST_LIMITED_USER='mekanik'
$env:SECURITY_TEST_LIMITED_PASSWORD='mekanik123'
$env:SECURITY_TEST_CROSS_SCOPE_ASSET_ID='DT-00001'
powershell -ExecutionPolicy Bypass -File tests/run_security_tests.ps1
```

## 13. Pengujian responsif

Uji minimal pada lebar berikut:

- [ ] 320 px
- [ ] 360 px
- [ ] 375 px
- [ ] 390 px
- [ ] 430 px
- [ ] 768 px
- [ ] 1024 px
- [ ] 1366 px

Kriteria:

- [ ] Tidak ada horizontal overflow pada body.
- [ ] Tabel memiliki area scroll lokal jika diperlukan.
- [ ] Sidebar dapat dibuka dan ditutup.
- [ ] Modal tidak keluar viewport.
- [ ] Tombol dapat ditekan dan memiliki label aksesibel.
- [ ] Input tidak tertutup keyboard mobile.
- [ ] Grafik dan peta menyesuaikan ukuran viewport.

### Menjalankan audit mobile otomatis

```powershell
powershell -ExecutionPolicy Bypass -File tests/mobile_layout_audit.ps1 `
  -BaseUrl 'http://localhost/ServicePlan-BRA' `
  -Username admin `
  -Password admin123
```

## 14. Area risiko RBAC yang wajib diperiksa

- Tabel `permissions` dan `role_permissions` pada database lokal masih dapat kosong sehingga backend menggunakan permission fallback.
- Equipment Manager dan Asset Manager memperoleh akses server yang sangat luas pada fallback saat ini.
- Archive selalu dapat terlihat pada UI, tetapi permission API berbeda antar-role.
- Matriks UI dan permission API tidak sepenuhnya identik.

Jika tombol tersembunyi tetapi API tetap menerima aksi terlarang, catat sebagai **FAIL — RBAC/Security**.

## 15. Format hasil pengujian

| Test ID | Tanggal | Tester | Role | Modul | Fungsi | Data uji | Expected | Actual | Status | Bukti/Defect ID |
|---|---|---|---|---|---|---|---|---|---|---|
| QA-001 |  |  |  |  |  |  |  |  | NOT RUN |  |
| QA-002 |  |  |  |  |  |  |  |  | NOT RUN |  |
| QA-003 |  |  |  |  |  |  |  |  | NOT RUN |  |

Status yang digunakan:

- `PASS`
- `FAIL`
- `BLOCKED`
- `NOT RUN`
- `NOT APPLICABLE`

## 16. Format pencatatan defect

```text
Defect ID:
Judul:
Severity: Critical / High / Medium / Low
Role:
Modul:
Environment:
Prasyarat:
Langkah reproduksi:
1.
2.
3.

Expected result:
Actual result:
HTTP status / console error:
Data yang terdampak:
Screenshot/video:
```

## 17. Kriteria kelulusan

Pengujian dinyatakan lulus jika:

- [ ] Seluruh fungsi kritis memiliki status `PASS`.
- [ ] Tidak ada defect Critical atau High yang masih terbuka.
- [ ] Semua operasi terlarang ditolak oleh UI dan API.
- [ ] Tidak ada kebocoran data lintas lokasi.
- [ ] Tidak ada HTTP `500` pada alur normal.
- [ ] Tidak ada error JavaScript yang menghentikan fungsi.
- [ ] Alur end-to-end lintas role selesai.
- [ ] Tampilan mobile dan desktop dapat digunakan.
- [ ] Data `QA-*` telah dibersihkan atau database sengaja dikembalikan ke baseline.

## 18. Sign-off

| Pihak | Nama | Status | Tanggal | Catatan |
|---|---|---|---|---|
| QA/Tester |  |  |  |  |
| Equipment Manager |  |  |  |  |
| Administrator |  |  |  |  |
| Project Owner |  |  |  |  |

## 19. Batch integrasi antar-menu

### Batch 1 — BHW-IN ke Spare Part & Logistik

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- finalisasi laporan `BHW-IN` menambah stok pada tabel `parts`;
- setiap baris barang masuk dicatat di `inventory_transactions`;
- transaksi tampil pada tab Barang Masuk di menu Spare Part & Logistik;
- saldo terbaru tampil pada tab Stok;
- finalisasi ulang/retry tidak menggandakan stok;
- void laporan membalik penambahan stok;
- draft dan autosave tidak mengubah stok.

#### Persiapan

1. [ ] Login sebagai Administrator.
2. [ ] Lakukan hard refresh dengan `Ctrl+F5`.
3. [ ] Jalankan query berikut di HeidiSQL untuk memperoleh saldo awal:

```sql
SELECT part_number, part_name, unit_measure, stock_qty
FROM u646470441_ServicePlanBRA.parts
WHERE part_number = 'P-001-OIL';
```

#### Data uji yang disarankan

| Field | Nilai |
|---|---|
| Nomor log | `QA-BHWIN-001` |
| Project | `QA Laragon` |
| Tanggal laporan | Tanggal pengujian |
| Tanggal baris | Tanggal pengujian |
| No. BAPB | `QA-BAPB-001` |
| Terima dari | `QA Supplier` |
| Part number | `P-001-OIL` |
| Nama parts | `Filter Oli Engine Komatsu PC200-10M0` |
| Satuan | `Pcs` |
| Jumlah | `1` |
| Saldo lalu | Hasil `stock_qty` dari query persiapan |
| Saldo sekarang | Terhitung otomatis: saldo lalu + 1 |
| Keterangan | `Pengujian Batch 1` |

#### Pengujian draft

1. [ ] Isi form, tetapi jangan tekan **Simpan Laporan**.
2. [ ] Tunggu badge autosave selesai.
3. [ ] Pastikan laporan berstatus `DRAFT` di `report_records`.
4. [ ] Pastikan `stock_qty` belum berubah.
5. [ ] Pastikan belum ada transaksi aktif pada `inventory_transactions`.

#### Pengujian finalisasi

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pesan sukses menyebut jumlah baris stok yang diperbarui.
3. [ ] Pastikan status laporan menjadi `FINAL`.
4. [ ] Pastikan `parts.stock_qty` bertambah tepat sebesar `jumlah`.
5. [ ] Pastikan satu ledger aktif tercipta:

```sql
SELECT
    r.report_number,
    it.movement_type,
    p.part_number,
    p.part_name,
    it.quantity,
    it.stock_before,
    it.stock_after,
    it.reversed_at
FROM u646470441_ServicePlanBRA.inventory_transactions it
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = it.report_id
JOIN u646470441_ServicePlanBRA.parts p
    ON p.part_id = it.part_id
WHERE r.report_number = 'QA-BHWIN-001';
```

6. [ ] Buka **Spare Part & Logistik → Barang Masuk**.
7. [ ] Cari `P-001-OIL` atau `QA-BAPB-001` dan pastikan transaksi tampil.
8. [ ] Buka tab **Stok** dan pastikan saldo baru tampil.

#### Pengujian validasi saldo

1. [ ] Buat laporan kedua dengan `saldo_lalu` yang sengaja berbeda dari `parts.stock_qty`.
2. [ ] Tekan **Simpan Laporan**.
3. [ ] Sistem harus menolak finalisasi dan menampilkan stok database yang benar.
4. [ ] Pastikan stok dan ledger tidak berubah.

#### Pengujian void

1. [ ] Buka **Riwayat Laporan**.
2. [ ] Klik **Void** pada `QA-BHWIN-001`.
3. [ ] Konfirmasi pembatalan.
4. [ ] Pastikan stok kembali ke saldo sebelum finalisasi.
5. [ ] Pastikan `inventory_transactions.reversed_at` terisi.
6. [ ] Pastikan transaksi tidak lagi muncul sebagai Barang Masuk aktif.

#### Kriteria lulus Batch 1

- [ ] Draft tidak mengubah stok.
- [ ] Finalisasi menambah stok tepat satu kali.
- [ ] Menu Logistik menampilkan transaksi dan saldo database.
- [ ] Saldo lama yang tidak cocok ditolak.
- [ ] Void mengembalikan stok.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 1 sebelum implementasi Batch 2 dimulai.
