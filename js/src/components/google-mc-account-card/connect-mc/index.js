/**
 * External dependencies
 */
import { CardDivider } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import ContentButtonLayout from '.~/components/content-button-layout';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import CreateAccountButton from '../create-account-button';
import useConnectMCAccount from '.~/hooks/useConnectMCAccount';
import useCreateMCAccount from '.~/hooks/useCreateMCAccount';
import AccountConnectionStatus from '../account-connection-status';
import './index.scss';

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
 * @fires gla_mc_account_connect_button_click
 */
const ConnectMC = () => {
	const [ value, setValue ] = useState();
	const [ handleConnectMC, resultConnectMC ] = useConnectMCAccount( value );
	const [ handleCreateAccount, resultCreateAccount ] = useCreateMCAccount();

	if (
		[ 403, 409 ].includes( resultConnectMC.response?.status ) ||
		[ 403, 503 ].includes( resultCreateAccount.response?.status ) ||
		resultCreateAccount.loading
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
		<AccountCard
			className="gla-connect-mc-card"
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Connect to an existing account',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<ContentButtonLayout>
					<MerchantCenterSelectControl
						value={ value }
						onChange={ setValue }
					/>
					<AppButton
						isSecondary
						loading={ resultConnectMC.loading }
						disabled={ ! value }
						eventName="gla_mc_account_connect_button_click"
						eventProps={ { id: Number( value ) } }
						onClick={ handleConnectMC }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<CreateAccountButton
					isLink
					disabled={ resultConnectMC.loading }
					onCreateAccount={ handleCreateAccount }
				>
					{ __(
						'Or, create a new Merchant Center account',
						'google-listings-and-ads'
					) }
				</CreateAccountButton>
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectMC;
