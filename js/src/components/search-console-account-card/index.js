/**
 * Internal dependencies
 */
import {
	SEARCH_CONSOLE_ACCOUNT_STATUS,
	SEARCH_CONSOLE_ACCOUNT_STEP,
} from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import SpinnerCard from '~/components/spinner-card';
import ConnectSearchConsole from './connect-search-console';
import PropertySelector from './property-selector';
import VerificationStep from './verification-step';
import ActionNeededCard from './action-needed-card';
import ReconnectCard from './reconnect-card';
import ConnectionFailedCard from './connection-failed-card';
import IncompleteResumeCard from './incomplete-resume-card';
import ConnectedSearchConsoleAccountCard from './connected-search-console-account-card';

const { CONNECTED, INCOMPLETE } = SEARCH_CONSOLE_ACCOUNT_STATUS;
const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

/**
 * Renders the Google Search Console account card, self-contained and driven entirely by the
 * backend-determined connection state (no props), mirroring `YouTubeAccountCard`'s composition
 * point.
 *
 * This is a sequential-if state router across the connect flow's states — loading,
 * not-connected/authorizing, property-selection, verification, action-needed/reconnect/
 * connection-failed, and connected — modeled on `GoogleAdsAccountCard`'s own sequential-if
 * pattern rather than Merchant Center's indicator-only `getIndicator()` switch, since Search
 * Console needs full-card swaps across states rather than just varying an indicator.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever sub-state the backend currently reports
 * (AC-022, AC-023).
 */
export default function SearchConsoleAccountCard() {
	const { searchConsoleAccount, hasFinishedResolution } =
		useSearchConsoleAccount();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( searchConsoleAccount?.status === CONNECTED ) {
		return (
			<ConnectedSearchConsoleAccountCard
				searchConsoleAccount={ searchConsoleAccount }
			/>
		);
	}

	if ( searchConsoleAccount?.status !== INCOMPLETE ) {
		return <ConnectSearchConsole />;
	}

	if ( searchConsoleAccount.step === PROPERTY_SELECTION ) {
		return <PropertySelector />;
	}

	if ( searchConsoleAccount.step === VERIFICATION ) {
		return <VerificationStep />;
	}

	if ( searchConsoleAccount.step === ACTION_NEEDED ) {
		return <ActionNeededCard />;
	}

	if ( searchConsoleAccount.step === RECONNECT ) {
		return <ReconnectCard />;
	}

	if ( searchConsoleAccount.step === CONNECTION_FAILED ) {
		return <ConnectionFailedCard />;
	}

	// Generic fallback for an abandoned flow that isn't covered by a more specific step
	// (AC-018) — never a silent success.
	return <IncompleteResumeCard />;
}
