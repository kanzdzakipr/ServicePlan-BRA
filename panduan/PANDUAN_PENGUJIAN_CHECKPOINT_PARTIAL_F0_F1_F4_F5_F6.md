# Panduan Pengujian Checkpoint yang Masih Partial

Tanggal penyusunan: 24 Oktober 2026  
Target lokal: `http://serviceplan-bra.test/`  
Database: `u646470441_ServicePlanBRA` pada Laragon2

Dokumen ini menutup pengujian yang masih tersisa pada F0, F1B, F4, F5, dan F6. F1A, F2, serta F3 tidak diulang kecuali sebagai bagian regresi akhir F6.

## 1. Status awal dan urutan pengujian

| Checkpoint | Status awal | Target pengujian |
| --- | --- | --- |
| F0 | PARTIAL | Mutasi akun, password sendiri, CSRF, sesi, lokasi, dan audit |
| F1A | PASS | Hanya regresi singkat pada F6 |
| F1B | PARTIAL | Sinkronisasi satu ID, konflik dua browser, P2H/CM, role/lokasi |
| F4 | PASS otomatis 26/26 | Lifecycle KPI dan review SPL melalui browser/DB |
| F5 | PASS otomatis 28/28 | Archive, notifikasi, settings melalui browser/DB |
| F6 | PARTIAL | Rekonsiliasi analitik dan regression gate akhir |

Urutan wajib: **F0 → F1B → F4 → F5 → F6**. Jangan menyatakan backend selesai sebelum seluruh gate di bagian akhir dokumen lulus.

## 2. Aturan keselamatan

1. Gunakan hanya data dengan awalan `QA-FINISH-` dan akun/lokasi yang memang disetujui untuk pengujian.
2. Jangan mengubah, mengarsipkan, menonaktifkan, atau menghapus unit dan akun operasional.
3. Jangan memakai contoh lama `EXC-00001` untuk uji Archive. Gunakan unit QA.
4. Sebelum mutasi, catat nilai awal dan ambil screenshot/query DB read-only.
5. Jangan impor dump SQL, reset database, atau menjalankan `sync_to_laragon.ps1` saat pengguna lain sedang mengubah Laragon2.
6. Tes otomatis yang menulis data hanya boleh dipakai jika seluruh perubahan berada dalam transaksi rollback dan telah membuktikan data QA tersisa `0`.
7. Jika UI hanya menyediakan periode/data operasional dan tidak menyediakan periode QA terisolasi, hentikan skenario tersebut dengan status **BLOCKED**, bukan mengubah data nyata.
8. Satu skenario dinyatakan PASS hanya jika UI, respons HTTP, refresh/login ulang, dan DB memberikan hasil yang konsisten.

## 3. Persiapan dan lembar bukti

Siapkan dua browser atau satu browser normal dan satu incognito:

- Browser A: Administrator/approver.
- Browser B: role operasional non-admin dengan lokasi terbatas.
- Akun QA admin: `________________`.
- Akun QA non-admin: `________________`.
- Lokasi akun QA: `________________`.
- Unit QA: `QA-FINISH-ASSET-________________`.
- Waktu mulai: `________________`.

Catat baseline di HeidiSQL:

```sql
SELECT COUNT(*) AS users_total FROM users;
SELECT COUNT(*) AS assets_total FROM assets;
SELECT COUNT(*) AS work_orders_total FROM work_orders;
SELECT COUNT(*) AS archived_total FROM archived_items;
SELECT COUNT(*) AS notification_recipient_total FROM notification_recipients;
SELECT COUNT(*) AS settings_total FROM operational_settings;
SELECT COUNT(*) AS kpi_rows_total FROM head_kpi_assessments;
```

Untuk setiap skenario, simpan:

| Bukti | Isi |
| --- | --- |
| Akun | Username, role, lokasi; jangan mencatat password |
| Browser | URL, waktu, screenshot sebelum/sesudah |
| Network | Method, endpoint, HTTP status, response code/message |
| Database | Query SELECT sebelum/sesudah dan ID QA |
| Cleanup | Nilai dipulihkan atau data QA tersisa 0 |
| Status | PASS / FAIL / BLOCKED / NOT TESTED |

---

## 4. F0 — Pengamanan akun dan otorisasi

### F0.1 Akses daftar pengguna

1. Login Browser A sebagai Administrator dan buka **Pengaturan → Manajemen Pengguna**.
2. Pastikan GET `api/users.php` menghasilkan HTTP 200.
3. Login Browser B sebagai non-admin dan buka menu/URL yang sama.
4. Pastikan menu tidak tersedia atau GET menghasilkan HTTP 403.
5. Pastikan respons tidak membocorkan daftar username, role, atau lokasi.

