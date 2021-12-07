/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get the supported countries from API. Returns `{ hasFinishedResolution, data, invalidateResolution }`.
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
	return useAppSelectDispatch( 'getCountries' );
};

export default useGetCountries;
