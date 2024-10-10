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
import StepContentActions from '.~/components/stepper/step-content-actions';
import AppDocumentationLink from '.~/components/app-documentation-link';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import AudienceSection from '../audience-section';
import BudgetSection from '../budget-section';
import { CampaignPreviewCard } from '../campaign-preview';
import PaidAdsFaqsPanel from '../faqs-panel';

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * Renders the container of the form content for campaign management.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @fires gla_documentation_link_click with `{ context: 'create-ads' | 'edit-ads' | 'setup-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {JSX.Element|Function} props.continueButton Continue button component.
 * @param {'create-ads'|'edit-ads'|'setup-ads'} props.trackingContext A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
export default function AdsCampaign( {
	campaign,
	continueButton,
	trackingContext,
} ) {
	const isCreation = ! campaign;
	const formContext = useAdaptiveFormContext();

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
				countryCodes={ formContext.values.countryCodes }
			>
				<CampaignPreviewCard />
			</BudgetSection>

			<StepContentFooter>
				<StepContentActions>
					{ typeof continueButton === 'function'
						? continueButton( {
								formProps: formContext,
						  } )
						: continueButton }
				</StepContentActions>
				<PaidAdsFaqsPanel />
			</StepContentFooter>
		</StepContent>
	);
}
