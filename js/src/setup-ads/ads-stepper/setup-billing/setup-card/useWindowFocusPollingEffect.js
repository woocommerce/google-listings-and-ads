/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useWindowFocusRef from '.~/hooks/useWindowFocusRef';

const useWindowFocusPollingEffect = ( fn = () => {}, intervalInSeconds ) => {
	const focusRef = useWindowFocusRef();

	// poll for billing status only when window is in focus.
	useEffect( () => {
		const intervalID = setInterval( () => {
			if ( focusRef.current ) {
				fn();
			}
		}, intervalInSeconds * 1000 );

		return () => clearInterval( intervalID );
	}, [ fn, focusRef, intervalInSeconds ] );
};

export default useWindowFocusPollingEffect;
