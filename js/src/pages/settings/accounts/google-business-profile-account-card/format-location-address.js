/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Formats a Google Business Profile location's address on one line, e.g.
 * "2423 1st Ave, Seattle, WA 98121, US". Falls back to the business name when the location has no
 * storefront address.
 *
 * @param {GoogleBusinessProfileLocation} location The location to format.
 * @return {string} The formatted address.
 */
export default function formatLocationAddress( location ) {
	const address = location?.storefrontAddress;

	if ( ! address ) {
		return location?.title ?? '';
	}

	const regionAndPostalCode = [
		address.administrativeArea,
		address.postalCode,
	]
		.filter( Boolean )
		.join( ' ' );

	return [
		...( address.addressLines ?? [] ),
		address.locality,
		regionAndPostalCode,
		address.regionCode,
	]
		.filter( Boolean )
		.join( ', ' );
}
