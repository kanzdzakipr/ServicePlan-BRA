'use strict';

const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const browser = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const origin = (process.env.BROWSER_TEST_BASE_URL || 'http://serviceplan-bra.test').replace(/\/$/, '');
const port = 9334;
const profile = path.join(os.tmpdir(), `serviceplan-contract-csv-${process.pid}`);
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

async function waitFor(check, timeout = 25000) {
    const until = Date.now() + timeout;
    while (Date.now() < until) {
        if (await check()) return;
        await delay(250);
    }
    throw new Error('Waktu tunggu browser habis.');
}

async function main() {
    if (!fs.existsSync(browser)) throw new Error(`Chrome tidak ditemukan: ${browser}`);
    const chrome = spawn(browser, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank',
    ], { stdio: 'ignore', windowsHide: true });
    let socket;
    try {
        await waitFor(async () => {
            try { return (await fetch(`http://127.0.0.1:${port}/json/version`)).ok; } catch { return false; }
        });
        const page = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent(origin + '/index.html')}`, { method: 'PUT' }).then(r => r.json());
        socket = new WebSocket(page.webSocketDebuggerUrl);
        await new Promise((resolve, reject) => {
            socket.addEventListener('open', resolve, { once: true });
            socket.addEventListener('error', reject, { once: true });
        });
        let id = 0;
        const pending = new Map();
        socket.addEventListener('message', event => {
            const result = JSON.parse(event.data);
            if (!pending.has(result.id)) return;
            const item = pending.get(result.id);
            pending.delete(result.id);
            result.error ? item.reject(new Error(result.error.message)) : item.resolve(result.result || {});
        });
        const send = (method, params = {}) => new Promise((resolve, reject) => {
            const key = ++id;
            pending.set(key, { resolve, reject });
            socket.send(JSON.stringify({ id: key, method, params }));
        });
        const evaluate = async expression => {
            const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
            if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'Evaluasi browser gagal.');
            return result.result?.value;
        };
        await send('Runtime.enable');
        await waitFor(() => evaluate("document.readyState === 'complete'"));
        const login = await evaluate(`(async () => {
            const session = await fetch('${origin}/api/auth.php?action=session').then(r => r.json());
            return fetch('${origin}/api/auth.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': session.csrf_token },
                body: JSON.stringify({ action: 'login', username: ${JSON.stringify(process.env.BROWSER_TEST_USER || 'admin')}, password: ${JSON.stringify(process.env.BROWSER_TEST_PASSWORD || 'admin123')} })
            }).then(r => r.json());
        })()`);
        if (login?.status !== 'success') throw new Error(login?.message || 'Login gagal.');
        await send('Page.navigate', { url: `${origin}/dashboard.php` });
        await waitFor(() => evaluate("typeof window.showView === 'function' && Boolean(window.globalData?.assets?.length)"));
        await evaluate("showView('productivity'); document.querySelector('[data-prod-tab=\"tab-contracts\"]').click();");
        await waitFor(() => evaluate("Boolean(document.getElementById('contractCsvFile'))"));
        await evaluate(`(() => {
            const asset = window.globalData.assets[0].id;
            const csv = 'contract_number,project,site,asset_id,start_date,end_date,target_hours,notes\\nQA-PREVIEW,Proyek Uji,Site Uji,' + asset + ',2035-01-01,2035-01-31,120,Preview';
            const file = new File([csv], 'kontrak-uji.csv', { type: 'text/csv' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            const input = document.getElementById('contractCsvFile');
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('contractPreviewButton').click();
        })()`);
        await waitFor(() => evaluate("document.getElementById('contractCsvFeedback')?.textContent?.includes('1 baris valid')"));
        const ready = await evaluate("!document.getElementById('contractImportButton').disabled");
        if (!ready) throw new Error('Tombol impor tidak aktif setelah pratinjau valid.');
        console.log('PASS: tab kontrak muncul, CSV valid dipratinjau lewat API, dan tombol impor aktif.');
    } finally {
        socket?.close();
        chrome.kill();
        await delay(500);
        if (path.resolve(profile).startsWith(path.resolve(os.tmpdir()))) {
            fs.rmSync(profile, { recursive: true, force: true, maxRetries: 3 });
        }
    }
}

main().catch(error => {
    console.error('FAIL:', error.message);
    process.exitCode = 1;
});
