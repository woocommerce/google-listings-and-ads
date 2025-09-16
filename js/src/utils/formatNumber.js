/**
 * Formats a number into a compact, human-readable string using locale-specific formatting.
 * For example, 1500 becomes "1.5k" and 2000000 becomes "2M".
 * Falls back to manual formatting if Intl.NumberFormat is unavailable.
 *
 * @param {number} num - The number to format.
 * @return {string} The formatted number as a string.
 */
export default function formatNumber( num ) {
	if ( typeof Intl !== 'undefined' && Intl.NumberFormat ) {
		try {
			return new Intl.NumberFormat( 'en', {
				notation: 'compact',
				compactDisplay: 'short',
			} ).format( num );
		} catch ( e ) {}
	}

	// Fallback approach
	if ( num >= 1000000 ) {
		return ( num / 1000000 ).toFixed( 1 ).replace( '.0', '' ) + 'M';
	}

	if ( num >= 1000 ) {
		return ( num / 1000 ).toFixed( 1 ).replace( '.0', '' ) + 'k';
	}

	return num.toString();
}
