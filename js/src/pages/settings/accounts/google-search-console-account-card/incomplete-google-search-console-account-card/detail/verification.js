/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { SEARCH_CONSOLE_EVENT_CONTEXT } from '../../constants';
import NoticeDetail from '../notice-detail';

const VERIFY_SITE_HELP_URL =
	'https://support.google.com/webmasters/answer/9008080';
const REQUEST_ACCESS_HELP_URL =
	'https://support.google.com/webmasters/answer/7687615';

/**
 * Clicking on the button to verify the site for the selected Google Search Console property.
 *
 * @event gla_google_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the verification step's detail, shown when the connected Google account isn't a
 * verified owner of the selected property yet: a single "Verify site" action that adds the
 * verification tag to the site and asks Google to verify it. After a failed attempt, it also
 * points to Google's help on requesting access from the property's owner.
 *
 * @fires gla_google_search_console_verify_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function Verification() {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [ hasFailed, setHasFailed ] = useState( false );

	const [ fetchVerify, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/verify`,
		method: 'POST',
	} );

	const handleVerifyClick = async () => {
		try {
			await fetchVerify();
			invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
		} catch ( error ) {
			setHasFailed( true );
			createNotice(
				'error',
				__(
					'Unable to verify your site. Please try again, or ask the owner of the Search Console property for access.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const actions = [
		<AppButton
			key="verify"
			eventName="gla_google_search_console_verify_button_click"
			eventProps={ { context: SEARCH_CONSOLE_EVENT_CONTEXT } }
			onClick={ handleVerifyClick }
			loading={ loading }
			isSecondary
		>
			{ __( 'Verify site', 'google-listings-and-ads' ) }
		</AppButton>,
	];

	if ( hasFailed ) {
		actions.push(
			<ExternalLink key="request-access" href={ REQUEST_ACCESS_HELP_URL }>
				{ __(
					'Request access from the property owner',
					'google-listings-and-ads'
				) }
			</ExternalLink>
		);
	} else {
		actions.push(
			<ExternalLink key="learn-more" href={ VERIFY_SITE_HELP_URL }>
				{ __( 'Learn more', 'google-listings-and-ads' ) }
			</ExternalLink>
		);
	}

	return (
		<NoticeDetail
			status="info"
			title={ __(
				'Verify your site with Google',
				'google-listings-and-ads'
			) }
			body={ __(
				'Google Search Console needs to confirm that you own this site before it can share search data. Verify your site and we will add the verification tag for you.',
				'google-listings-and-ads'
			) }
			actions={ actions }
		/>
	);
}
