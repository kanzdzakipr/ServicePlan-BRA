'use strict';

const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const chromePath = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const projectRoot = path.join(__dirname, '..');
const profileDir = path.join(os.tmpdir(), `serviceplan-bra-asset-registration-${process.pid}`);

function connectPipe(chrome) {
    const writePipe = chrome.stdio[3];
    const readPipe = chrome.stdio[4];
    let id = 0;
    let buffer = '';
    const pending = new Map();
    readPipe.setEncoding('utf8');
    readPipe.on('data', chunk => {
        buffer += chunk;
        let boundary;
        while ((boundary = buffer.indexOf('\0')) >= 0) {
            const raw = buffer.slice(0, boundary);
            buffer = buffer.slice(boundary + 1);
            if (!raw) continue;
            const message = JSON.parse(raw);
            const waiter = pending.get(message.id);
            if (!waiter) continue;
            pending.delete(message.id);
            if (message.error) waiter.reject(new Error(message.error.message));
            else waiter.resolve(message.result || {});
        }
    });
    const send = (method, params = {}, sessionId = null) => new Promise((resolve, reject) => {
        const commandId = ++id;
        pending.set(commandId, { resolve, reject });
        const message = { id: commandId, method, params };
        if (sessionId) message.sessionId = sessionId;
        writePipe.write(JSON.stringify(message) + '\0');
    });
    const evaluate = async (sessionId, expression) => {
        const response = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }, sessionId);
        if (response.exceptionDetails) throw new Error(response.exceptionDetails.text || 'Evaluasi browser gagal.');
        return response.result?.value;
    };
    return { send, evaluate };
}

