/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { addBaseEventProperties } from '~/utils/tracks';
import GetStartedCTA from './get-started-cta';

const BASE_EVENT_PROPS = addBaseEventProperties( {} );

const GoogleAdsPromoCTA = ( { onDismiss } ) => {
	return (
		<Flex className="gla-channel-visibility-google-ads-promo-cta" gap={ 4 }>
			<FlexBlock>
				<GetStartedCTA />
			</FlexBlock>

			<FlexBlock>
				<AppButton
					eventName="gla_google_ads_promo_dismiss_click"
					eventProps={ BASE_EVENT_PROPS }
					isTertiary
					onClick={ onDismiss }
				>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default GoogleAdsPromoCTA;
