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
import useConnectGoogleBusinessProfileLocation from '../../hooks/useConnectGoogleBusinessProfileLocation';
import NoticeDetail from '../../notice-detail';
import GoogleBusinessProfileLocationSelectControl from '../google-business-profile-location-select-control';
import CreateNewLink from '../create-new-link';
import CreateNewRefreshNotice from '../create-new-refresh-notice';

/**
 * Renders the form shown when multiple Google Business Profile locations were found: a notice, a
 * picker to choose which to connect, a "Save" button connecting the picked location, plus a
 * create-new-location link, with the refresh reminder below the notice once that link is clicked.
 * The picked location is this form's own state.
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
