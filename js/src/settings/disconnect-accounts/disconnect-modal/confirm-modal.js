/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import WarningIcon from '.~/components/warning-icon';
import { useAppDispatch } from '.~/data';
import { ALL_ACCOUNTS, ADS_ACCOUNT } from '../constants';

const textDict = {
	[ ALL_ACCOUNTS ]: {
		title: __( 'Disconnect all accounts', 'google-listings-and-ads' ),
		confirmButton: __(
			'Disconnect all accounts',
			'google-listings-and-ads'
		),
		confirmation: __(
			'Yes, I want to disconnect all my accounts.',
			'google-listings-and-ads'
		),
		contents: [
			__(
				'I understand that I am disconnecting my WordPress.com account, Google account, Google Merchant Center account and Google Ads account from this extension.',
				'google-listings-and-ads'
			),
			__(
				'My Product Feed will also be disconnected from my Google Merchant Centre account. Any free listings and/or paid campaigns will be paused.',
				'google-listings-and-ads'
			),
			__(
				'All my configurations for the Google Listings & Ads extension in WooCommerce will be deleted. This cannot be undone.',
				'google-listings-and-ads'
			),
		],
	},

	[ ADS_ACCOUNT ]: {
		title: __( 'Disconnect Google Ads account', 'google-listings-and-ads' ),
		confirmButton: __(
			'Disconnect Google Ads Account',
			'google-listings-and-ads'
		),
		confirmation: __(
			'Yes, I want to disconnect my Google Ads account.',
			'google-listings-and-ads'
		),
		contents: [
			__(
				'I understand that I am disconnecting my Google Ads account.',
				'google-listings-and-ads'
			),
			__(
				'Any paid campaigns will be paused.',
				'google-listings-and-ads'
			),
			__(
				'All my configurations for Google Ads specific to WooCommerce will be deleted. This cannot be undone.',
				'google-listings-and-ads'
			),
		],
	},
};

export default function ConfirmModal( {
	disconnectTarget,
	onRequestClose,
	onDisconnected,
} ) {
	const [ isAgreed, setAgreed ] = useState( false );
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const dispatcher = useAppDispatch();

	const { title, confirmButton, confirmation, contents } = textDict[
		disconnectTarget
	];

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		const disconnect =
			disconnectTarget === ALL_ACCOUNTS
				? dispatcher.disconnectAllAccounts
				: dispatcher.disconnectGoogleAdsAccount;

		setDisconnecting( true );
		disconnect()
			.then( () => {
				onDisconnected();
				onRequestClose();
			} )
			.catch( () => {
				setDisconnecting( false );
			} );
	};

	return (
		<AppModal
			className="gla-disconnect-accounts-modal"
			title={
				<>
					<WarningIcon size={ 20 } />
					{ title }
				</>
			}
			buttons={ [
				<Button
					key="1"
					isSecondary
					disabled={ isDisconnecting }
					onClick={ handleRequestClose }
				>
					{ __( 'Never mind', 'google-listings-and-ads' ) }
				</Button>,
				<AppButton
					key="2"
					isPrimary
					isDestructive
					loading={ isDisconnecting }
					disabled={ ! isAgreed }
					onClick={ handleConfirmClick }
				>
					{ confirmButton }
				</AppButton>,
			] }
			onRequestClose={ handleRequestClose }
		>
			{ contents.map( ( text, idx ) => (
				<p key={ idx }>{ text }</p>
			) ) }
			<CheckboxControl
				label={ confirmation }
				checked={ isAgreed }
				onChange={ setAgreed }
			/>
		</AppModal>
	);
}
