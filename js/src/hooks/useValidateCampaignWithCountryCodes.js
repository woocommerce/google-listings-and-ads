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
 * @param {Array<CountryCode>} countryCodes Country code array.
 * @return {Object} errors.
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
