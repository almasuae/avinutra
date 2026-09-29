// Browser test: every public page (and the CRM login) must fit a 390 px phone screen
// (no horizontal scroll) and load without Content-Security-Policy violations or
// JavaScript errors.
//
//   node --experimental-websocket scripts/check-overflow.mjs http://127.0.0.1:8766
//
// Crawls same-origin links from the home page (the CRM and /dev pages excluded) in
// headless Edge or Chrome, through the DevTools protocol (no npm dependency). Starts
// its own browser process and stops only that process. Exit code 1 lists the pages
// that are wider than the viewport and the elements that stick out.
import { spawn } from 'node:child_process';
import { existsSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const BASE = (process.argv[2] ?? 'http://127.0.0.1:8766').replace(/\/$/, '');
const WIDTH = Number(process.env.OVERFLOW_WIDTH ?? 390);
const MAX_PAGES = 250;
const PORT = 9335;

const candidates = [
    process.env.BROWSER_PATH,
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/microsoft-edge',
].filter(Boolean);
const executable = candidates.find((path) => existsSync(path));
if (!executable) {
    console.error('No Edge/Chrome found. Set BROWSER_PATH.');
    process.exit(2);
}

const browser = spawn(executable, ['--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${mkdtempSync(join(tmpdir(), 'overflow-'))}`, '--no-first-run', '--hide-scrollbars', 'about:blank'], { stdio: 'ignore' });
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const stop = (code) => { browser.kill(); process.exit(code); };

let targets = [];
for (let i = 0; i < 75 && targets.length === 0; i++) {
    try { targets = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).filter((t) => t.type === 'page'); } catch { /* starting */ }
    await sleep(200);
}
if (targets.length === 0) { console.error('The browser did not start.'); stop(2); }

const ws = new WebSocket(targets[0].webSocketDebuggerUrl);
await new Promise((resolve) => ws.addEventListener('open', resolve));
let id = 0;
const pending = new Map();
const waiters = [];
ws.addEventListener('message', (event) => {
    const message = JSON.parse(event.data);
    if (message.id && pending.has(message.id)) {
        const { resolve, reject } = pending.get(message.id);
        pending.delete(message.id);
        message.error ? reject(new Error(JSON.stringify(message.error))) : resolve(message.result);
    } else {
        waiters.forEach((waiter) => waiter(message));
    }
});
const send = (method, params = {}) => new Promise((resolve, reject) => { const i = ++id; pending.set(i, { resolve, reject }); ws.send(JSON.stringify({ id: i, method, params })); });
const loaded = () => new Promise((resolve) => { const waiter = (m) => { if (m.method === 'Page.loadEventFired') { waiters.splice(waiters.indexOf(waiter), 1); resolve(); } }; waiters.push(waiter); });
const evaluate = async (expression) => (await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;

await send('Page.enable');
await send('Runtime.enable');
await send('Log.enable');

// Console problems of the page being checked: CSP violations and uncaught errors.
let problems = [];
waiters.push((message) => {
    if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error' && /Content Security Policy|Refused to/i.test(message.params.entry.text)) {
        problems.push(message.params.entry.text.slice(0, 200));
    }
    if (message.method === 'Runtime.exceptionThrown') {
        problems.push('JavaScript error: ' + String(message.params.exceptionDetails.exception?.description ?? message.params.exceptionDetails.text).slice(0, 200));
    }
});
await send('Emulation.setDeviceMetricsOverride', { width: WIDTH, height: 844, deviceScaleFactor: 1, mobile: true });

const queue = ['/', '/crm/login'];
const seen = new Set(queue);
const failures = [];

while (queue.length > 0 && seen.size <= MAX_PAGES) {
    const path = queue.shift();
    problems = [];
    const done = loaded();
    await send('Page.navigate', { url: BASE + path });
    await done;
    await evaluate('document.fonts.ready.then(() => new Promise((r) => setTimeout(r, 150)))');

    const result = await evaluate(`(() => {
        // The designed width, not innerWidth: a phone zooms out to fit wide content.
        const width = ${WIDTH};
        const scrollWidth = document.documentElement.scrollWidth;
        const culprits = scrollWidth > width
            ? Array.from(document.querySelectorAll('body *'))
                .filter((el) => el.getBoundingClientRect().right > width + 1 && el.offsetParent !== null)
                .slice(0, 5)
                .map((el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\\s+/).slice(0, 3).join('.') : ''))
            : [];
        const links = Array.from(document.querySelectorAll('a[href]'))
            .map((a) => new URL(a.getAttribute('href'), location.href))
            .filter((u) => u.origin === location.origin)
            .map((u) => u.pathname + u.search);
        return { width, scrollWidth, culprits, links };
    })()`);

    await new Promise((resolve) => setTimeout(resolve, 300));
    for (const problem of problems) {
        failures.push(`${path}: ${problem}`);
    }

    if (result.scrollWidth > result.width) {
        failures.push(`${path}: ${result.scrollWidth}px wide at ${result.width}px — ${result.culprits.join(', ')}`);
    }

    for (const link of result.links) {
        if (!seen.has(link) && !/^\/(crm|dev|livewire|storage|build)(\/|$)/.test(link) && !/\.(pdf|png|jpe?g|webp|svg|ico|xml|txt)$/i.test(link)) {
            seen.add(link);
            queue.push(link);
        }
    }
}

ws.close();
console.log(`Checked ${seen.size - queue.length} pages at ${WIDTH}px.`);
if (failures.length > 0) {
    console.error('Pages wider than the viewport:\n  ' + failures.join('\n  '));
    stop(1);
}
console.log('No horizontal overflow, no CSP violations, no JavaScript errors.');
stop(0);
