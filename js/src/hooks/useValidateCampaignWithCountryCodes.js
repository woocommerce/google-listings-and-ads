/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useAdsCurrency from './useAdsCurrency';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import getHighestBudget from '.~/utils/getHighestBudget';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * @typedef {Object} ValidateCampaignWithCountryCodesHook
 * @property {(values: CampaignFormValues) => Object} validateCampaignWithCountryCodes A function to validate campaign form values.
 * @property {number | undefined} dailyBudget The daily budget recommendation.
 * @property {(number: string | number) => string} formatAmount A function to format an amount according to the user's currency settings.
 * @property {number} precision Number of decimal places after the decimal separator.
 * @property {string} currencyCode The currency code.
 * @property {boolean} loaded A boolean indicating whether the budget recommendation data has been resolved and the code currency available.
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @return {ValidateCampaignWithCountryCodesHook} The validate campaign with country codes hook.
 */
const useValidateCampaignWithCountryCodes = () => {
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const {
		formatAmount,
		adsCurrencyConfig: { code, precision },
	} = useAdsCurrency();

	return useSelect(
		( select ) => {
			// If country codes are yet resolved, return the default validateCampaign function.
			if ( ! countryCodes ) {
				return {
					validateCampaignWithCountryCodes: validateCampaign,
					dailyBudget: null,
					formatAmount,
					currencyCode: code,
					precision,
					loaded: false,
				};
			}

			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );
			const budgetData = getAdsBudgetRecommendations( countryCodes );
			const budget = getHighestBudget( budgetData?.recommendations );
			const loaded =
				hasFinishedResolution( 'getAdsBudgetRecommendations', [
					countryCodes,
				] ) && code;

			/**
			 * Validate campaign form. Accepts the form values object and returns errors object.
			 *
			 * @param {CampaignFormValues} values Campaign form values.
			 * @return {Object} An object containing any validation errors. If no errors, the object will be empty.
			 */
			const validateCampaignWithCountryCodes = ( values ) => {
				return validateCampaign( values, {
					dailyBudget: budget?.daily_budget,
					formatAmount,
					precision,
				} );
			};

			return {
				validateCampaignWithCountryCodes,
				dailyBudget: budget?.daily_budget,
				formatAmount,
				precision,
				currencyCode: code,
				loaded,
			};
		},
		[ countryCodes, formatAmount, code, precision ]
	);
};

export default useValidateCampaignWithCountryCodes;
