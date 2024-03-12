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
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsetup-mc',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
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
		const button = this.getCreateAccountButton();
		return button.locator( ':scope.is-secondary' ).nth( 1 );
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
	 * Get .gla-account-card__title class.
	 *
	 * @return {import('@playwright/test').Locator} Get .gla-account-card__title class.
	 */
	getCardTitleClass() {
		return this.page.locator( '.gla-account-card__title' );
	}

	/**
	 * Get .gla-account-card__description class.
	 *
	 * @return {import('@playwright/test').Locator} Get .gla-account-card__description class.
	 */
	getCardDescriptionClass() {
		return this.page.locator( '.gla-account-card__description' );
	}

	/**
	 * Get Jetpack description row.
	 *
	 * @return {import('@playwright/test').Locator} Get Jetpack description row.
	 */
	getJetpackDescriptionRow() {
		return this.getCardDescriptionClass().first();
	}

	/**
	 * Get Google description row.
	 *
	 * @return {import('@playwright/test').Locator} Get Google description row.
	 */
	getGoogleDescriptionRow() {
		return this.getCardDescriptionClass().nth( 1 );
	}

	/**
	 * Get Merchant Center description row.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center description row.
	 */
	getMCDescriptionRow() {
		return this.getCardDescriptionClass().nth( 3 );
	}

	/**
	 * Get Google Ads title.
	 *
	 * @return {import('@playwright/test').Locator} Get Google Ads title.
	 */
	getAdsTitleRow() {
		return this.getCardTitleClass().nth( 2 );
	}

	/**
	 * Get Google Merchant Center title.
	 *
	 * @return {import('@playwright/test').Locator} Get Google Merchant Center title.
	 */
	getMCTitleRow() {
		return this.getCardTitleClass().nth( 3 );
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
	 * Get .gla-connected-icon-label class.
	 *
	 * @return {import('@playwright/test').Locator} Get .gla-connected-icon-label class.
	 */
	getConnectedLabelClass() {
		return this.page.locator( '.gla-connected-icon-label' );
	}

	/**
	 * Get Jetpack connected label.
	 *
	 * @return {import('@playwright/test').Locator} Get Jetpack connected label.
	 */
	getJetpackConnectedLabel() {
		return this.getConnectedLabelClass().first();
	}

	/**
	 * Get Google connected label.
	 *
	 * @return {import('@playwright/test').Locator} Get Google connected label.
	 */
	getGoogleConnectedLabel() {
		return this.getConnectedLabelClass().nth( 1 );
	}

	/**
	 * Get Merchant Center connected label.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center connected label.
	 */
	getMCConnectedLabel() {
		return this.getConnectedLabelClass().nth( 2 );
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
	 * Get sub section title row.
	 *
	 * @return {import('@playwright/test').Locator} Get sub section title row.
	 */
	getSubSectionTitleRow() {
		return this.page.locator( '.wcdl-subsection-title' );
	}

	/**
	 * Get section footer row.
	 *
	 * @return {import('@playwright/test').Locator} Get section footer row.
	 */
	getSectionFooterRow() {
		return this.page.locator( '.wcdl-section-card-footer' );
	}

	/**
	 * Get select existing Merchant Center account title.
	 *
	 * @return {import('@playwright/test').Locator} Get select existing Merchant Center account title.
	 */
	getSelectExistingMCAccountTitle() {
		return this.getSubSectionTitleRow().nth( 4 );
	}

	/**
	 * Get MC accounts select element.
	 *
	 * @return {import('@playwright/test').Locator} Get select MC accounts select element.
	 */
	getMCAccountsSelect() {
		return this.page.locator( 'select[id*="inspector-select-control"]' );
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
	 * Get account cards.
	 *
	 * @return {import('@playwright/test').Locator} Get account cards.
	 */
	getAccountCards() {
		return this.page.locator( '.gla-account-card' );
	}

	/**
	 * Get WordPress account card.
	 *
	 * @return {import('@playwright/test').Locator} Get WordPress account card.
	 */
	getWPAccountCard() {
		return this.getAccountCards().first();
	}

	/**
	 * Get Google account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Google account card.
	 */
	getGoogleAccountCard() {
		return this.getAccountCards().nth( 1 );
	}

	/**
	 * Get WordPress account card.
	 *
	 * @return {import('@playwright/test').Locator} Get WordPress account card.
	 */
	getGoogleAdsAccountCard() {
		return this.getAccountCards().nth( 2 );
	}

	/**
	 * Get Merchant Center account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center account card.
	 */
	getMCAccountCard() {
		return this.getAccountCards().nth( 3 );
	}

	/**
	 * Get Merchant Center card footer.
	 *
	 * @return {import('@playwright/test').Locator} Get Merchant Center card footer.
	 */
	getMCCardFooter() {
		return this.getMCAccountCard().locator( '.wcdl-section-card-footer' );
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
	 * Click create account button.
	 *
	 * @return {Promise<void>}
	 */
	async getCreateAdsAccountButton() {
		return this.page
			.getByRole( 'button', {
				name: 'Create account',
				exact: true,
			} )
			.first();
	}

	/**
	 * Click claim account button.
	 *
	 * @return {Promise<void>}
	 */
	async getClaimAdsAccountButton() {
		return this.page
			.getByRole( 'button', {
				name: 'Claim Account',
				exact: true,
			} )
			.first();
	}

	/**
	 * Click create account button.
	 *
	 * @return {Promise<void>}
	 */
	async clickCreateAdsAccountButton() {
		const button = await this.getCreateAdsAccountButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Get ads account connected text.
	 *
	 * @return {import('@playwright/test').Locator} Get ads account connected text.
	 */
	getAdsAccountConnectedText() {
		return this.getGoogleAdsAccountCard().getByText( 'Connected' );
	}

	/**
	 * Get ads claim account button.
	 *
	 * @return {import('@playwright/test').Locator} Get ads account Claim Account button.
	 */
	getAdsClaimAccountButton() {
		return this.getClaimAdsAccountButton();
	}

	/**
	 * Get ads claim account text.
	 *
	 * @return {import('@playwright/test').Locator} Get ads account Claim account text.
	 */
	getAdsClaimAccountText() {
		return this.getGoogleAdsAccountCard().getByText(
			'Your new ads account has been created, but you do not have access to it yet. '
		);
	}
}
