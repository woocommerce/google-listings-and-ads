/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

/**
 * A hook that re-fetches the Google Tag Manager connection state without a full page reload —
 * shared by the zero-accounts "Check again" action and the container-selection "Check again"
 * action, since both are just re-invalidating the same connection resolution.
 *
 * @return {{ refresh: () => void, isResolving: boolean }} Click handler to wire to the "Check again" button, and whether a request is in flight.
 */
const useRefreshGoogleTagManagerConnection = () => {
	const { invalidateResolution } = useAppDispatch();
	const { isResolving } = useGoogleTagManagerAccount();

	const refresh = () => {
		invalidateResolution( 'getGoogleTagManagerAccount', [] );
	};

	return { refresh, isResolving };
};

export default useRefreshGoogleTagManagerConnection;
