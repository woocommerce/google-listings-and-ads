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

/**
 * Case 1 — revenue metrics. Read together from `/wc-analytics/reports/revenue/stats` totals.
 * "Down" when ANY of these is trending down (OR-any).
 */
const REVENUE_FIELDS = [ 'total_sales', 'net_revenue', 'orders_count' ];

/**
 * Case 2 — product metrics. Read from `/wc-analytics/reports/products/stats` totals.
 */
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
 * Determine whether the merchant's selected-period metrics are trending down on the Overview
 * dashboard, and which case applies. Cases are evaluated sequentially and short-circuit on the
 * first match — Case 1 (revenue) first, then Case 2 (products) — using the currently-selected
 * primary vs comparison ranges.
 *
 * Detection is prop-driven: the date picker writes the range into the URL query, wc-admin
 * re-renders the section with a fresh `query`, and this hook re-evaluates. No `useQuery()` needed.
 *
 * While the totals for the evaluated case are still resolving, `hasFinishedResolution` is `false`
 * so the consuming placement can render `null` and never flash before we know it should show.
 *
 * @param {Object} query The URL query params passed from core. Carries the selected range.
 * @param {string} defaultDateRange The merchant default range (`woocommerce_default_date_range`), used as the fallback when the query carries no explicit dates.
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

				const resolved =
					hasFinishedResolution( 'getWCReportStats', primaryArgs ) &&
					hasFinishedResolution( 'getWCReportStats', secondaryArgs );

				const isDown =
					resolved &&
					fields.some( ( field ) => {
						const delta = calculateDelta(
							primaryTotals?.[ field ],
							secondaryTotals?.[ field ]
						);
						return delta !== null && delta < 0;
					} );

				return { hasFinishedResolution: resolved, isDown };
			};

			// Case 1 (revenue). Short-circuits Case 2 when matched — evaluating (and thus
			// fetching) Case 2 only once Case 1 has resolved and is not down.
			const revenue = evaluate( 'revenue', REVENUE_FIELDS );
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
					metricsCase: 'revenue',
				};
			}

			// Case 2 (products).
			const products = evaluate( 'products', PRODUCTS_FIELDS );
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
				metricsCase: products.isDown ? 'products' : null,
			};
		},
		[ query, defaultDateRange ]
	);
}
