/**
 * Internal dependencies
 */
import useGoogleBusinessProfileLocations from '../hooks/useGoogleBusinessProfileLocations';
import useConnectGoogleBusinessProfileLocation from '../hooks/useConnectGoogleBusinessProfileLocation';
import ConnectionErrorCard from './connection-error-card';
import LoadingLocationsCard from './loading-locations-card';
import LocationsErrorCard from './locations-error-card';
import NoLocationsCard from './no-locations-card';
import SingleLocationCard from './single-location-card';
import MultipleLocationsCard from './multiple-locations-card';

/**
 * Renders the Google Business Profile account card for the not-yet-connected state: the
 * zero-locations notice, single-location auto-select, and multi-location selection, culminating in
 * connecting the picked location. Once a location is connected, `ConnectedGoogleBusinessProfileAccountCard`
 * takes over.
 *
 * Each possible state — loading, errored, zero/one/many candidate locations, and a failed connect
 * attempt — has its own card component owning both its `indicator` and its `detail`, picked here
 * rather than re-derived independently by each slot.
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleBusinessProfileAccountCard = () => {
	const { locations, hasFinishedResolution } =
		useGoogleBusinessProfileLocations();
	const {
		locationId,
		setLocationId,
		isConnecting,
		hasConnectionError,
		handleConnectClick,
	} = useConnectGoogleBusinessProfileLocation( locations );

	if ( hasConnectionError ) {
		return <ConnectionErrorCard />;
	}

	if ( ! hasFinishedResolution ) {
		return <LoadingLocationsCard />;
	}

	if ( ! locations ) {
		return <LocationsErrorCard />;
	}

	if ( locations.length === 0 ) {
		return <NoLocationsCard />;
	}

	if ( locations.length === 1 ) {
		return (
			<SingleLocationCard
				location={ locations[ 0 ] }
				locationId={ locationId }
				isConnecting={ isConnecting }
				onConnectClick={ handleConnectClick }
			/>
		);
	}

	return (
		<MultipleLocationsCard
			locationId={ locationId }
			onLocationChange={ setLocationId }
			isConnecting={ isConnecting }
			onConnectClick={ handleConnectClick }
		/>
	);
};

export default ConnectGoogleBusinessProfileAccountCard;
