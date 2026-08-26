/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getTagManagerCreateAccountUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Clicking the link to create a new Google Tag Manager account off-site.
 *
 * @event gla_google_tag_manager_create_account_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

const handleClick = () => {
	recordGlaEvent( 'gla_google_tag_manager_create_account_button_click', {
		context: 'settings-tag-manager',
	} );
};

/**
 * Renders the "Create new account" external link shown across every not-yet-connected
 * account-selection status (zero accounts, single account, multiple accounts) — creating a GTM
 * account is only possible through Google's own UI (the GTM API has no account-creation
 * endpoint), so this always opens off-site in a new tab.
 *
 * @fires gla_google_tag_manager_create_account_button_click
 *
 * @return {JSX.Element} The link.
 */
export default function CreateNewAccountLink() {
	return (
		<ExternalLink
			href={ getTagManagerCreateAccountUrl() }
			onClick={ handleClick }
		>
			{ __( 'Create new account', 'google-listings-and-ads' ) }
		</ExternalLink>
	);
}
