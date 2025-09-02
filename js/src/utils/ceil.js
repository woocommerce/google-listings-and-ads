/**
 * Round number up with precision.
 *
 * @param {number} number The number to round up.
 * @param {number} [precision=2] An integer >= 0 as precision to round to.
 * @return {number} The rounded up number.
 */
export default function ceil( number, precision = 2 ) {
	const shift = Math.pow( 10, precision );
	return Math.ceil( number * shift ) / shift;
}
