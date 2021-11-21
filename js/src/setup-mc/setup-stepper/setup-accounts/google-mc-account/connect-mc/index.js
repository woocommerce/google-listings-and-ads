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
import SwitchUrlCard from '../switch-url-card';
import ReclaimUrlCard from '../reclaim-url-card';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import CreateAccountButton from '../create-account-button';
import useConnectMCAccount from '../useConnectMCAccount';
import useCreateMCAccount from '../useCreateMCAccount';
import CreatingCard from '../creating-card';
import './index.scss';

const ConnectMC = () => {
	const [ value, setValue ] = useState();
	const [ handleConnectMC, resultConnectMC ] = useConnectMCAccount( value );
	const [ handleCreateAccount, resultCreateAccount ] = useCreateMCAccount();

	if ( resultConnectMC.response?.status === 409 ) {
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
		resultConnectMC.response?.status === 403 ||
		resultCreateAccount.response?.status === 403
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
		resultCreateAccount.loading ||
		resultCreateAccount.response?.status === 503
	) {
		return (
			<CreatingCard
				retryAfter={ resultCreateAccount.error?.retry_after }
				onRetry={ handleCreateAccount }
			/>
		);
	}

	return (
		<AccountCard
			className="gla-connect-mc-card"
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ __(
				'Required to sync products and list on Google Shopping',
				'google-listings-and-ads'
			) }
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Select an existing account',
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
						onClick={ handleConnectMC }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<CreateAccountButton
					isLink
					isSecondary
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
