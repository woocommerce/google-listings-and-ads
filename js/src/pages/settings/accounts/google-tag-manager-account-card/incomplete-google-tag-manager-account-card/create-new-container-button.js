/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '../constants';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import CreateContainerModal from './create-container-modal';

/**
 * Clicking the link that opens the modal to create a new Google Tag Manager container. Fires when
 * the modal opens, not when a container has been created.
 *
 * @event gla_google_tag_manager_create_container_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the "Create new container" link-style button shown from the container-selection detail — both when
 * the connected account has zero containers (replacing the selector entirely) and when it already
 * has one or more (as an inline link beside the populated selector), together with the modal it opens.
 * The link is hidden when the connection can't create containers. An open modal stays, so a refusal
 * it just received is still explained in it while the card behind it switches to the read-only notice.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onCreated Called, and awaited, once the container has been created. Rejecting
 * shows the failure in the modal.
 *
 * @fires gla_google_tag_manager_create_container_button_click
 *
 * @return {JSX.Element} The link, when creating is available, and the modal while it is open.
 */
export default function CreateNewContainerButton( { onCreated } ) {
	const { account } = useGoogleTagManagerAccount();
	const [ isOpen, setIsOpen ] = useState( false );

	const handleOpen = () => {
		setIsOpen( true );
	};

	const handleClose = () => {
		setIsOpen( false );
	};

	return (
		<>
			{ account?.canCreateContainer !== false && (
				<AppButton
					eventName="gla_google_tag_manager_create_container_button_click"
					eventProps={ {
						context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
					} }
					onClick={ handleOpen }
					isLink
				>
					{ __( 'Create new container', 'google-listings-and-ads' ) }
				</AppButton>
			) }
			{ isOpen && (
				<CreateContainerModal
					onCreated={ onCreated }
					onRequestClose={ handleClose }
				/>
			) }
		</>
	);
}
