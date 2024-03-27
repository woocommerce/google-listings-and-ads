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
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! googleAdsAccount || ! googleAdsAccount.id ) {
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
		[ googleAdsAccount, hasFinishedResolution ]
	);
};

export default useAcceptedCustomerDataTerms;
