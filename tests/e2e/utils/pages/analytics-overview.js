/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';
import stats from '../__fixtures__/analytics-overview-stats.json';

/**
 * Primary (selected) range used to drive the metrics-down check deterministically.
 * Tests navigate with these explicit custom dates so the report mocks can tell the
 * primary range from the comparison range by the request's `after` param.
 */
export const PRIMARY_AFTER = '2025-02-01';
export const PRIMARY_BEFORE = '2025-02-28';

/**
 * A second selected range whose metrics are up, used to assert the live show/hide
 * behaviour when the merchant switches the Analytics date range.
 */
export const UP_RANGE_AFTER = '2025-05-01';
export const UP_RANGE_BEFORE = '2025-05-31';

/**
 * Matched metrics cases returned by `useProductRevenueMetricsDown`.
 */
export const METRICS_CASE = {
	REVENUE: 'revenue',
	PRODUCTS: 'products',
};

const REPORT_STATS_URLS = {
	revenue: /\/wc-analytics\/reports\/revenue\/stats/,
	products: /\/wc-analytics\/reports\/products\/stats/,
};

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
	 * Go to the Analytics Overview page, optionally with an explicit custom date range.
	 *
	 * @param {Object} [range] Optional explicit range. When omitted, the store default range is used.
	 * @param {string} [range.after]   Inclusive start (Y-m-d) of the selected period.
	 * @param {string} [range.before]  Inclusive end (Y-m-d) of the selected period.
	 * @param {string} [range.compare] Comparison mode, e.g. `'previous_period'` or `'previous_year'`.
	 * @return {Promise<void>}
	 */
	async goto( range = {} ) {
		const { after, before, compare = 'previous_period' } = range;

		let path =
			'/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview';

		if ( after && before ) {
			path +=
				`&period=custom&compare=${ compare }` +
				`&after=${ after }&before=${ before }`;
		}

		await this.page.goto( path, {
			waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
		} );
	}

	/**
	 * Wait for the Analytics Overview page to be interactive before asserting on the promo. The
	 * date filter bar is a stable marker that the report page has rendered, so a "not shown"
	 * assertion cannot pass merely because the page has not finished loading.
	 *
	 * @return {Promise<void>}
	 */
	async waitForOverviewReady() {
		await this.page.waitForSelector( '.woocommerce-filters', {
			state: 'visible',
		} );
	}

	/**
	 * Get the Analytics Overview promo section registered by this plugin.
	 *
	 * @return {import('@playwright/test').Locator} The Analytics Overview promo section.
	 */
	getAnalyticsOverviewPromoSection() {
		return this.page.locator( '.gla-analytics-overview-promo' );
	}

	/**
	 * Card headline (heading) of the promo.
	 *
	 * @return {import('@playwright/test').Locator} The heading element.
	 */
	getCardHeadline() {
		return this.getAnalyticsOverviewPromoSection().getByRole( 'heading' );
	}

	/**
	 * Primary CTA link of the promo ("Get started" or "Launch a campaign").
	 *
	 * @return {import('@playwright/test').Locator} The CTA link element.
	 */
	getCtaButton() {
		return this.getAnalyticsOverviewPromoSection().getByRole( 'link' );
	}

	/**
	 * Dismiss control of the promo.
	 *
	 * @return {import('@playwright/test').Locator} The dismiss button element.
	 */
	getDismissButton() {
		return this.getAnalyticsOverviewPromoSection().getByRole( 'button', {
			name: 'Dismiss',
		} );
	}

	/**
	 * Mock a WooCommerce Analytics stats endpoint, returning different totals for the
	 * primary (selected) range vs. the comparison range. The primary range is identified
	 * by the request's `after` param starting with `primaryAfter`.
	 *
	 * @param {'revenue'|'products'} reportType      Report type.
	 * @param {Object}               options
	 * @param {string}               options.primaryAfter    `after` (Y-m-d) that identifies the primary range.
	 * @param {Object|null}          options.primaryTotals    `totals` returned for the primary range.
	 * @param {Object|null}          options.secondaryTotals  `totals` returned for the comparison range.
	 * @return {Promise<void>}
	 */
	async mockReportStats(
		reportType,
		{ primaryAfter, primaryTotals, secondaryTotals }
	) {
		await this.page.route(
			REPORT_STATS_URLS[ reportType ],
			async ( route ) => {
				const after =
					new URL( route.request().url() ).searchParams.get(
						'after'
					) || '';
				const totals = after.startsWith( primaryAfter )
					? primaryTotals
					: secondaryTotals;

				await route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( { totals, intervals: [] } ),
				} );
			}
		);
	}

	/**
	 * Remove the report stats route stubs so they do not leak into other scenarios.
	 *
	 * @return {Promise<void>}
	 */
	async clearReportStats() {
		await this.page.unroute( REPORT_STATS_URLS.revenue );
		await this.page.unroute( REPORT_STATS_URLS.products );
	}

	/**
	 * Force the selected period to read as "down" for the given case, evaluated against the
	 * comparison range. Revenue is checked first, then products, so a products-down scenario
	 * also has to report revenue as up.
	 *
	 * @param {'revenue'|'products'} metricsCase Which case should match.
	 * @param {string}               [primaryAfter=PRIMARY_AFTER] `after` identifying the primary range.
	 * @return {Promise<void>}
	 */
	async mockMetricsDown( metricsCase, primaryAfter = PRIMARY_AFTER ) {
		const revenueDown = metricsCase === METRICS_CASE.REVENUE;

		await this.mockReportStats( 'revenue', {
			primaryAfter,
			primaryTotals: revenueDown ? stats.revenue.down : stats.revenue.up,
			secondaryTotals: stats.revenue.secondary,
		} );

		await this.mockReportStats( 'products', {
			primaryAfter,
			primaryTotals:
				metricsCase === METRICS_CASE.PRODUCTS
					? stats.products.down
					: stats.products.up,
			secondaryTotals: stats.products.secondary,
		} );
	}

	/**
	 * Force the selected period to read as "up" (both revenue and products), so the card hides.
	 *
	 * @param {string} [primaryAfter=UP_RANGE_AFTER] `after` identifying the primary range.
	 * @return {Promise<void>}
	 */
	async mockMetricsUp( primaryAfter = UP_RANGE_AFTER ) {
		await this.mockReportStats( 'revenue', {
			primaryAfter,
			primaryTotals: stats.revenue.up,
			secondaryTotals: stats.revenue.secondary,
		} );

		await this.mockReportStats( 'products', {
			primaryAfter,
			primaryTotals: stats.products.up,
			secondaryTotals: stats.products.secondary,
		} );
	}

	/**
	 * Force the selected period to have no comparison data (the comparison totals are null),
	 * so the decline cannot be computed and the card stays hidden.
	 *
	 * @param {string} [primaryAfter=PRIMARY_AFTER] `after` identifying the primary range.
	 * @return {Promise<void>}
	 */
	async mockNoComparisonData( primaryAfter = PRIMARY_AFTER ) {
		await this.mockReportStats( 'revenue', {
			primaryAfter,
			primaryTotals: stats.revenue.down,
			secondaryTotals: null,
		} );

		await this.mockReportStats( 'products', {
			primaryAfter,
			primaryTotals: stats.products.down,
			secondaryTotals: null,
		} );
	}

	/**
	 * Mock the paid-programs report so the recent-ad-spend check reads the given spend.
	 * A spend greater than zero represents a campaign running in the last 14 days.
	 *
	 * @param {number} spend Ad spend total to report.
	 * @return {Promise<void>}
	 */
	async mockAdSpend( spend ) {
		await this.fulfillAdsReportProgram( { totals: { spend } } );
	}

	/**
	 * Merchant state: onboarded/connected to G4W with an account still in the `incomplete`
	 * status, which counts as ready for the placement. Resolves the "Launch a campaign" variant.
	 *
	 * @return {Promise<void>}
	 */
	async mockConnectedIncomplete() {
		await this.mockMCConnected();
		await this.mockAdsAccountIncomplete();
		await this.mockAdSpend( 0 );
	}

	/**
	 * Reset the durable-dismissal preference so the card is not dismissed at load.
	 *
	 * @return {Promise<void>}
	 */
	async mockNotDismissed() {
		await this.fulfillUsersPreferences( {} );
	}

	/**
	 * Install an in-page spy over `window.wcTracks.recordEvent`, capturing GLA tracking
	 * events into `window.__glaTrackedEvents` for assertion. Runs before every navigation.
	 *
	 * @return {Promise<void>}
	 */
	async installTracksSpy() {
		await this.page.addInitScript( () => {
			window.__glaTrackedEvents = [];
			const record = ( name, props ) => {
				window.__glaTrackedEvents.push( { name, props } );
			};
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.isEnabled = true;
			window.wcTracks.recordEvent = record;
		} );
	}

	/**
	 * Switch the Analytics date range in place through WooCommerce's own client-side navigation,
	 * the same path the report controls use, so the SPA re-renders without a full page reload.
	 *
	 * @param {Object} range
	 * @param {string} range.after    Inclusive start (Y-m-d) of the new selected period.
	 * @param {string} range.before   Inclusive end (Y-m-d) of the new selected period.
	 * @param {string} [range.compare] Comparison mode.
	 * @return {Promise<void>}
	 */
	async switchDateRangeInPlace( {
		after,
		before,
		compare = 'previous_period',
	} ) {
		await this.page.evaluate(
			( args ) => {
				const navigation = window.wc && window.wc.navigation;
				const path = navigation.getNewPath(
					{
						period: 'custom',
						compare: args.compare,
						after: args.after,
						before: args.before,
					},
					'/analytics/overview',
					{}
				);
				navigation.getHistory().push( path );
			},
			{ after, before, compare }
		);
	}

	/**
	 * Read the tracking events captured by the Tracks spy, optionally filtered by name.
	 *
	 * @param {string} [name] Event name to filter by.
	 * @return {Promise<Array<{name: string, props: Object}>>} The captured tracking events.
	 */
	async getTrackedEvents( name ) {
		const events = await this.page.evaluate(
			() => window.__glaTrackedEvents || []
		);
		return name
			? events.filter( ( event ) => event.name === name )
			: events;
	}
}
