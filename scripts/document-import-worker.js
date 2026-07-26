'use strict';

importScripts(
    'document-import-zip-preflight.js?v=20260726-2',
    'vendor/xlsx-0.20.3.full.min.js',
    'vendor/jszip-3.10.1.min.js'
);

const WORKER_VERSION = '1.1.1';
const MAX_ZIP_ENTRIES = 10000;
const MAX_ZIP_UNCOMPRESSED_BYTES = 64 * 1024 * 1024;
const MAX_ZIP_ENTRY_BYTES = 32 * 1024 * 1024;
const MAX_WORKBOOK_CELLS = 1100000;
const MAX_RETAINED_WORKBOOK_CELLS = 600000;
const MAX_RETAINED_FRAGMENTS = 600000;
const MAX_FRAGMENT_CHARACTERS = 2 * 1024 * 1024;
const MAX_RETAINED_CHARACTERS = 64 * 1024 * 1024;
const MAX_DERIVED_TABLE_CELLS = 100000;
const MAX_LEGACY_FALLBACK_BYTES = 16 * 1024 * 1024;

self.addEventListener('message', async event => {
    const { id, action, buffer, fileName, extension } = event.data || {};
    if (!id || !buffer) return;

    try {
        let result;
        if (action === 'spreadsheet') {
            result = await extractSpreadsheet(id, buffer, fileName, extension);
        } else if (action === 'legacy-doc') {
            result = extractLegacyDoc(id, buffer, fileName);
        } else {
            throw new Error(`Aksi worker tidak dikenali: ${action || '(kosong)'}`);
        }
        self.postMessage({ id, type: 'result', result });
    } catch (error) {
        self.postMessage({
            id,
            type: 'error',
            error: {
                name: error?.name || 'Error',
                message: error?.message || String(error),
                stack: error?.stack || ''
            }
        });
    }
});

function sendProgress(id, stage, current, total, message) {
    self.postMessage({
        id,
        type: 'progress',
        progress: { stage, current, total, message }
    });
}

