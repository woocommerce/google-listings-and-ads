/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';
/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
import { checkSnackBarMessage } from '../../utils/page';
import DashboardPage from '../../utils/pages/dashboard.js';
import EditFreeListingsPage from '../../utils/pages/edit-free-listings.js';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('../../utils/pages/edit-free-listings.js').default} editFreeListingsPage
 */
let editFreeListingsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Edit Free Listings', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		editFreeListingsPage = new EditFreeListingsPage( page );
		await setOnboardedMerchant();
		await dashboardPage.mockRequests();
		await dashboardPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test( 'Dashboard page contains Free Listings', async () => {
		await expect( dashboardPage.freeListingRow ).toContainText(
			'Free listings'
		);

		await expect( dashboardPage.editFreeListingButton ).toBeEnabled();
	} );

	test( 'Edit Free Listings should show modal', async () => {
		await dashboardPage.clickEditFreeListings();

		await page.waitForLoadState( 'domcontentloaded' );

		const continueToEditButton =
			await dashboardPage.getContinueToEditButton();
		const dontEditButton = await dashboardPage.getDontEditButton();
		await expect( continueToEditButton ).toBeEnabled();
		await expect( dontEditButton ).toBeEnabled();
	} );

	test( 'Continue to edit Free listings', async () => {
		await dashboardPage.clickContinueToEditButton();
		await page.waitForLoadState( 'domcontentloaded' );
	} );

	test( 'Check recommended shipping settings', async () => {
		await editFreeListingsPage.checkRecommendShippingSettings();
		await editFreeListingsPage.fillCountriesShippingTimeInput( '5' );
		await editFreeListingsPage.checkDestinationBasedTaxRates();
		const saveChangesButton =
			await editFreeListingsPage.getSaveChangesButton();
		await expect( saveChangesButton ).toBeEnabled();
	} );

	test( 'Save changes', async () => {
		const awaitForRequests = editFreeListingsPage.registerSavingRequests();
		await editFreeListingsPage.mockSuccessfulSavingSettingsResponse();
		await editFreeListingsPage.clickSaveChanges();
		const requests = await awaitForRequests;
		const settingsResponse = await (
			await requests[ 0 ].response()
		 ).json();

		expect( settingsResponse.status ).toBe( 'success' );
		expect( settingsResponse.message ).toBe(
			'Merchant Center Settings successfully updated.'
		);
		expect( settingsResponse.data.shipping_time ).toBe( 'flat' );
		expect( settingsResponse.data.tax_rate ).toBe( 'destination' );

		await checkSnackBarMessage(
			page,
			'Your changes to your Free Listings have been saved and will be synced to your Google Merchant Center account.'
		);
	} );
} );
