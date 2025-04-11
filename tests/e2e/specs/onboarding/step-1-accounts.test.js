/**
 * Internal dependencies
 */
import SetUpAccountsPage from '../../utils/pages/onboarding/step-1-set-up-accounts';
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
 * @type {import('../../utils/pages/onboarding/step-1-set-up-accounts.js').default} setUpAccountsPage
 */
let setUpAccountsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

const ADS_ACCOUNTS = [
	{
		id: 111111,
		name: 'gla',
	},
	{
		id: 222222,
		name: 'gla',
	},
	{
		id: 333333,
		name: 'gla',
	},
];

let wpComAppAuthURL;

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
		test.beforeAll( async ( { baseURL } ) => {
			// Mock Jetpack as not connected
			await setUpAccountsPage.mockJetpackNotConnected();

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await setUpAccountsPage.mockGoogleNotConnected();

			wpComAppAuthURL = baseURL + 'wpcom_app_auth';
			await setUpAccountsPage.fulfillRESTApiAuthorize( {
				auth_url: wpComAppAuthURL,
			} );

			await setUpAccountsPage.goto();
		} );

		test( 'should see the connect button and terms and conditions checkbox disabled when jetpack is not connected', async () => {
			const connectButton = setUpAccountsPage
				.getGoogleAccountCard()
				.getByRole( 'button', { name: 'Connect' } );

			await expect( connectButton ).toBeDisabled();

			const termsCheckbox = setUpAccountsPage.getTermsCheckbox();
			await expect( termsCheckbox ).toBeDisabled();
		} );

		test( 'should see their WPORG email, "Google" title & connect button', async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected();

			await setUpAccountsPage.goto();

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

		test( 'should seamlessly redirect to WPCOM app auth once being redirected back from Google auth with all required access', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.goto( { 'google-mc': 'connected' } );

			// Expect the user to be redirected automatically
			await page.waitForURL( wpComAppAuthURL );

			expect( page.url() ).toMatch( wpComAppAuthURL );
		} );

		test( 'should be able to go to WPCOM app auth page', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCNotConnected();
			await setUpAccountsPage.goto();

			const card = setUpAccountsPage.getAuthorizeWPComAppCard();

			await expect( card.getByText( 'mail@example.com' ) ).toBeVisible();
			await expect(
				card.getByText(
					'Granting Google access to your WooCommerce store is required'
				)
			).toBeVisible();
			await expect(
				card.getByRole( 'button', {
					name: 'Or, connect to a different',
				} )
			).toBeVisible();

			await card.getByRole( 'button', { name: 'Grant access' } ).click();
			await page.waitForURL( wpComAppAuthURL );

			expect( page.url() ).toMatch( wpComAppAuthURL );
		} );

		test( 'Should show a corresponding message when the user denies WPCOM app auth', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCNotConnected( false, 'disapproved' );
			await setUpAccountsPage.goto();

			await expect(
				setUpAccountsPage
					.getAuthorizeWPComAppCard()
					.getByText( 'Access was denied' )
			).toBeVisible();
		} );

		test( 'Should show a corresponding message when an error occurs in WPCOM app auth', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCNotConnected( false, 'error' );
			await setUpAccountsPage.goto();

			await expect(
				setUpAccountsPage
					.getAuthorizeWPComAppCard()
					.getByText( 'There was an error granting Google access' )
			).toBeVisible();
		} );

		test( 'should create merchant center and ads account if does not exist for the user', async () => {
			const once = setUpAccountsPage.withFulfillTimes( 1 );
			const deferred = once.withFulfillDeferred();

			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();

			await deferred.mockMCCreateAccountWebsiteClaimed();
			await deferred.mockAdsCreateAccount();

			await once.mockAdsHasNoAccounts();
			await once.mockMCHasNoAccounts();
			await once.mockAdsAccountDisconnected();
			await once.mockAdsStatusDisconnected();
			await once.mockMCNotConnected( false, 'approved' );

			await setUpAccountsPage.goto();

			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();

			await expect(
				googleAccountCard.getByText(
					'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
					{
						exact: true,
					}
				)
			).toBeVisible();

			deferred.continueFulfill();

			await expect(
				googleAccountCard.getByText( 'mail@example.com' )
			).toBeVisible();
		} );

		test( 'should show Ads claim and MC Reclaim after auto-creation, when appropriate', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCCreateAccountWebsiteClaimed();
			await setUpAccountsPage.mockAdsCreateAccount();
			await setUpAccountsPage.mockAdsAccountIncomplete( 'claim_account' );
			await setUpAccountsPage.mockAdsStatusNotClaimed();

			const once = setUpAccountsPage.withFulfillTimes( 1 );

			await once.mockAdsHasNoAccounts();
			await once.mockMCHasNoAccounts();
			await once.mockAdsAccountDisconnected();
			await once.mockAdsStatusDisconnected();
			await once.mockMCNotConnected( false, 'approved' );

			await setUpAccountsPage.goto();

			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();
			const googleMCReclaimButton =
				setUpAccountsPage.getReclaimMyURLButton();

			await expect(
				googleAccountCard.getByRole( 'button', {
					name: 'Claim account in Google Ads',
				} )
			).toBeVisible();

			await expect( googleMCReclaimButton ).toBeVisible();
		} );

		test.describe( 'After connecting Google account', () => {
			test.beforeEach( async () => {
				await setUpAccountsPage.mockJetpackConnected();
				await setUpAccountsPage.mockGoogleConnected();
				await setUpAccountsPage.mockMCConnected();
				await setUpAccountsPage.mockContactInformation();
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS );
				await setUpAccountsPage.mockMCHasAccounts();
				await setUpAccountsPage.goto();
			} );

			test( 'should see the merchant center id and ads account id if connected', async () => {
				await setUpAccountsPage.mockAdsStatusClaimed();

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

				await expect(
					googleAccountCard.getByText( 'Connected', { exact: true } )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Connect Merchant Center account', () => {
		test.beforeAll( async () => {
			await Promise.all( [
				// Mock Jetpack as connected.
				setUpAccountsPage.mockJetpackConnected(),

				// Mock google as connected.
				setUpAccountsPage.mockGoogleConnected( 'google@example.com' ),

				// Mock Google Ads as connected.
				setUpAccountsPage.mockAdsAccountConnected(),
				setUpAccountsPage.mockAdsStatusClaimed(),

				// Mock merchant center as not connected.
				setUpAccountsPage.mockMCNotConnected( false, 'approved' ),
			] );
		} );

		test.describe( 'Create Merchant Center Account', () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS );
				await setUpAccountsPage.mockMCHasAccounts();
				await setUpAccountsPage.goto();
			} );

			test( 'should see their Google email, "Google Merchant Center" title & "Create account" button', async () => {
				const googleDescriptionRow =
					setUpAccountsPage.getGoogleDescriptionRow();
				await expect( googleDescriptionRow ).toContainText(
					'google@example.com'
				);

				const mcTitleRow = setUpAccountsPage.getMCTitleRow();
				await expect( mcTitleRow ).toContainText(
					'Connect to existing Merchant Center account'
				);

				const mcFooterButton =
					setUpAccountsPage.getMCCardFooterButton();
				await expect( mcFooterButton ).toBeEnabled();

				const continueButton = setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );

			test( 'click "Create account" button should see the modal of confirmation of creating account', async () => {
				// Click the create account button
				const createAccountButton =
					setUpAccountsPage.getMCCreateAccountButtonFromPage();
				await createAccountButton.click();

				const modalHeader = setUpAccountsPage.getModalHeader();
				await expect( modalHeader ).toContainText(
					'Create Google Merchant Center Account'
				);

				const modalCheckbox = setUpAccountsPage.getModalCheckbox();
				await expect( modalCheckbox ).toBeEnabled();

				const createAccountButtonFromModal =
					setUpAccountsPage.getMCCreateAccountButtonFromModal();
				await expect( createAccountButtonFromModal ).toBeDisabled();

				// Click the checkbox of accepting ToS, the create account button will be enabled.
				await modalCheckbox.click();
				await expect( createAccountButtonFromModal ).toBeEnabled();
			} );

			test.describe( 'click "Create account" button from the modal', () => {
				test( 'should see Merchant Center account "Connected" when the website is not claimed', async () => {
					await Promise.all( [
						// Mock Merchant Center create accounts
						setUpAccountsPage.mockMCCreateAccountWebsiteNotClaimed(),

						// Mock Merchant Center as connected with ID 12345
						setUpAccountsPage.mockMCConnected( 12345 ),
					] );

					const createAccountButtonFromModal =
						setUpAccountsPage.getMCCreateAccountButtonFromModal();
					await createAccountButtonFromModal.click();
					const mcConnectedLabel =
						setUpAccountsPage.getGoogleComboConnectedLabel();
					await expect( mcConnectedLabel ).toContainText(
						'Connected'
					);

					const mcDescriptionRow =
						setUpAccountsPage.getMCDescriptionRow();
					await expect( mcDescriptionRow ).toContainText(
						'Merchant Center ID: 12345'
					);
				} );

				test.describe( 'when the website is already claimed', () => {
					test( 'should see "Reclaim my URL" button, "Switch account" button, and site URL input', async ( {
						baseURL,
					} ) => {
						const host = new URL( baseURL ).host;

						await setUpAccountsPage.mockMCNotConnected(
							false,
							'approved'
						);

						await page.reload();

						// Mock Merchant Center create accounts
						await setUpAccountsPage.mockMCCreateAccountWebsiteClaimed(
							12345,
							host
						);

						// Click "Create account" button from the page.
						const createAccountButton =
							setUpAccountsPage.getMCCreateAccountButtonFromPage();
						await createAccountButton.click();

						// Check the checkbox to accept ToS.
						const modalCheckbox =
							setUpAccountsPage.getModalCheckbox();
						await modalCheckbox.click();

						// Click "Create account" button from the modal.
						const createAccountButtonFromModal =
							setUpAccountsPage.getMCCreateAccountButtonFromModal();
						await createAccountButtonFromModal.click();

						const reclaimButton =
							setUpAccountsPage.getReclaimMyURLButton();
						await expect( reclaimButton ).toBeVisible();

						const switchAccountButton =
							setUpAccountsPage.getSwitchAccountButton();
						await expect( switchAccountButton ).toBeVisible();

						const reclaimingURLInput =
							setUpAccountsPage.getReclaimingURLInput();
						await expect( reclaimingURLInput ).toHaveValue(
							baseURL
						);

						const continueButton =
							setUpAccountsPage.getContinueButton();
						await expect( continueButton ).toBeDisabled();
					} );

					test( 'click "Reclaim my URL" should send a claim overwrite request and see Merchant Center "Connected"', async () => {
						await Promise.all( [
							// Mock Merchant Center accounts claim overwrite
							setUpAccountsPage.mockMCAccountsClaimOverwrite(
								12345
							),

							// Mock Merchant Center as connected with ID 12345
							setUpAccountsPage.mockMCConnected( 12345 ),
						] );

						const reclaimButton =
							setUpAccountsPage.getReclaimMyURLButton();
						await reclaimButton.click();

						const mcConnectedLabel =
							setUpAccountsPage.getGoogleComboConnectedLabel();
						await expect( mcConnectedLabel ).toContainText(
							'Connected'
						);

						const mcDescriptionRow =
							setUpAccountsPage.getMCDescriptionRow();
						await expect( mcDescriptionRow ).toContainText(
							'Merchant Center ID: 12345'
						);
					} );
				} );
			} );
		} );

		test.describe( 'Connect Merchant Center account', () => {
			test.beforeAll( async () => {
				await Promise.all( [
					setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS ),

					// Mock merchant center as not connected.
					setUpAccountsPage.mockMCNotConnected( false, 'approved' ),

					// Mock merchant center has accounts
					setUpAccountsPage.mockMCHasAccounts(),
				] );

				await setUpAccountsPage.goto();
			} );

			test.describe( 'connect to an existing account', () => {
				test( 'should see "Select an existing account" title', async () => {
					const selectAccountTitle =
						setUpAccountsPage.getSelectExistingMCAccountTitle();
					await expect( selectAccountTitle ).toContainText(
						'Connect to existing Merchant Center account'
					);
				} );

				test( 'should see "Or, create a new Merchant Center account" text', async () => {
					const mcFooter = setUpAccountsPage.getMCCardFooter();
					await expect( mcFooter ).toContainText(
						'Or, create a new Merchant Center account'
					);
				} );

				test( 'should see "Connect" button', async () => {
					const connectButton = setUpAccountsPage.getConnectButton();
					await expect( connectButton ).toBeEnabled();
				} );

				test( 'should see "Continue" button is disabled', async () => {
					const continueButton =
						setUpAccountsPage.getContinueButton();
					await expect( continueButton ).toBeDisabled();
				} );

				test( 'select MC Account 2 and click "Connect" button should see Merchant Center "Connected"', async () => {
					await Promise.all( [
						// Mock Merchant Center create accounts
						setUpAccountsPage.mockMCCreateAccountWebsiteNotClaimed(),

						// Mock Merchant Center as connected with ID 12345
						setUpAccountsPage.mockMCConnected( 23456 ),
					] );

					// Select MC Account 2 from the options
					const mcAccountsSelect =
						setUpAccountsPage.getMCAccountsSelect();
					await mcAccountsSelect.selectOption( {
						label: 'MC Account 2 ・ https://example.com (23456)',
					} );

					// Click connect button
					const connectButton = setUpAccountsPage.getConnectButton();
					await connectButton.click();

					const mcConnectedLabel =
						setUpAccountsPage.getGoogleComboConnectedLabel();
					await expect( mcConnectedLabel ).toContainText(
						'Connected'
					);

					const mcDescriptionRow =
						setUpAccountsPage.getMCDescriptionRow();
					await expect( mcDescriptionRow ).toContainText(
						'Merchant Center ID: 23456'
					);
				} );
			} );
		} );
	} );

	test.describe( 'Google Ads card', () => {
		test.beforeAll( async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCHasAccounts();
			await setUpAccountsPage.mockMCConnected();
			await setUpAccountsPage.mockContactInformation();
			await setUpAccountsPage.mockAdsAccountDisconnected();
			await setUpAccountsPage.fulfillAdsAccounts( ADS_ACCOUNTS );

			await setUpAccountsPage.goto();
		} );

		test.describe( 'When existing Google Ads accounts are available, but not connected', () => {
			test( 'should see the Google Ads card with the correct title and body', async () => {
				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();

				await expect(
					googleAdsAccountCard.getByText(
						'Connect to existing Google Ads account',
						{ exact: true }
					)
				).toBeVisible();

				await expect(
					googleAdsAccountCard.getByText(
						'Required to set up conversion measurement for your store.',
						{ exact: true }
					)
				).toBeVisible();
			} );

			test( 'should see the button as enabled when selects the account from dropdown', async () => {
				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();

				const adsAccountDropdown =
					googleAdsAccountCard.locator( 'select' );
				await adsAccountDropdown.selectOption( '222222' );

				await expect(
					googleAdsAccountCard.getByRole( 'button', {
						name: 'Connect',
					} )
				).toBeEnabled();
			} );

			test( 'should send an API request to connect existing Google Ads account', async () => {
				const adsAccountsResponse =
					setUpAccountsPage.registerAdsAccountsResponse();

				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();
				await setUpAccountsPage.mockAdsStatusClaimed();

				const adsAccountDropdown =
					googleAdsAccountCard.locator( 'select' );
				await adsAccountDropdown.selectOption( '222222' );
				await googleAdsAccountCard
					.getByRole( 'button', { name: 'Connect' } )
					.click();

				await setUpAccountsPage.mockAdsAccountConnected( 222222 );
				await adsAccountsResponse;

				const googleAccountCard =
					setUpAccountsPage.getGoogleAccountCard();
				await expect(
					googleAccountCard.getByText( 'Google Ads ID: 222222' )
				).toBeVisible();
			} );
		} );

		test.describe( 'When new Google Ads account is created', () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountDisconnected();

				await setUpAccountsPage.goto();
			} );

			test( 'should see the Create new Google Ads account link', async () => {
				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();

				await expect(
					googleAdsAccountCard.getByText(
						'Or, create a new Google Ads account',
						{ exact: true }
					)
				).toBeVisible();
			} );

			test( 'clicking the "Create new Google Ads account" link should open the modal', async () => {
				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();

				await googleAdsAccountCard
					.getByText( 'Or, create a new Google Ads account' )
					.click();

				await expect( setUpAccountsPage.getModal() ).toBeVisible();
				await expect( setUpAccountsPage.getModalHeader() ).toHaveText(
					'Create Google Ads Account'
				);

				// "Yes, I want a new account" button should be disabled and secondary.
				const yesButton = setUpAccountsPage.getModalSecondaryButton();
				const cancelButton = setUpAccountsPage.getModalPrimaryButton();
				await expect( yesButton ).toHaveText(
					'Yes, I want a new account'
				);

				await expect( cancelButton ).toHaveText( 'Cancel' );

				// Click the cancel button to close the modal.
				await cancelButton.click();
				await expect( setUpAccountsPage.getModal() ).not.toBeVisible();
			} );

			test( 'clicking the "Yes, I want a new account" button should create a new Google Ads account', async () => {
				const googleAccountCard =
					setUpAccountsPage.getGoogleAccountCard();
				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();

				await setUpAccountsPage.fulfillAdsAccounts( [
					{
						id: 111111,
					},
				] );

				await setUpAccountsPage.mockAdsStatusNotClaimed();
				await setUpAccountsPage.mockAdsAccountIncomplete();

				await googleAdsAccountCard
					.getByText( 'Or, create a new Google Ads account' )
					.click();

				await expect( setUpAccountsPage.getModal() ).toBeVisible();

				const yesButton = setUpAccountsPage.getModalSecondaryButton();
				await yesButton.click();

				await expect( setUpAccountsPage.getModal() ).not.toBeVisible();

				// Google Ads ID should be displayed.
				await expect(
					googleAccountCard.getByText( 'Google Ads ID: 12345' )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Claim Google Ads Account', () => {
		test.beforeAll( async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCHasAccounts();
			await setUpAccountsPage.mockMCConnected();
			await setUpAccountsPage.mockContactInformation();
			await setUpAccountsPage.fulfillAdsAccounts( [ { id: 12345 } ] );
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.mockAdsStatusNotClaimed();

			await setUpAccountsPage.goto();
		} );

		test( 'should see the claim button', async () => {
			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();
			await expect(
				googleAccountCard.getByRole( 'button', {
					name: 'Claim account in Google Ads',
				} )
			).toBeVisible();
		} );

		test( 'should open the popup when the claim button is clicked', async () => {
			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();

			const [ popupPage ] = await Promise.all( [
				page.waitForEvent( 'popup' ),
				googleAccountCard
					.getByRole( 'button', {
						name: 'Claim account in Google Ads',
					} )
					.click(),
			] );

			await popupPage.waitForLoadState();
			const url = popupPage.url();
			expect( url ).toMatch( /^https:\/\/example\.com\/?$/ );
			await popupPage.close();
		} );

		test( 'should see the accounts card connected', async () => {
			await setUpAccountsPage.mockAdsStatusClaimed();

			await page.reload();

			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();

			await expect(
				googleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
		} );
	} );

	test.describe( 'Store address card', () => {
		test.beforeAll( async () => {
			// Everything is set up except for the MC connection.
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.fulfillAdsAccounts( ADS_ACCOUNTS );
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.mockAdsStatusClaimed();
			await setUpAccountsPage.mockMCHasAccounts();
			await setUpAccountsPage.mockMCNotConnected( false, 'approved' );

			await setUpAccountsPage.goto();
		} );

		test( 'should not be shown when MC is not connected', async () => {
			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();
			const storeAddressCard = setUpAccountsPage.getStoreAddressCard();

			// Wait for UI to render before checking for no visibility.
			await expect( googleAccountCard ).toBeVisible();
			await expect( storeAddressCard ).not.toBeVisible();
		} );

		test( 'should be shown when MC is connected', async () => {
			await setUpAccountsPage.mockMCConnected();
			await setUpAccountsPage.mockContactInformation();

			await page.reload();

			const storeAddressCard = setUpAccountsPage.getStoreAddressCard();

			await expect( storeAddressCard ).toBeVisible();
		} );

		test( 'should update the store address when the button is clicked', async () => {
			await setUpAccountsPage.mockContactInformation( {
				streetAddress: '123 Main St',
			} );

			const storeAddressCard = setUpAccountsPage.getStoreAddressCard();
			const updateButton = setUpAccountsPage.getStoreAddressButton();

			await updateButton.click();

			await expect( storeAddressCard ).toContainText( '123 Main St' );
		} );

		test( 'should show an error message when the store address is not updated', async () => {
			await setUpAccountsPage.mockContactInformation( {
				streetAddress: '',
				wcAddressErrors: [ 'address_1' ],
			} );

			const storeAddressCard = setUpAccountsPage.getStoreAddressCard();
			const updateButton = setUpAccountsPage.getStoreAddressButton();

			await updateButton.click();

			await expect( storeAddressCard ).toContainText(
				'Your store address is required by Google for verification.'
			);

			await expect( storeAddressCard ).toContainText(
				'The address line of store address is required.'
			);
		} );
	} );

	test.describe( 'Continue button', () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected();

			// Mock google as connected.
			await setUpAccountsPage.mockGoogleConnected();
		} );

		test.describe( 'When only Ads is connected', async () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockMCNotConnected( false, 'approved' );
				await setUpAccountsPage.mockContactInformation( {
					wcAddressErrors: [],
					isMCAddressDifferent: false,
				} );

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
				await setUpAccountsPage.fulfillAdsAccounts( ADS_ACCOUNTS );
				await setUpAccountsPage.mockMCConnected();
				await setUpAccountsPage.mockContactInformation();

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is disabled when only MC is connected', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );
		} );

		test.describe( 'When the store address is invalid', async () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.fulfillAdsAccounts( ADS_ACCOUNTS );
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockAdsStatusClaimed();
				await setUpAccountsPage.mockMCHasAccounts();
				await setUpAccountsPage.mockMCConnected();
				await setUpAccountsPage.mockContactInformation( {
					streetAddress: '',
					wcAddressErrors: [ 'address_1' ],
				} );

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button disabled when the store address needs to be updated', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );
		} );

		test.describe( 'When all accounts are connected and store address is fulfilled', async () => {
			test.beforeAll( async () => {
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockMCConnected();
				await setUpAccountsPage.mockAdsStatusClaimed();
				await setUpAccountsPage.mockContactInformation( {} );

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is enabled', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();

				await expect( continueButton ).toBeEnabled();
			} );

			test( 'should sync the address and show the heading of the next step when clicked', async () => {
				const requestPromise =
					setUpAccountsPage.registerContactInformationSyncRequest();

				await setUpAccountsPage.clickContinueButton();

				const request = await requestPromise;
				const response = await request.response();
				const responseBody = await response.json();

				expect( response.status() ).toBe( 200 );
				expect( responseBody.wc_address_errors ).toStrictEqual( [] );

				await expect(
					page.getByRole( 'heading', {
						name: 'Configure your product listings',
						exact: true,
					} )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Edit button', () => {
		test.beforeAll( async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockMCConnected();
			await setUpAccountsPage.mockMCHasAccounts();
			await setUpAccountsPage.mockContactInformation();
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.fulfillAdsAccounts( ADS_ACCOUNTS );
			await setUpAccountsPage.mockAdsStatusClaimed();
			await setUpAccountsPage.goto();
		} );

		test( 'should display the Edit button and the Ads and MC account cards are not visible', async () => {
			const editButton = setUpAccountsPage.getEditButton();
			await expect( editButton ).toBeVisible();

			const googleMcAccountCard = setUpAccountsPage.getMCAccountCard();
			await expect( googleMcAccountCard ).not.toBeVisible();

			const googleAdsAccountCard =
				setUpAccountsPage.getGoogleAdsAccountCard();
			await expect( googleAdsAccountCard ).not.toBeVisible();
		} );

		test.describe( 'clicking the Edit button', async () => {
			test( 'the "Or, connect to a different Google account" button is visible', async () => {
				const editButton = setUpAccountsPage.getEditButton();
				await editButton.click();

				const connectDifferentGoogleAccountButton =
					setUpAccountsPage.getConnectDifferentGoogleAccountButton();
				await expect(
					connectDifferentGoogleAccountButton
				).toBeVisible();
			} );

			test( 'the "Cancel" button is visible', async () => {
				const cancelButton = setUpAccountsPage.getCancelButton();
				await expect( cancelButton ).toBeVisible();
			} );

			test( 'MC and Ads account cards are visible', async () => {
				const googleMcAccountCard =
					setUpAccountsPage.getMCAccountCard();
				await expect( googleMcAccountCard ).toBeVisible();

				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();
				await expect( googleAdsAccountCard ).toBeVisible();
			} );
		} );

		test.describe( 'clicking the Cancel button', async () => {
			test( 'the "Edit" button is visible', async () => {
				const cancelButton = setUpAccountsPage.getCancelButton();
				await cancelButton.click();

				const editButton = setUpAccountsPage.getEditButton();
				await expect( editButton ).toBeVisible();
			} );

			test( 'MC and Ads account cards are not visible', async () => {
				const googleMcAccountCard =
					setUpAccountsPage.getMCAccountCard();
				await expect( googleMcAccountCard ).not.toBeVisible();

				const googleAdsAccountCard =
					setUpAccountsPage.getGoogleAdsAccountCard();
				await expect( googleAdsAccountCard ).not.toBeVisible();
			} );
		} );

		test.describe( 'clicking "Edit" when an Ads account is being claimed', async () => {
			test( 'should let you connect to a different account', async () => {
				await setUpAccountsPage.mockAdsStatusNotClaimed();
				await setUpAccountsPage.mockAdsAccountIncomplete(
					'claim_account'
				);

				await setUpAccountsPage.goto();

				const editButton = setUpAccountsPage.getEditButton();
				await editButton.click();

				const connectDifferentGoogleAdsAccountButton =
					setUpAccountsPage.getConnectDifferentAdsAccountButton();

				await expect(
					connectDifferentGoogleAdsAccountButton
				).toBeVisible();
			} );

			test( 'should disable the create new account button if there are no other existing accounts', async () => {
				await setUpAccountsPage.mockAdsStatusNotClaimed();
				await setUpAccountsPage.mockAdsHasNoAccounts();
				await setUpAccountsPage.mockAdsAccountIncomplete(
					'claim_account'
				);

				await setUpAccountsPage.goto();

				const editButton = setUpAccountsPage.getEditButton();
				await editButton.click();

				const createNewAdsAccountButton =
					setUpAccountsPage.getCreateNewAdsAccountButton();

				await expect( createNewAdsAccountButton ).toBeDisabled();
			} );
		} );

		test.describe( 'clicking "Edit" when there are no other existing accounts', async () => {
			test( 'should let you create new accounts', async () => {
				await setUpAccountsPage.mockAdsStatusClaimed();
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockAdsHasNoAccounts();
				await setUpAccountsPage.mockMCHasNoAccounts();
				await setUpAccountsPage.mockMCConnected();

				await setUpAccountsPage.goto();

				const editButton = setUpAccountsPage.getEditButton();
				await editButton.click();

				const createNewAdsAccountButton =
					setUpAccountsPage.getCreateNewAdsAccountButton();
				const createNewMCAccountButton =
					setUpAccountsPage.getCreateNewMCAccountButton();

				await expect( createNewAdsAccountButton ).toBeVisible();
				await expect( createNewMCAccountButton ).toBeVisible();
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
