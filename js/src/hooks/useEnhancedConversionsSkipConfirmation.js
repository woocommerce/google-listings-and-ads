/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getEnhancedConversionsSkipConfirmation';

const useEnhancedConversionsSkipConfirmation = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			skipConfirmation: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useEnhancedConversionsSkipConfirmation;
