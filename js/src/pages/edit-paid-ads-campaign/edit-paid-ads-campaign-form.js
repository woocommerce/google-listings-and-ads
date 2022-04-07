/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import EditPaidAdsCampaignFormContent from '.~/components/paid-ads/edit-paid-ads-campaign-form-content';
import AppButton from '.~/components/app-button';
import { useAppDispatch } from '.~/data';
import { getDashboardUrl } from '.~/utils/urls';
import validateForm from '.~/utils/paid-ads/validateForm';

const EditPaidAdsCampaignForm = ( props ) => {
	const { campaign } = props;
	const { amount, allowMultiple, displayCountries: countryCodes } = campaign;
	const [ loading, setLoading ] = useState( false );
	const { updateAdsCampaign } = useAppDispatch();

	const handleValidate = ( values ) => {
		return validateForm( values );
	};

	const handleSubmit = async ( values ) => {
		setLoading( true );

		try {
			await updateAdsCampaign( campaign.id, { amount: values.amount } );
		} catch ( e ) {
			setLoading( false );
			return;
		}

		getHistory().push( getDashboardUrl() );
	};

	return (
		<Form
			initialValues={ { amount, countryCodes } }
			validate={ handleValidate }
			onSubmit={ handleSubmit }
		>
			{ ( formProps ) => {
				const {
					isValidForm,
					handleSubmit: handleSaveChangesClick,
				} = formProps;

				return (
					<StepContent>
						<StepContentHeader
							title={ __(
								'Edit your paid campaign',
								'google-listings-and-ads'
							) }
							description={
								<>
									{ __(
										'Paid ad campaigns are automatically optimized for you by Google.',
										'google-listings-and-ads'
									) }
									<br />
									<AppDocumentationLink
										context="edit-ads"
										linkId="see-what-ads-look-like"
										href="https://support.google.com/google-ads/answer/6275294"
									>
										{ __(
											'See what your ads will look like.',
											'google-listings-and-ads'
										) }
									</AppDocumentationLink>
								</>
							}
						/>
						<EditPaidAdsCampaignFormContent
							formProps={ formProps }
							allowMultiple={ allowMultiple }
						/>
						<StepContentFooter>
							<AppButton
								isPrimary
								disabled={ ! isValidForm }
								loading={ loading }
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
