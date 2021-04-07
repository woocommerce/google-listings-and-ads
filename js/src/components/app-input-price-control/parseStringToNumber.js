/**
 * Parse the user input price value string into floating-point number.
 *
 * This method uses similar implementation from accounting.js used in WooCommerce Admin.
 *
 * @param {string} stringValue User input price value.
 * @param {Object} currencySetting Store's currency setting.
 * @return {number} Parsed floating-point number.
 */
const parseStringToNumber = ( stringValue, currencySetting ) => {
	const { decimalSeparator } = currencySetting;

	// Build regex to strip out everything except digits, decimal point and minus sign:
	const regex = new RegExp( '[^0-9-' + decimalSeparator + ']', [ 'g' ] );
	const parsed = parseFloat(
		( '' + stringValue )
			.replace( /\((.*)\)/, '-$1' ) // replace bracketed values with negatives
			.replace( regex, '' ) // strip out any cruft
			.replace( decimalSeparator, '.' ) // make sure decimal point is standard
	);

	return ! isNaN( parsed ) ? parsed : 0;
};

export default parseStringToNumber;
