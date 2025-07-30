/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SetupBudgetPage from '../../utils/pages/ads-onboarding/setup-budget';
import CompleteCampaign from '../../utils/pages/onboarding/step-3-complete-campaign';
import SetupAdsAccountPage from '../../utils/pages/ads-onboarding/setup-ads-accounts';
import DashboardPage from '../../utils/pages/dashboard';
import {
	checkFAQExpandable,
	getFAQPanelTitle,
	getFAQPanelRow,
	checkBillingAdsPopup,
} from '../../utils/page';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-budget.js').default} setupBudgetPage
 */
let setupBudgetPage = null;

/**
 * @type {import('../../utils/pages/onboarding/step-3-complete-campaign.js').default} completeCampaign
 */
let completeCampaign = null;

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-ads-accounts.js').default} setupAdsAccountPage
 */
let setupAdsAccountPage = null;

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Complete your campaign', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setupBudgetPage = new SetupBudgetPage( page );
		dashboardPage = new DashboardPage( page );
		completeCampaign = new CompleteCampaign( page );
		setupAdsAccountPage = new SetupAdsAccountPage( page );
		await Promise.all( [
			// Mock Jetpack as connected
			completeCampaign.mockJetpackConnected(),

			// Mock google as connected.
			completeCampaign.mockGoogleConnected(),

			// Mock Merchant Center as connected
			completeCampaign.mockMCConnected(),

			// Mock Ads account as connected and claimed.
			setupAdsAccountPage.mockAdsAccountConnected(),
			setupAdsAccountPage.mockAdsStatusClaimed(),

			// Mock that billing is pending.
			setupBudgetPage.fulfillBillingStatusRequest( {
				status: 'pending',
			} ),

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

			completeCampaign.fulfillBudgetRecommendations(),
			setupBudgetPage.mockBudgetMetrics(),
			setupBudgetPage.mockAdsIncentiveCredits(),

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

			completeCampaign.fulfillMCReportProgram( {
				free_listings: null,
				products: null,
				intervals: null,
				totals: {
					clicks: 0,
					impressions: 0,
				},
				next_page: null,
			} ),
		] );

		await completeCampaign.goto();
	} );

	test.afterAll( async () => {
		await completeCampaign.closePage();
	} );

	test( 'should see the heading and the texts below', async () => {
		await expect(
			page.getByRole( 'heading', {
				name: 'Create a campaign to advertise your products',
			} )
		).toBeVisible();

		await expect(
			page.getByText(
				'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your products will be included in the campaign after they’re approved.'
			)
		).toBeVisible();
	} );

	test.describe( 'FAQ panels', () => {
		test( 'should see five questions in FAQ', async () => {
			const faqTitles = getFAQPanelTitle( page );
			await expect( faqTitles ).toHaveCount( 5 );
		} );

		test( 'should not see FAQ rows when FAQ titles are not clicked', async () => {
			const faqRows = getFAQPanelRow( page );
			await expect( faqRows ).toHaveCount( 0 );
		} );

		// eslint-disable-next-line jest/expect-expect
		test( 'should see FAQ rows when all FAQ titles are clicked', async () => {
			await checkFAQExpandable( page );
		} );
	} );

	test.describe( 'Set up paid ads', () => {
		test.describe( 'Click "Create a paid ad campaign" button', () => {
			test( 'should see "Complete setup" button is disabled', async () => {
				const completeSetupButton =
					completeCampaign.getCompleteSetupButton();
				await expect( completeSetupButton ).toBeVisible();
				await expect( completeSetupButton ).toBeDisabled();
			} );

			test( 'should see "Skip ads creation" button is enabled', async () => {
				const skipPaidAdsCreationButton =
					completeCampaign.getSkipPaidAdsCreationButton();
				await expect( skipPaidAdsCreationButton ).toBeVisible();
				await expect( skipPaidAdsCreationButton ).toBeEnabled();
			} );

			test.describe( 'Setup up ads to a Google Ads account', () => {
				test( 'should see "Set your budget" section is enabled', async () => {
					const budgetSection = completeCampaign.getBudgetSection();
					await expect( budgetSection ).toBeVisible();
				} );

				test( 'should see "Skip ads creation" is enabled and "Complete setup" button is disabled', async () => {
					const completeButton =
						completeCampaign.getCompleteSetupButton();
					await expect( completeButton ).toBeVisible();
					await expect( completeButton ).toBeDisabled();

					const skipButton =
						completeCampaign.getSkipPaidAdsCreationButton();
					await expect( skipButton ).toBeVisible();
					await expect( skipButton ).toBeEnabled();
				} );
			} );
		} );

		test.describe( 'Set up a campaign', () => {
			test.beforeAll( async () => {
				await setupAdsAccountPage.mockAdsAccountConnected();
				await setupBudgetPage.fulfillBudgetRecommendations( {
					currency: 'TWD',
					daily_budget_baseline: 100,
					recommendations: [
						{
							level: 'Recommended',
							country: 'TW',
							daily_budget: 120,
							metrics: {
								cost: 700,
								conversions: 2.2,
								conversions_value: 89.98,
							},
						},
						{
							level: 'High',
							country: 'TW',
							daily_budget: 200,
							metrics: {
								cost: 1400,
								conversions: 2.5,
								conversions_value: 98.59,
							},
						},
						{
							level: 'Low',
							country: 'TW',
							daily_budget: 50,
							metrics: {
								cost: 350,
								conversions: 2,
								conversions_value: 80.48,
							},
						},
					],
				} );

				await completeCampaign.goto();
			} );

			test.describe( 'Set up custom budget', () => {
				test( 'The input in the "Set custom budget" option should have been set to the recommended value by default', async () => {
					await page.getByLabel( 'custom' ).click();
					await expect(
						setupBudgetPage.getBudgetInput()
					).toHaveValue( '120.00' );
				} );
			} );

			test.describe( 'Validate budget percent for the custom budget', () => {
				test( 'should see validation error if lower than the 30%', async () => {
					await setupBudgetPage.fillBudget( '10' );
					await setupBudgetPage.getBudgetInput().blur();

					await expect( page.getByRole( 'alert' ) ).toHaveText(
						'Please make sure daily average cost is at least NT$30.00'
					);
				} );

				test( 'should see validation error if slightly less than the 30%', async () => {
					await setupBudgetPage.fillBudget( '29.99' );
					await setupBudgetPage.getBudgetInput().blur();

					await expect( page.getByRole( 'alert' ) ).toHaveText(
						'Please make sure daily average cost is at least NT$30.00'
					);
				} );

				test( 'should not see validation error if exactly 30%', async () => {
					await setupBudgetPage.fillBudget( '30' );
					await setupBudgetPage.getBudgetInput().blur();

					await expect( page.getByRole( 'alert' ) ).not.toBeVisible();
				} );

				test( 'should not see validation error if slightly greater than 30%', async () => {
					await setupBudgetPage.fillBudget( '30.5' );
					await setupBudgetPage.getBudgetInput().blur();

					await expect( page.getByRole( 'alert' ) ).not.toBeVisible();
				} );

				test( 'should display the recommended budget if the budget is valid but lower than the lowest recommended value', async () => {
					await setupBudgetPage.fillBudget( '40' );
					await setupBudgetPage.getBudgetInput().blur();

					await expect(
						page.getByText(
							`Your budget is lower than other advertisers' budgets, which may affect performance. For best results, we recommend at least NT$120.00 per day.`
						)
					).toBeVisible();
				} );
			} );

			test.describe( 'Set up billing', () => {
				let newPage;

				test( 'should see set up billing button is enabled', async () => {
					const setUpBillingButton =
						setupBudgetPage.getSetUpBillingButton();
					await expect( setUpBillingButton ).toBeEnabled();
				} );

				test( 'should see the correct set up billing link', async () => {
					const setUpBillingLink =
						setupBudgetPage.getSetUpBillingLink();
					await expect( setUpBillingLink ).toHaveAttribute(
						'href',
						'https://support.google.com/google-ads/answer/2375375'
					);
				} );

				// eslint-disable-next-line jest/expect-expect
				test( 'should open a popup when clicking set up billing button', async () => {
					await checkBillingAdsPopup( page );
				} );

				test( 'should open a new page when clicking set up billing link', async () => {
					const newPagePromise = page.waitForEvent( 'popup' );
					await setupBudgetPage.clickSetUpBillingLink();
					newPage = await newPagePromise;
					await newPage.waitForLoadState();
					const newPageTitle = await newPage.title();
					const newPageURL = newPage.url();
					expect( newPageTitle ).toBe(
						'Add a new payment method in Google Ads - Google Ads Help'
					);
					expect( newPageURL ).toBe(
						'https://support.google.com/google-ads/answer/2375375'
					);
				} );

				test( 'should see billing has been set up successfully when billing status API returns approved', async () => {
					await setupBudgetPage.mockAdsAccountsResponse( {
						id: 12345,
						billing_url: null,
					} );
					await setupBudgetPage.fulfillBillingStatusRequest( {
						status: 'pending',
					} );

					await newPage.close();
					await setupBudgetPage.fulfillBillingStatusRequest( {
						status: 'approved',
					} );

					const requestPromise =
						setupBudgetPage.awaitForBillingStatusRequest();

					// Regain page focus to trigger a new request to update billing status.
					await page.dispatchEvent( 'body', 'blur' );
					await page.dispatchEvent( 'body', 'focus' );

					await requestPromise;

					const billingSetupSuccessSection =
						setupBudgetPage.getBillingSetupSuccessSection();
					await expect( billingSetupSuccessSection ).toContainText(
						'Billing method for Google Ads added successfully'
					);
				} );

				test( 'should see "Complete setup" button is enabled', async () => {
					const button = completeCampaign.getCompleteSetupButton();
					await expect( button ).toBeEnabled();
				} );

				test( 'should go to "Product Feed" when clicking "Complete setup" button', async () => {
					await completeCampaign.mockCompleteAdsSetup();
					await completeCampaign.fulfillAdsCampaignsRequest(
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

					const requestsPromises =
						completeCampaign.registerCompleteSetupRequests();
					await completeCampaign.clickCompleteSetupButton();
					await requestsPromises;

					const setupSuccessModal =
						completeCampaign.getSetupSuccessModal();
					await expect( setupSuccessModal ).toBeVisible();
				} );
			} );

			test.describe( 'Budget recommendations', () => {
				test.beforeEach( async () => {
					await page.evaluate( () => window.sessionStorage.clear() );
					await setupAdsAccountPage.mockAdsAccountIncomplete();
					await setupBudgetPage.fulfillBillingStatusRequest( {
						status: 'approved',
					} );
					await completeCampaign.mockCompleteAdsSetup();
					await completeCampaign.fulfillBudgetRecommendations();
					await completeCampaign.goto();
				} );

				test( 'Create a campaign with a selected option from the budget recommendations', async () => {
					// The recommended option is selected by default
					await expect(
						page.getByLabel( 'recommended' )
					).toBeChecked();

					const highOption = page.getByLabel( 'high' );

					await highOption.click();
					await expect( highOption ).toBeChecked();

					const campaignCreation =
						setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
							'20.5',
							[ 'US', 'TW', 'GB' ]
						);

					await completeCampaign.clickCompleteSetupButton();
					await campaignCreation;
				} );

				test( 'Suggest a higher budget for getting back free credits', async () => {
					await setupBudgetPage.fillBudget( '8' );
					await completeCampaign.clickCompleteSetupButton();

					const confirmButton = page.getByRole( 'button', {
						name: 'Change budget',
					} );

					await expect(
						page.getByText( 'This offer won’t last long!' )
					).toBeVisible();
					await expect( confirmButton ).toBeEnabled();

					await setupBudgetPage.getBudgetInput().fill( '8.33' );

					await expect( confirmButton ).toBeDisabled();

					await setupBudgetPage.getBudgetInput().fill( '8.5' );

					await expect( confirmButton ).toBeEnabled();

					await confirmButton.click();

					await expect( confirmButton ).not.toBeVisible();

					await expect(
						setupBudgetPage.getBudgetInput()
					).toHaveValue( '8.50' );

					const campaignCreation =
						setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
							'8.5',
							[ 'US', 'TW', 'GB' ]
						);

					await completeCampaign.clickCompleteSetupButton();
					await campaignCreation;
				} );
			} );
		} );
	} );

	test.describe( 'Ask user for confirmation when clicking "Skip this step for now"', () => {
		test.describe( 'User skips paid ads creation', () => {
			test.beforeAll( async () => {
				// Reset the showing status for the "Set up paid ads" section.
				await page.evaluate( () => window.sessionStorage.clear() );
				await setupAdsAccountPage.mockAdsAccountIncomplete();
				await completeCampaign.goto();
				await completeCampaign.clickSkipPaidAdsCreationButton();
			} );

			test( 'should see the modal', async () => {
				const skipPaidAdsModal =
					completeCampaign.getSkipPaidAdsCreationModal();
				await expect( skipPaidAdsModal ).toBeVisible();
			} );

			test( 'should see the url contains product-feed if the user skips', async () => {
				await completeCampaign.clickCompleteSetupModalButton();
				await page.waitForURL( /path=%2Fgoogle%2Fproduct-feed/ );
				expect( page.url() ).toMatch( /path=%2Fgoogle%2Fproduct-feed/ );
			} );

			test( 'should see the setup success modal', async () => {
				const setupSuccessModal =
					completeCampaign.getSetupSuccessModal();
				await expect( setupSuccessModal ).toBeVisible();
			} );

			test( 'should see buttons on Dashboard for Google Ads onboarding', async () => {
				await page.keyboard.press( 'Escape' );
				await page.getByRole( 'tab', { name: 'Dashboard' } ).click();
				const { addPaidCampaignButton, createCampaignButton } =
					dashboardPage;

				await expect( addPaidCampaignButton ).toBeVisible();
				await expect( addPaidCampaignButton ).toBeEnabled();

				await expect( createCampaignButton ).toBeVisible();
				await expect( createCampaignButton ).toBeEnabled();
			} );
		} );

		test.describe( 'User does not skip paid ads creation', () => {
			test.beforeAll( async () => {
				// Reset the showing status for the "Set up paid ads" section.
				await page.evaluate( () => window.sessionStorage.clear() );
				await setupAdsAccountPage.mockAdsAccountIncomplete();
				await completeCampaign.goto();
				await completeCampaign.clickSkipPaidAdsCreationButton();
			} );

			test( 'should no longer see the confirmation modal', async () => {
				await completeCampaign.clickCancelModalButton();

				const skipPaidAdsModal =
					completeCampaign.getSkipPaidAdsCreationModal();
				await expect( skipPaidAdsModal ).not.toBeVisible();
			} );

			test( 'user should stay on the same page', async () => {
				await expect( page.url() ).toMatch(
					/path=%2Fgoogle%2Fsetup-mc&google-mc=connected/
				);
			} );
		} );
	} );

	test.describe( 'Enhanced conversion prompt', () => {
		test.beforeAll( async () => {
			await page.evaluate( () => {
				window.sessionStorage.clear();
			} );
			await setupAdsAccountPage.mockAdsAccountConnected();
			await completeCampaign.goto();
			await completeCampaign.clickSkipPaidAdsCreationButton();
			await completeCampaign.clickCompleteSetupModalButton();
		} );

		test( 'should see the setup success modal', async () => {
			const setupSuccessModal = completeCampaign.getSetupSuccessModal();
			await expect( setupSuccessModal ).toBeVisible();
		} );

		test.describe( 'Ads setup is incomplete', () => {
			test( 'should have three prompts in the setup success modal', async () => {
				const guideControls = page.getByRole( 'list', {
					name: 'Guide controls',
				} );
				const guideControlsItems =
					guideControls.getByRole( 'listitem' );
				await expect( guideControlsItems ).toHaveCount( 3 );
			} );

			test( 'should see the "Enhanced Conversions" prompt in the setup success modal', async () => {
				const guideControls = page.getByRole( 'list', {
					name: 'Guide controls',
				} );
				const guideControlsItems =
					guideControls.getByRole( 'listitem' );
				await guideControlsItems.nth( 1 ).click();
				await expect(
					page.getByText(
						'Improve conversion tracking accuracy to improve campaign performance'
					)
				).toBeVisible();
			} );

			test( 'should see the "Set up Enhanced Conversions" button in the setup success modal', async () => {
				const enhancedConversionsButton = page.getByRole( 'button', {
					name: 'Set up Enhanced Conversions',
				} );
				await expect( enhancedConversionsButton ).toBeVisible();

				const dataAction =
					await enhancedConversionsButton.getAttribute(
						'data-action'
					);
				expect( dataAction ).toBe(
					'view-enhanced-conversions-settings'
				);
			} );
		} );

		test.describe( 'Ads setup is complete', async () => {
			test.beforeAll( async () => {
				await completeCampaign.goto();
				await completeCampaign.clickSkipPaidAdsCreationButton();
				await completeCampaign.clickCompleteSetupModalButton();
				await page.waitForNavigation( {
					waitUntil: 'domcontentloaded',
				} );
				await page.evaluate( () => {
					window.glaData.adsSetupComplete = true;
				} );
			} );

			test( 'should have two prompts in the setup success modal', async () => {
				const guideControls = page.getByRole( 'list', {
					name: 'Guide controls',
				} );
				const guideControlsItems =
					guideControls.getByRole( 'listitem' );
				await guideControlsItems.nth( 1 ).click();
				await expect( guideControlsItems ).toHaveCount( 2 );
			} );
		} );

		test( 'should navigate to settings page when clicking "Set up Enhanced Conversions" button', async () => {
			const enhancedConversionsButton = page.getByRole( 'button', {
				name: 'Set up Enhanced Conversions',
			} );
			await enhancedConversionsButton.click();

			await page.waitForURL( /path=%2Fgoogle%2Fsettings/ );
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fsettings/ );

			const setupSuccessModal = completeCampaign.getSetupSuccessModal();
			await expect( setupSuccessModal ).not.toBeVisible();
		} );
	} );

	test.describe( 'Free Ad Credit', () => {
		test( 'should see the Free Ad Credit section always', async () => {
			await setupAdsAccountPage.mockAdsAccountConnected();
			await completeCampaign.goto();
			await setupAdsAccountPage.awaitAdsConnectionResponse();

			// Check we are on the correct page.
			await expect(
				page.getByText( 'Create a campaign to advertise your products' )
			).toBeVisible();

			await expect(
				page.getByText(
					'Spend $500 to get $500 in Google Ads credits!'
				)
			).toBeVisible();
		} );
	} );
} );
