/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';
import { warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useVerifySearchConsoleProperty from '~/hooks/useVerifySearchConsoleProperty';
import SearchConsoleNoticeAccountCard from './notice-account-card';

/**
 * Clicking on the button to verify the Search Console property, either during the normal
 * verification step or after re-verification is needed following the "action needed" state.
 *
 * @event gla_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

// Google's own help article on verifying Search Console site ownership, linked from the
// verification step's "Learn more" action.
const VERIFICATION_LEARN_MORE_URL =
	'https://support.google.com/webmasters/answer/9008080';

const DESCRIPTION = __(
	'See how your store performs in Google Search.',
	'google-listings-and-ads'
);

/**
 * Renders the verification step: a single "Verify site" click for the normal case, or a link
 * into Google's "request access" flow when the merchant can't self-verify.
 *
 * @fires gla_search_console_verify_button_click
 *
 * @return {JSX.Element} The account card.
 */
export default function VerificationSearchConsoleAccountCard() {
	const { account } = useSearchConsoleAccount();
	const { onClick: handleVerifyClick, loading } =
		useVerifySearchConsoleProperty();

	const canSelfVerify = account?.can_self_verify !== false;

	if ( ! canSelfVerify ) {
		return (
			<SearchConsoleNoticeAccountCard
				description={ DESCRIPTION }
				status="warning"
				icon={ warning }
				badgeLabel={ __( 'Action needed', 'google-listings-and-ads' ) }
				title={ __(
					"We couldn't verify your site",
					'google-listings-and-ads'
				) }
				body={ __(
					'Request access from your Search Console property owner to continue.',
					'google-listings-and-ads'
				) }
				action={
					<ExternalLink href={ account?.request_access_url }>
						{ __( 'Request access', 'google-listings-and-ads' ) }
					</ExternalLink>
				}
			/>
		);
	}

	return (
		<SearchConsoleNoticeAccountCard
			description={ DESCRIPTION }
			status="warning"
			icon={ warning }
			badgeLabel={ __( 'Action needed', 'google-listings-and-ads' ) }
			title={ __(
				'Verify your site with Google',
				'google-listings-and-ads'
			) }
			body={ __(
				'A one-time verification is needed before Search Console can collect search data for your site. We add the verification tag for you.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton
					eventName="gla_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleVerifyClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
			secondaryAction={
				<ExternalLink href={ VERIFICATION_LEARN_MORE_URL }>
					{ __( 'Learn more', 'google-listings-and-ads' ) }
				</ExternalLink>
			}
		/>
	);
}
