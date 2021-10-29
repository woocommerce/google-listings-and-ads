/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardDivider, Notice } from '@wordpress/components';
import { useState, createInterpolateElement } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAdminUrl from '.~/hooks/useAdminUrl';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import Section from '.~/wcdl/section';
import DisconnectModal, { ALL_ACCOUNTS } from '../disconnect-modal';

export default function DisconnectAccountCard( { email } ) {
	const adminUrl = useAdminUrl();
	const [ openedModal, setOpenedModal ] = useState( null );
	const handleAllDisconnected = () => {
		// Force reload WC admin page to initiate the Get Started page.
		const path = getNewPath( null, '/google/start', null );
		window.location.href = adminUrl + path;
	};

	const { disconnectGoogleAccount } = useAppDispatch();
	const [ isDisconnectingGoogle, setDisconnectingGoogle ] = useState( false );
	const handleDisconnectGoogleClick = () => {
		setDisconnectingGoogle( true );
		disconnectGoogleAccount().catch( () => {
			setDisconnectingGoogle( false );
		} );
	};

	return (
		<AccountCard appearance={ APPEARANCE.GOOGLE } description={ email }>
			<CardDivider />
			<Section.Card.Body>
				<Notice status="error" isDismissible={ false }>
					<p>
						{ createInterpolateElement(
							__(
								'This Google account, <accountEmail />, was not the Google account previously connected to this integration.',
								'google-listings-and-ads'
							),
							{
								accountEmail: <strong>{ email }</strong>,
							}
						) }
					</p>
					<p>
						{ __(
							'Thus, it doesn‘t have access to the Google Merchant Center and/or Google Ads account currently connected to this WooCommerce store.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						{ __(
							'Try connecting with a different Google account, or completely disconnect all your connected accounts.',
							'google-listings-and-ads'
						) }
					</p>
				</Notice>
			</Section.Card.Body>
			<Section.Card.Footer justify="flex-end">
				{ openedModal && (
					<DisconnectModal
						onRequestClose={ () => setOpenedModal( null ) }
						onDisconnected={ handleAllDisconnected }
						disconnectTarget={ openedModal }
					/>
				) }
				<AppButton
					isSecondary
					isDestructive
					disabled={ isDisconnectingGoogle }
					onClick={ () => setOpenedModal( ALL_ACCOUNTS ) }
				>
					{ __(
						'Disconnect all accounts',
						'google-listings-and-ads'
					) }
				</AppButton>
				<AppButton
					isPrimary
					loading={ isDisconnectingGoogle }
					onClick={ handleDisconnectGoogleClick }
				>
					{ __(
						'Try another Google account',
						'google-listings-and-ads'
					) }
				</AppButton>
			</Section.Card.Footer>
		</AccountCard>
	);
}
