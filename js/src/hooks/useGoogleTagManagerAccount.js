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

const selectorName = 'getGoogleTagManagerAccount';

/**
 * A hook to load the connection data of the Google Tag Manager account.
 *
 * @return {{ account: GoogleTagManagerAccount|null, hasFinishedResolution: boolean, isResolving: boolean }} The data and its resolution state.
 */
const useGoogleTagManagerAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			account: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
			isResolving: selector.isResolving( selectorName, [] ),
		};
	}, [] );
};

export default useGoogleTagManagerAccount;
