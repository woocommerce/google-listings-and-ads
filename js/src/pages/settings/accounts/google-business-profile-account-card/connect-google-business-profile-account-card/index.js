/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import Badge from '~/components/badge';
import useGoogleBusinessProfileLocations from '../hooks/useGoogleBusinessProfileLocations';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from '../constants';
import LocationsErrorNotice from './locations-error-notice';
import NoAccountNotice from './no-account-notice';
import SingleLocationNotice from './single-location-notice';
import LocationSelection from './location-selection';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

const ACTION_NEEDED_BADGE = (
	<Badge intent="warning">
		{ __( 'Action needed', 'google-listings-and-ads' ) }
	</Badge>
);

/**
 * Renders the Google Business Profile card once access is granted but before a location is
 * connected. It looks up the merchant's locations, then shows a loading spinner, an error with
 * "Try again", a no-account notice, the single location found with "Connect", or a location
 * picker when more than one is found.
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleBusinessProfileAccountCard = () => {
	const { locations, isLoading, hasError, refetch } =
		useGoogleBusinessProfileLocations();
	const { connectGoogleBusinessProfileLocation } = useAppDispatch();
	const [ isConnecting, setIsConnecting ] = useState( false );

	/**
	 * Connects the given location. A failure is reported by the action itself, so the card only
	 * needs to stop showing progress once the request settles.
	 *
	 * @param {GoogleBusinessProfileLocation} location The location to connect.
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleConnect = async ( location ) => {
		setIsConnecting( true );
		try {
			await connectGoogleBusinessProfileLocation( location );
		} catch ( error ) {
			// The action has already reported the failure.
		} finally {
			setIsConnecting( false );
		}
	};

	let indicator = null;
	let detail = null;

	if ( isLoading ) {
		indicator = <AppSpinner />;
	} else if ( hasError ) {
		detail = <LocationsErrorNotice onRetry={ refetch } />;
	} else if ( ! locations?.length ) {
		indicator = ACTION_NEEDED_BADGE;
		detail = <NoAccountNotice onCheckAgain={ refetch } />;
	} else if ( locations.length === 1 ) {
		const [ location ] = locations;

		const handleConnectClick = () => {
			handleConnect( location );
		};

		indicator = (
			<AppButton
				isSecondary
				loading={ isConnecting }
				onClick={ handleConnectClick }
			>
				{ __( 'Connect', 'google-listings-and-ads' ) }
			</AppButton>
		);
		detail = <SingleLocationNotice location={ location } />;
	} else {
		indicator = ACTION_NEEDED_BADGE;
		detail = (
			<LocationSelection
				locations={ locations }
				isSaving={ isConnecting }
				onSave={ handleConnect }
			/>
		);
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={ indicator }
			detail={ detail }
			expandedDetail
		/>
	);
};

export default ConnectGoogleBusinessProfileAccountCard;
