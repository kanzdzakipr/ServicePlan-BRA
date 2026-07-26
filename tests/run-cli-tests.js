/**
 * CLI Test Automation Suite - Impor Dokumen (Vanilla JS + PHP)
 * Evaluates DocumentImportEngine, Web Worker, IndexedDB, ReportForms, and ZipPreflight headlessly in Node.js.
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { pathToFileURL } = require('url');

const ROOT_DIR = path.resolve(__dirname, '..');
const SCRIPTS_DIR = path.join(ROOT_DIR, 'scripts');
const TESTS_DIR = path.join(ROOT_DIR, 'tests');
const VENDOR_DIR = path.join(SCRIPTS_DIR, 'vendor');

console.log('================================================================================');
console.log('          CLI TEST AUTOMATION SUITE: FITUR IMPOR DOKUMEN (NODE.JS CLI)');
console.log('================================================================================');
console.log(`Working Directory : ${ROOT_DIR}`);
console.log(`Node.js Version   : ${process.version}\n`);

// -----------------------------------------------------------------------------
// 1. MOCK GLOBAL OBJECTS (BROWSER ENVIRONMENT IN NODE.JS)
// -----------------------------------------------------------------------------

// LocalStorage Mock
class MockLocalStorage {
    constructor() {
        this.store = new Map();
    }
    getItem(key) {
        return this.store.has(String(key)) ? this.store.get(String(key)) : null;
    }
    setItem(key, value) {
        this.store.set(String(key), String(value));
    }
    removeItem(key) {
        this.store.delete(String(key));
    }
    clear() {
        this.store.clear();
    }
    get length() {
        return this.store.size;
    }
    key(index) {
        return Array.from(this.store.keys())[index] || null;
    }
}

// In-Memory IndexedDB Mock
class MockIDBIndex {
    constructor(store, name, keyPath, options) {
        this.store = store;
        this.name = name;
        this.keyPath = keyPath;
        this.options = options;
    }
    get(key) {
        const req = { result: undefined, onsuccess: null, onerror: null };
        queueMicrotask(() => {
            const matches = Array.from(this.store.data.values()).filter(item => {
                const val = this.keyPath.split('.').reduce((acc, curr) => acc?.[curr], item);
                return val === key;
            });
            req.result = matches[0];
            if (req.onsuccess) req.onsuccess({ target: req });
        });
        return req;
    }
    getAll(key) {
        const req = { result: [], onsuccess: null, onerror: null };
        queueMicrotask(() => {
            if (key === undefined) {
                req.result = Array.from(this.store.data.values());
            } else {
                req.result = Array.from(this.store.data.values()).filter(item => {
                    const val = this.keyPath.split('.').reduce((acc, curr) => acc?.[curr], item);
                    return val === key;
                });
            }
            if (req.onsuccess) req.onsuccess({ target: req });
        });
        return req;
    }
}

class MockIDBObjectStore {
    constructor(transaction, name, keyPath) {
        this.transaction = transaction;
        this.name = name;
        this.keyPath = keyPath;
        this.data = transaction.db.stores.get(name) || new Map();
        transaction.db.stores.set(name, this.data);
        this.indexes = new Map();
    }
    createIndex(name, keyPath, options) {
        const idx = new MockIDBIndex(this, name, keyPath, options);
        this.indexes.set(name, idx);
        return idx;
    }
    index(name) {
        if (!this.indexes.has(name)) {
            return new MockIDBIndex(this, name, name, {});
        }
        return this.indexes.get(name);
    }
    put(value) {
        const req = { result: undefined, onsuccess: null, onerror: null };
        queueMicrotask(() => {
            const key = value[this.keyPath];
            this.data.set(key, value);
            req.result = key;
            if (req.onsuccess) req.onsuccess({ target: req });
        });
        return req;
    }
    get(key) {
        const req = { result: undefined, onsuccess: null, onerror: null };
        queueMicrotask(() => {
            req.result = this.data.get(key);
            if (req.onsuccess) req.onsuccess({ target: req });
        });
        return req;
    }
    getAll() {
        const req = { result: [], onsuccess: null, onerror: null };
        queueMicrotask(() => {
            req.result = Array.from(this.data.values());
            if (req.onsuccess) req.onsuccess({ target: req });
        });
        return req;
    }
    delete(key) {
        const req = { result: undefined, onsuccess: null, onerror: null };
        queueMicrotask(() => {
            this.data.delete(key);
            if (req.onsuccess) req.onsuccess({ target: req });
        });
        return req;
    }
}

class MockIDBTransaction {
    constructor(db, storeNames, mode) {
        this.db = db;
        this.storeNames = Array.isArray(storeNames) ? storeNames : [storeNames];
        this.mode = mode;
        this.oncomplete = null;
        this.onerror = null;
        this.onabort = null;
        this.error = null;

        queueMicrotask(() => {
            queueMicrotask(() => {
                if (this.oncomplete) this.oncomplete({ target: this });
            });
        });
    }
    objectStore(name) {
        const keyPath = this.db.storeKeyPaths.get(name) || 'id';
        return new MockIDBObjectStore(this, name, keyPath);
    }
}

class MockIDBDatabase {
    constructor(name, version) {
        this.name = name;
        this.version = version;
        this.stores = new Map();
        this.storeKeyPaths = new Map();
        this.objectStoreNames = {
            contains: (name) => this.stores.has(name)
        };
    }
    createObjectStore(name, options = {}) {
        const keyPath = options.keyPath || 'id';
        const storeMap = new Map();
        this.stores.set(name, storeMap);
        this.storeKeyPaths.set(name, keyPath);
        const dummyTx = { db: this };
        return new MockIDBObjectStore(dummyTx, name, keyPath);
    }
    transaction(storeNames, mode = 'readonly') {
        return new MockIDBTransaction(this, storeNames, mode);
    }
    close() {}
}

const dbInstances = new Map();

class MockIDBFactory {
    open(name, version = 1) {
        const req = {
            result: null,
            error: null,
            onsuccess: null,
            onerror: null,
            onupgradeneeded: null,
            onblocked: null
        };
        queueMicrotask(() => {
            let db = dbInstances.get(name);
            const isNew = !db;
            if (isNew) {
                db = new MockIDBDatabase(name, version);
                dbInstances.set(name, db);
            }
            req.result = db;
            if (isNew && req.onupgradeneeded) {
                req.onupgradeneeded({ target: req, result: db });
            }
            if (req.onsuccess) {
                req.onsuccess({ target: req, result: db });
            }
        });
        return req;
    }
}

// Web Worker Mock System (Executing in Node vm)
class MockWorker {
    constructor(scriptUrl) {
        this.hostListeners = { message: [], error: [] };
        this.workerListeners = { message: [] };

        const urlObj = new URL(scriptUrl, global.document.baseURI);
        const relativeScriptPath = urlObj.pathname.replace(/^\/+/,'');
        let fullScriptPath = path.resolve(ROOT_DIR, relativeScriptPath);
        if (!fs.existsSync(fullScriptPath)) {
            fullScriptPath = path.join(SCRIPTS_DIR, path.basename(relativeScriptPath));
        }

        const workerContext = vm.createContext({
            console,
            setTimeout,
            clearTimeout,
            setInterval,
            clearInterval,
            Uint8Array,
            ArrayBuffer,
            DataView,
            TextDecoder,
            TextEncoder,
            Math,
            Date,
            JSON,
            Object,
            Array,
            String,
            Number,
            Boolean,
            Error,
            TypeError,
            RangeError,
            Set,
            Map,
            Promise,
            URL
        });

        workerContext.self = workerContext;

        workerContext.importScripts = (...urls) => {
            for (const urlStr of urls) {
                const cleanUrl = urlStr.split('?')[0];
                let scriptPath = path.resolve(path.dirname(fullScriptPath), cleanUrl);
                if (!fs.existsSync(scriptPath)) {
                    scriptPath = path.resolve(SCRIPTS_DIR, cleanUrl);
                }
                const code = fs.readFileSync(scriptPath, 'utf8');
                vm.runInContext(code, workerContext, { filename: scriptPath });
            }
        };

        workerContext.addEventListener = (type, listener) => {
            if (type === 'message') {
                this.workerListeners.message.push(listener);
            }
        };

        workerContext.postMessage = (data) => {
            const event = { data };
            for (const cb of this.hostListeners.message) {
                cb(event);
            }
        };

        const workerCode = fs.readFileSync(fullScriptPath, 'utf8');
        vm.runInContext(workerCode, workerContext, { filename: fullScriptPath });
    }

    addEventListener(type, listener) {
        if (this.hostListeners[type]) {
            this.hostListeners[type].push(listener);
        }
    }

    postMessage(data) {
        const event = { data };
        for (const cb of this.workerListeners.message) {
            try {
                cb(event);
            } catch (err) {
                for (const errCb of this.hostListeners.error) {
                    errCb(err);
                }
            }
        }
    }

    terminate() {
        this.hostListeners = { message: [], error: [] };
        this.workerListeners = { message: [] };
    }
}

// DOM & Global Setup
const baseURI = pathToFileURL(ROOT_DIR + '/').href;

global.window = global;
global.self = global;
global.localStorage = new MockLocalStorage();
global.indexedDB = new MockIDBFactory();
global.Worker = MockWorker;

global.location = {
    search: '',
    pathname: '/dashboard.html',
    href: `${baseURI}dashboard.html`,
    hash: ''
};

global.history = {
    replaceState: () => {},
    pushState: () => {}
};

global.navigator = {
    userAgent: 'Node.js CLI Test Runner'
};

const dummyElement = {
    style: {},
    dataset: {},
    querySelector: () => dummyElement,
    querySelectorAll: () => [],
    classList: {
        add: () => {},
        remove: () => {},
        toggle: () => {},
        contains: () => false
    },
    appendChild: () => {},
    setAttribute: () => {},
    getAttribute: () => null,
    addEventListener: () => {},
    removeEventListener: () => {},
    scrollIntoView: () => {},
    focus: () => {},
    blur: () => {},
    click: () => {},
    textContent: '',
    value: '',
    innerHTML: ''
};

global.document = {
    readyState: 'complete',
    baseURI: baseURI,
    currentScript: {
        src: `${baseURI}scripts/document-import-engine.js`
    },
    body: dummyElement,
    addEventListener: () => {},
    removeEventListener: () => {},
    getElementById: () => dummyElement,
    querySelector: () => dummyElement,
    querySelectorAll: () => [],
    getElementsByClassName: () => [],
    getElementsByTagName: () => [],
    createElement: () => dummyElement,
    title: 'CLI Test Runner'
};

// Custom File polyfill if needed (Node 22 provides native File and Blob)
if (typeof global.File === 'undefined') {
    global.File = class MockFile extends Blob {
        constructor(parts, name, options = {}) {
            super(parts, options);
            this.name = name;
            this.lastModified = options.lastModified || Date.now();
            this.webkitRelativePath = options.relativePath || '';
        }
    };
}

// -----------------------------------------------------------------------------
// 2. LOAD APPLICATION SCRIPTS IN GLOBAL CONTEXT
// -----------------------------------------------------------------------------
console.log('Loading application scripts into mocked browser environment...');

function loadScript(relativePath) {
    const fullPath = path.join(SCRIPTS_DIR, relativePath);
    const code = fs.readFileSync(fullPath, 'utf8');
    vm.runInThisContext(code, { filename: fullPath });
}

loadScript('vendor/jszip-3.10.1.min.js');
loadScript('vendor/pdf-3.11.174.min.js');
loadScript('vendor/tesseract-5.1.1.min.js');
loadScript('report-forms.js');
loadScript('document-import-zip-preflight.js');
loadScript('document-import-engine.js');
loadScript('document-import.js');

console.log('✓ All scripts loaded successfully.\n');

// -----------------------------------------------------------------------------
// 3. TEST SUITES EXECUTION & ASSERTER
// -----------------------------------------------------------------------------

let totalAssertions = 0;
let passedAssertions = 0;
let failedAssertions = 0;

function assert(description, condition, actual, expected) {
    totalAssertions += 1;
    if (condition) {
        passedAssertions += 1;
        console.log(`  [PASS] ${description}`);
    } else {
        failedAssertions += 1;
        console.error(`  [FAIL] ${description}`);
        console.error(`         Actual   : ${JSON.stringify(actual)}`);
        console.error(`         Expected : ${JSON.stringify(expected)}`);
    }
}

async function runAllSuites() {
    console.log('--------------------------------------------------------------------------------');
    console.log('SUITE 1: Document Extraction & Mapping Engine (CSV Fixture & SourceRef Check)');
    console.log('--------------------------------------------------------------------------------');
    try {
        const csvPath = path.join(TESTS_DIR, 'fixtures', 'document-import-multiline.csv');
        const csvContent = fs.readFileSync(csvPath, 'utf8');
        const csvBlob = new Blob([csvContent], { type: 'text/csv' });
        const csvFile = new File([csvBlob], 'document-import-multiline.csv', {
            type: 'text/csv',
            lastModified: Date.now()
        });
        Object.defineProperty(csvFile, 'relativePath', {
            value: 'tests/fixtures/document-import-multiline.csv',
            configurable: true
        });

        const output = await global.FleetDocumentImportEngine.extractFile(csvFile, { ocrMode: 'off' });
        const extraction = output.extraction;
        const schemas = global.FleetReportForms.getSchemas();

        assert('Extraction completed cleanly with format csv', extraction.format === 'csv', extraction.format, 'csv');
        assert('SHA-256 checksum calculated', Boolean(output.source?.sha256), Boolean(output.source?.sha256), true);

        // Validate sourceRef on every fragment
        const totalFragments = extraction.fragments.length;
        const blankSourceRefs = extraction.fragments.filter(f => !String(f.sourceRef || '').trim()).length;
        assert('Every extracted fragment has complete sourceRef', blankSourceRefs === 0, blankSourceRefs, 0);

        const invalidSourceRefFormat = extraction.fragments.filter(f => !/^(table|p\d|sec|head|csv|xlsx|doc|pdf|ocr|img)/i.test(f.sourceRef)).length;
        assert('All fragment sourceRef follow valid structural format', invalidSourceRefFormat === 0, invalidSourceRefFormat, 0);

        // Analysis & Mapping
        const analysis = global.FleetDocumentImportEngine.analyzeImport(output, schemas, { targetSchemaId: 'bapp' });
        assert('Analysis mapped target schema to bapp', analysis.target?.schemaId === 'bapp', analysis.target?.schemaId, 'bapp');
        assert('Mapped row count matches expected (2 rows)', (analysis.mapping.rows || []).length === 2, (analysis.mapping.rows || []).length, 2);

        // Validate Provenance and sourceRef integrity
        let provenanceValid = true;
        for (const row of analysis.mapping.rows || []) {
            if (!row._import?.sourceRef) provenanceValid = false;
        }
        assert('Row mapping provenance maintains sourceRef link', provenanceValid, provenanceValid, true);

        // CSV specific checks
        const cells = new Map(extraction.fragments.filter(f => f.kind === 'cell').map(f => [f.meta?.address, f]));
        const a2 = cells.get('A2');
        const a3 = cells.get('A3');
        const b3 = cells.get('B3');
        const d2 = cells.get('D2');

        assert('CSV multiline single cell preserved', Boolean(d2?.value.includes('Baris pertama') && d2.value.includes('baris kedua tetap satu sel')), true, true);
        assert('CSV leading zeros preserved (00123 & 00456)', a2?.value === '00123' && a3?.value === '00456', { a2: a2?.value, a3: a3?.value }, { a2: '00123', a3: '00456' });
        assert('CSV formula literal not executed', b3?.value === '=HYPERLINK("https://invalid.example","jangan dieksekusi")' && extraction.stats.formulas === 0, b3?.value, '=HYPERLINK("https://invalid.example","jangan dieksekusi")');

        // Envelope Roundtrip Check
        const envelope = global.FleetDocumentImportEngine.buildDatabaseEnvelope(analysis);
        const envelopeJson = JSON.stringify(envelope);
        const restoredEnvelope = JSON.parse(envelopeJson);
        const envelopeRoundtrip = JSON.stringify(restoredEnvelope) === envelopeJson
            && restoredEnvelope.sourceDocument.sha256 === output.source.sha256
            && restoredEnvelope.extraction.fragments.length === extraction.fragments.length;
        assert('Database envelope roundtrip integrity', envelopeRoundtrip, envelopeRoundtrip, true);

    } catch (err) {
        assert('Suite 1 executed without unhandled exceptions', false, err.message, 'No Exception');
        console.error(err.stack);
    }

    console.log('\n--------------------------------------------------------------------------------');
    console.log('SUITE 2: IndexedDB State & Storage Transitions');
    console.log('--------------------------------------------------------------------------------');
    try {
        const testDbName = 'fleetmonitor-document-imports';
        const openReq = global.indexedDB.open(testDbName, 1);

        await new Promise((resolve, reject) => {
            openReq.onupgradeneeded = (e) => {
                const db = e.result;
                db.createObjectStore('records', { keyPath: 'importId' });
                db.createObjectStore('summaries', { keyPath: 'importId' });
            };
            openReq.onsuccess = (e) => resolve(e.result);
            openReq.onerror = (e) => reject(e.error);
        });

        const db = openReq.result;
        assert('IndexedDB opened and stores created', db.objectStoreNames.contains('records') && db.objectStoreNames.contains('summaries'), true, true);

        // Put Record Test
        const testRecord = {
            importId: 'IMPORT-CLI-001',
            createdAt: new Date().toISOString(),
            status: 'processing',
            source: { fileName: 'test-doc.csv', sha256: 'abc123sha256' },
            quality: { errors: 0 }
        };

        const tx = db.transaction(['records', 'summaries'], 'readwrite');
        const recStore = tx.objectStore('records');
        const sumStore = tx.objectStore('summaries');

        recStore.put(testRecord);
        sumStore.put({ importId: testRecord.importId, status: 'processing', fileName: testRecord.source.fileName });

        await new Promise(res => tx.oncomplete = res);

        // Retrieve Record Test
        const txRead = db.transaction(['records', 'summaries'], 'readonly');
        const readReq = txRead.objectStore('records').get('IMPORT-CLI-001');

        const fetchedRecord = await new Promise(res => readReq.onsuccess = () => res(readReq.result));
        assert('Fetched record from IndexedDB matching importId', fetchedRecord?.importId === 'IMPORT-CLI-001', fetchedRecord?.importId, 'IMPORT-CLI-001');

        // Status Transition Test
        testRecord.status = 'ready';
        const txUpdate = db.transaction(['records', 'summaries'], 'readwrite');
        txUpdate.objectStore('records').put(testRecord);
        txUpdate.objectStore('summaries').put({ importId: testRecord.importId, status: 'ready' });
        await new Promise(res => txUpdate.oncomplete = res);

        const txRead2 = db.transaction(['summaries'], 'readonly');
        const readSumReq = txRead2.objectStore('summaries').get('IMPORT-CLI-001');
        const fetchedSummary = await new Promise(res => readSumReq.onsuccess = () => res(readSumReq.result));
        assert('IndexedDB summary status updated to ready', fetchedSummary?.status === 'ready', fetchedSummary?.status, 'ready');

    } catch (err) {
        assert('Suite 2 executed without unhandled exceptions', false, err.message, 'No Exception');
        console.error(err.stack);
    }

    console.log('\n--------------------------------------------------------------------------------');
    console.log('SUITE 3: FleetReportForms State & Draft Normalization');
    console.log('--------------------------------------------------------------------------------');
    try {
        const schemas = global.FleetReportForms.getSchemas();
        assert('FleetReportForms loaded schemas count = 19', schemas.length === 19, schemas.length, 19);

        const p2hStateBefore = global.FleetReportForms.getDraftState('p2h-excavator');
        assert('Empty seed draft hasData is false', p2hStateBefore.hasData === false, p2hStateBefore.hasData, false);

        // Test importDraft into report form
        const importedP2h = global.FleetReportForms.importDraft({
            schemaId: 'p2h-excavator',
            fields: {},
            rows: [],
            importSource: {
                importId: 'TEST-IMPORT-CLI',
                fileName: 'fixture.pdf',
                sha256: '0'.repeat(64),
                mappingCoverage: 0,
                unmappedFragments: 1
            }
        });

        assert('Seed rows preserved in imported draft', importedP2h.rows.length > 1, importedP2h.rows.length, '>1');
        assert('Seed checklist item preserved in draft', Boolean(importedP2h.rows[0]?.item), Boolean(importedP2h.rows[0]?.item), true);

        const p2hStateAfter = global.FleetReportForms.getDraftState('p2h-excavator');
        assert('Import draft recognized as hasData = true', p2hStateAfter.hasData === true, p2hStateAfter.hasData, true);

        // Test LHO Typed Normalization
        const normalizedLho = global.FleetReportForms.importDraft({
            schemaId: 'lho',
            fields: {
                periode: '07/2026',
                jenis_alat: ' Excavator ',
                tipe_merk: ' PC 200 ',
                lokasi: ' Yard A ',
                operator: ' Operator A ',
                id_alat: ' 00123 ',
                ignored_field: 'DROP'
            },
            rows: [{
                tanggal: '26/07/2026',
                jam_awal: '7.05',
                jam_akhir: '08:35:00',
                jam_kerja: '99',
                hm_awal: '10',
                hm_akhir: '12.5',
                hm_operasi: '99',
                site: ' Site A ',
                bbm: 0,
                cuaca: 'cerah',
                keterangan: ' Normal ',
                status: 'misteri',
                _import: { sourceTable: 'table-1', sourceRow: 1 },
                ignored_column: 'DROP'
            }],
            importSource: {
                importId: 'TEST-TYPED-CLI',
                fileName: 'typed-fixture.xlsx',
                sha256: '1'.repeat(64),
                mappingCoverage: 1,
                unmappedFragments: 0
            }
        });

        assert('LHO typed field normalization (periode: 2026-07, id_alat: 00123)', normalizedLho.fields.periode === '2026-07' && normalizedLho.fields.id_alat === '00123', { periode: normalizedLho.fields.periode, id_alat: normalizedLho.fields.id_alat }, { periode: '2026-07', id_alat: '00123' });
        assert('LHO row date/time normalization (tanggal: 2026-07-26, jam_awal: 07:05, cuaca: Cerah)', normalizedLho.rows[0].tanggal === '2026-07-26' && normalizedLho.rows[0].jam_awal === '07:05' && normalizedLho.rows[0].cuaca === 'Cerah', { tanggal: normalizedLho.rows[0].tanggal, jam_awal: normalizedLho.rows[0].jam_awal, cuaca: normalizedLho.rows[0].cuaca }, { tanggal: '2026-07-26', jam_awal: '07:05', cuaca: 'Cerah' });
        assert('Invalid enum value rejected (status = "")', normalizedLho.rows[0].status === '', normalizedLho.rows[0].status, '');
        assert('Raw extra & readonly keys dropped', !('_import' in normalizedLho.rows[0]) && !('ignored_column' in normalizedLho.rows[0]) && !('jam_kerja' in normalizedLho.rows[0]), true, true);

    } catch (err) {
        assert('Suite 3 executed without unhandled exceptions', false, err.message, 'No Exception');
        console.error(err.stack);
    }

    console.log('\n--------------------------------------------------------------------------------');
    console.log('SUITE 4: ZIP Preflight & Security Audit Suite');
    console.log('--------------------------------------------------------------------------------');
    try {
        const baseZip = new Uint8Array(await (async () => {
            const zip = new global.JSZip();
            zip.file('aa.txt', 'A');
            zip.file('bb.txt', 'B');
            return zip.generateAsync({ type: 'uint8array', compression: 'STORE' });
        })());

        const rejectCheck = async (name, factory, expectedPattern) => {
            let rejected = false;
            let message = '';
            try {
                global.FleetZipPreflight.inspect(await factory(), name);
            } catch (error) {
                rejected = expectedPattern.test(error.message);
                message = error.message;
            }
            assert(`ZipPreflight security test: ${name}`, rejected, message || 'No Error', expectedPattern.toString());
        };

        await rejectCheck('path traversal', async () => {
            const zip = new global.JSZip();
            zip.file('../evil.txt', 'x');
            return zip.generateAsync({ type: 'uint8array', compression: 'STORE' });
        }, /ambigu|tidak aman/i);

        await rejectCheck('duplicate normalized path collision', async () => {
            const bytes = baseZip.slice();
            const findSigs = (b, sig) => {
                const matches = [];
                for (let o = 0; o + 4 <= b.length; o++) {
                    const v = b[o] + b[o+1]*256 + b[o+2]*65536 + b[o+3]*16777216;
                    if (v === sig) matches.push(o);
                }
                return matches;
            };
            const locals = findSigs(bytes, 0x04034b50);
            const centrals = findSigs(bytes, 0x02014b50);
            ['local', 'central'].forEach((kind, groupIndex) => {
                const offsets = groupIndex ? centrals : locals;
                const nameOffset = offsets[1] + (groupIndex ? 46 : 30);
                bytes.set(new TextEncoder().encode('aa.txt'), nameOffset);
            });
            return bytes;
        }, /collision/i);

        await rejectCheck('unsupported compression method', async () => {
            const bytes = baseZip.slice();
            const findSigs = (b, sig) => {
                const matches = [];
                for (let o = 0; o + 4 <= b.length; o++) {
                    const v = b[o] + b[o+1]*256 + b[o+2]*65536 + b[o+3]*16777216;
                    if (v === sig) matches.push(o);
                }
                return matches;
            };
            const put16 = (b, o, v) => { b[o] = v & 255; b[o+1] = (v >>> 8) & 255; };
            findSigs(bytes, 0x04034b50).forEach(o => put16(bytes, o + 8, 99));
            findSigs(bytes, 0x02014b50).forEach(o => put16(bytes, o + 10, 99));
            return bytes;
        }, /metode kompresi/i);

    } catch (err) {
        assert('Suite 4 executed without unhandled exceptions', false, err.message, 'No Exception');
        console.error(err.stack);
    }

    console.log('\n================================================================================');
    console.log(`SUMMARY: ${passedAssertions}/${totalAssertions} Assertions Passed`);
    console.log('================================================================================');
    if (failedAssertions > 0) {
        console.error(`❌ RESULT: ${failedAssertions} TEST(S) FAILED.`);
        process.exit(1);
    } else {
        console.log('✅ RESULT: ALL CLI UNIT & INTEGRATION TESTS PASSED SUCCESSFULLY!');
        process.exit(0);
    }
}

runAllSuites().catch(err => {
    console.error('Fatal error during CLI test execution:', err);
    process.exit(1);
});
