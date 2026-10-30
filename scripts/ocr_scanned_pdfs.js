'use strict';

const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const { spawn } = require('node:child_process');

const projectRoot = path.join(__dirname, '..');
const vendorRoot = path.join(__dirname, 'vendor');
const runnerPath = path.join(__dirname, 'pdf_ocr_runner.html');
const chromePath = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

function parseArguments(argv) {
    const outputIndex = argv.indexOf('--output');
    const scaleIndex = argv.indexOf('--scale');
    const limitIndex = argv.indexOf('--limit-pages');
    if (outputIndex < 0 || !argv[outputIndex + 1]) throw new Error('Argumen --output wajib diisi.');
    const consumed = new Set([outputIndex, outputIndex + 1]);
    let scale = 3;
    let limitPages = 0;
    if (scaleIndex >= 0) {
        scale = Number(argv[scaleIndex + 1]);
        consumed.add(scaleIndex);
        consumed.add(scaleIndex + 1);
    }
    if (limitIndex >= 0) {
        limitPages = Number(argv[limitIndex + 1]);
        consumed.add(limitIndex);
        consumed.add(limitIndex + 1);
    }
    const pdfPaths = argv.filter((value, index) => !consumed.has(index) && !value.startsWith('--'));
    if (!pdfPaths.length) throw new Error('Minimal satu PDF wajib diberikan.');
    return { outputDirectory: path.resolve(argv[outputIndex + 1]), scale, limitPages, pdfPaths: pdfPaths.map(item => path.resolve(item)) };
}

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
            const callback = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) callback.reject(new Error(message.error.message));
            else callback.resolve(message.result || {});
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
        if (response.exceptionDetails) {
            const detail = response.exceptionDetails.exception?.description || response.exceptionDetails.text || 'Evaluasi browser gagal.';
            throw new Error(detail);
        }
        return response.result?.value;
    };
    return { send, evaluate };
}

function safeName(filePath) {
    return path.basename(filePath, path.extname(filePath)).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
}

function markdownEscapeFence(text) {
    return String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/````/g, '` ` ` `');
}

function createServer(pdfPaths) {
    const contentTypes = {
        '.html': 'text/html; charset=utf-8',
        '.js': 'application/javascript; charset=utf-8',
        '.wasm': 'application/wasm',
        '.gz': 'application/gzip',
        '.pdf': 'application/pdf'
    };
    return http.createServer((request, response) => {
        try {
            const requestUrl = new URL(request.url, 'http://127.0.0.1');
            let filePath;
            if (requestUrl.pathname === '/runner.html') filePath = runnerPath;
            else if (requestUrl.pathname.startsWith('/vendor/')) filePath = path.join(vendorRoot, path.basename(requestUrl.pathname));
            else if (requestUrl.pathname.startsWith('/pdf/')) {
                const index = Number(requestUrl.pathname.split('/').pop());
                filePath = pdfPaths[index];
            }
            if (!filePath || !fs.existsSync(filePath)) {
                response.writeHead(404);
                response.end('Not found');
                return;
            }
            const stat = fs.statSync(filePath);
            const range = request.headers.range;
            const contentType = contentTypes[path.extname(filePath).toLowerCase()] || 'application/octet-stream';
            if (range) {
                const match = /bytes=(\d+)-(\d*)/.exec(range);
                const start = Number(match[1]);
                const end = match[2] ? Number(match[2]) : stat.size - 1;
                response.writeHead(206, {
                    'Content-Type': contentType,
                    'Content-Length': end - start + 1,
                    'Content-Range': `bytes ${start}-${end}/${stat.size}`,
                    'Accept-Ranges': 'bytes'
                });
                fs.createReadStream(filePath, { start, end }).pipe(response);
                return;
            }
            response.writeHead(200, { 'Content-Type': contentType, 'Content-Length': stat.size, 'Accept-Ranges': 'bytes' });
            fs.createReadStream(filePath).pipe(response);
        } catch (error) {
            response.writeHead(500);
            response.end(error.message);
        }
    });
}

async function waitForReady(cdp, sessionId) {
    const deadline = Date.now() + 30000;
    while (Date.now() < deadline) {
        if (await cdp.evaluate(sessionId, 'window.ocrReady === true')) return;
        await new Promise(resolve => setTimeout(resolve, 250));
    }
    throw new Error('Halaman OCR tidak siap dalam 30 detik.');
}

