/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getAllowEnhancedConversions';

const useAllowEnhancedConversions = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			allowEnhancedConversions: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useAllowEnhancedConversions;
