/**
 * Extremely naive shim for the native `structuredClone`,
 * to be used in tests before we update to Node 17.0.
 *
 * @param {*} value Value to be cloned.
 * @return {*} Deeply cloned value.
 */
export function structuredClone( value ) {
	return JSON.parse( JSON.stringify( value ) );
}
