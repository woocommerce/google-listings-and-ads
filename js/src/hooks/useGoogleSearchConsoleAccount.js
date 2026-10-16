/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleAccount} GoogleSearchConsoleAccount
 */

const selectorName = 'getGoogleSearchConsoleAccount';

/**
 * A hook to load the connection data of the Google Search Console account.
 *
 * @return {{ account: GoogleSearchConsoleAccount|null, isConnected: boolean, hasFinishedResolution: boolean }} The data, whether it is connected, and its resolution state.
 */
const useGoogleSearchConsoleAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const account = selector[ selectorName ]();

		return {
			account,
			isConnected:
				account?.status ===
				GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleSearchConsoleAccount;
