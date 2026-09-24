import { test, expect } from '@playwright/test';

/**
 * Smoke test: the dashboard renders its Chart.js charts.
 * Chart.js is loaded from a CDN, so we wait for window.Chart before asserting
 * that each canvas has an attached chart instance.
 */
test('dashboard renders chart canvases with live Chart.js instances', async ({ page }) => {
  await page.goto('/dashboard');
  await expect(page.locator('#cashFlowChart')).toBeVisible();

  // Chart.js is created in an inline @section('scripts') after the CDN loads.
  await page.waitForFunction(() => typeof window.Chart !== 'undefined', null, { timeout: 30_000 });

  for (const id of ['cashFlowChart', 'forecastChart', 'expenseCategoryChart']) {
    const canvas = page.locator(`#${id}`);
    await expect(canvas, `canvas #${id} should exist`).toHaveCount(1);

    const hasInstance = await canvas.evaluate(
      (el) => typeof window.Chart?.getChart === 'function' && !!window.Chart.getChart(el)
    );
    expect(hasInstance, `#${id} should have a Chart.js instance`).toBeTruthy();
  }
});
