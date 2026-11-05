/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '../../constants';
import AppButton from '~/components/app-button';
import useConnectGoogleTagManagerContainer from '../../hooks/useConnectGoogleTagManagerContainer';
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';
import CreateNewContainerButton from '../create-new-container-button';
import CreateContainerNotice from '../create-container-notice';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the container selector with an explicit "Save" action, and an inline "Create new container"
 * link. When the connection can't create containers, an explanation is shown in place of the link.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element} The selector.
 */
export default function SelectContainer() {
	const [ containerId, setContainerId ] = useState();
	const [ connectContainer, { loading: isConnecting } ] =
		useConnectGoogleTagManagerContainer();

	const handleSaveClick = () => {
		return connectContainer( containerId );
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<GoogleTagManagerContainerSelectControl
				label={ __( 'Container', 'google-listings-and-ads' ) }
				value={ containerId }
				onChange={ setContainerId }
			/>
			<CreateContainerNotice />
			<Flex justify="start" gap={ 4 }>
				<AppButton
					eventName="gla_google_tag_manager_container_select_button_click"
					eventProps={ {
						context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
					} }
					onClick={ handleSaveClick }
					disabled={ ! containerId }
					loading={ isConnecting }
					isPrimary
				>
					{ __( 'Save', 'google-listings-and-ads' ) }
				</AppButton>
				<CreateNewContainerButton />
			</Flex>
		</Flex>
	);
}
