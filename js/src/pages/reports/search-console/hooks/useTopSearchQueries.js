/**
 * Internal dependencies
 */
import useSearchConsoleReport from './useSearchConsoleReport';

const emptyQueries = [];

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleQuery} GoogleSearchConsoleQuery
 */

/**
 * Get the Search Console top search queries of the date range selected in the URL.
 *
 * @return {{
 *   hasFinishedResolution: boolean,
 *   property: string|null,
 *   queries: Array<GoogleSearchConsoleQuery>,
 *   limit: number|null,
 *   days: number
 * }} The top queries, the report limit, the connected property and the number of days the date range covers.
 */
const useTopSearchQueries = () => {
	const { hasFinishedResolution, property, report, days } =
		useSearchConsoleReport( 'getSearchConsoleQueriesReport' );

	return {
		hasFinishedResolution,
		property,
		queries: report?.queries ?? emptyQueries,
		limit: report?.limit ?? null,
		days,
	};
};

export default useTopSearchQueries;
