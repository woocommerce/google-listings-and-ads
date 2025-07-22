/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useCountryCodesForBudgetQuery from './useCountryCodesForBudgetQuery';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 * @typedef {import('~/data/types.js').AdsBudgetMetrics} AdsBudgetMetrics
 */

/**
 * @typedef {Object} BudgetMetricsPayload
 * @property {AdsBudgetMetrics|null} data The budget metrics data.
 * @property {boolean} hasResolved Whether the data fetching is finished.
 */

const selectorName = 'getAdsBudgetMetrics';

/**
 * Hook to fetch the budget metrics for the given countries and daily budget.
 * If the store country is included in the country codes, it will be moved to
 * the first position in the array as the primary country.
 *
 * @param {Array<CountryCode>} countryCodes An array of country codes.
 * @param {number} budget The daily budget. It expects a positive number.
 * @return {BudgetMetricsPayload} Budget metrics.
 */
export default function useBudgetMetrics( countryCodes, budget ) {
	const resolvedCountryCodes = useCountryCodesForBudgetQuery( countryCodes );

	return useSelect(
		( select ) => {
			if ( resolvedCountryCodes.length === 0 ) {
				return {
					data: null,
					hasResolved: false,
				};
			}

			if ( ! Number.isFinite( budget ) || budget <= 0 ) {
				return {
					data: null,
					hasResolved: true,
				};
			}

			const selector = select( STORE_KEY );
			const args = [ resolvedCountryCodes, budget ];

			return {
				data: selector[ selectorName ]( ...args ),
				hasResolved: selector.hasFinishedResolution(
					selectorName,
					args
				),
			};
		},
		[ resolvedCountryCodes, budget ]
	);
}
