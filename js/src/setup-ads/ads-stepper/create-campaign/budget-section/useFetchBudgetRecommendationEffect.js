/**
 * Internal dependencies
 */
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';

/**
 * Fetch the budget recommendation for a country in a side effect.
 *
 * @param {string} countryCode Country code string, e.g. 'US'.
 * @return {Object} Budget recommendation.
 */
const useFetchBudgetRecommendationEffect = ( countryCode ) => {
	const options = countryCode && {
		path: `/wc/gla/ads/campaigns/budget-recommendation/${ countryCode }`,
	};

	return useApiFetchEffect( options );
};

export default useFetchBudgetRecommendationEffect;
