/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../../constants';
import { getGoogleBusinessProfileCreateUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';

/**
 * Clicking the link to create a new Google Business Profile location off-site.
 *
 * @event gla_google_business_profile_create_location_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

const handleClick = ( onClick ) => () => {
	recordGlaEvent(
		'gla_google_business_profile_create_location_button_click',
		{
			context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
		}
	);
	onClick();
};

/**
 * Renders the "Create new location" external link. Creating a Google Business Profile location is only
 * possible through Google's own UI, so this always opens off-site in a new tab.
 *
 * @param {Object} props Component props.
 * @param {Function} [props.onClick] Called after the tracking event fires, alongside it.
 *
 * @fires gla_google_business_profile_create_location_button_click
 *
 * @return {JSX.Element} The link.
 */
export default function CreateNewLocationLink( { onClick = noop } ) {
	const createUrl = getGoogleBusinessProfileCreateUrl();
	const accountAwareUrl = useGoogleAccountAwareUrl( createUrl );

	return (
		<ExternalLink
			href={ accountAwareUrl }
			onClick={ handleClick( onClick ) }
		>
			{ __( 'Create new location', 'google-listings-and-ads' ) }
		</ExternalLink>
	);
}
