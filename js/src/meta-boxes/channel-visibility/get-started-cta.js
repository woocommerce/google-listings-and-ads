/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';

const GET_STARTED_URL = getGetStartedUrl();

const GetStartedCTA = ( { context } ) => {
	return (
		<AppButton
			href={ GET_STARTED_URL }
			eventName="gla_google_ads_promo_create_campaign_click"
			eventProps={ {
				href: GET_STARTED_URL,
				context,
			} }
			isSecondary
		>
			{ __( 'Get started', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
