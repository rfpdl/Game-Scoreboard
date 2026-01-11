import { defineConfig, devices } from '@playwright/test';

// Use BASE_URL from environment (Docker) or default to localhost
const baseURL = process.env.BASE_URL || 'http://localhost:9090';

export default defineConfig({
    testDir: './tests/playwright',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? 'list' : 'html',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: process.env.CI
        ? undefined
        : {
              command: 'echo "Server should already be running via docker-compose"',
              url: 'http://localhost:9090',
              reuseExistingServer: true,
          },
});
