/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';

/**
 * Google Ads Promo "Get started" button is clicked.
 *
 * @event gla_google_ads_promo_get_started_click
 * @property {string} context Context of the Google Ads Promo.
 * @property {string} href URL of the "Get started" button.
 */

/**
 * Get Started CTA component.
 *
 * @fires gla_google_ads_promo_get_started_click with `{ context: channel-visibility-meta-box, href: 'admin.php?page=wc-admin&path=%2Fgoogle%2Fsetup-mc' }`.
 *
 * @return {JSX.Element} The Get Started CTA component.
 */
const GetStartedCTA = () => {
	const onboardingUrl = getOnboardingUrl();

	return (
		<AppButton
			href={ onboardingUrl }
			eventName="gla_google_ads_promo_get_started_click"
			eventProps={ {
				href: onboardingUrl,
				context: CHANNEL_VISIBILITY_CONTEXT,
			} }
			isSecondary
		>
			{ __( 'Get started', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
