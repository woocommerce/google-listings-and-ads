/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useSearchConsoleSetupCompleteCallback from './hooks/useSearchConsoleSetupCompleteCallback';
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import ConnectedGoogleSearchConsoleAccountCard from './connected-google-search-console-account-card';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

/**
 * Renders the Google Search Console account card, driven entirely by the backend-determined
 * connection state: the connected steady state, the not-connected state, and every incomplete
 * connect-flow sub-state, handled by {@link IncompleteGoogleSearchConsoleAccountCard}.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * Also confirms Search Console's OAuth setup with the backend on a confirmed return from that
 * flow (`google-mc=connected` on the URL) while still locally disconnected — kept ahead of the
 * status checks below since the account is still reporting disconnected at that point.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element|null} The Google Search Console account card, or `null` until the account has resolved.
 */
const GoogleSearchConsoleAccountCard = ( { onDisconnect } ) => {
	const isSearchConsoleOAuthReturn =
		getQuery()?.[ 'google-mc' ] === 'connected';
	const [ handleCompleteSetup ] = useSearchConsoleSetupCompleteCallback();
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const isDisconnected =
		account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED;

	useEffect( () => {
		async function completeSetup() {
			await handleCompleteSetup();
			getHistory().replace( getNewPath( { 'google-mc': undefined } ) );
		}

		if ( isSearchConsoleOAuthReturn && isDisconnected ) {
			completeSetup();
		}
	}, [ isSearchConsoleOAuthReturn, isDisconnected, handleCompleteSetup ] );

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED ) {
		return (
			<ConnectedGoogleSearchConsoleAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	if ( isDisconnected ) {
		return <ConnectGoogleSearchConsoleAccountCard />;
	}

	return <IncompleteGoogleSearchConsoleAccountCard />;
};

export default GoogleSearchConsoleAccountCard;
