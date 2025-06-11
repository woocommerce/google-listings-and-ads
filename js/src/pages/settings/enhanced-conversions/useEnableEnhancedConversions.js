/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';

const selectorName = 'getEnableEnhancedConversions';

/**
 * Custom hook to manage the state of enhanced conversions.
 * @return {boolean} Current state of enhanced conversions.
 */
export const useEnableEnhancedConversions = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return selector[ selectorName ]();
	}, [] );
};
