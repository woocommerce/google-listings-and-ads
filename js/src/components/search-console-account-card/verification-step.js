/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';

/**
 * Clicking on the button to verify the Search Console property.
 *
 * @event gla_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the verification step of the Search Console connect flow.
 *
 * Confirmed genuinely net-new UI — no "single click to verify/confirm" pattern exists anywhere
 * in this codebase today. This is modeled on YouTube's one-click "Complete setup" button
 * (button + indicator + loading state). Selecting or creating a property does not, by itself,
 * mean the property is verified — this step is always shown separately, and is always
 * informational rather than styled as an error or warning, since tag placement happens
 * automatically and this is simply a single confirmation click.
 *
 * When the merchant can't self-verify, they're routed to Google's own "request access" flow
 * instead, via a backend-supplied URL.
 *
 * @fires gla_search_console_verify_button_click
 */
const VerificationStep = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const { searchConsoleAccount } = useSearchConsoleAccount();

	const [ fetchVerify, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/verify`,
		method: 'POST',
	} );

	const handleVerifyClick = async () => {
		try {
			await fetchVerify();
			invalidateResolution( 'getSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to verify your Search Console property. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const canSelfVerify = searchConsoleAccount?.can_self_verify !== false;

	if ( ! canSelfVerify ) {
		return (
			<AccountCard
				appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
				description={ __(
					"We couldn't automatically verify your Search Console property. Request access from your Search Console property owner to continue.",
					'google-listings-and-ads'
				) }
				indicator={
					<ExternalLink
						href={ searchConsoleAccount?.request_access_url }
					>
						{ __( 'Request access', 'google-listings-and-ads' ) }
					</ExternalLink>
				}
			/>
		);
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ __(
				"We've automatically placed a verification tag on your site. Verify your property to finish connecting.",
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton
					isSecondary
					loading={ loading }
					eventName="gla_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleVerifyClick }
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default VerificationStep;
