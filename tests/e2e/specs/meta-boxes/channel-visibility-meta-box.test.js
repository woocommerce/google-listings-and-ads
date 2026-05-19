/**
 * External dependencies
 */
import { expect, test, Page } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	setOnboardedMerchant,
	clearOnboardedMerchant,
	clearCompletedAdsSetup,
	createSimpleProduct,
	setCompletedAdsSetup,
	clearServiceBasedMerchant,
} from '../../utils/api';
import MockRequests from '../../utils/mock-requests';
import { getClassicProductEditorUtils } from '../../utils/product-editor';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

const PREFERENCES_NAMESPACE = 'woocommerce/google-listings-and-ads';
const PROMO_DISMISSED_KEY = 'gla_google_ads_promo_dismissed';
const GET_STARTED_URL_PATTERN = /page=wc-admin&path=%2Fgoogle%2Fsetup-mc/;

/**
 * @type {RequestUtils}
 */
let requestUtils = null;

/**
 * Sets or clears the promo dismissed preference for the current admin user via the REST API.
 *
 * @param {boolean} dismissed Whether the promo should be marked as dismissed.
 */
async function setPromoDismissed( dismissed ) {
	const persistedPreferences = dismissed
		? { [ PREFERENCES_NAMESPACE ]: { [ PROMO_DISMISSED_KEY ]: true } }
		: {};

	await requestUtils.rest( {
		path: '/wp/v2/users/me',
		method: 'PUT',
		data: { meta: { persisted_preferences: persistedPreferences } },
	} );
}

/**
 * @type {Page}
 */
let page = null;

/**
 * @type {import('../../utils/product-editor.js').default} productEditor
 */
let editorUtils = null;

/**
 * @type {MockRequests}
 */
let mockRequests = null;

