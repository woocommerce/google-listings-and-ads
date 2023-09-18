/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import MockRequests from '../../mock-requests';

/**
 * Configure product listings page object class.
 */
export default class CompleteCampaign extends MockRequests {
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
			{ waitUntil: LOAD_STATE.NETWORK_IDLE }
		);
	}

	/**
	 * Get gla-tooltip__children-container class.
	 *
	 * @return {import('@playwright/test').Locator} Get gla-tooltip__children-container class.
	 */
	getSyncableProductsCountTooltip() {
		return this.page.locator( '.gla-tooltip__children-container' );
	}

	/**
	 * Get skip this step for now button.
	 *
	 * @return {import('@playwright/test').Locator} Get skip this step for now button.
	 */
	getSkipStepButton() {
		return this.page.getByRole( 'button', {
			name: 'Skip this step for now',
			exact: true,
		} );
	}

	/**
	 * Click skip this step for now button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSkipStepButtonButon() {
		const button = this.getSkipStepButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}
}
