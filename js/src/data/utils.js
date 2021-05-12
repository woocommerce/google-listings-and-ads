/**
 * External dependencies
 */
import { format } from '@wordpress/date';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import round from '.~/utils/round';

export const freeFields = [ 'clicks', 'impressions' ];
const paidFields = [ 'sales', 'conversions', 'spend', ...freeFields ];

/**
 * Get report query for fetching performance data from API.
 *
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {Object} The report query for fetching performance data from API.
 */
export function getPerformanceQuery( type, query, dateReference ) {
	const datesQuery = getCurrentDates( query );
	const after = format( 'Y-m-d', datesQuery[ dateReference ].after );
	const before = format( 'Y-m-d', datesQuery[ dateReference ].before );
	const fields = type === 'free' ? freeFields : paidFields;

	return {
		after,
		before,
		fields,
	};
}

/**
 * Get report query for fetching report data from API.
 *
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {Object} The report query for fetching report data from API.
 */
export function getReportQuery( category, type, query, dateReference ) {
	const baseQuery = getPerformanceQuery( type, query, dateReference );
	const { orderby = baseQuery.fields[ 0 ], order = 'desc' } = query;

	const reportQuery = {
		...baseQuery,
		interval: 'day',
		orderby,
		order,
	};

	if ( category === 'programs' ) {
		// TODO: append `ids` for filtering by programs
	} else if ( category === 'products' && query.products ) {
		reportQuery.ids = query.products.replace( /\d+/g, 'gla_$&' );
	}

	return reportQuery;
}

/**
 * Get a key for accessing report data from store state.
 *
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} reportQuery The query parameters of report API.
 *
 * @return {string} The report key.
 */
export function getReportKey( category, type, reportQuery ) {
	const id = JSON.stringify( reportQuery, Object.keys( reportQuery ).sort() );
	return `${ category }:${ type }:${ id }`;
}

/**
 * Calculate performance data by each metric.
 *
 * @param {ReportFieldsSchema} primary The primary report fields fetched from report API.
 * @param {ReportFieldsSchema} secondary The secondary report fields fetched from report API.
 * @return {PerformanceData} The calculated performance data of each metric.
 */
export function mapReportFieldsToPerformance( primary, secondary ) {
	return Object.keys( primary ).reduce( ( acc, key ) => {
		const value = primary[ key ];
		const base = secondary[ key ];
		let delta = 0;

		if ( value !== base ) {
			const percent = ( ( value - base ) / base ) * 100;
			delta = Number.isFinite( percent ) ? round( percent ) : null;
		}

		return {
			...acc,
			[ key ]: { value, delta, prevValue: base },
		};
	}, {} );
}

/**
 * Report fields fetched from report API.
 *
 * @typedef {Object} ReportFieldsSchema
 * @property {number} clicks Clicks value.
 * @property {number} impressions Impressions value.
 * @property {number} [sales] Sales value. Available for paid type.
 * @property {number} [conversions] Conversions value. Available for paid type.
 * @property {number} [spend] Spend value. Available for paid type.
 */

/**
 * Performance data of each metric.
 *
 * @typedef {Object} PerformanceData
 * @property {PerformanceMetrics} clicks Clicks performance.
 * @property {PerformanceMetrics} impressions Impressions performance.
 * @property {PerformanceMetrics} [sales] Sales performance. Available for paid type.
 * @property {PerformanceMetrics} [conversions] Conversions performance. Available for paid type.
 * @property {PerformanceMetrics} [spend] Spend performance. Available for paid type.
 */

/**
 * Performance metrics.
 *
 * @typedef {Object} PerformanceMetrics
 * @property {number} value Value of the current period.
 * @property {number} prevValue Value of the previous period.
 * @property {number} delta The delta of the current value compared to the previous value.
 * @property {boolean} [missingFreeListingsData] Flag indicating whether the data miss entries from Free Listings.
 */
