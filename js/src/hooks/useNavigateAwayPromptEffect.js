/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Show prompt when the user tries to unload/leave the page.
 * Adds and removed `beforeunload` event listener according to the given flag.
 *
 * @param {string} message Message to be shown. Note, some browsers may not support this when unloading the page.
 * @param {boolean} shouldBlock Boolean flag, whether the prompt should be shown.
 * @param {( location: Object ) => boolean} [blockedLocation] Function to filter specific locations for blocking when navigating using woocommerce/navigation.
 */
export default function useNavigateAwayPromptEffect(
	message,
	shouldBlock,
	blockedLocation = () => true
) {
	useEffect( () => {
		// Bind beforeunload event for non `woocommerce/navigation` links and reloads.
		// history#v5 does bind `beforeunload` automatically, with v4 we need to do it ourselves.
		const eventListener = ( e ) => {
			// If you prevent default behavior in Mozilla Firefox prompt will always be shown.
			e.preventDefault();
			// Chrome requires returnValue to be set.
			e.returnValue = message;
		};

		if ( shouldBlock ) {
			window.addEventListener( 'beforeunload', eventListener );
		}
		// Block woocommerce/navigation.
		const unblock = getHistory().block( ( location ) => {
			// TODO: Consider being futureproof for history.v5 where
			// getHistory().block( ( { location, retry } ) => {

			// Return the message / truthy value to show a prompt.
			if ( shouldBlock && blockedLocation( location ) ) {
				return message;
			}
		} );

		return () => {
			unblock();
			window.removeEventListener( 'beforeunload', eventListener );
		};
	}, [ message, shouldBlock, blockedLocation ] );
}
