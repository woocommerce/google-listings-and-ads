/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useGoogleBusinessProfileLocations from '../../hooks/useGoogleBusinessProfileLocations';
import useConnectGoogleBusinessProfileLocation from '../../hooks/useConnectGoogleBusinessProfileLocation';
import ConnectAccountCard from '../connect-account-card';
import SingleBusinessProfileLocationNotice from './single-business-profile-location-notice';

/**
 * Renders the not-yet-connected card for when exactly one candidate location was found: a
 * "Connect" button connecting that location, and the location's address in the detail.
 * Connecting still requires this explicit click.
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
