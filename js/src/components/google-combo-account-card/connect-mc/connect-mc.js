/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import SwitchUrlCard from '.~/components/google-mc-account-card/switch-url-card';
import ReclaimUrlCard from '.~/components/google-mc-account-card/reclaim-url-card';
import useConnectMCAccount from '.~/components/google-mc-account-card/useConnectMCAccount';
import useCreateMCAccount from '.~/components/google-mc-account-card/useCreateMCAccount';
import CreatingCard from '.~/components/google-mc-account-card/creating-card';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectAccountCard from '../connect-account-card';
import ConnectMCBody from './connect-mc-body';
import ConnectMCFooter from './connect-mc-footer';
import SpinnerCard from '.~/components/spinner-card';

/**
 * Clicking on the "Switch account" button to select a different Google Merchant Center account to connect.
 *
 * @event gla_mc_account_switch_account_button_click
 * @property {string} context (`switch-url`|`reclaim-url`) - indicate the button is clicked from which step.
 */

const ConnectMC = () => {
	const { googleMCAccount, hasFinishedResolution, isPreconditionReady } =
		useGoogleMCAccount();
	const [ value, setValue ] = useState();
	const [ handleConnectMC, resultConnectMC ] = useConnectMCAccount( value );
	const [ handleCreateAccount, resultCreateAccount ] = useCreateMCAccount();

	// MC is ready when we have a connection.
	// The `link_ads` step will be resolved when the Ads account is connected
	// since these can be connected in any order.
	const isConnected =
		googleMCAccount?.id ||
		googleMCAccount?.status === 'connected' ||
		( googleMCAccount?.status === 'incomplete' &&
			googleMCAccount?.step === 'link_ads' );

	if ( ! isConnected && resultConnectMC.response?.status === 409 ) {
		return (
			<SwitchUrlCard
				id={ resultConnectMC.error.id }
				message={ resultConnectMC.error.message }
				claimedUrl={ resultConnectMC.error.claimed_url }
				newUrl={ resultConnectMC.error.new_url }
				onSelectAnotherAccount={ resultConnectMC.reset }
			/>
		);
	}

	if (
		! isConnected &&
		( resultConnectMC.response?.status === 403 ||
			resultCreateAccount.response?.status === 403 )
	) {
		return (
			<ReclaimUrlCard
				id={
					resultConnectMC.error?.id || resultCreateAccount.error?.id
				}
				websiteUrl={
					resultConnectMC.error?.website_url ||
					resultCreateAccount.error?.website_url
				}
				onSwitchAccount={ () => {
					resultConnectMC.reset();
					resultCreateAccount.reset();
				} }
			/>
		);
	}

	if (
		! isConnected &&
		( resultCreateAccount.loading ||
			resultCreateAccount.response?.status === 503 )
	) {
		return (
			<CreatingCard
				retryAfter={ resultCreateAccount.error?.retry_after }
				onRetry={ handleCreateAccount }
			/>
		);
	}

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
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
