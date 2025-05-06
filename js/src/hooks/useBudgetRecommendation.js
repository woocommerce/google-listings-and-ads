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
 * @typedef {import('~/data/types.js').AdsBudgetRecommendation} AdsBudgetRecommendation
 */

/**
 * @typedef {Object} BudgetRecommendationPayload
 * @property {AdsBudgetRecommendation|null} data The budget recommendation data.
 * @property {number|null} recommendedDailyBudget The recommended daily budget. `null` if not yet fetched.
 * @property {boolean} hasResolved Whether the data fetching is finished.
 */

/**
 * A hook to fetch the budget recommendations for the given countries. If the
 * store country is included in the country codes, it will be moved to the
 * first position in the array as the primary country.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes. If empty, the fetch will not be triggered.
 * @return {BudgetRecommendationPayload} Budget recommendation.
 */
const useBudgetRecommendation = ( countryCodes ) => {
	const resolvedCountryCodes = useCountryCodesForBudgetQuery( countryCodes );

	return useSelect(
		( select ) => {
			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			const data = getAdsBudgetRecommendations( resolvedCountryCodes );
			let recommendedDailyBudget = null;

			if ( data ) {
				recommendedDailyBudget = data.recommended.dailyBudget;
			}

			const hasResolved = resolvedCountryCodes.length
				? hasFinishedResolution( 'getAdsBudgetRecommendations', [
						resolvedCountryCodes,
				  ] )
				: false;

			return {
				data,
				recommendedDailyBudget,
				hasResolved,
			};
		},
		[ resolvedCountryCodes ]
	);
};

export default useBudgetRecommendation;
