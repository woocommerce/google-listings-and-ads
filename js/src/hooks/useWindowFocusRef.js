/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

const useWindowFocusRef = () => {
	const focusRef = useRef( true );

	useEffect( () => {
		const handleWindowFocus = () => {
			focusRef.current = true;
		};

		const handleWindowBlur = () => {
			focusRef.current = false;
		};

		window.addEventListener( 'focus', handleWindowFocus );
		window.addEventListener( 'blur', handleWindowBlur );

		return () => {
			window.removeEventListener( 'focus', handleWindowFocus );
			window.removeEventListener( 'blur', handleWindowBlur );
		};
	}, [] );

	return focusRef;
};

export default useWindowFocusRef;
