/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';

/**
 * Clicking on the button to disconnect the YouTube account.
 *
 * @event gla_youtube_account_disconnect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Renders a button to disconnect the YouTube account.
 *
 * @fires gla_youtube_account_disconnect_button_click When the user clicks on the button to disconnect the YouTube account.
 */
const DisconnectAccount = () => {
	const { disconnectYouTubeAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

	const handleSwitch = async () => {
		setDisconnecting( true );
		await disconnectYouTubeAccount();
		setDisconnecting( false );
	};

	return (
		<AppButton
			loading={ isDisconnecting }
			text={ __(
				'Disconnect YouTube account',
				'google-listings-and-ads'
			) }
			eventName="gla_youtube_account_disconnect_button_click"
			eventProps={ { context: 'settings-youtube' } }
			onClick={ handleSwitch }
			isDestructive
			isLink
		/>
	);
};

export default DisconnectAccount;
