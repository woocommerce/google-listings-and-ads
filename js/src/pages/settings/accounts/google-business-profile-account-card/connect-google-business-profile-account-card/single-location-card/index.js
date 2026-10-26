/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import ConnectAccountCard from '../connect-account-card';
import SingleBusinessProfileLocationNotice from './single-business-profile-location-notice';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders the not-yet-connected card for when exactly one candidate location was found: a
 * "Connect" button (already enabled — the single candidate is auto-selected) and the location's
 * address in the detail. Connecting still requires this explicit click.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation} props.location The single location found.
 * @param {string} [props.locationId] The currently picked location ID.
 * @param {boolean} props.isConnecting Whether the connect request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Connect".
 * @return {JSX.Element} The account card.
 */
export default function SingleLocationCard( {
	location,
	locationId,
	isConnecting,
	onConnectClick,
} ) {
	return (
		<ConnectAccountCard
			indicator={
				<AppButton
					onClick={ onConnectClick }
					disabled={ ! locationId }
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