test.describe( 'Channel Visibility Meta Box', () => {
	let productId = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		editorUtils = getClassicProductEditorUtils( page );
		mockRequests = new MockRequests( page );

		requestUtils = await RequestUtils.setup( {
			storageStatePath: process.env.ADMINSTATE,
		} );
		await requestUtils.setupRest();

		await setOnboardedMerchant();
		await clearCompletedAdsSetup();

		productId = await createSimpleProduct();
	} );

	test.afterAll( async () => {
		await requestUtils.resetPreferences();
		await clearOnboardedMerchant();
		await clearCompletedAdsSetup();
		await clearServiceBasedMerchant();
		await page.close();
	} );

	test.describe( 'Onboarding not completed', () => {
		test.beforeEach( async () => {
			await clearCompletedAdsSetup();
			await mockRequests.mockJetpackConnected();
			await mockRequests.mockGoogleConnected();
		} );

		test( 'Shows full promo banner when not dismissed', async () => {
			await setPromoDismissed( false );
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeVisible();

			await expect(
				glaBox.getByRole( 'heading', {
					level: 3,
					name: 'Get your products on Google',
				} )
			).toBeVisible();

			await expect(
				glaBox.getByText( /Sync your products to reach customers/ )
			).toBeVisible();

			const getStartedLink = glaBox.getByRole( 'link', {
				name: 'Get started',
			} );
			await expect( getStartedLink ).toBeVisible();
			await expect( getStartedLink ).toHaveAttribute(
				'href',
				GET_STARTED_URL_PATTERN
			);

			await expect(
				glaBox.getByRole( 'button', { name: 'Dismiss' } )
			).toBeVisible();

			await expect(
				glaBox.locator(
					'.gla-channel-visibility__get-started--is-dismissed'
				)
			).toBeHidden();
		} );

		test( 'Shows compact header with Get started only when dismissed', async () => {
			await setPromoDismissed( true );
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();

			const compactGetStarted = glaBox
				.locator( '.gla-channel-visibility__get-started--is-dismissed' )
				.getByRole( 'link', { name: 'Get started' } );

			await expect( compactGetStarted ).toBeVisible();
			await expect( compactGetStarted ).toHaveAttribute(
				'href',
				GET_STARTED_URL_PATTERN
			);

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeHidden();
			await expect(
				glaBox.getByRole( 'button', { name: 'Dismiss' } )
			).toBeHidden();

			await expect(
				glaBox.getByText( 'Get your products on Google' )
			).toBeHidden();
			await expect(
				glaBox.getByText( /Sync your products to reach customers/ )
			).toBeHidden();
		} );

		test( 'Clicking on dismiss shows compact layout and settings are persisted on page refresh', async () => {
			await setPromoDismissed( false );
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeVisible();

			await glaBox.getByRole( 'button', { name: 'Dismiss' } ).click();

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeHidden();

			await editorUtils.gotoEditProductPage( productId );

			const glaBoxAfterRefresh =
				editorUtils.getChannelVisibilityMetaBox();

			await expect(
				glaBoxAfterRefresh.locator(
					'.gla-channel-visibility__get-started--is-dismissed'
				)
			).toBeVisible();

			await expect(
				glaBoxAfterRefresh.getByRole( 'button', { name: 'Dismiss' } )
			).toBeHidden();
		} );
	} );

	test.describe( 'Onboarding completed', () => {
		test.beforeAll( async () => {
			await setCompletedAdsSetup();
			await mockRequests.mockJetpackConnected();
			await mockRequests.mockGoogleConnected();
			await mockRequests.mockMCConnected();
			await mockRequests.mockAdsAccountConnected();
		} );

		test.afterAll( async () => {
			await clearCompletedAdsSetup();
			await page.unroute( /\/wc\/gla\/jetpack\/connected\b/ );
			await page.unroute( /\/wc\/gla\/google\/connected\b/ );
			await page.unroute( /\/wc\/gla\/mc\/connection\b/ );
			await page.unroute( /\/wc\/gla\/ads\/connection\b/ );
		} );

		test( 'Shows channel visibility settings with Google label and dropdown', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();

			await expect( glaBox.getByRole( 'combobox' ) ).toBeVisible();

			await expect(
				glaBox.getByText( 'Get your products on Google' )
			).toBeHidden();
			await expect(
				glaBox.getByRole( 'button', { name: 'Dismiss' } )
			).toBeHidden();
			await expect(
				glaBox.locator(
					'.gla-channel-visibility__get-started--is-dismissed'
				)
			).toBeHidden();
		} );

		test( "Dropdown contains Sync and show and Don't sync and show options", async () => {
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();
			const select = glaBox.getByRole( 'combobox' );
			const options = select.locator( 'option' );

			await expect( select ).toBeVisible();
			await expect( options ).toHaveCount( 2 );
			await expect( select ).toHaveValue( 'sync-and-show' );
		} );

		test( 'Changing the dropdown updates the selected value', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();
			const select = glaBox.getByRole( 'combobox' );

			await expect( select ).toBeVisible();

			await select.selectOption( 'dont-sync-and-show' );
			await expect( select ).toHaveValue( 'dont-sync-and-show' );

			await select.selectOption( 'sync-and-show' );
			await expect( select ).toHaveValue( 'sync-and-show' );
		} );

		test( 'Selected visibility value is saved when the product form is submitted', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const glaBox = editorUtils.getChannelVisibilityMetaBox();
			const select = glaBox.getByRole( 'combobox' );

			await select.selectOption( 'dont-sync-and-show' );
			await expect( select ).toHaveValue( 'dont-sync-and-show' );

			await editorUtils.save();

			const savedSelect = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'combobox' );
			await expect( savedSelect ).toHaveValue( 'dont-sync-and-show' );

			await savedSelect.selectOption( 'sync-and-show' );
			await editorUtils.save();
			await editorUtils.gotoEditProductPage( productId );

			await expect( savedSelect ).toHaveValue( 'sync-and-show' );
		} );

		test( 'Changed visibility value persists after navigating away and back', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const select = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'combobox' );

			await select.selectOption( 'dont-sync-and-show' );
			await expect( select ).toHaveValue( 'dont-sync-and-show' );

			await editorUtils.save();

			await editorUtils.gotoEditProductPage( productId );

			const selectAfterRefresh = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'combobox' );

			await expect( selectAfterRefresh ).toHaveValue(
				'dont-sync-and-show'
			);

			await selectAfterRefresh.selectOption( 'sync-and-show' );
			await editorUtils.save();
			await editorUtils.gotoEditProductPage( productId );

			await expect( selectAfterRefresh ).toHaveValue( 'sync-and-show' );
		} );
	} );
} );
