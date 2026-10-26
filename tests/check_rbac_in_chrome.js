'use strict';

const { spawn } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const profileDir = path.join(os.tmpdir(), 'chrome-test-rbac-' + Date.now());

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
    console.log('Launching headless Chrome...');
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

        console.log('Navigating to index.html...');
        await cdp.send('Page.navigate', { url: 'http://localhost/ServicePlan-BRA/index.html' }, sessionId);

        // Wait for login page
        await new Promise(r => setTimeout(r, 1500));

        console.log('Submitting login form as admin / admin123...');
        await cdp.evaluate(sessionId, `(() => {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            document.getElementById('loginForm').dispatchEvent(new Event('submit'));
        })()`);

        // Wait for redirection to dashboard.php
        console.log('Waiting for dashboard.php...');
        let currentUrl = '';
        for (let i = 0; i < 30; i++) {
            await new Promise(r => setTimeout(r, 300));
            currentUrl = await cdp.evaluate(sessionId, 'window.location.href');
            if (currentUrl && currentUrl.includes('dashboard.php')) break;
        }
        console.log('Current URL after login:', currentUrl);

        // Wait 3 seconds for dashboard scripts to initialize
        await new Promise(r => setTimeout(r, 3000));

        console.log('Navigating to Settings view in dashboard...');
        const navSettingsResult = await cdp.evaluate(sessionId, `(() => {
            if (typeof window.showView === 'function') {
                window.showView('settings');
                return 'showView(settings) executed';
            }
            return 'showView not found';
        })()`);
        console.log('Navigation to settings result:', navSettingsResult);

        await new Promise(r => setTimeout(r, 1000));

        console.log('Checking RBAC DOM and rendering in dashboard.php...');
        const rbacCheck = await cdp.evaluate(sessionId, `(() => {
            const res = {};
            res.url = window.location.href;
            res.hasFleetRBAC = Boolean(window.FleetRBAC);
            res.hasSwitchSettingsTab = typeof window.switchSettingsTab === 'function';
            res.hasRenderRbacMatrix = typeof window.renderRbacMatrix === 'function';
            res.serverRbacMatrix = window.SERVER_RBAC_MATRIX;
            
            const table = document.getElementById('rbacMatrixTable');
            res.tableFound = Boolean(table);
            res.tableRows = table ? table.querySelectorAll('tr').length : 0;
            res.tableHtmlPreview = table ? table.innerHTML.substring(0, 300) : '';

            const filterBox = document.getElementById('rbacRoleFilterButtons');
            res.filterButtonsCount = filterBox ? filterBox.children.length : 0;

            const summaryBox = document.getElementById('rbacRoleSummaryBox');
            res.summaryBoxHtmlPreview = summaryBox ? summaryBox.innerHTML.substring(0, 200) : '';

            const settingsRbacPanel = document.getElementById('settings-rbac');
            res.settingsRbacDisplay = settingsRbacPanel ? settingsRbacPanel.style.display : 'not found';

            // Now let's explicitly invoke switchSettingsTab('rbac') to see what happens
            try {
                if (window.switchSettingsTab) {
                    window.switchSettingsTab('rbac');
                }
                res.afterSwitchTableRows = table ? table.querySelectorAll('tr').length : 0;
                res.afterSwitchPanelDisplay = settingsRbacPanel ? settingsRbacPanel.style.display : 'not found';
                res.afterSwitchHtml = table ? table.innerHTML.substring(0, 300) : '';
            } catch (err) {
                res.switchError = err.message + ' | ' + err.stack;
            }

            return res;
        })()`);

        console.log('RBAC Check Results:\n', JSON.stringify(rbacCheck, null, 2));

    } catch (err) {
        console.error('Test error:', err);
    } finally {
        chrome.kill();
        try { fs.rmdirSync(profileDir, { recursive: true }); } catch (e) {}
    }
}

main().then(() => {
    console.log('Done.');
    process.exit(0);
}).catch(err => {
    console.error(err);
    process.exit(1);
});
