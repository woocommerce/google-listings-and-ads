export default class EditFreeListingsPage {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Get Save Changes button.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} Get Save Changes button.
	 */
	async getSaveChangesButton() {
		return this.page.getByRole( 'button', {
			name: 'Save changes',
			exact: true,
		} );
	}

	/**
	 * Click the Save Changes button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSaveChanges() {
		const saveChangesButton = await this.getSaveChangesButton();
		await saveChangesButton.click();
	}

	/**
	 * Check the recommended shipping settings.
	 *
	 * @return {Promise<void>}
	 */
	async checkRecommendShippingSettings() {
		return this.page
			.locator(
				'text=Recommended: Automatically sync my store’s shipping settings to Google.'
			)
			.check();
	}
	/**
	 * Fill the countries shipping time input.
	 *
	 * @param {string} input The shipping time
	 * @return {Promise<void>}
	 */
	async fillCountriesShippingTimeInput( input ) {
		await this.page.locator( '.countries-time input' ).fill( input );
	}

	/**
	 * Check the destination based tax rates.
	 *
	 * @return {Promise<void>}
	 */
	async checkDestinationBasedTaxRates() {
		await this.page
			.locator( 'text=My store uses destination-based tax rates.' )
			.check();
	}

	/**
	 * Register the requests when the save button is clicked.
	 *
	 * @return {Promise<import('@playwright/test').Request[]>} The requests.
	 */
	registerSavingRequests() {
		const targetAudienceRequest = this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/target_audience' ) &&
				request.method() === 'POST' &&
				request.postDataJSON().countries[ 0 ] === 'US'
		);
		const settingsRequest = this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/settings' ) &&
				request.method() === 'POST' &&
				request.postDataJSON().shipping_rate === 'automatic' &&
				request.postDataJSON().shipping_time === 'flat'
		);

		const syncRequest = this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/settings/sync' ) &&
				request.method() === 'POST'
		);

		return Promise.all( [
			settingsRequest,
			targetAudienceRequest,
			syncRequest,
		] );
	}
}
