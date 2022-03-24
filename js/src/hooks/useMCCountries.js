/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get MC supported countries from API. Returns `{ hasFinishedResolution, data, invalidateResolution }`.
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
const useMCCountries = () => {
	return useAppSelectDispatch( 'getCountries' );
};

export default useMCCountries;
