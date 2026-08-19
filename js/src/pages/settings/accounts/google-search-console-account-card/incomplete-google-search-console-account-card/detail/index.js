/**
 * Internal dependencies
 */
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import PropertySelection from './property-selection';
import Verification from './verification';
import Reconnect from './reconnect';
import ConnectionFailed from './connection-failed';
import Generic from './generic';

const { INCOMPLETE, ACTION_NEEDED, RECONNECT, CONNECTION_FAILED } =
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

const DETAIL_BY_STATUS = {
	[ INCOMPLETE ]: PropertySelection,
	[ ACTION_NEEDED ]: Verification,
	[ RECONNECT ]: Reconnect,
	[ CONNECTION_FAILED ]: ConnectionFailed,
};

/**
 * Renders the `AccountCard` `detail` content for the current non-connected/disconnected status,
 * falling back to a generic "resume setup" message for transient-error and anything else not
 * covered by a more specific status. Self-contained — reads the account directly rather than
 * receiving it as a prop, since the data is already cached in the store and no request is made.
 *
 * @return {JSX.Element|null} The detail, or `null` until the account has resolved.
 */
export default function Detail() {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const DetailComponent = DETAIL_BY_STATUS[ account?.status ] ?? Generic;

	return <DetailComponent />;
}
