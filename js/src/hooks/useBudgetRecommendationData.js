/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useFetchBudgetRecommendationEffect from './useFetchBudgetRecommendationEffect';
import getHighestBudget from '.~/utils/getHighestBudget';

const useBudgetRecommendationData = ( countryCodes ) => {
	const { data, loading } =
		useFetchBudgetRecommendationEffect( countryCodes );

	const [ country, setCountry ] = useState( '' );
	const [ dailyBudget, setDailyBudget ] = useState( 0 );
	const [ multipleRecommendations, setMultipleRecommendations ] =
		useState( false );
	const [ recommendations, setRecommendations ] = useState( [] );

	useEffect( () => {
		if ( ! loading ) {
			const {
				country: budgetCountries = '',
				daily_budget: recommendedDailyBudget,
			} = getHighestBudget( data?.recommendations );

			setCountry( budgetCountries );
			setDailyBudget( recommendedDailyBudget );
			setMultipleRecommendations( data?.recommendations.length > 1 );
			setRecommendations( data?.recommendations );
		}
	}, [ data?.recommendations, loading ] );

	return {
		country,
		dailyBudget,
		multipleRecommendations,
		recommendations,
		loading,
	};
};

export default useBudgetRecommendationData;
