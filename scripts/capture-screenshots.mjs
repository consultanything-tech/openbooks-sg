/**
 * Captures README demo screenshots from a running local instance.
 *
 * Usage:
 *   php artisan serve --port=8000   (with seeded demo data)
 *   node scripts/capture-screenshots.mjs [baseUrl]
 */
import { chromium } from '@playwright/test';
import { mkdirSync } from 'node:fs';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8000';
const OUT = 'docs/screenshots';

const screens = [
    ['dashboard', '/dashboard'],
    ['invoices', '/invoices'],
    ['invoice-detail', '/invoices/5'],
    ['profit-loss', '/reports/profit-loss'],
    ['banking', '/banking'],
    ['ai-assistant', '/ask'],
];

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
await page.fill('#login-email', 'admin@openbooks.sg');
await page.fill('#login-password', 'password');
await page.click('button[type="submit"]');
await page.waitForURL('**/dashboard', { timeout: 15000 });

for (const [name, path] of screens) {
    await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200); // let charts/animations settle
    await page.screenshot({ path: `${OUT}/${name}.png` });
    console.log(`captured ${name}.png`);
}

await browser.close();
