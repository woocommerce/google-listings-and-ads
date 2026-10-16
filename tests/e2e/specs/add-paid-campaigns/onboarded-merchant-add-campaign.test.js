/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import DashboardPage from '../../utils/pages/dashboard';
import SetupAdsAccountsPage from '../../utils/pages/ads-onboarding/setup-ads-accounts';
import SetupBudgetPage from '../../utils/pages/ads-onboarding/setup-budget';
import OptimizeCampaignPage from '../../utils/pages/onboarding/step-3-optimize-campaign-ads-account-only';
import openCampaignCreationFlow from '../../utils/pages/open-campaign-creation-flow';
import optimizeCampaign from '../../utils/pages/optimize-campaign';
import createCampaignAndVerify from '../../utils/pages/create-campaign-and-verify';

import {
	clearCompletedAdsSetup,
	clearOnboardedMerchant,
	setCompletedAdsSetup,
	setOnboardedMerchant,
	setServiceBasedMerchant,
	clearServiceBasedMerchant,
	clearCompleteMCSetup,
} from '../../utils/api';

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
		setupMerchant: noop,
		clearMerchant: async () => {
			await clearOnboardedMerchant();
		},
		campaignId: 23232323,
		campaignName: 'Test Campaign 2',
		expectedCampaigns: [ 'Test Campaign 1', 'Test Campaign 2' ],
	},
	{
		name: 'For ads only setup',
		setupMerchant: async () => {
			await clearCompleteMCSetup();
			await setServiceBasedMerchant();
		},
		clearMerchant: async () => {
			await clearServiceBasedMerchant();
		},
		campaignId: 45454545,
		campaignName: 'Test Campaign 3',
		expectedCampaigns: [ 'Test Campaign 1', 'Test Campaign 3' ],
	},
];

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

		await setCompletedAdsSetup();

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
		await clearCompletedAdsSetup();
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
					await setOnboardedMerchant();
					await setupMerchant();

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
					await clearOnboardedMerchant();
					await clearMerchant();
				} );

				test( 'User can create campaign post onboarding', async () => {
					await expect(
						dashboardPage.addPaidCampaignButton
					).toBeEnabled();

					await openCampaignCreationFlow( page, dashboardPage );
					await optimizeCampaign(
						page,
						setupAdsAccounts,
						optimizeCampaignPage
					);
					await createCampaignAndVerify( page, expectedCampaigns );
				} );
			} );
		}
	);
} );
