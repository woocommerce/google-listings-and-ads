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

test.describe( 'Settings - WPCOM REST API', () => {
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
		const gmcCard = page
			.locator( '.gla-account-card' )
			.getByText( /^Google Merchant Center$/ );
		const button = settingsPage.getGrantAccessBtn();

		await expect( gmcCard ).toBeVisible();
		await expect( button ).not.toBeVisible();
	} );

	test( 'When REST API is Error it shows a warning notice in MC and allows to grant access', async () => {
		await settingsPage.goto();
		await settingsPage.mockMCConnected( 1234, true, 'error' );
		const mockAuthURL = 'https://example.com';
		await settingsPage.fulfillRESTApiAuthorize( { auth_url: mockAuthURL } );
		const errorAccessMessage = page
			.locator( '#woocommerce-layout__primary' )
			.getByText(
				'There was an issue granting access to Google for fetching your products.'
			);
		const grantAccessBtn = settingsPage.getGrantAccessBtn();
		await expect( errorAccessMessage ).toBeVisible();
		await expect( grantAccessBtn ).toBeVisible();
		await grantAccessBtn.click();
		await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
		await page.waitForURL( mockAuthURL );
		expect( page.url() ).toMatch( mockAuthURL );
	} );
} );
