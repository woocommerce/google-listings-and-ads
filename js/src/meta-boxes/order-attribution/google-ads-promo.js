/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import { glaData } from '~/constants';
import { getGetStartedUrl, getCreateCampaignUrl } from '~/utils/urls';
import { addBaseEventProperties } from '~/utils/tracks';
import { hasRecentPaidCampaigns } from '../utils';
import './google-ads-promo.scss';

const { adsSetupComplete } = glaData;
const GET_STARTED_URL = getGetStartedUrl();
const CREATE_CAMPAIGN_URL = getCreateCampaignUrl();
const BASE_EVENT_PROPS = addBaseEventProperties( {} );

const CONTENT = adsSetupComplete
	? {
			title: __(
				'Get more sales with Google Ads',
				'google-listings-and-ads'
			),
			description: __(
				'Launch a Google Ads campaign and get your products discovered by high-intent shoppers across Google',
				'google-listings-and-ads'
			),
			cta: (
				<AppButton
					href={ CREATE_CAMPAIGN_URL }
					eventName="gla_google_ads_promo_create_campaign_click"
					eventProps={ BASE_EVENT_PROPS }
					isSecondary
				>
					{ __( 'Create campaign', 'google-listings-and-ads' ) }
				</AppButton>
			),
	  }
	: {
			title: __(
				'Get your products on Google',
				'google-listings-and-ads'
			),
			description: __(
				'Sync your products to reach customers when they’re searching for products like yours across Google',
				'google-listings-and-ads'
			),
			cta: (
				<AppButton
					href={ GET_STARTED_URL }
					eventName="gla_google_ads_promo_get_started_click"
					eventProps={ BASE_EVENT_PROPS }
					isSecondary
				>
					{ __( 'Get started', 'google-listings-and-ads' ) }
				</AppButton>
			),
	  };

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	const { data: campaigns, loading } = useAdsCampaigns();

	if ( loading || ! Array.isArray( campaigns ) ) {
		return null;
	}

	if ( hasRecentPaidCampaigns( campaigns ) ) {
		return null;
	}

	const { title, description, cta } = CONTENT;

	return (
		<Flex className="gla-google-ads-promo" direction="column" gap={ 4 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center">
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
					<FlexBlock>
						<strong>{ title }</strong>
					</FlexBlock>
				</Flex>
			</FlexBlock>

			<FlexBlock>
				<p>{ description }</p>
			</FlexBlock>

			<FlexBlock>{ cta }</FlexBlock>
		</Flex>
	);
};

export default GoogleAdsPromo;
