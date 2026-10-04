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
- Jangan menjalankan `scripts/sync_to_laragon.ps1` tanpa opsi di tengah pengujian karena mode standar mengimpor ulang database dan mereset password lokal.
- Untuk memasang perubahan kode tanpa mengubah data uji, gunakan `powershell -ExecutionPolicy Bypass -File scripts/sync_to_laragon.ps1 -PreserveDatabase`.

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

### Batch 2 — BHW-OUT ke Spare Part & Logistik

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- finalisasi laporan `BHW-OUT` mengurangi stok pada tabel `parts`;
- setiap pengeluaran dicatat sebagai movement `OUT` pada `inventory_transactions`;
- transaksi tampil pada tab Barang Keluar;
- total pemakaian dan saldo terbaru tampil pada tab Stok;
- stok tidak boleh negatif;
- saldo form harus sama dengan stok database saat finalisasi;
- retry tidak menggandakan pengeluaran;
- void mengembalikan stok.

#### Data uji yang disarankan

Sebelum mengisi form, ambil saldo aktual:

```sql
SELECT part_number, part_name, unit_measure, stock_qty
FROM u646470441_ServicePlanBRA.parts
WHERE part_number = 'P-001-OIL';
```

| Field | Nilai |
|---|---|
| Nomor log | `QA-BHWOUT-USER-001` |
| Project | `QA Laragon` |
| Tanggal laporan | Tanggal pengujian |
| Tanggal baris | Tanggal pengujian |
| No. bukti kirim | `QA-BK-USER-001` |
| Dikirim ke | `QA Workshop` |
| Part number | `P-001-OIL` |
| Nama parts | `Filter Oli Engine Komatsu PC200-10M0` |
| Satuan | `Pcs` |
| Persediaan | Hasil `stock_qty` dari query persiapan |
| Diberikan | `1` |
| Sisa | Terhitung otomatis: persediaan − 1 |
| Keterangan | `Pengujian Batch 2` |

#### Pengujian draft

1. [ ] Isi form dan tunggu autosave tanpa menekan **Simpan Laporan**.
2. [ ] Pastikan laporan berstatus `DRAFT`.
3. [ ] Pastikan stok belum berkurang.
4. [ ] Pastikan belum ada movement `OUT` aktif.

#### Pengujian finalisasi

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pesan sukses menyebut satu baris stok diperbarui.
3. [ ] Pastikan stok berkurang tepat sebesar `diberikan`.
4. [ ] Verifikasi ledger:

```sql
SELECT
    r.report_number,
    it.movement_type,
    p.part_number,
    it.quantity,
    it.stock_before,
    it.stock_after,
    it.reversed_at
FROM u646470441_ServicePlanBRA.inventory_transactions it
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = it.report_id
JOIN u646470441_ServicePlanBRA.parts p
    ON p.part_id = it.part_id
WHERE r.report_number = 'QA-BHWOUT-USER-001';
```

5. [ ] Buka **Spare Part & Logistik → Barang Keluar**.
6. [ ] Cari `QA-BK-USER-001` atau `P-001-OIL` dan pastikan transaksi tampil.
7. [ ] Buka tab **Stok** dan pastikan saldo serta total pemakaian berubah.

#### Pengujian penolakan

1. [ ] Coba jumlah `diberikan` lebih besar daripada persediaan; finalisasi harus ditolak.
2. [ ] Coba part number yang tidak ada; finalisasi harus ditolak.
3. [ ] Coba `persediaan` berbeda dari stok database; finalisasi harus ditolak.
4. [ ] Pastikan semua kegagalan tidak mengubah stok maupun ledger.

#### Pengujian void

1. [ ] Buka **Riwayat Laporan**.
2. [ ] Klik **Void** pada `QA-BHWOUT-USER-001`.
3. [ ] Pastikan stok kembali ke saldo sebelum pengeluaran.
4. [ ] Pastikan `inventory_transactions.reversed_at` terisi.
5. [ ] Pastikan transaksi tidak lagi tampil sebagai Barang Keluar aktif.

#### Kriteria lulus Batch 2

- [ ] Draft tidak mengurangi stok.
- [ ] Finalisasi mengurangi stok tepat satu kali.
- [ ] Stok negatif tidak mungkin terjadi.
- [ ] Menu Barang Keluar dan Stok membaca data database terbaru.
- [ ] Void mengembalikan stok.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 2 sebelum implementasi Batch 3 dimulai.

### Batch 3 — P2H ke Inspeksi & P2H dan Master Asset

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- finalisasi `P2H (Hydraulic Excavator)` dan `P2H (Single Drum Rollers)` membuat record pada tabel `inspections`;
- hasil final langsung tampil pada menu **Inspeksi & P2H → Riwayat & Tabulasi P2H**;
- `Code number` harus cocok dengan `asset_id` atau `asset_code` pada Master Asset;
- kategori unit harus cocok dengan template: `Excavator` atau `Vibro Compactor`;
- HM selesai tidak boleh lebih kecil daripada HM awal maupun HM terakhir pada Master Asset;
- hasil normal berstatus `PASS`, item `OK — Sudah diperbaiki` berstatus `WARNING`, dan item `X — Tidak normal` berstatus `FAIL`;
- `WARNING` mengubah status unit menjadi `INSPEKSI`;
- `FAIL` mengubah status unit menjadi `BREAKDOWN`;
- retry finalisasi tidak membuat inspeksi ganda;
- void menonaktifkan riwayat hasil integrasi dan memulihkan status serta HM jika unit belum mengalami perubahan lanjutan.

#### Persiapan data uji

Cari unit Excavator yang aman untuk pengujian dan catat status serta HM awalnya:

```sql
SELECT asset_id, asset_code, category, status, last_hm_km, raw_location_notes
FROM u646470441_ServicePlanBRA.assets
WHERE category = 'Excavator'
  AND status NOT IN ('ACCIDENT_HOLD', 'INACTIVE')
ORDER BY asset_id;
```

Contoh yang dapat digunakan bila hasil query masih sesuai:

