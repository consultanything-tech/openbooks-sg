import { test, expect } from '@playwright/test';

/**
 * Smoke test: the hand-rolled live-refresh wiring (partials/scripts.blade.php).
 * An `ob:data-changed` event in one tab posts to the 'openbooks-sync'
 * BroadcastChannel, and any other open tab on an affected path reloads itself.
 * /dashboard is affected by create_invoice. This only triggers a reload — no
 * data is written.
 */
test('a data-changed event in one tab live-reloads another tab on an affected path', async ({ page, context }) => {
  const tabA = page;
  const tabB = await context.newPage();

  await tabA.goto('/dashboard');
  await tabB.goto('/dashboard');
  await tabA.waitForLoadState('load');
  await tabB.waitForLoadState('load');

  expect(
    await tabB.evaluate(() => 'BroadcastChannel' in window),
    'BroadcastChannel must be supported for cross-tab sync'
  ).toBeTruthy();

  // Mark tab B so we can prove it actually reloaded (the marker is wiped on reload).
  await tabB.evaluate(() => { window.__lr_marker = 'alive'; });
  await expect
    .poll(() => tabB.evaluate(() => window.__lr_marker), { timeout: 5_000 })
    .toBe('alive');

  // Fire the same CustomEvent the AI widget dispatches after a write action.
  // It is dispatched on `document`, which is where the live-refresh listener lives.
  await tabA.evaluate(() => {
    document.dispatchEvent(new CustomEvent('ob:data-changed', { detail: { action: 'create_invoice' } }));
  });

  // tab B receives the broadcast and reloads, clearing the marker.
  await tabB.waitForFunction(() => window.__lr_marker === undefined, null, { timeout: 30_000 });
  const marker = await tabB.evaluate(() => window.__lr_marker ?? null);
  expect(marker, 'tab B should have reloaded and lost its marker').toBeNull();

  await tabB.close();
});
