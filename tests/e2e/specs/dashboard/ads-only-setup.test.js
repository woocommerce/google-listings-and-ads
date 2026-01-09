/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	setOnboardedMerchant,
	setCompletedAdsSetup,
	setCompleteMCSetup,
	clearCompleteMCSetup,
	clearCompletedAdsSetup,
} from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Limited UI elements visibility for Ads only setup', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		await setOnboardedMerchant();
		await clearCompleteMCSetup();
		await dashboardPage.mockRequests();

		await dashboardPage.mockMCNotConnected();
		await setCompletedAdsSetup();
		await dashboardPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await setCompleteMCSetup();
		await clearCompletedAdsSetup();
		await page.close();
	} );

	test.describe( 'Should display limited elements', () => {
		test( 'Should display only "Dashboard" and "Settings" tabs in the main navigation', async () => {
			const tabs = await dashboardPage.getTabTitles();
			expect( tabs ).toEqual( [ 'Dashboard', 'Settings' ] );
		} );

		test( 'Should not display "Product Feed (Limited Visibility)" card', async () => {
			// Get all the summary cards with `gla-summary-card` class.
			const summaryCards = await dashboardPage.getSummaryCards();
			expect( summaryCards ).not.toContainText(
				'Product Feed (Limited Visibility)'
			);
		} );
	} );
} );
