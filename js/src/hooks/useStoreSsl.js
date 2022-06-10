/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Call `useAppSelectDispatch` with `"getIsStoreSsl"`.
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
/**
 * Get store ssl from API. Returns `{ hasFinishedResolution, data, invalidateResolution }`.
 *
 * `data` is an object of store ssl mapping. e.g.:
 *
 * ```json
 * {
 * 		"is Store Ssl" : "true"
 * }
 * ```
 */
const useStoreSsl = () => {
	return useAppSelectDispatch( 'getIsStoreSsl' );
};

export default useStoreSsl;
