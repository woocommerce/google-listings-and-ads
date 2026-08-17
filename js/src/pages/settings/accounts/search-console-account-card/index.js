/**
 * Internal dependencies
 */
import {
	SEARCH_CONSOLE_ACCOUNT_STATUS,
	SEARCH_CONSOLE_ACCOUNT_STEP,
} from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import ConnectSearchConsoleAccountCard from './connect-search-console-account-card';
import ConnectedSearchConsoleAccountCard from './connected-search-console-account-card';
import PropertySelectionSearchConsoleAccountCard from './property-selection-search-console-account-card';
import VerificationSearchConsoleAccountCard from './verification-search-console-account-card';
import ActionNeededSearchConsoleAccountCard from './action-needed-search-console-account-card';
import ReconnectSearchConsoleAccountCard from './reconnect-search-console-account-card';
import ConnectionFailedSearchConsoleAccountCard from './connection-failed-search-console-account-card';
import IncompleteSearchConsoleAccountCard from './incomplete-search-console-account-card';
import './index.scss';

const { CONNECTED, INCOMPLETE } = SEARCH_CONSOLE_ACCOUNT_STATUS;
const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

const STEP_CARD_MAP = {
	[ PROPERTY_SELECTION ]: PropertySelectionSearchConsoleAccountCard,
	[ VERIFICATION ]: VerificationSearchConsoleAccountCard,
	[ ACTION_NEEDED ]: ActionNeededSearchConsoleAccountCard,
	[ RECONNECT ]: ReconnectSearchConsoleAccountCard,
	[ CONNECTION_FAILED ]: ConnectionFailedSearchConsoleAccountCard,
};

/**
 * Renders the Search Console account card, self-contained and driven entirely by the
 * backend-determined connection state (no props): the connected steady state, the not-connected
 * state, and every incomplete connect-flow sub-state — property selection, verification,
 * action-needed (verification lost), reconnect (connection expired), connection-failed (initial
 * attempt failed), and a generic resume fallback for an abandoned flow that isn't covered by a
 * more specific step — never a silent success.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * @return {JSX.Element} The Search Console account card.
 */
export default function SearchConsoleAccountCard() {
	const { account } = useSearchConsoleAccount();

	if ( account?.status === CONNECTED ) {
		return <ConnectedSearchConsoleAccountCard account={ account } />;
	}

	if ( account?.status !== INCOMPLETE ) {
		return <ConnectSearchConsoleAccountCard />;
	}

	const StepCard = STEP_CARD_MAP[ account.step ];

	if ( StepCard ) {
		return <StepCard />;
	}

	// Generic fallback for an abandoned flow that isn't covered by a more specific step.
	return <IncompleteSearchConsoleAccountCard />;
}
