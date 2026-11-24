import { defineConfig } from '@playwright/test';

const port = Number(process.env.EDGEPHP_PORT || 8765);
const baseURL = process.env.EDGEPHP_BASE_URL || `http://127.0.0.1:${port}`;

export default defineConfig({
  testDir: './tests/E2E',
  timeout: 60_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  retries: 0,
  use: {
    baseURL,
    headless: true,
    trace: 'retain-on-failure',
  },
  ...(process.env.EDGEPHP_BASE_URL
    ? {}
    : {
        webServer: {
          command: `php artisan serve --host=127.0.0.1 --port=${port}`,
          url: baseURL,
          reuseExistingServer: !process.env.CI,
          timeout: 120_000,
        },
      }),
});
