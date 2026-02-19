/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Flex,
	FlexItem,
	FlexBlock,
	SelectControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import { glaData } from '~/constants';
import { getGetStartedUrl } from '~/utils/urls';
import { addBaseEventProperties } from '~/utils/tracks';
import './google-ads-promo.scss';

const { adsSetupComplete } = glaData;
const GET_STARTED_URL = getGetStartedUrl();
const BASE_EVENT_PROPS = addBaseEventProperties( {} );

const CONTENT = {
	title: __( 'Get your products on Google', 'google-listings-and-ads' ),
	description: __(
		'Sync your products to reach customers when they’re searching for products like yours across Google',
		'google-listings-and-ads'
	),
	cta: (
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
	),
};

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	if ( adsSetupComplete ) {
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
	}

	const { title, description, cta } = CONTENT;

	return (
		<Flex
			className="gla-channel-visibility-google-ads-promo"
			direction="column"
			gap={ 4 }
		>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<img
							src={ googleLogoURL }
							alt={ __(
								'Google Logo',
								'google-listings-and-ads'
							) }
							width={ 24 }
							height={ 24 }
						/>
					</FlexItem>
					<FlexItem>
						{ __( 'Google', 'google-listings-and-ads' ) }
					</FlexItem>
				</Flex>
			</FlexBlock>

			<Flex className="gla-channel-visibility-google-ads-promo-content">
				<FlexBlock>
					<strong>{ title }</strong>
				</FlexBlock>
				<FlexBlock>
					<p>{ description }</p>
				</FlexBlock>

				<FlexBlock>{ cta }</FlexBlock>
			</Flex>
		</Flex>
	);
};

export default GoogleAdsPromo;
