(function (global) {
    'use strict';

    const TEMPLATE = Object.freeze({
        name: 'Template Laporan Monitoring',
        reportType: 'monitoring_report',
        code: 'TPL-LM-001',
        version: '1.0',
        publishedDate: '31/07/2026',
        updatedDate: '31/07/2026',
        status: 'Aktif',
        description: 'Template resmi untuk laporan kegiatan, peserta, temuan, tindak lanjut, dan dokumentasi pendukung.'
    });

    const MAIN_FIELDS = Object.freeze([
        ['report_title', 'Judul Laporan', 'Isi judul utama yang menggambarkan isi laporan.', 'Laporan Monitoring Alat Berat Periode Juli 2026', 'Teks pendek', 'Ya'],
        ['report_number', 'Nomor Laporan', 'Isi nomor atau kode resmi laporan apabila tersedia.', 'LAP/MON/007/2026', 'Teks pendek', 'Tidak'],
        ['report_date', 'Tanggal Laporan', 'Isi tanggal laporan dibuat. Gunakan nilai tanggal Excel.', '31/07/2026', 'Tanggal', 'Ya'],
        ['report_period', 'Periode Laporan', 'Isi periode waktu yang dibahas dalam laporan.', 'Juli 2026', 'Teks pendek', 'Tidak'],
        ['activity_name', 'Nama Kegiatan', 'Isi nama kegiatan yang dilaporkan.', 'Monitoring Kondisi Alat Berat', 'Teks pendek', 'Ya'],
        ['activity_date', 'Tanggal Kegiatan', 'Isi tanggal kegiatan dilaksanakan. Gunakan nilai tanggal Excel.', '30/07/2026', 'Tanggal', 'Ya'],
        ['activity_location', 'Lokasi Kegiatan', 'Isi lokasi kegiatan secara jelas dan lengkap.', 'Proyek Pembangunan Gedung A, Jakarta', 'Teks pendek', 'Ya'],
        ['responsible_person', 'Penanggung Jawab', 'Isi nama lengkap penanggung jawab kegiatan.', 'Ahmad Pratama', 'Teks pendek', 'Ya'],
        ['responsible_position', 'Jabatan Penanggung Jawab', 'Isi jabatan penanggung jawab.', 'Site Manager', 'Teks pendek', 'Tidak'],
        ['activity_background', 'Latar Belakang', 'Jelaskan kondisi, masalah, atau kebutuhan yang menyebabkan kegiatan dilakukan.', 'Jelaskan alasan kegiatan ini perlu dilaksanakan.', 'Teks panjang', 'Ya'],
        ['activity_objective', 'Tujuan Kegiatan', 'Jelaskan hasil atau kondisi yang ingin dicapai.', 'Jelaskan tujuan utama dan kondisi yang ingin dicapai.', 'Teks panjang', 'Ya'],
        ['activity_description', 'Deskripsi Pelaksanaan', 'Jelaskan tahapan kegiatan secara runtut.', 'Tuliskan proses kegiatan dari awal sampai selesai.', 'Teks panjang', 'Ya'],
        ['activity_result', 'Hasil Kegiatan', 'Jelaskan hasil, temuan, atau pencapaian kegiatan.', 'Tuliskan hasil yang diperoleh setelah kegiatan selesai.', 'Teks panjang', 'Ya'],
        ['issue_summary', 'Ringkasan Kendala', 'Jelaskan kendala utama yang ditemukan.', 'Jelaskan kendala teknis atau administratif yang ditemukan.', 'Teks panjang', 'Tidak'],
        ['recommendation_summary', 'Ringkasan Rekomendasi', 'Jelaskan rekomendasi utama berdasarkan hasil kegiatan.', 'Tuliskan tindakan perbaikan yang disarankan.', 'Teks panjang', 'Tidak'],
        ['follow_up_summary', 'Ringkasan Tindak Lanjut', 'Jelaskan rencana tindak lanjut berikutnya.', 'Tuliskan tindakan berikutnya, penanggung jawab, dan target waktu.', 'Teks panjang', 'Tidak'],
        ['completion_status', 'Status Kegiatan', 'Pilih status kegiatan dari dropdown yang tersedia.', 'Selesai, Berjalan, Tertunda, atau Dibatalkan', 'Pilihan dropdown', 'Ya'],
        ['completion_percentage', 'Persentase Penyelesaian', 'Isi angka antara 0 sampai 100 tanpa simbol persen.', '75', 'Persentase', 'Tidak'],
        ['approved_by', 'Disetujui Oleh', 'Isi nama pihak yang memberikan persetujuan.', 'Budi Santoso', 'Teks pendek', 'Tidak'],
        ['approval_date', 'Tanggal Persetujuan', 'Isi tanggal persetujuan; tidak boleh lebih awal dari tanggal laporan.', '31/07/2026', 'Tanggal', 'Tidak'],
        ['additional_notes', 'Catatan Tambahan', 'Isi informasi penting lain yang belum tercakup.', 'Tuliskan catatan tambahan yang relevan apabila diperlukan.', 'Teks panjang', 'Tidak']
    ]);

    const SHEETS = Object.freeze([
        { name: 'PETUNJUK', id: 1 },
        { name: 'DATA_LAPORAN', id: 2, table: 'tblDataLaporan' },
        { name: 'DAFTAR_PESERTA', id: 3, table: 'tblDaftarPeserta' },
        { name: 'DAFTAR_TEMUAN', id: 4, table: 'tblDaftarTemuan' },
        { name: 'TINDAK_LANJUT', id: 5, table: 'tblTindakLanjut' },
        { name: 'DOKUMENTASI', id: 6, table: 'tblDokumentasi' },
        { name: 'METADATA', id: 7, hidden: true }
    ]);

    const LISTS = Object.freeze({
        CompletionStatus: ['Selesai', 'Berjalan', 'Tertunda', 'Dibatalkan'],
        FindingPriority: ['Rendah', 'Sedang', 'Tinggi', 'Kritis'],
        FindingCategory: ['Teknis', 'Operasional', 'Administratif', 'Keselamatan', 'Sumber Daya', 'Dokumentasi', 'Lainnya'],
        ActionStatus: ['Belum Dimulai', 'Sedang Berjalan', 'Selesai', 'Tertunda', 'Dibatalkan'],
        DocumentationType: ['Foto', 'Dokumen', 'Berita Acara', 'Surat', 'Tautan', 'Lainnya'],
        YesNo: ['Ya', 'Tidak']
    });

    function xml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function columnName(index) {
        let number = index + 1;
        let result = '';
        while (number > 0) {
            number -= 1;
            result = String.fromCharCode(65 + (number % 26)) + result;
            number = Math.floor(number / 26);
        }
        return result;
    }

    function cell(reference, value, style = 0, options = {}) {
        const styleAttribute = style ? ` s="${style}"` : '';
        if (options.formula) {
            return `<c r="${reference}"${styleAttribute}><f>${xml(options.formula)}</f><v>${xml(options.cachedValue || '')}</v></c>`;
        }
        if (value == null || value === '') return `<c r="${reference}"${styleAttribute}/>`;
        if (options.type === 'number') {
            return `<c r="${reference}"${styleAttribute}><v>${Number(value)}</v></c>`;
        }
        return `<c r="${reference}"${styleAttribute} t="inlineStr"><is><t xml:space="preserve">${xml(value)}</t></is></c>`;
    }

    function row(number, cells, options = {}) {
        const height = options.height ? ` ht="${options.height}" customHeight="1"` : '';
        return `<row r="${number}"${height}>${cells.join('')}</row>`;
    }

    function columnsXml(widths, hiddenColumns = []) {
        return `<cols>${widths.map((width, index) => (
            `<col min="${index + 1}" max="${index + 1}" width="${width}" customWidth="1"${hiddenColumns.includes(index + 1) ? ' hidden="1"' : ''}/>`
        )).join('')}</cols>`;
    }

    function validation(type, sqref, formula1, options = {}) {
        const allowBlank = options.allowBlank === false ? '0' : '1';
        const operator = options.operator ? ` operator="${options.operator}"` : '';
        return `<dataValidation type="${type}"${operator} allowBlank="${allowBlank}" showInputMessage="1" showErrorMessage="1" errorStyle="stop" sqref="${xml(sqref)}" promptTitle="${xml(options.promptTitle || 'Petunjuk Pengisian')}" prompt="${xml(options.prompt || 'Isi sesuai petunjuk field.')}" errorTitle="${xml(options.errorTitle || 'Data Tidak Valid')}" error="${xml(options.error || 'Periksa kembali nilai yang dimasukkan.')}"><formula1>${xml(formula1)}</formula1>${options.formula2 ? `<formula2>${xml(options.formula2)}</formula2>` : ''}</dataValidation>`;
    }

    function conditionalFormatting(sqref, formula) {
        return `<conditionalFormatting sqref="${xml(sqref)}"><cfRule type="expression" dxfId="0" priority="1"><formula>${xml(formula)}</formula></cfRule></conditionalFormatting>`;
    }

    function sheetProtection(allowInsertRows = false) {
        return `<sheetProtection sheet="1" objects="1" scenarios="1" formatCells="1" formatColumns="1" formatRows="1" insertColumns="1" insertRows="${allowInsertRows ? 0 : 1}" deleteColumns="1" deleteRows="1" selectLockedCells="1" selectUnlockedCells="0"/>`;
    }

    function worksheetXml(config) {
        const validations = config.validations || [];
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <dimension ref="${config.dimension}"/>
  <sheetViews><sheetView workbookViewId="0" showGridLines="${config.showGridLines === false ? 0 : 1}">${config.freeze ? `<pane ySplit="${config.freeze}" topLeftCell="A${config.freeze + 1}" activePane="bottomLeft" state="frozen"/>` : ''}</sheetView></sheetViews>
  <sheetFormatPr defaultRowHeight="15"/>
  ${columnsXml(config.widths, config.hiddenColumns)}
  <sheetData>${config.rows.join('')}</sheetData>
  ${sheetProtection(Boolean(config.allowInsertRows))}
  ${config.conditionalFormats?.join('') || ''}
  ${validations.length ? `<dataValidations count="${validations.length}">${validations.join('')}</dataValidations>` : ''}
  ${config.autoFilter ? `<autoFilter ref="${config.autoFilter}"/>` : ''}
  <pageMargins left="0.35" right="0.35" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>
  <pageSetup orientation="${config.landscape ? 'landscape' : 'portrait'}" fitToWidth="1" fitToHeight="0"/>
  ${config.tableId ? `<tableParts count="1"><tablePart r:id="rId1"/></tableParts>` : ''}
</worksheet>`;
    }

    function petunjukSheet() {
        const instructions = [
            'Isi data hanya pada sel yang telah disediakan.',
            'Jangan mengubah nama sheet.',
            'Jangan menghapus atau menambah kolom.',
            'Jangan mengubah kode field.',
            'Field dengan tanda bintang * wajib diisi.',
            'Gunakan format tanggal DD/MM/YYYY.',
            'Gunakan pilihan dropdown apabila tersedia.',
            'Jangan mengganti data teks dengan gambar.',
            'Jangan menggunakan merged cell pada area pengisian data.',
            'Jangan mengubah struktur tabel.',
            'Hapus atau ganti seluruh data contoh sebelum file diunggah.'
        ];
        const info = [
            ['Nama Template', TEMPLATE.name],
            ['Jenis Laporan', 'Laporan Monitoring'],
            ['Kode Template', TEMPLATE.code],
            ['Versi Template', TEMPLATE.version],
            ['Tanggal Diterbitkan', TEMPLATE.publishedDate],
            ['Tanggal Diperbarui', TEMPLATE.updatedDate],
            ['Status Template', TEMPLATE.status],
            ['Deskripsi', TEMPLATE.description]
        ];
        const rows = [
            row(1, [cell('A1', 'TEMPLATE LAPORAN RESMI SISTEM', 2)], { height: 28 }),
            row(2, [cell('A2', 'PT Bina Rekayasa Anugrah · FleetMonitor', 6)]),
            ...info.map((item, index) => row(index + 4, [
                cell(`A${index + 4}`, item[0], 6),
                cell(`B${index + 4}`, item[1], 6)
            ], { height: item[0] === 'Deskripsi' ? 34 : 20 })),
            row(13, [cell('A13', 'ATURAN PENGISIAN', 1)], { height: 24 }),
            ...instructions.map((instruction, index) => row(index + 14, [
                cell(`A${index + 14}`, `${index + 1}.`, 6),
                cell(`B${index + 14}`, instruction, 6)
            ], { height: 22 })),
            row(26, [cell('A26', 'PENANDA VISUAL', 1)], { height: 24 }),
            row(27, [cell('A27', 'Kuning', 3), cell('B27', 'Field wajib diisi; tanda * dan kolom Wajib tetap menjadi penanda tekstual.', 6)]),
            row(28, [cell('A28', 'Putih', 4), cell('B28', 'Field opsional.', 6)]),
            row(29, [cell('A29', 'Abu-abu', 6), cell('B29', 'Struktur, kode field, formula, atau petunjuk yang tidak boleh diubah.', 6)]),
            row(30, [cell('A30', 'Hijau', 5), cell('B30', 'Sel memiliki pilihan dropdown; pilih salah satu nilai yang tersedia.', 6)]),
            row(31, [cell('A31', 'Merah', 17), cell('B31', 'Nilai kosong, tidak sesuai format, atau tidak konsisten dengan field terkait.', 6)])
        ];
        return worksheetXml({
            dimension: 'A1:C31',
            widths: [18, 88, 4],
            rows,
            showGridLines: false,
            landscape: false
        });
    }

    function dataLaporanSheet() {
        const headers = ['Kode Field', 'Nama Field', 'Petunjuk Pengisian', 'Contoh atau Placeholder', 'Tipe Data', 'Wajib', 'Nilai / Isian Pengguna'];
        const rows = [row(1, headers.map((header, index) => cell(`${columnName(index)}1`, header, 10)), { height: 32 })];
        const validations = [];
        const conditionalFormats = [];
        MAIN_FIELDS.forEach((field, index) => {
            const sheetRow = index + 2;
            const required = field[5] === 'Ya';
            const isDate = field[4] === 'Tanggal';
            const isLong = field[4] === 'Teks panjang';
            const isDropdown = field[0] === 'completion_status';
            const isPercentage = field[0] === 'completion_percentage';
            let inputStyle = required ? 3 : 4;
            if (isDate) inputStyle = required ? 7 : 8;
            if (isLong) inputStyle = required ? 15 : 16;
            if (isDropdown) inputStyle = 5;
            if (isPercentage) inputStyle = 9;
            rows.push(row(sheetRow, [
                cell(`A${sheetRow}`, field[0], 6),
                cell(`B${sheetRow}`, `${field[1]}${required ? ' *' : ''}`, 6),
                cell(`C${sheetRow}`, field[2], 6),
                cell(`D${sheetRow}`, field[3], 14),
                cell(`E${sheetRow}`, field[4], 6),
                cell(`F${sheetRow}`, field[5], 6),
                cell(`G${sheetRow}`, '', inputStyle)
            ], { height: isLong ? 46 : 30 }));

            const prompt = `${field[2]} Contoh: ${field[3]}`;
            if (isDropdown) {
                validations.push(validation('list', `G${sheetRow}`, 'CompletionStatus', {
                    allowBlank: false,
                    prompt,
                    error: 'Pilih Selesai, Berjalan, Tertunda, atau Dibatalkan dari dropdown.'
                }));
            } else if (field[0] === 'approval_date') {
                validations.push(validation('custom', `G${sheetRow}`, `OR(G${sheetRow}="",AND(ISNUMBER(G${sheetRow}),G${sheetRow}>=$G$4))`, {
                    prompt,
                    error: 'Tanggal persetujuan harus berupa tanggal Excel dan tidak boleh lebih awal dari tanggal laporan.'
                }));
            } else if (isDate) {
                validations.push(validation('date', `G${sheetRow}`, 'DATE(2000,1,1)', {
                    formula2: 'DATE(2100,12,31)',
                    operator: 'between',
                    allowBlank: !required,
                    prompt: `${prompt} Gunakan format DD/MM/YYYY.`,
                    error: 'Masukkan nilai tanggal Excel antara 01/01/2000 dan 31/12/2100.'
                }));
            } else if (isPercentage) {
                validations.push(validation('custom', `G${sheetRow}`, `OR(G${sheetRow}="",AND(ISNUMBER(G${sheetRow}),G${sheetRow}>=0,G${sheetRow}<=100,OR($G$18<>"Selesai",G${sheetRow}=100)))`, {
                    prompt,
                    error: 'Persentase harus 0–100. Jika status Selesai, persentase harus 100.'
                }));
            } else {
                const maximum = isLong ? 5000 : 255;
                const formula = required
                    ? `AND(LEN(TRIM(G${sheetRow}))>0,LEN(G${sheetRow})<=${maximum})`
                    : `OR(G${sheetRow}="",LEN(G${sheetRow})<=${maximum})`;
                validations.push(validation('custom', `G${sheetRow}`, formula, {
                    allowBlank: !required,
                    prompt,
                    error: required
                        ? `Field wajib diisi dan maksimal ${maximum} karakter.`
                        : `Maksimal ${maximum} karakter.`
                }));
            }
            if (required) conditionalFormats.push(conditionalFormatting(`G${sheetRow}`, `LEN(TRIM(G${sheetRow}))=0`));
        });
        conditionalFormats.push(conditionalFormatting('G18:G19', 'AND($G$18="Selesai",$G$19<>100)'));
        conditionalFormats.push(conditionalFormatting('G21', 'AND(ISNUMBER(G21),ISNUMBER($G$4),G21<$G$4)'));
        return worksheetXml({
            dimension: 'A1:G22',
            widths: [22, 30, 62, 54, 20, 11, 46],
            hiddenColumns: [1],
            rows,
            freeze: 1,
            validations,
            conditionalFormats,
            tableId: 1,
            landscape: true
        });
    }

    function repeatedSheet(config) {
        const lastRow = 101;
        const rows = [row(1, config.headers.map((header, index) => cell(`${columnName(index)}1`, header, 10)), { height: 34 })];
        for (let rowNumber = 2; rowNumber <= lastRow; rowNumber += 1) {
            rows.push(row(rowNumber, config.headers.map((header, index) => {
                const reference = `${columnName(index)}${rowNumber}`;
                if (index === 0) {
                    return cell(reference, '', 6, {
                        formula: `IF(B${rowNumber}="","",ROW()-1)`,
                        cachedValue: ''
                    });
                }
                const style = config.dropdownColumns?.[index]
                    ? 12
                    : config.dateColumns?.includes(index)
                        ? 13
                        : config.percentageColumns?.includes(index)
                            ? 9
                            : 11;
                return cell(reference, '', style);
            }), { height: 26 }));
        }
        const validations = [];
        Object.entries(config.dropdownColumns || {}).forEach(([index, listName]) => {
            const column = columnName(Number(index));
            validations.push(validation('list', `${column}2:${column}${lastRow}`, listName, {
                prompt: config.prompts[index],
                error: 'Pilih salah satu nilai dari dropdown; nilai lain tidak diperbolehkan.'
            }));
        });
        (config.dateColumns || []).forEach(index => {
            const column = columnName(index);
            if (config.dateRelation && config.dateRelation.targetIndex === index) {
                const startColumn = columnName(config.dateRelation.startIndex);
                validations.push(validation('custom', `${column}2:${column}${lastRow}`, `OR(${column}2="",AND(ISNUMBER(${column}2),OR(${startColumn}2="",${column}2>=${startColumn}2)))`, {
                    prompt: config.prompts[index],
                    error: 'Tanggal target harus berupa tanggal Excel dan tidak boleh lebih awal dari tanggal mulai.'
                }));
            } else {
                validations.push(validation('date', `${column}2:${column}${lastRow}`, 'DATE(2000,1,1)', {
                    formula2: 'DATE(2100,12,31)',
                    operator: 'between',
                    prompt: `${config.prompts[index]} Gunakan format DD/MM/YYYY.`,
                    error: 'Masukkan nilai tanggal Excel antara 01/01/2000 dan 31/12/2100.'
                }));
            }
        });
        (config.percentageColumns || []).forEach(index => {
            const column = columnName(index);
            const statusColumn = config.percentageStatusIndex == null
                ? null
                : columnName(config.percentageStatusIndex);
            const formula = statusColumn
                ? `OR(${column}2="",AND(ISNUMBER(${column}2),${column}2>=0,${column}2<=100,OR(${statusColumn}2<>"Selesai",${column}2=100)))`
                : `OR(${column}2="",AND(ISNUMBER(${column}2),${column}2>=0,${column}2<=100))`;
            validations.push(validation('custom', `${column}2:${column}${lastRow}`, formula, {
                prompt: config.prompts[index],
                error: statusColumn
                    ? 'Persentase harus 0–100; status Selesai harus bernilai 100.'
                    : 'Masukkan angka antara 0 dan 100.'
            }));
        });
        (config.generalValidationRanges || []).forEach(range => {
            validations.push(validation('custom', range.sqref, range.formula, {
                prompt: range.prompt,
                error: range.error
            }));
        });
        const lastColumn = columnName(config.headers.length - 1);
        return worksheetXml({
            dimension: `A1:${lastColumn}${lastRow}`,
            widths: config.widths,
            rows,
            freeze: 1,
            validations,
            conditionalFormats: [
                conditionalFormatting(`B2:${lastColumn}${lastRow}`, config.incompleteFormula)
            ],
            tableId: config.tableId,
            allowInsertRows: true,
            landscape: true
        });
    }

    function participantSheet() {
        return repeatedSheet({
            tableId: 2,
            headers: ['Nomor', 'Nama Peserta', 'Jabatan', 'Instansi atau Divisi', 'Peran dalam Kegiatan', 'Keterangan'],
            widths: [10, 30, 26, 30, 38, 38],
            prompts: {
                1: 'Tuliskan nama lengkap peserta.',
                2: 'Tuliskan jabatan peserta.',
                3: 'Tuliskan unit kerja, divisi, atau instansi peserta.',
                4: 'Jelaskan peran peserta dalam kegiatan.',
                5: 'Tuliskan informasi tambahan yang relevan.'
            },
            generalValidationRanges: [{
                sqref: 'B2:F101',
                formula: 'OR(COUNTA($B2:$F2)=0,LEN(TRIM($B2))>0)',
                prompt: 'Nama Peserta wajib diisi apabila salah satu field pada baris peserta digunakan.',
                error: 'Lengkapi Nama Peserta atau kosongkan seluruh baris.'
            }],
            incompleteFormula: 'AND(COUNTA($B2:$F2)>0,LEN(TRIM($B2))=0)'
        });
    }

    function findingSheet() {
        return repeatedSheet({
            tableId: 3,
            headers: ['Nomor', 'Kode Temuan', 'Kategori Temuan', 'Deskripsi Temuan', 'Tingkat Prioritas', 'Dampak', 'Rekomendasi', 'Penanggung Jawab', 'Target Penyelesaian'],
            widths: [10, 18, 24, 48, 20, 38, 42, 28, 22],
            dropdownColumns: { 2: 'FindingCategory', 4: 'FindingPriority' },
            dateColumns: [8],
            prompts: {
                1: 'Tuliskan kode unik temuan, misalnya TMN-001.',
                2: 'Pilih kategori temuan yang paling sesuai.',
                3: 'Jelaskan kondisi atau masalah yang ditemukan secara rinci.',
                4: 'Pilih Rendah, Sedang, Tinggi, atau Kritis.',
                5: 'Jelaskan dampak yang dapat terjadi.',
                6: 'Tuliskan tindakan perbaikan yang disarankan.',
                7: 'Tuliskan nama pihak yang bertanggung jawab.',
                8: 'Isi tanggal target penyelesaian dalam format DD/MM/YYYY.'
            },
            generalValidationRanges: [{
                sqref: 'B2:B101 D2:D101 F2:H101',
                formula: 'OR(COUNTA($B2:$I2)=0,AND($B2<>"",$C2<>"",$D2<>"",$E2<>""))',
                prompt: 'Jika baris digunakan, Kode, Kategori, Deskripsi, dan Tingkat Prioritas wajib diisi.',
                error: 'Lengkapi seluruh field wajib pada baris temuan atau kosongkan baris.'
            }],
            incompleteFormula: 'AND(COUNTA($B2:$I2)>0,OR($B2="",$C2="",$D2="",$E2=""))'
        });
    }

    function actionSheet() {
        return repeatedSheet({
            tableId: 4,
            headers: ['Nomor', 'Kode Tindakan', 'Tindakan yang Dilakukan', 'Penanggung Jawab', 'Tanggal Mulai', 'Target Selesai', 'Status', 'Persentase', 'Keterangan'],
            widths: [10, 18, 48, 28, 18, 18, 22, 16, 38],
            dropdownColumns: { 6: 'ActionStatus' },
            dateColumns: [4, 5],
            dateRelation: { startIndex: 4, targetIndex: 5 },
            percentageColumns: [7],
            percentageStatusIndex: 6,
            prompts: {
                1: 'Tuliskan kode unik tindakan, misalnya TL-001.',
                2: 'Jelaskan tindakan perbaikan atau tindak lanjut.',
                3: 'Tuliskan nama pihak yang bertanggung jawab.',
                4: 'Isi tanggal mulai dalam format DD/MM/YYYY.',
                5: 'Isi target selesai; tidak boleh lebih awal dari tanggal mulai.',
                6: 'Pilih status tindakan dari dropdown.',
                7: 'Isi angka 0–100 tanpa simbol persen.',
                8: 'Tuliskan catatan perkembangan terbaru.'
            },
            generalValidationRanges: [{
                sqref: 'B2:D101 I2:I101',
                formula: 'OR(COUNTA($B2:$I2)=0,AND($B2<>"",$C2<>"",$D2<>"",$G2<>""))',
                prompt: 'Jika baris digunakan, Kode, Tindakan, Penanggung Jawab, dan Status wajib diisi.',
                error: 'Lengkapi seluruh field wajib pada baris tindak lanjut atau kosongkan baris.'
            }],
            incompleteFormula: 'AND(COUNTA($B2:$I2)>0,OR($B2="",$C2="",$D2="",$G2=""))'
        });
    }

    function documentationSheet() {
        return repeatedSheet({
            tableId: 5,
            headers: ['Nomor', 'Nama Dokumentasi', 'Jenis Dokumentasi', 'Deskripsi', 'Tanggal', 'Lokasi atau Nama File', 'Keterangan'],
            widths: [10, 30, 22, 44, 18, 38, 38],
            dropdownColumns: { 2: 'DocumentationType' },
            dateColumns: [4],
            prompts: {
                1: 'Tuliskan nama foto, dokumen, atau bukti pendukung.',
                2: 'Pilih jenis dokumentasi dari dropdown.',
                3: 'Jelaskan isi dokumentasi; jangan menanam gambar di dalam sel.',
                4: 'Isi tanggal dokumentasi dibuat dalam format DD/MM/YYYY.',
                5: 'Tuliskan nama file atau lokasi penyimpanan dokumentasi.',
                6: 'Tuliskan informasi tambahan yang relevan.'
            },
            generalValidationRanges: [{
                sqref: 'B2:B101 D2:D101 F2:G101',
                formula: 'OR(COUNTA($B2:$G2)=0,AND($B2<>"",$C2<>"",$D2<>""))',
                prompt: 'Jika baris digunakan, Nama, Jenis, dan Deskripsi dokumentasi wajib diisi.',
                error: 'Lengkapi seluruh field wajib dokumentasi atau kosongkan baris.'
            }],
            incompleteFormula: 'AND(COUNTA($B2:$G2)>0,OR($B2="",$C2="",$D2=""))'
        });
    }

    function metadataSheet(options) {
        const metadata = [
            ['template_name', TEMPLATE.name],
            ['template_code', TEMPLATE.code],
            ['template_version', TEMPLATE.version],
            ['report_type', TEMPLATE.reportType],
            ['published_date', TEMPLATE.publishedDate],
            ['updated_date', TEMPLATE.updatedDate],
            ['template_status', 'active'],
            ['template_description', TEMPLATE.description],
            ['source_schema_id', options.schemaId || 'general-report'],
            ['source_schema_code', options.schemaCode || 'GENERAL'],
            ['structure_signature', 'PETUNJUK|DATA_LAPORAN|DAFTAR_PESERTA|DAFTAR_TEMUAN|TINDAK_LANJUT|DOKUMENTASI|METADATA'],
            ['field_count', String(MAIN_FIELDS.length)]
        ];
        const listEntries = Object.entries(LISTS);
        const maximumRows = Math.max(metadata.length + 1, ...listEntries.map(([, values]) => values.length + 1));
        const rows = [];
        for (let rowNumber = 1; rowNumber <= maximumRows; rowNumber += 1) {
            const cells = [];
            if (rowNumber === 1) {
                cells.push(cell('A1', 'Key', 10), cell('B1', 'Value', 10));
            } else if (metadata[rowNumber - 2]) {
                cells.push(
                    cell(`A${rowNumber}`, metadata[rowNumber - 2][0], 6),
                    cell(`B${rowNumber}`, metadata[rowNumber - 2][1], 6)
                );
            }
            listEntries.forEach(([name, values], listIndex) => {
                const column = columnName(listIndex + 3);
                if (rowNumber === 1) cells.push(cell(`${column}1`, name, 10));
                else if (values[rowNumber - 2]) cells.push(cell(`${column}${rowNumber}`, values[rowNumber - 2], 6));
            });
            rows.push(row(rowNumber, cells));
        }
        return worksheetXml({
            dimension: `A1:${columnName(listEntries.length + 2)}${maximumRows}`,
            widths: [28, 88, 24, 24, 24, 24, 24, 18],
            rows,
            showGridLines: false
        });
    }

    function stylesXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="2"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/><numFmt numFmtId="165" formatCode="0.00"/></numFmts>
  <fonts count="4">
    <font><sz val="11"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>
    <font><b/><color rgb="FF173F99"/><sz val="15"/><name val="Calibri"/></font>
    <font><i/><color rgb="FF657083"/><sz val="10"/><name val="Calibri"/></font>
  </fonts>
  <fills count="8">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2457C5"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE7E9ED"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE2F0D9"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF4CCCC"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border><left style="thin"><color rgb="FFB8C2D1"/></left><right style="thin"><color rgb="FFB8C2D1"/></right><top style="thin"><color rgb="FFB8C2D1"/></top><bottom style="thin"><color rgb="FFB8C2D1"/></bottom><diagonal/></border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="18">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="top" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="top" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="center" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
    <xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyProtection="1"><protection locked="0"/></xf>
    <xf numFmtId="164" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyProtection="1"><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment horizontal="right"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="top" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="top" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="164" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyProtection="1"><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="top" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyProtection="1"><alignment vertical="top" wrapText="1"/><protection locked="0"/></xf>
    <xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
  </cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
  <dxfs count="1"><dxf><font><color rgb="FF9C0006"/></font><fill><patternFill patternType="solid"><fgColor rgb="FFFFC7CE"/><bgColor indexed="64"/></patternFill></fill></dxf></dxfs>
  <tableStyles count="1" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>`;
    }

    function tableXml(id, name, headers, ref) {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="${id}" name="${name}" displayName="${name}" ref="${ref}" totalsRowShown="0">
  <autoFilter ref="${ref}"/>
  <tableColumns count="${headers.length}">${headers.map((header, index) => `<tableColumn id="${index + 1}" name="${xml(header)}"/>`).join('')}</tableColumns>
  <tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/>
</table>`;
    }

    function workbookXml() {
        const definedNames = [
            ['CompletionStatus', "'METADATA'!$D$2:$D$5"],
            ['FindingPriority', "'METADATA'!$E$2:$E$5"],
            ['FindingCategory', "'METADATA'!$F$2:$F$8"],
            ['ActionStatus', "'METADATA'!$G$2:$G$6"],
            ['DocumentationType', "'METADATA'!$H$2:$H$7"],
            ['YesNo', "'METADATA'!$I$2:$I$3"]
        ];
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <fileVersion appName="xl" lastEdited="7" lowestEdited="7" rupBuild="27126"/>
  <workbookPr date1904="0"/>
  <workbookProtection workbookPassword="CF75" lockStructure="1"/>
  <bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000" activeTab="0"/></bookViews>
  <sheets>${SHEETS.map(sheet => `<sheet name="${sheet.name}" sheetId="${sheet.id}"${sheet.hidden ? ' state="hidden"' : ''} r:id="rId${sheet.id}"/>`).join('')}</sheets>
  <definedNames>${definedNames.map(item => `<definedName name="${item[0]}">${item[1]}</definedName>`).join('')}</definedNames>
  <calcPr calcId="191029" calcMode="auto" fullCalcOnLoad="1"/>
</workbook>`;
    }

    function workbookRelationshipsXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  ${SHEETS.map(sheet => `<Relationship Id="rId${sheet.id}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${sheet.id}.xml"/>`).join('')}
  <Relationship Id="rId8" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>`;
    }

    function contentTypesXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  ${SHEETS.map(sheet => `<Override PartName="/xl/worksheets/sheet${sheet.id}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('')}
  ${SHEETS.filter(sheet => sheet.table).map((sheet, index) => `<Override PartName="/xl/tables/table${index + 1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>`).join('')}
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/docProps/custom.xml" ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml"/>
</Types>`;
    }

    function rootRelationshipsXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
  <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/custom.xml"/>
</Relationships>`;
    }

    function corePropertiesXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>${TEMPLATE.name}</dc:title>
  <dc:subject>Template XLSX laporan terstruktur</dc:subject>
  <dc:creator>FleetMonitor · PT Bina Rekayasa Anugrah</dc:creator>
  <cp:keywords>${TEMPLATE.code},${TEMPLATE.reportType},template-xlsx-v1</cp:keywords>
  <dc:description>${TEMPLATE.description}</dc:description>
  <dcterms:created xsi:type="dcterms:W3CDTF">2026-07-31T00:00:00Z</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">2026-07-31T00:00:00Z</dcterms:modified>
</cp:coreProperties>`;
    }

    function appPropertiesXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>FleetMonitor</Application><AppVersion>1.0</AppVersion>
  <TitlesOfParts><vt:vector size="${SHEETS.length}" baseType="lpstr">${SHEETS.map(sheet => `<vt:lpstr>${sheet.name}</vt:lpstr>`).join('')}</vt:vector></TitlesOfParts>
</Properties>`;
    }

    function customPropertiesXml(options) {
        const properties = [
            ['TemplateCode', TEMPLATE.code],
            ['TemplateVersion', TEMPLATE.version],
            ['ReportType', TEMPLATE.reportType],
            ['TemplateStatus', 'active'],
            ['SourceSchemaId', options.schemaId || 'general-report']
        ];
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">${properties.map((item, index) => `<property fmtid="{D5CDD505-2E9C-101B-9397-08002B2CF9AE}" pid="${index + 2}" name="${item[0]}"><vt:lpwstr>${xml(item[1])}</vt:lpwstr></property>`).join('')}</Properties>`;
    }

    function sheetRelationshipXml(tableIndex) {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table${tableIndex}.xml"/></Relationships>`;
    }

    async function build(options = {}) {
        if (!global.JSZip) throw new Error('Pustaka pembentuk XLSX belum siap.');
        const zip = new global.JSZip();
        zip.file('[Content_Types].xml', contentTypesXml());
        zip.folder('_rels').file('.rels', rootRelationshipsXml());
        zip.folder('docProps').file('core.xml', corePropertiesXml());
        zip.folder('docProps').file('app.xml', appPropertiesXml());
        zip.folder('docProps').file('custom.xml', customPropertiesXml(options));
        const xl = zip.folder('xl');
        xl.file('workbook.xml', workbookXml());
        xl.file('styles.xml', stylesXml());
        xl.folder('_rels').file('workbook.xml.rels', workbookRelationshipsXml());

        const worksheetXmlList = [
            petunjukSheet(),
            dataLaporanSheet(),
            participantSheet(),
            findingSheet(),
            actionSheet(),
            documentationSheet(),
            metadataSheet(options)
        ];
        worksheetXmlList.forEach((contents, index) => {
            xl.folder('worksheets').file(`sheet${index + 1}.xml`, contents);
        });

        const tableDefinitions = [
            { id: 1, name: 'tblDataLaporan', headers: ['Kode Field', 'Nama Field', 'Petunjuk Pengisian', 'Contoh atau Placeholder', 'Tipe Data', 'Wajib', 'Nilai / Isian Pengguna'], ref: 'A1:G22', sheet: 2 },
            { id: 2, name: 'tblDaftarPeserta', headers: ['Nomor', 'Nama Peserta', 'Jabatan', 'Instansi atau Divisi', 'Peran dalam Kegiatan', 'Keterangan'], ref: 'A1:F101', sheet: 3 },
            { id: 3, name: 'tblDaftarTemuan', headers: ['Nomor', 'Kode Temuan', 'Kategori Temuan', 'Deskripsi Temuan', 'Tingkat Prioritas', 'Dampak', 'Rekomendasi', 'Penanggung Jawab', 'Target Penyelesaian'], ref: 'A1:I101', sheet: 4 },
            { id: 4, name: 'tblTindakLanjut', headers: ['Nomor', 'Kode Tindakan', 'Tindakan yang Dilakukan', 'Penanggung Jawab', 'Tanggal Mulai', 'Target Selesai', 'Status', 'Persentase', 'Keterangan'], ref: 'A1:I101', sheet: 5 },
            { id: 5, name: 'tblDokumentasi', headers: ['Nomor', 'Nama Dokumentasi', 'Jenis Dokumentasi', 'Deskripsi', 'Tanggal', 'Lokasi atau Nama File', 'Keterangan'], ref: 'A1:G101', sheet: 6 }
        ];
        tableDefinitions.forEach(table => {
            xl.folder('tables').file(`table${table.id}.xml`, tableXml(table.id, table.name, table.headers, table.ref));
            xl.folder('worksheets').folder('_rels').file(`sheet${table.sheet}.xml.rels`, sheetRelationshipXml(table.id));
        });
        return zip.generateAsync({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            compression: 'DEFLATE',
            compressionOptions: { level: 6 }
        });
    }

    global.FleetXlsxTemplateBuilder = Object.freeze({
        version: '1.0.0',
        template: { ...TEMPLATE },
        sheetNames: SHEETS.map(sheet => sheet.name),
        build
    });
})(window);
