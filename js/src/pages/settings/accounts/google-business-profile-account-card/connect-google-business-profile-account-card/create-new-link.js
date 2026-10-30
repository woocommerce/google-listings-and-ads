/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../constants';
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

const LINK_CONFIG_BY_TYPE = {
	account: {
		eventName: 'gla_google_business_profile_create_account_button_click',
		label: __( 'Create new account', 'google-listings-and-ads' ),
	},
	location: {
		eventName: 'gla_google_business_profile_create_location_button_click',
		label: __( 'Create new location', 'google-listings-and-ads' ),
	},
};

/**
 * Renders the external link to create a new Google Business Profile account or location. This is
 * only possible in Google's own UI, so the link opens off-site in a new tab. The card hosting the
 * link shows {@see CreateNewRefreshNotice} once it's clicked, outside its own notice.
 *
 * @fires gla_google_business_profile_create_account_button_click with `{ context: 'settings-business-profile' }`.
 * @fires gla_google_business_profile_create_location_button_click with `{ context: 'settings-business-profile' }`.
 *
 * @param {Object} props Component props.
 * @param {'account'|'location'} props.type What the link creates.
 * @param {Function} props.onClick Called once the link is clicked.
 * @return {JSX.Element} The link.
 */
export default function CreateNewLink( { type, onClick } ) {
	const { eventName, label } = LINK_CONFIG_BY_TYPE[ type ];
	const createUrl = getGoogleBusinessProfileCreateUrl();
	const accountAwareUrl = useGoogleAccountAwareUrl( createUrl );

	const handleClick = () => {
		recordGlaEvent( eventName, {
			context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
		} );
		onClick();
	};

	return (
		<ExternalLink href={ accountAwareUrl } onClick={ handleClick }>
			{ label }
		</ExternalLink>
	);
}
