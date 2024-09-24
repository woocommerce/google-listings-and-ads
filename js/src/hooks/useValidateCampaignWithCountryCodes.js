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
 * @typedef {Object} ValidateCampaignWithCountryCodesHook
 * @property {(values: CampaignFormValues) => Object} validateCampaignWithCountryCodes A function to validate campaign form values.
 * @property {boolean} hasFinishedResolution A boolean indicating whether the budget recommendation data has been resolved.
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {Array<CountryCode>} [countryCodes] Country code array. If not provided, the validate function will not take into account budget recommendations.
 * @return {ValidateCampaignWithCountryCodesHook} An object containing the `validateCampaignWithCountryCodes` function and a `loading` state.
 */
const useValidateCampaignWithCountryCodes = ( countryCodes ) => {
	const { dailyBudget, formatAmount, hasFinishedResolution } =
		useHighestBudgetRecommendation( countryCodes );

	/**
	 * Validate campaign form. Accepts the form values object and returns errors object.
	 *
	 * @param {CampaignFormValues} values Campaign form values.
	 * @return {Object} An object containing any validation errors. If no errors, the object will be empty.
	 */
	const validateCampaignWithCountryCodes = ( values ) => {
		return validateCampaign( values, {
			dailyBudget,
			formatAmount,
		} );
	};

	return { validateCampaignWithCountryCodes, hasFinishedResolution };
};

export default useValidateCampaignWithCountryCodes;
