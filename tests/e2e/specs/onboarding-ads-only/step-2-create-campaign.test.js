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
import DashboardPage from '../../utils/pages/dashboard';
import {
	checkFAQExpandable,
	getFAQPanelTitle,
	getFAQPanelRow,
	checkBillingAdsPopup,
} from '../../utils/page';
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
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Create campaign for Ads only merchants', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupBudgetPage = new SetupBudgetPage( page );
		createCampaignPage = new CreateCampaignPage( page );
		setupAdsAccountPage = new SetupAdsAccountPage( page );

		await Promise.all( [
			// Mock Jetpack as connected
			createCampaignPage.mockJetpackConnected(),

			// Mock google as connected.
			createCampaignPage.mockGoogleConnected(),

			// Mock Ads account as connected and claimed.
			setupAdsAccountPage.mockAdsAccountConnected(),
			setupAdsAccountPage.mockAdsStatusClaimed(),

			// Mock that billing is pending.
			setupBudgetPage.fulfillBillingStatusRequest( {
				status: 'pending',
			} ),

			// Mock MC step as create_campaign
			createCampaignPage.mockMCSetup( 'incomplete', 'create_campaign' ),

			// Mock target audience, only mocks GET method
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
			setServiceBasedMerchant(),
		] );

		await createCampaignPage.goto();
	} );

	test.afterAll( async () => {
		await clearServiceBasedMerchant();
		await createCampaignPage.closePage();
	} );

	test( 'should see the heading and the texts below', async () => {
		await expect(
			page.getByRole( 'heading', {
				name: 'Create a campaign',
			} )
		).toBeVisible();

		await expect(
			page.getByText(
				'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your services will be included in the campaign after they’re approved.'
			)
		).toBeVisible();
	} );

	test.describe( 'FAQ panels', () => {
		test( 'should see four questions in FAQ', async () => {
			const faqTitles = getFAQPanelTitle( page );
			await expect( faqTitles ).toHaveCount( 4 );
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
			test( 'should see "Continue" button is disabled', async () => {
				const continueButton =
					createCampaignPage.getContinueSetupButton();
				await expect( continueButton ).toBeVisible();
				await expect( continueButton ).toBeDisabled();
			} );

			test( 'should see "Skip ads creation" button is enabled', async () => {
				const skipPaidAdsCreationButton =
					createCampaignPage.getSkipPaidAdsCreationButton();
				await expect( skipPaidAdsCreationButton ).toBeVisible();
				await expect( skipPaidAdsCreationButton ).toBeEnabled();
			} );

			test.describe( 'Setup up ads to a Google Ads account', () => {
				test( 'should see "Set your budget" section is enabled', async () => {
					const budgetSection = createCampaignPage.getBudgetSection();
					await expect( budgetSection ).toBeVisible();
				} );

				test( 'should see "Skip ads creation" is enabled and "Continue" button is disabled', async () => {
					const continueButton =
						createCampaignPage.getContinueSetupButton();
					await expect( continueButton ).toBeVisible();
					await expect( continueButton ).toBeDisabled();
					const skipButton =
						createCampaignPage.getSkipPaidAdsCreationButton();
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

				await createCampaignPage.goto();
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

				test( 'should see "Continue" button is enabled', async () => {
					const button = createCampaignPage.getContinueSetupButton();
					await expect( button ).toBeEnabled();
				} );

				test( 'should go to the next step when clicking "Continue" button', async () => {
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

					await createCampaignPage.clickContinueButton();

					await expect(
						page.getByRole( 'heading', {
							name: 'Optimize your campaign',
						} )
					).toBeVisible();
				} );
			} );

			test.describe( 'Budget recommendations', () => {
				test.beforeEach( async () => {
					await setupAdsAccountPage.mockAdsAccountIncomplete();
					await setupBudgetPage.fulfillBillingStatusRequest( {
						status: 'approved',
					} );
					await createCampaignPage.mockCompleteAdsSetup();
					await createCampaignPage.fulfillBudgetRecommendations();
					await createCampaignPage.goto();
					await page.evaluate( () => window.sessionStorage.clear() );
				} );

				test( 'Suggest a higher budget for getting back free credits', async () => {
					await setupBudgetPage.fillBudget( '8' );
					await createCampaignPage.clickContinueButton();

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

					await createCampaignPage.clickContinueButton();
				} );
			} );
		} );
	} );

	test.describe( 'Ask user for confirmation when clicking "Skip this step for now"', () => {
		test.describe( 'User skips paid ads creation', () => {
			test.describe( 'With WooCommerce tracking disabled', () => {
				test.beforeAll( async () => {
					await dashboardPage.fulfillAdsCampaignsRequest( [] );
					await setupAdsAccountPage.mockAdsAccountIncomplete();
					await dashboardPage.fulfillAdsCampaignsRequest( [] );
					await createCampaignPage.goto();
					await createCampaignPage.clickSkipPaidAdsCreationButton();
				} );

				test( 'should see the modal', async () => {
					const skipPaidAdsModal =
						createCampaignPage.getSkipPaidAdsCreationModal();
					await expect( skipPaidAdsModal ).toBeVisible();
				} );

				test( 'should see the url contains dashboard if the user skips', async () => {
					await createCampaignPage.clickCompleteSetupModalButton();
					await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
					expect( page.url() ).toMatch(
						/path=%2Fgoogle%2Fdashboard/
					);
				} );

				test( 'should see the setup success modal', async () => {
					const setupSuccessModal =
						createCampaignPage.getSetupSuccessModal();
					await expect( setupSuccessModal ).toBeVisible();
				} );

				test( 'should be able to close the setup success modal', async () => {
					const closeModalButton =
						createCampaignPage.getSetupSuccessModalCloseButton();
					await expect( closeModalButton ).toBeVisible();
					await expect( closeModalButton ).toBeEnabled();
					await closeModalButton.click();
					await expect(
						createCampaignPage.getSetupSuccessModal()
					).toBeHidden();
				} );

				test( 'should see Google Ads onboarding buttons on Dashboard', async () => {
					const { addPaidCampaignButton, createCampaignButton } =
						dashboardPage;

					await expect( addPaidCampaignButton ).toBeVisible();
					await expect( addPaidCampaignButton ).toBeEnabled();

					await expect( createCampaignButton ).toBeVisible();
					await expect( createCampaignButton ).toBeEnabled();
				} );
			} );

			test.describe( 'With WooCommerce tracking enabled', () => {
				test.beforeAll( async () => {
					// Reset the showing status for the "Set up paid ads" section.
					await page.evaluate( () => window.sessionStorage.clear() );
					await setupAdsAccountPage.mockAdsAccountIncomplete();
					await createCampaignPage.goto();
					// Mock WC Tracks as enabled
					await page.evaluate( () => {
						if ( window.wcTracks ) {
							window.wcTracks.isEnabled = true;
						} else {
							window.wcTracks = { isEnabled: true };
						}
					} );
					await createCampaignPage.clickSkipPaidAdsCreationButton();
				} );

				test( 'should display SkipPaidAdsSurveyModal when WC Tracks is enabled', async () => {
					const skipPaidAdsSurveyModal =
						createCampaignPage.getSkipPaidAdsSurveyModal();
					await expect( skipPaidAdsSurveyModal ).toBeVisible();
					// Optionally, check for a survey element inside the modal
					await expect(
						page.getByRole( 'button', {
							name: 'Send and complete setup',
						} )
					).toBeVisible();
				} );

				test( 'should show a text box when clicking the "I don’t want ads on Google" option', async () => {
					await page
						.getByRole( 'checkbox', {
							name: 'I don’t want ads on Google',
						} )
						.check();

					await expect(
						page.locator(
							'textarea[name="i_dont_want_ads_on_google_text"]'
						)
					).toBeVisible();
				} );

				test( 'should show a text box when clicking the "I’ll create ads later" option', async () => {
					await page
						.getByRole( 'checkbox', {
							name: 'I’ll create ads later',
						} )
						.check();

					await expect(
						page.locator(
							'textarea[name="ill_create_ads_later_text"]'
						)
					).toBeVisible();
				} );

				test( 'should show a text box when clicking the "Other" option', async () => {
					await page
						.getByRole( 'checkbox', { name: 'Other' } )
						.check();

					await expect(
						page.locator( 'textarea[name="other_text"]' )
					).toBeVisible();
				} );

				// @TODO: review when we have the onboarding completion flow updated.
				// test( 'should send survey and complete setup', async () => {
				// 	await createCampaignPage.clickSendAndCompleteSetupModalButton();
				// 	await page.waitForURL( /path=%2Fgoogle%2Fproduct-feed/ );
				// 	expect( page.url() ).toMatch(
				// 		/path=%2Fgoogle%2Fproduct-feed/
				// 	);
				// } );
			} );
		} );

		test.describe( 'User does not skip paid ads creation', () => {
			test.beforeAll( async () => {
				// Reset the showing status for the "Set up paid ads" section.
				await page.evaluate( () => window.sessionStorage.clear() );
				await setupAdsAccountPage.mockAdsAccountIncomplete();
				await createCampaignPage.goto();
				await createCampaignPage.clickSkipPaidAdsCreationButton();
			} );

			test( 'should no longer see the confirmation modal', async () => {
				await createCampaignPage.clickCancelModalButton();

				const skipPaidAdsModal =
					createCampaignPage.getSkipPaidAdsCreationModal();
				await expect( skipPaidAdsModal ).not.toBeVisible();
			} );

			test( 'user should stay on the same page', async () => {
				await expect( page.url() ).toMatch(
					/path=%2Fgoogle%2Fsetup-mc&google-mc=connected/
				);
			} );
		} );
	} );

	test.describe( 'EU Regulations section', () => {
		test( 'Displays the EU Regulations checkbox if the target audience contains an EU country', async () => {
			await createCampaignPage.fulfillTargetAudience(
				{
					location: 'selected',
					countries: [ 'GB' ],
					locale: 'en_US',
					language: 'English',
				},
				[ 'GET' ]
			);

			await createCampaignPage.goto();
			const checkbox = page.getByRole( 'checkbox', {
				name: "My ads include political content as defined by Google's EU political content policy.",
			} );
			await expect( checkbox ).toBeVisible();
		} );

		test( 'Does not display the EU Regulations section if the target audience does not contain an EU country', async () => {
			await createCampaignPage.fulfillTargetAudience(
				{
					location: 'selected',
					countries: [ 'MU' ],
					locale: 'en_US',
					language: 'English',
				},
				[ 'GET' ]
			);

			await createCampaignPage.goto();
			await page
				.getByText( 'Create a campaign to advertise your services', {
					exact: true,
				} )
				.waitFor( { state: 'visible' } );

			await expect(
				page.getByText( 'EU regulations' )
			).not.toBeVisible();
		} );
	} );

	test.describe( 'Choose Your Own Incentive (CYOI)', () => {
		test.beforeAll( async () => {
			await setupBudgetPage.fulfillBillingStatusRequest( {
				status: 'approved',
			} );
			await createCampaignPage.fulfillCYOIncentives();
			await createCampaignPage.fulfillApplyCYOIncentive();
			await createCampaignPage.goto();
		} );

		test( 'should show the incentive picker when billing is approved and offers are available', async () => {
			await expect( page.getByText( 'Ads credit offer' ) ).toBeVisible();
		} );

		test( 'should hide the incentive picker when no offers are available', async () => {
			await createCampaignPage.fulfillCYOIncentives( [] );
			await createCampaignPage.goto();
			await expect(
				page.getByText( 'Ads credit offer' )
			).not.toBeVisible();

			// Restore for subsequent tests.
			await createCampaignPage.fulfillCYOIncentives();
			await createCampaignPage.fulfillApplyCYOIncentive();
			await createCampaignPage.goto();
		} );

		test( 'should hide the incentive picker when billing is not yet approved', async () => {
			await setupBudgetPage.fulfillBillingStatusRequest( {
				status: 'pending',
			} );
			await createCampaignPage.goto();
			await expect(
				page.getByText( 'Ads credit offer' )
			).not.toBeVisible();

			// Restore for subsequent tests.
			await setupBudgetPage.fulfillBillingStatusRequest( {
				status: 'approved',
			} );
			await createCampaignPage.fulfillCYOIncentives();
			await createCampaignPage.fulfillApplyCYOIncentive();
			await createCampaignPage.goto();
		} );

		test( 'should apply the selected incentive on form submission', async () => {
			await dashboardPage.fulfillAdsCampaignsRequest( [] );
			const incentivePostPromise = page.waitForRequest(
				( request ) =>
					request.url().includes( '/gla/ads/incentive' ) &&
					request.method() === 'POST'
			);
			await createCampaignPage.clickSkipPaidAdsCreationButton();
			await createCampaignPage.clickCompleteSetupModalButton();
			const incentiveRequest = await incentivePostPromise;
			expect( incentiveRequest.method() ).toBe( 'POST' );
			expect( incentiveRequest.postDataJSON() ).toMatchObject( {
				id: 'incentive-medium-id',
			} );
			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
		} );

		test( 'should skip incentive application when no offer is available', async () => {
			await createCampaignPage.fulfillCYOIncentives( [] );
			await dashboardPage.fulfillAdsCampaignsRequest( [] );
			await createCampaignPage.goto();
			let incentivePostFired = false;
			const interceptor = ( route ) => {
				if ( route.request().method() === 'POST' ) {
					incentivePostFired = true;
				}
				route.fallback();
			};
			await page.route( /\/wc\/gla\/ads\/incentive\b/, interceptor );
			await createCampaignPage.clickSkipPaidAdsCreationButton();
			await createCampaignPage.clickCompleteSetupModalButton();
			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
			expect( incentivePostFired ).toBe( false );
			await page.unroute( /\/wc\/gla\/ads\/incentive\b/, interceptor );
		} );

		test( 'should still complete onboarding when applying the incentive fails', async () => {
			await createCampaignPage.fulfillCYOIncentives();
			await setupBudgetPage.fulfillBillingStatusRequest( {
				status: 'approved',
			} );
			await createCampaignPage.fulfillApplyCYOIncentive( {}, 500 );

			const incentivePostPromise = page.waitForRequest(
				( request ) =>
					request.url().includes( '/gla/ads/incentive' ) &&
					request.method() === 'POST'
			);

			await dashboardPage.fulfillAdsCampaignsRequest( [] );
			await createCampaignPage.goto();
			await createCampaignPage.clickSkipPaidAdsCreationButton();
			await createCampaignPage.clickCompleteSetupModalButton();

			const incentiveRequest = await incentivePostPromise;
			expect( incentiveRequest.method() ).toBe( 'POST' );

			await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );
		} );
	} );
} );
