/**
 * Internal dependencies
 */
import { SEARCH_CONSOLE_ACCOUNT_STEP } from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import PropertySelection from './property-selection';
import Verification from './verification';
import ActionNeeded from './action-needed';
import Reconnect from './reconnect';
import ConnectionFailed from './connection-failed';
import Generic from './generic';

const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

const DETAIL_BY_STEP = {
	[ PROPERTY_SELECTION ]: PropertySelection,
	[ VERIFICATION ]: Verification,
	[ ACTION_NEEDED ]: ActionNeeded,
	[ RECONNECT ]: Reconnect,
	[ CONNECTION_FAILED ]: ConnectionFailed,
};

/**
 * Renders the `AccountCard` `detail` content for the current incomplete-flow step, falling back
 * to a generic "resume setup" message for a step that isn't covered by a more specific one.
 * Self-contained — reads the account directly rather than receiving it as a prop, since the
 * data is already cached in the store and no request is made.
 *
 * @return {JSX.Element|null} The detail, or `null` until the account has resolved.
 */
export default function Detail() {
	const { account, hasFinishedResolution } = useSearchConsoleAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const DetailComponent = DETAIL_BY_STEP[ account?.step ] ?? Generic;

	return <DetailComponent account={ account } />;
}
