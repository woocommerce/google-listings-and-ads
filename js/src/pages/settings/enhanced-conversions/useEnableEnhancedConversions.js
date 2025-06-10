/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';

/**
 * Custom hook to manage the state of enhanced conversions.
 * @return {boolean} An array containing the current state and a function to toggle it.
 */
export const useEnableEnhancedConversions = () => {
	return useSelect( ( select ) => {
		const { getEnableEnhancedConversions } = select( STORE_KEY );
		return getEnableEnhancedConversions();
	}, [] );
};
