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
import PaidAdsSetupSections from './paid-ads-setup-sections';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * @typedef {import('.~/components/adaptive-form/adaptive-form-context').AdaptiveFormContext} AdaptiveFormContext
 */

/**
 * @typedef {import('.~/components/paid-ads/ads-campaign/paid-ads-setup-sections').PaidAdsData} PaidAdsData
 */

/**
 * Renders the container of the form content for campaign management.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 *
 * @fires gla_documentation_link_click with `{ context: 'create-ads' | 'edit-ads' | 'setup-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 * @param {Object} props React props.
 * @param {string} props.headerTitle The title of the step.
 * @param {boolean} [props.isOnboardingFlow=false] Whether this component is used in onboarding flow.
 * @param {'create-ads'|'edit-ads'|'setup-ads'} props.trackingContext A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 * @param {JSX|(PaidAdsData) =>JSX} [props.skipButton] A React element or function to render the "Skip" button. If a function is passed, it receives the paid ads data and returns the button element.
 * @param {JSX|(AdaptiveFormContext, PaidAdsData) =>JSX} [props.continueButton] A React element or function to render the "Continue" button. If a function is passed, it receives the form context and paid ads data and returns the button element. It handles submission logic in the form.
 */
export default function AdsCampaign( {
	headerTitle,
	isOnboardingFlow = false,
	trackingContext,
	skipButton,
	continueButton,
} ) {
	const formContext = useAdaptiveFormContext();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();

	let description = createInterpolateElement(
		__(
			'Paid Performance Max campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
			'google-listings-and-ads'
		),
		{
			link: (
				<AppDocumentationLink
					context={ trackingContext }
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

			<PaidAdsSetupSections
				countryCodes={ countryCodes }
				showCampaignPreviewCard={
					trackingContext === 'setup-ads' ||
					trackingContext === 'create-ads'
				}
				showBillingCard={
					// only show the billing card during onboarding or setup Ads flow.
					// For creating/editing a campaign, we assume billing is already set up.
					isOnboardingFlow || trackingContext === 'setup-ads'
				}
			/>

			<StepContentFooter>
				<StepContentActions>
					{ typeof skipButton === 'function'
						? skipButton()
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
