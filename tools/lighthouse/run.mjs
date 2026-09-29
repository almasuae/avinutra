// Local Lighthouse audit (mobile settings, Lighthouse's default), printing the four
// category scores. Never installed on the server (see package.json here).
//
//   cd tools/lighthouse && npm ci
//   node run.mjs http://127.0.0.1:8765/            # the site must be running, with built assets
//
// Uses Edge or Chrome (CHROME_PATH to override). Lighthouse starts its own browser
// and closes only that process. The JSON and HTML reports go to tools/lighthouse/reports/.
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const url = process.argv[2] ?? 'http://127.0.0.1:8765/';
const browser = [
    process.env.CHROME_PATH,
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome', '/usr/bin/chromium',
].filter(Boolean).find((path) => existsSync(path));

if (!browser) {
    console.error('No Edge/Chrome found. Set CHROME_PATH.');
    process.exit(2);
}

const reports = join(here, 'reports');
mkdirSync(reports, { recursive: true });
const base = join(reports, 'home-mobile');
const cli = join(here, 'node_modules', 'lighthouse', 'cli', 'index.js');

const result = spawnSync(process.execPath, [
    cli, url,
    '--quiet',
    // No external services: Lighthouse's own error reporting stays off.
    '--no-enable-error-reporting',
    '--chrome-flags=--headless=new --no-first-run',
    '--only-categories=performance,accessibility,best-practices,seo',
    '--output=json', '--output=html', `--output-path=${base}`,
], { stdio: 'inherit', env: { ...process.env, CHROME_PATH: browser } });

if (result.status !== 0) {
    process.exit(result.status ?? 1);
}

const report = JSON.parse(readFileSync(`${base}.report.json`, 'utf8'));
for (const category of Object.values(report.categories)) {
    console.log(`${category.title.padEnd(16)} ${Math.round(category.score * 100)}`);
}

const metrics = ['first-contentful-paint', 'largest-contentful-paint', 'total-blocking-time', 'cumulative-layout-shift', 'speed-index'];
for (const id of metrics) {
    const audit = report.audits[id];
    console.log(`  ${audit.title.padEnd(28)} ${audit.displayValue}`);
}

const opportunities = Object.values(report.audits)
    .filter((audit) => audit.score !== null && audit.score < 0.9 && audit.details?.type === 'opportunity')
    .map((audit) => `  - ${audit.title} ${audit.displayValue ?? ''}`);
if (opportunities.length > 0) {
    console.log('Opportunities:\n' + opportunities.join('\n'));
}
console.log(`Reports: ${base}.report.html`);
