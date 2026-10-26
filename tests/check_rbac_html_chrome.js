'use strict';

const { spawn } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const profileDir = path.join(os.tmpdir(), 'chrome-test-rbac-html-' + Date.now());

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
            let message;
            try { message = JSON.parse(payload); } catch (e) { continue; }
            if (message.method === 'Runtime.consoleAPICalled') {
                const args = (message.params.args || []).map(a => a.value || a.description).join(' ');
                console.log('[BROWSER CONSOLE]', message.params.type, args);
            }
            if (message.method === 'Runtime.exceptionThrown') {
                console.error('[BROWSER EXCEPTION]', JSON.stringify(message.params.exceptionDetails, null, 2));
            }
            if (!message.id || !pending.has(message.id)) continue;
            const promise = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) promise.reject(new Error(message.error.message));
            else promise.resolve(message.result || {});
        }
    });
    const send = (method, params, sessionId) => new Promise((resolve, reject) => {
        const commandId = ++id;
        pending.set(commandId, { resolve, reject });
        const message = { id: commandId, method, params: params || {} };
        if (sessionId) message.sessionId = sessionId;
        writePipe.write(JSON.stringify(message) + '\0');
    });
    const evaluate = async (sessionId, expression) => {
        const response = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true }, sessionId);
        if (response.exceptionDetails) {
            console.error('[EVAL EXCEPTION]', response.exceptionDetails);
            throw new Error(response.exceptionDetails.text || 'Evaluasi browser gagal.');
        }
        return response.result ? response.result.value : undefined;
    };
    return { send, evaluate };
}

async function main() {
    console.log('Testing dashboard.html directly in Chrome...');
    const chrome = spawn(chromePath, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        '--remote-debugging-pipe',
        '--user-data-dir=' + profileDir, 'about:blank'
    ], { stdio: ['ignore', 'ignore', 'ignore', 'pipe', 'pipe'], windowsHide: true });

    try {
        const cdp = connectPipe(chrome);
        const target = await cdp.send('Target.createTarget', { url: 'about:blank' });
        const attached = await cdp.send('Target.attachToTarget', { targetId: target.targetId, flatten: true });
        const sessionId = attached.sessionId;
        await cdp.send('Runtime.enable', {}, sessionId);
        await cdp.send('Page.enable', {}, sessionId);

        // Login first via index.html so cookie is set
        await cdp.send('Page.navigate', { url: 'http://localhost/ServicePlan-BRA/index.html' }, sessionId);
        await new Promise(r => setTimeout(r, 1500));
        await cdp.evaluate(sessionId, `(() => {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            document.getElementById('loginForm').dispatchEvent(new Event('submit'));
        })()`);
        await new Promise(r => setTimeout(r, 2000));

        // Now navigate to dashboard.html
        console.log('Navigating to dashboard.html...');
        await cdp.send('Page.navigate', { url: 'http://localhost/ServicePlan-BRA/dashboard.html' }, sessionId);
        await new Promise(r => setTimeout(r, 3000));

        const res = await cdp.evaluate(sessionId, `(() => {
            if (typeof window.showView === 'function') {
                window.showView('settings');
            }
            const table = document.getElementById('rbacMatrixTable');
            return {
                url: window.location.href,
                tableRows: table ? table.querySelectorAll('tr').length : 0,
                tableHtml: table ? table.innerHTML.substring(0, 200) : ''
            };
        })()`);

        console.log('Result for dashboard.html:', JSON.stringify(res, null, 2));

    } catch (err) {
        console.error('Test error:', err);
    } finally {
        chrome.kill();
        try { fs.rmdirSync(profileDir, { recursive: true }); } catch (e) {}
    }
}

main().then(() => {
    process.exit(0);
}).catch(err => {
    console.error(err);
    process.exit(1);
});
