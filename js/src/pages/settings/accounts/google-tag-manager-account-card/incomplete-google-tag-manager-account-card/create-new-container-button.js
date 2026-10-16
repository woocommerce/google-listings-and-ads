/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	CREATED_NOT_CONNECTED_MESSAGE,
	GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
} from '../constants';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';
import CreateContainerModal from './create-container-modal';

/**
 * Clicking the link that opens the modal to create a new Google Tag Manager container. Fires when
 * the modal opens, not when a container has been created.
 *
 * @event gla_google_tag_manager_create_container_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the "Create new container" link-style button shown from the connect-container detail — both when
 * the connected account has zero containers (replacing the selector entirely) and when it already
 * has one or more (as an inline link beside the populated selector), together with the modal it opens.
 * Renders nothing when the connection can't create containers.
 *
 * Once a container has been created, the connection is refreshed. The card then switches to its
 * connected state, or to the notice for a container that couldn't be connected, which removes this
 * button and its modal.
 *
 * @fires gla_google_tag_manager_create_container_button_click
 *
 * @return {JSX.Element|null} The link and the modal while it is open, or `null` when creating isn't available.
 */
export default function CreateNewContainerButton() {
	const { account } = useGoogleTagManagerAccount();
	const { refreshConnection } = useRefreshGoogleTagManagerConnection();
	const [ isOpen, setIsOpen ] = useState( false );

	if ( account.canCreateContainer === false ) {
		return null;
	}

	const handleOpen = () => {
		setIsOpen( true );
	};

	const handleClose = () => {
		setIsOpen( false );
	};

	const handleCreated = async () => {
		const refreshedAccount = await refreshConnection();

		// The container is already created, so a refresh that reports neither a connected nor a pending
		// container is reported to the modal, which retries.
		if (
			refreshedAccount?.status !==
				GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED &&
			! refreshedAccount?.pendingContainerId
		) {
			throw new Error( CREATED_NOT_CONNECTED_MESSAGE );
		}
	};

	return (
		<>
			<AppButton
				eventName="gla_google_tag_manager_create_container_button_click"
				eventProps={ { context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } }
				onClick={ handleOpen }
				isLink
			>
				{ __( 'Create new container', 'google-listings-and-ads' ) }
			</AppButton>

			{ isOpen && (
				<CreateContainerModal
					onCreated={ handleCreated }
					onRequestClose={ handleClose }
				/>
			) }
		</>
	);
}
