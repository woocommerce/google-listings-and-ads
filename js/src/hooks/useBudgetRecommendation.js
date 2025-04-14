/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useStoreCountry from '~/hooks/useStoreCountry';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 * @typedef {import('~/data/types.js').AdsBudgetRecommendation} AdsBudgetRecommendation
 */

/**
 * @typedef {Object} BudgetRecommendationPayload
 * @property {AdsBudgetRecommendation|null} data The budget recommendation data.
 * @property {number|null} recommendedDailyBudget The recommended daily budget. `null` if not yet fetched.
 * @property {boolean} hasFinishedResolution Whether the data fetching is finished.
 */

function resolveCountryCodes( storeCountry, countryCodes ) {
	if ( ! storeCountry || ! countryCodes ) {
		return [];
	}

	const idx = countryCodes.indexOf( storeCountry );

	if ( idx > 0 ) {
		const codes = countryCodes.slice();
		return codes.splice( idx, 1 ).concat( codes );
	}

	return countryCodes;
}

/**
 * A hook to fetch the budget recommendations for the given countries. If the
 * store country is included in the country codes, it will be moved to the
 * first position in the array as the primary country.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes. If empty, the fetch will not be triggered.
 * @return {BudgetRecommendationPayload} Budget recommendation.
 */
const useBudgetRecommendation = ( countryCodes ) => {
	const { code: storeCountry } = useStoreCountry();

	return useSelect(
		( select ) => {
			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			const resolvedCountryCodes = resolveCountryCodes(
				storeCountry,
				countryCodes
			);

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
				hasFinishedResolution: hasResolved,
			};
		},
		[ storeCountry, countryCodes ]
	);
};

export default useBudgetRecommendation;
