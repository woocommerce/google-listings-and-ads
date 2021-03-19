/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useGoogleAdsAccountBillingStatus = () => {
	return useSelect( ( select ) => {
		const billingStatus = select(
			STORE_KEY
		).getGoogleAdsAccountBillingStatus();

		return {
			billingStatus,
		};
	} );
};

export default useGoogleAdsAccountBillingStatus;
