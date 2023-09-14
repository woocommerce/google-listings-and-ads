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
import { LOAD_STATE } from '../../utils/constants';

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
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Set up Ads account', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupAdsAccounts = new SetupAdsAccountsPage( page );
		await setOnboardedMerchant();
		await setupAdsAccounts.mockAdsAccountsResponse( [] );
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
			await dashboardPage.getAdsConnectionAllProgramsButton(
				'summary-card'
			)
		).toBeEnabled();

		//Add page campaign in the programs section.
		adsConnectionButton =
			await dashboardPage.getAdsConnectionAllProgramsButton();
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
			await expect(
				await setupAdsAccounts.getContinueButton()
			).toBeDisabled();
		} );
	} );

	test.describe( 'Add paid campaigns with no Ads account', async () => {
		test( 'Create an account should be visible', async () => {
			const createAccountButton = page.getByRole( 'button', {
				name: 'Create account',
			} );

			await expect( createAccountButton ).toBeVisible();

			await expect(
				await setupAdsAccounts.getContinueButton()
			).toBeDisabled();

			await expect(
				page.getByText(
					'Connect with millions of shoppers who are searching for products like yours and drive sales with Google.'
				)
			).toBeVisible();

			await createAccountButton.click();

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

			await setupAdsAccounts.getAcceptTermCreateAccount().check();

			await expect(
				setupAdsAccounts.getCreateAdsAccountButtonModal()
			).toBeEnabled();

			//Intercept Ads connection request
			const connectAdsAccountRequest =
				setupAdsAccounts.registerConnectAdsAccountRequests();

			await setupAdsAccounts.mockAdsAccountsResponse( [
				ADS_ACCOUNTS[ 1 ],
			] );

			//Mock request to fulfill Ads connection
			setupAdsAccounts.fulfillAdsConnection( {
				id: ADS_ACCOUNTS[ 1 ].id,
				currency: 'EUR',
				symbol: '\u20ac',
				status: 'connected',
			} );

			await setupAdsAccounts.getCreateAdsAccountButtonModal().click();

			await connectAdsAccountRequest;

			await expect(
				await setupAdsAccounts.getContinueButton()
			).toBeEnabled();

			await expect(
				page.getByRole( 'link', {
					name: `Account ${ ADS_ACCOUNTS[ 1 ].id }`,
				} )
			).toBeVisible();
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
				await setupAdsAccounts.getConnectAdsButton()
			).toBeDisabled();

			await setupAdsAccounts.selectAnExistingAdsAccount(
				adsAccountSelected
			);

			await expect(
				await setupAdsAccounts.getConnectAdsButton()
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

			await expect(
				await setupAdsAccounts.getContinueButton()
			).toBeEnabled();
		} );

		test( 'Continue to create paid campaign', async () => {
			await setupAdsAccounts.clickContinue();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

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
		} );
	} );
} );
