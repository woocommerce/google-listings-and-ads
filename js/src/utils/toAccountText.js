/**
 * Format a Google Ads customer ID for display.
 *
 * @param {string|number} id The account ID to be formatted.
 * @return {string} The formatted account ID.
 */
export default function toAccountText( id ) {
	const rawId = String( id );

	if ( /^\d{10}$/.test( rawId ) ) {
		return `${ rawId.slice( 0, 3 ) }-${ rawId.slice( 3, 6 ) }-${ rawId.slice( 6 ) }`;
	}

	return rawId;
}
