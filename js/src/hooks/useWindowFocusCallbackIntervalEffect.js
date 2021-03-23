/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useWindowFocus from './useWindowFocus';

/**
 * Execute a callback function when window has the focus and then at every interval.
 * Does not execute the callback function when window does not have focus.
 *
 * @param {Function} callback Function to be executed.
 * @param {number} intervalInSeconds Interval in seconds.
 */
const useWindowFocusCallbackIntervalEffect = (
	callback,
	intervalInSeconds
) => {
	const isFocus = useWindowFocus();

	useEffect( () => {
		if ( ! isFocus ) {
			return;
		}

		callback();

		const intervalID = setInterval( callback, intervalInSeconds * 1000 );

		return () => clearInterval( intervalID );
	}, [ callback, intervalInSeconds, isFocus ] );
};

export default useWindowFocusCallbackIntervalEffect;
