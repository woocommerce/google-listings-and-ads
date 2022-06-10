/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get allowed countries from calling `useAppSelectDispatch` with `"getAllowedCountries"`.
 * Returns `{ hasFinishedResolution, data, invalidateResolution }`.
 *
 * `data` is an object of country mapping. e.g.:
 *
 * ```json
 * {
 * 		 "AR": {
 * 			"name": "Argentina",
 * 			"currency": "ARS"
 * 		}
 * }
 * ```
 */
const useAllowedCountries = () => {
	return useAppSelectDispatch( 'getAllowedCountries' );
};

export default useAllowedCountries;
