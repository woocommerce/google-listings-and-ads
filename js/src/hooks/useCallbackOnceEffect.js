/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';

/**
 * A hook to call a callback function once when a condition is set to true.
 *
 * @param {boolean} condition Condition to call the callback.
 * @param {Function} callback Callback to be executed once when condition is true.
 * @param  {...any} args Arguments to be passed to the callback.
 */
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
