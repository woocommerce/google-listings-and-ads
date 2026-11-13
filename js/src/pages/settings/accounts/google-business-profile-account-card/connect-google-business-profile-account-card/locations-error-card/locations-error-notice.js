/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../../constants';
import NoticeDetail from '../../notice-detail';

/**
 * Clicking "Try again" after the Google Business Profile locations couldn't be loaded.
 *
 * @event gla_google_business_profile_locations_try_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Renders the notice shown when the Google Business Profile locations couldn't be loaded, with a
 * "Try again" refetch.
 *
 * @fires gla_google_business_profile_locations_try_again_button_click with `{ context: 'settings-business-profile' }`.
 *
 * @return {JSX.Element} The notice.
 */
export default function LocationsErrorNotice() {
	const { fetchGoogleBusinessProfileLocations } = useAppDispatch();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	const handleTryAgainClick = async () => {
		setIsRefreshing( true );
		await fetchGoogleBusinessProfileLocations();
		setIsRefreshing( false );
	};

	return (
		<NoticeDetail
			status="error"
			body={
				<p>
					{ __(
						"We couldn't load your Google Business Profile locations.",
						'google-listings-and-ads'
					) }
				</p>
			}
			actions={ [
				<AppButton
					key="try-again"
					eventName="gla_google_business_profile_locations_try_again_button_click"
					eventProps={ {
						context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
					} }
					onClick={ handleTryAgainClick }
					loading={ isRefreshing }
					isSecondary
				>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
