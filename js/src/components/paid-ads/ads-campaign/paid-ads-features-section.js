/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import GridiconCheckmark from 'gridicons/dist/checkmark';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import CampaignPreview from '~/components/paid-ads/campaign-preview';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './paid-ads-features-section.scss';

function FeatureList() {
	const featuresItems = [
		{
			Icon: GridiconCheckmark,
			content: __(
				'Performance Max puts your products in front of active customers on Search, Shopping, YouTube, and more.',
				'google-listings-and-ads'
			),
		},
		{
			Icon: GridiconCheckmark,
			content: __(
				"By combining your unique business insights with Google AI, you'll capture high-value customers by reaching the right audience at the right time—while staying perfectly aligned with your budget and goals.",
				'google-listings-and-ads'
			),
		},
	];

	return (
		<div className="gla-paid-ads-features-section__feature-list">
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
 * @fires gla_documentation_link_click with `{ context: 'setup-paid-ads', link_id: 'paid-ads-with-performance-max-campaigns-learn-more', href: 'https://support.google.com/google-ads/answer/10724817' }`
 */

/**
 * Renders a section layout to elaborate on the features of paid ads and show the buttons
 * for the next actions: skip or continue the paid ads setup.
 */
export default function PaidAdsFeaturesSection() {
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	let description = __(
		'Performance Max uses the best of Google’s AI to show the most impactful ads for your products at the right time and place.',
		'google-listings-and-ads'
	);

	if ( hasGoogleMCConnection ) {
		description += ` ${ __(
			'Google will use your product data to create ads for this campaign.',
			'google-listings-and-ads'
		) }`;
	}

	return (
		<Section
			className="gla-paid-ads-features-section"
			description={
				<>
					<p>{ description }</p>
					<p>
						<AppDocumentationLink
							context="setup-paid-ads"
							href="https://support.google.com/google-ads/answer/10724817"
							linkId="paid-ads-with-performance-max-campaigns-learn-more"
						>
							{ __(
								'Learn more about Performance Max',
								'google-listings-and-ads'
							) }
						</AppDocumentationLink>
					</p>
				</>
			}
			title={ __(
				'Performance Max campaign',
				'google-listings-and-ads'
			) }
			topContent={
				<Pill>{ __( 'Recommended', 'google-listings-and-ads' ) }</Pill>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<Flex
						align="flex-start"
						className="gla-paid-ads-features-section__content"
						gap={ 9 }
					>
						<FlexBlock>
							<Section.Card.Title>
								{ __(
									'Grow your business and connect with high-intent shoppers across Google—all from a single campaign.',
									'google-listings-and-ads'
								) }
							</Section.Card.Title>
							<FeatureList />
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
