/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';

/**
 * ProductFeed page object class.
 */
export default class ProductFeedPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Go to the product feed page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Mock all requests related to external accounts such as Merchant Center, Google, etc.
	 *
	 * @return {Promise<void>}
	 */
	async mockRequests() {
		await Promise.all( [
			this.fulfillMCReview( {
				cooldown: 0,
				issues: [],
				reviewEligibleRegions: [],
				status: 'ONBOARDING',
			} ),

			this.fulfillAccountIssuesRequest( {
				issues: [],
				page: 1,
				total: 0,
				loading: false,
			} ),

			this.fulfillJetPackConnection( {
				active: 'yes',
				owner: 'yes',
				displayName: 'John',
				email: 'john@email.com',
			} ),

			this.mockGoogleConnected(),

			this.fulfillAdsConnection( {
				id: 1111111,
				currency: 'USD',
				symbol: '$',
				status: 'connected',
				step: '',
			} ),
		] );
	}

	/**
	 * Get the active product value element.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The active product value element.
	 */
	async getActiveProductValueElement() {
		return this.page
			.locator( '.woocommerce-summary__item-label span >> text=Active' )
			.locator( '../..' )
			.locator( '.woocommerce-summary__item-value span' );
	}

	/**
	 * Get the active product value.
	 *
	 * @return {Promise<string>} The active product value as a string.
	 */
	async getActiveProductValue() {
		return this.page
			.locator( '.woocommerce-summary__item-label span >> text=Active' )
			.locator( '../..' )
			.locator( '.woocommerce-summary__item-value span' )
			.innerText();
	}

	/**
	 * Get the campaign notice section.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The campaign notice section.
	 */
	async getCampaignNoticeSection() {
		return this.page.locator( '.gla-create-campaign-notice' );
	}

	/**
	 * Get the create campaign button in the notice section.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The create campaign button.
	 */
	async getInNoticeCreateCampaignButton() {
		const campaignNoticeSection = await this.getCampaignNoticeSection();
		return campaignNoticeSection.getByRole( 'button', {
			name: 'Create Campaign',
		} );
	}
}
