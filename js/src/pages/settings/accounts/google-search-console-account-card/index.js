/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS,
	GOOGLE_CONNECTION_OAUTH_PARAM,
	GOOGLE_CONNECTION_OAUTH_CONNECTED,
	GOOGLE_SERVICE_OAUTH_PARAM,
	GOOGLE_SERVICE,
} from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useScrollIntoView from '~/hooks/useScrollIntoView';
import useSearchConsoleSetupCompleteCallback from './hooks/useSearchConsoleSetupCompleteCallback';
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import ConnectedGoogleSearchConsoleAccountCard from './connected-google-search-console-account-card';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

/**
 * Picks the card matching the backend-determined connection status.
 *
 * @param {Object} params
 * @param {Object} [params.account] The Google Search Console connection.
 * @param {boolean} params.isCompletingSetup Whether a confirmed OAuth return is being completed.
 * @param {() => void} params.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The card to render.
 */
function getCard( { account, isCompletingSetup, onDisconnect } ) {
	if ( account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED ) {
		return (
			<ConnectedGoogleSearchConsoleAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	if (
		account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED
	) {
		return (
			<ConnectGoogleSearchConsoleAccountCard
				isCompletingSetup={ isCompletingSetup }
			/>
		);
	}

	return <IncompleteGoogleSearchConsoleAccountCard />;
}

/**
 * Renders the Google Search Console account card, driven entirely by the backend-determined
 * connection state: the connected steady state, the not-connected state, and every incomplete
 * connect-flow sub-state, handled by {@link IncompleteGoogleSearchConsoleAccountCard}.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element|null} The Google Search Console account card, or `null` until the account has resolved.
 */
const GoogleSearchConsoleAccountCard = ( { onDisconnect } ) => {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const [ handleCompleteSetup ] = useSearchConsoleSetupCompleteCallback();
	const { containerRef, scrollIntoView } = useScrollIntoView();

	const query = getQuery();
	const isSearchConsoleFlow =
		query?.[ GOOGLE_SERVICE_OAUTH_PARAM ] === GOOGLE_SERVICE.SEARCH_CONSOLE;
	const isSearchConsoleOAuthReturn =
		isSearchConsoleFlow &&
		query?.[ GOOGLE_CONNECTION_OAUTH_PARAM ] ===
			GOOGLE_CONNECTION_OAUTH_CONNECTED;

	useEffect( () => {
		async function handleSearchConsoleFlow() {
			scrollIntoView();

			if ( isSearchConsoleOAuthReturn ) {
				await handleCompleteSetup();
			}

			getHistory().replace(
				getNewPath( {
					[ GOOGLE_CONNECTION_OAUTH_PARAM ]: undefined,
					[ GOOGLE_SERVICE_OAUTH_PARAM ]: undefined,
				} )
			);
		}

		if ( isSearchConsoleFlow && hasFinishedResolution ) {
			handleSearchConsoleFlow();
		}
	}, [
		isSearchConsoleFlow,
		isSearchConsoleOAuthReturn,
		hasFinishedResolution,
		handleCompleteSetup,
		scrollIntoView,
	] );

	if ( ! hasFinishedResolution ) {
		return null;
	}

	return (
		<div ref={ containerRef }>
			{ getCard( {
				account,
				isCompletingSetup: isSearchConsoleOAuthReturn,
				onDisconnect,
			} ) }
		</div>
	);
};

export default GoogleSearchConsoleAccountCard;
