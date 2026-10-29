/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS, glaData } from '~/constants';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleAccount} GoogleSearchConsoleAccount
 */

const selectorName = 'getGoogleSearchConsoleAccount';

/**
 * A hook to load the connection data of the Google Search Console account.
 *
 * `isReportAvailable` is true when Search Console data can be requested. A transient failure
 * checking the status says nothing about the connection itself, so in that state it relies on the
 * stored connection data sent with the page instead.
 *
 * @return {{ account: GoogleSearchConsoleAccount|null, isConnected: boolean, isReportAvailable: boolean, hasFinishedResolution: boolean }} The data, whether it is connected, whether the report is available, and its resolution state.
 */
const useGoogleSearchConsoleAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const account = selector[ selectorName ]();

		const isConnected =
			account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED;

		const isReportAvailable =
			account?.status ===
			GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.TRANSIENT_ERROR
				? Boolean( glaData.searchConsoleConnected )
				: isConnected;

		return {
			account,
			isConnected,
			isReportAvailable,
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleSearchConsoleAccount;
