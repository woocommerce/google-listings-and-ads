/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useSwitchGoogleAccount from './useSwitchGoogleAccount';

/**
 * Clicking on the "connect to a different Google account" button.
 *
 * @event gla_google_account_connect_different_account_button_click
 */

/**
 * Renders a switch button that lets user connect with another Google account.
 *
 * @fires gla_google_account_connect_different_account_button_click
 */
const SwitchAccountButton = () => {
	const [ handleSwitch, { loading } ] = useSwitchGoogleAccount();
	return (
		<AppButton
			isLink
			disabled={ loading }
			text={ __(
				'Or, connect to a different Google account',
				'google-listings-and-ads'
			) }
			eventName="gla_google_account_connect_different_account_button_click"
			onClick={ handleSwitch }
		/>
	);
};

export default SwitchAccountButton;
