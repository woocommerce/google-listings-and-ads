/**
 * Internal dependencies
 */
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useAutoResolveSearchConsoleProperty from '~/hooks/useAutoResolveSearchConsoleProperty';
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import ConnectedGoogleSearchConsoleAccountCard from './connected-google-search-console-account-card';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

/**
 * Renders the Google Search Console account card, driven by the backend-determined connection
 * state (connected, not-connected, and every incomplete connect-flow sub-state, handled by
 * {@link IncompleteGoogleSearchConsoleAccountCard}) plus one frontend-owned decision:
 * {@see useAutoResolveSearchConsoleProperty} auto-resolves a property with no merchant action
 * when there are zero or exactly one candidates, so the merchant lands on the connected card
 * with a one-time success notice instead of ever seeing a picker for a "choice" that isn't
 * really one. Mounted here, above the connected/incomplete branch, so its `justResolved` state
 * survives the switch from one to the other.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element|null} The Google Search Console account card, or `null` until the account has resolved.
 */
const GoogleSearchConsoleAccountCard = ( { onDisconnect } ) => {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const { justResolved } = useAutoResolveSearchConsoleProperty();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED ) {
		return (
			<ConnectedGoogleSearchConsoleAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
				justResolved={ justResolved }
			/>
		);
	}

	if (
		account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED
	) {
		return <ConnectGoogleSearchConsoleAccountCard />;
	}

	return <IncompleteGoogleSearchConsoleAccountCard />;
};

export default GoogleSearchConsoleAccountCard;
