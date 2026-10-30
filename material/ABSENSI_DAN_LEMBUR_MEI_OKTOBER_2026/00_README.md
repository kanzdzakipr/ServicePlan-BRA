# Arsip Absensi dan Lembur Mei-Oktober 2026

Folder ini merupakan hasil parsing dan konversi 9 PDF serta 4 workbook Excel.

setiap tabel terpisah jadi satu markdown

## Hasil PDF

- [Fingerprint 21 Mei-20 Juni 2026](pdf/01_fingerprint_21_mei_20_juni_2026.md) - 16 halaman, diekstrak dari lapisan teks PDF.
- [Fingerprint 21 Juni-21 Juli 2026](pdf/02_fingerprint_21_juni_21_juli_2026.md) - 15 halaman, diekstrak dari lapisan teks PDF.
- [Fingerprint 21 Juli-20 Agustus 2026](pdf/rekap_absen_finger_print_periode_21_juli_20_agustus_2026.md) - 15 halaman scan, OCR dan citra halaman lengkap.
- [Fingerprint 21 Agustus-20 September 2026](pdf/rekapan_absensi_fingerprint_periode_21_ags_sd_20_sept_2026.md) - 16 halaman scan, OCR dan citra halaman lengkap.
- [Fingerprint 21 September-20 Oktober 2026](pdf/rekap_absensi_finger_print_periode_21_sep_sd_20_okto_2026.md) - 16 halaman scan, OCR dan citra halaman lengkap.
- [Absen dan Lembur Juli 2026](pdf/06_absen_dan_lembur_juli_2026.md) - 2 halaman.
- [Absen dan Lembur Agustus 2026](pdf/07_absen_dan_lembur_agustus_2026.md) - 2 halaman.
- [Absen dan Lembur September 2026](pdf/08_absen_dan_lembur_september_2026.md) - 2 halaman.
- [Absen dan Lembur Oktober 2026](pdf/09_absen_dan_lembur_oktober_2026.md) - 3 halaman.

Total PDF: 87 halaman. Enam PDF memiliki lapisan teks dan dipertahankan per halaman dalam blok teks berformat tetap. Tiga PDF fingerprint tanpa lapisan teks diproses dengan OCR lokal Indonesia+Inggris. Karena confidence rata-rata OCR berada pada 46,19%-47,00% untuk tabel yang sangat padat, setiap halaman scan juga disimpan sebagai JPEG render 4x dan ditautkan tepat di bagian halaman terkait. Citra tersebut menjadi rujukan visual utama jika teks OCR salah mengenali nama, jam, atau angka.

## Hasil Excel

- [Juli 2026 Yard KM12](excel/07_juli_2026_yard_km12/00_README.md) - 5 sheet, 4.865 sel terisi, 1.761 rumus, 2 objek non-sel, 1 media, dan 4 named range.
- [Agustus 2026 Yard KM12](excel/08_agustus_2026_yard_km12/00_README.md) - 6 sheet, 7.891 sel terisi, 3.346 rumus, 4 objek non-sel, 1 media, dan 7 named range.
- [September 2026 Yard KM12](excel/09_september_2026_yard_km12/00_README.md) - 5 sheet, 4.935 sel terisi, 1.740 rumus, 2 objek non-sel, 1 media, dan 4 named range.
- [Oktober 2026 Yard KM12](excel/10_oktober_2026_yard_km12/00_README.md) - 5 sheet, 5.329 sel terisi, 1.940 rumus, 2 objek non-sel, 1 media, dan 4 named range.

Total Excel: 21 sheet diperiksa, terdiri dari 19 sheet berisi data dan 2 sheet kosong; 23.020 sel berisi nilai atau rumus; 8.787 rumus; 10 objek non-sel; 4 media asli; dan 19 named range. Setiap sheet disimpan sebagai satu Markdown katalog sel agar koordinat, nilai mentah/hasil tersimpan, rumus, dan posisi data tetap dapat dilacak. Text box dicatat di bagian objek non-sel dan media gambar diekstrak langsung dari paket XLSX.

## Validasi kelengkapan

- Seluruh 13 file sumber berhasil ditemukan dan dibaca.
- Jumlah bagian `Halaman` pada Markdown PDF cocok dengan 87 halaman sumber.
- Ketiga PDF scan memiliki 47 citra halaman: 15 + 16 + 16.
- Keempat README Excel memuat kalimat `setiap tabel terpisah jadi satu markdown`.
- Sheet kosong tetap didokumentasikan dan tidak dihilangkan.
- Rumus, hyperlink, named range, text box, serta media workbook diperiksa dan dicatat.
