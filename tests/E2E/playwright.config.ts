import { defineConfig, devices } from "@playwright/test";

import wpEnvTest from "../../.wp-env.test.json" with { type: "json" };

const baseURL = `http://localhost:${wpEnvTest.port}`;
const isCI = !!process.env.CI;

export default defineConfig({
  testDir: "./tests",
  outputDir: "./results",
  timeout: 10_000,
  fullyParallel: true,
  forbidOnly: isCI,
  retries: isCI ? 1 : 0,
  workers: isCI ? 1 : undefined,
  reporter: isCI
    ? [["dot"], ["github"]]
    : [["list"], ["html", { outputFolder: "./reports/html", open: "on-failure" }]],
  expect: { timeout: 3_000 },
  use: {
    baseURL,
    trace: "on-first-retry",
    screenshot: "only-on-failure",
  },
  /** CI installs only Firefox (`npm run test:e2e:install`) */
  projects: [{ name: "firefox", use: { ...devices["Desktop Firefox"] } }],
  webServer: {
    url: baseURL,
    command: "npm run env:test start",
    reuseExistingServer: true,
  },
});
