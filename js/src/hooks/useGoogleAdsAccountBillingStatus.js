/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getGoogleAdsAccountBillingStatus';

const useGoogleAdsAccountBillingStatus = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			billingStatus: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleAdsAccountBillingStatus;
