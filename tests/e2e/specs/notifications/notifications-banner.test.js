/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import SettingsPage from '../../utils/pages/settings';
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
import { LOAD_STATE } from '../../utils/constants';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/settings.js').default } settingsPage
 */
let settingsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Notifications Banner', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingsPage = new SettingsPage( page );
		await setOnboardedMerchant();
		await settingsPage.mockRequests();

		settingsPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test( 'Grant Access button is not visible on Settings page when notifications service is disabled', async () => {
		const button = settingsPage.getGrantAccessBtn();
		await expect( button ).not.toBeVisible();
	} );

	test( 'Grant Access button is visible on Settings page when notifications service is enabled', async () => {
		// Mock Merchant Center as connected
		await settingsPage.mockMCConnected( 1234, true, null );
		const button = settingsPage.getGrantAccessBtn();

		await expect( button ).toBeVisible();
	} );

	test( 'When click on Grant Access button redirect to Auth page', async () => {
		const mockAuthURL = 'https://example.com';
		// Mock Merchant Center as connected
		await settingsPage.mockMCConnected( 1234, true, null );
		await settingsPage.fulfillRESTApiAuthorize( { auth_url: mockAuthURL } );
		const button = settingsPage.getGrantAccessBtn();

		button.click();
		await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
		await page.waitForURL( mockAuthURL );
		expect( page.url() ).toMatch( mockAuthURL );
	} );

	test( 'When REST API is Approved it shows a success notice', async () => {
		await settingsPage.goto();
		await settingsPage.mockMCConnected( 1234, true, 'approved' );
		const grantedAccessMessage = page
			.locator( '#woocommerce-layout__primary' )
			.getByText(
				'Google has been granted access to fetch your product data.'
			);
		await expect( grantedAccessMessage ).toBeVisible();
	} );

	test( 'When REST API is Error it shows a waring notice and allows to grant access', async () => {
		await settingsPage.goto();
		await settingsPage.mockMCConnected( 1234, true, 'error' );
		const mockAuthURL = 'https://example.com';
		await settingsPage.fulfillRESTApiAuthorize( { auth_url: mockAuthURL } );
		const errorAccessMessage = page
			.locator( '#woocommerce-layout__primary' )
			.getByText(
				'There was an error granting Google access to your WooCommerce store.'
			);
		const grantAccessBtn = page.getByRole( 'button', {
			name: 'Grant access',
			exact: true,
		} );
		await expect( errorAccessMessage ).toBeVisible();
		await expect( grantAccessBtn ).toBeVisible();
		await grantAccessBtn.click();
		await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
		await page.waitForURL( mockAuthURL );
		expect( page.url() ).toMatch( mockAuthURL );
	} );
} );
