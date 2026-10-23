/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Gets the text shown for a Google Business Profile location: its one-line address, or the
 * business name when the location has no address.
 *
 * @param {GoogleBusinessProfileLocation} location The location.
 * @return {string} The address, or the business name.
 */
export default function formatLocationAddress( location ) {
	return location?.address || location?.title || '';
}