| Field | Nilai |
|---|---|
| Template | `P2H (Hydraulic Excavator)` |
| Bulan pemeriksaan | Bulan pengujian |
| Model / unit | `PC 200-8 MO` |
| Nama operator | `QA Inspector Batch 3` |
| NRP | `QA-P2H-003` |
| Code number | `CS-41001` atau unit Excavator dari query |
| Job site | `QA Laragon` |
| Tanggal pelaksanaan | Tanggal pengujian |
| HM sebelum operasi | HM master + `1` |
| HM selesai operasi | HM master + `2` |

Isi tiga baris seed sebagai berikut:

| Item | Kondisi | Tindakan |
|---|---|---|
| Baris 1 | `V — Normal` | Kosong |
| Baris 2 | `OK — Sudah diperbaiki` | `Dikencangkan saat pemeriksaan` |
| Baris 3 | `V — Normal` | Kosong |

#### Pengujian draft

1. [ ] Isi identitas dan checklist, lalu tunggu autosave tanpa menekan **Simpan Laporan**.
2. [ ] Pastikan laporan masih berstatus `DRAFT`.
3. [ ] Pastikan belum ada record integrasi pada `inspections`.
4. [ ] Pastikan status dan HM Master Asset belum berubah.

#### Pengujian finalisasi WARNING

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pesan sukses harus menyatakan laporan masuk ke Riwayat Inspeksi & P2H.
3. [ ] Buka **Inspeksi & P2H → Riwayat & Tabulasi P2H**.
4. [ ] Cari Code number/unit dan operator `QA Inspector Batch 3`.
5. [ ] Pastikan hasilnya `LULUS DENGAN CATATAN`.
6. [ ] Buka Master Asset dan pastikan status unit menjadi `INSPEKSI` serta HM berubah ke HM selesai.
7. [ ] Verifikasi database:

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    i.inspection_id,
    i.asset_id,
    i.inspection_date,
    i.current_hm_km,
    i.overall_result,
    i.findings_summary,
    rii.previous_asset_status,
    rii.applied_asset_status,
    rii.previous_hm,
    rii.applied_hm,
    rii.reversed_at
FROM u646470441_ServicePlanBRA.report_inspection_integrations rii
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = rii.report_id
JOIN u646470441_ServicePlanBRA.inspections i
    ON i.inspection_id = rii.inspection_id
WHERE i.asset_id = 'CS-41001'
ORDER BY rii.integration_id DESC
LIMIT 5;
```

#### Pengujian penolakan

1. [ ] Gunakan Code number yang tidak ada; finalisasi harus ditolak dengan pesan Master Asset tidak ditemukan.
2. [ ] Gunakan template Excavator untuk unit `Vibro Compactor`; finalisasi harus ditolak karena kategori tidak cocok.
3. [ ] Isi HM selesai lebih kecil dari HM awal; finalisasi harus ditolak.
4. [ ] Kosongkan kondisi salah satu baris yang berisi item; finalisasi harus ditolak.
5. [ ] Pastikan seluruh kegagalan tidak membuat record inspeksi atau mengubah Master Asset.

#### Pengujian FAIL

1. [ ] Buat P2H lain pada unit uji yang aman.
2. [ ] Pilih `X — Tidak normal` pada salah satu item.
3. [ ] Isi tindakan, misalnya `Unit ditahan untuk pemeriksaan hydraulic`.
4. [ ] Finalkan laporan dan pastikan hasil riwayat `GAGAL (CRITICAL FAIL)`.
5. [ ] Pastikan status Master Asset berubah menjadi `BREAKDOWN`.

#### Pengujian void

1. [ ] Buka **Laporan & Form → Riwayat Laporan**.
2. [ ] Klik **Void** pada laporan P2H uji.
3. [ ] Pastikan pesan menyebut riwayat inspeksi dinonaktifkan.
4. [ ] Pastikan record tidak lagi tampil pada Riwayat & Tabulasi P2H aktif.
5. [ ] Pastikan `report_inspection_integrations.reversed_at` terisi.
6. [ ] Jika belum ada perubahan lanjutan pada unit, pastikan status dan HM kembali ke nilai sebelum finalisasi.

#### Kriteria lulus Batch 3

- [ ] Draft tidak mengubah inspeksi atau Master Asset.
- [ ] Finalisasi membuat tepat satu record inspeksi.
- [ ] Riwayat P2H membaca hasil integrasi dari database.
- [ ] WARNING mengubah status menjadi `INSPEKSI`.
- [ ] FAIL mengubah status menjadi `BREAKDOWN`.
- [ ] Validasi unit, kategori, kondisi, dan HM bekerja.
- [ ] Void menonaktifkan inspeksi dan memulihkan keadaan unit secara aman.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 3 sebelum implementasi Batch 4 dimulai.

### Batch 4 — LHO ke Produktivitas dan Master Asset

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- finalisasi `Laporan Harian Operasi Alat (LHO)` membuat ledger pada `report_operation_logs`;
- data final tampil di **Produktivitas → LHO Operasional**;
- HM terakhir pada Master Asset diperbarui ke `HM akhir` baris terakhir;
- jam kerja divalidasi dari selisih `Jam awal` dan `Jam akhir`, termasuk shift lintas tengah malam;
- HM operasi divalidasi dari `HM akhir − HM awal`;
- HM awal baris pertama harus sama dengan HM Master Asset;
- HM awal baris berikutnya harus sama dengan HM akhir baris sebelumnya;
- tanggal setiap baris harus berada pada periode laporan;
- hanya baris berstatus `Terverifikasi` yang dapat difinalkan;
- retry tidak membuat ledger operasi ganda;
- void menonaktifkan ledger dan memulihkan HM bila belum ada pembaruan lanjutan.

#### Persiapan data uji

Ambil HM aktual unit yang akan digunakan:

```sql
SELECT asset_id, asset_code, category, status, last_hm_km, raw_location_notes
FROM u646470441_ServicePlanBRA.assets
WHERE asset_id = 'CS-41001';
```

Catat nilai `last_hm_km` sebagai **HM_MASTER**. Jika unit tersebut sedang digunakan pengujian lain, pilih unit aktif lain dan gunakan ID serta HM aktualnya.

| Field laporan | Nilai |
|---|---|
| Template | `Laporan Harian Operasi Alat` |
| Bulan / tahun | Bulan pengujian |
| Jenis alat | `Excavator` |
| Tipe / merk | `PC 200-8 MO` |
| Lokasi alat | `QA Laragon` |
| Operator | `QA Operator Batch 4` |
| ID alat | `CS-41001` atau unit dari query |

Isi satu baris operasi:

| Kolom | Nilai |
|---|---|
| Tanggal | Tanggal dalam bulan pengujian |
| Jam awal | `08:00` |
| Jam akhir | `16:00` |
| Jam kerja | Otomatis `8` |
| HM awal | **HM_MASTER** |
| HM akhir | **HM_MASTER + 6.5** |
| HM operasi | Otomatis `6.5` |
| Site area | `QA Site Batch 4` |
| BBM | `65` |
| Cuaca | `Cerah` |
| Keterangan | `Pengujian integrasi LHO Batch 4` |
| Verifikasi | `Terverifikasi` |

#### Pengujian draft

1. [ ] Isi identitas dan baris operasi, lalu tunggu autosave tanpa finalisasi.
2. [ ] Pastikan laporan masih `DRAFT`.
3. [ ] Pastikan `report_operation_logs` belum memiliki record laporan tersebut.
4. [ ] Pastikan HM Master Asset belum berubah.

#### Pengujian finalisasi

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pesan sukses harus menyebut baris operasi masuk ke Produktivitas dan HM diperbarui.
3. [ ] Buka **Produktivitas → LHO Operasional**.
4. [ ] Jika diperlukan, klik **Muat ulang**.
5. [ ] Pastikan unit, operator, tanggal, jam kerja, HM, BBM, dan nomor laporan tampil.
6. [ ] Pastikan ringkasan record aktif, total jam, total HM, dan BBM/HM berubah.
7. [ ] Buka Master Asset dan pastikan HM menjadi **HM_MASTER + 6.5**.
8. [ ] Verifikasi database:

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    o.operation_date,
    o.asset_id,
    o.operator_name,
    o.start_time,
    o.end_time,
    o.work_hours,
    o.hm_start,
    o.hm_end,
    o.hm_operation,
    o.fuel_liters,
    o.verification_status,
    o.previous_asset_hm,
    o.applied_asset_hm,
    o.reversed_at
FROM u646470441_ServicePlanBRA.report_operation_logs o
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = o.report_id
WHERE o.asset_id = 'CS-41001'
ORDER BY o.operation_log_id DESC
LIMIT 10;
```

