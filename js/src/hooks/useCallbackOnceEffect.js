/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';

const useCallbackOnceEffect = ( condition, callback, ...args ) => {
	const calledRef = useRef( false );
	const argsRefValue = useIsEqualRefValue( args );

	useEffect( () => {
		if ( calledRef.current === true ) {
			return;
		}

		if ( condition ) {
			calledRef.current = true;
			callback( ...argsRefValue );
		}
	}, [ argsRefValue, callback, condition ] );
};

export default useCallbackOnceEffect;
