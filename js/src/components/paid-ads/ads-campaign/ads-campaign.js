/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentActions from '.~/components/stepper/step-content-actions';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import PaidAdsFaqsPanel from './faqs-panel';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import CampaignPreviewCard from '.~/components/paid-ads/campaign-preview/campaign-preview-card';
import BudgetSection from '.~/components/paid-ads/budget-section';
import BillingCard from '.~/components/paid-ads/billing-card';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * @typedef {import('.~/components/adaptive-form/adaptive-form-context').AdaptiveFormContext} AdaptiveFormContext
 */

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * Renders the container of the form content for campaign management.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 *
 * @fires gla_documentation_link_click with `{ context: 'create-ads' | 'edit-ads' | 'setup-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. The displayCountries property will be used to fetch budget recommendation data.
 * @param {string} props.headerTitle The title of the step.
 * @param {'create-ads'|'edit-ads'|'setup-ads'|'setup-mc'} props.context A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 * @param {(AdaptiveFormContext) => JSX.Element|JSX.Element} [props.skipButton] A React element or function to render the "Skip" button. If a function is passed, it receives the form context and returns the button element.
 * @param {(AdaptiveFormContext) => JSX.Element|JSX.Element} [props.continueButton] A React element or function to render the "Continue" button. If a function is passed, it receives the form context and returns the button element.
 */
export default function AdsCampaign( {
	campaign,
	headerTitle,
	context,
	skipButton,
	continueButton,
} ) {
	const formContext = useAdaptiveFormContext();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const isOnboardingFlow = context === 'setup-mc';
	const showCampaignPreviewCard =
		context === 'setup-ads' ||
		context === 'create-ads' ||
		context === 'edit-ads';
	// only show the billing card during onboarding or setup Ads flow.
	// For creating/editing a campaign, we assume billing is already set up.
	const showBillingCard = context === 'setup-mc' || context === 'setup-ads';

	let description = createInterpolateElement(
		__(
			'Paid Performance Max campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
			'google-listings-and-ads'
		),
		{
			link: (
				<AppDocumentationLink
					context={ context }
					linkId="see-what-ads-look-like"
					href="https://support.google.com/google-ads/answer/6275294"
				/>
			),
		}
	);

	if ( isOnboardingFlow ) {
		description = __(
			'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your products will be included in the campaign after they’re approved.',
			'google-listings-and-ads'
		);
	}

	return (
		<StepContent>
			<StepContentHeader
				title={ headerTitle }
				description={ description }
			/>

			{ isOnboardingFlow && <PaidAdsFeaturesSection /> }

			<BudgetSection
				formProps={ formContext }
				countryCodes={
					context === 'edit-ads'
						? campaign?.displayCountries
						: countryCodes
				}
			>
				{ showBillingCard && <BillingCard /> }

				{ showCampaignPreviewCard && <CampaignPreviewCard /> }
			</BudgetSection>

			<StepContentFooter>
				<StepContentActions>
					{ typeof skipButton === 'function'
						? skipButton( formContext )
						: skipButton }

					{ typeof continueButton === 'function'
						? continueButton( formContext )
						: continueButton }
				</StepContentActions>

				<PaidAdsFaqsPanel />
			</StepContentFooter>
		</StepContent>
	);
}
