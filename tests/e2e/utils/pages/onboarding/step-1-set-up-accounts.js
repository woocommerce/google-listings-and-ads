/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import MockRequests from '../../mock-requests';

/**
 * Set up accounts page object class.
 */
export default class SetUpAccountsPage extends MockRequests {
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
	 * @param {Object} [searchParamsObject] Additional Search params to be added to the URL.
	 * @return {Promise<void>}
	 */
	async goto( searchParamsObject ) {
		const search = new URLSearchParams( {
			page: 'wc-admin',
			path: '/google/setup-mc',
			...searchParamsObject,
		} );

		await this.page.goto( '/wp-admin/admin.php?' + search.toString(), {
			waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
		} );
	}

	/**
	 * Get "Create account" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Create account" button.
	 */
	getCreateAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Create account',
			exact: true,
		} );
	}

	/**
	 * Get MC "Create account" button from the page.
	 *
	 * @return {import('@playwright/test').Locator} Get MC "Create account" button from the page.
	 */
	getMCCreateAccountButtonFromPage() {
		return this.getMCCardFooterButton();
	}

	/**
	 * Get MC "Create account" button from the modal.
	 *
	 * @return {import('@playwright/test').Locator} Get MC "Create account" button from the modal.
	 */
	getMCCreateAccountButtonFromModal() {
		const button = this.getCreateAccountButton();
		return button.locator( ':scope.is-primary' );
	}

	/**
	 * Get Google description row.
	 *
	 * @return {import('@playwright/test').Locator} Get Google description row.
	 */
	getGoogleDescriptionRow() {
		return this.getGoogleAccountCard().locator(
			'.gla-account-card__description'
		);
	}

	/**
	 * Get Merchant Center description row.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center description row.
	 */
	getMCDescriptionRow() {
		return this.getGoogleDescriptionRow().locator( 'p', {
			hasText: 'Merchant Center ID',
		} );
	}

	/**
	 * Get Google Merchant Center title.
	 *
	 * @return {import('@playwright/test').Locator} Get Google Merchant Center title.
	 */
	getMCTitleRow() {
		return this.getMCAccountCard().locator( '.gla-account-card__title' );
	}

	/**
	 * Get modal.
	 *
	 * @return {import('@playwright/test').Locator} Get modal.
	 */
	getModal() {
		return this.page.locator( '.components-modal__content' );
	}

	/**
	 * Get modal header.
	 *
	 * @return {import('@playwright/test').Locator} Get modal header.
	 */
	getModalHeader() {
		return this.page.locator( '.components-modal__header' );
	}

	/**
	 * Get modal checkbox.
	 *
	 * @return {import('@playwright/test').Locator} Get modal checkbox.
	 */
	getModalCheckbox() {
		return this.page.getByRole( 'checkbox' );
	}

	/**
	 * Get modal primary button.
	 *
	 * @return {import('@playwright/test').Locator} Get modal primary button.
	 */
	getModalPrimaryButton() {
		return this.getModal().locator( 'button.is-primary' );
	}

	/**
	 * Get modal secondary button.
	 *
	 * @return {import('@playwright/test').Locator} Get modal secondary button.
	 */
	getModalSecondaryButton() {
		return this.getModal().locator( 'button.is-secondary' );
	}

	/**
	 * Get Google combo card connected label.
	 *
	 * @return {import('@playwright/test').Locator} Get Google combo card connected label.
	 */
	getGoogleComboConnectedLabel() {
		return this.getGoogleAccountCard().locator(
			'.gla-connected-icon-label'
		);
	}

	/**
	 * Get "Reclaim my URL" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Reclaim my URL" button.
	 */
	getReclaimMyURLButton() {
		return this.page.getByRole( 'button', {
			name: 'Reclaim my URL',
			exact: true,
		} );
	}

	/**
	 * Get "Switch account" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Switch account" button.
	 */
	getSwitchAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Switch account',
			exact: true,
		} );
	}

	/**
	 * Get reclaiming URL input.
	 *
	 * @return {import('@playwright/test').Locator} Get reclaiming URL input.
	 */
	getReclaimingURLInput() {
		return this.page.locator( 'input#inspector-input-control-0' );
	}

	/**
	 * Get select existing Merchant Center account title.
	 *
	 * @return {import('@playwright/test').Locator} Get select existing Merchant Center account title.
	 */
	getSelectExistingMCAccountTitle() {
		return this.getMCAccountCard().locator( '.gla-account-card__title' );
	}

	/**
	 * Get MC accounts select element.
	 *
	 * @return {import('@playwright/test').Locator} Get select MC accounts select element.
	 */
	getMCAccountsSelect() {
		return this.getMCAccountCard().getByRole( 'combobox' );
	}

	/**
	 * Get "Connect" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Connect" button.
	 */
	getConnectButton() {
		return this.page.getByRole( 'button', {
			name: 'Connect',
			exact: true,
		} );
	}

	/**
	 * Get WordPress account card.
	 *
	 * @return {import('@playwright/test').Locator} Get WordPress account card.
	 */
	getWPAccountCard() {
		return this.page.locator( '.gla-account-card', {
			hasText: 'WordPress.com',
		} );
	}

	/**
	 * Get Google account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Google account card.
	 */
	getGoogleAccountCard() {
		return this.page.locator(
			'.gla-google-combo-service-account-card--google'
		);
	}

	/**
	 * Get WPCom app authorization card.
	 *
	 * @return {import('@playwright/test').Locator} Locator for WPCom app authorization card.
	 */
	getAuthorizeWPComAppCard() {
		return this.page.locator( '.gla-authorize-wpcom-app-card' );
	}

	/**
	 * Get Google Ads account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Google Ads account card.
	 */
	getGoogleAdsAccountCard() {
		return this.page.locator(
			'.gla-google-combo-service-account-card--ads'
		);
	}

	/**
	 * Get Merchant Center account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center account card.
	 */
	getMCAccountCard() {
		return this.page.locator(
			'.gla-google-combo-service-account-card--mc'
		);
	}

	/**
	 * Get Merchant Center card footer.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center card footer.
	 */
	getMCCardFooter() {
		return this.getMCAccountCard().locator( '.gla-account-card__actions' );
	}

	/**
	 * Get Merchant Center card footer button.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center card footer button.
	 */
	getMCCardFooterButton() {
		return this.getMCCardFooter().getByRole( 'button' );
	}

	/**
	 * Get create a new Merchant Center account button.
	 *
	 * @return {import('@playwright/test').Locator} Get create a new Merchant Center account button.
	 */
	getCreateNewMCAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, create a new Merchant Center account',
			exact: true,
		} );
	}

	/**
	 * Get "Continue" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Continue" button.
	 */
	getContinueButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Click "Continue" button.
	 *
	 * @return {Promise<void>}
	 */
	async clickContinueButton() {
		const continueButton = this.getContinueButton();
		await continueButton.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Get link of Google Merchant Center Help.
	 *
	 * @return {import('@playwright/test').Locator} Get link of Google Merchant Center Help.
	 */
	getMCHelpLink() {
		return this.page.getByRole( 'link', {
			name: 'Google Merchant Center Help',
			exact: true,
		} );
	}

	/**
	 * Get link of CSS partners.
	 *
	 * @param {string} name
	 *
	 * @return {import('@playwright/test').Locator} Get link of CSS partners.
	 */
	getCSSPartnersLink( name = 'here' ) {
		return this.page.getByRole( 'link', {
			name,
			exact: true,
		} );
	}

	/**
	 * Get connect to a different Google Ads account button.
	 *
	 * @return {import('@playwright/test').Locator} Get connect to a different Google Ads account button.
	 */
	getConnectDifferentAdsAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, connect to a different Google Ads account',
			exact: true,
		} );
	}

	/**
	 * Get create a new Google Ads account button.
	 *
	 * @return {import('@playwright/test').Locator} Get create a new Google Ads account button.
	 */
	getCreateNewAdsAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, create a new Google Ads account',
			exact: true,
		} );
	}

	/**
	 * Get terms checkbox.
	 *
	 * @return {import('@playwright/test').Locator} Terms checkbox.
	 */
	getTermsCheckbox() {
		return this.page.getByLabel( /I accept the terms and conditions/ );
	}

	/**
	 * Get "Edit" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Edit" button.
	 */
	getEditButton() {
		return this.page.getByRole( 'button', {
			name: 'Edit',
			exact: true,
		} );
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
	 * Get the "Update store address" button on the store address card.
	 *
	 * @return {import('@playwright/test').Locator} Get the "Update store address" button on the store address card.
	 */
	getStoreAddressButton() {
		return this.getStoreAddressCard().getByRole( 'button', {
			name: 'Update store address',
			exact: true,
		} );
	}

	/**
	 * Get "Or, connect to a different Google account" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Or, connect to a different Google account" button.
	 */
	getConnectDifferentGoogleAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, connect to a different Google account',
			exact: true,
		} );
	}

	/**
	 * Get "Cancel" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Cancel" button.
	 */
	getCancelButton() {
		return this.page.getByRole( 'button', {
			name: 'Cancel',
			exact: true,
		} );
	}

	/*
	 * Register the response when connecting an Ads account.
	 *
	 * @return {Promise<import('@playwright/test').Response>} The response.
	 */
	registerAdsAccountsResponse() {
		return this.page.waitForResponse(
			( response ) =>
				response.url().includes( '/gla/ads/accounts' ) &&
				response.status() === 200
		);
	}

	/**
	 * Register the response when syncing the store address.
	 *
	 * @return {Promise<import('@playwright/test').Response>} The response.
	 */
	registerContactInformationSyncRequest() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/contact-information' ) &&
				request.method() === 'POST'
		);
	}
}
