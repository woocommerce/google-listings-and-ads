/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '../constants';
import {
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS,
	SETTINGS_SECTIONS,
} from '~/constants';
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import { recordGlaEvent } from '~/utils/tracks';
import { getSettingsUrl } from '~/utils/urls';

/**
 * A Google Tag Manager container has been connected.
 *
 * @event gla_google_tag_manager_container_connected
 * @property {string} context Indicates from which page the container was connected. Possible value: 'settings-tag-manager'.
 * @property {string} gtm_account_id The connected Google Tag Manager account ID.
 */

/**
 * A connected Google Tag Manager container has no public ID, so its snippet can't be injected.
 *
 * @event gla_google_tag_manager_injection_failure
 * @property {string} context Indicates from which page the container was connected. Possible value: 'settings-tag-manager'.
 * @property {string} gtm_account_id The connected Google Tag Manager account ID.
 */

/**
 * Opens the Google Tag Manager snippet setting in Settings > General.
 */
const openGeneralSettings = () => {
	getHistory().push(
		getSettingsUrl( { section: SETTINGS_SECTIONS.GENERAL } )
	);
};

/**
 * A hook returning a function that refreshes the Google Tag Manager connection and snippet settings
 * after a container was connected, records the connection, reloads the Google Ads connection, and
 * shows a snackbar either confirming the snippet was added, or explaining that the plugin doesn't
 * add its own Google Ads tag because the container already has one.
 *
 * The refreshed connection is returned because the connection of the render that calls it still
 * holds the state from before the refresh.
 *
 * @fires gla_google_tag_manager_container_connected
 * @fires gla_google_tag_manager_injection_failure
 *
 * @return {{ refreshConnection: () => Promise<Object|undefined>, loading: boolean }} An object with
 * `refreshConnection`, which resolves with the refreshed connection, or `undefined` when it couldn't
 * be loaded, and `loading`, which is `true` while a refresh is in progress.
 */
export default function useRefreshGoogleTagManagerConnection() {
	const {
		fetchGoogleTagManagerAccount,
		fetchGoogleTagManagerSettings,
		fetchGoogleAdsAccount,
	} = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { hasGoogleAdsConnection } = useGoogleAdsAccount();
	const [ loading, setLoading ] = useState( false );

	const refreshConnection = useCallback( async () => {
		setLoading( true );

		let accountAction;
		let settingsAction;

		try {
			[ accountAction, settingsAction ] = await Promise.all( [
				fetchGoogleTagManagerAccount(),
				fetchGoogleTagManagerSettings(),
			] );
		} finally {
			setLoading( false );
		}

		const refreshedAccount = accountAction?.account;

		// Nothing is recorded unless the refreshed state reports a connected container, so a failed
		// refresh never counts as a connection.
		if (
			refreshedAccount?.status ===
			GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED
		) {
			const eventProps = {
				context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
				gtm_account_id: String( refreshedAccount.id ?? '' ),
			};

			recordGlaEvent(
				'gla_google_tag_manager_container_connected',
				eventProps
			);

			if ( refreshedAccount.injectionFailed ) {
				recordGlaEvent(
					'gla_google_tag_manager_injection_failure',
					eventProps
				);
			}
		}

		const isConnected =
			refreshedAccount?.status ===
			GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED;

		// The plugin only has its own Google Ads tag to leave out while an Ads account is connected.
		const leavesOutAdsTag =
			isConnected &&
			hasGoogleAdsConnection &&
			Boolean( refreshedAccount.adsConversionConflict );

		if ( isConnected && hasGoogleAdsConnection ) {
			// Whether the plugin adds its own Google Ads tag depends on the connected container.
			// Fetched, not invalidated (see `disconnectGoogleTagManagerAccount`).
			fetchGoogleAdsAccount();
		}

		if ( leavesOutAdsTag ) {
			createNotice(
				'info',
				__(
					"Your Google Tag Manager container already has a Google Ads tag, so Google for WooCommerce doesn't add its own.",
					'google-listings-and-ads'
				),
				{ type: 'snackbar' }
			);
		}

		if (
			isConnected &&
			! leavesOutAdsTag &&
			! refreshedAccount.injectionFailed &&
			settingsAction?.settings?.snippetInjectionEnabled === true
		) {
			createNotice(
				'success',
				__(
					'The Google Tag Manager snippet has been successfully added to your site.',
					'google-listings-and-ads'
				),
				{
					type: 'snackbar',
					actions: [
						{
							label: __( 'View', 'google-listings-and-ads' ),
							onClick: openGeneralSettings,
						},
					],
				}
			);
		}

		return refreshedAccount;
	}, [
		fetchGoogleTagManagerAccount,
		fetchGoogleTagManagerSettings,
		fetchGoogleAdsAccount,
		createNotice,
		hasGoogleAdsConnection,
	] );

	return { refreshConnection, loading };
}
