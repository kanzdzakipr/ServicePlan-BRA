# Acceptance Criteria untuk Developer
Berikut adalah daftar kriteria penerimaan yang harus diuji dan dipastikan berfungsi saat sistem (MVP) diserahkan. Ini diadaptasi dari dokumen BPMN.

| ID | Kriteria Uji (Skenario) | Harapan Respons Sistem (MVP) |
|---|---|---|
| **AC-01** | Saat operator/pelapor menandai unit down, sistem mencatat waktu kejadian. | Sistem menghitung deadline WO otomatis 30 menit dan memberikan flag/alert jika lewat. |
| **AC-02** | Mekanik menekan tombol "Start Work" pada WO. | Tombol *disabled* (tidak aktif) atau tertolak jika WO belum *Approved* atau belum ada PIC yang ditugaskan. |
| **AC-04** | Mekanik menekan "Pause" dengan alasan `WAITING_PART` pada WO yang *In Progress*. | Repair-clock (waktu mekanik) berhenti, namun jam *Downtime Unit* (sejak dilaporkan rusak) tetap terus berjalan sampai RTW. |
| **AC-05** | Supervisor menekan tombol "Close WO". | Sistem memvalidasi dan menolak penutupan jika tidak ada: foto before-after, time log, cause/action, dan verifikasi akhir. |
| **AC-06** | User menginput HM/KM (Meter Reading) baru. | Input ditolak oleh sistem jika angka yang dimasukkan lebih kecil dari bacaan *HM/KM* terakhir pada unit tersebut. |
| **AC-08** | User membuka halaman Dashboard dan memilih dropdown Filter Lokasi. | Seluruh angka (kartu & grafik) otomatis ter-update, dan saat diklik, menampilkan daftar detail tabel (drill-down). |
| **AC-10** | Segregation of Duties. User `Admin1` membuat WO. | Sistem tidak menampilkan tombol Approve untuk `Admin1` pada WO tersebut, guna menghindari konflik kepentingan. |
| **AC-12** | User melihat halaman Detail Unit atau Detail WO. | Tersedia tab Timeline/Histori yang menampilkan jejak perubahan status dan persetujuan (bersumber dari `audit_logs`). |