Kriteria PASS: admin dapat membaca; non-admin ditolak oleh server, bukan hanya disembunyikan oleh UI.

### F0.2 Membuat akun QA

1. Catat `users_total` dan `user_roles` sebelum uji.
2. Sebagai Administrator, buat akun bernama `QA-FINISH-USER-<tanggal/jam>`.
3. Pilih role non-admin dan lokasi QA.
4. Simpan dan pastikan respons HTTP sukses hanya sekali.
5. Refresh lalu cari akun yang sama.

```sql
SELECT user_id, username, full_name, role_id, assigned_location_id, is_active, created_at
FROM users
WHERE username LIKE 'QA-FINISH-USER-%';
```

Kriteria PASS: tepat satu akun terbentuk, role/lokasi benar, dan refresh tidak menggandakan akun.

### F0.3 Toggle status dan login

1. Nonaktifkan akun QA dari Browser A.
2. Logout lalu coba login memakai akun QA; login harus ditolak.
3. Aktifkan kembali akun QA.
4. Login harus berhasil kembali.
5. Refresh halaman admin dan pastikan status tetap sama dengan DB.

```sql
SELECT user_id, username, is_active
FROM users
WHERE username = 'QA-FINISH-USER-...';
```

### F0.4 Ganti password sendiri

1. Login sebagai akun QA.
2. Coba ganti password dengan password lama yang salah; harus ditolak.
3. Gunakan password lama yang benar; perubahan harus berhasil.
4. Logout dan pastikan password lama tidak bisa digunakan.
5. Login memakai password baru.
6. Kembalikan password QA ke nilai pengujian yang disepakati sebelum cleanup.

Jangan menjalankan skenario ini pada akun bersama atau akun operasional.

### F0.5 CSRF, sesi, dan role negatif

Gunakan DevTools Network pada request QA:

> **Penting:** jangan memakai `fetch()` dari Console untuk uji token kosong/palsu.
> `scripts/auth-client.js` membungkus `window.fetch` dan otomatis mengganti header
> mutasi dengan token CSRF sesi yang valid. Gunakan `XMLHttpRequest` berikut agar
> request yang diuji benar-benar dikirim tanpa token atau dengan token palsu.

Saat login sebagai Administrator, uji tanpa token:

```javascript
(() => {
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/users.php');
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onload = () => console.log('HTTP', xhr.status, xhr.responseText);
  xhr.send(JSON.stringify({ action: 'create' }));
})();
```

Hasil wajib: `HTTP 403` dengan kode `CSRF_INVALID`.

Kemudian uji token palsu:

```javascript
(() => {
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/users.php');
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.setRequestHeader('X-CSRF-Token', 'token-palsu-qa');
  xhr.onload = () => console.log('HTTP', xhr.status, xhr.responseText);
  xhr.send(JSON.stringify({ action: 'create' }));
})();
```

Hasil wajib: `HTTP 403` dengan kode `CSRF_INVALID`. Respons `422` berarti token
valid masih ikut terkirim atau pemeriksaan CSRF gagal dan skenario belum PASS.

1. POST tanpa sesi harus 401/403.
2. POST tanpa `X-CSRF-Token` atau dengan token salah harus 403.
3. Non-admin mencoba create/toggle/delete harus 403 sebelum perubahan DB.
4. Pengguna lokasi lain tidak boleh memperoleh data yang berada di luar cakupannya.
5. Hitungan akun dan status QA tidak boleh berubah pada seluruh skenario negatif.

### F0.6 Delete dan audit

1. Hapus akun QA yang belum memiliki transaksi operasional.
2. Pastikan akun benar-benar hilang atau dinonaktifkan secara aman bila sudah direferensikan.
3. Cari bukti audit aktor, waktu, jenis aksi, dan target.
4. Jika tidak ada audit trail mutasi akun, catat **FAIL P1 — user mutation audit belum tersedia**.

```sql
SELECT COUNT(*) AS qa_user_remaining
FROM users
WHERE username LIKE 'QA-FINISH-USER-%';

SELECT audit_id, action, actor_user_id, actor_username,
       target_user_id, target_username, details_json, created_at
FROM user_audit_trail
WHERE target_username LIKE 'qa-finish-user-%'
ORDER BY audit_id DESC;
```

Gate F0 PASS apabila create, toggle, password sendiri, delete/soft-delete, CSRF, sesi, role/lokasi, refresh, dan audit seluruhnya terbukti.

---

## 5. F1B — Sinkronisasi aset/WO terarah

### F1B.1 Persiapan unit QA

1. Buat atau pilih unit khusus `QA-FINISH-ASSET-<tanggal/jam>`.
2. Catat `status`, `last_hm_km`, lokasi, jumlah WO, dan jumlah inspeksi.
3. Jangan menggunakan `CS-41001`, unit yang sedang `ACCIDENT_HOLD`, atau unit operasional lain.

