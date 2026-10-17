'use strict';

const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const chromePath = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const projectRoot = path.join(__dirname, '..');
const profileDir = path.join(os.tmpdir(), `serviceplan-bra-new-wo-${process.pid}`);
const screenshotPath = path.join(os.tmpdir(), `serviceplan-bra-new-wo-${process.pid}.png`);

const delay = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));

function connectPipe(chrome) {
    const writePipe = chrome.stdio[3];
    const readPipe = chrome.stdio[4];
    let id = 0;
    const pending = new Map();
    let buffer = '';
    readPipe.setEncoding('utf8');
    readPipe.on('data', chunk => {
        buffer += chunk;
        let boundary;
        while ((boundary = buffer.indexOf('\0')) >= 0) {
            const payload = buffer.slice(0, boundary);
            buffer = buffer.slice(boundary + 1);
            if (!payload) continue;
            const message = JSON.parse(payload);
            if (!message.id || !pending.has(message.id)) continue;
            const promise = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) promise.reject(new Error(message.error.message));
            else promise.resolve(message.result || {});
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
        const response = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true }, sessionId);
        if (response.exceptionDetails) throw new Error(response.exceptionDetails.text || 'Evaluasi browser gagal.');
        return response.result?.value;
    };
    return { send, evaluate };
}

async function waitForCondition(cdp, sessionId, expression, timeout = 20000) {
    const deadline = Date.now() + timeout;
    while (Date.now() < deadline) {
        if (await cdp.evaluate(sessionId, expression)) return;
        await delay(250);
    }
    throw new Error(`Kondisi tidak terpenuhi: ${expression}`);
}

async function main() {
    if (!fs.existsSync(chromePath)) throw new Error(`Chrome tidak ditemukan: ${chromePath}`);
    const chrome = spawn(chromePath, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        '--allow-file-access-from-files', '--remote-debugging-pipe',
        `--user-data-dir=${profileDir}`, 'about:blank'
    ], { stdio: ['ignore', 'ignore', 'ignore', 'pipe', 'pipe'], windowsHide: true });

    try {
        const cdp = connectPipe(chrome);
        const target = await cdp.send('Target.createTarget', { url: 'about:blank' });
        const attached = await cdp.send('Target.attachToTarget', { targetId: target.targetId, flatten: true });
        const sessionId = attached.sessionId;
        await cdp.send('Runtime.enable', {}, sessionId);
        await cdp.send('Page.enable', {}, sessionId);

        const dashboardHtml = fs.readFileSync(path.join(projectRoot, 'dashboard.html'), 'utf8');
        const dashboardCss = fs.readFileSync(path.join(projectRoot, 'scripts', 'dashboard.css'), 'utf8');
        const dashboardJs = fs.readFileSync(path.join(projectRoot, 'scripts', 'dashboard.js'), 'utf8');
        const modalStart = dashboardHtml.indexOf('<!-- Modal Lapor Breakdown / WO -->');
        const modalEnd = dashboardHtml.indexOf('<!-- Modal Reject Approval -->', modalStart);
        const moduleStart = dashboardJs.indexOf('/* Work Order / Job Order creation');
        const moduleEnd = dashboardJs.indexOf('// SPARE PART & LOGISTICS', moduleStart);
        if (modalStart < 0 || modalEnd < 0 || moduleStart < 0 || moduleEnd < 0) {
            throw new Error('Source modal atau module WO tidak ditemukan.');
        }

        const componentHtml = `<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><style>${dashboardCss}</style></head><body>${dashboardHtml.slice(modalStart, modalEnd)}</body></html>`;
        const frameTree = await cdp.send('Page.getFrameTree', {}, sessionId);
        await cdp.send('Page.setDocumentContent', { frameId: frameTree.frameTree.frame.id, html: componentHtml }, sessionId);
        await cdp.evaluate(sessionId, `(() => {
            window.openModal = id => { const modal = document.getElementById(id); modal.classList.add('active'); modal.style.display = 'flex'; };
            window.closeModal = id => { const modal = document.getElementById(id); modal.classList.remove('active'); modal.style.display = 'none'; };
        })()`);
        await cdp.evaluate(sessionId, dashboardJs.slice(moduleStart, moduleEnd));
        await waitForCondition(cdp, sessionId, "document.readyState !== 'loading' && Boolean(window.NewWorkOrderForm) && typeof window.openModal === 'function'");

        await cdp.evaluate(sessionId, `(() => {
            window.globalData = { assets: [{
                id: 'EXC-0099', asset_code: 'EXC-0099', hull_number: 'BRA-EX-27',
                category: 'Excavator', make_model: 'Komatsu PC200-10M0', serial_number: 'KMTPC20099',
                license_plate: 'BM 9099 BRA', location_name: 'Pit Barat Front 3', last_hm_km: 8421.7,
                status: 'READY'
            }], work_orders: [] };
            window.NewWorkOrderForm.open('EXC-0099');
            document.getElementById('nwo-forced-operation').checked = true;
            window.toggleNewWOMitigation(true);
            document.getElementById('nwo-mechanic-input').value = 'Apeng (Lead Mechanic)';
            window.addNewWOMechanic();
        })()`);

        const desktop = await cdp.evaluate(sessionId, `(() => ({
            visible: document.getElementById('modalNewWO').classList.contains('active'),
            category: document.getElementById('nwo-equipment-type').value,
            hull: document.getElementById('nwo-hull-number').value,
            location: document.getElementById('nwo-location').value,
            hm: document.getElementById('nwo-hm-km').value,
            mitigationVisible: !document.getElementById('nwo-mitigation-details').hidden,
            mechanicCount: document.querySelectorAll('#nwo-mechanic-chips .wo-chip').length,
            columns: getComputedStyle(document.querySelector('.wo-form-grid')).gridTemplateColumns.split(' ').length
        }))()`);

        if (!desktop.visible || desktop.category !== 'Excavator' || desktop.hull !== 'BRA-EX-27'
            || desktop.location !== 'Pit Barat Front 3' || desktop.hm !== '8421.7'
            || !desktop.mitigationVisible || desktop.mechanicCount !== 1 || desktop.columns !== 2) {
            throw new Error(`Desktop form tidak sesuai: ${JSON.stringify(desktop)}`);
        }

        const screenshot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }, sessionId);
        fs.writeFileSync(screenshotPath, Buffer.from(screenshot.data, 'base64'));

        await cdp.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true }, sessionId);
        await delay(350);
        const mobileColumns = await cdp.evaluate(sessionId, "getComputedStyle(document.querySelector('.wo-form-grid')).gridTemplateColumns.split(' ').length");
        if (mobileColumns !== 1) throw new Error(`Grid mobile masih ${mobileColumns} kolom.`);

        console.log(`PASS: modal WO terlihat, autofill lengkap, mitigasi aktif, chip PIC bekerja, dan grid mobile 1 kolom.`);
        console.log(`SCREENSHOT: ${screenshotPath}`);
    } finally {
        chrome.kill();
        await delay(300);
        if (fs.existsSync(profileDir) && path.dirname(profileDir) === os.tmpdir()) {
            fs.rmSync(profileDir, { recursive: true, force: true });
        }
    }
}

main().catch(error => {
    console.error(`FAIL: ${error.message}`);
    process.exitCode = 1;
});
