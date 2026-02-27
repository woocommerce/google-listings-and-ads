/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';

/**
 * Google Ads Promo "Get started" button is clicked.
 *
 * @event gla_google_ads_promo_create_campaign_click
 * @property {string} context Context of the Google Ads Promo.
 * @property {string} href URL of the "Get started" button.
 */

/**
 * Get Started CTA component.
 *
 * @fires gla_google_ads_promo_create_campaign_click with `{ context: CHANNEL_VISIBILITY_CONTEXT, href: getGetStartedUrl() }`.
 *
 * @return {JSX.Element} The Get Started CTA component.
 */
const GetStartedCTA = () => {
	const getStartedUrl = getGetStartedUrl();

	return (
		<AppButton
			href={ getStartedUrl }
			eventName="gla_google_ads_promo_create_campaign_click"
			eventProps={ {
				href: getStartedUrl,
				context: CHANNEL_VISIBILITY_CONTEXT,
			} }
			isSecondary
		>
			{ __( 'Get started', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