```sql
SELECT asset_id, asset_code, status, current_location_id, last_hm_km, last_update_timestamp
FROM assets
WHERE asset_id = 'QA-FINISH-ASSET-...';

SELECT COUNT(*) AS wo_count FROM work_orders WHERE asset_id = 'QA-FINISH-ASSET-...';
SELECT COUNT(*) AS inspection_count FROM inspections WHERE asset_id = 'QA-FINISH-ASSET-...';
```

### F1B.2 Mutasi satu ID

1. Dari Browser A, ubah satu status unit QA menggunakan alur resmi.
2. Network harus menunjukkan payload `mode: targeted` dan hanya satu `asset_id`.
3. Refresh dan login ulang.
4. Pastikan hanya unit QA yang berubah.
5. Total aset dan WO lain tidak berubah.

Kriteria PASS: UI = respons API = baris DB untuk ID QA yang sama.

### F1B.3 Konflik dua browser

1. Buka unit QA yang sama pada Browser A dan B.
2. Browser B menyimpan perubahan status terlebih dahulu.
3. Tanpa refresh Browser A, simpan perubahan berbeda.
4. Browser A harus menerima HTTP 409 dan instruksi refresh.
5. Perubahan Browser B tetap utuh di DB.
6. UI Browser A tidak boleh menampilkan pesan sukses palsu.

### F1B.4 P2H dan idempotensi

1. Kirim P2H kritis satu kali pada unit QA.
2. Pastikan tepat satu inspeksi dan paling banyak satu WO dibuat.
3. Ulangi/replay request yang sama; jumlah tidak boleh bertambah.
4. Uji P2H normal; status tidak boleh menjadi breakdown tanpa alasan.
5. Uji input invalid; server harus menolak tanpa perubahan DB.

### F1B.5 Condition Monitoring, role, dan lokasi

1. Simpan satu temuan berbahaya pada unit QA.
2. Periksa perubahan status/WO setelah refresh dan login ulang.
3. Role tanpa `sync.write` harus mendapat 403.
4. Akun dari lokasi lain harus mendapat 403/404 sesuai kebijakan tanpa membocorkan detail unit.
5. Kirim payload massal berisi ID fiktif; harus 422 sebelum transaksi.

### F1B.6 Gangguan jaringan

1. Blokir endpoint sync melalui DevTools pada akun QA.
2. Coba simpan perubahan.
3. UI harus menampilkan gagal dan tidak mengubah state lokal seolah-olah sukses.
4. Aktifkan kembali jaringan dan refresh; UI harus mengikuti DB.

Gate F1 PASS apabila F1A tetap lulus dan seluruh skenario F1B di atas terbukti tanpa perubahan lintas ID.

---

## 6. F4 — People & KPI resmi

### F4.0 Tujuan, akun, dan aturan keselamatan

F4 membuktikan bahwa penilaian KPI dan review SPL merupakan data resmi di database, bukan hanya perubahan tampilan browser. Siapkan:

- Browser A: akun penilai yang berwenang, bukan akun approver.
- Browser B/incognito: Administrator atau General Manager sebagai approver.
- Browser C/incognito lain bila tersedia: Maintenance Planner/Mekanik untuk uji penolakan role.
- Periode draf QA: `2099-12`.
- Periode rejection QA: `2099-11`.
- Lokasi QA yang sama pada akun penilai dan data pengujian.
- DevTools tab **Network** dengan opsi **Preserve log** aktif.
- HeidiSQL pada database `u646470441_serviceplanbra`.

Jangan menggunakan Januari 2026 untuk mutasi. Jangan mengubah 10 baris `head_kpi_assessments` dan 5 baris `planner_evaluations` historis.

#### Status kesiapan per 24 Oktober 2026

Blocker periode hardcoded sudah diperbaiki. Pemilih periode menerima rentang `2026-01` sampai `2099-12`, lokasi dimuat sesuai scope akun, dan payload Simpan/Submit/Approve membawa `period` serta `location_id` yang dipilih. Backend memvalidasi 10 indikator, total bobot 100%, role/lokasi, transisi status, pemisahan penilai–approver, dan mencatat audit lifecycle. Tetap lakukan preflight F4.2 untuk membuktikan versi yang tampil di browser adalah versi terbaru dari Laragon2.

### F4.1 Baseline database dan bukti awal

Jalankan di HeidiSQL sebelum membuka menu People & KPI:

