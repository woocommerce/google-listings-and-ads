/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import {
	paidFields,
	fieldsToPerformance,
	mapReportFieldsToPerformance,
} from '.~/data/utils';
import { getIdsFromQuery } from '../utils';
import useUrlQuery from '.~/hooks/useUrlQuery';
import { FREE_LISTINGS_PROGRAM_ID, REPORT_PROGRAM_PARAM } from '.~/constants';

const category = 'programs';
const emptyData = {
	free_listings: [],
	campaigns: [],
	intervals: [],
	totals: {},
};

/**
 * @typedef { import(".~/data/utils").ReportFieldsSchema } ReportFieldsSchema
 * @typedef { import(".~/data/utils").PerformanceData } PerformanceData
 * @typedef { import("../index.js").ProgramsReportData } ProgramsReportData
 * @typedef { import("../index.js").ProgramsReportSchema } ProgramsReportSchema
 */

/**
 * Get programs report data.
 * Query parameters will be parsed from the URL by this hook.
 *
 * @return {ProgramsReportSchema} The fetched programs report data and its status.
 */
export default function useProgramsReport() {
	const query = useUrlQuery();
	return useSelect(
		( select ) => {
			const { getReport } = select( STORE_KEY );

			const queriedPrograms = getIdsFromQuery(
				query[ REPORT_PROGRAM_PARAM ]
			);
			const containsFree =
				queriedPrograms.length === 0 ||
				queriedPrograms.includes( FREE_LISTINGS_PROGRAM_ID );

			const containsPaid =
				queriedPrograms.length === 0 ||
				queriedPrograms.some(
					( id ) => id !== FREE_LISTINGS_PROGRAM_ID
				);
			if ( containsFree && containsPaid ) {
				return transformReportAggregated( getReport, query );
			} else if ( containsFree && ! containsPaid ) {
				return transfromReportForType( getReport, 'free', query );
			} else if ( ! containsFree && containsPaid ) {
				return transfromReportForType( getReport, 'paid', query );
			}
		},
		[ query ]
	);
}

/**
 * Transforms raw report data given by API,
 * for the current and the previous time,
 * to a single object to be used by UI components.
 *
 * @param  {Function} getReport Report selector.
 * @param  {string} type Data source of program type, 'free' or 'paid'.
 * @param  {Object} query Query parameters in the URL.
 *
 * @return {ProgramsReportSchema} The fetched programs report data and its status.
 */
function transfromReportForType( getReport, type, query ) {
	const primary = getReport( category, type, query, 'primary' );
	const secondary = getReport( category, type, query, 'secondary' );

	const loaded = primary.loaded && secondary.loaded;
	const haveAllData = primary.data && secondary.data;

	let data = emptyData;

	if ( loaded && haveAllData ) {
		data = {
			freeListings: primary.data.free_listings || [],
			campaigns: primary.data.campaigns || [],
			intervals: primary.data.intervals || [],
			totals: mapReportFieldsToPerformance(
				primary.data.totals,
				secondary.data.totals
			),
		};
	}

	const reportQuery = primary.reportQuery;
	return { data, loaded, reportQuery };
}

/**
 * Transforms raw report data given by API, for free & paid programs,
 * for the current and the previous time,
 * to a single object, to be used by UI components.
 *
 * @param  {Function} getReport Report selector.
 * @param  {Object} query Query parameters in the URL.
 *
 * @return {ProgramsReportSchema} The fetched programs report data and its status.
 */
function transformReportAggregated( getReport, query ) {
	// The glue code for summing up two reports could be moved to the serverside,
	// where the data is more accesible.
	// That could improve the UX by reducing the latency of XHR pipeline.
	const free = {
		primary: getReport( category, 'free', query, 'primary' ),
		secondary: getReport( category, 'free', query, 'secondary' ),
	};
	const paid = {
		primary: getReport( category, 'paid', query, 'primary' ),
		secondary: getReport( category, 'paid', query, 'secondary' ),
	};
	const loaded =
		free.primary.loaded &&
		free.secondary.loaded &&
		paid.primary.loaded &&
		paid.secondary.loaded;
	const haveAllData =
		free.primary.data &&
		free.secondary.data &&
		paid.primary.data &&
		paid.secondary.data;

	let data = emptyData;

	if ( loaded && haveAllData ) {
		const aggregatedIntervals = aggregateIntervals(
			free.primary.data.intervals,
			paid.primary.data.intervals
		);

		data = {
			freeListings: free.primary.data.free_listings || [],
			campaigns: paid.primary.data.campaigns || [],
			// We need to combine the intervals here
			// https://github.com/woocommerce/google-listings-and-ads/issues/589#issuecomment-840317729
			// But maybe its better to do it in more general level,
			// as we need that for single type Product reports as well.
			intervals: aggregatedIntervals,
			totals: mapReportFieldsToAggregatesPerformance(
				paid.primary.data.totals,
				paid.secondary.data.totals,
				free.primary.data.totals,
				free.secondary.data.totals
			),
		};
	}

	const reportQuery = paid.primary.reportQuery;
	return { data, loaded, reportQuery };
}

/**
 * Calculate performance data by each metric,
 * sum totals from paid and free programs,
 * state missing free data as `missingFreeListingsData`.
 *
 * `paid`'s keys should be a subset of `free`s keys. and should be same as `paidBase` keys.
 *
 * @param {ReportFieldsSchema} paid The primary report fields fetched for paid campaigns.
 * @param {ReportFieldsSchema} paidBase The secondary report fields fetched for paid campaigns.
 * @param {ReportFieldsSchema} free The primary report fields fetched for free campaigns.
 * @param {ReportFieldsSchema} freeBase The secondary report fields fetched for free campaigns.
 *
 * @return {PerformanceData} The calculated performance data of each metric.
 */
export function mapReportFieldsToAggregatesPerformance(
	paid,
	paidBase,
	free,
	freeBase
) {
	return Object.keys( paid ).reduce(
		( acc, key ) => ( {
			...acc,
			[ key ]: fieldsToPerformance(
				// State the lack of `free*[key]` data as `missingFreeListingsData`,
				// but sum gracefully as `0`.
				Number( paid[ key ] ) + Number( free[ key ] || 0 ),
				Number( paidBase[ key ] ) + Number( freeBase[ key ] || 0 ),
				free[ key ] === undefined || freeBase[ key ] === undefined
			),
		} ),
		{}
	);
}

function aggregateIntervals( intervals1, intervals2 ) {
	// Convert to Map object
	// Consider doing it on serverside
	const intervals1Map = new Map(
		intervals1.map( ( item ) => [ item.interval, item.subtotals ] )
	);
	const intervals2Map = new Map(
		intervals2.map( ( item ) => [ item.interval, item.subtotals ] )
	);
	const allIntervals = [
		...new Set( [ ...intervals1Map.keys(), ...intervals2Map.keys() ] ),
	].sort();
	// We assume subtotals has the consistent schema across all intervals, and the `paidFields` is the superset.
	const result = allIntervals.reduce( ( acc, interval ) => {
		acc.push( {
			interval,
			subtotals: mergeSubtotals(
				paidFields,
				intervals1Map.get( interval ),
				intervals2Map.get( interval )
			),
		} );
		return acc;
	}, [] );
	return result;
}

function mergeSubtotals( metrics, totals1 = {}, totals2 = {} ) {
	return metrics.reduce( ( sum, metric ) => {
		sum[ metric ] = ( totals1[ metric ] || 0 ) + ( totals2[ metric ] || 0 );
		return sum;
	}, {} );
}
