/**
 * Internal dependencies
 */
import SetUpAccountsPage from '../../utils/pages/setup-mc/step-1-set-up-accounts.js';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-1-set-up-accounts.js').default} setUpAccountsPage
 */
let setUpAccountsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Set up accounts', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setUpAccountsPage = new SetUpAccountsPage( page );
		await setUpAccountsPage.goto();
	} );

	test.afterAll( async () => {
		await setUpAccountsPage.closePage();
	} );

	test( 'should see accounts step header, "Connect your WordPress.com account" & connect button', async () => {
		await expect(
			page.getByRole( 'heading', { name: 'Set up your accounts' } )
		).toBeVisible();

		await expect(
			page.getByText(
				'Connect the accounts required to use Google Listings & Ads.'
			)
		).toBeVisible();

		expect(
			page.getByRole( 'button', { name: 'Connect' } ).first()
		).toBeEnabled();
	} );

	test.describe( 'Connect WordPress.com account', () => {
		test( 'should send an API request to connect Jetpack, and redirect to the returned URL', async ( { baseURL } ) => {
			// Mock Jetpack connect
			setUpAccountsPage.mockJetpackConnect( baseURL + 'auth_url' );

			// Click the enabled connect button.
			page.locator( "//button[text()='Connect'][not(@disabled)]" ).click();
			await page.waitForLoadState( 'networkidle' );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'auth_url' );
		} );
	} );

	test.describe( 'Connect Google account', () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected( 'Test user', 'mail@example.com' );

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await setUpAccountsPage.mockGoogleNotConnected();

			setUpAccountsPage.goto();

			page.getByRole( 'heading', { name: 'Set up your accounts' } );
		} );

		test( 'should see their WPORG email, "Google" title & connect button', async () => {
			await expect( page.getByText( 'mail@example.com' ) ).toBeVisible();

			await expect(
				page.getByText( 'Google', { exact: true } )
			).toBeVisible();

			expect(
				page.getByRole( 'button', { name: 'Connect' } ).first()
			).toBeEnabled();
		} );

		test( 'after clicking the "Connect your Google account" button should send an API request to connect Google account, and redirect to the returned URL', async ( { baseURL } ) => {
			// Mock google connect.
			await setUpAccountsPage.mockGoogleConnect( baseURL + 'google_auth' );

			// Click the enabled connect button
			page.locator( "//button[text()='Connect'][not(@disabled)]" ).click();
			await page.waitForLoadState( 'networkidle' );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'google_auth' );
		} );
	} );
} );