```sql
SELECT period_key, status, COUNT(*) AS indicator_count,
       MIN(kpi_id) AS first_id, MAX(kpi_id) AS last_id,
       MIN(updated_at) AS first_update, MAX(updated_at) AS last_update
FROM head_kpi_assessments
GROUP BY period_key, status
ORDER BY period_key, status;

SELECT period_key, planner_user_id, COUNT(*) AS evaluation_count,
       MIN(eval_id) AS first_id, MAX(eval_id) AS last_id,
       MIN(updated_at) AS first_update, MAX(updated_at) AS last_update
FROM planner_evaluations
GROUP BY period_key, planner_user_id
ORDER BY period_key, planner_user_id;

SELECT COUNT(*) AS qa_kpi_before
FROM head_kpi_assessments
WHERE period_key IN ('2099-11', '2099-12');

SELECT COUNT(*) AS spl_reviews_before FROM spl_planner_reviews;
SELECT COUNT(*) AS overtime_rows_before FROM spl_overtime_records;
```

Catat atau screenshot hasilnya. Kondisi aman untuk memulai:

- Januari 2026 tetap memiliki 10 indikator KPI Head.
- Evaluasi planner historis tetap memiliki 5 baris.
- `qa_kpi_before = 0`; jika tidak nol, hentikan dan periksa apakah itu data QA lama milik pengguna lain.
- Catat jumlah review SPL dan lembur agar perubahan dapat direkonsiliasi.

### F4.2 Preflight periode dan lokasi — wajib sebelum menulis

1. Login Browser A sebagai penilai.
2. Buka **People & KPI → KPI Head of Equipment**.
3. Periksa pilihan periode. Periode QA `Desember 2099` harus tersedia tanpa mengubah HTML melalui DevTools.
4. Jika hanya tahun 2026 yang tersedia, catat **BLOCKED** dan jangan klik Simpan Draf.
5. Jika periode QA sudah tersedia, pilih `2099-12`.
6. Buka DevTools → Network, lalu cari request `people_kpi.php`.
7. Request baca harus berbentuk:

   ```text
   GET api/people_kpi.php?action=head_kpi&period=2099-12&location_id=ID_LOKASI_QA
   HTTP 200
   ```

8. Pastikan UI menunjukkan periode Desember 2099 dan status `DRAFT` baru.
9. Sebelum mengirim, periksa bahwa implementasi akan mengirim:

   ```json
   {
     "period": "2099-12",
     "location_id": "ID_LOKASI_QA",
     "status": "DRAFT"
   }
   ```

10. Jika payload menunjukkan `2026-01` atau tidak memiliki `location_id`, hentikan. Jangan meneruskan F4.3–F4.5.

Kriteria PASS preflight: periode yang dipilih sama pada UI, URL GET, payload mutasi, dan lokasi akun QA.

### F4.3 Simpan DRAFT

Jalankan hanya jika F4.2 lulus.

1. Catat skor awal indikator nomor 1 dan total nilai pada layar.
2. Ubah skor indikator nomor 1 ke nilai berbeda antara `1–5`.
3. Klik **Simpan Draf** satu kali.
4. Pada Network, buka request:

   ```text
   POST api/people_kpi.php?action=save_head_kpi
   ```

5. Pastikan HTTP `200`, respons `status: success`, payload `period: 2099-12`, `status: DRAFT`, dan `location_id` benar.
6. Refresh halaman menggunakan `Ctrl+F5`.
7. Pilih kembali `2099-12`; skor dan status harus tetap.
8. Logout/login ulang Browser A; nilai harus tetap sama.
9. Jalankan:

```sql
SELECT kpi_id, period_key, location_id, indicator_no,
       score_val, weight_pct, weighted_score,
       ROUND((score_val * weight_pct) / 5, 2) AS expected_weighted_score,
       status, version, assessor_user_id, evaluated_user_id,
       submitted_at, approved_by, approved_at, approval_notes,
       created_at, updated_at
FROM head_kpi_assessments
WHERE period_key = '2099-12'
ORDER BY indicator_no;

SELECT period_key, location_id, status, version,
       COUNT(*) AS indicator_count,
       SUM(weight_pct) AS total_weight,
       SUM(weighted_score) AS total_weighted_score
FROM head_kpi_assessments
WHERE period_key = '2099-12'
GROUP BY period_key, location_id, status, version;
```

Hasil wajib:

- Tepat 10 indikator.
- `location_id` tidak `NULL` dan sesuai lokasi QA.
- `status = DRAFT`.
- `assessor_user_id` adalah akun Browser A.
- Setiap `weighted_score = score_val × weight_pct ÷ 5`.
- Total bobot sesuai konfigurasi resmi dan UI sama dengan `SUM(weighted_score)`.
- Refresh dan login ulang tidak mengembalikan nilai lama.

### F4.4 Submit dan idempotensi

