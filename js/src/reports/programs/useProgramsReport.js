/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import {
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
 * @typedef { import("../index.js").ReportSchema<ProgramsReportData> } ProgramsReportSchema
 */

/**
 * Get programs report data.
 * Query parameters will be parsed from the URL by this hook.
 *
 * @return {ProgramsReportSchema} The fetched products report data and its status.
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
 * @return {ProgramsReportSchema} The fetched products report data and its status.
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

	return { data, loaded };
}

/**
 * Transforms raw report data given by API, for free & paid programs,
 * for the current and the previous time,
 * to a single object, to be used by UI components.
 *
 * @param  {Function} getReport Report selector.
 * @param  {Object} query Query parameters in the URL.
 *
 * @return {ProgramsReportSchema} The fetched products report data and its status.
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
		data = {
			freeListings: free.primary.data.free_listings || [],
			campaigns: paid.primary.data.campaigns || [],
			// We need to combine the intervals here
			// https://github.com/woocommerce/google-listings-and-ads/issues/589#issuecomment-840317729
			// But maybe its better to do it in more general level,
			// as we need that for single type Product reports as well.
			intervals: [
				...( free.primary.data.intervals || [] ),
				...( paid.primary.data.intervals || [] ),
			],
			totals: mapReportFieldsToAggregatesPerformance(
				paid.primary.data.totals,
				free.primary.data.totals,
				paid.secondary.data.totals,
				free.secondary.data.totals
			),
		};
	}

	return { data, loaded };
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
				// State the lack of data as `missingFreeListingsData`,
				// but sum gracefully as `0`.
				Number( paid[ key ] ) + Number( free[ key ] ),
				Number( paidBase[ key ] ) + Number( freeBase[ key ] ),
				free[ key ] === undefined || freeBase[ key ] === undefined
			),
		} ),
		{}
	);
}
