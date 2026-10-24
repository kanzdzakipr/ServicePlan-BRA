# Hasil Finishing Backend — 24 Oktober 2026

## Kesimpulan

**Backend F0–F6: PASS untuk lapisan server/API/database.**

Status ini didukung oleh request HTTP dengan sesi login nyata, CSRF, role berbeda,
mutasi data QA terisolasi, rekonsiliasi database, dan regresi otomatis. Status ini
tidak menggantikan release gate visual untuk browser/mobile.

## Bukti pengujian

### HTTP end-to-end

Perintah:

```powershell
& 'C:\laragon2\bin\php\php-8.3.16-Win32-vs16-x64\php.exe' tests\finishing_http_e2e.php
```

Hasil: **42/42 PASS**.

Cakupan:

- F0: autentikasi, sesi, daftar pengguna, create, toggle aktif/nonaktif, login
  akun nonaktif, password mandiri, CSRF kosong/palsu, role negatif, dan audit.
- F4: `DRAFT → SUBMITTED → APPROVED`, retry submit idempoten, penilai ditolak
  menyetujui, approver berbeda, data approved terkunci, 10 indikator/bobot 100%,
  dan audit lifecycle.
- F5: archive, tampil pada register Archive, restore, role negatif, audit,
  create/read notification, mark-read persisten, perubahan setting tervalidasi,
  role negatif, dan pemulihan nilai setting.

### Regresi otomatis F0–F6

Sebanyak 12 suite dijalankan dan seluruhnya exit code `0`: **205/205 PASS**.

| Checkpoint | Bukti utama | Hasil |
| --- | --- | --- |
| F0 | checkpoint akun/otorisasi | 34/34 PASS |
| F1 | sumber data dan sync terarah | 44/44 PASS |
| F2 | WO/SPB, PO, dan biaya | 15/15 PASS |
| F4 | kontrak dan lifecycle People & KPI | 39/39 PASS |
| F5 | Archive, notifikasi, settings | 36/36 PASS |
| F6 | rekonsiliasi analitik/regresi | 37/37 PASS |

Total bukti otomatis + HTTP: **247 pemeriksaan PASS**.

## Bukti cleanup database

Setelah tes:

| Pemeriksaan | Hasil |
| --- | ---: |
| akun `qa-finish-http-*` / `qa-finish-user-*` | 0 |
| aset `QA-FINISH-HTTP-*` / `QA-FINISH-ASSET-20*` | 0 |
| KPI periode `2099-*` | 0 |
| `archived_items` aktif | 0 |
| `cfg_sla_wo` | kembali ke `30` |

Tidak ada dump yang diimpor, database tidak di-reset, dan data operasional tidak
dihapus. File tes disinkronkan secara terarah ke Laragon2 dengan SHA-256 sumber
dan target yang sama.

## Status release gate

| Lapisan | Status |
| --- | --- |
| Kode/kontrak | PASS |
| HTTP/API dengan sesi nyata | PASS |
| Database dan audit trail | PASS |
| Cleanup data QA | PASS |
| Browser yang sebelumnya dikonfirmasi pengguna (F0/F1/F2/F3 dan Archive F5) | PASS sesuai konfirmasi |
| Visual F4/F5 lintas browser dan seluruh ukuran mobile | perlu konfirmasi manual akhir |

Backend boleh dipakai untuk melanjutkan pengujian pengguna. Pernyataan
“release keseluruhan selesai” tetap menunggu smoke test visual F4/F5 dan mobile,
karena hal tersebut tidak dapat dibuktikan hanya dari HTTP/API.

