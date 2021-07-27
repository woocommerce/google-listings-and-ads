/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';

/**
 * Calls apiFetch as a side effect. This uses useApiFetchCallback in a useEffect hook.
 *
 * If you pass falsey options value (e.g. undefined or null), the apiFetch effect won't be fired. This can be useful for initial rendering.
 *
 * @param {import('@wordpress/api-fetch').APIFetchOptions} options Options to be used in apiFetch call.
 * @return {Object} FetchResult from useApiFetchCallback.
 */
const useApiFetchEffect = ( options ) => {
	const optionsRefValue = useIsEqualRefValue( options );

	const [ apiFetch, fetchResult ] = useApiFetchCallback( optionsRefValue, {
		loading: true,
	} );

	useEffect( () => {
		if ( ! optionsRefValue ) {
			return;
		}

		apiFetch();
	}, [ apiFetch, optionsRefValue ] );

	return fetchResult;
};

export default useApiFetchEffect;
