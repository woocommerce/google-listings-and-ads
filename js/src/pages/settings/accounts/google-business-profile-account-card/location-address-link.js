/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';

/**
 * Renders a location's address, linked to its listing on Google when it has a listing URL.
 *
 * @param {Object} props Component props.
 * @param {string} props.address The address to display.
 * @param {string} [props.url] The account-aware URL to the listing on Google, if it has one.
 * @return {JSX.Element|string} The linked address, or the plain address when there's no listing URL.
 */
export default function LocationAddressLink( { address, url } ) {
	if ( ! url ) {
		return address;
	}

	return <ExternalLink href={ url }>{ address }</ExternalLink>;
}