1. Pada Browser A dan periode `2099-12`, klik **Ajukan Penilaian (Submit)**.
2. Konfirmasi satu kali.
3. Network harus menunjukkan HTTP `200`, payload `status: SUBMITTED`, periode dan lokasi QA yang benar.
4. Status UI menjadi `SUBMITTED` dan waktu pengajuan tampil.
5. Refresh dan login ulang; status tetap `SUBMITTED`.
6. Jangan klik berkali-kali. Jika request terulang karena refresh/retry, jumlah indikator tetap 10 dan tidak boleh menjadi 20.

```sql
SELECT period_key, location_id, status, version,
       COUNT(*) AS indicator_count,
       COUNT(DISTINCT submitted_at) AS submitted_timestamp_count,
       MIN(submitted_at) AS submitted_at
FROM head_kpi_assessments
WHERE period_key = '2099-12'
GROUP BY period_key, location_id, status, version;
```

Hasil wajib: `SUBMITTED`, tepat 10 baris, satu timestamp submit, dan tidak ada duplikasi.

### F4.5 Approval positif dan pemisahan tugas

1. Biarkan Browser A tetap login sebagai penilai.
2. Login Browser B sebagai Administrator/General Manager yang berbeda.
3. Buka periode `2099-12`.
4. Tombol **Setujui (Approve GM)** hanya muncul pada Browser B.
5. Klik Approve dan isi catatan unik, misalnya `QA-F4-APPROVE-2099-12`.
6. Network harus menghasilkan HTTP `200` dan keputusan `APPROVED`.
7. Refresh/login ulang pada Browser A dan B.
8. Kedua browser harus menampilkan status dan approver yang sama.

```sql
SELECT period_key, status, version, assessor_user_id,
       approved_by, approved_at, approval_notes,
       COUNT(*) AS indicator_count
FROM head_kpi_assessments
WHERE period_key = '2099-12'
GROUP BY period_key, status, version, assessor_user_id,
         approved_by, approved_at, approval_notes;
```

Hasil wajib:

- `status = APPROVED`.
- `approved_by` adalah Browser B dan berbeda dari `assessor_user_id`.
- `approved_at` serta catatan persetujuan terisi.
- Tombol Simpan/Submit hilang atau server menolak edit biasa setelah approval.
- Percobaan edit setelah approval tidak boleh menghapus keputusan lama atau kembali menjadi DRAFT tanpa proses revisi/versioning resmi.

Jika akun yang sama dapat menilai sekaligus menyetujui, tandai **FAIL P1 — pemisahan tugas belum ditegakkan server**.

### F4.6 Rejection dan perbaikan

Gunakan periode terpisah `2099-11`, bukan mengubah data approval pada `2099-12`.

1. Ulangi DRAFT dan SUBMITTED untuk `2099-11`.
2. Browser B pilih keputusan `REJECTED`.
3. Isi alasan wajib, misalnya `QA-F4-REJECT-SKOR-PERLU-DIPERBAIKI`.
4. Refresh kedua browser.
5. Pastikan status `REJECTED`, approver, waktu, dan alasan tetap tersimpan.
6. Jika revisi diizinkan, ubah skor sebagai Browser A dan submit ulang.
7. Versi harus bertambah atau riwayat keputusan lama tetap tersedia. Jika data lama langsung ditimpa tanpa riwayat, tandai **FAIL P1 — versioning/audit lifecycle belum tersedia**.

```sql
SELECT period_key, status, version, assessor_user_id,
       approved_by, approved_at, approval_notes, updated_at
FROM head_kpi_assessments
WHERE period_key = '2099-11'
ORDER BY indicator_no;
```

### F4.7 Role negatif dan request langsung

1. Login Browser C sebagai Maintenance Planner atau Mekanik.
2. Pastikan tombol Approve tidak terlihat.
3. Buka DevTools → Console dan jalankan request aman terhadap periode QA yang tidak ada:

```javascript
fetch('api/people_kpi.php?action=approve_head_kpi', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    period: '2099-10',
    decision: 'APPROVED',
    approval_notes: 'QA role negative'
  })
}).then(async r => console.log('HTTP', r.status, await r.text()));
```

`auth-client.js` akan memasukkan CSRF sesi yang sah. Server tetap wajib menjawab `HTTP 403` dengan `FORBIDDEN` karena role tidak berwenang.

4. Pastikan tidak ada data yang terbentuk:

```sql
SELECT COUNT(*) AS unauthorized_rows
FROM head_kpi_assessments
WHERE period_key = '2099-10';
```

Hasil wajib: `unauthorized_rows = 0`.

### F4.8 Review SPL Planner

Jangan memakai SPL operasional secara sembarang. Gunakan SPL QA/final yang telah disetujui. Jika tidak tersedia, tandai **BLOCKED — tidak ada SPL QA final**.

1. Catat SPL QA dan baseline review:

