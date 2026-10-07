/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useGoogleBusinessProfileLocations from '../../hooks/useGoogleBusinessProfileLocations';
import NoticeDetail from '../../notice-detail';
import LocationsErrorNotice from './locations-error-notice';
import NoBusinessProfileAccountNotice from './no-business-profile-account-notice';
import SingleBusinessProfileLocationNotice from './single-business-profile-location-notice';
import MultipleBusinessProfileLocationsNotice from './multiple-business-profile-locations-notice';
import './location-selection.scss';

/**
 * Renders the location-selection detail: the error notice when the locations couldn't be loaded,
 * the zero-locations notice (see `NoBusinessProfileAccountNotice`), the single candidate location
 * shown as plain text, or a selector with a "Save" action when more than one exists. Clicking
 * "Create new account" or "Create new location" shows a local info notice reminding the merchant
 * to refresh the page once they've created it.
 *
 * @param {Object} props Component props.
 * @param {string} [props.locationId] The currently picked location ID.
 * @param {( locationId: string ) => void} props.onLocationChange Callback when the picked location changes.
 * @param {boolean} props.isConnecting Whether the connect request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Save" to connect the picked location.
 * @return {JSX.Element} The detail, or a loading spinner until the locations list has resolved.
 */
export default function LocationSelection( {
	locationId,
	onLocationChange,
	isConnecting,
	onConnectClick,
} ) {
	const { locations, hasFinishedResolution } =
		useGoogleBusinessProfileLocations();
	const [ hasClickedCreate, setHasClickedCreate ] = useState( false );

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	if ( ! locations ) {
		return <LocationsErrorNotice />;
	}

	const handleCreateClick = () => {
		setHasClickedCreate( true );
	};

	const getRefreshNotice = ( text ) => {
		if ( ! hasClickedCreate ) {
			return null;
		}

		return (
			<div className="gla-google-business-profile-account-card__refresh-notice">
				<NoticeDetail status="info" body={ <p>{ text }</p> } />
			</div>
		);
	};

	if ( ! locations.length ) {
		return (
			<Flex direction="column" gap={ 4 }>
				<NoBusinessProfileAccountNotice
					onCreateAccountClick={ handleCreateClick }
				/>
				{ getRefreshNotice(
					__(
						'Refresh the page to see your new account.',
						'google-listings-and-ads'
					)
				) }
			</Flex>
		);
	}

	const createLocationNotice = getRefreshNotice(
		__(
			'Refresh the page to see your new location.',
			'google-listings-and-ads'
		)
	);

	if ( locations.length === 1 ) {
		const [ singleLocation ] = locations;

		return (
			<SingleBusinessProfileLocationNotice
				location={ singleLocation }
				createLocationNotice={ createLocationNotice }
				onCreateLocationClick={ handleCreateClick }
			/>
		);
	}

	return (
		<MultipleBusinessProfileLocationsNotice
			locationId={ locationId }
			onLocationChange={ onLocationChange }
			isConnecting={ isConnecting }
			onConnectClick={ onConnectClick }
			createLocationNotice={ createLocationNotice }
			onCreateLocationClick={ handleCreateClick }
		/>
	);
}
