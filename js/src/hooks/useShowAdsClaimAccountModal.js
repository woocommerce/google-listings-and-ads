/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useShowAdsClaimAccountModal = () => {
	return useSelect( ( select ) => {
		return select( STORE_KEY ).getShowAdsClaimAccountModal();
	}, [] );
};

export default useShowAdsClaimAccountModal;
