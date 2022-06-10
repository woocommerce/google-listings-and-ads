/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Call `useAppSelectDispatch` with `"getIsSsl"`.
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
 * 		"is Ssl" : "true"
 * }
 * ```
 */
const useSsl = () => {
	return useAppSelectDispatch( 'getIsSsl' );
};

export default useSsl;
