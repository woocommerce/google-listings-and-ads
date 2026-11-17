/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import {
	CONNECTION_ERROR_SLOTS,
	GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
} from '../constants';
import { useAppDispatch } from '~/data';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import NoticeDetail from '../notice-detail';
import useConnectGoogleBusinessProfileLocation from '../hooks/useConnectGoogleBusinessProfileLocation';

/**
 * Clicking "Try again" after connecting a Google Business Profile location failed.
 *
 * @event gla_google_business_profile_connection_try_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 * @property {string} retry_type Which step is retried. Possible values: 'connect' (falls back to the location picker), 'status_refresh' (refreshes the status of an already-connected location).
 */

/**
 * Renders the connection-error notice shown in the `AccountCard` error area when connecting a
 * Google Business Profile location failed. "Try again" retries only the status refresh when the
 * connect request itself already succeeded and only confirming it failed -- repeating the connect
 * request in that case gets rejected by the backend, since the location is already connected.
 * Otherwise it just clears the error slot, falling back to the location picker for a fresh attempt.
 *
 * `null` when the store has no error for the connection error slot.
 *
 * @fires gla_google_business_profile_connection_try_again_button_click with `{ context: 'settings-business-profile', retry_type: 'connect' | 'status_refresh' }`.
 *
 * @return {JSX.Element|null} The notice, or `null` when there's no connection error.
 */
export default function ConnectionErrorNotice() {
	const [ detailedError ] = useDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	const { clearDetailedErrorBySlots } = useAppDispatch();
	const { isConnecting, retryStatusRefresh } =
		useConnectGoogleBusinessProfileLocation();

	if ( ! detailedError ) {
		return null;
	}

	const apiMessage = detailedError.error?.message;
	const refreshFailedAfterConnecting =
		detailedError.error?.refreshFailedAfterConnecting;

	const handleTryAgainClick = () => {
		if ( refreshFailedAfterConnecting ) {
			retryStatusRefresh();
			return;
		}

		clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	};

	return (
		<NoticeDetail
			status="error"
			title={ __(
				"We couldn't connect Google Business Profile",
				'google-listings-and-ads'
			) }
			body={
				<p>
					{ apiMessage ||
						__(
							"Something went wrong. Check that you're signed in to the right Google account, then try again.",
							'google-listings-and-ads'
						) }
				</p>
			}
			actions={ [
				<AppButton
					key="try-again"
					eventName="gla_google_business_profile_connection_try_again_button_click"
					eventProps={ {
						context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
						retry_type: refreshFailedAfterConnecting
							? 'status_refresh'
							: 'connect',
					} }
					onClick={ handleTryAgainClick }
					loading={ refreshFailedAfterConnecting && isConnecting }
					isSecondary
				>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
