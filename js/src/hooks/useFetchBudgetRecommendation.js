/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import getHighestBudget from '.~/utils/getHighestBudget';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Fetch the highest budget recommendation for countries in a side effect.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes. If empty, the fetch will not be triggered.
 * @return {Object} Budget recommendation.
 */
const useFetchBudgetRecommendation = ( countryCodes ) => {
	return useSelect(
		( select ) => {
			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			const data = getAdsBudgetRecommendations( countryCodes );
			let highestDailyBudget;
			let highestDailyBudgetCountryCode;

			if ( data ) {
				const { recommendations } = data;
				( {
					daily_budget: highestDailyBudget,
					country: highestDailyBudgetCountryCode,
				} = getHighestBudget( recommendations ) );
			}

			return {
				data,
				highestDailyBudget,
				highestDailyBudgetCountryCode,
				hasFinishedResolution: hasFinishedResolution(
					'getAdsBudgetRecommendations',
					[ countryCodes ]
				),
			};
		},
		[ countryCodes ]
	);
};

export default useFetchBudgetRecommendation;
