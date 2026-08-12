/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';

/**
 * Analytics Overview page object class.
 */
export default class AnalyticsOverviewPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Go to the Analytics Overview page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get the Analytics Overview promo section registered by this plugin.
	 *
	 * @return {import('@playwright/test').Locator} The Analytics Overview promo section.
	 */
	getAnalyticsOverviewPromoSection() {
		return this.page.locator( '.gla-analytics-overview-promo' );
	}
}
