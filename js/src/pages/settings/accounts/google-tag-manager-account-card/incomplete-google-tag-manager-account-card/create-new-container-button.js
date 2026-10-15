/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '../constants';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

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
 * has one or more (as an inline link beside the populated selector). Renders nothing when the
 * connection can't create containers.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onClick Called when the link is clicked, alongside the tracking event.
 *
 * @fires gla_google_tag_manager_create_container_button_click
 *
 * @return {JSX.Element|null} The link, or `null` when creating isn't available.
 */
export default function CreateNewContainerButton( { onClick } ) {
	const { account } = useGoogleTagManagerAccount();

	if ( account?.canCreateContainer === false ) {
		return null;
	}

	return (
		<AppButton
			eventName="gla_google_tag_manager_create_container_button_click"
			eventProps={ { context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } }
			onClick={ onClick }
			isLink
		>
			{ __( 'Create new container', 'google-listings-and-ads' ) }
		</AppButton>
	);
}
