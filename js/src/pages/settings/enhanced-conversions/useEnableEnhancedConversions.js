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
 * @typedef {Object} EnableEnhancedConversions
 * @property {boolean} isEnabled Whether enhanced conversions are enabled.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the enabled state and resolution status for the enhanced conversions feature.
 *
 * @return {EnableEnhancedConversions} The data and its state.
 */
const useEnableEnhancedConversions = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			isEnabled: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useEnableEnhancedConversions;