#### Pengujian validasi

1. [ ] Ubah `Jam kerja` agar tidak sama dengan selisih waktu; finalisasi harus ditolak.
2. [ ] Isi `HM operasi` berbeda dari `HM akhir − HM awal`; finalisasi harus ditolak.
3. [ ] Isi HM awal berbeda dari HM Master Asset; finalisasi harus ditolak.
4. [ ] Tambahkan baris kedua dengan HM awal berbeda dari HM akhir baris pertama; finalisasi harus ditolak.
5. [ ] Gunakan tanggal di luar bulan laporan; finalisasi harus ditolak.
6. [ ] Pilih status `Draft` atau `Perlu koreksi`; finalisasi harus ditolak.
7. [ ] Pastikan semua kegagalan tidak membuat ledger atau mengubah HM unit.

#### Pengujian void

1. [ ] Buka **Laporan & Form → Riwayat Laporan**.
2. [ ] Klik **Void** pada laporan LHO uji.
3. [ ] Pastikan pesan menyebut riwayat operasi dinonaktifkan dan HM dipulihkan.
4. [ ] Buka **Produktivitas → LHO Operasional**, kemudian klik **Muat ulang**.
5. [ ] Pastikan record void tidak lagi tampil sebagai record aktif.
6. [ ] Pastikan `report_operation_logs.reversed_at` terisi.
7. [ ] Jika belum ada pembaruan unit setelah LHO, pastikan HM kembali ke **HM_MASTER**.

#### Kriteria lulus Batch 4

- [ ] Draft tidak mengubah ledger atau HM.
- [ ] Finalisasi membuat tepat satu ledger untuk setiap baris operasi.
- [ ] Menu Produktivitas membaca data LHO aktif dari database.
- [ ] Perhitungan jam kerja dan HM divalidasi.
- [ ] Urutan HM antarbaris dan terhadap Master Asset konsisten.
- [ ] Record yang belum terverifikasi ditolak.
- [ ] Void menonaktifkan ledger dan memulihkan HM secara aman.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 4 sebelum implementasi Batch 5 dimulai.

### Batch 5 — LHO ke Fuel Management

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- setiap baris LHO final dengan `BBM > 0` membuat satu transaksi pada `fuel_logs`;
- transaksi tampil di menu **Fuel** dengan sumber `Laporan LHO`, nomor laporan, site, operator, HM, liter, dan konsumsi L/HM;
- konsumsi dihitung dengan rumus `BBM / HM operasi`;
- baris dengan BBM `0` tidak membuat transaksi Fuel;
- retry finalisasi tidak membuat transaksi ganda;
- transaksi BBM manual tetap tampil dan tidak diubah;
- void menonaktifkan tautan transaksi LHO sehingga tidak lagi tampil di Fuel, tanpa menghapus histori `fuel_logs`.

#### Persiapan data uji

Gunakan LHO yang baru dan ambil HM aktual unit terlebih dahulu:

```sql
SELECT asset_id, asset_code, category, status, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE asset_id = 'CS-41001';
```

Catat `last_hm_km` sebagai **HM_MASTER**, lalu isi satu baris LHO:

| Kolom | Nilai |
|---|---|
| Operator | `QA Operator Batch 5` |
| ID alat | `CS-41001` atau unit uji aktif |
| Jam awal / akhir | `08:00` / `16:00` |
| HM awal | **HM_MASTER** |
| HM akhir | **HM_MASTER + 6.5** |
| HM operasi | `6.5` |
| Site area | `QA Site Batch 5` |
| BBM | `65` |
| Verifikasi | `Terverifikasi` |

Hasil konsumsi yang diharapkan adalah `65 / 6.5 = 10 L/HM`.

