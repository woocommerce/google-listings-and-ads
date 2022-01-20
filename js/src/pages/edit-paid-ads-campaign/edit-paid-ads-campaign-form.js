/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { getNewPath, getHistory } from '@woocommerce/navigation';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import EditPaidAdsCampaignFormContent from './edit-paid-ads-campaign-form-content';
import AppButton from '.~/components/app-button';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import validateForm from '.~/utils/paid-ads/validateForm';

const EditPaidAdsCampaignForm = ( props ) => {
	const { campaign } = props;
	const [ loading, setLoading ] = useState( false );
	const { fetchAdsCampaigns } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const handleValidate = ( values ) => {
		return validateForm( values );
	};

	const handleSubmit = async ( values ) => {
		setLoading( true );

		try {
			await apiFetch( {
				path: `/wc/gla/ads/campaigns/${ campaign.id }`,
				method: 'PATCH',
				data: {
					amount: values.amount,
				},
			} );
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to update your paid ads campaign. Please try again later.',
					'google-listings-and-ads'
				)
			);
			setLoading( false );
			return;
		}

		await fetchAdsCampaigns();
		getHistory().push( getNewPath( {}, '/google/dashboard', {} ) );

		setLoading( false );
	};

	return (
		<Form
			initialValues={ {
				id: campaign.id,
				amount: campaign.amount,
				country: [ campaign.country ],
			} }
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
