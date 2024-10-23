/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectAccountCard from '../connect-account-card';
import ConnectMCBody from './connect-mc-body';
import ConnectMCFooter from './connect-mc-footer';
import SpinnerCard from '.~/components/spinner-card';
import AccountConnectionStatus from '.~/components/google-mc-account-card/account-connection-status';
import useConnectMCAccount from '.~/hooks/useConnectMCAccount';

/**
 * Clicking on the "Switch account" button to select a different Google Merchant Center account to connect.
 *
 * @event gla_mc_account_switch_account_button_click
 * @property {string} context (`switch-url`|`reclaim-url`) - indicate the button is clicked from which step.
 */

const ConnectMC = ( { createMCAccount, resultCreateMCAccount } ) => {
	const {
		googleMCAccount,
		hasFinishedResolution,
		hasGoogleMCConnection,
		isPreconditionReady,
	} = useGoogleMCAccount();
	const [ accountID, setAccountID ] = useState();
	const [ handleConnectMC, resultConnectMC ] =
		useConnectMCAccount( accountID );

	useEffect( () => {
		if ( hasGoogleMCConnection ) {
			setAccountID( googleMCAccount.id );
		}
	}, [ googleMCAccount, hasGoogleMCConnection ] );

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if (
		! hasGoogleMCConnection &&
		( resultConnectMC.response?.status === 409 ||
			resultConnectMC.response?.status === 403 ||
			resultCreateMCAccount.response?.status === 403 ||
			resultCreateMCAccount.loading ||
			resultCreateMCAccount.response?.status === 503 )
	) {
		return (
			<AccountConnectionStatus
				resultConnectMC={ resultConnectMC }
				resultCreateAccount={ resultCreateMCAccount }
				onRetry={ createMCAccount }
			/>
		);
	}

	return (
		<ConnectAccountCard
			className={ classNames( 'gla-google-combo-account-card--mc', {
				'gla-google-combo-account-card--connected':
					hasGoogleMCConnection,
			} ) }
			title={ __(
				'Connect to existing Merchant Center account',
				'google-listings-and-ads'
			) }
			helper={ __(
				'Required to sync products so they show on Google.',
				'google-listings-and-ads'
			) }
			body={
				<ConnectMCBody
					value={ accountID }
					setValue={ setAccountID }
					isConnected={ hasGoogleMCConnection }
					isConnecting={ resultConnectMC.loading }
					handleConnectMC={ handleConnectMC }
				/>
			}
			footer={
				<ConnectMCFooter
					isConnected={ hasGoogleMCConnection }
					resultConnectMC={ resultConnectMC }
					resultCreateAccount={ resultCreateMCAccount }
					handleCreateAccount={ createMCAccount }
				/>
			}
			disabled={ ! isPreconditionReady }
		/>
	);
};

export default ConnectMC;
