const { defineConfig, devices } = require( '@playwright/test' );
const { url } = require( './default.json' );

module.exports = defineConfig( {
	testDir: '../specs',

	/* Maximum time one test can run for. */
	timeout: 120 * 1000,

	expect: {
		/**
		 * Maximum time expect() should wait for the condition to be met.
		 * For example in `await expect(locator).toHaveText();`
		 */
		timeout: 20 * 1000,
	},

	/* Run tests in files in parallel */
	fullyParallel: true,

	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !! process.env.CI,

	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,

	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 4 : undefined,

	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: [
		[ 'list' ],
		[
			'html',
			{
				outputFolder: '../test-results/playwright-report',
				open: 'never',
			},
		],
	],

	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		/* Maximum time each action such as `click()` can take. Defaults to 0 (no limit). */
		actionTimeout: 0,

		/* Base URL to use in actions like `await page.goto('/')`. */
		baseURL: url,

		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: 'retain-on-failure',

		screenshot: 'only-on-failure',
		stateDir: 'tests/e2e/test-results/storage/',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 720 },
	},

	/* Configure projects for major browsers */
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],

	/* Folder for test artifacts such as screenshots, videos, traces, etc. */
	outputDir: '../test-results/report',

	/* Global setup and teardown scripts. */
	globalSetup: require.resolve( '../global-setup' ),

	/* Maximum number of tests to run in parallel. */
	maxFailures: 10,
} );
