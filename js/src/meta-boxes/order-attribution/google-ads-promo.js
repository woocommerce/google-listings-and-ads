/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { glaData } from '~/constants';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import { recordGlaEvent } from '~/utils/tracks';
import { getCreateCampaignUrl, getGetStartedUrl } from '~/utils/urls';
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
 * Event properties for Google Ads Promo.
 *
 * @type {Object}
 */
const GOOGLE_ADS_PROMO_EVENT_PROPS = {
	context: 'order-attribution-meta-box',
};

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	const { adsSetupComplete } = glaData;
	const { data: campaigns, loading } = useAdsCampaigns();
	const hasTrackedRef = useRef( false );

	// Checks if the component is ready to render
	const isReadyToRender =
		! loading &&
		Array.isArray( campaigns ) &&
		! hasRecentPaidCampaigns( campaigns );

	useEffect( () => {
		// Only fire if all conditions for rendering are met and not already tracked
		if ( ! hasTrackedRef.current && isReadyToRender ) {
			recordGlaEvent(
				'gla_google_ads_promo_shown',
				GOOGLE_ADS_PROMO_EVENT_PROPS
			);
			hasTrackedRef.current = true;
		}
	}, [ isReadyToRender ] );

	if ( ! isReadyToRender ) {
		return null;
	}

	const campaignUrl = getCreateCampaignUrl();
	const getStartedUrl = getGetStartedUrl();

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
						href={ campaignUrl }
						eventName="gla_google_ads_promo_create_campaign_click"
						eventProps={ {
							...GOOGLE_ADS_PROMO_EVENT_PROPS,
							href: campaignUrl,
						} }
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
						href={ getStartedUrl }
						eventName="gla_google_ads_promo_get_started_click"
						eventProps={ {
							...GOOGLE_ADS_PROMO_EVENT_PROPS,
							href: getStartedUrl,
						} }
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
