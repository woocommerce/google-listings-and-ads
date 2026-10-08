/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';
import formatLocationAddress from './format-location-address';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders a Google Business Profile location's address, linked out to its listing on Google. The
 * link resolves to the connected Google account when its email is known, so it doesn't open under
 * whichever Google account happens to be active in the browser. Without a listing URL, the address
 * is shown as plain text.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation} props.location The location to display.
 * @return {JSX.Element|string} The location's address, linked when possible.
 */
export default function LocationNameWithLink( { location } ) {
	const accountAwareUrl = useGoogleAccountAwareUrl( location.mapsUri );
	const address = formatLocationAddress( location );

	if ( ! location.mapsUri ) {
		return address;
	}

	return <ExternalLink href={ accountAwareUrl }>{ address }</ExternalLink>;
}
