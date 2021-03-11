/**
 * Internal dependencies
 */
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';

const useFetchBudgetRecommendationEffect = ( countryCode ) => {
	const options = countryCode && {
		path: `/wc/gla/ads/campaigns/budget-recommendation/${ countryCode }`,
	};

	return useApiFetchEffect( options );
};

export default useFetchBudgetRecommendationEffect;
