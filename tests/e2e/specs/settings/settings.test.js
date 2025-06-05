/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
import SettingsPage from '../../utils/pages/settings';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/settings.js').default} settingsPage
 */
let settingsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Settings', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingsPage = new SettingsPage( page );

		await setOnboardedMerchant();
		await settingsPage.mockRequests();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Tax rate setup', () => {
		test( 'Should not show the setup when selling in regions unrelated to the US', async () => {
			// Mock the country where the store is located as outside of the US.
			const once = settingsPage.withFulfillTimes( 1 );
			await once.fulfillRequest(
				// Having`(\w+%2C)*` is because multiple option queries may be consolidated into a single request.
				/\/wc-admin\/options\?options=(\w+%2C)*woocommerce_default_country\b/,
				{ woocommerce_default_country: 'JP' }
			);
			await settingsPage.mockTargetAudienceCountries( 'JP' );
			await settingsPage.goto();

			await expect(
				page.getByRole( 'heading', { name: 'Settings' } )
			).toBeVisible();

			await expect(
				page.locator( '.woocommerce-spinner' ).first()
			).not.toBeVisible();

			await expect(
				page.getByText( 'Tax rate (required for U.S. only)' )
			).not.toBeVisible();
		} );

		test( 'Should show the setup when selling to the US and can update the setting', async () => {
			await settingsPage.mockTargetAudienceCountries();
			await settingsPage.goto();

			await expect(
				page.getByText( 'Tax rate (required for U.S. only)' )
			).toBeVisible();

			const option = page.getByRole( 'radio', { checked: false } );
			const optionValue = option.getAttribute( 'value' );

			await option.check();

			// Reload to assert the setting has been actually saved.
			await page.reload();
			await expect(
				page.getByRole( 'radio', { checked: true } )
			).toHaveAttribute( 'value', optionValue );
		} );
	} );

	test.describe( 'Enhanced Conversion Setting', () => {
		test( 'should show the "Enhanced Conversion" setting card', async () => {
			await settingsPage.goto();
			await expect(
				page.getByRole( 'heading', { name: 'Settings' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'heading', {
					name: 'Improve conversion accuracy',
				} )
			).toBeVisible();
		} );

		test( 'should toggle the "Enhanced Conversion" setting', async () => {
			const once = settingsPage.withFulfillTimes( 1 );
			await once.fulfillRequest( /\/ads\/settings/, { enabled: false } );
			await settingsPage.goto();
			const checkbox = page.getByRole( 'checkbox', {
				name: 'Send Enhanced Conversions data to Google Ads',
			} );

			await expect( checkbox ).toBeVisible();
			await expect( checkbox ).not.toBeChecked();

			await checkbox.check();
			await expect( checkbox ).toBeChecked();
		} );

		test( 'should show the "Enhanced Conversion" setting saved success notice', async () => {
			// Get the notice with class 'components-notice is-success'
			const notice = page.locator(
				'.components-notice.is-success:has-text("Enhanced Conversions status updated successfully.")'
			);
			await expect( notice ).toBeVisible();
		} );

		test( 'should dismiss the "Enhanced Conversion" setting saved success notice', async () => {
			const notice = page.locator(
				'.components-notice.is-success:has-text("Enhanced Conversions status updated successfully.")'
			);
			await expect( notice ).toBeVisible();

			const dismissButton = notice.getByRole( 'button', {
				name: 'Close',
			} );
			await dismissButton.click();
			await expect( notice ).not.toBeVisible();
		} );
	} );
} );