```sql
SELECT overtime_id, report_id, spl_number, spl_date, location_id,
       employee_user_id, duration_hours, start_verified,
       end_verified, reversed_at
FROM spl_overtime_records
WHERE spl_number LIKE 'QA-%'
ORDER BY overtime_id DESC;

SELECT review_id, report_id, spl_number, planner_user_id,
       review_status, review_notes, reviewed_at
FROM spl_planner_reviews
WHERE spl_number LIKE 'QA-%'
ORDER BY reviewed_at DESC;
```

2. Login sebagai Maintenance Planner pada lokasi yang sesuai.
3. Buka **People & KPI → Evaluasi Maintenance Planner**.
4. Pilih SPL QA dan klik **Catat sudah ditinjau Planner**.
5. Isi catatan unik `QA-F4-SPL-REVIEW` bila kolom tersedia.
6. Refresh lalu buka browser lain dengan akun Planner yang sama.
7. Status review harus tetap ada dan berasal dari database.
8. Jalankan query review kembali; `planner_user_id` harus sama dengan akun Planner dan hanya satu review aktif untuk SPL tersebut.
9. Role yang bukan Planner mencoba request langsung harus ditolak. Jika berhasil menulis review, tandai **FAIL P1 — role review SPL belum ditegakkan server**.

### F4.9 Leaderboard lembur dan data kosong

1. Buka tab **Absensi & Lembur Tim**.
2. Pilih periode SPL QA.
3. Jika tidak ada SPL final, layar harus menampilkan `Belum ada dokumen lembur / SPL terverifikasi`, bukan nama/jam contoh.
4. Jika ada data, cocokkan UI dengan query:

```sql
SELECT s.employee_user_id,
       COALESCE(u.full_name, s.role_snapshot) AS employee_name,
       s.role_snapshot, l.location_name,
       COUNT(DISTINCT s.spl_number) AS total_spl,
       SUM(s.duration_hours) AS total_hours,
       SUM(CASE WHEN s.end_verified = 1 THEN 1 ELSE 0 END) AS verified_sessions
FROM spl_overtime_records s
LEFT JOIN users u ON u.user_id = s.employee_user_id
LEFT JOIN locations l ON l.location_id = s.location_id
WHERE s.reversed_at IS NULL
  AND s.spl_date >= '2099-12-01'
  AND s.spl_date <  '2100-01-01'
GROUP BY s.employee_user_id, employee_name, s.role_snapshot, l.location_name
ORDER BY total_hours DESC;
```

5. Baris dengan `reversed_at IS NOT NULL` tidak boleh dihitung.
6. Urutan, jumlah SPL, sesi terverifikasi, total jam, nama, dan lokasi harus sama dengan UI.

### F4.10 Ekspor CSV

1. Pilih periode QA yang telah diuji.
2. Catat status, versi, lokasi, total skor, dan sumber pada UI/DB.
3. Klik **Ekspor Laporan KPI**.
4. Buka CSV dan pastikan minimal memuat:
   - periode;
   - lokasi;
   - status lifecycle;
   - versi;
   - indikator dan skor;
   - bobot dan nilai bobot;
   - catatan;
   - sumber data atau label simulasi/draf.
5. Hitung ulang satu indikator: `score × weight ÷ 5` harus sama dengan CSV dan DB.
6. Draf atau simulasi tidak boleh diberi label `APPROVED`.
7. Jika nama file/periode masih tetap `2026` ketika periode lain dipilih, atau CSV tidak memiliki status/lokasi/versi, tandai **FAIL P2 — ekspor belum merepresentasikan versi tersimpan**.

### F4.11 Audit lifecycle, cleanup, dan format hasil

Periksa apakah setiap transisi memiliki riwayat terpisah, bukan hanya kondisi terakhir:

```sql
SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name LIKE '%kpi%audit%';
```

Jika tidak ada audit append-only untuk DRAFT, SUBMITTED, APPROVED/REJECTED, revisi, aktor, dan waktu, catat **FAIL P1 — audit lifecycle KPI belum lengkap**.

Jangan menjalankan `DELETE` manual pada database bersama. Setelah bukti disimpan, cleanup periode QA dilakukan melalui alur aplikasi/migrasi cleanup yang disetujui. Pastikan data historis tetap sama:

```sql
SELECT period_key, status, COUNT(*) AS indicator_count,
       MIN(updated_at) AS first_update, MAX(updated_at) AS last_update
FROM head_kpi_assessments
WHERE period_key = '2026-01'
GROUP BY period_key, status;

SELECT period_key, planner_user_id, COUNT(*) AS evaluation_count,
       MIN(updated_at) AS first_update, MAX(updated_at) AS last_update
FROM planner_evaluations
WHERE period_key = '2026-01'
GROUP BY period_key, planner_user_id;
```

