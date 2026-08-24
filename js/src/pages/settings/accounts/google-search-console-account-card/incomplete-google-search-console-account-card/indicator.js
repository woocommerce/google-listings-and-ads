/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import Badge from '~/components/badge';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
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
 * Renders the `AccountCard` `indicator` for the current non-connected/disconnected status.
 *
 * The `incomplete` status covers two visually distinct sub-cases sharing one underlying status:
 * a genuine unresolved property choice shows the "Action needed" badge, while a property still
 * silently auto-resolving (no `matches` yet) shows a loading spinner instead — there is nothing
 * for the merchant to do or click at that point. `action-needed` (the separate site-verification
 * case) keeps its own badge. The remaining statuses (reconnect,
 * connection-failed, and the generic fallback covering transient-error and anything else
 * unrecognized) render the sole recovery action button instead, with no accompanying badge.
 *
 * @fires gla_google_search_console_connect_button_click
 *
 * @return {JSX.Element|null} The indicator, or `null` until the account has resolved.
 */
export default function Indicator() {
	const { account, hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const { connect: handleClick, loading } =
		useGoogleSearchConsoleConnectRedirect();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const status = account?.status;

	if ( status === INCOMPLETE ) {
		if ( account.matches?.length ) {
			return (
				<Badge intent={ ACTION_NEEDED_BADGE.intent }>
					{ ACTION_NEEDED_BADGE.label }
				</Badge>
			);
		}

		return <Spinner />;
	}

	const badge = BADGE_BY_STATUS[ status ];

	if ( badge ) {
		return <Badge intent={ badge.intent }>{ badge.label }</Badge>;
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
