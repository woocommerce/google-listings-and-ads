/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import AppButton from '.~/components/app-button';

const AccountSwitch = () => {
	const { disconnectGoogleAdsAccount } = useAppDispatch();
	const { refetchGoogleAdsAccountStatus } = useGoogleAdsAccountStatus();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

	const handleSwitch = () => {
		setDisconnecting( true );
		disconnectGoogleAdsAccount( true ).catch( () =>
			setDisconnecting( false )
		);
		refetchGoogleAdsAccountStatus();
	};

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

export default AccountSwitch;
