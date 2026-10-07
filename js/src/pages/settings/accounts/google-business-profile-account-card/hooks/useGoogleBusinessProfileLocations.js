/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * A hook to load the Google Business Profile locations the connected Google Account can post to.
 * Locations are only requested when this hook is first used, and again only through `refetch`.
 *
 * @return {{ locations: GoogleBusinessProfileLocation[]|null, isLoading: boolean, hasError: boolean, refetch: () => void }} The locations, whether a request is in flight, whether the last request failed, and a callback to request them again.
 */
const useGoogleBusinessProfileLocations = () => {
	const {
		data: locations,
		isResolving,
		hasFinishedResolution,
		invalidateResolution,
	} = useAppSelectDispatch( 'getGoogleBusinessProfileLocations' );

	const hasError = useSelect( ( select ) => {
		return select( STORE_KEY ).getGoogleBusinessProfileLocationsError();
	}, [] );

	return {
		locations,
		isLoading: isResolving || ! hasFinishedResolution,
		hasError,
		refetch: invalidateResolution,
	};
};

export default useGoogleBusinessProfileLocations;
