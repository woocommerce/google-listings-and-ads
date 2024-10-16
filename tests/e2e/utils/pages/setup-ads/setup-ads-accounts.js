/**
 * Internal dependencies
 */
import MockRequests from '../../mock-requests';

export default class SetupAdsAccount extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Get Continue button.
	 *
	 * @return {import('@playwright/test').Locator} The Continue button.
	 */
	getContinueButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Get Connect Account button.
	 *
	 * @return {import('@playwright/test').Locator} The Connect button.
	 */
	getConnectAdsButton() {
		return this.page.getByRole( 'button', {
			name: 'Connect',
			exact: true,
		} );
	}

	/**
	 * Get ads account select.
	 *
	 * @return {import('@playwright/test').Locator} Get ads account select.
	 */
	getAdsAccountSelect() {
		return this.page.locator( '.gla-connect-ads select' );
	}

	/**
	 * Get the create account modal.
	 *
	 * @return {import('@playwright/test').Locator} The create account modal.
	 */
	getCreateAccountModal() {
		return this.page.locator( '.gla-ads-terms-modal' );
	}

	/**
	 * Get the accept invite for google ads account modal.
	 *
	 * @return {import('@playwright/test').Locator} The accept invite account modal.
	 */
	getAcceptAccountModal() {
		return this.page.locator( '.gla-ads-invite-modal' );
	}

	/**
	 * Get Accept terms checkbox.
	 *
	 * @return {import('@playwright/test').Locator} Get Accept terms checkbox.
	 */
	getAcceptTermCreateAccount() {
		return this.getCreateAccountModal().getByRole( 'checkbox', {
			name: 'I have read and accept these terms',
		} );
	}

	/**
	 * Get Create account button located in the modal.
	 *
	 * @return {import('@playwright/test').Locator} Get Create account button.
	 */
	getCreateAdsAccountButtonModal() {
		return this.getCreateAccountModal().getByRole( 'button', {
			name: 'Create account',
			exact: true,
		} );
	}

	/**
	 * Get Close button in the Accept Invitation modal.
	 *
	 * @return {import('@playwright/test').Locator} Close modal button.
	 */
	getCloseAcceptAccountButtonModal() {
		return this.getAcceptAccountModal().getByRole( 'button', {
			name: 'Close',
			exact: true,
		} );
	}

	/**
	 * Get Claim account button in the Accept Invitation modal.
	 *
	 * @return {import('@playwright/test').Locator} Claim account button.
	 */
	getClaimAcceptAccountButtonModal() {
		return this.getAcceptAccountModal().getByRole( 'button', {
			name: 'Claim account in Google Ads',
			exact: true,
		} );
	}

	/**
	 * Get claim account button in the Google Ads card.
	 *
	 * @return {import('@playwright/test').Locator} Claim account button.
	 */
	getAdsClaimAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Claim Account',
			exact: true,
		} );
	}

	/**
	 * Get claim Google Ads account text.
	 *
	 * @return {import('@playwright/test').Locator} Claim Google Ads account text.
	 */
	getAdsClaimAccountText() {
		return this.page.getByText(
			'Claim your new Google Ads account to complete this setup.'
		);
	}

	/**
	 * Get connect to a different account button.
	 *
	 * @return {import('@playwright/test').Locator} Get connect to a different account button.
	 */
	getConnectDifferentAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, connect to a different Google Ads account',
			exact: true,
		} );
	}

	/**
	 * Click the Continue button.
	 *
	 * @return {Promise<void>}
	 */
	async clickContinue() {
		await this.getContinueButton().click();
	}

	/**
	 * Click the Connect Ads button.
	 *
	 * @return {Promise<void>}
	 */
	async clickConnectAds() {
		await this.getConnectAdsButton().click();
	}

	/**
	 * Selects the ads account.
	 *
	 * @param {string} accountNumber The Ads account number.
	 * @return {Promise<void>}
	 */
	async selectAnExistingAdsAccount( accountNumber ) {
		await this.getAdsAccountSelect().selectOption( accountNumber );
	}

	/**
	 * Register the requests when the save button is clicked.
	 *
	 * @param {string} [adsAccountID] The Ads account ID.
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	async registerConnectAdsAccountRequests( adsAccountID = null ) {
		return this.page.waitForRequest( ( request ) =>
			request.url().includes( '/gla/ads/accounts' ) &&
			request.method() === 'POST' &&
			adsAccountID
				? request.postDataJSON().id === adsAccountID
				: true
		);
	}

	/**
	 * Click ToS checkbox from modal.
	 *
	 * @return {Promise<void>}
	 */
	async clickToSCheckboxFromModal() {
		const checkbox = this.getAcceptTermCreateAccount();
		await checkbox.check();
	}

	/**
	 * Click create account button from modal.
	 *
	 * @return {Promise<void>}
	 */
	async clickCreateAccountButtonFromModal() {
		const button = this.getCreateAdsAccountButtonModal();
		await button.click();
	}

	/**
	 * Click close button from modal.
	 *
	 * @return {Promise<void>}
	 */
	async clickCloseAcceptAccountButtonFromModal() {
		const button = this.getCloseAcceptAccountButtonModal();
		await button.click();
	}

	/**
	 * Click connect to a different account button.
	 *
	 * @return {Promise<void>}
	 */
	async clickConnectDifferentAccountButton() {
		const button = this.getConnectDifferentAccountButton();
		await button.click();
	}

	/**
	 * Await the response for ads connection.
	 *
	 * @return {Promise<import('@playwright/test').Response>} The response.
	 */
	async awaitAdsConnectionResponse() {
		return this.page.waitForResponse( ( response ) => {
			return (
				response.url().includes( '/gla/ads/connection' ) &&
				response.status() === 200
			);
		} );
	}
}
