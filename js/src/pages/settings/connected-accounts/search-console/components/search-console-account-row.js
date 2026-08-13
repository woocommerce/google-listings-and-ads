/**
 * Internal dependencies
 */
import {
	SEARCH_CONSOLE_ACCOUNT_STATUS,
	SEARCH_CONSOLE_ACCOUNT_STEP,
} from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import ConnectedRow from './connected-row';
import PropertySelectionRow from './property-selection-row';
import VerificationRow from './verification-row';
import ActionNeededRow from './action-needed-row';
import ReconnectExpiredRow from './reconnect-expired-row';
import ConnectionFailedRow from './connection-failed-row';
import IncompleteRow from './incomplete-row';
import './search-console-account-row.scss';

const { CONNECTED } = SEARCH_CONSOLE_ACCOUNT_STATUS;
const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

const STEP_ROW_MAP = {
	[ PROPERTY_SELECTION ]: PropertySelectionRow,
	[ VERIFICATION ]: VerificationRow,
	[ ACTION_NEEDED ]: ActionNeededRow,
	[ RECONNECT ]: ReconnectExpiredRow,
	[ CONNECTION_FAILED ]: ConnectionFailedRow,
};

/**
 * Renders the specialized row for every non-default Search Console state: the connected steady
 * state, and every incomplete connect-flow sub-state — property selection, verification,
 * action-needed (verification lost), reconnect (connection expired), connection-failed (initial
 * attempt failed), and a generic resume fallback for an abandoned flow that isn't covered by a
 * more specific step — never a silent success.
 *
 * The connecting/property-selection/verification/action-needed states follow the landed Figma
 * design (status badge + colored notice with icon, title, and body). Reconnect, connection-failed,
 * and the generic fallback have no design yet, so they keep the plain error-notice treatment.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The Search Console account row.
 */
export default function SearchConsoleAccountRow( { account } ) {
	const { searchConsoleAccount } = useSearchConsoleAccount();
	const status = searchConsoleAccount?.status;
	const step = searchConsoleAccount?.step;

	if ( status === CONNECTED ) {
		return <ConnectedRow account={ account } />;
	}

	const StepRow = STEP_ROW_MAP[ step ];

	if ( StepRow ) {
		return <StepRow account={ account } />;
	}

	// Generic fallback for an abandoned flow that isn't covered by a more specific step.
	return <IncompleteRow account={ account } />;
}
