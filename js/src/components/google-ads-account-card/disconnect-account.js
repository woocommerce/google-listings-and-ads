/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '~/utils/tracks';

/**
 * Clicking on the button to disconnect the Google Ads account.
 *
 * @event gla_ads_account_disconnect_button_click
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Renders a button to disconnect the Google Ads account.
 *
 * @fires gla_ads_account_disconnect_button_click When the user clicks on the button to disconnect the Google Ads account.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 */
const DisconnectAccount = ( { onDisconnected = noop } ) => {
	const { disconnectGoogleAdsAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	const handleSwitch = () => {
		setDisconnecting( true );
		disconnectGoogleAdsAccount( true )
			.then( () => onDisconnected() )
			.catch( () => setDisconnecting( false ) );
	};

	return (
		<AppButton
			eventName="gla_ads_account_disconnect_button_click"
			eventProps={ getEventProps() }
			loading={ isDisconnecting }
			onClick={ handleSwitch }
			text={ __(
				'Or, connect to a different Google Ads account',
				'google-listings-and-ads'
			) }
			isTertiary
		/>
	);
};

export default DisconnectAccount;
