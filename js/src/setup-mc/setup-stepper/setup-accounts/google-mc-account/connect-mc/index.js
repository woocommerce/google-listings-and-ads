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
import CreateAccountButton from './create-account-button';
import useConnectMCAccount from '../useConnectMCAccount';
import './index.scss';

const ConnectMC = ( props ) => {
	const { onCreateNew = () => {} } = props;
	const [ value, setValue ] = useState();
	const [
		handleConnectClick,
		{ loading, error, response, reset },
	] = useConnectMCAccount( value );

	const handleSelectAnotherAccount = () => {
		reset();
	};

	if ( response && response.status === 409 ) {
		return (
			<SwitchUrlCard
				id={ error.id }
				message={ error.message }
				claimedUrl={ error.claimed_url }
				newUrl={ error.new_url }
				onSelectAnotherAccount={ handleSelectAnotherAccount }
			/>
		);
	}

	if ( response && response.status === 403 ) {
		return (
			<ReclaimUrlCard
				id={ error.id }
				websiteUrl={ error.website_url }
				onSwitchAccount={ handleSelectAnotherAccount }
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
						loading={ loading }
						disabled={ ! value }
						eventName="gla_mc_account_connect_button_click"
						onClick={ handleConnectClick }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<CreateAccountButton
					isLink
					disabled={ loading }
					onCreateAccount={ onCreateNew }
				/>
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectMC;
