/**
 * Internal dependencies
 */
import { SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import ConnectSearchConsoleAccountCard from './connect-search-console-account-card';
import ConnectedSearchConsoleAccountCard from './connected-search-console-account-card';
import IncompleteSearchConsoleAccountCard from './incomplete-search-console-account-card';

/**
 * Renders the Search Console account card, self-contained and driven entirely by the
 * backend-determined connection state (no props): the connected steady state, the not-connected
 * state, and every incomplete connect-flow sub-state, handled by
 * {@link IncompleteSearchConsoleAccountCard}.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * @return {JSX.Element} The Search Console account card.
 */
export default function SearchConsoleAccountCard() {
	const { account } = useSearchConsoleAccount();

	if ( account?.status === SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED ) {
		return <ConnectedSearchConsoleAccountCard account={ account } />;
	}

	if ( account?.status === SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED ) {
		return <ConnectSearchConsoleAccountCard />;
	}

	return <IncompleteSearchConsoleAccountCard />;
}
