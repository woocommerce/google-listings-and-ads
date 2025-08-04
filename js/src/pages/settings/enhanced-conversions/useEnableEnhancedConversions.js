/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

const selectorName = 'getEnableEnhancedConversions';

/**
 * @typedef {Object} EnableEnhancedConversions
 * @property {boolean} isEnabled Whether enhanced conversions are enabled.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the enabled state and resolution status for the enhanced conversions feature.
 * If the Google Ads connection is not established or the resolution has not finished,
 * it returns a default state indicating that enhanced conversions are not enabled.
 *
 * @return {EnableEnhancedConversions} The data and its state.
 */
const useEnableEnhancedConversions = () => {
	const { hasGoogleAdsConnection, hasFinishedResolution } =
		useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! hasFinishedResolution || ! hasGoogleAdsConnection ) {
				return {
					isEnabled: false,
					hasFinishedResolution,
				};
			}

			const selector = select( STORE_KEY );

			return {
				isEnabled: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ hasGoogleAdsConnection, hasFinishedResolution ]
	);
};

export default useEnableEnhancedConversions;
