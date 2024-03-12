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
		const allowEnhancedConversions =
			select( STORE_KEY ).getAllowEnhancedConversions();
		const isResolving = select( STORE_KEY ).isResolving(
			'getAllowEnhancedConversions'
		);

		return { allowEnhancedConversions, isResolving };
	}, [] );
};

export default useAllowEnhancedConversions;