function createExtraction(format, parser) {
    return {
        format,
        parser: {
            name: parser,
            version: WORKER_VERSION
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

function addFragment(extraction, kind, value, sourceRef, meta = {}) {
    const text = normalizeCellText(value);
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
    extraction._fragmentSequence += 1;
    const fragment = {
        id: `F${String(extraction._fragmentSequence).padStart(7, '0')}`,
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
    extraction.stats.sections = extraction.sections.length;
    extraction.stats.tables = extraction.tables.length;
    extraction.stats.fragments = extraction.fragments.length;
    extraction.stats.characters = characterCount;
    delete extraction._fragmentSequence;
    delete extraction._characterCount;
    return extraction;
}

function normalizeCellText(value) {
    if (value == null) return '';
    if (value instanceof Date) return value.toISOString();
    if (typeof value === 'object') {
        try {
            return JSON.stringify(value);
        } catch (error) {
            return String(value);
        }
    }
    return String(value).replace(/\u0000/g, '').trim();
}

function extensionOf(fileName) {
    const match = String(fileName || '').toLowerCase().match(/(\.[a-z0-9]+)$/);
    return match ? match[1] : '';
}

function decodeDelimitedText(bytes) {
    if (bytes.length >= 2 && bytes[0] === 0xff && bytes[1] === 0xfe) {
        return {
            text: new TextDecoder('utf-16le').decode(bytes.subarray(2)),
            encoding: 'UTF-16LE'
        };
    }
    if (bytes.length >= 2 && bytes[0] === 0xfe && bytes[1] === 0xff) {
        const swapped = new Uint8Array(bytes.length - 2);
        for (let index = 2; index + 1 < bytes.length; index += 2) {
            swapped[index - 2] = bytes[index + 1];
            swapped[index - 1] = bytes[index];
        }
        return {
            text: new TextDecoder('utf-16le').decode(swapped),
            encoding: 'UTF-16BE'
        };
    }

    try {
        const text = new TextDecoder('utf-8', { fatal: true }).decode(bytes);
        return {
            text: text.replace(/^\uFEFF/, ''),
            encoding: 'UTF-8'
        };
    } catch (error) {
        return {
            text: new TextDecoder('windows-1252').decode(bytes),
            encoding: 'Windows-1252'
        };
    }
}

async function extractSpreadsheet(taskId, buffer, fileName, explicitExtension) {
    const bytes = new Uint8Array(buffer);
    const extension = explicitExtension || extensionOf(fileName);
    const isDelimited = ['.csv', '.tsv', '.txt'].includes(extension);
    const extraction = createExtraction(
        isDelimited ? 'csv' : extension.replace('.', '') || 'spreadsheet',
        `SheetJS 0.20.3 + Fleet structured adapter`
    );

    sendProgress(taskId, 'read', 0, 1, `Membaca ${fileName}`);
    let packageAudit = null;
    if (!isDelimited && ['.xlsx', '.xlsm', '.xltx', '.xltm'].includes(extension)) {
        packageAudit = await validateZipPackage(bytes, fileName);
        extraction.properties.packageEntries = packageAudit.entries.length;
        extraction.properties.packageUncompressedBytes = packageAudit.uncompressedBytes;
        extraction.properties.centralDirectoryEntries = packageAudit.centralDirectoryEntries;
        extraction.properties.packagePhysicalCells = await countOpenXmlWorksheetCells(
            packageAudit.entries,
            fileName
        );
    }
    let workbook;
    if (isDelimited) {
        const decoded = decodeDelimitedText(bytes);
        extraction.properties.encoding = decoded.encoding;
        workbook = XLSX.read(decoded.text, {
            type: 'string',
            dense: true,
            raw: true,
            cellDates: false,
            cellFormula: true,
            cellNF: true,
            cellStyles: true
        });
    } else {
        workbook = XLSX.read(bytes, {
            type: 'array',
            dense: true,
            cellDates: true,
            cellFormula: true,
            cellNF: true,
            cellText: true,
            cellStyles: true,
            bookVBA: true
        });
    }
    const retainedWorkbookCells = validateWorkbookCellLimit(workbook, fileName);
    extraction.properties.retainedWorkbookCells = retainedWorkbookCells;

    extraction.properties.workbook = {
        sheetNames: [...workbook.SheetNames],
        props: serializableValue(workbook.Props || {}),
        customProps: serializableValue(workbook.Custprops || {}),
        definedNames: serializableValue(workbook.Workbook?.Names || [])
    };

    const workbookSheets = workbook.Workbook?.Sheets || [];
    let formulaCount = 0;
    let errorCount = 0;
    let commentCount = 0;
    let hyperlinkCount = 0;
    let nonEmptyCellCount = 0;
    let hiddenSheetCount = 0;
    let hiddenRowCount = 0;
    let hiddenColumnCount = 0;

    for (let sheetIndex = 0; sheetIndex < workbook.SheetNames.length; sheetIndex += 1) {
        const sheetName = workbook.SheetNames[sheetIndex];
        const sheet = workbook.Sheets[sheetName];
        const sheetInfo = workbookSheets[sheetIndex] || {};
        const visibility = Number(sheetInfo.Hidden || 0);
        if (visibility) hiddenSheetCount += 1;

        sendProgress(
            taskId,
            'sheet',
            sheetIndex + 1,
            workbook.SheetNames.length,
            `Mengekstrak sheet ${sheetName}`
        );

        const range = safeDecodeRange(sheet?.['!ref']);
        const rowMap = new Map();
        const section = {
            id: `SHEET-${sheetIndex + 1}`,
            title: sheetName,
            kind: 'sheet',
            sourceRef: `xlsx:${sheetName}`,
            meta: {
                index: sheetIndex,
                visibility: visibility === 2 ? 'very-hidden' : visibility === 1 ? 'hidden' : 'visible',
                range: sheet?.['!ref'] || '',
                merges: (sheet?.['!merges'] || []).map(merge => XLSX.utils.encode_range(merge))
            }
        };
        extraction.sections.push(section);

        const hiddenRows = [];
        (sheet?.['!rows'] || []).forEach((rowInfo, rowIndex) => {
            if (rowInfo?.hidden) {
                hiddenRows.push(rowIndex + 1);
                hiddenRowCount += 1;
            }
        });
        const hiddenColumns = [];
        (sheet?.['!cols'] || []).forEach((columnInfo, columnIndex) => {
            if (columnInfo?.hidden) {
                hiddenColumns.push(XLSX.utils.encode_col(columnIndex));
                hiddenColumnCount += 1;
            }
        });
        section.meta.hiddenRows = hiddenRows;
        section.meta.hiddenColumns = hiddenColumns;

        forEachPresentCell(sheet, range, (cell, rowIndex, columnIndex) => {
            if (!cell) return;
            const formula = cell.f == null ? '' : String(cell.f);
            const comments = Array.isArray(cell.c)
                ? cell.c.map(comment => ({
                    author: comment.a || '',
                    text: comment.t || '',
                    hidden: Boolean(comment.hidden)
                }))
                : [];
            const hyperlink = cell.l
                ? {
                    target: cell.l.Target || '',
                    tooltip: cell.l.Tooltip || ''
                }
                : null;
            const hasValue = cell.v !== undefined && cell.v !== null && cell.v !== '';
            if (!hasValue && !formula && !comments.length && !hyperlink) return;

            const address = XLSX.utils.encode_cell({ r: rowIndex, c: columnIndex });
            const formatted = formattedCellValue(cell);
            const rawValue = serializableValue(cell.v);
            const value = formatted || normalizeCellText(rawValue) || (formula ? `=${formula}` : '');
            const fragmentId = addFragment(
                extraction,
                'cell',
                value,
                `xlsx:${sheetName}!${address}`,
                {
                    sheet: sheetName,
                    address,
                    row: rowIndex + 1,
                    column: XLSX.utils.encode_col(columnIndex),
                    rawValue,
                    formattedValue: formatted,
                    formula,
                    type: cell.t || '',
                    numberFormat: cell.z || '',
                    comments,
                    hyperlink
                }
            );

            if (!rowMap.has(rowIndex)) rowMap.set(rowIndex, new Map());
            rowMap.get(rowIndex).set(columnIndex, {
                value,
                fragmentId,
                address,
                formula,
                rawValue,
                formattedValue: formatted
            });

            nonEmptyCellCount += 1;
            if (formula) formulaCount += 1;
            if (cell.t === 'e') errorCount += 1;
            commentCount += comments.length;
            if (hyperlink) hyperlinkCount += 1;
        });

        const sheetCellCount = [...rowMap.values()].reduce(
            (sum, cells) => sum + cells.size,
            0
        );
        let blocks = [];
        if (sheetCellCount <= MAX_DERIVED_TABLE_CELLS) {
            blocks = buildRowBlocks(rowMap);
            blocks.forEach((block, blockIndex) => {
                const table = createTableFromBlock(sheetName, block, blockIndex);
                if (table) extraction.tables.push(table);
            });
        } else {
            addWarning(
                extraction,
                'large_sheet_table_derivation_limited',
                `Sheet ${sheetName} memiliki ${sheetCellCount.toLocaleString('id-ID')} sel berisi data. Seluruh sel dipertahankan, tetapi duplikasi sebagai tabel turunan dilewati agar browser tidak kehabisan memori.`,
                'warning',
                `xlsx:${sheetName}`
            );
        }

        section.meta.nonEmptyCells = sheetCellCount;
        section.meta.tableBlocks = blocks.length;
        section.meta.tableDerivationLimited = sheetCellCount > MAX_DERIVED_TABLE_CELLS;
    }

    if (!isDelimited && ['.xlsx', '.xlsm', '.xltx', '.xltm'].includes(extension)) {
        await catalogueOpenXmlWorkbookArtifacts(bytes, extraction, packageAudit?.zip);
    } else if (extension === '.xls') {
        addWarning(
            extraction,
            'legacy_xls_embedded_objects_review',
            'Nilai, formula, komentar, hyperlink, sheet tersembunyi, dan merge telah diaudit. Gambar/chart OLE pada XLS lama perlu pemeriksaan visual.',
            'warning',
            'workbook'
        );
    }

    if (hiddenSheetCount || hiddenRowCount || hiddenColumnCount) {
        addWarning(
            extraction,
            'hidden_content_preserved',
            `${hiddenSheetCount} sheet, ${hiddenRowCount} baris, dan ${hiddenColumnCount} kolom tersembunyi tetap ikut diaudit.`,
            'info',
            'workbook'
        );
    }
    if (errorCount) {
        addWarning(
            extraction,
            'formula_error_cells',
            `${errorCount} sel berisi error spreadsheet. Nilai error dan formulanya dipertahankan untuk review.`,
            'error',
            'workbook'
        );
    }

    extraction.stats.sheets = workbook.SheetNames.length;
    extraction.stats.hiddenSheets = hiddenSheetCount;
    extraction.stats.hiddenRows = hiddenRowCount;
    extraction.stats.hiddenColumns = hiddenColumnCount;
    extraction.stats.nonEmptyCells = nonEmptyCellCount;
    extraction.stats.formulas = formulaCount;
    extraction.stats.comments = commentCount;
    extraction.stats.hyperlinks = hyperlinkCount;
    extraction.stats.merges = extraction.sections.reduce(
        (sum, section) => sum + (section.meta?.merges?.length || 0),
        0
    );
    sendProgress(taskId, 'complete', 1, 1, `${fileName} selesai diekstrak`);
    return finalizeExtraction(extraction);
}

function safeDecodeRange(reference) {
    if (!reference) return null;
    try {
        return XLSX.utils.decode_range(reference);
    } catch (error) {
        return null;
    }
}

function forEachPresentCell(sheet, range, callback) {
    if (!sheet) return;
    const denseRows = Array.isArray(sheet)
        ? sheet
        : Array.isArray(sheet['!data'])
            ? sheet['!data']
            : null;
    if (denseRows) {
        for (let rowIndex = 0; rowIndex < denseRows.length; rowIndex += 1) {
            const row = denseRows[rowIndex];
            if (!Array.isArray(row)) continue;
            for (let columnIndex = 0; columnIndex < row.length; columnIndex += 1) {
                if (row[columnIndex] !== undefined) callback(row[columnIndex], rowIndex, columnIndex);
            }
        }
        return;
    }

    Object.keys(sheet).forEach(address => {
        if (address.startsWith('!')) return;
        const decoded = XLSX.utils.decode_cell(address);
        if (range && (
            decoded.r < range.s.r
            || decoded.r > range.e.r
            || decoded.c < range.s.c
            || decoded.c > range.e.c
        )) return;
        callback(sheet[address], decoded.r, decoded.c);
    });
}

function formattedCellValue(cell) {
    try {
        if (cell.w != null) return String(cell.w);
        return XLSX.utils.format_cell(cell);
    } catch (error) {
        return normalizeCellText(cell.v);
    }
}

function serializableValue(value) {
    if (value instanceof Date) return value.toISOString();
    if (Array.isArray(value)) return value.map(serializableValue);
    if (value && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([key, child]) => [key, serializableValue(child)])
        );
    }
    return value;
}

function buildRowBlocks(rowMap) {
    const rowIndexes = [...rowMap.keys()].sort((left, right) => left - right);
    if (!rowIndexes.length) return [];
    const blocks = [];
    let current = [];
    rowIndexes.forEach(rowIndex => {
        if (current.length && rowIndex - current[current.length - 1].rowIndex > 1) {
            blocks.push(current);
            current = [];
        }
        current.push({ rowIndex, cells: rowMap.get(rowIndex) });
    });
    if (current.length) blocks.push(current);
    return blocks;
}

function createTableFromBlock(sheetName, block, blockIndex) {
    if (!block.length) return null;
    const columnIndexes = [...new Set(
        block.flatMap(row => [...row.cells.keys()])
    )].sort((left, right) => left - right);
    if (!columnIndexes.length) return null;

    const headerOffset = detectHeaderOffset(block);
    const headerRow = block[headerOffset];
    const seenHeaders = new Map();
    const headers = columnIndexes.map(columnIndex => {
        const raw = headerRow.cells.get(columnIndex)?.value || `Kolom ${XLSX.utils.encode_col(columnIndex)}`;
        const base = String(raw).trim() || `Kolom ${XLSX.utils.encode_col(columnIndex)}`;
        const occurrence = (seenHeaders.get(base) || 0) + 1;
        seenHeaders.set(base, occurrence);
        return occurrence > 1 ? `${base} (${occurrence})` : base;
    });

    const preamble = block.slice(0, headerOffset).map(row => ({
        rowNumber: row.rowIndex + 1,
        values: columnIndexes.map(columnIndex => row.cells.get(columnIndex)?.value || ''),
        fragmentRefs: columnIndexes.map(columnIndex => row.cells.get(columnIndex)?.fragmentId || null)
    }));
    const dataStart = Math.min(headerOffset + 1, block.length);
    let dataRows = block.slice(dataStart);
    if (!dataRows.length && block.length === 1) dataRows = block;

    return {
        id: `TABLE-${sheetName}-${blockIndex + 1}`,
        title: `${sheetName} — blok ${blockIndex + 1}`,
        sourceRef: `xlsx:${sheetName}!R${block[0].rowIndex + 1}:R${block[block.length - 1].rowIndex + 1}`,
        headers,
        headerRowNumber: headerRow.rowIndex + 1,
        headerFragmentRefs: columnIndexes.map(
            columnIndex => headerRow.cells.get(columnIndex)?.fragmentId || null
        ),
        preamble,
        rows: dataRows.map(row => ({
            rowNumber: row.rowIndex + 1,
            values: columnIndexes.map(columnIndex => row.cells.get(columnIndex)?.value || ''),
            fragmentRefs: columnIndexes.map(
                columnIndex => row.cells.get(columnIndex)?.fragmentId || null
            )
        })),
        meta: {
            derivedFromCells: true,
            columnAddresses: columnIndexes.map(XLSX.utils.encode_col)
        }
    };
}

function detectHeaderOffset(block) {
    const candidateCount = Math.min(12, block.length);
    let bestOffset = 0;
    let bestScore = Number.NEGATIVE_INFINITY;
    for (let offset = 0; offset < candidateCount; offset += 1) {
        const values = [...block[offset].cells.values()].map(cell => cell.value).filter(Boolean);
        if (!values.length) continue;
        const stringCount = values.filter(value => Number.isNaN(Number(String(value).replace(/[.,]/g, '')))).length;
        const uniqueCount = new Set(values.map(value => String(value).toLocaleLowerCase('id'))).size;
        const nextSize = block[offset + 1]?.cells.size || 0;
        const score = values.length * 2
            + stringCount * 1.5
            + uniqueCount
            + Math.min(nextSize, values.length)
            - (values.length === 1 ? 4 : 0);
        if (score > bestScore) {
            bestScore = score;
            bestOffset = offset;
        }
    }
    return bestOffset;
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
    if (diskNumber !== 0 || centralDisk !== 0 || entriesOnDisk !== totalEntries) {
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
    if (centralEnd > eocdOffset || centralEnd > bytes.length) {
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

async function validateZipPackage(bytes, fileName) {
    if (!self.FleetZipPreflight) {
        throw new Error('Modul preflight ZIP tidak termuat di worker.');
    }
    const centralAudit = self.FleetZipPreflight.inspect(bytes, fileName, {
        maxEntries: MAX_ZIP_ENTRIES,
        maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
        maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
    });
    const zip = await JSZip.loadAsync(bytes);
    self.FleetZipPreflight.verifyJsZip(zip, centralAudit, fileName);
    const entries = Object.values(zip.files).filter(entry => !entry.dir);
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
    return {
        zip,
        entries,
        uncompressedBytes,
        centralDirectoryEntries: centralAudit.entries
    };
}

async function countOpenXmlWorksheetCells(entries, fileName) {
    const worksheets = entries.filter(entry => /^xl\/worksheets\/[^/]+\.xml$/i.test(entry.name));
    const cellPattern = /<(?:[A-Za-z_][\w.-]*:)?c(?=[\s/>])/g;
    let count = 0;
    for (const entry of worksheets) {
        const xml = await entry.async('text');
        cellPattern.lastIndex = 0;
        while (cellPattern.exec(xml)) {
            count += 1;
            if (count > MAX_WORKBOOK_CELLS) {
                throw new Error(
                    `${fileName} memiliki lebih dari ${MAX_WORKBOOK_CELLS.toLocaleString('id-ID')} `
                    + 'node sel worksheet; impor dihentikan sebelum workbook dimaterialisasi.'
                );
            }
        }
    }
    return count;
}

function validateWorkbookCellLimit(workbook, fileName) {
    let retainedCells = 0;
    for (const sheetName of workbook.SheetNames || []) {
        const sheet = workbook.Sheets?.[sheetName];
        const range = safeDecodeRange(sheet?.['!ref']);
        forEachPresentCell(sheet, range, cell => {
            const formula = cell?.f == null ? '' : String(cell.f);
            const comments = Array.isArray(cell?.c) ? cell.c : [];
            const hyperlink = cell?.l || null;
            const hasValue = cell?.v !== undefined && cell?.v !== null && cell?.v !== '';
            if (!hasValue && !formula && !comments.length && !hyperlink) return;
            retainedCells += 1;
            if (retainedCells > MAX_RETAINED_WORKBOOK_CELLS) {
                throw new Error(
                    `${fileName} menghasilkan lebih dari ${MAX_RETAINED_WORKBOOK_CELLS.toLocaleString('id-ID')} `
                    + 'sel yang perlu dipertahankan; impor dihentikan untuk melindungi memori browser.'
                );
            }
        });
    }
    return retainedCells;
}

async function catalogueOpenXmlWorkbookArtifacts(bytes, extraction, existingZip) {
    try {
        const zip = existingZip || await JSZip.loadAsync(bytes);
        const entries = Object.values(zip.files).filter(entry => !entry.dir);
        const relevant = entries.filter(entry => (
            /^xl\/(media|charts|drawings|externalLinks|embeddings)\//i.test(entry.name)
            || /^xl\/connections\.xml$/i.test(entry.name)
            || /^xl\/vbaProject\.bin$/i.test(entry.name)
        ));
        let mediaCount = 0;
        let chartCount = 0;
        let externalCount = 0;
        let macroCount = 0;
        relevant.forEach(entry => {
            const normalizedName = entry.name.toLowerCase();
            const kind = normalizedName.includes('/media/')
                ? 'embedded-media'
                : normalizedName.includes('/charts/')
                    ? 'chart'
                    : normalizedName.includes('/externallinks/') || normalizedName.endsWith('/connections.xml')
                        ? 'external-link'
                        : normalizedName.includes('/embeddings/')
                            ? 'embedded-object'
                            : normalizedName.endsWith('/vbaproject.bin')
                                ? 'macro-project'
                                : 'drawing';
            if (kind === 'embedded-media') mediaCount += 1;
            if (kind === 'chart') chartCount += 1;
            if (kind === 'external-link') externalCount += 1;
            if (kind === 'macro-project') macroCount += 1;
            extraction.artifacts.push({
                kind,
                name: entry.name,
                size: entry._data?.uncompressedSize || 0,
                sourceRef: `xlsx-package:${entry.name}`
            });
        });
        extraction.stats.embeddedMedia = mediaCount;
        extraction.stats.charts = chartCount;
        extraction.stats.externalLinks = externalCount;
        extraction.stats.macroProjects = macroCount;
        if (mediaCount) {
            addWarning(
                extraction,
                'spreadsheet_media_catalogued',
                `${mediaCount} media tertanam telah dicatat. Aktifkan OCR maksimal untuk mencoba membaca teks di dalam gambar.`,
                'info',
                'workbook'
            );
        }
        if (chartCount) {
            addWarning(
                extraction,
                'spreadsheet_charts_catalogued',
                `${chartCount} definisi chart dicatat; nilai sumber chart tetap berasal dari sel workbook.`,
                'info',
                'workbook'
            );
        }
        if (externalCount) {
            addWarning(
                extraction,
                'spreadsheet_external_links',
                `${externalCount} referensi/koneksi eksternal ditemukan. Data di luar file tidak dapat diambil otomatis.`,
                'warning',
                'workbook'
            );
        }
        if (macroCount) {
            addWarning(
                extraction,
                'spreadsheet_macro_present',
                `${macroCount} proyek macro/VBA ditemukan dan dicatat tetapi tidak pernah dijalankan.`,
                'warning',
                'workbook'
            );
        }
    } catch (error) {
        addWarning(
            extraction,
            'openxml_package_audit_failed',
            `Audit paket XLSX gagal: ${error.message}`,
            'warning',
            'workbook'
        );
    }
}

function extractLegacyDoc(taskId, buffer, fileName) {
    const bytes = new Uint8Array(buffer);
    const extraction = createExtraction(
        'doc',
        'MS-DOC piece-table reader + SheetJS CFB 0.20.3'
    );
    sendProgress(taskId, 'read', 0, 1, `Membaca struktur biner ${fileName}`);

    let cfb;
    try {
        cfb = XLSX.CFB.read(bytes, { type: 'array' });
    } catch (error) {
        throw new Error(`Kontainer Word biner tidak dapat dibaca: ${error.message}`);
    }

    const streams = (cfb.FullPaths || []).map((path, index) => {
        const entry = cfb.FileIndex?.[index];
        return {
            path,
            size: entry?.size || entry?.content?.length || 0,
            entry
        };
    });
    streams.forEach(stream => {
        if (!stream.entry || stream.entry.type === 1 || stream.entry.type === 5) return;
        extraction.artifacts.push({
            kind: 'cfb-stream',
            name: stream.path,
            size: stream.size,
            sourceRef: `doc-stream:${stream.path}`
        });
    });

    const wordDocument = findCfbStream(streams, 'WordDocument');
    if (!wordDocument) throw new Error('Stream WordDocument tidak ditemukan.');
    const wordBytes = toUint8Array(wordDocument.entry.content);
    if (readUInt16(wordBytes, 0) !== 0xA5EC) {
        throw new Error('Signature Word Binary File tidak valid.');
    }

    const flags = readUInt16(wordBytes, 0x0A);
    const encrypted = Boolean(flags & 0x0100);
    const tableStreamName = flags & 0x0200 ? '1Table' : '0Table';
    const tableStream = findCfbStream(streams, tableStreamName);
    const dataStream = findCfbStream(streams, 'Data');
    if (encrypted) {
        addWarning(
            extraction,
            'legacy_doc_encrypted',
            'Dokumen .doc ditandai terenkripsi/terobfuscasi dan tidak dapat diekstrak tanpa sandi.',
            'error',
            'doc:FIB'
        );
        return finalizeExtraction(extraction);
    }
    if (!tableStream) throw new Error(`Stream ${tableStreamName} tidak ditemukan.`);

    const tableBytes = toUint8Array(tableStream.entry.content);
    const dataBytes = dataStream ? toUint8Array(dataStream.entry.content) : new Uint8Array();
    let decodedText = '';
    let pieceCount = 0;
    try {
        const result = readWordPieceTable(wordBytes, tableBytes);
        decodedText = result.text;
        pieceCount = result.pieceCount;
        extraction._legacyPieceTable = result;
    } catch (error) {
        addWarning(
            extraction,
            'legacy_doc_piece_table_failed',
            `Piece table tidak dapat dibaca penuh (${error.message}); fallback pemindaian seluruh stream digunakan dan hasil wajib diverifikasi.`,
            'error',
            'doc:FIB'
        );
        decodedText = heuristicLegacyDocText(wordBytes);
    }

    const normalized = normalizeWordControls(decodedText);
    const blocks = textToBlocks(normalized);
    let structuralTables = [];
    if (extraction._legacyPieceTable) {
        try {
            structuralTables = readLegacyTableStructures(
                wordBytes,
                tableBytes,
                dataBytes,
                extraction._legacyPieceTable
            );
            if (structuralTables.inferredRowCount) {
                addWarning(
                    extraction,
                    'legacy_doc_ttp_inherited',
                    `${structuralTables.inferredRowCount} baris kosong/berformat warisan dipulihkan dari pasangan cell-mark/TTP dan grid PAPX di sekitarnya.`,
                    'warning',
                    'doc:PAPX'
                );
            }
        } catch (error) {
            addWarning(
                extraction,
                'legacy_doc_table_structure_failed',
                `Properti tabel PAPX tidak dapat dibaca (${error.message}); deteksi berbasis karakter kontrol digunakan.`,
                'warning',
                'doc:PAPX'
            );
        }
    }
    let tableIndex = 0;
    const section = {
        id: 'DOC-STORIES',
        title: 'Seluruh story Word (body, header/footer, catatan)',
        kind: 'legacy-word-stories',
        sourceRef: 'doc:piece-table',
        meta: { pieceCount }
    };
    extraction.sections.push(section);

    const tableBlocks = structuralTables.length
        ? structuralTables.map(table => ({
            kind: 'table',
            rows: table.rows,
            meta: {
                derivedFromControlCharacters: false,
                derivedFromPapx: true,
                rowPropertyRefs: table.rowPropertyRefs,
                gridSignature: table.gridSignature
            }
        }))
        : blocks.filter(block => block.kind === 'table');

    tableBlocks.forEach(block => {
        if (block.kind === 'table') {
            tableIndex += 1;
            const maxColumns = Math.max(...block.rows.map(row => row.length));
            const headers = Array.from(
                { length: maxColumns },
                (_, columnIndex) => `Kolom ${columnIndex + 1}`
            );
            const rows = block.rows.map((row, rowIndex) => {
                const values = Array.from({ length: maxColumns }, (_, columnIndex) => row[columnIndex] || '');
                const fragmentRefs = values.map((value, columnIndex) => addFragment(
                    extraction,
                    'cell',
                    value,
                    `doc:table-${tableIndex}:r${rowIndex + 1}:c${columnIndex + 1}`
                ));
                return { rowNumber: rowIndex + 1, values, fragmentRefs };
            });
            extraction.tables.push({
                id: `DOC-TABLE-${tableIndex}`,
                title: `Tabel terdeteksi ${tableIndex}`,
                sourceRef: `doc:table-${tableIndex}`,
                headers,
                headerRowNumber: null,
                headerFragmentRefs: [],
                preamble: [],
                rows,
                meta: {
                    derivedFromControlCharacters: !block.meta?.derivedFromPapx,
                    derivedFromPapx: Boolean(block.meta?.derivedFromPapx),
                    rowPropertyRefs: block.meta?.rowPropertyRefs || [],
                    gridSignature: block.meta?.gridSignature || '',
                    reviewRequired: true
                }
            });
        }
    });

    blocks.forEach((block, blockIndex) => {
        if (block.kind !== 'table') {
            block.lines.forEach((line, lineIndex) => {
                const fragmentId = addFragment(
                    extraction,
                    'paragraph',
                    line,
                    `doc:paragraph-${blockIndex + 1}-${lineIndex + 1}`
                );
                if (!section.fragmentRefs) section.fragmentRefs = [];
                if (fragmentId) section.fragmentRefs.push(fragmentId);
            });
        } else if (structuralTables.length) {
            // Keep every decoded control-derived line as searchable provenance.
            // PAPX supplies the authoritative table grid, while this parallel
            // fragment prevents text outside an inferred cell boundary from
            // disappearing from the canonical source record.
            block.rows.forEach((row, rowIndex) => {
                const fragmentId = addFragment(
                    extraction,
                    'legacy-table-text',
                    row.join(' | '),
                    `doc:control-block-${blockIndex + 1}-row-${rowIndex + 1}`,
                    { structuralTableGridAvailable: true }
                );
                if (!section.fragmentRefs) section.fragmentRefs = [];
                if (fragmentId) section.fragmentRefs.push(fragmentId);
            });
        }
    });

    const embeddedObjectCount = streams.filter(stream => /objectpool|data|pictures/i.test(stream.path)).length;
    if (embeddedObjectCount) {
        addWarning(
            extraction,
            'legacy_doc_embedded_objects',
            `${embeddedObjectCount} stream objek/gambar tertanam telah dicatat, tetapi isi visualnya memerlukan review atau konversi DOCX.`,
            'warning',
            'doc:cfb'
        );
    }
    addWarning(
        extraction,
        'legacy_doc_review_required',
        'Format .doc lama diekstrak dari piece table resmi Word, namun struktur tabel/objek tidak seandal DOCX. Review manusia atau konversi ke DOCX tetap diwajibkan.',
        'warning',
        'doc'
    );

    extraction.stats.pieces = pieceCount;
    extraction.stats.streams = extraction.artifacts.length;
    extraction.stats.embeddedObjectStreams = embeddedObjectCount;
    extraction.stats.structuralTables = structuralTables.length;
    delete extraction._legacyPieceTable;
    sendProgress(taskId, 'complete', 1, 1, `${fileName} selesai diekstrak dengan review wajib`);
    return finalizeExtraction(extraction);
}

function findCfbStream(streams, name) {
    const normalized = String(name).toLowerCase();
    return streams.find(stream => (
        String(stream.path).replace(/\\/g, '/').toLowerCase().endsWith(`/${normalized}`)
        || String(stream.path).replace(/\\/g, '/').toLowerCase().endsWith(normalized)
    ));
}

function toUint8Array(value) {
    if (value instanceof Uint8Array) return value;
    if (value instanceof ArrayBuffer) return new Uint8Array(value);
    if (Array.isArray(value)) return Uint8Array.from(value);
    if (value?.buffer instanceof ArrayBuffer) {
        return new Uint8Array(value.buffer, value.byteOffset || 0, value.byteLength || value.length);
    }
    return new Uint8Array();
}

function readUInt16(bytes, offset) {
    if (offset < 0 || offset + 2 > bytes.length) throw new Error(`Offset UInt16 tidak valid: ${offset}`);
    return bytes[offset] | (bytes[offset + 1] << 8);
}

function readInt16(bytes, offset) {
    const value = readUInt16(bytes, offset);
    return value & 0x8000 ? value - 0x10000 : value;
}

function readUInt32(bytes, offset) {
    if (offset < 0 || offset + 4 > bytes.length) throw new Error(`Offset UInt32 tidak valid: ${offset}`);
    return (
        bytes[offset]
        + bytes[offset + 1] * 0x100
        + bytes[offset + 2] * 0x10000
        + bytes[offset + 3] * 0x1000000
    ) >>> 0;
}

function readWordPieceTable(wordBytes, tableBytes) {
    const fcClx = readUInt32(wordBytes, 0x01A2);
    const lcbClx = readUInt32(wordBytes, 0x01A6);
    if (!lcbClx || fcClx + lcbClx > tableBytes.length) {
        throw new Error('Pointer CLX berada di luar Table Stream.');
    }

    let offset = fcClx;
    const clxEnd = fcClx + lcbClx;
    while (offset < clxEnd && tableBytes[offset] === 0x01) {
        const groupSize = readUInt16(tableBytes, offset + 1);
        offset += 3 + Math.max(0, groupSize);
    }
    if (offset >= clxEnd || tableBytes[offset] !== 0x02) {
        throw new Error('Record Pcdt (0x02) tidak ditemukan.');
    }

    const plcSize = readUInt32(tableBytes, offset + 1);
    const plcStart = offset + 5;
    if (plcSize < 4 || plcStart + plcSize > tableBytes.length || (plcSize - 4) % 12 !== 0) {
        throw new Error('Ukuran PlcPcd tidak valid.');
    }
    const pieceCount = (plcSize - 4) / 12;
    const cpOffsets = [];
    for (let index = 0; index <= pieceCount; index += 1) {
        cpOffsets.push(readUInt32(tableBytes, plcStart + index * 4));
    }
    const pcdStart = plcStart + (pieceCount + 1) * 4;
    const textParts = [];
    const pieces = [];
    let textOffset = 0;

    for (let index = 0; index < pieceCount; index += 1) {
        const characterCount = cpOffsets[index + 1] - cpOffsets[index];
        if (!characterCount || characterCount > 100000000) continue;
        const fcRaw = readUInt32(tableBytes, pcdStart + index * 8 + 2);
        const compressed = Boolean(fcRaw & 0x40000000);
        const fc = fcRaw & 0x3fffffff;
        const byteOffset = compressed ? Math.floor(fc / 2) : fc;
        const byteLength = characterCount * (compressed ? 1 : 2);
        if (byteOffset < 0 || byteOffset + byteLength > wordBytes.length) {
            throw new Error(`Piece ${index + 1} menunjuk di luar WordDocument Stream.`);
        }
        const slice = wordBytes.subarray(byteOffset, byteOffset + byteLength);
        const decoder = new TextDecoder(compressed ? 'windows-1252' : 'utf-16le');
        const decoded = decoder.decode(slice);
        textParts.push(decoded);
        pieces.push({
            cpStart: cpOffsets[index],
            cpEnd: cpOffsets[index + 1],
            textStart: textOffset,
            textEnd: textOffset + decoded.length,
            fcStart: byteOffset,
            fcEnd: byteOffset + byteLength,
            bytesPerCharacter: compressed ? 1 : 2
        });
        textOffset += decoded.length;
    }
    return { text: textParts.join(''), pieceCount, pieces };
}

function readFibFcLcbPair(wordBytes, pairIndex) {
    let offset = 0x20;
    const csw = readUInt16(wordBytes, offset);
    offset += 2 + csw * 2;
    const cslw = readUInt16(wordBytes, offset);
    offset += 2 + cslw * 4;
    const pairCount = readUInt16(wordBytes, offset);
    const pairsOffset = offset + 2;
    if (pairIndex < 0 || pairIndex >= pairCount) {
        throw new Error(`Pasangan FIB fc/lcb ${pairIndex} tidak tersedia.`);
    }
    const pairOffset = pairsOffset + pairIndex * 8;
    return {
        fc: readUInt32(wordBytes, pairOffset),
        lcb: readUInt32(wordBytes, pairOffset + 4)
    };
}

function textIndexForFileOffset(pieces, fileOffset, preferEnd = false) {
    for (let index = 0; index < pieces.length; index += 1) {
        const piece = pieces[index];
        if (
            fileOffset >= piece.fcStart
            && (fileOffset < piece.fcEnd || (preferEnd && fileOffset === piece.fcEnd))
        ) {
            const byteDelta = Math.max(0, Math.min(
                piece.fcEnd - piece.fcStart,
                fileOffset - piece.fcStart
            ));
            return piece.textStart + Math.floor(byteDelta / piece.bytesPerCharacter);
        }
    }
    return null;
}

function papxGrpprl(fkp, paragraphCount, paragraphIndex) {
    const bxOffset = (paragraphCount + 1) * 4 + paragraphIndex * 13;
    if (bxOffset < 0 || bxOffset >= 511) return new Uint8Array();
    const papxOffset = fkp[bxOffset] * 2;
    if (!papxOffset || papxOffset >= 511) return new Uint8Array();

    const cb = fkp[papxOffset];
    let dataOffset = papxOffset + 1;
    let dataLength;
    if (cb === 0) {
        if (dataOffset >= 511) return new Uint8Array();
        const cbPrime = fkp[dataOffset];
        dataOffset += 1;
        dataLength = cbPrime * 2;
    } else {
        dataLength = cb * 2 - 1;
    }
    if (dataLength < 2 || dataOffset + dataLength > 511) return new Uint8Array();
    // GrpPrlAndIstd starts with the two-byte paragraph style index.
    return fkp.subarray(dataOffset + 2, dataOffset + dataLength);
}

function readPrls(grpprl) {
    const SPRM_TDEF_TABLE = 0xD608;
    const SPRM_P_CHG_TABS = 0xC615;
    const prls = [];
    let offset = 0;
    while (offset + 2 <= grpprl.length) {
        const sprm = readUInt16(grpprl, offset);
        const operandOffset = offset + 2;
        const spra = (sprm >>> 13) & 0x07;
        let operandLength;
        if (spra === 0 || spra === 1) operandLength = 1;
        else if (spra === 2 || spra === 4 || spra === 5) operandLength = 2;
        else if (spra === 3) operandLength = 4;
        else if (spra === 7) operandLength = 3;
        else if (sprm === SPRM_TDEF_TABLE) {
            if (operandOffset + 2 > grpprl.length) break;
            // TDefTable.cb is the byte count of the remainder plus one.
            operandLength = readUInt16(grpprl, operandOffset) + 1;
        } else {
            if (operandOffset >= grpprl.length) break;
            const variableLength = grpprl[operandOffset];
            // PChgTabs with cb=255 uses a computed legacy layout. It is not
            // relevant to table flags; stop safely rather than scanning its
            // operand bytes as false SPRM opcodes.
            if (sprm === SPRM_P_CHG_TABS && variableLength === 0xff) break;
            operandLength = 1 + variableLength;
        }
        if (
            !Number.isInteger(operandLength)
            || operandLength < 0
            || operandOffset + operandLength > grpprl.length
        ) {
            break;
        }
        prls.push({
            sprm,
            operand: grpprl.subarray(operandOffset, operandOffset + operandLength)
        });
        offset = operandOffset + operandLength;
    }
    return prls;
}

function findBoolSprm(grpprl, sprmCode) {
    let value = false;
    readPrls(grpprl).forEach(prl => {
        if (prl.sprm === sprmCode && prl.operand.length) {
            value = Boolean(prl.operand[0] & 0x01);
        }
    });
    return value;
}

function findTableDefinition(grpprl) {
    const SPRM_TDEF_TABLE = 0xD608;
    for (const prl of readPrls(grpprl)) {
        if (prl.sprm !== SPRM_TDEF_TABLE || prl.operand.length < 3) continue;
        const operandLength = readUInt16(prl.operand, 0);
        const columns = prl.operand[2];
        if (!columns || columns > 63 || operandLength < 1) continue;
        const boundaryOffset = 3;
        const availableBoundaries = Math.min(
            columns + 1,
            Math.floor((prl.operand.length - boundaryOffset) / 2)
        );
        const boundaries = [];
        for (let index = 0; index < availableBoundaries; index += 1) {
            boundaries.push(readInt16(prl.operand, boundaryOffset + index * 2));
        }
        return {
            columns,
            signature: `${columns}:${boundaries.join(',')}`
        };
    }
    return null;
}

function referencedPapxGrpprls(grpprl, dataBytes, visited = new Set(), depth = 0) {
    const SPRM_P_HUGE_PAPX = 0x6646;
    const SPRM_P_TABLE_PROPS = 0x646B;
    const groups = [grpprl];
    if (!dataBytes?.length || depth >= 8) return groups;

    for (const prl of readPrls(grpprl)) {
        if (
            (prl.sprm !== SPRM_P_HUGE_PAPX && prl.sprm !== SPRM_P_TABLE_PROPS)
            || prl.operand.length < 4
        ) {
            continue;
        }
        const dataOffset = readUInt32(prl.operand, 0);
        if (visited.has(dataOffset) || dataOffset + 2 > dataBytes.length) continue;
        const groupLength = readUInt16(dataBytes, dataOffset);
        if (
            groupLength < 10
            || groupLength > 0x3fa2
            || dataOffset + 2 + groupLength > dataBytes.length
        ) {
            continue;
        }
        visited.add(dataOffset);
        const referenced = dataBytes.subarray(dataOffset + 2, dataOffset + 2 + groupLength);
        groups.push(...referencedPapxGrpprls(referenced, dataBytes, visited, depth + 1));
        if (prl.sprm === SPRM_P_HUGE_PAPX || prl.sprm === SPRM_P_TABLE_PROPS) break;
    }
    return groups;
}

function cleanLegacyTableCell(value) {
    return String(value || '')
        .replace(/\u0001/g, ' [[OBJEK/GAMBAR]] ')
        .replace(/[\u0013\u0014\u0015]/g, '')
        .replace(/\u000B/g, '\n')
        .replace(/\u000C/g, '\n[PAGE BREAK]\n')
        .replace(/\u000D/g, '\n')
        .replace(/\u0007/g, '')
        .replace(/\u0000/g, '')
        .replace(/[ \u00A0]+\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function rowCellsFromTtpRange(text, rangeStart, ttpIndex, columnCount) {
    const segment = text.slice(Math.max(0, rangeStart), Math.max(rangeStart, ttpIndex + 1));
    const parts = segment.split('\u0007');
    if (parts.length && parts.at(-1) === '') parts.pop();
    // A depth-one row normally ends with a cell mark followed by a separate
    // TTP mark. Remove the empty interval between those two markers, while
    // retaining a legitimately empty final cell.
    if (parts.length > columnCount && parts.at(-1) === '') parts.pop();
    const selected = parts.slice(-columnCount);
    while (selected.length < columnCount) selected.unshift('');
    return selected.map(cleanLegacyTableCell);
}

function readLegacyTableStructures(wordBytes, tableBytes, dataBytes, pieceTable) {
    const PLCF_BTE_PAPX_PAIR_INDEX = 13;
    const SPRM_PF_IN_TABLE = 0x2416;
    const SPRM_PF_TTP = 0x2417;
    const { fc, lcb } = readFibFcLcbPair(wordBytes, PLCF_BTE_PAPX_PAIR_INDEX);
    if (!fc || lcb < 12 || fc + lcb > tableBytes.length || (lcb - 4) % 8 !== 0) {
        throw new Error('PlcBtePapx tidak valid atau berada di luar Table Stream.');
    }

    const pageCount = (lcb - 4) / 8;
    const pageNumberOffset = fc + (pageCount + 1) * 4;
    const propertyRanges = [];
    const rowMarks = [];

    for (let pageIndex = 0; pageIndex < pageCount; pageIndex += 1) {
        const pageFcStart = readUInt32(tableBytes, fc + pageIndex * 4);
        const pageFcEnd = readUInt32(tableBytes, fc + (pageIndex + 1) * 4);
        const pnRaw = readUInt32(tableBytes, pageNumberOffset + pageIndex * 4);
        const fkpOffset = (pnRaw & 0x003fffff) * 512;
        if (fkpOffset < 0 || fkpOffset + 512 > wordBytes.length) continue;
        const fkp = wordBytes.subarray(fkpOffset, fkpOffset + 512);
        const paragraphCount = fkp[511];
        if (!paragraphCount || paragraphCount > 0x1d) continue;

        for (let paragraphIndex = 0; paragraphIndex < paragraphCount; paragraphIndex += 1) {
            const fcStart = readUInt32(fkp, paragraphIndex * 4);
            const fcEnd = readUInt32(fkp, (paragraphIndex + 1) * 4);
            if (fcEnd <= pageFcStart || fcStart >= pageFcEnd) continue;
            const grpprl = papxGrpprl(fkp, paragraphCount, paragraphIndex);
            const grpprls = referencedPapxGrpprls(grpprl, dataBytes);
            const inTable = grpprls.some(group => findBoolSprm(group, SPRM_PF_IN_TABLE));
            const isTtp = grpprls.some(group => findBoolSprm(group, SPRM_PF_TTP));
            const tableDefinition = grpprls
                .map(findTableDefinition)
                .filter(Boolean)
                .at(-1) || null;
            const textStart = textIndexForFileOffset(pieceTable.pieces, fcStart);
            const textEnd = textIndexForFileOffset(pieceTable.pieces, fcEnd, true);
            propertyRanges.push({ fcStart, fcEnd, textStart, textEnd, inTable, isTtp });
            // A producer can store the TTP flag and the row grid in separate
            // PAPX/PrcData records. Keep either normative row signal here;
            // missing grid sizes are inherited only when adjacent row marks
            // agree on the same grid.
            if (!isTtp && !tableDefinition) continue;

            if (textStart == null || textEnd == null) continue;
            const searchStart = Math.max(0, Math.min(textStart, textEnd));
            const searchEnd = Math.min(pieceTable.text.length, Math.max(textStart, textEnd));
            let ttpIndex = searchEnd - 1;
            if (pieceTable.text[ttpIndex] !== '\u0007') {
                const relativeTtp = pieceTable.text.slice(searchStart, searchEnd).lastIndexOf('\u0007');
                if (relativeTtp < 0) continue;
                ttpIndex = searchStart + relativeTtp;
            }
            rowMarks.push({
                fcStart,
                fcEnd,
                ttpIndex,
                columns: tableDefinition?.columns || 0,
                gridSignature: tableDefinition?.signature || '',
                explicitTtp: isTtp,
                sourceRef: `doc:PAPX:${fkpOffset}:${paragraphIndex}`
            });
        }
    }

    rowMarks.sort((left, right) => left.ttpIndex - right.ttpIndex);
    const uniqueRows = rowMarks.filter((row, index) => (
        index === 0 || row.ttpIndex !== rowMarks[index - 1].ttpIndex
    ));
    if (!uniqueRows.length) return [];

    let inferredRowCount = 0;
    uniqueRows.forEach((row, rowIndex) => {
        if (row.columns) return;
        const previous = [...uniqueRows.slice(0, rowIndex)].reverse().find(item => item.columns);
        const next = uniqueRows.slice(rowIndex + 1).find(item => item.columns);
        if (!previous || !next || previous.columns !== next.columns) return;
        row.columns = previous.columns;
        row.gridSignature = previous.gridSignature || next.gridSignature;
        row.inferredFromAdjacentPapx = true;
        inferredRowCount += 1;
    });
    const usableRows = uniqueRows.filter(row => row.columns);

    const tables = [];
    let previousTtpIndex = 0;
    usableRows.forEach((row, rowIndex) => {
        const previousRow = usableRows[rowIndex - 1];
        const nonTableGaps = previousRow
            ? propertyRanges.filter(range => (
                !range.inTable
                && range.fcStart >= previousRow.fcEnd
                && range.fcEnd <= row.fcEnd
            ))
            : propertyRanges.filter(range => (
                !range.inTable
                && range.fcEnd <= row.fcEnd
            ));
        const hasNonTableGap = previousRow ? nonTableGaps.length > 0 : false;
        const currentTable = tables.at(-1);
        const startsNewTable = !currentTable
            || hasNonTableGap;
        let rowRangeStart = previousTtpIndex;
        if (startsNewTable && nonTableGaps.length) {
            const finalGap = nonTableGaps
                .filter(range => range.textEnd != null)
                .sort((left, right) => left.fcEnd - right.fcEnd)
                .at(-1);
            if (finalGap) rowRangeStart = finalGap.textEnd;
        }
        const values = rowCellsFromTtpRange(
            pieceTable.text,
            rowRangeStart,
            row.ttpIndex,
            row.columns
        );
        if (startsNewTable) {
            tables.push({
                columns: row.columns,
                gridSignature: row.gridSignature,
                rows: [],
                rowPropertyRefs: []
            });
        }
        const targetTable = tables.at(-1);
        targetTable.columns = Math.max(targetTable.columns, row.columns);
        targetTable.rows.push(values);
        targetTable.rowPropertyRefs.push(row.sourceRef);
        previousTtpIndex = row.ttpIndex + 1;
    });
    tables.inferredRowCount = inferredRowCount;
    return tables;
}

function heuristicLegacyDocText(wordBytes) {
    if (wordBytes.length > MAX_LEGACY_FALLBACK_BYTES) {
        throw new Error(
            `Fallback DOC dibatasi ${MAX_LEGACY_FALLBACK_BYTES / 1024 / 1024} MB; `
            + 'konversikan dokumen ke DOCX untuk mencegah pemindaian biner berlebihan.'
        );
    }
    const candidates = [];
    const unicodePattern = /(?:[\u0020-\u007E\u00A0-\u024F]\u0000){4,}/g;
    const ansiPattern = /[\u0020-\u007E\u00A0-\u00FF]{6,}/g;
    const scanBytes = wordBytes;
    let binaryString = '';
    for (let offset = 0; offset < scanBytes.length; offset += 8192) {
        binaryString += String.fromCharCode(...scanBytes.subarray(offset, offset + 8192));
    }
    let match;
    while ((match = unicodePattern.exec(binaryString))) {
        candidates.push(match[0].replace(/\u0000/g, ''));
    }
    while ((match = ansiPattern.exec(binaryString))) {
        candidates.push(match[0]);
    }
    return [...new Set(candidates.map(value => value.trim()).filter(Boolean))].join('\r');
}

function normalizeWordControls(text) {
    return String(text || '')
        .replace(/\u0001/g, ' [[OBJEK/GAMBAR]] ')
        .replace(/[\u0013\u0014\u0015]/g, '')
        // In a depth-one Word table the final cell mark and the following
        // table-terminating paragraph (TTP) mark are both U+0007. Preserve
        // that pair as a row boundary before converting ordinary cell marks.
        .replace(/\u0007\u0007/g, '\t\n')
        .replace(/\u0007/g, '\t')
        .replace(/\u000B/g, '\n')
        .replace(/\u000C/g, '\n[PAGE BREAK]\n')
        .replace(/\u000D/g, '\n')
        .replace(/\u0000/g, '')
        .replace(/[ \u00A0]+\n/g, '\n')
        .replace(/\n{4,}/g, '\n\n')
        .trim();
}

function textToBlocks(text) {
    const lines = String(text || '').split(/\n/).map(line => line.trim());
    const blocks = [];
    let tableRows = [];
    let paragraphLines = [];

    function flushTable() {
        if (tableRows.length) {
            blocks.push({ kind: 'table', rows: tableRows });
            tableRows = [];
        }
    }
    function flushParagraphs() {
        if (paragraphLines.length) {
            blocks.push({ kind: 'paragraphs', lines: paragraphLines });
            paragraphLines = [];
        }
    }

    lines.forEach(line => {
        if (!line) {
            flushTable();
            flushParagraphs();
            return;
        }
        const cells = line.split(/\t/).map(cell => cell.trim());
        const populatedCellCount = cells.filter(Boolean).length;
        if (line.includes('\t') && populatedCellCount >= 1) {
            flushParagraphs();
            tableRows.push(cells);
        } else {
            flushTable();
            paragraphLines.push(line);
        }
    });
    flushTable();
    flushParagraphs();
    return blocks;
}
