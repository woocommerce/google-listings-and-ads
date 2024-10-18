/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import getHighestBudget from '.~/utils/getHighestBudget';
import useGoogleAdsAccount from './useGoogleAdsAccount';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Fetch the highest budget recommendation for countries in a side effect.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes. If empty, the fetch will not be triggered.
 * @return {Object} Budget recommendation.
 */
const useFetchBudgetRecommendation = ( countryCodes ) => {
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasFinishedResolutionAdsAccount,
	} = useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! hasGoogleAdsConnection ) {
				return {
					data: undefined,
					highestDailyBudget: 0,
					highestDailyBudgetCountryCode: undefined,
					hasFinishedResolution: hasFinishedResolutionAdsAccount,
				};
			}

			const { getAdsBudgetRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			const data = getAdsBudgetRecommendations( countryCodes );
			let highestDailyBudget = 0;
			let highestDailyBudgetCountryCode;

			if ( data ) {
				const { recommendations } = data;
				( {
					daily_budget: highestDailyBudget,
					country: highestDailyBudgetCountryCode,
				} = getHighestBudget( recommendations ) );
			}

			return {
				data,
				highestDailyBudget,
				highestDailyBudgetCountryCode,
				hasFinishedResolution: hasFinishedResolution(
					'getAdsBudgetRecommendations',
					[ countryCodes ]
				),
			};
		},
		[
			countryCodes,
			hasGoogleAdsConnection,
			hasFinishedResolutionAdsAccount,
		]
	);
};

export default useFetchBudgetRecommendation;
