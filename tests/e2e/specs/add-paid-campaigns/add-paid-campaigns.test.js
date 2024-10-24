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
	getFAQPanelTitle,
	getFAQPanelRow,
	checkFAQExpandable,
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

test.describe( 'Set up Ads account', () => {
	// The campaign budget
	let budget = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupAdsAccounts = new SetupAdsAccountsPage( page );
		setupBudgetPage = new SetupBudgetPage( page );
		await setOnboardedMerchant();
		await setupAdsAccounts.mockAdsAccountsResponse( [] );
		await setupBudgetPage.fulfillBillingStatusRequest( {
			status: 'approved',
		} );
		await setupBudgetPage.fulfillBudgetRecommendations( {
			currency: 'EUR',
			recommendations: [
				{
					country: 'FR',
					daily_budget: 15,
				},
			],
		} );
		await dashboardPage.mockRequests();
		await dashboardPage.goto();
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

	test.describe( 'Set up your accounts page', async () => {
		test.beforeAll( async () => {
			await setupAdsAccounts.mockAdsAccountsResponse( [] );
			await adsConnectionButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
		} );

		test( 'Page header should be "Set up your accounts"', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Set up your accounts' } )
			).toBeVisible();
			await expect(
				page.getByText(
					'Connect your Google account and your Google Ads account to set up a paid Performance Max campaign.'
				)
			).toBeVisible();
		} );

		test( 'Google Account should show as connected', async () => {
			await expect(
				page.getByText(
					'This Google account is connected to your store’s product feed.'
				)
			).toBeVisible();
		} );

		test( 'Continue Button should be disabled', async () => {
			await expect( setupAdsAccounts.getContinueButton() ).toBeDisabled();
		} );
	} );

	test.describe( 'Add paid campaigns with no Ads account', async () => {
		test( 'Create an account should be visible', async () => {
			const createAccountButton = page.getByRole( 'button', {
				name: 'Create account',
			} );

			await expect( createAccountButton ).toBeVisible();

			await expect( setupAdsAccounts.getContinueButton() ).toBeDisabled();

			await expect(
				page.getByText(
					'Required to set up conversion measurement and create campaigns.'
				)
			).toBeVisible();

			await createAccountButton.click();
		} );

		test( 'Create account button should be disable if the ToS have not been accepted.', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Create Google Ads Account',
				} )
			).toBeVisible();

			await expect(
				page.getByText(
					'By creating a Google Ads account, you agree to the following terms and conditions:'
				)
			).toBeVisible();

			await expect(
				setupAdsAccounts.getCreateAdsAccountButtonModal()
			).toBeDisabled();
		} );

		test( 'Accept terms and conditions to enable the create account button', async () => {
			await setupAdsAccounts.getAcceptTermCreateAccount().check();

			await expect(
				setupAdsAccounts.getCreateAdsAccountButtonModal()
			).toBeEnabled();
		} );

		test( 'Create an Ads account', async () => {
			// Intercept Ads connection request.
			const connectAdsAccountRequest =
				setupAdsAccounts.registerConnectAdsAccountRequests();

			await setupAdsAccounts.mockAdsAccountsResponse( ADS_ACCOUNTS );

			// Mock request to fulfill Ads connection.
			await setupAdsAccounts.fulfillAdsConnection( {
				id: ADS_ACCOUNTS[ 0 ].id,
				currency: 'USD',
				symbol: '$',
				status: 'incomplete',
				step: 'account_access',
			} );

			await setupAdsAccounts.mockAdsStatusNotClaimed();

			await setupAdsAccounts.getCreateAdsAccountButtonModal().click();

			await connectAdsAccountRequest;

			const modal = setupAdsAccounts.getAcceptAccountModal();
			await expect( modal ).toBeVisible();
		} );

		test( 'Show Unclaimed Ads account', async () => {
			await setupAdsAccounts.clickCloseAcceptAccountButtonFromModal();

			const claimButton = setupAdsAccounts.getAdsClaimAccountButton();
			const claimText = setupAdsAccounts.getAdsClaimAccountText();

			await expect( claimButton ).toBeVisible();
			await expect( claimText ).toBeVisible();

			await expect( setupAdsAccounts.getContinueButton() ).toBeDisabled();
		} );

		test( 'Show Claimed Ads account', async () => {
			// Intercept Ads connection request.
			await setupAdsAccounts.fulfillAdsConnection( {
				id: ADS_ACCOUNTS[ 0 ].id,
				currency: 'USD',
				symbol: '$',
				status: 'connected',
				step: '',
			} );

			await setupAdsAccounts.mockAdsStatusClaimed();

			await page.dispatchEvent( 'body', 'blur' );
			await page.dispatchEvent( 'body', 'focus' );

			await expect( setupAdsAccounts.getContinueButton() ).toBeEnabled();

			await expect(
				page.getByRole( 'link', {
					name: `Account ${ ADS_ACCOUNTS[ 0 ].id }`,
				} )
			).toBeVisible();

			await expect( setupAdsAccounts.getContinueButton() ).toBeEnabled();
		} );
	} );

	test.describe( 'Add paid campaigns with existing Ads accounts', () => {
		test.beforeAll( async () => {
			await setupAdsAccounts.mockAdsAccountsResponse( ADS_ACCOUNTS );
			//Disconnect the account from the previous test
			setupAdsAccounts.fulfillAdsConnection( {
				id: ADS_ACCOUNTS[ 1 ].id,
				currency: 'EUR',
				symbol: '\u20ac',
				status: 'disconnected',
			} );

			await page.reload();
		} );

		test( 'Select one existing account', async () => {
			const adsAccountSelected = `${ ADS_ACCOUNTS[ 1 ].id }`;

			await expect(
				setupAdsAccounts.getConnectAdsButton()
			).toBeDisabled();

			await setupAdsAccounts.selectAnExistingAdsAccount(
				adsAccountSelected
			);

			await expect(
				setupAdsAccounts.getConnectAdsButton()
			).toBeEnabled();

			//Intercept Ads connection request
			const connectAdsAccountRequest =
				setupAdsAccounts.registerConnectAdsAccountRequests(
					adsAccountSelected
				);

			//Mock request to fulfill Ads connection
			setupAdsAccounts.fulfillAdsConnection( {
				id: ADS_ACCOUNTS[ 1 ].id,
				currency: 'EUR',
				symbol: '\u20ac',
				status: 'connected',
			} );

			await setupAdsAccounts.clickConnectAds();
			await connectAdsAccountRequest;

			await expect( setupAdsAccounts.getContinueButton() ).toBeEnabled();
		} );
	} );

	test.describe( 'Create your paid campaign', () => {
		test( 'Continue to create paid ad campaign', async () => {
			await setupAdsAccounts.clickContinue();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			await expect(
				page.getByRole( 'heading', {
					name: 'Create your paid campaign',
				} )
			).toBeVisible();

			await expect(
				page.getByRole( 'heading', { name: 'Set your budget' } )
			).toBeVisible();

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
	} );

	test.describe( 'Create Ads with billing data already setup', () => {
		test.describe( 'Set the budget', async () => {
			test( 'Continue button should be disabled if budget is 0', async () => {
				budget = '0';
				await setupBudgetPage.fillBudget( budget );

				await expect(
					setupBudgetPage.getLaunchPaidCampaignButton()
				).toBeDisabled();
			} );

			test( 'Continue button should be disabled if budget is less than recommended value', async () => {
				budget = '2';
				await setupBudgetPage.fillBudget( budget );

				await expect(
					setupBudgetPage.getLaunchPaidCampaignButton()
				).toBeDisabled();
			} );

			test( 'User is notified of the minimum value', async () => {
				budget = '4';
				await setupBudgetPage.fillBudget( budget );
				await setupBudgetPage.getBudgetInput().blur();

				await expect(
					page.getByText(
						'Please make sure daily average cost is at least €5.00'
					)
				).toBeVisible();
			} );

			test( 'Continue button should be enabled if budget is above the recommended value', async () => {
				budget = '6';
				await setupBudgetPage.fillBudget( budget );

				await expect(
					setupBudgetPage.getLaunchPaidCampaignButton()
				).toBeEnabled();
			} );

			test( 'Budget Recommendation should be visible', async () => {
				await expect(
					page.getByText( 'set a daily budget of 15 EUR' )
				).toBeVisible();
			} );
		} );

		test( 'It should show the campaign creation success message', async () => {
			// Mock the campaign creation request.
			const campaignCreation =
				setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
					'6',
					[ 'US' ]
				);

			await setupBudgetPage.getLaunchPaidCampaignButton().click();

			await campaignCreation;

			//It should redirect to the dashboard page
			await page.waitForURL(
				'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard&guide=campaign-creation-success',
				{
					timeout: 30000,
					waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
				}
			);

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
