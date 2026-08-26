/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import AppButton from '~/components/app-button';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

const { DISCONNECTED, NO_ACCOUNT, CONTAINER_SELECTION } =
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

const ACTION_NEEDED_BADGE = {
	intent: 'warning',
	label: __( 'Action needed', 'google-listings-and-ads' ),
};

const BADGE_BY_STATUS = {
	[ DISCONNECTED ]: ACTION_NEEDED_BADGE,
	[ NO_ACCOUNT ]: ACTION_NEEDED_BADGE,
	[ CONTAINER_SELECTION ]: ACTION_NEEDED_BADGE,
};

/**
 * Clicking on the button to connect the selected Google Tag Manager account.
 *
 * @event gla_google_tag_manager_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the `AccountCard` `indicator` for the current not-yet-connected status: a status
 * badge for every status whose action lives inside the `detail` instead (zero-accounts,
 * container-selection, and the disconnected/error fallback), or the "Connect" button for the
 * account-selection status, which has no accompanying badge.
 *
 * @fires gla_google_tag_manager_account_connect_button_click
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked (not yet connected) account ID.
 * @param {boolean} props.isConnecting Whether the "Connect" request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Connect".
 * @return {JSX.Element|null} The indicator, or `null` until the account has resolved.
 */
export default function Indicator( {
	accountId,
	isConnecting,
	onConnectClick,
} ) {
	const { account, hasFinishedResolution } = useGoogleTagManagerAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const badge = BADGE_BY_STATUS[ account?.status ];

	if ( badge ) {
		return <Badge intent={ badge.intent }>{ badge.label }</Badge>;
	}

	return (
		<AppButton
			eventName="gla_google_tag_manager_account_connect_button_click"
			eventProps={ { context: 'settings-tag-manager' } }
			onClick={ onConnectClick }
			disabled={ ! accountId || isConnecting }
			loading={ isConnecting }
			isSecondary
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
}
