/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

// Shortest time between two status checks that are not caused by opening the report.
export const MIN_RECHECK_INTERVAL = 30 * 1000;

/**
 * Re-checks the Search Console connection when the Organic search report opens and when the
 * browser tab is shown again, since the property can change at Google while the page stays open.
 *
 * @return {{ isRefreshPending: boolean }} Whether the status held from an earlier visit is still being checked, so the report content must wait.
 */
const useRefreshGoogleSearchConsoleAccount = () => {
	const { hasFinishedResolution } = useGoogleSearchConsoleAccount();
	const { refreshGoogleSearchConsoleAccount } = useAppDispatch();
	const [ isRefreshPending, setIsRefreshPending ] = useState(
		hasFinishedResolution
	);
	const lastCheckedAt = useRef( Date.now() );

	useEffect( () => {
		if ( ! isRefreshPending ) {
			return;
		}

		// A failed check keeps the stored status, so the report is never held back for good.
		Promise.resolve( refreshGoogleSearchConsoleAccount() ).finally( () => {
			setIsRefreshPending( false );
		} );
	}, [ isRefreshPending, refreshGoogleSearchConsoleAccount ] );

	useEffect( () => {
		const handleVisibilityChange = () => {
			const now = Date.now();

			if (
				document.visibilityState !== 'visible' ||
				now - lastCheckedAt.current < MIN_RECHECK_INTERVAL
			) {
				return;
			}

			lastCheckedAt.current = now;
			// Fetched in place, so the reports stay on screen until the new status says otherwise.
			refreshGoogleSearchConsoleAccount();
		};

		document.addEventListener( 'visibilitychange', handleVisibilityChange );

		return () => {
			document.removeEventListener(
				'visibilitychange',
				handleVisibilityChange
			);
		};
	}, [ refreshGoogleSearchConsoleAccount ] );

	return { isRefreshPending };
};

export default useRefreshGoogleSearchConsoleAccount;
