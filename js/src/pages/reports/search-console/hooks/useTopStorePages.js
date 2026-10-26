/**
 * Internal dependencies
 */
import useSearchConsoleReport from './useSearchConsoleReport';

const emptyPages = [];

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleStorePage} GoogleSearchConsoleStorePage
 */

/**
 * Get the Search Console top store pages of the date range selected in the URL.
 *
 * @return {{
 *   hasFinishedResolution: boolean,
 *   property: string|null,
 *   pages: Array<GoogleSearchConsoleStorePage>,
 *   limit: number|null,
 *   days: number
 * }} The report, the connected property and the number of days the date range covers.
 */
const useTopStorePages = () => {
	const { hasFinishedResolution, property, report, days } =
		useSearchConsoleReport( 'getSearchConsoleTopStorePages' );

	return {
		hasFinishedResolution,
		property,
		pages: report?.pages ?? emptyPages,
		limit: report?.limit ?? null,
		days,
	};
};

export default useTopStorePages;
