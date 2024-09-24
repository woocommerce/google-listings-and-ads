/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import getHighestBudget from '.~/utils/getHighestBudget';
import useAdsCurrency from './useAdsCurrency';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Fetch the highest budget recommendation for countries in a side effect.
 *
 * @param {Array<CountryCode>} countryCodes An array of country codes. If empty, the dailyBudget will be undefined.
 * @return {Object} An object containing the `dailyBudget` value, `formatAmount` function and a `loading` state.
 * @property {number|null} dailyBudget The highest recommended daily budget. If no recommendations are available, this will be `undefined`.
 * @property {boolean} loading A boolean indicating whether the budget recommendation data is being fetched.
 * @property {Function} formatAmount A function to format the budget amount according to the currency settings.
 */
const useHighestBudgetRecommendation = ( countryCodes ) => {
	const { formatAmount } = useAdsCurrency();

	return useSelect( ( select ) => {
		const { getAdsBudgetRecommendations, isResolving } =
			select( STORE_KEY );

		const budgetData = getAdsBudgetRecommendations( countryCodes );
		const budget = getHighestBudget( budgetData?.recommendations );

		return {
			dailyBudget: budget?.daily_budget,
			formatAmount,
			loading: isResolving( 'getAdsBudgetRecommendations' ),
		};
	} );
};

export default useHighestBudgetRecommendation;
