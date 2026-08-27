/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import GridiconCheckmark from 'gridicons/dist/checkmark';

/**
 * Internal dependencies
 */
import { ContentLink } from '~/components/guide-page-content';
import CampaignPreview from '~/components/paid-ads/campaign-preview';
import AddPaidCampaignButton from '~/components/paid-ads/add-paid-campaign-button';
import VerticalGapLayout from '~/components/vertical-gap-layout';
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
							context="campaign-creation-performance-max"
							href="https://support.google.com/google-ads/answer/10724817"
						/>
					),
				}
			),
		},
	];

	return (
		<div className="gla-paid-features__feature-list">
			{ featuresItems.map( ( { Icon, content }, idx ) => (
				<Flex align="flex-start" key={ idx }>
					<Icon size="18" />
					<FlexBlock>{ content }</FlexBlock>
				</Flex>
			) ) }
		</div>
	);
}

/**
 * Returns a component with paid features content.
 *
 * @return {JSX.Element} Paid Features component.
 */
const PaidFeatures = () => {
	return (
		<VerticalGapLayout className="gla-paid-features" size="medium">
			<Flex
				align="center"
				className="gla-paid-features__content"
				gap={ 9 }
			>
				<FlexItem>
					<CampaignPreview />
				</FlexItem>
				<FlexBlock>
					<FeatureList />
				</FlexBlock>
			</Flex>
			<AddPaidCampaignButton
				eventProps={ {
					context: 'add-paid-campaign-promotion',
				} }
				isSecondary={ false }
				isSmall={ false }
				isPrimary
			>
				{ __( 'Create Campaign', 'google-listings-and-ads' ) }
			</AddPaidCampaignButton>
		</VerticalGapLayout>
	);
};

export default PaidFeatures;
