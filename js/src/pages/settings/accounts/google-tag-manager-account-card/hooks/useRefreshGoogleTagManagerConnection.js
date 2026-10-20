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
 * Clicking "Learn more" on the snackbar shown when the selected container already contains a
 * Google Ads conversion tag, so the Google Tag Manager snippet wasn't added.
 *
 * @event gla_google_tag_manager_ads_conflict_snackbar_learn_more_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * A hook returning a function that refreshes the Google Tag Manager connection and snippet settings
 * after a container was connected, records the connection and shows a snackbar when a detected
 * Google Ads conflict turned the snippet off.
 *
 * The refreshed connection is returned because the connection of the render that calls it still
 * holds the state from before the refresh.
 *
 * @fires gla_google_tag_manager_container_connected
 * @fires gla_google_tag_manager_injection_failure
 * @fires gla_google_tag_manager_ads_conflict_snackbar_learn_more_click
 *
 * @return {{ refreshConnection: () => Promise<Object|undefined>, loading: boolean }} An object with
 * `refreshConnection`, which resolves with the refreshed connection, or `undefined` when it couldn't
 * be loaded, and `loading`, which is `true` while a refresh is in progress.
 */
export default function useRefreshGoogleTagManagerConnection() {
	const { fetchGoogleTagManagerAccount, fetchGoogleTagManagerSettings } =
		useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
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

		if (
			refreshedAccount?.adsConversionConflict &&
			settingsAction?.settings?.snippetInjectionEnabled === false
		) {
			const handleClick = () => {
				recordGlaEvent(
					'gla_google_tag_manager_ads_conflict_snackbar_learn_more_click',
					{ context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT }
				);
				getHistory().push(
					getSettingsUrl( {
						section: SETTINGS_SECTIONS.GENERAL,
					} )
				);
			};

			createNotice(
				'warning',
				__(
					"The Google Tag Manager snippet wasn't added to your site due to a conflict with existing Google Ads tracking.",
					'google-listings-and-ads'
				),
				{
					type: 'snackbar',
					explicitDismiss: true,
					actions: [
						{
							label: __(
								'Learn more',
								'google-listings-and-ads'
							),
							onClick: handleClick,
						},
					],
				}
			);
		}

		return refreshedAccount;
	}, [
		fetchGoogleTagManagerAccount,
		fetchGoogleTagManagerSettings,
		createNotice,
	] );

	return { refreshConnection, loading };
}
