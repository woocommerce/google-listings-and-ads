/**
 * Internal dependencies
 */
import SetUpAccountsPage from '../../utils/pages/setup-mc/step-1-set-up-accounts';
import { LOAD_STATE } from '../../utils/constants';
import {
	getFAQPanelTitle,
	getFAQPanelRow,
	checkFAQExpandable,
} from '../../utils/page';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-1-set-up-accounts.js').default} setUpAccountsPage
 */
let setUpAccountsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Set up accounts', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setUpAccountsPage = new SetUpAccountsPage( page );
	} );

	test.afterAll( async () => {
		await setUpAccountsPage.closePage();
	} );

	test( 'JetpackDisconnected: should see accounts step header, "Connect your WordPress.com account" & connect button', async () => {
		await setUpAccountsPage.goto();

		await expect(
			page.getByRole( 'heading', { name: 'Set up your accounts' } )
		).toBeVisible();

		await expect(
			page.getByText(
				'Connect the accounts required to use Google for WooCommerce.'
			)
		).toBeVisible();

		const wpAccountCard = setUpAccountsPage.getWPAccountCard();
		await expect( wpAccountCard ).toBeEnabled();
		await expect( wpAccountCard ).toContainText( 'WordPress.com' );
		await expect( wpAccountCard.getByRole( 'button' ) ).toBeEnabled();

		const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();
		await expect( googleAccountCard.getByRole( 'button' ) ).toBeDisabled();

		const continueButton = setUpAccountsPage.getContinueButton();
		await expect( continueButton ).toBeDisabled();
	} );

	test.describe( 'FAQ panels', () => {
		test( 'should see two questions in FAQ', async () => {
			const faqTitles = getFAQPanelTitle( page );
			await expect( faqTitles ).toHaveCount( 2 );
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

	test.describe( 'Connect WordPress.com account', () => {
		test( 'should send an API request to connect Jetpack, and redirect to the returned URL', async ( {
			baseURL,
		} ) => {
			// Mock Jetpack connect
			await setUpAccountsPage.mockJetpackConnect( baseURL + 'auth_url' );

			// Click the enabled connect button.
			page.locator(
				"//button[text()='Connect'][not(@disabled)]"
			).click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'auth_url' );

			expect( page.url() ).toMatch( baseURL + 'auth_url' );
		} );
	} );

	test.describe( 'Connected WordPress.com account', async () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected(
				'Test user',
				'jetpack@example.com'
			);

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await setUpAccountsPage.mockGoogleNotConnected();

			await setUpAccountsPage.goto();
		} );

		test( 'should not show the WP.org connection card when already connected', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Set up your accounts' } )
			).toBeVisible();

			await expect(
				page.getByText(
					'Connect the accounts required to use Google for WooCommerce.'
				)
			).toBeVisible();

			const wpAccountCard = setUpAccountsPage.getWPAccountCard();
			await expect( wpAccountCard ).not.toBeVisible();
		} );
	} );

	test.describe( 'Connect Google account', () => {
		test.beforeEach( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected(
				'Test user',
				'jetpack@example.com'
			);

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await setUpAccountsPage.mockGoogleNotConnected();

			await setUpAccountsPage.goto();
		} );

		test( 'should see their WPORG email, "Google" title & connect button', async () => {
			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();

			await expect(
				googleAccountCard.getByText( 'Google', { exact: true } )
			).toBeVisible();

			await expect(
				googleAccountCard.getByRole( 'button', { name: 'Connect' } )
			).toBeDisabled();

			const continueButton = setUpAccountsPage.getContinueButton();
			await expect( continueButton ).toBeDisabled();
		} );

		test( 'should see the terms and conditions checkbox unchecked by default', async () => {
			const termsCheckbox = setUpAccountsPage.getTermsCheckbox();
			await expect( termsCheckbox ).not.toBeChecked();

			// Also ensure that connect button is disabled.
			const connectButton = setUpAccountsPage.getConnectButton();
			await expect( connectButton ).toBeDisabled();
		} );

		test( 'should see the connect button and terms and conditions checkbox disabled when jetpack is not connected', async () => {
			// Mock Jetpack as disconnected
			await setUpAccountsPage.mockJetpackNotConnected();

			await setUpAccountsPage.goto();

			const connectButton = setUpAccountsPage
				.getGoogleAccountCard()
				.getByRole( 'button', { name: 'Connect' } );

			await expect( connectButton ).toBeDisabled();

			const termsCheckbox = setUpAccountsPage.getTermsCheckbox();
			await expect( termsCheckbox ).toBeDisabled();
		} );

		test( 'after clicking the "Connect your Google account" button should send an API request to connect Google account, and redirect to the returned URL', async ( {
			baseURL,
		} ) => {
			// Mock google connect.
			await setUpAccountsPage.mockGoogleConnect(
				baseURL + 'google_auth'
			);

			await setUpAccountsPage.getTermsCheckbox().check();

			// Click the enabled connect button
			page.locator(
				"//button[text()='Connect'][not(@disabled)]"
			).click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'google_auth' );

			expect( page.url() ).toMatch( baseURL + 'google_auth' );
		} );

		test( 'should create merchant center and ads account if does not exist for the user', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockAdsAccountDisconnected();
			await setUpAccountsPage.mockMCNotConnected();

			await setUpAccountsPage.fulfillAdsAccounts(
				[
					[],
					[
						{
							id: 78787878,
							name: 'gla',
						},
					],
				],
				200,
				[ 'GET' ],
				true
			);

			await setUpAccountsPage.fulfillMCAccounts(
				[
					[],
					[
						{
							id: 5432178,
							name: null,
							subaccount: null,
							domain: null,
						},
					],
				],
				200,
				'GET',
				true
			);

			await setUpAccountsPage.fulfillAdsConnection(
				[
					{
						id: 0,
						currency: 'USD',
						status: 'disconnected',
						symbol: '$',
					},
					{
						id: 78787878,
						currency: 'USD',
						status: 'incomplete',
						step: 'account_access',
						sub_account: true,
						symbol: '$',
					},
				],
				200,
				'GET',
				true
			);

			await setUpAccountsPage.fulfillMCConnection(
				[
					{
						id: 0,
						name: null,
						subaccount: null,
						domain: null,
					},
					{
						id: 5432178,
						name: null,
						subaccount: null,
						domain: null,
						status: 'incomplete',
						step: 'claim',
					},
				],
				200,
				'GET',
				true
			);

			await setUpAccountsPage.goto();
			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();

			await expect(
				googleAccountCard.getByText(
					/You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you./,
					{
						exact: true,
					}
				)
			).toBeVisible();
		} );

		test.describe( 'After connecting Google account', () => {
			test.beforeEach( async () => {
				await setUpAccountsPage.mockJetpackConnected();
				await setUpAccountsPage.mockGoogleConnected();
				await setUpAccountsPage.mockMCConnected();
				await setUpAccountsPage.mockAdsAccountConnected();

				await setUpAccountsPage.goto();
			} );

			test( 'should see the merchant center id and ads account id if connected', async () => {
				const googleAccountCard =
					setUpAccountsPage.getGoogleAccountCard();
				await expect(
					googleAccountCard.getByText( 'Merchant Center ID: 1234', {
						exact: true,
					} )
				).toBeVisible();

				await expect(
					googleAccountCard.getByText( 'Google Ads ID: 12345', {
						exact: true,
					} )
				).toBeVisible();
			} );

			test( 'should see the connected label', async () => {
				const googleAccountCard =
					setUpAccountsPage.getGoogleAccountCard();

				await setUpAccountsPage.fulfillAdsAccountStatus( {
					has_access: true,
					invite_link: '',
					step: 'link_merchant',
				} );

				await setUpAccountsPage.fulfillMCConnection( {
					id: 1234,
					name: 'Test Merchant Center',
					subaccount: null,
					domain: 'example.com',
					status: 'connected',
					step: '',
				} );

				await expect(
					googleAccountCard.getByText( 'Connected', { exact: true } )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Continue button', () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected(
				'Test user',
				'jetpack@example.com'
			);

			// Mock google as connected.
			await setUpAccountsPage.mockGoogleConnected();
		} );

		test.describe( 'When only Ads is connected', async () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockMCNotConnected();

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is disabled when only Ads is connected', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );
		} );

		test.describe( 'When only MC is connected', async () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountDisconnected();
				await setUpAccountsPage.mockMCConnected();

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is disabled when only MC is connected', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );
		} );

		test.describe( 'When all accounts are connected', async () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockMCConnected();

				await setUpAccountsPage.fulfillAdsAccountStatus( {
					has_access: true,
					invite_link: '',
					step: 'link_merchant',
				} );

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is enabled', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();

				await expect( continueButton ).toBeEnabled();
			} );
		} );
	} );

	test.describe( 'Links', () => {
		test( 'should contain the correct URL for "Google Merchant Center Help" link', async () => {
			await setUpAccountsPage.goto();
			const link = setUpAccountsPage.getMCHelpLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://support.google.com/merchants/topic/9080307'
			);
		} );

		test( 'should contain the correct URL for CSS Partners link', async () => {
			const cssPartersLink =
				'https://comparisonshoppingpartners.withgoogle.com/find_a_partner/';
			const link = setUpAccountsPage.getCSSPartnersLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute( 'href', cssPartersLink );

			const faqTitle2 = getFAQPanelTitle( page ).nth( 1 );
			await faqTitle2.click();
			const linkInFAQ = setUpAccountsPage.getCSSPartnersLink(
				'Please find more information here'
			);
			await expect( linkInFAQ ).toBeVisible();
			await expect( linkInFAQ ).toHaveAttribute( 'href', cssPartersLink );
		} );
	} );
} );
