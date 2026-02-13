/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import DashboardPage from '../../utils/pages/dashboard';
import SetupAdsAccountsPage from '../../utils/pages/ads-onboarding/setup-ads-accounts';
import SetupBudgetPage from '../../utils/pages/ads-onboarding/setup-budget';
import OptimizeCampaignPage from '../../utils/pages/onboarding/step-3-optimize-campaign-ads-account-only';
import {
	clearOnboardedMerchant,
	setOnboardedMerchant,
	setServiceBasedMerchant,
} from '../../utils/api';
import { LOAD_STATE } from '../../utils/constants';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-ads-accounts').default} setupAdsAccounts
 */
let setupAdsAccounts = null;

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-budget.js').default} setupBudgetPage
 */
let setupBudgetPage = null;

/**
 * @type {import('../../utils/pages/onboarding/step-3-optimize-campaign-ads-account-only.js').default} optimizeCampaignPage
 */
let optimizeCampaignPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Post onboarding campaign setup', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupAdsAccounts = new SetupAdsAccountsPage( page );
		setupBudgetPage = new SetupBudgetPage( page );
		optimizeCampaignPage = new OptimizeCampaignPage( page );

		await dashboardPage.mockRequests();
		await dashboardPage.mockAdsAccountConnected();

		await dashboardPage.fulfillAdsCampaignsRequest( [
			{
				id: 1,
				name: 'Test Campaign 1',
				status: 'enabled',
				type: 'performance_max',
				amount: 1,
				country: 'US',
				targeted_locations: [ 'US' ],
			},
		] );

		await setupBudgetPage.fulfillBudgetRecommendations( {
			currency: 'EUR',
			daily_budget_baseline: 12,
			recommendations: [
				{
					level: 'Recommended',
					country: 'FR',
					daily_budget: 15,
					metrics: {
						cost: 105,
						conversions: 2.2,
						conversions_value: 89.98,
					},
				},
				{
					level: 'High',
					country: 'FR',
					daily_budget: 20.5,
					metrics: {
						cost: 143.5,
						conversions: 2.5,
						conversions_value: 98.59,
					},
				},
				{
					level: 'Low',
					country: 'FR',
					daily_budget: 7,
					metrics: {
						cost: 49,
						conversions: 2,
						conversions_value: 80.48,
					},
				},
			],
		} );

		await optimizeCampaignPage.mockOptimizeCampaignRequests();
		await optimizeCampaignPage.fulfillAssetGroupsForCampaign();

		await setOnboardedMerchant();
		await dashboardPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'When merchant account is connected', () => {
		test.beforeAll( async () => {
			await optimizeCampaignPage.fulfillAdsCampaignsRequest(
				{
					id: 23232323,
					name: 'Test Campaign 2',
					status: 'enabled',
					type: 'performance_max',
					amount: 101.5,
					country: 'US',
					targeted_locations: [ 'US' ],
					eu_political_advertising_confirmation: false,
				},
				200,
				[ 'POST' ]
			);

			await dashboardPage.fulfillAssetGroupsForCampaign( 23232323, [
				{
					id: 23232323,
					final_url: '',
					display_url_path: [ '', '' ],
					assets: {},
				},
			] );
		} );

		test( 'Dashboard page contains Add campaign button', async () => {
			await expect( dashboardPage.addPaidCampaignButton ).toBeEnabled();
		} );

		test( 'Clicking on Add campaign button opens the campaign creation flow', async () => {
			await dashboardPage.addPaidCampaignButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			await expect(
				page.getByRole( 'heading', { name: 'Create your campaign' } )
			).toBeVisible();
		} );

		test( 'Clicking on "Continue" button proceeds to "Optimize your campaign" step', async () => {
			await setupAdsAccounts.clickContinue();

			await expect(
				page.getByRole( 'heading', { name: 'Optimize your campaign' } )
			).toBeVisible();

			await optimizeCampaignPage.selectUrlOption();

			const createCampaignButton = page.locator(
				'[data-action="submit-campaign-and-assets"]'
			);
			await expect( createCampaignButton ).toBeEnabled();
		} );

		test( 'Clicking the "Create Campaign" button navigates to the dashboard', async () => {
			const createCampaignButton = page.locator(
				'[data-action="submit-campaign-and-assets"]'
			);

			await createCampaignButton.click();

			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fdashboard/ );

			await expect( page.getByText( 'Test Campaign 1' ) ).toBeVisible();
			await expect( page.getByText( 'Test Campaign 2' ) ).toBeVisible();
		} );
	} );

	test.describe( 'For ads only setup', () => {
		test.beforeAll( async () => {
			await optimizeCampaignPage.fulfillAdsCampaignsRequest(
				{
					id: 45454545,
					name: 'Test Campaign 3',
					status: 'enabled',
					type: 'performance_max',
					amount: 101.5,
					country: 'US',
					targeted_locations: [ 'US' ],
					eu_political_advertising_confirmation: false,
				},
				200,
				[ 'POST' ]
			);

			await dashboardPage.fulfillAssetGroupsForCampaign( 45454545, [
				{
					id: 45454545,
					final_url: '',
					display_url_path: [ '', '' ],
					assets: {},
				},
			] );

			await setServiceBasedMerchant();
		} );

		test.afterAll( async () => {
			await clearOnboardedMerchant();
		} );

		test( 'Dashboard page contains Add campaign button', async () => {
			await expect( dashboardPage.addPaidCampaignButton ).toBeEnabled();
		} );

		test( 'Clicking on Add campaign button opens the campaign creation flow', async () => {
			await dashboardPage.addPaidCampaignButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			await expect(
				page.getByRole( 'heading', { name: 'Create your campaign' } )
			).toBeVisible();
		} );

		test( 'Clicking on "Continue" button proceeds to "Optimize your campaign" step', async () => {
			await setupAdsAccounts.clickContinue();

			await expect(
				page.getByRole( 'heading', { name: 'Optimize your campaign' } )
			).toBeVisible();

			await optimizeCampaignPage.selectUrlOption();

			const createCampaignButton = page.locator(
				'[data-action="submit-campaign-and-assets"]'
			);
			await expect( createCampaignButton ).toBeEnabled();
		} );

		test( 'Clicking the "Create Campaign" button navigates to the dashboard', async () => {
			const createCampaignButton = page.locator(
				'[data-action="submit-campaign-and-assets"]'
			);

			await createCampaignButton.click();

			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fdashboard/ );

			await expect( page.getByText( 'Test Campaign 1' ) ).toBeVisible();
			await expect( page.getByText( 'Test Campaign 2' ) ).toBeVisible();
			await expect( page.getByText( 'Test Campaign 3' ) ).toBeVisible();
		} );
	} );
} );
