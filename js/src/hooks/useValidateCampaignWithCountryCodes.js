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
 * @property {boolean} hasFinishedResolution A boolean indicating whether the budget recommendation data has been resolved.
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {Array<CountryCode>} [initialCountryCodes] Country code array. If not provided, the validate function will not take into account budget recommendations.
 * @return {ValidateCampaignWithCountryCodesHook} An object containing the `validateCampaignWithCountryCodes` function and a `loading` state.
 */
const useValidateCampaignWithCountryCodes = ( initialCountryCodes ) => {
	const {
		formatAmount,
		adsCurrencyConfig: { code },
	} = useAdsCurrency();
	const [ countryCodes, setCountryCodes ] = useState( [] );

	useEffect( () => {
		setCountryCodes( initialCountryCodes );
	}, [ initialCountryCodes ] );

	return useSelect(
		( select ) => {
			// If no country codes are provided, return the default validateCampaign function.
			if ( ! countryCodes?.length ) {
				return {
					validateCampaignWithCountryCodes: validateCampaign,
					dailyBudget: null,
					formatAmount,
					refreshCountryCodes: setCountryCodes,
					currencyCode: code,
					hasFinishedResolution: true,
				};
			}

			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );
			const budgetData = getAdsBudgetRecommendations( countryCodes );
			const budget = getHighestBudget( budgetData?.recommendations );

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
				} );
			};

			return {
				validateCampaignWithCountryCodes,
				dailyBudget: budget?.daily_budget,
				formatAmount,
				refreshCountryCodes: setCountryCodes,
				currencyCode: code,
				hasFinishedResolution:
					hasFinishedResolution( 'getAdsBudgetRecommendations', [
						countryCodes,
					] ) && code,
			};
		},
		[ countryCodes, formatAmount, code ]
	);
};

export default useValidateCampaignWithCountryCodes;
