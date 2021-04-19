/**
 * Round number with precision.
 *
 * @param {number} number The number to round.
 * @param {number} [precision=2] An integer >= 1 as precision to round to.
 * @return {number} The rounded number.
 */
export default function round( number, precision = 2 ) {
	const shift = Math.pow( 10, precision );
	return Math.round( number * shift ) / shift;
}
