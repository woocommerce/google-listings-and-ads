/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getGoogleTagManagerCreateContainerUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Clicking the link to create a new Google Tag Manager container off-site.
 *
 * @event gla_google_tag_manager_create_container_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

const handleClick = () => {
	recordGlaEvent( 'gla_google_tag_manager_create_container_button_click', {
		context: 'settings-tag-manager',
	} );
};

/**
 * Renders the "Create new container" external link shown from the container-selection detail —
 * both when the connected account has zero containers (replacing the selector entirely) and when
 * it already has one or more (as an inline link beside the populated selector). Creating a GTM
 * container is only possible through Google's own UI (the plugin never calls
 * `accounts.containers.create`), so this always opens off-site in a new tab.
 *
 * @fires gla_google_tag_manager_create_container_button_click
 *
 * @return {JSX.Element} The link.
 */
export default function CreateNewContainerLink() {
	return (
		<ExternalLink
			href={ getGoogleTagManagerCreateContainerUrl() }
			onClick={ handleClick }
		>
			{ __( 'Create new container', 'google-listings-and-ads' ) }
		</ExternalLink>
	);
}