#### Pengujian draft

1. [ ] Isi laporan dan tunggu autosave tanpa menekan **Simpan Laporan**.
2. [ ] Pastikan laporan masih `DRAFT`.
3. [ ] Pastikan belum ada record untuk laporan tersebut di `report_fuel_integrations`.
4. [ ] Pastikan jumlah transaksi pada menu Fuel belum berubah.

#### Pengujian finalisasi dan menu Fuel

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pesan sukses harus menyebut satu transaksi BBM masuk ke Fuel.
3. [ ] Buka menu **Fuel**; data akan dimuat ulang otomatis.
4. [ ] Cari nomor laporan atau ID unit pada kotak pencarian.
5. [ ] Pastikan sumber tampil sebagai `Laporan LHO` dan referensi berisi nomor laporan.
6. [ ] Pastikan site, operator, HM awal, HM akhir, HM operasi, dan liter sesuai LHO.
7. [ ] Pastikan konsumsi aktual tampil `10 L/HM`.
8. [ ] Pastikan transaksi BBM manual yang sudah ada tetap tampil.
9. [ ] Verifikasi database:

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    rfi.fuel_integration_id,
    rfi.report_item_position,
    f.fuel_log_id,
    f.asset_id,
    f.refuel_date,
    f.liters_issued,
    f.current_hm_km,
    f.calculated_lph,
    o.site,
    o.hm_start,
    o.hm_end,
    o.hm_operation,
    o.operator_name,
    rfi.reversed_at
FROM u646470441_ServicePlanBRA.report_fuel_integrations rfi
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = rfi.report_id
JOIN u646470441_ServicePlanBRA.fuel_logs f
    ON f.fuel_log_id = rfi.fuel_log_id
JOIN u646470441_ServicePlanBRA.report_operation_logs o
    ON o.report_id = rfi.report_id
   AND o.report_item_position = rfi.report_item_position
