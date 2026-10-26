/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';
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
 * Renders the "Create new account" external link. Creating a Google Business Profile account is only
 * possible through Google's own UI, so this always opens off-site in a new tab. Once clicked, shows
 * a reminder to refresh the page to see the new account — there's no webhook telling this page
 * a new account now exists.
 *
 * @fires gla_google_business_profile_create_account_button_click
 *
 * @return {JSX.Element} The link, preceded by the refresh reminder once clicked.
 */
export default function CreateNewAccountLink() {
	const createUrl = getGoogleBusinessProfileCreateUrl();
	const accountAwareUrl = useGoogleAccountAwareUrl( createUrl );
	const [ hasClicked, setHasClicked ] = useState( false );

	const handleClick = () => {
		recordGlaEvent(
			'gla_google_business_profile_create_account_button_click',
			{
				context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
			}
		);
		setHasClicked( true );
	};

	return (
		<>
			{ hasClicked && (
				<div className="gla-google-business-profile-account-card__refresh-notice">
					<NoticeDetail
						status="info"
						body={
							<p>
								{ __(
									'Refresh the page to see your new account.',
									'google-listings-and-ads'
								) }
							</p>
						}
					/>
				</div>
			) }
			<ExternalLink href={ accountAwareUrl } onClick={ handleClick }>
				{ __( 'Create new account', 'google-listings-and-ads' ) }
			</ExternalLink>
		</>
	);
}
