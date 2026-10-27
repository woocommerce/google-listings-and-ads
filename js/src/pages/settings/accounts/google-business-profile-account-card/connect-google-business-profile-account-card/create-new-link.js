/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink, Flex } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../constants';
import { getGoogleBusinessProfileCreateUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';
import NoticeDetail from '../notice-detail';

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
		refreshText: __(
			'Refresh the page to see your new account.',
			'google-listings-and-ads'
		),
	},
	location: {
		eventName: 'gla_google_business_profile_create_location_button_click',
		label: __( 'Create new location', 'google-listings-and-ads' ),
		refreshText: __(
			'Refresh the page to see your new location.',
			'google-listings-and-ads'
		),
	},
};

/**
 * Renders the external link to create a new Google Business Profile account or location. This is
 * only possible in Google's own UI, so the link opens off-site in a new tab. Once clicked, it shows
 * a reminder to refresh the page, because nothing tells this page that a new one exists.
 *
 * @fires gla_google_business_profile_create_account_button_click with `{ context: 'settings-business-profile' }`.
 * @fires gla_google_business_profile_create_location_button_click with `{ context: 'settings-business-profile' }`.
 *
 * @param {Object} props Component props.
 * @param {'account'|'location'} props.type What the link creates.
 * @return {JSX.Element} The link, preceded by the refresh reminder once clicked.
 */
export default function CreateNewLink( { type } ) {
	const { eventName, label, refreshText } = LINK_CONFIG_BY_TYPE[ type ];
	const createUrl = getGoogleBusinessProfileCreateUrl();
	const accountAwareUrl = useGoogleAccountAwareUrl( createUrl );
	const [ hasClicked, setHasClicked ] = useState( false );

	const handleClick = () => {
		recordGlaEvent( eventName, {
			context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
		} );
		setHasClicked( true );
	};

	return (
		<Flex direction="column" align="flex-start" gap={ 4 }>
			{ hasClicked && (
				<NoticeDetail status="info" body={ <p>{ refreshText }</p> } />
			) }
			<ExternalLink href={ accountAwareUrl } onClick={ handleClick }>
				{ label }
			</ExternalLink>
		</Flex>
	);
}