WHERE f.asset_id = 'CS-41001'
ORDER BY rfi.fuel_integration_id DESC
LIMIT 10;
```

#### Pengujian BBM nol dan duplikasi

1. [ ] Buat LHO baru dengan `BBM = 0` dan HM yang berurutan dari HM Master Asset terbaru.
2. [ ] Finalkan laporan; ledger Produktivitas harus dibuat, tetapi transaksi Fuel tidak bertambah.
3. [ ] Muat ulang atau kirim ulang permintaan finalisasi laporan yang sama.
4. [ ] Pastikan jumlah `report_fuel_integrations` untuk laporan tersebut tidak bertambah.

#### Pengujian void

1. [ ] Buka **Laporan & Form → Riwayat Laporan**.
2. [ ] Klik **Void** pada laporan LHO Batch 5.
3. [ ] Pastikan pesan menyebut transaksi BBM terkait dinonaktifkan.
4. [ ] Buka kembali menu **Fuel** dan cari nomor laporan.
5. [ ] Pastikan transaksi tersebut tidak lagi tampil sebagai transaksi aktif.
6. [ ] Pastikan `report_fuel_integrations.reversed_at` dan `reversed_by` terisi.
7. [ ] Pastikan baris historis di `fuel_logs` tetap ada untuk audit.
8. [ ] Pastikan transaksi BBM manual lain tidak berubah.

#### Kriteria lulus Batch 5

- [ ] Draft tidak membuat transaksi Fuel.
- [ ] Setiap baris final dengan BBM positif membuat tepat satu transaksi Fuel.
- [ ] BBM nol tidak membuat transaksi Fuel.
- [ ] Nilai liter dan L/HM sesuai data LHO.
- [ ] Sumber, nomor laporan, site, operator, dan HM tampil benar.
- [ ] Retry tidak membuat duplikasi.
- [ ] Void menghilangkan transaksi LHO dari data Fuel aktif tetapi mempertahankan histori audit.
- [ ] Transaksi manual tidak berubah.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 5 sebelum implementasi Batch 6 dimulai.

### Revisi Batch 1–5 — Pilihan dan pengisian otomatis dari database

Status implementasi: **SIAP UJI**.

Seluruh template laporan sekarang menyediakan autocomplete dari database untuk field yang sesuai:

| Jenis field | Sumber database | Otomatisasi setelah dipilih |
|---|---|---|
| Project, site, job site, lokasi | `locations` dan site LHO aktif | Menggunakan nama lokasi resmi |
| ID/kode unit | `assets` | Jenis alat, model, lokasi, serial, HM awal/sebelum |
| Jenis alat | Kategori aktif pada `assets` | Pilihan kategori konsisten dengan Master Asset |
| Tipe/merk | `assets.make_model` | Menggunakan model yang sudah terdaftar |
| Operator/personel | `users.full_name` | Menggunakan nama personel aktif |
| Part number/nama part | `parts` | Nama/nomor part, satuan, stok, dan harga bila tersedia |
| Satuan part | `parts.unit_measure` | Pilihan satuan dari Master Part |

Autocomplete tetap mengizinkan pengetikan manual agar laporan lama atau referensi baru yang belum dimasukkan ke master tidak terblokir.

#### Pengujian koneksi referensi

1. [ ] Buka salah satu template pada **Laporan & Form**.
2. [ ] Pastikan indikator hijau menyebut jumlah unit, lokasi, part, dan personel dari database.
3. [ ] Klik field `Project`, `Site`, `Jenis alat`, `Lokasi`, atau `Operator`.
4. [ ] Ketik sebagian nama dan pastikan pilihan database muncul.
5. [ ] Login dengan role yang memiliki `reports.read` dan pastikan referensi mengikuti kebijakan akses aplikasi untuk role tersebut.

#### Pengujian otomatisasi unit

1. [ ] Buka LHO atau P2H.
2. [ ] Pada `ID alat` atau `Code number`, pilih unit dari daftar database.
3. [ ] Pastikan jenis alat, model, lokasi/job site, dan serial terisi bila field tersedia.
4. [ ] Pada LHO, pastikan HM awal baris pertama mengikuti `assets.last_hm_km`.
5. [ ] Pada P2H, pastikan HM sebelum operasi mengikuti `assets.last_hm_km`.
6. [ ] Ganti pilihan unit dan pastikan field terkait ikut diperbarui.

#### Pengujian otomatisasi part Batch 1–2

1. [ ] Buka BHW-IN atau BHW-OUT.
2. [ ] Pilih `Part number` dari daftar database.
3. [ ] Pastikan nama part dan satuan terisi otomatis.
4. [ ] Pada BHW-IN, pastikan `Saldo lalu` mengikuti stok Master Part.
5. [ ] Pada BHW-OUT, pastikan `Persediaan` mengikuti stok Master Part.
6. [ ] Isi jumlah masuk/keluar dan pastikan saldo akhir tetap dihitung otomatis.

#### Kriteria lulus revisi

- [ ] Pilihan referensi berasal dari database Laragon, bukan daftar statis browser.
- [ ] Field tetap dapat diketik manual saat data belum terdaftar.
- [ ] Pemilihan unit mengisi atribut unit yang relevan.
- [ ] Pemilihan part mengisi identitas part dan stok yang relevan.
- [ ] Endpoint referensi mengikuti izin `reports.read` dan kebijakan akses lokasi aplikasi.
- [ ] Draft, finalisasi, integrasi Batch 1–5, dan void tetap berjalan.
- [ ] Tidak ada error JavaScript atau HTTP `500`.

### Batch 6 — Maintenance Board ke Preventive Maintenance

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- pilihan `Kode unit` berasal dari Master Asset Laragon;
- setelah unit dipilih, `Jenis A2B`, `HM awal`, dan `Tgl HM` diisi otomatis;
- laporan berstatus `DRAFT` tidak mengubah `pm_plans`;
- setiap baris laporan final membuat atau menautkan satu rencana pada `pm_plans`;
- target HM dihitung dari `HM awal + interval`;
- status awal dihitung menjadi `PLANNED`, `DUE_SOON`, `OVERDUE`, atau `COMPLETED`;
- rencana yang identik ditautkan tanpa dibuat ulang;
- menu **Preventive Maintenance** memuat rencana yang berasal dari laporan;
- void hanya menghapus rencana yang dibuat laporan dan belum diubah planner;
- rencana manual atau rencana yang telah diubah planner tidak dihapus saat laporan di-void.

#### Persiapan data uji

1. [ ] Jalankan query berikut dan pilih satu unit aktif:

```sql
SELECT asset_id, asset_code, category, make_model, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE is_active = 1
ORDER BY asset_code
LIMIT 20;
```

2. [ ] Catat `asset_id`, `category`, dan `last_hm_km` unit tersebut.
3. [ ] Catat jumlah rencana awal:

```sql
SELECT COUNT(*) AS jumlah_awal
FROM u646470441_ServicePlanBRA.pm_plans;
```

#### Pengujian otomatisasi form

1. [ ] Buka **Laporan & Form → Maintenance Board A2B**.
2. [ ] Isi lokasi, tanggal pembaruan, dan pembuat menggunakan pilihan database.
3. [ ] Pada kolom `Kode unit`, ketik sebagian ID/kode lalu pilih unit dari daftar.
4. [ ] Pastikan `Jenis A2B` terisi dari `assets.category`.
5. [ ] Pastikan `HM awal` terisi dari `assets.last_hm_km`.
6. [ ] Pastikan `Tgl HM` mengikuti tanggal pembaruan jika sebelumnya kosong.
7. [ ] Pilih interval `500 HM`, `1000 HM`, `1500 HM`, atau `2000 HM`.
8. [ ] Pastikan tanggal realisasi, parts dipesan, dan parts tiba boleh dikosongkan untuk rencana yang belum selesai.

#### Pengujian draft

1. [ ] Isi form dan tunggu autosave tanpa menekan **Simpan Laporan**.
2. [ ] Pastikan status laporan masih `DRAFT`.
3. [ ] Pastikan jumlah `pm_plans` belum bertambah.
4. [ ] Pastikan belum ada ledger aktif pada `report_pm_integrations` untuk draft tersebut.

#### Pengujian finalisasi dan menu PM

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pesan sukses harus menyebut jumlah rencana PM yang dibuat atau ditautkan.
3. [ ] Buka menu **Preventive Maintenance → Forecast & Due Tracker**.
4. [ ] Cari kode/ID unit yang baru difinalkan.
5. [ ] Pastikan unit, interval, HM awal, target HM, status, dan catatan sumber laporan tampil.
6. [ ] Verifikasi database:

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    rpi.integration_id,
    rpi.report_item_position,
    rpi.owns_pm_plan,
    rpi.reversed_at,
    p.pm_plan_id,
    p.asset_id,
    p.interval_hm,
    p.current_smr,
    p.last_service_hm,
    p.last_service_date,
    p.target_due_hm,
    p.variance_hm,
    p.status AS pm_status,
    p.planner_note
FROM u646470441_ServicePlanBRA.report_pm_integrations rpi
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = rpi.report_id
LEFT JOIN u646470441_ServicePlanBRA.pm_plans p
    ON p.pm_plan_id = rpi.pm_plan_id
ORDER BY rpi.integration_id DESC
LIMIT 20;
```

7. [ ] Pastikan `target_due_hm = last_service_hm + interval_hm`.
8. [ ] Pastikan satu baris laporan hanya memiliki satu ledger integrasi.

#### Pengujian tanpa duplikasi

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Pastikan jumlah rencana dan ledger untuk laporan yang sama tidak bertambah.
3. [ ] Bila sudah ada rencana manual dengan unit, tanggal HM, HM awal, interval, dan target yang sama, pastikan laporan menautkannya dengan `owns_pm_plan = 0`.

#### Pengujian void aman

1. [ ] Buat dan finalkan satu laporan Maintenance Board baru.
2. [ ] Tanpa mengubah rencana PM, lakukan void dari **Riwayat Laporan**.
3. [ ] Pastikan rencana buatan laporan tersebut dihapus dan `reversed_at` terisi.
4. [ ] Ulangi dengan laporan baru, lalu ubah `planner_note` rencana melalui database atau fungsi planner sebelum void.
5. [ ] Lakukan void dan pastikan rencana yang telah diubah tetap ada.
6. [ ] Pastikan rencana manual lain tidak berubah.

