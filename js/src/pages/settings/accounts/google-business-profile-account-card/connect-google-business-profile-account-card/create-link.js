/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getGoogleBusinessProfileCreateUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';

/**
 * Clicking the link to create a new Google Business Profile account off-site.
 *
 * @event gla_google_business_profile_create_account_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Clicking the link to create a new Google Business Profile location off-site.
 *
 * @event gla_google_business_profile_create_location_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

const LINK_BY_TYPE = {
	account: {
		label: __( 'Create new account', 'google-listings-and-ads' ),
		eventName: 'gla_google_business_profile_create_account_button_click',
	},
	location: {
		label: __( 'Create new location', 'google-listings-and-ads' ),
		eventName: 'gla_google_business_profile_create_location_button_click',
	},
};

/**
 * Renders a "Create new account" or "Create new location" link. Creation is only possible in
 * Google's own UI, so the link opens Google in a new tab under the connected Google Account.
 *
 * @fires gla_google_business_profile_create_account_button_click
 * @fires gla_google_business_profile_create_location_button_click
 *
 * @param {Object} props Component props.
 * @param {'account'|'location'} props.type What the link creates.
 * @param {() => void} props.onClick Called after the link is clicked.
 * @return {JSX.Element} The link.
 */
export default function CreateLink( { type, onClick } ) {
	const { label, eventName } = LINK_BY_TYPE[ type ];
	const href = useGoogleAccountAwareUrl(
		getGoogleBusinessProfileCreateUrl()
	);

	const handleClick = () => {
		recordGlaEvent( eventName, { context: 'settings-business-profile' } );
		onClick();
	};

	return (
		<ExternalLink href={ href } onClick={ handleClick }>
			{ label }
		</ExternalLink>
	);
}
