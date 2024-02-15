/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SetupBudgetPage from '../../utils/pages/setup-ads/setup-budget';
import CompleteCampaign from '../../utils/pages/setup-mc/step-4-complete-campaign';
import SetupAdsAccountPage from '../../utils/pages/setup-ads/setup-ads-accounts';
import DashboardPage from '../../utils/pages/dashboard.js';
import {
	checkFAQExpandable,
	fillCountryInSearchBox,
	getCountryInputSearchBoxContainer,
	getCountryTagsFromInputSearchBoxContainer,
	getFAQPanelTitle,
	getFAQPanelRow,
	getTreeSelectMenu,
	removeCountryFromSearchBox,
	checkBillingAdsPopup,
} from '../../utils/page';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('../../utils/pages/setup-ads/setup-budget.js').default} setupBudgetPage
 */
let setupBudgetPage = null;

/**
 * @type {import('../../utils/pages/setup-mc/step-4-complete-campaign.js').default} completeCampaign
 */
let completeCampaign = null;

/**
 * @type {import('../../utils/pages/setup-ads/setup-ads-accounts.js').default} setupAdsAccountPage
 */
let setupAdsAccountPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Enhanced Conversion', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupBudgetPage = new SetupBudgetPage( page );
		completeCampaign = new CompleteCampaign( page );
		setupAdsAccountPage = new SetupAdsAccountPage( page );
		await Promise.all( [
			// Mock Jetpack as connected
			completeCampaign.mockJetpackConnected(),

			// Mock google as connected.
			completeCampaign.mockGoogleConnected(),

			// Mock Merchant Center as connected
			completeCampaign.mockMCConnected(),

			// Mock Google Ads as not yet connected.
			setupAdsAccountPage.mockAdsAccountDisconnected(),

			// Mock there is no existing Google Ads account.
			setupAdsAccountPage.mockAdsAccountsResponse( [] ),

			// Mock MC step as paid_ads
			completeCampaign.mockMCSetup( 'incomplete', 'paid_ads' ),

			// Mock MC target audience, only mocks GET method
			completeCampaign.fulfillTargetAudience(
				{
					location: 'selected',
					countries: [ 'US', 'TW', 'GB' ],
					locale: 'en_US',
					language: 'English',
				},
				[ 'GET' ]
			),

			// Mock syncable products count
			completeCampaign.fulfillSyncableProductsCountRequest( {
				count: 1024,
			} ),

			// The following mocks are requests will happen after completing the onboarding
			completeCampaign.mockSuccessfulSettingsSyncRequest(),

			completeCampaign.fulfillProductStatisticsRequest( {
				timestamp: 1695011644,
				statistics: {
					active: 0,
					expiring: 0,
					pending: 0,
					disapproved: 0,
					not_synced: 1137,
				},
				scheduled_sync: 1,
			} ),

			completeCampaign.fulfillAccountIssuesRequest( {
				issues: [],
				page: 1,
				total: 0,
			} ),

			completeCampaign.fulfillProductIssuesRequest( {
				issues: [],
				page: 1,
				total: 0,
			} ),

			completeCampaign.fulfillMCReview( {
				cooldown: 0,
				issues: [],
				reviewEligibleRegions: [],
				status: 'ONBOARDING',
			} ),

			dashboardPage.mockRequests(),
		] );

		await completeCampaign.goto();
	} );

	test.afterAll( async () => {
		await completeCampaign.closePage();
	} );

	test.describe( 'Complete onboarding by "Skip this step for now"', () => {
		test.beforeAll( async () => {
			// Reset the showing status for the "Set up paid ads" section.
			await page.evaluate( () => window.sessionStorage.clear() );
			await setupAdsAccountPage.mockAdsAccountConnected();
			await completeCampaign.goto();
			await completeCampaign.clickSkipStepButton();
		} );

		test( 'should see the setup success modal', async () => {
			const setupSuccessModal = page
				.locator( '.components-modal__content' )
				.filter( {
					hasText:
						'You’ve successfully set up Google Listings & Ads!',
				} );

			await page.waitForTimeout( 30000 );

			await expect( setupSuccessModal ).toBeVisible();
		} );
	} );
} );
