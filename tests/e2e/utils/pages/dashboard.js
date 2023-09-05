/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';

/**
 * Dashboard page object class.
 */
export default class DashboardPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
		this.freeListingRow = this.page.locator(
			'.gla-all-programs-table-card table tr:nth-child(2)'
		);
		this.editFreeListingButton = this.freeListingRow.getByRole( 'button', {
			name: 'Edit',
		} );
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
	 * Mock all requests related to external accounts such as Merchant Center, Google, etc.
	 *
	 * @return {Promise<void>}
	 */
	async mockRequests() {
		// Mock Reports Programs
		await this.fulfillMCReportProgram( {
			free_listings: null,
			products: null,
			intervals: null,
			totals: {
				clicks: 0,
				impressions: 0,
			},
			next_page: null,
		} );

		await this.fulfillTargetAudience( {
			location: 'selected',
			countries: [ 'US' ],
			locale: 'en_US',
			language: 'English',
		} );

		await this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName: 'John',
			email: 'john@email.com',
		} );

		await this.fulfillGoogleConnection( {
			active: 'yes',
			email: 'john@email.com',
			scope: [],
		} );

		await this.fulfillAdsConnection( {
			id: 0,
			currency: null,
			symbol: '$',
			status: 'disconnected',
		} );
	}

	/**
	 * Go to the dashboard page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard',
			{ waitUntil: 'domcontentloaded' }
		);
	}

	/**
	 * Click the edit free listings button.
	 *
	 * @return {Promise<void>}
	 */
	async clickEditFreeListings() {
		await this.editFreeListingButton.click();
	}

	/**
	 * Get the continue to edit button from the modal.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} Get the continue to edit button from the modal.
	 */
	async getContinueToEditButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue to edit',
			exact: true,
		} );
	}

	/**
	 * Get the don't edit button from the modal.
	 *
	 * @return {Promise<import('@playwright/test').Locator>}  Get the don't edit button from the modal.
	 */
	async getDontEditButton() {
		return this.page.getByRole( 'button', {
			name: "Don't edit",
			exact: true,
		} );
	}

	/**
	 * Click the continue to edit button from the modal.
	 *
	 * @return {Promise<void>}
	 */
	async clickContinueToEditButton() {
		const continueToEditButton = await this.getContinueToEditButton();
		await continueToEditButton.click();
	}
}
