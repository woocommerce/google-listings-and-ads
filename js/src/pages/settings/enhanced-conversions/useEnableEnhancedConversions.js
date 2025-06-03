/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

const selectorName = 'getEnableEnhancedConversions';

/**
 * Custom hook to manage the state of enhanced conversions.
 * @return {Array} An array containing the current state and a function to toggle it.
 */
export const useEnableEnhancedConversions = () => {
	const { data, isResolving } = useAppSelectDispatch( selectorName );

	const { updateEnhancedConversionsStatus } = useAppDispatch();
	const isEnabled = data;

	const toggleEnhancedConversions = useCallback( async () => {
		await updateEnhancedConversionsStatus( ! isEnabled );
	}, [ updateEnhancedConversionsStatus, isEnabled ] );

	return [ isEnabled, isResolving, toggleEnhancedConversions ];
};
