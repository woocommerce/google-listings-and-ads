/**
 * Polyfill of `Number.isNaN`.
 *
 * @param {*} value The value to check.
 * @return {boolean} Whether the value is `NaN`.
 */
export default function isNaN( value ) {
	return value !== value;
}
