/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleTagManagerSettings} GoogleTagManagerSettings
 */

const selectorName = 'getGoogleTagManagerSettings';

/**
 * A hook to load the Google Tag Manager settings.
 *
 * @return {{ settings: GoogleTagManagerSettings|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleTagManagerSettings = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			settings: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleTagManagerSettings;
