/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Returns a Boolean value indicating whether the window has focus.
 *
 * @return {boolean} Boolean value indicating whether the window has focus.
 */
const useWindowFocus = () => {
	const [ isFocus, setFocus ] = useState( document.hasFocus() );

	useEffect( () => {
		const handleWindowFocus = () => {
			setFocus( true );
		};

		const handleWindowBlur = () => {
			setFocus( false );
		};

		window.addEventListener( 'focus', handleWindowFocus );
		window.addEventListener( 'blur', handleWindowBlur );

		return () => {
			window.removeEventListener( 'focus', handleWindowFocus );
			window.removeEventListener( 'blur', handleWindowBlur );
		};
	}, [] );

	return isFocus;
};

export default useWindowFocus;
