/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import { addBaseEventProperties } from '~/utils/tracks';

const GET_STARTED_URL = getGetStartedUrl();
const BASE_EVENT_PROPS = addBaseEventProperties( {} );

const GoogleAdsPromoSetupCompleted = () => {
	return (
		<Flex
			gap={ 2 }
			align="center"
			justify="space-between"
			className="gla-channel-visibility-google-ads-promo"
		>
			<FlexItem>
				<Flex gap={ 4 } align="center">
					<FlexItem>
						<img
							src={ googleLogoURL }
							alt={ __(
								'Google Logo',
								'google-listings-and-ads'
							) }
							width={ 16 }
							height={ 16 }
						/>
					</FlexItem>
					<FlexItem>
						{ __( 'Google', 'google-listings-and-ads' ) }
					</FlexItem>
				</Flex>
			</FlexItem>

			<FlexItem>
				<SelectControl
					name="gla_channel_visibility_visibility"
					options={ [
						{
							label: 'Sync and show',
							value: 'sync-and-show',
						},
						{
							label: "Don't sync and show",
							value: 'dont-sync-and-show',
						},
					] }
					__nextHasNoMarginBottom
				/>
				<AppButton
					href={ GET_STARTED_URL }
					eventName="gla_google_ads_promo_get_started_click"
					eventProps={ BASE_EVENT_PROPS }
					isSecondary
				>
					{ __( 'Get started', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>
		</Flex>
	);
};

export default GoogleAdsPromoSetupCompleted;
