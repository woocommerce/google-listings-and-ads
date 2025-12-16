/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import CompleteCampaign from './step-3-complete-campaign';

/**
 * Configure product listings page object class.
 */
export default class CreateCampaign extends CompleteCampaign {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page, { serviceBasedMerchant: true } );
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
}
