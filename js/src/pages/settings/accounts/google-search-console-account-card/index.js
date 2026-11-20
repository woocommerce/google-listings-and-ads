/**
 * Internal dependencies
 */
import {
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS,
	GOOGLE_SERVICE,
} from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import FocusableAccountCard from '~/components/focusable-account-card';
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import ConnectedGoogleSearchConsoleAccountCard from './connected-google-search-console-account-card';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

/**
 * Picks the card matching the backend-determined connection status.
 *
 * @param {Object} params
 * @param {Object} [params.account] The Google Search Console connection.
 * @param {() => void} params.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The card to render.
 */
function getCard( { account, onDisconnect } ) {
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
		return <ConnectGoogleSearchConsoleAccountCard />;
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
 * {@link ConnectGoogleSearchConsoleAccountCard} additionally owns confirming a just-completed
 * OAuth connect-flow return with the backend, while the account is still reporting disconnected.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element|null} The Google Search Console account card, or `null` until the account has resolved.
 */
const GoogleSearchConsoleAccountCard = ( { onDisconnect } ) => {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	return (
		<FocusableAccountCard id={ GOOGLE_SERVICE.SEARCH_CONSOLE }>
			{ getCard( { account, onDisconnect } ) }
		</FocusableAccountCard>
	);
};

export default GoogleSearchConsoleAccountCard;
