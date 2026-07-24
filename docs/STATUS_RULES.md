# Status Rules (State Machine)

Sistem memberlakukan aturan ketat pada status objek agar integritas laporan dan kalkulasi KPI terjaga (tidak ada input teks bebas untuk status).

## 1. Status Unit (Asset Status)
Sebuah unit hanya boleh memiliki **satu** status yang efektif pada satu waktu. Riwayat perpindahan status (timeline) disimpan dan tidak boleh dihapus.
Status yang dikunci sistem:
- **READY**: Unit sehat, belum dioperasikan.
- **OPERATING**: Unit sehat, sedang dioperasikan (dapat diturunkan dari aktivitas).
- **STANDBY**: Unit sehat namun menunggu karena faktor eksternal/cuaca.
- **INSPECTION**: Unit sedang diinspeksi.
- **PM**: Unit sedang dalam perawatan berkala.
- **BREAKDOWN**: Unit rusak, tidak dapat beroperasi.
- **WAITING_PART**: Pekerjaan tertunda menunggu ketersediaan komponen/part.
- **MOBILIZATION**: Unit sedang dipindahkan antar lokasi.
- **ACCIDENT_HOLD**: Unit ditahan karena kecelakaan (investigasi).
- **INACTIVE**: Unit tidak aktif / grounded.
- **DISPOSED**: Unit telah dijual/dibuang.

*Aturan Tambahan*: Status unit harus otomatis berubah ketika sebuah dokumen Work Order prioritas tinggi terjadi (Misal: WO Breakdown Dibuat -> Unit otomatis BREAKDOWN). Jika WAITING_PART, downtime jam kerja mekanik dijeda, namun downtime availability unit tetap berjalan (kalkulasi AC-04).

## 2. Transisi Status Work Order (WO)
Siklus hidup WO harus mengikuti tahapan berikut secara kaku:

`DRAFT` → `SUBMITTED` → `APPROVED` → `ASSIGNED` → `IN_PROGRESS` ↔ `PAUSED/WAITING_PART` → `TESTING` → `VERIFIED` → `CLOSED`

**Aturan/SLA WO:**
- **Pembuatan**: Maksimal 30 menit setelah unit dinyatakan down, tiket/WO harus terbuat (SLA BR-01).
- **Mulai**: Pekerjaan tidak bisa `IN_PROGRESS` (tombol start) jika WO belum `APPROVED` dan belum ada `ASSIGNED` PIC (AC-02).
- **Penundaan**: Status `PAUSED` atau `WAITING_PART` digunakan saat mekanik menekan tombol pause, perlu input alasan wajib.
- **Penyelesaian**: Mekanik menyelesaikan kerja hingga `TESTING`.
- **Penutupan (AC-05)**: WO tidak dapat di-`CLOSED` tanpa:
  1. Bukti foto Before-After.
  2. Log waktu pekerjaan.
  3. Penyebab/Tindakan (Cause/Action).
  4. Hasil tes fungsi.
  5. Verifikasi oleh supervisor (verifikator).
- **Pembatalan**: Status `REJECTED`, `CANCELLED`, atau `REOPENED` wajib disertai alasan dan otorisasi approval (audit trail).
