/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import CreateCampaignFormContent from '.~/components/paid-ads/create-campaign-form-content';
import createCampaign from '.~/apis/createCampaign';
import validateForm from '.~/utils/paid-ads/validateForm';
import { recordLaunchPaidCampaignClickEvent } from '.~/utils/recordEvent';

const CreatePaidAdsCampaignForm = () => {
	const [ loading, setLoading ] = useState( false );
	const { fetchAdsCampaigns } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const handleValidate = ( values ) => {
		return validateForm( values );
	};

	const handleSubmit = async ( values ) => {
		setLoading( true );

		try {
			const { amount, country: countryArr } = values;
			const country = countryArr && countryArr[ 0 ];

			recordLaunchPaidCampaignClickEvent( amount, country );

			await createCampaign( amount, country );

			createNotice(
				'success',
				__(
					'You’ve successfully created a paid campaign!',
					'google-listings-and-ads'
				)
			);
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to launch your ads campaign. Please try again later.',
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
				amount: 0,
				country: [],
			} }
			validate={ handleValidate }
			onSubmit={ handleSubmit }
		>
			{ ( formProps ) => {
				const {
					isValidForm,
					handleSubmit: handleLaunchCampaignClick,
				} = formProps;

				return (
					<StepContent>
						<StepContentHeader
							title={ __(
								'Create your paid campaign',
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
											context="create-ads"
											linkId="see-what-ads-look-like"
											href="https://support.google.com/google-ads/answer/6275294"
										/>
									),
								}
							) }
						/>
						<CreateCampaignFormContent formProps={ formProps } />
						<StepContentFooter>
							<AppButton
								isPrimary
								disabled={ ! isValidForm }
								loading={ loading }
								onClick={ handleLaunchCampaignClick }
							>
								{ __(
									'Launch paid campaign',
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

export default CreatePaidAdsCampaignForm;
