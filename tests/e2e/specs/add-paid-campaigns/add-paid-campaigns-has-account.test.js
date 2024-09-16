/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';
/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard.js';
import SetupAdsAccountsPage from '../../utils/pages/setup-ads/setup-ads-accounts.js';
import SetupBudgetPage from '../../utils/pages/setup-ads/setup-budget';
import { LOAD_STATE } from '../../utils/constants';
import {
	getCountryInputSearchBoxContainer,
	getCountryTagsFromInputSearchBoxContainer,
	getFAQPanelTitle,
	getFAQPanelRow,
	checkFAQExpandable,
	checkBillingAdsPopup,
} from '../../utils/page';

const ADS_ACCOUNTS = [
	{
		id: 1111111,
		name: 'Test 1',
	},
	{
		id: 2222222,
		name: 'Test 2',
	},
];

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('../../utils/pages/setup-ads/setup-ads-accounts').default} setupAdsAccounts
 */
let setupAdsAccounts = null;

/**
 * @type {import('@playwright/test').Locator} adsConnectionButton
 */
let adsConnectionButton = null;

/**
 * @type {import('../../utils/pages/setup-ads/setup-budget.js').default} setupBudgetPage
 */
let setupBudgetPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Set up paid campaign', () => {
	// The campaign budget
	let budget = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupAdsAccounts = new SetupAdsAccountsPage( page );
		await setOnboardedMerchant();
		await setupAdsAccounts.mockAdsAccountsResponse( [] );
		await dashboardPage.mockRequests();
		await dashboardPage.goto();

		// Add a connected Ads account.
		await setupAdsAccounts.fulfillAdsConnection( {
			id: ADS_ACCOUNTS[ 0 ].id,
			currency: 'USD',
			symbol: '$',
			status: 'connected',
			step: '',
		} );
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test( 'Dashboard page contains Add Paid campaign buttons', async () => {
		//Add page campaign in the Performance (Paid Campaigns) section
		await expect(
			dashboardPage.getAdsConnectionAllProgramsButton( 'summary-card' )
		).toBeEnabled();

		//Add page campaign in the programs section.
		adsConnectionButton = dashboardPage.getAdsConnectionAllProgramsButton();
		await expect( adsConnectionButton ).toBeEnabled();
	} );

	test.describe( 'Create your paid campaign', () => {
		test.beforeAll( async () => {
			setupBudgetPage = new SetupBudgetPage( page );
			await adsConnectionButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
		} );

		test( 'Create paid campaign page', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Create your paid campaign',
				} )
			).toBeVisible();

			await expect(
				page.getByRole( 'heading', { name: 'Ads audience' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'heading', { name: 'Set your budget' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'button', { name: 'Continue' } )
			).toBeDisabled();

			await expect(
				page.getByRole( 'link', {
					name: 'See what your ads will look like.',
				} )
			).toBeVisible();
		} );

		test.describe( 'Preview product ad', () => {
			test( 'Preview product ad should be visible', async () => {
				await expect(
					page.getByText( 'Preview product ad' )
				).toBeVisible();
				await expect(
					page.getByText(
						"Each of your product variants will have its own ad. Previews shown here are examples and don't include all possible formats."
					)
				).toBeVisible();
			} );

			test( 'Change image buttons should be enabled', async () => {
				const buttonsToChangeImage = page.locator(
					'.gla-campaign-preview-card__moving-button'
				);

				expect( buttonsToChangeImage ).toHaveCount( 2 );

				for ( const button of await buttonsToChangeImage.all() ) {
					await expect( button ).toBeEnabled();
				}
			} );
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

		test( 'Audience should be United States', async () => {
			const countrySearchBoxContainer =
				getCountryInputSearchBoxContainer( page );
			const countryTags =
				getCountryTagsFromInputSearchBoxContainer( page );
			await expect( countryTags ).toHaveCount( 1 );
			await expect( countrySearchBoxContainer ).toContainText(
				'United States'
			);
		} );

		test( 'Set the budget', async () => {
			budget = '0';
			await setupBudgetPage.fillBudget( budget );

			await expect(
				page.getByRole( 'button', { name: 'Continue' } )
			).toBeDisabled();

			budget = '1';
			await setupBudgetPage.fillBudget( budget );

			await expect(
				page.getByRole( 'button', { name: 'Continue' } )
			).toBeEnabled();
		} );

		test( 'Budget Recommendation', async () => {
			await expect(
				page.getByText( 'set a daily budget of 15 USD' )
			).toBeVisible();
		} );
	} );

	test.describe( 'Set up billing', () => {
		test.describe( 'Billing status is not approved', () => {
			test.beforeAll( async () => {
				await setupBudgetPage.fulfillBillingStatusRequest( {
					status: 'pending',
				} );
			} );
			test( 'It should say that the billing is not setup', async () => {
				await page.getByRole( 'button', { name: 'Continue' } ).click();
				await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

				await expect(
					page.getByRole( 'button', {
						name: 'Set up billing',
						exact: true,
					} )
				).toBeEnabled();

				await expect(
					page.getByText(
						'In order to launch your paid campaign, your billing information is required. You will be billed directly by Google and only pay when someone clicks on your ad.'
					)
				).toBeVisible();

				await expect(
					page.getByRole( 'link', {
						name: 'click here instead',
					} )
				).toBeVisible();
			} );

			// eslint-disable-next-line jest/expect-expect
			test( 'should open a popup when clicking set up billing button', async () => {
				await checkBillingAdsPopup( page );
			} );
		} );

		test.describe( 'Billing status is approved', async () => {
			test.beforeAll( async () => {
				await setupBudgetPage.fulfillBillingStatusRequest( {
					status: 'approved',
				} );

				await setupAdsAccounts.mockAdsAccountsResponse( {
					id: ADS_ACCOUNTS[ 1 ],
					billing_url: null,
				} );

				// Simulate a bit of delay when creating the Ads campaign so we have enough time to test the content in the page before the redirect.
				await page.route(
					/\/wc\/gla\/ads\/campaigns\b/,
					async ( route ) => {
						await new Promise( ( f ) => setTimeout( f, 500 ) );
						await route.continue();
					}
				);
			} );
			test( 'It should say that the billing is setup', async () => {
				//Every 30s the page will check if the billing status is approved and it will trigger the campaign creation.
				await setupBudgetPage.awaitForBillingStatusRequest();
				await setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
					budget,
					[ 'US' ]
				);

				await expect(
					page.getByText(
						'Great! You already have billing information saved for this'
					)
				).toBeVisible();

				//It should redirect to the dashboard page
				await page.waitForURL(
					'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard&guide=campaign-creation-success',
					{
						timeout: 30000,
						waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
					}
				);
			} );

			test( 'It should show the campaign creation success message', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: "You've set up a paid Performance Max Campaign!",
					} )
				).toBeVisible();

				await expect(
					page.getByRole( 'button', {
						name: 'Create another campaign',
					} )
				).toBeEnabled();

				await expect(
					page.getByRole( 'button', {
						name: 'Got It',
					} )
				).toBeEnabled();

				await page
					.getByRole( 'button', {
						name: 'Got It',
					} )
					.click();
			} );
		} );
	} );

	test.describe( 'Create Ads with billing data already setup', () => {
		test( 'Launch paid campaign should be enabled', async () => {
			//Click Add paid Campaign
			await adsConnectionButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			//Step 1 - Accounts are already set up.
			await setupAdsAccounts.clickContinue();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			//Step 2 - Fill the budget
			await setupBudgetPage.fillBudget( '1' );
			await page.getByRole( 'button', { name: 'Continue' } ).click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			//Step 3 - Billing is already setup
			await expect(
				page.getByText(
					'Great! You already have billing information saved for this'
				)
			).toBeVisible();

			await expect(
				page.getByRole( 'button', { name: 'Launch paid campaign' } )
			).toBeEnabled();

			const campaignCreation =
				setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
					'1',
					[ 'US' ]
				);
			await page
				.getByRole( 'button', { name: 'Launch paid campaign' } )
				.click();
			await campaignCreation;
		} );
	} );
} );
