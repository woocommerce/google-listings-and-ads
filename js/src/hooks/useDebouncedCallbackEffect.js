/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';

const defaultOptions = {
	wait: 500,
	callOnFirstRender: false,
};

/**
 * Call function with debounce delay.
 *
 * By default, it does not call on first render, since the first render is the loading of value from API.
 * Pass an options object with `callOnFirstRender` set to `true` to call on first render.
 *
 * @param {Object} value Value to be passed to func.
 * @param {Function} func Function to be debounced.
 * @param {Object} [options] Options object.
 * @param {number} [options.wait] Number of milliseconds to wait before calling the function. Default is 500.
 * @param {boolean} [options.callOnFirstRender] Boolean indicating whether to call the function on first render. Default is false.
 */
const useDebouncedCallbackEffect = (
	value = {},
	func = () => {},
	options = defaultOptions
) => {
	const { wait, callOnFirstRender } = {
		...defaultOptions,
		...options,
	};
	const valueRefValue = useIsEqualRefValue( value );
	const debouncedCallback = useDebouncedCallback( func, wait );
	const ref = useRef( null );

	useEffect( () => {
		// whether to call on first render.
		if ( ! callOnFirstRender && ref.current === null ) {
			ref.current = valueRefValue;
			return;
		}

		// call the debounced callback.
		debouncedCallback.callback( valueRefValue );
	}, [ callOnFirstRender, debouncedCallback, valueRefValue ] );
};

export default useDebouncedCallbackEffect;
