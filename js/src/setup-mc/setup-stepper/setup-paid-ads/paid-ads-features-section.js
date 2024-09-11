/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import GridiconCheckmark from 'gridicons/dist/checkmark';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import CampaignPreview from '.~/components/paid-ads/campaign-preview';
import './paid-ads-features-section.scss';

function FeatureList( { hideBudgetContent } ) {
	const featuresItems = [
		{
			Icon: GridiconCheckmark,
			content: __(
				'Set a daily budget, and only pay when people click on your ads.',
				'google-listings-and-ads'
			),
		},
	];

	if ( ! hideBudgetContent ) {
		featuresItems.push( {
			Icon: GridiconGift,
			content: __(
				'Claim $500 in ads credit when you spend your first $500 with Google Ads. Terms and conditions apply.',
				'google-listings-and-ads'
			),
		} );
	}

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

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-paid-ads', link_id: 'paid-ads-with-performance-max-campaigns-learn-more', href: 'https://support.google.com/google-ads/answer/10724817' }`
 */

/**
 * Renders a section layout to elaborate on the features of paid ads and show the buttons
 * for the next actions: skip or continue the paid ads setup.
 *
 * @param {Object} props React props.
 * @param {boolean} props.hideBudgetContent Whether to hide the content about the ad budget.
 */
export default function PaidAdsFeaturesSection( { hideBudgetContent } ) {
	return (
		<Section
			className="gla-paid-ads-features-section"
			topContent={
				<Pill>{ __( 'Recommended', 'google-listings-and-ads' ) }</Pill>
			}
			title={ __(
				'Performance Max campaign',
				'google-listings-and-ads'
			) }
			description={
				<>
					<p>
						{ __(
							'Performance Max uses the best of Google’s AI to show the most impactful ads for your products at the right time and place. Google will use your product data to create ads for this campaign. ',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="setup-paid-ads"
							linkId="paid-ads-with-performance-max-campaigns-learn-more"
							href="https://support.google.com/google-ads/answer/10724817"
						>
							{ __(
								'Learn more about Performance Max',
								'google-listings-and-ads'
							) }
						</AppDocumentationLink>
					</p>
				</>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<Flex
						className="gla-paid-ads-features-section__content"
						align="center"
						gap={ 9 }
					>
						<FlexBlock>
							<Section.Card.Title>
								{ __(
									'Drive more sales with Performance Max',
									'google-listings-and-ads'
								) }
							</Section.Card.Title>
							<div className="gla-paid-ads-features-section__subtitle">
								{ __(
									'Reach more customers by advertising your products across Google Ads channels like Search, YouTube and Discover. Set up your campaign now so your products are included as soon as they’re approved.',
									'google-listings-and-ads'
								) }
							</div>
							<FeatureList
								hideBudgetContent={ hideBudgetContent }
							/>
						</FlexBlock>
						<FlexItem>
							<CampaignPreview />
						</FlexItem>
					</Flex>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
}
