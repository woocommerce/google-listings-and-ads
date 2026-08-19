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
	} );

	test.afterAll( async () => {
		await page.close();
	} );

	test.describe( 'When not connected to Google for WooCommerce', () => {
		test.beforeAll( async () => {
			await analyticsOverviewPage.mockMCNotConnected();
			await analyticsOverviewPage.goto();
		} );

		test( 'Renders the not-onboarded copy with a Get started CTA', async () => {
			const promoSection =
				analyticsOverviewPage.getAnalyticsOverviewPromoSection();

			await expect( promoSection ).toBeVisible();
			await expect( promoSection ).toContainText(
				'Sales a bit slow? Reach more shoppers with Google.'
			);
			await expect( promoSection ).toContainText(
				'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.'
			);

			const cta = promoSection.getByRole( 'link', {
				name: 'Get started',
			} );
			await expect( cta ).toBeVisible();
			await expect( cta ).toHaveAttribute(
				'href',
				/page=wc-admin&path=%2Fgoogle%2Fsetup-mc/
			);
		} );
	} );

	test.describe( 'When connected to Google for WooCommerce', () => {
		test.beforeAll( async () => {
			await Promise.all( [
				analyticsOverviewPage.mockJetpackConnected(),
				analyticsOverviewPage.mockGoogleConnected(),
				analyticsOverviewPage.mockMCConnected(),
			] );
			await analyticsOverviewPage.goto();
		} );

		test( 'Renders the connected copy with a Launch a campaign CTA', async () => {
			const promoSection =
				analyticsOverviewPage.getAnalyticsOverviewPromoSection();

			await expect( promoSection ).toBeVisible();
			await expect( promoSection ).toContainText(
				'Sales a bit slow? Give your products a boost with Google.'
			);
			await expect( promoSection ).toContainText(
				'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.'
			);

			const cta = promoSection.getByRole( 'link', {
				name: 'Launch a campaign',
			} );
			await expect( cta ).toBeVisible();
			await expect( cta ).toHaveAttribute(
				'href',
				/page=wc-admin&path=%2Fgoogle%2Fsetup-ads/
			);
		} );
	} );
} );
