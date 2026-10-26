/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import ConnectAccountCard from '../connect-account-card';
import MultipleBusinessProfileLocationsNotice from './multiple-business-profile-locations-notice';

/**
 * Renders the not-yet-connected card for when multiple candidate locations were found: an "Action
 * needed" badge, with the picker and its own "Save" action in the detail.
 *
 * @param {Object} props Component props.
 * @param {string} [props.locationId] The currently picked location ID.
 * @param {( locationId: string ) => void} props.onLocationChange Callback when the picked location changes.
 * @param {boolean} props.isConnecting Whether the connect request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Save".
 * @return {JSX.Element} The account card.
 */
export default function MultipleLocationsCard( {
	locationId,
	onLocationChange,
	isConnecting,
	onConnectClick,
} ) {
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
					onLocationChange={ onLocationChange }
					isConnecting={ isConnecting }
					onConnectClick={ onConnectClick }
				/>
			}
		/>
	);
}
