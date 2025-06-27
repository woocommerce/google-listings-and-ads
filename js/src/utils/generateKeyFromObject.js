/**
 * External dependencies
 */
import {
	toPairs,
	flatMap,
	isArray,
	isPlainObject,
	join,
	filter,
	map,
	sortBy,
} from 'lodash';

/**
 * Recursively flattens an object into a list of key-value parts,
 * preserving full key paths and sorting keys alphabetically.
 *
 * @param {Object} obj - The object to flatten.
 * @param {string} [parentKey] - The prefix for nested keys.
 * @return {string[]} - A flat array of key and value parts.
 */
function flattenObjectToParts( obj, parentKey = '' ) {
	const sortedPairs = sortBy( toPairs( obj ), ( [ key ] ) => key );

	return flatMap( sortedPairs, ( [ key, value ] ) => {
		const fullKey = parentKey ? `${ parentKey }.${ key }` : key;

		if ( isArray( value ) ) {
			// Include the key and each array item (as string)
			return [ fullKey, ...map( value, String ) ];
		}

		if ( isPlainObject( value ) ) {
			// Recurse into nested object
			return flattenObjectToParts( value, fullKey );
		}

		return [ fullKey, String( value ) ];
	} );
}

/**
 * Generates a stable hyphen-delimited string from an object,
 * including full key paths and sorted keys at every level.
 *
 * @param {Object} obj - The input object.
 * @return {string} - A deterministic, hyphen-separated key string.
 *                    Returns 'empty' if the object is empty.
 *
 * @example
 * generateKeyFromObject({
 *   page: 1,
 *   sort: { direction: 'desc', field: 'score' },
 *   filters: ['foo', 'bar']
 * });
 * // → "filters-foo-bar-page-1-sort.direction-desc-sort.field-score"
 *
 * generateKeyFromObject({});
 * // → "empty"
 */
export function generateKeyFromObject( obj ) {
	// Check if the object is empty
	if ( ! obj || Object.keys( obj ).length === 0 ) {
		return 'empty';
	}

	const parts = flattenObjectToParts( obj );
	return join(
		filter( parts, ( part ) => part !== '' ),
		'-'
	);
}
