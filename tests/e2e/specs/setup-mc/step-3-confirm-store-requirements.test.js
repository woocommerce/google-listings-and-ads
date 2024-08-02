/**
 * External dependencies
 */
import { parsePhoneNumberFromString as parsePhoneNumber } from 'libphonenumber-js';
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../utils/constants';
import StoreRequirements from '../../utils/pages/setup-mc/step-3-confirm-store-requirements';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-3-confirm-store-requirements.js').default} storeRequirements
 */
let storeRequirements = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Confirm store requirements', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		storeRequirements = new StoreRequirements( page );
		await Promise.all( [
			// Mock Jetpack as connected
			storeRequirements.mockJetpackConnected(),

			// Mock google as connected.
			storeRequirements.mockGoogleConnected(),

			// Mock Merchant Center as connected
			storeRequirements.mockMCConnected(),

			// Mock MC step as store_requirements
			storeRequirements.mockMCSetup( 'incomplete', 'store_requirements' ),

			// Mock MC contact information
			storeRequirements.mockContactInformation(),
		] );
	} );

	test.afterAll( async () => {
		await storeRequirements.closePage();
	} );

	test( 'should see the heading and the texts below', async () => {
		await storeRequirements.goto();

		await expect(
			page.getByRole( 'heading', {
				name: 'Confirm store requirements',
			} )
		).toBeVisible();

		await expect(
			page.getByText(
				'Review and confirm that your store meets Google Merchant Center requirements.'
			)
		).toBeVisible();
	} );

	test.describe( 'Phone verification', () => {
		test.beforeAll( async () => {
			await storeRequirements.fulfillPhoneVerificationRequest( {
				verification_id: 'abc-123-def',
			} );
		} );

		test( 'should see the correct texts in phone verification card', async () => {
			const phoneNumberDescriptionRow =
				storeRequirements.getPhoneNumberDescriptionRow();
			await expect( phoneNumberDescriptionRow ).toContainText(
				'Please enter a phone number to be used for verification.'
			);
		} );

		test( 'should see the "Send verfication code" button to be disabled', async () => {
			const sendCodeButton =
				storeRequirements.getSendVerificationCodeButton();
			await expect( sendCodeButton ).toBeDisabled();
		} );

		test( 'should see the "Send verfication code" button to be disabled when enter a phone number with wrong format', async () => {
			const sendCodeButton =
				storeRequirements.getSendVerificationCodeButton();
			await storeRequirements.selectCountryCodeOption(
				'United States (US) (+1)'
			);
			await expect( sendCodeButton ).toBeDisabled();
			await storeRequirements.fillPhoneNumber( '9999999999' );
			await expect( sendCodeButton ).toBeDisabled();
		} );

		test( 'should see the "Send verfication code" button to be enabled when country code and phone number are entered', async () => {
			const sendCodeButton =
				storeRequirements.getSendVerificationCodeButton();
			await storeRequirements.selectCountryCodeOption(
				'United States (US) (+1)'
			);
			await expect( sendCodeButton ).toBeDisabled();
			await storeRequirements.fillPhoneNumber( '8888888888' );
			await expect( sendCodeButton ).toBeEnabled();
		} );

		test( 'should see "Verify phone number" button is disabled after clicking "Send verification code" button', async () => {
			await storeRequirements.clickSendVerificationCodeButton();
			const verifyNumberButton =
				storeRequirements.getVerifyPhoneNumberButton();
			await expect( verifyNumberButton ).toBeDisabled();
		} );

		test( 'should see "Verify phone number" button is enabled after entering the verification code', async () => {
			await storeRequirements.fillVerificationCode();
			const verifyNumberButton =
				storeRequirements.getVerifyPhoneNumberButton();
			await expect( verifyNumberButton ).toBeEnabled();
		} );

		test( 'should see an error notice after entering wrong verification code', async () => {
			// Mock phone verification request as failed
			await storeRequirements.fulfillPhoneVerificationVerifyRequest(
				{
					message: '[verificationCode] Wrong code.',
					reason: 'badRequest',
				},
				400
			);
			await storeRequirements.clickVerifyPhoneNumberButon();

			const errorNotice = storeRequirements.getPhoneNumberErrorNotice();
			await expect( errorNotice ).toContainText(
				'Wrong verification code. Please try again.'
			);
		} );

		test( 'should see phone number and the "Edit" button after entering correct verification code', async () => {
			// Mock MC contact information
			await storeRequirements.mockContactInformation( {
				phoneNumber: '+18888888888',
				phoneVerificationStatus: 'verified',
			} );

			// Mock phone verification request as successful
			await storeRequirements.fulfillPhoneVerificationVerifyRequest(
				null,
				204
			);

			await storeRequirements.fillVerificationCode( '654321' );
			await storeRequirements.clickVerifyPhoneNumberButon();

			const parsedNumber = parsePhoneNumber( '8888888888', 'US' );
			const expectedNumber = parsedNumber.formatInternational();

			const phoneNumberDescriptionRow =
				storeRequirements.getPhoneNumberDescriptionRow();
			await expect( phoneNumberDescriptionRow ).toContainText(
				expectedNumber
			);

			const phoneNumberEditButton =
				storeRequirements.getPhoneNumberEditButton();
			await expect( phoneNumberEditButton ).toBeVisible();
		} );
	} );

	test.describe( 'Store address', () => {
		test.beforeAll( async () => {
			// Mock MC contact information
			await storeRequirements.mockContactInformation( {
				phoneNumber: '+18888888888',
				phoneVerificationStatus: 'verified',
				streetAddress: 'Automata Road',
			} );
			await storeRequirements.goto();
		} );

		test( 'should see "Refresh to sync" button', async () => {
			const refreshToSyncButton =
				storeRequirements.getStoreAddressRefreshToSyncButton();
			await expect( refreshToSyncButton ).toBeVisible();
			await expect( refreshToSyncButton ).toBeEnabled();
		} );

		test( 'should see store address contains "Automata Road"', async () => {
			const storeAddressCard = storeRequirements.getStoreAddressCard();
			await expect( storeAddressCard ).toContainText( 'Automata Road' );
		} );

		test( 'should see store address contains "WooCommerce Road" after clicking "Refresh to sync" button', async () => {
			// Mock MC contact information
			await storeRequirements.mockContactInformation( {
				phoneNumber: '+18888888888',
				phoneVerificationStatus: 'verified',
				streetAddress: 'WooCommerce Road',
			} );

			const refreshToSyncButton =
				storeRequirements.getStoreAddressRefreshToSyncButton();
			await refreshToSyncButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			const storeAddressCard = storeRequirements.getStoreAddressCard();
			await expect( storeAddressCard ).toContainText(
				'WooCommerce Road'
			);
		} );
	} );

	test.describe( 'Links', () => {
		test( 'should contain the correct URL for "Learn more" link', async () => {
			const link = storeRequirements.getLearnMoreLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information'
			);
		} );

		test( 'should contain the correct URL for "Read Google Merchant Center requirements" link', async () => {
			const link =
				storeRequirements.getReadGoogleMerchantCenterRequirementsLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'https://woocommerce.com/document/google-for-woocommerce/compliance-policy-2'
			);
		} );

		test( 'should contain the correct URL for "WooCommerce settings" link', async () => {
			const link = storeRequirements.getWooCommerceSettingsLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'admin.php?page=wc-settings'
			);
		} );
	} );

	test.describe( 'Pre-Launch Checklist', () => {
		test.beforeAll( async () => {
			// Mock MC contact information
			await storeRequirements.mockContactInformation( {
				phoneNumber: '+18888888888',
				phoneVerificationStatus: 'verified',
			} );
		} );

		test.describe(
			'When the policy checks are not compliant and settings are false',
			() => {
				test.beforeAll( async () => {
					// Mock all policy checks as non-compliant
					await storeRequirements.fulfillPolicyCheckRequest( {
						allowed_countries: false,
						robots_restriction: true,
						page_not_found_error: true,
						page_redirects: true,
						payment_gateways: false,
						store_ssl: false,
						refund_returns: false,
					} );

					// Mock all settings as false
					await storeRequirements.fulfillSettings(
						{
							website_live: false,
							checkout_process_secure: false,
							payment_methods_visible: false,
							refund_tos_visible: false,
							contact_info_visible: false,
						},
						200,
						[ 'GET' ]
					);

					await storeRequirements.goto();
				} );

				test( 'should see all checkboxes are not checked', async () => {
					const checkboxes =
						storeRequirements.getPrelaunchChecklistCheckboxes();

					await expect( checkboxes ).toHaveCount( 5 );

					for ( const checkbox of await checkboxes.all() ) {
						await expect( checkbox ).not.toBeChecked();
					}
				} );

				test( 'should toggle all checklists and see the "Confirm" button in each checklist', async () => {
					const panels =
						storeRequirements.getPrelaunchChecklistPanels();

					await expect( panels ).toHaveCount( 5 );

					for ( const panel of await panels.all() ) {
						await panel.click();

						const confirmButton = panel.getByRole( 'button', {
							name: 'Confirm',
							exact: true,
						} );
						await expect( confirmButton ).toBeVisible();
						await expect( confirmButton ).toBeEnabled();
					}
				} );

				test( 'should see the correct link in each toggled panel', async () => {
					const panels =
						storeRequirements.getPrelaunchChecklistPanels();

					const panel1 = await panels.nth( 0 );
					const link1 = panel1.getByRole( 'link' );
					await expect( link1 ).toBeVisible();
					await expect( link1 ).toHaveAttribute(
						'href',
						'https://woocommerce.com/document/google-for-woocommerce/compliance-policy-2/#store-is-live'
					);

					const panel2 = await panels.nth( 1 );
					const link2 = panel2.getByRole( 'link' );
					await expect( link2 ).toBeVisible();
					await expect( link2 ).toHaveAttribute(
						'href',
						'https://woocommerce.com/document/google-for-woocommerce/compliance-policy-2/#complete-checkout'
					);

					const panel3 = await panels.nth( 2 );
					const link3 = panel3.getByRole( 'link' );
					await expect( link3 ).toBeVisible();
					await expect( link3 ).toHaveAttribute(
						'href',
						'https://woocommerce.com/document/google-for-woocommerce/compliance-policy-2/#complete-checkout'
					);

					const panel4 = await panels.nth( 3 );
					const link4 = panel4.getByRole( 'link' );
					await expect( link4 ).toBeVisible();
					await expect( link4 ).toHaveAttribute(
						'href',
						'https://woocommerce.com/document/google-for-woocommerce/compliance-policy-2/#refund-and-terms'
					);
				} );

				test( 'should have the checkbox checked by clicking "Confirm" button', async () => {
					// Click "Confirm" button from panel 1 and panel 2

					const panels =
						storeRequirements.getPrelaunchChecklistPanels();
					const checkboxes =
						storeRequirements.getPrelaunchChecklistCheckboxes();

					const requestPromises =
						storeRequirements.registerPrelaunchChecklistConfirmCheckedRequest(
							[ 'website_live', 'payment_methods_visible' ]
						);

					for ( let i = 0; i <= 1; i++ ) {
						// Using .first() because after clicking "Confirm" the row that was clicked would disappear,
						// the second panel would become the first one at the second iteration of for loop.
						const panel = await panels.first();

						// Did not use .first() for the checkboxes because checkboxes would not disappear after clicking "Confirm".
						const checkbox = await checkboxes.nth( i );

						const confirmButton = panel.getByRole( 'button', {
							name: 'Confirm',
							exact: true,
						} );

						await confirmButton.click();

						await expect( checkbox ).toBeDisabled();
						await expect( checkbox ).toBeChecked();
					}

					const requests = await requestPromises;
					for ( const request of requests ) {
						const response = await request.response();
						const responseBody = await response.json();
						expect( response.status() ).toBe( 200 );
						expect( responseBody.status ).toBe( 'success' );
						expect( responseBody.message ).toBe(
							'Merchant Center Settings successfully updated.'
						);
					}
				} );

				test( 'should have the checkbox checked by clicking the checkbox', async () => {
					// Click checkboxes from panel 3 and panel 4

					const checkboxes =
						storeRequirements.getPrelaunchChecklistCheckboxes();

					const requestPromises =
						storeRequirements.registerPrelaunchChecklistConfirmCheckedRequest(
							[ 'checkout_process_secure', 'refund_tos_visible' ]
						);

					for ( let i = 2; i <= 3; i++ ) {
						const checkbox = await checkboxes.nth( i );
						await checkbox.click();
						await expect( checkbox ).toBeDisabled();
						await expect( checkbox ).toBeChecked();
					}

					const requests = await requestPromises;
					for ( const request of requests ) {
						const response = await request.response();
						const responseBody = await response.json();
						expect( response.status() ).toBe( 200 );
						expect( responseBody.status ).toBe( 'success' );
						expect( responseBody.message ).toBe(
							'Merchant Center Settings successfully updated.'
						);
					}
				} );

				test( 'should see error message after clicking "Continue" when any one of the checkboxes was not checked', async () => {
					const continueButton =
						storeRequirements.getContinueButton();
					await continueButton.click();
					const errorMessageRow =
						storeRequirements.getErrorMessageRow();
					await expect( errorMessageRow ).toContainText(
						'Please check all requirements.'
					);
				} );

				test( 'should send settings POST request by clicking the last checkbox', async () => {
					// Click the checkbox of panel 5

					const checkboxes =
						storeRequirements.getPrelaunchChecklistCheckboxes();
					const checkbox = await checkboxes.nth( 4 );

					const requestPromises =
						storeRequirements.registerPrelaunchChecklistConfirmCheckedRequest(
							[ 'contact_info_visible' ]
						);

					await checkbox.click();
					await expect( checkbox ).toBeDisabled();
					await expect( checkbox ).toBeChecked();

					const requests = await requestPromises;
					const response = await requests[ 0 ].response();
					const responseBody = await response.json();
					expect( response.status() ).toBe( 200 );
					expect( responseBody.status ).toBe( 'success' );
					expect( responseBody.message ).toBe(
						'Merchant Center Settings successfully updated.'
					);
				} );
			}
		);

		test.describe(
			'When the policy checks are compliant and settings are true',
			() => {
				test.beforeAll( async () => {
					// Mock all policy checks as compliant
					await storeRequirements.fulfillPolicyCheckRequest( {
						allowed_countries: true,
						robots_restriction: false,
						page_not_found_error: false,
						page_redirects: false,
						payment_gateways: true,
						store_ssl: true,
						refund_returns: true,
					} );

					// Mock all settings as true
					await storeRequirements.fulfillSettings(
						{
							website_live: true,
							checkout_process_secure: true,
							payment_methods_visible: true,
							refund_tos_visible: true,
							contact_info_visible: true,
						},
						200,
						[ 'GET' ]
					);

					await storeRequirements.goto();
				} );

				test( 'should see all checkboxes are checked', async () => {
					const checkboxes =
						storeRequirements.getPrelaunchChecklistCheckboxes();

					await expect( checkboxes ).toHaveCount( 5 );

					for ( const checkbox of await checkboxes.all() ) {
						await expect( checkbox ).toBeChecked();
						await expect( checkbox ).toBeDisabled();
					}
				} );

				test( 'should see the heading of next step after clicking "Continue"', async () => {
					const continueButton =
						storeRequirements.getContinueButton();
					await continueButton.click();
					await page.waitForLoadState(
						LOAD_STATE.DOM_CONTENT_LOADED
					);
					await expect(
						page.getByRole( 'heading', {
							name: 'Create a campaign to advertise your products',
							exact: true,
						} )
					).toBeVisible();
				} );
			}
		);
	} );
} );
