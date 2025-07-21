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

	test.describe( 'Enhanced Conversions Setting', () => {
		test.describe( 'When ads account is connected', () => {
			test( 'should show the "Enhanced Conversion" setting card', async () => {
				await expect(
					page.getByRole( 'heading', { name: 'Settings' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'heading', {
						name: 'Improve conversion accuracy',
					} )
				).toBeVisible();
			} );

			test( 'checkbox should be unchecked by default', async () => {
				const checkbox = settingsPage.getEnhancedConversionsCheckbox();

				await expect( checkbox ).not.toBeChecked();
			} );

			test( 'checkbox should be checked when the setting is enabled', async () => {
				await settingsPage.mockEnhancedConversionsStatus( true );
				await page.reload();

				const checkbox = settingsPage.getEnhancedConversionsCheckbox();

				await expect( checkbox ).toBeChecked();
			} );

			test( 'should send POST request to disable Enhanced Conversions when enabled with the correct payload', async () => {
				const requestPromise =
					settingsPage.registerEnhancedConversionsStatusRequests();

				await settingsPage.mockEnhancedConversionsStatus( false, [
					'POST',
				] );

				const checkbox = settingsPage.getEnhancedConversionsCheckbox();
				await expect( checkbox ).toBeChecked();

				await checkbox.click();

				const request = await requestPromise;
				const requestPayload = await request.postDataJSON();
				const response = await request.response();
				const responseBody = await response.json();
				const payload = {
					enhanced_conversions_enabled: false,
				};

				expect( requestPayload ).toEqual( payload );
				expect( responseBody ).toEqual( payload );
			} );

			test( 'should show the "Enhanced Conversion" setting saved success notice', async () => {
				// Get the notice with class 'components-notice is-success'
				const notice = page.locator(
					'.components-snackbar:has-text("Enhanced Conversions status updated successfully.")'
				);
				await expect( notice ).toBeVisible();
			} );
		} );

		test.describe( 'When ads account is not connected', () => {
			test.beforeAll( async () => {
				await settingsPage.mockAdsAccountDisconnected();
				await settingsPage.goto();
			} );

			test( 'should show the "Enhanced Conversion" setting card', async () => {
				await expect(
					page.getByRole( 'heading', { name: 'Settings' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'heading', {
						name: 'Improve conversion accuracy',
					} )
				).toBeVisible();
			} );

			test( 'checkbox should be unchecked and disabled by default', async () => {
				const checkbox = settingsPage.getEnhancedConversionsCheckbox();

				await expect( checkbox ).not.toBeChecked();
				await expect( checkbox ).toBeDisabled();
			} );

			test( 'should show the message that Google Ads account is not connected', async () => {
				await expect(
					page.getByText(
						'Please connect your Google Ads account in order to use Enhanced Conversions data.'
					)
				).toBeVisible();
			} );
		} );
	} );
} );
