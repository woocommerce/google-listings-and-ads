/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../../constants';
import useGoogleBusinessProfileLocations from '../../hooks/useGoogleBusinessProfileLocations';
import useConnectGoogleBusinessProfileLocation from '../../hooks/useConnectGoogleBusinessProfileLocation';
import ConnectAccountCard from '../connect-account-card';
import SingleBusinessProfileLocationNotice from './single-business-profile-location-notice';

/**
 * Clicking "Connect" or "Save" to connect a Google Business Profile location.
 *
 * @event gla_google_business_profile_location_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Renders the not-yet-connected card for when exactly one candidate location was found: a
 * "Connect" button connecting that location, and the location's address in the detail.
 * Connecting still requires this explicit click.
 *
 * @fires gla_google_business_profile_location_connect_button_click with `{ context: 'settings-business-profile' }`.
 *
 * @return {JSX.Element} The account card.
 */
export default function SingleLocationCard() {
	const { locations } = useGoogleBusinessProfileLocations();
	const { isConnecting, connectLocation } =
		useConnectGoogleBusinessProfileLocation();
	const location = locations[ 0 ];

	const handleConnectClick = () => {
		connectLocation( location.id );
	};

	return (
		<ConnectAccountCard
			indicator={
				<AppButton
					eventName="gla_google_business_profile_location_connect_button_click"
					eventProps={ {
						context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
					} }
					onClick={ handleConnectClick }
					loading={ isConnecting }
					isSecondary
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
			detail={
				<SingleBusinessProfileLocationNotice location={ location } />
			}
		/>
	);
}
