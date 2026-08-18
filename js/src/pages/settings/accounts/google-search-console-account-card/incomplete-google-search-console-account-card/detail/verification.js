/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useVerifyGoogleSearchConsoleProperty from '../../hooks/useVerifyGoogleSearchConsoleProperty';
import NoticeDetail from '../notice-detail';

/**
 * Clicking on the button to verify the Google Search Console property, either during the normal
 * verification step or after re-verification is needed following the "action needed" state.
 *
 * @event gla_google_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

// Google's own help article on verifying Google Search Console site ownership, linked from the
// verification step's "Learn more" action.
const VERIFICATION_LEARN_MORE_URL =
	'https://support.google.com/webmasters/answer/9008080';

/**
 * Renders the verification step's detail: a single "Verify site" click for the normal case, or
 * a link into Google's "request access" flow when the merchant can't self-verify.
 *
 * @fires gla_google_search_console_verify_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function Verification() {
	const { account } = useGoogleSearchConsoleAccount();
	const { verify: handleVerifyClick, loading } =
		useVerifyGoogleSearchConsoleProperty();

	const canSelfVerify = account.can_self_verify !== false;

	if ( ! canSelfVerify ) {
		return (
			<NoticeDetail
				status="warning"
				title={ __(
					"We couldn't verify your site",
					'google-listings-and-ads'
				) }
				body={ __(
					'Request access from your Google Search Console property owner to continue.',
					'google-listings-and-ads'
				) }
				actions={ [
					<ExternalLink
						key="request-access"
						href={ account.request_access_url }
					>
						{ __( 'Request access', 'google-listings-and-ads' ) }
					</ExternalLink>,
				] }
			/>
		);
	}

	return (
		<NoticeDetail
			status="warning"
			title={ __(
				'Verify your site with Google',
				'google-listings-and-ads'
			) }
			body={ __(
				'A one-time verification is needed before Google Search Console can collect search data for your site. We add the verification tag for you.',
				'google-listings-and-ads'
			) }
			actions={ [
				<AppButton
					key="verify"
					eventName="gla_google_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleVerifyClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>,
				<ExternalLink
					key="learn-more"
					href={ VERIFICATION_LEARN_MORE_URL }
				>
					{ __( 'Learn more', 'google-listings-and-ads' ) }
				</ExternalLink>,
			] }
		/>
	);
}
