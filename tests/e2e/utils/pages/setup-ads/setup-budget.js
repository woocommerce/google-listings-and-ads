/**
 * Internal dependencies
 */
import MockRequests from '../../mock-requests';

export default class SetupBudget extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Get budget input.
	 *
	 * @return {import('@playwright/test').Locator} The budget input box.
	 */
	getBudgetInput() {
		return this.page
			.locator( '.gla-budget-section__card-body__cost' )
			.getByLabel( 'Daily average cost' );
	}

	/**
	 * Get lower budget tip.
	 *
	 * @return {import('@playwright/test').Locator} The lower budget tip.
	 */
	getLowerBudgetTip() {
		return this.page.locator( '.gla-budget-recommendation__low-budget' );
	}

	/**
	 * Get billing setup section.
	 *
	 * @return {import('@playwright/test').Locator} The billing setup section.
	 */
	getBillingSetupSection() {
		return this.page.locator( '.gla-google-ads-billing-setup-card' );
	}

	/**
	 * Get set up billing button.
	 *
	 * @return {import('@playwright/test').Locator} The set up billing button.
	 */
	getSetUpBillingButton() {
		return this.getBillingSetupSection().getByRole( 'button', {
			name: 'Set up billing',
			exact: true,
		} );
	}

	/**
	 * Get set up billing link.
	 *
	 * @return {import('@playwright/test').Locator} The set up billing link.
	 */
	getSetUpBillingLink() {
		return this.getBillingSetupSection().getByRole( 'link', {
			name: 'click here instead',
		} );
	}

	/**
	 * Get billing setup success section.
	 *
	 * @return {import('@playwright/test').Locator} The billing setup success section.
	 */
	getBillingSetupSuccessSection() {
		return this.page.locator(
			'.gla-google-ads-billing-card__success-status'
		);
	}

	/**
	 * Fill the budget.
	 *
	 * @param {string} budget
	 *
	 * @return {Promise<void>}
	 */
	async fillBudget( budget = '0' ) {
		const input = this.getBudgetInput();
		await input.fill( budget );
	}

	/**
	 * Click set up billing button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSetUpBillingButton() {
		const button = this.getSetUpBillingButton();
		await button.click();
	}

	/**
	 * Click set up billing link.
	 *
	 * @return {Promise<void>}
	 */
	async clickSetUpBillingLink() {
		const link = this.getSetUpBillingLink();
		await link.click();
	}

	/**
	 * Await for billing status request.
	 *
	 * @return {Promise<Request>} The request.
	 */
	async awaitForBillingStatusRequest() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/ads/billing-status' ) &&
				request.method() === 'GET',
			{ timeout: 35000 }
		);
	}

	/**
	 * Await for the campaign creation request.
	 *
	 * @param {string} budget The campaign budget.
	 * @param {Array}  targetLocations The targeted locations.
	 * @return {Promise<Request>} The request.
	 */
	async awaitForCampaignCreationRequest( budget, targetLocations ) {
		return this.page.waitForRequest(
			( request ) => {
				return (
					request.url().includes( '/gla/ads/campaigns' ) &&
					request.method() === 'POST' &&
					request.postDataJSON().amount === parseInt( budget, 10 ) &&
					targetLocations.every( ( item ) =>
						request
							.postDataJSON()
							.targeted_locations.includes( item )
					)
				);
			},
			{
				timeout: 35000,
			}
		);
	}

	/**
	 * Mock the budget recommendation.
	 *
	 * @param {Object} payload The payload.
	 * @return {Promise<void>}
	 */
	async mockBudgetRecommendation( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/campaigns\/budget-recommendation\b/,
			payload,
			200
		);
	}

	/**
	 * Mock the campaign creation process and the Ads setup completion.
	 *
	 * @param {string} budget The campaign budget.
	 * @param {Array}  targetLocations The targeted locations.
	 * @return {Promise<void>}
	 */
	async mockCampaignCreationAndAdsSetupCompletion( budget, targetLocations ) {
		//This step is necessary; otherwise, it will set the ADS_SETUP_COMPLETED_AT option in the database, which could potentially impact other tests.
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/setup\/complete\b/,
			null,
			200,
			[ 'POST' ]
		);

		await this.fulfillAdsCampaignsRequest(
			{
				id: 111111111,
				name: 'Test Campaign',
				status: 'enabled',
				type: 'performance_max',
				amount: budget,
				country: 'US',
				targeted_locations: targetLocations,
			},
			200,
			[ 'POST' ]
		);

		await this.awaitForCampaignCreationRequest( budget, targetLocations );
	}
}
