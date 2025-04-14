/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import getHighestBudget from '~/utils/getHighestBudget';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

/**
 * A hook to fetch the budget recommendations and the highest recommendation therein
 * for the given countries.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes. If empty, the fetch will not be triggered.
 * @return {Object} Budget recommendation.
 */
const useBudgetRecommendation = ( countryCodes ) => {
	return useSelect(
		( select ) => {
			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			const data = getAdsBudgetRecommendations( countryCodes );
			let highestDailyBudget = 0;

			if ( data ) {
				const { recommendations } = data;
				( { daily_budget: highestDailyBudget } =
					getHighestBudget( recommendations ) );
			}

			return {
				data,
				highestDailyBudget,
				hasFinishedResolution: hasFinishedResolution(
					'getAdsBudgetRecommendations',
					[ countryCodes ]
				),
			};
		},
		[ countryCodes ]
	);
};

export default useBudgetRecommendation;
