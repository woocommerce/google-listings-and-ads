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
		await this.page
			.locator( '.gla-connect-ads select' )
			.selectOption( accountNumber );
	}

	/**
	 * Mock the Ads accounts response.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async mockAdsAccountsResponse( payload ) {
		await this.fulfillAdsAccounts( payload );
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
	 * Get Accept terms checkbox.
	 *
	 * @return {import('@playwright/test').Locator} Get Accept terms checkbox.
	 */
	getAcceptTermCreateAccount() {
		return this.getCreateAccountModal().locator(
			'text=I have read and accept these terms'
		);
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
	 * Register the requests when the save button is clicked.
	 *
	 * @param {string} [adsAccountID] The Ads account ID.
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	async registerConnectAdsAccountRequests( adsAccountID = null ) {
		return this.page.waitForRequest( ( request ) => {
			if ( adsAccountID ) {
				return (
					request.url().includes( '/gla/ads/accounts' ) &&
					request.method() === 'POST' &&
					request.postDataJSON().id === adsAccountID
				);
			}

			return (
				request.url().includes( '/gla/ads/accounts' ) &&
				request.method() === 'POST'
			);
		} );
	}
}
