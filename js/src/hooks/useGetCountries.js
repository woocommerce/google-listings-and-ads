/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';

/**
 * Get the supported countries from API. Returns `{ loading, data }`.
 *
 * `loading` is a boolean indicating whether the request is still resolving.
 *
 * `data` is an object of country mapping. e.g.:
 *
 * ```json
 * {
 * 		"AR": {
 * 			"name": "Argentina",
 * 			"currency": "ARS"
 * 		}
 * }
 * ```
 */
const useGetCountries = () => {
	return useSelect( ( select ) => {
		const { getCountries, isResolving } = select( STORE_KEY );

		const data = getCountries();
		const loading = isResolving( 'getCountries' );

		return {
			loading,
			data,
		};
	} );
};

export default useGetCountries;
