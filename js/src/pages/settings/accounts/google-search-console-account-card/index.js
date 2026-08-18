/**
 * Internal dependencies
 */
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import ConnectedGoogleSearchConsoleAccountCard from './connected-google-search-console-account-card';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

/**
 * Renders the Google Search Console account card, self-contained and driven entirely by the
 * backend-determined connection state (no props): the connected steady state, the not-connected
 * state, and every incomplete connect-flow sub-state, handled by
 * {@link IncompleteGoogleSearchConsoleAccountCard}.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * @return {JSX.Element} The Google Search Console account card.
 */
const GoogleSearchConsoleAccountCard = () => {
	const { account } = useGoogleSearchConsoleAccount();

	if ( account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED ) {
		return <ConnectedGoogleSearchConsoleAccountCard account={ account } />;
	}

	if (
		account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED
	) {
		return <ConnectGoogleSearchConsoleAccountCard />;
	}

	return <IncompleteGoogleSearchConsoleAccountCard />;
};

export default GoogleSearchConsoleAccountCard;
