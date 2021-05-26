/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import {
	getIdsFromQuery,
	aggregateIntervals,
	sumToPerformance,
	addBaseToPerformance,
} from '../utils';
import useUrlQuery from '.~/hooks/useUrlQuery';
import { FREE_LISTINGS_PROGRAM_ID, REPORT_PROGRAM_PARAM } from '.~/constants';

const category = 'programs';
const emptyData = {
	free_listings: [],
	campaigns: [],
	intervals: [],
};
const emptyTotals = {};
/**
 * @type {import('.~/data/selectors').ReportSchema}
 */
const emptyReport = {
	loaded: true,
	data: {},
	reportQuery: null,
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
	const { paid, free } = useSelect(
		( select ) => {
			const { getReport } = select( STORE_KEY );
			return getReports( getReport, query, 'primary' );
		},
		[ query ]
	);
	const loaded = paid.loaded && free.loaded;
	// Assume paid being a superset.
	const reportQuery = paid.reportQuery || free.reportQuery;

	// Memoize expensive aggregation of intervals, and merging of
	const data = useMemo( () => {
		const freeData = free.data;
		const paidData = paid.data;
		// We return empty data when partials are still being loaded.
		// That saves some computations.
		// Alternatively, we could consider UI where chunks of loaded data are being shown.
		if ( ! loaded || ! paidData || ! freeData ) {
			return emptyData;
		}

		return {
			freeListings: freeData.free_listings || emptyData.free_listings,
			campaigns: paidData.campaigns || emptyData.campaigns,
			intervals: aggregateIntervals(
				paidData.intervals,
				freeData.intervals
			),
		};
	}, [ loaded, paid.data, free.data ] );

	return {
		loaded,
		reportQuery,
		data,
	};
}

/**
 * Get programs report totals, from the previous period.
 *
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {{loaded: boolean, data: PerformanceData}} Loaded flag, and eventually the fetched data.
 */
function useTotals( dateReference ) {
	const query = useUrlQuery();
	const { paid, free, expectBoth } = useSelect(
		( select ) => {
			const { getReport } = select( STORE_KEY );
			return getReports( getReport, query, dateReference );
		},
		[ query, dateReference ]
	);
	const loaded = paid.loaded && free.loaded;

	const data = useMemo( () => {
		const freeData = free.data;
		const paidData = paid.data;
		// We return empty data when partials are still being loaded.
		// That saves some computations.
		// Alternatively, we could consider UI where chunks of loaded data are being shown.
		if ( ! loaded || ! paidData || ! freeData ) {
			return emptyTotals;
		}

		return sumToPerformance( paidData.totals, freeData.totals, expectBoth );
	}, [ loaded, paid.data, free.data, expectBoth ] );

	return {
		loaded,
		data,
	};
}

export function usePerformanceReport() {
	const primary = useTotals( 'primary' );
	const secondary = useTotals( 'secondary' );

	const data =
		primary.loaded && secondary.loaded
			? addBaseToPerformance( primary.data, secondary.data )
			: primary.data;

	return {
		// Only consider primary for displaying the main performance as soon as possible.
		loaded: primary.loaded,
		data,
	};
}

/**
 * Gets `free` or `paid` report, or both.
 * Fetches according to queried params.
 * Returns uniform structure of `{free, paid}` reports, with dummy ones if applicable.
 *
 * @param  {Function} getReport Report selector.
 * @param  {Object} query Query parameters in the URL.
 * @param  {string} dateReference Which date range to use, 'primary' or 'secondary'.
 *
 * @return {{free: ProgramsReportSchema, paid: ProgramsReportSchema, expectBoth: boolean}} The fetched programs reports, and a flag whether both were expected..
 */
function getReports( getReport, query, dateReference ) {
	const queriedPrograms = getIdsFromQuery( query[ REPORT_PROGRAM_PARAM ] );
	const containsFree =
		queriedPrograms.length === 0 ||
		queriedPrograms.includes( FREE_LISTINGS_PROGRAM_ID );

	const containsPaid =
		queriedPrograms.length === 0 ||
		queriedPrograms.some( ( id ) => id !== FREE_LISTINGS_PROGRAM_ID );

	const result = {
		free:
			( containsFree &&
				getReport( category, 'free', query, dateReference ) ) ||
			emptyReport,
		paid:
			( containsPaid &&
				getReport( category, 'paid', query, dateReference ) ) ||
			emptyReport,
		expectBoth: containsFree && containsFree,
	};
	return result;
}
