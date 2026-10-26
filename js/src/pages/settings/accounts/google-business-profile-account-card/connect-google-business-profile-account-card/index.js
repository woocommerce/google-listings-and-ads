/**
 * Internal dependencies
 */
import useGoogleBusinessProfileLocations from '../hooks/useGoogleBusinessProfileLocations';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import { CONNECTION_ERROR_SLOTS } from '../hooks/useConnectGoogleBusinessProfileLocation';
import ConnectionErrorCard from './connection-error-card';
import LoadingLocationsCard from './loading-locations-card';
import LocationsErrorCard from './locations-error-card';
import NoLocationsCard from './no-locations-card';
import SingleLocationCard from './single-location-card';
import MultipleLocationsCard from './multiple-locations-card';

/**
 * Renders the Google Business Profile account card for the not-yet-connected state: the
 * zero-locations notice, the single location, or the multi-location selection, culminating in
 * connecting a location. Once a location is connected, `ConnectedGoogleBusinessProfileAccountCard`
 * takes over.
 *
 * Each possible state — loading, errored, zero/one/many candidate locations, and a failed connect
 * attempt — has its own card component owning both its `indicator` and its `detail`, picked here
 * rather than re-derived independently by each slot. The single and multiple location cards each
 * run their own connect flow.
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleBusinessProfileAccountCard = () => {
	const { locations, hasFinishedResolution } =
		useGoogleBusinessProfileLocations();
	const [ connectionError ] = useDetailedErrorBySlots(
		CONNECTION_ERROR_SLOTS
	);

	if ( ! hasFinishedResolution ) {
		return <LoadingLocationsCard />;
	}

	if ( connectionError ) {
		return <ConnectionErrorCard />;
	}

	if ( ! locations ) {
		return <LocationsErrorCard />;
	}

	if ( locations.length === 0 ) {
		return <NoLocationsCard />;
	}

	if ( locations.length === 1 ) {
		return <SingleLocationCard />;
	}

	return <MultipleLocationsCard />;
};

export default ConnectGoogleBusinessProfileAccountCard;