Catat hasil akhir:

| Skenario | UI | HTTP | DB | Status |
| --- | --- | --- | --- | --- |
| Baseline historis 10/5 |  | read-only |  | PASS/FAIL |
| Periode dan lokasi QA |  | GET/POST |  | PASS/BLOCKED |
| DRAFT + refresh/login ulang |  | 200 |  | PASS/FAIL |
| SUBMITTED + idempotensi |  | 200 |  | PASS/FAIL |
| APPROVED beda aktor |  | 200 |  | PASS/FAIL |
| REJECTED + revisi/versioning |  | 200/409 |  | PASS/FAIL |
| Role negatif | tombol tersembunyi | 403 | tidak berubah | PASS/FAIL |
| Review SPL Planner |  | 200/403 |  | PASS/BLOCKED |
| Leaderboard/empty state |  | 200 | agregat sama | PASS/FAIL |
| CSV |  | unduhan | sama dengan DB | PASS/FAIL |
| Audit lifecycle |  |  | append-only | PASS/FAIL |

Gate F4 hanya PASS apabila semua skenario penting lulus di browser, HTTP, dan DB; approver berbeda dari penilai; role/lokasi lain ditolak; refresh/login ulang konsisten; CSV sama dengan versi tersimpan; audit tersedia; serta 10/5 baris historis tidak berubah.

---

## 7. F5 — Archive, notifikasi, dan pengaturan

### F5.1 Archive dan restore unit QA

1. Gunakan unit `QA-FINISH-ASSET-...`; jangan memakai `EXC-00001` atau unit operasional.
2. Archive dengan alasan `QA FINISH archive test`.
3. Pastikan unit hilang dari daftar aktif dan muncul di Archive.
4. Refresh/login ulang; status arsip harus tetap.
5. Restore dengan alasan `QA FINISH restore test`.
6. Pastikan unit kembali aktif.

```sql
SELECT item_type, item_id, archived_at, archived_by_user_id,
       archived_by_name, reason
FROM archived_items
WHERE item_id = 'QA-FINISH-ASSET-...';

SELECT action, actor_user_id, actor_name, reason, created_at
FROM archive_audit_trail
WHERE item_id = 'QA-FINISH-ASSET-...'
ORDER BY audit_id;
```

Kriteria PASS: marker aktif hilang setelah restore, tetapi dua audit event `ARCHIVE` dan `RESTORE` tetap ada.

### F5.2 Fail-closed Archive

1. Blokir GET `api/archive.php` melalui DevTools.
2. Refresh.
3. UI harus menampilkan banner kegagalan dan tidak boleh menampilkan item arsip sebagai data aktif yang dipercaya.
4. Pulihkan jaringan dan refresh; daftar harus kembali sesuai DB.

### F5.3 Notifikasi

1. Gunakan notifikasi QA yang ditujukan kepada akun penguji.
2. Catat badge unread dan `read_at` awal.
3. Klik satu notifikasi; badge turun dan `read_at` terisi.
4. Refresh/login ulang; notifikasi tetap terbaca.
5. Uji **Tandai Semua Dibaca** pada akun QA.
6. Akun penerima lain tidak boleh ikut berubah.
7. Request tanpa CSRF harus 403.
8. Tautan notifikasi harus menuju objek yang boleh dilihat role/lokasi tersebut; jika tidak berwenang harus 403/404.

```sql
SELECT notification_id, user_id, location_id, read_at, created_at
FROM notification_recipients
WHERE user_id = <ID_USER_QA>
ORDER BY created_at DESC;
```

### F5.4 Operational settings

1. Sebagai Administrator, catat nilai awal `cfg_sla_wo`.
2. Ubah ke nilai QA yang valid, simpan, lalu refresh/login ulang.
3. Pastikan `updated_by_user_id`, nama, dan waktu berubah.
4. Uji nilai di bawah/di atas batas; harus 422/validasi gagal tanpa mengubah DB.
5. Role non-admin harus mendapat 403.
6. Kembalikan `cfg_sla_wo` ke nilai awal melalui UI dan verifikasi DB.

```sql
SELECT setting_key, setting_value, category,
       updated_by_user_id, updated_by_name, updated_at
FROM operational_settings
WHERE setting_key = 'cfg_sla_wo';
```

7. Pastikan panel Master Site Project dan Standard Rates yang belum operasional berlabel Nonaktif/Read-Only dan tidak menampilkan sukses palsu.

Gate F5 PASS apabila archive/restore, audit, fail-closed, notifikasi per penerima, CSRF, settings, validasi, role negatif, dan persistensi refresh/login ulang seluruhnya terbukti.

---

## 8. F6 — Rekonsiliasi analitik dan regression gate

