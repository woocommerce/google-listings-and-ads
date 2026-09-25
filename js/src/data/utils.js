/**
 * External dependencies
 */
import { format } from '@wordpress/date';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import round from '~/utils/round';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

export const freeFields = [ 'clicks', 'impressions' ];
export const paidFields = [ 'sales', 'conversions', 'spend', ...freeFields ];
/**
 * Reasons why the product feed data may be missing.
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

/*
 * This `replacer` is used with `JSON.stringify` to ensure that an object/array
 * will be stringified to the same result regardless of the values' order when
 * it has the same set of keys and values.
 *
 * For example, the following two inputs will both result in
 * '{"a":"","b":["c","d"]}'
 *
 * - JSON.stringify( { a: '', b: [ 'c', 'd' ] }, replacer );
 * - JSON.stringify( { b: [ 'd', 'c' ], a: '' }, replacer );
 */
function replacer( key, value ) {
	if ( value ) {
		if ( Array.isArray( value ) ) {
			return [ ...value ].sort();
		}
		if ( typeof value === 'object' ) {
			return Object.fromEntries( Object.entries( value ).sort() );
		}
	}
	return value;
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
	const id = JSON.stringify( reportQuery, replacer );
	return `${ category }:${ type }:${ id }`;
}

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
 * Calculate deltas and map indidual ReportField metrics to PerformanceData field.
 *
 * @param {number} [value] The primary report field fetched from report API.
 * @param {number} [base] The secondary report field fetched from report API.
 * @param {MISSING_FREE_LISTINGS_DATA} [missingFreeListingsData] Flag indicating whether the data miss entries from Product Feed.
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
 * Converts an array of strings into a single underscore-separated, lowercase string.
 *
 * @param {string[]} [arr=[]] - The array of strings to convert.
 * @return {string} The underscore-separated, lowercase string.
 */
export const arrayToUnderscoreKey = ( arr = [] ) => {
	return arr
		.map(
			( str ) =>
				String( str )
					.normalize( 'NFKD' )
					.replace( /[\u0300-\u036f]/g, '' ) // remove accents
					.trim()
					.toLowerCase()
					.replace( /[^a-z0-9_ ]/g, '' ) // allow underscores
					.replace( /\s+/g, '_' ) // replace spaces with underscores
					.replace( /_+/g, '_' ) // collapse multiple underscores
		)
		.join( '_' );
};

/**
 * Generates a unique key (slug) from an array of country codes.
 *
 * @param {Array<CountryCode>} [countryCodes] - An array of country code strings.
 * @return {string} An underscore-separated, lowercase string representing the given country codes.
 */
export function getCountryCodesKey( countryCodes = [] ) {
	return arrayToUnderscoreKey( countryCodes );
}

/**
 * Generates a key for ads budget metrics.
 *
 * @param {Array<CountryCode>} countryCodes An array of country code strings.
 * @param {number} budget A daily budget.
 * @return {string} A key for ads budget metrics.
 */
export function getAdsBudgetMetricsKey( countryCodes, budget ) {
	const key = getCountryCodesKey( countryCodes );
	const budgetString = budget.toString().replace( '.', '#' );
	return `${ key }::${ budgetString }`;
}

/**
 * Recursively convert the object's own enumerable keys from snake to camel case.
 *
 * @param {*} data The data to convert.
 * @return {*} The converted data.
 */
export function convertKeysFromSnakeCaseToCamelCase( data ) {
	if ( Array.isArray( data ) ) {
		return data.map( convertKeysFromSnakeCaseToCamelCase );
	}

	if ( Object.prototype.toString.call( data ) !== '[object Object]' ) {
		return data;
	}

	return Object.entries( data ).reduce( ( acc, [ key, value ] ) => {
		const camelKey = key.replace( /(?<=[a-z\d])_([a-z])/g, ( _, letter ) =>
			letter.toUpperCase()
		);
		acc[ camelKey ] = convertKeysFromSnakeCaseToCamelCase( value );
		return acc;
	}, {} );
}

/**
 * Applies character limits to asset texts based on provided specifications.
 *
 * @param {Object<string, string[]>} assets The asset texts to apply character limits to.
 * @param {Array<Object>} specs The specifications defining character limits for each asset type.
 * @return {Object<string, string[]>} The asset texts with character limits applied.
 */
export function applyAssetTextCharacterLimits( assets, specs ) {
	return Object.fromEntries(
		Object.entries( assets ).map( ( [ type, values ] ) => {
			const spec = specs.find( ( s ) => s.key === type );
			if ( ! spec ) {
				return [ type, values ];
			}

			const limits = Array.isArray( spec.maxCharacterCounts )
				? spec.maxCharacterCounts
				: Array.from(
						{ length: values.length },
						() => spec.maxCharacterCounts
				  );

			const ellipsis = '…';

			// Prepare positions with numeric limits (we’ll fill these first).
			const positions = limits
				.map( ( max, index ) =>
					typeof max === 'number' ? { index, max } : null
				)
				.filter( Boolean );

			// Sort positions by max ascending (tightest slots first).
			positions.sort( ( a, b ) => a.max - b.max );

			// Keep texts with their original index so ties preserve original order.
			const texts = values.map( ( text, index ) => ( { text, index } ) );

			// Sort texts by length ascending (shortest first).
			// Tie-breaker keeps original order.
			texts.sort(
				( a, b ) => a.text.length - b.text.length || a.index - b.index
			);

			const out = new Array( values.length );
			const usedTextIndexes = new Set();

			// Assign shortest texts to tightest positions.
			for ( let i = 0; i < positions.length; i++ ) {
				const { index: posIndex, max } = positions[ i ];
				const picked = texts[ i ];

				if ( ! picked ) {
					break;
				}

				usedTextIndexes.add( picked.index );

				if ( picked.text.length <= max ) {
					out[ posIndex ] = picked.text;
				} else {
					const sliceLength = Math.max( max - ellipsis.length, 0 );
					out[ posIndex ] =
						picked.text.slice( 0, sliceLength ) + ellipsis;
				}
			}

			// Fill any remaining slots (including positions without max limits)
			// with remaining texts in original order.
			const remainingTexts = values.filter(
				( _, i ) => ! usedTextIndexes.has( i )
			);

			let r = 0;
			for ( let i = 0; i < out.length; i++ ) {
				if ( out[ i ] === undefined ) {
					out[ i ] = remainingTexts[ r++ ];
				}
			}

			return [ type, out ];
		} )
	);
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
 * @property {MISSING_FREE_LISTINGS_DATA} [missingFreeListingsData] Flag indicating whether the data miss entries from Product Feed.
 */
