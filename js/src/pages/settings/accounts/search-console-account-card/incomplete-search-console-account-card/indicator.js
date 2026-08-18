/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import Badge from '~/components/badge';
import { SEARCH_CONSOLE_ACCOUNT_STEP } from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useSearchConsoleConnectRedirect from '../hooks/useSearchConsoleConnectRedirect';

const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

const BADGE_BY_STEP = {
	[ PROPERTY_SELECTION ]: {
		intent: 'info',
		label: __( 'In progress', 'google-listings-and-ads' ),
	},
	[ VERIFICATION ]: {
		intent: 'warning',
		label: __( 'Action needed', 'google-listings-and-ads' ),
	},
	[ ACTION_NEEDED ]: {
		intent: 'warning',
		label: __( 'Action needed', 'google-listings-and-ads' ),
	},
};

const BUTTON_LABEL_BY_STEP = {
	[ RECONNECT ]: __( 'Reconnect', 'google-listings-and-ads' ),
	[ CONNECTION_FAILED ]: __( 'Retry', 'google-listings-and-ads' ),
};

const DEFAULT_BUTTON_LABEL = __( 'Resume setup', 'google-listings-and-ads' );

/**
 * Clicking on the button to (re)connect the Search Console account — covers reconnecting after
 * expiry, retrying after a failed attempt, and resuming a generic abandoned flow.
 *
 * @event gla_search_console_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the `AccountCard` `indicator` for the current incomplete-flow step: a status badge
 * for the steps whose action lives inside the notice `detail` (property selection,
 * verification, action-needed), or the sole recovery action button itself for the remaining,
 * undesigned steps (reconnect, connection-failed, and the generic fallback), which have no
 * accompanying badge. Self-contained — reads the account step directly rather than receiving it
 * as a prop, since the data is already cached in the store and no request is made.
 *
 * @fires gla_search_console_connect_button_click
 *
 * @return {JSX.Element|null} The indicator, or `null` until the account has resolved.
 */
export default function Indicator() {
	const { account, hasFinishedResolution } = useSearchConsoleAccount();
	const { connect: handleClick, loading } = useSearchConsoleConnectRedirect( {
		next_page_name: 'search-console-settings',
	} );

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const step = account?.step;
	const badge = BADGE_BY_STEP[ step ];

	if ( badge ) {
		return <Badge intent={ badge.intent }>{ badge.label }</Badge>;
	}

	const isError = step === RECONNECT || step === CONNECTION_FAILED;
	const buttonLabel = BUTTON_LABEL_BY_STEP[ step ] ?? DEFAULT_BUTTON_LABEL;

	return (
		<AppButton
			eventName="gla_search_console_connect_button_click"
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
