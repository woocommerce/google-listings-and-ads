/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem, Card } from '@wordpress/components';
import GridiconCheckmark from 'gridicons/dist/checkmark';

/**
 * Internal dependencies
 */
import { ContentLink } from '.~/components/guide-page-content';
import CampaignPreview from '.~/components/paid-ads/campaign-preview';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import FreeAdCredit from '.~/components/free-ad-credit';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import './index.scss';

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
		<div className="gla-paid-features-section__feature-list">
			{ featuresItems.map( ( { Icon, content }, idx ) => (
				<Flex key={ idx } align="flex-start">
					<Icon size="18" />
					<FlexBlock>{ content }</FlexBlock>
				</Flex>
			) ) }
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
		<VerticalGapLayout size="medium" className="gla-paid-features-section">
			<Flex
				align="center"
				gap={ 9 }
				className="gla-paid-features-section__content"
			>
				<FlexItem>
					<CampaignPreview />
				</FlexItem>
				<FlexBlock>
					<FeatureList />
				</FlexBlock>
			</Flex>
			<FreeAdCredit />
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
