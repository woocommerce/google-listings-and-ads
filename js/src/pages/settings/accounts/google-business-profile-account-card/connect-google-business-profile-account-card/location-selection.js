/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppSelectControl from '~/components/app-select-control';
import NoticeDetail from '../notice-detail';
import formatLocationAddress from '../format-location-address';
import CreateLink from './create-link';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders the location picker shown when more than one Google Business Profile location was
 * found: a notice, a location dropdown, a "Save" button and a "Create new location" link. Once the
 * merchant has clicked through to Google, the notice asks them to refresh the page instead.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation[]} props.locations The locations to choose from.
 * @param {boolean} props.isSaving Whether the chosen location is being saved.
 * @param {(locationName: string) => void} props.onSave Called with the chosen location's resource name.
 * @return {JSX.Element} The location picker.
 */
export default function LocationSelection( { locations, isSaving, onSave } ) {
	const [ locationName, setLocationName ] = useState();
	const [ hasClickedCreate, setHasClickedCreate ] = useState( false );

	const options = locations.map( ( location ) => {
		return {
			value: location.name,
			label: formatLocationAddress( location ),
		};
	} );

	const handleCreateClick = () => {
		setHasClickedCreate( true );
	};

	const handleSaveClick = () => {
		onSave( locationName );
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<NoticeDetail
				status="info"
				body={
					<p>
						{ hasClickedCreate
							? __(
									'Refresh the page to see your new location.',
									'google-listings-and-ads'
							  )
							: __(
									'We found multiple Google Business Profile locations. Pick one to connect.',
									'google-listings-and-ads'
							  ) }
					</p>
				}
			/>
			<AppSelectControl
				label={ __( 'Select a location', 'google-listings-and-ads' ) }
				options={ options }
				value={ locationName }
				onChange={ setLocationName }
				disabled={ isSaving }
				autoSelectFirstOption
			/>
			<Flex justify="flex-start" gap={ 4 }>
				<AppButton
					isPrimary
					loading={ isSaving }
					disabled={ ! locationName }
					onClick={ handleSaveClick }
				>
					{ __( 'Save', 'google-listings-and-ads' ) }
				</AppButton>
				<CreateLink type="location" onClick={ handleCreateClick } />
			</Flex>
		</Flex>
	);
}
