# Panduan Implementasi Modul Laporan & Form

## 1. Implementasi yang tersedia pada prototipe

Modul frontend berada pada:

- `dashboard.html` sebagai shell aplikasi;
- `scripts/report-forms.js` untuk schema, state, validasi, riwayat, duplikasi, dan preview;
- `scripts/report-forms.css` untuk form, riwayat, upload gambar, dan layout cetak.

Penyimpanan browser dipisahkan menjadi:

| Data | Key | Perilaku |
|---|---|---|
| Draft aktif | `fleetmonitor-report-draft-{schemaId}` | Diperbarui otomatis setiap input berubah |
| Laporan final | `fleetmonitor-report-history-v1` | Snapshot immutable untuk riwayat lokal |

Alur finalisasi:

1. User mengisi field dan baris tabel.
   Seluruh field identitas dan seluruh kolom tabel yang dapat diedit wajib diisi; kolom hasil perhitungan tetap otomatis.
2. Perubahan disimpan otomatis sebagai draft.
3. Tombol **Preview** hanya memvalidasi dan menampilkan layout cetak.
4. Tombol **Simpan Laporan** memvalidasi field, item, dan gambar wajib.
5. Snapshot final disimpan ke riwayat dengan `createdAt`.
6. Draft form tersebut dihapus.
7. Form dibangun ulang dari nilai template/default sehingga siap digunakan kembali.

Reset tidak dijalankan apabila penyimpanan final gagal.

## 2. Template dan autofill

Nilai baku disimpan pada properti `seedFields` setiap schema:

```js
{
    id: 'bapp',
    seedFields: {
        nomor: 'BAPP / ___ / ___ / ___ / 20__'
    },
    fields: [
        field('nomor', 'Nomor BAPP', 'text', true)
    ]
}
```

`createEmptyDraft(schema)` menggabungkan `seedFields` dan `seedRows`. Dengan demikian nilai baku selalu kembali setelah finalisasi atau reset, sedangkan autosave tetap dapat menimpa nilai tersebut selama form sedang dikerjakan.

Nomor dokumen yang memiliki format baku dirender sebagai input tersegmentasi. Teks seperti `BAPP`, `SPPU`, `Dept-Equip`, garis miring, dan awalan tahun dikunci; user hanya mengisi bagian nomor urut, periode, bulan, atau tahun. Nilai segmen kemudian digabung kembali sebelum draft/final disimpan.

Untuk menambahkan kop atau paragraf baku, gunakan field `textarea` dan isi nilai awal pada `seedFields`:

```js
seedFields: {
    kop_surat: 'Dengan hormat,\nSehubungan dengan kebutuhan operasional ...'
}
```

## 3. Upload bukti per item

Form pengajuan yang membutuhkan bukti dicantumkan dalam `evidenceRequiredFormIds`. Setiap baris yang memiliki data wajib memiliki `_evidence.dataUrl`.

Pada prototipe:

- format: JPG, PNG, atau WebP;
- resolusi sumber minimum: 1280×720 piksel;
- ukuran sumber maksimum: 8 MB per gambar;
- gambar dioptimalkan otomatis ke JPEG berkualitas tinggi dengan sisi terpanjang maksimal 1920 piksel;
- setiap gambar wajib memiliki keterangan/konteks singkat;
- draft dan gambar tersimpan pada browser;
- gambar tidak lagi dicetak sebagai thumbnail pada tabel utama;
- setiap gambar memperoleh halaman dokumentasi sendiri dengan ukuran 60% area cetak dan tabel identitas item.

Data URL hanya sesuai untuk prototipe. Untuk produksi, simpan file pada object storage dan hanya simpan `storage_key`/URL pada database.

## 4. Riwayat dan duplikasi

Tab **Riwayat Laporan** membaca snapshot final dan menyediakan:

- pencarian berdasarkan nomor, kode, atau jenis laporan;
- **Lihat/Cetak** menggunakan timestamp finalisasi asli;
- **Gunakan Ulang** yang menyalin field, item, dan lampiran ke draft baru;
- **Hapus** dengan konfirmasi nomor laporan untuk menghilangkan data final yang salah;
- nomor laporan dikembalikan ke format template agar tidak menduplikasi nomor final lama.

## 5. Layout laporan

Layout cetak baku terdiri dari:

