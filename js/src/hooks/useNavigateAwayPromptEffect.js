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
		/**
		 * Bind beforeunload event for non `woocommerce/navigation` links and reloads.
		 * history#v5 does bind `beforeunload` automatically, with v4 we need to do it ourselves.
		 *
		 * @param {Event} e Before Unload Event
		 */
		const eventListener = ( e ) => {
			// If you prevent default behavior in Mozilla Firefox prompt will always be shown.
			e.preventDefault();
			// Chrome requires returnValue to be set.
			e.returnValue = message;
		};

		// Block woocommerce/navigation in order to show a confirmation prompt in case shouldBlock is true
		const unblock = getHistory().block( ( transition ) => {
			let shouldUnblock = true;

			if ( shouldBlock ) {
				window.addEventListener( 'beforeunload', eventListener );
			}

			/**
			 * In history v4 (< WC 6.7) block method only receives one parameter (the location)
			 * In v5 (>= WC 6.7) its a transition object with location, retry and action props
			 */
			const location = transition?.location || transition;

			// show prompt if we want to block the navigation
			if ( shouldBlock && blockedLocation( location ) ) {
				// eslint-disable-next-line no-alert
				shouldUnblock = window.confirm( message );
			}

			// if it is not blocked unblock the navigation and retry (v5) or return true (v4) ...
			if ( shouldUnblock ) {
				unblock();
				if ( typeof transition?.retry !== 'undefined' ) {
					transition.retry();
				} else {
					return true;
				}
			}

			// v4 compatibility requires return false to actually block the navigation
			return false;
		} );

		return () => {
			unblock();
			window.removeEventListener( 'beforeunload', eventListener );
		};
	}, [ message, shouldBlock, blockedLocation ] );
}
