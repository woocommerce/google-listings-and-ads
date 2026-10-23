/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import AppButton from '~/components/app-button';
import useGoogleBusinessProfileLocations from '../hooks/useGoogleBusinessProfileLocations';

/**
 * Renders the `AccountCard` `indicator` for the not-yet-connected state: a "Not connected" badge
 * while there's a connection error, an "Action needed" badge while there are zero or multiple
 * candidate locations (the action lives in the `detail` instead), or the "Connect" button when
 * exactly one candidate location exists. Nothing is shown while the locations list is loading or
 * couldn't be loaded, since the `detail` covers both.
 *
 * @param {Object} props Component props.
 * @param {boolean} props.hasConnectionError Whether the connection error slot has an error.
 * @param {string} [props.locationId] The currently picked location ID.
 * @param {boolean} props.isConnecting Whether the connect request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Connect".
 * @return {JSX.Element|null} The indicator, or `null` until the locations list has loaded.
 */
export default function Indicator( {
	hasConnectionError,
	locationId,
	isConnecting,
	onConnectClick,
} ) {
	const { locations, hasFinishedResolution } =
		useGoogleBusinessProfileLocations();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( hasConnectionError ) {
		return (
			<Badge intent="error">
				{ __( 'Not connected', 'google-listings-and-ads' ) }
			</Badge>
		);
	}

	if ( ! locations ) {
		return null;
	}

	if ( locations.length !== 1 ) {
		return (
			<Badge intent="warning">
				{ __( 'Action needed', 'google-listings-and-ads' ) }
			</Badge>
		);
	}

	return (
		<AppButton
			onClick={ onConnectClick }
			disabled={ ! locationId || isConnecting }
			loading={ isConnecting }
			isSecondary
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
}
