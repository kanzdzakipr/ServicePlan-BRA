'use strict';

const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const chromePath = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const baseUrl = (process.env.BROWSER_TEST_BASE_URL || 'http://serviceplan-bra.test').replace(/\/$/, '');
const username = process.env.BROWSER_TEST_USER || 'admin';
const password = process.env.BROWSER_TEST_PASSWORD || 'admin123';
const port = 9333;
const profileDir = path.join(os.tmpdir(), `serviceplan-bra-browser-smoke-${process.pid}`);

function delay(milliseconds) {
    return new Promise(resolve => setTimeout(resolve, milliseconds));
}

async function waitForDebugger() {
    for (let attempt = 0; attempt < 60; attempt++) {
        try {
            const response = await fetch(`http://127.0.0.1:${port}/json/version`);
            if (response.ok) return;
        } catch (_) { }
        await delay(250);
    }
    throw new Error('Chrome DevTools tidak siap.');
}

async function createPage() {
    const response = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent(baseUrl + '/index.html')}`, {
        method: 'PUT',
    });
    if (!response.ok) throw new Error(`Gagal membuat tab Chrome: HTTP ${response.status}`);
    return response.json();
}

async function connectCdp(webSocketDebuggerUrl) {
    const socket = new WebSocket(webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true });
        socket.addEventListener('error', reject, { once: true });
    });

    let id = 0;
    const pending = new Map();
    const waiters = new Map();
    const exceptions = [];
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.id && pending.has(message.id)) {
            const { resolve, reject } = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) reject(new Error(message.error.message));
            else resolve(message.result || {});
            return;
        }
        if (message.method === 'Runtime.exceptionThrown') {
            const details = message.params?.exceptionDetails;
            exceptions.push(details?.exception?.description || details?.text || 'JavaScript exception');
        }
        const queue = waiters.get(message.method);
        if (queue?.length) queue.shift()(message.params || {});
    });

    function send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const commandId = ++id;
            pending.set(commandId, { resolve, reject });
            socket.send(JSON.stringify({ id: commandId, method, params }));
        });
    }

    function waitEvent(method, timeout = 20000) {
        return new Promise((resolve, reject) => {
            const queue = waiters.get(method) || [];
            queue.push(resolve);
            waiters.set(method, queue);
            const timer = setTimeout(() => {
                const current = waiters.get(method) || [];
                const index = current.indexOf(resolve);
                if (index >= 0) current.splice(index, 1);
                reject(new Error(`Timeout menunggu ${method}`));
            }, timeout);
            const originalResolve = resolve;
            queue[queue.length - 1] = value => {
                clearTimeout(timer);
                originalResolve(value);
            };
        });
    }

    async function evaluate(expression) {
        const result = await send('Runtime.evaluate', {
            expression,
            awaitPromise: true,
            returnByValue: true,
        });
        if (result.exceptionDetails) {
            throw new Error(result.exceptionDetails.text || 'Evaluasi JavaScript gagal.');
        }
        return result.result?.value;
    }

    return { socket, send, waitEvent, evaluate, exceptions };
}

async function waitForCondition(cdp, expression, timeout = 25000) {
    const deadline = Date.now() + timeout;
    while (Date.now() < deadline) {
        if (await cdp.evaluate(expression)) return;
        await delay(300);
    }
    throw new Error(`Kondisi browser tidak terpenuhi: ${expression}`);
}

async function main() {
    if (!fs.existsSync(chromePath)) throw new Error(`Chrome tidak ditemukan: ${chromePath}`);
    const chrome = spawn(chromePath, [
        '--headless=new',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        `--remote-debugging-port=${port}`,
        `--user-data-dir=${profileDir}`,
        'about:blank',
    ], { stdio: 'ignore', windowsHide: true });

    try {
        await waitForDebugger();
        const page = await createPage();
        const cdp = await connectCdp(page.webSocketDebuggerUrl);
        await cdp.send('Runtime.enable');
        await cdp.send('Page.enable');

        await waitForCondition(cdp, "document.readyState === 'complete'");
        const login = await cdp.evaluate(`(async () => {
            const session = await fetch('${baseUrl}/api/auth.php?action=session').then(response => response.json());
            return fetch('${baseUrl}/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': session.csrf_token },
                body: JSON.stringify({ action: 'login', username: ${JSON.stringify(username)}, password: ${JSON.stringify(password)} })
            }).then(response => response.json());
        })()`);
        if (login?.status !== 'success') throw new Error(login?.message || 'Login browser gagal.');

        const loaded = cdp.waitEvent('Page.loadEventFired');
        await cdp.send('Page.navigate', { url: `${baseUrl}/dashboard.php` });
        await loaded;
        await waitForCondition(cdp, "typeof window.openWoDetailModal === 'function' && typeof window.showView === 'function'");
        await cdp.evaluate("showView('wo')");
        await waitForCondition(cdp, "Boolean(document.querySelector('.kanban-card[data-wo-id] button[title=\"Buka detail Work Order\"]'))");

        const clickedWo = await cdp.evaluate(`(() => {
            const button = document.querySelector('.kanban-card[data-wo-id] button[title="Buka detail Work Order"]');
            const card = button.closest('.kanban-card[data-wo-id]');
            button.click();
            return card.dataset.woId;
        })()`);
        await waitForCondition(cdp, "document.getElementById('modalWoDetail')?.classList.contains('active')");
        await delay(700);
        const modal = await cdp.evaluate(`(() => {
            const element = document.getElementById('modalWoDetail');
            return {
                active: element.classList.contains('active'),
                display: getComputedStyle(element).display,
                visible: element.getBoundingClientRect().width > 0 && element.getBoundingClientRect().height > 0,
                woId: document.getElementById('modalWoId')?.textContent?.trim() || '',
                issue: document.getElementById('modalWoIssue')?.textContent?.trim() || ''
            };
        })()`);

        if (!modal.active || modal.display === 'none' || !modal.visible) {
            throw new Error(`Modal detail tidak terlihat: ${JSON.stringify(modal)}`);
        }
        if (modal.woId !== clickedWo || modal.issue === '') {
            throw new Error(`Isi modal detail tidak sesuai: ${JSON.stringify(modal)}`);
        }

        await cdp.evaluate("document.getElementById('modalWoSpbBtn').click()");
        await waitForCondition(cdp, "document.getElementById('modalWoSupport')?.classList.contains('active')");
        await waitForCondition(cdp, "document.getElementById('supportLoading')?.style.display === 'none'");
        const supportModal = await cdp.evaluate(`(() => {
            const element = document.getElementById('modalWoSupport');
            return {
                active: element.classList.contains('active'),
                display: getComputedStyle(element).display,
                visible: element.getBoundingClientRect().width > 0 && element.getBoundingClientRect().height > 0,
                woId: document.getElementById('supportWoTitle')?.textContent?.trim() || ''
            };
        })()`);
        if (!supportModal.active || supportModal.display === 'none' || !supportModal.visible || supportModal.woId !== clickedWo) {
            throw new Error(`Modal kebutuhan dan layanan tidak terlihat: ${JSON.stringify(supportModal)}`);
        }

        await cdp.evaluate("openPartRequestFromSupport()")
        await waitForCondition(cdp, "Boolean(document.querySelector('.sl-modal-overlay .sl-spb-builder'))");
        await waitForCondition(cdp, "typeof window.SpareLogistics === 'object'");
        await cdp.evaluate("document.querySelector('[data-sl-action=\"add-draft\"]').click()")
        await waitForCondition(cdp, "document.querySelectorAll('[data-sl-master-part] option').length > 2");
        const selectedPart = await cdp.evaluate(`(() => {
            const select = document.querySelector('[data-sl-master-part]');
            const option = Array.from(select.options).find(item => item.value && item.value !== '__MANUAL__');
            if (!option) return null;
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            const row = document.querySelector('[data-sl-draft-row="0"]');
            return {
                selected: option.value,
                partNumber: row?.querySelector('[data-sl-draft-field="partNumber"]')?.value || '',
                description: row?.querySelector('[data-sl-draft-field="description"]')?.value || '',
                uom: row?.querySelector('[data-sl-draft-field="uom"]')?.value || '',
                partNumberReadOnly: Boolean(row?.querySelector('[data-sl-draft-field="partNumber"]')?.readOnly)
            };
        })()`);
        if (!selectedPart || selectedPart.partNumber !== selectedPart.selected || !selectedPart.description || !selectedPart.uom || !selectedPart.partNumberReadOnly) {
            throw new Error(`Master Part tidak mengisi draft SPB secara otomatis: ${JSON.stringify(selectedPart)}`);
        }

        await cdp.evaluate(`(() => {
            const nativeFetch = window.fetch.bind(window);
            window.fetch = (input, options = {}) => {
                if (String(input).includes('api/logistics.php') && options.method === 'POST') {
                    return Promise.resolve(new Response(JSON.stringify({ status: 'success' }), {
                        status: 200,
                        headers: { 'Content-Type': 'application/json' }
                    }));
                }
                return nativeFetch(input, options);
            };
            document.querySelector('[data-sl-action="submit"]').click();
        })()`);
        await waitForCondition(cdp, "!document.querySelector('.sl-modal-overlay')");

        console.log(`PASS: detail WO ${modal.woId}, auto-fill ${selectedPart.partNumber}, dan form SPB tertutup setelah pengajuan berhasil.`);
        if (cdp.exceptions.length) {
            console.log(`INFO: exception lain selama pemuatan dashboard: ${cdp.exceptions.join(' | ')}`);
        }
        cdp.socket.close();
    } finally {
        chrome.kill();
        await delay(500);
        if (path.resolve(profileDir).startsWith(path.resolve(os.tmpdir()))) {
            fs.rmSync(profileDir, { recursive: true, force: true, maxRetries: 3 });
        }
    }
}

main().catch(error => {
    console.error('FAIL:', error.message);
    process.exitCode = 1;
});

