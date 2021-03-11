/**
 * External dependencies
 */
import { useEffect, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

const useFetchBudgetRecommendationEffect = ( countryCode ) => {
	const options = useMemo( () => {
		return {
			path: `/wc/gla/ads/campaigns/budget-recommendation/${ countryCode }`,
		};
	}, [ countryCode ] );

	const [ fetchBudgetRecommendation, fetchResult ] = useApiFetchCallback(
		options
	);

	useEffect( () => {
		if ( ! countryCode ) {
			return;
		}

		fetchBudgetRecommendation();
	}, [ fetchBudgetRecommendation, countryCode ] );

	return fetchResult;
};

export default useFetchBudgetRecommendationEffect;
