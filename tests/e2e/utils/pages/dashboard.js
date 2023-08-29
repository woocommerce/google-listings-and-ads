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
	 */
	async closePage() {
		await this.page.close();
	}

	/**
	 * Mock all requests related to external accounts such as Merchant Center, Google, etc.
	 */
	async mockRequests() {
		// Mock Reports Programs
		this.fulfillMCReportProgram( {
			free_listings: null,
			products: null,
			intervals: null,
			totals: {
				clicks: 0,
				impressions: 0,
			},
			next_page: null,
		} );

		this.fulfillTargetAudience( {
			location: 'selected',
			countries: [ 'US' ],
			locale: 'en_US',
			language: 'English',
		} );

		this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName: 'John',
			email: 'john@email.com',
		} );

		this.fulfillGoogleConnection( {
			active: 'yes',
			email: 'john@email.com',
			scope: [],
		} );

		this.fulfillAdsConnection( {
			id: 0,
			currency: null,
			symbol: '$',
			status: 'disconnected',
		} );
	}

	/**
	 * Mock the onboarded state. Otherwise it will be redirected to the onboarding page.
	 */
	async onboarded() {
		this.page.route( /\/admin.php\b/, ( route ) => {
			const url = `${ route.request().url() }&gla-e2e-onboarded=true`;
			route.continue( { url } );
		} );
	}

	/**
	 * Go to the dashboard page.
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard',
			{ waitUntil: 'domcontentloaded' }
		);
	}

	/**
	 * Click the edit free listings button.
	 */
	async clickEditFreeListings() {
		await this.editFreeListingButton.click();
	}

	/**
	 * Get the continue to edit button from the modal.
	 *
	 * @returns {Promise<import('@playwright/test').Locator>}
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
	 * @returns {Promise<import('@playwright/test').Locator>}
	 */
	async getDontEditButton() {
		return this.page.getByRole( 'button', {
			name: "Don't edit",
			exact: true,
		} );
	}

	/**
	 * Click the continue to edit button from the modal.
	 * @returns {Promise<void>}
	 */
	async clickContinueToEditButton() {
		const continueToEditButton = await this.getContinueToEditButton();
		await continueToEditButton.click();
	}
}
