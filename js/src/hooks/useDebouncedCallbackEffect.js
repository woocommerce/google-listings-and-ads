/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Call function with debounce delay.
 * It does not call on first render since the first render is the loading of value from API.
 *
 * @param {Object} value Value to be passed to func.
 * @param {Function} func Function to be debounced.
 * @param {number} wait Number of milliseconds to wait.
 */
const useDebouncedCallbackEffect = (
	value = {},
	func = () => {},
	wait = 500
) => {
	const debouncedCallback = useDebouncedCallback( func, wait );
	const ref = useRef( null );

	useEffect( () => {
		// do not call on first render.
		if ( ref.current === null ) {
			ref.current = value;
			return;
		}

		// call the debounced callback.
		debouncedCallback.callback( value );
	}, [ debouncedCallback, value ] );
};

export default useDebouncedCallbackEffect;
