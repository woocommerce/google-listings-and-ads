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
import CreateCampaignFormContent from '.~/components/paid-ads/create-campaign-form-content';
import EditPaidAdsCampaignFormContent from '.~/components/paid-ads/edit-paid-ads-campaign-form-content';

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * Renders the form container for campaign management.
 *
 * Please note that this component relies on an AdaptiveForm's context, so it expects
 * a context provider component (`AdaptiveForm`) to existing in its parents.
 *
 * @fires gla_documentation_link_click with `{ context: 'create-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 * @fires gla_documentation_link_click with `{ context: 'edit-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 *
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {() => void} props.onContinue Callback called once continue button is clicked.
 */
export default function AdsCampaign( { campaign, onContinue } ) {
	const isCreation = ! campaign;
	const formContext = useAdaptiveFormContext();
	const { isValidForm } = formContext;

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
								context={
									isCreation ? 'create-ads' : 'edit-ads'
								}
								linkId="see-what-ads-look-like"
								href="https://support.google.com/google-ads/answer/6275294"
							/>
						),
					}
				) }
			/>
			{ isCreation ? (
				<CreateCampaignFormContent formProps={ formContext } />
			) : (
				<EditPaidAdsCampaignFormContent
					formProps={ formContext }
					allowMultiple={ campaign.allowMultiple }
				/>
			) }
			<StepContentFooter>
				<AppButton
					isPrimary
					disabled={ ! isValidForm }
					onClick={ onContinue }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
}
