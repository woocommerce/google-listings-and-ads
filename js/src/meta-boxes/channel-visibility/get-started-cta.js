/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';

const GetStartedCTA = ( { context } ) => {
	const getStartedUrl = getGetStartedUrl();

	return (
		<AppButton
			href={ getStartedUrl }
			eventName="gla_google_ads_promo_create_campaign_click"
			eventProps={ {
				href: getStartedUrl,
				context,
			} }
			isSecondary
		>
			{ __( 'Get started', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
