/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useAdsCurrency from './useAdsCurrency';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import getHighestBudget from '.~/utils/getHighestBudget';

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
 * @property {(countryCodes: Array<CountryCode>) => void} refreshCountryCodes A function to refresh the country codes.
 * @property {string} currencyCode The currency code.
 * @property {boolean} loaded A boolean indicating whether the budget recommendation data has been resolved and the code currency available.
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {Array<CountryCode>} [initialCountryCodes] Country code array. If not provided, the validate function will not take into account budget recommendations.
 * @return {ValidateCampaignWithCountryCodesHook} The validate campaign with country codes hook.
 */
const useValidateCampaignWithCountryCodes = ( initialCountryCodes ) => {
	const {
		formatAmount,
		adsCurrencyConfig: { code, precision },
	} = useAdsCurrency();
	const [ countryCodes, setCountryCodes ] = useState( [] );

	useEffect( () => {
		if ( initialCountryCodes ) {
			setCountryCodes( initialCountryCodes );
		}
	}, [ initialCountryCodes ] );

	return useSelect(
		( select ) => {
			// If no country codes are provided, return the default validateCampaign function.
			// countryCodes is initially empty when being set in state, hence the check for initialCountryCodes as well.
			if ( ! countryCodes.length && ! initialCountryCodes?.length ) {
				return {
					validateCampaignWithCountryCodes: validateCampaign,
					dailyBudget: null,
					formatAmount,
					refreshCountryCodes: setCountryCodes,
					currencyCode: code,
					precision,
					loaded: true,
				};
			}

			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );
			const budgetData = getAdsBudgetRecommendations( countryCodes );
			const budget = getHighestBudget( budgetData?.recommendations );
			const loaded =
				hasFinishedResolution( 'getAdsBudgetRecommendations', [
					countryCodes,
				] ) &&
				code &&
				countryCodes.length; // make sure the country codes are set in state before considering the data loaded

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
				refreshCountryCodes: setCountryCodes,
				currencyCode: code,
				loaded,
			};
		},
		[ countryCodes, formatAmount, code, initialCountryCodes, precision ]
	);
};

export default useValidateCampaignWithCountryCodes;
