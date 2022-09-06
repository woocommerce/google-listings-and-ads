/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Form from '.~/components/form';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import { useAppDispatch } from '.~/data';
import CreateCampaignFormContent from '.~/components/paid-ads/create-campaign-form-content';
import validateForm from '.~/utils/paid-ads/validateForm';
import { getDashboardUrl } from '.~/utils/urls';

/**
 * @fires gla_launch_paid_campaign_button_click on submit
 * @fires gla_documentation_link_click with `{ context: 'create-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 */
const CreatePaidAdsCampaignForm = () => {
	const [ loading, setLoading ] = useState( false );
	const { createAdsCampaign } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: targetAudience } = useTargetAudienceFinalCountryCodes();

	const handleValidate = ( values ) => {
		return validateForm( values );
	};

	const handleSubmit = async ( values ) => {
		setLoading( true );

		try {
			const { amount, countryCodes } = values;

			recordEvent( 'gla_launch_paid_campaign_button_click', {
				audiences: countryCodes.join( ',' ),
				budget: amount,
			} );

			await createAdsCampaign( amount, countryCodes );

			createNotice(
				'success',
				__(
					'You’ve successfully created a paid campaign!',
					'google-listings-and-ads'
				)
			);
		} catch ( e ) {
			setLoading( false );
			return;
		}

		getHistory().push( getDashboardUrl() );
	};

	if ( ! targetAudience ) {
		return null;
	}

	return (
		<Form
			initialValues={ {
				amount: 0,
				countryCodes: targetAudience,
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
									'Paid Performance Max campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
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