1. Logo/identitas perusahaan di kiri.
2. Identitas project/mitra di kanan.
3. Kode, judul, dan nomor laporan di tengah.
4. Metadata form dan tabel item.
5. Halaman dokumentasi terpisah untuk setiap gambar bukti.
6. Rekap/perhitungan form.
7. Kolom Pengaju, Pengada, dan Pihak yang Diajukan.
8. Timestamp pembuatan laporan pada footer.

Aturan pagination PDF:

- ukuran halaman A4 landscape dengan margin 10 mm;
- halaman identitas dan rincian tabel dipisahkan agar kepala tabel tidak tertinggal di dasar halaman;
- tabel menggunakan layout tetap dan ukuran font adaptif berdasarkan jumlah kolom;
- header tabel diulang pada setiap halaman lanjutan;
- setiap baris dijaga tetap utuh; blok rekap, tanda tangan, dan footer dipindahkan bersama ke halaman berikutnya bila ruang tersisa tidak cukup;
- setiap gambar dokumentasi dimulai pada halaman baru;
- proses cetak menunggu font dan seluruh gambar selesai dimuat sebelum dialog PDF dibuka.

Logo pada prototipe berupa brand mark berbasis CSS karena repository belum memiliki file logo resmi. Pada produksi, ganti `.company-logo` dan `.partner-logo` dengan `<img>` yang mengarah ke asset resmi atau URL object storage.

Contoh struktur HTML cetak:

```html
<header class="print-brand">
  <div class="print-logo-block">
    <img src="/assets/logo-bra.svg" alt="Logo PT BRA">
    <div>
      <h2>PT Bina Rekayasa Anugerah</h2>
      <small>Departemen Equipment</small>
    </div>
  </div>
  <div class="print-logo-block partner">
    <strong>Project / Mitra</strong>
    <img src="/api/projects/123/logo" alt="Logo project">
  </div>
</header>
```

Contoh aturan CSS cetak:

```css
@media print {
  @page { size: A4 landscape; margin: 10mm; }
  .preview-toolbar { display: none !important; }
  .print-data-section { break-before: page; }
  .print-table tr { break-inside: avoid; }
  .print-closing-section { break-inside: avoid; }
}
```

## 6. Struktur database produksi yang direkomendasikan

Contoh PostgreSQL:

```sql
create table report_templates (
    id uuid primary key default gen_random_uuid(),
    code varchar(40) not null,
    title varchar(200) not null,
    version integer not null default 1,
    schema_json jsonb not null,
    is_active boolean not null default true,
    created_at timestamptz not null default now(),
    unique (code, version)
);

create table reports (
    id uuid primary key default gen_random_uuid(),
    template_id uuid not null references report_templates(id),
    report_number varchar(120) not null,
    status varchar(20) not null check (status in ('draft', 'final', 'void')),
    field_data jsonb not null default '{}'::jsonb,
    cloned_from_report_id uuid null references reports(id),
    created_by uuid not null,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    finalized_at timestamptz null,
    unique (template_id, report_number)
);

create table report_items (
    id uuid primary key default gen_random_uuid(),
    report_id uuid not null references reports(id) on delete cascade,
    position integer not null,
    item_data jsonb not null,
    created_at timestamptz not null default now(),
    unique (report_id, position)
);

create table report_attachments (
    id uuid primary key default gen_random_uuid(),
    report_id uuid not null references reports(id) on delete cascade,
    report_item_id uuid null references report_items(id) on delete cascade,
    attachment_type varchar(40) not null,
    original_name varchar(255) not null,
    mime_type varchar(80) not null,
    file_size bigint not null,
    storage_key varchar(500) not null,
    checksum_sha256 char(64) not null,
    uploaded_by uuid not null,
    created_at timestamptz not null default now()
);

create table report_approvals (
    id uuid primary key default gen_random_uuid(),
    report_id uuid not null references reports(id) on delete cascade,
    approval_role varchar(60) not null,
    person_name varchar(160),
    job_title varchar(160),
    approval_status varchar(20) not null default 'pending',
    signed_at timestamptz null
);

create table report_audit_logs (
    id bigserial primary key,
    report_id uuid not null references reports(id) on delete cascade,
    actor_id uuid not null,
    action varchar(50) not null,
    payload jsonb not null default '{}'::jsonb,
    occurred_at timestamptz not null default now()
);
```

Indeks tambahan:

```sql
create index reports_created_at_idx on reports (created_at desc);
create index reports_status_idx on reports (status);
create index reports_field_data_gin_idx on reports using gin (field_data);
create index report_items_item_data_gin_idx on report_items using gin (item_data);
```

