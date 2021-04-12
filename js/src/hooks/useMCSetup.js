/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

/**
 * Get Merchant Center setup info.
 */
const useMCSetup = () => {
	return useSelect( ( select ) => {
		return select( STORE_KEY ).getMCSetup();
	} );
};

export default useMCSetup;
