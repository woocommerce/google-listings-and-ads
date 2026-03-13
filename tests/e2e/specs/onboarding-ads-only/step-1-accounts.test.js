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
import {
	setServiceBasedMerchant,
	clearServiceBasedMerchant,
} from '../../utils/api';

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

test.describe( 'Set up accounts for Ads only merchants', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setUpAccountsPage = new SetUpAccountsPage( page );
		await setServiceBasedMerchant();
	} );

	test.afterAll( async () => {
		await clearServiceBasedMerchant();
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
		test( 'should see one question in FAQ', async () => {
			const faqTitles = getFAQPanelTitle( page );
			await expect( faqTitles ).toHaveCount( 1 );
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
		test.beforeAll( async () => {
			// Mock Jetpack as not connected
			await setUpAccountsPage.mockJetpackNotConnected();

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await setUpAccountsPage.mockGoogleNotConnected();

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

		test( 'should create an Ads account if does not exist for the user', async () => {
			const once = setUpAccountsPage.withFulfillTimes( 1 );
			const deferred = once.withFulfillDeferred();

			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();

			await deferred.mockAdsCreateAccount();

			await once.mockAdsHasNoAccounts();
			await once.mockAdsAccountDisconnected();
			await once.mockAdsStatusDisconnected();

			await setUpAccountsPage.goto();

			const googleAccountCard =
				setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();

			await expect(
				googleAccountCard.getByText(
					'You don’t have Google Ads account, so we’re creating one for you.',
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

		test( 'should show Ads claim after auto-creation, when appropriate', async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockAdsCreateAccount();
			await setUpAccountsPage.mockAdsAccountIncomplete( 'claim_account' );
			await setUpAccountsPage.mockAdsStatusNotClaimed();

			const once = setUpAccountsPage.withFulfillTimes( 1 );

			await once.mockAdsHasNoAccounts();
			await once.mockAdsAccountDisconnected();
			await once.mockAdsStatusDisconnected();

			await setUpAccountsPage.goto();

			const googleAccountCard =
				setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();

			await expect(
				googleAccountCard.getByRole( 'button', {
					name: 'Claim account in Google Ads',
				} )
			).toBeVisible();
		} );

		test.describe( 'After connecting Google account', () => {
			test.beforeEach( async () => {
				await setUpAccountsPage.mockJetpackConnected();
				await setUpAccountsPage.mockGoogleConnected();
				await setUpAccountsPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS );
				await setUpAccountsPage.goto();
			} );

			test( 'should see the ads account id if connected', async () => {
				await setUpAccountsPage.mockAdsStatusClaimed();

				const googleAccountCard =
					setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();

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

	test.describe( 'Google Ads card', () => {
		test.beforeAll( async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
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
					setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();
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
					setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();
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
			await setUpAccountsPage.fulfillAdsAccounts( [ { id: 12345 } ] );
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.mockAdsStatusNotClaimed();

			await setUpAccountsPage.goto();
		} );

		test( 'should see the claim button', async () => {
			const googleAccountCard =
				setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();
			await expect(
				googleAccountCard.getByRole( 'button', {
					name: 'Claim account in Google Ads',
				} )
			).toBeVisible();
		} );

		test( 'should open the popup when the claim button is clicked', async () => {
			const googleAccountCard =
				setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();

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

			const googleAccountCard =
				setUpAccountsPage.getServiceBasedMerchantGoogleAccountCard();

			await expect(
				googleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
		} );

		test( 'should see the choose audience section after claiming the account', async () => {
			const chooseAudienceSection =
				setUpAccountsPage.getChooseAudienceSection();
			await expect( chooseAudienceSection ).toBeVisible();
		} );
	} );

	test.describe( 'Continue button', () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected();

			// Mock google as connected.
			await setUpAccountsPage.mockGoogleConnected();
		} );

		test( 'should be disabled when Ads is not connected', async () => {
			await setUpAccountsPage.mockAdsAccountDisconnected();
			await setUpAccountsPage.goto();

			const continueButton = await setUpAccountsPage.getContinueButton();
			await expect( continueButton ).toBeDisabled();
		} );

		test( 'should be disabled when Ads is connected and no countries are selected', async () => {
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS );
			await setUpAccountsPage.fulfillTargetAudience(
				{
					location: 'selected',
					countries: [],
					locale: '',
					language: 'English',
				},
				[ 'GET' ]
			);
			await setUpAccountsPage.goto();

			const continueButton = await setUpAccountsPage.getContinueButton();
			await expect( continueButton ).toBeDisabled();
		} );

		test( 'should be enabled when Ads is connected and all countries are selected', async () => {
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS );
			await setUpAccountsPage.fulfillTargetAudience(
				{
					location: 'all',
					countries: [],
					locale: '',
					language: 'English',
				},
				[ 'GET' ]
			);
			await setUpAccountsPage.goto();

			const continueButton = await setUpAccountsPage.getContinueButton();
			await expect( continueButton ).toBeEnabled();
		} );

		test( 'should be enabled when Ads is connected and at least one country is selected', async () => {
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.mockAdsAccountsResponse( ADS_ACCOUNTS );
			await setUpAccountsPage.fulfillTargetAudience(
				{
					location: 'selected',
					countries: [ 'MU', 'US' ],
					locale: '',
					language: 'English',
				},
				[ 'GET' ]
			);
			await setUpAccountsPage.goto();

			const continueButton = await setUpAccountsPage.getContinueButton();
			await expect( continueButton ).toBeEnabled();
		} );
	} );

	test.describe( 'Edit button', () => {
		test.beforeAll( async () => {
			await setUpAccountsPage.mockJetpackConnected();
			await setUpAccountsPage.mockGoogleConnected();
			await setUpAccountsPage.mockAdsAccountConnected();
			await setUpAccountsPage.fulfillAdsAccounts( ADS_ACCOUNTS );
			await setUpAccountsPage.mockAdsStatusClaimed();
			await setUpAccountsPage.goto();
		} );

		test( 'should display the Edit button and the Ads account card is not visible', async () => {
			const editButton = setUpAccountsPage.getEditButton();
			await expect( editButton ).toBeVisible();

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

			test( 'Ads account card is visible', async () => {
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

			test( 'Ads account card is not visible', async () => {
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

				await setUpAccountsPage.goto();

				const editButton = setUpAccountsPage.getEditButton();
				await editButton.click();

				const createNewAdsAccountButton =
					setUpAccountsPage.getCreateNewAdsAccountButton();

				await expect( createNewAdsAccountButton ).toBeVisible();
			} );
		} );
	} );
} );
