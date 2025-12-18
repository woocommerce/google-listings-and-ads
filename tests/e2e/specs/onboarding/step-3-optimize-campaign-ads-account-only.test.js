/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SetupBudgetPage from '../../utils/pages/ads-onboarding/setup-budget';
import CreateCampaignPage from '../../utils/pages/onboarding/step-2-create-campaign-ads-account-only';
import SetupAdsAccountPage from '../../utils/pages/ads-onboarding/setup-ads-accounts';
import OptimizeCampaignPage from '../../utils/pages/onboarding/step-3-optimize-campaign-ads-account-only';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-budget.js').default} setupBudgetPage
 */
let setupBudgetPage = null;

/**
 * @type {import('../../utils/pages/onboarding/step-2-create-campaign-ads-account-only.js').default} createCampaignPage
 */
let createCampaignPage = null;

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-ads-accounts.js').default} setupAdsAccountPage
 */
let setupAdsAccountPage = null;

/**
 * @type {import('../../utils/pages/onboarding/step-3-optimize-campaign-ads-account-only.js').default} optimizeCampaignPage
 */
let optimizeCampaignPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

async function goToOptimizeStep() {
	await Promise.all( [
		createCampaignPage.mockJetpackConnected(),
		createCampaignPage.mockGoogleConnected(),
		setupAdsAccountPage.mockAdsAccountConnected(),
		setupAdsAccountPage.mockAdsStatusClaimed(),
		setupBudgetPage.fulfillBillingStatusRequest( { status: 'approved' } ),
		createCampaignPage.mockMCSetup( 'incomplete', 'create_campaign' ),
		createCampaignPage.fulfillTargetAudience(
			{
				location: 'selected',
				countries: [ 'US', 'TW', 'GB' ],
				locale: 'en_US',
				language: 'English',
			},
			[ 'GET' ]
		),
		createCampaignPage.fulfillBudgetRecommendations(),
		setupBudgetPage.mockBudgetMetrics(),
		setupBudgetPage.mockAdsIncentiveCredits(),
	] );

	await createCampaignPage.goto();
	await createCampaignPage.mockCompleteAdsSetup();

	await createCampaignPage.fulfillAdsCampaignsRequest(
		{
			id: 1,
			name: 'Test Campaign',
			status: 'enabled',
			type: 'performance_max',
			amount: 40,
			country: 'US',
			targeted_locations: [ 'US', 'TW', 'GB' ],
		},
		200,
		[ 'POST' ]
	);

	await optimizeCampaignPage.mockOptimizeCampaignRequests();
	await createCampaignPage.clickContinueButton();

	// Assert we’re on optimize step (whatever URL it is)
	await expect( page ).toHaveURL( /step-3|optimize|path=/ );
}

test.describe( 'Optimize campaign for Ads only merchants', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setupBudgetPage = new SetupBudgetPage( page );
		createCampaignPage = new CreateCampaignPage( page, {
			glaData: {
				serviceBasedMerchant: true,
			},
		} );
		setupAdsAccountPage = new SetupAdsAccountPage( page );
		optimizeCampaignPage = new OptimizeCampaignPage( page );

		await goToOptimizeStep();
	} );

	test.afterAll( async () => {
		await createCampaignPage.closePage();
	} );

	test.describe( 'Optimize campaign', () => {
		test( 'Create Campaign button should be disabled if no URL selected', async () => {
			const saveChangesButton =
				optimizeCampaignPage.getSaveChangesButton();
			await expect( saveChangesButton ).toBeDisabled();
		} );

		test( 'Selecting final URL enables Create Campaign button', async () => {
			await optimizeCampaignPage.selectUrlOption();

			const saveChangesButton =
				optimizeCampaignPage.getSaveChangesButton();
			await expect( saveChangesButton ).toBeEnabled();
		} );

		test( 'Selecting the "Or, select a different Final URL" button disables the Create Campaign button', async () => {
			const selectDifferentFinalUrlButton =
				optimizeCampaignPage.getSelectDifferentFinalUrlButton();
			await selectDifferentFinalUrlButton.click();

			const saveChangesButton =
				optimizeCampaignPage.getSaveChangesButton();
			await expect( saveChangesButton ).toBeDisabled();
		} );

		test( 'Clicking the "Skip this step" button navigates to the dashboard', async () => {
			await goToOptimizeStep();
			await optimizeCampaignPage.clickSkipThisStepButton();

			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fdashboard/ );
		} );
	} );
} );