### F6.1 Temuan awal yang harus ditutup

1. Hapus atau fail-close data KOMTRAX hardcoded yang masih dapat tampil ketika API produktivitas gagal.
2. Rekonsiliasi dua baris `telematics_logs` tanpa `asset_id`; jangan mengarang pemetaan jika serial number belum terbukti.
3. Karena `report_operation_logs` dan template LHO belum memiliki data, tampilkan `Belum ada LHO`, bukan nol atau data contoh.
4. Jangan membandingkan telematika Januari 2026 dengan Fuel Agustus 2026 tanpa periode yang sama.
5. Aturan biaya sudah diputuskan: biaya tercatat tepat sekali ketika PO disetujui; SPPU tidak mencatat biaya sendiri; pembatalan PO melakukan reversal.

### F6.2 Rekonsiliasi kartu dengan sumber DB

Untuk Fuel, Produktivitas, KPI, Biaya, Telematika, dan Laporan, catat:

- periode;
- unit dan lokasi;
- jumlah baris sumber;
- rumus dan versi formula;
- nilai kartu;
- total detail;
- kualitas data/sumber hilang.

Kartu harus sama dengan jumlah detail dan query agregat DB. Jika sumber tidak ada, tampilkan status sumber hilang.

### F6.3 Regresi role dan lokasi

Ulangi menu utama memakai sedikitnya:

1. Administrator.
2. Planner/approver operasional.
3. Mekanik/operator terbatas.
4. Pengguna dari lokasi berbeda.

Periksa menu terlihat sesuai izin dan API tetap menolak akses langsung yang tidak berwenang.

### F6.4 Desktop dan mobile

Uji lebar 320, 360, 375, 390, 430, tablet, dan desktop untuk:

- login;
- Dashboard;
- Master Asset/detail;
- WO/SPB;
- Laporan;
- People & KPI;
- HSE;
- Produktivitas/Fuel/Biaya;
- Approval;
- Archive;
- Settings.

Tidak boleh ada kontrol tanpa nama, tombol tidak dapat ditekan, tabel memotong aksi tanpa scroll lokal, atau modal keluar viewport.

### F6.5 Refresh, login ulang, ekspor, dan reversal

1. Semua status resmi tetap setelah refresh dan login ulang.
2. Void/reversal kedua tidak menggandakan pembalikan.
3. Ekspor CSV/PDF sama dengan versi tersimpan dan filter aktif.
4. Data pada menu sumber dan menu hilir konsisten.
5. Tidak ada data QA atau transaksi yatim setelah cleanup.

### F6.6 Tes otomatis aman

Tes yang telah lulus dan dapat diulang saat kode stabil:

```powershell
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests\verify_kpi_math.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests\batch6_readonly_audit.php
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests\f2_cost_rollback.php
```

Hasil acuan 23 Oktober 2026: KPI 22/22, audit read-only 9/9, dan biaya rollback 6/6 dengan sisa QA 0.

Jangan menjalankan `productivity_integration.php` atau `report_lho_integration.php` pada DB bersama sebelum memastikan persiapan tabel/DDL-nya tidak berjalan di luar transaksi.

---

## 9. Cleanup akhir

1. Pulihkan seluruh settings ke nilai awal.
2. Restore seluruh data QA yang masih terarsip.
3. Hapus/nonaktifkan akun dan unit QA hanya setelah memastikan tidak mempunyai relasi operasional yang harus dipertahankan.
4. Jangan menghapus audit trail; audit harus tetap menjadi bukti pengujian.
5. Pastikan tidak ada transaksi QA aktif.
6. Catat total tabel penting dan bandingkan dengan baseline; selisih harus dapat dijelaskan.

## 10. Release gate

Backend boleh dinyatakan selesai hanya jika:

- F0, F1, F4, dan F5 berstatus PASS browser + DB;
- temuan P1/P2 F6 ditutup atau diterima secara eksplisit dengan mitigasi;
- seluruh role/lokasi negatif ditolak server;
- refresh, login ulang, dua browser, dan mobile lulus;
- void/reversal idempoten;
- ekspor sama dengan DB;
- tidak ada mock/fallback yang menyamarkan API gagal;
- cleanup selesai tanpa merusak data operasional;
- pengguna menyetujui checkpoint akhir.

## 11. Rekap hasil

| Checkpoint | Browser | HTTP/API | Database | Cleanup | Status akhir |
| --- | --- | --- | --- | --- | --- |
| F0 |  |  |  |  |  |
| F1B |  |  |  |  |  |
| F4 |  |  |  |  |  |
| F5 |  |  |  |  |  |
| F6 |  |  |  |  |  |

Temuan baru:

| ID | Prioritas | Menu/alur | Bukti | Dampak | Usulan batch |
| --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |
