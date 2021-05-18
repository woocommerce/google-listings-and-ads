/**
 * Get an array of unique IDs from a comma-separated query parameter.
 * We cannot use '@woocommerce/navigation.getIdsFromQuery' as it does not support `0` as an Id
 * https://github.com/woocommerce/woocommerce-admin/issues/6980
 *
 * @param {string} [queryString=''] string value extracted from URL.
 * @return {Array} List of IDs converted to numbers.
 */
export function getIdsFromQuery( queryString = '' ) {
	return [
		...new Set( // Return only unique ids.
			queryString
				.split( ',' )
				.map( ( id ) => parseInt( id, 10 ) )
				.filter( ( id ) => ! isNaN( id ) )
		),
	];
}
