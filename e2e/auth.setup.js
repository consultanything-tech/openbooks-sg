import { test as setup, expect } from '@playwright/test';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
const authFile = join(here, '.auth', 'user.json');

// E2E tests authenticate using the seeded admin account.
// Set E2E_EMAIL and E2E_PASSWORD env vars to override defaults.
const EMAIL = process.env.E2E_EMAIL || 'admin@openbooks.sg';
const PASSWORD = process.env.E2E_PASSWORD || 'password';

setup('authenticate', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('form[action*="/login"] button[type="submit"]');

  // Successful login redirects to the dashboard (2FA is disabled for this user).
  await page.waitForURL(/\/(dashboard|onboarding)/, { timeout: 30_000 });
  await expect(page).toHaveURL(/\/(dashboard|onboarding)/);

  await page.context().storageState({ path: authFile });
});
