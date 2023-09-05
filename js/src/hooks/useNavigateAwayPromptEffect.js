/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';
import { noop } from 'lodash';

const alwaysTrue = () => true;

/**
 * Returns a normalized location to handle the inconsistent pathname in history v5 (≥ WC 6.7).
 *
 * Since WC calls `history.push()` with a path that starts with 'admin.php?...', it brings
 * the inconsistent `location` results.
 *
 * The `pathname` in v5 may be 'admin.php' or '/wp-admin/admin.php'.
 *
 * @see https://github.com/remix-run/history/blob/v5.3.0/packages/history/index.ts#L735
 * @see https://github.com/remix-run/history/blob/v5.3.0/packages/history/index.ts#L701
 * @see https://github.com/remix-run/history/blob/v5.3.0/packages/history/index.ts#L1086
 *
 * @param {Object} location Location object to be normalized.
 * @return {Object} Normalized location object.
 */
function normalizeLocation( location ) {
	return {
		...location,
		pathname: location.pathname.replace( /^(\/wp-admin)?\//, '' ),
	};
}

/**
 * Show prompt when the user tries to unload/leave the page.
 *
 * @param {string} message Message to be shown. Note, some browsers may not support this when unloading the page.
 * @param {boolean} shouldBlock Boolean flag, whether the prompt should be shown.
 * @param {( location: Object ) => boolean} [blockedLocation] Function to filter specific locations for blocking when navigating using woocommerce/navigation.
 */
export default function useNavigateAwayPromptEffect(
	message,
	shouldBlock,
	blockedLocation = alwaysTrue
) {
	// history#v5: As one of useEffect deps for triggering a new blocking after history is changed.
	const { key } = getHistory().location;

	useEffect( () => {
		let unblock = noop;

		if ( shouldBlock ) {
			// Block navigation in order to show a confirmation prompt.
			unblock = getHistory().block( ( transition ) => {
				const { location, retry } = transition;
				let shouldUnblock = true;

				if ( blockedLocation( normalizeLocation( location ) ) ) {
					// Show prompt to confirm if the user wants to navigate away
					shouldUnblock = window.confirm( message ); // eslint-disable-line no-alert
				}

				// history v5 requires the calls to unblock and retry functions.
				if ( shouldUnblock ) {
					unblock();
					retry();
				}
			} );
		}

		return () => {
			unblock();
		};
	}, [ key, message, shouldBlock, blockedLocation ] );
}
