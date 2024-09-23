/**
 * Internal dependencies
 */
import useFetchBudgetRecommendationEffect from './useFetchBudgetRecommendationEffect';
import getHighestBudget from '.~/utils/getHighestBudget';
import useAdsCurrency from './useAdsCurrency';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Fetch the highest budget recommendation for countries in a side effect.
 *
 * @param {Array<CountryCode>} countryCodes Country code array.
 * @return {Object} Budget recommendation.
 */
const useHighestBudgetRecommendation = ( countryCodes ) => {
	const { adsCurrencyConfig } = useAdsCurrency();
	const { code: currency } = adsCurrencyConfig;
	const { data: budgetData, loading } =
		useFetchBudgetRecommendationEffect( countryCodes );
	const budget = getHighestBudget( budgetData?.recommendations );

	return {
		dailyBudget: budget?.daily_budget,
		currency,
		loading,
		budgetData,
	};
};

export default useHighestBudgetRecommendation;
