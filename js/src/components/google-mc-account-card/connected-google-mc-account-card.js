/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
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

/**
 * Clicking on the "connect to a different Google Merchant Center account" button.
 *
 * @event gla_mc_account_connect_different_account_button_click
 */

/**
 * Renders a Google Merchant Center account card UI with connected account information.
 * It also provides a switch button that lets user connect with another account.
 *
 * @fires gla_mc_account_connect_different_account_button_click
 * @param {Object} props React props.
 * @param {{ id: number }} props.googleMCAccount A data payload object containing the user's Google Merchant Center account ID.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 * @param {boolean} [props.hideDisableProductFetch=true] Indicate whether hide the disable product fetch block at the card footer.
 */
const ConnectedGoogleMCAccountCard = ( {
	googleMCAccount,
	hideAccountSwitch = false,
	hideDisableProductFetch = false,
} ) => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchGoogleMCDisconnect, { loading: loadingGoogleMCDisconnect } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/mc/connection`,
			method: 'DELETE',
		} );

	const [
		fetchDisableNotifications,
		{ loading: loadingDisableNotifications },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/rest-api/authorize`,
		method: 'DELETE',
	} );

	const domain = new URL( getSetting( 'homeUrl' ) ).host;

	/**
	 * Event handler to switch GMC account. Upon click, it will:
	 *
	 * 1. Display a notice to indicate disconnection in progress, and advise users to wait.
	 * 2. Call API to disconnect the current connected GMC account.
	 * 3. Call API to refetch list of GMC accounts.
	 * Users may have just created a new account,
	 * and we want that new account to show up in the list.
	 * 4. Call API to refetch GMC account connection status.
	 * 5. If there is an error in the above API calls, display an error notice.
	 */
	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Disconnecting your Google Merchant Center account, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await fetchGoogleMCDisconnect();
			invalidateResolution( 'getExistingGoogleMCAccounts', [] );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to disconnect your Google Merchant Center account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}

		removeNotice( notice.id );
	};

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

	const isGoogleWPCOMAppApproved =
		googleMCAccount.wpcom_rest_api_status ===
		GOOGLE_WPCOM_APP_CONNECTED_STATUS.APPROVED;

	// Show the button if the status is "approved" and the "disable product fetch" is not hidden.
	const showDisconnectNotificationsButton =
		! hideDisableProductFetch && isGoogleWPCOMAppApproved;

	// Show the error if the status is set but is not "approved".
	const showErrorNotificationsNotice =
		googleMCAccount.wpcom_rest_api_status && ! isGoogleWPCOMAppApproved;

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
			{ isGoogleWPCOMAppApproved && (
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

			{ showFooter && (
				<Section.Card.Footer>
					{ ! hideAccountSwitch && (
						<AppButton
							isLink
							disabled={ loadingGoogleMCDisconnect }
							text={ __(
								'Or, connect to a different Google Merchant Center account',
								'google-listings-and-ads'
							) }
							eventName="gla_mc_account_connect_different_account_button_click"
							onClick={ handleSwitch }
						/>
					) }
					{ showDisconnectNotificationsButton && (
						<AppButton
							isDestructive
							isLink
							disabled={ loadingDisableNotifications }
							text={ __(
								'Disable product data fetch',
								'google-listings-and-ads'
							) }
							eventName="gla_mc_account_disconnect_wpcom_rest_api"
							onClick={ disableNotifications }
						/>
					) }
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default ConnectedGoogleMCAccountCard;
