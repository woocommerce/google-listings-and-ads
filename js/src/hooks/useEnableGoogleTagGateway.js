/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

const selectorName = 'getEnableGoogleTagGateway';

/**
 * @typedef {Object} EnableGoogleTagGateway
 * @property {boolean} isEnabled Whether Google Tag Gateway is enabled.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the enabled state and resolution status for the Google Tag Gateway feature.
 * If the Google Ads connection is not established or the resolution has not finished,
 * it returns a default state indicating that Google Tag Gateway is not enabled.
 *
 * @return {EnableGoogleTagGateway} The data and its state.
 */
const useEnableGoogleTagGateway = () => {
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

export default useEnableGoogleTagGateway;
