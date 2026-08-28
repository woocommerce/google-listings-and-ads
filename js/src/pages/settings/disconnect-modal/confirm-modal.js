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
import { ALL_ACCOUNTS, ADS_ONLY, YOUTUBE_ACCOUNT } from './constants';

const textDict = {
	[ ALL_ACCOUNTS ]: {
		title: __( 'Disconnect all accounts?', 'google-listings-and-ads' ),
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
				'Your WordPress.com, Google, Merchant Center, and Google Ads accounts will be disconnected from your WooCommerce store. Some settings created in WooCommerce may be lost and can’t be restored.',
				'google-listings-and-ads'
			),
			__(
				'Your product listings will keep showing on Google, and your campaigns will keep running. To pause, edit, or delete them, go to Google Merchant Center (merchants.google.com) and Google Ads (ads.google.com).',
				'google-listings-and-ads'
			),
		],
	},

	[ ADS_ONLY ]: {
		title: __( 'Disconnect all accounts?', 'google-listings-and-ads' ),
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
				'Your WordPress.com, Google, and Google Ads accounts will be disconnected from your WooCommerce store. Some settings created in WooCommerce may be lost and can’t be restored.',
				'google-listings-and-ads'
			),
			__(
				'Your campaigns will keep running. To pause, edit, or delete them, go to Google Ads (ads.google.com).',
				'google-listings-and-ads'
			),
		],
	},

	[ YOUTUBE_ACCOUNT ]: {
		title: __( 'Disconnect YouTube account?', 'google-listings-and-ads' ),
		confirmButton: __(
			'Disconnect YouTube account',
			'google-listings-and-ads'
		),
		confirmation: __(
			'Yes, I want to disconnect my YouTube account.',
			'google-listings-and-ads'
		),
		contents: [
			__(
				'Your YouTube account will be disconnected from your WooCommerce store.',
				'google-listings-and-ads'
			),
			__(
				'Your products will no longer be promoted on YouTube through Shopping ads. You can reconnect at any time.',
				'google-listings-and-ads'
			),
		],
	},
};

/**
 * Clicking on the button to disconnect the YouTube account.
 *
 * @event gla_youtube_account_disconnect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Renders the disconnect confirmation modal.
 *
 * @fires gla_youtube_account_disconnect_button_click When the user confirms the disconnection of the YouTube account.
 *
 * @param {Object} props Component props.
 * @param {string} props.disconnectTarget Which accounts the modal disconnects.
 * @param {() => void} props.onRequestClose Called when the modal is dismissed.
 * @param {() => void} props.onDisconnected Called after a successful disconnection.
 * @param {() => Promise<*>} [props.disconnectAction] Overrides the disconnect action inferred from `disconnectTarget`.
 * @return {JSX.Element} The confirmation modal.
 */
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
	if ( disconnectTarget === YOUTUBE_ACCOUNT ) {
		targetTextDict = YOUTUBE_ACCOUNT;
	} else if ( disconnectTarget === ALL_ACCOUNTS && ! hasGoogleMCConnection ) {
		targetTextDict = ADS_ONLY;
	}

	const { title, confirmButton, confirmation, contents } =
		textDict[ targetTextDict ];

	const isYouTubeTarget = disconnectTarget === YOUTUBE_ACCOUNT;

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		let disconnect;
		if ( disconnectTarget === ALL_ACCOUNTS ) {
			disconnect = dispatcher.disconnectAllAccounts;
		} else if ( disconnectTarget === YOUTUBE_ACCOUNT ) {
			disconnect = dispatcher.disconnectYouTubeAccount;
		} else {
			disconnect = dispatcher.disconnectGoogleAdsAccount;
		}

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
			buttons={ [
				<AppButton
					disabled={ isDisconnecting }
					key="1"
					onClick={ handleRequestClose }
					isSecondary
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					disabled={ ! isAgreed }
					eventName={
						isYouTubeTarget
							? 'gla_youtube_account_disconnect_button_click'
							: undefined
					}
					eventProps={ { context: 'settings-youtube' } }
					key="2"
					loading={ isDisconnecting }
					onClick={ handleConfirmClick }
					isDestructive
					isPrimary
				>
					{ confirmButton }
				</AppButton>,
			] }
			className="gla-disconnect-accounts-modal"
			isDismissible={ ! isDisconnecting }
			onRequestClose={ handleRequestClose }
			title={
				<>
					<WarningIcon size={ 20 } />
					{ title }
				</>
			}
		>
			{ contents.map( ( text, idx ) => (
				<p key={ idx }>{ text }</p>
			) ) }
			<CheckboxControl
				checked={ isAgreed }
				disabled={ isDisconnecting }
				label={ confirmation }
				onChange={ setAgreed }
			/>
		</AppModal>
	);
}
