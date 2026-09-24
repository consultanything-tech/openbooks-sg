import { test, expect } from '@playwright/test';

/**
 * Smoke test: the Report Builder runs a query and renders both the results
 * table and a Chart.js bar chart. Read-only (POST /reports/custom/run).
 */
test('report builder runs an invoice report and renders table + chart', async ({ page }) => {
  // runReport() surfaces backend failures via alert(); fail loudly if one fires.
  const dialogs = [];
  page.on('dialog', async (d) => { dialogs.push(d.message()); await d.dismiss(); });

  await page.goto('/reports/custom');

  await page.selectOption('#reportSource', 'invoices');
  await expect(page.locator('#btnRunReport')).toBeVisible();
  await page.click('#btnRunReport');

  // Results table populates.
  await expect(page.locator('#resultsTableWrap')).toBeVisible({ timeout: 30_000 });
  await expect(page.locator('#resultsBody tr')).not.toHaveCount(0, { timeout: 30_000 });
  expect(dialogs, `report run raised an alert: ${dialogs.join(' | ')}`).toEqual([]);

  // Chart section is un-hidden and a Chart.js bar instance is attached.
  await expect(page.locator('#chartSection')).toBeVisible();
  await page.waitForFunction(() => typeof window.Chart !== 'undefined', null, { timeout: 30_000 });
  const charted = await page.locator('#reportChart').evaluate(
    (el) => !!window.Chart?.getChart?.(el)
  );
  expect(charted, '#reportChart should have a Chart.js instance after Run').toBeTruthy();
});
