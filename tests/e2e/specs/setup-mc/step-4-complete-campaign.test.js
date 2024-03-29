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

test.describe( 'Complete your campaign', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
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

			// Mock Ads account as connected.
			setupAdsAccountPage.mockAdsAccountConnected(),

			// Mock there is no existing Google Ads account.
			setupAdsAccountPage.mockAdsAccountsResponse( [] ),

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
			test.beforeAll( async () => {
				await setupAdsAccountPage.mockAdsAccountConnected();
				await completeCampaign.goto();
				await completeCampaign.clickCreatePaidAdButton();
			} );

			test( 'should not see the "Create a paid ad campaign" button after this section is shown', async () => {
				const button = completeCampaign.getCreatePaidAdButton();
				await expect( button ).toBeHidden();
			} );

			test( 'should see "Complete setup" button is disabled', async () => {
				const completeSetupButton =
					completeCampaign.getCompleteSetupButton();
				await expect( completeSetupButton ).toBeVisible();
				await expect( completeSetupButton ).toBeDisabled();
			} );

			test( 'should see "Skip paid ads creation" button is enabled', async () => {
				const skipPaidAdsCreationButton =
					completeCampaign.getSkipPaidAdsCreationButton();
				await expect( skipPaidAdsCreationButton ).toBeVisible();
				await expect( skipPaidAdsCreationButton ).toBeEnabled();
			} );

			test.describe( 'Setup up ads to a Google Ads account', () => {
				test( 'should see "Ads audience" section is enabled', async ( page ) => {
					const adsAudienceSection =
						completeCampaign.getAdsAudienceSection();
					await expect( adsAudienceSection ).toBeVisible();

					// check if the section has the class.
					await expect(
						adsAudienceSection.locator( 'h1' )
					).toContainText( 'Ads audience' );
				} );

				test( 'should see "Set your budget" section is enabled', async () => {
					const budgetSection = completeCampaign.getBudgetSection();
					await expect( budgetSection ).toBeVisible();
				} );

				test( 'should see "Skip paid ads creation" is enabled and "Complete setup" button is disabled', async () => {
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
				await completeCampaign.goto();
			} );

			test.describe( 'Select audience', () => {
				test( 'should see only three country tags in country input search box', async () => {
					const countrySearchBoxContainer =
						getCountryInputSearchBoxContainer( page );
					const countryTags =
						getCountryTagsFromInputSearchBoxContainer( page );
					await expect( countryTags ).toHaveCount( 3 );
					await expect( countrySearchBoxContainer ).toContainText(
						'United States'
					);
					await expect( countrySearchBoxContainer ).toContainText(
						'Taiwan'
					);
					await expect( countrySearchBoxContainer ).toContainText(
						'United Kingdom'
					);
				} );

				test( 'should only allow searching for the same set of the countries selected in step 2, which is returned by target audience API', async () => {
					const treeSelectMenu = getTreeSelectMenu( page );

					await fillCountryInSearchBox( page, 'United States' );
					await expect( treeSelectMenu ).toBeVisible();

					await fillCountryInSearchBox( page, 'United Kingdom' );
					await expect( treeSelectMenu ).toBeVisible();

					await fillCountryInSearchBox( page, 'Taiwan' );
					await expect( treeSelectMenu ).toBeVisible();

					await fillCountryInSearchBox( page, 'Japan' );
					await expect( treeSelectMenu ).not.toBeVisible();

					await fillCountryInSearchBox( page, 'Spain' );
					await expect( treeSelectMenu ).not.toBeVisible();
				} );

				test( 'should see the budget recommendation value changed, and see the budget recommendation request is triggered when changing the ads audience', async () => {
					let textContent = await setupBudgetPage
						.getBudgetRecommendationTextRow()
						.textContent();

					const textBeforeRemoveCountry =
						setupBudgetPage.extractBudgetRecommendationValue(
							textContent
						);

					const responsePromise =
						setupBudgetPage.registerBudgetRecommendationResponse();

					await removeCountryFromSearchBox(
						page,
						'United Kingdom (UK)'
					);

					await responsePromise;

					textContent = await setupBudgetPage
						.getBudgetRecommendationTextRow()
						.textContent();

					const textAfterRemoveCountry =
						setupBudgetPage.extractBudgetRecommendationValue(
							textContent
						);

					await expect( textBeforeRemoveCountry ).not.toBe(
						textAfterRemoveCountry
					);
				} );
			} );

			test.describe( 'Set up budget', () => {
				test( 'should see the low budget tip when the buget is set lower than the recommended value', async () => {
					await setupBudgetPage.fillBudget( '1' );
					const lowBudgetTip = setupBudgetPage.getLowerBudgetTip();
					await expect( lowBudgetTip ).toBeVisible();
				} );

				test( 'should not see the low budget tip when the buget is set higher than the recommended value', async () => {
					await setupBudgetPage.fillBudget( '99999' );
					const lowBudgetTip = setupBudgetPage.getLowerBudgetTip();
					await expect( lowBudgetTip ).not.toBeVisible();
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
					await setupBudgetPage.fulfillBillingStatusRequest( {
						status: 'approved',
					} );

					await newPage.close();
					await page.reload();

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
					await completeCampaign.fulfillAdsCampaignsRequest();
					const requestsPromises =
						completeCampaign.registerCompleteSetupRequests();
					await completeCampaign.clickCompleteSetupButton();
					await requestsPromises;

					const setupSuccessModal = page
						.locator( '.components-modal__content' )
						.filter( {
							hasText:
								'You’ve successfully set up Google Listings & Ads!',
						} );
					await expect( setupSuccessModal ).toBeVisible();
				} );
			} );
		} );
	} );

	test.describe( 'Complete onboarding by "Skip this step for now"', () => {
		test.beforeAll( async () => {
			// Reset the showing status for the "Set up paid ads" section.
			await page.evaluate( () => window.sessionStorage.clear() );
			await setupAdsAccountPage.mockAdsAccountIncomplete();
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
			await expect( setupSuccessModal ).toBeVisible();
		} );

		test( 'should see the url contains product-feed', async () => {
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fproduct-feed/ );
		} );
	} );

	test.describe( 'Complete onboarding by "Skip paid ads creation"', () => {
		test.beforeAll( async () => {
			await setupAdsAccountPage.mockAdsAccountIncomplete();
			await completeCampaign.goto();
			await completeCampaign.clickCreatePaidAdButton();
			await completeCampaign.clickSkipPaidAdsCreationButon();
		} );

		test( 'should also see the setup success modal', async () => {
			const setupSuccessModal = page
				.locator( '.components-modal__content' )
				.filter( {
					hasText:
						'You’ve successfully set up Google Listings & Ads!',
				} );
			await expect( setupSuccessModal ).toBeVisible();
		} );

		test( 'should also see the url contains product-feed', async () => {
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fproduct-feed/ );
		} );

		test( 'should see buttons on Dashboard for Google Ads onboarding', async () => {
			await page.keyboard.press( 'Escape' );
			await page.getByRole( 'tab', { name: 'Dashboard' } ).click();

			const buttons = page.getByRole( 'button', {
				name: 'Add paid campaign',
			} );

			await expect( buttons ).toHaveCount( 2 );
			for ( const button of await buttons.all() ) {
				await expect( button ).toBeVisible();
				await expect( button ).toBeEnabled();
			}
		} );
	} );
} );
