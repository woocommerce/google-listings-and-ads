/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import ConnectExistingAccountActions from './connect-existing-account-actions';
import LoadingLabel from '~/components/loading-label';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import { useAppDispatch } from '~/data';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import AdsAccountSelectControl from '~/components/ads-account-select-control';
import ConnectedIconLabel from '~/components/connected-icon-label';
import { ConnectAccountButton } from '~/components/google-ads-account-card';

/**
 * Renders an account card to connect to an existing Google Ads account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onCreateClick Callback when clicking on the button to create a new account
 */
const ConnectExistingAccount = ( { onCreateClick } ) => {
	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccountStatus } = useAppDispatch();
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();
	const {
		googleAdsAccount,
		hasFinishedResolution,
		hasGoogleAdsConnection,
		refetchGoogleAdsAccount,
	} = useGoogleAdsAccount();
	const [ connectGoogleAdsAccount ] = useApiFetchCallback( {
		path: '/wc/gla/ads/accounts',
		method: 'POST',
		data: { id: value },
	} );

	useEffect( () => {
		if ( hasGoogleAdsConnection ) {
			setValue( googleAdsAccount.id );
		}
	}, [ googleAdsAccount, hasGoogleAdsConnection ] );

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		setLoading( true );
		try {
			await connectGoogleAdsAccount();
			await fetchGoogleAdsAccountStatus();
			await refetchGoogleAdsAccount();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google Ads account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setLoading( false );
		}
	};

	const handleDisconnected = () => {
		/*
		 * Prevent the `value` from staying on the unclaimed and disconnected account ID.
		 * Please note that the reset works because the `AdsAccountSelectControl` happens to
		 * switch between two different `AppSelectControls` so that `autoSelectFirstOption`
		 * can be triggered again.
		 */
		setValue( undefined );
	};

	const getIndicator = () => {
		if ( ! hasFinishedResolution ) {
			return <LoadingLabel />;
		}

		if ( isLoading ) {
			return (
				<LoadingLabel
					text={ __( 'Connecting…', 'google-listings-and-ads' ) }
				/>
			);
		}

		if ( isGoogleAdsReady ) {
			return <ConnectedIconLabel />;
		}

		return (
			<ConnectAccountButton
				disabled={ hasGoogleAdsConnection }
				accountID={ value }
				onClick={ handleConnectClick }
			/>
		);
	};

	return (
		<AccountCard
			className="gla-google-combo-account-card gla-google-combo-service-account-card--ads"
			title={ __(
				'Connect to existing Google Ads account',
				'google-listings-and-ads'
			) }
			helper={ __(
				'Required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			) }
			alignIndicator="toDetail"
			indicator={ getIndicator() }
			detail={
				<AdsAccountSelectControl
					// Setting `key` is to ensure that `autoSelectFirstOption` will be
					// triggered after disconnecting, so that the automatically selected
					// account can call back to this component.
					key={ Boolean( value ) }
					value={ value }
					onChange={ setValue }
					autoSelectFirstOption
					nonInteractive={ hasGoogleAdsConnection }
				/>
			}
			actions={
				<ConnectExistingAccountActions
					disabled={ isLoading }
					isConnected={ hasGoogleAdsConnection }
					onCreateNewClick={ onCreateClick }
					onDisconnected={ handleDisconnected }
				/>
			}
		/>
	);
};

export default ConnectExistingAccount;
