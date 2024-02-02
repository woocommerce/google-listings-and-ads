/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useAllowEnhancedConversions = () => {
	return useSelect( ( select ) => {
		const allowEnhanceConversions =
			select( STORE_KEY ).getAllowEnhanceConversions();
		const isResolving = select( STORE_KEY ).isResolving(
			'getAllowEnhanceConversions'
		);

		return { allowEnhanceConversions, isResolving };
	}, [] );
};

export default useAllowEnhancedConversions;
