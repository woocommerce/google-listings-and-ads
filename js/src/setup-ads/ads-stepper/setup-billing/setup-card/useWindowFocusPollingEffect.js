/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useWindowFocusRef from '.~/hooks/useWindowFocusRef';

/**
 * Execute a callback function at every interval only when the window has focus.
 *
 * @param {Function} callback Function to be executed.
 * @param {number} intervalInSeconds Interval in seconds.
 */
const useWindowFocusCallbackIntervalEffect = (
	callback = () => {},
	intervalInSeconds
) => {
	const focusRef = useWindowFocusRef();

	useEffect( () => {
		const intervalID = setInterval( () => {
			if ( focusRef.current ) {
				callback();
			}
		}, intervalInSeconds * 1000 );

		return () => clearInterval( intervalID );
	}, [ callback, focusRef, intervalInSeconds ] );
};

export default useWindowFocusCallbackIntervalEffect;
