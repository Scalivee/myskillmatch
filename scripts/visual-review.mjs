// Chrome DevTools Protocol, Node built-ins only. No project dependencies required.
// Runs synthetic data in a unique SQLite file; working .env/database are never changed.
import { spawn, spawnSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { createServer } from 'node:net';

async function freePort() { return new Promise(resolve => { const socket = createServer(); socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); }); }); }
const port = await freePort();
const debugPort = await freePort();
const origin = `http://127.0.0.1:${port}`;

const output = resolve('storage/app/visual-review');
mkdirSync(output, { recursive: true });
const run = Date.now();
const database = resolve(output, `review-${run}.sqlite`);
writeFileSync(database, '');
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: origin, DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', SESSION_DRIVER: 'file', CACHE_STORE: 'array', QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'array', SKILLMATCH_DEMO: 'true', BCRYPT_ROUNDS: '4', SESSION_COOKIE: `visual_review_${run}` };
const php = process.env.PHP_BINARY || (process.platform === 'win32' ? spawnSync('php -r "echo PHP_BINARY;"', { shell: true, encoding: 'utf8', windowsHide: true }).stdout.trim() : 'php');
const fixture = spawnSync(php, ['tests/Support/visual-fixture.php'], { env, encoding: 'utf8', windowsHide: true });
if (fixture.status !== 0) throw new Error(String(fixture.error || '') + fixture.stderr + fixture.stdout);
console.log(fixture.stdout.trim());
// Own the actual PHP server process so cleanup cannot leave an artisan child listening.
const server = spawn(php, ['-S', `127.0.0.1:${port}`, resolve('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: resolve('public'), env, windowsHide: true, stdio: 'ignore' });
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', `--remote-debugging-port=${debugPort}`, `--user-data-dir=${resolve(output, `chrome-${run}`)}`, '--no-first-run', '--no-default-browser-check', '--disable-background-networking', 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function retry(fn) { for (let i = 0; i < 100; i++) { try { const result = await fn(); if (result) return result; } catch {} await delay(200); } throw new Error('Timed out waiting for local browser/server'); }
let ws;
const results = [];
const errors = [];
try {
    await retry(async () => (await fetch(`${origin}/login`)).ok);
    const targets = await retry(async () => (await fetch(`http://127.0.0.1:${debugPort}/json`)).json());
    ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve, { once: true }));
    let id = 0;
    const pending = new Map();
    ws.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.id) { const entry = pending.get(message.id); if (entry) { pending.delete(message.id); message.error ? entry.reject(new Error(JSON.stringify(message.error))) : entry.resolve(message.result); } }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text + ': ' + (message.params.exceptionDetails.exception?.description || ''));
        if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error') errors.push(message.params.entry.text);
    });
    function cdp(method, params = {}) { return new Promise((resolve, reject) => { const key = ++id; pending.set(key, { resolve, reject }); ws.send(JSON.stringify({ id: key, method, params })); }); }
    async function evaluate(expression) { const r = await cdp('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (r.exceptionDetails) throw new Error(JSON.stringify(r.exceptionDetails)); return r.result.value; }
    await cdp('Page.enable'); await cdp('Runtime.enable'); await cdp('Log.enable');
    async function navigate(path) {
        const loaded = new Promise((resolve, reject) => {
            const timeout = setTimeout(() => { ws.removeEventListener('message', listener); reject(new Error('Navigation timed out: ' + path)); }, 20000);
            function listener(event) { if (JSON.parse(event.data).method === 'Page.loadEventFired') { clearTimeout(timeout); ws.removeEventListener('message', listener); resolve(); } }
            ws.addEventListener('message', listener);
        });
        await cdp('Page.navigate', { url: `${origin}${path}` });
        await loaded;
        await retry(async () => await evaluate(`document.readyState === 'complete' && location.pathname === ${JSON.stringify(path.split('?')[0])}`));
        await evaluate('document.fonts.ready.then(() => true)');
        await delay(120);
    }
    async function login(role, admin = false) {
        await navigate(admin ? '/admin/login' : '/login');
        await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(role + '@visual.example.test')};document.querySelector('[name=password]').value='VisualReview123';document.querySelector('main form').requestSubmit();`);
        await retry(async () => await evaluate(`location.pathname === ${JSON.stringify(admin ? '/admin/dashboard' : '/volunteer/aktivitas')} || location.pathname === '/organizer/aktivitas'`));
    }
    async function logout(admin = false) {
        await evaluate(`document.querySelector('form[action$="/${admin ? 'admin/logout' : 'logout'}"]').requestSubmit()`);
        await retry(async () => await evaluate(`location.pathname === ${JSON.stringify(admin ? '/admin/login' : '/')}`));
    }
    async function capture(name, path) {
        for (const width of [375, 768, 1440]) {
            await cdp('Emulation.setDeviceMetricsOverride', { width, height: width === 375 ? 812 : 960, deviceScaleFactor: 1, mobile: false });
            if (path) await navigate(path); else await delay(150);
            const check = await evaluate(`({title:document.title, heading:document.querySelector('h1')?.innerText, width:innerWidth, scrollWidth:document.documentElement.scrollWidth, font:getComputedStyle(document.body).fontFamily, fontLoaded:document.fonts.check('16px "Plus Jakarta Sans"'), overflow:[...document.querySelectorAll('main *')].filter(e=>{const r=e.getBoundingClientRect();return r.right>innerWidth+1&&!e.closest('.table-wrap')}).slice(0,5).map(e=>e.tagName+'.'+e.className)})`);
            if (check.scrollWidth > width) throw new Error(`Horizontal overflow ${name} ${width}: ${JSON.stringify(check)}`);
            if (!check.heading || /^(500|503|419|404|403)$/.test(check.heading.trim())) throw new Error(`Unexpected page at ${name}: ${JSON.stringify(check)}`);
            if (!check.fontLoaded) throw new Error(`Font failed: ${name} ${JSON.stringify(check)} ${JSON.stringify(errors)} ${JSON.stringify(await evaluate("[...document.querySelectorAll('link')].map(e=>e.href)"))}`);
            const screenshot = await cdp('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
            writeFileSync(resolve(output, `${name}-${width}.png`), Buffer.from(screenshot.data, 'base64'));
            results.push({ name, ...check });
            console.log(`Captured ${name} at ${width}px`);
        }
    }
    await capture('landing', '/');
    await capture('catalog', '/events');
    await capture('catalog-empty', '/events?q=NoMatchingActivity');
    await capture('event', '/events/1');
    await capture('login', '/login');
    await cdp('Emulation.setDeviceMetricsOverride', { width: 375, height: 812, deviceScaleFactor: 1, mobile: false });
    await navigate('/login');
    await cdp('Network.enable');
    await cdp('Network.emulateNetworkConditions', { offline: false, latency: 700, downloadThroughput: -1, uploadThroughput: -1 });
    const processing = await evaluate(`document.querySelector('[name=email]').value='invalid@visual.example.test';document.querySelector('[name=password]').value='WrongPassword123';document.querySelector('main form').requestSubmit();document.querySelector('main form').getAttribute('aria-busy')==='true'`);
    if (!processing) throw new Error('Submit processing feedback missing');
    const processingImage = await cdp('Page.captureScreenshot', { format: 'png' });
    writeFileSync(resolve(output, 'login-processing-375.png'), Buffer.from(processingImage.data, 'base64'));
    await cdp('Network.emulateNetworkConditions', { offline: false, latency: 0, downloadThroughput: -1, uploadThroughput: -1 });
    await retry(async () => await evaluate(`Boolean(document.querySelector('[data-error-summary]')) && document.readyState==='complete'`));
    await capture('login-error', null);
    if (!await evaluate(`document.activeElement.hasAttribute('data-error-summary')`)) throw new Error('Validation summary was not focused');
    await login('volunteer');
    await capture('volunteer', '/volunteer/aktivitas');
    await capture('application-upload', '/applications/4');
    await capture('assessment', '/applications/1/assessment');
    await capture('profile', '/volunteer/profile');
    await logout();
    await login('new');
    await capture('onboarding', '/volunteer/aktivitas');
    await logout();
    await login('organizer');
    await capture('organizer', '/organizer/aktivitas');
    await capture('event-management', '/organizer/events/1');
    await capture('applicants', '/organizer/events/2/applicants');
    await capture('attendance', '/organizer/events/3/attendance');
    await capture('transactions', '/organizer/transactions');
    await logout();
    await capture('admin-login', '/admin/login');
    await login('admin', true);
    await capture('admin-dashboard', '/admin/dashboard');
    await capture('admin-empty', '/admin/dashboard?period=custom&start=2020-01-01&end=2020-01-07');
    for (const module of ['events', 'users', 'master', 'packages', 'transactions', 'settings', 'logs', 'account']) await capture(`admin-${module}`, `/admin/${module}`);
    await cdp('Emulation.setDeviceMetricsOverride', { width: 375, height: 812, deviceScaleFactor: 1, mobile: false });
    await navigate('/admin/dashboard');
    await evaluate(`document.querySelector('.menu-toggle').click()`);
    await delay(100);
    if (!await evaluate(`document.querySelector('.menu-toggle').getAttribute('aria-expanded')==='true' && document.querySelector('#admin-mobile').offsetHeight>0`)) throw new Error('Mobile menu did not open');
    await cdp('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' });
    if (!await evaluate(`document.querySelector('.menu-toggle').getAttribute('aria-expanded')==='false'`)) throw new Error('Escape did not close menu');
    await navigate('/admin/dashboard');
    await cdp('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    const focus = await evaluate('document.activeElement.textContent');
    if (!focus.includes('Lewati')) throw new Error('Skip link keyboard focus missing: ' + focus);
    writeFileSync(resolve(output, 'report.json'), JSON.stringify({ results, errors, keyboard: 'Skip link, mobile menu and Escape passed', externalServices: 'SMTP, scanner, gateway not exercised' }, null, 2));
    if (errors.length) throw new Error('Browser errors: ' + errors.join('\n'));
    console.log(`PASS: ${results.length} responsive captures, no body overflow or browser errors.`);
} finally {
    ws?.close();
    chrome.kill();
    server.kill();
}
