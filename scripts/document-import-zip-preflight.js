(function (global) {
    'use strict';

    const VERSION = '1.0.1';
    const EOCD_SIGNATURE = 0x06054b50;
    const ZIP64_LOCATOR_SIGNATURE = 0x07064b50;
    const CENTRAL_SIGNATURE = 0x02014b50;
    const LOCAL_SIGNATURE = 0x04034b50;
    const DEFAULT_LIMITS = Object.freeze({
        maxEntries: 10000,
        maxEntryBytes: 32 * 1024 * 1024,
        maxUncompressedBytes: 64 * 1024 * 1024
    });

    function uint16(bytes, offset) {
        return bytes[offset] + bytes[offset + 1] * 0x100;
    }

    function uint32(bytes, offset) {
        return bytes[offset]
            + bytes[offset + 1] * 0x100
            + bytes[offset + 2] * 0x10000
            + bytes[offset + 3] * 0x1000000;
    }

    function signatureAt(bytes, offset, signature) {
        return offset >= 0
            && offset + 4 <= bytes.length
            && uint32(bytes, offset) === signature;
    }

    function bytesEqual(left, right) {
        if (left.length !== right.length) return false;
        for (let index = 0; index < left.length; index += 1) {
            if (left[index] !== right[index]) return false;
        }
        return true;
    }

    function parseExtraFields(bytes, start, length, label) {
        const identifiers = new Set();
        const end = start + length;
        let position = start;
        while (position < end) {
            if (position + 4 > end) throw new Error(`${label} memiliki extra field ZIP yang terpotong.`);
            const identifier = uint16(bytes, position);
            const valueLength = uint16(bytes, position + 2);
            position += 4;
            if (position + valueLength > end) {
                throw new Error(`${label} memiliki panjang extra field ZIP yang tidak valid.`);
            }
            identifiers.add(identifier);
            position += valueLength;
        }
        return identifiers;
    }

    function decodeName(rawName, utf8, label) {
        if (!utf8 && [...rawName].some(byte => byte > 0x7f)) {
            throw new Error(
                `${label} memiliki nama non-ASCII tanpa flag UTF-8; paket ditolak agar nama tidak berubah setelah preflight.`
            );
        }
        try {
            return new TextDecoder('utf-8', { fatal: true }).decode(rawName);
        } catch (error) {
            throw new Error(`${label} memiliki nama entry UTF-8 yang tidak valid.`);
        }
    }

    function validateName(name, isDirectory, label) {
        if (
            !name
            || name.includes('\\')
            || name.includes('\u0000')
            || /[\u0001-\u001f\u007f]/.test(name)
            || name.startsWith('/')
            || /^[A-Za-z]:/.test(name)
        ) {
            throw new Error(`${label} memiliki nama entry ZIP yang tidak aman: ${name || '(kosong)'}.`);
        }
        const path = isDirectory ? name.slice(0, -1) : name;
        const segments = path.split('/');
        if (
            !path
            || segments.some(segment => (
                !segment
                || segment === '.'
                || segment === '..'
                || /[. ]$/.test(segment)
                || ['__proto__', 'prototype', 'constructor'].includes(segment.toLowerCase())
            ))
        ) {
            throw new Error(`${label} memiliki path entry ZIP yang ambigu: ${name}.`);
        }
        return `${path.normalize('NFC').toLocaleLowerCase('en-US')}${isDirectory ? '/' : ''}`;
    }

    function inspect(input, label = 'Paket ZIP', customLimits = {}) {
        const bytes = input instanceof Uint8Array ? input : new Uint8Array(input);
        const limits = { ...DEFAULT_LIMITS, ...customLimits };
        if (bytes.length < 22) throw new Error(`${label} terlalu pendek untuk menjadi ZIP.`);

        const searchFloor = Math.max(0, bytes.length - 22 - 0xffff);
        let eocdOffset = -1;
        for (let offset = bytes.length - 22; offset >= searchFloor; offset -= 1) {
            if (!signatureAt(bytes, offset, EOCD_SIGNATURE)) continue;
            const commentLength = uint16(bytes, offset + 20);
            if (offset + 22 + commentLength === bytes.length) {
                eocdOffset = offset;
                break;
            }
        }
        if (eocdOffset < 0) throw new Error(`${label} tidak memiliki EOCD ZIP yang valid.`);
        if (signatureAt(bytes, eocdOffset - 20, ZIP64_LOCATOR_SIGNATURE)) {
            throw new Error(`${label} memakai ZIP64 yang tidak didukung importer browser.`);
        }

        const diskNumber = uint16(bytes, eocdOffset + 4);
        const centralDisk = uint16(bytes, eocdOffset + 6);
        const entriesOnDisk = uint16(bytes, eocdOffset + 8);
        const totalEntries = uint16(bytes, eocdOffset + 10);
        const centralSize = uint32(bytes, eocdOffset + 12);
        const centralOffset = uint32(bytes, eocdOffset + 16);
        if (diskNumber || centralDisk || entriesOnDisk !== totalEntries) {
            throw new Error(`${label} memakai ZIP multi-disk yang tidak didukung.`);
        }
        if (
            totalEntries === 0xffff
            || centralSize === 0xffffffff
            || centralOffset === 0xffffffff
        ) {
            throw new Error(`${label} memakai nilai ZIP64 yang tidak didukung.`);
        }
        if (totalEntries > limits.maxEntries) {
            throw new Error(`${label} memiliki ${totalEntries} record; batas aman ${limits.maxEntries}.`);
        }
        const centralEnd = centralOffset + centralSize;
        if (centralEnd !== eocdOffset || centralEnd > bytes.length) {
            throw new Error(`${label} memiliki rentang Central Directory yang tidak konsisten.`);
        }

        const records = [];
        const collisionKeys = new Set();
        const localOffsets = new Set();
        let position = centralOffset;
        let uncompressedBytes = 0;
        for (let index = 0; index < totalEntries; index += 1) {
            if (!signatureAt(bytes, position, CENTRAL_SIGNATURE) || position + 46 > centralEnd) {
                throw new Error(`${label} memiliki central header ke-${index + 1} yang rusak.`);
            }
            const flags = uint16(bytes, position + 8);
            const method = uint16(bytes, position + 10);
            const crc32 = uint32(bytes, position + 16);
            const compressedSize = uint32(bytes, position + 20);
            const uncompressedSize = uint32(bytes, position + 24);
            const nameLength = uint16(bytes, position + 28);
            const extraLength = uint16(bytes, position + 30);
            const commentLength = uint16(bytes, position + 32);
            const startDisk = uint16(bytes, position + 34);
            const externalAttributes = uint32(bytes, position + 38);
            const localOffset = uint32(bytes, position + 42);
            if ((flags & 0x0001) || (flags & 0x0040) || (flags & 0x2000)) {
                throw new Error(`${label} memiliki entry terenkripsi/berheader tersamar.`);
            }
            if ((flags & ~0x080e) !== 0 || (method !== 8 && (flags & 0x0006) !== 0)) {
                throw new Error(`${label} memakai general-purpose flag ZIP yang tidak didukung.`);
            }
            if (![0, 8].includes(method)) {
                throw new Error(`${label} memakai metode kompresi ZIP ${method} yang tidak diizinkan.`);
            }
            if (
                compressedSize === 0xffffffff
                || uncompressedSize === 0xffffffff
                || localOffset === 0xffffffff
                || startDisk !== 0
            ) {
                throw new Error(`${label} memiliki entry ZIP64 atau multi-disk.`);
            }

            const nameStart = position + 46;
            const extraStart = nameStart + nameLength;
            const recordEnd = extraStart + extraLength + commentLength;
            if (!nameLength || recordEnd > centralEnd) {
                throw new Error(`${label} memiliki panjang central header yang tidak valid.`);
            }
            const extraIdentifiers = parseExtraFields(bytes, extraStart, extraLength, label);
            if (extraIdentifiers.has(0x0001) || extraIdentifiers.has(0x7075)) {
                throw new Error(`${label} memiliki extra field ZIP64/Unicode Path yang tidak diizinkan.`);
            }
            const rawName = bytes.slice(nameStart, nameStart + nameLength);
            const name = decodeName(rawName, Boolean(flags & 0x0800), label);
            const isDirectory = name.endsWith('/') || Boolean(externalAttributes & 0x10);
            if (isDirectory !== name.endsWith('/')) {
                throw new Error(`${label} memiliki penanda file/direktori yang tidak konsisten: ${name}.`);
            }
            const collisionKey = validateName(name, isDirectory, label).replace(/\/$/, '');
            if (collisionKeys.has(collisionKey)) {
                throw new Error(`${label} memiliki collision nama entry ZIP: ${name}.`);
            }
            collisionKeys.add(collisionKey);
            if (uncompressedSize > limits.maxEntryBytes) {
                throw new Error(
                    `${label} memiliki entry ${name} sebesar ${(uncompressedSize / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman ${limits.maxEntryBytes / 1024 / 1024} MB.`
                );
            }
            if (method === 0 && compressedSize !== uncompressedSize) {
                throw new Error(`${label} memiliki ukuran entry STORE yang tidak konsisten: ${name}.`);
            }
            if (isDirectory && (uncompressedSize !== 0 || crc32 !== 0)) {
                throw new Error(`${label} memiliki entry direktori dengan data/CRC tidak kosong: ${name}.`);
            }
            uncompressedBytes += uncompressedSize;
            if (uncompressedBytes > limits.maxUncompressedBytes) {
                throw new Error(
                    `${label} mengembang menjadi ${(uncompressedBytes / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman ${limits.maxUncompressedBytes / 1024 / 1024} MB.`
                );
            }
            if (localOffsets.has(localOffset)) {
                throw new Error(`${label} memakai offset local header berulang.`);
            }
            localOffsets.add(localOffset);
            records.push({
                name,
                rawName,
                flags,
                method,
                crc32,
                compressedSize,
                uncompressedSize,
                localOffset,
                isDirectory
            });
            position = recordEnd;
        }
        if (position !== centralEnd) {
            throw new Error(`${label} memiliki data tambahan di Central Directory.`);
        }

        const spans = [];
        records.forEach(record => {
            const offset = record.localOffset;
            if (!signatureAt(bytes, offset, LOCAL_SIGNATURE) || offset + 30 > centralOffset) {
                throw new Error(`${label} memiliki local header rusak untuk ${record.name}.`);
            }
            const localFlags = uint16(bytes, offset + 6);
            const localMethod = uint16(bytes, offset + 8);
            const localCrc32 = uint32(bytes, offset + 14);
            const localCompressedSize = uint32(bytes, offset + 18);
            const localUncompressedSize = uint32(bytes, offset + 22);
            const localNameLength = uint16(bytes, offset + 26);
            const localExtraLength = uint16(bytes, offset + 28);
            const localNameStart = offset + 30;
            const localExtraStart = localNameStart + localNameLength;
            const dataStart = localExtraStart + localExtraLength;
            const dataEnd = dataStart + record.compressedSize;
            if (
                dataEnd > centralOffset
                || localFlags !== record.flags
                || localMethod !== record.method
                || !bytesEqual(
                    bytes.subarray(localNameStart, localNameStart + localNameLength),
                    record.rawName
                )
            ) {
                throw new Error(`${label} memiliki local header tidak konsisten untuk ${record.name}.`);
            }
            const localExtras = parseExtraFields(bytes, localExtraStart, localExtraLength, label);
            if (localExtras.has(0x0001) || localExtras.has(0x7075)) {
                throw new Error(`${label} memiliki local extra field yang tidak diizinkan.`);
            }
            if (
                !(record.flags & 0x0008)
                && (
                    localCrc32 !== record.crc32
                    || localCompressedSize !== record.compressedSize
                    || localUncompressedSize !== record.uncompressedSize
                )
            ) {
                throw new Error(`${label} memiliki CRC/ukuran local header yang tidak konsisten.`);
            }
            spans.push({ start: offset, end: dataEnd, name: record.name });
        });
        spans.sort((left, right) => left.start - right.start);
        for (let index = 1; index < spans.length; index += 1) {
            if (spans[index].start < spans[index - 1].end) {
                throw new Error(
                    `${label} memiliki rentang data entry yang overlap: `
                    + `${spans[index - 1].name} / ${spans[index].name}.`
                );
            }
        }

        return {
            version: VERSION,
            entries: totalEntries,
            uncompressedBytes,
            centralDirectoryBytes: centralSize,
            records
        };
    }

    function verifyJsZip(zip, audit, label = 'Paket ZIP') {
        const files = Object.values(zip?.files || {});
        if (files.length !== audit.records.length) {
            throw new Error(
                `${label} berubah dari ${audit.records.length} record raw menjadi ${files.length} entry JSZip.`
            );
        }
        const byName = new Map(files.map(entry => [entry.name, entry]));
        audit.records.forEach(record => {
            const entry = byName.get(record.name);
            if (!entry || Boolean(entry.dir) !== record.isDirectory) {
                throw new Error(`${label} kehilangan atau mengubah entry ${record.name} setelah JSZip.`);
            }
            const parsedData = entry._data;
            if (
                (
                    !record.isDirectory
                    && (
                        !parsedData
                        || !Number.isFinite(Number(parsedData.uncompressedSize))
                        || !Number.isFinite(Number(parsedData.compressedSize))
                        || !Number.isFinite(Number(parsedData.crc32))
                        || Number(parsedData.uncompressedSize) !== record.uncompressedSize
                        || Number(parsedData.compressedSize) !== record.compressedSize
                        || (Number(parsedData.crc32) >>> 0) !== record.crc32
                    )
                )
                || (entry.unsafeOriginalName && entry.unsafeOriginalName !== record.name)
            ) {
                throw new Error(`${label} mengubah CRC/ukuran/nama entry ${record.name} setelah JSZip.`);
            }
        });
        return true;
    }

    global.FleetZipPreflight = Object.freeze({
        version: VERSION,
        inspect,
        verifyJsZip
    });
})(typeof self !== 'undefined' ? self : window);
