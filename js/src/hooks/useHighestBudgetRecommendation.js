/**
 * Internal dependencies
 */
import useFetchBudgetRecommendationEffect from './useFetchBudgetRecommendationEffect';
import getHighestBudget from '.~/utils/getHighestBudget';
import useStoreCurrency from './useStoreCurrency';

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
	const { code: currency, formatNumber } = useStoreCurrency();
	const { data: budgetData, loading } =
		useFetchBudgetRecommendationEffect( countryCodes );
	const budget = getHighestBudget( budgetData?.recommendations );

	return {
		dailyBudget: budget?.daily_budget,
		currency,
		formatNumber,
		loading,
	};
};

export default useHighestBudgetRecommendation;