#### Kriteria lulus Batch 6

- [ ] Pilihan unit dan atribut otomatis berasal dari database Laragon.
- [ ] Draft tidak membuat rencana PM.
- [ ] Finalisasi membuat atau menautkan tepat satu rencana per baris.
- [ ] Target HM dan status dihitung benar.
- [ ] Rencana muncul pada menu Preventive Maintenance.
- [ ] Retry atau reload tidak membuat duplikasi.
- [ ] Void menghapus rencana milik laporan yang belum diubah.
- [ ] Void mempertahankan rencana manual dan rencana yang telah diubah planner.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 6 sebelum Batch 7 dimulai.

### Standar otomatisasi untuk Batch 6 dan batch berikutnya

Untuk seluruh batch berikutnya, field yang memiliki master data wajib menggunakan pola yang sama:

- unit dipilih dari `assets`, lalu kategori, model, lokasi, serial/plat, dan HM diisi otomatis jika field tersedia;
- lokasi, site, dan project dipilih dari `locations` atau histori site aktif;
- personel dipilih dari `users` aktif;
- part dipilih dari `parts`, lalu nomor/nama, satuan, stok, dan harga diisi otomatis;
- nilai otomatis tetap divalidasi ulang oleh backend saat finalisasi;
- data operasional hanya berubah pada status `FINAL`, bukan saat autosave draft;
- setiap integrasi harus idempoten, memiliki ledger sumber, dapat ditelusuri, dan aman saat void;
- data manual yang tidak dibuat oleh laporan tidak boleh dihapus atau ditimpa.

### Batch 7 — Repair & Overhaul ke Work Order

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- `Kode unit` dipilih dari Master Asset Laragon;
- pilihan unit otomatis mengisi nama/kategori asset, serial number, dan HM/KM terakhir;
- nama atau nomor part dipilih dari Master Part dan mengisi pasangannya secara otomatis;
- PIC pada tabel solusi dipilih dari personel aktif;
- laporan `DRAFT` tidak membuat Work Order;
- laporan `FINAL` membuat satu Work Order berstatus `Open`;
- urgensi dipetakan menjadi prioritas: `Normal → Normal`, `Mendesak → High`, dan `Emergency → Emergency`;
- nomor laporan digunakan sebagai ID Work Order agar sumbernya mudah ditelusuri;
- Work Order hasil integrasi tampil di menu **Work Order** dan dapat dicari memakai nomor laporan;
- finalisasi ulang tidak membuat Work Order ganda;
- void menghapus Work Order milik laporan hanya jika belum diubah dan belum memiliki aktivitas lanjutan.

#### Persiapan data uji

1. [ ] Pilih satu unit aktif:

```sql
SELECT asset_id, asset_code, category, make_model, serial_number, last_hm_km
FROM u646470441_ServicePlanBRA.assets
WHERE is_active = 1
ORDER BY asset_code
LIMIT 20;
```

2. [ ] Pilih satu part dan satu personel aktif:

```sql
SELECT part_number, part_name, unit_measure
FROM u646470441_ServicePlanBRA.parts
ORDER BY part_name
LIMIT 20;

SELECT user_id, full_name
FROM u646470441_ServicePlanBRA.users
WHERE is_active = 1
ORDER BY full_name
LIMIT 20;
```

3. [ ] Catat jumlah Work Order awal:

```sql
SELECT COUNT(*) AS jumlah_awal
FROM u646470441_ServicePlanBRA.work_orders;
```

#### Pengujian otomatisasi form

1. [ ] Buka **Laporan & Form → Repair & Overhaul**.
2. [ ] Isi nomor laporan yang unik, misalnya `RO-UJI-001`, dan pilih tanggal laporan.
3. [ ] Pada `Kode unit`, pilih unit dari daftar database.
4. [ ] Pastikan asset, serial number, dan HM/KM terisi sesuai Master Asset.
5. [ ] Pilih nama atau nomor part dari daftar database.
6. [ ] Pastikan nama dan nomor part saling terisi sesuai Master Part.
7. [ ] Isi temuan, riwayat, urgensi, serta estimasi biaya. Nilai minimum tidak boleh melebihi maksimum.
8. [ ] Pada tabel solusi, isi solusi, pilih PIC, pilih target, lalu isi keterangan bila perlu.

#### Pengujian draft

1. [ ] Tunggu autosave tanpa menekan **Simpan Laporan**.
2. [ ] Pastikan laporan masih berstatus `DRAFT`.
3. [ ] Pastikan jumlah `work_orders` tidak bertambah.
4. [ ] Pastikan belum ada ledger aktif pada `report_work_order_integrations`.

#### Pengujian finalisasi dan menu Work Order

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pastikan pesan sukses menyebut Work Order yang dibuat.
3. [ ] Buka menu **Work Order**.
4. [ ] Cari menggunakan nomor laporan, kode unit, atau nama PIC.
5. [ ] Pastikan Work Order berstatus `Open`, asset benar, prioritas sesuai urgensi, dan PIC memakai PIC baris solusi pertama.
6. [ ] Pastikan kartu atau tabel Work Order menampilkan label sumber laporan.
7. [ ] Verifikasi database:

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    rwi.integration_id,
    rwi.owns_work_order,
    rwi.reversed_at,
    w.wo_id,
    w.asset_id,
    w.status AS wo_status,
    w.priority,
    w.assigned_mechanic,
    w.reported_at,
    w.issue_description
FROM u646470441_ServicePlanBRA.report_work_order_integrations rwi
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = rwi.report_id
LEFT JOIN u646470441_ServicePlanBRA.work_orders w
    ON w.wo_id = rwi.work_order_id
