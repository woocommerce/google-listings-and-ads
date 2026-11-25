/**
 * Internal dependencies
 */
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import PropertySelection from './property-selection';
import Reconnect from './reconnect';
import ConnectionFailed from './connection-failed';
import Generic from './generic';
import Verification from './verification';

const { INCOMPLETE, ACTION_NEEDED, RECONNECT, CONNECTION_FAILED } =
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

const DETAIL_BY_STATUS = {
	[ INCOMPLETE ]: PropertySelection,
	[ ACTION_NEEDED ]: PropertySelection,
	[ RECONNECT ]: Reconnect,
	[ CONNECTION_FAILED ]: ConnectionFailed,
};

/**
 * Renders the `AccountCard` `detail` content for the current non-connected/disconnected status,
 * falling back to a generic "resume setup" message for transient-error and anything else not
 * covered by a more specific status. When action is needed only because the connected account
 * doesn't own the selected property yet, it offers to verify the site instead of asking for a
 * different property.
 *
 * @return {JSX.Element|null} The detail, or `null` until the account has resolved.
 */
export default function Detail() {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( account.status === ACTION_NEEDED && account.needs_verification ) {
		return <Verification />;
	}

	const DetailComponent = DETAIL_BY_STATUS[ account.status ] ?? Generic;

	return <DetailComponent />;
}
