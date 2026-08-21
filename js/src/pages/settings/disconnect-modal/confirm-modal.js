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
import {
	ALL_ACCOUNTS,
	ADS_ONLY,
	YOUTUBE_ACCOUNT,
	SEARCH_CONSOLE_ACCOUNT,
} from './constants';

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

	[ SEARCH_CONSOLE_ACCOUNT ]: {
		title: __(
			'Disconnect Google Search Console account?',
			'google-listings-and-ads'
		),
		confirmButton: __(
			'Disconnect Google Search Console account',
			'google-listings-and-ads'
		),
		confirmation: __(
			'Yes, I want to disconnect my Google Search Console account.',
			'google-listings-and-ads'
		),
		contents: [
			__(
				'Your Google Search Console account will be disconnected from your WooCommerce store.',
				'google-listings-and-ads'
			),
			__(
				'Your organic search data will no longer be available in Reports. You can reconnect at any time.',
				'google-listings-and-ads'
			),
		],
	},
};

/**
 * Tracking event fired when the user confirms disconnecting a specific account, keyed by
 * disconnect target. Targets with no entry here (e.g. the all-accounts/Ads-only bulk actions)
 * fire no tracking event.
 */
const disconnectEventsByTarget = {
	[ YOUTUBE_ACCOUNT ]: {
		eventName: 'gla_youtube_account_disconnect_button_click',
		eventProps: { context: 'settings-youtube' },
	},
	[ SEARCH_CONSOLE_ACCOUNT ]: {
		eventName: 'gla_google_search_console_account_disconnect_button_click',
		eventProps: { context: 'settings-search-console' },
	},
};

/**
 * Dispatcher action name to call on confirm, keyed by disconnect target. Any unmapped target
 * (i.e. the Ads-only bulk action) falls back to disconnecting Google Ads.
 */
const disconnectActionNameByTarget = {
	[ ALL_ACCOUNTS ]: 'disconnectAllAccounts',
	[ YOUTUBE_ACCOUNT ]: 'disconnectYouTubeAccount',
	[ SEARCH_CONSOLE_ACCOUNT ]: 'disconnectGoogleSearchConsoleAccount',
};

/**
 * Clicking on the button to disconnect the YouTube account.
 *
 * @event gla_youtube_account_disconnect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Clicking on the button to disconnect the Google Search Console account.
 *
 * @event gla_google_search_console_account_disconnect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the disconnect confirmation modal.
 *
 * @fires gla_youtube_account_disconnect_button_click When the user confirms the disconnection of the YouTube account.
 * @fires gla_google_search_console_account_disconnect_button_click When the user confirms the disconnection of the Google Search Console account.
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
	} else if ( disconnectTarget === SEARCH_CONSOLE_ACCOUNT ) {
		targetTextDict = SEARCH_CONSOLE_ACCOUNT;
	} else if ( disconnectTarget === ALL_ACCOUNTS && ! hasGoogleMCConnection ) {
		targetTextDict = ADS_ONLY;
	}

	const { title, confirmButton, confirmation, contents } =
		textDict[ targetTextDict ];

	const { eventName, eventProps } =
		disconnectEventsByTarget[ disconnectTarget ] ?? {};

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		const disconnect =
			disconnectAction ??
			dispatcher[
				disconnectActionNameByTarget[ disconnectTarget ] ??
					'disconnectGoogleAdsAccount'
			];

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
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="2"
					isPrimary
					isDestructive
					loading={ isDisconnecting }
					disabled={ ! isAgreed }
					eventName={ eventName }
					eventProps={ eventProps }
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