ORDER BY rwi.integration_id DESC
LIMIT 20;
```

8. [ ] Pastikan `wo_id` sama dengan nomor laporan dan hanya ada satu ledger untuk laporan tersebut.

#### Pengujian tanpa duplikasi

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Pastikan jumlah Work Order dan ledger laporan yang sama tidak bertambah.
3. [ ] Pastikan nomor yang sudah digunakan Work Order lain dengan data berbeda ditolak, bukan ditimpa.

#### Pengujian void aman

1. [ ] Buat dan finalkan laporan Repair & Overhaul baru.
2. [ ] Tanpa mengubah Work Order, lakukan void dari **Riwayat Laporan**.
3. [ ] Pastikan Work Order buatan laporan dihapus dan `reversed_at` pada ledger terisi.
4. [ ] Buat dan finalkan laporan lain, lalu ubah status Work Order menjadi `In Progress` atau tambahkan aktivitas lanjutan.
5. [ ] Void laporan tersebut.
6. [ ] Pastikan Work Order yang sudah diproses tetap ada, sedangkan tautan laporan ditandai telah dibalik.
7. [ ] Pastikan Work Order manual lain tidak berubah.

#### Kriteria lulus Batch 7

- [ ] Referensi unit, part, dan PIC berasal dari database Laragon.
- [ ] Atribut unit dan part terisi otomatis dengan benar.
- [ ] Draft tidak membuat Work Order.
- [ ] Finalisasi membuat tepat satu Work Order dan ledger sumber.
- [ ] Prioritas, PIC, tanggal, dan deskripsi Work Order sesuai isi laporan.
- [ ] Work Order tampil serta dapat dicari pada menu Work Order.
- [ ] Retry atau reload tidak membuat duplikasi.
- [ ] Void menghapus Work Order milik laporan yang belum diproses.
- [ ] Void mempertahankan Work Order yang sudah diubah atau memiliki aktivitas lanjutan.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 7 sebelum Batch 8 dimulai.

### Batch 8 — SPB ke Spare Part & Logistik

Status implementasi: **SIAP UJI**.

Ruang lingkup batch ini:

- Work Order/JO dipilih dari Work Order aktif pada database Laragon;
- pilihan Work Order otomatis mengisi unit, lokasi/project, dan urgensi;
- kode unit tetap dapat dipilih dari Master Asset tetapi harus sesuai dengan Work Order;
- nama atau spesifikasi/part number dipilih dari Master Part dan saling mengisi otomatis;
- laporan `DRAFT` tidak membuat Purchase Request;
- laporan `FINAL` membuat satu header `purchase_requests` dan satu item `purchase_request_items` untuk setiap baris;
- nomor SPB menjadi ID Purchase Request;
- data hasil laporan tampil pada menu **Spare Part & Logistik** dengan sumber nomor laporan;
- finalisasi ulang tidak membuat SPB atau item ganda;
- void menghapus SPB milik laporan yang belum diproses;
- SPB yang sudah disetujui, diubah statusnya, diubah itemnya, atau memiliki aktivitas approval tetap dipertahankan.

#### Persiapan data uji

1. [ ] Pilih satu Work Order aktif beserta unitnya:

```sql
SELECT w.wo_id, w.asset_id, w.status, w.priority, a.asset_code, a.category
FROM u646470441_ServicePlanBRA.work_orders w
JOIN u646470441_ServicePlanBRA.assets a ON a.asset_id = w.asset_id
WHERE a.is_active = 1
  AND w.status NOT IN ('Closed', 'Cancelled')
ORDER BY w.reported_at DESC
LIMIT 20;
```

2. [ ] Pilih satu part dari Master Part:

```sql
SELECT part_number, part_name, unit_measure, stock_qty
FROM u646470441_ServicePlanBRA.parts
ORDER BY part_name
LIMIT 20;
```

3. [ ] Catat jumlah awal Purchase Request:

```sql
SELECT COUNT(*) AS jumlah_awal
FROM u646470441_ServicePlanBRA.purchase_requests;
```

#### Pengujian otomatisasi form

1. [ ] Buka **Laporan & Form → SPB (Surat Permintaan Barang)**.
2. [ ] Ganti nomor SPB dengan nomor unik, misalnya `SPB-UJI-001`.
3. [ ] Pada `Work Order / JO`, pilih Work Order aktif dari daftar database.
4. [ ] Pastikan `Kode unit` sesuai asset Work Order.
5. [ ] Pastikan lokasi/project terisi dari lokasi unit bila datanya tersedia.
6. [ ] Pastikan urgensi menjadi `Emergency` untuk Work Order prioritas `High` atau `Emergency`, selain itu `Normal`.
7. [ ] Pada tabel barang, pilih nama barang atau spesifikasi/part number dari database.
8. [ ] Pastikan nama, part number, dan satuan terisi sesuai Master Part.
9. [ ] Isi jumlah, status `Diajukan`, keterangan bila perlu, dan unggah bukti gambar beserta keterangannya.

#### Pengujian draft

1. [ ] Tunggu autosave tanpa menekan **Simpan Laporan**.
2. [ ] Pastikan laporan masih berstatus `DRAFT`.
3. [ ] Pastikan jumlah `purchase_requests` dan `purchase_request_items` tidak berubah.
4. [ ] Pastikan belum ada ledger aktif pada `report_purchase_request_integrations`.

#### Pengujian finalisasi dan menu Logistik

1. [ ] Tekan **Simpan Laporan**.
2. [ ] Pastikan pesan sukses menyebut nomor SPB dan jumlah item yang dibuat.
3. [ ] Buka menu **Spare Part & Logistik**.
4. [ ] Cari unit yang dipakai pada laporan.
5. [ ] Buka detail unit dan pastikan nomor SPB, Work Order, part, kuantitas, prioritas, serta sumber laporan tampil.
6. [ ] Verifikasi database:

```sql
SELECT
    r.report_number,
    r.status AS report_status,
    rpri.integration_id,
    rpri.owns_purchase_request,
    rpri.reversed_at,
    pr.spb_id,
    pr.wo_id,
    pr.asset_id,
    pr.urgency,
    pr.status AS request_status,
    pri.id AS item_id,
    pri.part_number,
    pri.description,
    pri.qty_requested,
    pri.status AS item_status
FROM u646470441_ServicePlanBRA.report_purchase_request_integrations rpri
JOIN u646470441_ServicePlanBRA.report_records r
    ON r.report_id = rpri.report_id
LEFT JOIN u646470441_ServicePlanBRA.purchase_requests pr
    ON pr.spb_id = rpri.spb_id
