/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import MockRequests from '../../mock-requests';

/**
 * Configure product listings page object class.
 */
export default class StoreRequirements extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Close the current page.
	 *
	 * @return {Promise<void>}
	 */
	async closePage() {
		await this.page.close();
	}

	/**
	 * Go to the set up mc page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsetup-mc&google-mc=connected',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get phone verification card.
	 *
	 * @return {import('@playwright/test').Locator} Get phone verification card.
	 */
	getPhoneVerificationCard() {
		return this.page.locator( '.gla-phone-number-card' );
	}

	/**
	 * Get store address card.
	 *
	 * @return {import('@playwright/test').Locator} Get store address card.
	 */
	getStoreAddressCard() {
		return this.page.locator( '.gla-store-address-card' );
	}

	/**
	 * Get checklist card.
	 *
	 * @return {import('@playwright/test').Locator} Get checklist card.
	 */
	getChecklistCard() {
		return this.page.locator( '.gla-pre-launch-checklist' );
	}

	/**
	 * Get phone number description text.
	 *
	 * @return {import('@playwright/test').Locator} Get checklist card.
	 */
	getPhoneNumberDescriptionRow() {
		return this.getPhoneVerificationCard().locator(
			'.gla-account-card__description'
		);
	}

	/**
	 * Get "Send verification code" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Send verification code" button.
	 */
	getSendVerificationCodeButton() {
		return this.getPhoneVerificationCard().getByRole( 'button', {
			name: 'Send verification code',
			exact: true,
		} );
	}

	/**
	 * Get "Verify phone number" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Verify phone number" button.
	 */
	getVerifyPhoneNumberButton() {
		return this.getPhoneVerificationCard().getByRole( 'button', {
			name: 'Verify phone number',
			exact: true,
		} );
	}

	/**
	 * Get country code input box.
	 *
	 * @return {import('@playwright/test').Locator} Get country code input box.
	 */
	getCountryCodeInputBox() {
		return this.getPhoneVerificationCard().locator(
			'input[id*="woocommerce-select-control"]'
		);
	}

	/**
	 * Get phone number input box.
	 *
	 * @return {import('@playwright/test').Locator} Get phone number input box.
	 */
	getPhoneNumberInputBox() {
		return this.getPhoneVerificationCard().locator(
			'input[id*="inspector-input-control"]'
		);
	}

	/**
	 * Get verification code input boxes.
	 *
	 * @return {import('@playwright/test').Locator} Get verification code input box.
	 */
	getVerificationCodeInputBoxes() {
		return this.getPhoneVerificationCard().locator(
			'.wcdl-subsection .app-input-control input'
		);
	}

	/**
	 * Get phone number error notice.
	 *
	 * @return {import('@playwright/test').Locator} Get phone number error notice.
	 */
	getPhoneNumberErrorNotice() {
		return this.getPhoneVerificationCard().locator(
			'.components-notice.is-error'
		);
	}

	/**
	 * Get phone number edit button.
	 *
	 * @return {import('@playwright/test').Locator} Get phone number edit button.
	 */
	getPhoneNumberEditButton() {
		return this.getPhoneVerificationCard().getByRole( 'button', {
			name: 'Edit',
			exact: true,
		} );
	}

	/**
	 * Get store address refresh to sync button.
	 *
	 * @return {import('@playwright/test').Locator} Get store address refresh to sync button.
	 */
	getStoreAddressRefreshToSyncButton() {
		return this.getStoreAddressCard().getByRole( 'button', {
			name: 'Refresh to sync',
			exact: true,
		} );
	}

	/**
	 * Get pre-launch checklist checkboxes.
	 *
	 * @return {import('@playwright/test').Locator} Get pre-launch checklist checkboxes.
	 */
	getPrelaunchChecklistCheckboxes() {
		return this.getChecklistCard().getByRole( 'checkbox' );
	}

	/**
	 * Get pre-launch checklist panels.
	 *
	 * @return {import('@playwright/test').Locator} Get pre-launch checklist panels.
	 */
	getPrelaunchChecklistPanels() {
		return this.getChecklistCard().locator( '.components-panel__body' );
	}

	/**
	 * Get Continue button.
	 *
	 * @return {import('@playwright/test').Locator} Get Continue button.
	 */
	getContinueButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Get error message row.
	 *
	 * @return {import('@playwright/test').Locator} Get error message row.
	 */
	getErrorMessageRow() {
		return this.page.locator( '.gla-validation-errors' );
	}

	/**
	 * Get learn more link.
	 *
	 * @return {import('@playwright/test').Locator} Get learn more link.
	 */
	getLearnMoreLink() {
		return this.page.getByRole( 'link', {
			name: 'Learn more',
			exact: true,
		} );
	}

	/**
	 * Get read google merchant center requirements link.
	 *
	 * @return {import('@playwright/test').Locator} Get read google merchant center requirements link.
	 */
	getReadGoogleMerchantCenterRequirementsLink() {
		return this.page.getByRole( 'link', {
			name: 'Read Google Merchant Center requirements',
			exact: true,
		} );
	}

	/**
	 * Get WooCommerce settings link.
	 *
	 * @return {import('@playwright/test').Locator} Get WooCommerce settings link.
	 */
	getWooCommerceSettingsLink() {
		return this.page.getByRole( 'link', {
			name: 'WooCommerce settings',
			exact: true,
		} );
	}

	/**
	 * Register settings request when the confirm button or checkbox in pre-lauch checklist is checked.
	 *
	 * @param {Array} settings
	 * @return {Promise<import('@playwright/test').Request[]>} The requests.
	 */
	registerPrelaunchChecklistConfirmCheckedRequest( settings = [] ) {
		const promises = [];

		for ( const setting of settings ) {
			promises.push(
				this.page.waitForRequest(
					( request ) =>
						request.url().includes( '/gla/mc/settings' ) &&
						request.method() === 'POST' &&
						request.postDataJSON()[ setting ] === true
				)
			);
		}

		return Promise.all( promises );
	}

	/**
	 * Fill country code.
	 *
	 * @param {string} code
	 *
	 * @return {Promise<void>}
	 */
	async fillCountryCode( code = 'United States (US) (+1)' ) {
		const countryCodeInputBox = this.getCountryCodeInputBox();
		await countryCodeInputBox.fill( code );
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Select country code option.
	 *
	 * @param {string} code
	 *
	 * @return {Promise<void>}
	 */
	async selectCountryCodeOption( code = 'United States (US) (+1)' ) {
		await this.fillCountryCode( code );
		const countryCodeOption = this.page.getByRole( 'option', {
			name: code,
		} );
		await countryCodeOption.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Fill phone number.
	 *
	 * @param {string} number
	 *
	 * @return {Promise<void>}
	 */
	async fillPhoneNumber( number = '8888888888' ) {
		const phoneNumberInputBox = this.getPhoneNumberInputBox();
		await phoneNumberInputBox.fill( number );

		// A hack to finish typing in the input box, similar to pressing anywhere in the page.
		await phoneNumberInputBox.press( 'Tab' );

		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Click send verification code button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSendVerificationCodeButton() {
		const sendCodeButton = this.getSendVerificationCodeButton();
		await sendCodeButton.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Fill verification code.
	 *
	 * @param {string} code
	 *
	 * @return {Promise<void>}
	 */
	async fillVerificationCode( code = '123456' ) {
		const verificationInputBoxes = this.getVerificationCodeInputBoxes();
		const count = await verificationInputBoxes.count();

		for ( let i = 0; i < count; i++ ) {
			const input = await verificationInputBoxes.nth( i );
			const digit = code[ i ];
			await input.fill( digit );
		}

		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Click verify phone number button.
	 *
	 * @return {Promise<void>}
	 */
	async clickVerifyPhoneNumberButon() {
		const verifyPhoneNumberButton = this.getVerifyPhoneNumberButton();
		await verifyPhoneNumberButton.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}
}
