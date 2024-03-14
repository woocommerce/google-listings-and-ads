/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';

const selectorName = 'getAcceptedCustomerDataTerms';

const useAcceptedCustomerDataTerms = () => {
	const { hasGoogleAdsConnection, hasFinishedResolution } =
		useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! hasGoogleAdsConnection ) {
				return {
					acceptedCustomerDataTerms: null,
					hasFinishedResolution,
				};
			}

			const selector = select( STORE_KEY );

			return {
				acceptedCustomerDataTerms: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ hasGoogleAdsConnection, hasFinishedResolution ]
	);
};

export default useAcceptedCustomerDataTerms;
