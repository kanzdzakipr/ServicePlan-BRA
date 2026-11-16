# Update Dump Truck 26 Oktober-9 November 2026

Kumpulan ini mentranskripsikan 13 PDF operasional dump truck. Snapshot terbaru (9 November 2026) dipakai untuk memperbarui status, kode lambung, pelat, dan posisi Master Asset. Snapshot sebelumnya tetap disimpan sebagai jejak audit.

| Tanggal | PDF | Markdown | Baris unit |
|---|---|---|---:|
| 26-10-2026 | [UPDATE DUMPTRUCK 26.10.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 26.10.2026.pdf) | [UPDATE_DUMPTRUCK_26.10.2026.md](UPDATE_DUMPTRUCK_26.10.2026.md) | 74 |
| 27-10-2026 | [UPDATE DUMPTRUCK 27.10.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 27.10.2026.pdf) | [UPDATE_DUMPTRUCK_27.10.2026.md](UPDATE_DUMPTRUCK_27.10.2026.md) | 76 |
| 28-10-2026 | [UPDATE DUMPTRUCK 28.10.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 28.10.2026.pdf) | [UPDATE_DUMPTRUCK_28.10.2026.md](UPDATE_DUMPTRUCK_28.10.2026.md) | 76 |
| 29-10-2026 | [UPDATE DUMPTRUCK 29.10.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 29.10.2026.pdf) | [UPDATE_DUMPTRUCK_29.10.2026.md](UPDATE_DUMPTRUCK_29.10.2026.md) | 76 |
| 30-10-2026 | [UPDATE DUMPTRUCK 30.10.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 30.10.2026.pdf) | [UPDATE_DUMPTRUCK_30.10.2026.md](UPDATE_DUMPTRUCK_30.10.2026.md) | 76 |
| 31-10-2026 | [UPDATE DUMPTRUCK 31.10.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 31.10.2026.pdf) | [UPDATE_DUMPTRUCK_31.10.2026.md](UPDATE_DUMPTRUCK_31.10.2026.md) | 76 |
| 02-11-2026 | [UPDATE DUMPTRUCK 02.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 02.11.2026.pdf) | [UPDATE_DUMPTRUCK_02.11.2026.md](UPDATE_DUMPTRUCK_02.11.2026.md) | 76 |
| 03-11-2026 | [UPDATE DUMPTRUCK 03.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 03.11.2026.pdf) | [UPDATE_DUMPTRUCK_03.11.2026.md](UPDATE_DUMPTRUCK_03.11.2026.md) | 76 |
| 04-11-2026 | [UPDATE DUMPTRUCK 04.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 04.11.2026.pdf) | [UPDATE_DUMPTRUCK_04.11.2026.md](UPDATE_DUMPTRUCK_04.11.2026.md) | 76 |
| 05-11-2026 | [UPDATE DUMPTRUCK 05.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 05.11.2026.pdf) | [UPDATE_DUMPTRUCK_05.11.2026.md](UPDATE_DUMPTRUCK_05.11.2026.md) | 76 |
| 06-11-2026 | [UPDATE DUMPTRUCK 06.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 06.11.2026.pdf) | [UPDATE_DUMPTRUCK_06.11.2026.md](UPDATE_DUMPTRUCK_06.11.2026.md) | 76 |
| 07-11-2026 | [UPDATE DUMPTRUCK 07.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 07.11.2026.pdf) | [UPDATE_DUMPTRUCK_07.11.2026.md](UPDATE_DUMPTRUCK_07.11.2026.md) | 76 |
| 09-11-2026 | [UPDATE DUMPTRUCK 09.11.2026.pdf](../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE DUMPTRUCK 09.11.2026.pdf) | [UPDATE_DUMPTRUCK_09.11.2026.md](UPDATE_DUMPTRUCK_09.11.2026.md) | 76 |

## Aturan integrasi

- Nomor polisi dinormalisasi tanpa spasi hanya untuk pencocokan; tampilan tetap menggunakan format berjarak.
- `DT 1429` dan `DT 1440` memakai pelat `BM 9819 QO` pada semua snapshot. Master aset dan dokumen aset sebelumnya memastikan hanya satu aset fisik (`DT-04029`), sehingga konflik disimpan di snapshot dan tidak dibuat menjadi dua aset.
- `DT-00152` adalah duplikat lama `DT-00056` untuk pelat `B 9892 PYW`. Referensi historis dipindahkan ke aset kanonis, lalu record duplikat dinonaktifkan dan diarsipkan.
- Data aset di luar 75 nomor polisi unik pada snapshot terbaru tidak disentuh oleh migrasi.
