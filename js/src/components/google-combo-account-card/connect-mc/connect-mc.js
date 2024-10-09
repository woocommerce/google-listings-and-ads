/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import classNames from 'classnames';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useConnectMCAccount from '.~/components/google-mc-account-card/useConnectMCAccount';
import useCreateMCAccount from '.~/components/google-mc-account-card/useCreateMCAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectAccountCard from '../connect-account-card';
import ConnectMCBody from './connect-mc-body';
import ConnectMCFooter from './connect-mc-footer';
import SpinnerCard from '.~/components/spinner-card';
import AccountConnectionStatus from '.~/components/google-mc-account-card/account-connection-status';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';

/**
 * Clicking on the "Switch account" button to select a different Google Merchant Center account to connect.
 *
 * @event gla_mc_account_switch_account_button_click
 * @property {string} context (`switch-url`|`reclaim-url`) - indicate the button is clicked from which step.
 */

const ConnectMC = ( { onCreateAccountLoading = noop } ) => {
	const { googleMCAccount, hasFinishedResolution, isPreconditionReady } =
		useGoogleMCAccount();
	const [ value, setValue ] = useState();
	const [ handleConnectMC, resultConnectMC ] = useConnectMCAccount( value );
	const [ handleCreateAccount, resultCreateAccount ] = useCreateMCAccount();

	useEffect( () => {
		onCreateAccountLoading( resultCreateAccount?.loading );
	}, [ resultCreateAccount, onCreateAccountLoading ] );

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	// MC is ready when we have a connection and preconditions are met.
	// The `link_ads` step will be resolved when the Ads account is connected
	// since these can be connected in any order.
	const isConnected =
		isPreconditionReady &&
		( googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.CONNECTED ||
			( googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE &&
				googleMCAccount?.step === 'link_ads' ) );

	if (
		resultConnectMC.response?.status === 409 ||
		resultConnectMC.response?.status === 403 ||
		resultCreateAccount.response?.status === 403 ||
		resultCreateAccount.loading ||
		resultCreateAccount.response?.status === 503
	) {
		return (
			<AccountConnectionStatus
				resultConnectMC={ resultConnectMC }
				resultCreateAccount={ resultCreateAccount }
				onRetry={ handleCreateAccount }
			/>
		);
	}

	return (
		<ConnectAccountCard
			className={ classNames( 'gla-google-combo-account-card--mc', {
				'gla-google-combo-account-card--connected': isConnected,
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
					value={ value }
					setValue={ setValue }
					isConnected={ isConnected }
					isConnecting={ resultConnectMC.loading }
					handleConnectMC={ handleConnectMC }
				/>
			}
			footer={
				<ConnectMCFooter
					isConnected={ isConnected }
					resultConnectMC={ resultConnectMC }
					handleCreateAccount={ handleCreateAccount }
				/>
			}
			disabled={ ! isPreconditionReady }
		/>
	);
};

export default ConnectMC;
