/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem, Card } from '@wordpress/components';
import GridiconCheckmark from 'gridicons/dist/checkmark';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import { ContentLink } from '.~/components/guide-page-content';
import CampaignPreview from '.~/components/paid-ads/campaign-preview';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import '.~/setup-mc/setup-stepper/setup-paid-ads/paid-ads-features-section.scss';
import '.~/setup-ads/ads-stepper/setup-accounts/free-ad-credit/index.scss';
import VerticalGapLayout from '.~/components/vertical-gap-layout';

function FeatureList() {
	const featuresItems = [
		{
			Icon: GridiconCheckmark,
			content: __(
				'Reach more customer by advertising your products across Google Ads channels like Search, YouTube and Discover.',
				'google-listings-and-ads'
			),
		},
		{
			Icon: GridiconCheckmark,
			content: __(
				'Set a daily budget and only pay when people click on your ads.',
				'google-listings-and-ads'
			),
		},
		{
			Icon: GridiconCheckmark,
			content: createInterpolateElement(
				__(
					"Performance Max uses the best of Google's AI to show the most impactful ads for your products at the right time and place. <link>Learn more about Performance Max technology.</link>",
					'google-listings-and-ads'
				),
				{
					link: (
						<ContentLink
							href="https://support.google.com/google-ads/answer/10724817"
							context="campaign-creation-performance-max"
						/>
					),
				}
			),
		},
	];

	return (
		<div className="gla-paid-ads-features-section__feature-list">
			{ featuresItems.map( ( { Icon, content }, idx ) => (
				<Flex key={ idx } align="flex-start">
					<Icon size="18" />
					<FlexBlock>{ content }</FlexBlock>
				</Flex>
			) ) }
		</div>
	);
}

function FreeAdCredit() {
	return (
		<div className="gla-free-ad-credit">
			<GridiconGift />
			<div>
				<div className="gla-free-ad-credit__title">
					{ __(
						'Claim $500 in ads credit when you spend your first $500 with Google Ads. Terms and conditions apply.',
						'google-listings-and-ads'
					) }
				</div>
			</div>
		</div>
	);
}

/**
 * Returns a Card with the given content.
 *
 * @return {Card} Card with title and content.
 */
const PaidFeatures = () => {
	return (
		<VerticalGapLayout size="medium">
			<Flex
				className="gla-paid-ads-features-section__content"
				align="center"
				gap={ 9 }
			>
				<FlexItem>
					<CampaignPreview />
				</FlexItem>
				<FlexBlock>
					<FeatureList />
				</FlexBlock>
				<FreeAdCredit />
			</Flex>
			<AddPaidCampaignButton
				isPrimary
				isSecondary={ false }
				isSmall={ false }
				eventProps={ {
					context: 'add-paid-campaign-promotion',
				} }
			>
				{ __( 'Create Campaign', 'google-listings-and-ads' ) }
			</AddPaidCampaignButton>
		</VerticalGapLayout>
	);
};

export default PaidFeatures;
