/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Show prompt when the user tries to unload/leave the page.
 * Adds and removed `beforeunload` event listener according to the given flag.
 *
 * @param {boolean} shouldPreventClose Boolean flag, whether the prompt should be shown.
 */
export default function useBeforeUnloadPromptEffect( shouldPreventClose ) {
	useEffect( () => {
		const eventListener = ( e ) => {
			// If you prevent default behavior in Mozilla Firefox prompt will always be shown.
			e.preventDefault();

			// Chrome requires returnValue to be set.
			e.returnValue = '';
		};

		if ( shouldPreventClose ) {
			window.addEventListener( 'beforeunload', eventListener );
		}

		return () => {
			window.removeEventListener( 'beforeunload', eventListener );
		};
	}, [ shouldPreventClose ] );
}
