/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelect from './merchant-center-select';
import AppButton from '~/components/app-button';
import useConnectMCAccount from '~/hooks/useConnectMCAccount';
import SwitchUrlCard from '../switch-url-card';
import ReclaimUrlCard from '../reclaim-url-card';
import LoadingLabel from '~/components/loading-label';
import ConnectedIconLabel from '~/components/connected-icon-label';
import AccountCard from '~/components/account-card';
import Actions from './actions';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import CreatingCard from '../creating-card';
import { ERROR_SLOTS } from '~/data/constants';

/**
 * Clicking on the button to connect an existing Google Merchant Center account.
 *
 * @event gla_mc_account_connect_button_click
 * @property {number} id The account ID to be connected.
 */

/**
 * Clicking on the "Switch account" button to select a different Google Merchant Center account to connect.
 *
 * @event gla_mc_account_switch_account_button_click
 * @property {string} context (`switch-url`|`reclaim-url`) - indicate the button is clicked from which step.
 */

/**
 * ConnectMC component.
 *
 * This component renders Merchant Center connection card.
 * It is using createAccount and resultCreateAccount from the parent component.
 * @fires gla_mc_account_connect_button_click
 * @param {Object} props
 * @param {Function} props.createAccount Callback function for creating a new Merchant Center account.
 * @param {Object} props.resultCreateAccount The result of the create account request.
 * @param {string} [props.className] Additional class name to be added to the card.
 */
const ConnectMC = ( { createAccount, resultCreateAccount, className } ) => {
	const [ value, setValue ] = useState();
	const [ handleConnectMC, resultConnectMC ] = useConnectMCAccount( value );
	const {
		googleMCAccount,
		hasFinishedResolution,
		isReady: isGoogleMCReady,
		hasGoogleMCConnection,
	} = useGoogleMCAccount();

	useEffect( () => {
		if ( hasGoogleMCConnection ) {
			setValue( googleMCAccount.id );
		}
	}, [ googleMCAccount, hasGoogleMCConnection ] );

	if ( ! isGoogleMCReady ) {
		if ( resultConnectMC.response?.status === 409 ) {
			return (
				<SwitchUrlCard
					claimedUrl={ resultConnectMC.error.claimed_url }
					id={ resultConnectMC.error.id }
					message={ resultConnectMC.error.message }
					newUrl={ resultConnectMC.error.new_url }
					onSelectAnotherAccount={ resultConnectMC.reset }
				/>
			);
		}

		if (
			resultConnectMC.response?.status === 403 ||
			resultCreateAccount.response?.status === 403
		) {
			return (
				<ReclaimUrlCard
					id={
						resultConnectMC.error?.id ||
						resultCreateAccount.error?.id
					}
					onSwitchAccount={ () => {
						resultConnectMC.reset();
						resultCreateAccount.reset();
					} }
					websiteUrl={
						resultConnectMC.error?.website_url ||
						resultCreateAccount.error?.website_url
					}
				/>
			);
		}

		if (
			resultCreateAccount.loading ||
			resultCreateAccount.response?.status === 503
		) {
			return (
				<CreatingCard
					onRetry={ createAccount }
					retryAfter={ resultCreateAccount.error?.retry_after }
				/>
			);
		}
	}

	const getIndicator = () => {
		if ( ! hasFinishedResolution ) {
			return <LoadingLabel />;
		}

		if ( isGoogleMCReady ) {
			return <ConnectedIconLabel />;
		}

		if ( resultConnectMC.loading ) {
			return (
				<LoadingLabel
					text={ __( 'Connecting…', 'google-listings-and-ads' ) }
				/>
			);
		}

		return (
			<AppButton
				eventName="gla_mc_account_connect_button_click"
				eventProps={ { id: Number( value ) } }
				onClick={ handleConnectMC }
				isSecondary
			>
				{ __( 'Connect', 'google-listings-and-ads' ) }
			</AppButton>
		);
	};

	return (
		<AccountCard
			actions={
				<Actions
					isConnected={ hasGoogleMCConnection }
					onCreateAccount={ createAccount }
					resultConnectMC={ resultConnectMC }
					resultCreateAccount={ resultCreateAccount }
				/>
			}
			alignIndicator="toDetail"
			className={ classnames( 'gla-connect-mc-card', className ) }
			detail={
				<MerchantCenterSelect
					isConnected={ hasGoogleMCConnection }
					onChange={ setValue }
					value={ value }
				/>
			}
			errorSlots={ [ ERROR_SLOTS.GOOGLE_MC_CONNECTION_ERROR_SLOT ] }
			helper={ __(
				'Required to sync products so they show on Google.',
				'google-listings-and-ads'
			) }
			indicator={ getIndicator() }
			title={ __(
				'Connect to existing Merchant Center account',
				'google-listings-and-ads'
			) }
		/>
	);
};

export default ConnectMC;
