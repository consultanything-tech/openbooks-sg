import { test, expect } from '@playwright/test';

/**
 * Smoke test: the floating AI assistant opens, sends a message and renders the
 * reply. POST /ai/chat is intercepted with a canned action:'none' response so
 * the test is hermetic and never mutates the dev database.
 */
test('AI assistant widget opens, sends a message and renders a reply', async ({ page }) => {
  const REPLY = 'Mocked AI reply OK';
  await page.route('**/ai/chat', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        reply: REPLY,
        action: 'none',
        action_data: null,
        action_card: null,
      }),
    })
  );

  await page.goto('/dashboard');

  // Launcher is present on every authenticated page; panel starts hidden
  // (the modal carries `overflow-hidden` too, so assert visibility, not class).
  await expect(page.locator('#aiLauncherBtn')).toBeVisible();
  await expect(page.locator('#aiChatModal')).toBeHidden();
  await page.click('#aiLauncherBtn');
  await expect(page.locator('#aiChatModal')).toBeVisible();

  await page.fill('#aiUserInput', 'How much revenue this month?');
  await page.click('#aiSendBtn');

  await expect(page.locator('#aiChatFeed')).toContainText(REPLY, { timeout: 30_000 });
});
