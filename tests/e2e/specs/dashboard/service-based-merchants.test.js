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

test.describe( 'Limited UI elements for Service-based Merchants', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		await setOnboardedMerchant();
		await dashboardPage.mockRequests();
		await setCompletedAdsSetup();
		await dashboardPage.goto();

		await page.evaluate( () => {
			window.glaData.mcSetupComplete = false;
		} );
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();

		await dashboardPage.goto();
		await page.evaluate( () => {
			window.glaData.mcSetupComplete = true;
		} );
	} );

	test.describe( 'Should display limited elements', () => {
		test( 'Should display only "Dashboard" and "Settings" tabs in the main navigation', async () => {
			const tabs = await dashboardPage.mainTabNav.getTabTitles();
			expect( tabs ).toEqual( [ 'Dashboard', 'Settings' ] );
		} );
	} );
} );
