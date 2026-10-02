/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from './useGoogleTagManagerAccount';

/**
 * @typedef {import('~/data/types.js').GoogleTagManagerSettings} GoogleTagManagerSettings
 */

const selectorName = 'getGoogleTagManagerSettings';

/**
 * A hook to load the Google Tag Manager settings. They're only requested once a container is
 * connected; until then `settings` is `null`.
 *
 * @return {{ settings: GoogleTagManagerSettings|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleTagManagerSettings = () => {
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleTagManagerAccount();
	const isConnected =
		account?.status === GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED;

	return useSelect(
		( select ) => {
			if ( ! isConnected ) {
				return {
					settings: null,
					hasFinishedResolution: hasResolvedAccount,
				};
			}

			const selector = select( STORE_KEY );

			return {
				settings: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ isConnected, hasResolvedAccount ]
	);
};

export default useGoogleTagManagerSettings;
