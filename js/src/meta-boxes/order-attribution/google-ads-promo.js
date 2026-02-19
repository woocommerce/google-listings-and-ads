/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { getCreateCampaignUrl, getGetStartedUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import './google-ads-promo.scss';

/**
 * Check if there are any recent paid campaigns.
 *
 * @param {Array} campaigns List of campaigns.
 *
 * @return {boolean} True if there are recent paid campaigns, false otherwise.
 */
const hasRecentPaidCampaigns = ( campaigns ) => {
	const fourteenDaysAgo = new Date();
	fourteenDaysAgo.setDate( fourteenDaysAgo.getDate() - 14 );

	return campaigns.some( ( campaign ) => {
		const campaignDate = new Date( campaign.start_date );

		return (
			campaign.status === 'enabled' &&
			campaign.type === 'performance_max' &&
			campaignDate >= fourteenDaysAgo
		);
	} );
};

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	const { adsSetupComplete } = glaData;
	const { data: campaigns, loading } = useAdsCampaigns();

	if (
		loading ||
		! Array.isArray( campaigns ) ||
		hasRecentPaidCampaigns( campaigns )
	) {
		return null;
	}

	const content = adsSetupComplete
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
						href={ getCreateCampaignUrl() }
						eventName="gla_google_ads_promo_create_campaign_click"
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
						href={ getGetStartedUrl() }
						eventName="gla_google_ads_promo_get_started_click"
						isSecondary
					>
						{ __( 'Get started', 'google-listings-and-ads' ) }
					</AppButton>
				),
		  };

	const { title, description, cta } = content;

	return (
		<Flex className="gla-google-ads-promo" direction="column" gap={ 3 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center">
					<FlexItem>
						<img
							className="gla-google-ads-promo__logo"
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
						<h3 className="gla-google-ads-promo__title">
							{ title }
						</h3>
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
