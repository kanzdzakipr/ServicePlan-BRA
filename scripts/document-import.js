(function () {
    'use strict';

    const DB_NAME = 'fleetmonitor-document-imports';
    const DB_VERSION = 1;
    const RECORD_STORE = 'records';
    const SUMMARY_STORE = 'summaries';
    const MAX_BATCH_FILES = 500;
    const MAX_BATCH_BYTES = 500 * 1024 * 1024;
    const MAX_RECORD_CACHE = 3;
    const MAX_DRAFT_ROWS = 1000;
    const state = {
        initialized: false,
        summaries: [],
        records: new Map(),
        selectedId: null,
        processing: false,
        detailTab: 'mapping',
        tableIndex: 0,
        detailGeneration: 0,
        currentBatch: null,
        enumerationWarnings: [],
        db: null,
        storageWarning: '',
        hydrating: false,
        hydrationPromise: null
    };

    const engine = () => window.FleetDocumentImportEngine;
    const reports = () => window.FleetReportForms;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatBytes(bytes) {
        const value = Number(bytes || 0);
        if (value < 1024) return `${value} B`;
        if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
        return `${(value / 1024 / 1024).toFixed(2)} MB`;
    }

    function formatPercent(value) {
        return `${Math.round(Number(value || 0) * 100)}%`;
    }

    function formatDateTime(value) {
        if (!value) return '—';
        try {
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(new Date(value));
        } catch (error) {
            return value;
        }
    }

    function statusMeta(status) {
        const values = {
            queued: { label: 'Antre', icon: 'fa-clock', className: 'neutral' },
            processing: { label: 'Memproses', icon: 'fa-spinner fa-spin', className: 'processing' },
            ready: { label: 'Siap jadi draft', icon: 'fa-circle-check', className: 'success' },
            extracted: { label: 'Perlu pilih tipe', icon: 'fa-layer-group', className: 'warning' },
            review: { label: 'Perlu review', icon: 'fa-triangle-exclamation', className: 'warning' },
            duplicate: { label: 'Duplikat', icon: 'fa-copy', className: 'neutral' },
            unsupported: { label: 'Tidak didukung', icon: 'fa-ban', className: 'danger' },
            failed: { label: 'Gagal', icon: 'fa-circle-xmark', className: 'danger' }
        };
        return values[status] || values.queued;
    }

    function makeSummary(record, overrides = {}) {
        const unsupported = record.extraction?.stats?.supported === false;
        let status = unsupported
            ? 'unsupported'
            : record.quality?.errors
                ? 'review'
                : record.target
                    ? 'ready'
                    : 'extracted';
        if (record.duplicateOf) status = 'duplicate';
        return {
            importId: record.importId,
            fileName: record.source.fileName,
            relativePath: record.source.relativePath,
            extension: record.source.extension,
            size: record.source.size,
            sha256: record.source.sha256,
            createdAt: record.createdAt,
            updatedAt: record.updatedAt,
            status,
            target: record.target,
            classification: record.classification,
            quality: {
                extractionCoverage: record.quality?.extractionCoverage || 0,
                mappingCoverage: record.quality?.mappingCoverage || 0,
                requiredCoverage: record.quality?.requiredCoverage || 0,
                errors: record.quality?.errors || 0,
                warnings: record.quality?.warnings?.length || 0,
                sourceFragments: record.quality?.sourceFragments || 0,
                unmappedFragments: record.quality?.unmappedFragments || 0
            },
            stats: record.extraction?.stats || {},
            duplicateOf: record.duplicateOf || null,
            ...overrides
        };
    }

    function openDatabase() {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                reject(new Error('IndexedDB tidak tersedia.'));
                return;
            }
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            let settled = false;
            const finish = (callback, value) => {
                if (settled) return;
                settled = true;
                window.clearTimeout(timeoutId);
                callback(value);
            };
            const timeoutId = window.setTimeout(() => {
                finish(reject, new Error('IndexedDB tidak merespons dalam 10 detik.'));
            }, 10000);
            request.onupgradeneeded = () => {
                const database = request.result;
                if (!database.objectStoreNames.contains(RECORD_STORE)) {
                    const records = database.createObjectStore(RECORD_STORE, { keyPath: 'importId' });
                    records.createIndex('sha256', 'source.sha256', { unique: false });
                    records.createIndex('createdAt', 'createdAt', { unique: false });
                }
                if (!database.objectStoreNames.contains(SUMMARY_STORE)) {
                    const summaries = database.createObjectStore(SUMMARY_STORE, { keyPath: 'importId' });
                    summaries.createIndex('sha256', 'sha256', { unique: false });
                    summaries.createIndex('createdAt', 'createdAt', { unique: false });
                }
            };
            request.onsuccess = () => {
                if (settled) {
                    request.result.close();
                    return;
                }
                finish(resolve, request.result);
            };
            request.onerror = () => finish(
                reject,
                request.error || new Error('Database impor gagal dibuka.')
            );
            request.onblocked = () => finish(
                reject,
                new Error('IndexedDB diblokir oleh tab lain. Tutup tab dashboard lama lalu muat ulang.')
            );
        });
    }

    function dbRequest(storeName, mode, action) {
        return new Promise((resolve, reject) => {
            if (!state.db) {
                reject(new Error('IndexedDB belum aktif.'));
                return;
            }
            const transaction = state.db.transaction(storeName, mode);
            const store = transaction.objectStore(storeName);
            let request;
            let result;
            let settled = false;
            try {
                request = action(store);
            } catch (error) {
                reject(error);
                return;
            }
            request.onsuccess = () => {
                result = request.result;
            };
            request.onerror = () => {
                if (settled) return;
                settled = true;
                reject(request.error || transaction.error);
            };
            transaction.oncomplete = () => {
                if (settled) return;
                settled = true;
                resolve(result);
            };
            transaction.onerror = () => {
                if (settled) return;
                settled = true;
                reject(transaction.error || new Error('Transaksi IndexedDB gagal.'));
            };
            transaction.onabort = transaction.onerror;
        });
    }

    function writeRecordAndSummary(record, summary) {
        return new Promise((resolve, reject) => {
            if (!state.db) {
                reject(new Error('IndexedDB belum aktif.'));
                return;
            }
            const transaction = state.db.transaction(
                [RECORD_STORE, SUMMARY_STORE],
                'readwrite'
            );
            transaction.objectStore(RECORD_STORE).put(record);
            transaction.objectStore(SUMMARY_STORE).put(summary);
            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error || new Error('Transaksi arsip gagal.'));
            transaction.onabort = transaction.onerror;
        });
    }

    function rememberRecord(record) {
        state.records.delete(record.importId);
        state.records.set(record.importId, record);
        while (state.records.size > MAX_RECORD_CACHE) {
            const removableId = [...state.records.keys()].find(importId => (
                importId !== state.selectedId && importId !== record.importId
            ));
            if (!removableId) break;
            state.records.delete(removableId);
        }
    }

    async function saveRecord(record) {
        rememberRecord(record);
        const summary = makeSummary(record);
        const index = state.summaries.findIndex(item => item.importId === record.importId);
        if (index >= 0) state.summaries[index] = summary;
        else state.summaries.unshift(summary);
        updateImportCount();
        if (!state.db) return;
        try {
            await writeRecordAndSummary(record, summary);
        } catch (error) {
            state.storageWarning = `Hasil tetap tersedia selama halaman ini terbuka, tetapi gagal disimpan ke IndexedDB: ${error.message}`;
        }
    }

    async function saveSummary(summary) {
        const index = state.summaries.findIndex(item => item.importId === summary.importId);
        if (index >= 0) state.summaries[index] = summary;
        else state.summaries.unshift(summary);
        updateImportCount();
        if (!state.db) return;
        try {
            await dbRequest(SUMMARY_STORE, 'readwrite', store => store.put(summary));
        } catch (error) {
            state.storageWarning = `Status impor gagal disimpan: ${error.message}`;
        }
    }

    async function loadRecord(importId) {
        if (state.records.has(importId)) return state.records.get(importId);
        if (!state.db) return null;
        try {
            const record = await dbRequest(RECORD_STORE, 'readonly', store => store.get(importId));
            if (record) rememberRecord(record);
            return record || null;
        } catch (error) {
            state.storageWarning = `Detail arsip gagal dibaca: ${error.message}`;
            return null;
        }
    }

    async function deleteRecord(importId) {
        if (state.db) {
            await new Promise((resolve, reject) => {
                const transaction = state.db.transaction(
                    [RECORD_STORE, SUMMARY_STORE],
                    'readwrite'
                );
                transaction.objectStore(RECORD_STORE).delete(importId);
                transaction.objectStore(SUMMARY_STORE).delete(importId);
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error || new Error('Arsip gagal dihapus.'));
                transaction.onabort = transaction.onerror;
            });
        }
        state.records.delete(importId);
        state.summaries = state.summaries.filter(item => item.importId !== importId);
        if (state.selectedId === importId) {
            state.selectedId = state.summaries[0]?.importId || null;
        }
        updateImportCount();
        render();
    }

    async function clearAllRecords() {
        if (state.db) {
            await new Promise((resolve, reject) => {
                const transaction = state.db.transaction(
                    [RECORD_STORE, SUMMARY_STORE],
                    'readwrite'
                );
                transaction.objectStore(RECORD_STORE).clear();
                transaction.objectStore(SUMMARY_STORE).clear();
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error || new Error('Arsip gagal dikosongkan.'));
                transaction.onabort = transaction.onerror;
            });
        }
        state.records.clear();
        state.summaries = [];
        state.selectedId = null;
        state.currentBatch = null;
        updateImportCount();
        render();
    }

    async function hydrateSummaries() {
        try {
            state.db = await openDatabase();
            const summaries = await dbRequest(SUMMARY_STORE, 'readonly', store => store.getAll());
            const merged = new Map(
                state.summaries.map(summary => [summary.importId, summary])
            );
            for (const summary of summaries || []) {
                if (!merged.has(summary.importId)) merged.set(summary.importId, summary);
            }
            state.summaries = [...merged.values()].sort(
                (left, right) => new Date(right.createdAt) - new Date(left.createdAt)
            );
            if (!state.selectedId) {
                state.selectedId = state.summaries[0]?.importId || null;
            }
        } catch (error) {
            state.storageWarning = `Arsip impor persisten tidak tersedia: ${error.message}`;
            state.db = null;
        }
        updateImportCount();
    }

    function setImportControlsDisabled(disabled) {
        [
            'documentFileInput',
            'documentFolderInput',
            'chooseImportFiles',
            'chooseImportFolder',
            'documentOcrMode',
            'clearAllImports'
        ].forEach(id => {
            const control = document.getElementById(id);
            if (control) control.disabled = disabled;
        });
        const dropzone = document.getElementById('documentDropzone');
        if (dropzone) {
            dropzone.setAttribute('aria-disabled', disabled ? 'true' : 'false');
            dropzone.setAttribute('aria-busy', disabled ? 'true' : 'false');
            dropzone.tabIndex = disabled ? -1 : 0;
        }
    }

    function updateImportCount() {
        const counter = document.getElementById('reportImportCount');
        if (counter) counter.textContent = state.summaries.length;
    }

    function createMarkup() {
        const module = document.getElementById('documentImportModule');
        if (!module || module.dataset.ready === 'true') return;
        module.dataset.ready = 'true';
        module.innerHTML = `
            <div class="import-heading">
                <div>
                    <div class="report-eyebrow">Ingest dokumen & staging data</div>
                    <h1>Ubah Dokumen Menjadi Draft Laporan</h1>
                    <p>Impor Word, PDF, Excel, atau CSV. Seluruh pemrosesan berjalan di perangkat ini; hasil ekstraksi, sumber nilai, konflik, dan informasi yang belum terpetakan tetap diaudit.</p>
                </div>
                <div class="import-local-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div><strong>LOCAL PROCESSING</strong><span>Tidak mengunggah file ke layanan luar</span></div>
                </div>
            </div>
            <section class="import-dropzone" id="documentDropzone" tabindex="0" role="button" aria-label="Pilih atau jatuhkan dokumen">
                <div class="import-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <div>
                    <h2>Jatuhkan file atau folder di sini</h2>
                    <p>DOCX/DOC, PDF, XLSX/XLS, CSV/TSV, serta gambar JPG/PNG untuk OCR. File RAR akan dicatat sebagai arsip yang perlu diekstrak.</p>
                    <div class="import-drop-actions">
                        <button type="button" class="import-primary-button" id="chooseImportFiles">
                            <i class="fa-solid fa-file-circle-plus"></i> Pilih File
                        </button>
                        <button type="button" class="import-secondary-button" id="chooseImportFolder">
                            <i class="fa-solid fa-folder-tree"></i> Audit Satu Folder
                        </button>
                    </div>
                </div>
                <input id="documentFileInput" type="file" multiple hidden
                    accept=".doc,.docx,.pdf,.xls,.xlsx,.xlsm,.csv,.tsv,.jpg,.jpeg,.png,.webp,.rar">
                <input id="documentFolderInput" type="file" multiple webkitdirectory directory hidden>
            </section>
            <div class="import-options-bar">
                <div>
                    <label for="documentOcrMode">Mode pembacaan gambar / scan</label>
                    <select id="documentOcrMode" class="report-select">
                        <option value="auto">OCR otomatis pada halaman tanpa teks</option>
                        <option value="all">OCR maksimal, termasuk semua gambar tertanam</option>
                        <option value="off">Tanpa OCR (audit akan menandai halaman scan)</option>
                    </select>
                </div>
                <div class="import-pipeline">
                    <span><b>1</b> Hash</span><i class="fa-solid fa-chevron-right"></i>
                    <span><b>2</b> Ekstrak</span><i class="fa-solid fa-chevron-right"></i>
                    <span><b>3</b> Cocokkan</span><i class="fa-solid fa-chevron-right"></i>
                    <span><b>4</b> Review</span>
                </div>
            </div>
            <div class="import-privacy-note">
                <i class="fa-solid fa-lock"></i>
                <span>Hasil ekstraksi dapat memuat data pribadi dan disimpan di IndexedDB browser ini tanpa enkripsi aplikasi. Hapus arsip ketika review selesai, terutama pada perangkat bersama.</span>
                <button type="button" class="import-secondary-button" id="clearAllImports">
                    <i class="fa-regular fa-trash-can"></i> Hapus Semua Arsip
                </button>
            </div>
            <div id="importStorageWarning"></div>
            <div id="importBatchSummary"></div>
            <div class="import-workbench">
                <aside class="import-queue-panel">
                    <div class="import-panel-title">
                        <div><h2>Antrean & Arsip</h2><span id="importQueueCount"></span></div>
                        <button type="button" id="clearImportSelection" title="Tutup detail" aria-label="Tutup detail dokumen"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div id="documentImportQueue"></div>
                </aside>
                <section class="import-detail-panel" id="documentImportDetail"></section>
            </div>
        `;
        bindBaseEvents();
    }

    function bindBaseEvents() {
        const dropzone = document.getElementById('documentDropzone');
        const fileInput = document.getElementById('documentFileInput');
        const folderInput = document.getElementById('documentFolderInput');
        document.getElementById('chooseImportFiles').addEventListener('click', event => {
            event.stopPropagation();
            if (state.hydrating) return;
            fileInput.click();
        });
        document.getElementById('chooseImportFolder').addEventListener('click', event => {
            event.stopPropagation();
            if (state.hydrating) return;
            folderInput.click();
        });
        fileInput.addEventListener('change', () => {
            enqueueFiles([...fileInput.files]);
            fileInput.value = '';
        });
        folderInput.addEventListener('change', () => {
            enqueueFiles([...folderInput.files]);
            folderInput.value = '';
        });
        dropzone.addEventListener('click', event => {
            if (state.hydrating || event.target.closest('button')) return;
            fileInput.click();
        });
        dropzone.addEventListener('keydown', event => {
            if (state.hydrating || event.target !== dropzone) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, event => {
                event.preventDefault();
                dropzone.classList.add('dragging');
            });
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, event => {
                event.preventDefault();
                dropzone.classList.remove('dragging');
            });
        });
        dropzone.addEventListener('drop', async event => {
            if (state.hydrating) return;
            const result = await filesFromDrop(event.dataTransfer);
            state.enumerationWarnings = result.warnings;
            enqueueFiles(result.files);
        });
        document.getElementById('clearImportSelection').addEventListener('click', () => {
            state.selectedId = null;
            render();
        });
        document.getElementById('clearAllImports').addEventListener('click', async event => {
            if (state.processing) {
                reports()?.notify('Tunggu batch aktif selesai sebelum menghapus arsip.', true);
                return;
            }
            if (!state.summaries.length) {
                reports()?.notify('Arsip impor sudah kosong.');
                return;
            }
            const referenced = state.summaries.filter(summary => (
                reports()?.isImportReferenced?.(summary.importId)
            )).length;
            const confirmed = window.confirm(
                `Hapus seluruh ${state.summaries.length} arsip impor dari browser ini?\n\n`
                + `${referenced ? `${referenced} arsip masih direferensikan oleh draft/laporan. ` : ''}`
                + 'File sumber asli tidak berubah. Tindakan ini tidak dapat dipulihkan.'
            );
            if (!confirmed) return;
            event.currentTarget.disabled = true;
            try {
                await clearAllRecords();
                reports()?.notify('Seluruh arsip impor di browser ini telah dihapus.');
            } catch (error) {
                reports()?.notify(`Arsip gagal dihapus: ${error.message}`, true);
            } finally {
                if (event.currentTarget.isConnected) {
                    event.currentTarget.disabled = state.processing || state.hydrating;
                }
            }
        });
    }

    async function filesFromDrop(dataTransfer) {
        const items = [...(dataTransfer?.items || [])];
        if (!items.length) {
            return { files: [...(dataTransfer?.files || [])], warnings: [] };
        }
        const files = [];
        const warnings = [];
        for (const item of items.filter(candidate => candidate.kind === 'file')) {
            const entry = item.webkitGetAsEntry?.();
            if (entry) {
                await readDroppedEntry(entry, '', files, warnings);
            } else {
                const file = item.getAsFile?.();
                if (file) files.push(file);
                else warnings.push('Satu item drop tidak dapat dibaca sebagai file.');
            }
        }
        [...(dataTransfer?.files || [])].forEach(file => {
            const key = `${file.webkitRelativePath || file.name}|${file.size}|${file.lastModified}`;
            const exists = files.some(existing => (
                `${existing.webkitRelativePath || existing.relativePath || existing.name}|${existing.size}|${existing.lastModified}` === key
            ));
            if (!exists) files.push(file);
        });
        return { files, warnings };
    }

    function readDroppedEntry(entry, parentPath, output, warnings) {
        return new Promise(resolve => {
            if (entry.isFile) {
                entry.file(file => {
                    Object.defineProperty(file, 'relativePath', {
                        value: `${parentPath}${file.name}`,
                        configurable: true
                    });
                    output.push(file);
                    resolve();
                }, error => {
                    warnings.push(`File ${parentPath}${entry.name} gagal dibaca: ${error?.message || 'unknown error'}`);
                    resolve();
                });
                return;
            }
            if (!entry.isDirectory) {
                resolve();
                return;
            }
            const reader = entry.createReader();
            const children = [];
            const readBatch = () => {
                reader.readEntries(async batch => {
                    if (!batch.length) {
                        for (const child of children) {
                            await readDroppedEntry(child, `${parentPath}${entry.name}/`, output, warnings);
                        }
                        resolve();
                        return;
                    }
                    children.push(...batch);
                    readBatch();
                }, error => {
                    warnings.push(`Folder ${parentPath}${entry.name} gagal dibaca: ${error?.message || 'unknown error'}`);
                    resolve();
                });
            };
            readBatch();
        });
    }

    async function enqueueFiles(files) {
        if (state.hydrationPromise) await state.hydrationPromise;
        if (!files.length || state.processing) {
            if (state.processing) reports()?.notify('Tunggu antrean saat ini selesai diproses.', true);
            return;
        }
        if (files.length > MAX_BATCH_FILES) {
            reports()?.notify(`Batch berisi ${files.length} file; batas aman ${MAX_BATCH_FILES} file. Pecah folder menjadi beberapa batch.`, true);
            return;
        }
        const batchBytes = files.reduce((sum, file) => sum + Number(file.size || 0), 0);
        if (batchBytes > MAX_BATCH_BYTES) {
            reports()?.notify(
                `Total batch ${formatBytes(batchBytes)} melewati batas aman ${formatBytes(MAX_BATCH_BYTES)}.`,
                true
            );
            return;
        }
        state.processing = true;
        const ocrMode = document.getElementById('documentOcrMode')?.value || 'auto';
        const batchId = globalThis.crypto?.randomUUID?.() || `BATCH-${Date.now()}`;
        state.currentBatch = {
            batchId,
            startedAt: new Date().toISOString(),
            enumerated: files.length,
            processed: 0,
            failed: 0,
            warnings: [...state.enumerationWarnings],
            complete: false
        };
        state.enumerationWarnings = [];
        const queueIds = [];
        files.forEach((file, index) => {
            const importId = `QUEUE-${Date.now()}-${index}`;
            queueIds.push(importId);
            state.summaries.unshift({
                importId,
                fileName: file.name,
                relativePath: file.webkitRelativePath || file.relativePath || file.name,
                extension: file.name.match(/\.[^.]+$/)?.[0]?.toLowerCase() || '',
                size: file.size,
                createdAt: new Date().toISOString(),
                status: 'queued',
                progress: { current: 0, total: 1, message: 'Menunggu antrean' },
                batchId,
                batchExpected: files.length,
                transient: true
            });
        });
        state.selectedId = queueIds[0];
        render();

        for (let index = 0; index < files.length; index += 1) {
            const file = files[index];
            const queueId = queueIds[index];
            updateTransient(queueId, {
                status: 'processing',
                progress: { current: 0, total: 1, message: 'Menyiapkan parser…' }
            });
            try {
                const extracted = await engine().extractFile(file, { ocrMode }, progress => {
                    updateTransient(queueId, { status: 'processing', progress }, false);
                });
                const schemas = reports()?.getSchemas?.() || [];
                const record = engine().analyzeImport(extracted, schemas);
                record.batchId = batchId;
                record.batchExpected = files.length;
                const duplicate = state.summaries.find(summary => (
                    !summary.transient
                    && summary.sha256
                    && summary.sha256 === record.source.sha256
                    && summary.importId !== record.importId
                ));
                if (duplicate) {
                    record.duplicateOf = duplicate.importId;
                    record.quality.warnings.push({
                        code: 'duplicate_sha256',
                        message: `Isi file identik dengan ${duplicate.fileName}. Keduanya tetap tercatat untuk audit folder.`,
                        severity: 'info',
                        sourceRef: record.source.fileName
                    });
                }
                removeTransient(queueId);
                await saveRecord(record);
                state.selectedId = record.importId;
                state.currentBatch.processed += 1;
            } catch (error) {
                const failed = {
                    importId: queueId,
                    fileName: file.name,
                    relativePath: file.webkitRelativePath || file.relativePath || file.name,
                    extension: file.name.match(/\.[^.]+$/)?.[0]?.toLowerCase() || '',
                    size: file.size,
                    createdAt: new Date().toISOString(),
                    updatedAt: new Date().toISOString(),
                    status: 'failed',
                    error: error.message,
                    batchId,
                    batchExpected: files.length,
                    transient: false
                };
                const summaryIndex = state.summaries.findIndex(item => item.importId === queueId);
                if (summaryIndex >= 0) state.summaries[summaryIndex] = failed;
                await saveSummary(failed);
                state.selectedId = queueId;
                state.currentBatch.processed += 1;
                state.currentBatch.failed += 1;
            }
            render();
        }
        state.processing = false;
        state.currentBatch.complete = true;
        state.currentBatch.completedAt = new Date().toISOString();
        render();
    }

    function updateTransient(importId, values, shouldRender = true) {
        const summary = state.summaries.find(item => item.importId === importId);
        if (summary) Object.assign(summary, values);
        if (shouldRender) renderQueue();
        else {
            const card = document.querySelector(`[data-import-id="${CSS.escape(importId)}"]`);
            if (card) {
                const progress = card.querySelector('.import-card-progress span');
                const label = card.querySelector('.import-card-progress small');
                const current = Number(values.progress?.current || 0);
                const total = Number(values.progress?.total || 1);
                if (progress) progress.style.width = `${Math.round(current / Math.max(total, 1) * 100)}%`;
                if (label) label.textContent = values.progress?.message || 'Memproses…';
            }
        }
    }

    function removeTransient(importId) {
        state.summaries = state.summaries.filter(item => item.importId !== importId);
    }

    function render() {
        renderStorageWarning();
        renderBatchSummary();
        renderQueue();
        renderDetail();
        updateImportCount();
    }

    function renderStorageWarning() {
        const container = document.getElementById('importStorageWarning');
        if (!container) return;
        container.innerHTML = state.storageWarning
            ? `<div class="import-inline-alert danger"><i class="fa-solid fa-database"></i><span>${escapeHtml(state.storageWarning)}</span></div>`
            : '';
    }

    function renderBatchSummary() {
        const container = document.getElementById('importBatchSummary');
        if (!container) return;
        const persistent = state.summaries.filter(item => !item.transient);
        if (!persistent.length && !state.currentBatch) {
            container.innerHTML = '';
            return;
        }
        const counts = persistent.reduce((result, item) => {
            result[item.status] = (result[item.status] || 0) + 1;
            return result;
        }, {});
        const totalBytes = persistent.reduce((sum, item) => sum + Number(item.size || 0), 0);
        const batch = state.currentBatch;
        container.innerHTML = `
            ${batch ? `
                <div class="import-batch-manifest ${batch.complete && batch.failed === 0 && !batch.warnings.length ? 'complete' : ''}">
                    <i class="fa-solid ${batch.complete ? 'fa-clipboard-check' : 'fa-spinner fa-spin'}"></i>
                    <div>
                        <strong>Batch ${escapeHtml(batch.batchId.slice(0, 8))}: ${batch.processed}/${batch.enumerated} file diproses</strong>
                        <span>${batch.failed} gagal · ${batch.warnings.length} masalah enumerasi${batch.complete ? ' · audit selesai' : ' · sedang berjalan'}</span>
                        ${batch.warnings.length ? `<small>${escapeHtml(batch.warnings.slice(0, 3).join(' · '))}</small>` : ''}
                    </div>
                </div>
            ` : ''}
            <div class="import-summary-strip">
                <div><strong>${persistent.length}</strong><span>FILE DIAUDIT</span></div>
                <div><strong>${counts.ready || 0}</strong><span>SIAP DRAFT</span></div>
                <div><strong>${(counts.review || 0) + (counts.extracted || 0)}</strong><span>PERLU REVIEW</span></div>
                <div><strong>${counts.duplicate || 0}</strong><span>DUPLIKAT HASH</span></div>
                <div><strong>${(counts.unsupported || 0) + (counts.failed || 0)}</strong><span>GAGAL / ARSIP</span></div>
                <div><strong>${formatBytes(totalBytes)}</strong><span>TOTAL SUMBER</span></div>
            </div>
        `;
    }

    function renderQueue() {
        const container = document.getElementById('documentImportQueue');
        const count = document.getElementById('importQueueCount');
        if (!container) return;
        if (count) count.textContent = `${state.summaries.length} item`;
        if (!state.summaries.length) {
            container.innerHTML = `
                <div class="import-empty-queue">
                    <i class="fa-regular fa-folder-open"></i>
                    <strong>Belum ada dokumen</strong>
                    <span>Pilih file atau audit satu folder.</span>
                </div>
            `;
            return;
        }
        container.innerHTML = state.summaries.map(summary => {
            const status = statusMeta(summary.status);
            const progressCurrent = Number(summary.progress?.current || 0);
            const progressTotal = Math.max(1, Number(summary.progress?.total || 1));
            const progressPercent = Math.round(progressCurrent / progressTotal * 100);
            return `
                <button type="button"
                    class="import-queue-card ${state.selectedId === summary.importId ? 'active' : ''}"
                    data-import-id="${escapeHtml(summary.importId)}">
                    <div class="import-file-type">${escapeHtml((summary.extension || '?').replace('.', '').slice(0, 4).toUpperCase())}</div>
                    <div class="import-queue-copy">
                        <strong title="${escapeHtml(summary.relativePath || summary.fileName)}">${escapeHtml(summary.fileName)}</strong>
                        <span>${formatBytes(summary.size)} · ${escapeHtml(summary.target?.code || 'Belum dipetakan')}</span>
                        <em class="import-status ${status.className}"><i class="fa-solid ${status.icon}"></i>${status.label}</em>
                        ${summary.status === 'processing' || summary.status === 'queued' ? `
                            <div class="import-card-progress" role="progressbar" aria-label="Progres ${escapeHtml(summary.fileName)}"
                                aria-valuemin="0" aria-valuemax="100" aria-valuenow="${progressPercent}">
                                <div><span style="width:${progressPercent}%"></span></div>
                                <small>${escapeHtml(summary.progress?.message || 'Memproses…')}</small>
                            </div>
                        ` : ''}
                    </div>
                </button>
            `;
        }).join('');
        container.querySelectorAll('[data-import-id]').forEach(button => {
            button.addEventListener('click', async () => {
                state.selectedId = button.dataset.importId;
                state.detailTab = 'mapping';
                state.tableIndex = 0;
                renderQueue();
                await renderDetail();
            });
        });
    }

    async function renderDetail() {
        const container = document.getElementById('documentImportDetail');
        if (!container) return;
        const generation = ++state.detailGeneration;
        if (!state.selectedId) {
            container.innerHTML = `
                <div class="import-detail-empty">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <h2>Review hasil ekstraksi</h2>
                    <p>Pilih salah satu dokumen pada antrean untuk melihat kecocokan template, provenance, tabel, warning, dan paket database.</p>
                </div>
            `;
            return;
        }
        const summary = state.summaries.find(item => item.importId === state.selectedId);
        if (!summary) {
            state.selectedId = null;
            renderDetail();
            return;
        }
        if (summary.status === 'processing' || summary.status === 'queued') {
            container.innerHTML = `
                <div class="import-detail-empty processing">
                    <i class="fa-solid fa-gears"></i>
                    <h2>${escapeHtml(summary.fileName)}</h2>
                    <p>${escapeHtml(summary.progress?.message || 'Dokumen sedang diproses…')}</p>
                </div>
            `;
            return;
        }
        if (summary.status === 'failed') {
            container.innerHTML = `
                <div class="import-detail-empty failed">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <h2>Ekstraksi gagal</h2>
                    <p>${escapeHtml(summary.error || 'Terjadi kesalahan yang tidak diketahui.')}</p>
                    <button type="button" class="import-danger-button" data-delete-import="${escapeHtml(summary.importId)}">
                        <i class="fa-regular fa-trash-can"></i> Hapus catatan
                    </button>
                </div>
            `;
            bindDeleteButtons(container);
            return;
        }

        container.innerHTML = `
            <div class="import-detail-loading">
                <i class="fa-solid fa-spinner fa-spin"></i> Memuat hasil ekstraksi…
            </div>
        `;
        const record = await loadRecord(summary.importId);
        if (
            generation !== state.detailGeneration
            || state.selectedId !== summary.importId
            || !container.isConnected
        ) return;
        if (!record) {
            container.innerHTML = `
                <div class="import-detail-empty failed">
                    <i class="fa-solid fa-database"></i>
                    <h2>Detail arsip tidak ditemukan</h2>
                    <p>Ringkasan tersedia, tetapi payload IndexedDB tidak dapat dibaca.</p>
                </div>
            `;
            return;
        }
        renderRecordDetail(container, record);
    }

    function renderRecordDetail(container, record) {
        const schemas = reports()?.getSchemas?.() || [];
        const status = statusMeta(makeSummary(record).status);
        const warningCounts = record.quality.warnings.reduce((result, warning) => {
            result[warning.severity] = (result[warning.severity] || 0) + 1;
            return result;
        }, {});
        const stats = record.extraction.stats || {};
        const unitSummary = record.extraction.format === 'pdf'
            ? `${stats.pagesProcessed || 0}/${stats.pagesExpected || 0} halaman`
            : ['xlsx', 'xls', 'xlsm', 'csv', 'tsv'].includes(record.extraction.format)
                ? `${stats.sheets || 0} sheet · ${(stats.nonEmptyCells || 0).toLocaleString('id-ID')} sel berisi data`
                : record.extraction.format === 'docx'
                    ? `${stats.xmlPartsProcessed || 0} bagian · ${stats.tables || 0} tabel`
                    : `${(stats.fragments || 0).toLocaleString('id-ID')} fragmen`;

        container.innerHTML = `
            <header class="import-detail-header">
                <div class="import-detail-title">
                    <div class="import-file-hero">${escapeHtml(record.source.extension.replace('.', '').toUpperCase())}</div>
                    <div>
                        <div class="import-detail-kicker">${escapeHtml(record.source.relativePath || record.source.fileName)}</div>
                        <h2>${escapeHtml(record.source.fileName)}</h2>
                        <p>${formatBytes(record.source.size)} · ${escapeHtml(unitSummary)} · SHA-256 <code>${escapeHtml(record.source.sha256.slice(0, 16))}…</code></p>
                    </div>
                </div>
                <div class="import-detail-actions">
                    <button type="button" class="import-secondary-button" data-export-import="${escapeHtml(record.importId)}">
                        <i class="fa-solid fa-file-code"></i> Ekspor JSON
                    </button>
                    <button type="button" class="import-danger-icon" data-delete-import="${escapeHtml(record.importId)}" title="Hapus arsip" aria-label="Hapus arsip ${escapeHtml(record.source.fileName)}">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </header>
            <div class="import-review-strip">
                <div class="import-template-selector">
                    <label for="importTargetSchema">Tipe laporan tujuan</label>
                    <select id="importTargetSchema" class="report-select">
                        <option value="__none__" ${!record.target && record.classification.mode === 'manual' ? 'selected' : ''}>— Belum ada template yang dipilih —</option>
                        ${schemas.map(schema => `
                            <option value="${escapeHtml(schema.id)}" ${record.target?.schemaId === schema.id ? 'selected' : ''}>
                                ${escapeHtml(schema.code)} — ${escapeHtml(schema.title)}
                            </option>
                        `).join('')}
                    </select>
                    <span>Deteksi ${escapeHtml(confidenceLabel(record.classification.confidence))} · skor ${formatPercent(record.classification.score)}</span>
                </div>
                <div class="import-review-action">
                    <em class="import-status ${status.className}"><i class="fa-solid ${status.icon}"></i>${status.label}</em>
                    <button type="button" class="import-primary-button" data-apply-import="${escapeHtml(record.importId)}"
                        ${record.target && record.extraction.stats.supported !== false ? '' : 'disabled'}>
                        <i class="fa-solid fa-wand-magic-sparkles"></i> ${record.quality.requiresIncompleteOverride ? 'Buat Draft Review' : 'Buat Draft Laporan'}
                    </button>
                </div>
            </div>
            <div class="import-quality-grid">
                ${qualityGauge('Cakupan ekstraksi', record.quality.extractionCoverage, 'Semua halaman/sheet/bagian yang berhasil dibaca')}
                ${qualityGauge('Field wajib terisi', record.quality.requiredCoverage, `${record.quality.filledRequiredFields}/${record.quality.requiredFields} field`)}
                ${qualityGauge('Masuk ke schema', record.quality.mappingCoverage, `${record.quality.mappedFragments}/${record.quality.sourceFragments} fragmen`)}
                <div class="import-quality-card warnings">
                    <span>Audit & konflik</span>
                    <strong>${(warningCounts.error || 0) + (warningCounts.warning || 0)}</strong>
                    <small>${warningCounts.error || 0} error · ${warningCounts.warning || 0} warning · ${record.quality.conflicts || 0} konflik</small>
                </div>
            </div>
            ${record.duplicateOf ? `
                <div class="import-inline-alert">
                    <i class="fa-solid fa-copy"></i>
                    <span>Checksum identik dengan arsip <code>${escapeHtml(record.duplicateOf)}</code>. File tetap dicatat agar audit folder berjumlah lengkap.</span>
                </div>
            ` : ''}
            <div class="import-warning-list">
                ${record.quality.warnings.slice(0, 20).map(warning => `
                    <div class="${escapeHtml(warning.severity)}">
                        <i class="fa-solid ${warning.severity === 'error' ? 'fa-circle-xmark' : warning.severity === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info'}"></i>
                        <span><strong>${escapeHtml(warning.code)}</strong>${escapeHtml(warning.message)}<small>${escapeHtml(warning.sourceRef || '')}</small></span>
                    </div>
                `).join('')}
            </div>
            <nav class="import-detail-tabs" aria-label="Detail hasil impor">
                ${detailTabButton('mapping', 'fa-diagram-project', 'Pemetaan')}
                ${detailTabButton('content', 'fa-align-left', 'Konten')}
                ${detailTabButton('tables', 'fa-table-cells', `Tabel (${record.extraction.tables.length})`)}
                ${detailTabButton('audit', 'fa-database', 'Audit & Database')}
            </nav>
            <div id="importDetailBody">${detailBody(record, schemas)}</div>
        `;
        bindDetailEvents(container, record, schemas);
    }

    function confidenceLabel(value) {
        const labels = {
            high: 'tinggi',
            medium: 'sedang',
            low: 'rendah',
            none: 'belum meyakinkan',
            manual: 'dipilih manual'
        };
        return labels[value] || value || '—';
    }

    function qualityGauge(label, value, note) {
        const percentage = Math.round(Number(value || 0) * 100);
        const tone = percentage >= 80 ? 'good' : percentage >= 45 ? 'medium' : 'low';
        return `
            <div class="import-quality-card ${tone}">
                <span>${escapeHtml(label)}</span>
                <strong>${percentage}%</strong>
                <div><i style="width:${percentage}%"></i></div>
                <small>${escapeHtml(note)}</small>
            </div>
        `;
    }

    function detailTabButton(tab, icon, label) {
        const selected = state.detailTab === tab;
        return `
            <button type="button" role="tab" aria-selected="${selected}" data-import-detail-tab="${tab}" class="${selected ? 'active' : ''}">
                <i class="fa-solid ${icon}"></i>${escapeHtml(label)}
            </button>
        `;
    }

    function detailBody(record, schemas) {
        if (state.detailTab === 'content') return contentDetail(record);
        if (state.detailTab === 'tables') return tablesDetail(record);
        if (state.detailTab === 'audit') return auditDetail(record);
        return mappingDetail(record, schemas);
    }

    function mappingDetail(record, schemas) {
        if (!record.target) {
            return `
                <div class="import-tab-empty">
                    <i class="fa-solid fa-arrow-up"></i>
                    <strong>Pilih tipe laporan tujuan</strong>
                    <p>Konten sudah diekstrak dan disimpan. Pemilihan template akan menjalankan ulang pemetaan field dan tabel.</p>
                </div>
            `;
        }
        const schema = schemas.find(item => item.id === record.target.schemaId);
        const fields = schema?.fields || [];
        const mappedFields = fields.filter(field => (
            record.mapping.fields[field.key] !== undefined
            && record.mapping.fields[field.key] !== ''
        ));
        const missingFields = fields.filter(field => (
            field.required
            && (record.mapping.fields[field.key] === undefined || record.mapping.fields[field.key] === '')
        ));
        return `
            <div class="import-mapping-summary">
                <div>
                    <strong>${mappedFields.length}</strong>
                    <span>field identitas terisi</span>
                </div>
                <div>
                    <strong>${record.mapping.rows.length}</strong>
                    <span>baris data dibentuk</span>
                </div>
                <div>
                    <strong>${missingFields.length}</strong>
                    <span>field wajib perlu input</span>
                </div>
                <div>
                    <strong>${record.mapping.unmappedCount}</strong>
                    <span>fragmen tetap di arsip</span>
                </div>
            </div>
            <section class="import-detail-section">
                <div class="import-section-title"><h3>Pemetaan field identitas</h3><span>Nilai normalized + sumber asli</span></div>
                <div class="import-mapping-table-wrap">
                    <table class="import-mapping-table">
                        <thead><tr><th>Field laporan</th><th>Nilai hasil</th><th>Confidence</th><th>Sumber</th></tr></thead>
                        <tbody>
                            ${fields.map(field => {
                                const provenance = record.mapping.fieldProvenance[field.key];
                                const value = record.mapping.fields[field.key];
                                return `
                                    <tr class="${value === undefined || value === '' ? 'missing' : ''}">
                                        <td><strong>${escapeHtml(field.label)}</strong>${field.required ? '<small>WAJIB</small>' : ''}</td>
                                        <td>${value !== undefined && value !== '' ? escapeHtml(value) : '<em>Belum ditemukan</em>'}</td>
                                        <td>${provenance ? formatPercent(provenance.confidence) : '—'}</td>
                                        <td><code>${escapeHtml(provenance?.sourceRef || '—')}</code></td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="import-detail-section">
                <div class="import-section-title"><h3>Preview baris laporan</h3><span>${record.mapping.rows.length > 50 ? '50 baris pertama ditampilkan' : 'Semua baris ditampilkan'}</span></div>
                ${mappedRowsTable(record, schema)}
            </section>
            <details class="import-unmapped-details">
                <summary>Preview informasi belum terpetakan (${record.mapping.unmappedCount})</summary>
                <p>Fragmen berikut belum cocok dengan field template, tetapi tetap ada dalam JSON/IndexedDB.</p>
                <div class="import-fragment-list">
                    ${record.mapping.unmappedPreview.slice(0, 100).map(fragment => `
                        <div><code>${escapeHtml(fragment.sourceRef)}</code><span>${escapeHtml(fragment.value)}</span></div>
                    `).join('') || '<em>Tidak ada fragmen.</em>'}
                </div>
            </details>
        `;
    }

    function mappedRowsTable(record, schema) {
        if (!record.mapping.rows.length) {
            return `<div class="import-tab-empty compact"><p>Belum ada tabel sumber yang cukup cocok dengan kolom ${escapeHtml(schema?.title || '')}.</p></div>`;
        }
        const columns = (schema?.columns || []).filter(column => !column.readonly);
        return `
            <div class="import-source-table-wrap">
                <table class="import-source-table">
                    <thead><tr>${columns.map(column => `<th>${escapeHtml(column.label)}</th>`).join('')}</tr></thead>
                    <tbody>
                        ${record.mapping.rows.slice(0, 50).map(row => `
                            <tr>${columns.map(column => `<td>${escapeHtml(row[column.key] ?? '')}</td>`).join('')}</tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function contentDetail(record) {
        return `
            <div class="import-content-toolbar">
                <div>
                    <strong>${record.extraction.fragments.length.toLocaleString('id-ID')} fragmen dipertahankan</strong>
                    <span>Ditampilkan maksimal 500; gunakan pencarian untuk memfilter preview.</span>
                </div>
                <input type="search" class="report-input" id="importContentSearch" placeholder="Cari isi atau source reference…">
            </div>
            <div class="import-fragment-list large" id="importContentList">
                ${fragmentListMarkup(record.extraction.fragments.slice(0, 500))}
            </div>
        `;
    }

    function fragmentListMarkup(fragments) {
        return fragments.map(fragment => `
            <div>
                <code>${escapeHtml(fragment.sourceRef)}</code>
                <span>${escapeHtml(fragment.value)}</span>
                <small>${escapeHtml(fragment.kind)}</small>
            </div>
        `).join('') || '<div class="import-tab-empty compact"><p>Tidak ada teks yang berhasil diekstrak.</p></div>';
    }

    function tablesDetail(record) {
        const tables = record.extraction.tables;
        if (!tables.length) {
            return `<div class="import-tab-empty"><i class="fa-solid fa-table-cells"></i><strong>Tabel tidak terdeteksi</strong><p>Konten teks tetap tersedia pada tab Konten.</p></div>`;
        }
        state.tableIndex = Math.min(state.tableIndex, tables.length - 1);
        const table = tables[state.tableIndex];
        const maxColumns = Math.min(30, table.headers?.length || 0);
        return `
            <div class="import-table-toolbar">
                <div>
                    <label for="importTableSelect">Tabel sumber</label>
                    <select id="importTableSelect" class="report-select">
                        ${tables.map((item, index) => `
                            <option value="${index}" ${index === state.tableIndex ? 'selected' : ''}>
                                ${escapeHtml(item.title)} — ${(item.rows || []).length} baris
                            </option>
                        `).join('')}
                    </select>
                </div>
                <span><code>${escapeHtml(table.sourceRef)}</code></span>
            </div>
            <div class="import-source-table-wrap">
                <table class="import-source-table">
                    <thead><tr>${(table.headers || []).slice(0, maxColumns).map(header => `<th>${escapeHtml(header)}</th>`).join('')}</tr></thead>
                    <tbody>
                        ${(table.rows || []).slice(0, 100).map(row => `
                            <tr>${(row.values || []).slice(0, maxColumns).map(value => `<td>${escapeHtml(value)}</td>`).join('')}</tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <p class="import-table-note">${(table.rows || []).length > 100 ? 'Preview dibatasi 100 baris; seluruh baris tetap ada pada ekspor JSON.' : 'Seluruh baris tabel ini ditampilkan.'}${(table.headers || []).length > 30 ? ' Preview dibatasi 30 kolom.' : ''}</p>
        `;
    }

    function auditDetail(record) {
        const databasePreview = {
            schemaVersion: record.schemaVersion,
            importJob: {
                id: record.importId,
                status: record.quality.errors ? 'review_required' : 'extracted'
            },
            sourceDocument: {
                fileName: record.source.fileName,
                sha256: record.source.sha256,
                size: record.source.size
            },
            target: record.target,
            counts: {
                sections: record.extraction.sections.length,
                tables: record.extraction.tables.length,
                fragments: record.extraction.fragments.length,
                artifacts: record.extraction.artifacts.length,
                mappedRows: record.mapping.rows.length
            }
        };
        return `
            <div class="import-audit-grid">
                <section class="import-detail-section">
                    <div class="import-section-title"><h3>Coverage manifest</h3><span>Parser ${escapeHtml(record.extraction.parser.name)}</span></div>
                    <pre>${escapeHtml(JSON.stringify(record.extraction.stats, null, 2))}</pre>
                </section>
                <section class="import-detail-section">
                    <div class="import-section-title"><h3>Envelope database</h3><span>Versi ${escapeHtml(record.schemaVersion)}</span></div>
                    <pre>${escapeHtml(JSON.stringify(databasePreview, null, 2))}</pre>
                </section>
            </div>
            <section class="import-detail-section">
                <div class="import-section-title"><h3>Artefak & bagian non-teks</h3><span>${record.extraction.artifacts.length} item</span></div>
                <div class="import-artifact-list">
                    ${record.extraction.artifacts.slice(0, 300).map(artifact => `
                        <div><i class="fa-regular fa-file"></i><span><strong>${escapeHtml(artifact.name)}</strong><small>${escapeHtml(artifact.kind)} · ${formatBytes(artifact.size)} · ${escapeHtml(artifact.sourceRef)}</small></span></div>
                    `).join('') || '<em>Tidak ada artefak tambahan.</em>'}
                </div>
            </section>
            <div class="import-database-note">
                <i class="fa-solid fa-code-branch"></i>
                <div>
                    <strong>Siap dipindahkan ke staging database</strong>
                    <p>Envelope memisahkan source document, extraction fragments, field/row mapping, provenance, quality, serta warning. File asli nantinya disimpan di object storage; JSON ini masuk ke tabel staging sebelum menjadi report final.</p>
                </div>
                <button type="button" class="import-primary-button" data-export-import="${escapeHtml(record.importId)}">
                    <i class="fa-solid fa-download"></i> Unduh Envelope Lengkap
                </button>
            </div>
        `;
    }

    function bindDetailEvents(container, record, schemas) {
        container.querySelectorAll('[data-import-detail-tab]').forEach(button => {
            button.addEventListener('click', () => {
                state.detailTab = button.dataset.importDetailTab;
                renderRecordDetail(container, record);
            });
        });
        const schemaSelect = container.querySelector('#importTargetSchema');
        schemaSelect?.addEventListener('change', async () => {
            const targetSchemaId = schemaSelect.value;
            const mutableButtons = [
                ...container.querySelectorAll(
                    '[data-apply-import], [data-export-import], [data-delete-import]'
                )
            ];
            const disabledStates = mutableButtons.map(button => [button, button.disabled]);
            schemaSelect.disabled = true;
            schemaSelect.setAttribute('aria-busy', 'true');
            mutableButtons.forEach(button => {
                button.disabled = true;
            });
            try {
                const reanalyzed = engine().analyzeImport(
                    { source: record.source, extraction: record.extraction },
                    schemas,
                    { targetSchemaId }
                );
                reanalyzed.importId = record.importId;
                reanalyzed.createdAt = record.createdAt;
                reanalyzed.updatedAt = new Date().toISOString();
                reanalyzed.duplicateOf = record.duplicateOf || null;
                await saveRecord(reanalyzed);
                render();
            } catch (error) {
                reports()?.notify(`Pemetaan ulang gagal: ${error.message}`, true);
                if (schemaSelect.isConnected) {
                    schemaSelect.disabled = false;
                    schemaSelect.removeAttribute('aria-busy');
                    disabledStates.forEach(([button, wasDisabled]) => {
                        if (button.isConnected) button.disabled = wasDisabled;
                    });
                }
            }
        });
        container.querySelectorAll('[data-apply-import]').forEach(button => {
            button.addEventListener('click', () => {
                const latestRecord = state.records.get(record.importId) || record;
                applyToDraft(latestRecord, button);
            });
        });
        bindDeleteButtons(container);
        container.querySelectorAll('[data-export-import]').forEach(button => {
            button.addEventListener('click', () => exportRecord(record, button));
        });
        const contentSearch = container.querySelector('#importContentSearch');
        let searchTimer = null;
        contentSearch?.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => {
                const query = contentSearch.value.trim().toLocaleLowerCase('id');
                const filtered = [];
                for (const fragment of record.extraction.fragments) {
                    if (
                        !query
                        || `${fragment.value} ${fragment.sourceRef} ${fragment.kind}`
                            .toLocaleLowerCase('id')
                            .includes(query)
                    ) {
                        filtered.push(fragment);
                        if (filtered.length >= 500) break;
                    }
                }
                const list = container.querySelector('#importContentList');
                if (list) list.innerHTML = fragmentListMarkup(filtered);
            }, 180);
        });
        const tableSelect = container.querySelector('#importTableSelect');
        tableSelect?.addEventListener('change', () => {
            state.tableIndex = Number(tableSelect.value || 0);
            renderRecordDetail(container, record);
        });
    }

    function bindDeleteButtons(container) {
        container.querySelectorAll('[data-delete-import]').forEach(button => {
            button.addEventListener('click', async () => {
                const summary = state.summaries.find(
                    item => item.importId === button.dataset.deleteImport
                );
                const referenced = reports()?.isImportReferenced?.(button.dataset.deleteImport);
                const confirmed = window.confirm(
                    `Hapus arsip impor “${summary?.fileName || button.dataset.deleteImport}”?\n\n`
                    + `${referenced ? 'Arsip ini masih direferensikan oleh draft/laporan; provenance detail tidak lagi dapat dibuka. ' : ''}`
                    + 'Hasil ekstraksi di browser ini akan dihapus. File sumber asli tidak berubah.'
                );
                if (!confirmed) return;
                button.disabled = true;
                try {
                    await deleteRecord(button.dataset.deleteImport);
                } catch (error) {
                    button.disabled = false;
                    reports()?.notify(`Arsip gagal dihapus: ${error.message}`, true);
                }
            });
        });
    }

    function applyToDraft(record, button) {
        if (!record.target) {
            reports()?.notify('Pilih tipe laporan tujuan terlebih dahulu.', true);
            return;
        }
        if (record.quality.errors) {
            const confirmed = window.confirm(
                `Ekstraksi “${record.source.fileName}” memiliki ${record.quality.errors} error audit.\n\n`
                + 'Draft akan tetap berstatus review dan tidak difinalkan otomatis. Lanjutkan membuat draft parsial?'
            );
            if (!confirmed) return;
        }
        const draftState = reports()?.getDraftState?.(record.target.schemaId);
        if (draftState?.hasData) {
            const confirmed = window.confirm(
                `Template ${record.target.code} sudah memiliki draft.\n\nGanti draft tersebut dengan hasil ekstraksi “${record.source.fileName}”?`
            );
            if (!confirmed) return;
        }
        try {
            button.disabled = true;
            const payload = engine().prepareDraftPayload(record);
            const totalMappedRows = payload.rows.length;
            if (totalMappedRows > MAX_DRAFT_ROWS) {
                payload.rows = payload.rows.slice(0, MAX_DRAFT_ROWS);
                payload.importSource.totalMappedRows = totalMappedRows;
                payload.importSource.appliedRows = MAX_DRAFT_ROWS;
                payload.importSource.rowsLimited = true;
            }
            reports().importDraft(payload, { replace: true });
        } catch (error) {
            button.disabled = false;
            reports()?.notify(`Draft gagal dibuat: ${error.message}`, true);
        }
    }

    function safeDownloadName(value) {
        return String(value || 'dokumen')
            .replace(/\.[^.]+$/, '')
            .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '_')
            .slice(0, 120);
    }

    async function exportRecord(record, button) {
        const original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyusun JSON…';
        await new Promise(resolve => setTimeout(resolve, 30));
        try {
            const envelope = engine().buildDatabaseEnvelope(record);
            const json = JSON.stringify(envelope);
            const blob = new Blob([json], { type: 'application/json;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${safeDownloadName(record.source.fileName)}_extraction_${record.source.sha256.slice(0, 8)}.json`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
            reports()?.notify(`Envelope JSON berhasil dibuat (${formatBytes(blob.size)}).`);
        } catch (error) {
            reports()?.notify(`Ekspor JSON gagal: ${error.message}`, true);
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    }

    async function initialize() {
        if (state.initialized) return;
        if (!engine() || !reports()) {
            const module = document.getElementById('documentImportModule');
            if (module) {
                module.innerHTML = `
                    <div class="import-inline-alert danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Mesin impor atau API laporan tidak berhasil dimuat.</span>
                    </div>
                `;
            }
            return;
        }
        state.initialized = true;
        createMarkup();
        state.hydrating = true;
        setImportControlsDisabled(true);
        state.hydrationPromise = hydrateSummaries();
        try {
            await state.hydrationPromise;
        } finally {
            state.hydrating = false;
            setImportControlsDisabled(false);
            render();
        }
    }

    document.addEventListener('fleetreport:import-visible', initialize);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('documentImportModule')) initialize();
        });
    } else if (document.getElementById('documentImportModule')) {
        initialize();
    }
})();
