/**
 * External dependencies
 */
import { Locator } from '@playwright/test';

/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';
import { LOAD_STATE } from '../constants';
import adsReportProductsData from '../__fixtures__/ads-report-products.json';
import mcProductStatistics from '../__fixtures__/mc-product-statistics.json';

export default class ShippingPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Mock all requests related to external resources.
	 *
	 * @return {Promise<void>}
	 */
	async mockRequests() {
		await this.mockSuccessfulSettingsSyncRequest();

		await this.fulfillSettings( {
			shipping_rate: 'flat',
			shipping_time: 'flat',
			tax_rate: 'destination',
		} );

		await this.fulfillTargetAudience( {
			location: 'selected',
			countries: [ 'US' ],
			locale: 'en_US',
			language: 'English',
		} );

		await this.fulfillAdsReportProducts( adsReportProductsData );
		await this.fulfillProductStatisticsRequest( mcProductStatistics );
	}

	/**
	 * Go to the Shipping page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fshipping',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get Save Changes button.
	 *
	 * @return {Locator} Get Save Changes button.
	 */
	getSaveChangesButton() {
		return this.page.getByRole( 'button', {
			name: 'Save changes',
			exact: true,
		} );
	}

	/**
	 * Get the "Don't save" button.
	 *
	 * @return {Locator} The button.
	 */
	getConfirmationModal() {
		return this.page.getByRole( 'dialog', { name: 'Before you save…' } );
	}

	/**
	 * Get the "Don't save" button in the confirmation modal.
	 *
	 * @return {Locator} The button.
	 */
	getDontSaveButton() {
		return this.getConfirmationModal().getByRole( 'button', {
			name: `Don't save`,
		} );
	}

	/**
	 * Get the "Continue to save" button in the confirmation modal.
	 *
	 * @return {Locator} The button.
	 */
	getContinueSaveButton() {
		return this.getConfirmationModal().getByRole( 'button', {
			name: 'Continue to save',
		} );
	}

	/**
	 * Click the Save Changes button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSaveChanges() {
		await this.getSaveChangesButton().click();
	}

	/**
	 * Check the recommended shipping settings.
	 *
	 * @return {Promise<void>}
	 */
	async checkRecommendShippingSettings() {
		return this.page
			.locator(
				'text=Automatically sync my store’s shipping settings to Google.'
			)
			.check();
	}
	/**
	 * Fill the countries shipping time input.
	 *
	 * @param {string} min The minimum shipping time
	 * @param {string} max The maximum shipping time
	 * @return {Promise<void>}
	 */
	async fillCountriesShippingTimeInput( min, max ) {
		const timesLocator = this.page.locator( '.countries-time input' );
		await timesLocator.first().fill( min );
		await timesLocator.last().fill( max );
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
