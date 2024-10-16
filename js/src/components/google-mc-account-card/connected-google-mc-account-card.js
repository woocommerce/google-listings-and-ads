/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import Section from '.~/wcdl/section';
import { GOOGLE_WPCOM_APP_CONNECTED_STATUS } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import EnableNewProductSyncButton from '.~/components/enable-new-product-sync-button';
import AppNotice from '.~/components/app-notice';
import DisconnectAccountButton from './disconnect-account-button';
import DisconnectModal, {
	API_DATA_FETCH_FEATURE,
} from '.~/settings/disconnect-modal';
import { getSettingsUrl } from '.~/utils/urls';

/**
 * Renders a Google Merchant Center account card UI with connected account information.
 * It also provides a switch button that lets user connect with another account.
 *
 * @param {Object} props React props.
 * @param {{ id: number }} props.googleMCAccount A data payload object containing the user's Google Merchant Center account ID.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 * @param {boolean} [props.hideNotificationService=true] Indicate whether hide the enable Notification service block at the card footer.
 */
const ConnectedGoogleMCAccountCard = ( {
	googleMCAccount,
	hideAccountSwitch = false,
	hideNotificationService = false,
} ) => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [
		fetchDisableNotifications,
		{ loading: loadingDisableNotifications },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/rest-api/authorize`,
		method: 'DELETE',
	} );

	/**
	 * Temporary code for disabling the API PULL Beta Feature from the GMC Card
	 */
	const [ openedModal, setOpenedModal ] = useState( null );
	const dismissModal = () => setOpenedModal( null );
	const openDisableDataFetchModal = () =>
		setOpenedModal( API_DATA_FETCH_FEATURE );

	const domain = new URL( getSetting( 'homeUrl' ) ).host;

	const disableNotifications = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Disabling the new Product Sync feature, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await fetchDisableNotifications();
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to disable new product sync. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}

		removeNotice( notice.id );
	};

	// Show the button if the status is "approved" and the Notification Service is not hidden.
	const showDisconnectNotificationsButton =
		! hideNotificationService &&
		googleMCAccount.wpcom_rest_api_status ===
			GOOGLE_WPCOM_APP_CONNECTED_STATUS.APPROVED;

	// Show the error if the status is set but is not "approved" and the Notification Service is not hidden.
	const showErrorNotificationsNotice =
		! hideNotificationService &&
		googleMCAccount.wpcom_rest_api_status &&
		googleMCAccount.notification_service_enabled &&
		googleMCAccount.wpcom_rest_api_status !==
			GOOGLE_WPCOM_APP_CONNECTED_STATUS.APPROVED;

	const showFooter = ! hideAccountSwitch || showDisconnectNotificationsButton;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ sprintf(
				// translators: 1: account domain, 2: account ID.
				__( '%1$s (%2$s)', 'google-listings-and-ads' ),
				domain,
				googleMCAccount.id
			) }
			indicator={
				showErrorNotificationsNotice ? (
					<EnableNewProductSyncButton
						text={ __( 'Grant access', 'google-listings-and-ads' ) }
						eventName="gla_enable_product_sync_click"
						eventProps={ { context: 'mc_card' } }
					/>
				) : (
					<ConnectedIconLabel />
				)
			}
		>
			{ showDisconnectNotificationsButton && (
				<AppNotice status="success" isDismissible={ false }>
					{ __(
						'Google has been granted access to fetch your product data.',
						'google-listings-and-ads'
					) }
				</AppNotice>
			) }

			{ showErrorNotificationsNotice && (
				<AppNotice status="warning" isDismissible={ false }>
					{ __(
						'There was an issue granting access to Google for fetching your products.',
						'google-listings-and-ads'
					) }
				</AppNotice>
			) }

			{ openedModal && (
				<DisconnectModal
					onRequestClose={ dismissModal }
					onDisconnected={ () => {
						window.location.href = getSettingsUrl();
					} }
					disconnectTarget={ openedModal }
					disconnectAction={ disableNotifications }
				/>
			) }

			{ showFooter && (
				<Section.Card.Footer>
					{ ! hideAccountSwitch && <DisconnectAccountButton /> }

					{ showDisconnectNotificationsButton && (
						<AppButton
							isDestructive
							isLink
							disabled={ loadingDisableNotifications }
							text={ __(
								'Disable product data fetch',
								'google-listings-and-ads'
							) }
							eventName="gla_disable_product_sync_click"
							onClick={ openDisableDataFetchModal }
						/>
					) }
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default ConnectedGoogleMCAccountCard;
