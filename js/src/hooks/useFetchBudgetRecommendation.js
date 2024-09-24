/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

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
			const { getAdsBudgetRecommendations } = select( STORE_KEY );

			const data = getAdsBudgetRecommendations( countryCodes );
			return {
				data,
			};
		},
		[ countryCodes ]
	);
};

export default useFetchBudgetRecommendation;