LEFT JOIN u646470441_ServicePlanBRA.purchase_request_items pri
    ON pri.spb_id = pr.spb_id
ORDER BY rpri.integration_id DESC, pri.id
LIMIT 50;
```

7. [ ] Pastikan satu laporan mempunyai satu ledger dan jumlah item sama dengan jumlah baris laporan.

#### Pengujian validasi dan tanpa duplikasi

1. [ ] Muat ulang halaman setelah finalisasi.
2. [ ] Pastikan jumlah SPB dan item laporan yang sama tidak bertambah.
3. [ ] Coba pilih Work Order lalu ganti kode unit menjadi unit berbeda.
4. [ ] Pastikan finalisasi ditolak karena unit tidak sesuai dengan Work Order.
5. [ ] Pastikan nomor SPB yang sudah digunakan objek lain dengan data berbeda ditolak dan tidak menimpa data lama.

#### Pengujian void aman

1. [ ] Buat dan finalkan satu laporan SPB baru.
2. [ ] Tanpa mengubah status atau item SPB, lakukan void dari **Riwayat Laporan**.
3. [ ] Pastikan header dan item SPB buatan laporan dihapus serta `reversed_at` terisi.
4. [ ] Buat dan finalkan laporan SPB lain.
5. [ ] Ubah status Purchase Request menjadi `Approved`, atau proses melalui alur logistik/approval.
6. [ ] Void laporan tersebut.
7. [ ] Pastikan SPB yang sudah diproses tetap ada dan ledger ditandai telah dibalik.
8. [ ] Pastikan SPB manual lain tidak berubah.

#### Kriteria lulus Batch 8

- [ ] Work Order, unit, lokasi, urgensi, dan part berasal dari database Laragon.
- [ ] Pemilihan Work Order dan part mengisi field terkait secara otomatis.
- [ ] Work Order dan unit yang tidak cocok ditolak backend.
- [ ] Draft tidak membuat Purchase Request.
- [ ] Finalisasi membuat tepat satu SPB, item sesuai baris, dan ledger sumber.
- [ ] SPB tampil pada menu Spare Part & Logistik.
- [ ] Retry atau reload tidak membuat duplikasi.
- [ ] Void menghapus SPB milik laporan yang belum diproses.
- [ ] Void mempertahankan SPB yang telah diproses atau memiliki approval.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 8 sebelum Batch 9 dimulai.

### Batch 9 — PPB ke Pengadaan / Spare Part & Logistik

Status implementasi: **SIAP UJI**.

Integrasi Batch 9 menghubungkan laporan PPB dengan SPB aktif. Nomor SPB dipilih dari database; lokasi SPB mengisi project dan tempat penyerahan. Nama atau nomor part dipilih dari Master Part. Finalisasi membuat `purchase_orders`, `purchase_order_items`, dan ledger `report_purchase_order_integrations`. Subtotal, PPN 11%, dan total divalidasi ulang oleh backend.

#### Pengujian otomatisasi dan finalisasi

1. [ ] Pastikan minimal satu laporan SPB Batch 8 sudah difinalkan dan belum berstatus `Closed`.
2. [ ] Buka **Laporan & Form → PPB**.
3. [ ] Gunakan nomor unik, misalnya `PPB-UJI-001`.
4. [ ] Pilih nomor SPB dari daftar database.
5. [ ] Pastikan project dan tempat penyerahan mengikuti lokasi unit SPB jika tersedia.
6. [ ] Isi vendor, nomor/tanggal penawaran, dan batas penyerahan.
7. [ ] Pilih nama barang atau No. SC/part number dari Master Part.
8. [ ] Pastikan nama, part number, satuan, dan harga terisi bila tersedia; isi jumlah dan periksa jumlah harga otomatis.
9. [ ] Unggah bukti gambar dan keterangannya, lalu tekan **Simpan Laporan**.
10. [ ] Buka **Spare Part & Logistik**, cari unit SPB, dan pastikan status pengadaan serta sumber PPB tampil.

#### Verifikasi database

```sql
SELECT
    r.report_number,
    rpoi.owns_purchase_order,
    rpoi.reversed_at,
    po.ppb_id,
    po.spb_id,
    po.wo_id,
    po.asset_id,
    po.vendor,
    po.subtotal,
    po.tax_amount,
    po.total_amount,
    po.status,
    poi.part_number,
    poi.quantity,
    poi.unit_price,
    poi.total_price
FROM u646470441_ServicePlanBRA.report_purchase_order_integrations rpoi
JOIN u646470441_ServicePlanBRA.report_records r ON r.report_id = rpoi.report_id
LEFT JOIN u646470441_ServicePlanBRA.purchase_orders po ON po.ppb_id = rpoi.ppb_id
LEFT JOIN u646470441_ServicePlanBRA.purchase_order_items poi ON poi.ppb_id = po.ppb_id
ORDER BY rpoi.integration_id DESC, poi.id
LIMIT 50;
```

#### Pengujian idempotensi dan void

1. [ ] Muat ulang halaman; pastikan PPB dan item tidak bertambah.
2. [ ] Coba ubah jumlah harga agar tidak sama dengan `jumlah × harga`; finalisasi harus ditolak.
3. [ ] Void PPB baru yang belum diproses; header dan item harus terhapus dan ledger memiliki `reversed_at`.
4. [ ] Buat PPB lain, ubah statusnya menjadi `Approved` atau `Ordered`, lalu void laporannya.
5. [ ] Pastikan PPB yang sudah diproses tetap dipertahankan.

#### Kriteria lulus Batch 9

- [ ] SPB dan part dipilih dari database Laragon.
- [ ] PPB selalu terhubung ke SPB, Work Order, dan unit asal.
- [ ] Draft tidak membuat transaksi pengadaan.
- [ ] PPN 11% dan total sesuai perhitungan.
- [ ] PPB tampil pada menu Spare Part & Logistik.
- [ ] Retry tidak membuat duplikasi.
- [ ] Void aman membedakan PPB baru dan PPB yang sudah diproses.
- [ ] Tidak ada HTTP `500` atau error JavaScript.
- [ ] Pengguna menyetujui hasil Batch 9 sebelum Batch 10 dimulai.
