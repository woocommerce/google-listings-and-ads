/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '.~/utils/tracks';

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
 */
const DisconnectAccount = () => {
	const { disconnectGoogleAdsAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	const handleSwitch = useCallback( () => {
		setDisconnecting( true );
		disconnectGoogleAdsAccount( true ).catch( () =>
			setDisconnecting( false )
		);
	}, [ disconnectGoogleAdsAccount ] );

	return (
		<AppButton
			isTertiary
			loading={ isDisconnecting }
			text={ __(
				'Or, connect to a different Google Ads account',
				'google-listings-and-ads'
			) }
			eventName="gla_ads_account_disconnect_button_click"
			eventProps={ getEventProps() }
			onClick={ handleSwitch }
		/>
	);
};

export default DisconnectAccount;
