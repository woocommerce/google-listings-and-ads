import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  forbidOnly: !!process.env.CI,
  retries: 0,
  fullyParallel: false,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
    ['playwright-ctrf-json-reporter', {
      outputDir: './results',
      outputFile: 'ctrf.json',
    }],
    ['allure-playwright', {
      resultsDir: './results/allure',
    }],
    ['blob', {
      outputDir: './results/blob',
    }],
  ],
  use: {
    baseURL: process.env.QIT_SITE_URL || 'http://localhost:8080',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
