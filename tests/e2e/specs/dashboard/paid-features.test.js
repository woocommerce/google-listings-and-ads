/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	setOnboardedMerchant,
	setCompletedAdsSetup,
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

test.describe( 'Paid Feature Listing', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		await setOnboardedMerchant();
		await dashboardPage.mockRequests();
		await clearCompletedAdsSetup();
		await dashboardPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test( 'Paid Features Listing is visible if ads campaign setup is not complete', async () => {
		await expect( dashboardPage.googleAdsSummaryCard ).toContainText(
			'Google Ads'
		);

		await expect( dashboardPage.paidFeatures ).toContainText(
			'Reach more customer by advertising your products across Google Ads channels like Search, YouTube and Discover.'
		);

		await expect( dashboardPage.paidFeatures ).toContainText(
			'Get $500 USD or more in Google Ads credits. New advertiser? Choose between three offers, based on your monthly budget, to jumpstart your first campaign!'
		);

		const termsAndConditionsLink = dashboardPage.paidFeatures.getByRole(
			'link',
			{ name: 'Terms and conditions' }
		);
		await expect( termsAndConditionsLink ).toBeVisible();
		await expect( termsAndConditionsLink ).toHaveAttribute(
			'target',
			'_blank'
		);

		await expect( dashboardPage.createCampaignButton ).toBeEnabled();
		await dashboardPage.mockAdsAccountsResponse( [] );
		await dashboardPage.createCampaignButton.click();
		await expect(
			page.getByRole( 'heading', {
				level: 1,
				name: 'Set up your accounts',
			} )
		).toBeVisible();
	} );

	test.describe( 'When ads campaign setup is complete', async () => {
		test.beforeAll( async () => {
			await setCompletedAdsSetup();
		} );

		test.afterAll( async () => {
			await clearCompletedAdsSetup();
			await page.close();
		} );
		test( 'When no campaign present', async () => {
			await dashboardPage.fulfillAdsCampaignsRequest( [] );
			await dashboardPage.goto();
			await expect( dashboardPage.googleAdsSummaryCard ).toContainText(
				'Google Ads'
			);

			await expect( dashboardPage.paidFeatures ).toBeVisible();
		} );
		test( 'When at least one campaign present', async () => {
			await dashboardPage.fulfillAdsCampaignsRequest( [
				{
					id: 111111111,
					name: 'Test Campaign',
					status: 'enabled',
					type: 'performance_max',
					amount: 1,
					country: 'US',
					targeted_locations: [ 'US' ],
				},
			] );
			await dashboardPage.goto();
			await expect( dashboardPage.googleAdsSummaryCard ).toContainText(
				/Google Ads.*Total Sales.*Total Spend/
			);

			await expect( dashboardPage.paidFeatures ).not.toBeVisible();
		} );
	} );
} );