async function runDocument(cdp, serverPort, pdfPath, pdfIndex, outputDirectory, scale, limitPages) {
    const target = await cdp.send('Target.createTarget', { url: `http://127.0.0.1:${serverPort}/runner.html` });
    const attached = await cdp.send('Target.attachToTarget', { targetId: target.targetId, flatten: true });
    const sessionId = attached.sessionId;
    await cdp.send('Runtime.enable', {}, sessionId);
    await cdp.send('Page.enable', {}, sessionId);
    await waitForReady(cdp, sessionId);

    const initialized = await cdp.evaluate(sessionId, `window.initializeOcr(${JSON.stringify(`/pdf/${pdfIndex}`)}, ${JSON.stringify(scale)})`);
    const totalPages = initialized.pages;
    const pagesToRun = limitPages > 0 ? Math.min(totalPages, limitPages) : totalPages;
    const outputPath = path.join(outputDirectory, `${safeName(pdfPath)}.md`);
    const assetDirectoryName = `${safeName(pdfPath)}_assets`;
    const assetDirectory = path.join(outputDirectory, assetDirectoryName);
    fs.mkdirSync(assetDirectory, { recursive: true });
    const stream = fs.createWriteStream(outputPath, { encoding: 'utf8' });
    stream.write(`# ${path.basename(pdfPath, path.extname(pdfPath))}\n\n`);
    stream.write(`Sumber: \`${path.basename(pdfPath)}\`\n\n`);
    stream.write(`Metode: OCR lokal Tesseract (Indonesia + Inggris), render PDF skala ${scale}. Jumlah halaman sumber: ${totalPages}.\n\n`);

    const confidences = [];
    for (let pageNumber = 1; pageNumber <= pagesToRun; pageNumber++) {
        const result = await cdp.evaluate(sessionId, `window.recognizePage(${pageNumber})`);
        confidences.push(Number(result.confidence) || 0);
        const imageName = `halaman_${String(pageNumber).padStart(3, '0')}.jpg`;
        const imageBase64 = String(result.imageDataUrl || '').replace(/^data:image\/jpeg;base64,/, '');
        fs.writeFileSync(path.join(assetDirectory, imageName), Buffer.from(imageBase64, 'base64'));
        stream.write(`## Halaman ${pageNumber}\n\n`);
        stream.write(`![Citra asli halaman ${pageNumber}](${assetDirectoryName}/${imageName})\n\n`);
        stream.write(`Confidence OCR: ${Number(result.confidence || 0).toFixed(2)}% · Render: ${result.width}×${result.height}px\n\n`);
        stream.write('````text\n');
        stream.write(markdownEscapeFence(result.text));
        if (!String(result.text || '').endsWith('\n')) stream.write('\n');
        stream.write('````\n\n');
        process.stdout.write(`[${path.basename(pdfPath)}] halaman ${pageNumber}/${pagesToRun}\n`);
    }
    await new Promise((resolve, reject) => stream.end(error => error ? reject(error) : resolve()));
    await cdp.evaluate(sessionId, 'window.shutdownOcr()');
    await cdp.send('Target.closeTarget', { targetId: target.targetId });
    return {
        pdf: path.basename(pdfPath),
        output: outputPath,
        sourcePages: totalPages,
        writtenPages: pagesToRun,
        averageConfidence: confidences.reduce((sum, value) => sum + value, 0) / Math.max(confidences.length, 1)
    };
}

async function main() {
    const options = parseArguments(process.argv.slice(2));
    for (const pdfPath of options.pdfPaths) {
        if (!fs.existsSync(pdfPath)) throw new Error(`PDF tidak ditemukan: ${pdfPath}`);
    }
    fs.mkdirSync(options.outputDirectory, { recursive: true });
    if (!fs.existsSync(chromePath)) throw new Error(`Chrome tidak ditemukan: ${chromePath}`);

    const server = createServer(options.pdfPaths);
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const serverPort = server.address().port;
    const profileDirectory = path.join(os.tmpdir(), `serviceplan-pdf-ocr-${process.pid}`);
    const chrome = spawn(chromePath, [
        '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
        '--remote-debugging-pipe', `--user-data-dir=${profileDirectory}`, 'about:blank'
    ], { stdio: ['ignore', 'ignore', 'pipe', 'pipe', 'pipe'], windowsHide: true });

    try {
        const cdp = connectPipe(chrome);
        const results = await Promise.all(options.pdfPaths.map((pdfPath, index) => (
            runDocument(cdp, serverPort, pdfPath, index, options.outputDirectory, options.scale, options.limitPages)
        )));
        process.stdout.write(`${JSON.stringify(results, null, 2)}\n`);
    } finally {
        server.close();
        if (!chrome.killed) chrome.kill();
    }
}

main().catch(error => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exitCode = 1;
});
