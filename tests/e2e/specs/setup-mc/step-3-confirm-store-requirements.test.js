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

		test( 'should see the "Send verification code" button to be disabled', async () => {
			const sendCodeButton =
				storeRequirements.getSendVerificationCodeButton();
			await expect( sendCodeButton ).toBeDisabled();
		} );

		test( 'should see the "Send verification code" button to be disabled when enter a phone number with wrong format', async () => {
			const sendCodeButton =
				storeRequirements.getSendVerificationCodeButton();
			await storeRequirements.selectCountryCodeOption(
				'United States (US) (+1)'
			);
			await expect( sendCodeButton ).toBeDisabled();
			await storeRequirements.fillPhoneNumber( '9999999999' );
			await expect( sendCodeButton ).toBeDisabled();
		} );

		test( 'should see the "Send verification code" button to be enabled when country code and phone number are entered', async () => {
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

		test( 'should contain the correct URL for "WooCommerce settings" link', async () => {
			const link = storeRequirements.getWooCommerceSettingsLink();
			await expect( link ).toBeVisible();
			await expect( link ).toHaveAttribute(
				'href',
				'admin.php?page=wc-settings'
			);
		} );
	} );
} );
