/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import Badge from '~/components/badge';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleProperties from '~/hooks/useGoogleSearchConsoleProperties';
import useGoogleSearchConsoleConnectRedirect from '../hooks/useGoogleSearchConsoleConnectRedirect';

const { INCOMPLETE, ACTION_NEEDED, RECONNECT, CONNECTION_FAILED } =
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

const ACTION_NEEDED_BADGE = {
	intent: 'warning',
	label: __( 'Action needed', 'google-listings-and-ads' ),
};

const BADGE_BY_STATUS = {
	[ ACTION_NEEDED ]: ACTION_NEEDED_BADGE,
};

const BUTTON_LABEL_BY_STATUS = {
	[ RECONNECT ]: __( 'Reconnect', 'google-listings-and-ads' ),
	[ CONNECTION_FAILED ]: __( 'Retry', 'google-listings-and-ads' ),
};

const DEFAULT_BUTTON_LABEL = __( 'Resume setup', 'google-listings-and-ads' );

/**
 * Clicking on the button to (re)connect the Google Search Console account — covers reconnecting after
 * expiry, retrying after a failed attempt, and resuming a generic abandoned flow.
 *
 * @event gla_google_search_console_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the `AccountCard` `indicator` for the current non-connected/disconnected status: a
 * status badge for the `action-needed` status, or for `incomplete` while a genuine multi-match
 * property choice is pending — both cases whose action lives inside the notice `detail` — or the
 * sole recovery action button itself for the remaining cases (incomplete with no pending choice,
 * reconnect, connection-failed, and the generic fallback covering transient-error and anything
 * else unrecognized), which have no accompanying badge.
 *
 * For `incomplete`, rendering is held back until the candidate-properties list has itself
 * finished resolving — otherwise, for the instant before that list loads, this would show the
 * generic recovery button (implying an action the merchant doesn't actually need) directly above
 * the sibling detail's own "Loading…" text.
 *
 * @fires gla_google_search_console_connect_button_click
 *
 * @return {JSX.Element|null} The indicator, or `null` until the account (and, for `incomplete`,
 *   the candidate-properties list) has resolved.
 */
export default function Indicator() {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const status = account?.status;
	// Only `incomplete` ever needs the candidate-properties list — skip the fetch entirely for
	// every other status (action-needed, reconnect, connection-failed, and the initial
	// not-yet-resolved render) rather than triggering it on every render regardless of status.
	const { properties, hasFinishedResolution: hasResolvedProperties } =
		useGoogleSearchConsoleProperties( { skip: status !== INCOMPLETE } );
	const { connect: handleClick, loading } =
		useGoogleSearchConsoleConnectRedirect();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( status === INCOMPLETE && ! hasResolvedProperties ) {
		return null;
	}

	const hasPendingPropertyChoice =
		status === INCOMPLETE && properties?.length > 0;

	const badge = BADGE_BY_STATUS[ status ];

	if ( badge || hasPendingPropertyChoice ) {
		const { intent, label } = badge ?? ACTION_NEEDED_BADGE;
		return <Badge intent={ intent }>{ label }</Badge>;
	}

	const isError = status === RECONNECT || status === CONNECTION_FAILED;
	const buttonLabel =
		BUTTON_LABEL_BY_STATUS[ status ] ?? DEFAULT_BUTTON_LABEL;

	return (
		<AppButton
			eventName="gla_google_search_console_connect_button_click"
			eventProps={ { context: 'settings-search-console' } }
			onClick={ handleClick }
			isDestructive={ isError }
			loading={ loading }
			isSecondary
		>
			{ buttonLabel }
		</AppButton>
	);
}
