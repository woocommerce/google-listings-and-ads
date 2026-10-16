/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import CompleteCampaign from './step-3-complete-campaign';

/**
 * Create campaign page object class.
 */
export default class CreateCampaign extends CompleteCampaign {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Get continue button.
	 *
	 * @return {import('@playwright/test').Locator} Get continue button.
	 */
	getContinueSetupButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Click complete setup button.
	 *
	 * @return {Promise<void>}
	 */
	async clickContinueButton() {
		const button = this.getContinueSetupButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Get final URL card.
	 *
	 * @return {import('@playwright/test').Locator} Get final URL card.
	 */
	getFinalUrlCard() {
		return this.page.locator( '.gla-final-url-card' );
	}

	/**
	 * Get select different final URL button.
	 *
	 * @return {import('@playwright/test').Locator} Get select different final URL button.
	 */
	getSelectDifferentFinalUrlButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, select a different Final URL',
		} );
	}

	/**
	 * Get create campaign button.
	 *
	 * @return {import('@playwright/test').Locator} Get create campaign button.
	 */
	getCreateCampaignButton() {
		// Intentionally not using getByRole here, as another button with the same accessible name exists in the Stepper header.
		return this.page.locator(
			'button[data-action="submit-campaign-and-assets"]'
		);
	}
}
