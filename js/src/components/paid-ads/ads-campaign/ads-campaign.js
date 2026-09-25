/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepContent from '~/components/stepper/step-content';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import AppDocumentationLink from '~/components/app-documentation-link';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import BillingCard from '~/components/paid-ads/billing-card';
import BudgetSection from '../budget-section';
import { CampaignPreviewCard } from '../campaign-preview';
import Faqs from './faqs';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import EuRegulationsSection from '../eu-regulations-section';
import CyoIncentivePicker from './cyo-incentive-picker';

/**
 * @typedef {import('~/components/adaptive-form/adaptive-form-context').AdaptiveFormContext} AdaptiveFormContext
 */

/**
 * Renders the container of the form content for campaign management.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @fires gla_documentation_link_click with `{ context: 'create-ads' | 'edit-ads' | 'setup-ads' | 'setup-ads-only', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 * @param {Object} props React props.
 * @param {string} props.headerTitle The title of the step.
 * @param {'create-ads'|'edit-ads'|'setup-ads'|'setup-mc'|'setup-ads-only'} props.context A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 * @param {(formContext: AdaptiveFormContext) => JSX.Element | JSX.Element} [props.skipButton] A React element or function to render the "Skip" button. If a function is passed, it receives the form context and returns the button element.
 * @param {(formContext: AdaptiveFormContext) => JSX.Element | JSX.Element} [props.continueButton] A React element or function to render the "Continue" button. If a function is passed, it receives the form context and returns the button element.
 * @return {JSX.Element} The rendered component.
 */
export default function AdsCampaign( {
	headerTitle,
	context,
	skipButton,
	continueButton,
} ) {
	const formContext = useAdaptiveFormContext();
	const isOnboardingFlow =
		context === 'setup-mc' || context === 'setup-ads-only';
	const showCampaignPreviewCard =
		context === 'setup-ads' ||
		context === 'create-ads' ||
		context === 'edit-ads';
	// only show the billing card during onboarding or setup Ads flow.
	// For creating/editing a campaign, we assume billing is already set up.
	const showBillingCard =
		context === 'setup-mc' ||
		context === 'setup-ads' ||
		context === 'setup-ads-only';

	let description = createInterpolateElement(
		__(
			'Performance Max campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
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
		const subject =
			context === 'setup-ads-only'
				? __( 'services', 'google-listings-and-ads' )
				: __( 'products', 'google-listings-and-ads' );

		description = sprintf(
			/* translators: %s: products or services */
			__(
				'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your %s will be included in the campaign after they’re approved.',
				'google-listings-and-ads'
			),
			subject
		);
	}

	return (
		<StepContent>
			<StepContentHeader
				title={ headerTitle }
				description={ description }
			/>

			{ isOnboardingFlow && <PaidAdsFeaturesSection /> }

			<BudgetSection>
				{ showBillingCard && <BillingCard /> }
				{ showCampaignPreviewCard && <CampaignPreviewCard /> }
			</BudgetSection>

			{ isOnboardingFlow && <CyoIncentivePicker context={ context } /> }

			<EuRegulationsSection context={ context } />

			<StepContentFooter>
				<StepContentActions>
					{ typeof skipButton === 'function'
						? skipButton( formContext )
						: skipButton }

					{ typeof continueButton === 'function'
						? continueButton( formContext )
						: continueButton }
				</StepContentActions>
				<Faqs />
			</StepContentFooter>
		</StepContent>
	);
}
