import { defineConfig, devices } from '@playwright/test'

/**
 * Бэкенд (см. ../backend) должен быть уже запущен и доступен по VITE_API_URL —
 * Playwright поднимает только фронтенд-dev-сервер. См. docs/rules/05-testing.md.
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: 'http://localhost:5173',
    trace: 'on-first-retry',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: !process.env.CI,
    timeout: 30_000,
  },
})
