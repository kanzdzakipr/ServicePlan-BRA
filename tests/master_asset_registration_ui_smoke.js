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
        const response = await send('Runtime.evaluate', { expression, returnByValue: true }, sessionId);
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
    const modalEnd = view.indexOf('<!-- Modal Edit Aset -->', modalStart);
    const behaviorStart = view.indexOf('window.updateNewAssetAge = function()');
    const behaviorEnd = view.indexOf('window.uploadAssetAttachment = async function', behaviorStart);
    if (modalStart < 0 || modalEnd < 0 || behaviorStart < 0 || behaviorEnd < 0) {
        throw new Error('Source formulir registrasi aset tidak ditemukan.');
    }
    if (/Tim Brian|dari tim Brian/i.test(view.slice(modalStart, modalEnd))) {
        throw new Error('Keterangan Tim Brian masih terdapat pada formulir runtime.');
    }
    if (!view.includes('<th style="width:54px; text-align:center;">No.</th>') || !view.includes('${rowIndex + 1}')) {
        throw new Error('Nomor urut otomatis tabel Master Asset tidak ditemukan.');
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
            return {
                labels: Array.from(document.querySelectorAll('.asset-registration-grid > label:not([hidden]) > span:first-child')).map(el => el.textContent.replace('*', '').trim()),
                mode: document.getElementById('na-form-mode').textContent,
                age: document.getElementById('na-age').value,
                template: getComputedStyle(document.querySelector('.asset-registration-grid')).gridTemplateColumns,
                columns: new Set(Array.from(document.querySelectorAll('.asset-registration-grid > label:not([hidden])')).slice(1).map(el => Math.round(el.getBoundingClientRect().left))).size,
                legalTitle: document.getElementById('newAssetLegalTitle').textContent.trim()
            };
        })()`);

        const general = await inspect('Excavator');
        const dump = await inspect('Dump Truck');
        const expectedGeneral = ['Nama aset', 'Dealer', 'Tahun', 'Serial number', 'Nomor lambung aktual', 'Project', 'Lokasi', 'Status', 'Lokasi invoice', 'Usia'];
        const expectedDump = ['Nama aset', 'Tahun', 'Serial number / pelat kendaraan', 'Kode aktual', 'Project', 'Posisi', 'Status', 'Lokasi STNK', 'Lokasi BPKB', 'Nomor rangka', 'Tipe', 'Usia'];
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

        await cdp.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true }, sessionId);
        const mobileColumns = await cdp.evaluate(sessionId, "new Set(Array.from(document.querySelectorAll('.asset-registration-grid > label:not([hidden])')).slice(1).map(el => Math.round(el.getBoundingClientRect().left))).size");
        if (mobileColumns !== 1) throw new Error(`Layout mobile seharusnya satu kolom, aktual ${mobileColumns}.`);

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
