/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { appendTimestamp, getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { calculateDelta } from '~/data/utils';

// Report types, also used as the matched-case identifier returned in `metricsCase`.
const REVENUE = 'revenue';
const PRODUCTS = 'products';

/**
 * Revenue fields from `/wc-analytics/reports/revenue/stats` totals.
 * "Down" when ANY of these is trending down (OR-any).
 */
const REVENUE_FIELDS = [ 'total_sales', 'net_revenue', 'orders_count' ];

// Product fields from `/wc-analytics/reports/products/stats` totals.
const PRODUCTS_FIELDS = [ 'items_sold' ];

/**
 * Build the range query for a WooCommerce Analytics stats request from a resolved date range.
 *
 * @param {Object} dateRange A `primary`/`secondary` range from `getCurrentDates()`.
 * @return {Object} Range query params (after, before, interval).
 */
function getStatsQuery( dateRange ) {
	return {
		after: appendTimestamp( dateRange.after, 'start' ),
		before: appendTimestamp( dateRange.before, 'end' ),
		interval: 'day',
	};
}

/**
 * @typedef {Object} ProductRevenueMetricsDown
 * @property {boolean} hasFinishedResolution Whether the totals for both ranges of the evaluated case(s) have resolved.
 * @property {boolean} isDown Whether the merchant's selected-period metrics are trending down.
 * @property {'revenue'|'products'|null} metricsCase Which case matched, or `null` when nothing is down.
 */

/**
 * Determine whether revenue or product metrics are trending down for a given date range, and
 * which case applies. Cases are evaluated sequentially and short-circuit on the first match —
 * revenue first, then products — comparing the primary range against the comparison range.
 *
 * @param {Object} query The URL query params carrying the selected range, e.g. `{ period: 'month', compare: 'previous_period' }` or `{ after: '2025-02-01', before: '2025-02-28', compare: 'previous_period' }`.
 * @param {string} defaultDateRange The default range used as the fallback when the query carries no explicit dates, e.g. `'period=month&compare=previous_period'`.
 * @return {ProductRevenueMetricsDown} Resolution state, whether metrics are down, and the matched case.
 */
export default function useProductRevenueMetricsDown(
	query,
	defaultDateRange
) {
	return useSelect(
		( select ) => {
			const { getWCReportStats, hasFinishedResolution } =
				select( STORE_KEY );

			const { primary, secondary } = getCurrentDates(
				query,
				defaultDateRange
			);
			const primaryQuery = getStatsQuery( primary );
			const secondaryQuery = getStatsQuery( secondary );

			const evaluate = ( reportType, fields ) => {
				const primaryArgs = [ reportType, primaryQuery ];
				const secondaryArgs = [ reportType, secondaryQuery ];

				// Calling the selector is what triggers its resolver (the fetch), so it
				// must run before the resolution check — otherwise the data never loads.
				const primaryTotals = getWCReportStats( ...primaryArgs );
				const secondaryTotals = getWCReportStats( ...secondaryArgs );

				const hasResolved =
					hasFinishedResolution( 'getWCReportStats', primaryArgs ) &&
					hasFinishedResolution( 'getWCReportStats', secondaryArgs );

				const isDown =
					hasResolved &&
					fields.some( ( field ) => {
						const delta = calculateDelta(
							primaryTotals?.[ field ],
							secondaryTotals?.[ field ]
						);
						return delta < 0;
					} );

				return { hasFinishedResolution: hasResolved, isDown };
			};

			// Evaluate revenue first. Short-circuits products when matched — evaluating (and
			// thus fetching) products only once revenue has resolved and is not down.
			const revenue = evaluate( REVENUE, REVENUE_FIELDS );
			if ( ! revenue.hasFinishedResolution ) {
				return {
					hasFinishedResolution: false,
					isDown: false,
					metricsCase: null,
				};
			}

			if ( revenue.isDown ) {
				return {
					hasFinishedResolution: true,
					isDown: true,
					metricsCase: REVENUE,
				};
			}

			// Products.
			const products = evaluate( PRODUCTS, PRODUCTS_FIELDS );
			if ( ! products.hasFinishedResolution ) {
				return {
					hasFinishedResolution: false,
					isDown: false,
					metricsCase: null,
				};
			}

			return {
				hasFinishedResolution: true,
				isDown: products.isDown,
				metricsCase: products.isDown ? PRODUCTS : null,
			};
		},
		[ query, defaultDateRange ]
	);
}
