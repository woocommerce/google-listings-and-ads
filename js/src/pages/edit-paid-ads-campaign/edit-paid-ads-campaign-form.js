/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import EditPaidAdsCampaignFormContent from './edit-paid-ads-campaign-form-content';
import AppButton from '.~/components/app-button';

const EditPaidAdsCampaignForm = ( props ) => {
	const { campaign } = props;

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmit = async ( values ) => {
		console.log( 'values:', values );
	};

	return (
		<Form
			initialValues={ {
				id: campaign.id,
				amount: campaign.amount,
				country: [ campaign.country ],
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmit }
		>
			{ ( formProps ) => {
				const { handleSubmit: handleSaveChangesClick } = formProps;

				return (
					<StepContent>
						<StepContentHeader
							title={ __(
								'Edit your paid campaign',
								'google-listings-and-ads'
							) }
							description={ createInterpolateElement(
								__(
									'Paid Smart Shopping campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
									'google-listings-and-ads'
								),
								{
									link: (
										<AppDocumentationLink
											context="edit-ads"
											linkId="see-what-ads-look-like"
											href="https://support.google.com/google-ads/answer/6275294"
										/>
									),
								}
							) }
						/>
						<EditPaidAdsCampaignFormContent
							formProps={ formProps }
						/>
						<StepContentFooter>
							<AppButton
								isPrimary
								onClick={ handleSaveChangesClick }
							>
								{ __(
									'Save changes',
									'google-listings-and-ads'
								) }
							</AppButton>
						</StepContentFooter>
					</StepContent>
				);
			} }
		</Form>
	);
};

export default EditPaidAdsCampaignForm;
