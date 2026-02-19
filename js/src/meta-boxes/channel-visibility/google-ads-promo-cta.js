/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';
import { addBaseEventProperties } from '~/utils/tracks';

const GET_STARTED_URL = getGetStartedUrl();
const BASE_EVENT_PROPS = addBaseEventProperties( {} );

const GoogleAdsPromoCTA = () => {
	return (
		<Flex className="gla-channel-visibility-google-ads-promo-cta" gap={ 4 }>
			<FlexBlock>
				<AppButton
					href={ GET_STARTED_URL }
					eventName="gla_google_ads_promo_get_started_click"
					eventProps={ BASE_EVENT_PROPS }
					isSecondary
				>
					{ __( 'Get started', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexBlock>

			<FlexBlock>
				<AppButton
					eventName="gla_google_ads_promo_dismiss_click"
					eventProps={ BASE_EVENT_PROPS }
					isTertiary
				>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default GoogleAdsPromoCTA;