async function main() {
    if (!fs.existsSync(chromePath)) throw new Error(`Chrome tidak ditemukan: ${chromePath}`);
    const view = fs.readFileSync(path.join(projectRoot, 'dashboard.view.php'), 'utf8');
    const css = fs.readFileSync(path.join(projectRoot, 'scripts', 'dashboard.css'), 'utf8');
    const modalStart = view.indexOf('<!-- Modal Tambah Aset -->');
    const modalEnd = view.indexOf('<!-- Modal Update Status -->', modalStart);
    const behaviorStart = view.indexOf('window.updateNewAssetAge = function()');
    const behaviorEnd = view.indexOf('window.uploadAssetAttachment = async function', behaviorStart);
    const tableStart = view.indexOf('<div class="table-responsive master-asset-table-scroll dashboard-wo-scroll">');
    const tableClose = view.indexOf('</table>', tableStart);
    const printModalStart = view.indexOf('<div class="modal-overlay" id="modalMasterAssetPrint">');
    const printModalEnd = view.indexOf('<div class="modal-overlay" id="modalAssetLegalDocuments">', printModalStart);
    const assetBehaviorStart = view.indexOf('window.renderAssetTableBody = function()');
    const assetBehaviorEnd = view.indexOf('window.openRenameHullModal = function', assetBehaviorStart);
    if (modalStart < 0 || modalEnd < 0 || behaviorStart < 0 || behaviorEnd < 0) {
        throw new Error('Source formulir registrasi aset tidak ditemukan.');
    }
    if (/Tim Brian|dari tim Brian/i.test(view.slice(modalStart, modalEnd))) {
        throw new Error('Keterangan Tim Brian masih terdapat pada formulir runtime.');
    }
    if (!view.includes('<table class="table" id="assetTable">') || !view.includes('<th>No.</th>') || !view.includes('${rowIndex + 1}')) {
        throw new Error('Nomor urut otomatis tabel Master Asset tidak ditemukan.');
    }
    if (tableStart < 0 || tableClose < 0 || printModalStart < 0 || printModalEnd < 0 || assetBehaviorStart < 0 || assetBehaviorEnd < 0) throw new Error('Struktur tabel atau modal cetak Master Asset tidak ditemukan.');
    if (!view.includes('Cetak Laporan Master Asset') || !view.includes('<th>Serial Number</th><th>Pelat Kendaraan</th>')
        || !view.includes('<th>Usia</th>') || !view.includes('openAssetLegalDocuments')
        || !view.includes("assets/logo-pt-bina-rekayasa-anugrah.png")
        || !view.includes('class="page-footer"') || !view.includes('class="report-title"><h1>Laporan Master Asset</h1>')
        || !view.includes('id="masterAssetPrintUseCategory"') || !view.includes('id="masterAssetPrintUseStatus"')
        || !view.includes('id="masterAssetPrintUseSite"') || !view.includes('window.getMasterAssetPrintFilteredAssets = function()')) {
        throw new Error('Fitur laporan landscape atau popup legalitas/dokumentasi belum lengkap.');
    }
    const reportSource = view.slice(view.indexOf('window.printMasterAssetReport = function()'), view.indexOf('window.openRenameHullModal = function'));
    const reportColumnWidths = Array.from(reportSource.matchAll(/<col style="width:([0-9.]+)%">/g), match => Number(match[1]));
    const reportColumnWidthTotal = reportColumnWidths.reduce((total, width) => total + width, 0);
    if (!reportSource.includes('class="table-viewport"') || !reportSource.includes('@page { size: A4 landscape; margin: 5mm; }')
        || !reportSource.includes('body { width:min(297mm, 100%); min-height:210mm;')
        || !reportSource.includes('body { width:auto; min-height:0; margin:0; padding:0; box-shadow:none; }')
        || !reportSource.includes('td:nth-child(7) { white-space:normal; overflow-wrap:break-word; }')
        || reportColumnWidths.length !== 18 || Math.abs(reportColumnWidthTotal - 100) > 0.001) {
        throw new Error(`Layout A4 landscape Master Asset tidak valid: margin wajib 5 mm, ${reportColumnWidths.length} kolom, total lebar ${reportColumnWidthTotal}%.`);
    }

    const chrome = spawn(chromePath, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        '--remote-debugging-pipe', `--user-data-dir=${profileDir}`, 'about:blank'
    ], { stdio: ['ignore', 'ignore', 'ignore', 'pipe', 'pipe'], windowsHide: true });

    try {
        const cdp = connectPipe(chrome);
        const target = await cdp.send('Target.createTarget', { url: 'about:blank' });
        const attached = await cdp.send('Target.attachToTarget', { targetId: target.targetId, flatten: true });
        const sessionId = attached.sessionId;
        await cdp.send('Runtime.enable', {}, sessionId);
        await cdp.send('Page.enable', {}, sessionId);
        await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1180, height: 900, deviceScaleFactor: 1, mobile: false }, sessionId);
        const frameTree = await cdp.send('Page.getFrameTree', {}, sessionId);
        const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body>${view.slice(modalStart, modalEnd)}</body></html>`;
        await cdp.send('Page.setDocumentContent', { frameId: frameTree.frameTree.frame.id, html }, sessionId);
        await cdp.evaluate(sessionId, view.slice(behaviorStart, behaviorEnd));
        await cdp.evaluate(sessionId, "document.getElementById('modalNewAsset').style.display = 'flex'");

        const inspect = category => cdp.evaluate(sessionId, `(() => {
            const select = document.getElementById('na-cat');
            select.value = ${JSON.stringify(category)};
            window.updateNewAssetFormLayout();
            document.getElementById('na-year').value = '2022';
            window.updateNewAssetAge();
            const grid = document.querySelector('#modalNewAsset section[aria-labelledby="newAssetDataTitle"] .asset-registration-grid');
            const visibleFields = Array.from(grid.querySelectorAll(':scope > label:not([hidden])'));
            return {
                labels: visibleFields.map(field => field.querySelector(':scope > span:first-child').textContent.replace('*', '').trim()),
                mode: document.getElementById('na-form-mode').textContent,
                age: document.getElementById('na-age').value,
                template: getComputedStyle(document.querySelector('.asset-registration-grid')).gridTemplateColumns,
                columns: new Set(visibleFields.slice(1).map(el => Math.round(el.getBoundingClientRect().left))).size,
                legalTitle: document.getElementById('newAssetLegalTitle').textContent.trim()
            };
        })()`);

        const general = await inspect('Excavator');
        const dump = await inspect('Dump Truck');
        const expectedGeneral = ['Nama aset', 'Kode Unit', 'Status Warranty', 'Kapasitas', 'Dealer', 'Tahun', 'Serial number', 'Nomor lambung aktual', 'Project', 'Lokasi', 'Status', 'Lokasi invoice', 'Usia'];
        const expectedDump = ['Nama aset', 'Kode Unit', 'Status Warranty', 'Kapasitas', 'Tahun', 'Serial number / pelat kendaraan', 'Kode aktual', 'Project', 'Posisi', 'Status', 'Lokasi STNK', 'Lokasi BPKB', 'Nomor rangka', 'Tipe', 'Usia'];
        if (JSON.stringify(general.labels) !== JSON.stringify(expectedGeneral) || general.mode !== 'Formulir umum') {
            throw new Error(`Field umum tidak sesuai: ${JSON.stringify(general)}`);
        }
        if (JSON.stringify(dump.labels) !== JSON.stringify(expectedDump) || dump.mode !== 'Khusus Dump Truck') {
            throw new Error(`Field Dump Truck tidak sesuai: ${JSON.stringify(dump)}`);
        }
        if (general.columns !== 2 || dump.columns !== 2 || general.legalTitle !== 'Legalitas dan Dokumentasi') {
            throw new Error(`Layout desktop/legalitas tidak sesuai: ${JSON.stringify({ general, dump })}`);
        }
        if (Number(general.age) !== new Date().getFullYear() - 2022) throw new Error(`Usia otomatis tidak sesuai: ${general.age}`);

        await cdp.evaluate(sessionId, `(() => {
            document.getElementById('modalNewAsset').style.display = 'none';
            document.getElementById('modalEditAsset').style.display = 'flex';
        })()`);
        const inspectEdit = category => cdp.evaluate(sessionId, `(() => {
            const select = document.getElementById('ea-cat');
            select.value = ${JSON.stringify(category)};
            window.updateEditAssetFormLayout();
            document.getElementById('ea-year').value = '2022';
            window.updateEditAssetAge();
            const grid = document.querySelector('#modalEditAsset section[aria-labelledby="editAssetDataTitle"] .asset-registration-grid');
            const visibleFields = Array.from(grid.querySelectorAll(':scope > label:not([hidden])'));
            return {
                labels: visibleFields.map(field => field.querySelector(':scope > span:first-child').textContent.replace('*', '').trim()),
                mode: document.getElementById('ea-form-mode').textContent,
                age: document.getElementById('ea-age').value,
                columns: new Set(visibleFields.slice(1).map(el => Math.round(el.getBoundingClientRect().left))).size,
                sectionCount: document.querySelectorAll('#modalEditAsset .asset-registration-section').length,
                legalTitle: document.getElementById('editAssetLegalTitle').textContent.trim(),
                legalStep: document.getElementById('editAssetLegalTitle').previousElementSibling.textContent.trim(),
                hasPhotoEditor: Boolean(document.getElementById('ea-photos')),
                hasPdfEditor: Boolean(document.getElementById('ea-service-pdf')),
                hasCurrentDocuments: Boolean(document.getElementById('ea-document-status')),
                hullAuditAction: Boolean(document.querySelector('#modalEditAsset .asset-registration-field-action button'))
            };
        })()`);
        const editGeneral = await inspectEdit('Excavator');
        const editDump = await inspectEdit('Dump Truck');
        if (JSON.stringify(editGeneral.labels) !== JSON.stringify(expectedGeneral) || editGeneral.mode !== 'Formulir umum') {
            throw new Error(`Field edit umum tidak sesuai: ${JSON.stringify(editGeneral)}`);
        }
        if (JSON.stringify(editDump.labels) !== JSON.stringify(expectedDump) || editDump.mode !== 'Khusus Dump Truck') {
            throw new Error(`Field edit Dump Truck tidak sesuai: ${JSON.stringify(editDump)}`);
        }
        if (editGeneral.columns !== 2 || editDump.columns !== 2 || editGeneral.sectionCount !== 3
            || editGeneral.legalTitle !== 'Legalitas dan Dokumentasi' || editGeneral.legalStep !== '03'
            || !editGeneral.hasPhotoEditor || !editGeneral.hasPdfEditor || !editGeneral.hasCurrentDocuments
            || !editGeneral.hullAuditAction) {
            throw new Error(`Layout edit desktop tidak sesuai: ${JSON.stringify({ editGeneral, editDump })}`);
        }

        await cdp.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true }, sessionId);
        const mobileColumns = await cdp.evaluate(sessionId, "new Set(Array.from(document.querySelectorAll('#modalEditAsset section[aria-labelledby=\"editAssetDataTitle\"] .asset-registration-grid > label:not([hidden])')).slice(1).map(el => Math.round(el.getBoundingClientRect().left))).size");
        if (mobileColumns !== 1) throw new Error(`Layout mobile seharusnya satu kolom, aktual ${mobileColumns}.`);

        await cdp.send('Emulation.setDeviceMetricsOverride', { width: 820, height: 844, deviceScaleFactor: 1, mobile: false }, sessionId);
        const tableHtml = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body><section id="view-asset" class="view-section active" style="display:block">${view.slice(tableStart, tableClose + 8)}</section>${view.slice(printModalStart, printModalEnd)}</body></html>`;
        await cdp.send('Page.setDocumentContent', { frameId: frameTree.frameTree.frame.id, html: tableHtml }, sessionId);
        await cdp.evaluate(sessionId, `window.escapeHtml = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;'); window.openModal = id => { document.getElementById(id).style.display = 'flex'; }; window.closeModal = id => { document.getElementById(id).style.display = 'none'; }; window.openAssetActionCalloutFromElement = () => {}; window.openUnitProperties = () => {}; window.openSiteProperties = () => {}; window.openEditAssetModal = () => {}; window.openRenameHullModal = () => {}; window.formatAssetIdentityDetails = asset => ({ unitId: asset.id, hull: asset.hull_number, model: asset.make_model, year: String(asset.year_manufacture), sn: asset.serial_number, plate: asset.license_plate }); window.getAssetCompleteness = () => ({ isComplete: true, missing: [] }); window.getAssetStatusBadge = status => '<span class="badge">' + status + '</span>'; window.masterAssetFilters = { id:'', hull:'', model:'', sn:'', warranty:'', chassis:'', category:'', location:'', completeness:'', status:'', query3Param:'' }; window.masterAssetSort = { col:null, dir:'asc' };`);
        await cdp.evaluate(sessionId, view.slice(assetBehaviorStart, assetBehaviorEnd));
        const groupedRow = await cdp.evaluate(sessionId, `(async () => { const sample = { id:'DT-001', asset_code:'UNIT-DT-001', hull_number:'DT-001', make_model:'Dump Truck', year_manufacture:2022, asset_age:4, capacity:'20 ton', serial_number:'SN-001', license_plate:'B 1234 XY', chassis_number:'CH-001', category:'Dump Truck', warranty:'Aktif', project_name:'Project A', location:'Site A', operational_position:'Pit 1', status:'READY' }; window.currentAssetData = Array.from({length:8}, (_, index) => ({...sample, id:'DT-' + String(index + 1).padStart(3, '0')})); window.renderAssetTableBody(); await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))); const row = document.querySelector('#assetTableBody tr'); const viewport = document.querySelector('.master-asset-table-scroll'); const visibleRows = Array.from(document.querySelectorAll('#assetTableBody tr')).filter(item => item.getBoundingClientRect().top < viewport.getBoundingClientRect().bottom - 1).length; const edit = row.querySelector('.asset-row-actions .btn-primary'); return { cells: row.children.length, text: row.textContent, visibleRows, editText: edit.textContent.trim(), editBackground: getComputedStyle(edit).backgroundColor, printHasSeparateIdentity: window.printMasterAssetReport.toString().includes('<th>Serial Number</th><th>Pelat Kendaraan</th>'), printHasNewFields: window.printMasterAssetReport.toString().includes('<th>Kode Unit</th>') && window.printMasterAssetReport.toString().includes('<th>Kapasitas</th>') && window.printMasterAssetReport.toString().includes('<th>Status Warranty</th>') }; })()`);
        if (groupedRow.cells !== 9 || !groupedRow.text.includes('Usia') || !groupedRow.text.includes('4 tahun') || !groupedRow.text.includes('UNIT-DT-001') || !groupedRow.text.includes('20 ton') || !groupedRow.text.includes('Aktif') || !groupedRow.text.includes('Dokumen')
            || groupedRow.visibleRows < 1 || groupedRow.visibleRows > 5 || groupedRow.editText !== 'Edit'
            || groupedRow.editBackground === 'rgb(255, 255, 255)' || !groupedRow.printHasSeparateIdentity || !groupedRow.printHasNewFields) {
            throw new Error(`Baris tabel terkelompok atau laporan cetak tidak sesuai: ${JSON.stringify(groupedRow)}`);
        }
        const printFilters = await cdp.evaluate(sessionId, `(() => {
            window.currentAssetData = [
                { id:'EX-001', category:'Excavator', status:'READY', location:'Site A' },
                { id:'EX-002', category:'Excavator', status:'BREAKDOWN', location:'Site A' },
                { id:'DT-001', category:'Dump Truck', status:'READY', location:'Site B' },
                { id:'EX-003', category:'Excavator', status:'READY', location:'Site B' }
            ];
            window.openMasterAssetPrintDialog();
            document.querySelector('input[name="masterAssetPrintScope"][value="filtered"]').checked = true;
            document.getElementById('masterAssetPrintUseCategory').checked = true;
            document.getElementById('masterAssetPrintUseStatus').checked = true;
            document.getElementById('masterAssetPrintUseSite').checked = true;
            document.getElementById('masterAssetPrintCategory').value = 'Excavator';
            document.getElementById('masterAssetPrintStatus').value = 'READY';
            document.getElementById('masterAssetPrintSite').value = 'Site B';
            window.updateMasterAssetPrintFilters();
            const combined = window.getMasterAssetPrintFilteredAssets().map(asset => asset.id);
            const combinedCount = document.getElementById('masterAssetPrintFilteredCount').textContent;
            const panelVisible = !document.getElementById('masterAssetPrintFilters').hidden;
            const submitEnabled = !document.getElementById('masterAssetPrintSubmit').disabled;
            document.getElementById('masterAssetPrintUseSite').checked = false;
            window.updateMasterAssetPrintFilters();
            const withoutSite = window.getMasterAssetPrintFilteredAssets().map(asset => asset.id);
            return { combined, combinedCount, panelVisible, submitEnabled, withoutSite };
        })()`);
        if (JSON.stringify(printFilters.combined) !== JSON.stringify(['EX-003'])
            || printFilters.combinedCount !== '1 unit sesuai filter' || !printFilters.panelVisible || !printFilters.submitEnabled
            || JSON.stringify(printFilters.withoutSite) !== JSON.stringify(['EX-001', 'EX-003'])) {
            throw new Error(`Filter cetak prioritas tidak sesuai: ${JSON.stringify(printFilters)}`);
        }
        const tableLayout = await cdp.evaluate(sessionId, `(() => {
            const body = document.getElementById('assetTableBody');
            body.innerHTML = Array.from({ length: 30 }, (_, row) => '<tr>' + Array.from({ length: 9 }, (_, col) => '<td>Data unit panjang ' + (row + 1) + ' kolom ' + (col + 1) + '</td>').join('') + '</tr>').join('');
            const wrap = document.querySelector('.master-asset-table-scroll');
            const table = document.getElementById('assetTable');
            const header = document.getElementById('assetTableHeader');
            const firstCell = body.querySelector('td');
            const windowScrollBefore = window.scrollY;
            const heightBefore = wrap.getBoundingClientRect().height;
            wrap.scrollTop = 240;
            return {
                hasVerticalScroll: wrap.scrollHeight > wrap.clientHeight,
                internalScrollTop: wrap.scrollTop,
                windowScrollStable: window.scrollY === windowScrollBefore,
                stableHeight: Math.abs(wrap.getBoundingClientRect().height - heightBefore) < 1,
                hasHorizontalScroll: wrap.scrollWidth > wrap.clientWidth + 1,
                tableFits: table.getBoundingClientRect().width <= wrap.clientWidth + 1,
                headerPosition: getComputedStyle(header).position,
                headerPinned: Math.abs(header.getBoundingClientRect().top - wrap.getBoundingClientRect().top) <= 2,
                textAlign: getComputedStyle(firstCell).textAlign,
                verticalAlign: getComputedStyle(firstCell).verticalAlign,
                fontSize: parseFloat(getComputedStyle(table).fontSize)
            };
        })()`);
        if (!tableLayout.hasVerticalScroll || tableLayout.internalScrollTop <= 0 || !tableLayout.windowScrollStable || !tableLayout.stableHeight
            || tableLayout.hasHorizontalScroll || !tableLayout.tableFits
            || tableLayout.headerPosition !== 'sticky' || !tableLayout.headerPinned
            || tableLayout.textAlign !== 'left' || tableLayout.verticalAlign !== 'top'
            || tableLayout.fontSize < 12) {
            throw new Error(`Layout tabel Master Asset tidak sesuai: ${JSON.stringify(tableLayout)}`);
        }

        process.stdout.write('MASTER_ASSET_REGISTRATION_UI_SMOKE_OK\n');
    } finally {
        chrome.kill();
        if (chrome.exitCode === null) {
            await new Promise(resolve => chrome.once('exit', resolve));
        }
        try {
            fs.rmSync(profileDir, { recursive: true, force: true });
        } catch (error) {
            if (error.code !== 'EPERM') throw error;
        }
    }
}

main().catch(error => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exitCode = 1;
});
