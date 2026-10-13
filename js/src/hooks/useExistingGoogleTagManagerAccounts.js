/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleTagManagerAccount} GoogleTagManagerAccount
 */

const selectorName = 'getExistingGoogleTagManagerAccounts';

/**
 * A hook to load the Google Tag Manager accounts available to the connected Google user.
 *
 * @return {{ existingAccounts: GoogleTagManagerAccount[]|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useExistingGoogleTagManagerAccounts = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			existingAccounts: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useExistingGoogleTagManagerAccounts;
