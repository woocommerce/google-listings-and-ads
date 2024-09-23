/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { noop } from 'lodash';
import GridiconCheckmark from 'gridicons/dist/checkmark';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import CampaignPreview from '.~/components/paid-ads/campaign-preview';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppButton from '.~/components/app-button';
import SkipButton from './skip-button';
import FreeAdCredit from '.~/components/free-ad-credit';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import useFreeAdCredit from '.~/hooks/useFreeAdCredit';
import './paid-ads-features-section.scss';

function FeatureList() {
	const featuresItems = [
		{
			Icon: GridiconCheckmark,
			content: __(
				'Set a daily budget, and only pay when people click on your ads.',
				'google-listings-and-ads'
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

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-paid-ads', link_id: 'paid-ads-with-performance-max-campaigns-learn-more', href: 'https://support.google.com/google-ads/answer/10724817' }`
 */

/**
 * Renders a section layout to elaborate on the features of paid ads and show the buttons
 * for the next actions: skip or continue the paid ads setup.
 *
 * @param {Object} props React props.
 * @param {boolean} props.hidePaidAdsSetupFooterButtons Whether to hide the buttons at the section footer.
 * @param {() => void} props.onSkipClick Callback called when the skip button is clicked.
 * @param {() => void} props.onCreateCampaignClick Callback called when the create campaign button is clicked.
 * @param {boolean} [props.disableCreateButton=false] Whether to disable the create campaign button.
 */
export default function PaidAdsFeaturesSection( {
	hidePaidAdsSetupFooterButtons,
	onSkipClick = noop,
	onCreateCampaignClick = noop,
	disableCreateButton = false,
} ) {
	const hasFreeAdCredit = useFreeAdCredit();
	const { hasGoogleAdsConnection } = useGoogleAdsAccount();
	const hideFooterButtons =
		! hasGoogleAdsConnection || hidePaidAdsSetupFooterButtons;

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
					<VerticalGapLayout size="medium">
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
								<FeatureList />
							</FlexBlock>
							<FlexItem>
								<CampaignPreview />
							</FlexItem>
						</Flex>

						{ hasFreeAdCredit && <FreeAdCredit /> }
					</VerticalGapLayout>
				</Section.Card.Body>
				<Section.Card.Footer hidden={ hideFooterButtons }>
					<SkipButton
						text={ __(
							'Skip this step for now',
							'google-listings-and-ads'
						) }
						onClick={ onSkipClick }
					/>

					<AppButton
						isPrimary
						text={ __(
							'Create campaign',
							'google-listings-and-ads'
						) }
						disabled={ disableCreateButton }
						onClick={ onCreateCampaignClick }
						eventName="gla_onboarding_open_paid_ads_setup_button_click"
					/>
				</Section.Card.Footer>
			</Section.Card>
		</Section>
	);
}
