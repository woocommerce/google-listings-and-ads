/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import NoticeDetail from '../../notice-detail';
import GoogleBusinessProfileLocationSelectControl from './google-business-profile-location-select-control';
import CreateNewLocationLink from './create-new-location-link';

/**
 * Renders the notice shown when multiple Google Business Profile locations were found: a picker
 * to choose which to connect, a "Save" button connecting the picked location, plus a
 * create-new-location link.
 *
 * @param {Object} props Component props.
 * @param {string} [props.locationId] The currently picked location ID.
 * @param {( locationId: string ) => void} props.onLocationChange Callback when the picked location changes.
 * @param {boolean} props.isConnecting Whether the connect request is in flight.
 * @param {() => void} props.onConnectClick Callback when the user clicks "Save".
 * @param {JSX.Element|null} props.createLocationNotice The refresh reminder shown above the create-new-location link once it's been clicked.
 * @param {() => void} props.onCreateLocationClick Callback when the user clicks the create-new-location link.
 * @return {JSX.Element} The notice.
 */
export default function MultipleBusinessProfileLocationsNotice( {
	locationId,
	onLocationChange,
	isConnecting,
	onConnectClick,
	createLocationNotice,
	onCreateLocationClick,
} ) {
	return (
		<Flex direction="column" gap={ 4 }>
			<NoticeDetail
				status="info"
				body={
					<p>
						{ __(
							'We found multiple Google Business Profile locations. Pick one to connect.',
							'google-listings-and-ads'
						) }
					</p>
				}
			/>
			<GoogleBusinessProfileLocationSelectControl
				label={ __( 'Select a location', 'google-listings-and-ads' ) }
				value={ locationId }
				onChange={ onLocationChange }
				disabled={ isConnecting }
			/>
			{ createLocationNotice }
			<Flex justify="start" gap={ 4 }>
				<AppButton
					onClick={ onConnectClick }
					disabled={ ! locationId || isConnecting }
					loading={ isConnecting }
					isPrimary
				>
					{ __( 'Save', 'google-listings-and-ads' ) }
				</AppButton>
				<CreateNewLocationLink onClick={ onCreateLocationClick } />
			</Flex>
		</Flex>
	);
}
