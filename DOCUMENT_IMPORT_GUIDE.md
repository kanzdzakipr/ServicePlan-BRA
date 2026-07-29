# Panduan Modul Impor Laporan

## Ringkasan

Menu **Laporan & Form → Impor Laporan** menerima:

- Word: `.docx` dan `.doc`;
- PDF: `.pdf`, termasuk OCR adaptif untuk halaman scan;
- spreadsheet: `.xlsx`, `.xls`, `.xlsm`, `.csv`, dan `.tsv`;
- laporan manual Markdown: `.md` dan `.markdown`, termasuk heading, tabel pipe,
  list, blockquote, code block, serta provenance nomor baris;
- gambar pendukung: `.jpg`, `.jpeg`, `.png`, dan `.webp`.

Semua parser, worker, WebAssembly, serta model OCR Indonesia/Inggris berada di `scripts/vendor`. File sumber diproses di browser dan tidak dikirim ke layanan eksternal. Versi dipatok dan checksum SHA-256 dicatat di `scripts/vendor/README.md`; spreadsheet memakai distribusi resmi [SheetJS CE 0.20.3](https://docs.sheetjs.com/docs/getting-started/installation/standalone/).

Arsip `.rar`, `.zip`, atau `.7z` tidak dibuka. Arsip tetap masuk manifest dengan status eksplisit **perlu diekstrak** sehingga audit folder tidak melewatkannya secara diam-diam.

## Alur

1. Browser menghitung SHA-256 sumber untuk deduplikasi.
2. Magic signature dibandingkan dengan ekstensi file.
3. Adapter mengekstrak struktur canonical.
4. Semua fragmen diberi `sourceRef`.
5. Classifier memberi kandidat tipe laporan dari 20 schema yang tersedia.
6. Mapper menormalisasi field dan baris beserta confidence/provenance.
7. User meninjau warning, konflik, dan informasi yang belum terpetakan.
8. Tombol **Buat Draft Laporan** mengisi form; modul tidak pernah memfinalkan laporan otomatis.
9. Envelope JSON lengkap dapat diunduh untuk staging database.

## Adapter dan cakupan

### DOCX

Adapter membaca paket OOXML secara langsung:

- `word/document.xml`;
- seluruh header dan footer;
- footnote, endnote, dan comment;
- paragraf, field code, text box yang muncul dalam XML, dan tabel;
- document properties;
- relationship/hyperlink;
- daftar media dan embedded object.

Mode OCR maksimal mencoba membaca teks pada seluruh gambar tertanam. Mode otomatis hanya melakukannya bila dokumen hampir tidak memiliki teks native.

### DOC lama

Adapter membaca Compound File Binary melalui SheetJS CFB, kemudian mengikuti FIB/CLX/PlcPcd piece table untuk mengambil seluruh story text. Tabel dibaca secara struktural melalui `PlcBtePapx`, `PapxFkp`, `sprmPFTtp`, `sprmTDefTable`, serta referensi `sprmPHugePapx`/`sprmPTableProps` ke Data Stream. Implementasi mengikuti [Retrieving Text](https://learn.microsoft.com/en-us/openspecs/office_file_formats/ms-doc/01d5d8c4-cf9c-4ef9-80fd-439e763cfe01) dan [Overview of Tables](https://learn.microsoft.com/en-us/openspecs/office_file_formats/ms-doc/5b45f0e7-7760-4fdb-af88-0146de2feb4c) pada spesifikasi MS-DOC.

Sel kosong, baris kosong, grid dengan merge, dan teks di luar tabel tetap dipertahankan. Floating object/drawing Word binary masih hanya masuk katalog stream, sehingga setiap `.doc` selalu berstatus **review wajib**. Untuk produksi, konversi backend ke DOCX/PDF dapat dipakai sebagai verifikasi kedua, bukan pengganti sumber asli.

### PDF

PDF.js hanya membaca metadata dokumen dan text layer native setiap halaman beserta koordinatnya. Text layer dibaca sebagai stream dengan batas keras 100.000 item atau 5 MiB karakter per halaman; hasil tidak boleh dianggap lengkap bila batas tersebut tercapai.

Outline/action, anotasi, field form interaktif, dan lampiran tertanam sengaja tidak dimaterialisasi. Pada PDF.js 3.11, membaca objek interaktif tertentu dapat ikut mengembangkan payload lampiran ke memori. Untuk alasan yang sama, modul tidak membuat daftar operator maupun menghitung image-object. Nilai statistik terkait diisi `null`/`skipped-memory-safety`, bukan `0`. Jika PDF memakai form atau lampiran, ekspor datanya lalu impor sebagai file tersendiri atau lakukan review pada dokumen asli.

Halaman tanpa text layer yang memadai, berkarakter rusak, atau hanya memiliki sedikit teks native dirender untuk OCR. Render OCR menonaktifkan annotation layer, memilih skala dari dimensi halaman, membagi render menjadi tile yang overlap, lalu menyimpan bounding box setiap baris. Ini mencegah tabel ultra-lebar diturunkan ke bitmap kecil tanpa secara tidak sengaja membaca objek interaktif. Tabel native dan OCR sama-sama dibentuk dari jarak koordinat, tetapi tetap berstatus review karena layout PDF tidak memiliki schema tabel eksplisit.

Seluruh pekerjaan PDF memakai satu deadline absolut 15 menit yang mencakup pembukaan dokumen, metadata, halaman, streaming text layer, render, dan OCR. Saat deadline tercapai, reader/render dibatalkan, worker PDF dihancurkan, dan impor menghasilkan error eksplisit; pekerjaan tidak dibiarkan berjalan di belakang layar.

### XLSX/XLS/XLSM/CSV/TSV

Parsing berlangsung di Web Worker agar workbook besar tidak membekukan UI. Semua sheet, termasuk `hidden` dan `veryHidden`, diproses. Setiap sel berisi informasi menyimpan:

- alamat `Sheet!Cell`;
- raw value dan formatted value;
- formula;
- data type dan number format;
- comment;
- hyperlink.

Manifest juga mencatat merge, hidden row/column, defined name, error cell, media, chart, embedded object, external link, dan connection. Gambar workbook hanya di-OCR pada mode maksimal.

CSV/TSV mendeteksi UTF-8, UTF-16LE/BE, atau Windows-1252. SheetJS menangani delimiter serta quoted multiline. Nilai tidak pernah dieksekusi sebagai formula oleh modul impor.

Sebelum JSZip atau parser OOXML membaca `.docx`, `.xlsx`, dan `.xlsm`, modul melakukan preflight langsung terhadap byte ZIP mentah. Pemeriksaan ini memvalidasi EOCD, Central Directory dan local header, ukuran/CRC yang tersedia, metode kompresi, rentang data, serta kesesuaian nama dan jumlah entry setelah JSZip. ZIP64, multi-disk, entry terenkripsi, path absolut/traversal/ambigu, collision nama, rentang overlap, dan paket yang melewati batas ukuran ditolak sebelum isi dimaterialisasi.

### Markdown dan laporan manual Cutting Bit

Adapter Markdown membaca UTF-8 atau UTF-16 dengan BOM, menolak byte NUL dan
kontrol biner, serta membatasi sumber hingga 32 MiB. Parser tidak merender raw
HTML, tidak mengikuti link, tidak mengambil resource eksternal, dan tidak
menjalankan fenced code. Setiap heading, sel tabel, paragraf, list, blockquote,
dan code block mendapat `sourceRef` berbasis nomor baris; raw Markdown tetap
berada pada metadata fragmen.

Schema ke-20 adalah `cutting-bit-usage` (`CB-RM`). Dua fixture utamanya:

- `material/BAN-GREASE-CUTTING_BIT-AKI/Perhitungan_Cutting_Bit_XCMG_BRR-RM-4101_Tabulasi.md`;
- `material/BAN-GREASE-CUTTING_BIT-AKI/Perhitungan_Cutting_Bit_CAT_RM500_BRR-RM-4102_Tabulasi.md`.

Mapper khusus hanya membentuk baris draft dari section **Tabulasi Harian
{Bulan}** yang memiliki kolom Tanggal, Planning, dan Actual. Bulan berasal dari
heading section; nilai tanggal/planning/actual tetap menunjuk sel sumber.
Tabel rekap, rumus, material, status kebutuhan, dan catatan tidak dicampur
menjadi transaksi harian, tetapi seluruhnya tetap tersimpan dalam extraction
audit.

Actual pada tanggal tanpa planning tidak dipindahkan. Sistem memberi warning
`cutting_bit_actual_without_planning`. Karena sumber hanya menyebut hari dan
bulan, sistem juga memberi `cutting_bit_year_missing` dan tidak menebak tahun.
Validasi draft menolak tanggal di luar bulan, bulan/tanggal duplikat, jumlah
pcs pecahan/negatif, safety stock di luar 0-100%, dan tahun non-empat-digit.

Baseline regresi:

| Dokumen | Heading / tabel | Baris harian | Planning | Actual | Sentinel |
|---|---:|---:|---:|---:|---|
| XCMG BRR-RM-4101 | 14 / 12 | 62 | 1.900 | 117 | 19 Juli: 0 / 20 |
| CAT RM500 BRR-RM-4102 | 11 / 8 | 31 | 150 | 50 | 21 Juli: 0 / 50 |

## Model canonical

Setiap import menghasilkan bentuk berikut:

```json
{
  "schemaVersion": "1.0.0",
  "importId": "uuid",
  "source": {
    "fileName": "FORM SPPU YARD.xlsx",
    "relativePath": "raw-material/SOP/Form/FORM SPPU YARD.xlsx",
    "size": 34082,
    "sha256": "...",
    "signature": "zip"
  },
  "extraction": {
    "parser": {"name": "...", "version": "1.0.0"},
    "sections": [],
    "tables": [],
    "fragments": [
      {
        "id": "F0000001",
        "kind": "cell",
        "value": "006/SPPU/PF-04/WS/II/2026",
        "sourceRef": "xlsx:Sheet1!D7",
        "meta": {"rawValue": "...", "formula": ""}
      }
    ],
    "artifacts": [],
    "warnings": [],
    "stats": {}
  },
  "classification": {},
  "target": {},
  "mapping": {
    "fields": {},
    "fieldProvenance": {},
    "rows": [],
    "rowProvenance": [],
    "mappedFragmentIds": [],
    "conflicts": []
  },
  "quality": {
    "extractionCoverage": 1,
    "mappingCoverage": 0.42,
    "requiredCoverage": 0.75,
    "reviewRequired": true,
    "canFinalizeAutomatically": false
  }
}
```

`mappingCoverage` bukan ukuran apakah informasi hilang. Fragmen yang tidak masuk schema tetap ada pada `extraction.fragments`; nilai itu hanya belum cocok dengan field laporan yang tersedia. Batas kandidat/baris pemetaan hanya memotong hasil turunan untuk draft, bukan extraction sumber. `pagesProcessed` berarti struktur halaman berhasil dibuka, sedangkan `pagesFullyExtracted` berarti halaman memiliki teks native atau OCR yang memadai. Karena itu mode OCR `off` dapat menghasilkan `pagesProcessed=pagesExpected` tanpa mengklaim isi scan telah terbaca.

## Penyimpanan prototipe

- extraction lengkap: IndexedDB `fleetmonitor-document-imports`;
- draft laporan kecil: `localStorage` melalui API `FleetReportForms.importDraft`;
- file asli: tetap berada pada perangkat user dan tidak disalin ke IndexedDB;
- final report: mekanisme riwayat lokal modul laporan yang sudah ada.

Extraction di IndexedDB mengandung informasi sumber dan **tidak dienkripsi oleh aplikasi**; perlakukan profil browser/perangkat sebagai area data sensitif. Tombol **Hapus Semua Arsip** menghapus record dan ringkasan secara transaksional. Jika quota IndexedDB tidak cukup, UI memberi warning dan hasil tetap tersedia sampai tab ditutup.

## Batas operasional

### Batas ekstraksi keras

- maksimal 100 MiB per file;
- Markdown maksimal 32 MiB per file;
- maksimal 500 file dan 500 MiB per batch;
- paket OOXML maksimal 10.000 entry, 64 MiB total uncompressed, dan 32 MiB per entry;
- workbook OOXML maksimal 1.100.000 node sel sebelum materialisasi, lalu maksimal 600.000 sel bernilai/formula/comment/hyperlink yang perlu dipertahankan;
- maksimal 600.000 fragmen, maksimal 64 MiB karakter fragmen yang dipertahankan, dan maksimal 2 MiB teks per fragmen;
- tugas parser Web Worker dihentikan setelah 240 detik agar file rusak/ekstrem tidak menggantung tab;
- PDF maksimal 1.000 halaman;
- text layer PDF maksimal 100.000 item atau 5 MiB karakter per halaman, dengan deadline absolut 15 menit per dokumen;
- OCR maksimal 100 tile per halaman, dengan sisi render target 10.000 px pada mode otomatis atau 12.000 px pada mode maksimal;
- raster dibatasi 40 juta piksel dan dimensi 20.000 px.

Batas keras menghentikan ekstraksi dengan error/status gagal yang eksplisit. Prefix data yang sempat terbaca tidak dilaporkan sebagai extraction lengkap. Batch tetap menyimpan manifest jumlah file yang ditemukan, berhasil diproses, gagal, dan diberi warning.

### Batas hasil turunan

- duplikasi sheet sebagai tabel turunan dilewati bila satu sheet memiliki lebih dari 100.000 sel berisi data; fragmen setiap sel tetap dipertahankan;
- classifier menilai sampel berstrata maksimal 6.000 fragmen;
- mapper menilai maksimal 50.000 kandidat dan menyimpan maksimal 2.000 baris terpetakan, dengan warning `mapping_candidate_limit`/`mapping_row_limit` bila hasil turunan dibatasi;
- preview fragmen belum terpetakan dibatasi untuk tampilan, tetapi seluruh fragmen tetap berada dalam extraction/envelope;
- penerapan ke satu draft dibatasi 1.000 baris agar form tetap responsif.

Batas turunan tidak menggugurkan extraction dan tidak menghapus sumber. Ia hanya membatasi klasifikasi, tabel duplikat, pemetaan, preview, atau draft yang dibangun dari extraction; pengguna tetap dapat mencari dan meninjau data sumber lengkap yang lolos batas keras.

## Rancangan staging database

Implementasi backend dapat dimulai dengan tabel berikut. File asli sebaiknya disimpan pada object storage dan database hanya menyimpan object key serta checksum.

```sql
create table document_imports (
    id uuid primary key,
    source_sha256 char(64) not null,
    source_file_name varchar(255) not null,
    source_object_key text not null,
    source_mime varchar(150),
    source_size bigint not null,
    parser_name varchar(160) not null,
    parser_version varchar(40) not null,
    envelope_version varchar(40) not null,
    status varchar(40) not null,
    target_template_id uuid null references report_templates(id),
    extraction_coverage numeric(6,5) not null,
    mapping_coverage numeric(6,5) not null,
    required_coverage numeric(6,5) not null,
    created_by uuid not null,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (source_sha256, target_template_id)
);

create table document_fragments (
    id uuid primary key,
    import_id uuid not null references document_imports(id) on delete cascade,
    sequence bigint not null,
    kind varchar(60) not null,
    source_ref text not null,
    raw_value text,
    meta jsonb not null default '{}'::jsonb,
    unique (import_id, sequence)
);

create table document_mappings (
    id uuid primary key,
    import_id uuid not null references document_imports(id) on delete cascade,
    fragment_id uuid null references document_fragments(id),
    report_field_key varchar(120),
    report_row_id uuid,
    normalized_value jsonb,
    confidence numeric(6,5),
    method varchar(80),
    review_status varchar(40) not null default 'pending',
    reviewed_by uuid,
    reviewed_at timestamptz
);

create table document_import_warnings (
    id uuid primary key,
    import_id uuid not null references document_imports(id) on delete cascade,
    severity varchar(20) not null,
    code varchar(100) not null,
    message text not null,
    source_ref text,
    resolved_by uuid,
    resolved_at timestamptz
);

create index document_imports_sha256_idx on document_imports (source_sha256);
create index document_fragments_import_idx on document_fragments (import_id, sequence);
create index document_fragments_meta_gin_idx on document_fragments using gin (meta);
create index document_mappings_review_idx on document_mappings (review_status, import_id);
```

ID fragmen browser seperti `F0000001` adalah kunci sumber di dalam satu envelope. Adapter backend perlu mengubahnya menjadi UUID deterministik atau menyimpannya sebagai `source_fragment_key`; jangan memasukkan string tersebut langsung ke kolom UUID.

Finalisasi backend harus dilakukan dalam transaksi:

1. lock import job;
2. pastikan tidak ada warning `error` yang belum diselesaikan;
3. validasi nomor dokumen unik;
4. tulis `reports`, `report_items`, attachment, provenance, dan audit log;
5. ubah status import menjadi `committed`;
6. simpan snapshot PDF dari data final, bukan dari extraction yang masih dapat berubah.

## Audit corpus `raw-material`

Hasil regresi browser pada 26 Juli 2026:

- 69/69 file fisik terenumerasi dan memperoleh hasil audit tanpa unhandled exception;
- 68 file dengan format yang didukung selesai diproses; satu `SOP.rar` sengaja tidak dibuka dan dicatat dengan status `archive_requires_unpack`;
- 33 PDF dan 198/198 halaman memiliki status;
- 27 workbook dan 129/129 sheet terproses, termasuk 14 `hidden`/`veryHidden`;
- 7 dokumen Word menghasilkan tepat 77 tabel; dua DOC lama cocok dengan dimensi Microsoft Word native (`4` dan `8` tabel);
- 585.080 fragmen canonical dan 3.153 tabel/struktur tabular tercatat;
- 67 hash unik dan dua kelompok duplikat terdeteksi lewat SHA-256;
- workbook biaya yang ditolak Excel tetap diproses sebagai OOXML;
- formula error, external link, media, OCR confidence rendah, dan legacy DOC selalu masuk warning/review;
- CSV diuji dengan fixture terpisah karena corpus saat ini tidak memilikinya.

Angka tersebut membuktikan cakupan struktural terhadap corpus saat ini, bukan jaminan bahwa setiap nilai telah dipetakan semantik ke salah satu dari 19 tipe laporan. Informasi yang belum cocok tetap ditampilkan sebagai fragmen tidak terpetakan untuk review.

Daftar 69 path serta isi byte-for-byte corpus diikat oleh
`tests/verify-raw-material-manifest.ps1` (exact set, ukuran, dan SHA-256 gabungan).
Checksum aset parser lokal diverifikasi sebagai exact set oleh
`tests/verify-vendor-checksums.ps1`. Harness browser juga memeriksa integritas
provenance pemetaan, round-trip envelope database, draft tanpa auto-finalisasi,
CSV multiline/leading zero/formula-literal, dan koordinat hasil OCR.

## Aturan keamanan

- Jangan percaya ekstensi; selalu periksa magic signature.
- Jangan mengeksekusi macro, formula, hyperlink, atau embedded object.
- Jangan menganggap OCR sebagai kebenaran tanpa confidence dan source bounding box.
- Jangan auto-finalize draft hasil impor.
- Jangan menghapus fragmen hanya karena tidak cocok dengan schema laporan.
- Jangan menyimpan file besar/base64 ke `localStorage`.
