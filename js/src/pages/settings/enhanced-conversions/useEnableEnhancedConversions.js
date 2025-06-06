/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY, useAppDispatch } from '~/data';

/**
 * Custom hook to manage the state of enhanced conversions.
 * @return {Array} An array containing the current state and a function to toggle it.
 */
export const useEnableEnhancedConversions = () => {
	const isEnabled = useSelect( ( select ) => {
		const { getEnableEnhancedConversions } = select( STORE_KEY );
		return getEnableEnhancedConversions();
	} );

	const { updateEnhancedConversionsStatus } = useAppDispatch();

	const toggleEnhancedConversions = useCallback( async () => {
		await updateEnhancedConversionsStatus( ! isEnabled );
	}, [ updateEnhancedConversionsStatus, isEnabled ] );

	return [ isEnabled, toggleEnhancedConversions ];
};
