/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import useConnectGoogleBusinessProfileLocation from '../../hooks/useConnectGoogleBusinessProfileLocation';
import ConnectAccountCard from '../connect-account-card';
import MultipleBusinessProfileLocationsNotice from './multiple-business-profile-locations-notice';

/**
 * Renders the not-yet-connected card for when multiple candidate locations were found: an "Action
 * needed" badge, with the picker and its own "Save" action in the detail.
 * The picked location is this card's own state.
 *
 * @return {JSX.Element} The account card.
 */
export default function MultipleLocationsCard() {
	const [ locationId, setLocationId ] = useState();
	const { isConnecting, connectLocation } =
		useConnectGoogleBusinessProfileLocation();

	const handleSaveClick = () => {
		connectLocation( locationId );
	};

	return (
		<ConnectAccountCard
			indicator={
				<Badge intent="warning">
					{ __( 'Action needed', 'google-listings-and-ads' ) }
				</Badge>
			}
			detail={
				<MultipleBusinessProfileLocationsNotice
					locationId={ locationId }
					onLocationChange={ setLocationId }
					isConnecting={ isConnecting }
					onSaveClick={ handleSaveClick }
				/>
			}
		/>
	);
}
