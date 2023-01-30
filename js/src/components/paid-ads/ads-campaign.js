/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import AudienceSection from './audience-section';
import BudgetSection from './budget-section';
import { CampaignPreviewCard } from './campaign-preview';
import FaqsSection from './faqs-section';

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
 *
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {() => void} props.onContinue Callback called once continue button is clicked.
 * @param {'create-ads'|'edit-ads'|'setup-ads'} props.trackingContext A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
export default function AdsCampaign( {
	campaign,
	onContinue,
	trackingContext,
} ) {
	const isCreation = ! campaign;
	const formContext = useAdaptiveFormContext();
	const { isValidCampaign } = formContext.adapter;

	const disabledBudgetSection = ! formContext.values.countryCodes.length;
	const helperText = isCreation
		? __(
				'You can only choose from countries you’ve selected during product listings configuration.',
				'google-listings-and-ads'
		  )
		: __(
				'Once a campaign has been created, you cannot change the target country(s).',
				'google-listings-and-ads'
		  );

	return (
		<StepContent>
			<StepContentHeader
				title={
					isCreation
						? __(
								'Create your paid campaign',
								'google-listings-and-ads'
						  )
						: __(
								'Edit your paid campaign',
								'google-listings-and-ads'
						  )
				}
				description={ createInterpolateElement(
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
				) }
			/>
			<AudienceSection
				disabled={ ! isCreation }
				multiple={ isCreation || campaign.allowMultiple }
				countrySelectHelperText={ helperText }
				formProps={ formContext }
			/>
			<BudgetSection
				formProps={ formContext }
				disabled={ disabledBudgetSection }
			>
				<CampaignPreviewCard />
			</BudgetSection>
			<FaqsSection />
			<StepContentFooter>
				<AppButton
					isPrimary
					disabled={ ! isValidCampaign }
					onClick={ onContinue }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
}
