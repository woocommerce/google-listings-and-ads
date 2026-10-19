/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';
import adsReportProductsData from '../__fixtures__/ads-report-products.json';
import mcProductStatistics from '../__fixtures__/mc-product-statistics.json';

/**
 * A sync health summary for a store whose changes have all been sent to Google.
 */
const syncHealthSummary = {
	generated_at: 1751036728,
	state: 'synced',
	syncing: { products: 0, full_sync: false, shipping: false },
	reasons: [],
	pillars: {
		connection: { status: 'ok' },
		products: {
			status: 'ok',
			last_push_at: 1751036000,
			full_sync: {
				in_progress: false,
				interrupted: false,
				last_completed_at: 1751000000,
			},
		},
		shipping: { status: 'ok', syncable: false, last_success_at: null },
		review: {
			status: 'ok',
			data_at: 1751036000,
			refresh_at: null,
			loading: false,
		},
	},
	queue: { pending: 0, running: 0, failed_24h: 0, last_completed_at: null },
};

/**
 * Product counts per sync status, as returned by `sync-health/product-journey`.
 *
 * @param {number} approved Products approved by Google.
 * @param {number} [notSynced=137] Products not synced. Kept under 1,000 so
 *   counts read the same in every locale, without a thousands separator.
 * @return {Object} The product-journey response.
 */
export const buildProductJourney = ( approved, notSynced = 137 ) => ( {
	total: approved + notSynced,
	segments: {
		approved,
		pending_sync: 0,
		pending_review: 0,
		disapproved: 0,
		not_synced: notSynced,
	},
} );

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
				status: 'APPROVED',
				issues: [],
				reviewAction: null,
			} ),

			this.fulfillAccountIssuesRequest( {
				issues: [],
				page: 1,
				total: 0,
				loading: false,
			} ),

			this.fulfillAdsReportProducts( adsReportProductsData ),
			this.fulfillProductStatisticsRequest( mcProductStatistics ),

			this.fulfillSyncHealth( syncHealthSummary ),
			this.fulfillProductJourney( buildProductJourney( 0 ) ),
			this.fulfillReviewRefresh( {
				started: true,
				reason: 'started',
				available_at: null,
			} ),
			this.fulfillProductFeed( { products: [], total: 0 } ),

			this.mockJetpackConnected(),
			this.mockGoogleConnected(),
			this.mockAdsAccountConnected(),
		] );
	}

	/**
	 * Get the overview headline saying how many products Google approved.
	 *
	 * @return {import('@playwright/test').Locator} The headline element.
	 */
	getApprovedProductsHeadline() {
		return this.page.getByText(
			/^\d+ of \d+ products? approved by Google$/
		);
	}

	/**
	 * Get the campaign notice section.
	 *
	 * @return {import('@playwright/test').Locator} The campaign notice section.
	 */
	async getCampaignNoticeSection() {
		return this.page.locator( '.gla-ads-inline-notice' ).filter( {
			hasText:
				'You have approved products. Create a Google Ads campaign to reach more customers and drive more sales.',
		} );
	}

	/**
	 * Get the create campaign button in the notice section.
	 *
	 * @return {import('@playwright/test').Locator} The create campaign button.
	 */
	getInNoticeCreateCampaignButton() {
		return this.page.getByRole( 'button', {
			name: 'Create Campaign',
		} );
	}
}
