/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

const selectorName = 'getGoogleSearchConsoleAccount';

/**
 * Re-checks the Search Console connection when the Organic search report opens, if the page already
 * holds a status from earlier, and again each time the browser tab becomes visible while the report
 * stays open. The property can change at Google while the page stays open, and a stale `connected`
 * status would otherwise keep showing reports that cannot load.
 *
 * @return {{ isRefreshPending: boolean }} Whether the stored status still has to be discarded, so the report content must wait.
 */
const useRefreshGoogleSearchConsoleAccount = () => {
	const { hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const { invalidateResolution, fetchGoogleSearchConsoleAccount } =
		useAppDispatch();
	const [ isRefreshPending, setIsRefreshPending ] = useState(
		hasFinishedResolution
	);

	useEffect( () => {
		if ( ! isRefreshPending ) {
			return;
		}

		invalidateResolution( selectorName, [] );
		setIsRefreshPending( false );
		// Only the status held when the report opened is refreshed.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		const handleVisibilityChange = () => {
			if ( document.visibilityState === 'visible' ) {
				// Fetched in place, without discarding the stored status, so the reports stay on screen
				// until the new status says otherwise.
				fetchGoogleSearchConsoleAccount();
			}
		};

		document.addEventListener( 'visibilitychange', handleVisibilityChange );

		return () => {
			document.removeEventListener(
				'visibilitychange',
				handleVisibilityChange
			);
		};
	}, [ fetchGoogleSearchConsoleAccount ] );

	return { isRefreshPending };
};

export default useRefreshGoogleSearchConsoleAccount;
