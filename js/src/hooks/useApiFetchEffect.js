/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import isEqual from 'lodash/isEqual';
import { APIFetchOptions } from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

/**
 * Calls apiFetch as a side effect. This uses useApiFetchCallback in a useEffect hook.
 *
 * If you pass falsey options value (e.g. undefined or null), the apiFetch effect won't be fired. This can be useful for initial rendering.
 *
 * @param {APIFetchOptions} options Options to be used in apiFetch call.
 * @return {Object} FetchResult from useApiFetchCallback.
 */
const useApiFetchEffect = ( options ) => {
	const optionsRef = useRef( options );
	if ( ! isEqual( optionsRef.current, options ) ) {
		optionsRef.current = options;
	}

	const [ apiFetch, fetchResult ] = useApiFetchCallback( optionsRef.current );

	useEffect( () => {
		if ( ! optionsRef.current ) {
			return;
		}

		apiFetch();
	}, [ apiFetch ] );

	return fetchResult;
};

export default useApiFetchEffect;
