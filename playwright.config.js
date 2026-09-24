import { defineConfig, devices } from '@playwright/test';

/**
 * Frontend smoke tests for OpenBooks SG.
 *
 * These run against the real dev server + MySQL database (APP_URL is pinned to
 * http://127.0.0.1:8096), so they are kept READ-ONLY: the AI chat endpoint is
 * route-intercepted with action:'none' so no record is ever created, and the
 * live-refresh test only triggers a page reload (never a write).
 *
 * Run with:  npm run e2e
 */
const BASE_URL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8096';
const PHP = process.env.E2E_PHP || 'php';

export default defineConfig({
  testDir: './e2e',
  // Shared dev database + reload-based assertions: keep runs serial.
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: [['list']],
  timeout: 60_000,
  expect: { timeout: 15_000 },
  use: {
    baseURL: BASE_URL,
    // public/sw.js is cache-first and can serve stale pages across runs.
    serviceWorkers: 'block',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'setup', testMatch: /.*\.setup\.js/ },
    {
      name: 'chromium',
      testMatch: /.*\.spec\.js/,
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'], storageState: 'e2e/.auth/user.json' },
    },
  ],
  webServer: {
    command: `${PHP} artisan serve --host=127.0.0.1 --port=8096`,
    url: `${BASE_URL}/up`,
    reuseExistingServer: true,
    timeout: 120_000,
    // PHP's built-in server is single-threaded by default; the two-tab
    // live-refresh test needs concurrent request handling.
    env: { PHP_CLI_SERVER_WORKERS: '4' },
  },
});
