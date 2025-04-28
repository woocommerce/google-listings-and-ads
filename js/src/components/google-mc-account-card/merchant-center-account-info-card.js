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
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import ConnectedIconLabel from '~/components/connected-icon-label';
import Section from '~/components/section';
import { GOOGLE_WPCOM_APP_CONNECTED_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { useAppDispatch } from '~/data';
import EnableNewProductSyncButton from '~/components/enable-new-product-sync-button';
import AppNotice from '~/components/app-notice';
import DisconnectModal, {
	API_DATA_FETCH_FEATURE,
} from '~/pages/settings/disconnect-modal';
import { getSettingsUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * @typedef {import('~/data/types.js').GoogleMCAccount} GoogleMCAccount
 */

/**
 * Clicking on the button to disable the new product sync (API Pull).
 *
 * @event gla_disable_product_sync_click
 */

/**
 * Renders a Google Merchant Center account card UI with connected account information.
 *
 * @param {Object} props React props.
 * @param {GoogleMCAccount} props.googleMCAccount A data payload object of Google Merchant Center account.
 *
 * @fires gla_disable_product_sync_click
 */
const MerchantCenterAccountInfoCard = ( { googleMCAccount } ) => {
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
		recordGlaEvent( 'gla_disable_product_sync_click' );

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

	// Show the button if the status is "approved".
	const showDisconnectNotificationsButton =
		googleMCAccount.wpcom_rest_api_status ===
		GOOGLE_WPCOM_APP_CONNECTED_STATUS.APPROVED;

	// Show the error if the status is set but is not "approved".
	const showErrorNotificationsNotice =
		googleMCAccount.wpcom_rest_api_status &&
		googleMCAccount.notification_service_enabled &&
		googleMCAccount.wpcom_rest_api_status !==
			GOOGLE_WPCOM_APP_CONNECTED_STATUS.APPROVED;

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
						eventProps={ { page: 'settings', context: 'mc_card' } }
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

			{ showDisconnectNotificationsButton && (
				<Section.Card.Footer>
					<AppButton
						isDestructive
						isLink
						disabled={ loadingDisableNotifications }
						text={ __(
							'Disable product data fetch',
							'google-listings-and-ads'
						) }
						onClick={ openDisableDataFetchModal }
					/>
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default MerchantCenterAccountInfoCard;
