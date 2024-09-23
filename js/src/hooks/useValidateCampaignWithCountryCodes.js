/**
 * Internal dependencies
 */
import useHighestBudgetRecommendation from './useHighestBudgetRecommendation';
import validateCampaign from '.~/components/paid-ads/validateCampaign';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {Array<CountryCode>} countryCodes Country code array. If not provided, the validate function will not take into account budget recommendations.
 * @return {Object} An object containing the `validateCampaignWithCountryCodes` function and a `loading` state.
 * @property {Function} validateCampaignWithCountryCodes A function to validate campaign form values.
 * @property {boolean} loading A boolean indicating whether the budget recommendation data is being fetched.
 */
const useValidateCampaignWithCountryCodes = ( countryCodes ) => {
	const { dailyBudget, formatAmount, loading } =
		useHighestBudgetRecommendation( countryCodes );

	/**
	 * Validate campaign form. Accepts the form values object and returns errors object.
	 *
	 * @param {CampaignFormValues} values Campaign form values.
	 * @return {Object} errors.
	 */
	const validateCampaignWithCountryCodes = ( values ) => {
		return validateCampaign( values, {
			dailyBudget,
			formatAmount,
		} );
	};

	return { validateCampaignWithCountryCodes, loading };
};

export default useValidateCampaignWithCountryCodes;
