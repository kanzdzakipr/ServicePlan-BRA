(function (global) {
    'use strict';

    const ENGINE_VERSION = '1.1.1';
    const ENVELOPE_VERSION = '1.0.0';
    const SUPPORTED_EXTENSIONS = new Set([
        '.doc',
        '.docx',
        '.pdf',
        '.xls',
        '.xlsx',
        '.xlsm',
        '.csv',
        '.tsv',
        '.jpg',
        '.jpeg',
        '.png',
        '.webp'
    ]);
    const AUDITED_UNSUPPORTED_EXTENSIONS = new Set(['.rar', '.zip', '.7z']);
    const SPREADSHEET_EXTENSIONS = new Set(['.xls', '.xlsx', '.xlsm', '.csv', '.tsv']);
    const IMAGE_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp']);
    const MAX_SOURCE_BYTES = 100 * 1024 * 1024;
    const MAX_ZIP_ENTRIES = 10000;
    const MAX_ZIP_UNCOMPRESSED_BYTES = 64 * 1024 * 1024;
    const MAX_ZIP_ENTRY_BYTES = 32 * 1024 * 1024;
    const MAX_RETAINED_FRAGMENTS = 600000;
    const MAX_FRAGMENT_CHARACTERS = 2 * 1024 * 1024;
    const MAX_RETAINED_CHARACTERS = 64 * 1024 * 1024;
    const MAX_WORKER_TASK_MS = 240000;
    const MAX_PDF_PAGES = 1000;
    const MAX_PDF_TEXT_ITEMS_PER_PAGE = 100000;
    const MAX_PDF_TEXT_CHARACTERS_PER_PAGE = 5 * 1024 * 1024;
    const MAX_PDF_TASK_MS = 15 * 60 * 1000;
    const MAX_OCR_TILES_PER_PAGE = 100;
    const MAX_RASTER_PIXELS = 40 * 1000 * 1000;
    const MAX_RASTER_DIMENSION = 20000;
    const MAX_MAPPING_CANDIDATES = 50000;
    const MAX_MAPPED_ROWS = 2000;
    const APPLICATION_ROOT = (() => {
        const scriptSource = document.currentScript?.src;
        return scriptSource ? new URL('../', scriptSource).href : document.baseURI;
    })();

    const SCHEMA_HINTS = {
        bapp: ['bapp', 'berita acara penerimaan penyerahan barang', 'penerimaan barang parts'],
        'bast-mde1': ['bast', 'mde 1', 'serah terima alat berat', 'berita acara serah terima'],
        'bhw-in': ['bhw in', 'barang masuk', 'buku harian warehouse masuk'],
        'bhw-out': ['bhw out', 'barang keluar', 'buku harian warehouse keluar'],
        'bukti-kirim': ['bukti kirim', 'bukti terima barang intern', 'dept equip'],
        'mde-02': ['mde 02', 'kartu pemeriksaan a2b', 'kartu pemeriksaan alat berat'],
        kalibrasi: ['laporan pelaksanaan kalibrasi', 'kalibrasi alat ukur'],
        lho: ['form lho', 'laporan harian operasi alat', 'lho'],
        'p2h-excavator': ['p2h hydraulic excavator', 'p2h excavator', 'pc 200'],
        'p2h-roller': ['p2h single drum rollers', 'p2h roller', 'bw211', 'cs10 gc'],
        'penyerahan-ekspedisi': ['penyerahan a2b kepada expedisi', 'penyerahan alat berat kepada ekspedisi'],
        ppb: ['form ppb', 'pesanan pembelian', 'pengadaan barang', 'p 3'],
        'repair-overhaul': ['repair overhoul', 'repair overhaul', 'r o', 'permohonan perbaikan'],
        spb: ['form spb', 'surat permintaan barang', 'p 1'],
        spl: ['form spl', 'surat perintah lembur', 'spl yard'],
        sppu: ['form sppu', 'surat permintaan parts urgent', 'sppu yard'],
        'sppu-006-pf04-cs10': ['sppu 006', 'pf 04', 'cs 10'],
        'maintenance-board': ['maintenance board a2b', 'maintenace board'],
        'parts-weekly': ['report parts weekly', 'laporan parts mingguan', 'parts weekly']
    };

    const FIELD_ALIASES = {
        nomor: ['no', 'nomor', 'nomor dokumen', 'document no'],
        nomor_urut: ['no urut', 'nomor urut'],
        nomor_log: ['no log', 'nomor log'],
        nomor_bukti: ['no bukti', 'nomor bukti'],
        nomor_po: ['no po', 'nomor po', 'purchase order'],
        nomor_spb: ['no spb', 'nomor spb'],
        nomor_ppb: ['no ppb', 'nomor ppb'],
        nomor_polisi: ['no polisi', 'nomor polisi', 'nopol', 'plat nomor'],
        nomor_faktur: ['no faktur', 'nomor faktur', 'invoice'],
        tanggal: ['tgl', 'tanggal', 'date'],
        tanggal_po: ['tanggal po', 'tgl po'],
        tanggal_faktur: ['tanggal faktur', 'tgl faktur'],
        periode: ['periode', 'bulan tahun', 'period'],
        bulan: ['bulan', 'month'],
        project: ['project', 'proyek', 'job site', 'site'],
        job_site: ['job site', 'site', 'lokasi kerja'],
        lokasi: ['lokasi', 'location', 'site'],
        kode_alat: ['kode alat', 'nomor kode alat', 'unit code', 'kode unit'],
        id_alat: ['id alat', 'unit id', 'kode unit'],
        code_number: ['code number', 'kode unit', 'unit code'],
        serial_number: ['serial number', 'nomor seri', 'no seri'],
        nomor_seri: ['serial number', 'nomor seri', 'no seri'],
        jenis_alat: ['jenis alat', 'tipe unit', 'equipment type'],
        tipe_alat: ['tipe alat', 'model unit', 'type'],
        merek_alat: ['merek alat', 'brand', 'merk alat'],
        merek_model: ['merek model', 'brand model', 'merk model'],
        model: ['model', 'unit model'],
        operator: ['operator', 'nama operator'],
        nrp: ['nrp', 'nik', 'employee id'],
        hm_om: ['hm om', 'hour meter', 'odometer'],
        hm_awal: ['hm awal', 'hour meter awal'],
        hm_akhir: ['hm akhir', 'hour meter akhir'],
        hm_sebelum: ['hm sebelum', 'hour meter sebelum'],
        hm_selesai: ['hm selesai', 'hour meter selesai'],
        nama: ['nama', 'nama barang', 'nama parts', 'part name', 'description', 'uraian'],
        item: ['item', 'uraian', 'description', 'pemeriksaan'],
        pn: ['pn', 'p n', 'part number', 'no part', 'nomor part'],
        part_number: ['part number', 'pn', 'p n', 'nomor part'],
        jumlah: ['jumlah', 'qty', 'quantity', 'kuantitas'],
        satuan: ['satuan', 'sat', 'uom', 'unit'],
        harga: ['harga', 'unit price', 'harga satuan'],
        total: ['total', 'jumlah harga', 'amount'],
        keterangan: ['keterangan', 'ket', 'remark', 'remarks', 'catatan', 'notes'],
        kondisi: ['kondisi', 'condition', 'status'],
        tanggal_mulai: ['tanggal mulai', 'start date'],
        tanggal_selesai: ['tanggal selesai', 'end date'],
        jam_mulai: ['jam mulai', 'start time'],
        jam_selesai: ['jam selesai', 'end time'],
        dibuat_oleh: ['dibuat oleh', 'prepared by', 'pembuat'],
        diperiksa_oleh: ['diperiksa oleh', 'checked by', 'pemeriksa'],
        disetujui_oleh: ['disetujui oleh', 'approved by', 'menyetujui']
    };

    const STOP_WORDS = new Set([
        'dan', 'atau', 'yang', 'untuk', 'dari', 'pada', 'dengan', 'ke', 'di', 'oleh',
        'form', 'laporan', 'surat', 'berita', 'acara', 'the', 'of', 'a', 'an'
    ]);

    let sharedWorker = null;
    let workerSequence = 0;
    const workerPending = new Map();

    function extensionOf(fileName) {
        const match = String(fileName || '').toLowerCase().match(/(\.[a-z0-9]+)$/);
        return match ? match[1] : '';
    }

    function assetUrl(relativePath) {
        return new URL(relativePath, APPLICATION_ROOT).href;
    }

    function normalizeSpace(value) {
        return String(value == null ? '' : value)
            .replace(/\u0000/g, '')
            .replace(/\r\n?/g, '\n')
            .replace(/[ \t\u00A0]+/g, ' ')
            .replace(/ *\n */g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function normalizeKey(value) {
        return String(value == null ? '' : value)
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('id')
            .replace(/&/g, ' dan ')
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokens(value) {
        return normalizeKey(value)
            .split(' ')
            .filter(token => token.length > 1 && !STOP_WORDS.has(token));
    }

    function unique(values) {
        return [...new Set(values.filter(value => value !== '' && value != null))];
    }

    function clamp(value, minimum = 0, maximum = 1) {
        return Math.max(minimum, Math.min(maximum, value));
    }

    function createExtraction(format, parserName) {
        return {
            format,
            parser: {
                name: parserName,
                version: ENGINE_VERSION
            },
            sections: [],
            tables: [],
            fragments: [],
            artifacts: [],
            warnings: [],
            properties: {},
            stats: {
                sections: 0,
                tables: 0,
                fragments: 0,
                characters: 0
            },
            _fragmentSequence: 0,
            _characterCount: 0
        };
    }

    function nextFragmentId(extraction) {
        if (!Number.isFinite(extraction._fragmentSequence)) {
            extraction._fragmentSequence = extraction.fragments.reduce((maximum, fragment) => {
                const value = Number(String(fragment.id || '').replace(/\D/g, ''));
                return Number.isFinite(value) ? Math.max(maximum, value) : maximum;
            }, 0);
        }
        extraction._fragmentSequence += 1;
        return `F${String(extraction._fragmentSequence).padStart(7, '0')}`;
    }

    function addFragment(extraction, kind, value, sourceRef, meta = {}) {
        const text = normalizeSpace(value);
        if (!text && !meta.formula && !meta.comment && !meta.hyperlink) return null;
        if (text.length > MAX_FRAGMENT_CHARACTERS) {
            throw new Error(
                `${sourceRef || kind} memuat ${text.length.toLocaleString('id-ID')} karakter; `
                + `batas aman per fragmen ${MAX_FRAGMENT_CHARACTERS.toLocaleString('id-ID')}.`
            );
        }
        if (extraction.fragments.length >= MAX_RETAINED_FRAGMENTS) {
            throw new Error(
                `Dokumen menghasilkan lebih dari ${MAX_RETAINED_FRAGMENTS.toLocaleString('id-ID')} fragmen; `
                + 'impor dihentikan untuk melindungi memori browser.'
            );
        }
        const nextCharacterCount = Number(extraction._characterCount || 0) + text.length;
        if (nextCharacterCount > MAX_RETAINED_CHARACTERS) {
            throw new Error(
                `Dokumen menghasilkan lebih dari ${MAX_RETAINED_CHARACTERS / 1024 / 1024} MiB teks; `
                + 'impor dihentikan untuk melindungi memori browser.'
            );
        }
        extraction._characterCount = nextCharacterCount;
        const fragment = {
            id: nextFragmentId(extraction),
            kind,
            value: text,
            sourceRef,
            meta
        };
        extraction.fragments.push(fragment);
        return fragment.id;
    }

    function addWarning(extraction, code, message, severity = 'warning', sourceRef = '') {
        extraction.warnings.push({ code, message, severity, sourceRef });
    }

    function finalizeExtraction(extraction) {
        const characterCount = extraction.fragments.reduce(
            (sum, fragment) => sum + String(fragment.value || '').length,
            0
        );
        if (characterCount > MAX_RETAINED_CHARACTERS) {
            throw new Error(
                `Dokumen menghasilkan ${(characterCount / 1024 / 1024).toFixed(1)} MiB teks; `
                + `batas aman ${MAX_RETAINED_CHARACTERS / 1024 / 1024} MiB.`
            );
        }
        extraction.stats = {
            ...extraction.stats,
            sections: extraction.sections.length,
            tables: extraction.tables.length,
            fragments: extraction.fragments.length,
            characters: characterCount
        };
        delete extraction._fragmentSequence;
        delete extraction._characterCount;
        return extraction;
    }

    async function sha256(buffer) {
        if (!global.crypto?.subtle) {
            throw new Error('Browser tidak menyediakan Web Crypto SHA-256. Impor dihentikan agar deduplikasi tidak memakai checksum lemah.');
        }
        const digest = await global.crypto.subtle.digest('SHA-256', buffer);
        return [...new Uint8Array(digest)]
            .map(byte => byte.toString(16).padStart(2, '0'))
            .join('');
    }

    function detectSignature(bytes) {
        if (bytes.length >= 5 && new TextDecoder('ascii').decode(bytes.subarray(0, 5)) === '%PDF-') {
            return 'pdf';
        }
        if (bytes.length >= 4 && bytes[0] === 0x50 && bytes[1] === 0x4b) return 'zip';
        if (
            bytes.length >= 8
            && bytes[0] === 0xd0
            && bytes[1] === 0xcf
            && bytes[2] === 0x11
            && bytes[3] === 0xe0
        ) return 'cfb';
        if (bytes.length >= 3 && bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) return 'jpeg';
        if (
            bytes.length >= 8
            && bytes[0] === 0x89
            && bytes[1] === 0x50
            && bytes[2] === 0x4e
            && bytes[3] === 0x47
        ) return 'png';
        if (
            bytes.length >= 6
            && String.fromCharCode(...bytes.subarray(0, 6)) === 'Rar!\x1a\x07'
        ) return 'rar';
        return 'text-or-unknown';
    }

    function signatureExpectedFor(extension) {
        if (extension === '.pdf') return ['pdf'];
        if (['.docx', '.xlsx', '.xlsm'].includes(extension)) return ['zip'];
        if (['.doc', '.xls'].includes(extension)) return ['cfb'];
        if (extension === '.jpg' || extension === '.jpeg') return ['jpeg'];
        if (extension === '.png') return ['png'];
        if (extension === '.rar') return ['rar'];
        return [];
    }

    function zipUint16(bytes, offset) {
        return bytes[offset] + bytes[offset + 1] * 0x100;
    }

    function zipUint32(bytes, offset) {
        return bytes[offset]
            + bytes[offset + 1] * 0x100
            + bytes[offset + 2] * 0x10000
            + bytes[offset + 3] * 0x1000000;
    }

    function zipSignatureAt(bytes, offset, signature) {
        return offset >= 0
            && offset + 4 <= bytes.length
            && zipUint32(bytes, offset) === signature;
    }

    function sanitizeZipEntryName(name) {
        const parts = [];
        String(name || '').replace(/\\/g, '/').split('/').forEach(part => {
            if (!part || part === '.') return;
            if (part === '..') {
                parts.pop();
                return;
            }
            parts.push(part);
        });
        return parts.join('/');
    }

    function preflightZipCentralDirectory(bytes, fileName) {
        const minimumEocdSize = 22;
        const searchFloor = Math.max(0, bytes.length - minimumEocdSize - 0xffff);
        let eocdOffset = -1;
        for (let offset = bytes.length - minimumEocdSize; offset >= searchFloor; offset -= 1) {
            if (!zipSignatureAt(bytes, offset, 0x06054b50)) continue;
            const commentLength = zipUint16(bytes, offset + 20);
            if (offset + minimumEocdSize + commentLength <= bytes.length) {
                eocdOffset = offset;
                break;
            }
        }
        if (eocdOffset < 0) {
            throw new Error(`${fileName} tidak memiliki End of Central Directory ZIP yang valid.`);
        }

        const diskNumber = zipUint16(bytes, eocdOffset + 4);
        const centralDisk = zipUint16(bytes, eocdOffset + 6);
        const entriesOnDisk = zipUint16(bytes, eocdOffset + 8);
        const totalEntries = zipUint16(bytes, eocdOffset + 10);
        const centralSize = zipUint32(bytes, eocdOffset + 12);
        const centralOffset = zipUint32(bytes, eocdOffset + 16);
        if (
            diskNumber !== 0
            || centralDisk !== 0
            || entriesOnDisk !== totalEntries
        ) {
            throw new Error(`${fileName} memakai ZIP multi-disk yang tidak didukung.`);
        }
        if (
            totalEntries === 0xffff
            || centralSize === 0xffffffff
            || centralOffset === 0xffffffff
        ) {
            throw new Error(`${fileName} memakai ZIP64; format ini ditolak oleh batas impor browser.`);
        }
        if (totalEntries > MAX_ZIP_ENTRIES) {
            throw new Error(`${fileName} memiliki ${totalEntries} record ZIP; batas aman ${MAX_ZIP_ENTRIES}.`);
        }
        const centralEnd = centralOffset + centralSize;
        if (
            centralOffset < 0
            || centralEnd > eocdOffset
            || centralEnd > bytes.length
        ) {
            throw new Error(`${fileName} memiliki Central Directory ZIP di luar batas file.`);
        }

        const utf8Decoder = new TextDecoder('utf-8');
        const legacyDecoder = new TextDecoder('windows-1252');
        const seenNames = new Set();
        let position = centralOffset;
        let uncompressedBytes = 0;
        for (let index = 0; index < totalEntries; index += 1) {
            if (!zipSignatureAt(bytes, position, 0x02014b50) || position + 46 > centralEnd) {
                throw new Error(`${fileName} memiliki record Central Directory ZIP yang rusak.`);
            }
            const flags = zipUint16(bytes, position + 8);
            const compressedSize = zipUint32(bytes, position + 20);
            const uncompressedSize = zipUint32(bytes, position + 24);
            const nameLength = zipUint16(bytes, position + 28);
            const extraLength = zipUint16(bytes, position + 30);
            const commentLength = zipUint16(bytes, position + 32);
            if (compressedSize === 0xffffffff || uncompressedSize === 0xffffffff) {
                throw new Error(`${fileName} memiliki entry ZIP64 yang tidak didukung.`);
            }
            if (flags & 0x0001) {
                throw new Error(`${fileName} memiliki entry ZIP terenkripsi yang tidak dapat diaudit.`);
            }
            const nameStart = position + 46;
            const recordEnd = nameStart + nameLength + extraLength + commentLength;
            if (!nameLength || recordEnd > centralEnd || recordEnd > bytes.length) {
                throw new Error(`${fileName} memiliki panjang record ZIP yang tidak valid.`);
            }
            const rawName = bytes.subarray(nameStart, nameStart + nameLength);
            const decodedName = (flags & 0x0800 ? utf8Decoder : legacyDecoder).decode(rawName);
            const safeName = sanitizeZipEntryName(decodedName);
            if (!safeName || decodedName.includes('\u0000')) {
                throw new Error(`${fileName} memiliki nama entry ZIP yang tidak valid.`);
            }
            if (seenNames.has(safeName)) {
                throw new Error(`${fileName} memiliki nama entry ZIP duplikat setelah sanitasi: ${safeName}.`);
            }
            seenNames.add(safeName);
            if (uncompressedSize > MAX_ZIP_ENTRY_BYTES) {
                throw new Error(
                    `${fileName} memiliki entry ${safeName} sebesar `
                    + `${(uncompressedSize / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman per entry ${MAX_ZIP_ENTRY_BYTES / 1024 / 1024} MB.`
                );
            }
            uncompressedBytes += uncompressedSize;
            if (uncompressedBytes > MAX_ZIP_UNCOMPRESSED_BYTES) {
                throw new Error(
                    `${fileName} mengembang menjadi ${(uncompressedBytes / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman ${MAX_ZIP_UNCOMPRESSED_BYTES / 1024 / 1024} MB.`
                );
            }
            position = recordEnd;
        }
        if (position > centralEnd) {
            throw new Error(`${fileName} memiliki Central Directory ZIP yang tidak konsisten.`);
        }
        return {
            entries: totalEntries,
            uncompressedBytes,
            centralDirectoryBytes: centralSize
        };
    }

    function ensureWorker() {
        if (sharedWorker) return sharedWorker;
        if (!global.Worker) throw new Error('Browser ini tidak mendukung Web Worker.');
        try {
            sharedWorker = new Worker(`${assetUrl('scripts/document-import-worker.js')}?v=20260726-15`);
        } catch (error) {
            throw new Error(`Web Worker tidak dapat dijalankan. Buka dashboard melalui HTTP/HTTPS. ${error.message}`);
        }
        sharedWorker.addEventListener('message', event => {
            const { id, type, result, error, progress } = event.data || {};
            const pending = workerPending.get(id);
            if (!pending) return;
            if (type === 'progress') {
                pending.onProgress?.(progress);
                return;
            }
            workerPending.delete(id);
            clearTimeout(pending.timer);
            if (type === 'error') {
                const workerError = new Error(error?.message || 'Worker gagal memproses dokumen.');
                workerError.name = error?.name || 'WorkerError';
                workerError.stack = error?.stack || '';
                pending.reject(workerError);
            } else {
                pending.resolve(result);
            }
        });
        sharedWorker.addEventListener('error', event => {
            workerPending.forEach(pending => {
                clearTimeout(pending.timer);
                pending.reject(new Error(`Worker dokumen berhenti: ${event.message || 'unknown error'}`));
            });
            workerPending.clear();
            sharedWorker?.terminate();
            sharedWorker = null;
        });
        return sharedWorker;
    }

    function runWorker(action, buffer, fileName, extension, onProgress, preserveBuffer = false) {
        const worker = ensureWorker();
        workerSequence += 1;
        const id = `IMPORT-WORK-${Date.now()}-${workerSequence}`;
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => {
                const timedOut = workerPending.get(id);
                if (!timedOut) return;
                workerPending.delete(id);
                timedOut.reject(new Error(
                    `${fileName} melewati batas waktu parser ${Math.round(MAX_WORKER_TASK_MS / 1000)} detik.`
                ));
                workerPending.forEach(pending => {
                    clearTimeout(pending.timer);
                    pending.reject(new Error('Worker direset setelah tugas lain melewati batas waktu.'));
                });
                workerPending.clear();
                worker.terminate();
                if (sharedWorker === worker) sharedWorker = null;
            }, MAX_WORKER_TASK_MS);
            workerPending.set(id, { resolve, reject, onProgress, timer });
            const transferable = preserveBuffer ? buffer.slice(0) : buffer;
            try {
                worker.postMessage(
                    { id, action, buffer: transferable, fileName, extension },
                    [transferable]
                );
            } catch (error) {
                clearTimeout(timer);
                workerPending.delete(id);
                reject(error);
            }
        });
    }

    class OcrSession {
        constructor(onProgress) {
            this.onProgress = onProgress;
            this.workerPromise = null;
        }

        async getWorker() {
            if (!global.Tesseract?.createWorker) {
                throw new Error('Tesseract OCR tidak termuat.');
            }
            if (!this.workerPromise) {
                const vendorBase = assetUrl('scripts/vendor/').replace(/\/$/, '');
                this.workerPromise = global.Tesseract.createWorker(['ind', 'eng'], 1, {
                    workerPath: assetUrl('scripts/vendor/tesseract-5.1.1.worker.min.js'),
                    corePath: vendorBase,
                    langPath: vendorBase,
                    gzip: true,
                    logger: message => {
                        this.onProgress?.({
                            stage: 'ocr',
                            current: Number(message.progress || 0),
                            total: 1,
                            message: message.status || 'OCR'
                        });
                    }
                }).then(async worker => {
                    await worker.setParameters({
                        preserve_interword_spaces: '1',
                        user_defined_dpi: '300'
                    });
                    return worker;
                });
            }
            return this.workerPromise;
        }

        async recognize(image, sourceRef) {
            const worker = await this.getWorker();
            const result = await worker.recognize(image, {}, {
                text: true,
                tsv: true,
                blocks: true
            });
            return {
                text: normalizeSpace(result?.data?.text || ''),
                confidence: Number(result?.data?.confidence || 0),
                sourceRef,
                words: parseOcrTsv(result?.data?.tsv || '')
            };
        }

        async terminate() {
            if (!this.workerPromise) return;
            try {
                const worker = await this.workerPromise;
                await worker.terminate();
            } catch (error) {
                // OCR cleanup should never hide a successful extraction.
            }
            this.workerPromise = null;
        }
    }

    function parseOcrTsv(tsv) {
        const rows = String(tsv || '').split(/\r?\n/);
        if (rows.length < 2) return [];
        const headers = rows[0].split('\t');
        const indexes = Object.fromEntries(headers.map((header, index) => [header, index]));
        return rows.slice(1).map(row => row.split('\t')).filter(columns => (
            columns[indexes.level] === '5' && columns[indexes.text]?.trim()
        )).map(columns => ({
            text: columns.slice(indexes.text).join('\t').trim(),
            confidence: Number(columns[indexes.conf] || 0),
            left: Number(columns[indexes.left] || 0),
            top: Number(columns[indexes.top] || 0),
            width: Number(columns[indexes.width] || 0),
            height: Number(columns[indexes.height] || 0),
            block: Number(columns[indexes.block_num] || 0),
            paragraph: Number(columns[indexes.par_num] || 0),
            line: Number(columns[indexes.line_num] || 0),
            word: Number(columns[indexes.word_num] || 0)
        }));
    }

    async function extractFile(file, options = {}, onProgress) {
        if (!file || typeof file.arrayBuffer !== 'function') {
            throw new TypeError('Input harus berupa File/Blob browser.');
        }
        if (Number(file.size || 0) > MAX_SOURCE_BYTES) {
            throw new Error(
                `Ukuran file ${Math.ceil(file.size / 1024 / 1024)} MB melewati batas aman `
                + `${MAX_SOURCE_BYTES / 1024 / 1024} MB.`
            );
        }
        const extension = extensionOf(file.name);
        const buffer = await file.arrayBuffer();
        const bytes = new Uint8Array(buffer);
        const signature = detectSignature(bytes);
        const expectedSignatures = signatureExpectedFor(extension);
        const checksum = await sha256(buffer);
        const source = {
            fileName: file.name || 'dokumen-tanpa-nama',
            relativePath: file.webkitRelativePath || file.relativePath || file.name || '',
            extension,
            mimeType: file.type || '',
            size: Number(file.size || buffer.byteLength),
            lastModified: file.lastModified ? new Date(file.lastModified).toISOString() : null,
            sha256: checksum,
            signature
        };
        const requestedPdfScale = Number(options.pdfScale);
        const extractionOptions = {
            ...options,
            ocrMode: ['off', 'auto', 'all'].includes(options.ocrMode) ? options.ocrMode : 'auto',
            pdfScale: Number.isFinite(requestedPdfScale)
                ? clamp(requestedPdfScale, 1.25, 3)
                : 2
        };
        const zipPreflightAudit = signature === 'zip' && expectedSignatures.includes('zip')
            ? global.FleetZipPreflight?.inspect(bytes, source.fileName, {
                maxEntries: MAX_ZIP_ENTRIES,
                maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
                maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
            })
            : null;
        if (signature === 'zip' && expectedSignatures.includes('zip') && !zipPreflightAudit) {
            throw new Error('Modul preflight ZIP tidak termuat; dokumen OOXML tidak diproses.');
        }

        onProgress?.({ stage: 'hash', current: 1, total: 1, message: 'Checksum SHA-256 selesai' });

        let extraction;
        if (expectedSignatures.length && !expectedSignatures.includes(signature)) {
            extraction = createExtraction(extension.replace('.', '') || 'unknown', 'File signature audit adapter');
            addWarning(
                extraction,
                'signature_extension_mismatch',
                `Isi file terdeteksi sebagai ${signature}, tidak sesuai ekstensi ${extension}. Parser tidak dijalankan demi keamanan.`,
                'error',
                source.fileName
            );
            extraction.artifacts.push({
                kind: 'signature-mismatch-source',
                name: source.fileName,
                size: source.size,
                sourceRef: source.relativePath || source.fileName
            });
            extraction.stats.bytes = source.size;
            extraction.stats.signature = signature;
            extraction.stats.supported = false;
            return { source, extraction: finalizeExtraction(extraction) };
        } else if (extension === '.pdf') {
            extraction = await extractPdf(buffer, source, extractionOptions, onProgress);
        } else if (extension === '.docx') {
            extraction = await extractDocx(buffer, source, extractionOptions, onProgress);
        } else if (extension === '.doc') {
            extraction = await runWorker('legacy-doc', buffer, source.fileName, extension, onProgress);
        } else if (SPREADSHEET_EXTENSIONS.has(extension)) {
            const needsOriginalBuffer = extractionOptions.ocrMode === 'all'
                && ['.xlsx', '.xlsm'].includes(extension);
            extraction = await runWorker(
                'spreadsheet',
                buffer,
                source.fileName,
                extension,
                onProgress,
                needsOriginalBuffer
            );
            if (extractionOptions.ocrMode === 'all' && ['.xlsx', '.xlsm'].includes(extension)) {
                await appendSpreadsheetMediaOcr(buffer, extraction, extractionOptions, onProgress);
            }
        } else if (IMAGE_EXTENSIONS.has(extension)) {
            extraction = await extractImage(file, source, extractionOptions, onProgress, bytes);
        } else {
            extraction = extractUnsupported(source);
        }

        if (zipPreflightAudit) {
            extraction.properties.zipPreflight = {
                version: zipPreflightAudit.version,
                entries: zipPreflightAudit.entries,
                uncompressedBytes: zipPreflightAudit.uncompressedBytes,
                centralDirectoryBytes: zipPreflightAudit.centralDirectoryBytes
            };
        }
        extraction = finalizeExtraction(extraction);
        extraction.stats.bytes = source.size;
        extraction.stats.signature = signature;
        extraction.stats.supported = SUPPORTED_EXTENSIONS.has(extension);
        return { source, extraction };
    }

    function extractUnsupported(source) {
        const extraction = createExtraction(source.extension.replace('.', '') || 'unknown', 'File audit adapter');
        const isArchive = AUDITED_UNSUPPORTED_EXTENSIONS.has(source.extension);
        addWarning(
            extraction,
            isArchive ? 'archive_requires_unpack' : 'unsupported_file_type',
            isArchive
                ? `Arsip ${source.extension.toUpperCase()} dicatat tetapi tidak dibuka. Ekstrak arsip lalu impor file di dalamnya.`
                : `Jenis file ${source.extension || '(tanpa ekstensi)'} belum didukung.`,
            'error',
            source.fileName
        );
        extraction.artifacts.push({
            kind: 'unsupported-source',
            name: source.fileName,
            size: source.size,
            sourceRef: source.relativePath || source.fileName
        });
        extraction.stats.supported = false;
        return extraction;
    }

    async function extractDocx(buffer, source, options, onProgress) {
        if (!global.JSZip) throw new Error('JSZip tidak tersedia untuk membaca DOCX.');
        const extraction = createExtraction('docx', 'Fleet OOXML complete-part adapter + JSZip 3.10.1');
        const rawZipAudit = global.FleetZipPreflight.inspect(
            new Uint8Array(buffer),
            source.fileName,
            {
                maxEntries: MAX_ZIP_ENTRIES,
                maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
                maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
            }
        );
        const zip = await global.JSZip.loadAsync(buffer);
        global.FleetZipPreflight.verifyJsZip(zip, rawZipAudit, source.fileName);
        const allEntries = Object.values(zip.files).filter(entry => !entry.dir);
        validateZipEntries(allEntries, source.fileName);
        if (!zip.file('word/document.xml')) {
            throw new Error('Paket ZIP tidak memiliki word/document.xml dan bukan DOCX yang valid.');
        }
        extraction.properties.packageParts = allEntries.map(entry => ({
            name: entry.name,
            size: entry._data?.uncompressedSize || 0
        }));

        const orderedParts = allEntries
            .filter(entry => (
                /^word\/document\.xml$/i.test(entry.name)
                || /^word\/header\d+\.xml$/i.test(entry.name)
                || /^word\/footer\d+\.xml$/i.test(entry.name)
                || /^word\/(footnotes|endnotes|comments)\.xml$/i.test(entry.name)
            ))
            .sort((left, right) => docxPartOrder(left.name) - docxPartOrder(right.name)
                || left.name.localeCompare(right.name));
        let xmlFailureCount = 0;
        let tableCount = 0;
        let paragraphCount = 0;
        let fieldCodeCount = 0;

        for (let partIndex = 0; partIndex < orderedParts.length; partIndex += 1) {
            const entry = orderedParts[partIndex];
            onProgress?.({
                stage: 'docx-part',
                current: partIndex + 1,
                total: orderedParts.length,
                message: `Membaca ${entry.name}`
            });
            const xml = await entry.async('text');
            const documentNode = new DOMParser().parseFromString(xml, 'application/xml');
            if (documentNode.querySelector('parsererror')) {
                xmlFailureCount += 1;
                addWarning(
                    extraction,
                    'docx_xml_part_failed',
                    `Bagian ${entry.name} tidak valid dan tidak dapat diurai.`,
                    'error',
                    `docx:${entry.name}`
                );
                continue;
            }
            const section = {
                id: `DOCX-PART-${partIndex + 1}`,
                title: docxPartTitle(entry.name),
                kind: docxPartKind(entry.name),
                sourceRef: `docx:${entry.name}`,
                fragmentRefs: [],
                meta: { packagePart: entry.name }
            };
            extraction.sections.push(section);
            const container = firstByLocalName(documentNode, 'body') || documentNode.documentElement;
            const blocks = docxContentBlocks(container);
            let localParagraph = 0;
            let localTable = 0;

            blocks.forEach((block, blockIndex) => {
                if (block.localName === 'p') {
                    localParagraph += 1;
                    paragraphCount += 1;
                    const text = openXmlVisibleText(block);
                    const sourceRef = `docx:${entry.name}#p${localParagraph}`;
                    if (text) {
                        const fragmentId = addFragment(
                            extraction,
                            'paragraph',
                            text,
                            sourceRef,
                            { part: entry.name, blockIndex }
                        );
                        if (fragmentId) section.fragmentRefs.push(fragmentId);
                    }
                    const fieldCodes = elementsByLocalName(block, 'instrText')
                        .map(node => normalizeSpace(node.textContent))
                        .filter(Boolean);
                    fieldCodes.forEach((fieldCode, fieldIndex) => {
                        const fragmentId = addFragment(
                            extraction,
                            'field-code',
                            fieldCode,
                            `${sourceRef}:field${fieldIndex + 1}`,
                            { part: entry.name }
                        );
                        if (fragmentId) section.fragmentRefs.push(fragmentId);
                        fieldCodeCount += 1;
                    });
                    const drawingDescriptions = elementsByLocalName(block, 'docPr')
                        .flatMap(node => unique([
                            node.getAttribute('title'),
                            node.getAttribute('descr'),
                            node.getAttribute('name')
                        ]))
                        .filter(Boolean);
                    drawingDescriptions.forEach((description, descriptionIndex) => {
                        const fragmentId = addFragment(
                            extraction,
                            'image-description',
                            description,
                            `${sourceRef}:drawing${descriptionIndex + 1}`,
                            { part: entry.name }
                        );
                        if (fragmentId) section.fragmentRefs.push(fragmentId);
                    });
                } else if (block.localName === 'tbl') {
                    localTable += 1;
                    tableCount += 1;
                    const table = parseDocxTable(
                        extraction,
                        block,
                        entry.name,
                        localTable,
                        tableCount
                    );
                    if (table) {
                        extraction.tables.push(table);
                        section.fragmentRefs.push(
                            ...table.headerFragmentRefs,
                            ...table.rows.flatMap(row => row.fragmentRefs)
                        .filter(Boolean));
                    }
                }
            });
            section.meta.paragraphs = localParagraph;
            section.meta.tables = localTable;
        }

        const relationshipEntries = allEntries.filter(entry => /_rels\/.*\.rels$/i.test(entry.name));
        for (const entry of relationshipEntries) {
            try {
                const xml = await entry.async('text');
                const relationDocument = new DOMParser().parseFromString(xml, 'application/xml');
                const relationships = elementsByLocalName(relationDocument, 'Relationship');
                relationships.forEach(relation => {
                    const target = relation.getAttribute('Target') || '';
                    const type = relation.getAttribute('Type') || '';
                    const targetMode = relation.getAttribute('TargetMode') || '';
                    extraction.artifacts.push({
                        kind: targetMode === 'External' ? 'external-relationship' : 'package-relationship',
                        name: target,
                        size: 0,
                        sourceRef: `docx:${entry.name}`,
                        meta: { type, targetMode }
                    });
                    if (targetMode === 'External' && target) {
                        addFragment(
                            extraction,
                            'hyperlink',
                            target,
                            `docx:${entry.name}#${relation.getAttribute('Id') || target}`,
                            { type }
                        );
                    }
                });
            } catch (error) {
                addWarning(
                    extraction,
                    'docx_relationship_audit_failed',
                    `Relasi ${entry.name} gagal diaudit: ${error.message}`,
                    'warning',
                    `docx:${entry.name}`
                );
            }
        }

        await readDocxProperties(zip, extraction);
        const mediaEntries = allEntries.filter(entry => /^word\/media\//i.test(entry.name));
        const embeddedEntries = allEntries.filter(entry => /^word\/embeddings\//i.test(entry.name));
        mediaEntries.forEach(entry => {
            extraction.artifacts.push({
                kind: 'embedded-media',
                name: entry.name,
                size: entry._data?.uncompressedSize || 0,
                sourceRef: `docx:${entry.name}`
            });
        });
        embeddedEntries.forEach(entry => {
            extraction.artifacts.push({
                kind: 'embedded-object',
                name: entry.name,
                size: entry._data?.uncompressedSize || 0,
                sourceRef: `docx:${entry.name}`
            });
        });

        const visibleCharacterCount = extraction.fragments
            .filter(fragment => (
                ['paragraph', 'cell'].includes(fragment.kind)
                && /^docx:word\/document\.xml/i.test(fragment.sourceRef)
            ))
            .reduce(
                (sum, fragment) => sum + String(fragment.value || '').length,
            0
            );
        const shouldOcrMedia = options.ocrMode === 'all'
            || (options.ocrMode === 'auto' && visibleCharacterCount < 80);
        let ocrMediaCount = 0;
        if (shouldOcrMedia && mediaEntries.length) {
            const ocr = new OcrSession(onProgress);
            try {
                for (let mediaIndex = 0; mediaIndex < mediaEntries.length; mediaIndex += 1) {
                    const entry = mediaEntries[mediaIndex];
                    if (!isOcrImageName(entry.name)) continue;
                    onProgress?.({
                        stage: 'docx-media-ocr',
                        current: mediaIndex + 1,
                        total: mediaEntries.length,
                        message: `OCR media ${entry.name}`
                    });
                    try {
                        const blob = await entry.async('blob');
                        await assertSafeRasterBlob(blob, entry.name);
                        const result = await ocr.recognize(blob, `docx:${entry.name}:ocr`);
                        if (result.text) {
                            addFragment(
                                extraction,
                                'ocr-text',
                                result.text,
                                result.sourceRef,
                                { confidence: result.confidence, mediaName: entry.name }
                            );
                            if (result.confidence < 55) {
                                addWarning(
                                    extraction,
                                    'low_ocr_confidence',
                                    `Confidence OCR ${entry.name} hanya ${Math.round(result.confidence)}%; verifikasi isi gambar.`,
                                    'warning',
                                    result.sourceRef
                                );
                            }
                            ocrMediaCount += 1;
                        }
                    } catch (error) {
                        addWarning(
                            extraction,
                            'docx_media_ocr_failed',
                            `OCR ${entry.name} gagal: ${error.message}`,
                            'warning',
                            `docx:${entry.name}`
                        );
                    }
                }
            } finally {
                await ocr.terminate();
            }
        } else if (mediaEntries.length) {
            addWarning(
                extraction,
                'docx_media_catalogued_not_ocr',
                `${mediaEntries.length} gambar tertanam dicatat. Gunakan mode OCR maksimal bila gambar memuat teks penting.`,
                'info',
                'docx:word/media'
            );
        }
        if (embeddedEntries.length) {
            addWarning(
                extraction,
                'docx_embedded_objects',
                `${embeddedEntries.length} objek tertanam dicatat tetapi membutuhkan aplikasi asal untuk interpretasi penuh.`,
                'warning',
                'docx:word/embeddings'
            );
        }
        if (xmlFailureCount) {
            addWarning(
                extraction,
                'docx_incomplete_xml_coverage',
                `${xmlFailureCount} bagian XML gagal diproses; hasil tidak boleh difinalkan sebelum review.`,
                'error',
                'docx:package'
            );
        }

        extraction.stats.packageParts = allEntries.length;
        extraction.stats.xmlPartsProcessed = orderedParts.length - xmlFailureCount;
        extraction.stats.xmlPartsFailed = xmlFailureCount;
        extraction.stats.paragraphs = paragraphCount;
        extraction.stats.tables = tableCount;
        extraction.stats.fieldCodes = fieldCodeCount;
        extraction.stats.embeddedMedia = mediaEntries.length;
        extraction.stats.embeddedObjects = embeddedEntries.length;
        extraction.stats.ocrMedia = ocrMediaCount;
        return finalizeExtraction(extraction);
    }

    function validateZipEntries(entries, fileName) {
        if (entries.length > MAX_ZIP_ENTRIES) {
            throw new Error(`${fileName} memiliki ${entries.length} entry; batas aman ${MAX_ZIP_ENTRIES}.`);
        }
        const oversizedEntry = entries.find(
            entry => Number(entry._data?.uncompressedSize || 0) > MAX_ZIP_ENTRY_BYTES
        );
        if (oversizedEntry) {
            throw new Error(
                `${fileName} memiliki entry ${oversizedEntry.name} sebesar `
                + `${(Number(oversizedEntry._data?.uncompressedSize || 0) / 1024 / 1024).toFixed(1)} MB; `
                + `batas aman per entry ${MAX_ZIP_ENTRY_BYTES / 1024 / 1024} MB.`
            );
        }
        const uncompressedBytes = entries.reduce(
            (sum, entry) => sum + Number(entry._data?.uncompressedSize || 0),
            0
        );
        if (uncompressedBytes > MAX_ZIP_UNCOMPRESSED_BYTES) {
            throw new Error(
                `${fileName} mengembang menjadi ${(uncompressedBytes / 1024 / 1024).toFixed(1)} MB; `
                + `batas aman ${MAX_ZIP_UNCOMPRESSED_BYTES / 1024 / 1024} MB.`
            );
        }
        return uncompressedBytes;
    }

    function docxPartOrder(name) {
        if (/word\/document\.xml$/i.test(name)) return 0;
        if (/word\/header/i.test(name)) return 10;
        if (/word\/footer/i.test(name)) return 20;
        if (/word\/footnotes/i.test(name)) return 30;
        if (/word\/endnotes/i.test(name)) return 40;
        if (/word\/comments/i.test(name)) return 50;
        return 100;
    }

    function docxPartTitle(name) {
        if (/document\.xml$/i.test(name)) return 'Isi utama dokumen';
        if (/header/i.test(name)) return `Header — ${name.split('/').pop()}`;
        if (/footer/i.test(name)) return `Footer — ${name.split('/').pop()}`;
        if (/footnotes/i.test(name)) return 'Catatan kaki';
        if (/endnotes/i.test(name)) return 'Catatan akhir';
        if (/comments/i.test(name)) return 'Komentar/review';
        return name;
    }

    function docxPartKind(name) {
        if (/document\.xml$/i.test(name)) return 'document-body';
        if (/header/i.test(name)) return 'header';
        if (/footer/i.test(name)) return 'footer';
        if (/footnotes/i.test(name)) return 'footnotes';
        if (/endnotes/i.test(name)) return 'endnotes';
        if (/comments/i.test(name)) return 'comments';
        return 'xml-part';
    }

    function firstByLocalName(root, localName) {
        return elementsByLocalName(root, localName)[0] || null;
    }

    function elementsByLocalName(root, localName) {
        if (!root?.getElementsByTagNameNS) return [];
        return [...root.getElementsByTagNameNS('*', localName)];
    }

    function directElementChildren(node) {
        return [...(node?.childNodes || [])].filter(child => child.nodeType === 1);
    }

    function docxContentBlocks(container) {
        const blocks = [];
        function visit(node) {
            directElementChildren(node).forEach(child => {
                if (child.localName === 'p' || child.localName === 'tbl') {
                    blocks.push(child);
                    return;
                }
                visit(child);
            });
        }
        visit(container);
        return blocks;
    }

    function attributeByLocalName(node, localName) {
        return [...(node?.attributes || [])].find(attribute => attribute.localName === localName)?.value || '';
    }

    function openXmlVisibleText(node) {
        const parts = [];
        function walk(current) {
            if (current.nodeType === 3) return;
            if (current.nodeType !== 1) return;
            switch (current.localName) {
            case 't':
                parts.push(current.textContent || '');
                return;
            case 'delText':
                parts.push(` [TEKS DIHAPUS: ${current.textContent || ''}] `);
                return;
            case 'tab':
                parts.push('\t');
                return;
            case 'br':
            case 'cr':
                parts.push('\n');
                return;
            case 'noBreakHyphen':
                parts.push('‑');
                return;
            case 'softHyphen':
                parts.push('\u00AD');
                return;
            case 'sym': {
                const hexadecimal = attributeByLocalName(current, 'char');
                const codePoint = Number.parseInt(hexadecimal, 16);
                parts.push(
                    Number.isFinite(codePoint) && codePoint >= 0 && codePoint <= 0x10ffff
                        ? String.fromCodePoint(codePoint)
                        : `[SYMBOL ${hexadecimal || '?'}]`
                );
                return;
            }
            case 'checkBox': {
                const checked = elementsByLocalName(current, 'checked')[0];
                const value = attributeByLocalName(checked, 'val').toLowerCase();
                parts.push(checked && !['0', 'false', 'off'].includes(value) ? '☒' : '☐');
                return;
            }
            case 'instrText':
                return;
            default:
                [...current.childNodes].forEach(walk);
            }
        }
        walk(node);
        return normalizeSpace(parts.join(''));
    }

    function parseDocxTable(extraction, tableNode, partName, localTableIndex, globalTableIndex) {
        const rowNodes = directElementChildren(tableNode).filter(child => child.localName === 'tr');
        if (!rowNodes.length) return null;
        const rawRows = rowNodes.map((rowNode, rowIndex) => {
            const cells = directElementChildren(rowNode).filter(child => child.localName === 'tc');
            const logicalCells = [];
            cells.forEach((cellNode, physicalColumnIndex) => {
                const value = normalizeSpace(
                    directElementChildren(cellNode)
                        .map(child => child.localName === 'tbl'
                            ? elementsByLocalName(child, 't').map(node => node.textContent).join(' ')
                            : openXmlVisibleText(child))
                        .filter(Boolean)
                        .join('\n')
                );
                const gridSpanNode = elementsByLocalName(cellNode, 'gridSpan')[0];
                const gridSpan = clamp(Number.parseInt(attributeByLocalName(gridSpanNode, 'val') || '1', 10), 1, 256);
                const verticalMergeNode = elementsByLocalName(cellNode, 'vMerge')[0];
                const verticalMerge = verticalMergeNode
                    ? attributeByLocalName(verticalMergeNode, 'val') || 'continue'
                    : '';
                const logicalColumnIndex = logicalCells.length;
                const sourceRef = `docx:${partName}#table${localTableIndex}:r${rowIndex + 1}:c${logicalColumnIndex + 1}`;
                const fragmentId = addFragment(extraction, 'cell', value, sourceRef, {
                    part: partName,
                    table: localTableIndex,
                    row: rowIndex + 1,
                    column: logicalColumnIndex + 1,
                    physicalColumn: physicalColumnIndex + 1,
                    gridSpan,
                    verticalMerge
                });
                logicalCells.push({
                    value,
                    fragmentId,
                    gridSpan,
                    verticalMerge
                });
                for (let spanIndex = 1; spanIndex < gridSpan; spanIndex += 1) {
                    logicalCells.push({
                        value: '',
                        fragmentId: null,
                        mergedFrom: logicalColumnIndex + 1,
                        verticalMerge
                    });
                }
            });
            return logicalCells;
        });
        const maxColumns = Math.max(...rawRows.map(row => row.length), 1);
        const firstRowValues = rawRows[0].map(cell => cell.value);
        const headerLooksUseful = firstRowValues.filter(Boolean).length >= Math.min(2, maxColumns)
            && new Set(firstRowValues.map(normalizeKey).filter(Boolean)).size === firstRowValues.filter(Boolean).length;
        const headers = Array.from({ length: maxColumns }, (_, columnIndex) => (
            headerLooksUseful && firstRowValues[columnIndex]
                ? firstRowValues[columnIndex]
                : `Kolom ${columnIndex + 1}`
        ));
        const dataRows = headerLooksUseful ? rawRows.slice(1) : rawRows;
        return {
            id: `DOCX-TABLE-${globalTableIndex}`,
            title: `${docxPartTitle(partName)} — tabel ${localTableIndex}`,
            sourceRef: `docx:${partName}#table${localTableIndex}`,
            headers,
            headerRowNumber: headerLooksUseful ? 1 : null,
            headerFragmentRefs: headerLooksUseful
                ? Array.from({ length: maxColumns }, (_, index) => rawRows[0][index]?.fragmentId || null)
                : [],
            preamble: [],
            rows: dataRows.map((row, rowIndex) => ({
                rowNumber: rowIndex + (headerLooksUseful ? 2 : 1),
                values: Array.from({ length: maxColumns }, (_, columnIndex) => row[columnIndex]?.value || ''),
                fragmentRefs: Array.from({ length: maxColumns }, (_, columnIndex) => row[columnIndex]?.fragmentId || null)
            })),
            meta: {
                derivedFromCells: true,
                part: partName,
                headerDetected: headerLooksUseful,
                mergedCellsPreserved: rawRows.some(row => row.some(cell => (
                    cell.gridSpan > 1 || cell.verticalMerge
                )))
            }
        };
    }

    async function readDocxProperties(zip, extraction) {
        const propertyFiles = ['docProps/core.xml', 'docProps/app.xml', 'docProps/custom.xml'];
        const properties = {};
        for (const fileName of propertyFiles) {
            const entry = zip.file(fileName);
            if (!entry) continue;
            try {
                const xml = await entry.async('text');
                const documentNode = new DOMParser().parseFromString(xml, 'application/xml');
                const values = {};
                directElementChildren(documentNode.documentElement).forEach(child => {
                    const value = normalizeSpace(child.textContent);
                    if (!value) return;
                    const key = child.getAttribute('name') || child.localName;
                    values[key] = value;
                    addFragment(
                        extraction,
                        'document-property',
                        `${key}: ${value}`,
                        `docx:${fileName}#${key}`,
                        { key, value }
                    );
                });
                properties[fileName] = values;
            } catch (error) {
                addWarning(
                    extraction,
                    'docx_property_audit_failed',
                    `Properti ${fileName} gagal dibaca: ${error.message}`,
                    'warning',
                    `docx:${fileName}`
                );
            }
        }
        extraction.properties.documentProperties = properties;
    }

    function isOcrImageName(name) {
        return /\.(png|jpe?g|webp|bmp|tiff?)$/i.test(name);
    }

    function readRasterDimensions(bytes, fileName = '') {
        const lowerName = String(fileName).toLowerCase();
        if (
            bytes.length >= 24
            && bytes[0] === 0x89
            && bytes[1] === 0x50
            && bytes[2] === 0x4e
            && bytes[3] === 0x47
            && String.fromCharCode(...bytes.subarray(12, 16)) === 'IHDR'
        ) {
            const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
            return {
                width: view.getUint32(16, false),
                height: view.getUint32(20, false),
                format: 'png'
            };
        }
        if (bytes.length >= 4 && bytes[0] === 0xff && bytes[1] === 0xd8) {
            const startOfFrameMarkers = new Set([
                0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7,
                0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf
            ]);
            let offset = 2;
            while (offset + 3 < bytes.length) {
                while (offset < bytes.length && bytes[offset] !== 0xff) offset += 1;
                while (offset < bytes.length && bytes[offset] === 0xff) offset += 1;
                if (offset >= bytes.length) break;
                const marker = bytes[offset];
                offset += 1;
                if (marker === 0xd9 || marker === 0xda) break;
                if (marker === 0x01 || (marker >= 0xd0 && marker <= 0xd7)) continue;
                if (offset + 2 > bytes.length) break;
                const segmentLength = bytes[offset] * 0x100 + bytes[offset + 1];
                if (segmentLength < 2 || offset + segmentLength > bytes.length) break;
                if (startOfFrameMarkers.has(marker) && segmentLength >= 7) {
                    return {
                        width: bytes[offset + 5] * 0x100 + bytes[offset + 6],
                        height: bytes[offset + 3] * 0x100 + bytes[offset + 4],
                        format: 'jpeg'
                    };
                }
                offset += segmentLength;
            }
        }
        const ascii = (offset, length) => (
            offset + length <= bytes.length
                ? String.fromCharCode(...bytes.subarray(offset, offset + length))
                : ''
        );
        if (bytes.length >= 30 && ascii(0, 4) === 'RIFF' && ascii(8, 4) === 'WEBP') {
            const chunk = ascii(12, 4);
            if (chunk === 'VP8X' && bytes.length >= 30) {
                return {
                    width: 1 + bytes[24] + bytes[25] * 0x100 + bytes[26] * 0x10000,
                    height: 1 + bytes[27] + bytes[28] * 0x100 + bytes[29] * 0x10000,
                    format: 'webp'
                };
            }
            if (
                chunk === 'VP8 '
                && bytes.length >= 30
                && bytes[23] === 0x9d
                && bytes[24] === 0x01
                && bytes[25] === 0x2a
            ) {
                return {
                    width: (bytes[26] + bytes[27] * 0x100) & 0x3fff,
                    height: (bytes[28] + bytes[29] * 0x100) & 0x3fff,
                    format: 'webp'
                };
            }
            if (chunk === 'VP8L' && bytes.length >= 25 && bytes[20] === 0x2f) {
                return {
                    width: 1 + bytes[21] + ((bytes[22] & 0x3f) << 8),
                    height: 1 + (bytes[22] >> 6) + (bytes[23] << 2) + ((bytes[24] & 0x0f) << 10),
                    format: 'webp'
                };
            }
        }
        if (/\.(png|jpe?g|webp)$/i.test(lowerName)) return null;
        return null;
    }

    function assertSafeRasterBytes(bytes, fileName) {
        const dimensions = readRasterDimensions(bytes, fileName);
        if (!dimensions || !dimensions.width || !dimensions.height) {
            throw new Error(`Header dimensi raster ${fileName} tidak valid atau tidak didukung.`);
        }
        const pixels = dimensions.width * dimensions.height;
        if (
            dimensions.width > MAX_RASTER_DIMENSION
            || dimensions.height > MAX_RASTER_DIMENSION
            || !Number.isSafeInteger(pixels)
            || pixels > MAX_RASTER_PIXELS
        ) {
            throw new Error(
                `Raster ${fileName} berukuran ${dimensions.width} x ${dimensions.height} piksel; `
                + `batas aman ${MAX_RASTER_DIMENSION} piksel per sisi dan `
                + `${MAX_RASTER_PIXELS.toLocaleString('id-ID')} piksel total.`
            );
        }
        return dimensions;
    }

    async function assertSafeRasterBlob(blob, fileName) {
        return assertSafeRasterBytes(new Uint8Array(await blob.arrayBuffer()), fileName);
    }

    function pdfDeadlineError(label) {
        const error = new Error(`${label} melewati batas waktu aman PDF.`);
        error.code = 'pdf_deadline';
        return error;
    }

    function withPdfDeadline(promise, deadline, label, cancel) {
        const remaining = deadline - Date.now();
        if (remaining <= 0) {
            const error = pdfDeadlineError(label);
            try { cancel?.(error); } catch (unused) {}
            return Promise.reject(error);
        }
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => {
                const error = pdfDeadlineError(label);
                try { cancel?.(error); } catch (unused) {}
                reject(error);
            }, remaining);
            Promise.resolve(promise).then(
                value => {
                    clearTimeout(timer);
                    resolve(value);
                },
                error => {
                    clearTimeout(timer);
                    reject(error);
                }
            );
        });
    }

    async function readPdfTextItems(page, pageNumber, deadline, destroyDocument) {
        if (typeof page.streamTextContent !== 'function') {
            throw new Error('PDF.js tidak menyediakan streaming text layer yang dibutuhkan untuk batas memori.');
        }
        const reader = page.streamTextContent({
            includeMarkedContent: false,
            disableNormalization: false
        }).getReader();
        const items = [];
        let characters = 0;
        let completed = false;
        try {
            while (true) {
                const chunk = await withPdfDeadline(
                    reader.read(),
                    deadline,
                    `Text layer halaman ${pageNumber}`,
                    error => {
                        void reader.cancel(error).catch(() => {});
                        destroyDocument();
                    }
                );
                if (chunk.done) {
                    completed = true;
                    break;
                }
                const chunkItems = Array.isArray(chunk.value?.items) ? chunk.value.items : [];
                const chunkCharacters = chunkItems.reduce(
                    (sum, item) => sum + (typeof item?.str === 'string' ? item.str.length : 0),
                    0
                );
                if (
                    items.length + chunkItems.length > MAX_PDF_TEXT_ITEMS_PER_PAGE
                    || characters + chunkCharacters > MAX_PDF_TEXT_CHARACTERS_PER_PAGE
                ) {
                    const error = new Error(
                        `Text layer halaman ${pageNumber} melewati batas `
                        + `${MAX_PDF_TEXT_ITEMS_PER_PAGE.toLocaleString('id-ID')} item / `
                        + `${MAX_PDF_TEXT_CHARACTERS_PER_PAGE / 1024 / 1024} MiB karakter.`
                    );
                    error.code = 'pdf_text_limit';
                    await reader.cancel(error).catch(() => {});
                    throw error;
                }
                items.push(...chunkItems);
                characters += chunkCharacters;
            }
        } finally {
            if (!completed) {
                await reader.cancel(new Error('Pembacaan text layer dihentikan.')).catch(() => {});
            }
            try { reader.releaseLock(); } catch (unused) {}
        }
        return items;
    }

    async function extractPdf(buffer, source, options, onProgress) {
        if (!global.pdfjsLib) throw new Error('PDF.js tidak tersedia.');
        global.pdfjsLib.GlobalWorkerOptions.workerSrc = assetUrl(
            'scripts/vendor/pdf-3.11.174.worker.min.js'
        );
        const extraction = createExtraction('pdf', 'PDF.js 3.11.174 coordinate adapter + Tesseract OCR');
        const deadline = Date.now() + MAX_PDF_TASK_MS;
        let documentDestroyed = false;
        const loadingTask = global.pdfjsLib.getDocument({
            data: new Uint8Array(buffer),
            useSystemFonts: true,
            isEvalSupported: false,
            verbosity: 0
        });
        const destroyDocument = () => {
            if (documentDestroyed) return;
            documentDestroyed = true;
            void loadingTask.destroy().catch(() => {});
        };
        const pdf = await withPdfDeadline(
            loadingTask.promise,
            deadline,
            'Membuka dokumen PDF',
            destroyDocument
        );
        const pageTotal = pdf.numPages;
        if (pageTotal > MAX_PDF_PAGES) {
            await pdf.destroy().catch(() => {});
            throw new Error(`PDF memiliki ${pageTotal} halaman; batas aman ${MAX_PDF_PAGES} halaman per file.`);
        }
        extraction.stats.pagesExpected = pageTotal;
        let pageFailureCount = 0;
        let nativeTextPages = 0;
        let ocrPages = 0;
        let ocrRequiredPages = 0;
        let ocrFailedPages = 0;
        let ocrEmptyPages = 0;
        const fullyExtractedPages = new Set();
        const ocr = new OcrSession(onProgress);

        try {
            const metadata = await withPdfDeadline(
                pdf.getMetadata(),
                deadline,
                'Metadata PDF',
                destroyDocument
            ).catch(error => {
                if (error?.code === 'pdf_deadline') throw error;
                return null;
            });
            extraction.properties.pdfInfo = serializableObject(metadata?.info || {});
            extraction.properties.pdfMetadata = metadata?.metadata?.getAll?.() || {};
            Object.entries(extraction.properties.pdfInfo).forEach(([key, value]) => {
                if (value == null || value === '') return;
                addFragment(
                    extraction,
                    'document-property',
                    `${key}: ${String(value)}`,
                    `pdf:metadata#${key}`,
                    { key, value: serializableObject(value) }
                );
            });

            extraction.stats.pdfInteractiveAudit = 'skipped-memory-safety';
            addWarning(
                extraction,
                'pdf_interactive_objects_skipped',
                'Outline, anotasi, field interaktif, dan lampiran PDF tidak dimuat karena PDF.js 3.11 dapat '
                + 'mengembangkan isi lampiran saat objek tersebut dibaca. Teks halaman dan OCR tetap diproses; '
                + 'ekspor field/lampiran secara terpisah bila dokumen menggunakannya.',
                'warning',
                'pdf:interactive-objects'
            );

            for (let pageNumber = 1; pageNumber <= pageTotal; pageNumber += 1) {
                let page = null;
                onProgress?.({
                    stage: 'pdf-page',
                    current: pageNumber,
                    total: pdf.numPages,
                    message: `Membaca halaman ${pageNumber} dari ${pdf.numPages}`
                });
                try {
                    page = await withPdfDeadline(
                        pdf.getPage(pageNumber),
                        deadline,
                        `Membuka halaman ${pageNumber}`,
                        destroyDocument
                    );
                    const textItems = await readPdfTextItems(
                        page,
                        pageNumber,
                        deadline,
                        destroyDocument
                    );
                    const lines = pdfItemsToLines(textItems);
                    const nativeText = normalizeSpace(lines.map(line => line.text).join('\n'));
                    const garbledRatio = nativeText
                        ? (nativeText.match(/[\uFFFD□�]/g) || []).length / nativeText.length
                        : 1;
                    const nativeReadable = nativeText.length >= 30 && garbledRatio < 0.08;
                    if (nativeReadable) nativeTextPages += 1;

                    const section = {
                        id: `PDF-PAGE-${pageNumber}`,
                        title: `Halaman ${pageNumber}`,
                        kind: 'pdf-page',
                        sourceRef: `pdf:p${pageNumber}`,
                        fragmentRefs: [],
                        meta: {
                            pageNumber,
                            nativeCharacters: nativeText.length,
                            garbledRatio
                        }
                    };
                    extraction.sections.push(section);
                    lines.forEach((line, lineIndex) => {
                        const fragmentId = addFragment(
                            extraction,
                            'paragraph',
                            line.text,
                            `pdf:p${pageNumber}:line${lineIndex + 1}`,
                            { page: pageNumber, bbox: line.bbox, cells: line.cells }
                        );
                        if (fragmentId) {
                            line.fragmentId = fragmentId;
                            section.fragmentRefs.push(fragmentId);
                        }
                    });
                    pdfLinesToTables(lines, pageNumber).forEach(table => extraction.tables.push(table));
                    section.meta.interactiveObjectAudit = 'skipped-memory-safety';
                    section.meta.imageObjectAudit = 'skipped-memory-safety';
                    const visualOcrRequired = !nativeReadable || nativeText.length < 500;
                    const needsOcr = options.ocrMode === 'all'
                        || (options.ocrMode === 'auto' && visualOcrRequired);
                    if (needsOcr) {
                        try {
                            const ocrResult = await ocrPdfPageTiled(
                                page,
                                pageNumber,
                                ocr,
                                options,
                                onProgress,
                                deadline,
                                destroyDocument
                            );
                            section.meta.ocrTileFailures = ocrResult.tileFailures.length;
                            if (ocrResult.tileFailures.length) {
                                ocrFailedPages += 1;
                                addWarning(
                                    extraction,
                                    'pdf_ocr_tile_failed',
                                    `${ocrResult.tileFailures.length} dari ${ocrResult.tiles} tile OCR halaman ${pageNumber} gagal; hasil tile lain tetap dipertahankan.`,
                                    'error',
                                    `pdf:p${pageNumber}`
                                );
                            }
                            if (ocrResult.lines.length) {
                                ocrResult.lines.forEach((line, lineIndex) => {
                                    const fragmentId = addFragment(
                                        extraction,
                                        'ocr-text',
                                        line.text,
                                        line.sourceRef || `pdf:p${pageNumber}:ocr-line${lineIndex + 1}`,
                                        {
                                            page: pageNumber,
                                            confidence: line.confidence,
                                            nativeTextCharacters: nativeText.length,
                                            bbox: line.bbox,
                                            cells: line.cells,
                                            tile: line.tile
                                        }
                                    );
                                    if (fragmentId) {
                                        line.fragmentId = fragmentId;
                                        section.fragmentRefs.push(fragmentId);
                                    }
                                });
                                pdfLinesToTables(ocrResult.lines, pageNumber, 'OCR')
                                    .forEach(table => extraction.tables.push(table));
                                section.meta.ocrTiles = ocrResult.tiles;
                                section.meta.ocrScale = ocrResult.scale;
                                section.meta.ocrCharacters = ocrResult.lines.reduce(
                                    (sum, line) => sum + line.text.length,
                                    0
                                );
                                const ocrConfidence = ocrResult.lines.reduce(
                                    (sum, line) => sum + Number(line.confidence || 0),
                                    0
                                ) / ocrResult.lines.length;
                                section.meta.ocrConfidence = ocrConfidence;
                                if (ocrConfidence < 55) {
                                    addWarning(
                                        extraction,
                                        'low_ocr_confidence',
                                        `Confidence OCR halaman ${pageNumber} hanya ${Math.round(ocrConfidence)}%; verifikasi nilai penting dan tabel.`,
                                        'warning',
                                        `pdf:p${pageNumber}`
                                    );
                                }
                                ocrPages += 1;
                                if (!ocrResult.tileFailures.length) fullyExtractedPages.add(pageNumber);
                            } else {
                                ocrEmptyPages += 1;
                                addWarning(
                                    extraction,
                                    'pdf_ocr_empty',
                                    `OCR halaman ${pageNumber} tidak menemukan teks.`,
                                    visualOcrRequired ? 'error' : 'warning',
                                    `pdf:p${pageNumber}`
                                );
                                if (!visualOcrRequired && nativeReadable) fullyExtractedPages.add(pageNumber);
                            }
                        } catch (error) {
                            if (error?.code === 'pdf_deadline') throw error;
                            ocrFailedPages += 1;
                            addWarning(
                                extraction,
                                'pdf_ocr_failed',
                                `OCR halaman ${pageNumber} gagal: ${error.message}`,
                                'error',
                                `pdf:p${pageNumber}`
                            );
                            if (!visualOcrRequired && nativeReadable) fullyExtractedPages.add(pageNumber);
                        }
                    } else if (options.ocrMode === 'off' && visualOcrRequired) {
                        ocrRequiredPages += 1;
                        addWarning(
                            extraction,
                            'pdf_ocr_required',
                            `Halaman ${pageNumber} tidak memiliki text layer yang memadai; aktifkan OCR.`,
                            'error',
                            `pdf:p${pageNumber}`
                        );
                    } else if (nativeReadable) {
                        fullyExtractedPages.add(pageNumber);
                    }
                } catch (error) {
                    if (error?.code === 'pdf_deadline') throw error;
                    pageFailureCount += 1;
                    addWarning(
                        extraction,
                        'pdf_page_failed',
                        `Halaman ${pageNumber} gagal diproses: ${error.message}`,
                        'error',
                        `pdf:p${pageNumber}`
                    );
                } finally {
                    page?.cleanup?.();
                }
            }
        } finally {
            await ocr.terminate();
            await pdf.cleanup().catch(() => {});
            await pdf.destroy().catch(() => {});
        }

        extraction.stats.pagesProcessed = pageTotal - pageFailureCount;
        extraction.stats.pagesFailed = pageFailureCount;
        extraction.stats.pagesFullyExtracted = fullyExtractedPages.size;
        extraction.stats.nativeTextPages = nativeTextPages;
        extraction.stats.ocrPages = ocrPages;
        extraction.stats.ocrRequiredPages = ocrRequiredPages;
        extraction.stats.ocrFailedPages = ocrFailedPages;
        extraction.stats.ocrEmptyPages = ocrEmptyPages;
        extraction.stats.imageObjects = null;
        extraction.stats.annotations = null;
        extraction.stats.formFields = null;
        extraction.stats.interactiveObjectAudit = 'skipped-memory-safety';
        extraction.stats.imageObjectAudit = 'skipped-memory-safety';
        if (pageFailureCount) {
            addWarning(
                extraction,
                'pdf_incomplete_page_coverage',
                `${pageFailureCount} dari ${pageTotal} halaman gagal diproses.`,
                'error',
                'pdf'
            );
        }
        return finalizeExtraction(extraction);
    }

    function serializableObject(value) {
        if (value instanceof Date) return value.toISOString();
        if (Array.isArray(value)) return value.map(serializableObject);
        if (value && typeof value === 'object') {
            return Object.fromEntries(
                Object.entries(value).map(([key, child]) => [key, serializableObject(child)])
            );
        }
        return value;
    }

    function flattenPdfOutline(items, depth = 0, output = []) {
        items.forEach(item => {
            if (item.title) output.push({ title: item.title, depth });
            if (item.items?.length) flattenPdfOutline(item.items, depth + 1, output);
        });
        return output;
    }

    function pdfItemsToLines(items) {
        const textItems = items
            .map((item, index) => ({
                text: normalizeSpace(item.str || ''),
                x: Number(item.transform?.[4] || 0),
                y: Number(item.transform?.[5] || 0),
                width: Number(item.width || 0),
                height: Math.abs(Number(item.height || item.transform?.[3] || 10)),
                index,
                hasEOL: Boolean(item.hasEOL)
            }))
            .filter(item => item.text);
        const groups = [];
        textItems.forEach(item => {
            let group = groups.find(candidate => Math.abs(candidate.y - item.y) <= Math.max(2, item.height * 0.22));
            if (!group) {
                group = { y: item.y, items: [], firstIndex: item.index };
                groups.push(group);
            }
            group.items.push(item);
            group.firstIndex = Math.min(group.firstIndex, item.index);
        });
        groups.sort((left, right) => right.y - left.y || left.firstIndex - right.firstIndex);
        return groups.map(group => {
            const sorted = group.items.sort((left, right) => left.x - right.x || left.index - right.index);
            const cells = [];
            let current = '';
            let previousEnd = null;
            sorted.forEach(item => {
                const averageCharacter = item.text.length ? item.width / item.text.length : item.height * 0.5;
                const gap = previousEnd == null ? 0 : item.x - previousEnd;
                const splitThreshold = Math.max(14, item.height * 1.35, averageCharacter * 3.2);
                if (current && gap > splitThreshold) {
                    cells.push(normalizeSpace(current));
                    current = item.text;
                } else {
                    current += `${current ? ' ' : ''}${item.text}`;
                }
                previousEnd = Math.max(previousEnd == null ? item.x : previousEnd, item.x + item.width);
            });
            if (current) cells.push(normalizeSpace(current));
            const minimumX = Math.min(...sorted.map(item => item.x));
            const maximumX = Math.max(...sorted.map(item => item.x + item.width));
            const height = Math.max(...sorted.map(item => item.height));
            return {
                text: normalizeSpace(cells.join(' | ')),
                cells,
                bbox: [minimumX, group.y - height, maximumX, group.y],
                fragmentId: null
            };
        }).filter(line => line.text);
    }

    function pdfLinesToTables(lines, pageNumber, sourceType = 'NATIVE') {
        const blocks = [];
        let current = [];
        lines.forEach(line => {
            if (line.cells.length >= 2) {
                current.push(line);
            } else if (current.length) {
                blocks.push(current);
                current = [];
            }
        });
        if (current.length) blocks.push(current);
        return blocks.filter(block => block.length >= 2).map((block, blockIndex) => {
            const maxColumns = Math.max(...block.map(line => line.cells.length));
            const headers = Array.from({ length: maxColumns }, (_, index) => (
                block[0].cells[index] || `Kolom ${index + 1}`
            ));
            return {
                id: `PDF-${sourceType}-TABLE-${pageNumber}-${blockIndex + 1}`,
                title: `Halaman ${pageNumber} — tabel ${sourceType === 'OCR' ? 'OCR' : 'text layer'} ${blockIndex + 1}`,
                sourceRef: `pdf:p${pageNumber}:${sourceType.toLowerCase()}-table${blockIndex + 1}`,
                headers,
                headerRowNumber: 1,
                headerFragmentRefs: Array.from({ length: maxColumns }, () => block[0].fragmentId),
                preamble: [],
                rows: block.slice(1).map((line, rowIndex) => ({
                    rowNumber: rowIndex + 2,
                    values: Array.from({ length: maxColumns }, (_, columnIndex) => line.cells[columnIndex] || ''),
                    fragmentRefs: Array.from({ length: maxColumns }, () => line.fragmentId)
                })),
                meta: {
                    derivedFromCoordinates: true,
                    reviewRequired: true,
                    page: pageNumber,
                    sourceType
                }
            };
        });
    }

    async function ocrPdfPageTiled(
        page,
        pageNumber,
        ocr,
        options,
        onProgress,
        deadline,
        destroyDocument
    ) {
        const baseViewport = page.getViewport({ scale: 1 });
        const longestBaseSide = Math.max(baseViewport.width, baseViewport.height);
        const targetLongSide = options.ocrMode === 'all' ? 12000 : 10000;
        const scale = clamp(
            targetLongSide / Math.max(longestBaseSide, 1),
            Math.max(3, Number(options.pdfScale || 2)),
            14
        );
        const viewport = page.getViewport({ scale });
        const tileSize = options.ocrMode === 'all' ? 2600 : 2400;
        const overlap = 120;
        const step = tileSize - overlap;
        const columns = Math.max(1, Math.ceil((viewport.width - overlap) / step));
        const rows = Math.max(1, Math.ceil((viewport.height - overlap) / step));
        const totalTiles = columns * rows;
        if (totalTiles > MAX_OCR_TILES_PER_PAGE) {
            throw new Error(
                `Halaman ${pageNumber} membutuhkan ${totalTiles} tile OCR; `
                + `batas aman ${MAX_OCR_TILES_PER_PAGE}.`
            );
        }
        const lines = [];
        const tileFailures = [];
        let tileNumber = 0;

        for (let row = 0; row < rows; row += 1) {
            for (let column = 0; column < columns; column += 1) {
                tileNumber += 1;
                const left = Math.round(column * step);
                const top = Math.round(row * step);
                const width = Math.max(1, Math.min(tileSize, Math.ceil(viewport.width - left)));
                const height = Math.max(1, Math.min(tileSize, Math.ceil(viewport.height - top)));
                onProgress?.({
                    stage: 'pdf-ocr-tile',
                    current: tileNumber,
                    total: totalTiles,
                    message: `OCR halaman ${pageNumber}, tile ${tileNumber}/${totalTiles}`
                });
                const canvas = document.createElement('canvas');
                try {
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext('2d', { alpha: false, willReadFrequently: true });
                    if (!context) throw new Error('Canvas 2D tidak tersedia.');
                    context.fillStyle = '#fff';
                    context.fillRect(0, 0, width, height);
                    const renderTask = page.render({
                        canvasContext: context,
                        viewport,
                        transform: [1, 0, 0, 1, -left, -top],
                        annotationMode: global.pdfjsLib.AnnotationMode.DISABLE
                    });
                    await withPdfDeadline(
                        renderTask.promise,
                        deadline,
                        `Render PDF halaman ${pageNumber}`,
                        error => {
                            renderTask.cancel();
                            destroyDocument(error);
                        }
                    );
                    const sourceRef = `pdf:p${pageNumber}:ocr@${left},${top},${width},${height}`;
                    const result = await withPdfDeadline(
                        ocr.recognize(canvas, sourceRef),
                        deadline,
                        `OCR PDF halaman ${pageNumber}`,
                        destroyDocument
                    );
                    const tileLines = ocrWordsToLines(result.words, {
                        left,
                        top,
                        width,
                        height,
                        sourceRef,
                        fallbackText: result.text,
                        confidence: result.confidence
                    });
                    lines.push(...tileLines);
                } catch (error) {
                    if (error?.code === 'pdf_deadline') throw error;
                    tileFailures.push({
                        tile: tileNumber,
                        left,
                        top,
                        width,
                        height,
                        error: error.message
                    });
                } finally {
                    canvas.width = 1;
                    canvas.height = 1;
                }
            }
        }
        return {
            lines: deduplicateOcrLines(lines),
            tiles: totalTiles,
            tileFailures,
            scale
        };
    }

    function ocrWordsToLines(words, tile) {
        if (!words?.length) {
            const fallbackLines = String(tile.fallbackText || '')
                .split(/\n/)
                .map(normalizeSpace)
                .filter(Boolean);
            const safeWidth = Math.max(1, Number(tile.width || 0));
            const safeHeight = Math.max(1, Number(tile.height || 0));
            const lineHeight = safeHeight / Math.max(1, fallbackLines.length);
            return fallbackLines.map((text, index) => ({
                text,
                cells: [text],
                bbox: [
                    Number(tile.left || 0),
                    Number(tile.top || 0) + index * lineHeight,
                    Number(tile.left || 0) + safeWidth,
                    Number(tile.top || 0) + Math.min(safeHeight, (index + 1) * lineHeight)
                ],
                confidence: tile.confidence,
                sourceRef: `${tile.sourceRef}:line${index + 1}`,
                tile
            }));
        }
        const groups = new Map();
        words.forEach(word => {
            const key = `${word.block}-${word.paragraph}-${word.line}`;
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(word);
        });
        return [...groups.values()].map((lineWords, lineIndex) => {
            const sorted = lineWords.sort((left, right) => left.left - right.left || left.word - right.word);
            const cells = [];
            let current = '';
            let previousEnd = null;
            sorted.forEach(word => {
                const gap = previousEnd == null ? 0 : word.left - previousEnd;
                const averageCharacter = word.text.length ? word.width / word.text.length : word.height * 0.5;
                const splitThreshold = Math.max(32, word.height * 1.7, averageCharacter * 4);
                if (current && gap > splitThreshold) {
                    cells.push(normalizeSpace(current));
                    current = word.text;
                } else {
                    current += `${current ? ' ' : ''}${word.text}`;
                }
                previousEnd = word.left + word.width;
            });
            if (current) cells.push(normalizeSpace(current));
            const left = Math.min(...sorted.map(word => word.left)) + tile.left;
            const top = Math.min(...sorted.map(word => word.top)) + tile.top;
            const right = Math.max(...sorted.map(word => word.left + word.width)) + tile.left;
            const bottom = Math.max(...sorted.map(word => word.top + word.height)) + tile.top;
            const confidence = sorted.reduce((sum, word) => sum + word.confidence, 0) / sorted.length;
            return {
                text: normalizeSpace(cells.join(' | ')),
                cells,
                bbox: [left, top, right, bottom],
                confidence,
                sourceRef: `${tile.sourceRef}:line${lineIndex + 1}`,
                tile: {
                    left: tile.left,
                    top: tile.top,
                    width: tile.width,
                    height: tile.height
                }
            };
        }).filter(line => line.text);
    }

    function deduplicateOcrLines(lines) {
        const output = [];
        lines
            .sort((left, right) => left.bbox[1] - right.bbox[1] || left.bbox[0] - right.bbox[0])
            .forEach(line => {
                const normalized = normalizeKey(line.text);
                if (!normalized) return;
                const duplicate = output.find(existing => {
                    const sameText = normalizeKey(existing.text) === normalized;
                    const closeY = Math.abs(existing.bbox[1] - line.bbox[1]) < 80;
                    const overlapX = Math.min(existing.bbox[2], line.bbox[2])
                        - Math.max(existing.bbox[0], line.bbox[0]);
                    return sameText && closeY && overlapX > -100;
                });
                if (!duplicate) output.push(line);
            });
        return output;
    }

    async function extractImage(file, source, options, onProgress, bytes) {
        const extraction = createExtraction(
            source.extension.replace('.', '') || 'image',
            'Tesseract OCR image adapter'
        );
        extraction.artifacts.push({
            kind: 'source-image',
            name: source.fileName,
            size: source.size,
            sourceRef: source.relativePath || source.fileName
        });
        const dimensions = assertSafeRasterBytes(bytes, source.fileName);
        extraction.stats.rasterWidth = dimensions.width;
        extraction.stats.rasterHeight = dimensions.height;
        extraction.stats.rasterPixels = dimensions.width * dimensions.height;
        if (options.ocrMode === 'off') {
            addWarning(
                extraction,
                'image_ocr_disabled',
                'File gambar dicatat tetapi teks belum dibaca karena OCR dinonaktifkan.',
                'error',
                source.fileName
            );
            return extraction;
        }

        const ocr = new OcrSession(onProgress);
        try {
            const result = await ocr.recognize(file, `image:${source.fileName}:ocr`);
            if (result.text) {
                const imageWidth = Math.max(dimensions.width, result.words.reduce(
                    (maximum, word) => Math.max(maximum, Number(word.left || 0) + Number(word.width || 0)),
                    1
                ));
                const imageHeight = Math.max(dimensions.height, result.words.reduce(
                    (maximum, word) => Math.max(maximum, Number(word.top || 0) + Number(word.height || 0)),
                    1
                ));
                const lines = ocrWordsToLines(result.words, {
                    left: 0,
                    top: 0,
                    width: imageWidth,
                    height: imageHeight,
                    sourceRef: result.sourceRef,
                    fallbackText: result.text,
                    confidence: result.confidence
                });
                const fragmentRefs = lines.map(line => addFragment(
                    extraction,
                    'ocr-text',
                    line.text,
                    line.sourceRef,
                    {
                        confidence: line.confidence,
                        bbox: line.bbox,
                        cells: line.cells
                    }
                )).filter(Boolean);
                extraction.sections.push({
                    id: 'IMAGE-OCR',
                    title: 'Hasil OCR gambar',
                    kind: 'image-ocr',
                    sourceRef: result.sourceRef,
                    fragmentRefs,
                    meta: {
                        confidence: result.confidence,
                        width: imageWidth,
                        height: imageHeight,
                        lines: lines.length
                    }
                });
                if (result.confidence < 55) {
                    addWarning(
                        extraction,
                        'low_ocr_confidence',
                        `Confidence OCR gambar hanya ${Math.round(result.confidence)}%; verifikasi nomor, jam, dan nama.`,
                        'warning',
                        source.fileName
                    );
                }
                extraction.stats.ocrImages = 1;
            } else {
                addWarning(
                    extraction,
                    'image_ocr_empty',
                    'OCR selesai tetapi tidak menemukan teks pada gambar.',
                    'error',
                    source.fileName
                );
            }
        } catch (error) {
            addWarning(
                extraction,
                'image_ocr_failed',
                `OCR gambar gagal: ${error.message}`,
                'error',
                source.fileName
            );
        } finally {
            await ocr.terminate();
        }
        return finalizeExtraction(extraction);
    }

    async function appendSpreadsheetMediaOcr(buffer, extraction, options, onProgress) {
        if (!global.JSZip) return;
        let zip;
        try {
            const rawZipAudit = global.FleetZipPreflight.inspect(
                new Uint8Array(buffer),
                'Spreadsheet media',
                {
                    maxEntries: MAX_ZIP_ENTRIES,
                    maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
                    maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
                }
            );
            zip = await global.JSZip.loadAsync(buffer);
            global.FleetZipPreflight.verifyJsZip(zip, rawZipAudit, 'Spreadsheet media');
            validateZipEntries(
                Object.values(zip.files).filter(entry => !entry.dir),
                'Spreadsheet media'
            );
        } catch (error) {
            return;
        }
        const mediaEntries = Object.values(zip.files).filter(entry => (
            !entry.dir && /^xl\/media\//i.test(entry.name) && isOcrImageName(entry.name)
        ));
        if (!mediaEntries.length) return;
        const ocr = new OcrSession(onProgress);
        let recognized = 0;
        try {
            for (let index = 0; index < mediaEntries.length; index += 1) {
                const entry = mediaEntries[index];
                onProgress?.({
                    stage: 'spreadsheet-media-ocr',
                    current: index + 1,
                    total: mediaEntries.length,
                    message: `OCR gambar workbook ${entry.name}`
                });
                try {
                    const blob = await entry.async('blob');
                    await assertSafeRasterBlob(blob, entry.name);
                    const result = await ocr.recognize(blob, `xlsx-media:${entry.name}:ocr`);
                    if (!result.text) continue;
                    addFragment(
                        extraction,
                        'ocr-text',
                        result.text,
                        result.sourceRef,
                        { confidence: result.confidence, mediaName: entry.name }
                    );
                    if (result.confidence < 55) {
                        addWarning(
                            extraction,
                            'low_ocr_confidence',
                            `Confidence OCR ${entry.name} hanya ${Math.round(result.confidence)}%; verifikasi isi gambar.`,
                            'warning',
                            result.sourceRef
                        );
                    }
                    recognized += 1;
                } catch (error) {
                    addWarning(
                        extraction,
                        'spreadsheet_media_ocr_failed',
                        `OCR ${entry.name} gagal: ${error.message}`,
                        'warning',
                        `xlsx-media:${entry.name}`
                    );
                }
            }
        } finally {
            await ocr.terminate();
        }
        extraction.stats.ocrMedia = recognized;
        if (recognized) {
            extraction.warnings = extraction.warnings.filter(
                warning => warning.code !== 'spreadsheet_media_catalogued'
            );
        }
        finalizeExtraction(extraction);
    }

    function fieldAliases(item) {
        const keyAliases = FIELD_ALIASES[item.key] || [];
        return unique([
            item.label,
            item.key,
            String(item.key || '').replace(/_/g, ' '),
            ...keyAliases
        ]).map(normalizeKey).filter(Boolean);
    }

    function textSimilarity(leftValue, rightValue) {
        const left = normalizeKey(leftValue);
        const right = normalizeKey(rightValue);
        if (!left || !right) return 0;
        if (left === right) return 1;
        if (left.length >= 3 && right.includes(left)) return Math.min(0.94, 0.72 + left.length / right.length * 0.2);
        if (right.length >= 3 && left.includes(right)) return Math.min(0.92, 0.7 + right.length / left.length * 0.2);
        const leftTokens = new Set(tokens(left));
        const rightTokens = new Set(tokens(right));
        if (!leftTokens.size || !rightTokens.size) return 0;
        const intersection = [...leftTokens].filter(token => rightTokens.has(token)).length;
        const union = new Set([...leftTokens, ...rightTokens]).size;
        const jaccard = union ? intersection / union : 0;
        const prefixBonus = [...leftTokens].some(leftToken => (
            [...rightTokens].some(rightToken => (
                leftToken.length >= 3
                && rightToken.length >= 3
                && (leftToken.startsWith(rightToken) || rightToken.startsWith(leftToken))
            ))
        )) ? 0.12 : 0;
        return clamp(jaccard + prefixBonus);
    }

    function bestAliasScore(value, aliases) {
        return aliases.reduce(
            (maximum, alias) => Math.max(maximum, textSimilarity(value, alias)),
            0
        );
    }

    function classifySchemas(source, extraction, schemas, forcedSchemaId = '') {
        const normalizedFileName = normalizeKey(source.fileName);
        const tableHeaderText = extraction.tables
            .flatMap(table => table.headers || [])
            .slice(0, 2000)
            .join(' ');
        const sampleSize = Math.min(6000, extraction.fragments.length);
        const sampledFragments = Array.from({ length: sampleSize }, (_, index) => (
            extraction.fragments[Math.floor(index * extraction.fragments.length / Math.max(sampleSize, 1))]?.value || ''
        ));
        const sampledText = normalizeKey([
            source.fileName,
            tableHeaderText,
            ...sampledFragments
        ].join(' ').slice(0, 1800000));
        const results = schemas.map(schema => {
            const hints = unique([
                ...(SCHEMA_HINTS[schema.id] || []),
                schema.code,
                schema.title,
                String(schema.source || '').replace(/\.[^.]+$/, '').replace(/_/g, ' ')
            ]).map(normalizeKey).filter(value => value.length >= 2);
            const labels = [...(schema.fields || []), ...(schema.columns || [])]
                .flatMap(fieldAliases);
            const exactFileHints = hints.filter(hint => normalizedFileName.includes(hint));
            const bodyHints = hints.filter(hint => sampledText.includes(hint));
            const labelMatches = labels.filter(label => sampledText.includes(label));
            const fileTitleSimilarity = textSimilarity(normalizedFileName, schema.title);
            const sourceSimilarity = textSimilarity(
                normalizedFileName,
                String(schema.source || '').replace(/\.[^.]+$/, '')
            );
            const labelCoverage = labels.length
                ? unique(labelMatches).length / unique(labels).length
                : 0;
            let score = 0;
            if (exactFileHints.length) {
                score += Math.min(0.58, 0.42 + exactFileHints.length * 0.08);
            }
            if (bodyHints.length) score += Math.min(0.24, bodyHints.length * 0.07);
            score += fileTitleSimilarity * 0.2;
            score += sourceSimilarity * 0.24;
            score += Math.min(0.32, labelCoverage * 0.9);
            return {
                schemaId: schema.id,
                title: schema.title,
                code: schema.code,
                score: clamp(score),
                evidence: {
                    filenameHints: exactFileHints.slice(0, 8),
                    contentHints: bodyHints.slice(0, 8),
                    labelMatches: unique(labelMatches).slice(0, 20),
                    labelCoverage
                }
            };
        }).sort((left, right) => right.score - left.score);

        if (forcedSchemaId === '__none__') {
            return {
                selectedSchemaId: null,
                mode: 'manual',
                confidence: 'manual',
                score: 0,
                margin: 1,
                alternatives: results.slice(0, 5)
            };
        }
        if (forcedSchemaId) {
            const forced = results.find(result => result.schemaId === forcedSchemaId);
            return {
                selectedSchemaId: forced ? forcedSchemaId : null,
                mode: 'manual',
                confidence: forced ? 'manual' : 'none',
                score: forced?.score || 0,
                margin: 1,
                alternatives: results.slice(0, 5)
            };
        }

        const best = results[0];
        const second = results[1];
        const margin = best ? best.score - (second?.score || 0) : 0;
        const accepted = Boolean(best && best.score >= 0.32 && (margin >= 0.035 || best.score >= 0.72));
        const confidence = !accepted
            ? 'none'
            : best.score >= 0.74 && margin >= 0.1
                ? 'high'
                : best.score >= 0.52 && margin >= 0.055
                    ? 'medium'
                    : 'low';
        return {
            selectedSchemaId: accepted ? best.schemaId : null,
            mode: 'automatic',
            confidence,
            score: best?.score || 0,
            margin,
            alternatives: results.slice(0, 5)
        };
    }

    function collectLabelValueCandidates(extraction) {
        const candidates = [];
        let truncated = false;
        function pushCandidate(candidate) {
            if (candidates.length >= MAX_MAPPING_CANDIDATES) {
                truncated = true;
                return false;
            }
            candidates.push(candidate);
            return true;
        }
        extraction.tables.forEach(table => {
            const rows = [
                ...(table.preamble || []),
                ...(table.rows || [])
            ];
            rows.forEach(row => {
                const values = (row.values || []).map(value => normalizeSpace(value));
                const nonEmptyIndexes = values
                    .map((value, index) => ({ value, index }))
                    .filter(item => item.value);
                if (nonEmptyIndexes.length < 2) return;
                for (let offset = 0; offset < nonEmptyIndexes.length - 1; offset += 1) {
                    const labelCell = nonEmptyIndexes[offset];
                    const valueCell = nonEmptyIndexes[offset + 1];
                    if (labelCell.value.length > 120 || valueCell.value.length > 1200) continue;
                    pushCandidate({
                        label: labelCell.value,
                        value: valueCell.value,
                        sourceRefs: unique([
                            row.fragmentRefs?.[labelCell.index],
                            row.fragmentRefs?.[valueCell.index]
                        ]),
                        sourceRef: `${table.sourceRef}:r${row.rowNumber || offset + 1}`,
                        origin: 'table-pair',
                        baseConfidence: 0.88
                    });
                }
            });
        });

        const fragments = extraction.fragments;
        fragments.forEach((fragment, index) => {
            if (!['paragraph', 'form-field', 'annotation', 'ocr-text', 'cell'].includes(fragment.kind)) return;
            const lines = String(fragment.value || '').split(/\n/).map(normalizeSpace).filter(Boolean);
            lines.forEach(line => {
                const separatorMatch = line.match(/^(.{1,120}?)(?:\s*[:=]\s*|\s+\|\s+)(.{1,1200})$/);
                if (separatorMatch) {
                    pushCandidate({
                        label: separatorMatch[1],
                        value: separatorMatch[2],
                        sourceRefs: [fragment.id],
                        sourceRef: fragment.sourceRef,
                        origin: 'labelled-text',
                        baseConfidence: fragment.kind === 'ocr-text' ? 0.68 : 0.84
                    });
                }
            });
            const shortLabel = normalizeSpace(fragment.value);
            const next = fragments[index + 1];
            if (
                shortLabel
                && shortLabel.length <= 80
                && !/[:=]/.test(shortLabel)
                && next
                && normalizeSpace(next.value)
                && normalizeSpace(next.value).length <= 600
                && sourceBoundary(fragment.sourceRef) === sourceBoundary(next.sourceRef)
            ) {
                pushCandidate({
                    label: shortLabel,
                    value: normalizeSpace(next.value),
                    sourceRefs: [fragment.id, next.id],
                    sourceRef: fragment.sourceRef,
                    origin: 'adjacent-fragment',
                    baseConfidence: 0.62
                });
            }
        });
        return { candidates, truncated };
    }

    function sourceBoundary(sourceRef) {
        const value = String(sourceRef || '');
        return value.match(/^pdf:p\d+/i)?.[0]
            || value.match(/^xlsx:[^!]+/i)?.[0]
            || value.match(/^docx:[^#]+/i)?.[0]
            || value.match(/^doc:(?:table-\d+|paragraph-\d+)/i)?.[0]
            || value.match(/^image:[^:]+/i)?.[0]
            || value.split('#')[0]
            || value;
    }

    function normalizeMappedValue(rawValue, item) {
        const raw = normalizeSpace(rawValue);
        if (!raw || /^[-_.\s/]+$/.test(raw) || /_{2,}/.test(raw)) return '';
        if (item.type === 'number') return normalizeNumber(raw);
        if (item.type === 'date') return normalizeDate(raw);
        if (item.type === 'month') {
            const date = normalizeDate(raw);
            if (date) return date.slice(0, 7);
            const match = raw.match(/\b(20\d{2})[-/.](0?[1-9]|1[0-2])\b/);
            return match ? `${match[1]}-${String(match[2]).padStart(2, '0')}` : '';
        }
        if (item.type === 'time') {
            const match = raw.match(/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/);
            return match ? `${String(match[1]).padStart(2, '0')}:${match[2]}` : '';
        }
        if (item.type === 'select' && item.options?.length) {
            const ranked = item.options
                .map(option => ({ option, score: textSimilarity(raw, option) }))
                .sort((left, right) => right.score - left.score);
            return ranked[0]?.score >= 0.48 ? ranked[0].option : '';
        }
        return raw;
    }

    function normalizeNumber(rawValue) {
        let value = String(rawValue).replace(/[^\d,.\-()]/g, '').trim();
        if (!value) return '';
        const negative = /^\(.*\)$/.test(value) || value.startsWith('-');
        value = value.replace(/[()\-]/g, '');
        const lastComma = value.lastIndexOf(',');
        const lastDot = value.lastIndexOf('.');
        let decimalSeparator = '';
        if (lastComma >= 0 && lastDot >= 0) {
            decimalSeparator = lastComma > lastDot ? ',' : '.';
        } else if (lastComma >= 0 && value.length - lastComma - 1 <= 2) {
            decimalSeparator = ',';
        } else if (lastDot >= 0 && value.length - lastDot - 1 <= 2) {
            decimalSeparator = '.';
        }
        if (decimalSeparator) {
            const separatorIndex = value.lastIndexOf(decimalSeparator);
            const integer = value.slice(0, separatorIndex).replace(/[.,]/g, '');
            const decimal = value.slice(separatorIndex + 1).replace(/[.,]/g, '');
            value = `${integer}.${decimal}`;
        } else {
            value = value.replace(/[.,]/g, '');
        }
        const number = Number(value);
        if (!Number.isFinite(number)) return '';
        return String(negative ? -number : number);
    }

    function normalizeDate(rawValue) {
        const raw = normalizeKey(rawValue);
        const iso = raw.match(/\b(20\d{2})[-/.](0?[1-9]|1[0-2])[-/.]([0-2]?\d|3[01])\b/);
        if (iso) return `${iso[1]}-${String(iso[2]).padStart(2, '0')}-${String(iso[3]).padStart(2, '0')}`;
        const dmy = raw.match(/\b([0-2]?\d|3[01])[-/.](0?[1-9]|1[0-2])[-/.](\d{2,4})\b/);
        if (dmy) {
            const year = dmy[3].length === 2 ? `20${dmy[3]}` : dmy[3];
            return `${year}-${String(dmy[2]).padStart(2, '0')}-${String(dmy[1]).padStart(2, '0')}`;
        }
        const monthNames = {
            januari: 1, january: 1, jan: 1,
            februari: 2, february: 2, feb: 2,
            maret: 3, march: 3, mar: 3,
            april: 4, apr: 4,
            mei: 5, may: 5,
            juni: 6, june: 6, jun: 6,
            juli: 7, july: 7, jul: 7,
            agustus: 8, august: 8, agu: 8, aug: 8,
            september: 9, sep: 9,
            oktober: 10, october: 10, okt: 10, oct: 10,
            november: 11, nov: 11,
            desember: 12, december: 12, des: 12, dec: 12
        };
        const named = raw.match(/\b([0-2]?\d|3[01])\s+([a-z]+)\s+(20\d{2})\b/);
        if (named && monthNames[named[2]]) {
            return `${named[3]}-${String(monthNames[named[2]]).padStart(2, '0')}-${String(named[1]).padStart(2, '0')}`;
        }
        const parsed = Date.parse(rawValue);
        if (Number.isFinite(parsed)) return new Date(parsed).toISOString().slice(0, 10);
        return '';
    }

    function mapFields(extraction, schema, usedFragmentIds, conflicts) {
        const candidateResult = collectLabelValueCandidates(extraction);
        const candidates = candidateResult.candidates;
        const fields = {};
        const provenance = {};
        (schema.fields || []).forEach(item => {
            const aliases = fieldAliases(item);
            let selected = null;
            let alternative = null;
            candidates.forEach(candidate => {
                const labelScore = bestAliasScore(candidate.label, aliases);
                if (labelScore < 0.58) return;
                const normalized = normalizeMappedValue(candidate.value, item);
                if (normalized === '') return;
                const score = labelScore * candidate.baseConfidence;
                const result = { candidate, labelScore, normalized, score };
                if (!selected || result.score > selected.score) {
                    alternative = selected;
                    selected = result;
                } else if (!alternative || result.score > alternative.score) {
                    alternative = result;
                }
            });
            if (!selected) return;
            fields[item.key] = selected.normalized;
            provenance[item.key] = {
                rawValue: selected.candidate.value,
                normalizedValue: selected.normalized,
                confidence: clamp(selected.score),
                sourceRef: selected.candidate.sourceRef,
                fragmentIds: selected.candidate.sourceRefs,
                method: selected.candidate.origin
            };
            selected.candidate.sourceRefs.forEach(id => usedFragmentIds.add(id));
            if (
                alternative
                && alternative.normalized !== selected.normalized
                && selected.score - alternative.score < 0.08
            ) {
                conflicts.push({
                    type: 'field',
                    key: item.key,
                    label: item.label,
                    selected: provenance[item.key],
                    alternative: {
                        rawValue: alternative.candidate.value,
                        normalizedValue: alternative.normalized,
                        confidence: clamp(alternative.score),
                        sourceRef: alternative.candidate.sourceRef
                    }
                });
            }
        });
        return { fields, provenance, candidatesTruncated: candidateResult.truncated };
    }

    function headerMapping(headers, columns) {
        const assignments = [];
        const usedColumnKeys = new Set();
        headers.forEach((header, sourceIndex) => {
            const ranked = columns
                .filter(column => !column.readonly && !usedColumnKeys.has(column.key))
                .map(column => ({
                    column,
                    score: bestAliasScore(header, fieldAliases(column))
                }))
                .sort((left, right) => right.score - left.score);
            if (ranked[0]?.score >= 0.5) {
                usedColumnKeys.add(ranked[0].column.key);
                assignments.push({
                    sourceIndex,
                    sourceHeader: header,
                    targetKey: ranked[0].column.key,
                    targetLabel: ranked[0].column.label,
                    score: ranked[0].score
                });
            }
        });
        const ratio = columns.length ? assignments.length / columns.filter(column => !column.readonly).length : 0;
        const average = assignments.length
            ? assignments.reduce((sum, assignment) => sum + assignment.score, 0) / assignments.length
            : 0;
        return {
            assignments,
            score: average * 0.7 + Math.min(1, ratio) * 0.3
        };
    }

    function bestTableMapping(table, schema) {
        const declaredHeaderRow = {
            rowNumber: table.headerRowNumber || 0,
            values: table.headers || [],
            fragmentRefs: table.headerFragmentRefs || []
        };
        const allRows = [
            ...(table.preamble || []),
            declaredHeaderRow,
            ...(table.rows || [])
        ].sort((left, right) => Number(left.rowNumber || 0) - Number(right.rowNumber || 0));
        const variants = [];
        allRows.slice(0, 8).forEach((row, index) => {
            variants.push({
                headers: row.values || [],
                dataRows: allRows.filter((unused, rowIndex) => rowIndex !== index),
                headerSource: `row-${row.rowNumber || index + 1}`,
                headerFragmentRefs: row.fragmentRefs || []
            });
        });
        return variants
            .map(variant => ({
                ...variant,
                mapping: headerMapping(variant.headers, schema.columns || [])
            }))
            .sort((left, right) => right.mapping.score - left.mapping.score)[0];
    }

    function mapRows(extraction, schema, usedFragmentIds) {
        const rows = [];
        const provenance = [];
        const fragmentSourceRefs = new Map(
            extraction.fragments.map(fragment => [fragment.id, fragment.sourceRef])
        );
        let truncated = false;
        extraction.tables.forEach(table => {
            if (rows.length >= MAX_MAPPED_ROWS) {
                truncated = true;
                return;
            }
            const variant = bestTableMapping(table, schema);
            if (!variant?.mapping.assignments.length) return;
            const minimumAssignments = (schema.columns || []).length <= 2 ? 1 : 2;
            if (
                variant.mapping.assignments.length < minimumAssignments
                || variant.mapping.score < 0.48
            ) return;
            variant.dataRows.forEach((sourceRow, sourceRowIndex) => {
                if (rows.length >= MAX_MAPPED_ROWS) {
                    truncated = true;
                    return;
                }
                const mappedRow = {};
                const rowSources = {};
                variant.mapping.assignments.forEach(assignment => {
                    const column = schema.columns.find(item => item.key === assignment.targetKey);
                    const rawValue = sourceRow.values?.[assignment.sourceIndex] ?? '';
                    const normalized = normalizeMappedValue(rawValue, column);
                    if (normalized === '') return;
                    mappedRow[assignment.targetKey] = normalized;
                    const fragmentId = sourceRow.fragmentRefs?.[assignment.sourceIndex] || null;
                    if (fragmentId) usedFragmentIds.add(fragmentId);
                    rowSources[assignment.targetKey] = {
                        rawValue,
                        normalizedValue: normalized,
                        confidence: assignment.score,
                        sourceRef: fragmentId
                            ? fragmentSourceRefs.get(fragmentId)
                            : `${table.sourceRef}:r${sourceRow.rowNumber || sourceRowIndex + 1}`,
                        fragmentId,
                        sourceHeader: assignment.sourceHeader
                    };
                });
                if (!Object.keys(mappedRow).length) return;
                const rowId = global.crypto?.randomUUID?.()
                    || `ROW-${Date.now()}-${rows.length + 1}`;
                mappedRow._import = {
                    rowId,
                    sourceRef: table.sourceRef,
                    confidence: variant.mapping.score
                };
                rows.push(mappedRow);
                provenance.push({
                    rowId,
                    tableId: table.id,
                    sourceRef: table.sourceRef,
                    confidence: variant.mapping.score,
                    fields: rowSources
                });
            });
        });
        return { rows, provenance, truncated };
    }

    function calculateExtractionCoverage(extraction) {
        if (extraction.stats.supported === false) return 0;
        if (extraction.stats.pagesExpected) {
            const completePages = Number.isFinite(Number(extraction.stats.pagesFullyExtracted))
                ? Number(extraction.stats.pagesFullyExtracted)
                : Number(extraction.stats.pagesProcessed || 0);
            return clamp(completePages / extraction.stats.pagesExpected);
        }
        if (extraction.stats.xmlPartsFailed) {
            const total = extraction.stats.xmlPartsProcessed + extraction.stats.xmlPartsFailed;
            return total ? clamp(extraction.stats.xmlPartsProcessed / total) : 0;
        }
        return extraction.warnings.some(warning => (
            warning.severity === 'error'
            && /failed|incomplete|required|mismatch|unsupported|encrypted/.test(warning.code)
        )) ? 0.75 : 1;
    }

    function analyzeImport(extracted, schemas, options = {}) {
        if (!extracted?.source || !extracted?.extraction) {
            throw new TypeError('Hasil ekstraksi tidak valid.');
        }
        const schemaList = Array.isArray(schemas) ? schemas : [];
        const classification = classifySchemas(
            extracted.source,
            extracted.extraction,
            schemaList,
            options.targetSchemaId || ''
        );
        const selectedSchema = schemaList.find(
            schema => schema.id === classification.selectedSchemaId
        ) || null;
        const usedFragmentIds = new Set();
        const conflicts = [];
        let mapping = {
            fields: {},
            fieldProvenance: {},
            rows: [],
            rowProvenance: [],
            conflicts,
            mappedFragmentIds: [],
            unmappedCount: extracted.extraction.fragments.length,
            unmappedPreview: extracted.extraction.fragments.slice(0, 200)
        };

        if (selectedSchema) {
            const fieldResult = mapFields(
                extracted.extraction,
                selectedSchema,
                usedFragmentIds,
                conflicts
            );
            const rowResult = mapRows(
                extracted.extraction,
                selectedSchema,
                usedFragmentIds
            );
            mapping = {
                fields: fieldResult.fields,
                fieldProvenance: fieldResult.provenance,
                rows: rowResult.rows,
                rowProvenance: rowResult.provenance,
                conflicts,
                mappedFragmentIds: [...usedFragmentIds],
                unmappedCount: Math.max(0, extracted.extraction.fragments.length - usedFragmentIds.size),
                unmappedPreview: extracted.extraction.fragments
                    .filter(fragment => !usedFragmentIds.has(fragment.id))
                    .slice(0, 200),
                candidatesTruncated: fieldResult.candidatesTruncated,
                rowsTruncated: rowResult.truncated
            };
        }

        const requiredFields = selectedSchema
            ? (selectedSchema.fields || []).filter(item => item.required)
            : [];
        const filledRequiredFields = requiredFields.filter(item => (
            String(mapping.fields[item.key] ?? '').trim()
        ));
        const extractionCoverage = calculateExtractionCoverage(extracted.extraction);
        const mappingCoverage = extracted.extraction.fragments.length
            ? usedFragmentIds.size / extracted.extraction.fragments.length
            : 0;
        const requiredCoverage = requiredFields.length
            ? filledRequiredFields.length / requiredFields.length
            : selectedSchema ? 1 : 0;
        const errorCount = extracted.extraction.warnings.filter(
            warning => warning.severity === 'error'
        ).length;
        const warningCount = extracted.extraction.warnings.filter(
            warning => warning.severity === 'warning'
        ).length;

        const qualityWarnings = [...extracted.extraction.warnings];
        if (!selectedSchema) {
            qualityWarnings.push({
                code: 'report_template_not_detected',
                message: 'Ekstraksi berhasil, tetapi tidak ada tipe laporan yang cukup cocok. Pilih template secara manual atau kembangkan template baru.',
                severity: 'warning',
                sourceRef: extracted.source.fileName
            });
        } else {
            const missing = requiredFields.filter(item => !String(mapping.fields[item.key] ?? '').trim());
            if (missing.length) {
                qualityWarnings.push({
                    code: 'required_fields_missing',
                    message: `${missing.length} field wajib belum ditemukan otomatis: ${missing.slice(0, 8).map(item => item.label).join(', ')}${missing.length > 8 ? ', …' : ''}.`,
                    severity: 'warning',
                    sourceRef: selectedSchema.id
                });
            }
            if (classification.mode === 'automatic' && classification.confidence === 'low') {
                qualityWarnings.push({
                    code: 'low_template_confidence',
                    message: 'Kecocokan tipe laporan rendah; konfirmasi template sebelum membuat draft.',
                    severity: 'warning',
                    sourceRef: selectedSchema.id
                });
            }
        }
        if (mapping.unmappedCount) {
            qualityWarnings.push({
                code: 'unmapped_fragments_preserved',
                message: `${mapping.unmappedCount} fragmen belum masuk field laporan, tetapi tetap dipertahankan di paket ekstraksi/JSON.`,
                severity: 'info',
                sourceRef: extracted.source.fileName
            });
        }
        if (conflicts.length) {
            qualityWarnings.push({
                code: 'mapping_conflicts',
                message: `${conflicts.length} kandidat pemetaan memiliki nilai alternatif dan perlu review.`,
                severity: 'warning',
                sourceRef: selectedSchema?.id || ''
            });
        }
        if (mapping.candidatesTruncated) {
            qualityWarnings.push({
                code: 'mapping_candidate_limit',
                message: `Pemetaan otomatis dibatasi pada ${MAX_MAPPING_CANDIDATES.toLocaleString('id-ID')} kandidat agar workbook besar tetap responsif. Seluruh fragmen sumber tetap tersimpan untuk pencarian/review.`,
                severity: 'warning',
                sourceRef: extracted.source.fileName
            });
        }
        if (mapping.rowsTruncated) {
            qualityWarnings.push({
                code: 'mapping_row_limit',
                message: `Pemetaan draft dibatasi ${MAX_MAPPED_ROWS.toLocaleString('id-ID')} baris. Baris selebihnya tetap tersimpan di arsip ekstraksi dan envelope database.`,
                severity: 'warning',
                sourceRef: selectedSchema?.id || extracted.source.fileName
            });
        }

        const createdAt = new Date().toISOString();
        const importId = global.crypto?.randomUUID?.()
            || `IMP-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        return {
            schemaVersion: ENVELOPE_VERSION,
            importId,
            createdAt,
            updatedAt: createdAt,
            source: extracted.source,
            extraction: extracted.extraction,
            classification,
            target: selectedSchema
                ? {
                    schemaId: selectedSchema.id,
                    code: selectedSchema.code,
                    title: selectedSchema.title,
                    schemaVersion: 'prototype-1'
                }
                : null,
            mapping,
            quality: {
                extractionCoverage,
                mappingCoverage,
                requiredCoverage,
                sourceFragments: extracted.extraction.fragments.length,
                mappedFragments: usedFragmentIds.size,
                unmappedFragments: mapping.unmappedCount,
                requiredFields: requiredFields.length,
                filledRequiredFields: filledRequiredFields.length,
                errors: errorCount,
                warningCount,
                conflicts: conflicts.length,
                reviewRequired: true,
                canCreateDraft: Boolean(
                    selectedSchema
                    && extracted.extraction.stats.supported !== false
                    && errorCount === 0
                ),
                requiresIncompleteOverride: Boolean(
                    selectedSchema
                    && extracted.extraction.stats.supported !== false
                    && errorCount > 0
                ),
                canFinalizeAutomatically: false,
                warnings: qualityWarnings
            }
        };
    }

    function buildDatabaseEnvelope(record) {
        if (!record) throw new TypeError('Record impor tidak tersedia.');
        return {
            schemaVersion: record.schemaVersion || ENVELOPE_VERSION,
            importJob: {
                id: record.importId,
                status: record.quality?.errors ? 'review_required' : 'extracted',
                createdAt: record.createdAt,
                updatedAt: record.updatedAt,
                parserVersion: ENGINE_VERSION
            },
            sourceDocument: {
                id: `DOC-${record.source.sha256}`,
                fileName: record.source.fileName,
                relativePath: record.source.relativePath,
                mimeType: record.source.mimeType,
                extension: record.source.extension,
                size: record.source.size,
                sha256: record.source.sha256,
                lastModified: record.source.lastModified,
                signature: record.source.signature
            },
            parser: record.extraction.parser,
            extraction: {
                format: record.extraction.format,
                stats: record.extraction.stats,
                properties: record.extraction.properties,
                sections: record.extraction.sections,
                tables: record.extraction.tables,
                fragments: record.extraction.fragments,
                artifacts: record.extraction.artifacts,
                warnings: record.extraction.warnings
            },
            target: record.target,
            classification: record.classification,
            mapping: {
                fields: record.mapping.fields,
                fieldProvenance: record.mapping.fieldProvenance,
                rows: record.mapping.rows,
                rowProvenance: record.mapping.rowProvenance,
                mappedFragmentIds: record.mapping.mappedFragmentIds,
                conflicts: record.mapping.conflicts
            },
            quality: record.quality
        };
    }

    function prepareDraftPayload(record) {
        if (!record?.target) throw new Error('Pilih tipe laporan sebelum membuat draft.');
        return {
            schemaId: record.target.schemaId,
            fields: { ...record.mapping.fields },
            rows: record.mapping.rows.map(row => ({ ...row })),
            importSource: {
                importId: record.importId,
                fileName: record.source.fileName,
                relativePath: record.source.relativePath,
                sha256: record.source.sha256,
                parser: record.extraction.parser,
                targetConfidence: record.classification.confidence,
                extractionCoverage: record.quality.extractionCoverage,
                mappingCoverage: record.quality.mappingCoverage,
                requiredCoverage: record.quality.requiredCoverage,
                mappedFragments: record.quality.mappedFragments,
                unmappedFragments: record.quality.unmappedFragments,
                warningCodes: record.quality.warnings.map(warning => warning.code),
                importedAt: new Date().toISOString()
            }
        };
    }

    global.FleetDocumentImportEngine = Object.freeze({
        version: ENGINE_VERSION,
        envelopeVersion: ENVELOPE_VERSION,
        supportedExtensions: [...SUPPORTED_EXTENSIONS],
        extractFile,
        analyzeImport,
        buildDatabaseEnvelope,
        prepareDraftPayload,
        normalizeKey,
        textSimilarity,
        terminateWorker() {
            workerPending.forEach(pending => {
                clearTimeout(pending.timer);
                pending.reject(new Error('Worker dokumen dihentikan sebelum tugas selesai.'));
            });
            sharedWorker?.terminate();
            sharedWorker = null;
            workerPending.clear();
        }
    });
})(window);
