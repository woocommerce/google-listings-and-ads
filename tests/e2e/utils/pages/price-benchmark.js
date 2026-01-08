/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';
import adsReportProductsData from '../__fixtures__/ads-report-products.json';
import mcProductStatistics from '../__fixtures__/mc-product-statistics.json';

/**
 * ProductFeed page object class.
 */
export default class PriceBenchmarkPage extends MockRequests {
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
	 * @param {string|null} path - The path to navigate to. If null, defaults to the Price Benchmark page.
	 *
	 * @return {Promise<void>}
	 */
	async goto( path = null ) {
		const url =
			path ||
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fprice-benchmark';
		await this.page.goto( url, {
			waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
		} );
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

			this.fulfillAdsReportProducts( adsReportProductsData ),
			this.fulfillProductStatisticsRequest( mcProductStatistics ),

			this.mockJetpackConnected(),
			this.mockGoogleConnected(),
			this.mockAdsAccountConnected(),
		] );
	}

	/**
	 * Retrieves the locator for the "Change Price" modal element on the page.
	 *
	 * @return {import('@playwright/test').Locator} The "Change Price" modal element.
	 */
	getChangePriceModal() {
		return this.page.locator( '.gla-change-price-modal' );
	}

	/**
	 * Retrieves the price input field within the "Change Price" modal.
	 *
	 * @return {import('@playwright/test').Locator} The element handle for the "New Price" input field.
	 */
	getPriceInputModal() {
		return this.getChangePriceModal().getByLabel( 'New Price' );
	}

	/**
	 * Retrieves the "Change Price" button element within the change price modal.
	 *
	 * @return {import('@playwright/test').Locator} The "Change Price" button element within the modal.
	 */
	getChangePriceModalButton() {
		return this.getChangePriceModal().getByRole( 'button', {
			name: 'Change Price',
		} );
	}

	/**
	 * Retrieves the "Close" button element from the change price modal.
	 *
	 * @return {import('@playwright/test').Locator} The "Close" button element.
	 */
	getCloseModalButton() {
		return this.getChangePriceModal().getByRole( 'button', {
			name: 'Close',
		} );
	}

	/**
	 * Retrieves the error message element for the price input field
	 * within the "Change Price" modal.
	 *
	 * @return {import('@playwright/test').Locator} The error message element.
	 */
	getPriceInputError() {
		return this.getChangePriceModal().locator(
			'.components-base-control__help'
		);
	}

	/**
	 * Retrieves the first product row element from the table on the page.
	 *
	 * @return {import('@playwright/test').Locator} The first row in the table body.
	 */
	getFirstProductRow() {
		return this.page.locator( 'table tbody tr:first-child' );
	}

	/**
	 * Retrieves the "Change Price" button element from the first product row.
	 *
	 * @return {import('@playwright/test').Locator} The "Change Price" button element.
	 */
	getFirstProductChangePriceLink() {
		return this.getFirstProductRow().getByRole( 'button', {
			name: 'Change Price',
		} );
	}
}
