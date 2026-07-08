/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import SettingsPage from '../../utils/pages/settings';
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';

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

	test( 'Grant Access button is not visible on Settings page', async () => {
		const gmcCard = page
			.locator( '.gla-account-card' )
			.getByText( /^Google Merchant Center$/ );
		const button = settingsPage.getGrantAccessBtn();

		await expect( gmcCard ).toBeVisible();
		await expect( button ).not.toBeVisible();
	} );
} );
