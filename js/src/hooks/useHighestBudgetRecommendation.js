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
 * @typedef {Object} HighestBudgetRecommendationHook
 * @property {number|undefined} dailyBudget The highest recommended daily budget. If no recommendations are available, this will be `undefined`.
 * @property {boolean} hasFinishedResolution A boolean indicating whether the budget recommendation data has been fetched.
 * @property {(amount: number) => string} formatAmount A function to format the budget amount according to the currency settings.
 */

/**
 * Fetch the highest budget recommendation for countries in a side effect.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes. If empty, the dailyBudget will be undefined.
 * @return {HighestBudgetRecommendationHook} An object containing the `dailyBudget` value, `formatAmount` function and a `hasFinishedResolution` state.
 */
const useHighestBudgetRecommendation = ( countryCodes ) => {
	const { formatAmount } = useAdsCurrency();

	return useSelect(
		( select ) => {
			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			const budgetData = getAdsBudgetRecommendations( countryCodes );
			const budget = getHighestBudget( budgetData?.recommendations );

			return {
				dailyBudget: budget?.daily_budget,
				formatAmount,
				hasFinishedResolution: hasFinishedResolution(
					'getAdsBudgetRecommendations'
				),
			};
		},
		[ countryCodes, formatAmount ]
	);
};

export default useHighestBudgetRecommendation;
