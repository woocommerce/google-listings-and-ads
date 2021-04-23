/**
 * External dependencies
 */

import { format } from '@wordpress/date';
import { getCurrentDates } from '@woocommerce/date';

const freeFields = [ 'clicks', 'impressions' ];
const paidFields = [ 'sales', 'solds', 'conversions', 'spend', ...freeFields ];

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
