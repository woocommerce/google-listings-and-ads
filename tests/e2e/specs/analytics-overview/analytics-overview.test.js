/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import AnalyticsOverviewPage from '../../utils/pages/analytics-overview';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/analytics-overview').default} analyticsOverviewPage
 */
let analyticsOverviewPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Analytics Overview', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		analyticsOverviewPage = new AnalyticsOverviewPage( page );
		await analyticsOverviewPage.goto();
	} );

	test.afterAll( async () => {
		await page.close();
	} );

	test( 'Renders the Analytics Overview promo section', async () => {
		await expect(
			analyticsOverviewPage.getAnalyticsOverviewPromoSection()
		).toBeVisible();
	} );
} );
