/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import AppButton from '~/components/app-button';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';

/**
 * Clicking on the button to connect the selected Google Tag Manager account.
 *
 * @event gla_google_tag_manager_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the `AccountCard` `indicator` for the not-yet-connected state: an "Action needed"
 * badge while there are no candidate accounts to pick from (the action lives in the `detail`
 * instead), or the "Connect" button once at least one candidate account exists.
 *
 * @fires gla_google_tag_manager_account_connect_button_click
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {boolean} props.isConnecting Whether the "Connect" request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Connect".
 * @return {JSX.Element|null} The indicator, or `null` until the accounts list has resolved.
 */
export default function Indicator( {
	accountId,
	isConnecting,
	onConnectClick,
} ) {
	const { existingAccounts, hasFinishedResolution } =
		useExistingGoogleTagManagerAccounts();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( ! existingAccounts?.length ) {
		return (
			<Badge intent="warning">
				{ __( 'Action needed', 'google-listings-and-ads' ) }
			</Badge>
		);
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
