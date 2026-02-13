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
	clearServiceBasedMerchant,
	clearCompleteMCSetup,
} from '../../utils/api';

import { LOAD_STATE } from '../../utils/constants';

test.use( { storageState: process.env.ADMINSTATE } );
test.describe.configure( { mode: 'serial' } );

let page;
let dashboardPage;
let setupAdsAccounts;
let setupBudgetPage;
let optimizeCampaignPage;

const SCENARIOS = [
	{
		name: 'When merchant account is connected',
		setupMerchant: setOnboardedMerchant,
		clearMerchant: () => {
			clearOnboardedMerchant();
		},
		campaignId: 23232323,
		campaignName: 'Test Campaign 2',
		expectedCampaigns: [ 'Test Campaign 1', 'Test Campaign 2' ],
	},
	{
		name: 'For ads only setup',
		setupMerchant: () => {
			clearCompleteMCSetup();
			setOnboardedMerchant();
			setServiceBasedMerchant();
		},
		clearMerchant: () => {
			clearServiceBasedMerchant();
		},
		campaignId: 45454545,
		campaignName: 'Test Campaign 3',
		expectedCampaigns: [ 'Test Campaign 1', 'Test Campaign 3' ],
	},
];

async function openCampaignCreationFlow() {
	await dashboardPage.addPaidCampaignButton.click();

	await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

	await expect(
		page.getByRole( 'heading', { name: 'Create your campaign' } )
	).toBeVisible();
}

async function optimizeCampaign() {
	await setupAdsAccounts.clickContinue();

	await expect(
		page.getByRole( 'heading', { name: 'Optimize your campaign' } )
	).toBeVisible();

	await optimizeCampaignPage.selectUrlOption();

	const createCampaignButton = page.locator(
		'[data-action="submit-campaign-and-assets"]'
	);

	await expect( createCampaignButton ).toBeEnabled();
}

async function createCampaignAndVerify( expectedCampaigns ) {
	const createCampaignButton = page.locator(
		'[data-action="submit-campaign-and-assets"]'
	);

	await createCampaignButton.click();

	await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );

	for ( const campaign of expectedCampaigns ) {
		await expect( page.getByText( campaign ) ).toBeVisible();
	}
}

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
			],
		} );

		await optimizeCampaignPage.mockOptimizeCampaignRequests();
		await optimizeCampaignPage.fulfillAssetGroupsForCampaign();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	SCENARIOS.forEach(
		( {
			name,
			setupMerchant,
			clearMerchant,
			campaignId,
			campaignName,
			expectedCampaigns,
		} ) => {
			test.describe( name, () => {
				test.beforeAll( async () => {
					setupMerchant();

					await optimizeCampaignPage.fulfillAdsCampaignsRequest(
						{
							id: campaignId,
							name: campaignName,
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

					await dashboardPage.fulfillAssetGroupsForCampaign(
						campaignId,
						[
							{
								id: campaignId,
								final_url: '',
								display_url_path: [ '', '' ],
								assets: {},
							},
						]
					);

					await dashboardPage.goto();
				} );

				test.afterAll( async () => {
					await clearMerchant();
					await clearOnboardedMerchant();
				} );

				test( 'User can create campaign post onboarding', async () => {
					await expect(
						dashboardPage.addPaidCampaignButton
					).toBeEnabled();

					await openCampaignCreationFlow();
					await optimizeCampaign();
					await createCampaignAndVerify( expectedCampaigns );
				} );
			} );
		}
	);
} );
