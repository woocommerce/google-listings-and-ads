/**
 * Internal dependencies
 */
import { LOAD_STATE } from './constants';
import MockRequests from './mock-requests';
import adsReportProductsData from './__fixtures__/ads-report-products.json';

/**
 * AppRatingsOverview class.
 */
export default class AppRatingsOverview extends MockRequests {
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
	async goto( path = null ) {
		await this.page.goto(
			path ||
				'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard',
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
			this.fulfillProductStatisticsRequest( {
				timestamp: 1678886400,
				statistics: {
					active: 1250,
					expiring: 75,
					pending: 200,
					disapproved: 30,
					not_synced: 0,
				},
				scheduled_sync: 5,
				loading: false,
				error: null,
			} ),
			this.fulfillAdsReportProducts( {
				...adsReportProductsData,
				totals: {
					...adsReportProductsData.totals,
					conversions: 5,
				},
			} ),
			this.mockJetpackConnected(),
			this.mockGoogleConnected(),
			this.mockAdsAccountConnected(),
			this.mockMCConnected(),
		] );
	}

	clickGoodButton() {
		const goodButton = this.page
			.locator( '.gla-experience-rating-banner__actions' )
			.getByRole( 'button', {
				name: 'Good',
			} );

		goodButton.click();
	}
}
