/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleAccount} GoogleSearchConsoleAccount
 */

const selectorName = 'getGoogleSearchConsoleAccount';

/**
 * A hook to load the connection data of the Google Search Console account.
 *
 * @return {{ account: GoogleSearchConsoleAccount|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleSearchConsoleAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			account: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleSearchConsoleAccount;
