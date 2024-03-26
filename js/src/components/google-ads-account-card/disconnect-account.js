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

const DisconnectAccount = () => {
	const { disconnectGoogleAdsAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

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
			onClick={ handleSwitch }
		/>
	);
};

export default DisconnectAccount;
