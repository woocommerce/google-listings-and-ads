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
 * Although `countryCodes` is optional and defaults to an empty array,
 * it eventually requires a non-empty array of country codes to return valid results.
 * If `countryCodes` is empty, no fetch will be triggered, and undefined will be returned.
 *
 * @param {Array<CountryCode>} [countryCodes=[]] An array of country codes. If empty, the fetch will not be triggered.
 * @return {Object} Budget recommendation, or `undefined` if no valid country codes are provided.
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
