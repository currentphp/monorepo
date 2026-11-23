import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    timeout: 45_000,
    outputDir: 'tests/browser/.runtime/results',
    snapshotPathTemplate:
        '{testDir}/.runtime/references/{testFilePath}/{testName}/{arg}{ext}',
    use: {
        baseURL: 'http://127.0.0.1:18765',
        browserName: 'chromium',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'npm run build && python3 tests/http/run.py --serve',
        url: 'http://127.0.0.1:18765/up',
        reuseExistingServer: false,
        timeout: 30_000,
    },
});