## 7. Kontrak API

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/report-drafts` | Membuat draft server |
| `PATCH` | `/api/report-drafts/:id` | Autosave draft |
| `POST` | `/api/reports/finalize` | Validasi dan finalisasi dalam transaksi |
| `GET` | `/api/reports` | Riwayat dengan pagination/filter |
| `GET` | `/api/reports/:id` | Detail laporan |
| `POST` | `/api/reports/:id/clone` | Membuat draft dari laporan lama |
| `POST` | `/api/uploads/presign` | URL upload object storage |
| `GET` | `/api/reports/:id/pdf` | Generate/ambil PDF |

Contoh respons finalisasi:

```json
{
  "id": "c8110be7-7ab7-4ed9-9002-48ec19c8bdea",
  "reportNumber": "006/SPPU/PF-04/WS/II/2026",
  "status": "final",
  "finalizedAt": "2026-07-25T09:30:00.000Z"
}
```

## 8. Contoh backend Node.js/Express

Validasi harus diulang di server; validasi browser tidak boleh menjadi satu-satunya kontrol.

```js
app.post('/api/reports/finalize', requireAuth, async (req, res, next) => {
    const client = await pool.connect();
    try {
        const input = finalizeReportSchema.parse(req.body);
        await client.query('begin');

        const reportResult = await client.query(
            `insert into reports
                (template_id, report_number, status, field_data, created_by, finalized_at)
             values ($1, $2, 'final', $3, $4, now())
             returning id, report_number, finalized_at`,
            [input.templateId, input.reportNumber, input.fields, req.user.id]
        );
        const report = reportResult.rows[0];

        for (const [index, item] of input.items.entries()) {
            if (input.requiresEvidence && !item.attachmentId) {
                throw new Error(`Bukti gambar item ${index + 1} wajib diisi`);
            }
            await client.query(
                `insert into report_items (report_id, position, item_data)
                 values ($1, $2, $3)`,
                [report.id, index + 1, item]
            );
        }

        await client.query(
            `insert into report_audit_logs (report_id, actor_id, action)
             values ($1, $2, 'FINALIZE')`,
            [report.id, req.user.id]
        );

        await client.query('commit');
        res.status(201).json({
            id: report.id,
            reportNumber: report.report_number,
            status: 'final',
            finalizedAt: report.finalized_at
        });
    } catch (error) {
        await client.query('rollback');
        next(error);
    } finally {
        client.release();
    }
});
```

Endpoint clone:

```js
app.post('/api/reports/:id/clone', requireAuth, async (req, res) => {
    const source = await reportRepository.findComplete(req.params.id);
    const draft = await reportRepository.createDraft({
        templateId: source.templateId,
        fields: { ...source.fields, nomor: '' },
        items: source.items,
        clonedFromReportId: source.id,
        createdBy: req.user.id
    });
    res.status(201).json(draft);
});
```

## 9. Contoh integrasi frontend ke backend

Reset hanya dilakukan setelah server mengembalikan status sukses:

```js
async function finalizeToServer(schema, draft) {
    const response = await fetch('/api/reports/finalize', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            templateId: schema.id,
            reportNumber: getReportNumber(schema, draft.fields),
            fields: draft.fields,
            items: draft.rows,
            requiresEvidence: requiresEvidence(schema)
        })
    });

    if (!response.ok) {
        throw new Error(await response.text());
    }

    const savedReport = await response.json();
    localStorage.removeItem(`fleetmonitor-report-draft-${schema.id}`);
    return savedReport;
}
```

Untuk gambar produksi:

1. frontend meminta signed upload URL;
2. frontend mengunggah file langsung ke object storage;
3. backend menyimpan metadata attachment;
4. item hanya mengirim `attachmentId`;
5. finalisasi memverifikasi semua `attachmentId` dimiliki user dan belum kedaluwarsa.

## 10. Checklist produksi

- Terapkan authentication dan role-based access.
- Validasi schema, MIME type, ukuran file, dan checksum di server.
- Scan malware untuk attachment.
- Jangan menyimpan data URL gambar pada database.
- Gunakan transaksi saat finalisasi.
- Nomor laporan harus unik per template/periode.
- Simpan audit log untuk finalisasi, clone, void, dan approval.
- Generate PDF dari snapshot final, bukan dari draft yang masih berubah.
- Tambahkan backup dan retention policy untuk file laporan.
