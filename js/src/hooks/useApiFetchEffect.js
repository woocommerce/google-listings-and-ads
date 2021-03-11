/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import isEqual from 'lodash/isEqual';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

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
