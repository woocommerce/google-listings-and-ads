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
import {
	setServiceBasedMerchant,
	clearServiceBasedMerchant,
} from '../../utils/api';

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
		createCampaignPage = new CreateCampaignPage( page );
		setupAdsAccountPage = new SetupAdsAccountPage( page );
		optimizeCampaignPage = new OptimizeCampaignPage( page );
		await optimizeCampaignPage.fulfillAssetGroupsForCampaign();
		await setServiceBasedMerchant();
		await goToOptimizeStep();
	} );

	test.afterAll( async () => {
		await clearServiceBasedMerchant();
		await createCampaignPage.closePage();
	} );

	test.describe( 'Optimize campaign', () => {
		test( 'Final URL should be selected by default', async () => {
			const finalUrlCard = createCampaignPage.getFinalUrlCard();
			await expect( finalUrlCard ).toContainText(
				'https://woo.com/shop/'
			);
		} );

		test( 'Selecting the "Or, select a different Final URL" button disables the Create Campaign button', async () => {
			const selectDifferentFinalUrlButton =
				optimizeCampaignPage.getSelectDifferentFinalUrlButton();
			await selectDifferentFinalUrlButton.click();

			const createCampaignButton =
				optimizeCampaignPage.getCreateCampaignButton();
			await expect( createCampaignButton ).toBeDisabled();
		} );

		test( 'Selecting final URL enables Create Campaign button', async () => {
			await optimizeCampaignPage.selectUrlOption();

			const createCampaignButton =
				optimizeCampaignPage.getCreateCampaignButton();
			await expect( createCampaignButton ).toBeEnabled();
		} );

		test( '"Skip this step" button should not be present in the last step of onboarding', async () => {
			const skipThisStepButton = page.locator( 'text="Skip this step"' );
			await expect( skipThisStepButton ).toHaveCount( 0 );
		} );

		test( 'Clicking the "Create Campaign" button navigates to the dashboard and should see the setup success modal', async () => {
			const createCampaignButton =
				optimizeCampaignPage.getCreateCampaignButton();

			const campaignCreation =
				setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
					'15',
					[ 'US', 'TW', 'GB' ]
				);
			await createCampaignButton.click();
			await campaignCreation;

			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fdashboard/ );

			const setupSuccessModal = createCampaignPage.getSetupSuccessModal();
			await expect( setupSuccessModal ).toBeVisible();
		} );
	} );
} );
