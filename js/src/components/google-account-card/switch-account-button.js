/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
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
 * @param {Object} props React props
 * @param {string} [props.text="Or, connect to a different Google account"] Text to display on the button
 */
const SwitchAccountButton = ( {
	text = __(
		'Or, connect to a different Google account',
		'google-listings-and-ads'
	),
	...restProps
} ) => {
	const [ handleSwitch, { loading } ] = useSwitchGoogleAccount();

	return (
		<AppButton
			disabled={ loading }
			eventName="gla_google_account_connect_different_account_button_click"
			onClick={ handleSwitch }
			text={ text }
			isLink
			{ ...restProps }
		/>
	);
};

export default SwitchAccountButton;
