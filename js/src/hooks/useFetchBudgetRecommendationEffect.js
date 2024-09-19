/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchEffect from './useApiFetchEffect';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Fetch the budget recommendation for a country in a side effect.
 *
 * @param {Array<CountryCode>} countryCodes Country code array.
 * @return {Object} Budget recommendation.
 */
const useFetchBudgetRecommendationEffect = ( countryCodes = [] ) => {
	let args;

	// If there are no country codes, undefined will be passed to useApiFetchEffect which won't trigger the fetch.
	if ( countryCodes.length > 0 ) {
		const url = `${ API_NAMESPACE }/ads/campaigns/budget-recommendation`;
		const query = { country_codes: countryCodes };
		const path = addQueryArgs( url, query );

		args = { path };
	}

	return useApiFetchEffect( args );
};

export default useFetchBudgetRecommendationEffect;
