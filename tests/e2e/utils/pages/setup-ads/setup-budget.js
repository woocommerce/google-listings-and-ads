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
	 * Get budget recommendation text row.
	 *
	 * @return {import('@playwright/test').Locator} The budget recommendation text row.
	 */
	getBudgetRecommendationTextRow() {
		return this.page.locator( '.components-tip p > em > strong' );
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
	 * Extract budget recommendation range.
	 *
	 * @param {string} text
	 *
	 * @return {string} The budget recommendation range.
	 */
	extractBudgetRecommendationRange( text ) {
		const match = text.match( /set a daily budget of (\d+ to \d+)/ );
		if ( match ) {
			return match[ 1 ];
		}
		return '';
	}

	/**
	 * Register the responses when removing an ads audience.
	 *
	 * @return {Promise<import('@playwright/test').Response>} The response.
	 */
	registerBudgetRecommendationResponse() {
		return this.page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes( '/gla/ads/campaigns/budget-recommendation' ) &&
				response.status() === 200
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
}
