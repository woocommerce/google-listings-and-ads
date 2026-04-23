/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import WarningIcon from '~/components/warning-icon';
import { useAppDispatch } from '~/data';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { ALL_ACCOUNTS, ADS_ACCOUNT, ADS_ONLY } from './constants';

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
				'I understand that I am disconnecting any WordPress.com account, Google account, Google Merchant Center account and Google Ads account connected to this extension.',
				'google-listings-and-ads'
			),
			__(
				'Any active product listings will continue to show on Google. They can be managed, edited, or deleted manually from Google Merchant Center (merchants.google.com).',
				'google-listings-and-ads'
			),
			__(
				'Any ongoing campaigns will continue to run. They can be managed, edited, or deleted manually from Google Ads (ads.google.com).',
				'google-listings-and-ads'
			),
		],
	},

	[ ADS_ONLY ]: {
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
				'I understand that I am disconnecting any WordPress.com account, Google account and Google Ads account connected to this extension.',
				'google-listings-and-ads'
			),
			__(
				'Any ongoing campaigns will continue to run. They can be managed, edited, or deleted manually from Google Ads (ads.google.com).',
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
				'I understand that I am disconnecting my Google Ads account from this WooCommerce extension.',
				'google-listings-and-ads'
			),
			__(
				'Any ongoing campaigns will continue to run. They can be managed, edited, or deleted manually from Google Ads (ads.google.com).',
				'google-listings-and-ads'
			),
			__(
				'Some configurations for Google Ads created through WooCommerce may be lost. This cannot be undone.',
				'google-listings-and-ads'
			),
		],
	},
};

export default function ConfirmModal( {
	disconnectTarget,
	onRequestClose,
	onDisconnected,
	disconnectAction,
} ) {
	const [ isAgreed, setAgreed ] = useState( false );
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const dispatcher = useAppDispatch();
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	let targetTextDict = ALL_ACCOUNTS;
	if ( disconnectTarget === ADS_ACCOUNT ) {
		targetTextDict = ADS_ACCOUNT;
	} else if ( disconnectTarget === ALL_ACCOUNTS && ! hasGoogleMCConnection ) {
		targetTextDict = ADS_ONLY;
	}

	const { title, confirmButton, confirmation, contents } =
		textDict[ targetTextDict ];

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		let disconnect =
			disconnectTarget === ALL_ACCOUNTS
				? dispatcher.disconnectAllAccounts
				: dispatcher.disconnectGoogleAdsAccount;

		if ( disconnectAction ) {
			disconnect = disconnectAction;
		}

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
			isDismissible={ ! isDisconnecting }
			buttons={ [
				<AppButton
					key="1"
					isSecondary
					disabled={ isDisconnecting }
					onClick={ handleRequestClose }
				>
					{ __( 'Never mind', 'google-listings-and-ads' ) }
				</AppButton>,
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
				disabled={ isDisconnecting }
				onChange={ setAgreed }
			/>
		</AppModal>
	);
}
