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
export const paidFields = [ 'sales', 'conversions', 'spend', ...freeFields ];
/**
 * Reasons why the free listings data may be missing.
 *
 * @enum {number}
 */
export const MISSING_FREE_LISTINGS_DATA = Object.freeze( {
	/** 0 - No anticipated data is missing. */
	NONE: 0,
	/** 1 - The data for this metric is not (yet) available in the API and was not even requested. */
	FOR_METRIC: 1,
	/** 2 - The data was requested but Google API failed to return it. */
	FOR_REQUEST: 2,
} );

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
 * @typedef {import('./selectors').ReportQuery} ReportQuery
 */
/**
 * Get report query for fetching report data from API.
 *
 * @param  {string} category Category of report, 'programs' or 'products'.
 * @param  {string} type Type of report, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {ReportQuery} The report query for fetching report data from API.
 */
export function getReportQuery( category, type, query, dateReference ) {
	const baseQuery = getPerformanceQuery( type, query, dateReference );
	const { order = 'desc' } = query;
	let { orderby } = query;
	// Ignore orderby's not supported by fields.
	if ( ! orderby || ! baseQuery.fields.includes( orderby ) ) {
		orderby = baseQuery.fields[ 0 ];
	}

	const reportQuery = {
		...baseQuery,
		interval: 'day',
		orderby,
		order,
	};

	if ( category === 'programs' && query.programs ) {
		reportQuery.ids = query.programs;
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
 * @param  {ReportQuery} reportQuery The query parameters of report API.
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
 * @param {Array<string>} [fields] Array of expected metrics.
 * @return {PerformanceData} The calculated performance data of each metric.
 */
export function mapReportFieldsToPerformance(
	primary = {},
	secondary = {},
	fields
) {
	return ( fields || Object.keys( primary ) ).reduce(
		( acc, key ) => ( {
			...acc,
			[ key ]: fieldsToPerformance(
				primary[ key ],
				secondary[ key ],
				! primary[ key ] || ! secondary[ key ]
					? MISSING_FREE_LISTINGS_DATA.FOR_REQUEST
					: MISSING_FREE_LISTINGS_DATA.NONE
			),
		} ),
		{}
	);
}

/**
 * Calculate deltas and map indidual ReportField metrics to PerformanceData field.
 *
 * @param {number} [value] The primary report field fetched from report API.
 * @param {number} [base] The secondary report field fetched from report API.
 * @param {MISSING_FREE_LISTINGS_DATA} [missingFreeListingsData] Flag indicating whether the data miss entries from Free Listings.
 * @return {PerformanceData} The calculated performance data of each metric.
 */
export const fieldsToPerformance = (
	value,
	base,
	missingFreeListingsData
) => ( {
	value,
	delta: calculateDelta( value, base ),
	prevValue: base,
	missingFreeListingsData,
} );

/**
 * Calculate delta.
 *
 * @param {number} [value] The primary report field fetched from report API.
 * @param {number} [base] The secondary report field fetched from report API.
 * @return {number | null} The delta percentage calculated by the `value` compared to the `base` and then rounded to second decimal.
 *                         `null` if any number is not number type, or the result is not finite.
 */
export function calculateDelta( value, base ) {
	let delta = null;
	if ( typeof value === 'number' && typeof base === 'number' ) {
		delta = 0;
		if ( value !== base ) {
			const percent = ( ( value - base ) / base ) * 100;
			delta = Number.isFinite( percent ) ? round( percent ) : null;
		}
	}

	return delta;
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
 * @property {MISSING_FREE_LISTINGS_DATA} [missingFreeListingsData] Flag indicating whether the data miss entries from Free Listings.
 */
