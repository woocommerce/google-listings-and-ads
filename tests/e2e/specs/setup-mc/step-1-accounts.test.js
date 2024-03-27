/**
 * Internal dependencies
 */
import SetUpAccountsPage from '../../utils/pages/setup-mc/step-1-set-up-accounts';
import SetupAdsAccountPage from '../../utils/pages/setup-ads/setup-ads-accounts';
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
 * @type {import('../../utils/pages/setup-ads/setup-ads-accounts.js').default} setupAdsAccountPage
 */
let setupAdsAccountPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Set up accounts', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setUpAccountsPage = new SetUpAccountsPage( page );
		setupAdsAccountPage = new SetupAdsAccountPage( page );
	} );

	test.afterAll( async () => {
		await setUpAccountsPage.closePage();
	} );

	test( 'should see accounts step header, "Connect your WordPress.com account" & connect button', async () => {
		await setUpAccountsPage.goto();

		await expect(
			page.getByRole( 'heading', { name: 'Set up your accounts' } )
		).toBeVisible();

		await expect(
			page.getByText(
				'Connect the accounts required to use Google Listings & Ads.'
			)
		).toBeVisible();

		await expect(
			page.getByRole( 'button', { name: 'Connect' } ).first()
		).toBeEnabled();

		const wpAccountCard = setUpAccountsPage.getWPAccountCard();
		await expect( wpAccountCard ).toBeEnabled();
		await expect( wpAccountCard ).toContainText( 'WordPress.com' );

		const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();
		await expect( googleAccountCard.getByRole( 'button' ) ).toBeDisabled();

		const mcAccountCard = setUpAccountsPage.getMCAccountCard();
		await expect( mcAccountCard.getByRole( 'button' ) ).toBeDisabled();

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

	test.describe( 'Connect Google account', () => {
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

		test( 'should see their WPORG email, "Google" title & connect button', async () => {
			const jetpackDescriptionRow =
				setUpAccountsPage.getJetpackDescriptionRow();

			await expect( jetpackDescriptionRow ).toContainText(
				'jetpack@example.com'
			);

			const googleAccountCard = setUpAccountsPage.getGoogleAccountCard();

			await expect(
				googleAccountCard.getByText( 'Google', { exact: true } )
			).toBeVisible();

			await expect(
				googleAccountCard
					.getByRole( 'button', { name: 'Connect' } )
					.first()
			).toBeEnabled();

			const mcAccountCard = setUpAccountsPage.getMCAccountCard();
			await expect( mcAccountCard.getByRole( 'button' ) ).toBeDisabled();

			const continueButton = setUpAccountsPage.getContinueButton();
			await expect( continueButton ).toBeDisabled();
		} );

		test( 'after clicking the "Connect your Google account" button should send an API request to connect Google account, and redirect to the returned URL', async ( {
			baseURL,
		} ) => {
			// Mock google connect.
			await setUpAccountsPage.mockGoogleConnect(
				baseURL + 'google_auth'
			);

			// Click the enabled connect button
			page.locator(
				"//button[text()='Connect'][not(@disabled)]"
			).click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'google_auth' );

			expect( page.url() ).toMatch( baseURL + 'google_auth' );
		} );
	} );

	test.describe( 'Google Ads', () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected(
				'Test user',
				'jetpack@example.com'
			);

			// Mock google as connected.
			await setUpAccountsPage.mockGoogleConnected();

			// Start with no Ads account connected.
			await setupAdsAccountPage.mockAdsAccountDisconnected();
			await setupAdsAccountPage.mockAdsHasNoAccounts();

			// Mock MC as not connected and has not existing accounts
			// to avoid errors when attempting to fetch accounts.
			await setUpAccountsPage.mockMCNotConnected();
			await setUpAccountsPage.mockMCHasNoAccounts();

			await setUpAccountsPage.goto();
		} );

		test( 'should see Google Ads section', async () => {
			const section = setUpAccountsPage.getGoogleAdsAccountCard();
			await expect( section ).toBeVisible();
		} );

		test.describe( 'Google Ads account', () => {
			test.describe( 'Create account', () => {
				test.beforeAll( async () => {
					await setUpAccountsPage.clickCreateAdsAccountButton();
				} );

				test( 'should see "Create Google Ads Account" modal', async () => {
					const modal = setupAdsAccountPage.getCreateAccountModal();
					await expect( modal ).toBeVisible();
				} );

				test( 'should see "ToS checkbox" from modal is unchecked', async () => {
					const checkbox =
						setupAdsAccountPage.getAcceptTermCreateAccount();
					await expect( checkbox ).toBeVisible();
					await expect( checkbox ).not.toBeChecked();
				} );

				test( 'should see "Create account" from modal is disabled', async () => {
					const button =
						setupAdsAccountPage.getCreateAdsAccountButtonModal();
					await expect( button ).toBeVisible();
					await expect( button ).toBeDisabled();
				} );

				test( 'should see "Create account" from modal is enabled when ToS checkbox is checked', async () => {
					const button =
						setupAdsAccountPage.getCreateAdsAccountButtonModal();
					await setupAdsAccountPage.clickToSCheckboxFromModal();
					await expect( button ).toBeEnabled();
				} );

				test( 'should see ads account connected after creating a new ads account', async () => {
					await setupAdsAccountPage.mockAdsAccountsResponse( [
						{
							id: 12345,
							name: 'Test Ad',
						},
					] );

					await setupAdsAccountPage.mockAdsAccountConnected();

					await setupAdsAccountPage.clickCreateAccountButtonFromModal();
					const connectedText =
						setUpAccountsPage.getAdsAccountConnectedText();
					await expect( connectedText ).toBeVisible();
				} );
			} );

			test.describe( 'Connect to an existing account', () => {
				test.beforeAll( async () => {
					await setupAdsAccountPage.mockAdsAccountsResponse( [
						{
							id: 12345,
							name: 'Test Ad 1',
						},
						{
							id: 23456,
							name: 'Test Ad 2',
						},
					] );

					await setupAdsAccountPage.mockAdsAccountDisconnected();
					await setupAdsAccountPage.clickConnectDifferentAccountButton();
				} );

				test( 'should see the ads account select', async () => {
					const select = setupAdsAccountPage.getAdsAccountSelect();
					await expect( select ).toBeVisible();
				} );

				test( 'should see connect button is disabled when no ads account is selected', async () => {
					const button = setupAdsAccountPage.getConnectAdsButton();
					await expect( button ).toBeVisible();
					await expect( button ).toBeDisabled();
				} );

				test( 'should see connect button is enabled when an ads account is selected', async () => {
					await setupAdsAccountPage.selectAnExistingAdsAccount(
						'23456'
					);
					const button = setupAdsAccountPage.getConnectAdsButton();
					await expect( button ).toBeVisible();
					await expect( button ).toBeEnabled();
				} );

				test( 'should see ads account connected text and ads/accounts request is triggered after clicking connect button', async () => {
					await setupAdsAccountPage.mockAdsAccountsResponse( [
						{
							id: 23456,
							name: 'Test Ad 2',
						},
					] );

					await setupAdsAccountPage.mockAdsAccountConnected( 23456 );

					const requestPromise =
						setupAdsAccountPage.registerConnectAdsAccountRequests(
							'23456'
						);

					await setupAdsAccountPage.clickConnectAds();

					await requestPromise;

					const connectedText =
						setUpAccountsPage.getAdsAccountConnectedText();
					await expect( connectedText ).toBeVisible();
				} );
			} );
		} );
	} );

	test.describe( 'Connect Merchant Center account', () => {
		test.beforeAll( async () => {
			await Promise.all( [
				// Mock Jetpack as connected.
				setUpAccountsPage.mockJetpackConnected(
					'Test user',
					'jetpack@example.com'
				),

				// Mock google as connected.
				setUpAccountsPage.mockGoogleConnected( 'google@example.com' ),

				// Mock Google Ads as not connected.
				setupAdsAccountPage.mockAdsAccountDisconnected(),
				setupAdsAccountPage.mockAdsHasNoAccounts(),

				// Mock merchant center as not connected.
				setUpAccountsPage.mockMCNotConnected(),
			] );
		} );

		test.describe( 'Merchant Center has no existing accounts', () => {
			test.beforeAll( async () => {
				// Mock merchant center has no accounts
				await setUpAccountsPage.mockMCHasNoAccounts();
				await setUpAccountsPage.goto();
			} );

			test( 'should see their WPORG email, Google email, "Google Merchant Center" title & "Create account" button', async () => {
				const jetpackDescriptionRow =
					setUpAccountsPage.getJetpackDescriptionRow();
				await expect( jetpackDescriptionRow ).toContainText(
					'jetpack@example.com'
				);

				const googleDescriptionRow =
					setUpAccountsPage.getGoogleDescriptionRow();
				await expect( googleDescriptionRow ).toContainText(
					'google@example.com'
				);

				const mcTitleRow = setUpAccountsPage.getMCTitleRow();
				await expect( mcTitleRow ).toContainText(
					'Google Merchant Center'
				);

				const createAccountButton =
					setUpAccountsPage.getMCCreateAccountButtonFromPage();
				await expect( createAccountButton ).toBeEnabled();

				const continueButton = setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );

			test( 'click "Create account" button should see the modal of confirmation of creating account', async () => {
				// Click the create account button
				const createAccountButton =
					setUpAccountsPage.getMCCreateAccountButtonFromPage();
				await createAccountButton.click();
				await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

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

			test.describe(
				'click "Create account" button from the modal',
				() => {
					test( 'should see Merchant Center "Connected" when the website is not claimed', async ( {
						baseURL,
					} ) => {
						await Promise.all( [
							// Mock Merchant Center create accounts
							setUpAccountsPage.mockMCCreateAccountWebsiteNotClaimed(),

							// Mock Merchant Center as connected with ID 12345
							setUpAccountsPage.mockMCConnected( 12345 ),
						] );

						const createAccountButtonFromModal =
							setUpAccountsPage.getMCCreateAccountButtonFromModal();
						await createAccountButtonFromModal.click();
						await page.waitForLoadState(
							LOAD_STATE.DOM_CONTENT_LOADED
						);
						const mcConnectedLabel =
							setUpAccountsPage.getMCConnectedLabel();
						await expect( mcConnectedLabel ).toContainText(
							'Connected'
						);

						const host = new URL( baseURL ).host;
						const mcDescriptionRow =
							setUpAccountsPage.getMCDescriptionRow();
						await expect( mcDescriptionRow ).toContainText(
							`${ host } (12345)`
						);
					} );

					test.describe(
						'when the website is already claimed',
						() => {
							test( 'should see "Reclaim my URL" button, "Switch account" button, and site URL input', async ( {
								baseURL,
							} ) => {
								const host = new URL( baseURL ).host;

								await Promise.all( [
									// Mock merchant center has no accounts
									setUpAccountsPage.mockMCHasNoAccounts(),

									// Mock Merchant Center as not connected
									setUpAccountsPage.mockMCNotConnected(),
								] );

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
								await page.waitForLoadState(
									LOAD_STATE.DOM_CONTENT_LOADED
								);

								// Check the checkbox to accept ToS.
								const modalCheckbox =
									setUpAccountsPage.getModalCheckbox();
								await modalCheckbox.click();

								// Click "Create account" button from the modal.
								const createAccountButtonFromModal =
									setUpAccountsPage.getMCCreateAccountButtonFromModal();
								await createAccountButtonFromModal.click();
								await page.waitForLoadState(
									LOAD_STATE.DOM_CONTENT_LOADED
								);

								const reclaimButton =
									setUpAccountsPage.getReclaimMyURLButton();
								await expect( reclaimButton ).toBeVisible();

								const switchAccountButton =
									setUpAccountsPage.getSwitchAccountButton();
								await expect(
									switchAccountButton
								).toBeVisible();

								const reclaimingURLInput =
									setUpAccountsPage.getReclaimingURLInput();
								await expect( reclaimingURLInput ).toHaveValue(
									baseURL
								);

								const continueButton =
									setUpAccountsPage.getContinueButton();
								await expect( continueButton ).toBeDisabled();
							} );

							test( 'click "Reclaim my URL" should send a claim overwrite request and see Merchant Center "Connected"', async ( {
								baseURL,
							} ) => {
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
								await page.waitForLoadState(
									LOAD_STATE.DOM_CONTENT_LOADED
								);

								const mcConnectedLabel =
									setUpAccountsPage.getMCConnectedLabel();
								await expect( mcConnectedLabel ).toContainText(
									'Connected'
								);

								const host = new URL( baseURL ).host;
								const mcDescriptionRow =
									setUpAccountsPage.getMCDescriptionRow();
								await expect( mcDescriptionRow ).toContainText(
									`${ host } (12345)`
								);
							} );
						}
					);
				}
			);
		} );

		test.describe( 'Merchant Center has existing accounts', () => {
			test.beforeAll( async () => {
				await Promise.all( [
					// Mock merchant center as not connected.
					setUpAccountsPage.mockMCNotConnected(),

					// Mock merchant center has accounts
					setUpAccountsPage.fulfillMCAccounts( [
						{
							id: 12345,
							subaccount: true,
							name: 'MC Account 1',
							domain: 'https://example.com',
						},
						{
							id: 23456,
							subaccount: true,
							name: 'MC Account 2',
							domain: 'https://example.com',
						},
					] ),
				] );

				await setUpAccountsPage.goto();
			} );

			test.describe( 'connect to an existing account', () => {
				test( 'should see "Select an existing account" title', async () => {
					const selectAccountTitle =
						setUpAccountsPage.getSelectExistingMCAccountTitle();
					await expect( selectAccountTitle ).toContainText(
						'Select an existing account'
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

				test( 'select MC Account 2 and click "Connect" button should see Merchant Center "Connected"', async ( {
					baseURL,
				} ) => {
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
					await page.waitForLoadState(
						LOAD_STATE.DOM_CONTENT_LOADED
					);

					const mcConnectedLabel =
						setUpAccountsPage.getMCConnectedLabel();
					await expect( mcConnectedLabel ).toContainText(
						'Connected'
					);

					const host = new URL( baseURL ).host;
					const mcDescriptionRow =
						setUpAccountsPage.getMCDescriptionRow();
					await expect( mcDescriptionRow ).toContainText(
						`${ host } (23456)`
					);
				} );
			} );

			test.describe(
				'click "Or, create a new Merchant Center account"',
				() => {
					test.beforeAll( async () => {
						await Promise.all( [
							// Mock merchant center as not connected.
							setUpAccountsPage.mockMCNotConnected(),

							// Mock merchant center has accounts
							setUpAccountsPage.fulfillMCAccounts( [
								{
									id: 12345,
									subaccount: true,
									name: 'MC Account 1',
									domain: 'https://example.com',
								},
								{
									id: 23456,
									subaccount: true,
									name: 'MC Account 2',
									domain: 'https://example.com',
								},
							] ),
						] );

						await setUpAccountsPage.goto();
					} );

					test( 'should see see a modal to ensure the intention of creating a new account', async () => {
						// Click 'Or, create a new Merchant Center account'
						const mcFooterButton =
							setUpAccountsPage.getMCCardFooterButton();
						await mcFooterButton.click();
						await page.waitForLoadState(
							LOAD_STATE.DOM_CONTENT_LOADED
						);

						const modalHeader = setUpAccountsPage.getModalHeader();
						await expect( modalHeader ).toContainText(
							'Create Google Merchant Center Account'
						);

						const modalCheckbox =
							setUpAccountsPage.getModalCheckbox();
						await expect( modalCheckbox ).toBeEnabled();

						const modalPrimaryButton =
							setUpAccountsPage.getModalPrimaryButton();
						await expect( modalPrimaryButton ).toContainText(
							'Create account'
						);
						await expect( modalPrimaryButton ).toBeDisabled();

						// Select the checkbox, the button should be enabled.
						await modalCheckbox.click();
						await expect( modalPrimaryButton ).toBeEnabled();
					} );
				}
			);
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
				await setupAdsAccountPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockMCNotConnected();

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is disabled without Ads', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );
		} );

		test.describe( 'When only MC is disconnected', async () => {
			test.beforeAll( async () => {
				await setupAdsAccountPage.mockAdsAccountDisconnected();
				await setUpAccountsPage.mockMCConnected();

				await setUpAccountsPage.goto();
			} );

			test( 'should see "Continue" button is disabled without MC', async () => {
				const continueButton =
					await setUpAccountsPage.getContinueButton();
				await expect( continueButton ).toBeDisabled();
			} );
		} );

		test.describe( 'When all accounts are connected', async () => {
			test.beforeAll( async () => {
				await setupAdsAccountPage.mockAdsAccountConnected();
				await setUpAccountsPage.mockMCConnected();

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
