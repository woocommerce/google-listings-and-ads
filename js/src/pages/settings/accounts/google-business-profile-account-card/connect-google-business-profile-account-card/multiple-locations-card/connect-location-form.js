/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../../constants';
import useConnectGoogleBusinessProfileLocation from '../../hooks/useConnectGoogleBusinessProfileLocation';
import NoticeDetail from '../../notice-detail';
import GoogleBusinessProfileLocationSelectControl from '../google-business-profile-location-select-control';
import CreateNewLink from '../create-new-link';
import CreateNewRefreshNotice from '../create-new-refresh-notice';

/**
 * Clicking "Save" to connect the Google Business Profile location picked from several found.
 *
 * @event gla_google_business_profile_location_save_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Renders the form shown when multiple Google Business Profile locations were found: a notice, a
 * picker to choose which to connect, a "Save" button connecting the picked location, plus a
 * create-new-location link, with the refresh reminder below the notice once that link is clicked.
 * The picked location is this form's own state.
 *
 * @fires gla_google_business_profile_location_save_button_click with `{ context: 'settings-business-profile' }`.
 *
 * @return {JSX.Element} The form.
 */
export default function ConnectLocationForm() {
	const [ locationId, setLocationId ] = useState();
	const [ hasClickedCreate, setHasClickedCreate ] = useState( false );
	const { isConnecting, connectLocation } =
		useConnectGoogleBusinessProfileLocation();

	const handleSaveClick = () => {
		connectLocation( locationId );
	};

	const handleCreateClick = () => {
		setHasClickedCreate( true );
	};

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
			{ hasClickedCreate && <CreateNewRefreshNotice type="location" /> }
			<GoogleBusinessProfileLocationSelectControl
				label={ __( 'Select a location', 'google-listings-and-ads' ) }
				value={ locationId }
				onChange={ setLocationId }
				disabled={ isConnecting }
			/>
			<Flex justify="start" gap={ 4 }>
				<AppButton
					eventName="gla_google_business_profile_location_save_button_click"
					eventProps={ {
						context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
					} }
					onClick={ handleSaveClick }
					disabled={ ! locationId }
					loading={ isConnecting }
					isPrimary
				>
					{ __( 'Save', 'google-listings-and-ads' ) }
				</AppButton>
				<CreateNewLink type="location" onClick={ handleCreateClick } />
			</Flex>
		</Flex>
	);
}
