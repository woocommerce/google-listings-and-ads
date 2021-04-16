/**
 * External dependencies
 */

import { format } from '@wordpress/date';
import { getCurrentDates } from '@woocommerce/date';

function isObject( value ) {
	return Object.prototype.toString.call( value ) === '[object Object]';
}

function stringifyAny( any ) {
	if ( Array.isArray( any ) ) {
		const out = any.map( stringifyAny ).join( ',' );
		return `[${ out }]`;
	}

	if ( isObject( any ) ) {
		const out = Object.keys( any )
			.sort()
			.map( ( key ) => `${ key }:${ stringifyAny( any[ key ] ) }` )
			.join( ',' );
		return `{${ out }}`;
	}

	return JSON.stringify( any );
}

export function getErrorKey( selectorName, args = [] ) {
	const key = stringifyAny( args );
	return `${ selectorName }::${ key }`;
}

/**
 * Get report query for fetching API data.
 *
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {Object} The report query for fetching API data.
 */
export function getReportQuery( query, dateReference ) {
	// TODO: add more query parameters when implementing the report page.
	// ref: https://github.com/woocommerce/google-listings-and-ads/pull/251
	const datesQuery = getCurrentDates( query );
	const after = format( 'Y-m-d', datesQuery[ dateReference ].after );
	const before = format( 'Y-m-d', datesQuery[ dateReference ].before );
	return {
		after,
		before,
		fields: [ 'sales', 'spend', 'clicks', 'impressions' ],
	};
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
