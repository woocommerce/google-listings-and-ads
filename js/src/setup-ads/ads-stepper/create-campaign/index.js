/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import AppDocumentationLink from '.~/components/app-documentation-link';
import FormContent from './form-content';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';

const CreateCampaign = ( props ) => {
	const { onContinue = () => {} } = props;
	const [ fetchCreateCampaign, { loading } ] = useApiFetchCallback();
	const { createNotice } = useDispatchCoreNotices();

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = async ( values ) => {
		try {
			const date = formatDate( 'Y-m-d', new Date() );

			await fetchCreateCampaign( {
				path: '/wc/gla/ads/campaigns',
				method: 'POST',
				data: {
					name: `Ads Campaign ${ date }`,
					amount: Number( values.amount ),
					country: values.country && values.country[ 0 ],
				},
			} );

			onContinue();
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to launch your ads campaign. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<StepContent>
			<StepContentHeader
				step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
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
								context="setup-ads"
								linkId="see-what-ads-look-like"
								href="https://support.google.com/google-ads/answer/6275294"
							/>
						),
					}
				) }
			/>
			<Form
				initialValues={ {
					amount: '',
					country: [],
				} }
				validate={ handleValidate }
				onSubmitCallback={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { handleSubmit } = formProps;

					return (
						<FormContent
							formProps={ formProps }
							submitButton={
								<AppButton
									isPrimary
									loading={ loading }
									onClick={ handleSubmit }
								>
									{ __(
										'Launch campaign',
										'google-listings-and-ads'
									) }
								</AppButton>
							}
						/>
					);
				} }
			</Form>
		</StepContent>
	);
};

export default CreateCampaign;
