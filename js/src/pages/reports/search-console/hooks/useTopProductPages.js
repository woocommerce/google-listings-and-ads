/**
 * Internal dependencies
 */
import useSearchConsoleReport from './useSearchConsoleReport';

const emptyProducts = [];

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleProductPage} GoogleSearchConsoleProductPage
 */

/**
 * Get the Search Console top product pages of the date range selected in the URL.
 *
 * @return {{
 *   hasFinishedResolution: boolean,
 *   property: string|null,
 *   products: Array<GoogleSearchConsoleProductPage>,
 *   limit: number|null,
 *   days: number
 * }} The report, the connected property and the number of days the date range covers.
 */
const useTopProductPages = () => {
	const { hasFinishedResolution, property, report, days } =
		useSearchConsoleReport( 'getSearchConsoleTopProductPages' );

	return {
		hasFinishedResolution,
		property,
		products: report?.products ?? emptyProducts,
		limit: report?.limit ?? null,
		days,
	};
};

export default useTopProductPages;
